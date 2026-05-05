<?php
/**
 * upsert.php — Batch insert/update/soft-delete de reseñas
 * POST /api/google/reviews/upsert.php
 * Header: X-WSP-Token
 * Body JSON: { "locationId": "...", "operations": [{ "action": "insert|update|delete", "review": {...} }] }
 *
 * Procesa en una sola transacción. Devuelve resumen de cambios.
 * Agrega columna deleted_at si no existe (solo la primera vez).
 * Es idempotente: insert usa ON DUPLICATE KEY UPDATE.
 */

require_once __DIR__ . '/../auth.php';

verificarTokenGMB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hikErr('Método no permitido. Usar POST.', 405);
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !isset($body['operations']) || !is_array($body['operations'])) {
    hikErr('Body inválido. Se esperaba: { locationId, operations: [...] }');
}

$operations = $body['operations'];
$locationId = isset($body['locationId']) ? trim($body['locationId']) : '';

if (empty($locationId)) {
    hikErr('Falta locationId en el body.');
}

// ── Asegurar que deleted_at existe ───────────────────────────────────────────
try {
    $col = $conn->query("SHOW COLUMNS FROM ResenasGoogle LIKE 'deleted_at'")->fetch();
    if (!$col) {
        $conn->exec("ALTER TABLE ResenasGoogle ADD COLUMN deleted_at DATETIME DEFAULT NULL");
    }
} catch (Exception $e) {
    hikErr('Error al verificar schema: ' . $e->getMessage(), 500);
}

// ── Procesar batch en transacción ─────────────────────────────────────────────
$inserted = 0;
$updated  = 0;
$deleted  = 0;
$errors   = [];

try {
    $conn->beginTransaction();

    foreach ($operations as $i => $op) {
        $action = $op['action'] ?? '';
        $review = $op['review'] ?? [];

        if (!in_array($action, ['insert', 'update', 'delete'], true)) {
            $errors[] = "Operación $i: action inválido '$action'";
            continue;
        }

        $reviewId = $review['reviewId'] ?? '';
        if (empty($reviewId)) {
            $errors[] = "Operación $i: reviewId vacío";
            continue;
        }

        try {
            if ($action === 'insert') {
                // INSERT con ON DUPLICATE KEY → idempotente
                $stmt = $conn->prepare("
                    INSERT INTO ResenasGoogle (
                        locationId, locationName, reviewId, reviewerName,
                        starRating, comment, createTime, updateTime,
                        reviewReplyComment, reviewReplyUpdateTime, extractionDate,
                        deleted_at
                    ) VALUES (
                        :locationId, :locationName, :reviewId, :reviewerName,
                        :starRating, :comment, :createTime, :updateTime,
                        :reviewReplyComment, :reviewReplyUpdateTime, :extractionDate,
                        NULL
                    )
                    ON DUPLICATE KEY UPDATE
                        locationName          = VALUES(locationName),
                        reviewerName          = VALUES(reviewerName),
                        starRating            = VALUES(starRating),
                        comment               = VALUES(comment),
                        updateTime            = VALUES(updateTime),
                        reviewReplyComment    = VALUES(reviewReplyComment),
                        reviewReplyUpdateTime = VALUES(reviewReplyUpdateTime),
                        extractionDate        = VALUES(extractionDate),
                        deleted_at            = NULL
                ");
                $stmt->execute([
                    ':locationId'            => $locationId,
                    ':locationName'          => substr($review['locationName']          ?? '', 0, 50),
                    ':reviewId'              => substr($reviewId, 0, 100),
                    ':reviewerName'          => substr($review['reviewerName']          ?? '', 0, 100),
                    ':starRating'            => substr($review['starRating']            ?? '', 0, 20),
                    ':comment'               => substr($review['comment']               ?? '', 0, 3000),
                    ':createTime'            => substr($review['createTime']            ?? '', 0, 50),
                    ':updateTime'            => substr($review['updateTime']            ?? '', 0, 50),
                    ':reviewReplyComment'    => substr($review['reviewReplyComment']    ?? '', 0, 3000),
                    ':reviewReplyUpdateTime' => substr($review['reviewReplyUpdateTime'] ?? '', 0, 50),
                    ':extractionDate'        => substr($review['extractionDate']        ?? date('Y-m-d H:i:s'), 0, 50),
                ]);
                $inserted++;

            } elseif ($action === 'update') {
                $stmt = $conn->prepare("
                    UPDATE ResenasGoogle
                    SET
                        locationName          = :locationName,
                        reviewerName          = :reviewerName,
                        starRating            = :starRating,
                        comment               = :comment,
                        updateTime            = :updateTime,
                        reviewReplyComment    = :reviewReplyComment,
                        reviewReplyUpdateTime = :reviewReplyUpdateTime,
                        extractionDate        = :extractionDate,
                        deleted_at            = NULL
                    WHERE reviewId = :reviewId
                      AND locationId = :locationId
                ");
                $stmt->execute([
                    ':locationId'            => $locationId,
                    ':locationName'          => substr($review['locationName']          ?? '', 0, 50),
                    ':reviewerName'          => substr($review['reviewerName']          ?? '', 0, 100),
                    ':starRating'            => substr($review['starRating']            ?? '', 0, 20),
                    ':comment'               => substr($review['comment']               ?? '', 0, 3000),
                    ':updateTime'            => substr($review['updateTime']            ?? '', 0, 50),
                    ':reviewReplyComment'    => substr($review['reviewReplyComment']    ?? '', 0, 3000),
                    ':reviewReplyUpdateTime' => substr($review['reviewReplyUpdateTime'] ?? '', 0, 50),
                    ':extractionDate'        => date('Y-m-d H:i:s'),
                    ':reviewId'              => substr($reviewId, 0, 100),
                ]);
                $updated++;

            } elseif ($action === 'delete') {
                // Soft delete
                $stmt = $conn->prepare("
                    UPDATE ResenasGoogle
                    SET deleted_at = NOW()
                    WHERE reviewId    = :reviewId
                      AND locationId  = :locationId
                      AND deleted_at IS NULL
                ");
                $stmt->execute([
                    ':reviewId'   => substr($reviewId, 0, 100),
                    ':locationId' => $locationId,
                ]);
                $deleted++;
            }

        } catch (Exception $opErr) {
            $errors[] = "Operación $i ($action/$reviewId): " . $opErr->getMessage();
        }
    }

    $conn->commit();

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    hikErr('Error en transacción: ' . $e->getMessage(), 500);
}

hikOk([
    'locationId' => $locationId,
    'inserted'   => $inserted,
    'updated'    => $updated,
    'deleted'    => $deleted,
    'errors'     => $errors
]);

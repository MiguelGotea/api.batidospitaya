<?php
/**
 * crear.php — Crea una nueva reunión y devuelve el link de grabación
 * POST /api/resumen_reuniones_ia/crear.php
 * Header: X-Resumen-Token (ERP)
 *
 * Body JSON:
 *   titulo            string   requerido
 *   descripcion       string   opcional
 *   colaboradores     int[]    array de CodOperario
 *   creado_por        int      CodOperario del usuario que crea
 */

require_once __DIR__ . '/auth.php';

verificarTokenERP();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reunionErr('Método no permitido', 405);
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    reunionErr('Body JSON inválido o vacío', 400);
}

$titulo      = trim($body['titulo']      ?? '');
$descripcion = trim($body['descripcion'] ?? '');
$colaboradores = $body['colaboradores']  ?? [];
$creado_por  = intval($body['creado_por'] ?? 0);

if (empty($titulo)) {
    reunionErr('El campo "titulo" es requerido', 422);
}
if ($creado_por <= 0) {
    reunionErr('El campo "creado_por" es requerido y debe ser un CodOperario válido', 422);
}
if (!is_array($colaboradores)) {
    reunionErr('El campo "colaboradores" debe ser un array', 422);
}

// Sanitizar array de colaboradores (solo enteros positivos)
$colaboradores = array_values(array_filter(array_map('intval', $colaboradores), fn($v) => $v > 0));

try {
    // Generar token único de 48 caracteres
    $token = bin2hex(random_bytes(24));

    // Verificar colisión (extremadamente improbable, pero por seguridad)
    $check = $conn->prepare("SELECT id FROM resumen_reuniones_ia WHERE token = ?");
    $check->execute([$token]);
    if ($check->fetch()) {
        // Regenerar si hay colisión
        $token = bin2hex(random_bytes(24));
    }

    $stmt = $conn->prepare("
        INSERT INTO resumen_reuniones_ia
            (titulo, descripcion, colaboradores, creado_por, token, token_expira, estado, fecha_creacion)
        VALUES
            (:titulo, :descripcion, :colaboradores, :creado_por, :token,
             DATE_ADD(NOW(), INTERVAL 6 HOUR), 'creada', NOW())
    ");

    $stmt->execute([
        ':titulo'        => $titulo,
        ':descripcion'   => $descripcion ?: null,
        ':colaboradores' => json_encode($colaboradores),
        ':creado_por'    => $creado_por,
        ':token'         => $token,
    ]);

    $reunionId = (int) $conn->lastInsertId();

    $link = "https://reuniones.batidospitaya.com/?token={$token}";

    reunionOk([
        'reunion_id' => $reunionId,
        'token'      => $token,
        'link'       => $link,
    ]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] crear.php error: ' . $e->getMessage());
    reunionErr('Error interno al crear la reunión', 500);
}

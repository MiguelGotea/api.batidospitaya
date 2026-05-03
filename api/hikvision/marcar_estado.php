<?php
/**
 * marcar_estado.php — El worker actualiza el estado de un item de la cola
 * POST /api/hikvision/marcar_estado.php
 * Header: X-WSP-Token
 * Body JSON:
 *   { "id_cola": 5, "estado": "fallido", "error": "mensaje de error" }
 *   { "id_cola": 5, "estado": "procesando" }
 *
 * Estados permitidos vía este endpoint: procesando, fallido
 * (completado se registra vía registrar_resultado.php)
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$data    = json_decode(file_get_contents('php://input'), true);
$id_cola = isset($data['id_cola']) ? intval($data['id_cola']) : null;
$estado  = isset($data['estado'])  ? trim($data['estado'])   : null;
$error   = isset($data['error'])   ? trim($data['error'])    : null;

if (!$id_cola || !$estado) {
    hikErr('Faltan parámetros: id_cola, estado');
}

$estadosPermitidos = ['procesando', 'fallido'];
if (!in_array($estado, $estadosPermitidos)) {
    hikErr("Estado inválido. Permitidos: " . implode(', ', $estadosPermitidos));
}

try {
    $stmt = $conn->prepare("
        SELECT id, estado, intentos
        FROM hikvision_cola_analisis
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id_cola]);
    $item = $stmt->fetch();

    if (!$item) {
        hikErr("Item $id_cola no encontrado en la cola", 404);
    }

    $nuevoIntentos = $item['intentos'];
    if ($estado === 'fallido') {
        $nuevoIntentos++;
    }

    $upd = $conn->prepare("
        UPDATE hikvision_cola_analisis
        SET estado        = :estado,
            intentos      = :intentos,
            error_mensaje = :error,
            updated_at    = NOW()
        WHERE id = :id
    ");
    $upd->execute([
        ':estado'   => $estado,
        ':intentos' => $nuevoIntentos,
        ':error'    => $error,
        ':id'       => $id_cola,
    ]);

    hikOk([
        'id_cola'  => $id_cola,
        'estado'   => $estado,
        'intentos' => $nuevoIntentos,
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}

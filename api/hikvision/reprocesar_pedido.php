<?php
/**
 * reprocesar_pedido.php — Resetea un item fallido para que sea reintentado
 * POST /api/hikvision/reprocesar_pedido.php
 * Header: X-WSP-Token
 * Body JSON:
 *   { "id_cola": 5 }          → reprocesar por ID de cola
 *   { "cod_pedido": 12345, "local": "10" } → buscar y reprocesar por pedido
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$data      = json_decode(file_get_contents('php://input'), true);
$id_cola   = isset($data['id_cola'])   ? intval($data['id_cola'])   : null;
$cod_pedido = isset($data['cod_pedido']) ? intval($data['cod_pedido']) : null;
$local      = isset($data['local'])      ? trim($data['local'])        : null;

if (!$id_cola && (!$cod_pedido || !$local)) {
    hikErr('Proporciona id_cola, o cod_pedido+local');
}

try {
    // Buscar el item
    if ($id_cola) {
        $stmt = $conn->prepare("
            SELECT id, estado, cod_pedido, local_codigo
            FROM hikvision_cola_analisis WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id_cola]);
    } else {
        $stmt = $conn->prepare("
            SELECT id, estado, cod_pedido, local_codigo
            FROM hikvision_cola_analisis
            WHERE cod_pedido = :cp AND local_codigo = :lc
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([':cp' => $cod_pedido, ':lc' => $local]);
    }

    $item = $stmt->fetch();

    if (!$item) {
        hikErr('Item no encontrado en la cola', 404);
    }

    if ($item['estado'] === 'pendiente') {
        hikOk(['mensaje' => 'El item ya está pendiente, no necesita reproceso.', 'id_cola' => $item['id']]);
    }

    if ($item['estado'] === 'procesando') {
        hikErr('El item está siendo procesado actualmente. Espera a que termine.', 409);
    }

    // Reset a pendiente con prioridad 1 (urgente, ya que es un reproceso manual)
    $conn->prepare("
        UPDATE hikvision_cola_analisis
        SET estado        = 'pendiente',
            prioridad     = 1,
            error_mensaje = NULL,
            updated_at    = NOW()
        WHERE id = :id
    ")->execute([':id' => $item['id']]);

    hikOk([
        'reprocesado' => true,
        'id_cola'     => $item['id'],
        'cod_pedido'  => $item['cod_pedido'],
        'local'       => $item['local_codigo'],
        'estado_anterior' => $item['estado'],
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}

<?php
/**
 * ping.php — Endpoint de conectividad básica
 * GET /api/wsp/ping.php
 * Público — no requiere token
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status'  => 'ok',
    'servicio'=> 'Pitaya WSP API',
    'hora'    => date('Y-m-d H:i:s'),
    'version' => '1.0'
]);

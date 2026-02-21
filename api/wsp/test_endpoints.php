<?php
/**
 * TEST DE ENDPOINTS WSP — Verificar que los endpoints funcionan
 * Acceder en navegador: https://api.batidospitaya.com/api/wsp/test_endpoints.php
 *
 * NO dejar en producción permanentemente — eliminar o proteger después de las pruebas.
 */

header('Content-Type: text/html; charset=utf-8');

// Token de prueba (debe coincidir con WSP_TOKEN_SECRETO en auth.php)
$TOKEN = 'CAMBIAR_POR_TOKEN_SECRETO_SEGURO_32CHARS';
$BASE = 'https://api.batidospitaya.com';

function testGet($url, $headers = [])
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'error' => $err];
}

function testPost($url, $data, $headers = [])
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function mostrarResultado($nombre, $esperado, $real, $cuerpo)
{
    $ok = ($real == $esperado);
    $color = $ok ? '#d4edda' : '#f8d7da';
    $icon = $ok ? '✅' : '❌';
    echo "<tr style='background:$color'>
        <td>$icon $nombre</td>
        <td>$esperado</td>
        <td>$real</td>
        <td><small><pre>" . htmlspecialchars(substr($cuerpo, 0, 300)) . "</pre></small></td>
    </tr>";
}

$tokenHeader = ["X-WSP-Token: $TOKEN"];

// Ejecutar pruebas
$ping = testGet("$BASE/api/wsp/ping.php");
$status = testGet("$BASE/api/wsp/status.php");
$sinToken = testGet("$BASE/api/wsp/pendientes.php");
$conToken = testGet("$BASE/api/wsp/pendientes.php", $tokenHeader);
$registrar = testPost(
    "$BASE/api/wsp/registrar_sesion.php",
    ['estado' => 'desconectado', 'qr_base64' => null],
    $tokenHeader
);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>WSP API — Test de Endpoints</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }

        h1 {
            color: #0E544C;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #0E544C;
            color: white;
            padding: 10px;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        pre {
            margin: 0;
            font-size: 11px;
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <h1>🔌 Pitaya WSP — Test de Endpoints API</h1>
    <p><strong>Fecha:</strong>
        <?= date('Y-m-d H:i:s') ?>
    </p>

    <table>
        <thead>
            <tr>
                <th>Test</th>
                <th>HTTP Esperado</th>
                <th>HTTP Recibido</th>
                <th>Respuesta</th>
            </tr>
        </thead>
        <tbody>
            <?php mostrarResultado('GET /ping.php (público)', 200, $ping['code'], $ping['body']); ?>
            <?php mostrarResultado('GET /status.php (público)', 200, $status['code'], $status['body']); ?>
            <?php mostrarResultado('GET /pendientes.php SIN token (debe ser 401)', 401, $sinToken['code'], $sinToken['body']); ?>
            <?php mostrarResultado('GET /pendientes.php CON token (debe ser 200)', 200, $conToken['code'], $conToken['body']); ?>
            <?php mostrarResultado('POST /registrar_sesion.php CON token', 200, $registrar['code'], $registrar['body']); ?>
        </tbody>
    </table>

    <p style="margin-top:15px; color:#888; font-size:12px;">
        ⚠️ Eliminar este archivo después de las pruebas o protegerlo con IP whitelist.
    </p>
</body>

</html>
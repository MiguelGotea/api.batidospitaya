<?php
/**
 * enrolar.php — Extrae minucias de una imagen y las guarda en operarios.minucias_huella
 *
 * Método: POST
 * Header: X-Huella-Token: <HUELLA_TOKEN_ERP>
 * Body JSON:
 *   {
 *     "cod_operario": 123,
 *     "num_muestra": 1,        // 1, 2 o 3
 *     "imagen_b64": "..."      // bytes crudos 384x289 sin cabecera, en base64
 *   }
 *
 * Respuesta exitosa:
 *   {
 *     "success": true,
 *     "cod_operario": 123,
 *     "num_muestra": 1,
 *     "calidad": 85,
 *     "muestras_guardadas": [1, 2]  // qué muestras tiene ya este operario
 *   }
 */

require_once __DIR__ . '/auth.php';

verificarTokenHuellaERP();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    huellaErr('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$cod_operario = isset($input['cod_operario']) ? (int)$input['cod_operario'] : 0;
$num_muestra  = isset($input['num_muestra'])  ? (int)$input['num_muestra']  : 0;
$imagen_b64   = $input['imagen_b64'] ?? '';

// ── Validación básica ─────────────────────────────────────────
if ($cod_operario <= 0) {
    huellaErr('cod_operario inválido');
}
if ($num_muestra < 1 || $num_muestra > 3) {
    huellaErr('num_muestra debe ser 1, 2 o 3');
}
if (empty($imagen_b64)) {
    huellaErr('imagen_b64 es requerido');
}

// ── Verificar que el operario existe ─────────────────────────
$stmt = $conn->prepare("SELECT CodOperario, Nombre, Apellido, minucias_huella FROM Operarios WHERE CodOperario = ? AND Operativo = 1 LIMIT 1");
$stmt->execute([$cod_operario]);
$operario = $stmt->fetch();

if (!$operario) {
    huellaErr('Operario no encontrado o no está activo', 404);
}

// ── Llamar al VPS para extraer minucias ──────────────────────
try {
    $respuesta = llamarVPS('/api/enroll', [
        'imagen_b64'  => $imagen_b64,
        'cod_operario' => $cod_operario,
        'num_muestra' => $num_muestra,
    ]);
} catch (RuntimeException $e) {
    error_log("[lector_huella] enrolar.php cURL error: " . $e->getMessage());
    huellaErr('Error de comunicación con el servicio biométrico: ' . $e->getMessage(), 503);
}

if ($respuesta['http_code'] !== 200) {
    $detalle = $respuesta['body']['detail'] ?? 'Error desconocido';
    error_log("[lector_huella] enrolar.php VPS error {$respuesta['http_code']}: $detalle");
    huellaErr("El servicio biométrico retornó error: $detalle", 502);
}

$vps_data = $respuesta['body'];
$xyt      = $vps_data['xyt']     ?? '';
$calidad  = $vps_data['calidad'] ?? 0;

if (empty($xyt)) {
    huellaErr('El servicio biométrico no retornó minucias válidas');
}

// ── Actualizar minucias_huella en la BD ──────────────────────
// La columna es JSON: array de objetos {muestra, xyt, calidad, fecha}
$minucias_actuales = [];
if (!empty($operario['minucias_huella'])) {
    $minucias_actuales = json_decode($operario['minucias_huella'], true) ?? [];
}

// Reemplazar o agregar la muestra correspondiente
$nueva_muestra = [
    'muestra' => $num_muestra,
    'xyt'     => $xyt,
    'calidad' => $calidad,
    'fecha'   => date('Y-m-d H:i:s'),
];

// Filtrar la muestra anterior del mismo número
$minucias_actuales = array_values(array_filter(
    $minucias_actuales,
    fn($m) => ($m['muestra'] ?? 0) !== $num_muestra
));
$minucias_actuales[] = $nueva_muestra;

// Ordenar por número de muestra
usort($minucias_actuales, fn($a, $b) => ($a['muestra'] ?? 0) - ($b['muestra'] ?? 0));

$stmt_update = $conn->prepare("UPDATE Operarios SET minucias_huella = ? WHERE CodOperario = ?");
$stmt_update->execute([json_encode($minucias_actuales), $cod_operario]);

$muestras_guardadas = array_column($minucias_actuales, 'muestra');

error_log("[lector_huella] enrolar: operario=$cod_operario muestra=$num_muestra calidad=$calidad ✓");

huellaOk([
    'cod_operario'     => $cod_operario,
    'nombre'           => trim($operario['Nombre'] . ' ' . $operario['Apellido']),
    'num_muestra'      => $num_muestra,
    'calidad'          => $calidad,
    'muestras_guardadas' => $muestras_guardadas,
    'total_muestras'   => count($muestras_guardadas),
]);
?>

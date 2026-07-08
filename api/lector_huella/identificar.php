<?php
/**
 * identificar.php — Identifica a un colaborador a partir de una imagen de huella
 *
 * Flujo:
 *   1. Recibe imagen base64 del ERP
 *   2. Lee todas las minucias de operarios activos desde la BD
 *   3. Envía imagen + plantillas al VPS (FastAPI) para matching
 *   4. Retorna el colaborador identificado o "sin coincidencia"
 *
 * Método: POST
 * Header: X-Huella-Token: <HUELLA_TOKEN_ERP>
 * Body JSON:
 *   {
 *     "imagen_b64": "...",   // bytes crudos 384x289 sin cabecera, en base64
 *     "umbral": 40           // opcional, default 40
 *   }
 *
 * Respuesta exitosa (identificado):
 *   {
 *     "success": true,
 *     "identificado": true,
 *     "cod_operario": 123,
 *     "nombre": "Juan Pérez",
 *     "cargo": "Operario Producción",
 *     "score": 67,
 *     "calidad_probe": 85
 *   }
 *
 * Respuesta (sin coincidencia):
 *   {
 *     "success": true,
 *     "identificado": false,
 *     "score_maximo": 15,
 *     "umbral": 40
 *   }
 */

require_once __DIR__ . '/auth.php';

verificarTokenHuellaERP();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    huellaErr('Método no permitido', 405);
}

$input    = json_decode(file_get_contents('php://input'), true);
$imagen_b64 = $input['imagen_b64'] ?? '';
$umbral     = isset($input['umbral']) ? (int)$input['umbral'] : 40;

if (empty($imagen_b64)) {
    huellaErr('imagen_b64 es requerido');
}

// ── Obtener todas las plantillas enroladas de la BD ──────────
// Solo operarios activos con al menos 1 muestra guardada
$stmt = $conn->prepare("
    SELECT CodOperario, Nombre, Nombre2, Apellido, Apellido2, minucias_huella
    FROM Operarios
    WHERE Operativo = 1
      AND minucias_huella IS NOT NULL
      AND JSON_LENGTH(minucias_huella) > 0
");
$stmt->execute();
$operarios_con_minucias = $stmt->fetchAll();

if (empty($operarios_con_minucias)) {
    huellaOk([
        'identificado' => false,
        'razon'        => 'No hay colaboradores enrolados',
    ]);
}

// ── Construir lista de plantillas para el VPS ─────────────────
$plantillas = [];
$mapa_operarios = [];  // cod_operario → datos del operario

foreach ($operarios_con_minucias as $op) {
    $cod  = (int)$op['CodOperario'];
    $muestras = json_decode($op['minucias_huella'] ?? '[]', true) ?? [];

    $nombre_completo = trim(
        $op['Nombre'] . ' ' . $op['Nombre2'] . ' ' . $op['Apellido'] . ' ' . $op['Apellido2']
    );
    $mapa_operarios[$cod] = $nombre_completo;

    foreach ($muestras as $muestra) {
        if (!empty($muestra['xyt'])) {
            $plantillas[] = [
                'cod_operario' => $cod,
                'num_muestra'  => $muestra['muestra'] ?? 1,
                'xyt'          => $muestra['xyt'],
            ];
        }
    }
}

if (empty($plantillas)) {
    huellaOk([
        'identificado' => false,
        'razon'        => 'No hay plantillas de minucias válidas',
    ]);
}

// ── Obtener cargo del colaborador identificado ────────────────
function obtenerCargoOperario(int $cod_operario): string
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT nc.Nombre
        FROM AsignacionNivelesCargos anc
        JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
        WHERE anc.CodOperario = ?
          AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
          AND anc.Fecha <= CURDATE()
        ORDER BY anc.CodNivelesCargos DESC
        LIMIT 1
    ");
    $stmt->execute([$cod_operario]);
    $result = $stmt->fetch();
    return $result ? $result['Nombre'] : 'Sin cargo';
}

// ── Llamar al VPS para identificación ────────────────────────
try {
    $respuesta = llamarVPS('/api/identify', [
        'imagen_b64' => $imagen_b64,
        'plantillas' => $plantillas,
        'umbral'     => $umbral,
    ]);
} catch (RuntimeException $e) {
    error_log("[lector_huella] identificar.php cURL error: " . $e->getMessage());
    huellaErr('Error de comunicación con el servicio biométrico: ' . $e->getMessage(), 503);
}

if ($respuesta['http_code'] !== 200) {
    $detalle = $respuesta['body']['detail'] ?? 'Error desconocido';
    error_log("[lector_huella] identificar.php VPS error {$respuesta['http_code']}: $detalle");
    huellaErr("El servicio biométrico retornó error: $detalle", 502);
}

$vps = $respuesta['body'];

if (!($vps['identificado'] ?? false)) {
    huellaOk([
        'identificado' => false,
        'score_maximo' => $vps['score_maximo'] ?? 0,
        'umbral'       => $umbral,
        'calidad_probe' => $vps['calidad_probe'] ?? 0,
    ]);
}

$cod_identificado = (int)($vps['cod_operario'] ?? 0);
$nombre = $mapa_operarios[$cod_identificado] ?? 'Desconocido';
$cargo  = obtenerCargoOperario($cod_identificado);

error_log("[lector_huella] identificar: operario=$cod_identificado nombre='$nombre' score={$vps['score']} ✓");

huellaOk([
    'identificado'  => true,
    'cod_operario'  => $cod_identificado,
    'nombre'        => $nombre,
    'cargo'         => $cargo,
    'score'         => $vps['score'] ?? 0,
    'calidad_probe' => $vps['calidad_probe'] ?? 0,
]);
?>

<?php
/**
 * buscar.php — Búsqueda fuzzy de tareas por palabras clave
 * POST: cod_operario, palabras_clave (string o array), estado (opcional)
 * Devuelve hasta 5 tareas. Si >5 pide más detalle.
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respuestaError('Método no permitido', 405);

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$codOperario = (int)($body['cod_operario'] ?? 0);
$palabrasRaw = $body['palabras_clave']    ?? '';
$estado      = trim($body['estado']       ?? '');

if (!$codOperario) {
    respuestaError('Datos incompletos: se requiere cod_operario');
}

// Normalizar palabras clave
if (is_array($palabrasRaw)) {
    $palabras = implode(' ', $palabrasRaw);
} else {
    $palabras = trim((string)$palabrasRaw);
}

// Construir condición LIKE con múltiples palabras
$terminos = array_filter(explode(' ', $palabras), fn($t) => strlen($t) >= 3);
$likeCondicion = '1=1';
$params = [':codOperario' => $codOperario];

if (!empty($terminos)) {
    $partes = [];
    foreach ($terminos as $i => $termino) {
        $key = ":kw$i";
        $partes[] = "titulo LIKE $key";
        $params[$key] = '%' . $termino . '%';
    }
    $likeCondicion = '(' . implode(' OR ', $partes) . ')';
}

// Filtrar por estado si viene
$estadoCondicion = '';
if (!empty($estado) && in_array($estado, ['en_progreso', 'solicitado', 'finalizado', 'cancelado'])) {
    $estadoCondicion = "AND estado = :estado";
    $params[':estado'] = $estado;
} else {
    // Por defecto: tareas activas
    $estadoCondicion = "AND estado IN ('solicitado', 'en_progreso')";
}

try {
    $stmt = $conn->prepare("
        SELECT id, tipo, titulo, descripcion, estado, fecha_meta, prioridad,
               DATEDIFF(fecha_meta, CURDATE()) AS dias_restantes
        FROM gestion_tareas_reuniones_items
        WHERE cod_operario_creador = :codOperario
          AND tipo = 'tarea'
          AND $likeCondicion
          $estadoCondicion
        ORDER BY fecha_meta ASC
        LIMIT 6
    ");
    $stmt->execute($params);
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($tareas);

    if ($total === 0) {
        respuestaOk([
            'data'      => [],
            'total'     => 0,
            'demasiados'=> false,
            'message'   => 'No se encontraron tareas que coincidan con la búsqueda.'
        ]);
        return;
    }

    if ($total > 5) {
        respuestaOk([
            'data'      => [],
            'total'     => $total,
            'demasiados'=> true,
            'message'   => 'Hay muchos resultados, sé más específico en tu búsqueda.'
        ]);
        return;
    }

    respuestaOk([
        'data'      => $tareas,
        'total'     => $total,
        'demasiados'=> false,
        'message'   => "Se encontraron $total tarea(s)."
    ]);
} catch (Exception $e) {
    error_log('Error tareas/buscar.php: ' . $e->getMessage());
    respuestaError('Error interno al buscar tareas', 500);
}

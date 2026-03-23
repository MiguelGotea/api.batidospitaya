<?php
/**
 * buscar.php — Busca reuniones del operario por titulo, fecha o participante
 *
 * POST { cod_operario, palabras_clave?, fecha? }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respuestaError('Metodo no permitido', 405);

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$codOperario = (int)($body['cod_operario']   ?? 0);
$keywords    = trim($body['palabras_clave']  ?? '');
$fecha       = trim($body['fecha']           ?? '');

if (!$codOperario) respuestaError('cod_operario requerido');

try {
    $params = [':cod' => $codOperario, ':tipo' => 'reunion'];
    $where  = ['r.tipo = :tipo', 'r.estado != "cancelado"'];

    // Filtro por titulo
    if ($keywords) {
        $palabras = array_filter(array_unique(explode(' ', $keywords)));
        $condsPalabra = [];
        $i = 0;
        foreach ($palabras as $p) {
            $key = ':kw' . $i++;
            $condsPalabra[] = "r.titulo LIKE $key";
            $params[$key] = '%' . $p . '%';
        }
        if ($condsPalabra) $where[] = '(' . implode(' OR ', $condsPalabra) . ')';
    }

    // Filtro por fecha
    if ($fecha && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $where[]        = 'r.fecha_meta = :fecha';
        $params[':fecha'] = $fecha;
    }

    $whereSql = implode(' AND ', $where);

    $stmt = $conn->prepare("
        SELECT
            r.id, r.titulo, r.descripcion,
            r.fecha_meta, r.hora_inicio, r.duracion_min, r.lugar, r.estado,
            r.ics_uid, r.ics_sequence,
            GROUP_CONCAT(
                CONCAT(TRIM(o.Nombre), ' ', TRIM(o.Apellido))
                ORDER BY o.Nombre SEPARATOR ', '
            ) AS participantes_nombres
        FROM gestion_tareas_reuniones_items r
        LEFT JOIN gestion_tareas_reuniones_participantes grp ON grp.id_item = r.id
        LEFT JOIN AsignacionNivelesCargos anc
               ON anc.CodNivelesCargos = grp.cod_cargo
              AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        LEFT JOIN Operarios o ON o.CodOperario = anc.CodOperario
        WHERE $whereSql
          AND (
              r.cod_operario_creador = :cod
              OR anc.CodOperario = :cod
          )
        GROUP BY r.id
        ORDER BY r.fecha_meta ASC, r.hora_inicio ASC
        LIMIT 6
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $demasiados = count($rows) >= 6;
    if ($demasiados) $rows = array_slice($rows, 0, 5);

    respuestaOk([
        'data'       => $rows,
        'total'      => count($rows),
        'demasiados' => $demasiados
    ]);

} catch (Exception $e) {
    error_log('Error reuniones/buscar.php: ' . $e->getMessage());
    respuestaError('Error buscando reuniones', 500);
}

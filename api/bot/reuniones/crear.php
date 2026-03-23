<?php
/**
 * crear.php — Crea una reunion, registra participantes y envia invitaciones ICS
 *
 * POST {
 *   cod_operario, titulo, descripcion, fecha (Y-m-d), hora (H:i),
 *   duracion_min, lugar,
 *   participantes: [{ cod_cargo, cod_operario, nombre_completo, email }]
 * }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';
require_once __DIR__ . '/../../../core/email/EmailService.php';

verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respuestaError('Metodo no permitido', 405);

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$codOperario = (int)($body['cod_operario'] ?? 0);
$codCargo    = (int)($body['cod_cargo']    ?? 0);
$titulo      = trim($body['titulo']       ?? '');
$descripcion = trim($body['descripcion']  ?? '');
$fecha       = trim($body['fecha']        ?? '');
$hora        = trim($body['hora']         ?? '09:00');
$duracion    = (int)($body['duracion_min'] ?? 60);
$lugar       = trim($body['lugar']        ?? 'Presencial');
$participantes = $body['participantes']   ?? [];

if (!$codOperario || !$codCargo || !$titulo || !$fecha) {
    respuestaError('Se requiere cod_operario, cod_cargo, titulo y fecha');
}

$dtFecha = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$dtFecha) respuestaError('Formato de fecha invalido. Use Y-m-d');
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) $hora = '09:00';

$icsUid = 'pb-' . uniqid('', true) . '@batidospitaya.com';
$fechaReunion = "$fecha $hora:00";

// Construir INSERT con los nuevos requerimientos
$cols   = ['tipo','titulo','descripcion','cod_cargo_creador','cod_cargo_asignado','cod_operario_creador','fecha_reunion','estado','fecha_creacion'];
$vals   = ["'reunion'",' :titulo',':desc',':codCargo',':codCargo',':codOp',':fechaR',"'en_progreso'",'CONVERT_TZ(NOW(), \'+00:00\', \'-06:00\')'];
$params = [
    ':titulo'   => $titulo,
    ':desc'     => $descripcion ?: null,
    ':codCargo' => $codCargo,
    ':codOp'    => $codOperario,
    ':fechaR'   => $fechaReunion
];

// Columnas opcionales que podrian no existir aun
function columnExists($conn, $tabla, $columna) {
    $r = $conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
    return $r && $r->rowCount() > 0;
}

$tieneDuracion = columnExists($conn, 'gestion_tareas_reuniones_items', 'duracion_min');
$tieneLugar    = columnExists($conn, 'gestion_tareas_reuniones_items', 'lugar');
$tieneSequence = columnExists($conn, 'gestion_tareas_reuniones_items', 'ics_sequence');
$tieneUid      = columnExists($conn, 'gestion_tareas_reuniones_items', 'ics_uid');

if ($tieneDuracion) { $cols[] = 'duracion_min'; $vals[] = ':dur';   $params[':dur']   = $duracion; }
if ($tieneLugar)    { $cols[] = 'lugar';        $vals[] = ':lugar'; $params[':lugar'] = $lugar ?: 'Presencial'; }
if ($tieneSequence) { $cols[] = 'ics_sequence'; $vals[] = '0'; }
if ($tieneUid)      { $cols[] = 'ics_uid';      $vals[] = ':uid';   $params[':uid']   = $icsUid; }

$sqlCols = implode(', ', $cols);
$sqlVals = implode(', ', $vals);

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO gestion_tareas_reuniones_items ($sqlCols) VALUES ($sqlVals)");
    $stmt->execute($params);
    $idReunion = $conn->lastInsertId();

    // Insertar participantes
    $stmtPart = $conn->prepare("
        INSERT IGNORE INTO gestion_tareas_reuniones_participantes
            (id_item, cod_cargo, confirmacion)
        VALUES (:id_item, :cod_cargo, 'pendiente')
    ");
    foreach ($participantes as $p) {
        if (!empty($p['cod_cargo'])) {
            $stmtPart->execute([':id_item' => $idReunion, ':cod_cargo' => (int)$p['cod_cargo']]);
        }
    }

    $conn->commit();

    // Enviar invitaciones ICS
    $emailService = new EmailService($conn);
    $enviados = [];
    $errores  = [];

    foreach ($participantes as $p) {
        if (empty($p['email'])) continue;
        $res = $emailService->enviarInvitacionCalendario(
            $codOperario,
            $p['email'],
            $p['nombre_completo'] ?? 'Participante',
            $titulo,
            $descripcion ?: $titulo,
            $fecha,
            $hora,
            $duracion,
            $lugar
        );
        if ($res['success']) $enviados[] = $p['nombre_completo'] ?? $p['email'];
        else $errores[] = $p['email'];
    }

    respuestaOk([
        'data' => [
            'id'           => $idReunion,
            'ics_uid'      => $icsUid,
            'titulo'       => $titulo,
            'fecha'        => $fecha,
            'hora'         => $hora,
            'duracion_min' => $duracion,
            'lugar'        => $lugar,
            'enviados'     => $enviados,
            'errores'      => $errores,
        ],
        'message' => "Reunion '$titulo' creada. Invitaciones enviadas a " . count($enviados) . " participante(s)."
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Error reuniones/crear.php: ' . $e->getMessage());
    respuestaError('Error al crear la reunion: ' . $e->getMessage(), 500);
}

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
$titulo      = trim($body['titulo']       ?? '');
$descripcion = trim($body['descripcion']  ?? '');
$fecha       = trim($body['fecha']        ?? ''); // Y-m-d
$hora        = trim($body['hora']         ?? '09:00'); // H:i
$duracion    = (int)($body['duracion_min'] ?? 60);
$lugar       = trim($body['lugar']        ?? 'Presencial');
$participantes = $body['participantes']   ?? []; // [{cod_cargo, cod_operario, nombre_completo, email}]

if (!$codOperario || !$titulo || !$fecha) {
    respuestaError('Se requiere cod_operario, titulo y fecha');
}

// Validar fecha
$dtFecha = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$dtFecha) respuestaError('Formato de fecha invalido. Use Y-m-d');

// Validar hora
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) $hora = '09:00';

$icsUid = 'pb-' . uniqid('', true) . '@batidospitaya.com';

try {
    $conn->beginTransaction();

    // 1. Insertar en gestion_tareas_reuniones_items
    $stmt = $conn->prepare("
        INSERT INTO gestion_tareas_reuniones_items
            (tipo, titulo, descripcion, cod_operario_creador, fecha_meta,
             hora_inicio, duracion_min, lugar, estado, ics_uid,
             fecha_creacion)
        VALUES
            ('reunion', :titulo, :desc, :codOp, :fecha,
             :hora, :dur, :lugar, 'en_progreso', :uid,
             CONVERT_TZ(NOW(), '+00:00', '-06:00'))
    ");
    $stmt->execute([
        ':titulo' => $titulo,
        ':desc'   => $descripcion ?: null,
        ':codOp'  => $codOperario,
        ':fecha'  => $fecha,
        ':hora'   => $hora,
        ':dur'    => $duracion,
        ':lugar'  => $lugar ?: 'Presencial',
        ':uid'    => $icsUid,
    ]);
    $idReunion = $conn->lastInsertId();

    // 2. Insertar participantes
    $stmtPart = $conn->prepare("
        INSERT IGNORE INTO gestion_tareas_reuniones_participantes
            (id_item, cod_cargo, confirmacion)
        VALUES (:id_item, :cod_cargo, 'pendiente')
    ");
    foreach ($participantes as $p) {
        if (!empty($p['cod_cargo'])) {
            $stmtPart->execute([
                ':id_item'   => $idReunion,
                ':cod_cargo' => (int)$p['cod_cargo']
            ]);
        }
    }

    $conn->commit();

    // 3. Enviar invitaciones ICS
    $emailService = new EmailService($conn);
    $enviados = [];
    $errores  = [];

    foreach ($participantes as $p) {
        if (empty($p['email'])) continue;
        $resultado = $emailService->enviarInvitacionCalendario(
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
        if ($resultado['success']) {
            $enviados[] = $p['nombre_completo'] ?? $p['email'];
        } else {
            $errores[] = $p['email'];
        }
    }

    respuestaOk([
        'data' => [
            'id'          => $idReunion,
            'ics_uid'     => $icsUid,
            'titulo'      => $titulo,
            'fecha'       => $fecha,
            'hora'        => $hora,
            'duracion_min'=> $duracion,
            'lugar'       => $lugar,
            'enviados'    => $enviados,
            'errores'     => $errores,
        ],
        'message' => "Reunion '$titulo' creada. Invitaciones enviadas a " . count($enviados) . " participante(s)."
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Error reuniones/crear.php: ' . $e->getMessage());
    respuestaError('Error interno al crear la reunion: ' . $e->getMessage(), 500);
}

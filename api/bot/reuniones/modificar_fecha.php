<?php
/**
 * modificar_fecha.php — Cambia la fecha/hora de una reunion y reenvía ICS actualizado
 *
 * POST { id_reunion, cod_operario, nueva_fecha, nueva_hora? }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';
require_once __DIR__ . '/../../../core/email/EmailService.php';

verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respuestaError('Metodo no permitido', 405);

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$idReunion   = (int)($body['id_reunion']   ?? 0);
$codOperario = (int)($body['cod_operario'] ?? 0);
$nuevaFecha  = trim($body['nueva_fecha']   ?? '');
$nuevaHora   = trim($body['nueva_hora']    ?? '');

if (!$idReunion || !$codOperario || !$nuevaFecha) {
    respuestaError('Se requiere id_reunion, cod_operario y nueva_fecha');
}

$dtFecha = DateTime::createFromFormat('Y-m-d', $nuevaFecha);
if (!$dtFecha) respuestaError('Formato de fecha invalido. Use Y-m-d');

try {
    // Verificar que existe y que el operario es el organizador
    $stmt = $conn->prepare("
        SELECT id, titulo, descripcion, fecha_reunion, duracion_min, lugar, ics_sequence, estado
        FROM gestion_tareas_reuniones_items
        WHERE id = :id AND cod_operario_creador = :cod AND tipo = 'reunion'
    ");
    $stmt->execute([':id' => $idReunion, ':cod' => $codOperario]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) respuestaError('Reunion no encontrada o no eres el organizador');
    if ($reunion['estado'] === 'cancelado') respuestaError('La reunion ya fue cancelada');

    $nuevaSequence = (int)$reunion['ics_sequence'] + 1;
    $horaFinal     = $nuevaHora ?: date('H:i', strtotime($reunion['fecha_reunion']));
    $nuevaFechaR   = "$nuevaFecha $horaFinal:00";

    // Actualizar fecha_reunion y sequence
    $upd = $conn->prepare("
        UPDATE gestion_tareas_reuniones_items
        SET fecha_reunion = :fechaR, ics_sequence = :seq
        WHERE id = :id
    ");
    $upd->execute([
        ':fechaR' => $nuevaFechaR,
        ':seq'    => $nuevaSequence,
        ':id'     => $idReunion,
    ]);

    // Reenviar ICS a todos los participantes
    $stmtParts = $conn->prepare("
        SELECT o.email_trabajo AS email,
               CONCAT(TRIM(o.Nombre), ' ', TRIM(o.Apellido)) AS nombre_completo
        FROM gestion_tareas_reuniones_participantes grp
        INNER JOIN AsignacionNivelesCargos anc
               ON anc.CodNivelesCargos = grp.cod_cargo
              AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        INNER JOIN Operarios o ON o.CodOperario = anc.CodOperario
        WHERE grp.id_item = :id AND o.email_trabajo IS NOT NULL
    ");
    $stmtParts->execute([':id' => $idReunion]);
    $parts = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

    $emailService = new EmailService($conn);
    foreach ($parts as $p) {
        $emailService->enviarInvitacionCalendario(
            $codOperario,
            $p['email'],
            $p['nombre_completo'],
            $reunion['titulo'],
            $reunion['descripcion'] ?? $reunion['titulo'],
            $nuevaFecha,
            $horaFinal ?: '09:00',
            (int)$reunion['duracion_min'],
            $reunion['lugar'] ?? 'Presencial'
        );
    }

    $fechaLegible = $dtFecha->format('d/m/Y');
    respuestaOk([
        'message' => "Fecha de la reunion '{$reunion['titulo']}' actualizada al $fechaLegible. ICS reenviado a " . count($parts) . " participante(s)."
    ]);

} catch (Exception $e) {
    error_log('Error reuniones/modificar_fecha.php: ' . $e->getMessage());
    respuestaError('Error interno', 500);
}

<?php
/**
 * cancelar.php — Cancela una reunion y envia ICS METHOD:CANCEL
 *
 * POST { id_reunion, cod_operario }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';
require_once __DIR__ . '/../../../core/email/EmailService.php';

verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respuestaError('Metodo no permitido', 405);

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$idReunion   = (int)($body['id_reunion']   ?? 0);
$codOperario = (int)($body['cod_operario'] ?? 0);

if (!$idReunion || !$codOperario) respuestaError('Se requiere id_reunion y cod_operario');

try {
    $stmt = $conn->prepare("
        SELECT id, titulo, descripcion, fecha_reunion,
               duracion_min, lugar, estado, ics_uid, ics_sequence
        FROM gestion_tareas_reuniones_items
        WHERE id = :id AND cod_operario_creador = :cod AND tipo = 'reunion'
    ");
    $stmt->execute([':id' => $idReunion, ':cod' => $codOperario]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) respuestaError('Reunion no encontrada o no eres el organizador');
    if ($reunion['estado'] === 'cancelado') respuestaError('La reunion ya estaba cancelada');

    // Cambiar estado
    $conn->prepare("
        UPDATE gestion_tareas_reuniones_items
        SET estado = 'cancelado', ics_sequence = ics_sequence + 1
        WHERE id = :id
    ")->execute([':id' => $idReunion]);

    // Enviar ICS de cancelacion a todos los participantes
    // Usamos el mismo metodo; el asunto lleva [CANCELADO]
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
            '[CANCELADO] ' . $reunion['titulo'],
            'Esta reunion ha sido cancelada.',
            date('Y-m-d', strtotime($reunion['fecha_reunion'])),
            date('H:i', strtotime($reunion['fecha_reunion'])),
            (int)$reunion['duracion_min'],
            $reunion['lugar'] ?? 'Presencial'
        );
    }

    respuestaOk([
        'message' => "Reunion '{$reunion['titulo']}' cancelada. Notificacion enviada a " . count($parts) . " participante(s)."
    ]);

} catch (Exception $e) {
    error_log('Error reuniones/cancelar.php: ' . $e->getMessage());
    respuestaError('Error interno', 500);
}

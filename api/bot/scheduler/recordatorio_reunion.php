<?php
/**
 * recordatorio_reunion.php — Reuniones que comienzan en 55-65 minutos.
 *
 * GET — retorna [{ celular, mensaje }]
 * Llamado cada 15 min por el cron.
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

try {
    $stmtCron = $conn->prepare("SELECT activo FROM bot_crons_config WHERE clave = 'recordatorio_reunion' LIMIT 1");
    $stmtCron->execute();
    $cron = $stmtCron->fetch(PDO::FETCH_ASSOC);
    if ($cron && !$cron['activo']) {
        respuestaOk(['data' => [], 'motivo' => 'cron desactivado']);
    }
} catch (Exception $e) { /* tabla aún no creada */ }

$ejecutar = (isset($_GET['ejecutar']) && $_GET['ejecutar'] == 1);


// Reuniones que inician en 55-65 minutos
$stmt = $conn->prepare("
    SELECT r.id, r.titulo, r.descripcion, r.fecha_reunion,
           TIME_FORMAT(r.fecha_reunion, '%h:%i %p') AS hora_formato,
           TIMESTAMPDIFF(MINUTE, NOW(), r.fecha_reunion) AS minutos_restantes
    FROM gestion_tareas_reuniones_items r
    WHERE r.tipo = 'reunion'
    AND r.estado NOT IN ('cancelado', 'finalizado')
    AND TIMESTAMPDIFF(MINUTE, NOW(), r.fecha_reunion) BETWEEN 55 AND 65
");
$stmt->execute();
$reuniones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultados = [];

foreach ($reuniones as $reunion) {
    // Obtener participantes con teléfono
    $stmtPart = $conn->prepare("
        SELECT DISTINCT o.Nombre, o.Apellido, o.telefono_corporativo
        FROM gestion_tareas_reuniones_participantes p
        INNER JOIN AsignacionNivelesCargos anc ON anc.CodNivelesCargos = p.cod_cargo
        INNER JOIN Operarios o ON o.CodOperario = anc.CodOperario
        INNER JOIN Contratos c ON c.cod_operario = o.CodOperario AND c.Finalizado = 0
        INNER JOIN tools_erp_permisos tep ON tep.cod_operario = o.CodOperario
        INNER JOIN tools_erp te ON te.id = tep.id_herramienta AND te.slug = 'pitayabot'
        WHERE p.id_item = ?
        AND o.telefono_corporativo IS NOT NULL
    ");
    $stmtPart->execute([$reunion['id']]);
    $participantes = $stmtPart->fetchAll(PDO::FETCH_ASSOC);

    if (empty($participantes)) continue;

    $nombresParticipantes = implode(', ', array_map(
        fn($p) => trim($p['Nombre'] . ' ' . $p['Apellido']),
        $participantes
    ));

    $minutos = (int)$reunion['minutos_restantes'];
    $mensaje = "⏰ *Recordatorio — en ~1 hora*\n\n" .
               "📌 {$reunion['titulo']}\n" .
               "🕙 {$reunion['hora_formato']} (en {$minutos} minutos)\n" .
               "👥 Con: {$nombresParticipantes}\n\n" .
               "¡Prepárate con tiempo!";

    foreach ($participantes as $part) {
        $resultados[] = [
            'celular' => $part['telefono_corporativo'],
            'mensaje' => $mensaje
        ];

        // Envío real si se solicita
        if ($ejecutar) {
            enviarMensajeWsp($op['telefono_corporativo'], $mensaje);
            usleep(2000000); // Anti-ban: esperar 2 segundos entre envíos
        }
    }
}

// Solo actualizar marca de tiempo si fue una ejecución real
if ($ejecutar) {
    try {
        $conn->prepare("UPDATE bot_crons_config SET ultima_ejecucion = NOW() WHERE clave = 'recordatorio_reunion'")->execute();
    } catch (Exception $e) {}
}

respuestaOk(['data' => $resultados, 'ejecutado' => $ejecutar]);

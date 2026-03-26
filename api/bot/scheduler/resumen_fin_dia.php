<?php
/**
 * resumen_fin_dia.php — Resumen de tareas del día al cierre (6 PM).
 *
 * GET — retorna [{ celular, mensaje }]
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

try {
    $stmtCron = $conn->prepare("SELECT activo FROM bot_crons_config WHERE clave = 'resumen_fin_dia' LIMIT 1");
    $stmtCron->execute();
    $cron = $stmtCron->fetch(PDO::FETCH_ASSOC);
    if ($cron && !$cron['activo']) {
        respuestaOk(['data' => [], 'motivo' => 'cron desactivado']);
    }
} catch (Exception $e) { /* tabla aún no creada */ }

try { $conn->prepare("UPDATE bot_crons_config SET ultima_ejecucion = NOW() WHERE clave = 'resumen_fin_dia'")->execute(); } catch (Exception $e) {}


$hoy = date('Y-m-d');

$stmtOps = $conn->prepare("
    SELECT DISTINCT o.CodOperario, o.Nombre, o.telefono_corporativo
    FROM Operarios o
    INNER JOIN Contratos c ON c.cod_operario = o.CodOperario AND c.Finalizado = 0
    WHERE o.telefono_corporativo IS NOT NULL AND o.Operativo = 1 AND o.bot_activo = 1
");
$stmtOps->execute();
$operarios = $stmtOps->fetchAll(PDO::FETCH_ASSOC);

$resultados = [];

foreach ($operarios as $op) {
    $codOp = $op['CodOperario'];

    $stmtRes = $conn->prepare("
        SELECT
            SUM(CASE WHEN estado = 'finalizado' THEN 1 ELSE 0 END) AS completadas,
            SUM(CASE WHEN estado IN ('solicitado','en_progreso') THEN 1 ELSE 0 END) AS pendientes
        FROM gestion_tareas_reuniones_items
        WHERE tipo = 'tarea' AND DATE(fecha_meta) = ? AND cod_operario_creador = ?
    ");
    $stmtRes->execute([$hoy, $codOp]);
    $stats = $stmtRes->fetch(PDO::FETCH_ASSOC);

    $completadas = (int)($stats['completadas'] ?? 0);
    $pendientes  = (int)($stats['pendientes'] ?? 0);

    if ($completadas === 0 && $pendientes === 0) continue;

    $emoji     = $pendientes === 0 ? '🎉' : ($pendientes <= 2 ? '😊' : '😐');
    $cierre    = $pendientes === 0
        ? "¡Completaste todo tu plan del día! Buen trabajo. 🏆"
        : "Quedan $pendientes tarea(s) para mañana. ¡Mañana más!";

    $mensaje = "📊 *Resumen de tu día — " . date('d/m') . "*\n\n" .
               "✅ Completadas: $completadas\n" .
               "⏳ Pendientes: $pendientes\n\n" .
               "$emoji $cierre";

    $resultados[] = ['celular' => $op['telefono_corporativo'], 'mensaje' => $mensaje];
}

respuestaOk(['data' => $resultados]);

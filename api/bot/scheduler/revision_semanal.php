<?php
/**
 * revision_semanal.php — Resumen narrativo de la semana generado por Gemini (Viernes 5 PM).
 *
 * GET — retorna [{ celular, mensaje }]
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

try {
    $stmtCron = $conn->prepare("SELECT activo FROM bot_crons_config WHERE clave = 'revision_semanal' LIMIT 1");
    $stmtCron->execute();
    $cron = $stmtCron->fetch(PDO::FETCH_ASSOC);
    if ($cron && !$cron['activo']) {
        respuestaOk(['data' => [], 'motivo' => 'cron desactivado']);
    }
} catch (Exception $e) { /* tabla aún no creada */ }

try { $conn->prepare("UPDATE bot_crons_config SET ultima_ejecucion = NOW() WHERE clave = 'revision_semanal'")->execute(); } catch (Exception $e) {}


$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$hoy          = date('Y-m-d');
$semanaLabel  = date('d') . ' al ' . date('d', strtotime($hoy)) . ' de ' . ['','enero','febrero','marzo','abril','mayo','junio',
                'julio','agosto','septiembre','octubre','noviembre','diciembre'][date('n')];

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

    // Tareas de esta semana
    $stmtT = $conn->prepare("
        SELECT titulo, estado FROM gestion_tareas_reuniones_items
        WHERE tipo = 'tarea' AND cod_operario_creador = ?
        AND fecha_meta BETWEEN ? AND ?
        AND estado != 'cancelado'
    ");
    $stmtT->execute([$codOp, $inicioSemana, $hoy]);
    $tareasSemanales = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    // Reuniones de esta semana
    $stmtR = $conn->prepare("
        SELECT r.titulo FROM gestion_tareas_reuniones_items r
        WHERE r.tipo = 'reunion' AND r.estado NOT IN ('cancelado')
        AND DATE(r.fecha_reunion) BETWEEN ? AND ?
        AND EXISTS (
            SELECT 1 FROM gestion_tareas_reuniones_participantes p
            INNER JOIN AsignacionNivelesCargos anc ON anc.CodNivelesCargos = p.cod_cargo
            WHERE p.id_item = r.id AND anc.CodOperario = ?
        )
    ");
    $stmtR->execute([$inicioSemana, $hoy, $codOp]);
    $reunionesSemanales = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tareasSemanales) && empty($reunionesSemanales)) continue;

    $completadas = count(array_filter($tareasSemanales, fn($t) => $t['estado'] === 'finalizado'));
    $pendientes  = count($tareasSemanales) - $completadas;

    $prompt = "Genera un resumen semanal motivacional en español para {$op['Nombre']} (colaborador de Batidos Pitaya). " .
              "Semana del $semanaLabel. Usa *negrita* y emojis. Máximo 12 líneas.\n\n" .
              "Tareas completadas esta semana: $completadas\nTareas pendientes para próxima semana: $pendientes\n" .
              "Reuniones esta semana: " . count($reunionesSemanales) . "\n\n" .
              "Genera el mensaje incluyendo una sugerencia práctica para la siguiente semana.";

    // Obtener API key de Gemini desde el rotador de la BD
    $geminiKey = '';
    try {
        $stmtKey = $conn->prepare("
            SELECT api_key FROM ia_proveedores_api
            WHERE proveedor = 'google' AND activa = 1 AND limite_alcanzado_hoy = 0
            ORDER BY RAND() LIMIT 1
        ");
        $stmtKey->execute();
        $geminiKey = $stmtKey->fetchColumn() ?: '';
    } catch (Exception $e) {}

    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-latest:generateContent?key=' . $geminiKey);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]),
        CURLOPT_TIMEOUT        => 20,
    ]);
    $geminiResp = curl_exec($ch);
    curl_close($ch);


    $mensaje = '';
    if ($geminiResp) {
        $datos   = json_decode($geminiResp, true);
        $mensaje = $datos['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    if (!$mensaje) {
        $mensaje = "📊 *Resumen semanal — semana del $semanaLabel*\n\n" .
                   "✅ Completaste $completadas tarea(s).\n" .
                   "🔄 $pendientes tarea(s) pasan a la próxima semana.\n" .
                   "🤝 Participaste en " . count($reunionesSemanales) . " reunión(es).\n\n" .
                   "¡Buen fin de semana! 🌟";
    }

    $resultados[] = ['celular' => $op['telefono_corporativo'], 'mensaje' => trim($mensaje)];
}

respuestaOk(['data' => $resultados]);

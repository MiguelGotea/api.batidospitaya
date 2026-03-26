<?php
/**
 * briefing_diario.php — Genera el briefing matutino para cada usuario activo del bot.
 *
 * GET (sin params) — retorna [{ celular, mensaje }]
 * Llamado por el cron de las 7 AM Lun-Vie.
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

// Verificar si este cron está activo (resiliente si la tabla aún no existe)
try {
    $stmtCron = $conn->prepare("SELECT activo FROM bot_crons_config WHERE clave = 'briefing_diario' LIMIT 1");
    $stmtCron->execute();
    $cron = $stmtCron->fetch(PDO::FETCH_ASSOC);
    if ($cron && !$cron['activo']) {
        respuestaOk(['data' => [], 'motivo' => 'cron desactivado']);
    }
} catch (Exception $e) { /* tabla aún no creada — continuar como activo */ }

$ejecutar = (isset($_GET['ejecutar']) && $_GET['ejecutar'] == 1);

$hoy       = date('Y-m-d');
$diaNombre = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][date('N') - 1];
$fechaHum  = $diaNombre . ' ' . date('j') . ' de ' . ['','enero','febrero','marzo','abril','mayo','junio',
             'julio','agosto','septiembre','octubre','noviembre','diciembre'][date('n')] . ' de ' . date('Y');

// Obtener operarios activos (contrato vigente) con teléfono corporativo
$stmtOps = $conn->prepare("
    SELECT DISTINCT o.CodOperario, o.Nombre, o.telefono_corporativo
    FROM Operarios o
    INNER JOIN Contratos c ON c.cod_operario = o.CodOperario AND c.Finalizado = 0
    WHERE o.telefono_corporativo IS NOT NULL
");
$stmtOps->execute();
$operarios = $stmtOps->fetchAll(PDO::FETCH_ASSOC);

$resultados = [];

foreach ($operarios as $op) {
    try {
        $codOp = $op['CodOperario'];

        // Tareas del día
        $stmtTareas = $conn->prepare("
            SELECT t.titulo, t.estado FROM gestion_tareas_reuniones_items t
            WHERE t.tipo = 'tarea' AND DATE(t.fecha_meta) = ? AND t.estado NOT IN ('cancelado')
            AND (t.cod_operario_creador = ? OR EXISTS (
                SELECT 1 FROM gestion_tareas_reuniones_participantes p
                INNER JOIN AsignacionNivelesCargos anc ON anc.CodNivelesCargos = p.cod_cargo
                WHERE p.id_item = t.id AND anc.CodOperario = ?
            ))
            ORDER BY t.fecha_meta ASC LIMIT 10
        ");
        $stmtTareas->execute([$hoy, $codOp, $codOp]);
        $tareasHoy = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);

        // Reuniones del día
        $stmtReus = $conn->prepare("
            SELECT r.titulo, TIME_FORMAT(r.fecha_reunion, '%h:%i %p') AS hora
            FROM gestion_tareas_reuniones_items r
            WHERE r.tipo = 'reunion' AND DATE(r.fecha_reunion) = ?
            AND r.estado NOT IN ('cancelado')
            AND EXISTS (
                SELECT 1 FROM gestion_tareas_reuniones_participantes p
                INNER JOIN AsignacionNivelesCargos anc ON anc.CodNivelesCargos = p.cod_cargo
                WHERE p.id_item = r.id AND anc.CodOperario = ?
            )
            ORDER BY r.fecha_reunion ASC LIMIT 5
        ");
        $stmtReus->execute([$hoy, $codOp]);
        $reunionesHoy = $stmtReus->fetchAll(PDO::FETCH_ASSOC);

        // Tareas retrasadas
        $stmtRet = $conn->prepare("
            SELECT titulo FROM gestion_tareas_reuniones_items
            WHERE tipo = 'tarea' AND fecha_meta < ? AND estado IN ('solicitado','en_progreso')
            AND cod_operario_creador = ?
            LIMIT 3
        ");
        $stmtRet->execute([$hoy, $codOp]);
        $retrasadas = $stmtRet->fetchAll(PDO::FETCH_ASSOC);

        // Si no hay nada relevante, omitir
        if (empty($tareasHoy) && empty($reunionesHoy) && empty($retrasadas)) {
            continue;
        }

        // Llamar a Gemini para generar el briefing narrativo
        $promptGemini = "Genera un briefing matutino conciso en español para " . $op['Nombre'] . " (colaborador de Batidos Pitaya). Usa emojis, sé cálido y directo. Máximo 12 líneas. Incluye formato WhatsApp (*negrita*, _cursiva_).\n\nFecha: $fechaHum\n\nTareas para hoy: " . json_encode($tareasHoy, JSON_UNESCAPED_UNICODE) . "\n\nReuniones de hoy: " . json_encode($reunionesHoy, JSON_UNESCAPED_UNICODE) . "\n\nTareas retrasadas: " . json_encode($retrasadas, JSON_UNESCAPED_UNICODE) . "\n\nGenera el mensaje como si fueras su asistente personal.";

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
            CURLOPT_POSTFIELDS     => json_encode(['contents' => [['parts' => [['text' => $promptGemini]]]]]),
            CURLOPT_TIMEOUT        => 20,
        ]);
        $geminiResp = curl_exec($ch);
        curl_close($ch);


        $mensaje = '';
        if ($geminiResp) {
            $geminiData = json_decode($geminiResp, true);
            $mensaje = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }

        // Fallback si Gemini falla
        if (!$mensaje) {
            $nTareas   = count($tareasHoy);
            $nReus     = count($reunionesHoy);
            $nRet      = count($retrasadas);
            $mensaje   = "🌅 ¡Buenos días, {$op['Nombre']}!\n\n📅 Hoy es $fechaHum\n";
            if ($nTareas)  $mensaje .= "\n📋 Tienes $nTareas tarea(s) para hoy.";
            if ($nReus)    $mensaje .= "\n🤝 $nReus reunión(es) programada(s).";
            if ($nRet)     $mensaje .= "\n⚠️ $nRet tarea(s) retrasada(s) pendientes.";
            $mensaje .= "\n\n¡Buen día! 🚀";
        }

        $resultados[] = [
            'celular' => $op['telefono_corporativo'],
            'mensaje' => trim($mensaje)
        ];

        // Envío real si se solicita
        if ($ejecutar) {
            enviarMensajeWsp($op['telefono_corporativo'], trim($mensaje));
            usleep(2000000); // Anti-ban: esperar 2 segundos entre envíos
        }

    } catch (Exception $e) {
        error_log("Error briefing para operario {$op['CodOperario']}: " . $e->getMessage());
    }
}

// Solo actualizar marca de tiempo si fue una ejecución real
if ($ejecutar) {
    try {
        $conn->prepare("UPDATE bot_crons_config SET ultima_ejecucion = NOW() WHERE clave = 'briefing_diario'")->execute();
    } catch (Exception $e) {}
}

respuestaOk(['data' => $resultados, 'ejecutado' => $ejecutar]);

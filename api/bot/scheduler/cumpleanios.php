<?php
/**
 * cumpleanios.php — Notifica cumpleaños de compañeros a todos los usuarios del bot (8 AM).
 *
 * GET — retorna [{ celular, mensaje }]
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

try {
    $stmtCron = $conn->prepare("SELECT activo FROM bot_crons_config WHERE clave = 'cumpleanios' LIMIT 1");
    $stmtCron->execute();
    $cron = $stmtCron->fetch(PDO::FETCH_ASSOC);
    if ($cron && !$cron['activo']) {
        respuestaOk(['data' => [], 'motivo' => 'cron desactivado']);
    }
} catch (Exception $e) { /* tabla aún no creada */ }

try { $conn->prepare("UPDATE bot_crons_config SET ultima_ejecucion = NOW() WHERE clave = 'cumpleanios'")->execute(); } catch (Exception $e) {}


$hoyMD = date('m-d');

// Operarios que cumplen años hoy
$stmtCump = $conn->prepare("
    SELECT o.Nombre, o.Apellido, o.Cargo,
           TIMESTAMPDIFF(YEAR, o.Cumpleanos, CURDATE()) AS edad
    FROM Operarios o
    WHERE DATE_FORMAT(o.Cumpleanos, '%m-%d') = ?
    AND o.Operativo = 1
    AND o.Cumpleanos IS NOT NULL
");
$stmtCump->execute([$hoyMD]);
$cumpleaneros = $stmtCump->fetchAll(PDO::FETCH_ASSOC);

if (empty($cumpleaneros)) {
    respuestaOk(['data' => []]);
}

// Usuarios del bot que recibirán la notificación
$stmtOps = $conn->prepare("
    SELECT DISTINCT o.telefono_corporativo
    FROM Operarios o
    INNER JOIN Contratos c ON c.cod_operario = o.CodOperario AND c.Finalizado = 0
    WHERE o.telefono_corporativo IS NOT NULL AND o.Operativo = 1
");
$stmtOps->execute();
$destinatarios = $stmtOps->fetchAll(PDO::FETCH_COLUMN);

$resultados = [];

foreach ($cumpleaneros as $c) {
    $nombreCompleto = trim($c['Nombre'] . ' ' . $c['Apellido']);
    $cargo          = $c['Cargo'] ?: 'Colaborador';
    $edad           = (int)$c['edad'];
    $edadTexto      = $edad > 0 ? "🎈 Cumple $edad años hoy\n" : '';

    $mensaje = "🎂 *¡Hoy cumple años un compañero!*\n\n" .
               "🎉 $nombreCompleto\n" .
               "💼 $cargo\n" .
               $edadTexto .
               "\n¿Quieres enviarle un saludo por WhatsApp?\nResponde *sí* para enviarlo o *no* para omitir.";

    foreach ($destinatarios as $celular) {
        $resultados[] = ['celular' => $celular, 'mensaje' => $mensaje];
    }
}

respuestaOk(['data' => $resultados]);

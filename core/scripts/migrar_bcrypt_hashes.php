<?php
/**
 * SCRIPT DE MIGRACIÓN — Generar bcrypt hash para operarios sin clave_hash
 * =========================================================================
 * Propósito : Poblar Operarios.clave_hash con el hash bcrypt de Operarios.clave
 *             para todos los operarios que actualmente tengan clave_hash NULL.
 *
 * Cómo usar : Acceder una sola vez desde el navegador en:
 *             http://localhost/core/scripts/migrar_bcrypt_hashes.php?token=PITAYA_MIGRATE_2026
 *
 * Es idempotente: puede correrse varias veces sin riesgo — solo procesa los
 * que aún tienen clave_hash NULL y clave no vacía.
 *
 * ELIMINAR este archivo después de ejecutar exitosamente.
 * =========================================================================
 */

// ── Protección: requiere token de seguridad ───────────────────────────────
define('MIGRATION_TOKEN', 'PITAYA_MIGRATE_2026');

$tokenRecibido = $_GET['token'] ?? '';
if ($tokenRecibido !== MIGRATION_TOKEN) {
    http_response_code(403);
    die('<h2 style="color:red;font-family:monospace;">403 — Token requerido. Agrega ?token=PITAYA_MIGRATE_2026 a la URL.</h2>');
}

// ── Configuración ─────────────────────────────────────────────────────────
set_time_limit(300);        // 5 minutos máximo de ejecución
ini_set('output_buffering', 'off');
header('Content-Type: text/html; charset=utf-8');

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/database/conexion.php';

// ── Utilidades de salida ──────────────────────────────────────────────────
function out(string $msg, string $color = '#333'): void {
    echo "<div style=\"font-family:monospace;font-size:14px;color:{$color};margin:2px 0;\">{$msg}</div>";
    ob_flush(); flush();
}

function outOk(string $msg):    void { out("✅ {$msg}", '#1a7a3a'); }
function outSkip(string $msg):  void { out("⏭️  {$msg}", '#888');    }
function outErr(string $msg):   void { out("❌ {$msg}", '#c0392b');  }
function outInfo(string $msg):  void { out("ℹ️  {$msg}", '#2471a3'); }
function outSep():               void { out(str_repeat('─', 70), '#ccc'); }

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Migración bcrypt — Batidos Pitaya</title>
<style>
  body { background:#f5f5f5; padding:30px; }
  h1   { font-family:monospace; color:#0E544C; }
  #log { background:white; border:1px solid #ddd; border-radius:8px;
         padding:20px; max-height:600px; overflow-y:auto; }
  .resumen { background:#0E544C; color:white; padding:15px 20px;
             border-radius:8px; margin-top:20px; font-family:monospace; }
</style>
</head>
<body>
<h1>🔐 Migración — Generar bcrypt hashes</h1>
<div id="log">
<?php

outInfo('Iniciando migración de contraseñas a bcrypt...');
outSep();

// ── 1. Obtener operarios pendientes ───────────────────────────────────────
try {
    $stmt = $conn->query("
        SELECT CodOperario, Nombre, Apellido, clave
        FROM Operarios
        WHERE clave_hash IS NULL
          AND clave IS NOT NULL
          AND TRIM(clave) != ''
        ORDER BY CodOperario ASC
    ");
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    outErr('Error al consultar la BD: ' . htmlspecialchars($e->getMessage()));
    die('</div></body></html>');
}

$total     = count($pendientes);
$exitosos  = 0;
$errores   = 0;
$omitidos  = 0;

outInfo("Operarios con clave_hash NULL y clave no vacía encontrados: <strong>{$total}</strong>");
outSep();

if ($total === 0) {
    outOk('¡Todos los operarios ya tienen clave_hash poblado! No hay nada que migrar.');
} else {

    // ── 2. Preparar UPDATE ────────────────────────────────────────────────
    $stmtUpdate = $conn->prepare("
        UPDATE Operarios
        SET clave_hash = ?
        WHERE CodOperario = ?
          AND clave_hash IS NULL
    ");

    // ── 3. Procesar cada operario ─────────────────────────────────────────
    foreach ($pendientes as $op) {
        $codOp  = (int)$op['CodOperario'];
        $nombre = htmlspecialchars(trim($op['Nombre'] . ' ' . $op['Apellido']));
        $clave  = $op['clave'];

        // Saltar si clave tiene aspecto de hash bcrypt (ya se procesó antes por error)
        if (str_starts_with($clave, '$2y$') || str_starts_with($clave, '$2b$')) {
            outSkip("Op #{$codOp} ({$nombre}) — clave parece ser ya un hash bcrypt, omitido");
            $omitidos++;
            continue;
        }

        try {
            // Generar hash bcrypt (cost=10, igual que el sistema PHP estándar)
            $hash = password_hash($clave, PASSWORD_BCRYPT, ['cost' => 10]);

            if ($hash === false) {
                outErr("Op #{$codOp} ({$nombre}) — password_hash() retornó false");
                $errores++;
                continue;
            }

            $stmtUpdate->execute([$hash, $codOp]);

            if ($stmtUpdate->rowCount() === 1) {
                outOk("Op #{$codOp} ({$nombre}) — hash generado y guardado");
                $exitosos++;
            } else {
                outSkip("Op #{$codOp} ({$nombre}) — ya tenía clave_hash al momento del UPDATE (sin cambio)");
                $omitidos++;
            }
        } catch (Exception $e) {
            outErr("Op #{$codOp} ({$nombre}) — " . htmlspecialchars($e->getMessage()));
            $errores++;
        }
    }
}

// ── 4. Verificación final ─────────────────────────────────────────────────
outSep();
outInfo('Verificando estado final...');

try {
    $fila = $conn->query("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN clave_hash IS NOT NULL THEN 1 ELSE 0 END) AS con_hash,
            SUM(CASE WHEN clave_hash IS NULL AND clave IS NOT NULL AND TRIM(clave) != '' THEN 1 ELSE 0 END) AS sin_hash_pendientes,
            SUM(CASE WHEN clave_hash IS NULL AND (clave IS NULL OR TRIM(clave) = '') THEN 1 ELSE 0 END) AS sin_clave_alguna
        FROM Operarios
        WHERE Operativo = 1
    ")->fetch(PDO::FETCH_ASSOC);

    outInfo("Total operarios activos : <strong>{$fila['total']}</strong>");
    outOk("Con clave_hash          : <strong>{$fila['con_hash']}</strong>");
    if ($fila['sin_hash_pendientes'] > 0) {
        outErr("Aún sin clave_hash      : <strong>{$fila['sin_hash_pendientes']}</strong> — revisar manualmente");
    } else {
        outOk("Aún sin clave_hash      : <strong>0</strong> 🎉");
    }
    outInfo("Sin ninguna clave       : <strong>{$fila['sin_clave_alguna']}</strong> (no se puede migrar, no tienen contraseña)");
} catch (Exception $e) {
    outErr('Error en verificación: ' . htmlspecialchars($e->getMessage()));
}

?>
</div>

<div class="resumen">
    <strong>Resumen de migración</strong><br><br>
    ✅ Procesados exitosamente : <?= $exitosos ?><br>
    ⏭️  Omitidos (ya estaban)  : <?= $omitidos ?><br>
    ❌ Errores                 : <?= $errores ?><br>
</div>

<?php if ($errores === 0 && $exitosos >= 0): ?>
<div style="background:#fef9e7;border:1px solid #f1c40f;border-radius:8px;padding:15px;margin-top:15px;font-family:monospace;">
    ⚠️ <strong>IMPORTANTE:</strong> Elimina este archivo del servidor después de confirmar los resultados:<br>
    <code style="color:#c0392b;">/core/scripts/migrar_bcrypt_hashes.php</code>
</div>
<?php endif; ?>

</body>
</html>

<?php
/**
 * registrar_resultado.php — Guarda el análisis IA y marca el item como completado.
 *
 * POST /api/hikvision/registrar_resultado.php
 * Header: X-WSP-Token
 *
 * Body JSON (Protocolo 5 Grupos Pitaya + contexto membresía):
 * {
 *   "id_cola"          : 5,
 *   "grupo_bienvenida" : 8,       -- Paso 1
 *   "grupo_asesoria"   : 6,       -- Pasos 2-4 (null si no aplicó)
 *   "grupo_membresia"  : 3,       -- Paso 5
 *   "grupo_cobro"      : 7,       -- Pasos 6-8
 *   "
 *   "cal_promedio"       : 6.6,     -- Calculado en Python, verificado aquí
 *   "detalle_json"       : "{...}", -- JSON string con breakdown por paso
 *   "membresia_contexto" : "sin_membresia", -- sin_membresia|vendida|ya_tenia
 *   "resumen"            : "texto",
 *   "tiene_audio"        : 1,
 *   "duracion_segundos": 92,
 *   "modelo_ia"        : "gemini-2.5-flash"
 * }
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$data = json_decode(file_get_contents('php://input'), true);

// ── Parámetros requeridos ─────────────────────────────────────
$id_cola = isset($data['id_cola']) ? intval($data['id_cola']) : null;
if (!$id_cola) hikErr('Falta parámetro requerido: id_cola');

// ── Grupos de calificación (1-10 o null) ─────────────────────
$grupos = ['grupo_bienvenida', 'grupo_asesoria', 'grupo_membresia', 'grupo_cobro', '
$vals_grupo = [];
foreach ($grupos as $g) {
    $val = isset($data[$g]) && $data[$g] !== null ? intval($data[$g]) : null;
    if ($val !== null && ($val < 1 || $val > 10)) {
        hikErr("$g debe estar entre 1 y 10");
    }
    $vals_grupo[$g] = $val;
}

// ── Promedio: recalcular en PHP para verificar integridad ─────
$no_null = array_filter($vals_grupo, fn($v) => $v !== null);
$cal_promedio = count($no_null) > 0
    ? round(array_sum($no_null) / count($no_null), 2)
    : null;

// ── Resto de campos ───────────────────────────────────────────
$detalle_json        = $data['detalle_json']        ?? null;
$resumen             = $data['resumen']             ?? null;
$tiene_audio         = isset($data['tiene_audio'])       ? intval($data['tiene_audio'])       : 0;
$duracion_seg        = isset($data['duracion_segundos']) ? intval($data['duracion_segundos']) : null;
$modelo_ia           = $data['modelo_ia']           ?? 'gemini-2.5-flash';
$membresia_contexto  = $data['membresia_contexto']  ?? 'sin_membresia';

// Validar membresia_contexto
$opciones_membresia = ['sin_membresia', 'vendida', 'ya_tenia'];
if (!in_array($membresia_contexto, $opciones_membresia)) {
    $membresia_contexto = 'sin_membresia';
}

// Validar que detalle_json sea JSON válido si viene
if ($detalle_json !== null) {
    json_decode($detalle_json);
    if (json_last_error() !== JSON_ERROR_NONE) {
        hikErr('detalle_json no es un JSON válido');
    }
}

try {
    // ── Obtener datos del item de cola ────────────────────────
    $stmt = $conn->prepare("
        SELECT c.*, s.nombre AS sucursal_nombre
        FROM hikvision_cola_analisis c
        LEFT JOIN sucursales s ON s.codigo = c.local_codigo
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id_cola]);
    $item = $stmt->fetch();

    if (!$item) hikErr("Item $id_cola no encontrado en la cola", 404);

    // ── Insertar resultado ────────────────────────────────────
    $ins = $conn->prepare("
        INSERT INTO hikvision_analisis_ia_atencion
            (id_cola, cod_pedido, local_codigo, sucursal_nombre,
             fecha, hora_inicio, hora_fin,
             grupo_bienvenida, grupo_asesoria, grupo_membresia,
             grupo_cobro,
             detalle_json, resumen,
             tiene_audio, duracion_segundos, modelo_ia, membresia_contexto)
        VALUES
            (:id_cola, :cp, :lc, :sn,
             :fecha, :hi, :hf,
             :gbienvenida, :gasesoria, :gmembresia,
             :gcobro, :gentrega, :promedio,
             :detalle, :resumen,
             :audio, :dur, :modelo, :membresia_ctx)
    ");
    $ins->execute([
        ':id_cola'      => $id_cola,
        ':cp'           => $item['cod_pedido'],
        ':lc'           => $item['local_codigo'],
        ':sn'           => $item['sucursal_nombre'] ?? null,
        ':fecha'        => $item['fecha'],
        ':hi'           => $item['hora_inicio'],
        ':hf'           => $item['hora_fin'],
        ':gbienvenida'  => $vals_grupo['grupo_bienvenida'],
        ':gasesoria'    => $vals_grupo['grupo_asesoria'],
        ':gmembresia'   => $vals_grupo['grupo_membresia'],
        ':gcobro'       => $vals_grupo['grupo_cobro'],
        ':gentrega'     => $vals_grupo['
        ':promedio'     => $cal_promedio,
        ':detalle'        => $detalle_json,
        ':resumen'        => $resumen,
        ':audio'          => $tiene_audio,
        ':dur'            => $duracion_seg,
        ':modelo'         => $modelo_ia,
        ':membresia_ctx'  => $membresia_contexto,
    ]);

    $id_resultado = $conn->lastInsertId();

    // ── Marcar cola como completado ───────────────────────────
    $conn->prepare("
        UPDATE hikvision_cola_analisis
        SET estado = 'completado', updated_at = NOW()
        WHERE id = :id
    ")->execute([':id' => $id_cola]);

    hikOk([
        'id_resultado'       => (int) $id_resultado,
        'id_cola'            => $id_cola,
        'cal_promedio'       => $cal_promedio,
        'cod_pedido'         => $item['cod_pedido'],
        'grupos'             => $vals_grupo,
        'membresia_contexto' => $membresia_contexto,
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}


<?php
/**
 * registrar_resultado.php — Guarda el análisis IA y marca el item como completado
 * POST /api/hikvision/registrar_resultado.php
 * Header: X-WSP-Token
 * Body JSON: {
 *   "id_cola": 5,
 *   "cal_amabilidad": 8,
 *   "cal_saludo": 9,
 *   "cal_despedida": 7,
 *   "cal_membresia": 5,
 *   "resumen": "texto del análisis...",
 *   "tiene_audio": 1,
 *   "duracion_segundos": 185,
 *   "modelo_ia": "gemini-2.0-flash"
 * }
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$data = json_decode(file_get_contents('php://input'), true);

$id_cola          = isset($data['id_cola'])          ? intval($data['id_cola'])          : null;
$cal_amabilidad   = isset($data['cal_amabilidad'])   ? intval($data['cal_amabilidad'])   : null;
$cal_saludo       = isset($data['cal_saludo'])       ? intval($data['cal_saludo'])       : null;
$cal_despedida    = isset($data['cal_despedida'])    ? intval($data['cal_despedida'])    : null;
$cal_membresia    = isset($data['cal_membresia'])    ? intval($data['cal_membresia'])    : null;
$resumen          = $data['resumen']                 ?? null;
$tiene_audio      = isset($data['tiene_audio'])      ? intval($data['tiene_audio'])      : 0;
$duracion_seg     = isset($data['duracion_segundos'])? intval($data['duracion_segundos']): null;
$modelo_ia        = $data['modelo_ia']               ?? 'gemini-2.0-flash';

if (!$id_cola) {
    hikErr('Falta parámetro requerido: id_cola');
}

// Validar rangos de calificación
foreach (['cal_amabilidad','cal_saludo','cal_despedida','cal_membresia'] as $campo) {
    if ($$campo !== null && ($$campo < 1 || $$campo > 10)) {
        hikErr("$campo debe estar entre 1 y 10");
    }
}

try {
    // ── Obtener datos del item de cola ───────────────────────
    $stmt = $conn->prepare("
        SELECT c.*, s.nombre AS sucursal_nombre
        FROM hikvision_cola_analisis c
        LEFT JOIN sucursales s ON s.codigo = c.local_codigo
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id_cola]);
    $item = $stmt->fetch();

    if (!$item) {
        hikErr("Item $id_cola no encontrado en la cola", 404);
    }

    // ── Calcular promedio ────────────────────────────────────
    $calificaciones = array_filter(
        [$cal_amabilidad, $cal_saludo, $cal_despedida, $cal_membresia],
        fn($v) => $v !== null
    );
    $promedio = count($calificaciones) > 0
        ? round(array_sum($calificaciones) / count($calificaciones), 2)
        : null;

    // ── Insertar resultado ───────────────────────────────────
    $ins = $conn->prepare("
        INSERT INTO hikvision_analisis_ia_atencion
            (id_cola, cod_pedido, local_codigo, sucursal_nombre,
             fecha, hora_inicio, hora_fin,
             cal_amabilidad, cal_saludo, cal_despedida, cal_membresia,
             promedio, resumen, tiene_audio, duracion_segundos, modelo_ia)
        VALUES
            (:id_cola, :cp, :lc, :sn,
             :fecha, :hi, :hf,
             :cam, :cs, :cd, :cme,
             :prom, :res, :audio, :dur, :modelo)
    ");
    $ins->execute([
        ':id_cola'  => $id_cola,
        ':cp'       => $item['cod_pedido'],
        ':lc'       => $item['local_codigo'],
        ':sn'       => $item['sucursal_nombre'] ?? null,
        ':fecha'    => $item['fecha'],
        ':hi'       => $item['hora_inicio'],
        ':hf'       => $item['hora_fin'],
        ':cam'      => $cal_amabilidad,
        ':cs'       => $cal_saludo,
        ':cd'       => $cal_despedida,
        ':cme'      => $cal_membresia,
        ':prom'     => $promedio,
        ':res'      => $resumen,
        ':audio'    => $tiene_audio,
        ':dur'      => $duracion_seg,
        ':modelo'   => $modelo_ia,
    ]);

    $id_resultado = $conn->lastInsertId();

    // ── Marcar item de cola como completado ──────────────────
    $conn->prepare("
        UPDATE hikvision_cola_analisis
        SET estado = 'completado', updated_at = NOW()
        WHERE id = :id
    ")->execute([':id' => $id_cola]);

    hikOk([
        'id_resultado' => (int) $id_resultado,
        'id_cola'      => $id_cola,
        'promedio'     => $promedio,
        'cod_pedido'   => $item['cod_pedido'],
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}

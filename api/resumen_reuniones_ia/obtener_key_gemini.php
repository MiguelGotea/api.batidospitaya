<?php
/**
 * obtener_key_gemini.php — Devuelve una API key activa de Gemini al VPS
 * GET /api/resumen_reuniones_ia/obtener_key_gemini.php
 * Header: X-Resumen-Token (VPS)
 *
 * Misma lógica que api/hikvision/gemini_key.php:
 * - Auto-reset diario de keys que se agotaron ayer
 * - Selección aleatoria (balanceo de carga)
 * - Registra timestamp de uso
 */

require_once __DIR__ . '/auth.php';

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    reunionErr('Método no permitido', 405);
}

try {
    // Auto-reset: desbloquear keys de Gemini que se agotaron ayer
    $conn->prepare("
        UPDATE ia_proveedores_api
        SET limite_alcanzado_hoy = 0
        WHERE proveedor = 'google'
          AND limite_alcanzado_hoy = 1
          AND DATE(ultimo_uso) < CURDATE()
    ")->execute();

    // Obtener una key activa aleatoria (balanceo de carga)
    $stmt = $conn->prepare("
        SELECT id, api_key
        FROM ia_proveedores_api
        WHERE proveedor = 'google'
          AND activa = 1
          AND limite_alcanzado_hoy = 0
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        reunionErr('No hay keys de Gemini disponibles o todas alcanzaron límite diario', 503);
    }

    // Registrar timestamp de uso
    $conn->prepare("UPDATE ia_proveedores_api SET ultimo_uso = NOW() WHERE id = ?")
         ->execute([$row['id']]);

    reunionOk([
        'key_id'  => (int) $row['id'],
        'api_key' => $row['api_key'],
        'modelo'  => 'gemini-2.5-flash',
    ]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] obtener_key_gemini.php error: ' . $e->getMessage());
    reunionErr('Error interno al obtener la key de Gemini', 500);
}

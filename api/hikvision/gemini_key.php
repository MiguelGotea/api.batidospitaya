<?php
/**
 * gemini_key.php — Devuelve una API key activa de Gemini al VPS
 * GET /api/hikvision/gemini_key.php
 * Header: X-WSP-Token
 *
 * El VPS bot llama este endpoint para obtener la key activa,
 * luego llama a Gemini directamente. Sigue la misma lógica de
 * cascada que AIService.php (auto-reset diario, rotación aleatoria).
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

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
    $row = $stmt->fetch();

    if (!$row) {
        hikErr('No hay keys de Gemini disponibles o todas alcanzaron límite diario', 503);
    }

    // Registrar uso
    $conn->prepare("UPDATE ia_proveedores_api SET ultimo_uso = NOW() WHERE id = ?")
         ->execute([$row['id']]);

    hikOk([
        'key_id'  => $row['id'],
        'api_key' => $row['api_key'],
        'modelo'  => 'gemini-2.0-flash',
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}

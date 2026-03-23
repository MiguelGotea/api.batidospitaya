<?php
/**
 * obtener_api_key.php — Retorna una API Key activa del rotador IA
 *
 * GET ?proveedor=google
 * Devuelve una key activa disponible hoy para el proveedor solicitado,
 * seleccionada aleatoriamente (mismo comportamiento que AIService.php del ERP).
 *
 * Auto-reset diario: desbloquea keys que se agotaron ayer.
 *
 * Proveedores soportados: google, openai, deepseek, mistral, cerebras,
 *                          openrouter, huggingface, groq
 *
 * Llamado por: wsp-pitayabot/src/bot/classifier.js
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$proveedor = strtolower(trim($_GET['proveedor'] ?? ''));

$proveedoresSoportados = [
    'google', 'openai', 'deepseek', 'mistral',
    'cerebras', 'openrouter', 'huggingface', 'groq'
];

if (empty($proveedor) || !in_array($proveedor, $proveedoresSoportados)) {
    respuestaError('Proveedor requerido o no soportado. Válidos: ' . implode(', ', $proveedoresSoportados));
}

try {
    // Auto-reset: Si ya es un nuevo día, desbloquear keys que se agotaron ayer
    $conn->prepare("
        UPDATE ia_proveedores_api
        SET limite_alcanzado_hoy = 0
        WHERE proveedor = :proveedor
          AND limite_alcanzado_hoy = 1
          AND DATE(ultimo_uso) < CURDATE()
    ")->execute([':proveedor' => $proveedor]);

    // Obtener todas las keys activas disponibles hoy para este proveedor
    $stmt = $conn->prepare("
        SELECT id, api_key
        FROM ia_proveedores_api
        WHERE proveedor = :proveedor
          AND activa = 1
          AND limite_alcanzado_hoy = 0
    ");
    $stmt->execute([':proveedor' => $proveedor]);
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($keys)) {
        echo json_encode([
            'success'   => false,
            'message'   => "No hay API keys disponibles para el proveedor: $proveedor",
            'proveedor' => $proveedor
        ]);
        exit;
    }

    // Seleccionar key aleatoriamente (balanceo de carga)
    $seleccionada = $keys[array_rand($keys)];

    // Actualizar último uso
    $conn->prepare("UPDATE ia_proveedores_api SET ultimo_uso = NOW() WHERE id = :id")
         ->execute([':id' => $seleccionada['id']]);

    respuestaOk([
        'proveedor' => $proveedor,
        'api_key'   => $seleccionada['api_key'],
        'key_id'    => $seleccionada['id']
    ]);

} catch (Exception $e) {
    error_log('Error obtener_api_key.php: ' . $e->getMessage());
    respuestaError('Error interno del servidor', 500);
}

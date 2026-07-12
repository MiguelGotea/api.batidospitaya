<?php
/**
 * actualizar_estado.php — Cambia el estado de una reunión
 * POST /api/resumen_reuniones_ia/actualizar_estado.php
 * Header: X-Resumen-Token (VPS)
 *
 * Body JSON:
 *   token   string   token de la reunión
 *   estado  string   nuevo estado
 *
 * Transiciones válidas:
 *   creada      → grabando
 *   grabando    → pausada
 *   pausada     → grabando
 *   grabando    → finalizada   (guarda fecha_finalizada)
 *   finalizada  → procesando
 *   procesando  → completada   (lo maneja guardar_resultado.php directamente)
 *
 * Nota: la transición procesando→completada la hace guardar_resultado.php
 *       junto con el guardado del resumen, por eso no está aquí.
 */

require_once __DIR__ . '/auth.php';

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reunionErr('Método no permitido', 405);
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    reunionErr('Body JSON inválido o vacío', 400);
}

$tokenReunion = trim($body['token']  ?? '');
$nuevoEstado  = trim($body['estado'] ?? '');

if (empty($tokenReunion)) {
    reunionErr('El campo "token" es requerido', 422);
}
if (empty($nuevoEstado)) {
    reunionErr('El campo "estado" es requerido', 422);
}

// Transiciones válidas [estado_actual => [estados_permitidos]]
$transicionesValidas = [
    'creada'     => ['grabando'],
    'grabando'   => ['pausada', 'finalizada'],
    'pausada'    => ['grabando', 'finalizada'],
    'finalizada' => ['procesando'],
];

try {
    // Obtener estado actual
    $stmt = $conn->prepare("
        SELECT id, estado
        FROM resumen_reuniones_ia
        WHERE token = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenReunion]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) {
        reunionErr('Token de reunión no encontrado', 404);
    }



    $estadoActual = $reunion['estado'];

    // Idempotente: si ya está en ese estado, retornar éxito sin error
    if ($estadoActual === $nuevoEstado) {
        reunionOk([
            'reunion_id'    => (int) $reunion['id'],
            'estado_previo' => $estadoActual,
            'estado_nuevo'  => $nuevoEstado,
            'idempotent'    => true,
        ]);
    }

    // Validar transición
    $permitidos = $transicionesValidas[$estadoActual] ?? [];
    if (!in_array($nuevoEstado, $permitidos, true)) {
        reunionErr(
            "Transición no permitida: '{$estadoActual}' → '{$nuevoEstado}'. " .
            "Desde '{$estadoActual}' solo se puede ir a: " . implode(', ', $permitidos ?: ['ninguno']),
            422
        );
    }


    // Construir UPDATE
    $campos = ['estado = :estado'];
    $params = [
        ':estado' => $nuevoEstado,
        ':id'     => (int) $reunion['id'],
    ];

    // Guardar timestamp si corresponde
    if ($nuevoEstado === 'finalizada') {
        $campos[] = 'fecha_finalizada = NOW()';
    }

    $sql = "UPDATE resumen_reuniones_ia SET " . implode(', ', $campos) . " WHERE id = :id";
    $conn->prepare($sql)->execute($params);

    reunionOk([
        'reunion_id'   => (int) $reunion['id'],
        'estado_previo' => $estadoActual,
        'estado_nuevo'  => $nuevoEstado,
    ]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] actualizar_estado.php error: ' . $e->getMessage());
    reunionErr('Error interno al actualizar el estado', 500);
}

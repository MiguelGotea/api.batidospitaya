<?php
/**
 * buscar_participantes.php — Busqueda fuzzy de operarios activos por nombre
 *
 * GET ?nombre=Ana
 * Retorna: [{ cod_operario, cod_cargo, nombre_completo, cargo, email }]
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$nombre = trim($_GET['nombre'] ?? '');
if (strlen($nombre) < 2) {
    respuestaError('Se requiere al menos 2 caracteres para buscar');
}

$like = '%' . $nombre . '%';

try {
    $stmt = $conn->prepare("
        SELECT
            o.CodOperario       AS cod_operario,
            anc.CodNivelesCargos AS cod_cargo,
            CONCAT(TRIM(o.Nombre), ' ', TRIM(o.Apellido)) AS nombre_completo,
            nc.NombreNivel      AS cargo,
            o.email_trabajo     AS email
        FROM Operarios o
        INNER JOIN AsignacionNivelesCargos anc
               ON o.CodOperario = anc.CodOperario
              AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
              AND anc.Fecha <= CURDATE()
        INNER JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
        WHERE (o.Nombre LIKE :like OR o.Apellido LIKE :like2
               OR CONCAT(o.Nombre,' ',o.Apellido) LIKE :like3)
          AND o.Estado = 'activo'
        ORDER BY o.Nombre, o.Apellido
        LIMIT 6
    ");
    $stmt->execute([':like' => $like, ':like2' => $like, ':like3' => $like]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $demasiados = count($resultados) >= 6;
    if ($demasiados) $resultados = array_slice($resultados, 0, 5);

    respuestaOk([
        'data'      => $resultados,
        'total'     => count($resultados),
        'demasiados'=> $demasiados
    ]);
} catch (Exception $e) {
    error_log('Error buscar_participantes: ' . $e->getMessage());
    respuestaError('Error buscando participantes', 500);
}

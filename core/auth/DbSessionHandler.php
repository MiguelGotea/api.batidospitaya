<?php
// /core/auth/DbSessionHandler.php
// Handler de sesiones PHP respaldado por MySQL.
// Evita que el GC de archivos de Hostinger destruya sesiones activas.

class DbSessionHandler implements SessionHandlerInterface
{
    /** @var PDO */
    private PDO $pdo;

    /** @var int Tiempo máximo de vida de sesión inactiva (segundos) */
    private int $maxLifetime;

    public function __construct(PDO $pdo, int $maxLifetime = 57600)
    {
        $this->pdo         = $pdo;
        $this->maxLifetime = $maxLifetime;
    }

    // -------------------------------------------------------------------------
    // Crear la tabla si no existe (se llama una sola vez al registrar el handler)
    // -------------------------------------------------------------------------
    public function instalarTabla(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `php_sessions` (
                `session_id`    VARCHAR(128) NOT NULL COLLATE 'utf8mb4_bin',
                `data`          MEDIUMTEXT   NOT NULL,
                `last_activity` INT UNSIGNED NOT NULL,
                `created_at`    INT UNSIGNED NOT NULL,
                PRIMARY KEY (`session_id`),
                INDEX `idx_last_activity` (`last_activity`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // -------------------------------------------------------------------------
    // SessionHandlerInterface
    // -------------------------------------------------------------------------

    public function open(string $path, string $name): bool
    {
        return true; // La conexión PDO ya está abierta
    }

    public function close(): bool
    {
        // Aprovechar el cierre para limpiar sesiones expiradas (GC propio)
        // Solo ~1% de las veces para no sobrecargar la BD
        if (mt_rand(1, 100) === 1) {
            $limite = time() - $this->maxLifetime;
            $stmt   = $this->pdo->prepare('DELETE FROM `php_sessions` WHERE `last_activity` < ?');
            $stmt->execute([$limite]);
        }
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT `data` FROM `php_sessions` WHERE `session_id` = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $ahora = time();
        $stmt  = $this->pdo->prepare('
            INSERT INTO `php_sessions` (`session_id`, `data`, `last_activity`, `created_at`)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                `data`          = VALUES(`data`),
                `last_activity` = VALUES(`last_activity`)
        ');
        return $stmt->execute([$id, $data, $ahora, $ahora]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `php_sessions` WHERE `session_id` = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        // PHP puede llamar a gc(); delegamos al cierre de sesión para consistencia.
        $limite = time() - $this->maxLifetime;
        $stmt   = $this->pdo->prepare('DELETE FROM `php_sessions` WHERE `last_activity` < ?');
        $stmt->execute([$limite]);
        return $stmt->rowCount();
    }
}

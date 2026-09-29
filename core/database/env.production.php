<?php
// ============================================================
// ARCHIVO DE ENTORNO PRODUCCIÓN
// ============================================================
// Este archivo SOLO existe en el servidor de Hostinger.
// NUNCA debe estar en el repositorio Git.
//
// Para deployar:
//   Opción A (manual): subir este archivo por FTP/SSH al host en:
//     /public_html/core/database/env.php
//
//   Opción B (GitHub Actions): crear un Secret llamado ENV_PRODUCTION
//   con el contenido de este archivo. El workflow lo crea automáticamente.
// ============================================================

define('APP_ENV', 'production');

define('DB_HOST', 'localhost');
define('DB_NAME', 'u839374897_erp');
define('DB_USER', 'u839374897_erp');
define('DB_PASS', 'ERpPitHay2025$');

<?php
// ============================================================
// ARCHIVO DE ENTORNO LOCAL - NO SUBIR AL HOST - NO SUBIR A GIT
// ============================================================
// Este archivo es la "llave" de acceso al entorno local.
// Creado automáticamente por .scripts/setup_php_portable.ps1
//
// Credenciales de BD LOCAL (usuario restringido ≠ producción):
//   - Solo tiene acceso a la base espejo 'erp_local'
//   - No puede acceder a la BD de producción
//
// Para cambiar la IP del servidor MySQL: editar DB_HOST
//   'localhost'     → MySQL en esta misma PC
//   '192.168.1.X'  → MySQL en la PC servidor de la red local
// ============================================================

define('APP_ENV', 'local');

define('DB_HOST', 'localhost');      // ← cambiar a la IP del servidor MySQL si es remoto
define('DB_NAME', 'erp_local');
define('DB_USER', 'erp_dev');       // usuario restringido (≠ producción)
define('DB_PASS', 'DevLocal2025!'); // contraseña diferente a producción

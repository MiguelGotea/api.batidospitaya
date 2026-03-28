<?php
// Incluir este archivo entre los include o require_once para restar 6 horas a los registros en bd

// Configurar zona horaria de Nicaragua
date_default_timezone_set('America/Managua');

// Configurar MySQL para usar hora local también (opcional)
if (isset($conn)) {
    $conn->exec("SET time_zone = '-06:00'");
}
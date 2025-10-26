<?php
// Configuraciones generales de la aplicación
define('SITE_URL', 'http://localhost/terpenitos');
define('ADMIN_EMAIL', 'admin@terpenitos.com');
define('ADMIN_PASSWORD', '$2y$10$7rZJQQqxJJXLKRXgtY0YmeB5vYPCJdpgXNiC7DPIAiLftG1QVJvx2'); // bcrypt hash de "admin123"

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // cambiar a 1 en HTTPS
session_start();

// Configuración de errores (cambiar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Función para limpiar datos de entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para verificar si es administrador
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Función para redireccionar
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
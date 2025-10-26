<?php
// Configurar Content-Type antes que nada
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Capturar errores y convertirlos a JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla
ini_set('log_errors', 1);

// Función para manejar errores fatales
function handleFatalError() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error interno del servidor',
            'debug' => 'Error en el archivo: ' . basename($error['file']) . ' línea ' . $error['line']
        ]);
    }
}
register_shutdown_function('handleFatalError');

// Incluir configuraciones
require_once 'config/config.php';
require_once 'config/database.php';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar conexión a la base de datos antes de procesar requests
try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de conexión a la base de datos',
        'debug' => $e->getMessage()
    ]);
    exit();
}

// Obtener controlador y acción de los parámetros
$controller = $_GET['controller'] ?? '';
$action = $_GET['action'] ?? '';

// Endpoint especial de verificación de estado
if ($controller === 'system' && $action === 'health') {
    echo json_encode([
        'success' => true, 
        'message' => 'Sistema funcionando correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'database' => 'conectada'
    ]);
    exit();
}

// Validar controlador y acción
if (empty($controller) || empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Controlador y acción requeridos']);
    exit();
}

// Mapeo de controladores
$controllers = [
    'auth' => 'AuthController',
    'product' => 'ProductController',
    'category' => 'CategoryController',
    'order' => 'OrderController',
    'address' => 'AddressController',
    'image' => 'ImageController'
];

// Verificar si el controlador existe
if (!isset($controllers[$controller])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Controlador no encontrado']);
    exit();
}

$controllerClass = $controllers[$controller];
$controllerFile = "controllers/{$controllerClass}.php";

// Verificar si el archivo del controlador existe
if (!file_exists($controllerFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Archivo del controlador no encontrado']);
    exit();
}

// Incluir el controlador
require_once $controllerFile;

// Verificar si la clase del controlador existe
if (!class_exists($controllerClass)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Clase del controlador no encontrada']);
    exit();
}

// Crear instancia del controlador
$controllerInstance = new $controllerClass();

// Verificar si el método existe
if (!method_exists($controllerInstance, $action)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Acción no encontrada']);
    exit();
}

try {
    // Ejecutar la acción
    $controllerInstance->$action();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
}
?>
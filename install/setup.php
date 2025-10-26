<?php
// Configurar manejo de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// Verificar si ya está instalado (opcional, permitir reinstalación)
$force_install = isset($_GET['force']) && $_GET['force'] === 'true';
if(file_exists('../.installed') && !$force_install) {
    echo '<h2>⚠️ Aplicación ya instalada</h2>';
    echo '<p>La aplicación ya está instalada.</p>';
    echo '<p><a href="?force=true">🔧 Forzar reinstalación</a> | <a href="../">🏠 Ir a la tienda</a></p>';
    exit();
}

$success = true;
$messages = [];

try {
    // Crear la base de datos
    $database = new Database();
    
    // Primero verificar conexión sin base de datos específica
    $messages[] = '🔌 Intentando conectar al servidor MySQL...';
    
    if($database->createDatabase()) {
        $messages[] = '✓ Base de datos creada/verificada exitosamente';
    } else {
        $messages[] = '✗ Error al crear la base de datos';
        $success = false;
    }
    
    // Verificar conexión a la base de datos específica
    $conn = $database->getConnection();
    if(!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    $messages[] = '✓ Conexión a la base de datos establecida';
    
    // Ejecutar el script SQL
    $messages[] = '📄 Leyendo archivo database.sql...';
    $sql = file_get_contents('database.sql');
    if(!$sql) {
        throw new Exception('No se pudo leer el archivo database.sql');
    }
    $messages[] = '✓ Archivo SQL leído correctamente';
    
    // Ejecutar cada declaración SQL por separado
    $messages[] = '🔧 Ejecutando comandos SQL...';
    $statements = explode(';', $sql);
    $executed_count = 0;
    
    foreach($statements as $statement) {
        $statement = trim($statement);
        if(!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $conn->exec($statement);
                $executed_count++;
            } catch(PDOException $e) {
                // Ignorar errores de "ya existe" y comentarios
                if(strpos($e->getMessage(), 'already exists') === false && 
                   strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $messages[] = '✗ Error en SQL: ' . $e->getMessage();
                    $messages[] = '  Comando: ' . substr($statement, 0, 100) . '...';
                    $success = false;
                } else {
                    $messages[] = '⚠️ Tabla ya existe (ignorando): ' . substr($statement, 0, 50) . '...';
                }
            }
        }
    }
    
    $messages[] = "✓ Ejecutados $executed_count comandos SQL";
    
    // Verificar que las tablas se crearon
    $required_tables = ['users', 'products', 'categories', 'orders', 'order_items'];
    foreach($required_tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if($stmt->rowCount() > 0) {
            $messages[] = "✓ Tabla '$table' verificada";
        } else {
            $messages[] = "✗ Tabla '$table' no encontrada";
            $success = false;
        }
    }
    
    // Crear directorios necesarios
    $directories = [
        '../img/productos',
        '../img/categorias',
        '../uploads',
        '../logs'
    ];
    
    foreach($directories as $dir) {
        if(!is_dir($dir)) {
            if(mkdir($dir, 0755, true)) {
                $messages[] = '✓ Directorio creado: ' . basename($dir);
            } else {
                $messages[] = '✗ Error al crear directorio: ' . basename($dir);
                $success = false;
            }
        } else {
            $messages[] = '✓ Directorio ya existe: ' . basename($dir);
        }
    }
    
    // Crear archivo .htaccess para la API
    $htaccess_content = "RewriteEngine On\n";
    $htaccess_content .= "RewriteBase /terpenitos/\n";
    $htaccess_content .= "\n";
    $htaccess_content .= "# API Routes\n";
    $htaccess_content .= "RewriteRule ^api/auth/login$ api.php?controller=auth&action=login [L]\n";
    $htaccess_content .= "RewriteRule ^api/auth/register$ api.php?controller=auth&action=register [L]\n";
    $htaccess_content .= "RewriteRule ^api/auth/logout$ api.php?controller=auth&action=logout [L]\n";
    $htaccess_content .= "RewriteRule ^api/auth/user$ api.php?controller=auth&action=getUser [L]\n";
    $htaccess_content .= "RewriteRule ^api/auth/update-profile$ api.php?controller=auth&action=updateProfile [L]\n";
    $htaccess_content .= "RewriteRule ^api/auth/change-password$ api.php?controller=auth&action=changePassword [L]\n";
    $htaccess_content .= "\n";
    $htaccess_content .= "RewriteRule ^api/products$ api.php?controller=product&action=getAll [L]\n";
    $htaccess_content .= "RewriteRule ^api/products/featured$ api.php?controller=product&action=getFeatured [L]\n";
    $htaccess_content .= "RewriteRule ^api/products/search$ api.php?controller=product&action=search [L]\n";
    $htaccess_content .= "RewriteRule ^api/products/category$ api.php?controller=product&action=getByCategory [L]\n";
    $htaccess_content .= "RewriteRule ^api/products/([0-9]+)$ api.php?controller=product&action=getById&id=$1 [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/products$ api.php?controller=product&action=create [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/products/update$ api.php?controller=product&action=update [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/products/delete$ api.php?controller=product&action=delete [L]\n";
    $htaccess_content .= "\n";
    $htaccess_content .= "RewriteRule ^api/categories$ api.php?controller=category&action=getAll [L]\n";
    $htaccess_content .= "RewriteRule ^api/categories/([0-9]+)$ api.php?controller=category&action=getById&id=$1 [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/categories$ api.php?controller=category&action=create [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/categories/update$ api.php?controller=category&action=update [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/categories/delete$ api.php?controller=category&action=delete [L]\n";
    $htaccess_content .= "\n";
    $htaccess_content .= "RewriteRule ^api/orders$ api.php?controller=order&action=create [L]\n";
    $htaccess_content .= "RewriteRule ^api/orders/user$ api.php?controller=order&action=getUserOrders [L]\n";
    $htaccess_content .= "RewriteRule ^api/orders/([0-9]+)$ api.php?controller=order&action=getOrderDetails&id=$1 [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/orders$ api.php?controller=order&action=getAllOrders [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/orders/update-status$ api.php?controller=order&action=updateStatus [L]\n";
    $htaccess_content .= "RewriteRule ^api/admin/stats$ api.php?controller=order&action=getStats [L]\n";
    $htaccess_content .= "\n";
    $htaccess_content .= "RewriteRule ^api/addresses$ api.php?controller=address&action=getUserAddresses [L]\n";
    $htaccess_content .= "RewriteRule ^api/addresses/create$ api.php?controller=address&action=create [L]\n";
    $htaccess_content .= "RewriteRule ^api/addresses/update$ api.php?controller=address&action=update [L]\n";
    $htaccess_content .= "RewriteRule ^api/addresses/delete$ api.php?controller=address&action=delete [L]\n";
    $htaccess_content .= "RewriteRule ^api/addresses/default$ api.php?controller=address&action=getDefault [L]\n";
    
    if(file_put_contents('../.htaccess', $htaccess_content)) {
        $messages[] = '✓ Archivo .htaccess creado';
    } else {
        $messages[] = '✗ Error al crear archivo .htaccess';
        $success = false;
    }
    
    if($success) {
        // Crear archivo de instalación completada
        file_put_contents('../.installed', date('Y-m-d H:i:s'));
        $messages[] = '✓ Instalación completada exitosamente';
    }
    
} catch(Exception $e) {
    $messages[] = '✗ Error durante la instalación: ' . $e->getMessage();
    $success = false;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Terpenitos Growshop</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2d3748;
            margin-bottom: 30px;
            text-align: center;
        }
        .message {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            font-family: monospace;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .status {
            font-size: 24px;
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            border-radius: 8px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5855eb;
        }
        .info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .credentials {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌱 Instalación de Terpenitos Growshop</h1>
        
        <?php if($success): ?>
            <div class="status success">
                ✓ ¡Instalación completada exitosamente!
            </div>
            
            <div class="credentials">
                <h3>Credenciales de acceso:</h3>
                <p><strong>Administrador:</strong></p>
                <ul>
                    <li>Email: admin@terpenitos.com</li>
                    <li>Contraseña: admin</li>
                </ul>
                <p><strong>Usuario demo:</strong></p>
                <ul>
                    <li>Email: demo@terpenitos.com</li>
                    <li>Contraseña: admin123</li>
                </ul>
            </div>
            
            <div class="info">
                <h3>Próximos pasos:</h3>
                <ol>
                    <li>Configura la base de datos en <code>config/database.php</code> si es necesario</li>
                    <li>Coloca las imágenes de productos en <code>img/productos/</code></li>
                    <li>Coloca las imágenes de categorías en <code>img/categorias/</code></li>
                    <li>Configura el entorno de producción en <code>config/config.php</code></li>
                </ol>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="../index.php" class="btn">Ir a la Tienda</a>
                <a href="../admin.php" class="btn">Panel de Administración</a>
            </div>
            
        <?php else: ?>
            <div class="status error">
                ✗ Error durante la instalación
            </div>
        <?php endif; ?>
        
        <h3>Log de instalación:</h3>
        <?php foreach($messages as $message): ?>
            <div class="message <?php echo (strpos($message, '✓') === 0) ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endforeach; ?>
        
        <?php if(!$success): ?>
            <div style="text-align: center; margin-top: 30px;">
                <a href="setup.php" class="btn">Reintentar Instalación</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
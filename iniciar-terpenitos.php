<?php
// Script de inicio rápido para Terpenitos Growshop
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌱 Terpenitos Growshop - Inicio Rápido</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f8f9fa; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 48px; margin-bottom: 10px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #28a745; background: #f8fff9; }
        .error { border-left-color: #dc3545; background: #fff8f8; }
        .warning { border-left-color: #ffc107; background: #fffdf7; }
        .success { border-left-color: #28a745; background: #f8fff9; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px; text-decoration: none; 
               border-radius: 5px; font-weight: bold; color: white; }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; }
        .status { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .loading { text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🌱</div>
            <h1>TERPENITOS GROWSHOP</h1>
            <p>Sistema de Inicio Rápido</p>
        </div>

        <?php
        $action = $_GET['action'] ?? 'check';
        
        if ($action === 'check') {
            echo '<div class="step">';
            echo '<h3>🔍 Verificando Sistema...</h3>';
            
            // Verificar archivos esenciales
            $files_ok = true;
            $essential_files = [
                'config/database.php' => 'Configuración de base de datos',
                'api.php' => 'API del sistema',
                'index.php' => 'Página principal',
                'img/banner.jpg' => 'Banner de la tienda'
            ];
            
            foreach ($essential_files as $file => $desc) {
                if (file_exists($file)) {
                    echo "<div class='status success'>✅ $desc</div>";
                } else {
                    echo "<div class='status error'>❌ $desc</div>";
                    $files_ok = false;
                }
            }
            
            // Verificar base de datos
            $db_ok = false;
            try {
                require_once 'config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                if ($db) {
                    echo "<div class='status success'>✅ Conexión a base de datos</div>";
                    
                    // Verificar tablas
                    $tables = ['users', 'products', 'categories', 'orders'];
                    $tables_ok = true;
                    foreach ($tables as $table) {
                        $stmt = $db->prepare("SHOW TABLES LIKE ?");
                        $stmt->execute([$table]);
                        if ($stmt->rowCount() > 0) {
                            echo "<div class='status success'>✅ Tabla '$table'</div>";
                        } else {
                            echo "<div class='status error'>❌ Tabla '$table'</div>";
                            $tables_ok = false;
                        }
                    }
                    
                    if ($tables_ok) {
                        $db_ok = true;
                    }
                } else {
                    echo "<div class='status error'>❌ No se puede conectar a la base de datos</div>";
                }
            } catch (Exception $e) {
                echo "<div class='status error'>❌ Error de base de datos: " . $e->getMessage() . "</div>";
            }
            
            echo '</div>';
            
            // Mostrar opciones según el estado
            if ($files_ok && $db_ok) {
                echo '<div class="step success">';
                echo '<h3>🎉 ¡Todo está listo!</h3>';
                echo '<p>Tu tienda Terpenitos Growshop está configurada correctamente.</p>';
                echo '<a href="index.php" class="btn btn-success">🏪 Ir a la Tienda</a>';
                echo '<a href="admin.php" class="btn btn-primary">⚙️ Panel Admin</a>';
                echo '<a href="api.php?controller=system&action=health" class="btn btn-warning">🔧 Test API</a>';
                echo '</div>';
            } else {
                echo '<div class="step error">';
                echo '<h3>🚨 Problemas Detectados</h3>';
                echo '<p>Se necesita ejecutar la instalación o reparación.</p>';
                echo '<a href="?action=install" class="btn btn-danger">🔧 Instalar/Reparar</a>';
                echo '<a href="install/setup.php" class="btn btn-warning">📦 Instalador Manual</a>';
                echo '</div>';
            }
            
        } elseif ($action === 'install') {
            echo '<div class="step">';
            echo '<h3>🔧 Ejecutando Instalación...</h3>';
            echo '<div class="loading">⏳ Procesando...</div>';
            
            // Redirigir al instalador y luego volver aquí
            echo '<script>';
            echo 'window.location.href = "install/setup.php?auto=true";';
            echo '</script>';
            echo '</div>';
        }
        ?>

        <div class="step">
            <h3>📚 Información Útil</h3>
            <p><strong>🔐 Credenciales por defecto:</strong></p>
            <ul>
                <li><strong>Admin:</strong> admin@terpenitos.com / admin123</li>
                <li><strong>Usuario:</strong> user@test.com / user123</li>
            </ul>
            
            <p><strong>🌐 URLs importantes:</strong></p>
            <ul>
                <li><strong>Tienda:</strong> <a href="index.php">http://localhost:8080</a></li>
                <li><strong>Admin:</strong> <a href="admin.php">http://localhost:8080/admin.php</a></li>
                <li><strong>API Test:</strong> <a href="api.php?controller=system&action=health">http://localhost:8080/api.php?controller=system&action=health</a></li>
            </ul>
        </div>

        <div class="step">
            <h3>🛠️ Herramientas</h3>
            <a href="?action=check" class="btn btn-primary">🔍 Verificar Sistema</a>
            <a href="verificar-instalacion.php" class="btn btn-warning">📋 Diagnóstico Completo</a>
            <a href="install/setup.php?force=true" class="btn btn-danger">🔄 Reinstalar</a>
        </div>
    </div>
</body>
</html>
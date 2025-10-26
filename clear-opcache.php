<?php
/**
 * Script para limpiar OPcache
 * 
 * Este script fuerza la limpieza del caché de PHP para asegurar
 * que los cambios en los archivos se apliquen inmediatamente.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Limpiar OPcache</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .success { background: #e8f5e9; padding: 20px; border-left: 4px solid #4CAF50; margin: 20px 0; }
        .error { background: #ffebee; padding: 20px; border-left: 4px solid #f44336; margin: 20px 0; }
        .info { background: #e3f2fd; padding: 20px; border-left: 4px solid #2196F3; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
        .btn:hover { background: #45a049; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧯 Limpiar Caché de PHP (OPcache)</h1>
        
        <?php
        if (isset($_GET['clear'])) {
            echo '<h2>Resultados de la Limpieza:</h2>';
            
            $cleared = false;
            $messages = [];
            
            // Intentar limpiar OPcache
            if (function_exists('opcache_reset')) {
                if (opcache_reset()) {
                    $messages[] = '✅ OPcache limpiado exitosamente';
                    $cleared = true;
                } else {
                    $messages[] = '⚠️ No se pudo limpiar OPcache (puede que no tengas permisos)';
                }
            } else {
                $messages[] = 'ℹ️ OPcache no está disponible en esta instalación de PHP';
            }
            
            // Intentar limpiar otros cachés
            if (function_exists('apc_clear_cache')) {
                apc_clear_cache();
                $messages[] = '✅ APC cache limpiado';
                $cleared = true;
            }
            
            if (function_exists('apcu_clear_cache')) {
                apcu_clear_cache();
                $messages[] = '✅ APCu cache limpiado';
                $cleared = true;
            }
            
            if ($cleared) {
                echo '<div class="success">';
                echo '<h3 style="margin-top: 0;">✅ Caché Limpiado</h3>';
                foreach ($messages as $msg) {
                    echo '<p>' . $msg . '</p>';
                }
                echo '<p><strong>Los cambios en los archivos PHP ahora deberían aplicarse inmediatamente.</strong></p>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<h3 style="margin-top: 0;">⚠️ No se pudo limpiar el caché</h3>';
                foreach ($messages as $msg) {
                    echo '<p>' . $msg . '</p>';
                }
                echo '<p>Opciones alternativas:</p>';
                echo '<ul>';
                echo '<li>Contacta a tu proveedor de hosting para que reinicien el servidor web</li>';
                echo '<li>Espera 5-10 minutos para que el caché expire automáticamente</li>';
                echo '<li>Reinicia PHP-FPM si tienes acceso al servidor</li>';
                echo '</ul>';
                echo '</div>';
            }
            
            echo '<a href="clear-opcache.php" class="btn">Volver</a>';
            echo '<a href="../admin.php" class="btn" style="background: #2196F3;">Ir al Panel de Admin</a>';
            
        } else {
            // Mostrar información del OPcache
            echo '<h2>Estado Actual de OPcache:</h2>';
            
            if (function_exists('opcache_get_status')) {
                $status = opcache_get_status();
                
                if ($status !== false) {
                    echo '<div class="info">';
                    echo '<h3 style="margin-top: 0;">📈 OPcache Está Activo</h3>';
                    echo '<pre>';
                    echo 'Memoria utilizada: ' . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . ' MB<br>';
                    echo 'Memoria libre: ' . round($status['memory_usage']['free_memory'] / 1024 / 1024, 2) . ' MB<br>';
                    echo 'Archivos cacheados: ' . $status['opcache_statistics']['num_cached_scripts'] . '<br>';
                    echo 'Hits: ' . $status['opcache_statistics']['hits'] . '<br>';
                    echo 'Misses: ' . $status['opcache_statistics']['misses'] . '<br>';
                    echo '</pre>';
                    echo '<p><strong>El caché puede estar sirviendo versiones antiguas de tus archivos PHP.</strong></p>';
                    echo '</div>';
                } else {
                    echo '<div class="info">';
                    echo '<p>OPcache está instalado pero no se puede obtener su estado.</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="info">';
                echo '<p>ℹ️ OPcache no está disponible en esta instalación de PHP.</p>';
                echo '</div>';
            }
            
            echo '<h2>¿Por qué limpiar el caché?</h2>';
            echo '<div class="info">';
            echo '<p>Cuando actualizas archivos PHP (como <code>Product.php</code> o <code>ProductController.php</code>), ';
            echo 'OPcache puede seguir sirviendo la versión antigua del código que tiene en memoria.</p>';
            echo '<p>Limpiar el caché fuerza a PHP a recargar los archivos desde el disco, aplicando tus cambios inmediatamente.</p>';
            echo '</div>';
            
            echo '<h2>¿Cuándo usar este script?</h2>';
            echo '<div class="info">';
            echo '<ul>';
            echo '<li>Después de subir archivos PHP actualizados al servidor</li>';
            echo '<li>Si los cambios en el código no se reflejan en la aplicación</li>';
            echo '<li>Si sigues obteniendo errores 500 después de corregir el código</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<a href="?clear=1" class="btn" style="background: #f44336; font-size: 18px;">🧯 Limpiar Caché Ahora</a>';
        }
        ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999;">
            <small>Script ejecutado el <?php echo date('Y-m-d H:i:s'); ?></small>
        </div>
    </div>
</body>
</html>
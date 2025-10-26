<?php 
require_once 'config/config.php';
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Terpenitos Growshop</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="custom-styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body class="body-background-image">

    <header class="main-header">
        <nav class="container main-nav">
            <a href="index.php" class="logo">
                <img src="img/logo.png" alt="Terpenitos Logo">
                <span>Terpenitos</span>
            </a>
            <div class="nav-actions button-spacing">
                <a href="index.php" class="btn btn-primary btn-profile-back">Volver a la Tienda</a>
                <div id="user-menu-container" class="user-menu-wrapper hidden">
                    <button onclick="toggleUserMenu()" class="user-menu-button">
                        <div id="user-avatar" class="avatar">U</div>
                    </button>
                    <div id="user-menu" class="user-menu">
                        <a href="index.php" onclick="closeUserMenu()">Tienda</a>
                        <hr>
                        <button onclick="logout()">Cerrar Sesión</button>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
        <div class="main-content-wrapper">
            <div class="profile-header">
                <h1>Mi Cuenta</h1>
                <p>Gestiona tu información personal, pedidos y configuración.</p>
            </div>

            <div class="profile-container">
                <nav class="profile-tab-nav">
                    <button onclick="switchProfileTab('info')" id="tab-info" class="profile-tab">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>Información</span>
                    </button>
                    <button onclick="switchProfileTab('pedidos')" id="tab-pedidos" class="profile-tab">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        <span>Mis Pedidos</span>
                    </button>
                    <button onclick="switchProfileTab('direcciones')" id="tab-direcciones" class="profile-tab">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Direcciones</span>
                    </button>
                    <button onclick="switchProfileTab('seguridad')" id="tab-seguridad" class="profile-tab">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <span>Seguridad</span>
                    </button>
                </nav>

                <div class="tab-content-container">
                    <div id="content-info" class="tab-content">
                        <h2 class="tab-title">Detalles del Perfil</h2>
                        <form id="profileForm" class="profile-form" onsubmit="handleProfileUpdate(event)">
                            <div class="form-group">
                                <label for="profileName">Nombre</label>
                                <input type="text" id="profileName" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="profileEmail">Email</label>
                                <input type="email" id="profileEmail" class="form-input" required disabled>
                                <p class="form-hint">El email no se puede modificar.</p>
                            </div>
                            <div class="form-group">
                                <label for="profilePhone">Teléfono</label>
                                <input type="tel" id="profilePhone" class="form-input" required>
                            </div>
                            <input type="hidden" id="profileCsrfToken" value="<?php echo $csrf_token; ?>">
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                    
                    <div id="content-pedidos" class="tab-content">
                        <h2 class="tab-title">Historial de Pedidos</h2>
                        <div id="orders-container" class="orders-list"></div>
                        <div id="no-orders" class="notice notice-info hidden">
                            <p>Aún no has realizado ningún pedido. <a href="index.php" class="link">¡Empieza a comprar!</a></p>
                        </div>
                    </div>
                    
                    <div id="content-direcciones" class="tab-content">
                        <div class="flex justify-between items-center mb-8">
                            <h2 class="tab-title" style="margin-bottom: 0;">Mis Direcciones</h2>
                            <button onclick="openAddressModal()" class="btn btn-secondary">+ Nueva Dirección</button>
                        </div>
                        <div id="addresses-container" class="address-display-list"></div>
                        <div id="no-addresses" class="notice notice-info hidden" style="margin-top: 1.5rem;">
                            <p>No tienes ninguna dirección guardada. ¡Añade una para facilitar tus futuras compras!</p>
                        </div>
                    </div>
                    
                    <div id="content-seguridad" class="tab-content">
                        <h2 class="tab-title">Cambiar Contraseña</h2>
                        <form id="passwordForm" class="profile-form" onsubmit="handlePasswordUpdate(event)">
                            <div class="form-group">
                                <label for="currentPassword">Contraseña Actual</label>
                                <input type="password" id="currentPassword" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="newPassword">Nueva Contraseña</label>
                                <input type="password" id="newPassword" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirmar Contraseña</label>
                                <input type="password" id="confirmPassword" class="form-input" required>
                            </div>
                            <input type="hidden" id="passwordCsrfToken" value="<?php echo $csrf_token; ?>">
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Address Modal -->
    <div id="addressModal" class="modal">
        <div class="modal-content modal-glass">
            <div class="modal-header">
                <h2 id="addressModalTitle">Nueva Dirección</h2>
                <button onclick="closeModal('addressModal')" class="close-modal-btn">&times;</button>
            </div>
            <form id="addressForm" onsubmit="handleAddressSave(event)">
                <input type="hidden" id="addressId">
                <div class="form-group">
                    <label for="addressAlias">Alias (ej: Casa, Trabajo)</label>
                    <input type="text" id="addressAlias" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="addressLine1">Dirección (Calle y número)</label>
                    <input type="text" id="addressLine1" class="form-input" required>
                </div>
                <div class="form-grid-double">
                    <div class="form-group">
                        <label for="addressCity">Ciudad</label>
                        <input type="text" id="addressCity" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="addressZip">Código Postal</label>
                        <input type="text" id="addressZip" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="addressIsDefault">
                        <span class="checkmark"></span>
                        Establecer como dirección predeterminada
                    </label>
                </div>
                <input type="hidden" id="addressCsrfToken" value="<?php echo $csrf_token; ?>">
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary w-full">Guardar Dirección</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="orderDetailsTitle">Detalles del Pedido</h2>
                <button onclick="closeModal('orderDetailsModal')" class="close-modal-btn">&times;</button>
            </div>
            <div class="order-details-body">
                <div id="orderDetailsInfo" class="order-details-grid"></div>
                <div id="orderDetailsShipping" class="order-details-section"></div>
                <div class="order-details-section">
                    <h3 class="section-subtitle">Productos en este pedido</h3>
                    <div id="orderProductsList" class="order-products-list"></div>
                </div>
                <div id="orderDetailsTotals" class="order-details-totals"></div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification">
        <svg class="notification-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span id="notification-text"></span>
    </div>

    <script>
        // Configuración global
        window.API_BASE_URL = 'api.php';
        window.CSRF_TOKEN = '<?php echo $csrf_token; ?>';
        window.IS_PROFILE_PAGE = true;
    </script>
    <script src="js/app.js"></script>
</body>
</html>
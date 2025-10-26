<?php 
require_once 'config/config.php';
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Terpenitos</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="custom-styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body class="body-background-image">

    <div id="adminLoginModal" class="modal" style="display: flex;">
        <div class="modal-content" style="max-width: 420px;">
            <div class="modal-header"><h2>Acceso de Administrador</h2></div>
            <form onsubmit="handleAdminLogin(event)">
                <div class="form-group">
                    <label for="adminLoginEmail">Email</label>
                    <input id="adminLoginEmail" type="email" class="form-input" required value="admin@terpenitos.com">
                </div>
                <div class="form-group">
                    <label for="adminLoginPassword">Contraseña</label>
                    <input id="adminLoginPassword" type="password" class="form-input" required value="admin123">
                </div>
                <input type="hidden" id="adminLoginCsrfToken" value="<?php echo $csrf_token; ?>">
                <button type="submit" class="btn btn-primary w-full">Iniciar Sesión</button>
            </form>
        </div>
    </div>

    <main id="main-content" class="container" style="display: none; padding-top: 2rem; padding-bottom: 4rem;">
        <header class="admin-header">
            <h1>Panel de Administración</h1>
            <div>
                <a href="index.php" class="btn btn-neutral" style="margin-right: 10px;">Ver Tienda</a>
                <button onclick="logoutAdmin()" class="btn btn-danger">Cerrar Sesión</button>
            </div>
        </header>

        <div class="admin-section-wrapper">
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-icon icon-capital">💰</div>
                    <div><h3>Capital en Stock</h3><p id="dashboard-capital">$0.00</p></div>
                </div>
                <div class="dashboard-card">
                    <div class="card-icon icon-stock">📦</div>
                    <div><h3>Unidades en Stock</h3><p id="dashboard-stock">0</p></div>
                </div>
                <div class="dashboard-card">
                    <div class="card-icon icon-revenue">📈</div>
                    <div><h3>Facturación Total</h3><p id="dashboard-revenue">$0.00</p></div>
                </div>
                <div class="dashboard-card">
                    <div class="card-icon icon-profit">💸</div>
                    <div><h3>Ganancia Estimada</h3><p id="dashboard-profit">$0.00</p></div>
                </div>
                <div class="dashboard-card">
                    <div class="card-icon icon-pending">⏳</div>
                    <div><h3>Pedidos Pendientes</h3><p id="dashboard-pending-orders">0</p></div>
                </div>
                <div class="dashboard-card">
                    <div class="card-icon icon-shipped">🚚</div>
                    <div><h3>Pedidos Entregados</h3><p id="dashboard-shipped-orders">0</p></div>
                </div>
            </div>
        </div>

        <div class="admin-section-wrapper">
            <div class="admin-section-header">
                <h2>Gestión de Productos</h2>
                <button onclick="openProductModal()" class="btn btn-primary">+ Agregar Producto</button>
            </div>
            <div class="admin-controls">
                <label for="categoryFilter">Filtrar por categoría:</label>
                <select id="categoryFilter" class="form-input"></select>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Destacado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="products-table"></tbody>
                </table>
            </div>
        </div>

        <div class="admin-section-wrapper">
            <div class="admin-section-header">
                <h2>Gestión de Categorías</h2>
                <button onclick="openCategoryModal()" class="btn btn-secondary">+ Agregar Categoría</button>
            </div>
            <div id="categories-grid" class="category-list"></div>
        </div>

        <div class="admin-section-wrapper">
            <div class="admin-section-header">
                <h2>Gestión de Pedidos</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="orders-table"></tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="productModalTitle"></h2>
                <button onclick="closeProductModal()" class="close-modal-btn">&times;</button>
            </div>
            <form id="productForm" onsubmit="handleProductFormSubmit(event)">
                <div class="form-group">
                    <label for="productName">Nombre</label>
                    <input id="productName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="productDescription">Descripción</label>
                    <textarea id="productDescription" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="productCategory">Categoría</label>
                    <select id="productCategory" class="form-input" required></select>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="productPrice">Precio (Venta)</label>
                        <input id="productPrice" type="number" step="0.01" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="productCost">Costo (Compra)</label>
                        <input id="productCost" type="number" step="0.01" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="productStock">Stock</label>
                    <input id="productStock" type="number" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="productImage">Nombre Imagen (ej: mi-producto.jpg)</label>
                    <input id="productImage" type="text" class="form-input">
                    <p class="form-hint">La imagen debe estar en `img/productos/`.</p>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="productIsFeatured">
                        <span class="checkmark"></span>
                        Producto destacado
                    </label>
                </div>
                <input type="hidden" id="productId">
                <input type="hidden" id="productCsrfToken" value="<?php echo $csrf_token; ?>">
                <div class="modal-actions">
                    <button type="button" onclick="closeProductModal()" class="btn btn-neutral">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content" style="max-width: 480px;">
            <div class="modal-header">
                <h2 id="categoryModalTitle"></h2>
                <button onclick="closeCategoryModal()" class="close-modal-btn">&times;</button>
            </div>
            <form id="categoryForm" onsubmit="handleCategoryFormSubmit(event)">
                <input type="hidden" id="categoryId">
                <div class="form-group">
                    <label for="categoryName">Nombre de la categoría</label>
                    <input id="categoryName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="categoryImage">Nombre Imagen (ej: fertilizantes.jpg)</label>
                    <input id="categoryImage" type="text" class="form-input">
                    <p class="form-hint">La imagen debe estar en la carpeta `img/categorias/`.</p>
                </div>
                <input type="hidden" id="categoryCsrfToken" value="<?php echo $csrf_token; ?>">
                <div class="modal-actions">
                    <button type="button" onclick="closeCategoryModal()" class="btn btn-neutral">Cancelar</button>
                    <button type="submit" class="btn btn-secondary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="orderDetailsTitle"></h2>
                <button onclick="closeOrderDetailsModal()" class="close-modal-btn">&times;</button>
            </div>
            <div class="order-details-body">
                <div id="orderDetailsInfo" class="order-details-grid"></div>
                <div id="orderDetailsShipping" class="order-details-section"></div>
                <div class="order-details-section">
                    <h3 class="section-subtitle">Productos en este pedido</h3>
                    <div id="orderProductsList" class="order-products-list"></div>
                </div>
                <div id="orderDetailsTotals" class="order-details-totals"></div>
                <div class="order-actions">
                    <select id="orderStatusSelect" class="form-input">
                        <option value="Procesando">Procesando</option>
                        <option value="Enviado">Enviado</option>
                        <option value="Entregado">Entregado</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                    <button onclick="updateOrderStatus()" class="btn btn-primary">Actualizar Estado</button>
                </div>
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
        window.IS_ADMIN_PAGE = true;
    </script>
    <script src="js/app.js"></script>
</body>
</html>
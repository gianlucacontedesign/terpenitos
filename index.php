<?php 
require_once 'config/config.php';
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terpenitos Growshop - Todo para tu cultivo</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="/custom-styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body class="body-background-image">

    <header class="main-header">
        <nav class="container main-nav">
            <a href="index.php" class="logo">
                <img src="img/logo.png" alt="Terpenitos Logo">
            </a>
            <div class="nav-links">
                <a href="index.php">Inicio</a>
                <a href="#categorias">Categorías</a>
                <a href="#productos">Productos</a>
            </div>
            <div class="nav-actions">
                <div id="auth-buttons" class="auth-actions button-spacing">
                    <button onclick="openLoginModal()" class="btn btn-neutral">Iniciar Sesión</button>
                    <button onclick="openRegisterModal()" class="btn btn-primary">Registrarse</button>
                </div>
                <div id="user-menu-container" class="user-menu-wrapper hidden">
                    <button onclick="toggleUserMenu()" class="user-menu-button">
                        <div id="user-avatar" class="avatar">U</div>
                    </button>
                    <div id="user-menu" class="user-menu">
                        <a href="perfil.php" onclick="closeUserMenu()">Mi Perfil</a>
                        <hr>
                        <button onclick="logout()">Cerrar Sesión</button>
                    </div>
                </div>
                <button onclick="openCart()" class="cart-button" aria-label="Abrir carrito">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span id="cart-count" class="cart-badge hidden">0</span>
                </button>
                <button id="mobile-menu-button" class="mobile-menu-toggle" aria-label="Abrir menú móvil">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </nav>
        <div id="mobile-menu" class="mobile-nav-links hidden">
            <a href="index.php" onclick="closeMobileMenu()">Inicio</a>
            <a href="#categorias" onclick="closeMobileMenu()">Categorías</a>
            <a href="#productos" onclick="closeMobileMenu()">Productos</a>
        </div>
    </header>

    <main>
        <section id="inicio" class="hero-section">
            <div class="hero-content">
                <h1>TERPENITOS <span class="highlight">GROWSHOP</span></h1>
                <p>Somos un grow de cultivadores para cultivadores, enfocados en cultivo orgánico. Ofrecemos asesoramientos para cultivo de interior, exterior, salas y ONGs.</p>
                <div class="hero-actions button-spacing">
                    <a href="#categorias" class="btn btn-light">Ver Categorías</a>
                    <a href="#contacto" class="btn btn-outline">Contactar Asesor</a>
                </div>
            </div>
        </section>

        <div class="container">
            <div class="main-content-wrapper">
                <section id="categorias" class="content-section">
                    <div class="store-section-header">
                        <h2>Nuestras Categorías</h2>
                    </div>
                    <div id="categorias-grid" class="category-grid"></div>
                </section>

                <section id="category-products-section" class="content-section hidden">
                    <div class="store-section-header">
                        <h2 id="category-title"></h2>
                    </div>
                    <div class="category-nav-controls">
                        <select id="category-selector" onchange="changeCategoryFromSelector()" class="form-input" style="width: auto;"></select>
                        <button onclick="hideProducts()" class="btn-back-to-categories">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            <span>Volver a categorías</span>
                        </button>
                    </div>
                    <div class="controls-container">
                        <div class="controls-wrapper">
                            <div class="search-wrapper">
                                <input type="text" id="search-category" placeholder="Buscar en esta categoría..." class="form-input">
                                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <select id="sort-category" class="form-input" style="width: auto;">
                                <option value="default">Ordenar por</option>
                                <option value="price-asc">Precio: Menor a Mayor</option>
                                <option value="price-desc">Precio: Mayor a Menor</option>
                                <option value="name-asc">Nombre: A-Z</option>
                                <option value="name-desc">Nombre: Z-A</option>
                            </select>
                        </div>
                    </div>
                    <div id="products-grid" class="product-grid"></div>
                    <div id="category-pagination" class="pagination"></div>
                </section>

                <section id="productos" class="content-section">
                    <div class="store-section-header">
                        <h2>Productos Destacados</h2>
                    </div>
                    <div class="controls-container">
                        <div class="controls-wrapper">
                            <div class="search-wrapper">
                                <input type="text" id="search-featured" placeholder="Buscar en destacados..." class="form-input">
                                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <select id="sort-featured" class="form-input" style="width: auto;">
                                <option value="default">Ordenar por</option>
                                <option value="price-asc">Precio: Menor a Mayor</option>
                                <option value="price-desc">Precio: Mayor a Menor</option>
                                <option value="name-asc">Nombre: A-Z</option>
                                <option value="name-desc">Nombre: Z-A</option>
                            </select>
                        </div>
                    </div>
                    <div id="featured-products-grid" class="product-grid"></div>
                    <div id="featured-pagination" class="pagination"></div>
                </section>

                <section id="contacto" class="content-section">
                    <div class="store-section-header">
                        <h2>¿Tienes alguna consulta?</h2>
                    </div>
                    <p class="text-center" style="color: rgba(255, 255, 255, 0.8); margin-top: -1.5rem; margin-bottom: 2rem;">Estamos aquí para ayudarte. Envíanos un mensaje y te responderemos a la brevedad.</p>
                    <div class="contact-grid-new">
                        <form action="https://formspree.io/f/tu_codigo_aqui" method="POST" class="contact-form-new">
                            <div class="form-group"><label for="contact-name">Tu Nombre</label><input type="text" id="contact-name" name="name" class="form-input" required></div>
                            <div class="form-group"><label for="contact-email">Tu Email</label><input type="email" id="contact-email" name="email" class="form-input" required></div>
                            <div class="form-group"><label for="contact-message">Tu Mensaje</label><textarea id="contact-message" name="message" class="form-input" rows="5" required></textarea></div>
                            <button type="submit" class="btn btn-primary w-full">Enviar Mensaje</button>
                        </form>
                        <div class="contact-info-new">
                            <h3>O contáctanos directamente</h3>
                            <ul class="contact-list-new">
                                <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg><a href="mailto:info@terpenitos.com">info@terpenitos.com</a></li>
                                <li><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg><a href="https://wa.me/5491165716376" target="_blank">11 6571-6376</a></li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>&copy; 2025 Terpenitos Growshop. Todos los derechos reservados.</p>
            <p>Libertad 4, Lun a Jue 10:00 a 21:30 - Vie a Sab 10:00 a 22:00</p>
        </div>
        <a href="https://wa.me/5491165716376" class="whatsapp-float" target="_blank" rel="noopener noreferrer" aria-label="Contactar por WhatsApp">
            <svg viewBox="0 0 90 90" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M90,43.841c0,24.213-19.779,43.841-44.182,43.841c-7.747,0-15.025-1.98-21.357-5.455L0,90l7.975-23.522   c-4.023-6.606-6.34-14.354-6.34-22.637C1.635,19.628,21.416,0,45.818,0C70.223,0,90,19.628,90,43.841z M45.818,6.982   c-20.484,0-37.146,16.535-37.146,36.859c0,8.065,2.629,15.534,7.076,21.61l-4.64,13.688l14.274-4.537   c5.865,3.851,12.891,6.097,20.437,6.097c20.481,0,37.146-16.533,37.146-36.857S66.301,6.982,45.818,6.982z M68.129,53.938   c-0.273-0.455-0.994-0.75-2.067-1.32c-1.076-0.57-6.36-3.138-7.346-3.495c-0.984-0.358-1.706-0.572-2.427,0.571   c-0.722,1.142-2.793,3.495-3.43,4.216c-0.636,0.722-1.273,0.819-2.348,0.273c-1.075-0.545-4.543-1.673-8.654-5.333   c-3.201-2.848-5.368-6.36-6.005-7.431c-0.638-1.071-0.068-1.646,0.486-2.191c0.488-0.482,1.075-1.273,1.613-1.91   c0.537-0.636,0.722-1.071,1.075-1.792c0.358-0.722,0.182-1.364-0.09-1.932c-0.273-0.57-2.427-5.819-3.34-8.005   c-0.912-2.188-1.823-1.886-2.427-1.886c-0.606,0-1.32,0.273-2.067,0.273c-0.75,0-1.97,0.273-3.002,1.364   c-1.031,1.091-3.93,3.852-3.93,9.382c0,5.531,4.02,10.876,4.568,11.6c0.545,0.722,7.886,12.04,19.115,16.817   c11.228,4.773,11.228,3.18,13.256,2.998c2.028-0.182,6.36-2.592,7.254-5.106c0.894-2.515,0.894-4.66,0.636-5.106   C68.881,54.48,68.402,54.388,68.129,53.938z"/></svg>
        </a>
    </footer>
    
    <!-- Cart Sidebar -->
    <aside id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header"><h2>Carrito</h2><button onclick="closeCart()" class="close-modal-btn">&times;</button></div>
        <div id="cart-items" class="cart-body">
            <div id="empty-cart" class="empty-cart-message">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <h3>Tu carrito está vacío</h3>
            </div>
            <div id="cart-products"></div>
        </div>
        <div id="cart-footer" class="cart-footer hidden">
            <div class="cart-summary">
                <div class="cart-summary-row grand-total">
                    <strong>Total</strong>
                    <strong id="cart-total-price">$0</strong>
                </div>
            </div>
            <div class="cart-actions button-spacing">
                <button onclick="checkout()" class="btn btn-primary w-full">Finalizar Compra</button>
                <button onclick="clearCart()" class="btn btn-neutral w-full">Vaciar Carrito</button>
            </div>
        </div>
    </aside>
    <div id="cart-overlay" class="cart-overlay" onclick="closeCart()"></div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Iniciar Sesión</h2>
                <button onclick="closeModal('loginModal')" class="close-modal-btn">&times;</button>
            </div>
            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Contraseña</label>
                    <input type="password" id="loginPassword" class="form-input" required>
                </div>
                <input type="hidden" id="loginCsrfToken" value="<?php echo $csrf_token; ?>">
                <button type="submit" class="btn btn-primary w-full">Iniciar Sesión</button>
                <p class="modal-footer-text">¿No tienes cuenta? <button type="button" onclick="switchToRegister()" class="link">Regístrate</button></p>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Crear Cuenta</h2>
                <button onclick="closeModal('registerModal')" class="close-modal-btn">&times;</button>
            </div>
            <form id="registerForm" onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label for="registerName">Nombre</label>
                    <input type="text" id="registerName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="registerEmail">Email</label>
                    <input type="email" id="registerEmail" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="registerPhone">Teléfono</label>
                    <input type="tel" id="registerPhone" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="registerPassword">Contraseña</label>
                    <input type="password" id="registerPassword" class="form-input" required>
                </div>
                <input type="hidden" id="registerCsrfToken" value="<?php echo $csrf_token; ?>">
                <button type="submit" class="btn btn-primary w-full">Crear Cuenta</button>
                <p class="modal-footer-text">¿Ya tienes cuenta? <button type="button" onclick="switchToLogin()" class="link">Inicia sesión</button></p>
            </form>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Finalizar Compra</h2>
                <button onclick="closeModal('checkoutModal')" class="close-modal-btn">&times;</button>
            </div>
            <div id="checkout-content">
                <form id="checkoutForm" onsubmit="handleCheckout(event)">
                    <div class="form-group">
                        <label for="checkoutAddress">Dirección de envío</label>
                        <textarea id="checkoutAddress" class="form-input" rows="3" required placeholder="Ingresa tu dirección completa..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="checkoutPhone">Teléfono de contacto</label>
                        <input type="tel" id="checkoutPhone" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="checkoutNotes">Notas adicionales (opcional)</label>
                        <textarea id="checkoutNotes" class="form-input" rows="2" placeholder="Instrucciones de entrega, horarios preferidos, etc."></textarea>
                    </div>
                    <div id="checkout-summary" class="checkout-summary"></div>
                    <input type="hidden" id="checkoutCsrfToken" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="btn btn-primary w-full">Confirmar Pedido</button>
                </form>
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
    </script>
    <script src="js/app.js"></script>
</body>
</html>
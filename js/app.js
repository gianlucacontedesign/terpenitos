// Configuración global y estado de la aplicación
const PRODUCTS_PER_PAGE = 8;
let currentUser = null;
let allProducts = [];
let allCategories = [];
let cart = JSON.parse(localStorage.getItem('terpenitos_cart')) || [];

let storeState = {
    featured: { searchTerm: '', sortOrder: 'default', currentPage: 1 },
    category: { currentCategory: null, searchTerm: '', sortOrder: 'default', currentPage: 1 }
};

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', async () => {
    await initializeApp();
});

// Inicializar aplicación
async function initializeApp() {
    try {
        // Verificar si el usuario está logueado
        await checkUserLogin();
        
        // Cargar datos básicos
        await loadCategories();
        await loadProducts();
        
        // Inicializar según el tipo de página
        if (window.IS_ADMIN_PAGE) {
            await initializeAdminPanel();
        } else if (window.IS_PROFILE_PAGE) {
            await initializeProfilePage();
        } else {
            await initializeStorePage();
        }
        
        // Configurar listeners globales
        setupGlobalListeners();
        
        // Actualizar UI del carrito
        updateCartUI();
        
    } catch (error) {
        console.error('Error al inicializar la aplicación:', error);
        showNotification('Error al cargar la aplicación', true);
    }
}

// Configurar listeners globales
function setupGlobalListeners() {
    // Menú móvil
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', toggleMobileMenu);
    }
    
    // Cerrar modales al hacer clic fuera
    window.addEventListener('click', (event) => {
        const userMenuContainer = document.getElementById('user-menu-container');
        if (userMenuContainer && !userMenuContainer.contains(event.target)) {
            closeUserMenu();
        }
        
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });
    
    // Escape para cerrar modales
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });
}

// ============ API FUNCTIONS ============

// Función genérica para hacer llamadas a la API
async function apiCall(endpoint, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin' // Enviar cookies de sesión con las peticiones
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(`${window.API_BASE_URL}?${endpoint}`, finalOptions);
        const data = await response.json();
        
        if (!response.ok) {
            // Crear un error que incluya tanto el mensaje como los datos completos
            const error = new Error(data.message || `HTTP error! status: ${response.status}`);
            error.response = data; // Agregar la respuesta completa al error
            throw error;
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ============ AUTH FUNCTIONS ============

// Verificar si el usuario está logueado
async function checkUserLogin() {
    try {
        const response = await apiCall('controller=auth&action=getUser');
        if (response.success) {
            currentUser = response.user;
            updateAuthUI(true);
        } else {
            currentUser = null;
            updateAuthUI(false);
        }
    } catch (error) {
        currentUser = null;
        updateAuthUI(false);
    }
}

// Actualizar UI de autenticación
function updateAuthUI(isLoggedIn) {
    const authButtons = document.getElementById('auth-buttons');
    const userMenuContainer = document.getElementById('user-menu-container');
    
    if (isLoggedIn && currentUser) {
        if (authButtons) authButtons.classList.add('hidden');
        if (userMenuContainer) {
            userMenuContainer.classList.remove('hidden');
            const avatar = document.getElementById('user-avatar');
            if (avatar) {
                avatar.textContent = currentUser.name.charAt(0).toUpperCase();
            }
        }
        
        // Redireccionar admin si está en página normal
        if (currentUser.is_admin && !window.IS_ADMIN_PAGE && window.location.pathname.includes('admin.php')) {
            document.getElementById('adminLoginModal').style.display = 'none';
            document.getElementById('main-content').style.display = 'block';
        }
    } else {
        if (authButtons) authButtons.classList.remove('hidden');
        if (userMenuContainer) userMenuContainer.classList.add('hidden');
        
        // Redireccionar a login si está en página de perfil
        if (window.IS_PROFILE_PAGE) {
            window.location.href = 'index.php';
        }
    }
}

// Manejar login
async function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const csrfToken = document.getElementById('loginCsrfToken').value;
    
    try {
        const response = await apiCall('controller=auth&action=login', {
            method: 'POST',
            body: JSON.stringify({
                email: email,
                password: password,
                csrf_token: csrfToken
            })
        });
        
        if (response.success) {
            currentUser = response.user;
            showNotification(`¡Bienvenido, ${currentUser.name.split(' ')[0]}!`);
            closeModal('loginModal');
            updateAuthUI(true);
            
            // Redireccionar admin
            if (currentUser.is_admin && window.IS_ADMIN_PAGE) {
                document.getElementById('adminLoginModal').style.display = 'none';
                document.getElementById('main-content').style.display = 'block';
                await initializeAdminPanel();
            }
        }
    } catch (error) {
        showNotification('Email o contraseña incorrectos', true);
    }
}

// Manejar registro
async function handleRegister(event) {
    event.preventDefault();
    
    const name = document.getElementById('registerName').value;
    const email = document.getElementById('registerEmail').value;
    const phone = document.getElementById('registerPhone').value;
    const password = document.getElementById('registerPassword').value;
    const csrfToken = document.getElementById('registerCsrfToken').value;
    
    if (password.length < 6) {
        showNotification('La contraseña debe tener al menos 6 caracteres', true);
        return;
    }
    
    try {
        const response = await apiCall('controller=auth&action=register', {
            method: 'POST',
            body: JSON.stringify({
                name: name,
                email: email,
                phone: phone,
                password: password,
                csrf_token: csrfToken
            })
        });
        
        if (response.success) {
            showNotification('¡Cuenta creada con éxito! Ahora puedes iniciar sesión.');
            switchToLogin();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Manejar login de admin
async function handleAdminLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('adminLoginEmail').value;
    const password = document.getElementById('adminLoginPassword').value;
    const csrfToken = document.getElementById('adminLoginCsrfToken').value;
    
    try {
        const response = await apiCall('controller=auth&action=login', {
            method: 'POST',
            body: JSON.stringify({
                email: email,
                password: password,
                csrf_token: csrfToken
            })
        });
        
        if (response.success && response.user.is_admin) {
            currentUser = response.user;
            document.getElementById('adminLoginModal').style.display = 'none';
            document.getElementById('main-content').style.display = 'block';
            showNotification('Acceso de administrador concedido');
            await initializeAdminPanel();
        } else {
            showNotification('Credenciales de administrador incorrectas', true);
        }
    } catch (error) {
        showNotification('Credenciales de administrador incorrectas', true);
    }
}

// Logout
async function logout() {
    try {
        await apiCall('controller=auth&action=logout', { method: 'POST' });
        currentUser = null;
        updateAuthUI(false);
        showNotification('Has cerrado sesión');
        
        if (window.IS_PROFILE_PAGE || window.IS_ADMIN_PAGE) {
            window.location.href = 'index.php';
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    }
}

// Logout admin
function logoutAdmin() {
    logout();
}

// ============ PRODUCTS & CATEGORIES ============

// Cargar categorías
async function loadCategories() {
    try {
        const response = await apiCall('controller=category&action=getAll');
        if (response.success) {
            allCategories = response.categories;
            renderCategories();
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

// Cargar productos
async function loadProducts() {
    try {
        const response = await apiCall('controller=product&action=getAll');
        if (response.success) {
            allProducts = response.products;
        }
    } catch (error) {
        console.error('Error al cargar productos:', error);
    }
}

// Renderizar categorías
function renderCategories() {
    const grid = document.getElementById('categorias-grid');
    const selector = document.getElementById('category-selector');
    
    if (!grid) return;
    
    grid.innerHTML = '';
    if (selector) selector.innerHTML = '';
    
    allCategories.forEach(category => {
        const imageUrl = category.image ? `img/categorias/${category.image}` : '';
        const styleAttribute = imageUrl ? `style="background-image: url('${imageUrl}')"` : '';
        
        grid.innerHTML += `
            <div onclick="showProductsByCategory('${category.name}', ${category.id})" 
                 class="category-cell card-hover ${imageUrl ? 'has-image' : ''}" 
                 ${styleAttribute}>
                <div class="category-cell-overlay"></div>
                <h3 class="category-cell-title">${category.name}</h3>
            </div>
        `;
        
        if (selector) {
            selector.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        }
    });
}

// ============ STORE PAGE FUNCTIONS ============

// Inicializar página de tienda
async function initializeStorePage() {
    renderProductSection('featured');
    
    // Configurar listeners de búsqueda y filtros
    setupProductListeners();
}

// Configurar listeners de productos
function setupProductListeners() {
    const searchFeatured = document.getElementById('search-featured');
    const sortFeatured = document.getElementById('sort-featured');
    const searchCategory = document.getElementById('search-category');
    const sortCategory = document.getElementById('sort-category');
    
    if (searchFeatured) {
        searchFeatured.addEventListener('input', debounce((e) => {
            storeState.featured.searchTerm = e.target.value;
            storeState.featured.currentPage = 1;
            renderProductSection('featured');
        }, 300));
    }
    
    if (sortFeatured) {
        sortFeatured.addEventListener('change', (e) => {
            storeState.featured.sortOrder = e.target.value;
            renderProductSection('featured');
        });
    }
    
    if (searchCategory) {
        searchCategory.addEventListener('input', debounce((e) => {
            storeState.category.searchTerm = e.target.value;
            storeState.category.currentPage = 1;
            renderProductSection('category');
        }, 300));
    }
    
    if (sortCategory) {
        sortCategory.addEventListener('change', (e) => {
            storeState.category.sortOrder = e.target.value;
            renderProductSection('category');
        });
    }
}

// Renderizar sección de productos
function renderProductSection(sectionType) {
    let productsToRender = [];
    let state = storeState[sectionType];
    
    if (sectionType === 'featured') {
        productsToRender = allProducts.filter(p => p.is_featured == 1);
    } else if (sectionType === 'category' && state.currentCategory) {
        productsToRender = allProducts.filter(p => p.category_id == state.currentCategory);
    }
    
    // Aplicar búsqueda
    if (state.searchTerm) {
        const searchTermLower = state.searchTerm.toLowerCase();
        productsToRender = productsToRender.filter(p => 
            p.name.toLowerCase().includes(searchTermLower) || 
            (p.description && p.description.toLowerCase().includes(searchTermLower))
        );
    }
    
    // Aplicar ordenamiento
    switch (state.sortOrder) {
        case 'price-asc':
            productsToRender.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
            break;
        case 'price-desc':
            productsToRender.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
            break;
        case 'name-asc':
            productsToRender.sort((a, b) => a.name.localeCompare(b.name));
            break;
        case 'name-desc':
            productsToRender.sort((a, b) => b.name.localeCompare(a.name));
            break;
    }
    
    // Paginación
    const totalPages = Math.ceil(productsToRender.length / PRODUCTS_PER_PAGE);
    const startIndex = (state.currentPage - 1) * PRODUCTS_PER_PAGE;
    const paginatedProducts = productsToRender.slice(startIndex, startIndex + PRODUCTS_PER_PAGE);
    
    // Renderizar productos
    const gridId = sectionType === 'featured' ? 'featured-products-grid' : 'products-grid';
    const grid = document.getElementById(gridId);
    
    if (!grid) return;
    
    grid.innerHTML = '';
    if (paginatedProducts.length > 0) {
        paginatedProducts.forEach(product => {
            grid.innerHTML += renderProductCard(product);
        });
    } else {
        grid.innerHTML = '<p class="col-span-full text-center" style="color: white;">No se encontraron productos.</p>';
    }
    
    // Renderizar paginación
    const paginationId = sectionType === 'featured' ? 'featured-pagination' : 'category-pagination';
    renderPaginationControls(paginationId, totalPages, state.currentPage, sectionType);
}

// Renderizar tarjeta de producto
function renderProductCard(product) {
    const imageUrl = product.image ? `img/productos/${product.image}` : 'https://via.placeholder.com/300x300.png?text=Sin+Imagen';
    
    return `
        <div class="product-card">
            <div class="product-card-image-container">
                <img src="${imageUrl}" alt="${product.name}" onerror="this.src='https://via.placeholder.com/300x300.png?text=Error'">
            </div>
            <div class="product-card-content">
                <h3 class="product-card-name">${product.name}</h3>
                <p class="product-card-category">${product.category_name}</p>
                <div class="product-card-footer">
                    <span class="product-card-price">$${parseFloat(product.price).toFixed(2)}</span>
                    <button onclick="addToCart(${product.id})" class="product-card-button" ${product.stock == 0 ? 'disabled' : ''}>
                        ${product.stock == 0 ? 'Sin Stock' : 'Agregar'}
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Renderizar controles de paginación
function renderPaginationControls(containerId, totalPages, currentPage, sectionType) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '';
    if (totalPages <= 1) return;
    
    for (let i = 1; i <= totalPages; i++) {
        const button = document.createElement('button');
        button.textContent = i;
        button.className = 'pagination-btn';
        if (i === currentPage) {
            button.classList.add('active');
        }
        button.onclick = () => {
            storeState[sectionType].currentPage = i;
            renderProductSection(sectionType);
            document.getElementById(containerId).closest('.content-section')?.scrollIntoView({ behavior: 'smooth' });
        };
        container.appendChild(button);
    }
}

// Mostrar productos por categoría
function showProductsByCategory(categoryName, categoryId) {
    storeState.category.currentCategory = categoryId;
    storeState.category.searchTerm = '';
    storeState.category.sortOrder = 'default';
    storeState.category.currentPage = 1;
    
    const categoryTitle = document.getElementById('category-title');
    const categorySelector = document.getElementById('category-selector');
    const searchCategory = document.getElementById('search-category');
    const sortCategory = document.getElementById('sort-category');
    
    if (categoryTitle) categoryTitle.textContent = `Productos en ${categoryName}`;
    if (categorySelector) categorySelector.value = categoryId;
    if (searchCategory) searchCategory.value = '';
    if (sortCategory) sortCategory.value = 'default';
    
    renderProductSection('category');
    
    // Mostrar/ocultar secciones
    const categoriasSection = document.getElementById('categorias');
    const productosSection = document.getElementById('productos');
    const categoryProductsSection = document.getElementById('category-products-section');
    
    if (categoriasSection) categoriasSection.classList.add('hidden');
    if (productosSection) productosSection.classList.add('hidden');
    if (categoryProductsSection) {
        categoryProductsSection.classList.remove('hidden');
        categoryProductsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Ocultar productos y volver a categorías
function hideProducts() {
    const categoriasSection = document.getElementById('categorias');
    const productosSection = document.getElementById('productos');
    const categoryProductsSection = document.getElementById('category-products-section');
    
    if (categoryProductsSection) categoryProductsSection.classList.add('hidden');
    if (categoriasSection) categoriasSection.classList.remove('hidden');
    if (productosSection) productosSection.classList.remove('hidden');
    
    if (categoriasSection) {
        window.scrollTo({ 
            top: categoriasSection.offsetTop - 80, 
            behavior: 'smooth' 
        });
    }
}

// Cambiar categoría desde selector
function changeCategoryFromSelector() {
    const selector = document.getElementById('category-selector');
    if (!selector) return;
    
    const categoryId = selector.value;
    const category = allCategories.find(c => c.id == categoryId);
    if (category) {
        showProductsByCategory(category.name, category.id);
    }
}

// ============ CART FUNCTIONS ============

// Agregar al carrito
function addToCart(productId) {
    const product = allProducts.find(p => p.id == productId);
    if (!product || product.stock == 0) {
        showNotification('Producto sin stock', true);
        return;
    }
    
    const existingItem = cart.find(item => item.id == productId);
    if (existingItem) {
        if (existingItem.quantity < product.stock) {
            existingItem.quantity++;
        } else {
            showNotification('No puedes agregar más unidades (stock máximo alcanzado)', true);
            return;
        }
    } else {
        cart.push({
            id: productId,
            quantity: 1
        });
    }
    
    localStorage.setItem('terpenitos_cart', JSON.stringify(cart));
    updateCartUI();
    showNotification('Producto agregado al carrito');
}

// Aumentar cantidad en carrito
function increaseQuantity(productId) {
    const product = allProducts.find(p => p.id == productId);
    const cartItem = cart.find(item => item.id == productId);
    
    if (cartItem && product) {
        if (cartItem.quantity < product.stock) {
            cartItem.quantity++;
            localStorage.setItem('terpenitos_cart', JSON.stringify(cart));
            updateCartUI();
        } else {
            showNotification('No hay más stock disponible para este producto', true);
        }
    }
}

// Disminuir cantidad en carrito
function decreaseQuantity(productId) {
    const cartItem = cart.find(item => item.id == productId);
    
    if (cartItem) {
        cartItem.quantity--;
        if (cartItem.quantity <= 0) {
            cart = cart.filter(item => item.id != productId);
        }
        localStorage.setItem('terpenitos_cart', JSON.stringify(cart));
        updateCartUI();
    }
}

// Remover del carrito
function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    localStorage.setItem('terpenitos_cart', JSON.stringify(cart));
    updateCartUI();
    showNotification('Producto eliminado del carrito');
}

// Limpiar carrito
function clearCart() {
    if (confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
        cart = [];
        localStorage.setItem('terpenitos_cart', JSON.stringify(cart));
        updateCartUI();
        showNotification('Carrito vaciado');
    }
}

// Actualizar UI del carrito
function updateCartUI() {
    const cartItemsContainer = document.getElementById('cart-products');
    const cartFooter = document.getElementById('cart-footer');
    const emptyCartMessage = document.getElementById('empty-cart');
    const cartCount = document.getElementById('cart-count');
    const cartTotalPrice = document.getElementById('cart-total-price');
    
    if (!cartItemsContainer) return;
    
    // Calcular totales
    let totalItems = 0;
    let totalPrice = 0;
    
    cart.forEach(cartItem => {
        const product = allProducts.find(p => p.id == cartItem.id);
        if (product) {
            totalItems += cartItem.quantity;
            totalPrice += product.price * cartItem.quantity;
        }
    });
    
    // Actualizar contador del carrito
    if (cartCount) {
        if (totalItems > 0) {
            cartCount.textContent = totalItems;
            cartCount.classList.remove('hidden');
        } else {
            cartCount.classList.add('hidden');
        }
    }
    
    // Actualizar contenido del carrito
    if (cart.length === 0) {
        if (emptyCartMessage) emptyCartMessage.style.display = 'block';
        if (cartFooter) cartFooter.classList.add('hidden');
        cartItemsContainer.innerHTML = '';
    } else {
        if (emptyCartMessage) emptyCartMessage.style.display = 'none';
        if (cartFooter) cartFooter.classList.remove('hidden');
        
        cartItemsContainer.innerHTML = '';
        cart.forEach(cartItem => {
            const product = allProducts.find(p => p.id == cartItem.id);
            if (product) {
                cartItemsContainer.innerHTML += renderCartItem(product, cartItem);
            }
        });
        
        if (cartTotalPrice) {
            cartTotalPrice.textContent = `$${totalPrice.toFixed(2)}`;
        }
    }
}

// Renderizar item del carrito
function renderCartItem(product, cartItem) {
    const imageUrl = product.image ? `img/productos/${product.image}` : 'https://via.placeholder.com/80x80.png?text=Sin+Imagen';
    const itemTotal = product.price * cartItem.quantity;
    
    return `
        <div class="cart-item">
            <img src="${imageUrl}" alt="${product.name}" class="cart-item-image" onerror="this.src='https://via.placeholder.com/80x80.png?text=Error'">
            <div class="cart-item-details">
                <h4 class="cart-item-name">${product.name}</h4>
                <p class="cart-item-price">$${parseFloat(product.price).toFixed(2)}</p>
                <div class="cart-item-quantity">
                    <button onclick="decreaseQuantity(${product.id})" class="quantity-btn">-</button>
                    <span class="quantity">${cartItem.quantity}</span>
                    <button onclick="increaseQuantity(${product.id})" class="quantity-btn">+</button>
                </div>
            </div>
            <div class="cart-item-actions">
                <div class="cart-item-total">$${itemTotal.toFixed(2)}</div>
                <button onclick="removeFromCart(${product.id})" class="remove-btn">&times;</button>
            </div>
        </div>
    `;
}

// Abrir carrito
function openCart() {
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartOverlay = document.getElementById('cart-overlay');
    
    if (cartSidebar) cartSidebar.classList.add('open');
    if (cartOverlay) cartOverlay.classList.add('show');
}

// Cerrar carrito
function closeCart() {
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartOverlay = document.getElementById('cart-overlay');
    
    if (cartSidebar) cartSidebar.classList.remove('open');
    if (cartOverlay) cartOverlay.classList.remove('show');
}

// Proceder al checkout
function checkout() {
    if (!currentUser) {
        showNotification('Debes iniciar sesión para realizar un pedido', true);
        openLoginModal();
        return;
    }
    
    if (cart.length === 0) {
        showNotification('El carrito está vacío', true);
        return;
    }
    
    closeCart();
    openCheckoutModal();
}

// Abrir modal de checkout
function openCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    if (!modal) return;
    
    // Prellenar datos del usuario
    const phoneInput = document.getElementById('checkoutPhone');
    if (phoneInput && currentUser && currentUser.phone) {
        phoneInput.value = currentUser.phone;
    }
    
    // Mostrar resumen del pedido
    updateCheckoutSummary();
    
    modal.style.display = 'flex';
}

// Actualizar resumen del checkout
function updateCheckoutSummary() {
    const summaryContainer = document.getElementById('checkout-summary');
    if (!summaryContainer) return;
    
    let total = 0;
    let itemsHtml = '';
    
    cart.forEach(cartItem => {
        const product = allProducts.find(p => p.id == cartItem.id);
        if (product) {
            const itemTotal = product.price * cartItem.quantity;
            total += itemTotal;
            itemsHtml += `
                <div class="checkout-item">
                    <span>${product.name} x ${cartItem.quantity}</span>
                    <span>$${itemTotal.toFixed(2)}</span>
                </div>
            `;
        }
    });
    
    summaryContainer.innerHTML = `
        <h3>Resumen del pedido</h3>
        <div class="checkout-items">
            ${itemsHtml}
        </div>
        <div class="checkout-total">
            <strong>Total: $${total.toFixed(2)}</strong>
        </div>
    `;
}

// Manejar checkout
async function handleCheckout(event) {
    event.preventDefault();
    
    const address = document.getElementById('checkoutAddress').value;
    const phone = document.getElementById('checkoutPhone').value;
    const notes = document.getElementById('checkoutNotes').value;
    const csrfToken = document.getElementById('checkoutCsrfToken').value;
    
    if (!address || !phone) {
        showNotification('Dirección y teléfono son requeridos', true);
        return;
    }
    
    // Preparar items del carrito
    const cartItems = cart.map(cartItem => {
        const product = allProducts.find(p => p.id == cartItem.id);
        return {
            product_id: cartItem.id,
            quantity: cartItem.quantity,
            price: product.price,
            product_name: product.name
        };
    });
    
    try {
        const response = await apiCall('controller=order&action=create', {
            method: 'POST',
            body: JSON.stringify({
                cart_items: cartItems,
                shipping_address: address,
                phone: phone,
                notes: notes,
                csrf_token: csrfToken
            })
        });
        
        if (response.success) {
            // Limpiar carrito
            cart = [];
            localStorage.setItem('terpenitos_cart', JSON.stringify(cart));
            updateCartUI();
            
            showNotification('¡Pedido creado exitosamente!');
            closeModal('checkoutModal');
            
            // Recargar productos para actualizar stock
            await loadProducts();
            renderProductSection('featured');
            if (storeState.category.currentCategory) {
                renderProductSection('category');
            }
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// ============ PROFILE PAGE FUNCTIONS ============

// Inicializar página de perfil
async function initializeProfilePage() {
    if (!currentUser) {
        window.location.href = 'index.php';
        return;
    }
    
    loadProfileInfo();
    await loadUserOrders();
    await loadUserAddresses();
    
    // Activar tab desde hash o default
    const hash = window.location.hash.substring(1);
    const validTabs = ['info', 'pedidos', 'direcciones', 'seguridad'];
    const activeTab = validTabs.includes(hash) ? hash : 'info';
    switchProfileTab(activeTab);
}

// Cargar información del perfil
function loadProfileInfo() {
    if (!currentUser) return;
    
    const nameInput = document.getElementById('profileName');
    const emailInput = document.getElementById('profileEmail');
    const phoneInput = document.getElementById('profilePhone');
    
    if (nameInput) nameInput.value = currentUser.name || '';
    if (emailInput) emailInput.value = currentUser.email || '';
    if (phoneInput) phoneInput.value = currentUser.phone || '';
}

// Cargar pedidos del usuario
async function loadUserOrders() {
    try {
        const response = await apiCall('controller=order&action=getUserOrders');
        if (response.success) {
            renderUserOrders(response.orders);
        }
    } catch (error) {
        console.error('Error al cargar pedidos:', error);
    }
}

// Renderizar pedidos del usuario
function renderUserOrders(orders) {
    const container = document.getElementById('orders-container');
    const noOrders = document.getElementById('no-orders');
    
    if (!container) return;
    
    if (orders.length === 0) {
        container.innerHTML = '';
        if (noOrders) noOrders.classList.remove('hidden');
        return;
    }
    
    if (noOrders) noOrders.classList.add('hidden');
    
    container.innerHTML = orders.map(order => `
        <div class="order-item">
            <div class="order-header">
                <div class="order-info">
                    <h3>Pedido #${order.id}</h3>
                    <p class="order-date">${formatDate(order.created_at)}</p>
                </div>
                <div class="order-status">
                    <span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span>
                </div>
            </div>
            <div class="order-details">
                <p class="order-total">Total: <strong>$${parseFloat(order.total).toFixed(2)}</strong></p>
                <button onclick="viewOrderDetails(${order.id})" class="btn btn-outline btn-sm">Ver Detalles</button>
            </div>
        </div>
    `).join('');
}

// Ver detalles del pedido
async function viewOrderDetails(orderId) {
    try {
        const response = await apiCall(`controller=order&action=getOrderDetails&id=${orderId}`);
        if (response.success) {
            showOrderDetailsModal(response.order, response.items);
        }
    } catch (error) {
        showNotification('Error al cargar detalles del pedido', true);
    }
}

// Mostrar modal de detalles del pedido
function showOrderDetailsModal(order, items) {
    const modal = document.getElementById('orderDetailsModal');
    const title = document.getElementById('orderDetailsTitle');
    const info = document.getElementById('orderDetailsInfo');
    const shipping = document.getElementById('orderDetailsShipping');
    const productsList = document.getElementById('orderProductsList');
    const totals = document.getElementById('orderDetailsTotals');
    
    if (!modal) return;
    
    if (title) title.textContent = `Pedido #${order.id}`;
    
    if (info) {
        info.innerHTML = `
            <div class="detail-item">
                <label>Fecha:</label>
                <span>${formatDate(order.created_at)}</span>
            </div>
            <div class="detail-item">
                <label>Estado:</label>
                <span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span>
            </div>
            <div class="detail-item">
                <label>Teléfono:</label>
                <span>${order.phone}</span>
            </div>
        `;
    }
    
    if (shipping) {
        shipping.innerHTML = `
            <h3 class="section-subtitle">Dirección de envío</h3>
            <p>${order.shipping_address}</p>
            ${order.notes ? `<p><strong>Notas:</strong> ${order.notes}</p>` : ''}
        `;
    }
    
    if (productsList) {
        productsList.innerHTML = items.map(item => {
            const imageUrl = item.product_image ? `img/productos/${item.product_image}` : 'https://via.placeholder.com/60x60.png?text=Sin+Imagen';
            const itemTotal = item.price * item.quantity;
            
            return `
                <div class="order-product-item">
                    <img src="${imageUrl}" alt="${item.product_name}" class="product-image" onerror="this.src='https://via.placeholder.com/60x60.png?text=Error'">
                    <div class="product-details">
                        <h4>${item.product_name}</h4>
                        <p>Cantidad: ${item.quantity}</p>
                        <p>Precio unitario: $${parseFloat(item.price).toFixed(2)}</p>
                    </div>
                    <div class="product-total">
                        $${itemTotal.toFixed(2)}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    if (totals) {
        totals.innerHTML = `
            <div class="total-row">
                <strong>Total del pedido: $${parseFloat(order.total).toFixed(2)}</strong>
            </div>
        `;
    }
    
    modal.style.display = 'flex';
}

// Cargar direcciones del usuario
async function loadUserAddresses() {
    try {
        const response = await apiCall('controller=address&action=getUserAddresses');
        if (response.success) {
            renderUserAddresses(response.addresses);
        }
    } catch (error) {
        console.error('Error al cargar direcciones:', error);
    }
}

// Renderizar direcciones del usuario
function renderUserAddresses(addresses) {
    const container = document.getElementById('addresses-container');
    const noAddresses = document.getElementById('no-addresses');
    
    if (!container) return;
    
    if (addresses.length === 0) {
        container.innerHTML = '';
        if (noAddresses) noAddresses.classList.remove('hidden');
        return;
    }
    
    if (noAddresses) noAddresses.classList.add('hidden');
    
    container.innerHTML = addresses.map(address => `
        <div class="address-item ${address.is_default ? 'default' : ''}">
            <div class="address-header">
                <h3>${address.alias} ${address.is_default ? '<span class="default-badge">Predeterminada</span>' : ''}</h3>
                <div class="address-actions">
                    <button onclick="editAddress(${address.id})" class="btn btn-outline btn-sm">Editar</button>
                    <button onclick="deleteAddress(${address.id})" class="btn btn-danger btn-sm">Eliminar</button>
                </div>
            </div>
            <div class="address-details">
                <p>${address.address_line1}</p>
                <p>${address.city}, ${address.postal_code}</p>
            </div>
        </div>
    `).join('');
}

// Abrir modal de dirección
function openAddressModal(addressData = null) {
    const modal = document.getElementById('addressModal');
    const title = document.getElementById('addressModalTitle');
    const form = document.getElementById('addressForm');
    
    if (!modal || !form) return;
    
    // Resetear formulario
    form.reset();
    document.getElementById('addressId').value = '';
    
    if (addressData) {
        title.textContent = 'Editar Dirección';
        document.getElementById('addressId').value = addressData.id;
        document.getElementById('addressAlias').value = addressData.alias;
        document.getElementById('addressLine1').value = addressData.address_line1;
        document.getElementById('addressCity').value = addressData.city;
        document.getElementById('addressZip').value = addressData.postal_code;
        document.getElementById('addressIsDefault').checked = addressData.is_default;
    } else {
        title.textContent = 'Nueva Dirección';
    }
    
    modal.style.display = 'flex';
}

// Editar dirección
async function editAddress(addressId) {
    // En una implementación real, haríamos una llamada a la API para obtener los datos
    // Por ahora, buscaremos en los datos ya cargados
    const addresses = await getUserAddresses();
    const address = addresses.find(a => a.id == addressId);
    if (address) {
        openAddressModal(address);
    }
}

// Obtener direcciones del usuario (helper)
async function getUserAddresses() {
    try {
        const response = await apiCall('controller=address&action=getUserAddresses');
        return response.success ? response.addresses : [];
    } catch (error) {
        return [];
    }
}

// Eliminar dirección
async function deleteAddress(addressId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta dirección?')) {
        return;
    }
    
    try {
        const response = await apiCall('controller=address&action=delete', {
            method: 'POST',
            body: JSON.stringify({
                id: addressId,
                csrf_token: window.CSRF_TOKEN
            })
        });
        
        if (response.success) {
            showNotification('Dirección eliminada exitosamente');
            await loadUserAddresses();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Manejar guardado de dirección
async function handleAddressSave(event) {
    event.preventDefault();
    
    const id = document.getElementById('addressId').value;
    const alias = document.getElementById('addressAlias').value;
    const addressLine1 = document.getElementById('addressLine1').value;
    const city = document.getElementById('addressCity').value;
    const postalCode = document.getElementById('addressZip').value;
    const isDefault = document.getElementById('addressIsDefault').checked;
    const csrfToken = document.getElementById('addressCsrfToken').value;
    
    const addressData = {
        alias: alias,
        address_line1: addressLine1,
        city: city,
        postal_code: postalCode,
        is_default: isDefault,
        csrf_token: csrfToken
    };
    
    if (id) {
        addressData.id = id;
    }
    
    try {
        const endpoint = id ? 'controller=address&action=update' : 'controller=address&action=create';
        const response = await apiCall(endpoint, {
            method: 'POST',
            body: JSON.stringify(addressData)
        });
        
        if (response.success) {
            showNotification(id ? 'Dirección actualizada exitosamente' : 'Dirección creada exitosamente');
            closeModal('addressModal');
            await loadUserAddresses();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Manejar actualización de perfil
async function handleProfileUpdate(event) {
    event.preventDefault();
    
    const name = document.getElementById('profileName').value;
    const phone = document.getElementById('profilePhone').value;
    const csrfToken = document.getElementById('profileCsrfToken').value;
    
    try {
        const response = await apiCall('controller=auth&action=updateProfile', {
            method: 'POST',
            body: JSON.stringify({
                name: name,
                phone: phone,
                csrf_token: csrfToken
            })
        });
        
        if (response.success) {
            currentUser.name = name;
            currentUser.phone = phone;
            showNotification('Perfil actualizado exitosamente');
            updateAuthUI(true); // Actualizar avatar
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Manejar cambio de contraseña
async function handlePasswordUpdate(event) {
    event.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const csrfToken = document.getElementById('passwordCsrfToken').value;
    
    if (newPassword !== confirmPassword) {
        showNotification('Las contraseñas no coinciden', true);
        return;
    }
    
    if (newPassword.length < 6) {
        showNotification('La nueva contraseña debe tener al menos 6 caracteres', true);
        return;
    }
    
    try {
        const response = await apiCall('controller=auth&action=changePassword', {
            method: 'POST',
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword,
                csrf_token: csrfToken
            })
        });
        
        if (response.success) {
            showNotification('Contraseña actualizada exitosamente');
            document.getElementById('passwordForm').reset();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Cambiar tab del perfil
function switchProfileTab(tabName) {
    // Remover clase activa de todos los tabs
    document.querySelectorAll('.profile-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Ocultar todo el contenido
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Activar tab y contenido seleccionado
    const activeTab = document.getElementById(`tab-${tabName}`);
    const activeContent = document.getElementById(`content-${tabName}`);
    
    if (activeTab) activeTab.classList.add('active');
    if (activeContent) activeContent.classList.add('active');
    
    // Actualizar URL hash
    window.location.hash = tabName;
}

// ============ ADMIN FUNCTIONS ============

let currentOrderId = null;

// Inicializar panel de administración
async function initializeAdminPanel() {
    await loadDashboardStats();
    await loadAdminProducts();
    await loadAdminCategories();
    await loadAdminOrders();
    setupAdminFilters();
}

// Cargar estadísticas del dashboard
async function loadDashboardStats() {
    try {
        const response = await apiCall('controller=order&action=getStats');
        if (response.success) {
            const stats = response.stats;
            
            document.getElementById('dashboard-capital').textContent = `$${stats.total_capital.toFixed(2)}`;
            document.getElementById('dashboard-stock').textContent = stats.total_stock;
            document.getElementById('dashboard-revenue').textContent = `$${stats.total_revenue.toFixed(2)}`;
            document.getElementById('dashboard-profit').textContent = `$${stats.estimated_profit.toFixed(2)}`;
            document.getElementById('dashboard-pending-orders').textContent = stats.pending_orders;
            document.getElementById('dashboard-shipped-orders').textContent = stats.shipped_orders;
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

// Cargar productos para admin
async function loadAdminProducts() {
    const table = document.getElementById('products-table');
    if (!table) return;
    
    table.innerHTML = '';
    
    const categoryFilter = document.getElementById('categoryFilter')?.value || 'all';
    let productsToShow = allProducts;
    
    if (categoryFilter !== 'all') {
        productsToShow = allProducts.filter(p => p.category_id == categoryFilter);
    }
    
    if (productsToShow.length === 0) {
        table.innerHTML = '<tr><td colspan="6" class="text-center p-6">No hay productos para mostrar.</td></tr>';
        return;
    }
    
    productsToShow.forEach(product => {
        table.innerHTML += `
            <tr>
                <td>${product.name}</td>
                <td>${product.category_name}</td>
                <td>$${parseFloat(product.price).toFixed(2)}</td>
                <td>${product.stock}</td>
                <td>
                    <label class="checkbox-label">
                        <input type="checkbox" ${product.is_featured ? 'checked' : ''} 
                               onchange="toggleProductFeatured(${product.id}, this.checked)">
                        <span class="checkmark"></span>
                    </label>
                </td>
                <td>
                    <div class="flex items-center gap-2">
                        <button onclick="editProduct(${product.id})" class="admin-action-btn admin-btn-edit">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>
                            <span>Editar</span>
                        </button>
                        <button onclick="deleteProduct(${product.id})" class="admin-action-btn admin-btn-delete">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Eliminar</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
}

// Configurar filtros de admin
function setupAdminFilters() {
    const categoryFilter = document.getElementById('categoryFilter');
    if (!categoryFilter) return;
    
    categoryFilter.innerHTML = '<option value="all">Todas las categorías</option>';
    allCategories.forEach(category => {
        categoryFilter.innerHTML += `<option value="${category.id}">${category.name}</option>`;
    });
    
    categoryFilter.addEventListener('change', loadAdminProducts);
}

// Toggle producto destacado
async function toggleProductFeatured(productId, isFeatured) {
    const product = allProducts.find(p => p.id == productId);
    if (!product) return;
    
    try {
        const response = await apiCall('controller=product&action=update', {
            method: 'POST',
            body: JSON.stringify({
                id: productId,
                name: product.name,
                description: product.description,
                category_id: product.category_id,
                price: product.price,
                cost: product.cost,
                stock: product.stock,
                image: product.image,
                is_featured: isFeatured,
                csrf_token: window.CSRF_TOKEN
            })
        });
        
        if (response.success) {
            // Actualizar en memoria
            product.is_featured = isFeatured;
            showNotification('Producto actualizado');
            
            // Recargar productos si estamos en la tienda
            if (!window.IS_ADMIN_PAGE) {
                renderProductSection('featured');
            }
        }
    } catch (error) {
        showNotification(error.message, true);
        // Revertir el checkbox
        event.target.checked = !isFeatured;
    }
}

// Abrir modal de producto
function openProductModal(product = null) {
    const modal = document.getElementById('productModal');
    const title = document.getElementById('productModalTitle');
    const form = document.getElementById('productForm');
    
    if (!modal || !form) return;
    
    // Llenar categorías
    const categorySelect = document.getElementById('productCategory');
    if (categorySelect) {
        categorySelect.innerHTML = '';
        allCategories.forEach(category => {
            categorySelect.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        });
    }
    
    // Resetear formulario
    form.reset();
    document.getElementById('productId').value = '';
    
    // Limpiar imagen
    clearProductImage();
    
    if (product) {
        title.textContent = 'Editar Producto';
        document.getElementById('productId').value = product.id;
        document.getElementById('productName').value = product.name;
        document.getElementById('productDescription').value = product.description || '';
        document.getElementById('productCategory').value = product.category_id;
        document.getElementById('productPrice').value = product.price;
        document.getElementById('productCost').value = product.cost || 0;
        document.getElementById('productStock').value = product.stock;
        document.getElementById('productImage').value = product.image || '';
        document.getElementById('productIsFeatured').checked = product.is_featured;
        
        // Mostrar imagen actual si existe
        if (product.image) {
            const preview = document.getElementById('productImagePreview');
            const img = document.getElementById('productImagePreviewImg');
            img.src = `img/productos/${product.image}`;
            preview.style.display = 'block';
            document.getElementById('productImageFileName').textContent = product.image;
        }
    } else {
        title.textContent = 'Agregar Producto';
    }
    
    modal.style.display = 'flex';
}

// Cerrar modal de producto
function closeProductModal() {
    const modal = document.getElementById('productModal');
    if (modal) modal.style.display = 'none';
    clearProductImage();
}

// Editar producto
function editProduct(productId) {
    const product = allProducts.find(p => p.id == productId);
    if (product) {
        openProductModal(product);
    }
}

// Eliminar producto
async function deleteProduct(productId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
        return;
    }
    
    try {
        const response = await apiCall('controller=product&action=delete', {
            method: 'POST',
            body: JSON.stringify({
                id: productId,
                csrf_token: window.CSRF_TOKEN
            })
        });
        
        if (response.success) {
            showNotification('Producto eliminado exitosamente');
            await loadProducts();
            await loadAdminProducts();
            await loadDashboardStats();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Manejar envío del formulario de producto
async function handleProductFormSubmit(event) {
    event.preventDefault();
    
    const id = document.getElementById('productId').value;
    const name = document.getElementById('productName').value;
    const description = document.getElementById('productDescription').value;
    const categoryId = document.getElementById('productCategory').value;
    const price = document.getElementById('productPrice').value;
    const cost = document.getElementById('productCost').value;
    const stock = document.getElementById('productStock').value;
    let image = document.getElementById('productImage').value;
    const isFeatured = document.getElementById('productIsFeatured').checked;
    const csrfToken = document.getElementById('productCsrfToken').value;
    
    try {
        // Subir imagen si se seleccionó una nueva
        if (productImageFile) {
            showNotification('Subiendo imagen...');
            image = await uploadProductImage();
        }
        
        const productData = {
            name: name,
            description: description,
            category_id: categoryId,
            price: parseFloat(price),
            cost: parseFloat(cost),
            stock: parseInt(stock),
            image: image,
            is_featured: isFeatured,
            csrf_token: csrfToken
        };
        
        if (id) {
            productData.id = id;
        }
        
        const endpoint = id ? 'controller=product&action=update' : 'controller=product&action=create';
        const response = await apiCall(endpoint, {
            method: 'POST',
            body: JSON.stringify(productData)
        });
        
        if (response.success) {
            showNotification(id ? 'Producto actualizado exitosamente' : 'Producto creado exitosamente');
            closeProductModal();
            await loadProducts();
            await loadAdminProducts();
            await loadDashboardStats();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Cargar categorías para admin
async function loadAdminCategories() {
    const grid = document.getElementById('categories-grid');
    if (!grid) return;
    
    grid.innerHTML = '';
    allCategories.forEach(category => {
        grid.innerHTML += `
            <div class="flex justify-between items-center p-4 rounded-lg bg-white bg-opacity-10">
                <span class="text-white">${category.name}</span>
                <div class="flex items-center gap-2">
                    <button onclick="editCategory(${category.id})" class="admin-action-btn admin-btn-edit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>
                        <span>Editar</span>
                    </button>
                    <button onclick="deleteCategory(${category.id})" class="admin-action-btn admin-btn-delete">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <span>Eliminar</span>
                    </button>
                </div>
            </div>
        `;
    });
}

// Abrir modal de categoría
function openCategoryModal(category = null) {
    const modal = document.getElementById('categoryModal');
    const title = document.getElementById('categoryModalTitle');
    const form = document.getElementById('categoryForm');
    
    if (!modal || !form) return;
    
    // Resetear formulario
    form.reset();
    document.getElementById('categoryId').value = '';
    
    // Limpiar imagen
    clearCategoryImage();
    
    if (category) {
        title.textContent = 'Editar Categoría';
        document.getElementById('categoryId').value = category.id;
        document.getElementById('categoryName').value = category.name;
        document.getElementById('categoryImage').value = category.image || '';
        
        // Mostrar imagen actual si existe
        if (category.image) {
            const preview = document.getElementById('categoryImagePreview');
            const img = document.getElementById('categoryImagePreviewImg');
            img.src = `img/categorias/${category.image}`;
            preview.style.display = 'block';
            document.getElementById('categoryImageFileName').textContent = category.image;
        }
    } else {
        title.textContent = 'Nueva Categoría';
    }
    
    modal.style.display = 'flex';
}

// Cerrar modal de categoría
function closeCategoryModal() {
    const modal = document.getElementById('categoryModal');
    if (modal) modal.style.display = 'none';
    clearCategoryImage();
}

// Editar categoría
function editCategory(categoryId) {
    const category = allCategories.find(c => c.id == categoryId);
    if (category) {
        openCategoryModal(category);
    }
}

// Eliminar categoría
async function deleteCategory(categoryId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta categoría? Se ocultarán también todos los productos asociados.')) {
        return;
    }
    
    try {
        const response = await apiCall('controller=category&action=delete', {
            method: 'POST',
            body: JSON.stringify({
                id: categoryId,
                csrf_token: window.CSRF_TOKEN
            })
        });
        
        if (response.success) {
            showNotification('Categoría eliminada exitosamente');
            await loadCategories();
            await loadAdminCategories();
            await loadAdminProducts();
            setupAdminFilters();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Manejar envío del formulario de categoría
async function handleCategoryFormSubmit(event) {
    event.preventDefault();
    
    const id = document.getElementById('categoryId').value;
    const name = document.getElementById('categoryName').value;
    let image = document.getElementById('categoryImage').value;
    const csrfToken = document.getElementById('categoryCsrfToken').value;
    
    try {
        // Subir imagen si se seleccionó una nueva
        if (categoryImageFile) {
            showNotification('Subiendo imagen...');
            image = await uploadCategoryImage();
        }
        
        const categoryData = {
            name: name,
            image: image,
            csrf_token: csrfToken
        };
        
        if (id) {
            categoryData.id = id;
        }
        
        const endpoint = id ? 'controller=category&action=update' : 'controller=category&action=create';
        const response = await apiCall(endpoint, {
            method: 'POST',
            body: JSON.stringify(categoryData)
        });
        
        if (response.success) {
            showNotification(id ? 'Categoría actualizada exitosamente' : 'Categoría creada exitosamente');
            closeCategoryModal();
            await loadCategories();
            await loadAdminCategories();
            setupAdminFilters();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Cargar pedidos para admin
async function loadAdminOrders() {
    try {
        const response = await apiCall('controller=order&action=getAllOrders');
        if (response.success) {
            renderAdminOrders(response.orders);
        }
    } catch (error) {
        console.error('Error al cargar pedidos:', error);
    }
}

// Renderizar pedidos para admin
function renderAdminOrders(orders) {
    const table = document.getElementById('orders-table');
    if (!table) return;
    
    table.innerHTML = '';
    
    if (orders.length === 0) {
        table.innerHTML = '<tr><td colspan="6" class="text-center p-6">No hay pedidos para mostrar.</td></tr>';
        return;
    }
    
    orders.forEach(order => {
        table.innerHTML += `
            <tr>
                <td>#${order.id}</td>
                <td>${order.user_name}</td>
                <td>${formatDate(order.created_at)}</td>
                <td>$${parseFloat(order.total).toFixed(2)}</td>
                <td><span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span></td>
                <td>
                    <div class="flex items-center gap-2">
                        <button onclick="viewAdminOrderDetails(${order.id})" class="admin-action-btn admin-btn-edit">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>Ver</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
}

// Ver detalles del pedido (admin)
async function viewAdminOrderDetails(orderId) {
    try {
        const response = await apiCall(`controller=order&action=getOrderDetails&id=${orderId}`);
        if (response.success) {
            currentOrderId = orderId;
            showAdminOrderDetailsModal(response.order, response.items);
        }
    } catch (error) {
        showNotification('Error al cargar detalles del pedido', true);
    }
}

// Mostrar modal de detalles del pedido (admin)
function showAdminOrderDetailsModal(order, items) {
    const modal = document.getElementById('orderDetailsModal');
    const title = document.getElementById('orderDetailsTitle');
    const info = document.getElementById('orderDetailsInfo');
    const shipping = document.getElementById('orderDetailsShipping');
    const productsList = document.getElementById('orderProductsList');
    const totals = document.getElementById('orderDetailsTotals');
    const statusSelect = document.getElementById('orderStatusSelect');
    
    if (!modal) return;
    
    if (title) title.textContent = `Pedido #${order.id}`;
    
    if (info) {
        info.innerHTML = `
            <div class="detail-item">
                <label>Cliente:</label>
                <span>${order.user_name} (${order.user_email})</span>
            </div>
            <div class="detail-item">
                <label>Fecha:</label>
                <span>${formatDate(order.created_at)}</span>
            </div>
            <div class="detail-item">
                <label>Teléfono:</label>
                <span>${order.phone}</span>
            </div>
        `;
    }
    
    if (shipping) {
        shipping.innerHTML = `
            <h3 class="section-subtitle">Dirección de envío</h3>
            <p>${order.shipping_address}</p>
            ${order.notes ? `<p><strong>Notas:</strong> ${order.notes}</p>` : ''}
        `;
    }
    
    if (productsList) {
        productsList.innerHTML = items.map(item => {
            const imageUrl = item.product_image ? `img/productos/${item.product_image}` : 'https://via.placeholder.com/60x60.png?text=Sin+Imagen';
            const itemTotal = item.price * item.quantity;
            
            return `
                <div class="order-product-item">
                    <img src="${imageUrl}" alt="${item.product_name}" class="product-image" onerror="this.src='https://via.placeholder.com/60x60.png?text=Error'">
                    <div class="product-details">
                        <h4>${item.product_name}</h4>
                        <p>Cantidad: ${item.quantity}</p>
                        <p>Precio unitario: $${parseFloat(item.price).toFixed(2)}</p>
                    </div>
                    <div class="product-total">
                        $${itemTotal.toFixed(2)}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    if (totals) {
        totals.innerHTML = `
            <div class="total-row">
                <strong>Total del pedido: $${parseFloat(order.total).toFixed(2)}</strong>
            </div>
        `;
    }
    
    if (statusSelect) {
        statusSelect.value = order.status;
    }
    
    modal.style.display = 'flex';
}

// Actualizar estado del pedido
async function updateOrderStatus() {
    const statusSelect = document.getElementById('orderStatusSelect');
    if (!statusSelect || !currentOrderId) return;
    
    const newStatus = statusSelect.value;
    
    try {
        const response = await apiCall('controller=order&action=updateStatus', {
            method: 'POST',
            body: JSON.stringify({
                order_id: currentOrderId,
                status: newStatus,
                csrf_token: window.CSRF_TOKEN
            })
        });
        
        if (response.success) {
            showNotification('Estado del pedido actualizado exitosamente');
            closeOrderDetailsModal();
            await loadAdminOrders();
            await loadDashboardStats();
        }
    } catch (error) {
        showNotification(error.message, true);
    }
}

// Cerrar modal de detalles del pedido
function closeOrderDetailsModal() {
    const modal = document.getElementById('orderDetailsModal');
    if (modal) modal.style.display = 'none';
    currentOrderId = null;
}

// ============ UI HELPER FUNCTIONS ============

// Mostrar notificación
function showNotification(message, isError = false) {
    const notification = document.getElementById('notification');
    const notificationText = document.getElementById('notification-text');
    
    if (!notification || !notificationText) return;
    
    notificationText.textContent = message;
    notification.className = `notification ${isError ? 'error' : 'success'}`;
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Abrir modal de login
function openLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) modal.style.display = 'flex';
}

// Abrir modal de registro
function openRegisterModal() {
    const modal = document.getElementById('registerModal');
    if (modal) modal.style.display = 'flex';
}

// Cambiar a login
function switchToLogin() {
    closeModal('registerModal');
    openLoginModal();
}

// Cambiar a registro
function switchToRegister() {
    closeModal('loginModal');
    openRegisterModal();
}

// Cerrar modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Cerrar todos los modales
function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

// Toggle menú de usuario
function toggleUserMenu() {
    const userMenu = document.getElementById('user-menu');
    if (userMenu) {
        userMenu.classList.toggle('show');
    }
}

// Cerrar menú de usuario
function closeUserMenu() {
    const userMenu = document.getElementById('user-menu');
    if (userMenu) {
        userMenu.classList.remove('show');
    }
}

// Toggle menú móvil
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenu) {
        mobileMenu.classList.toggle('show');
    }
}

// Cerrar menú móvil
function closeMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenu) {
        mobileMenu.classList.remove('show');
    }
}

// Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Debounce function
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}
// ============ IMAGE UPLOAD FUNCTIONS ============

// Variables globales para las imágenes
let productImageFile = null;
let categoryImageFile = null;

// Vista previa de imagen de producto
function previewProductImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
        showNotification('Por favor selecciona un archivo de imagen válido', true);
        event.target.value = '';
        return;
    }
    
    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showNotification('La imagen es demasiado grande. Máximo 5MB.', true);
        event.target.value = '';
        return;
    }
    
    productImageFile = file;
    
    // Mostrar nombre del archivo
    document.getElementById('productImageFileName').textContent = file.name;
    
    // Crear vista previa
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('productImagePreview');
        const img = document.getElementById('productImagePreviewImg');
        img.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

// Limpiar imagen de producto
function clearProductImage() {
    productImageFile = null;
    document.getElementById('productImageFile').value = '';
    document.getElementById('productImageFileName').textContent = 'Seleccionar imagen';
    document.getElementById('productImagePreview').style.display = 'none';
    document.getElementById('productImage').value = '';
}

// Vista previa de imagen de categoría
function previewCategoryImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
        showNotification('Por favor selecciona un archivo de imagen válido', true);
        event.target.value = '';
        return;
    }
    
    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showNotification('La imagen es demasiado grande. Máximo 5MB.', true);
        event.target.value = '';
        return;
    }
    
    categoryImageFile = file;
    
    // Mostrar nombre del archivo
    document.getElementById('categoryImageFileName').textContent = file.name;
    
    // Crear vista previa
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('categoryImagePreview');
        const img = document.getElementById('categoryImagePreviewImg');
        img.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

// Limpiar imagen de categoría
function clearCategoryImage() {
    categoryImageFile = null;
    document.getElementById('categoryImageFile').value = '';
    document.getElementById('categoryImageFileName').textContent = 'Seleccionar imagen';
    document.getElementById('categoryImagePreview').style.display = 'none';
    document.getElementById('categoryImage').value = '';
}

// Subir imagen de producto al servidor
async function uploadProductImage() {
    if (!productImageFile) {
        return null;
    }
    
    const formData = new FormData();
    formData.append('image', productImageFile);
    
    try {
        const response = await fetch(`${window.API_BASE_URL}?controller=image&action=uploadProduct`, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Error al subir la imagen');
        }
        
        return data.filename;
    } catch (error) {
        console.error('Error al subir imagen de producto:', error);
        throw error;
    }
}

// Subir imagen de categoría al servidor
async function uploadCategoryImage() {
    if (!categoryImageFile) {
        return null;
    }
    
    const formData = new FormData();
    formData.append('image', categoryImageFile);
    
    try {
        const response = await fetch(`${window.API_BASE_URL}?controller=image&action=uploadCategory`, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Error al subir la imagen');
        }
        
        return data.filename;
    } catch (error) {
        console.error('Error al subir imagen de categoría:', error);
        throw error;
    }
}

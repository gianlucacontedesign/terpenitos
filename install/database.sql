-- Base de datos ya está seleccionada por Docker
-- No necesitamos USE ya que Docker automáticamente usa la BD configurada

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    category_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- Tabla de direcciones de usuarios
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    alias VARCHAR(50) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    status ENUM('Procesando', 'Enviado', 'Entregado', 'Cancelado') DEFAULT 'Procesando',
    total DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Tabla de items de pedidos
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Índices para optimización
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_user_addresses_user ON user_addresses(user_id);

-- Insertar categorías de ejemplo
INSERT INTO categories (name, image) VALUES 
('Fertilizantes', 'fertilizantes.jpg'),
('Sustratos', 'sustratos.jpg'),
('Iluminación', 'iluminacion.jpg'),
('Macetas', 'macetas.jpg'),
('Herramientas', 'herramientas.jpg'),
('Control de Plagas', 'control-plagas.jpg');

-- Insertar productos de ejemplo
INSERT INTO products (name, description, category_id, price, cost, stock, image, is_featured) VALUES 
-- Fertilizantes
('Fertilizante Orgánico NPK', 'Fertilizante orgánico completo NPK 4-4-4 para todas las etapas de crecimiento.', 1, 2500.00, 1500.00, 50, 'fertilizante-npk.jpg', TRUE),
('Fertilizante Floración', 'Fertilizante especializado para la etapa de floración con alto contenido de fósforo y potasio.', 1, 3200.00, 2000.00, 30, 'fertilizante-floracion.jpg', TRUE),
('Estimulador de Raíces', 'Estimulador de raíces orgánico para promover un sistema radicular fuerte.', 1, 1800.00, 1200.00, 40, 'estimulador-raices.jpg', FALSE),

-- Sustratos
('Sustrato Premium', 'Sustrato premium con perlita, fibra de coco y compost orgánico.', 2, 1500.00, 800.00, 100, 'sustrato-premium.jpg', TRUE),
('Fibra de Coco', 'Fibra de coco 100% natural, ideal para hidroponia y mezclas.', 2, 800.00, 500.00, 80, 'fibra-coco.jpg', FALSE),
('Perlita', 'Perlita expandida para mejorar el drenaje y aireación del sustrato.', 2, 600.00, 350.00, 60, 'perlita.jpg', FALSE),

-- Iluminación
('LED Full Spectrum 300W', 'Panel LED full spectrum 300W ideal para cultivo interior en todas las etapas.', 3, 25000.00, 18000.00, 15, 'led-300w.jpg', TRUE),
('LED COB 150W', 'Lámpara LED COB 150W con alta eficiencia lumínica.', 3, 15000.00, 11000.00, 20, 'led-cob-150w.jpg', FALSE),
('Reflector Ajustable', 'Reflector ajustable para optimizar la distribución de luz.', 3, 3500.00, 2500.00, 25, 'reflector-ajustable.jpg', FALSE),

-- Macetas
('Maceta Textil 20L', 'Maceta textil transpirable de 20 litros con asas reforzadas.', 4, 1200.00, 700.00, 50, 'maceta-textil-20l.jpg', TRUE),
('Maceta Plástica 10L', 'Maceta plástica negra con orificios de drenaje, capacidad 10L.', 4, 600.00, 350.00, 80, 'maceta-plastica-10l.jpg', FALSE),
('Maceta Cerámica 5L', 'Maceta de cerámica decorativa de 5 litros con plato.', 4, 2000.00, 1200.00, 30, 'maceta-ceramica-5l.jpg', FALSE),

-- Herramientas
('Tijeras de Poda', 'Tijeras de poda profesionales con mango ergonómico y hoja de acero inoxidable.', 5, 1800.00, 1200.00, 40, 'tijeras-poda.jpg', FALSE),
('pH Metro Digital', 'pH metro digital de alta precisión para medir el pH del agua y sustrato.', 5, 4500.00, 3000.00, 20, 'ph-metro.jpg', TRUE),
('Termómetro Higrómetro', 'Termómetro e higrómetro digital para controlar temperatura y humedad.', 5, 1500.00, 1000.00, 35, 'termometro-higrometro.jpg', FALSE),

-- Control de Plagas
('Aceite de Neem', 'Aceite de neem orgánico para control natural de plagas y hongos.', 6, 1200.00, 800.00, 45, 'aceite-neem.jpg', FALSE),
('Jabón Potásico', 'Jabón potásico ecológico para control de insectos de cuerpo blando.', 6, 900.00, 600.00, 55, 'jabon-potasico.jpg', FALSE),
('Trampas Cromotropicas', 'Pack de 10 trampas cromotropicas azules y amarillas para moscas y trips.', 6, 800.00, 500.00, 70, 'trampas-cromotropicas.jpg', FALSE);

-- Insertar usuarios de ejemplo
-- Usuario administrador (email: admin@terpenitos.com, password: admin123)
INSERT INTO users (name, email, phone, password, is_admin) VALUES 
('Administrador', 'admin@terpenitos.com', '+54 9 11 1111-1111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Usuario regular (email: user@test.com, password: user123)
INSERT INTO users (name, email, phone, password, is_admin) VALUES 
('Usuario de Prueba', 'user@test.com', '+54 9 11 2222-2222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

-- Insertar direcciones de ejemplo
INSERT INTO user_addresses (user_id, alias, address_line1, city, postal_code, is_default) VALUES 
(2, 'Casa', 'Libertad 4', 'Buenos Aires', '1000', TRUE);

-- Insertar un pedido de ejemplo
INSERT INTO orders (user_id, status, total, shipping_address, phone, notes) VALUES 
(2, 'Entregado', 5000.00, 'Libertad 4, Buenos Aires (1000)', '+54 9 11 2222-2222', 'Entregar en horario laboral');

-- Insertar items del pedido de ejemplo
INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES 
(1, 1, 'Fertilizante Orgánico NPK', 2, 2500.00);
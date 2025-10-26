-- ============================================
-- MIGRACIÓN: Soft Delete para Categorías y Productos
-- Fecha: 2025-10-22
-- ============================================

-- Agregar columna is_active a la tabla categories
ALTER TABLE categories 
ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=eliminado';

-- Agregar columna is_active a la tabla products
ALTER TABLE products 
ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=eliminado';

-- Crear índices para mejorar el rendimiento de las consultas
CREATE INDEX idx_categories_active ON categories(is_active);
CREATE INDEX idx_products_active ON products(is_active);

-- Marcar todos los registros existentes como activos (por defecto ya lo son)
UPDATE categories SET is_active = 1;
UPDATE products SET is_active = 1;

-- ============================================
-- NOTAS:
-- - Ejecutar este script UNA SOLA VEZ
-- - Hacer backup de la base de datos antes de ejecutar
-- - No elimina datos existentes, solo agrega columnas
-- ============================================
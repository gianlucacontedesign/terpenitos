-- Script para inicializar permisos de usuario
-- Se ejecuta como root para dar permisos completos al usuario terpenitos_user

-- Crear usuario si no existe
CREATE USER IF NOT EXISTS 'terpenitos_user'@'%' IDENTIFIED BY 'growshop2024';

-- Otorgar permisos completos en la base de datos terpenitos_growshop
GRANT ALL PRIVILEGES ON terpenitos_growshop.* TO 'terpenitos_user'@'%';

-- Otorgar permisos para crear bases de datos (por si acaso)
GRANT CREATE ON *.* TO 'terpenitos_user'@'%';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Confirmar permisos
SHOW GRANTS FOR 'terpenitos_user'@'%';
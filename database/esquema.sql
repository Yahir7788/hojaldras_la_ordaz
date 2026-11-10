-- ============================================================================
-- HOJALDRAS "LA ORDAZ"
-- Sistema de Gestión de Almacén e Inventarios
-- Base de datos: hojaldras_la_ordaz
-- Motor: MySQL 8.0+ / MariaDB 10.4+
-- Codificación: utf8mb4
--
-- Estructura normalizada (3FN), claves foráneas y restricciones de negocio.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS hojaldras_la_ordaz
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hojaldras_la_ordaz;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS movimientos_producto;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS movimientos_inventario;
DROP TABLE IF EXISTS mermas;
DROP TABLE IF EXISTS salidas;
DROP TABLE IF EXISTS lotes;
DROP TABLE IF EXISTS entradas;
DROP TABLE IF EXISTS insumos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Roles del sistema
-- ----------------------------------------------------------------------------
CREATE TABLE roles (
    id_rol      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(30)  NOT NULL,
    descripcion VARCHAR(180) NULL,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Usuarios
-- ----------------------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_completo VARCHAR(100) NOT NULL,
    usuario         VARCHAR(40)  NOT NULL,
    correo          VARCHAR(120) NULL,
    contrasena      VARCHAR(255) NOT NULL COMMENT 'Hash generado con password_hash()',
    id_rol          INT UNSIGNED NOT NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_creacion  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso   DATETIME     NULL,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    UNIQUE KEY uq_usuarios_correo (correo),
    KEY idx_usuarios_rol (id_rol),
    KEY idx_usuarios_activo (activo),
    CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol)
        REFERENCES roles (id_rol) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Categorías de insumos
-- ----------------------------------------------------------------------------
CREATE TABLE categorias (
    id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(60)  NOT NULL,
    descripcion  VARCHAR(200) NULL,
    perecedero   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = exige fecha de caducidad',
    PRIMARY KEY (id_categoria),
    UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Insumos / materias primas
-- ----------------------------------------------------------------------------
CREATE TABLE insumos (
    id_insumo       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,
    descripcion     VARCHAR(255) NULL,
    id_categoria    INT UNSIGNED NOT NULL,
    unidad_medida   ENUM('kg','g','L','ml','pza','caja','paquete','bolsa','rollo') NOT NULL,
    cantidad_actual DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    stock_minimo    DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_insumo),
    UNIQUE KEY uq_insumos_nombre (nombre),
    KEY idx_insumos_categoria (id_categoria),
    KEY idx_insumos_activo (activo),
    KEY idx_insumos_stock (cantidad_actual, stock_minimo),
    CONSTRAINT chk_insumos_cantidad CHECK (cantidad_actual >= 0),
    CONSTRAINT chk_insumos_minimo CHECK (stock_minimo >= 0),
    CONSTRAINT fk_insumos_categorias FOREIGN KEY (id_categoria)
        REFERENCES categorias (id_categoria) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Entradas de almacén
-- ----------------------------------------------------------------------------
CREATE TABLE entradas (
    id_entrada       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_insumo        INT UNSIGNED NOT NULL,
    cantidad         DECIMAL(12,3) NOT NULL,
    unidad_medida    ENUM('kg','g','L','ml','pza','caja','paquete','bolsa','rollo') NOT NULL,
    fecha_ingreso    DATE NOT NULL,
    fecha_caducidad  DATE NULL,
    numero_lote      VARCHAR(60) NOT NULL,
    id_usuario       INT UNSIGNED NOT NULL,
    observaciones    VARCHAR(500) NULL,
    fecha_registro   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_entrada),
    KEY idx_entradas_insumo (id_insumo),
    KEY idx_entradas_fecha (fecha_ingreso),
    KEY idx_entradas_usuario (id_usuario),
    KEY idx_entradas_caducidad (fecha_caducidad),
    CONSTRAINT chk_entradas_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_entradas_insumos FOREIGN KEY (id_insumo)
        REFERENCES insumos (id_insumo) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_entradas_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Lotes (trazabilidad y caducidad). Cada entrada genera un lote.
-- ----------------------------------------------------------------------------
CREATE TABLE lotes (
    id_lote          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_insumo        INT UNSIGNED NOT NULL,
    id_entrada       INT UNSIGNED NULL COMMENT 'NULL cuando proviene de un ajuste',
    numero_lote      VARCHAR(60) NOT NULL,
    cantidad_inicial DECIMAL(12,3) NOT NULL,
    cantidad_actual  DECIMAL(12,3) NOT NULL,
    fecha_caducidad  DATE NULL,
    fecha_registro   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_lote),
    UNIQUE KEY uq_lotes_entrada (id_entrada),
    KEY idx_lotes_insumo (id_insumo),
    KEY idx_lotes_caducidad (fecha_caducidad, cantidad_actual),
    CONSTRAINT chk_lotes_inicial CHECK (cantidad_inicial > 0),
    CONSTRAINT chk_lotes_actual CHECK (cantidad_actual >= 0),
    CONSTRAINT fk_lotes_insumos FOREIGN KEY (id_insumo)
        REFERENCES insumos (id_insumo) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lotes_entradas FOREIGN KEY (id_entrada)
        REFERENCES entradas (id_entrada) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Salidas de almacén (entrega a cocina)
-- ----------------------------------------------------------------------------
CREATE TABLE salidas (
    id_salida      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_insumo      INT UNSIGNED NOT NULL,
    cantidad       DECIMAL(12,3) NOT NULL,
    fecha          DATE NOT NULL,
    destino        VARCHAR(100) NOT NULL,
    id_usuario     INT UNSIGNED NOT NULL,
    observaciones  VARCHAR(500) NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_salida),
    KEY idx_salidas_insumo (id_insumo),
    KEY idx_salidas_fecha (fecha),
    KEY idx_salidas_usuario (id_usuario),
    CONSTRAINT chk_salidas_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_salidas_insumos FOREIGN KEY (id_insumo)
        REFERENCES insumos (id_insumo) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_salidas_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Mermas
-- ----------------------------------------------------------------------------
CREATE TABLE mermas (
    id_merma       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_insumo      INT UNSIGNED NOT NULL,
    cantidad       DECIMAL(12,3) NOT NULL,
    motivo         ENUM('Caducidad','Deterioro','Desperdicio en producción','Producto dañado') NOT NULL,
    fecha          DATE NOT NULL,
    descripcion    VARCHAR(500) NOT NULL,
    id_usuario     INT UNSIGNED NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_merma),
    KEY idx_mermas_insumo (id_insumo),
    KEY idx_mermas_fecha (fecha),
    KEY idx_mermas_motivo (motivo),
    KEY idx_mermas_usuario (id_usuario),
    CONSTRAINT chk_mermas_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_mermas_insumos FOREIGN KEY (id_insumo)
        REFERENCES insumos (id_insumo) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_mermas_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Historial de movimientos de inventario (auditoría)
-- ----------------------------------------------------------------------------
CREATE TABLE movimientos_inventario (
    id_movimiento    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_insumo        INT UNSIGNED NOT NULL,
    tipo             ENUM('Entrada','Salida','Merma','Ajuste') NOT NULL,
    cantidad         DECIMAL(12,3) NOT NULL,
    stock_anterior   DECIMAL(12,3) NOT NULL,
    stock_nuevo      DECIMAL(12,3) NOT NULL,
    id_referencia    INT UNSIGNED NULL,
    tabla_referencia VARCHAR(30) NULL,
    id_usuario       INT UNSIGNED NOT NULL,
    fecha_movimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones    VARCHAR(500) NULL,
    PRIMARY KEY (id_movimiento),
    KEY idx_mov_insumo (id_insumo),
    KEY idx_mov_tipo (tipo),
    KEY idx_mov_fecha (fecha_movimiento),
    KEY idx_mov_usuario (id_usuario),
    CONSTRAINT chk_mov_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_mov_stock CHECK (stock_anterior >= 0 AND stock_nuevo >= 0),
    CONSTRAINT fk_mov_insumos FOREIGN KEY (id_insumo)
        REFERENCES insumos (id_insumo) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_mov_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Producto terminado (hojaldres, volovanes y pizzas)
-- ----------------------------------------------------------------------------
CREATE TABLE productos (
    id_producto    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre         VARCHAR(80) NOT NULL,
    descripcion    VARCHAR(255) NULL,
    precio         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_actual   INT NOT NULL DEFAULT 0,
    stock_minimo   INT NOT NULL DEFAULT 0,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_producto),
    UNIQUE KEY uq_productos_nombre (nombre),
    CONSTRAINT chk_productos_stock CHECK (stock_actual >= 0),
    CONSTRAINT chk_productos_minimo CHECK (stock_minimo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Movimientos de producto terminado
-- ----------------------------------------------------------------------------
CREATE TABLE movimientos_producto (
    id_movimiento_producto BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_producto            INT UNSIGNED NOT NULL,
    tipo                   ENUM('Producción','Venta','Ajuste') NOT NULL,
    cantidad               INT NOT NULL,
    stock_anterior         INT NOT NULL,
    stock_nuevo            INT NOT NULL,
    id_usuario             INT UNSIGNED NOT NULL,
    fecha_movimiento       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones          VARCHAR(500) NULL,
    PRIMARY KEY (id_movimiento_producto),
    KEY idx_movprod_producto (id_producto),
    KEY idx_movprod_fecha (fecha_movimiento),
    CONSTRAINT chk_movprod_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_movprod_stock CHECK (stock_anterior >= 0 AND stock_nuevo >= 0),
    CONSTRAINT fk_movprod_productos FOREIGN KEY (id_producto)
        REFERENCES productos (id_producto) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movprod_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Datos base: roles, categorías, usuarios y productos
-- ============================================================================

INSERT INTO roles (id_rol, nombre, descripcion) VALUES
(1, 'gerente',  'Acceso completo al sistema, inventario, reportes y usuarios.'),
(2, 'cocinero', 'Consulta existencias, registra salidas y mermas de cocina.'),
(3, 'ventas',   'Consulta y controla el producto terminado disponible.');

INSERT INTO categorias (id_categoria, nombre, descripcion, perecedero) VALUES
(1, 'Secos',                'Harinas, azúcar, levadura, aceites y productos no perecederos.', 0),
(2, 'Refrigerados',         'Lácteos, carnes, embutidos y vegetales que requieren frío.', 1),
(3, 'Congelados',           'Masas y productos almacenados bajo congelación.', 1),
(4, 'Material de empaque',  'Cajas, bolsas, papel y desechables para el servicio.', 0);

-- Contraseñas de prueba (hash seguro con password_hash):
--   gerente  / Gerente123!
--   cocinero / Cocinero123!
--   ventas   / Ventas123!
INSERT INTO usuarios (id_usuario, nombre_completo, usuario, correo, contrasena, id_rol, activo) VALUES
(1, 'María Ordaz',        'gerente',  'gerente@laordaz.local',
 '$2y$10$byoeJJtfM4tgClbzwZj5geMdYtbgzKCXChyTV4D9XShckoQQB2nem', 1, 1),
(2, 'Juan Pérez',         'cocinero', 'cocina@laordaz.local',
 '$2y$10$g3mvRnoH8KRL3dYgctH4weanqitXIYWjCUijkKO6wdk.iB6a7lAs.', 2, 1),
(3, 'Ana López',          'ventas',   'ventas@laordaz.local',
 '$2y$10$Iun65yX/MpwzjWGO866aLeIXZ1wQDQuP.wfundov1tw8rbyz8EbK.', 3, 1);

INSERT INTO productos (id_producto, nombre, descripcion, precio, stock_actual, stock_minimo, activo) VALUES
(1, 'Hojaldre', 'Hojaldre artesanal recién horneado, disponible en mostrador.', 25.00, 24, 5, 1),
(2, 'Volován',  'Volován de hojaldre relleno, horneado del día.', 30.00, 18, 6, 1),
(3, 'Pizza',    'Pizza artesanal preparada al momento.', 85.00, 9, 4, 1);

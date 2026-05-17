-- ============================================================
--  Florería Alesli — Script de instalación para XAMPP/MySQL
--  Ejecutar en phpMyAdmin o con: mysql -u root floreria_alesli < setup.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `floreria_alesli`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `floreria_alesli`;

-- ─── Usuarios del sistema ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id_usuario`  INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`      VARCHAR(100) NOT NULL,
    `email`       VARCHAR(100) NOT NULL UNIQUE,
    `contrasena`  VARCHAR(255) NOT NULL,
    `rol`         ENUM('admin','empleado') DEFAULT 'empleado',
    `creado_en`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Catálogo de arreglos ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `catalogo_arreglos` (
    `id_catalogo_arreglo` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`      VARCHAR(100) NOT NULL,
    `descripcion` TEXT,
    `precio`      DECIMAL(10,2) NOT NULL DEFAULT 0,
    `foto_url`    VARCHAR(255),
    `disponible`  TINYINT(1) DEFAULT 1,
    `creado_en`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Clientes ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `clientes` (
    `id_cliente`          INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`              VARCHAR(100) NOT NULL,
    `telefono`            VARCHAR(20),
    `email`               VARCHAR(100),
    `direccion`           TEXT,
    `medio_pago_preferido` VARCHAR(50),
    `notas`               TEXT,
    `creado_en`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Pedidos ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id_pedido`              INT AUTO_INCREMENT PRIMARY KEY,
    `id_cliente`             INT,
    `id_usuario`             INT,
    `id_catalogo_arreglo`    INT,
    `nombre_cliente`         VARCHAR(100) NOT NULL,
    `telefono`               VARCHAR(20),
    `direccion_entrega`      TEXT NOT NULL,
    `producto`               VARCHAR(150) NOT NULL,
    `fecha_entrega`          DATE NOT NULL,
    `hora_entrega`           TIME,
    `mensaje_personalizado`  TEXT,
    `notas`                  TEXT,
    `estado`                 ENUM('pendiente','preparando','enviado','entregado','cancelado') DEFAULT 'pendiente',
    `medio_pago`             VARCHAR(50),
    `monto`                  DECIMAL(10,2),
    `foto_evidencia_url`     VARCHAR(255),
    `fecha_registro`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_cliente`)          REFERENCES `clientes`(`id_cliente`)                   ON DELETE SET NULL,
    FOREIGN KEY (`id_usuario`)          REFERENCES `usuarios`(`id_usuario`)                   ON DELETE SET NULL,
    FOREIGN KEY (`id_catalogo_arreglo`) REFERENCES `catalogo_arreglos`(`id_catalogo_arreglo`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Materiales (inventario) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `materiales` (
    `id_material`  INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`       VARCHAR(100) NOT NULL,
    `descripcion`  VARCHAR(255),
    `unidad`       VARCHAR(20) DEFAULT 'unidad',
    `stock_actual` INT DEFAULT 0,
    `stock_minimo` INT DEFAULT 5,
    `precio_costo` DECIMAL(10,2) DEFAULT 0,
    `creado_en`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Movimientos de inventario ───────────────────────────────
CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
    `id_mov_inventario` INT AUTO_INCREMENT PRIMARY KEY,
    `id_material`       INT NOT NULL,
    `id_usuario`        INT,
    `tipo`              ENUM('entrada','salida','baja') NOT NULL,
    `cantidad`          INT NOT NULL,
    `motivo`            VARCHAR(255),
    `fecha`             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_material`) REFERENCES `materiales`(`id_material`) ON DELETE CASCADE,
    FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`(`id_usuario`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Transacciones (contabilidad) ────────────────────────────
CREATE TABLE IF NOT EXISTS `transacciones` (
    `id_transaccion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pedido`      INT,
    `id_usuario`     INT,
    `tipo`           ENUM('ingreso','egreso') NOT NULL,
    `monto`          DECIMAL(10,2) NOT NULL,
    `categoria`      VARCHAR(50),
    `descripcion`    VARCHAR(255),
    `medio_pago`     VARCHAR(50),
    `fecha`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_pedido`)  REFERENCES `pedidos`(`id_pedido`)   ON DELETE SET NULL,
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Cursos ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cursos` (
    `id_curso`     INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`       VARCHAR(100) NOT NULL,
    `descripcion`  TEXT,
    `precio`       DECIMAL(10,2) NOT NULL DEFAULT 0,
    `fecha_inicio` DATE,
    `fecha_fin`    DATE,
    `cupo_maximo`  INT DEFAULT 10,
    `activo`       TINYINT(1) DEFAULT 1,
    `creado_en`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Alumnos ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `alumnos` (
    `id_alumno`  INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`     VARCHAR(100) NOT NULL,
    `telefono`   VARCHAR(20),
    `email`      VARCHAR(100),
    `creado_en`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Inscripciones ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inscripciones` (
    `id_inscripcion`   INT AUTO_INCREMENT PRIMARY KEY,
    `id_alumno`        INT,
    `id_curso`         INT,
    `fecha_inscripcion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `monto_pagado`     DECIMAL(10,2),
    `estado_pago`      ENUM('pendiente','completado') DEFAULT 'pendiente',
    FOREIGN KEY (`id_alumno`) REFERENCES `alumnos`(`id_alumno`) ON DELETE CASCADE,
    FOREIGN KEY (`id_curso`)  REFERENCES `cursos`(`id_curso`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Materiales por curso ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `material_curso` (
    `id_material_curso` INT AUTO_INCREMENT PRIMARY KEY,
    `id_curso`          INT NOT NULL,
    `id_material`       INT NOT NULL,
    `cantidad_requerida` INT NOT NULL DEFAULT 1,
    FOREIGN KEY (`id_curso`)    REFERENCES `cursos`(`id_curso`)       ON DELETE CASCADE,
    FOREIGN KEY (`id_material`) REFERENCES `materiales`(`id_material`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  DATOS DE PRUEBA
-- ============================================================

-- Usuarios (contraseñas: admin123 / empleado123)
INSERT INTO `usuarios` (`nombre`, `email`, `contrasena`, `rol`) VALUES
('Admin Alesli',  'admin@alesli.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Florencia Quispe', 'florencia@alesli.com', '$2y$10$TKh8H1.PfuBi02c2A6GxE.tKF4eTF6VhKYrVMC.VEnXw9GQVZ3xsC', 'empleado');

-- Catálogo
INSERT INTO `catalogo_arreglos` (`nombre`, `descripcion`, `precio`, `foto_url`) VALUES
('Ramo Rojo Pasión',   'Rosas rojas premium con follaje verde, el clásico de siempre.', 120.00, 'https://images.unsplash.com/photo-1548094990-c16ca90f1f0b?w=600&q=80'),
('Bouquet Primaveral', 'Mix de flores de temporada en tonos rosados y blancos.',         95.00,  'https://images.unsplash.com/photo-1490750967868-88df5691cc5d?w=600&q=80'),
('Caja de Girasoles',  'Girasoles frescos en caja de madera, ideales para regalo.',      110.00, 'https://images.unsplash.com/photo-1597848212624-a19eb35e2651?w=600&q=80'),
('Arreglo Romántico',  'Rosas blancas y rosas con detalles de lavanda y eucalipto.',     150.00, 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?w=600&q=80'),
('Centro de Mesa',     'Arreglo redondo con flores mixtas, perfecto para eventos.',      180.00, 'https://images.unsplash.com/photo-1487530811015-780a41aa2be2?w=600&q=80'),
('Mini Ramo Especial', 'Pequeño bouquet de tulipanes, ideal para sorprender.',           70.00,  'https://images.unsplash.com/photo-1508610048659-a06b669e3321?w=600&q=80');

-- Clientes
INSERT INTO `clientes` (`nombre`, `telefono`, `email`, `direccion`, `medio_pago_preferido`) VALUES
('María García',   '78901234', 'maria@gmail.com',   'Av. 6 de Agosto 234, La Paz',    'Efectivo'),
('Carlos López',   '76543210', 'carlos@gmail.com',  'C. Potosí 567, La Paz',           'Transferencia'),
('Ana Rodríguez',  '79876543', 'ana@gmail.com',     'Av. Arce 890, La Paz',            'QR'),
('Luis Martínez',  '71234567', 'luis@gmail.com',    'Av. Ballivián 321, La Paz',       'Efectivo'),
('Sofía Herrera',  '77654321', 'sofia@gmail.com',   'C. Comercio 654, La Paz',         'Transferencia');

-- Materiales
INSERT INTO `materiales` (`nombre`, `descripcion`, `unidad`, `stock_actual`, `stock_minimo`, `precio_costo`) VALUES
('Rosas Rojas',      'Rosas frescas rojas',                   'tallo',  45,  20, 2.50),
('Rosas Blancas',    'Rosas frescas blancas',                 'tallo',  30,  20, 2.50),
('Girasoles',        'Girasoles frescos',                     'tallo',  8,   15, 3.00),
('Tulipanes',        'Tulipanes de temporada',                'tallo',  25,  10, 4.00),
('Follaje Verde',    'Eucalipto, helecho y follaje decorativo','atado',  20,  10, 8.00),
('Papel Kraft',      'Papel para envolver arreglos',          'pliego', 100, 30, 0.50),
('Cinta Decorativa', 'Cintas de colores varios',              'rollo',  15,  5,  12.00),
('Cajas de Madera',  'Cajas decorativas para arreglos',       'unidad', 3,   10, 25.00),
('Lavanda',          'Flores de lavanda seca',                'atado',  10,  8,  15.00),
('Esponja Floral',   'Oasis para arreglos',                   'bloque', 20,  10, 5.00);

-- Pedidos de ejemplo
INSERT INTO `pedidos` (`id_cliente`,`id_usuario`,`id_catalogo_arreglo`,`nombre_cliente`,`telefono`,`direccion_entrega`,`producto`,`fecha_entrega`,`hora_entrega`,`mensaje_personalizado`,`estado`,`medio_pago`,`monto`) VALUES
(1, 1, 1, 'María García',  '78901234', 'Av. 6 de Agosto 234',  'Ramo Rojo Pasión',   CURDATE(),          '14:00', '¡Feliz cumpleaños!', 'pendiente',  'Efectivo',      120.00),
(2, 1, 2, 'Carlos López',  '76543210', 'C. Potosí 567',         'Bouquet Primaveral', CURDATE(),          '16:00', 'Con mucho cariño',   'preparando', 'Transferencia', 95.00),
(3, 1, 3, 'Ana Rodríguez', '79876543', 'Av. Arce 890',          'Caja de Girasoles',  CURDATE(),          '11:00', 'Para mi mamá',       'entregado',  'QR',            110.00),
(4, 1, 4, 'Luis Martínez', '71234567', 'Av. Ballivián 321',     'Arreglo Romántico',  DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00', 'Aniversario', 'pendiente', 'Efectivo', 150.00),
(5, 1, 6, 'Sofía Herrera', '77654321', 'C. Comercio 654',       'Mini Ramo Especial', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:30', NULL, 'pendiente', 'Transferencia', 70.00);

-- Transacciones
INSERT INTO `transacciones` (`id_pedido`,`id_usuario`,`tipo`,`monto`,`categoria`,`descripcion`,`medio_pago`) VALUES
(3, 1, 'ingreso', 110.00, 'Venta',   'Pago pedido #3 - Caja de Girasoles', 'QR'),
(NULL, 1, 'egreso',  180.00, 'Compra',  'Compra de rosas y follaje', 'Efectivo'),
(NULL, 1, 'egreso',   50.00, 'Compra',  'Papel y cintas decorativas', 'Efectivo'),
(NULL, 1, 'ingreso', 200.00, 'Curso',   'Inscripción curso básico (2 alumnos)', 'Transferencia'),
(NULL, 1, 'egreso',   35.00, 'Servicio','Transporte de materiales', 'Efectivo');

-- Cursos
INSERT INTO `cursos` (`nombre`, `descripcion`, `precio`, `fecha_inicio`, `fecha_fin`, `cupo_maximo`) VALUES
('Arreglos Florales Básico', 'Curso introductorio para aprender técnicas básicas de arreglos.',  150.00, DATE_ADD(CURDATE(), INTERVAL 7 DAY),  DATE_ADD(CURDATE(), INTERVAL 9 DAY),  8),
('Arreglos para Eventos',    'Diseño de centros de mesa y arreglos para bodas y eventos.',        250.00, DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 16 DAY), 6),
('Flores Preservadas',       'Técnicas de preservación y arreglos con flores eternas.',           200.00, DATE_ADD(CURDATE(), INTERVAL 21 DAY), DATE_ADD(CURDATE(), INTERVAL 23 DAY), 5);

-- Alumnos
INSERT INTO `alumnos` (`nombre`, `telefono`, `email`) VALUES
('Patricia Flores',  '72345678', 'patricia@gmail.com'),
('Roberto Choque',   '71239876', 'roberto@gmail.com'),
('Claudia Mamani',   '78001234', 'claudia@gmail.com');

-- Inscripciones
INSERT INTO `inscripciones` (`id_alumno`, `id_curso`, `monto_pagado`, `estado_pago`) VALUES
(1, 1, 150.00, 'completado'),
(2, 1, 150.00, 'completado'),
(3, 2,  50.00, 'pendiente');

-- Movimientos de inventario de ejemplo
INSERT INTO `movimientos_inventario` (`id_material`, `id_usuario`, `tipo`, `cantidad`, `motivo`) VALUES
(1, 1, 'entrada', 50, 'Compra semanal de rosas'),
(3, 1, 'salida',  5,  'Uso en pedido #3'),
(8, 1, 'entrada', 10, 'Reposición cajas'),
(3, 1, 'baja',    2,  'Flores dañadas');

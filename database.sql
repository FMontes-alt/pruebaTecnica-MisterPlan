-- ==========================================================
-- PRUEBA TÉCNICA MISTERPLAN - GESTIÓN DE RESERVAS
-- Base de Datos: MySQL / MariaDB (utf8mb4)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `misterplan_reservas`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `misterplan_reservas`;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Desactivar temporalmente revisión de claves foráneas para recreación limpia
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reserva_evento`;
DROP TABLE IF EXISTS `reserva`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------
-- 1. TABLA: reserva
-- ----------------------------------------------------------

CREATE TABLE `reserva` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre_huesped` VARCHAR(255) NOT NULL,
    `email_huesped` VARCHAR(255) NOT NULL,
    `nombre_alojamiento` VARCHAR(500) NOT NULL,
    `fecha_entrada` DATE NOT NULL,
    `fecha_salida` DATE NOT NULL,
    `estado` ENUM('pendiente','confirmada','cancelada') NOT NULL DEFAULT 'pendiente',
    `importe` DECIMAL(10, 2) UNSIGNED NULL,
    `notas` TEXT NULL,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Índices Reserva
    INDEX idx_estado (estado),
    INDEX idx_fechas (fecha_entrada, fecha_salida),
    INDEX idx_huesped (nombre_huesped),
    INDEX idx_creacion (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. TABLA: reserva_evento
-- ----------------------------------------------------------

CREATE TABLE `reserva_evento` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reserva_id` INT UNSIGNED NOT NULL,
    `tipo` VARCHAR(100) NOT NULL,
    `descripcion` TEXT NULL,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Clave foránea
    CONSTRAINT `fk_reserva_evento_reserva` 
        FOREIGN KEY (`reserva_id`) 
        REFERENCES `reserva`(`id`) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. DATOS DE EJEMPLO 
-- ----------------------------------------------------------

-- Reserva 1: Confirmada con notas
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`) VALUES
(1, 'Carlos Jiménez Navarro', 'carlos.jimenez@email.com', 'Hotel Rural Mirador de Gredos', '2026-11-01', '2026-11-05', 'confirmada', 480.00, 'Solicita cuna de viaje para bebé y habitación en planta baja.', '2026-10-10 10:15:00');

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`) VALUES
(1, 'CREACION', 'Reserva creada inicialmente en estado pendiente vía web.', '2026-10-10 10:15:00'),
(1, 'CONFIRMACION', 'Pago con tarjeta recibido satisfactoriamente. Reserva confirmada.', '2026-10-10 10:18:22');

-- Reserva 2: Pendiente de confirmación
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`) VALUES
(2, 'Lucía García Méndez', 'lucia.garcia@gmail.com', 'Apartamentos Sol & Mar - Torreón B', '2026-11-10', '2026-11-15', 'pendiente', 625.50, 'Llegada prevista sobre las 21:00h.', '2026-10-12 14:30:00');

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`) VALUES
(2, 'CREACION', 'Reserva creada pendiente de transferencia bancaria.', '2026-10-12 14:30:00');

-- Reserva 3: Cancelada
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`) VALUES
(3, 'Alejandro Morales Ruiz', 'amorales@empresa.es', 'Cabañas del Valle de Tena', '2026-10-25', '2026-10-28', 'cancelada', 320.00, 'Viaje cancelado por motivos laborales.', '2026-10-05 09:00:00');

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`) VALUES
(3, 'CREACION', 'Reserva registrada en estado pendiente.', '2026-10-05 09:00:00'),
(3, 'CONFIRMACION', 'Reserva confirmada por el cliente.', '2026-10-05 11:20:00'),
(3, 'CANCELACION', 'Cancelación solicitada por el huésped (fuerza mayor laboral).', '2026-10-08 16:45:00');

-- Reserva 4: Confirmada
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`) VALUES
(4, 'Elena Santos Delgado', 'elena.santos@outlook.com', 'Villa Serena Costa Brava', '2026-12-24', '2026-12-31', 'confirmada', 1450.00, 'Pack especial fin de año con cena incluida.', '2026-10-13 18:20:00');

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`) VALUES
(4, 'CREACION', 'Reserva creada con garantía de tarjeta.', '2026-10-13 18:20:00'),
(4, 'CONFIRMACION', 'Reserva confirmada tras validación de fianza.', '2026-10-13 18:22:15');

-- Reserva 5: Pendiente
INSERT INTO `reserva` (`id`, `nombre_huesped`, `email_huesped`, `nombre_alojamiento`, `fecha_entrada`, `fecha_salida`, `estado`, `importe`, `notas`, `fecha_creacion`) VALUES
(5, 'Mateo Fernández Ortiz', 'mateo.fernandez@correo.es', 'Casa Rural La Alcarria', '2026-11-20', '2026-11-22', 'pendiente', 210.00, 'Viaja con mascota (perro mediano educado).', '2026-10-14 12:05:00');

INSERT INTO `reserva_evento` (`reserva_id`, `tipo`, `descripcion`, `fecha_creacion`) VALUES
(5, 'CREACION', 'Reserva solicitada directamente desde motor MisterPlan.', '2026-10-14 12:05:00');

-- Asegurar que el autoincrement continúe después del último id insertado
ALTER TABLE `reserva` AUTO_INCREMENT = 6;
ALTER TABLE `reserva_evento` AUTO_INCREMENT = 8;

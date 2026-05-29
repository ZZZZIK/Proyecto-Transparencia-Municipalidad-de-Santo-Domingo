-- =========================================================================
-- Portal de Transparencia Activa — I. Municipalidad de Santo Domingo
-- Script SQL Consolidado para phpMyAdmin (MySQL / MariaDB)
-- Cumple con Estándares ISO 27001 / ISO 27701 e ISO/IEC 19628 (Privacidad)
-- =========================================================================

-- Configuración de juego de caracteres
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------------
-- 1. Estructura de Tablas
-- -------------------------------------------------------------------------

-- Tabla de Metadata de Transparencia
DROP TABLE IF EXISTS `metadata`;
CREATE TABLE `metadata` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ultima_actualizacion` DATE NOT NULL,
  `fuente` VARCHAR(100) NOT NULL,
  `periodo_informado` VARCHAR(50) NOT NULL,
  `recaudacion_total` BIGINT NOT NULL,
  `gasto_total` BIGINT NOT NULL,
  `poblacion_comuna` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Recaudación por Ítem
DROP TABLE IF EXISTS `recaudacion_items`;
CREATE TABLE `recaudacion_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `monto` BIGINT NOT NULL,
  `porcentaje` DECIMAL(5,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Áreas de Inversión / Gasto
DROP TABLE IF EXISTS `gasto_areas`;
CREATE TABLE `gasto_areas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `area` VARCHAR(100) NOT NULL UNIQUE,
  `icono` VARCHAR(50) NOT NULL,
  `color` VARCHAR(15) NOT NULL,
  `monto_asignado` BIGINT NOT NULL,
  `porcentaje` DECIMAL(5,2) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Desglose de Gastos (Sub-ítems)
DROP TABLE IF EXISTS `gasto_subitems`;
CREATE TABLE `gasto_subitems` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `area_id` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `monto` BIGINT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`area_id`) REFERENCES `gasto_areas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Proyecciones Financieras
DROP TABLE IF EXISTS `proyecciones_areas`;
CREATE TABLE `proyecciones_areas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `anio` INT NOT NULL,
  `area` VARCHAR(100) NOT NULL,
  `monto_proyectado` BIGINT NOT NULL,
  `variacion` DECIMAL(5,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Proyectos Municipales
DROP TABLE IF EXISTS `proyectos`;
CREATE TABLE `proyectos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(30) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `area` VARCHAR(50) NOT NULL,
  `monto` BIGINT NOT NULL,
  `porcentaje` DECIMAL(5,2) NOT NULL,
  `estado` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Servicios Municipales Contratados
DROP TABLE IF EXISTS `servicios`;
CREATE TABLE `servicios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `servicio` VARCHAR(100) NOT NULL,
  `proveedor` VARCHAR(100) NOT NULL,
  `monto` BIGINT NOT NULL,
  `porcentaje` DECIMAL(5,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Contribuyentes (Simulada bajo estándares de Privacidad ISO/IEC)
-- * rut_hash: Hash SHA-256 no reversible para realizar búsquedas rápidas (ej. Login ClaveÚnica) sin exponer el RUT
-- * rut_encriptado: RUT original guardado con cifrado robusto para visualización autorizada
-- * nombre_encriptado: Nombre completo guardado cifrado
-- * password_hash: Hash Bcrypt de la contraseña de acceso
-- * rol: Rol del usuario en el sistema (ciudadano o admin)
DROP TABLE IF EXISTS `contribuyentes`;
CREATE TABLE `contribuyentes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rut_hash` VARCHAR(64) NOT NULL UNIQUE,
  `rut_encriptado` TEXT NOT NULL,
  `nombre_encriptado` TEXT NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` VARCHAR(20) NOT NULL DEFAULT 'ciudadano',
  `aporte_contribucion` BIGINT NOT NULL DEFAULT 0,
  `aporte_circulacion` BIGINT NOT NULL DEFAULT 0,
  `aporte_aseo` BIGINT NOT NULL DEFAULT 0,
  `valores_mensuales` TEXT NOT NULL, -- Arreglo JSON serializado con aportes mensuales
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------------------
-- 2. Inserción de Datos Semilla (Datos Oficiales 2025/2026)
-- -------------------------------------------------------------------------

-- Insertar Metadata
INSERT INTO `metadata` (`id`, `ultima_actualizacion`, `fuente`, `periodo_informado`, `recaudacion_total`, `gasto_total`, `poblacion_comuna`)
VALUES (1, '2026-03-31', 'Dirección de Control y Finanzas', 'Año Fiscal 2025', 12850000000, 11920000000, 9800);

-- Insertar Recaudación por Ítem
INSERT INTO `recaudacion_items` (`nombre`, `monto`, `porcentaje`) VALUES
('Impuesto Territorial', 4112000000, 32.00),
('Permisos de Circulación', 2184500000, 17.00),
('Patentes Municipales', 1927500000, 15.00),
('Fondo Común Municipal', 2570000000, 20.00),
('Derechos de Aseo', 770000000, 6.00),
('Multas e Infracciones', 385500000, 3.00),
('Transferencias Gob. Central', 642500000, 5.00),
('Otros Ingresos', 257000000, 2.00);

-- Insertar Áreas de Inversión
INSERT INTO `gasto_areas` (`id`, `area`, `icono`, `color`, `monto_asignado`, `porcentaje`, `descripcion`) VALUES
(1, 'Educación', 'school', '#2E86AB', 3576000000, 30.00, 'Escuelas, liceos e infraestructura educativa.'),
(2, 'Salud', 'local_hospital', '#E8433E', 2384000000, 20.00, 'CESFAM, postas rurales y prevención.'),
(3, 'Seguridad Ciudadana', 'shield', '#1B4332', 1430000000, 12.00, 'Vigilancia, iluminación y seguridad comunal.'),
(4, 'Obras Municipales', 'construction', '#F77F00', 1192000000, 10.00, 'Pavimentación, áreas verdes y equipamiento.'),
(5, 'Servicios Comunitarios', 'groups', '#7209B7', 952000000, 8.00, 'Programas sociales y organizaciones comunitarias.'),
(6, 'Medio Ambiente', 'eco', '#52B788', 714000000, 6.00, 'Residuos, reciclaje y conservación costera.'),
(7, 'Cultura y Deporte', 'sports_soccer', '#E09F3E', 595000000, 5.00, 'Eventos, talleres e instalaciones deportivas.'),
(8, 'Administración', 'account_balance', '#6C757D', 1077000000, 9.00, 'Funcionamiento interno del municipio.');

-- Insertar Gasto Subitems (Desglose)
INSERT INTO `gasto_subitems` (`area_id`, `nombre`, `monto`) VALUES
(1, 'Personal Docente', 2146000000),
(1, 'Infraestructura Escolar', 536000000),
(1, 'Programas Educativos', 358000000),
(1, 'Material y Tecnología', 214000000),
(1, 'Becas', 179000000),
(1, 'Alimentación Escolar', 143000000),

(2, 'Personal de Salud', 1192000000),
(2, 'Insumos y Medicamentos', 358000000),
(2, 'Mantención CESFAM', 238000000),
(2, 'Prevención', 238000000),
(2, 'Equipamiento', 179000000),
(2, 'Atención Dental', 179000000),

(3, 'Televigilancia', 429000000),
(3, 'Iluminación LED', 357000000),
(3, 'Oficina Seguridad', 286000000),
(3, 'Plan Cuadrante', 215000000),
(3, 'Alarmas Comunitarias', 143000000),

(4, 'Pavimentación', 358000000),
(4, 'Áreas Verdes', 238000000),
(4, 'Edificios Municipales', 238000000),
(4, 'Sedes Comunitarias', 179000000),
(4, 'Señalización', 179000000),

(5, 'Adulto Mayor', 238000000),
(5, 'Protección de Derechos', 190000000),
(5, 'Inclusión', 190000000),
(5, 'Org. Comunitarias', 190000000),
(5, 'Registro Social', 144000000),

(6, 'Recolección Residuos', 321000000),
(6, 'Reciclaje', 143000000),
(6, 'Ecosistemas Costeros', 107000000),
(6, 'Educación Ambiental', 71000000),
(6, 'Monitoreo', 72000000),

(7, 'Instalaciones Deportivas', 178000000),
(7, 'Eventos Culturales', 149000000),
(7, 'Escuelas Deportivas', 119000000),
(7, 'Talleres', 89000000),
(7, 'Biblioteca', 60000000),

(8, 'Personal Administrativo', 646000000),
(8, 'Tecnología', 162000000),
(8, 'Servicios Básicos', 108000000),
(8, 'Insumos', 86000000),
(8, 'Capacitación', 75000000);

-- Insertar Proyecciones por Área
INSERT INTO `proyecciones_areas` (`anio`, `area`, `monto_proyectado`, `variacion`) VALUES
(2026, 'Educación', 3780000000, 5.70),
(2026, 'Salud', 2620000000, 9.90),
(2026, 'Seguridad', 1510000000, 5.60),
(2026, 'Obras', 1350000000, 13.30),
(2026, 'Comunitarios', 980000000, 2.90),
(2026, 'Medio Ambiente', 750000000, 5.00),
(2026, 'Cultura', 620000000, 4.20),
(2026, 'Administración', 1190000000, 10.50);

-- Insertar Proyectos Municipales
INSERT INTO `proyectos` (`codigo`, `nombre`, `area`, `monto`, `porcentaje`, `estado`) VALUES
('P-2025-001', 'Alumbrado Público Costanera', 'Seguridad', 185000000, 1.55, 'Completado'),
('P-2025-002', 'Multicancha Las Acacias', 'Deporte', 320000000, 2.42, 'En Ejecución'),
('P-2025-003', 'Pavimento Av. Principal', 'Obras', 450000000, 3.78, 'Completado'),
('P-2025-004', 'Ampliación CESFAM', 'Salud', 680000000, 4.28, 'En Ejecución'),
('P-2025-005', 'Televigilancia Centro', 'Seguridad', 220000000, 1.85, 'Completado'),
('P-2025-006', 'Restauración Escuela', 'Educación', 280000000, 1.64, 'En Ejecución'),
('P-2025-007', 'Reciclaje Comunal', 'Medio Ambiente', 95000000, 0.80, 'Completado'),
('P-2025-008', 'Centro Adulto Mayor', 'Comunitario', 150000000, 1.01, 'En Ejecución'),
('P-2025-009', 'Borde Costero', 'Obras', 520000000, 3.06, 'En Ejecución'),
('P-2025-010', 'Digitalización Municipal', 'Admin', 75000000, 0.63, 'Completado');

-- Insertar Servicios Municipales
INSERT INTO `servicios` (`servicio`, `proveedor`, `monto`, `porcentaje`) VALUES
('Recolección de Residuos', 'Servicios Ambientales SpA', 321000000, 2.69),
('Mantención Alumbrado', 'Enel Distribución', 180000000, 1.51),
('Seguridad Edificios', 'Securitas Chile S.A.', 96000000, 0.81),
('Transporte Escolar', 'Transportes Litoral', 72000000, 0.60),
('Mantención Áreas Verdes', 'Jardines del Pacífico', 145000000, 1.22),
('Conectividad Internet', 'Movistar Chile', 36000000, 0.30),
('Alimentación CESFAM', 'Catering Municipal', 48000000, 0.40);

-- Insertar Contribuyentes de Prueba (Cumplimiento de Privacidad y Requerimientos de Perfiles)
-- 1. Perfil A (Monto Bajo): RUT 12.345.678-9, Contraseña: Pb_123@01 (Alonso Alexander Maurel Murgas)
INSERT INTO `contribuyentes` (`id`, `rut_hash`, `rut_encriptado`, `nombre_encriptado`, `password_hash`, `rol`, `aporte_contribucion`, `aporte_circulacion`, `aporte_aseo`, `valores_mensuales`)
VALUES (
  1,
  '15e2b0d3c33891ebb0f1ef609ec419420c20e320ce94c65fbc8c3312448eb225', 
  'eyJpdiI6IktOVXFDT21HN1Z2Vm5DUER2WkIxOHc9PSIsInZhbHVlIjoiazJoaTRpc1ZqaVlsSjNTRXJtY1Y4SUdiSjJ2UXRzYmNlNVhPNXFFclBscz0iLCJtYWMiOiI2M2NjMTc5MGJjN2UyYTUyZjcwODE3MTFiYmIyMzg4OTk4Zjk3YjgzMTRiNjdiODU4MTUyYWJlMTdjZjZhNmU1IiwidGFnIjoiIn0=', 
  'eyJpdiI6ImlaMllKZjVtTHlpdEZHbThIemdaOGc9PSIsInZhbHVlIjoiQ0RFcDhmZzVVcXBTYzEvNTRORE9LejhwOGJpakpBdXBkT29tNTJPT2krOGZPc0hKSE1GQ2dndlVYeldaeGR1ZSIsIm1hYyI6IjJjZjRlOTA0ZmRkMDBiZmQ0MTliZWYzODU3Zjk0ZmNhNDljYjI4NjY1MzZlZTk2MWE4NWJiNTAzMjNlMjY2NWYiLCJ0YWciOiIifQ==',
  '$2y$10$AFKsoPSdORqBimgKa23/JebspJ7OnEhQhEEEHeouU9zRD7NKO0vby',
  'ciudadano',
  485000,
  165000,
  78000,
  '[58000,58000,62000,60000,65000,60000,62000,63000,58000,62000,60000,60000]'
);

-- 2. Perfil B (Monto Alto): RUT 89.234.255-4, Contraseña: Pb_321@02 (Sofía Elizabeth Álvarez Pérez)
INSERT INTO `contribuyentes` (`id`, `rut_hash`, `rut_encriptado`, `nombre_encriptado`, `password_hash`, `rol`, `aporte_contribucion`, `aporte_circulacion`, `aporte_aseo`, `valores_mensuales`)
VALUES (
  2,
  'd0a6cccbcb7427646e99fd630ccec71bc0f23a2341dd8a26d27e906a8c1a0e43', 
  'eyJpdiI6IndMTVN3alc0SXhpVjRHd1xlRDVOemc9PSIsInZhbHVlIjoiUHRvblZEOUo4Sk5CNlpPRkhLWXNpMHlVQzl0cjNhMWY4M1FSWWZKVC95ST0iLCJtYWMiOiI0NWFhYWMyNTNjZDcyOWFhMzMyMTVlNTBjNDJkZGNhODViZTBkNWEzYzhhZTEzMWM0NDExYzdlZWZiNjI4MzYyIiwidGFnIjoiIn0=', 
  'eyJpdiI6IkpHMXNCN1FTcHJJSHFjN0hTQUExemc9PSIsInZhbHVlIjoiaHM3S1VjU0hjMUVHYjNqUThseFpzS3ZCY1picmhBcmNIaFFBK2xxK29aM0I0LzBQb0s1ZUFDa0tXdnM1aEJyNyIsIm1hYyI6IjI0MGNkNzc5MjUwNjk1MjUwMzliOTU4Yzc2YzgyMGNkODhkNTg2Y2ZiYTQ3Yzk3YWQ1NzA1YzQwY2UxYmFhNDkiLCJ0YWciOiIifQ==',
  '$2y$10$dz5JmW/5wLnQLXEZTztxxOal/lJIwrG0JG8gAHoLVsYJ/nLlU1b5q',
  'ciudadano',
  3500000,
  1200000,
  300000,
  '[400000,420000,410000,430000,420000,410000,420000,430000,410000,420000,410000,420000]'
);

-- 3. Administrador Municipal: RUT 8.765.432-1, Contraseña: Pb_123@03
INSERT INTO `contribuyentes` (`id`, `rut_hash`, `rut_encriptado`, `nombre_encriptado`, `password_hash`, `rol`, `aporte_contribucion`, `aporte_circulacion`, `aporte_aseo`, `valores_mensuales`)
VALUES (
  3,
  'e24df920078c3dd4e7e8d2442f00e5c9ab2a231bb3918d65cc50906e49ecaef4', 
  'eyJpdiI6InV1NUFXV0JscUZ3dGk4RDlyWHNlNnc9PSIsInZhbHVlIjoiK0ZRSHREeFpkcUJUQ2tvVXFucnVWSGFRaHpDS21RVXIzek02MmgyTk54RT0iLCJtYWMiOiI2ZmJjMDQ3YmE0MDZiNDA1NjNmNWJlMGNiZTU5MzIxODk0Y2FlODc1Mjg1MWQ3NWMzNDkzYzM1NzE2ODFjNTkwIiwidGFnIjoiIn0=', 
  'eyJpdiI6IjhxdGJzcUxIUjdBc05JQUE4WHp1YXc9PSIsInZhbHVlIjoiSzFXcFR3aXQ3WnQ0ME92SlczREV5YzhQYVE4c09PcWRkS3kwWUdIUVNFVT0iLCJtYWMiOiJjYjQ0MDRiODAxODgyNjZlNTU5NzhmNzU1YTA5NzI0Mzc0N2I1YzU1MWZlNGNkOWUzN2Q3YmRkYzBjZjE1M2JmIiwidGFnIjoiIn0=',
  '$2y$10$GqPDTCmREFGhr.5qKnQ0xubjhpEPa4tGXe7ZWw5xIAdH/OvMbUKi6',
  'admin',
  0,
  0,
  0,
  '[]'
);

-- Tabla de Periodos de Consulta Habilitados/Deshabilitados
DROP TABLE IF EXISTS `periodos_consulta`;
CREATE TABLE `periodos_consulta` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `anio` INT NOT NULL,
  `mes` VARCHAR(20) NOT NULL, -- 'anual' o el número de mes (1-12)
  `nombre_mes` VARCHAR(50) NOT NULL,
  `habilitado` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_periodo` (`anio`, `mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar Periodos de Consulta Habilitados por Defecto (2023 - 2025)
INSERT INTO `periodos_consulta` (`anio`, `mes`, `nombre_mes`, `habilitado`) VALUES
(2023, 'anual', 'Anual (completo)', 1),
(2023, '1', 'Enero', 1),
(2023, '2', 'Febrero', 1),
(2023, '3', 'Marzo', 1),
(2023, '4', 'Abril', 1),
(2023, '5', 'Mayo', 1),
(2023, '6', 'Junio', 1),
(2023, '7', 'Julio', 1),
(2023, '8', 'Agosto', 1),
(2023, '9', 'Septiembre', 1),
(2023, '10', 'Octubre', 1),
(2023, '11', 'Noviembre', 1),
(2023, '12', 'Diciembre', 1),

(2024, 'anual', 'Anual (completo)', 1),
(2024, '1', 'Enero', 1),
(2024, '2', 'Febrero', 1),
(2024, '3', 'Marzo', 1),
(2024, '4', 'Abril', 1),
(2024, '5', 'Mayo', 1),
(2024, '6', 'Junio', 1),
(2024, '7', 'Julio', 1),
(2024, '8', 'Agosto', 1),
(2024, '9', 'Septiembre', 1),
(2024, '10', 'Octubre', 1),
(2024, '11', 'Noviembre', 1),
(2024, '12', 'Diciembre', 1),

(2025, 'anual', 'Anual (completo)', 1),
(2025, '1', 'Enero', 1),
(2025, '2', 'Febrero', 1),
(2025, '3', 'Marzo', 1),
(2025, '4', 'Abril', 1),
(2025, '5', 'Mayo', 1),
(2025, '6', 'Junio', 1),
(2025, '7', 'Julio', 1),
(2025, '8', 'Agosto', 1),
(2025, '9', 'Septiembre', 1),
(2025, '10', 'Octubre', 1),
(2025, '11', 'Noviembre', 1),
(2025, '12', 'Diciembre', 1),

-- Periodos de Consulta del Año 2026 (DESHABILITADOS por defecto para pruebas del admin)
(2026, 'anual', 'Anual (completo)', 0),
(2026, '1', 'Enero', 0),
(2026, '2', 'Febrero', 0),
(2026, '3', 'Marzo', 0),
(2026, '4', 'Abril', 0),
(2026, '5', 'Mayo', 0),
(2026, '6', 'Junio', 0),
(2026, '7', 'Julio', 0),
(2026, '8', 'Agosto', 0),
(2026, '9', 'Septiembre', 0),
(2026, '10', 'Octubre', 0),
(2026, '11', 'Noviembre', 0),
(2026, '12', 'Diciembre', 0);

SET FOREIGN_KEY_CHECKS = 1;

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: db
-- Tiempo de generación: 19-10-2026 a las 16:13:48
-- Versión del servidor: 26.7.0
-- Versión de PHP: 8.3.35

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `totilleria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `nombre`, `apellidos`, `razon_social`, `telefono`, `email`, `usuario`, `password_hash`, `activo`) VALUES
(1, 'Juan', 'Pérez', NULL, '722-111-2233', 'juan@email.com', NULL, NULL, 1),
(2, 'María', 'López', NULL, '722-444-5566', 'maria@email.com', NULL, NULL, 1),
(3, 'Taquería', 'El Pastor', 'Taquería El Pastor S.A. de C.V.', '722-777-8899', 'contacto@elpastor.com', NULL, NULL, 1),
(4, 'Restaurante', 'La Cocina', 'Restaurante La Cocina S.A.', '722-222-3344', 'info@lacocina.com', NULL, NULL, 1),
(5, 'Cliente', 'Anónimo', NULL, NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `detalle_pedido` int NOT NULL,
  `id_pedido` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`detalle_pedido`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 2, 50.00, 22.00),
(2, 1, 6, 20.00, 28.00),
(3, 2, 3, 30.00, 24.00),
(4, 3, 7, 10.00, 30.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `detalle_venta` int NOT NULL,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`detalle_venta`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 2.00, 20.00),
(2, 1, 5, 1.00, 26.00),
(3, 2, 2, 1.00, 22.00),
(4, 3, 3, 3.00, 24.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion`
--

CREATE TABLE `direccion` (
  `id_direccion` int NOT NULL,
  `id_cliente` int NOT NULL,
  `calle` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `colonia` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `municipio` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `direccion`
--

INSERT INTO `direccion` (`id_direccion`, `id_cliente`, `calle`, `colonia`, `municipio`, `codigo_postal`, `referencia`) VALUES
(1, 3, 'Av. Juárez 123', 'Centro', 'Oaxaca', '50000', 'Bodega Central, portón azul'),
(2, 3, 'Calle 5 de Mayo 456', 'Norte', 'Oaxaca', '50010', 'Sucursal Norte, junto a la farmacia'),
(3, 4, 'Av. Independencia 789', 'Centro', 'Oaxaca', '50020', 'Oficina administrativa, piso 3'),
(4, 1, 'Calle Hidalgo 321', 'Sur', 'Oaxaca', '50030', 'Casa particular, timbre rojo'),
(5, 2, 'Av. Universidad 654', 'Universidad', 'Oaxaca', '50040', 'Frente a la prepa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion_geolocalizacion`
--

CREATE TABLE `direccion_geolocalizacion` (
  `id_geolocalizacion` int NOT NULL,
  `id_direccion` int NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `google_place_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_geocodificacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `direccion_geolocalizacion`
--

INSERT INTO `direccion_geolocalizacion` (`id_geolocalizacion`, `id_direccion`, `latitud`, `longitud`, `google_place_id`, `fecha_geocodificacion`) VALUES
(1, 1, 19.29250000, -99.65320000, 'ChIJxxxxx1', '2026-10-12 09:44:29'),
(2, 2, 19.30000000, -99.66000000, 'ChIJxxxxx2', '2026-10-12 09:44:29'),
(3, 3, 19.28500000, -99.64500000, 'ChIJxxxxx3', '2026-10-12 09:44:29'),
(4, 4, 19.27500000, -99.63500000, 'ChIJxxxxx4', '2026-10-12 09:44:29'),
(5, 5, 19.28800000, -99.65000000, 'ChIJxxxxx5', '2026-10-12 09:44:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `id_empleado` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_rol` int NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`id_empleado`, `nombre`, `apellidos`, `id_rol`, `usuario`, `password_hash`, `email`, `activo`, `ultimo_login`) VALUES
(1, 'Carlos', 'Gómez', 1, 'carlos', '$2y$10$4werp5w36qJpqIJEiNe01OH9xeyqSxARaDWn8yI/S5kZtkP7/WUrO', 'chimilemmanuel@gmail.com', 1, NULL),
(2, 'Ana', 'Martínez', 2, 'ana', '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewKyDaKZzLhO5W.C', 'ana@tortilleria.com', 1, NULL),
(3, 'Luis', 'Hernández', 3, 'luis', '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewKyDaKZzLhO5W.C', 'luis@tortilleria.com', 1, NULL),
(4, 'Nombre', 'Apellidos', 1, 'tu_usuario', '$2b$12$R9h/cIPz0gi.URNNX3kh2OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'correo@tortilleria.com', 1, NULL),
(7, 'Nombre2', 'Apellidos2', 1, 'tu_usuario2', '$2b$12$R9h/cIPz0gi.URNNX3kh2OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'correo23@tortilleria.com', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiaprima`
--

CREATE TABLE `materiaprima` (
  `id_materia_prima` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unidad_medida` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materiaprima`
--

INSERT INTO `materiaprima` (`id_materia_prima`, `nombre`, `costo_unitario`, `unidad_medida`, `stock`) VALUES
(1, 'Maíz blanco', 12.50, 'Kilo', 500.00),
(2, 'Maíz azul', 15.00, 'Kilo', 200.00),
(3, 'Harina de trigo', 18.00, 'Kilo', 150.00),
(4, 'Gas LP', 25.00, 'Kilo', 100.00),
(5, 'Aceite vegetal', 35.00, 'Litro', 80.00),
(6, 'Sal', 8.00, 'Kilo', 50.00),
(7, 'Bolsas de plástico', 0.50, 'Pieza', 1000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `id_pago` int NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metodo_pago` enum('Efectivo','Cheque nominativo','Transferencia electrónica de fondos','Tarjeta de crédito','Tarjeta de débito') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `id_venta` int DEFAULT NULL,
  `id_pedido` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`id_pago`, `fecha_pago`, `metodo_pago`, `monto`, `id_venta`, `id_pedido`) VALUES
(1, '2026-10-12 09:45:21', 'Efectivo', 79.00, 1, NULL),
(2, '2026-10-12 09:45:21', 'Tarjeta de débito', 22.00, 2, NULL),
(3, '2026-10-12 09:45:21', 'Efectivo', 72.00, 3, NULL),
(4, '2026-10-12 09:45:21', 'Transferencia electrónica de fondos', 1660.00, NULL, 1),
(5, '2026-10-12 09:45:21', 'Cheque nominativo', 720.00, NULL, 2),
(6, '2026-10-12 09:45:25', 'Efectivo', 79.00, 1, NULL),
(7, '2026-10-12 09:45:25', 'Tarjeta de débito', 22.00, 2, NULL),
(8, '2026-10-12 09:45:25', 'Efectivo', 72.00, 3, NULL),
(9, '2026-10-12 09:45:25', 'Transferencia electrónica de fondos', 1660.00, NULL, 1),
(10, '2026-10-12 09:45:25', 'Cheque nominativo', 720.00, NULL, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int NOT NULL,
  `fecha_pedido` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('Pendiente','En Proceso','En Ruta','Entregado','Cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `id_cliente` int NOT NULL,
  `id_empleado` int NOT NULL,
  `id_repartidor` int DEFAULT NULL,
  `id_direccion` int DEFAULT NULL,
  `motivo_rechazo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_aceptacion` datetime DEFAULT NULL,
  `fecha_cancelacion` datetime DEFAULT NULL,
  `id_empleado_atiende` int DEFAULT NULL,
  `canal` enum('Web','Mostrador','Teléfono') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Web',
  `fecha_hora_entrega_solicitada` datetime DEFAULT NULL,
  `tipo_entrega` enum('Domicilio','Recoger en local') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Domicilio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`id_pedido`, `fecha_pedido`, `estado`, `id_cliente`, `id_empleado`, `id_repartidor`, `id_direccion`, `motivo_rechazo`, `fecha_aceptacion`, `fecha_cancelacion`, `id_empleado_atiende`, `canal`, `fecha_hora_entrega_solicitada`, `tipo_entrega`) VALUES
(1, '2026-10-12 09:44:56', 'Entregado', 3, 2, 1, 1, NULL, NULL, NULL, NULL, 'Web', NULL, 'Domicilio'),
(2, '2026-10-12 09:45:03', 'En Ruta', 4, 2, 2, 3, NULL, NULL, NULL, NULL, 'Web', NULL, 'Domicilio'),
(3, '2026-10-12 09:45:10', 'Pendiente', 3, 2, NULL, 1, NULL, NULL, NULL, NULL, 'Web', NULL, 'Domicilio'),
(4, '2026-10-12 12:41:58', 'Pendiente', 3, 2, NULL, 1, NULL, NULL, NULL, NULL, 'Web', '2025-01-20 10:00:00', 'Domicilio');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` enum('Tortilla','Masa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tortilla',
  `precio_kilo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unidad` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `nombre`, `categoria`, `precio_kilo`, `unidad`, `disponible`) VALUES
(1, 'Tortilla Mini / Botanera', 'Tortilla', 20.00, 'Kilo', 1),
(2, 'Tortilla Taquera', 'Tortilla', 22.00, 'Kilo', 1),
(3, 'Tortilla Copia Alta / Guisado', 'Tortilla', 24.00, 'Kilo', 1),
(4, 'Tortilla Estándar / Mesa', 'Tortilla', 25.00, 'Kilo', 1),
(5, 'Tortilla Grande / Enchilada', 'Tortilla', 26.00, 'Kilo', 1),
(6, 'Tortilla Burrera / Quesadillera', 'Tortilla', 28.00, 'Kilo', 1),
(7, 'Tortilla Tlayuda / Sobaquera', 'Tortilla', 30.00, 'Kilo', 1),
(8, 'Masa con cal', 'Masa', 18.00, 'Kilo', 1),
(9, 'Masa sin cal', 'Masa', 20.00, 'Kilo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_materiaprima`
--

CREATE TABLE `producto_materiaprima` (
  `id_producto` int NOT NULL,
  `id_materia_prima` int NOT NULL,
  `cantidad_necesaria` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_materiaprima`
--

INSERT INTO `producto_materiaprima` (`id_producto`, `id_materia_prima`, `cantidad_necesaria`) VALUES
(1, 1, 1.20),
(1, 4, 0.05),
(1, 6, 0.01),
(1, 7, 0.10),
(2, 1, 1.20),
(2, 4, 0.05),
(2, 6, 0.01),
(2, 7, 0.10),
(3, 1, 1.20),
(3, 4, 0.05),
(3, 6, 0.01),
(4, 1, 1.20),
(4, 4, 0.05),
(5, 1, 1.20),
(5, 4, 0.05),
(6, 1, 1.20),
(6, 4, 0.05),
(7, 2, 1.20),
(7, 4, 0.05);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperacion_password`
--

CREATE TABLE `recuperacion_password` (
  `id_recuperacion` int NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT '0',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `recuperacion_password`
--

INSERT INTO `recuperacion_password` (`id_recuperacion`, `email`, `codigo`, `expira_en`, `usado`, `creado_en`) VALUES
(1, 'chimilemmanuel@gmail.com', '115349', '2026-10-13 06:38:04', 1, '2026-10-13 06:28:04'),
(2, 'chimilemmanuel@gmail.com', '215921', '2026-10-13 06:38:10', 1, '2026-10-13 06:28:10'),
(3, 'chimilemmanuel@gmail.com', '967719', '2026-10-13 06:38:21', 1, '2026-10-13 06:28:21'),
(4, 'chimilemmanuel@gmail.com', '246240', '2026-10-13 06:38:33', 1, '2026-10-13 06:28:33'),
(5, 'chimilemmanuel@gmail.com', '125580', '2026-10-13 06:38:55', 1, '2026-10-13 06:28:55'),
(6, 'chimilemmanuel@gmail.com', '712879', '2026-10-13 06:39:53', 1, '2026-10-13 06:29:53'),
(7, 'chimilemmanuel@gmail.com', '953988', '2026-10-13 06:39:56', 1, '2026-10-13 06:29:56'),
(8, 'chimilemmanuel@gmail.com', '263544', '2026-10-13 06:40:42', 1, '2026-10-13 06:30:42'),
(9, 'chimilemmanuel@gmail.com', '974726', '2026-10-13 06:40:45', 1, '2026-10-13 06:30:45'),
(10, 'chimilemmanuel@gmail.com', '986865', '2026-10-13 06:40:49', 1, '2026-10-13 06:30:49'),
(11, 'chimilemmanuel@gmail.com', '309764', '2026-10-13 06:41:16', 1, '2026-10-13 06:31:16'),
(12, 'chimilemmanuel@gmail.com', '952020', '2026-10-13 06:41:49', 1, '2026-10-13 06:31:49'),
(13, 'chimilemmanuel@gmail.com', '719514', '2026-10-13 06:42:49', 1, '2026-10-13 06:32:49'),
(14, 'chimilemmanuel@gmail.com', '624318', '2026-10-13 06:45:25', 1, '2026-10-13 06:35:25'),
(15, 'chimilemmanuel@gmail.com', '222244', '2026-10-13 06:45:30', 1, '2026-10-13 06:35:30'),
(16, 'chimilemmanuel@gmail.com', '038620', '2026-10-13 06:45:37', 1, '2026-10-13 06:35:37'),
(17, 'chimilemmanuel@gmail.com', '596972', '2026-10-13 06:45:54', 1, '2026-10-13 06:35:54'),
(18, 'chimilemmanuel@gmail.com', '646611', '2026-10-13 06:45:58', 1, '2026-10-13 06:35:58'),
(19, 'chimilemmanuel@gmail.com', '890665', '2026-10-13 06:46:09', 0, '2026-10-13 06:36:09'),
(20, 'chimilemmanuel@gmail.com', '702972', '2026-10-13 06:57:31', 1, '2026-10-13 06:47:31'),
(21, 'chimilemmanuel@gmail.com', '069600', '2026-10-13 06:57:32', 1, '2026-10-13 06:47:32'),
(22, 'chimilemmanuel@gmail.com', '076490', '2026-10-13 07:00:17', 1, '2026-10-13 06:50:17'),
(23, 'chimilemmanuel@gmail.com', '487588', '2026-10-13 07:06:48', 1, '2026-10-13 06:56:48'),
(24, 'chimilemmanuel@gmail.com', '132405', '2026-10-13 07:08:31', 0, '2026-10-13 06:58:31'),
(25, 'chimilemmanuel@gmail.com', '185035', '2026-10-13 14:30:36', 1, '2026-10-13 14:20:36'),
(26, 'chimilemmanuel@gmail.com', '884452', '2026-10-13 18:07:37', 1, '2026-10-13 17:57:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repartidor`
--

CREATE TABLE `repartidor` (
  `id_repartidor` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('Disponible','No disponible') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `repartidor`
--

INSERT INTO `repartidor` (`id_repartidor`, `nombre`, `apellidos`, `telefono`, `estado`) VALUES
(1, 'Pedro', 'Ramírez', '722-123-4567', 'Disponible'),
(2, 'Jorge', 'López', '722-987-6543', 'Disponible'),
(3, 'Miguel', 'Torres', '722-555-1234', 'No disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int NOT NULL,
  `rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `rol`) VALUES
(1, 'Administrador'),
(2, 'Vendedor'),
(3, 'Cajero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `id_venta` int NOT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `monto_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `id_cliente` int DEFAULT NULL,
  `id_empleado` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`id_venta`, `fecha_venta`, `monto_total`, `id_cliente`, `id_empleado`) VALUES
(1, '2026-10-12 09:44:34', 79.00, NULL, 3),
(2, '2026-10-12 09:44:40', 22.00, 1, 2),
(3, '2026-10-12 09:44:48', 72.00, 2, 2);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_catalogo_web`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_catalogo_web` (
`categoria` enum('Tortilla','Masa')
,`disponible` tinyint(1)
,`id_producto` int
,`precio_kilo` decimal(10,2)
,`producto` varchar(100)
,`unidad` varchar(30)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_clientes_registrados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_clientes_registrados` (
`activo` tinyint(1)
,`apellidos` varchar(150)
,`email` varchar(100)
,`id_cliente` int
,`nombre` varchar(150)
,`razon_social` varchar(200)
,`telefono` varchar(20)
,`total_direcciones` bigint
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_login_empleado`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_login_empleado` (
`activo` tinyint(1)
,`apellidos` varchar(100)
,`email` varchar(120)
,`id_empleado` int
,`id_rol` int
,`modulo_principal` varchar(6)
,`nombre` varchar(100)
,`password_hash` varchar(255)
,`rol` varchar(50)
,`ultimo_login` datetime
,`usuario` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_pedidos_cliente`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_pedidos_cliente` (
`canal` enum('Web','Mostrador','Teléfono')
,`cliente` varchar(150)
,`colonia` varchar(100)
,`direccion_entrega` varchar(150)
,`estado` enum('Pendiente','En Proceso','En Ruta','Entregado','Cancelado')
,`fecha_hora_entrega_solicitada` datetime
,`fecha_pedido` datetime
,`id_pedido` int
,`municipio` varchar(100)
,`repartidor` varchar(100)
,`telefono_cliente` varchar(20)
,`tipo_entrega` enum('Domicilio','Recoger en local')
,`total_pedido` decimal(42,4)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_recuperacion_password`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_recuperacion_password` (
`codigo` char(6)
,`creado_en` datetime
,`email` varchar(120)
,`estado_codigo` varchar(8)
,`expira_en` datetime
,`id_cliente` bigint
,`id_empleado` bigint
,`id_recuperacion` int
,`minutos_restantes` bigint
,`tipo_usuario` varchar(13)
,`usado` tinyint(1)
);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`detalle_pedido`),
  ADD KEY `fk_detallepedido_pedido` (`id_pedido`),
  ADD KEY `fk_detallepedido_producto` (`id_producto`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`detalle_venta`),
  ADD KEY `fk_detalleventa_venta` (`id_venta`),
  ADD KEY `fk_detalleventa_producto` (`id_producto`);

--
-- Indices de la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD PRIMARY KEY (`id_direccion`),
  ADD KEY `fk_direccion_cliente` (`id_cliente`);

--
-- Indices de la tabla `direccion_geolocalizacion`
--
ALTER TABLE `direccion_geolocalizacion`
  ADD PRIMARY KEY (`id_geolocalizacion`),
  ADD UNIQUE KEY `id_direccion` (`id_direccion`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`id_empleado`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_empleado_rol` (`id_rol`);

--
-- Indices de la tabla `materiaprima`
--
ALTER TABLE `materiaprima`
  ADD PRIMARY KEY (`id_materia_prima`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `fk_pago_venta` (`id_venta`),
  ADD KEY `fk_pago_pedido` (`id_pedido`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `fk_pedido_cliente` (`id_cliente`),
  ADD KEY `fk_pedido_empleado` (`id_empleado`),
  ADD KEY `fk_pedido_repartidor` (`id_repartidor`),
  ADD KEY `fk_pedido_direccion` (`id_direccion`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `producto_materiaprima`
--
ALTER TABLE `producto_materiaprima`
  ADD PRIMARY KEY (`id_producto`,`id_materia_prima`),
  ADD KEY `fk_pmp_materia` (`id_materia_prima`);

--
-- Indices de la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  ADD PRIMARY KEY (`id_recuperacion`),
  ADD KEY `email` (`email`,`codigo`);

--
-- Indices de la tabla `repartidor`
--
ALTER TABLE `repartidor`
  ADD PRIMARY KEY (`id_repartidor`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `fk_venta_cliente` (`id_cliente`),
  ADD KEY `fk_venta_empleado` (`id_empleado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `detalle_pedido` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `detalle_venta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `id_direccion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `direccion_geolocalizacion`
--
ALTER TABLE `direccion_geolocalizacion`
  MODIFY `id_geolocalizacion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `id_empleado` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `materiaprima`
--
ALTER TABLE `materiaprima`
  MODIFY `id_materia_prima` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `id_pago` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_producto` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `recuperacion_password`
--
ALTER TABLE `recuperacion_password`
  MODIFY `id_recuperacion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `repartidor`
--
ALTER TABLE `repartidor`
  MODIFY `id_repartidor` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `id_venta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_catalogo_web`
--
DROP TABLE IF EXISTS `vista_catalogo_web`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_catalogo_web`  AS SELECT `p`.`id_producto` AS `id_producto`, `p`.`nombre` AS `producto`, `p`.`categoria` AS `categoria`, `p`.`precio_kilo` AS `precio_kilo`, `p`.`unidad` AS `unidad`, `p`.`disponible` AS `disponible` FROM `producto` AS `p` WHERE (`p`.`disponible` = 1) ORDER BY `p`.`categoria` ASC, `p`.`nombre` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_clientes_registrados`
--
DROP TABLE IF EXISTS `vista_clientes_registrados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_clientes_registrados`  AS SELECT `c`.`id_cliente` AS `id_cliente`, `c`.`nombre` AS `nombre`, `c`.`apellidos` AS `apellidos`, `c`.`razon_social` AS `razon_social`, `c`.`telefono` AS `telefono`, `c`.`email` AS `email`, `c`.`activo` AS `activo`, (select count(0) from `direccion` `d` where (`d`.`id_cliente` = `c`.`id_cliente`)) AS `total_direcciones` FROM `cliente` AS `c` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_login_empleado`
--
DROP TABLE IF EXISTS `vista_login_empleado`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_login_empleado`  AS SELECT `e`.`id_empleado` AS `id_empleado`, `e`.`nombre` AS `nombre`, `e`.`apellidos` AS `apellidos`, `e`.`usuario` AS `usuario`, `e`.`password_hash` AS `password_hash`, `e`.`email` AS `email`, `e`.`activo` AS `activo`, `e`.`ultimo_login` AS `ultimo_login`, `r`.`id_rol` AS `id_rol`, `r`.`rol` AS `rol`, (case when (`r`.`rol` = 'Administrador') then 'Admin' when (`r`.`rol` = 'Vendedor') then 'Ventas' when (`r`.`rol` = 'Cajero') then 'Caja' else 'Basico' end) AS `modulo_principal` FROM (`empleado` `e` join `rol` `r` on((`e`.`id_rol` = `r`.`id_rol`))) WHERE (`e`.`activo` = 1) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_pedidos_cliente`
--
DROP TABLE IF EXISTS `vista_pedidos_cliente`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_pedidos_cliente`  AS SELECT `p`.`id_pedido` AS `id_pedido`, `p`.`fecha_pedido` AS `fecha_pedido`, `p`.`estado` AS `estado`, `p`.`canal` AS `canal`, `p`.`tipo_entrega` AS `tipo_entrega`, `p`.`fecha_hora_entrega_solicitada` AS `fecha_hora_entrega_solicitada`, `c`.`nombre` AS `cliente`, `c`.`telefono` AS `telefono_cliente`, `r`.`nombre` AS `repartidor`, `d`.`calle` AS `direccion_entrega`, `d`.`colonia` AS `colonia`, `d`.`municipio` AS `municipio`, (select sum((`dp`.`cantidad` * `dp`.`precio_unitario`)) from `detalle_pedido` `dp` where (`dp`.`id_pedido` = `p`.`id_pedido`)) AS `total_pedido` FROM (((`pedido` `p` join `cliente` `c` on((`p`.`id_cliente` = `c`.`id_cliente`))) left join `repartidor` `r` on((`p`.`id_repartidor` = `r`.`id_repartidor`))) left join `direccion` `d` on((`p`.`id_direccion` = `d`.`id_direccion`))) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_recuperacion_password`
--
DROP TABLE IF EXISTS `vista_recuperacion_password`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_recuperacion_password`  AS SELECT `rp`.`id_recuperacion` AS `id_recuperacion`, `rp`.`email` AS `email`, `rp`.`codigo` AS `codigo`, `rp`.`expira_en` AS `expira_en`, `rp`.`usado` AS `usado`, `rp`.`creado_en` AS `creado_en`, (case when (`rp`.`usado` = 1) then 'Usado' when (`rp`.`expira_en` < now()) then 'Expirado' else 'Vigente' end) AS `estado_codigo`, timestampdiff(MINUTE,now(),`rp`.`expira_en`) AS `minutos_restantes`, (select `e`.`id_empleado` from `empleado` `e` where (`e`.`email` = `rp`.`email`) limit 1) AS `id_empleado`, (select `c`.`id_cliente` from `cliente` `c` where (`c`.`email` = `rp`.`email`) limit 1) AS `id_cliente`, (case when exists(select 1 from `empleado` `e` where (`e`.`email` = `rp`.`email`)) then 'Empleado' when exists(select 1 from `cliente` `c` where (`c`.`email` = `rp`.`email`)) then 'Cliente' else 'No registrado' end) AS `tipo_usuario` FROM `recuperacion_password` AS `rp` ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `fk_detallepedido_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detallepedido_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_detalleventa_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalleventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD CONSTRAINT `fk_direccion_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `direccion_geolocalizacion`
--
ALTER TABLE `direccion_geolocalizacion`
  ADD CONSTRAINT `fk_geo_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `fk_empleado_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_repartidor` FOREIGN KEY (`id_repartidor`) REFERENCES `repartidor` (`id_repartidor`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto_materiaprima`
--
ALTER TABLE `producto_materiaprima`
  ADD CONSTRAINT `fk_pmp_materia` FOREIGN KEY (`id_materia_prima`) REFERENCES `materiaprima` (`id_materia_prima`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pmp_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_venta_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.0.2-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tortilleria
-- ------------------------------------------------------
-- Server version	12.0.2-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `razon_social` varchar(200) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cliente` VALUES
(1,'Juan','Pérez',NULL,'722-111-2233','juan@email.com'),
(2,'María','López',NULL,'722-444-5566','maria@email.com'),
(3,'Taquería','El Pastor','Taquería El Pastor S.A. de C.V.','722-777-8899','contacto@elpastor.com'),
(4,'Restaurante','La Cocina','Restaurante La Cocina S.A.','722-222-3344','info@lacocina.com'),
(5,'Cliente','Anónimo',NULL,NULL,NULL);
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pedido` (
  `detalle_pedido` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`detalle_pedido`),
  KEY `fk_detallepedido_pedido` (`id_pedido`),
  KEY `fk_detallepedido_producto` (`id_producto`),
  CONSTRAINT `fk_detallepedido_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detallepedido_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedido`
--

LOCK TABLES `detalle_pedido` WRITE;
/*!40000 ALTER TABLE `detalle_pedido` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `detalle_pedido` VALUES
(1,1,2,50.00,22.00),
(2,1,6,20.00,28.00),
(3,2,3,30.00,24.00),
(4,3,7,10.00,30.00);
/*!40000 ALTER TABLE `detalle_pedido` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_venta` (
  `detalle_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`detalle_venta`),
  KEY `fk_detalleventa_venta` (`id_venta`),
  KEY `fk_detalleventa_producto` (`id_producto`),
  CONSTRAINT `fk_detalleventa_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalleventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta`
--

LOCK TABLES `detalle_venta` WRITE;
/*!40000 ALTER TABLE `detalle_venta` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `detalle_venta` VALUES
(1,1,1,2.00,20.00),
(2,1,5,1.00,26.00),
(3,2,2,1.00,22.00),
(4,3,3,3.00,24.00);
/*!40000 ALTER TABLE `detalle_venta` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `direccion`
--

DROP TABLE IF EXISTS `direccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `direccion` (
  `id_direccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `calle` varchar(150) NOT NULL,
  `colonia` varchar(100) NOT NULL,
  `municipio` varchar(100) NOT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_direccion`),
  KEY `fk_direccion_cliente` (`id_cliente`),
  CONSTRAINT `fk_direccion_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direccion`
--

LOCK TABLES `direccion` WRITE;
/*!40000 ALTER TABLE `direccion` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `direccion` VALUES
(1,3,'Av. Juárez 123','Centro','Oaxaca','50000','Bodega Central, portón azul'),
(2,3,'Calle 5 de Mayo 456','Norte','Oaxaca','50010','Sucursal Norte, junto a la farmacia'),
(3,4,'Av. Independencia 789','Centro','Oaxaca','50020','Oficina administrativa, piso 3'),
(4,1,'Calle Hidalgo 321','Sur','Oaxaca','50030','Casa particular, timbre rojo'),
(5,2,'Av. Universidad 654','Universidad','Oaxaca','50040','Frente a la prepa');
/*!40000 ALTER TABLE `direccion` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `direccion_geolocalizacion`
--

DROP TABLE IF EXISTS `direccion_geolocalizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `direccion_geolocalizacion` (
  `id_geolocalizacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_direccion` int(11) NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `google_place_id` varchar(255) DEFAULT NULL,
  `fecha_geocodificacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_geolocalizacion`),
  UNIQUE KEY `id_direccion` (`id_direccion`),
  CONSTRAINT `fk_geo_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direccion_geolocalizacion`
--

LOCK TABLES `direccion_geolocalizacion` WRITE;
/*!40000 ALTER TABLE `direccion_geolocalizacion` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `direccion_geolocalizacion` VALUES
(1,1,19.29250000,-99.65320000,'ChIJxxxxx1','2026-10-12 09:44:29'),
(2,2,19.30000000,-99.66000000,'ChIJxxxxx2','2026-10-12 09:44:29'),
(3,3,19.28500000,-99.64500000,'ChIJxxxxx3','2026-10-12 09:44:29'),
(4,4,19.27500000,-99.63500000,'ChIJxxxxx4','2026-10-12 09:44:29'),
(5,5,19.28800000,-99.65000000,'ChIJxxxxx5','2026-10-12 09:44:29');
/*!40000 ALTER TABLE `direccion_geolocalizacion` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `empleado`
--

DROP TABLE IF EXISTS `empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleado` (
  `id_empleado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `id_rol` int(11) NOT NULL,
  PRIMARY KEY (`id_empleado`),
  KEY `fk_empleado_rol` (`id_rol`),
  CONSTRAINT `fk_empleado_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleado`
--

LOCK TABLES `empleado` WRITE;
/*!40000 ALTER TABLE `empleado` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `empleado` VALUES
(1,'Carlos','Gómez',1),
(2,'Ana','Martínez',2),
(3,'Luis','Hernández',3);
/*!40000 ALTER TABLE `empleado` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `materiaprima`
--

DROP TABLE IF EXISTS `materiaprima`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `materiaprima` (
  `id_materia_prima` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unidad_medida` varchar(30) NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_materia_prima`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materiaprima`
--

LOCK TABLES `materiaprima` WRITE;
/*!40000 ALTER TABLE `materiaprima` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `materiaprima` VALUES
(1,'Maíz blanco',12.50,'Kilo',500.00),
(2,'Maíz azul',15.00,'Kilo',200.00),
(3,'Harina de trigo',18.00,'Kilo',150.00),
(4,'Gas LP',25.00,'Kilo',100.00),
(5,'Aceite vegetal',35.00,'Litro',80.00),
(6,'Sal',8.00,'Kilo',50.00),
(7,'Bolsas de plástico',0.50,'Pieza',1000.00);
/*!40000 ALTER TABLE `materiaprima` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pago`
--

DROP TABLE IF EXISTS `pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `metodo_pago` enum('Efectivo','Cheque nominativo','Transferencia electrónica de fondos','Tarjeta de crédito','Tarjeta de débito') NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_venta` int(11) DEFAULT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `fk_pago_venta` (`id_venta`),
  KEY `fk_pago_pedido` (`id_pedido`),
  CONSTRAINT `fk_pago_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago`
--

LOCK TABLES `pago` WRITE;
/*!40000 ALTER TABLE `pago` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pago` VALUES
(1,'2026-10-12 09:45:21','Efectivo',79.00,1,NULL),
(2,'2026-10-12 09:45:21','Tarjeta de débito',22.00,2,NULL),
(3,'2026-10-12 09:45:21','Efectivo',72.00,3,NULL),
(4,'2026-10-12 09:45:21','Transferencia electrónica de fondos',1660.00,NULL,1),
(5,'2026-10-12 09:45:21','Cheque nominativo',720.00,NULL,2),
(6,'2026-10-12 09:45:25','Efectivo',79.00,1,NULL),
(7,'2026-10-12 09:45:25','Tarjeta de débito',22.00,2,NULL),
(8,'2026-10-12 09:45:25','Efectivo',72.00,3,NULL),
(9,'2026-10-12 09:45:25','Transferencia electrónica de fondos',1660.00,NULL,1),
(10,'2026-10-12 09:45:25','Cheque nominativo',720.00,NULL,2);
/*!40000 ALTER TABLE `pago` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','En Proceso','En Ruta','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `id_cliente` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `id_repartidor` int(11) DEFAULT NULL,
  `id_direccion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_pedido`),
  KEY `fk_pedido_cliente` (`id_cliente`),
  KEY `fk_pedido_empleado` (`id_empleado`),
  KEY `fk_pedido_repartidor` (`id_repartidor`),
  KEY `fk_pedido_direccion` (`id_direccion`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_repartidor` FOREIGN KEY (`id_repartidor`) REFERENCES `repartidor` (`id_repartidor`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `pedido` VALUES
(1,'2026-10-12 09:44:56','Entregado',3,2,1,1),
(2,'2026-10-12 09:45:03','En Ruta',4,2,2,3),
(3,'2026-10-12 09:45:10','Pendiente',3,2,NULL,1);
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `precio_kilo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unidad` varchar(30) NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `producto` VALUES
(1,'Tortilla Mini / Botanera',20.00,'Kilo',1),
(2,'Tortilla Taquera',22.00,'Kilo',1),
(3,'Tortilla Copia Alta / Guisado',24.00,'Kilo',1),
(4,'Tortilla Estándar / Mesa',25.00,'Kilo',1),
(5,'Tortilla Grande / Enchilada',26.00,'Kilo',1),
(6,'Tortilla Burrera / Quesadillera',28.00,'Kilo',1),
(7,'Tortilla Tlayuda / Sobaquera',30.00,'Kilo',1);
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `producto_materiaprima`
--

DROP TABLE IF EXISTS `producto_materiaprima`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_materiaprima` (
  `id_producto` int(11) NOT NULL,
  `id_materia_prima` int(11) NOT NULL,
  `cantidad_necesaria` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_producto`,`id_materia_prima`),
  KEY `fk_pmp_materia` (`id_materia_prima`),
  CONSTRAINT `fk_pmp_materia` FOREIGN KEY (`id_materia_prima`) REFERENCES `materiaprima` (`id_materia_prima`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pmp_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_materiaprima`
--

LOCK TABLES `producto_materiaprima` WRITE;
/*!40000 ALTER TABLE `producto_materiaprima` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `producto_materiaprima` VALUES
(1,1,1.20),
(1,4,0.05),
(1,6,0.01),
(1,7,0.10),
(2,1,1.20),
(2,4,0.05),
(2,6,0.01),
(2,7,0.10),
(3,1,1.20),
(3,4,0.05),
(3,6,0.01),
(4,1,1.20),
(4,4,0.05),
(5,1,1.20),
(5,4,0.05),
(6,1,1.20),
(6,4,0.05),
(7,2,1.20),
(7,4,0.05);
/*!40000 ALTER TABLE `producto_materiaprima` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `repartidor`
--

DROP TABLE IF EXISTS `repartidor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `repartidor` (
  `id_repartidor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `estado` enum('Disponible','No disponible') NOT NULL DEFAULT 'Disponible',
  PRIMARY KEY (`id_repartidor`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repartidor`
--

LOCK TABLES `repartidor` WRITE;
/*!40000 ALTER TABLE `repartidor` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `repartidor` VALUES
(1,'Pedro','Ramírez','722-123-4567','Disponible'),
(2,'Jorge','López','722-987-6543','Disponible'),
(3,'Miguel','Torres','722-555-1234','No disponible');
/*!40000 ALTER TABLE `repartidor` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `rol` VALUES
(1,'Administrador'),
(2,'Vendedor'),
(3,'Cajero');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `venta`
--

DROP TABLE IF EXISTS `venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `monto_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_cliente` int(11) DEFAULT NULL,
  `id_empleado` int(11) NOT NULL,
  PRIMARY KEY (`id_venta`),
  KEY `fk_venta_cliente` (`id_cliente`),
  KEY `fk_venta_empleado` (`id_empleado`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta`
--

LOCK TABLES `venta` WRITE;
/*!40000 ALTER TABLE `venta` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `venta` VALUES
(1,'2026-10-12 09:44:34',79.00,NULL,3),
(2,'2026-10-12 09:44:40',22.00,1,2),
(3,'2026-10-12 09:44:48',72.00,2,2);
/*!40000 ALTER TABLE `venta` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Dumping routines for database 'tortilleria'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-12  9:46:16

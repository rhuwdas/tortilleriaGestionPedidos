-- MySQL dump 10.13  Distrib 26.7.0, for Linux (x86_64)
--
-- Host: localhost    Database: totilleria
-- ------------------------------------------------------
-- Server version	26.7.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `totilleria`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `totilleria` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `totilleria`;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'Juan','Pérez',NULL,'722-111-2233','juan@email.com',NULL,NULL,1),(2,'María','López',NULL,'722-444-5566','maria@email.com',NULL,NULL,1),(3,'Taquería','El Pastor','Taquería El Pastor S.A. de C.V.','722-777-8899','contacto@elpastor.com',NULL,NULL,1),(4,'Restaurante','La Cocina','Restaurante La Cocina S.A.','722-222-3344','info@lacocina.com',NULL,NULL,1),(5,'Cliente','Anónimo',NULL,NULL,NULL,NULL,NULL,1),(8,'juan','perez lopez',NULL,'9511786728',NULL,'s','$2y$10$SJmPyawBLHIaeFymlKvf6ODOSOW.jFHCFks5nXRSxqyMhrVSnrcG6',1),(9,'Juan','Pepe',NULL,'9515328254','ana@tortilleria.com','citlalihernandez3b10@gmail.com','$2y$10$Rp1wXJxVoX2A8KBlxda/AOdHtGme8yMXArIWK35GZEQvp9KW002xm',1),(57,'Cliente','Prueba',NULL,'55-0000-0001','cliente.prueba@tortilleria.local','cliente.prueba','$2y$10$Yt6NJxS0O3dRGB9iiD6r3eZA1MUqoOzUznNXCkWvks.iFGxyd.yxi',1),(58,'Emmanuel Fernando','Chimil Pacheco',NULL,'9511785728','cajero.prueba@tortilleria.local','cajero.prueba@tortilleria.local','$2y$10$x4wemtjIPcq3SSVk/bcKn.vGJIbVBPLo52du3aZfNYE43/i3aD7iW',1),(74,'Erain','Luna',NULL,'9613443524','luna@gmail.com.mx.mexico','luna@gmail.com.mx.mexico','$2y$10$GCYTNXfv1/O2hqzmTbsZ/e4tWeIfV3InquuyDb9p1uiIlyGvSWt/S',1);
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pedido` (
  `detalle_pedido` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`detalle_pedido`),
  KEY `fk_detallepedido_pedido` (`id_pedido`),
  KEY `fk_detallepedido_producto` (`id_producto`),
  CONSTRAINT `fk_detallepedido_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detallepedido_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedido`
--

LOCK TABLES `detalle_pedido` WRITE;
/*!40000 ALTER TABLE `detalle_pedido` DISABLE KEYS */;
INSERT INTO `detalle_pedido` VALUES (1,1,2,50.00,22.00),(2,1,6,20.00,28.00),(3,2,3,30.00,24.00),(4,3,7,10.00,30.00),(22,19,5,1.00,26.00),(23,20,5,1.00,26.00),(67,54,9,1.00,20.00),(68,55,2,1.00,22.00),(69,56,4,1.00,25.00),(70,57,6,1.00,28.00),(71,58,9,1.00,20.00),(76,61,4,1.00,25.00),(95,77,3,2.00,24.00),(96,78,3,2.00,24.00),(97,78,2,5.00,22.00),(98,79,9,1.00,20.00),(112,89,8,1.00,18.00),(113,90,9,1.00,20.00),(114,91,6,1.00,28.00),(140,108,6,1.00,28.00),(141,109,5,1.00,26.00),(142,110,1,1.00,20.00);
/*!40000 ALTER TABLE `detalle_pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_venta` (
  `detalle_venta` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`detalle_venta`),
  KEY `fk_detalleventa_venta` (`id_venta`),
  KEY `fk_detalleventa_producto` (`id_producto`),
  CONSTRAINT `fk_detalleventa_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalleventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta`
--

LOCK TABLES `detalle_venta` WRITE;
/*!40000 ALTER TABLE `detalle_venta` DISABLE KEYS */;
INSERT INTO `detalle_venta` VALUES (1,1,1,2.00,20.00),(2,1,5,1.00,26.00),(3,2,2,1.00,22.00),(4,3,3,3.00,24.00),(9,6,2,1.00,22.00),(10,7,7,10.00,30.00),(13,9,3,2.00,24.00),(14,10,3,2.00,24.00),(15,10,2,5.00,22.00),(18,12,8,1.00,18.00),(23,15,1,1.00,20.00);
/*!40000 ALTER TABLE `detalle_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `direccion`
--

DROP TABLE IF EXISTS `direccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `direccion` (
  `id_direccion` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `calle` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `colonia` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `municipio` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_direccion`),
  KEY `fk_direccion_cliente` (`id_cliente`),
  CONSTRAINT `fk_direccion_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direccion`
--

LOCK TABLES `direccion` WRITE;
/*!40000 ALTER TABLE `direccion` DISABLE KEYS */;
INSERT INTO `direccion` VALUES (1,3,'Av. Juárez 123','Centro','Oaxaca','50000','Bodega Central, portón azul'),(2,3,'Calle 5 de Mayo 456','Norte','Oaxaca','50010','Sucursal Norte, junto a la farmacia'),(3,4,'Av. Independencia 789','Centro','Oaxaca','50020','Oficina administrativa, piso 3'),(4,1,'Calle Hidalgo 321','Sur','Oaxaca','50030','Casa particular, timbre rojo'),(5,2,'Av. Universidad 654','Universidad','Oaxaca','50040','Frente a la prepa'),(31,57,'Av. Prueba 123','Centro','Oaxaca','68000','Dirección de prueba'),(33,9,'16 de septiembre d','aguayo','xoxo','','no hay'),(42,74,'fatrenidad','1a Ampliación de Xoxo','Santa Cruz Xoxocotlán','','casa roja, porton negro'),(45,74,'16sdfsd','Antequera','Santa Cruz Xoxocotlán','','casa verde');
/*!40000 ALTER TABLE `direccion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `direccion_geolocalizacion`
--

DROP TABLE IF EXISTS `direccion_geolocalizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `direccion_geolocalizacion` (
  `id_geolocalizacion` int NOT NULL AUTO_INCREMENT,
  `id_direccion` int NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `google_place_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_geocodificacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
INSERT INTO `direccion_geolocalizacion` VALUES (1,1,19.29250000,-99.65320000,'ChIJxxxxx1','2026-10-12 09:44:29'),(2,2,19.30000000,-99.66000000,'ChIJxxxxx2','2026-10-12 09:44:29'),(3,3,19.28500000,-99.64500000,'ChIJxxxxx3','2026-10-12 09:44:29'),(4,4,19.27500000,-99.63500000,'ChIJxxxxx4','2026-10-12 09:44:29'),(5,5,19.28800000,-99.65000000,'ChIJxxxxx5','2026-10-12 09:44:29');
/*!40000 ALTER TABLE `direccion_geolocalizacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleado`
--

DROP TABLE IF EXISTS `empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleado` (
  `id_empleado` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_rol` int NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_empleado_rol` (`id_rol`),
  CONSTRAINT `fk_empleado_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleado`
--

LOCK TABLES `empleado` WRITE;
/*!40000 ALTER TABLE `empleado` DISABLE KEYS */;
INSERT INTO `empleado` VALUES (1,'Carlos','Gómez',3,'carlos','$2y$10$Q2ne/jI3pvdeif81V6sDPe49BAfqZ3ceI8DW6YKYa8cfmPcRm5dFW','chimilemmanuel@gmail.com',1,'2026-10-27 02:26:57'),(2,'Ana','Martínez',2,'ana','$2y$10$Q2ne/jI3pvdeif81V6sDPe49BAfqZ3ceI8DW6YKYa8cfmPcRm5dFW','citlalihernandez3b10@gmail.com',1,NULL),(3,'Luis','Hernández',3,'luis','$2y$10$Q2ne/jI3pvdeif81V6sDPe49BAfqZ3ceI8DW6YKYa8cfmPcRm5dFW','luis@tortilleria.com',1,NULL),(4,'Nombre','Apellidos',1,'tu_usuario','$2y$10$Q2ne/jI3pvdeif81V6sDPe49BAfqZ3ceI8DW6YKYa8cfmPcRm5dFW','correo@tortilleria.com',1,NULL),(7,'Nombre2','Apellidos2',1,'tu_usuario2','$2y$10$Q2ne/jI3pvdeif81V6sDPe49BAfqZ3ceI8DW6YKYa8cfmPcRm5dFW','correo23@tortilleria.com',1,NULL),(31,'Vendedor','Prueba',2,'vendedor.prueba','$2y$10$QPTzDbS26NRi0D/Bbys3gO4ORdJMAVydLOUcdNa2GG0mHRA1JZQoG','vendedor.prueba@tortilleria.local',1,'2026-10-27 18:47:54'),(32,'Cajero','Prueba',3,'cajero.prueba','$2y$10$6Gc1o7UnzIs1wQZ0tVWJk.rMHLCLrve4DcY0eHr.MJ0Hh7oyjRiYS','cajero.prueba@tortilleria.local',1,'2026-10-27 17:56:34'),(33,'Repartidor','Prueba',4,'repartidor.prueba','$2y$10$5nFE24K3Dx7DgxSadUYTqOFkPazjmLmhaXHaqkYyAvgz6nYG6CMH.','repartidor.prueba@tortilleria.local',1,'2026-10-27 18:46:49');
/*!40000 ALTER TABLE `empleado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materiaprima`
--

DROP TABLE IF EXISTS `materiaprima`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materiaprima` (
  `id_materia_prima` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unidad_medida` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_materia_prima`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materiaprima`
--

LOCK TABLES `materiaprima` WRITE;
/*!40000 ALTER TABLE `materiaprima` DISABLE KEYS */;
INSERT INTO `materiaprima` VALUES (1,'Maíz blanco',12.50,'Kilo',500.00),(2,'Maíz azul',15.00,'Kilo',200.00),(3,'Harina de trigo',18.00,'Kilo',150.00),(4,'Gas LP',25.00,'Kilo',100.00),(5,'Aceite vegetal',35.00,'Litro',80.00),(6,'Sal',8.00,'Kilo',50.00),(7,'Bolsas de plástico',0.50,'Pieza',1000.00);
/*!40000 ALTER TABLE `materiaprima` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago`
--

DROP TABLE IF EXISTS `pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago` (
  `id_pago` int NOT NULL AUTO_INCREMENT,
  `fecha_pago` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metodo_pago` enum('Efectivo','Cheque nominativo','Transferencia electrónica de fondos','Tarjeta de crédito','Tarjeta de débito') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `id_venta` int DEFAULT NULL,
  `id_pedido` int DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `fk_pago_venta` (`id_venta`),
  KEY `fk_pago_pedido` (`id_pedido`),
  CONSTRAINT `fk_pago_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago`
--

LOCK TABLES `pago` WRITE;
/*!40000 ALTER TABLE `pago` DISABLE KEYS */;
INSERT INTO `pago` VALUES (1,'2026-10-12 09:45:21','Efectivo',79.00,1,NULL),(2,'2026-10-12 09:45:21','Tarjeta de débito',22.00,2,NULL),(3,'2026-10-12 09:45:21','Efectivo',72.00,3,NULL),(4,'2026-10-12 09:45:21','Transferencia electrónica de fondos',1660.00,NULL,1),(5,'2026-10-12 09:45:21','Cheque nominativo',720.00,NULL,2),(6,'2026-10-12 09:45:25','Efectivo',79.00,1,NULL),(7,'2026-10-12 09:45:25','Tarjeta de débito',22.00,2,NULL),(8,'2026-10-12 09:45:25','Efectivo',72.00,3,NULL),(9,'2026-10-12 09:45:25','Transferencia electrónica de fondos',1660.00,NULL,1),(10,'2026-10-12 09:45:25','Cheque nominativo',720.00,NULL,2),(13,'2026-10-27 14:47:24','Efectivo',22.00,6,55),(14,'2026-10-27 16:44:15','Efectivo',300.00,7,3),(16,'2026-10-27 17:32:18','Efectivo',48.00,9,77),(17,'2026-10-27 17:35:28','Efectivo',158.00,10,78),(19,'2026-10-27 17:54:43','Efectivo',18.00,12,89),(22,'2026-10-27 18:48:12','Efectivo',20.00,15,110);
/*!40000 ALTER TABLE `pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `fecha_pedido` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('Pendiente','En Proceso','En Ruta','Entregado','Cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `id_cliente` int NOT NULL,
  `id_empleado` int NOT NULL,
  `id_repartidor` int DEFAULT NULL,
  `id_direccion` int DEFAULT NULL,
  `motivo_rechazo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_aceptacion` datetime DEFAULT NULL,
  `fecha_cancelacion` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `id_empleado_atiende` int DEFAULT NULL,
  `canal` enum('Web','Mostrador','Teléfono') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Web',
  `fecha_hora_entrega_solicitada` datetime DEFAULT NULL,
  `tipo_entrega` enum('Domicilio','Recoger en local') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Domicilio',
  PRIMARY KEY (`id_pedido`),
  KEY `fk_pedido_cliente` (`id_cliente`),
  KEY `fk_pedido_empleado` (`id_empleado`),
  KEY `fk_pedido_repartidor` (`id_repartidor`),
  KEY `fk_pedido_direccion` (`id_direccion`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_repartidor` FOREIGN KEY (`id_repartidor`) REFERENCES `repartidor` (`id_repartidor`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
INSERT INTO `pedido` VALUES (1,'2026-10-12 09:44:56','Entregado',3,2,1,1,NULL,NULL,NULL,NULL,NULL,'Web',NULL,'Domicilio'),(2,'2026-10-12 09:45:03','En Ruta',4,2,2,3,NULL,NULL,NULL,NULL,NULL,'Web',NULL,'Domicilio'),(3,'2026-10-12 09:45:10','En Proceso',3,2,NULL,1,NULL,'2026-10-27 14:43:42',NULL,NULL,32,'Web',NULL,'Domicilio'),(4,'2026-10-12 12:41:58','En Proceso',3,2,NULL,1,NULL,'2026-10-27 14:43:32',NULL,NULL,32,'Web','2025-01-20 10:00:00','Domicilio'),(19,'2026-10-27 07:55:05','En Proceso',9,1,NULL,NULL,NULL,'2026-10-27 14:43:40',NULL,NULL,32,'Web','2026-10-27 08:54:00','Recoger en local'),(20,'2026-10-27 07:58:22','En Proceso',9,1,NULL,NULL,NULL,'2026-10-27 14:43:38',NULL,NULL,32,'Web','2026-10-27 02:58:00','Recoger en local'),(54,'2026-10-27 14:43:57','Pendiente',9,32,NULL,NULL,NULL,NULL,NULL,NULL,32,'Mostrador','2026-10-27 15:43:00','Recoger en local'),(55,'2026-10-27 14:44:43','En Proceso',9,32,NULL,NULL,NULL,'2026-10-27 14:47:24',NULL,NULL,32,'Mostrador','2026-10-27 15:43:00','Recoger en local'),(56,'2026-10-27 14:48:11','Pendiente',57,1,NULL,31,NULL,NULL,NULL,NULL,NULL,'Web','2026-10-27 15:47:00','Domicilio'),(57,'2026-10-27 16:35:53','En Proceso',57,1,NULL,31,NULL,'2026-10-27 17:28:39',NULL,NULL,31,'Web','2026-10-28 10:00:00','Domicilio'),(58,'2026-10-27 16:47:39','Pendiente',9,32,NULL,33,NULL,NULL,NULL,NULL,32,'Mostrador','2026-10-28 10:00:00','Domicilio'),(61,'2026-10-27 16:50:16','Cancelado',57,1,NULL,31,'me debe',NULL,'2026-10-27 17:29:37',NULL,31,'Web','2026-10-28 10:00:00','Domicilio'),(77,'2026-10-27 17:24:39','Entregado',74,1,12,42,NULL,'2026-10-27 17:28:19',NULL,'2026-10-27 17:54:57',31,'Web','2026-10-28 08:00:00','Domicilio'),(78,'2026-10-27 17:34:13','En Proceso',74,31,NULL,NULL,NULL,'2026-10-27 17:35:28',NULL,NULL,31,'Mostrador','2026-10-28 10:00:00','Recoger en local'),(79,'2026-10-27 17:44:12','Pendiente',74,31,NULL,NULL,NULL,NULL,NULL,NULL,31,'Mostrador','2026-10-28 10:00:00','Recoger en local'),(89,'2026-10-27 17:50:55','En Proceso',74,31,NULL,NULL,NULL,'2026-10-27 17:54:43',NULL,NULL,31,'Mostrador','2026-10-28 10:00:00','Recoger en local'),(90,'2026-10-27 17:51:51','Entregado',74,32,12,45,NULL,NULL,NULL,'2026-10-27 18:42:42',32,'Mostrador','2026-10-28 10:00:00','Domicilio'),(91,'2026-10-27 17:56:48','Pendiente',58,32,NULL,NULL,NULL,NULL,NULL,NULL,32,'Mostrador','2026-10-28 10:00:00','Recoger en local'),(108,'2026-10-27 18:39:42','Pendiente',57,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Web','2026-10-28 10:00:00','Recoger en local'),(109,'2026-10-27 18:47:17','Pendiente',74,1,12,45,NULL,NULL,NULL,NULL,NULL,'Web','2026-10-28 10:00:00','Domicilio'),(110,'2026-10-27 18:47:25','En Proceso',74,1,12,45,NULL,'2026-10-27 18:48:12',NULL,NULL,31,'Web','2026-10-28 10:00:00','Domicilio');
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` enum('Tortilla','Masa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tortilla',
  `precio_kilo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unidad` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (1,'Tortilla Mini / Botanera','Tortilla',20.00,'Kilo',1),(2,'Tortilla Taquera','Tortilla',22.00,'Kilo',1),(3,'Tortilla Copia Alta / Guisado','Tortilla',24.00,'Kilo',1),(4,'Tortilla Estándar / Mesa','Tortilla',25.00,'Kilo',1),(5,'Tortilla Grande / Enchilada','Tortilla',26.00,'Kilo',1),(6,'Tortilla Burrera / Quesadillera','Tortilla',28.00,'Kilo',1),(7,'Tortilla Tlayuda / Sobaquera','Tortilla',30.00,'Kilo',1),(8,'Masa con cal','Masa',18.00,'Kilo',1),(9,'Masa sin cal','Masa',20.00,'Kilo',1);
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_materiaprima`
--

DROP TABLE IF EXISTS `producto_materiaprima`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_materiaprima` (
  `id_producto` int NOT NULL,
  `id_materia_prima` int NOT NULL,
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
INSERT INTO `producto_materiaprima` VALUES (1,1,1.20),(1,4,0.05),(1,6,0.01),(1,7,0.10),(2,1,1.20),(2,4,0.05),(2,6,0.01),(2,7,0.10),(3,1,1.20),(3,4,0.05),(3,6,0.01),(4,1,1.20),(4,4,0.05),(5,1,1.20),(5,4,0.05),(6,1,1.20),(6,4,0.05),(7,2,1.20),(7,4,0.05);
/*!40000 ALTER TABLE `producto_materiaprima` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recuperacion_password`
--

DROP TABLE IF EXISTS `recuperacion_password`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recuperacion_password` (
  `id_recuperacion` int NOT NULL AUTO_INCREMENT,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT '0',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_recuperacion`),
  KEY `email` (`email`,`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recuperacion_password`
--

LOCK TABLES `recuperacion_password` WRITE;
/*!40000 ALTER TABLE `recuperacion_password` DISABLE KEYS */;
INSERT INTO `recuperacion_password` VALUES (1,'chimilemmanuel@gmail.com','115349','2026-10-13 06:38:04',1,'2026-10-13 06:28:04'),(2,'chimilemmanuel@gmail.com','215921','2026-10-13 06:38:10',1,'2026-10-13 06:28:10'),(3,'chimilemmanuel@gmail.com','967719','2026-10-13 06:38:21',1,'2026-10-13 06:28:21'),(4,'chimilemmanuel@gmail.com','246240','2026-10-13 06:38:33',1,'2026-10-13 06:28:33'),(5,'chimilemmanuel@gmail.com','125580','2026-10-13 06:38:55',1,'2026-10-13 06:28:55'),(6,'chimilemmanuel@gmail.com','712879','2026-10-13 06:39:53',1,'2026-10-13 06:29:53'),(7,'chimilemmanuel@gmail.com','953988','2026-10-13 06:39:56',1,'2026-10-13 06:29:56'),(8,'chimilemmanuel@gmail.com','263544','2026-10-13 06:40:42',1,'2026-10-13 06:30:42'),(9,'chimilemmanuel@gmail.com','974726','2026-10-13 06:40:45',1,'2026-10-13 06:30:45'),(10,'chimilemmanuel@gmail.com','986865','2026-10-13 06:40:49',1,'2026-10-13 06:30:49'),(11,'chimilemmanuel@gmail.com','309764','2026-10-13 06:41:16',1,'2026-10-13 06:31:16'),(12,'chimilemmanuel@gmail.com','952020','2026-10-13 06:41:49',1,'2026-10-13 06:31:49'),(13,'chimilemmanuel@gmail.com','719514','2026-10-13 06:42:49',1,'2026-10-13 06:32:49'),(14,'chimilemmanuel@gmail.com','624318','2026-10-13 06:45:25',1,'2026-10-13 06:35:25'),(15,'chimilemmanuel@gmail.com','222244','2026-10-13 06:45:30',1,'2026-10-13 06:35:30'),(16,'chimilemmanuel@gmail.com','038620','2026-10-13 06:45:37',1,'2026-10-13 06:35:37'),(17,'chimilemmanuel@gmail.com','596972','2026-10-13 06:45:54',1,'2026-10-13 06:35:54'),(18,'chimilemmanuel@gmail.com','646611','2026-10-13 06:45:58',1,'2026-10-13 06:35:58'),(19,'chimilemmanuel@gmail.com','890665','2026-10-13 06:46:09',0,'2026-10-13 06:36:09'),(20,'chimilemmanuel@gmail.com','702972','2026-10-13 06:57:31',1,'2026-10-13 06:47:31'),(21,'chimilemmanuel@gmail.com','069600','2026-10-13 06:57:32',1,'2026-10-13 06:47:32'),(22,'chimilemmanuel@gmail.com','076490','2026-10-13 07:00:17',1,'2026-10-13 06:50:17'),(23,'chimilemmanuel@gmail.com','487588','2026-10-13 07:06:48',1,'2026-10-13 06:56:48'),(24,'chimilemmanuel@gmail.com','132405','2026-10-13 07:08:31',0,'2026-10-13 06:58:31'),(25,'chimilemmanuel@gmail.com','185035','2026-10-13 14:30:36',1,'2026-10-13 14:20:36'),(26,'chimilemmanuel@gmail.com','884452','2026-10-13 18:07:37',1,'2026-10-13 17:57:37'),(27,'chimilemmanuel@gmail.com','441268','2026-10-19 18:09:24',1,'2026-10-19 17:59:24'),(28,'chimilemmanuel@gmail.com','433771','2026-10-19 18:10:08',1,'2026-10-19 18:00:08'),(29,'chimilemmanuel@gmail.com','865019','2026-10-19 18:10:44',0,'2026-10-19 18:00:44'),(30,'chimilemmanuel@gmail.com','223996','2026-10-26 18:08:28',1,'2026-10-26 17:58:28'),(31,'citlalihernandez3b10@gmail.com','385035','2026-10-26 18:20:28',1,'2026-10-26 18:10:28');
/*!40000 ALTER TABLE `recuperacion_password` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repartidor`
--

DROP TABLE IF EXISTS `repartidor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repartidor` (
  `id_repartidor` int NOT NULL AUTO_INCREMENT,
  `id_empleado` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('Disponible','No disponible') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Disponible',
  PRIMARY KEY (`id_repartidor`),
  UNIQUE KEY `uq_repartidor_empleado` (`id_empleado`),
  CONSTRAINT `fk_repartidor_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repartidor`
--

LOCK TABLES `repartidor` WRITE;
/*!40000 ALTER TABLE `repartidor` DISABLE KEYS */;
INSERT INTO `repartidor` VALUES (1,1,'Pedro','Ramírez','722-123-4567','Disponible'),(2,2,'Jorge','López','722-987-6543','Disponible'),(3,3,'Miguel','Torres','722-555-1234','No disponible'),(12,33,'Repartidor','Prueba','55-0000-0004','Disponible');
/*!40000 ALTER TABLE `repartidor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'Administrador'),(2,'Vendedor'),(3,'Cajero'),(4,'Repartidor');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta`
--

DROP TABLE IF EXISTS `venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `fecha_venta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `monto_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `id_cliente` int DEFAULT NULL,
  `id_empleado` int NOT NULL,
  PRIMARY KEY (`id_venta`),
  KEY `fk_venta_cliente` (`id_cliente`),
  KEY `fk_venta_empleado` (`id_empleado`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta`
--

LOCK TABLES `venta` WRITE;
/*!40000 ALTER TABLE `venta` DISABLE KEYS */;
INSERT INTO `venta` VALUES (1,'2026-10-12 09:44:34',79.00,NULL,3),(2,'2026-10-12 09:44:40',22.00,1,2),(3,'2026-10-12 09:44:48',72.00,2,2),(6,'2026-10-27 14:47:24',22.00,9,31),(7,'2026-10-27 16:44:15',300.00,3,32),(9,'2026-10-27 17:32:18',48.00,74,31),(10,'2026-10-27 17:35:28',158.00,74,31),(12,'2026-10-27 17:54:43',18.00,74,31),(15,'2026-10-27 18:48:12',20.00,74,31);
/*!40000 ALTER TABLE `venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `vista_catalogo_web`
--

DROP TABLE IF EXISTS `vista_catalogo_web`;
/*!50001 DROP VIEW IF EXISTS `vista_catalogo_web`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vista_catalogo_web` AS SELECT 
 1 AS `id_producto`,
 1 AS `producto`,
 1 AS `categoria`,
 1 AS `precio_kilo`,
 1 AS `unidad`,
 1 AS `disponible`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vista_clientes_registrados`
--

DROP TABLE IF EXISTS `vista_clientes_registrados`;
/*!50001 DROP VIEW IF EXISTS `vista_clientes_registrados`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vista_clientes_registrados` AS SELECT 
 1 AS `id_cliente`,
 1 AS `nombre`,
 1 AS `apellidos`,
 1 AS `razon_social`,
 1 AS `telefono`,
 1 AS `email`,
 1 AS `activo`,
 1 AS `total_direcciones`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vista_login_empleado`
--

DROP TABLE IF EXISTS `vista_login_empleado`;
/*!50001 DROP VIEW IF EXISTS `vista_login_empleado`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vista_login_empleado` AS SELECT 
 1 AS `id_empleado`,
 1 AS `nombre`,
 1 AS `apellidos`,
 1 AS `usuario`,
 1 AS `password_hash`,
 1 AS `email`,
 1 AS `activo`,
 1 AS `ultimo_login`,
 1 AS `id_rol`,
 1 AS `rol`,
 1 AS `modulo_principal`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vista_pedidos_cliente`
--

DROP TABLE IF EXISTS `vista_pedidos_cliente`;
/*!50001 DROP VIEW IF EXISTS `vista_pedidos_cliente`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vista_pedidos_cliente` AS SELECT 
 1 AS `id_pedido`,
 1 AS `fecha_pedido`,
 1 AS `estado`,
 1 AS `canal`,
 1 AS `tipo_entrega`,
 1 AS `fecha_hora_entrega_solicitada`,
 1 AS `cliente`,
 1 AS `telefono_cliente`,
 1 AS `repartidor`,
 1 AS `direccion_entrega`,
 1 AS `colonia`,
 1 AS `municipio`,
 1 AS `total_pedido`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vista_recuperacion_password`
--

DROP TABLE IF EXISTS `vista_recuperacion_password`;
/*!50001 DROP VIEW IF EXISTS `vista_recuperacion_password`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vista_recuperacion_password` AS SELECT 
 1 AS `id_recuperacion`,
 1 AS `email`,
 1 AS `codigo`,
 1 AS `expira_en`,
 1 AS `usado`,
 1 AS `creado_en`,
 1 AS `estado_codigo`,
 1 AS `minutos_restantes`,
 1 AS `id_empleado`,
 1 AS `id_cliente`,
 1 AS `tipo_usuario`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vista_usuarios_general`
--

DROP TABLE IF EXISTS `vista_usuarios_general`;
/*!50001 DROP VIEW IF EXISTS `vista_usuarios_general`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vista_usuarios_general` AS SELECT 
 1 AS `tipo_usuario`,
 1 AS `id_usuario`,
 1 AS `nombre_completo`,
 1 AS `usuario`,
 1 AS `email`,
 1 AS `password_hash`,
 1 AS `activo`,
 1 AS `rol`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'totilleria'
--

--
-- Dumping routines for database 'totilleria'
--

--
-- Current Database: `totilleria`
--

USE `totilleria`;

--
-- Final view structure for view `vista_catalogo_web`
--

/*!50001 DROP VIEW IF EXISTS `vista_catalogo_web`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_catalogo_web` AS select `p`.`id_producto` AS `id_producto`,`p`.`nombre` AS `producto`,`p`.`categoria` AS `categoria`,`p`.`precio_kilo` AS `precio_kilo`,`p`.`unidad` AS `unidad`,`p`.`disponible` AS `disponible` from `producto` `p` where (`p`.`disponible` = 1) order by `p`.`categoria`,`p`.`nombre` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vista_clientes_registrados`
--

/*!50001 DROP VIEW IF EXISTS `vista_clientes_registrados`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_clientes_registrados` AS select `c`.`id_cliente` AS `id_cliente`,`c`.`nombre` AS `nombre`,`c`.`apellidos` AS `apellidos`,`c`.`razon_social` AS `razon_social`,`c`.`telefono` AS `telefono`,`c`.`email` AS `email`,`c`.`activo` AS `activo`,(select count(0) from `direccion` `d` where (`d`.`id_cliente` = `c`.`id_cliente`)) AS `total_direcciones` from `cliente` `c` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vista_login_empleado`
--

/*!50001 DROP VIEW IF EXISTS `vista_login_empleado`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_login_empleado` AS select `e`.`id_empleado` AS `id_empleado`,`e`.`nombre` AS `nombre`,`e`.`apellidos` AS `apellidos`,`e`.`usuario` AS `usuario`,`e`.`password_hash` AS `password_hash`,`e`.`email` AS `email`,`e`.`activo` AS `activo`,`e`.`ultimo_login` AS `ultimo_login`,`r`.`id_rol` AS `id_rol`,`r`.`rol` AS `rol`,(case when (`r`.`rol` = 'Administrador') then 'Admin' when (`r`.`rol` = 'Vendedor') then 'Ventas' when (`r`.`rol` = 'Cajero') then 'Caja' else 'Basico' end) AS `modulo_principal` from (`empleado` `e` join `rol` `r` on((`e`.`id_rol` = `r`.`id_rol`))) where (`e`.`activo` = 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vista_pedidos_cliente`
--

/*!50001 DROP VIEW IF EXISTS `vista_pedidos_cliente`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_pedidos_cliente` AS select `p`.`id_pedido` AS `id_pedido`,`p`.`fecha_pedido` AS `fecha_pedido`,`p`.`estado` AS `estado`,`p`.`canal` AS `canal`,`p`.`tipo_entrega` AS `tipo_entrega`,`p`.`fecha_hora_entrega_solicitada` AS `fecha_hora_entrega_solicitada`,`c`.`nombre` AS `cliente`,`c`.`telefono` AS `telefono_cliente`,`r`.`nombre` AS `repartidor`,`d`.`calle` AS `direccion_entrega`,`d`.`colonia` AS `colonia`,`d`.`municipio` AS `municipio`,(select sum((`dp`.`cantidad` * `dp`.`precio_unitario`)) from `detalle_pedido` `dp` where (`dp`.`id_pedido` = `p`.`id_pedido`)) AS `total_pedido` from (((`pedido` `p` join `cliente` `c` on((`p`.`id_cliente` = `c`.`id_cliente`))) left join `repartidor` `r` on((`p`.`id_repartidor` = `r`.`id_repartidor`))) left join `direccion` `d` on((`p`.`id_direccion` = `d`.`id_direccion`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vista_recuperacion_password`
--

/*!50001 DROP VIEW IF EXISTS `vista_recuperacion_password`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_recuperacion_password` AS select `rp`.`id_recuperacion` AS `id_recuperacion`,`rp`.`email` AS `email`,`rp`.`codigo` AS `codigo`,`rp`.`expira_en` AS `expira_en`,`rp`.`usado` AS `usado`,`rp`.`creado_en` AS `creado_en`,(case when (`rp`.`usado` = 1) then 'Usado' when (`rp`.`expira_en` < now()) then 'Expirado' else 'Vigente' end) AS `estado_codigo`,timestampdiff(MINUTE,now(),`rp`.`expira_en`) AS `minutos_restantes`,(select `e`.`id_empleado` from `empleado` `e` where (`e`.`email` = `rp`.`email`) limit 1) AS `id_empleado`,(select `c`.`id_cliente` from `cliente` `c` where (`c`.`email` = `rp`.`email`) limit 1) AS `id_cliente`,(case when exists(select 1 from `empleado` `e` where (`e`.`email` = `rp`.`email`)) then 'Empleado' when exists(select 1 from `cliente` `c` where (`c`.`email` = `rp`.`email`)) then 'Cliente' else 'No registrado' end) AS `tipo_usuario` from `recuperacion_password` `rp` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vista_usuarios_general`
--

/*!50001 DROP VIEW IF EXISTS `vista_usuarios_general`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_usuarios_general` AS select 'Cliente' AS `tipo_usuario`,`c`.`id_cliente` AS `id_usuario`,concat(`c`.`nombre`,' ',`c`.`apellidos`) AS `nombre_completo`,`c`.`usuario` AS `usuario`,`c`.`email` AS `email`,`c`.`password_hash` AS `password_hash`,`c`.`activo` AS `activo`,NULL AS `rol` from `cliente` `c` where ((`c`.`usuario` is not null) or (`c`.`email` is not null)) union all select 'Empleado' AS `tipo_usuario`,`e`.`id_empleado` AS `id_usuario`,concat(`e`.`nombre`,' ',`e`.`apellidos`) AS `nombre_completo`,`e`.`usuario` AS `usuario`,`e`.`email` AS `email`,`e`.`password_hash` AS `password_hash`,`e`.`activo` AS `activo`,`r`.`rol` AS `rol` from (`empleado` `e` join `rol` `r` on((`e`.`id_rol` = `r`.`id_rol`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-27 18:51:01

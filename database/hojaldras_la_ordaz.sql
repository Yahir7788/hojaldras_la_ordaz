
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

/*!40000 DROP DATABASE IF EXISTS `hojaldras_la_ordaz`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `hojaldras_la_ordaz` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `hojaldras_la_ordaz`;
DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id_categoria` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perecedero` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = exige fecha de caducidad',
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Secos','Harinas, azúcar, levadura, aceites y productos no perecederos.',0),(2,'Refrigerados','Lácteos, carnes, embutidos y vegetales que requieren frío.',1),(3,'Congelados','Masas y productos almacenados bajo congelación.',1),(4,'Material de empaque','Cajas, bolsas, papel y desechables para el servicio.',0);
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
DROP TABLE IF EXISTS `entradas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entradas` (
  `id_entrada` int unsigned NOT NULL AUTO_INCREMENT,
  `id_insumo` int unsigned NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `unidad_medida` enum('kg','g','L','ml','pza','caja','paquete','bolsa','rollo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_caducidad` date DEFAULT NULL,
  `numero_lote` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario` int unsigned NOT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_entrada`),
  KEY `idx_entradas_insumo` (`id_insumo`),
  KEY `idx_entradas_fecha` (`fecha_ingreso`),
  KEY `idx_entradas_usuario` (`id_usuario`),
  KEY `idx_entradas_caducidad` (`fecha_caducidad`),
  CONSTRAINT `fk_entradas_insumos` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_entradas_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_entradas_cantidad` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `entradas` DISABLE KEYS */;
INSERT INTO `entradas` VALUES (2,2,25.000,'kg','2026-10-25',NULL,'LOTE-202610-001',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(3,3,8.000,'kg','2026-10-25',NULL,'LOTE-202610-002',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(4,4,2.500,'kg','2026-10-25','2026-11-19','LOTE-202610-003',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(5,5,3.000,'kg','2026-10-25',NULL,'LOTE-202610-004',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(6,6,12.000,'L','2026-10-25',NULL,'LOTE-202610-005',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(7,7,5.000,'kg','2026-10-25','2026-11-29','LOTE-202610-006',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(8,8,4.000,'kg','2026-10-25','2026-12-04','LOTE-202610-007',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(9,9,3.000,'kg','2026-10-25','2026-11-17','LOTE-202610-008',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(10,10,4.000,'kg','2026-10-25','2026-11-13','LOTE-202610-009',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(11,11,5.000,'kg','2026-10-25','2026-11-15','LOTE-202610-010',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(12,12,4.000,'kg','2026-10-25','2026-11-29','LOTE-202610-011',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(13,13,2.000,'kg','2026-10-25','2026-11-07','LOTE-202610-012',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(14,14,3.000,'L','2026-10-25','2026-12-24','LOTE-202610-013',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(15,15,6.000,'kg','2026-10-25','2026-11-11','LOTE-202610-014',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(16,16,12.000,'kg','2026-10-25','2027-01-08','LOTE-202610-015',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(17,17,8.000,'kg','2026-10-25','2026-12-09','LOTE-202610-016',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(18,18,100.000,'pza','2026-10-25',NULL,'LOTE-202610-017',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(19,19,250.000,'pza','2026-10-25',NULL,'LOTE-202610-018',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(20,20,200.000,'pza','2026-10-25',NULL,'LOTE-202610-019',1,'Compra inicial de materia prima.','2026-10-25 08:00:00'),(21,21,3.000,'kg','2026-10-25','2026-11-21','LOTE-202610-020',1,'Compra inicial de materia prima.','2026-10-25 08:00:00');
/*!40000 ALTER TABLE `entradas` ENABLE KEYS */;
DROP TABLE IF EXISTS `insumos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insumos` (
  `id_insumo` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_categoria` int unsigned NOT NULL,
  `unidad_medida` enum('kg','g','L','ml','pza','caja','paquete','bolsa','rollo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad_actual` decimal(12,3) NOT NULL DEFAULT '0.000',
  `stock_minimo` decimal(12,3) NOT NULL DEFAULT '0.000',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_insumo`),
  UNIQUE KEY `uq_insumos_nombre` (`nombre`),
  KEY `idx_insumos_categoria` (`id_categoria`),
  KEY `idx_insumos_activo` (`activo`),
  KEY `idx_insumos_stock` (`cantidad_actual`,`stock_minimo`),
  CONSTRAINT `fk_insumos_categorias` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_insumos_cantidad` CHECK ((`cantidad_actual` >= 0)),
  CONSTRAINT `chk_insumos_minimo` CHECK ((`stock_minimo` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `insumos` DISABLE KEYS */;
INSERT INTO `insumos` VALUES (2,'Harina','Harina de trigo para hojaldres y pizzas.',1,'kg',18.000,10.000,'2026-10-25 07:30:00',1),(3,'Azúcar','Azúcar refinada para masas y rellenos.',1,'kg',4.500,5.000,'2026-10-25 07:30:00',1),(4,'Levadura','Levadura fresca para fermentación.',2,'kg',1.200,1.000,'2026-10-25 07:30:00',1),(5,'Sal','Sal fina de mesa.',1,'kg',2.500,1.000,'2026-10-25 07:30:00',1),(6,'Aceite','Aceite vegetal para freído y horneado.',1,'L',8.000,5.000,'2026-10-25 07:30:00',1),(7,'Mantequilla','Mantequilla para hojaldre.',2,'kg',2.800,2.000,'2026-10-25 07:30:00',1),(8,'Margarina','Margarina vegetal para repostería.',2,'kg',1.500,2.000,'2026-10-25 07:30:00',1),(9,'Jamón','Jamón de pavo para volovanes.',2,'kg',2.200,1.000,'2026-10-25 07:30:00',1),(10,'Queso','Queso mozzarella para pizzas y volovanes.',2,'kg',3.200,2.000,'2026-10-25 07:30:00',1),(11,'Pollo','Pollo deshebrado para rellenos.',2,'kg',4.000,2.000,'2026-10-25 07:30:00',1),(12,'Carne al pastor','Carne al pastor para pizzas.',2,'kg',3.000,2.000,'2026-10-25 07:30:00',1),(13,'Atún','Atún en agua para volovanes.',2,'kg',1.500,1.000,'2026-10-25 07:30:00',1),(14,'Salsas','Salsa de tomate y aderezos.',2,'L',2.400,1.000,'2026-10-25 07:30:00',1),(15,'Vegetales','Cebolla, tomate y chile para preparaciones.',2,'kg',4.500,2.000,'2026-10-25 07:30:00',1),(16,'Masa de hojaldre','Masa de hojaldre congelada.',3,'kg',7.000,4.000,'2026-10-25 07:30:00',1),(17,'Masa de volován','Masa de volován congelada.',3,'kg',2.500,3.000,'2026-10-25 07:30:00',1),(18,'Cajas','Cajas de cartón para empaque.',4,'pza',75.000,30.000,'2026-10-25 07:30:00',1),(19,'Bolsas','Bolsas de papel para alimentos.',4,'pza',180.000,50.000,'2026-10-25 07:30:00',1),(20,'Papel para alimentos','Papel encerado para envolver.',4,'pza',0.000,40.000,'2026-10-25 07:30:00',1),(21,'Embutidos','Salchicha y embutidos para volovanes.',2,'kg',2.300,1.000,'2026-10-25 07:30:00',1);
/*!40000 ALTER TABLE `insumos` ENABLE KEYS */;
DROP TABLE IF EXISTS `lotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotes` (
  `id_lote` int unsigned NOT NULL AUTO_INCREMENT,
  `id_insumo` int unsigned NOT NULL,
  `id_entrada` int unsigned DEFAULT NULL COMMENT 'NULL cuando proviene de un ajuste',
  `numero_lote` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad_inicial` decimal(12,3) NOT NULL,
  `cantidad_actual` decimal(12,3) NOT NULL,
  `fecha_caducidad` date DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lote`),
  UNIQUE KEY `uq_lotes_entrada` (`id_entrada`),
  KEY `idx_lotes_insumo` (`id_insumo`),
  KEY `idx_lotes_caducidad` (`fecha_caducidad`,`cantidad_actual`),
  CONSTRAINT `fk_lotes_entradas` FOREIGN KEY (`id_entrada`) REFERENCES `entradas` (`id_entrada`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_lotes_insumos` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_lotes_actual` CHECK ((`cantidad_actual` >= 0)),
  CONSTRAINT `chk_lotes_inicial` CHECK ((`cantidad_inicial` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `lotes` DISABLE KEYS */;
INSERT INTO `lotes` VALUES (1,2,2,'LOTE-202610-001',25.000,18.000,NULL,'2026-10-25 08:00:00'),(2,3,3,'LOTE-202610-002',8.000,4.500,NULL,'2026-10-25 08:00:00'),(3,4,4,'LOTE-202610-003',2.500,1.200,'2026-11-19','2026-10-25 08:00:00'),(4,5,5,'LOTE-202610-004',3.000,2.500,NULL,'2026-10-25 08:00:00'),(5,6,6,'LOTE-202610-005',12.000,8.000,NULL,'2026-10-25 08:00:00'),(6,7,7,'LOTE-202610-006',5.000,2.800,'2026-11-29','2026-10-25 08:00:00'),(7,8,8,'LOTE-202610-007',4.000,1.500,'2026-12-04','2026-10-25 08:00:00'),(8,9,9,'LOTE-202610-008',3.000,2.200,'2026-11-17','2026-10-25 08:00:00'),(9,10,10,'LOTE-202610-009',4.000,3.200,'2026-11-13','2026-10-25 08:00:00'),(10,11,11,'LOTE-202610-010',5.000,4.000,'2026-11-15','2026-10-25 08:00:00'),(11,12,12,'LOTE-202610-011',4.000,3.000,'2026-11-29','2026-10-25 08:00:00'),(12,13,13,'LOTE-202610-012',2.000,1.500,'2026-11-07','2026-10-25 08:00:00'),(13,14,14,'LOTE-202610-013',3.000,2.400,'2026-12-24','2026-10-25 08:00:00'),(14,15,15,'LOTE-202610-014',6.000,4.500,'2026-11-11','2026-10-25 08:00:00'),(15,16,16,'LOTE-202610-015',12.000,7.000,'2027-01-08','2026-10-25 08:00:00'),(16,17,17,'LOTE-202610-016',8.000,2.500,'2026-12-09','2026-10-25 08:00:00'),(17,18,18,'LOTE-202610-017',100.000,75.000,NULL,'2026-10-25 08:00:00'),(18,19,19,'LOTE-202610-018',250.000,180.000,NULL,'2026-10-25 08:00:00'),(19,20,20,'LOTE-202610-019',200.000,0.000,NULL,'2026-10-25 08:00:00'),(20,21,21,'LOTE-202610-020',3.000,2.300,'2026-11-21','2026-10-25 08:00:00');
/*!40000 ALTER TABLE `lotes` ENABLE KEYS */;
DROP TABLE IF EXISTS `mermas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mermas` (
  `id_merma` int unsigned NOT NULL AUTO_INCREMENT,
  `id_insumo` int unsigned NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `motivo` enum('Caducidad','Deterioro','Desperdicio en producción','Producto dañado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario` int unsigned NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_merma`),
  KEY `idx_mermas_insumo` (`id_insumo`),
  KEY `idx_mermas_fecha` (`fecha`),
  KEY `idx_mermas_motivo` (`motivo`),
  KEY `idx_mermas_usuario` (`id_usuario`),
  CONSTRAINT `fk_mermas_insumos` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mermas_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_mermas_cantidad` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `mermas` DISABLE KEYS */;
INSERT INTO `mermas` VALUES (1,2,0.500,'Desperdicio en producción','2026-11-06','Restos de masa no aprovechados.',2,'2026-11-06 17:30:00'),(2,3,0.200,'Producto dañado','2026-11-06','Bolsa con humedad.',2,'2026-11-06 17:30:00'),(3,6,0.300,'Desperdicio en producción','2026-11-06','Aceite desechado tras la jornada.',2,'2026-11-06 17:30:00'),(4,13,0.500,'Caducidad','2026-11-06','Lote vencido detectado en revisión.',2,'2026-11-06 17:30:00'),(5,15,0.300,'Deterioro','2026-11-06','Vegetales marchitos.',2,'2026-11-06 17:30:00'),(6,16,0.400,'Deterioro','2026-11-06','Masa expuesta a descongelación.',2,'2026-11-06 17:30:00'),(7,20,12.000,'Producto dañado','2026-11-06','Papel manchado por humedad.',2,'2026-11-06 17:30:00');
/*!40000 ALTER TABLE `mermas` ENABLE KEYS */;
DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario` (
  `id_movimiento` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_insumo` int unsigned NOT NULL,
  `tipo` enum('Entrada','Salida','Merma','Ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `stock_anterior` decimal(12,3) NOT NULL,
  `stock_nuevo` decimal(12,3) NOT NULL,
  `id_referencia` int unsigned DEFAULT NULL,
  `tabla_referencia` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_usuario` int unsigned NOT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_mov_insumo` (`id_insumo`),
  KEY `idx_mov_tipo` (`tipo`),
  KEY `idx_mov_fecha` (`fecha_movimiento`),
  KEY `idx_mov_usuario` (`id_usuario`),
  CONSTRAINT `fk_mov_insumos` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_mov_cantidad` CHECK ((`cantidad` > 0)),
  CONSTRAINT `chk_mov_stock` CHECK (((`stock_anterior` >= 0) and (`stock_nuevo` >= 0)))
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;
INSERT INTO `movimientos_inventario` VALUES (1,2,'Entrada',25.000,0.000,25.000,2,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Harina.'),(2,2,'Salida',3.250,25.000,21.750,1,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - panadería.'),(3,2,'Salida',2.166,21.750,19.584,2,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - panadería.'),(4,2,'Merma',0.500,19.584,19.084,1,'mermas',2,'2026-11-06 17:30:00','Merma por desperdicio en producción.'),(5,2,'Salida',1.084,19.084,18.000,3,'salidas',2,'2026-11-07 16:05:00','Salida a Cocina - panadería.'),(6,3,'Entrada',8.000,0.000,8.000,3,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Azúcar.'),(7,3,'Salida',1.980,8.000,6.020,4,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - panadería.'),(8,3,'Salida',1.320,6.020,4.700,5,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - panadería.'),(9,3,'Merma',0.200,4.700,4.500,2,'mermas',2,'2026-11-06 17:30:00','Merma por producto dañado.'),(10,4,'Entrada',2.500,0.000,2.500,4,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Levadura.'),(11,4,'Salida',0.780,2.500,1.720,6,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(12,4,'Salida',0.520,1.720,1.200,7,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - preparación.'),(13,5,'Entrada',3.000,0.000,3.000,5,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Sal.'),(14,5,'Salida',0.500,3.000,2.500,8,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - panadería.'),(15,6,'Entrada',12.000,0.000,12.000,6,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Aceite.'),(16,6,'Salida',1.850,12.000,10.150,9,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - panadería.'),(17,6,'Salida',1.233,10.150,8.917,10,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - panadería.'),(18,6,'Merma',0.300,8.917,8.617,3,'mermas',2,'2026-11-06 17:30:00','Merma por desperdicio en producción.'),(19,6,'Salida',0.617,8.617,8.000,11,'salidas',2,'2026-11-07 16:05:00','Salida a Cocina - panadería.'),(20,7,'Entrada',5.000,0.000,5.000,7,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Mantequilla.'),(21,7,'Salida',1.320,5.000,3.680,12,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(22,7,'Salida',0.880,3.680,2.800,13,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - preparación.'),(23,8,'Entrada',4.000,0.000,4.000,8,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Margarina.'),(24,8,'Salida',1.250,4.000,2.750,14,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(25,8,'Salida',0.833,2.750,1.917,15,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - preparación.'),(26,8,'Salida',0.417,1.917,1.500,16,'salidas',2,'2026-11-07 16:05:00','Salida a Cocina - preparación.'),(27,9,'Entrada',3.000,0.000,3.000,9,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Jamón.'),(28,9,'Salida',0.480,3.000,2.520,17,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(29,9,'Salida',0.320,2.520,2.200,18,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - preparación.'),(30,10,'Entrada',4.000,0.000,4.000,10,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Queso.'),(31,10,'Salida',0.800,4.000,3.200,19,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(32,11,'Entrada',5.000,0.000,5.000,11,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Pollo.'),(33,11,'Salida',0.600,5.000,4.400,20,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(34,11,'Salida',0.400,4.400,4.000,21,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - preparación.'),(35,12,'Entrada',4.000,0.000,4.000,12,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Carne al pastor.'),(36,12,'Salida',0.600,4.000,3.400,22,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(37,12,'Salida',0.400,3.400,3.000,23,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - preparación.'),(38,13,'Entrada',2.000,0.000,2.000,13,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Atún.'),(39,13,'Merma',0.500,2.000,1.500,4,'mermas',2,'2026-11-06 17:30:00','Merma por caducidad.'),(40,14,'Entrada',3.000,0.000,3.000,14,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Salsas.'),(41,14,'Salida',0.600,3.000,2.400,24,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(42,15,'Entrada',6.000,0.000,6.000,15,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Vegetales.'),(43,15,'Salida',0.720,6.000,5.280,25,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(44,15,'Salida',0.480,5.280,4.800,26,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - preparación.'),(45,15,'Merma',0.300,4.800,4.500,5,'mermas',2,'2026-11-06 17:30:00','Merma por deterioro.'),(46,16,'Entrada',12.000,0.000,12.000,16,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Masa de hojaldre.'),(47,16,'Salida',2.300,12.000,9.700,27,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - producción.'),(48,16,'Salida',1.533,9.700,8.167,28,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - producción.'),(49,16,'Merma',0.400,8.167,7.767,6,'mermas',2,'2026-11-06 17:30:00','Merma por deterioro.'),(50,16,'Salida',0.767,7.767,7.000,29,'salidas',2,'2026-11-07 16:05:00','Salida a Cocina - producción.'),(51,17,'Entrada',8.000,0.000,8.000,17,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Masa de volován.'),(52,17,'Salida',2.750,8.000,5.250,30,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - producción.'),(53,17,'Salida',1.833,5.250,3.417,31,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - producción.'),(54,17,'Salida',0.917,3.417,2.500,32,'salidas',2,'2026-11-07 16:05:00','Salida a Cocina - producción.'),(55,18,'Entrada',100.000,0.000,100.000,18,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Cajas.'),(56,18,'Salida',15.000,100.000,85.000,33,'salidas',2,'2026-11-03 10:15:00','Salida a Mostrador.'),(57,18,'Salida',10.000,85.000,75.000,34,'salidas',2,'2026-11-05 12:40:00','Salida a Mostrador.'),(58,19,'Entrada',250.000,0.000,250.000,19,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Bolsas.'),(59,19,'Salida',35.000,250.000,215.000,35,'salidas',2,'2026-11-03 10:15:00','Salida a Mostrador.'),(60,19,'Salida',23.333,215.000,191.667,36,'salidas',2,'2026-11-05 12:40:00','Salida a Mostrador.'),(61,19,'Salida',11.667,191.667,180.000,37,'salidas',2,'2026-11-07 16:05:00','Salida a Mostrador.'),(62,20,'Entrada',200.000,0.000,200.000,20,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Papel para alimentos.'),(63,20,'Salida',94.000,200.000,106.000,38,'salidas',2,'2026-11-03 10:15:00','Salida a Mostrador.'),(64,20,'Salida',62.666,106.000,43.334,39,'salidas',2,'2026-11-05 12:40:00','Salida a Mostrador.'),(65,20,'Merma',12.000,43.334,31.334,7,'mermas',2,'2026-11-06 17:30:00','Merma por producto dañado.'),(66,20,'Salida',31.334,31.334,0.000,40,'salidas',2,'2026-11-07 16:05:00','Salida a Mostrador.'),(67,21,'Entrada',3.000,0.000,3.000,21,'entradas',1,'2026-10-25 08:00:00','Entrada inicial de Embutidos.'),(68,21,'Salida',0.420,3.000,2.580,41,'salidas',2,'2026-11-03 10:15:00','Salida a Cocina - preparación.'),(69,21,'Salida',0.280,2.580,2.300,42,'salidas',2,'2026-11-05 12:40:00','Salida a Cocina - preparación.');
/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;
DROP TABLE IF EXISTS `movimientos_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_producto` (
  `id_movimiento_producto` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_producto` int unsigned NOT NULL,
  `tipo` enum('Producción','Venta','Ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL,
  `stock_anterior` int NOT NULL,
  `stock_nuevo` int NOT NULL,
  `id_usuario` int unsigned NOT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_movimiento_producto`),
  KEY `idx_movprod_producto` (`id_producto`),
  KEY `idx_movprod_fecha` (`fecha_movimiento`),
  KEY `fk_movprod_usuarios` (`id_usuario`),
  CONSTRAINT `fk_movprod_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_movprod_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_movprod_cantidad` CHECK ((`cantidad` > 0)),
  CONSTRAINT `chk_movprod_stock` CHECK (((`stock_anterior` >= 0) and (`stock_nuevo` >= 0)))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `movimientos_producto` DISABLE KEYS */;
INSERT INTO `movimientos_producto` VALUES (1,1,'Producción',36,0,36,2,'2026-11-05 09:00:00','Producción de la jornada.'),(2,1,'Venta',12,36,24,3,'2026-11-08 13:15:00','Ventas en mostrador.'),(3,2,'Producción',24,0,24,2,'2026-11-05 09:00:00','Producción de la jornada.'),(4,2,'Venta',6,24,18,3,'2026-11-08 13:15:00','Ventas en mostrador.'),(5,3,'Producción',15,0,15,2,'2026-11-06 09:00:00','Producción de la jornada.'),(6,3,'Venta',6,15,9,3,'2026-11-08 13:15:00','Ventas en mostrador.');
/*!40000 ALTER TABLE `movimientos_producto` ENABLE KEYS */;
DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id_producto` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_actual` int NOT NULL DEFAULT '0',
  `stock_minimo` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `uq_productos_nombre` (`nombre`),
  CONSTRAINT `chk_productos_minimo` CHECK ((`stock_minimo` >= 0)),
  CONSTRAINT `chk_productos_stock` CHECK ((`stock_actual` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'Hojaldre','Hojaldre artesanal recién horneado, disponible en mostrador.',25.00,24,5,1,'2026-11-09 17:14:48'),(2,'Volován','Volován de hojaldre relleno, horneado del día.',30.00,18,6,1,'2026-11-09 17:14:48'),(3,'Pizza','Pizza artesanal preparada al momento.',85.00,9,4,1,'2026-11-09 17:14:48');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `uq_roles_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'gerente','Acceso completo al sistema, inventario, reportes y usuarios.'),(2,'cocinero','Consulta existencias, registra salidas y mermas de cocina.'),(3,'ventas','Consulta y controla el producto terminado disponible.');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
DROP TABLE IF EXISTS `salidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salidas` (
  `id_salida` int unsigned NOT NULL AUTO_INCREMENT,
  `id_insumo` int unsigned NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `fecha` date NOT NULL,
  `destino` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_usuario` int unsigned NOT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_salida`),
  KEY `idx_salidas_insumo` (`id_insumo`),
  KEY `idx_salidas_fecha` (`fecha`),
  KEY `idx_salidas_usuario` (`id_usuario`),
  CONSTRAINT `fk_salidas_insumos` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_salidas_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_salidas_cantidad` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `salidas` DISABLE KEYS */;
INSERT INTO `salidas` VALUES (1,2,3.250,'2026-11-03','Cocina - panadería',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(2,2,2.166,'2026-11-05','Cocina - panadería',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(3,2,1.084,'2026-11-07','Cocina - panadería',2,'Consumo para producción del día.','2026-11-07 16:05:00'),(4,3,1.980,'2026-11-03','Cocina - panadería',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(5,3,1.320,'2026-11-05','Cocina - panadería',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(6,4,0.780,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(7,4,0.520,'2026-11-05','Cocina - preparación',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(8,5,0.500,'2026-11-03','Cocina - panadería',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(9,6,1.850,'2026-11-03','Cocina - panadería',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(10,6,1.233,'2026-11-05','Cocina - panadería',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(11,6,0.617,'2026-11-07','Cocina - panadería',2,'Consumo para producción del día.','2026-11-07 16:05:00'),(12,7,1.320,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(13,7,0.880,'2026-11-05','Cocina - preparación',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(14,8,1.250,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(15,8,0.833,'2026-11-05','Cocina - preparación',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(16,8,0.417,'2026-11-07','Cocina - preparación',2,'Consumo para producción del día.','2026-11-07 16:05:00'),(17,9,0.480,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(18,9,0.320,'2026-11-05','Cocina - preparación',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(19,10,0.800,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(20,11,0.600,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(21,11,0.400,'2026-11-05','Cocina - preparación',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(22,12,0.600,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(23,12,0.400,'2026-11-05','Cocina - preparación',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(24,14,0.600,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(25,15,0.720,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(26,15,0.480,'2026-11-05','Cocina - preparación',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(27,16,2.300,'2026-11-03','Cocina - producción',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(28,16,1.533,'2026-11-05','Cocina - producción',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(29,16,0.767,'2026-11-07','Cocina - producción',2,'Consumo para producción del día.','2026-11-07 16:05:00'),(30,17,2.750,'2026-11-03','Cocina - producción',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(31,17,1.833,'2026-11-05','Cocina - producción',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(32,17,0.917,'2026-11-07','Cocina - producción',2,'Consumo para producción del día.','2026-11-07 16:05:00'),(33,18,15.000,'2026-11-03','Mostrador',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(34,18,10.000,'2026-11-05','Mostrador',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(35,19,35.000,'2026-11-03','Mostrador',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(36,19,23.333,'2026-11-05','Mostrador',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(37,19,11.667,'2026-11-07','Mostrador',2,'Consumo para producción del día.','2026-11-07 16:05:00'),(38,20,94.000,'2026-11-03','Mostrador',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(39,20,62.666,'2026-11-05','Mostrador',2,'Consumo para producción del día.','2026-11-05 12:40:00'),(40,20,31.334,'2026-11-07','Mostrador',2,'Consumo para producción del día.','2026-11-07 16:05:00'),(41,21,0.420,'2026-11-03','Cocina - preparación',2,'Consumo para producción del día.','2026-11-03 10:15:00'),(42,21,0.280,'2026-11-05','Cocina - preparación',2,'Consumo para producción del día.','2026-11-05 12:40:00');
/*!40000 ALTER TABLE `salidas` ENABLE KEYS */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contrasena` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash generado con password_hash()',
  `id_rol` int unsigned NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` datetime DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`),
  UNIQUE KEY `uq_usuarios_correo` (`correo`),
  KEY `idx_usuarios_rol` (`id_rol`),
  KEY `idx_usuarios_activo` (`activo`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'María Ordaz','gerente','gerente@laordaz.local','$2y$10$byoeJJtfM4tgClbzwZj5geMdYtbgzKCXChyTV4D9XShckoQQB2nem',1,1,'2026-11-09 17:14:48',NULL),(2,'Juan Pérez','cocinero','cocina@laordaz.local','$2y$10$g3mvRnoH8KRL3dYgctH4weanqitXIYWjCUijkKO6wdk.iB6a7lAs.',2,1,'2026-11-09 17:14:48',NULL),(3,'Ana López','ventas','ventas@laordaz.local','$2y$10$Iun65yX/MpwzjWGO866aLeIXZ1wQDQuP.wfundov1tw8rbyz8EbK.',3,1,'2026-11-09 17:14:48',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


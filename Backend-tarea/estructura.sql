-- MariaDB dump 10.17  Distrib 10.4.8-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tareas_restaurant
-- ------------------------------------------------------
-- Server version	10.4.8-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `adjuntos_tarea`
--

DROP TABLE IF EXISTS `adjuntos_tarea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adjuntos_tarea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tarea` int(11) NOT NULL,
  `id_subido_por` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL COMMENT 'Path relativo desde /api/uploads/adjuntos/',
  `tipo_archivo` varchar(50) DEFAULT NULL COMMENT 'MIME Type: image/jpeg, application/pdf',
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_subido_por` (`id_subido_por`),
  KEY `idx_tarea` (`id_tarea`),
  CONSTRAINT `adjuntos_tarea_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `adjuntos_tarea_ibfk_2` FOREIGN KEY (`id_subido_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auditoria_auth`
--

DROP TABLE IF EXISTS `auditoria_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL COMMENT 'NULL si es intento fallido',
  `evento` enum('LOGIN_EXITOSO','LOGIN_FALLIDO','BLOQUEO_CUENTA','LOGOUT') DEFAULT NULL,
  `detalles` varchar(255) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_evento` (`id_usuario`,`evento`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COMMENT='Registro de eventos de seguridad';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `extensiones_tarea`
--

DROP TABLE IF EXISTS `extensiones_tarea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `extensiones_tarea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tarea` int(11) NOT NULL,
  `id_autorizador` int(11) NOT NULL COMMENT 'Jefe que autorizó la extensión',
  `fecha_fin_anterior` datetime NOT NULL,
  `fecha_fin_nueva` datetime NOT NULL,
  `es_justificada` tinyint(1) NOT NULL COMMENT '1=KPI Verde (exonerado), 0=KPI Rojo (ineficiente)',
  `razon` varchar(255) DEFAULT NULL,
  `numero_extension` tinyint(4) NOT NULL COMMENT '1, 2 o 3 (máximo)',
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_autorizador` (`id_autorizador`),
  KEY `idx_tarea` (`id_tarea`),
  CONSTRAINT `extensiones_tarea_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `extensiones_tarea_ibfk_2` FOREIGN KEY (`id_autorizador`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de todas las extensiones con flag justificación';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historial_tarea`
--

DROP TABLE IF EXISTS `historial_tarea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_tarea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tarea` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `accion` enum('CREAR','EDITAR','COMPLETAR','SOLICITAR_VAL','VALIDAR','RECHAZAR','EXTENDER','FINALIZAR_VENCIDA','ELIMINAR') DEFAULT NULL,
  `metadatos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Detalles adicionales: {"campo":"titulo", "antes":"A", "despues":"B"}' CHECK (json_valid(`metadatos`)),
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tarea_accion` (`id_tarea`,`accion`),
  KEY `idx_usuario` (`id_usuario`),
  CONSTRAINT `historial_tarea_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_tarea_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Trazabilidad completa de cambios';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intentos_login`
--

DROP TABLE IF EXISTS `intentos_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intentos_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `ip_origen` varchar(45) NOT NULL COMMENT 'Soporta IPv4 e IPv6',
  `fecha_intento` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_fecha` (`email`,`fecha_intento`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='Prevención de fuerza bruta (3 intentos/2min)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subtareas`
--

DROP TABLE IF EXISTS `subtareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subtareas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tarea` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `completada` tinyint(1) DEFAULT 0,
  `id_creador` int(11) NOT NULL COMMENT 'Para saber si fue corrección del jefe tras rechazo',
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_creador` (`id_creador`),
  KEY `idx_tarea_completada` (`id_tarea`,`completada`),
  CONSTRAINT `subtareas_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subtareas_ibfk_2` FOREIGN KEY (`id_creador`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sucursales`
--

DROP TABLE IF EXISTS `sucursales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `zona_horaria` varchar(50) NOT NULL COMMENT 'Ej: America/Lima - VITAL para cálculo de vencimientos',
  `estado` enum('ACTIVA','INACTIVA') DEFAULT 'ACTIVA',
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='Multi-inquilino: Cada sucursal es un contexto aislado';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tareas`
--

DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tareas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sucursal` int(11) NOT NULL,
  `id_creador` int(11) NOT NULL COMMENT 'Quién creó la tarea',
  `id_asignado` int(11) DEFAULT NULL COMMENT 'NULL si está en Bolsa (Sin Asignar)',
  `id_validador` int(11) DEFAULT NULL COMMENT 'Quién aprobó la tarea (Para KPIs de liderazgo)',
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `prioridad` enum('BAJA','MEDIA','ALTA','CRITICA') DEFAULT 'MEDIA',
  `categoria_asignacion` enum('ESPECIFICA','BOLSA_COLABORADOR','BOLSA_GERENTE','BOLSA_AMBOS') NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin_original` datetime NOT NULL COMMENT 'Fecha prometida inicial',
  `fecha_fin_actual` datetime NOT NULL COMMENT 'Fecha real tras extensiones',
  `fecha_validacion` datetime DEFAULT NULL COMMENT 'Timestamp de aprobación final',
  `estado_ejecucion` enum('PROGRAMADA','EN_PROGRESO','COMPLETADA','VENCIDA') DEFAULT 'PROGRAMADA',
  `estado_ciclo_vida` varchar(20) NOT NULL DEFAULT 'ACTIVA',
  `estado_validacion` enum('SIN_VALIDAR','POR_VALIDAR','VALIDADA') DEFAULT 'SIN_VALIDAR',
  `contador_extensiones` tinyint(3) unsigned DEFAULT 0 COMMENT 'Máximo 3',
  `contador_rechazos` tinyint(3) unsigned DEFAULT 0 COMMENT 'Para KPI de Calidad',
  `es_iniciativa` tinyint(1) DEFAULT 0 COMMENT '1 si el usuario la creó para sí mismo',
  `eliminado_en` datetime DEFAULT NULL COMMENT 'Soft Delete para iniciativas abandonadas',
  `creado_en` datetime DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_asignado_estado` (`id_asignado`,`estado_ciclo_vida`),
  KEY `idx_sucursal_estado` (`id_sucursal`,`estado_ciclo_vida`),
  KEY `idx_creador` (`id_creador`),
  KEY `idx_validador` (`id_validador`),
  KEY `idx_fecha_fin` (`fecha_fin_actual`),
  KEY `idx_es_iniciativa` (`es_iniciativa`),
  CONSTRAINT `tareas_ibfk_1` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `tareas_ibfk_2` FOREIGN KEY (`id_creador`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `tareas_ibfk_3` FOREIGN KEY (`id_asignado`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tareas_ibfk_4` FOREIGN KEY (`id_validador`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='Tabla central con estados y contadores para BI';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sucursal` int(11) DEFAULT NULL COMMENT 'NULL solo para el CEO (alcance global)',
  `id_gerente_directo` int(11) DEFAULT NULL COMMENT 'Para jerarquía Gerente→Colaboradores',
  `rol` enum('CEO','GG','GERENTE','COLABORADOR') NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_sucursal_rol` (`id_sucursal`,`rol`),
  KEY `idx_gerente_directo` (`id_gerente_directo`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_gerente_directo`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='4 roles: CEO > GG > GERENTE > COLABORADOR';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-17  9:34:46

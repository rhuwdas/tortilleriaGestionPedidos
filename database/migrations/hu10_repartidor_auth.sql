-- Migración HU10: autenticación de repartidores y fecha de entrega.
-- Ejecutar una sola vez sobre una base de datos ya instalada.
-- Es segura de reejecutar: solo agrega los elementos que falten.
--
-- Las cuentas de repartidor usan la tabla empleado y el rol "Repartidor".
-- La migración NO crea usuarios ni contraseñas predeterminadas.

SET @esquema = DATABASE();

-- 1. Rol Repartidor con la capitalización usada por el sistema.
INSERT INTO rol (rol)
SELECT 'Repartidor'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE LOWER(rol) = 'repartidor');

UPDATE rol
   SET rol = 'Repartidor'
 WHERE LOWER(rol) = 'repartidor';

-- 2. Vínculo único entre repartidor y su cuenta de empleado.
SET @sql = IF(
    (SELECT COUNT(*)
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @esquema
        AND TABLE_NAME = 'repartidor'
        AND COLUMN_NAME = 'id_empleado') = 0,
    'ALTER TABLE `repartidor` ADD COLUMN `id_empleado` INT NULL AFTER `id_repartidor`',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*)
       FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @esquema
        AND TABLE_NAME = 'repartidor'
        AND INDEX_NAME = 'uq_repartidor_empleado') = 0,
    'ALTER TABLE `repartidor` ADD UNIQUE KEY `uq_repartidor_empleado` (`id_empleado`)',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*)
       FROM information_schema.KEY_COLUMN_USAGE
      WHERE TABLE_SCHEMA = @esquema
        AND TABLE_NAME = 'repartidor'
        AND COLUMN_NAME = 'id_empleado'
        AND REFERENCED_TABLE_NAME = 'empleado') = 0,
    'ALTER TABLE `repartidor` ADD CONSTRAINT `fk_repartidor_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE SET NULL ON UPDATE CASCADE',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Fecha en la que se confirma una entrega.
SET @sql = IF(
    (SELECT COUNT(*)
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @esquema
        AND TABLE_NAME = 'pedido'
        AND COLUMN_NAME = 'fecha_entrega') = 0,
    'ALTER TABLE `pedido` ADD COLUMN `fecha_entrega` DATETIME NULL AFTER `fecha_aceptacion`',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Vista unificada usada por el login para clientes y empleados.
CREATE OR REPLACE VIEW `vista_usuarios_general` AS
SELECT 'Cliente' AS `tipo_usuario`,
       `c`.`id_cliente` AS `id_usuario`,
       CONCAT(`c`.`nombre`, ' ', `c`.`apellidos`) AS `nombre_completo`,
       `c`.`usuario` AS `usuario`,
       `c`.`email` AS `email`,
       `c`.`password_hash` AS `password_hash`,
       `c`.`activo` AS `activo`,
       NULL AS `rol`
  FROM `cliente` AS `c`
 WHERE `c`.`usuario` IS NOT NULL OR `c`.`email` IS NOT NULL
UNION ALL
SELECT 'Empleado' AS `tipo_usuario`,
       `e`.`id_empleado` AS `id_usuario`,
       CONCAT(`e`.`nombre`, ' ', `e`.`apellidos`) AS `nombre_completo`,
       `e`.`usuario` AS `usuario`,
       `e`.`email` AS `email`,
       `e`.`password_hash` AS `password_hash`,
       `e`.`activo` AS `activo`,
       `r`.`rol` AS `rol`
  FROM `empleado` AS `e`
  JOIN `rol` AS `r` ON `e`.`id_rol` = `r`.`id_rol`;

-- Después de ejecutar esta migración:
-- 1. Cree la cuenta del empleado con rol Repartidor desde el flujo administrativo
--    o con un INSERT que incluya un password_hash generado (nunca texto plano).
-- 2. Vincule esa cuenta con su registro de repartidor:
--    UPDATE repartidor SET id_empleado = <id_empleado> WHERE id_repartidor = <id_repartidor>;

-- Ejecutar una sola vez sobre una base de datos ya instalada.
-- Las cuentas de repartidor usan la tabla empleado y el rol "Repartidor".

INSERT INTO rol (rol)
SELECT 'Repartidor'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE rol = 'Repartidor');

ALTER TABLE repartidor
    ADD COLUMN id_empleado INT NULL,
    ADD UNIQUE KEY uq_repartidor_empleado (id_empleado),
    ADD CONSTRAINT fk_repartidor_empleado
        FOREIGN KEY (id_empleado) REFERENCES empleado (id_empleado)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE pedido
    ADD COLUMN fecha_entrega DATETIME NULL AFTER fecha_aceptacion;

-- Cree la cuenta del empleado con rol Repartidor, después vincúlela al registro:
-- UPDATE repartidor SET id_empleado = <id_empleado> WHERE id_repartidor = <id_repartidor>;
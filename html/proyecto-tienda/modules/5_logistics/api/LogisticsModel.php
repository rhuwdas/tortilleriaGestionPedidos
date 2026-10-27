<?php
/** Consultas y confirmación de entregas asignadas (HU10). */

require_once __DIR__ . '/../../../core/Database.php';

class LogisticsModel
{
	private PDO $conexionBaseDatos;

	public function __construct()
	{
		$this->conexionBaseDatos = Database::obtenerInstancia()->obtenerConexion();
	}

	/** @return array<int, array<string, mixed>> */
	public function obtenerEntregasAsignadas(int $idRepartidor): array
	{
		$sentencia = $this->conexionBaseDatos->prepare(
			"SELECT p.id_pedido,
					p.estado,
					p.fecha_hora_entrega_solicitada,
					c.nombre AS nombre_cliente,
					c.apellidos AS apellidos_cliente,
					c.telefono,
					d.calle,
					d.colonia,
					d.municipio,
					d.codigo_postal,
					d.referencia,
					geo.latitud,
					geo.longitud,
					COALESCE((SELECT SUM(dp.cantidad * dp.precio_unitario)
								FROM detalle_pedido dp WHERE dp.id_pedido = p.id_pedido), 0) AS total_pedido,
					COALESCE((SELECT SUM(pa.monto) FROM pago pa WHERE pa.id_pedido = p.id_pedido), 0) AS total_pagado
			   FROM pedido p
			   INNER JOIN cliente c ON c.id_cliente = p.id_cliente
			   INNER JOIN direccion d ON d.id_direccion = p.id_direccion
			   LEFT JOIN direccion_geolocalizacion geo ON geo.id_direccion = d.id_direccion
			  WHERE p.id_repartidor = :id_repartidor
				AND p.tipo_entrega = 'Domicilio'
				AND p.estado IN ('Pendiente', 'En Proceso', 'En Ruta')
			  ORDER BY p.fecha_hora_entrega_solicitada IS NULL,
						p.fecha_hora_entrega_solicitada ASC,
						p.id_pedido ASC"
		);
		$sentencia->execute([':id_repartidor' => $idRepartidor]);
		$entregas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
		if ($entregas === []) {
			return [];
		}

		$idsPedidos = array_map(static fn(array $pedido): int => (int)$pedido['id_pedido'], $entregas);
		$marcadores = implode(',', array_fill(0, count($idsPedidos), '?'));
		$sentenciaDetalles = $this->conexionBaseDatos->prepare(
			"SELECT dp.id_pedido, dp.id_producto, pr.nombre AS producto, pr.unidad,
					dp.cantidad, dp.precio_unitario,
					(dp.cantidad * dp.precio_unitario) AS subtotal
			   FROM detalle_pedido dp
			   INNER JOIN producto pr ON pr.id_producto = dp.id_producto
			  WHERE dp.id_pedido IN ({$marcadores})
			  ORDER BY dp.id_pedido, pr.nombre"
		);
		$sentenciaDetalles->execute($idsPedidos);

		$detallesPorPedido = [];
		foreach ($sentenciaDetalles->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
			$detallesPorPedido[(int)$detalle['id_pedido']][] = $detalle;
		}

		foreach ($entregas as &$entrega) {
			$entrega['detalles'] = $detallesPorPedido[(int)$entrega['id_pedido']] ?? [];
			$entrega['total_pedido'] = (float)$entrega['total_pedido'];
			$entrega['total_pagado'] = (float)$entrega['total_pagado'];
			$entrega['monto_cobrar'] = max(0, round($entrega['total_pedido'] - $entrega['total_pagado'], 2));
		}
		unset($entrega);

		return $entregas;
	}

	public function confirmarEntrega(int $idPedido, int $idRepartidor): bool
	{
		$sentencia = $this->conexionBaseDatos->prepare(
			"UPDATE pedido
				SET estado = 'Entregado', fecha_entrega = NOW()
			  WHERE id_pedido = :id_pedido
				AND id_repartidor = :id_repartidor
				AND tipo_entrega = 'Domicilio'
				AND estado IN ('Pendiente', 'En Proceso', 'En Ruta')"
		);
		$sentencia->execute([
			':id_pedido' => $idPedido,
			':id_repartidor' => $idRepartidor,
		]);
		return $sentencia->rowCount() === 1;
	}
}

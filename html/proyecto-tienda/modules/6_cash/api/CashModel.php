<?php
/** Modelo transaccional de cobro de pedidos (HU9). */

require_once __DIR__ . '/../../../core/Database.php';

class EfectivoInsuficienteException extends RuntimeException
{
	public float $total;
	public float $efectivoRecibido;

	public function __construct(float $total, float $efectivoRecibido)
	{
		$this->total = $total;
		$this->efectivoRecibido = $efectivoRecibido;
		parent::__construct('El efectivo recibido es insuficiente.');
	}
}

class PedidoYaCobradoException extends RuntimeException
{
}

class CashModel
{
	private PDO $conexionBaseDatos;

	public function __construct()
	{
		$this->conexionBaseDatos = Database::obtenerInstancia()->obtenerConexion();
	}

	/** @return array<string, mixed>|null */
	public function obtenerPedidoParaCobro(int $idPedido): ?array
	{
		$sentencia = $this->conexionBaseDatos->prepare(
			"SELECT p.id_pedido,
					p.estado,
					p.canal,
					p.tipo_entrega,
					p.fecha_pedido,
					p.fecha_hora_entrega_solicitada,
					c.nombre AS nombre_cliente,
					c.apellidos AS apellidos_cliente,
					c.telefono,
					d.calle,
					d.colonia,
					d.municipio,
					COALESCE((SELECT SUM(dp.cantidad * dp.precio_unitario)
								FROM detalle_pedido dp WHERE dp.id_pedido = p.id_pedido), 0) AS total,
					EXISTS(SELECT 1 FROM pago pa WHERE pa.id_pedido = p.id_pedido) AS pagado
			   FROM pedido p
			   INNER JOIN cliente c ON c.id_cliente = p.id_cliente
			   LEFT JOIN direccion d ON d.id_direccion = p.id_direccion
			  WHERE p.id_pedido = :id_pedido
			  LIMIT 1"
		);
		$sentencia->execute([':id_pedido' => $idPedido]);
		$pedido = $sentencia->fetch(PDO::FETCH_ASSOC);
		if ($pedido === false) {
			return null;
		}

		$pedido['detalles'] = $this->obtenerDetallesPedido($idPedido);
		$pedido['total'] = (float)$pedido['total'];
		$pedido['pagado'] = (bool)$pedido['pagado'];
		return $pedido;
	}

	/** Registra venta, partidas y pago como una sola operación atómica. */
	public function cobrarPedido(int $idPedido, int $idEmpleado, float $efectivoRecibido): array
	{
		$this->conexionBaseDatos->beginTransaction();

		try {
			$sentenciaPedido = $this->conexionBaseDatos->prepare(
				'SELECT id_pedido, id_cliente, estado
				   FROM pedido
				  WHERE id_pedido = :id_pedido
				  FOR UPDATE'
			);
			$sentenciaPedido->execute([':id_pedido' => $idPedido]);
			$pedido = $sentenciaPedido->fetch(PDO::FETCH_ASSOC);
			if ($pedido === false) {
				throw new InvalidArgumentException('El pedido no existe.');
			}
			if (!in_array($pedido['estado'], ['Pendiente', 'En Proceso'], true)) {
				throw new InvalidArgumentException('El pedido no está disponible para cobro.');
			}

			$sentenciaPagoExistente = $this->conexionBaseDatos->prepare(
				'SELECT id_pago FROM pago WHERE id_pedido = :id_pedido LIMIT 1 FOR UPDATE'
			);
			$sentenciaPagoExistente->execute([':id_pedido' => $idPedido]);
			if ($sentenciaPagoExistente->fetchColumn()) {
				throw new PedidoYaCobradoException('El pedido ya tiene un pago registrado y no se puede volver a cobrar.');
			}

			$detalles = $this->obtenerDetallesPedido($idPedido);
			if ($detalles === []) {
				throw new InvalidArgumentException('El pedido no contiene productos para cobrar.');
			}

			$total = round(array_reduce(
				$detalles,
				static fn(float $acumulado, array $detalle): float => $acumulado + ((float)$detalle['cantidad'] * (float)$detalle['precio_unitario']),
				0.0
			), 2);
			$efectivoRecibido = round($efectivoRecibido, 2);
			if ($efectivoRecibido < $total) {
				throw new EfectivoInsuficienteException($total, $efectivoRecibido);
			}

			$sentenciaVenta = $this->conexionBaseDatos->prepare(
				'INSERT INTO venta (monto_total, id_cliente, id_empleado)
				 VALUES (:monto_total, :id_cliente, :id_empleado)'
			);
			$sentenciaVenta->execute([
				':monto_total' => number_format($total, 2, '.', ''),
				':id_cliente' => (int)$pedido['id_cliente'],
				':id_empleado' => $idEmpleado,
			]);
			$idVenta = (int)$this->conexionBaseDatos->lastInsertId();

			$sentenciaDetalleVenta = $this->conexionBaseDatos->prepare(
				'INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario)
				 VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario)'
			);
			foreach ($detalles as $detalle) {
				$sentenciaDetalleVenta->execute([
					':id_venta' => $idVenta,
					':id_producto' => (int)$detalle['id_producto'],
					':cantidad' => $detalle['cantidad'],
					':precio_unitario' => $detalle['precio_unitario'],
				]);
			}

			$sentenciaPago = $this->conexionBaseDatos->prepare(
				"INSERT INTO pago (metodo_pago, monto, id_venta, id_pedido)
				 VALUES ('Efectivo', :monto, :id_venta, :id_pedido)"
			);
			$sentenciaPago->execute([
				':monto' => number_format($total, 2, '.', ''),
				':id_venta' => $idVenta,
				':id_pedido' => $idPedido,
			]);

			$sentenciaActualizarPedido = $this->conexionBaseDatos->prepare(
				"UPDATE pedido
					SET estado = 'En Proceso',
						fecha_aceptacion = COALESCE(fecha_aceptacion, NOW()),
						id_empleado_atiende = COALESCE(id_empleado_atiende, :id_empleado)
				  WHERE id_pedido = :id_pedido"
			);
			$sentenciaActualizarPedido->execute([
				':id_empleado' => $idEmpleado,
				':id_pedido' => $idPedido,
			]);

			$sentenciaFechaVenta = $this->conexionBaseDatos->prepare(
				'SELECT fecha_venta FROM venta WHERE id_venta = :id_venta'
			);
			$sentenciaFechaVenta->execute([':id_venta' => $idVenta]);
			$fechaVenta = $sentenciaFechaVenta->fetchColumn();
			$this->conexionBaseDatos->commit();

			return [
				'id_venta' => $idVenta,
				'id_pedido' => $idPedido,
				'total' => $total,
				'efectivo_recibido' => $efectivoRecibido,
				'cambio' => round($efectivoRecibido - $total, 2),
				'fecha_venta' => $fechaVenta,
				'detalles' => $detalles,
			];
		} catch (Throwable $error) {
			if ($this->conexionBaseDatos->inTransaction()) {
				$this->conexionBaseDatos->rollBack();
			}
			throw $error;
		}
	}

	/** @return array<int, array<string, mixed>> */
	private function obtenerDetallesPedido(int $idPedido): array
	{
		$sentencia = $this->conexionBaseDatos->prepare(
			'SELECT dp.id_producto, pr.nombre, pr.unidad, dp.cantidad, dp.precio_unitario,
					(dp.cantidad * dp.precio_unitario) AS subtotal
			   FROM detalle_pedido dp
			   INNER JOIN producto pr ON pr.id_producto = dp.id_producto
			  WHERE dp.id_pedido = :id_pedido
			  ORDER BY dp.detalle_pedido'
		);
		$sentencia->execute([':id_pedido' => $idPedido]);
		return $sentencia->fetchAll(PDO::FETCH_ASSOC);
	}
}

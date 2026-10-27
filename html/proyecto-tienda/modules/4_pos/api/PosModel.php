<?php
/**
 * modules/4_pos/api/PosModel.php
 * Consultas de recepción y captura de pedidos (HU7 y HU8).
 */

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/AsignacionRepartidor.php';
require_once __DIR__ . '/../../3_ecommerce/api/EcommerceModel.php';

class PosModel
{
	private PDO $conexionBaseDatos;

	public function __construct()
	{
		$this->conexionBaseDatos = Database::obtenerInstancia()->obtenerConexion();
	}

	/** @return array<int, array<string, mixed>> */
	public function obtenerPedidosPendientes(): array
	{
		$sentencia = $this->conexionBaseDatos->query(
			"SELECT p.id_pedido,
					p.canal,
					p.estado,
					p.fecha_pedido,
					p.fecha_hora_entrega_solicitada,
					p.tipo_entrega,
					c.nombre AS nombre_cliente,
					c.apellidos AS apellidos_cliente,
					c.telefono,
					d.calle,
					d.colonia,
					d.municipio
			   FROM pedido p
			   INNER JOIN cliente c ON c.id_cliente = p.id_cliente
			   LEFT JOIN direccion d ON d.id_direccion = p.id_direccion
			  WHERE p.estado IN ('Pendiente', 'En Proceso')
				AND NOT EXISTS (SELECT 1 FROM pago pa WHERE pa.id_pedido = p.id_pedido)
			  ORDER BY p.fecha_pedido ASC,
					   p.id_pedido ASC
			  LIMIT 100"
		);
		$pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

		if ($pedidos === []) {
			return [];
		}

		$idsPedidos = array_map(static fn(array $pedido): int => (int)$pedido['id_pedido'], $pedidos);
		$marcadores = implode(',', array_fill(0, count($idsPedidos), '?'));
		$sentenciaDetalles = $this->conexionBaseDatos->prepare(
			"SELECT dp.id_pedido,
					dp.id_producto,
					pr.nombre AS producto,
					dp.cantidad,
					dp.precio_unitario
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

		foreach ($pedidos as &$pedido) {
			$pedido['detalles'] = $detallesPorPedido[(int)$pedido['id_pedido']] ?? [];
		}
		unset($pedido);

		return $pedidos;
	}

	/** @return array<int, array<string, mixed>> */
	public function buscarClientes(string $termino): array
	{
		$termino = trim($termino);
	$longitudTermino = preg_match_all('/./us', $termino, $coincidencias);
	if ($longitudTermino === false || $longitudTermino < 2) {
			return [];
		}

		$patron = '%' . $termino . '%';
	$digitosTelefono = preg_replace('/\D+/', '', $termino);
	$patronTelefono = $digitosTelefono === '' ? '__sin_coincidencia__' : '%' . $digitosTelefono . '%';
		$sentencia = $this->conexionBaseDatos->prepare(
			"SELECT id_cliente, nombre, apellidos, telefono
			   FROM cliente
			  WHERE activo = 1
				AND (
					nombre LIKE :nombre
					OR apellidos LIKE :apellidos
					OR telefono LIKE :telefono
					OR REPLACE(REPLACE(REPLACE(telefono, '-', ''), ' ', ''), '.', '') LIKE :telefono_normalizado
				)
			  ORDER BY nombre, apellidos
			  LIMIT 10"
		);
		$sentencia->execute([
			':nombre' => $patron,
			':apellidos' => $patron,
			':telefono' => $patronTelefono,
			':telefono_normalizado' => $patronTelefono,
		]);

		return $sentencia->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Crea un pedido presencial o telefónico y, si hace falta, el cliente nuevo en una transacción.
	 * El precio y la existencia se consultan en servidor, nunca se aceptan desde el navegador.
	 */
	public function crearPedidoPresencial(array $datosPedido, int $idEmpleado): int
	{
		$this->conexionBaseDatos->beginTransaction();

		try {
			$modeloEcommerce = new EcommerceModel();
			$validacionInventario = $modeloEcommerce->validarExistencias($datosPedido['items'], true);
			if (!$validacionInventario['valido']) {
				throw new ExistenciasInsuficientesException($validacionInventario['errores']);
			}

			if (!empty($datosPedido['id_cliente'])) {
				$idCliente = (int)$datosPedido['id_cliente'];
				$sentenciaCliente = $this->conexionBaseDatos->prepare(
					'SELECT id_cliente FROM cliente WHERE id_cliente = :id_cliente AND activo = 1 LIMIT 1'
				);
				$sentenciaCliente->execute([':id_cliente' => $idCliente]);
				if (!$sentenciaCliente->fetchColumn()) {
					throw new InvalidArgumentException('El cliente seleccionado no está disponible.');
				}
			} else {
				$clienteNuevo = $datosPedido['cliente_nuevo'] ?? [];
				$sentenciaCliente = $this->conexionBaseDatos->prepare(
					'INSERT INTO cliente (nombre, apellidos, telefono, activo)
					 VALUES (:nombre, :apellidos, :telefono, 1)'
				);
				$sentenciaCliente->bindValue(':nombre', trim($clienteNuevo['nombre']), PDO::PARAM_STR);
				$sentenciaCliente->bindValue(':apellidos', trim($clienteNuevo['apellidos']), PDO::PARAM_STR);
				$telefono = trim($clienteNuevo['telefono'] ?? '');
				$sentenciaCliente->bindValue(':telefono', $telefono === '' ? null : $telefono, $telefono === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
				$sentenciaCliente->execute();
				$idCliente = (int)$this->conexionBaseDatos->lastInsertId();
			}

			$idDireccion = null;
			if ($datosPedido['tipo_entrega'] === 'Domicilio') {
				$direccion = $datosPedido['direccion'];
				$sentenciaDireccion = $this->conexionBaseDatos->prepare(
					'INSERT INTO direccion (id_cliente, calle, colonia, municipio, codigo_postal, referencia)
					 VALUES (:id_cliente, :calle, :colonia, :municipio, :codigo_postal, :referencia)'
				);
				$sentenciaDireccion->execute([
					':id_cliente' => $idCliente,
					':calle' => trim($direccion['calle']),
					':colonia' => trim($direccion['colonia'] ?? ''),
					':municipio' => trim($direccion['municipio']),
					':codigo_postal' => trim($direccion['codigo_postal'] ?? ''),
					':referencia' => trim($direccion['referencia'] ?? ''),
				]);
				$idDireccion = (int)$this->conexionBaseDatos->lastInsertId();
			}

			$sentenciaPedido = $this->conexionBaseDatos->prepare(
				"INSERT INTO pedido (
					estado, id_cliente, id_empleado, id_empleado_atiende, id_direccion, canal,
					fecha_hora_entrega_solicitada, tipo_entrega
				) VALUES ('Pendiente', :id_cliente, :id_empleado, :id_empleado_atiende, :id_direccion, :canal, :fecha_entrega, :tipo_entrega)"
			);
			$sentenciaPedido->execute([
				':id_cliente' => $idCliente,
				':id_empleado' => $idEmpleado,
				':id_empleado_atiende' => $idEmpleado,
				':id_direccion' => $idDireccion,
				':canal' => $datosPedido['canal'],
				':fecha_entrega' => $datosPedido['fecha_hora_entrega_solicitada'],
				':tipo_entrega' => $datosPedido['tipo_entrega'],
			]);
			$idPedido = (int)$this->conexionBaseDatos->lastInsertId();

			$sentenciaDetalle = $this->conexionBaseDatos->prepare(
				'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario)
				 VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario)'
			);
			foreach ($datosPedido['items'] as $item) {
				$producto = $modeloEcommerce->obtenerProducto((int)$item['id_producto']);
				if ($producto === null || (int)$producto['disponible'] !== 1) {
					throw new InvalidArgumentException('Uno de los productos ya no está disponible.');
				}
				$sentenciaDetalle->execute([
					':id_pedido' => $idPedido,
					':id_producto' => (int)$item['id_producto'],
					':cantidad' => (float)$item['cantidad'],
					':precio_unitario' => (float)$producto['precio_kilo'],
				]);
			}

			// Asignar automáticamente el pedido a un repartidor disponible.
			asignarRepartidorAutomatico(
				$this->conexionBaseDatos,
				$idPedido,
				(string)$datosPedido['tipo_entrega']
			);

			$this->conexionBaseDatos->commit();
			return $idPedido;
		} catch (Throwable $error) {
			if ($this->conexionBaseDatos->inTransaction()) {
				$this->conexionBaseDatos->rollBack();
			}
			throw $error;
		}
	}

	/**
	 * Acepta o cancela un pedido mientras no tenga un pago registrado.
	 * El UPDATE condicional evita que dos vendedores atiendan el mismo pedido
	 * o que se cancele una venta ya cobrada.
	 */
	public function resolverPedidoNoPagado(int $idPedido, int $idEmpleado, string $accion, ?string $motivo): bool
	{
		if ($accion === 'aceptar') {
			$sentencia = $this->conexionBaseDatos->prepare(
				"UPDATE pedido
					SET estado = 'En Proceso',
						fecha_aceptacion = NOW(),
						id_empleado_atiende = :id_empleado
				  WHERE id_pedido = :id_pedido
					AND estado = 'Pendiente'
					AND NOT EXISTS (
						SELECT 1 FROM pago pa WHERE pa.id_pedido = pedido.id_pedido
					)"
			);
			$sentencia->execute([
				':id_empleado' => $idEmpleado,
				':id_pedido' => $idPedido,
			]);
		} else {
			$sentencia = $this->conexionBaseDatos->prepare(
				"UPDATE pedido
					SET estado = 'Cancelado',
						motivo_rechazo = :motivo,
						fecha_cancelacion = NOW(),
						id_empleado_atiende = :id_empleado
				  WHERE id_pedido = :id_pedido
					AND estado IN ('Pendiente', 'En Proceso')
					AND NOT EXISTS (
						SELECT 1 FROM pago pa WHERE pa.id_pedido = pedido.id_pedido
					)"
			);
			$sentencia->execute([
				':motivo' => $motivo,
				':id_empleado' => $idEmpleado,
				':id_pedido' => $idPedido,
			]);
		}

		return $sentencia->rowCount() === 1;
	}
}

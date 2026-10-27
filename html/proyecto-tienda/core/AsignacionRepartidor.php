<?php
/**
 * core/AsignacionRepartidor.php
 *
 * Asignación automática de pedidos a domicilio a un repartidor disponible.
 * Se elige primero a un repartidor con cuenta de acceso activa (rol
 * "Repartidor") y, entre ellos, al que tenga menos entregas activas.
 */

/**
 * Asigna un pedido a domicilio al repartidor disponible con menor carga.
 *
 * Solo actúa cuando el pedido es a domicilio y todavía no tiene repartidor,
 * por lo que es seguro llamarla dentro de la transacción de creación del
 * pedido.
 *
 * @param PDO    $conexion     Conexión activa a la base de datos.
 * @param int    $idPedido     Pedido recién creado.
 * @param string $tipoEntrega  Tipo de entrega del pedido.
 * @return int|null Identificador del repartidor asignado o null si no hay
 *                  repartidores disponibles.
 */
function asignarRepartidorAutomatico(PDO $conexion, int $idPedido, string $tipoEntrega): ?int
{
	if ($tipoEntrega !== 'Domicilio' || $idPedido <= 0) {
		return null;
	}

	$sentencia = $conexion->query(
		"SELECT r.id_repartidor
		   FROM repartidor r
		   LEFT JOIN empleado e ON e.id_empleado = r.id_empleado AND e.activo = 1
		   LEFT JOIN rol ro ON ro.id_rol = e.id_rol
		   LEFT JOIN pedido p ON p.id_repartidor = r.id_repartidor
		                      AND p.estado IN ('Pendiente', 'En Proceso', 'En Ruta')
		  WHERE r.estado = 'Disponible'
		  GROUP BY r.id_repartidor, ro.rol
		  ORDER BY (ro.rol = 'Repartidor') DESC,
		           COUNT(p.id_pedido) ASC,
		           r.id_repartidor ASC
		  LIMIT 1"
	);

	$idRepartidor = $sentencia->fetchColumn();
	if ($idRepartidor === false || $idRepartidor === null) {
		return null;
	}

	$asignacion = $conexion->prepare(
		'UPDATE pedido
		    SET id_repartidor = :id_repartidor
		  WHERE id_pedido = :id_pedido
		    AND id_repartidor IS NULL'
	);
	$asignacion->execute([
		':id_repartidor' => (int)$idRepartidor,
		':id_pedido' => $idPedido,
	]);

	return $asignacion->rowCount() === 1 ? (int)$idRepartidor : null;
}

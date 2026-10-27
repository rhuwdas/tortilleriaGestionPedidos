<?php
/** Pruebas de aceptación de cobro en efectivo e impresión (HU9). */

require_once __DIR__ . '/../core/Database.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$baseUrl = 'http://localhost/proyecto-tienda';
$sufijo = time() . random_int(100, 999);
$correoEmpleado = "hu9.cajero.{$sufijo}@example.com";
$contrasena = 'ClaveHU9Segura2026!';
$archivoCookies = sys_get_temp_dir() . "/hu9_cajero_{$sufijo}.txt";
$idEmpleado = null;
$idCliente = null;
$idPedido = null;
$idVenta = null;
$exitos = 0;
$totalPruebas = 5;

/** @return array{0: int, 1: mixed, 2: string} */
function peticionJsonHu9(string $url, string $metodo = 'GET', ?array $cuerpo = null, ?string $archivoCookies = null): array
{
	$manejador = curl_init($url);
	curl_setopt_array($manejador, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST => $metodo,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/json',
			'Accept: application/json',
			'X-Requested-With: XMLHttpRequest',
		],
	]);
	if ($cuerpo !== null) {
		curl_setopt($manejador, CURLOPT_POSTFIELDS, json_encode($cuerpo));
	}
	if ($archivoCookies !== null) {
		curl_setopt($manejador, CURLOPT_COOKIEJAR, $archivoCookies);
		curl_setopt($manejador, CURLOPT_COOKIEFILE, $archivoCookies);
	}
	$respuesta = (string)curl_exec($manejador);
	$codigo = (int)curl_getinfo($manejador, CURLINFO_HTTP_CODE);
	curl_close($manejador);
	return [$codigo, json_decode($respuesta, true), $respuesta];
}

try {
	$idRolCajero = $pdo->query("SELECT id_rol FROM rol WHERE rol = 'Cajero' LIMIT 1")->fetchColumn();
	$productos = $pdo->query('SELECT id_producto, precio_kilo FROM producto WHERE disponible = 1 ORDER BY id_producto LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
	if (!$idRolCajero || count($productos) < 2) {
		throw new RuntimeException('Falta el rol Cajero o no hay dos productos disponibles.');
	}

	$pdo->prepare(
		'INSERT INTO cliente (nombre, apellidos, telefono, activo)
		 VALUES (:nombre, :apellidos, :telefono, 1)'
	)->execute([':nombre' => 'Cliente HU9', ':apellidos' => 'Prueba', ':telefono' => '55-9999-0000']);
	$idCliente = (int)$pdo->lastInsertId();

	$pdo->prepare(
		'INSERT INTO empleado (nombre, apellidos, id_rol, usuario, password_hash, email, activo)
		 VALUES (:nombre, :apellidos, :id_rol, :usuario, :password_hash, :email, 1)'
	)->execute([
		':nombre' => 'Cajero HU9',
		':apellidos' => 'Prueba',
		':id_rol' => (int)$idRolCajero,
		':usuario' => "hu9cajero{$sufijo}",
		':password_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
		':email' => $correoEmpleado,
	]);
	$idEmpleado = (int)$pdo->lastInsertId();

	[$codigoLogin, $respuestaLogin, $crudoLogin] = peticionJsonHu9(
		$baseUrl . '/modules/1_auth/api/login.php',
		'POST',
		['correo' => $correoEmpleado, 'contrasena' => $contrasena],
		$archivoCookies
	);
	if ($codigoLogin !== 200 || empty($respuestaLogin['exito'])) {
		throw new RuntimeException("No se pudo iniciar sesión con el cajero: {$crudoLogin}");
	}

	$pdo->prepare(
		"INSERT INTO pedido (estado, id_cliente, id_empleado, id_empleado_atiende, canal, tipo_entrega)
		 VALUES ('En Proceso', :id_cliente, :id_empleado, :id_empleado_atiende, 'Mostrador', 'Recoger en local')"
	)->execute([
		':id_cliente' => $idCliente,
		':id_empleado' => $idEmpleado,
		':id_empleado_atiende' => $idEmpleado,
	]);
	$idPedido = (int)$pdo->lastInsertId();

	$insertarDetalle = $pdo->prepare(
		'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario)
		 SELECT :id_pedido, id_producto, :cantidad, precio_kilo FROM producto WHERE id_producto = :id_producto'
	);
	$insertarDetalle->execute([':id_pedido' => $idPedido, ':cantidad' => 1.5, ':id_producto' => (int)$productos[0]['id_producto']]);
	$insertarDetalle->execute([':id_pedido' => $idPedido, ':cantidad' => 2, ':id_producto' => (int)$productos[1]['id_producto']]);
	$totalEsperado = round(1.5 * (float)$productos[0]['precio_kilo'] + 2 * (float)$productos[1]['precio_kilo'], 2);

	echo "[PRUEBA 1] La pantalla de Caja muestra el formulario de cobro...\n";
	$manejadorVista = curl_init($baseUrl . '/?ruta=/caja&id_pedido=' . $idPedido);
	curl_setopt_array($manejadorVista, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_COOKIEFILE => $archivoCookies,
		CURLOPT_COOKIEJAR => $archivoCookies,
	]);
	$htmlVista = (string)curl_exec($manejadorVista);
	$codigoVista = (int)curl_getinfo($manejadorVista, CURLINFO_HTTP_CODE);
	curl_close($manejadorVista);
	if (
		$codigoVista === 200
		&& str_contains($htmlVista, 'Caja — Cobro y Venta')
		&& str_contains($htmlVista, 'boton-registrar-cobro')
		&& str_contains($htmlVista, 'ticket-impresion')
		&& str_contains($htmlVista, 'cajaUI.js')
	) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoVista})\n";
	}

	echo "[PRUEBA 2] El efectivo insuficiente indica faltante y no crea ventas...\n";
	$consultaVentasAntes = $pdo->prepare('SELECT COUNT(*) FROM venta WHERE id_empleado = :id_empleado');
	$consultaVentasAntes->execute([':id_empleado' => $idEmpleado]);
	$ventasAntes = (int)$consultaVentasAntes->fetchColumn();
	[$codigoInsuficiente, $respuestaInsuficiente] = peticionJsonHu9(
		$baseUrl . '/modules/4_pos/api/process_payment.php',
		'POST',
		['id_pedido' => $idPedido, 'efectivo_recibido' => $totalEsperado - 1],
		$archivoCookies
	);
	$consultaVentasAntes->execute([':id_empleado' => $idEmpleado]);
	$sinVentaIncompleta = (int)$consultaVentasAntes->fetchColumn() === $ventasAntes;
	if (
		$codigoInsuficiente === 422
		&& abs((float)($respuestaInsuficiente['faltante'] ?? 0) - 1.0) < 0.01
		&& $sinVentaIncompleta
	) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoInsuficiente})\n";
	}

	echo "[PRUEBA 3] El cobro registra venta, partidas, pago y cambio...\n";
	$efectivoRecibido = $totalEsperado + 5.25;
	[$codigoCobro, $respuestaCobro] = peticionJsonHu9(
		$baseUrl . '/modules/4_pos/api/process_payment.php',
		'POST',
		['id_pedido' => $idPedido, 'efectivo_recibido' => $efectivoRecibido],
		$archivoCookies
	);
	$ticket = $respuestaCobro['ticket'] ?? [];
	$idVenta = (int)($ticket['id_venta'] ?? 0);
	$conteoDetallesVenta = 0;
	if ($idVenta > 0) {
		$consultaDetalles = $pdo->prepare('SELECT COUNT(*) FROM detalle_venta WHERE id_venta = :id_venta');
		$consultaDetalles->execute([':id_venta' => $idVenta]);
		$conteoDetallesVenta = (int)$consultaDetalles->fetchColumn();
	}
	$consultaPago = $pdo->prepare('SELECT COUNT(*) FROM pago WHERE id_pedido = :id_pedido AND id_venta = :id_venta');
	$consultaPago->execute([':id_pedido' => $idPedido, ':id_venta' => $idVenta]);
	if (
		$codigoCobro === 201
		&& $idVenta > 0
		&& abs((float)($ticket['total'] ?? 0) - $totalEsperado) < 0.01
		&& abs((float)($ticket['cambio'] ?? 0) - 5.25) < 0.01
		&& $conteoDetallesVenta === 2
		&& (int)$consultaPago->fetchColumn() === 1
		&& $pdo->query('SELECT estado FROM pedido WHERE id_pedido = ' . $idPedido)->fetchColumn() === 'En Proceso'
	) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoCobro})\n";
	}

	echo "[PRUEBA 4] El pedido cobrado no admite un segundo cobro...\n";
	[$codigoDuplicado] = peticionJsonHu9(
		$baseUrl . '/modules/4_pos/api/process_payment.php',
		'POST',
		['id_pedido' => $idPedido, 'efectivo_recibido' => $efectivoRecibido],
		$archivoCookies
	);
	if ($codigoDuplicado === 409) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoDuplicado})\n";
	}

	echo "[PRUEBA 5] Un pedido cobrado ya no se puede cancelar...\n";
	[$codigoCancelarCobrado, $respuestaCancelarCobrado] = peticionJsonHu9(
		$baseUrl . '/modules/4_pos/api/manage_online_order.php',
		'POST',
		[
			'id_pedido' => $idPedido,
			'accion' => 'cancelar',
			'motivo' => 'Intento de cancelación posterior al cobro',
		],
		$archivoCookies
	);
	$consultaEstadoFinal = $pdo->prepare('SELECT estado FROM pedido WHERE id_pedido = :id_pedido');
	$consultaEstadoFinal->execute([':id_pedido' => $idPedido]);
	if (
		$codigoCancelarCobrado === 409
		&& ($respuestaCancelarCobrado['exito'] ?? true) === false
		&& $consultaEstadoFinal->fetchColumn() === 'En Proceso'
	) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoCancelarCobrado})\n";
	}
} catch (Throwable $error) {
	echo "\nERROR GENERAL: {$error->getMessage()}\n";
} finally {
	if ($idPedido !== null) {
		$pdo->prepare('DELETE FROM pago WHERE id_pedido = :id_pedido')->execute([':id_pedido' => $idPedido]);
		$pdo->prepare('DELETE FROM detalle_pedido WHERE id_pedido = :id_pedido')->execute([':id_pedido' => $idPedido]);
		$pdo->prepare('DELETE FROM pedido WHERE id_pedido = :id_pedido')->execute([':id_pedido' => $idPedido]);
	}
	if ($idVenta !== null && $idVenta > 0) {
		$pdo->prepare('DELETE FROM detalle_venta WHERE id_venta = :id_venta')->execute([':id_venta' => $idVenta]);
		$pdo->prepare('DELETE FROM venta WHERE id_venta = :id_venta')->execute([':id_venta' => $idVenta]);
	}
	if ($idCliente !== null) {
		$pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id_cliente')->execute([':id_cliente' => $idCliente]);
	}
	if ($idEmpleado !== null) {
		$pdo->prepare('DELETE FROM empleado WHERE id_empleado = :id_empleado')->execute([':id_empleado' => $idEmpleado]);
	}
	if (file_exists($archivoCookies)) {
		unlink($archivoCookies);
	}
}

echo "\nRESULTADO HU9: {$exitos}/{$totalPruebas} pruebas exitosas\n";
exit($exitos === $totalPruebas ? 0 : 1);
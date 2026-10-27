<?php
/** Pruebas de aceptación de ruta y aislamiento de entregas (HU10). */

require_once __DIR__ . '/../core/Database.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$baseUrl = 'http://localhost/proyecto-tienda';
$sufijo = time() . random_int(100, 999);
$contrasena = 'ClaveHU10Segura2026!';
$cookiesRepartidorA = sys_get_temp_dir() . "/hu10_repartidor_a_{$sufijo}.txt";
$cookiesRepartidorB = sys_get_temp_dir() . "/hu10_repartidor_b_{$sufijo}.txt";
$empleados = [];
$repartidores = [];
$clientes = [];
$direcciones = [];
$pedidos = [];
$exitos = 0;
$totalPruebas = 5;

/** @return array{0: int, 1: mixed, 2: string} */
function peticionJsonHu10(string $url, string $metodo = 'GET', ?array $cuerpo = null, ?string $cookies = null): array
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
	if ($cookies !== null) {
		curl_setopt($manejador, CURLOPT_COOKIEJAR, $cookies);
		curl_setopt($manejador, CURLOPT_COOKIEFILE, $cookies);
	}
	$respuesta = (string)curl_exec($manejador);
	$codigo = (int)curl_getinfo($manejador, CURLINFO_HTTP_CODE);
	curl_close($manejador);
	return [$codigo, json_decode($respuesta, true), $respuesta];
}

try {
	$idRolRepartidor = $pdo->query("SELECT id_rol FROM rol WHERE rol = 'Repartidor' LIMIT 1")->fetchColumn();
	$idProducto = $pdo->query('SELECT id_producto FROM producto WHERE disponible = 1 ORDER BY id_producto LIMIT 1')->fetchColumn();
	if (!$idRolRepartidor || !$idProducto) {
		throw new RuntimeException('Falta el rol Repartidor o no hay productos disponibles. Ejecuta primero la migración HU10.');
	}

	foreach (['a', 'b'] as $sufijoRepartidor) {
		$correo = "hu10.{$sufijoRepartidor}.{$sufijo}@example.com";
		$pdo->prepare(
			'INSERT INTO empleado (nombre, apellidos, id_rol, usuario, password_hash, email, activo)
			 VALUES (:nombre, :apellidos, :id_rol, :usuario, :password_hash, :email, 1)'
		)->execute([
			':nombre' => 'Repartidor ' . strtoupper($sufijoRepartidor),
			':apellidos' => 'Prueba HU10',
			':id_rol' => (int)$idRolRepartidor,
			':usuario' => "hu10{$sufijoRepartidor}{$sufijo}",
			':password_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
			':email' => $correo,
		]);
		$idEmpleado = (int)$pdo->lastInsertId();
		$empleados[$sufijoRepartidor] = $idEmpleado;

		$pdo->prepare(
			'INSERT INTO repartidor (id_empleado, nombre, apellidos, telefono, estado)
			 VALUES (:id_empleado, :nombre, :apellidos, :telefono, \'Disponible\')'
		)->execute([
			':id_empleado' => $idEmpleado,
			':nombre' => 'Repartidor ' . strtoupper($sufijoRepartidor),
			':apellidos' => 'Prueba HU10',
			':telefono' => '55-8000-00' . ($sufijoRepartidor === 'a' ? '01' : '02'),
		]);
		$repartidores[$sufijoRepartidor] = (int)$pdo->lastInsertId();

		[$codigoLogin, $respuestaLogin, $crudoLogin] = peticionJsonHu10(
			$baseUrl . '/modules/1_auth/api/login.php',
			'POST',
			['correo' => $correo, 'contrasena' => $contrasena],
			$sufijoRepartidor === 'a' ? $cookiesRepartidorA : $cookiesRepartidorB
		);
		if ($codigoLogin !== 200 || empty($respuestaLogin['exito'])) {
			throw new RuntimeException("No inició sesión el repartidor {$sufijoRepartidor}: {$crudoLogin}");
		}
	}

	$pdo->prepare(
		'INSERT INTO cliente (nombre, apellidos, telefono, activo)
		 VALUES (\'Cliente HU10 A\', \'Prueba\', \'55-7000-0001\', 1),
		        (\'Cliente HU10 B\', \'Prueba\', \'55-7000-0002\', 1)'
	)->execute();
	$clienteInicial = (int)$pdo->lastInsertId();
	$clientes = [$clienteInicial, $clienteInicial + 1];

	foreach ($clientes as $indice => $idCliente) {
		$pdo->prepare(
			'INSERT INTO direccion (id_cliente, calle, colonia, municipio, codigo_postal, referencia)
			 VALUES (:id_cliente, :calle, :colonia, :municipio, :codigo_postal, :referencia)'
		)->execute([
			':id_cliente' => $idCliente,
			':calle' => 'Calle Entrega HU10 ' . ($indice + 1),
			':colonia' => 'Centro',
			':municipio' => 'Oaxaca',
			':codigo_postal' => '68000',
			':referencia' => 'Puerta de prueba',
		]);
		$direcciones[] = (int)$pdo->lastInsertId();

		$claveRepartidor = $indice === 0 ? 'a' : 'b';
		$pdo->prepare(
			"INSERT INTO pedido (estado, id_cliente, id_empleado, id_repartidor, id_direccion, canal,
			                    fecha_hora_entrega_solicitada, tipo_entrega)
			 VALUES ('En Ruta', :id_cliente, :id_empleado, :id_repartidor, :id_direccion, 'Mostrador', NOW(), 'Domicilio')"
		)->execute([
			':id_cliente' => $idCliente,
			':id_empleado' => $empleados[$claveRepartidor],
			':id_repartidor' => $repartidores[$claveRepartidor],
			':id_direccion' => $direcciones[$indice],
		]);
		$pedidos[] = (int)$pdo->lastInsertId();
		$pdo->prepare(
			'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario)
			 SELECT :id_pedido, id_producto, 2, precio_kilo FROM producto WHERE id_producto = :id_producto'
		)->execute([':id_pedido' => $pedidos[$indice], ':id_producto' => (int)$idProducto]);
	}

	echo "[PRUEBA 1] El login del repartidor dirige a logística...\n";
	[$codigoSesionA, $respuestaSesionA] = peticionJsonHu10(
		$baseUrl . '/modules/1_auth/api/login.php',
		'POST',
		['correo' => "hu10.a.{$sufijo}@example.com", 'contrasena' => $contrasena],
		$cookiesRepartidorA
	);
	if ($codigoSesionA === 200 && str_contains($respuestaSesionA['redireccion'] ?? '', 'ruta=/logistica')) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoSesionA})\n";
	}

	echo "[PRUEBA 2] Cada repartidor ve solo su ruta y sus datos...\n";
	[$codigoRuta, $respuestaRuta] = peticionJsonHu10(
		$baseUrl . '/modules/5_logistics/api/get_delivery_route.php',
		'GET',
		null,
		$cookiesRepartidorA
	);
	$entregasA = $respuestaRuta['entregas'] ?? [];
	$idsEntregasA = array_map(static fn(array $entrega): int => (int)$entrega['id_pedido'], $entregasA);
	$entregaCompleta = $entregasA[0] ?? [];
	if (
		$codigoRuta === 200
		&& in_array($pedidos[0], $idsEntregasA, true)
		&& !in_array($pedidos[1], $idsEntregasA, true)
		&& !empty($entregaCompleta['telefono'])
		&& !empty($entregaCompleta['direccion_completa'])
		&& !empty($entregaCompleta['detalles'])
		&& (float)$entregaCompleta['monto_cobrar'] > 0
	) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoRuta})\n";
	}

	echo "[PRUEBA 3] El repartidor no puede confirmar entrega ajena...\n";
	[$codigoAjena] = peticionJsonHu10(
		$baseUrl . '/modules/5_logistics/api/confirm_delivery.php',
		'POST',
		['id_pedido' => $pedidos[1]],
		$cookiesRepartidorA
	);
	$consultaEstado = $pdo->prepare('SELECT estado, fecha_entrega FROM pedido WHERE id_pedido = :id_pedido');
	$consultaEstado->execute([':id_pedido' => $pedidos[1]]);
	$estadoAjeno = $consultaEstado->fetch(PDO::FETCH_ASSOC);
	if ($codigoAjena === 409 && $estadoAjeno['estado'] === 'En Ruta' && $estadoAjeno['fecha_entrega'] === null) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoAjena})\n";
	}

	echo "[PRUEBA 4] El repartidor asignado puede confirmar su entrega...\n";
	[$codigoConfirmacion] = peticionJsonHu10(
		$baseUrl . '/modules/5_logistics/api/confirm_delivery.php',
		'POST',
		['id_pedido' => $pedidos[0]],
		$cookiesRepartidorA
	);
	$consultaEstado->execute([':id_pedido' => $pedidos[0]]);
	$estadoPropio = $consultaEstado->fetch(PDO::FETCH_ASSOC);
	if ($codigoConfirmacion === 200 && $estadoPropio['estado'] === 'Entregado' && $estadoPropio['fecha_entrega'] !== null) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoConfirmacion})\n";
	}

	echo "[PRUEBA 5] La vista incluye navegación a Maps y diseño móvil...\n";
	$manejadorVista = curl_init($baseUrl . '/?ruta=/logistica');
	curl_setopt_array($manejadorVista, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_COOKIEFILE => $cookiesRepartidorA,
		CURLOPT_COOKIEJAR => $cookiesRepartidorA,
	]);
	$htmlVista = (string)curl_exec($manejadorVista);
	$codigoVista = (int)curl_getinfo($manejadorVista, CURLINFO_HTTP_CODE);
	curl_close($manejadorVista);
	if (
		$codigoVista === 200
		&& str_contains($htmlVista, 'Ruta de Entrega')
		&& str_contains($htmlVista, 'deliveryUI.js')
		&& str_contains(file_get_contents(__DIR__ . '/../modules/5_logistics/ui/mapRoutes.js'), 'google.com/maps/dir')
	) {
		$exitos++;
		echo "  -> ÉXITO\n";
	} else {
		echo "  -> FALLO (HTTP {$codigoVista})\n";
	}
} catch (Throwable $error) {
	echo "\nERROR GENERAL: {$error->getMessage()}\n";
} finally {
	if ($pedidos !== []) {
		$marcadores = implode(',', array_fill(0, count($pedidos), '?'));
		$pdo->prepare("DELETE FROM detalle_pedido WHERE id_pedido IN ({$marcadores})")->execute($pedidos);
		$pdo->prepare("DELETE FROM pedido WHERE id_pedido IN ({$marcadores})")->execute($pedidos);
	}
	if ($direcciones !== []) {
		$marcadores = implode(',', array_fill(0, count($direcciones), '?'));
		$pdo->prepare("DELETE FROM direccion_geolocalizacion WHERE id_direccion IN ({$marcadores})")->execute($direcciones);
		$pdo->prepare("DELETE FROM direccion WHERE id_direccion IN ({$marcadores})")->execute($direcciones);
	}
	if ($clientes !== []) {
		$marcadores = implode(',', array_fill(0, count($clientes), '?'));
		$pdo->prepare("DELETE FROM cliente WHERE id_cliente IN ({$marcadores})")->execute($clientes);
	}
	if ($repartidores !== []) {
		$marcadores = implode(',', array_fill(0, count($repartidores), '?'));
		$pdo->prepare("DELETE FROM repartidor WHERE id_repartidor IN ({$marcadores})")->execute(array_values($repartidores));
	}
	if ($empleados !== []) {
		$marcadores = implode(',', array_fill(0, count($empleados), '?'));
		$pdo->prepare("DELETE FROM empleado WHERE id_empleado IN ({$marcadores})")->execute(array_values($empleados));
	}
	foreach ([$cookiesRepartidorA, $cookiesRepartidorB] as $archivoCookies) {
		if (file_exists($archivoCookies)) {
			unlink($archivoCookies);
		}
	}
}

echo "\nRESULTADO HU10: {$exitos}/{$totalPruebas} pruebas exitosas\n";
exit($exitos === $totalPruebas ? 0 : 1);
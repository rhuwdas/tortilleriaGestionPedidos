<?php
/** Pruebas de aceptación para captura de pedidos presenciales y telefónicos (HU8). */

require_once __DIR__ . '/../core/Database.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$baseUrl = 'http://localhost/proyecto-tienda';
$sufijo = time() . random_int(100, 999);
$correoVendedor = "hu8.vendedor.{$sufijo}@example.com";
$contrasena = 'ClaveHU8Segura2026!';
$telefonoCliente = '55-1234-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
$archivoCookies = sys_get_temp_dir() . "/hu8_vendedor_{$sufijo}.txt";
$idEmpleado = null;
$idClienteExistente = null;
$idClienteNuevo = null;
$nombreClienteNuevo = "Cliente Nuevo HU8 {$sufijo}";
$apellidosClienteNuevo = 'Captura Rápida';
$pedidosCreados = [];
$pruebasExitosas = 0;
$pruebasTotales = 7;

/** @return array{0: int, 1: mixed, 2: string} */
function peticionJsonHu8(
    string $url,
    string $metodo = 'GET',
    ?array $cuerpo = null,
    ?string $archivoCookies = null
): array {
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
    $idRolVendedor = $pdo->query("SELECT id_rol FROM rol WHERE rol = 'Vendedor' LIMIT 1")->fetchColumn();
    $productos = $pdo->query('SELECT id_producto, precio_kilo FROM producto WHERE disponible = 1 ORDER BY id_producto LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
    if (!$idRolVendedor || count($productos) < 2) {
        throw new RuntimeException('Falta el rol Vendedor o no hay dos productos disponibles.');
    }

    $insertarCliente = $pdo->prepare(
        'INSERT INTO cliente (nombre, apellidos, telefono, activo)
         VALUES (:nombre, :apellidos, :telefono, 1)'
    );
    $insertarCliente->execute([
        ':nombre' => 'Cliente HU8',
        ':apellidos' => 'Búsqueda ' . $sufijo,
        ':telefono' => $telefonoCliente,
    ]);
    $idClienteExistente = (int)$pdo->lastInsertId();

    $insertarEmpleado = $pdo->prepare(
        'INSERT INTO empleado (nombre, apellidos, id_rol, usuario, password_hash, email, activo)
         VALUES (:nombre, :apellidos, :id_rol, :usuario, :password_hash, :email, 1)'
    );
    $insertarEmpleado->execute([
        ':nombre' => 'Vendedor HU8',
        ':apellidos' => 'Prueba',
        ':id_rol' => (int)$idRolVendedor,
        ':usuario' => "hu8vendedor{$sufijo}",
        ':password_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
        ':email' => $correoVendedor,
    ]);
    $idEmpleado = (int)$pdo->lastInsertId();

    [$codigoLogin, $respuestaLogin, $cuerpoLogin] = peticionJsonHu8(
        $baseUrl . '/modules/1_auth/api/login.php',
        'POST',
        ['correo' => $correoVendedor, 'contrasena' => $contrasena],
        $archivoCookies
    );
    if ($codigoLogin !== 200 || empty($respuestaLogin['exito'])) {
        throw new RuntimeException("No se pudo iniciar sesión de prueba: {$cuerpoLogin}");
    }

    echo "[PRUEBA 1] Buscar cliente por teléfono normalizado...\n";
    [$codigoBusqueda, $respuestaBusqueda] = peticionJsonHu8(
        $baseUrl . '/modules/4_pos/api/search_pos_customers.php?termino=' . urlencode(preg_replace('/\D+/', '', $telefonoCliente)),
        'GET',
        null,
        $archivoCookies
    );
    $idsClientes = array_map(static fn(array $cliente): int => (int)$cliente['id_cliente'], $respuestaBusqueda['clientes'] ?? []);
    if ($codigoBusqueda === 200 && in_array($idClienteExistente, $idsClientes, true)) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoBusqueda})\n";
    }

    $fechaEntrega = (new DateTimeImmutable('+1 day'))->setTime(10, 0)->format('Y-m-d\\TH:i');
    $crearPedido = static function (array $datos) use ($baseUrl, $archivoCookies): array {
        return peticionJsonHu8(
            $baseUrl . '/modules/4_pos/api/create_pos_order.php',
            'POST',
            $datos,
            $archivoCookies
        );
    };
    $articulos = [
        ['id_producto' => (int)$productos[0]['id_producto'], 'cantidad' => 1.5],
        ['id_producto' => (int)$productos[1]['id_producto'], 'cantidad' => 2],
    ];

    echo "[PRUEBA 2] Registrar pedido de mostrador para cliente existente...\n";
    [$codigoMostrador, $respuestaMostrador] = $crearPedido([
        'id_cliente' => $idClienteExistente,
        'canal' => 'Mostrador',
        'tipo_entrega' => 'Recoger en local',
        'fecha_hora_entrega_solicitada' => $fechaEntrega,
        'items' => $articulos,
    ]);
    if ($codigoMostrador === 201 && !empty($respuestaMostrador['id_pedido'])) {
        $pedidosCreados[] = (int)$respuestaMostrador['id_pedido'];
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoMostrador}): " . json_encode($respuestaMostrador, JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "[PRUEBA 3] Registrar cliente nuevo y pedido telefónico...\n";
    [$codigoTelefono, $respuestaTelefono] = $crearPedido([
        'cliente_nuevo' => [
                'nombre' => $nombreClienteNuevo,
                'apellidos' => $apellidosClienteNuevo,
            'telefono' => $telefonoCliente,
        ],
        'canal' => 'Teléfono',
        'tipo_entrega' => 'Domicilio',
            'direccion' => ['calle' => 'Calle HU8 123', 'municipio' => 'Oaxaca'],
        'fecha_hora_entrega_solicitada' => $fechaEntrega,
        'items' => $articulos,
    ]);
    if ($codigoTelefono === 201 && !empty($respuestaTelefono['id_pedido'])) {
        $pedidosCreados[] = (int)$respuestaTelefono['id_pedido'];
        $consultaClienteNuevo = $pdo->prepare(
            'SELECT id_cliente FROM cliente WHERE nombre = :nombre AND apellidos = :apellidos ORDER BY id_cliente DESC LIMIT 1'
        );
        $consultaClienteNuevo->execute([':nombre' => $nombreClienteNuevo, ':apellidos' => $apellidosClienteNuevo]);
        $idClienteNuevo = (int)$consultaClienteNuevo->fetchColumn();
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoTelefono}): " . json_encode($respuestaTelefono, JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "[PRUEBA 4] Verificar totales persistidos y presencia en bandeja compartida...\n";
    $pedidoCorrecto = false;
    if (count($pedidosCreados) === 2) {
        $consultaPedido = $pdo->prepare(
            'SELECT p.estado, p.canal, p.id_cliente,
                    SUM(dp.cantidad * dp.precio_unitario) AS total
               FROM pedido p
               INNER JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
              WHERE p.id_pedido = :id_pedido
              GROUP BY p.id_pedido'
        );
        $consultaPedido->execute([':id_pedido' => $pedidosCreados[0]]);
        $pedidoDb = $consultaPedido->fetch(PDO::FETCH_ASSOC);
        $totalEsperado = (1.5 * (float)$productos[0]['precio_kilo']) + (2 * (float)$productos[1]['precio_kilo']);
        $pedidoCorrecto = $pedidoDb
            && $pedidoDb['estado'] === 'Pendiente'
            && $pedidoDb['canal'] === 'Mostrador'
            && (int)$pedidoDb['id_cliente'] === $idClienteExistente
            && abs((float)$pedidoDb['total'] - $totalEsperado) < 0.01;

        [$codigoBandeja, $respuestaBandeja] = peticionJsonHu8(
            $baseUrl . '/modules/4_pos/api/receive_online_orders.php',
            'GET',
            null,
            $archivoCookies
        );
        $idsBandeja = array_map(static fn(array $pedido): int => (int)$pedido['id_pedido'], $respuestaBandeja['pedidos'] ?? []);
        $pedidoCorrecto = $pedidoCorrecto
            && $codigoBandeja === 200
            && in_array($pedidosCreados[0], $idsBandeja, true)
            && in_array($pedidosCreados[1], $idsBandeja, true);
    }

    if ($pedidoCorrecto) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO\n";
    }

    echo "[PRUEBA 5] Aceptar un pedido de mostrador que aún no se cobra...\n";
    [$codigoAceptar, $respuestaAceptar] = peticionJsonHu8(
        $baseUrl . '/modules/4_pos/api/manage_online_order.php',
        'POST',
        ['id_pedido' => $pedidosCreados[0], 'accion' => 'aceptar'],
        $archivoCookies
    );
    $consultaAceptado = $pdo->prepare(
        'SELECT estado, fecha_aceptacion, id_empleado_atiende FROM pedido WHERE id_pedido = :id_pedido'
    );
    $consultaAceptado->execute([':id_pedido' => $pedidosCreados[0]]);
    $pedidoAceptadoPos = $consultaAceptado->fetch(PDO::FETCH_ASSOC);
    if (
        $codigoAceptar === 200
        && ($respuestaAceptar['exito'] ?? false) === true
        && $pedidoAceptadoPos['estado'] === 'En Proceso'
        && $pedidoAceptadoPos['fecha_aceptacion'] !== null
        && (int)$pedidoAceptadoPos['id_empleado_atiende'] === $idEmpleado
    ) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoAceptar})\n";
    }

    echo "[PRUEBA 6] Cancelar un pedido aceptado que aún no se cobra...\n";
    [$codigoCancelarAceptado, $respuestaCancelarAceptado] = peticionJsonHu8(
        $baseUrl . '/modules/4_pos/api/manage_online_order.php',
        'POST',
        [
            'id_pedido' => $pedidosCreados[0],
            'accion' => 'cancelar',
            'motivo' => 'El cliente canceló antes de pagar',
        ],
        $archivoCookies
    );
    $consultaCancelado = $pdo->prepare(
        'SELECT estado, motivo_rechazo, fecha_cancelacion FROM pedido WHERE id_pedido = :id_pedido'
    );
    $consultaCancelado->execute([':id_pedido' => $pedidosCreados[0]]);
    $pedidoCanceladoPos = $consultaCancelado->fetch(PDO::FETCH_ASSOC);
    if (
        $codigoCancelarAceptado === 200
        && ($respuestaCancelarAceptado['exito'] ?? false) === true
        && $pedidoCanceladoPos['estado'] === 'Cancelado'
        && $pedidoCanceladoPos['motivo_rechazo'] === 'El cliente canceló antes de pagar'
        && $pedidoCanceladoPos['fecha_cancelacion'] !== null
    ) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoCancelarAceptado})\n";
    }

    echo "[PRUEBA 7] Cancelar un pedido pendiente y retirarlo de la bandeja...\n";
    [$codigoCancelarPendiente] = peticionJsonHu8(
        $baseUrl . '/modules/4_pos/api/manage_online_order.php',
        'POST',
        [
            'id_pedido' => $pedidosCreados[1],
            'accion' => 'cancelar',
            'motivo' => 'Sin disponibilidad para la entrega',
        ],
        $archivoCookies
    );
    [$codigoBandejaFinal, $respuestaBandejaFinal] = peticionJsonHu8(
        $baseUrl . '/modules/4_pos/api/receive_online_orders.php',
        'GET',
        null,
        $archivoCookies
    );
    $idsBandejaFinal = array_map(
        static fn(array $pedido): int => (int)$pedido['id_pedido'],
        $respuestaBandejaFinal['pedidos'] ?? []
    );
    $consultaCanceladoPendiente = $pdo->prepare(
        'SELECT estado FROM pedido WHERE id_pedido = :id_pedido'
    );
    $consultaCanceladoPendiente->execute([':id_pedido' => $pedidosCreados[1]]);
    if (
        $codigoCancelarPendiente === 200
        && $consultaCanceladoPendiente->fetchColumn() === 'Cancelado'
        && $codigoBandejaFinal === 200
        && !in_array($pedidosCreados[0], $idsBandejaFinal, true)
        && !in_array($pedidosCreados[1], $idsBandejaFinal, true)
    ) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoCancelarPendiente})\n";
    }
} catch (Throwable $error) {
    echo "\nERROR GENERAL: {$error->getMessage()}\n";
} finally {
    if ($pedidosCreados !== []) {
        $marcadores = implode(',', array_fill(0, count($pedidosCreados), '?'));
        $pdo->prepare("DELETE FROM detalle_pedido WHERE id_pedido IN ({$marcadores})")->execute($pedidosCreados);
        $pdo->prepare("DELETE FROM pedido WHERE id_pedido IN ({$marcadores})")->execute($pedidosCreados);
    }
    if ($idClienteExistente !== null) {
        $pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id_cliente')->execute([':id_cliente' => $idClienteExistente]);
    }
	if ($idClienteNuevo !== null) {
	    $pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id_cliente')->execute([':id_cliente' => $idClienteNuevo]);
	}
    if ($idEmpleado !== null) {
        $pdo->prepare('DELETE FROM empleado WHERE id_empleado = :id_empleado')->execute([':id_empleado' => $idEmpleado]);
    }
    if (file_exists($archivoCookies)) {
        unlink($archivoCookies);
    }
}

echo "\nRESULTADO HU8: {$pruebasExitosas}/{$pruebasTotales} pruebas exitosas\n";
exit($pruebasExitosas === $pruebasTotales ? 0 : 1);

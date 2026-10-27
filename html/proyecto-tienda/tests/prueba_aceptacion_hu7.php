<?php
/** Pruebas de aceptación de la bandeja de recepción de pedidos web (HU7). */

require_once __DIR__ . '/../core/Database.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$baseUrl = 'http://localhost/proyecto-tienda';
$sufijo = time() . random_int(100, 999);
$correoVendedor = "hu7.vendedor.{$sufijo}@example.com";
$correoCliente = "hu7.cliente.{$sufijo}@example.com";
$contrasena = 'ClaveHU7Segura2026!';
$archivoCookiesVendedor = sys_get_temp_dir() . "/hu7_vendedor_{$sufijo}.txt";
$archivoCookiesCliente = sys_get_temp_dir() . "/hu7_cliente_{$sufijo}.txt";
$pedidosCreados = [];
$idCliente = null;
$idEmpleado = null;
$pruebasExitosas = 0;
$pruebasTotales = 7;

/** @return array{0: int, 1: mixed, 2: string} */
function peticionJsonHu7(
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
    $codigoHttp = (int)curl_getinfo($manejador, CURLINFO_HTTP_CODE);
    curl_close($manejador);
    return [$codigoHttp, json_decode($respuesta, true), $respuesta];
}

function iniciarSesionHu7(string $baseUrl, string $correo, string $contrasena, string $cookies): array
{
    return peticionJsonHu7(
        $baseUrl . '/modules/1_auth/api/login.php',
        'POST',
        ['correo' => $correo, 'contrasena' => $contrasena],
        $cookies
    );
}

try {
    $idRolVendedor = $pdo->query("SELECT id_rol FROM rol WHERE rol = 'Vendedor' LIMIT 1")->fetchColumn();
    $idProducto = $pdo->query('SELECT id_producto FROM producto WHERE disponible = 1 ORDER BY id_producto LIMIT 1')->fetchColumn();
    if (!$idRolVendedor || !$idProducto) {
        throw new RuntimeException('Se requiere el rol Vendedor y un producto disponible para la prueba.');
    }

    $sentenciaCliente = $pdo->prepare(
        'INSERT INTO cliente (nombre, apellidos, email, password_hash, activo)
         VALUES (:nombre, :apellidos, :email, :password_hash, 1)'
    );
    $sentenciaCliente->execute([
        ':nombre' => 'Cliente HU7',
        ':apellidos' => 'Prueba',
        ':email' => $correoCliente,
        ':password_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
    ]);
    $idCliente = (int)$pdo->lastInsertId();

    $sentenciaEmpleado = $pdo->prepare(
        'INSERT INTO empleado (nombre, apellidos, id_rol, usuario, password_hash, email, activo)
         VALUES (:nombre, :apellidos, :id_rol, :usuario, :password_hash, :email, 1)'
    );
    $sentenciaEmpleado->execute([
        ':nombre' => 'Vendedor HU7',
        ':apellidos' => 'Prueba',
        ':id_rol' => (int)$idRolVendedor,
        ':usuario' => "hu7vendedor{$sufijo}",
        ':password_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
        ':email' => $correoVendedor,
    ]);
    $idEmpleado = (int)$pdo->lastInsertId();

    foreach ([1, 2] as $numeroPedido) {
        $sentenciaPedido = $pdo->prepare(
            "INSERT INTO pedido (estado, id_cliente, id_empleado, canal, tipo_entrega, fecha_hora_entrega_solicitada)
             VALUES ('Pendiente', :id_cliente, :id_empleado, 'Web', 'Recoger en local', :fecha_hora_entrega)"
        );
        $sentenciaPedido->execute([
            ':id_cliente' => $idCliente,
            ':id_empleado' => $idEmpleado,
            ':fecha_hora_entrega' => date('Y-m-d H:i:s', strtotime("+{$numeroPedido} hours")),
        ]);
        $idPedido = (int)$pdo->lastInsertId();
        $pedidosCreados[] = $idPedido;

        $pdo->prepare(
            'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario)
             SELECT :id_pedido, id_producto, 2, precio_kilo FROM producto WHERE id_producto = :id_producto'
        )->execute([':id_pedido' => $idPedido, ':id_producto' => (int)$idProducto]);
    }

    [$codigoLoginVendedor, $respuestaLoginVendedor] = iniciarSesionHu7(
        $baseUrl,
        $correoVendedor,
        $contrasena,
        $archivoCookiesVendedor
    );
    [$codigoLoginCliente, $respuestaLoginCliente] = iniciarSesionHu7(
        $baseUrl,
        $correoCliente,
        $contrasena,
        $archivoCookiesCliente
    );
    if ($codigoLoginVendedor !== 200 || empty($respuestaLoginVendedor['exito'])) {
        throw new RuntimeException('No fue posible iniciar sesión con el vendedor de prueba.');
    }
    if ($codigoLoginCliente !== 200 || empty($respuestaLoginCliente['exito'])) {
        throw new RuntimeException('No fue posible iniciar sesión con el cliente de prueba.');
    }

    echo "[PRUEBA 1] La pantalla de recepción está disponible para Vendedor...\n";
    $manejadorVista = curl_init($baseUrl . '/?ruta=/pos');
    curl_setopt_array($manejadorVista, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $archivoCookiesVendedor,
        CURLOPT_COOKIEJAR => $archivoCookiesVendedor,
    ]);
    $htmlVista = (string)curl_exec($manejadorVista);
    $codigoVista = (int)curl_getinfo($manejadorVista, CURLINFO_HTTP_CODE);
    curl_close($manejadorVista);
    if ($codigoVista === 200 && str_contains($htmlVista, 'Recepción de Pedidos en Línea')) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoVista})\n";
    }

    echo "[PRUEBA 2] La bandeja incluye pedidos web con detalle...\n";
    [$codigoBandeja, $respuestaBandeja] = peticionJsonHu7(
        $baseUrl . '/modules/4_pos/api/receive_online_orders.php',
        'GET',
        null,
        $archivoCookiesVendedor
    );
    $pedidosBandeja = $respuestaBandeja['pedidos'] ?? [];
    $idsBandeja = array_map(static fn(array $pedido): int => (int)$pedido['id_pedido'], $pedidosBandeja);
    $pedidoConDetalle = false;
    foreach ($pedidosBandeja as $pedidoBandeja) {
        if ((int)$pedidoBandeja['id_pedido'] === $pedidosCreados[0] && count($pedidoBandeja['detalles']) === 1) {
            $pedidoConDetalle = true;
        }
    }
    if (
        $codigoBandeja === 200
        && in_array($pedidosCreados[0], $idsBandeja, true)
        && in_array($pedidosCreados[1], $idsBandeja, true)
        && $pedidoConDetalle
    ) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO\n";
    }

    echo "[PRUEBA 3] El cliente no puede consultar la bandeja de ventas...\n";
    [$codigoSinPermiso] = peticionJsonHu7(
        $baseUrl . '/modules/4_pos/api/receive_online_orders.php',
        'GET',
        null,
        $archivoCookiesCliente
    );
    if ($codigoSinPermiso === 403) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoSinPermiso})\n";
    }

    echo "[PRUEBA 4] El rechazo exige un motivo no vacío...\n";
    [$codigoMotivoVacio] = peticionJsonHu7(
        $baseUrl . '/modules/4_pos/api/manage_online_order.php',
        'POST',
        ['id_pedido' => $pedidosCreados[1], 'accion' => 'rechazar', 'motivo' => '   '],
        $archivoCookiesVendedor
    );
    if ($codigoMotivoVacio === 422) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO (HTTP {$codigoMotivoVacio})\n";
    }

    echo "[PRUEBA 5] La aceptación mueve el pedido a En Proceso y asigna empleado...\n";
    [$codigoAceptar] = peticionJsonHu7(
        $baseUrl . '/modules/4_pos/api/manage_online_order.php',
        'POST',
        ['id_pedido' => $pedidosCreados[0], 'accion' => 'aceptar'],
        $archivoCookiesVendedor
    );
    $pedidoAceptado = $pdo->prepare('SELECT estado, id_empleado_atiende, fecha_aceptacion FROM pedido WHERE id_pedido = :id_pedido');
    $pedidoAceptado->execute([':id_pedido' => $pedidosCreados[0]]);
    $datosAceptacion = $pedidoAceptado->fetch(PDO::FETCH_ASSOC);
    if (
        $codigoAceptar === 200
        && $datosAceptacion['estado'] === 'En Proceso'
        && (int)$datosAceptacion['id_empleado_atiende'] === $idEmpleado
        && $datosAceptacion['fecha_aceptacion'] !== null
    ) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO\n";
    }

    echo "[PRUEBA 6] El rechazo guarda motivo y empleado responsable...\n";
    [$codigoRechazar] = peticionJsonHu7(
        $baseUrl . '/modules/4_pos/api/manage_online_order.php',
        'POST',
        ['id_pedido' => $pedidosCreados[1], 'accion' => 'rechazar', 'motivo' => 'Sin disponibilidad de producción'],
        $archivoCookiesVendedor
    );
    $pedidoRechazado = $pdo->prepare('SELECT estado, motivo_rechazo, id_empleado_atiende FROM pedido WHERE id_pedido = :id_pedido');
    $pedidoRechazado->execute([':id_pedido' => $pedidosCreados[1]]);
    $datosRechazo = $pedidoRechazado->fetch(PDO::FETCH_ASSOC);
    if (
        $codigoRechazar === 200
        && $datosRechazo['estado'] === 'Cancelado'
        && $datosRechazo['motivo_rechazo'] === 'Sin disponibilidad de producción'
        && (int)$datosRechazo['id_empleado_atiende'] === $idEmpleado
    ) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO\n";
    }

    echo "[PRUEBA 7] El cliente ve los estados aceptado y rechazado...\n";
    [$codigoEstadoAceptado, $respuestaEstadoAceptado] = peticionJsonHu7(
        $baseUrl . '/modules/3_ecommerce/api/track_order.php?id_pedido=' . $pedidosCreados[0],
        'GET',
        null,
        $archivoCookiesCliente
    );
    [$codigoEstadoRechazado, $respuestaEstadoRechazado] = peticionJsonHu7(
        $baseUrl . '/modules/3_ecommerce/api/track_order.php?id_pedido=' . $pedidosCreados[1],
        'GET',
        null,
        $archivoCookiesCliente
    );
    if (
        $codigoEstadoAceptado === 200
        && $respuestaEstadoAceptado['pedido']['estado'] === 'En Proceso'
        && $codigoEstadoRechazado === 200
        && $respuestaEstadoRechazado['pedido']['estado'] === 'Cancelado'
        && $respuestaEstadoRechazado['pedido']['motivo_rechazo'] === 'Sin disponibilidad de producción'
    ) {
        $pruebasExitosas++;
        echo "  -> ÉXITO\n";
    } else {
        echo "  -> FALLO\n";
    }
} catch (Throwable $error) {
    echo "\nERROR GENERAL: {$error->getMessage()}\n";
} finally {
    if ($pedidosCreados !== []) {
        $marcadores = implode(',', array_fill(0, count($pedidosCreados), '?'));
        $pdo->prepare("DELETE FROM detalle_pedido WHERE id_pedido IN ({$marcadores})")->execute($pedidosCreados);
        $pdo->prepare("DELETE FROM pedido WHERE id_pedido IN ({$marcadores})")->execute($pedidosCreados);
    }
    if ($idCliente !== null) {
        $pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id_cliente')->execute([':id_cliente' => $idCliente]);
    }
    if ($idEmpleado !== null) {
        $pdo->prepare('DELETE FROM empleado WHERE id_empleado = :id_empleado')->execute([':id_empleado' => $idEmpleado]);
    }
    foreach ([$archivoCookiesVendedor, $archivoCookiesCliente] as $archivoCookies) {
        if (file_exists($archivoCookies)) {
            unlink($archivoCookies);
        }
    }
}

echo "\nRESULTADO HU7: {$pruebasExitosas}/{$pruebasTotales} pruebas exitosas\n";
exit($pruebasExitosas === $pruebasTotales ? 0 : 1);
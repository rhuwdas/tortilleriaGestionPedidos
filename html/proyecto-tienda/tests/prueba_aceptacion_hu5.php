<?php
/**
 * tests/prueba_aceptacion_hu5.php
 *
 * Script de Pruebas de Aceptación para la Historia de Usuario 5
 * (HU5 - Realizar Pedido en Línea).
 *
 * Valida de forma automatizada:
 *  - CA-001: Selección del tipo de entrega ("A domicilio" o "Recoger en local").
 *  - CA-002: Resumen detallado con productos, cantidades y subtotales.
 *  - CA-003: Número único de pedido y envío a la bandeja de ventas (estado Pendiente).
 *  - CA-HU5-001: Bloqueo de confirmación cuando la cantidad supera el inventario.
 *
 * Ejecución:
 *  docker exec mi_xampp_apache php /var/www/html/proyecto-tienda/tests/prueba_aceptacion_hu5.php
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/1_auth/api/ClienteModel.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$modeloCliente = new ClienteModel();

$baseUrl = 'http://localhost/proyecto-tienda';
$archivoCookies = sys_get_temp_dir() . '/hu5_cookies_' . getmypid() . '.txt';

$exitos = 0;
$totalPruebas = 9;
$pedidosCreados = [];
$idCliente = null;

echo "\n=======================================================\n";
echo " INICIANDO PRUEBAS DE ACEPTACIÓN - HU5: PEDIDO EN LÍNEA\n";
echo "=======================================================\n\n";

/**
 * Ejecuta una petición HTTP y devuelve código, JSON y cuerpo crudo.
 *
 * @return array{0: int, 1: mixed, 2: string}
 */
function peticionJson(
    string $url,
    string $metodo = 'GET',
    ?array $cuerpo = null,
    ?string $archivoCookies = null,
    array $cabeceras = []
): array {
    $manejador = curl_init($url);

    curl_setopt_array($manejador, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
        ], $cabeceras),
    ]);

    if ($cuerpo !== null) {
        curl_setopt($manejador, CURLOPT_POSTFIELDS, json_encode($cuerpo));
    }

    if ($archivoCookies !== null) {
        curl_setopt($manejador, CURLOPT_COOKIEJAR, $archivoCookies);
        curl_setopt($manejador, CURLOPT_COOKIEFILE, $archivoCookies);
    }

    $respuesta = curl_exec($manejador);
    $codigoHttp = (int)curl_getinfo($manejador, CURLINFO_HTTP_CODE);
    curl_close($manejador);

    return [$codigoHttp, json_decode((string)$respuesta, true), (string)$respuesta];
}

/**
 * Crea un pedido en línea autenticado.
 *
 * @return array{0: int, 1: mixed, 2: string}
 */
function crearPedidoEnLinea(string $baseUrl, string $archivoCookies, array $items, string $tipoEntrega): array
{
    $fechaEntrega = new DateTimeImmutable('+3 hours');
    $minutosEntrega = ((int)$fechaEntrega->format('H') * 60) + (int)$fechaEntrega->format('i');
    if ($minutosEntrega < 420) {
        $fechaEntrega = $fechaEntrega->setTime(10, 0);
    } elseif ($minutosEntrega > 1020) {
        $fechaEntrega = $fechaEntrega->modify('+1 day')->setTime(10, 0);
    }

    return peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/create_online_order.php',
        'POST',
        [
            'items'                           => $items,
            'tipo_entrega'                    => $tipoEntrega,
            'fecha_hora_entrega_solicitada'   => $fechaEntrega->format('Y-m-d H:i:s'),
        ],
        $archivoCookies
    );
}

try {
    // ─────────────────────────────────────────────────────────────────────────
    // Preparación: cliente de prueba con dirección y sesión autenticada
    // ─────────────────────────────────────────────────────────────────────────
    $sufijo = time() . random_int(100, 999);
    $correoCliente = "hu5.{$sufijo}@example.com";
    $contrasenaCliente = 'ClaveHU5Segura2026!';

    $idCliente = $modeloCliente->registrarCliente(
        [
            'nombre'       => 'Cliente',
            'apellidos'    => 'Prueba HU5 ' . $sufijo,
            'email'        => $correoCliente,
            'telefono'     => '55' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'contrasena'   => $contrasenaCliente,
        ],
        [
            'calle'        => 'Av. Prueba HU5 101',
            'colonia'      => 'Centro',
            'municipio'    => 'Oaxaca',
            'codigo_postal' => '68000',
            'referencia'   => 'Domicilio de prueba HU5',
        ]
    );

    [$codigoLogin, $respuestaLogin, $crudoLogin] = peticionJson(
        $baseUrl . '/modules/1_auth/api/login.php',
        'POST',
        ['correo' => $correoCliente, 'contrasena' => $contrasenaCliente],
        $archivoCookies,
        ['X-Requested-With: XMLHttpRequest']
    );

    if ($codigoLogin !== 200 || empty($respuestaLogin['exito'])) {
        throw new RuntimeException("No se pudo iniciar sesión para HU5: {$crudoLogin}");
    }

    // Productos reales de la base de datos
    $productoUno = $pdo->query('SELECT id_producto, nombre, precio_kilo FROM producto WHERE disponible = 1 ORDER BY id_producto LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $productoDos = $pdo->query('SELECT id_producto, nombre, precio_kilo FROM producto WHERE disponible = 1 ORDER BY id_producto LIMIT 1 OFFSET 1')->fetch(PDO::FETCH_ASSOC);

    if (!$productoUno || !$productoDos) {
        throw new RuntimeException('No hay suficientes productos disponibles para ejecutar las pruebas HU5.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 1: CA-001 (Tipo de entrega a domicilio)
    // ─────────────────────────────────────────────────────────────────────────
    echo "[PRUEBA 1] Verificando CA-001: pedido con entrega a domicilio...\n";

    [$codigoUno, $respuestaUno, $crudoUno] = crearPedidoEnLinea(
        $baseUrl,
        $archivoCookies,
        [['id_producto' => (int)$productoUno['id_producto'], 'cantidad' => 1.5]],
        'Domicilio'
    );

    if ($codigoUno === 201 && !empty($respuestaUno['id_pedido'])) {
        $idPedidoUno = (int)$respuestaUno['id_pedido'];
        $pedidosCreados[] = $idPedidoUno;

        $consultaPedido = $pdo->prepare('SELECT tipo_entrega, estado, id_direccion, canal FROM pedido WHERE id_pedido = :id_pedido');
        $consultaPedido->execute([':id_pedido' => $idPedidoUno]);
        $pedidoBd = $consultaPedido->fetch(PDO::FETCH_ASSOC);

        $correcto = $pedidoBd
            && $pedidoBd['tipo_entrega'] === 'Domicilio'
            && $pedidoBd['estado'] === 'Pendiente'
            && $pedidoBd['canal'] === 'Web'
            && !empty($pedidoBd['id_direccion']);

        if ($correcto) {
            echo "  -> ÉXITO: Pedido a domicilio creado con dirección y estado Pendiente.\n";
            $exitos++;
        } else {
            echo "  -> FALLO: Datos del pedido incorrectos: " . json_encode($pedidoBd, JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "  -> FALLO: Código HTTP {$codigoUno}, Respuesta: {$crudoUno}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 2: CA-001 (Tipo de entrega en local)
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 2] Verificando CA-001: pedido para recoger en local...\n";

    [$codigoDos, $respuestaDos, $crudoDos] = crearPedidoEnLinea(
        $baseUrl,
        $archivoCookies,
        [['id_producto' => (int)$productoDos['id_producto'], 'cantidad' => 2]],
        'Recoger en local'
    );

    if ($codigoDos === 201 && !empty($respuestaDos['id_pedido'])) {
        $idPedidoDos = (int)$respuestaDos['id_pedido'];
        $pedidosCreados[] = $idPedidoDos;

        $consultaPedido = $pdo->prepare('SELECT tipo_entrega, id_direccion FROM pedido WHERE id_pedido = :id_pedido');
        $consultaPedido->execute([':id_pedido' => $idPedidoDos]);
        $pedidoBd = $consultaPedido->fetch(PDO::FETCH_ASSOC);

        if ($pedidoBd && $pedidoBd['tipo_entrega'] === 'Recoger en local' && $pedidoBd['id_direccion'] === null) {
            echo "  -> ÉXITO: Pedido para recoger en local creado sin dirección de entrega.\n";
            $exitos++;
        } else {
            echo "  -> FALLO: Datos del pedido incorrectos: " . json_encode($pedidoBd, JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "  -> FALLO: Código HTTP {$codigoDos}, Respuesta: {$crudoDos}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 3: CA-002 (Resumen detallado y subtotales persistidos)
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 3] Verificando CA-002: detalle del pedido y subtotal...\n";

    if (!empty($idPedidoUno)) {
        $consultaDetalle = $pdo->prepare(
            'SELECT dp.cantidad, dp.precio_unitario, pr.nombre
               FROM detalle_pedido dp
               INNER JOIN producto pr ON dp.id_producto = pr.id_producto
              WHERE dp.id_pedido = :id_pedido'
        );
        $consultaDetalle->execute([':id_pedido' => $idPedidoUno]);
        $detalles = $consultaDetalle->fetchAll(PDO::FETCH_ASSOC);

        $subtotalEsperado = 1.5 * (float)$productoUno['precio_kilo'];
        $subtotalBd = 0.0;

        foreach ($detalles as $detalle) {
            $subtotalBd += (float)$detalle['cantidad'] * (float)$detalle['precio_unitario'];
        }

        if (count($detalles) === 1 && abs($subtotalBd - $subtotalEsperado) < 0.01) {
            echo "  -> ÉXITO: Detalle guardado con cantidad, precio unitario y subtotal correcto ($" . number_format($subtotalBd, 2) . ").\n";
            $exitos++;
        } else {
            echo "  -> FALLO: Subtotal esperado $subtotalEsperado, obtenido $subtotalBd. Detalle: " . json_encode($detalles, JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "  -> FALLO: No se creó el pedido base para validar el detalle.\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 4: CA-003 (Número único de pedido)
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 4] Verificando CA-003: número único de pedido...\n";

    if (!empty($respuestaUno['numero_pedido']) && !empty($respuestaDos['numero_pedido'])) {
        $numeroUno = (string)$respuestaUno['numero_pedido'];
        $numeroDos = (string)$respuestaDos['numero_pedido'];
        $formatoValido = preg_match('/^PED-\d{6,}$/', $numeroUno) === 1
            && preg_match('/^PED-\d{6,}$/', $numeroDos) === 1;

        if ($formatoValido && $numeroUno !== $numeroDos) {
            echo "  -> ÉXITO: Números únicos generados ({$numeroUno} y {$numeroDos}).\n";
            $exitos++;
        } else {
            echo "  -> FALLO: Formatos o unicidad incorrectos ({$numeroUno}, {$numeroDos}).\n";
        }
    } else {
        echo "  -> FALLO: Las respuestas no incluyeron numero_pedido.\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 5: CA-HU5-001 (Bloqueo por inventario insuficiente)
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 5] Verificando CA-HU5-001: bloqueo por existencias insuficientes...\n";

    [$codigoStock, $respuestaStock, $crudoStock] = crearPedidoEnLinea(
        $baseUrl,
        $archivoCookies,
        [['id_producto' => (int)$productoUno['id_producto'], 'cantidad' => 100000]],
        'Recoger en local'
    );

    if (
        $codigoStock === 422
        && ($respuestaStock['mensaje'] ?? '') === 'Lo lamentamos, no podemos cubrir su pedido; vaya al local.'
    ) {
        echo "  -> ÉXITO: El sistema bloqueó la compra y mostró el mensaje genérico de stock.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoStock}, Respuesta: {$crudoStock}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 6: Carrito vacío
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 6] Verificando rechazo de carrito vacío...\n";

    [$codigoVacio, $respuestaVacio, $crudoVacio] = crearPedidoEnLinea($baseUrl, $archivoCookies, [], 'Recoger en local');

    if ($codigoVacio === 400 && !empty($respuestaVacio['mensaje'])) {
        echo "  -> ÉXITO: El sistema rechazó el carrito vacío con HTTP 400.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoVacio}, Respuesta: {$crudoVacio}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 7: Rechazo de entrega después del horario de cierre
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 7] Verificando rechazo de horario posterior a las 17:00...\n";

    [$codigoFueraHorario, $respuestaFueraHorario] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/create_online_order.php',
        'POST',
        [
            'items' => [['id_producto' => (int)$productoUno['id_producto'], 'cantidad' => 1]],
            'tipo_entrega' => 'Recoger en local',
            'fecha_hora_entrega_solicitada' => date('Y-m-d', strtotime('+1 day')) . ' 18:01:00',
        ],
        $archivoCookies
    );

    if ($codigoFueraHorario === 422 && str_contains((string)($respuestaFueraHorario['mensaje'] ?? ''), '07:00 a 17:00')) {
        echo "  -> ÉXITO: El sistema rechazó la hora posterior al cierre.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoFueraHorario}, Respuesta: " . json_encode($respuestaFueraHorario, JSON_UNESCAPED_UNICODE) . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 8: Rechazo de una fecha de entrega pasada
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 8] Verificando rechazo de fecha de entrega pasada...\n";

    [$codigoFechaPasada, $respuestaFechaPasada] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/create_online_order.php',
        'POST',
        [
            'items' => [['id_producto' => (int)$productoUno['id_producto'], 'cantidad' => 1]],
            'tipo_entrega' => 'Recoger en local',
            'fecha_hora_entrega_solicitada' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ],
        $archivoCookies
    );

    if ($codigoFechaPasada === 422 && str_contains((string)($respuestaFechaPasada['mensaje'] ?? ''), 'futura')) {
        echo "  -> ÉXITO: El sistema rechazó una fecha pasada.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoFechaPasada}, Respuesta: " . json_encode($respuestaFechaPasada, JSON_UNESCAPED_UNICODE) . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 9: Rechazo de recogida en local antes del horario de apertura
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 9] Verificando rechazo de recogida en local antes de las 07:00...\n";

    [$codigoAntesHorario, $respuestaAntesHorario] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/create_online_order.php',
        'POST',
        [
            'items' => [['id_producto' => (int)$productoUno['id_producto'], 'cantidad' => 1]],
            'tipo_entrega' => 'Recoger en local',
            'fecha_hora_entrega_solicitada' => date('Y-m-d', strtotime('+1 day')) . ' 06:59:00',
        ],
        $archivoCookies
    );

    if (
        $codigoAntesHorario === 422
        && str_contains((string)($respuestaAntesHorario['mensaje'] ?? ''), '07:00 a 17:00')
    ) {
        echo "  -> ÉXITO: El sistema rechazó la recogida antes de las 07:00.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoAntesHorario}, Respuesta: " . json_encode($respuestaAntesHorario, JSON_UNESCAPED_UNICODE) . "\n";
    }

} catch (Throwable $error) {
    echo "\n  -> ERROR GENERAL: " . $error->getMessage() . "\n";
} finally {
    // Limpieza de los datos generados por la prueba
    if (!empty($pedidosCreados)) {
        $marcadores = implode(',', array_fill(0, count($pedidosCreados), '?'));

        $sentenciaDetalle = $pdo->prepare("DELETE FROM detalle_pedido WHERE id_pedido IN ({$marcadores})");
        $sentenciaDetalle->execute($pedidosCreados);

        $sentenciaPedido = $pdo->prepare("DELETE FROM pedido WHERE id_pedido IN ({$marcadores})");
        $sentenciaPedido->execute($pedidosCreados);
    }

    if (!empty($idCliente)) {
        $pdo->prepare('DELETE FROM direccion WHERE id_cliente = :id_cliente')->execute([':id_cliente' => $idCliente]);
        $pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id_cliente')->execute([':id_cliente' => $idCliente]);
    }

    if (file_exists($archivoCookies)) {
        unlink($archivoCookies);
    }
}

echo "\n=======================================================\n";
echo " RESULTADO HU5: {$exitos}/{$totalPruebas} pruebas exitosas\n";
echo "=======================================================\n\n";

exit($exitos === $totalPruebas ? 0 : 1);

<?php
/**
 * tests/prueba_aceptacion_hu6.php
 *
 * Script de Pruebas de Aceptación para la Historia de Usuario 6
 * (HU6 - Seguimiento de mi Pedido).
 *
 * Valida de forma automatizada:
 *  - CA-001: Despliegue del estado actual del pedido.
 *  - CA-002: Aislamiento del historial por cliente autenticado.
 *  - CA-003: Datos completos del pedido para la acción "Repetir pedido".
 *  - Cancelación del pedido por el cliente mientras no esté "En Proceso".
 *  - Actualización del estado visible al cambiar en la base de datos.
 *
 * Ejecución:
 *  docker exec mi_xampp_apache php /var/www/html/proyecto-tienda/tests/prueba_aceptacion_hu6.php
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/1_auth/api/ClienteModel.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$modeloCliente = new ClienteModel();

$baseUrl = 'http://localhost/proyecto-tienda';
$sufijo = time() . random_int(100, 999);
$archivoCookiesA = sys_get_temp_dir() . "/hu6_cookies_a_{$sufijo}.txt";
$archivoCookiesB = sys_get_temp_dir() . "/hu6_cookies_b_{$sufijo}.txt";

$exitos = 0;
$totalPruebas = 10;
$pedidosCreados = [];
$clientesCreados = [];

echo "\n=======================================================\n";
echo " INICIANDO PRUEBAS DE ACEPTACIÓN - HU6: MIS PEDIDOS\n";
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
 * Registra un cliente de prueba y devuelve sus datos.
 *
 * @return array{id_cliente: int, correo: string, contrasena: string}
 */
function crearClientePrueba(ClienteModel $modeloCliente, string $sufijo, string $etiqueta): array
{
    $correo = "hu6.{$etiqueta}.{$sufijo}@example.com";
    $contrasena = 'ClaveHU6Segura2026!';

    $idCliente = $modeloCliente->registrarCliente([
        'nombre'     => 'Cliente ' . $etiqueta,
        'apellidos'  => 'Prueba HU6 ' . $sufijo,
        'email'      => $correo,
        'telefono'   => '55' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        'contrasena' => $contrasena,
    ]);

    return [
        'id_cliente' => $idCliente,
        'correo'     => $correo,
        'contrasena' => $contrasena,
    ];
}

/**
 * Inicia sesión y guarda la cookie de sesión.
 *
 * @return array{0: int, 1: mixed, 2: string}
 */
function iniciarSesion(string $baseUrl, array $cliente, string $archivoCookies): array
{
    return peticionJson(
        $baseUrl . '/modules/1_auth/api/login.php',
        'POST',
        ['correo' => $cliente['correo'], 'contrasena' => $cliente['contrasena']],
        $archivoCookies,
        ['X-Requested-With: XMLHttpRequest']
    );
}

/**
 * Crea un pedido en línea autenticado.
 *
 * @return array{0: int, 1: mixed, 2: string}
 */
function crearPedidoEnLinea(string $baseUrl, string $archivoCookies, array $items): array
{
    $fechaEntrega = new DateTimeImmutable('+4 hours');
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
            'items'                         => $items,
            'tipo_entrega'                  => 'Recoger en local',
            'fecha_hora_entrega_solicitada' => $fechaEntrega->format('Y-m-d H:i:s'),
        ],
        $archivoCookies
    );
}

try {
    $productoUno = $pdo->query('SELECT id_producto FROM producto WHERE disponible = 1 ORDER BY id_producto LIMIT 1')->fetchColumn();
    $productoDos = $pdo->query('SELECT id_producto FROM producto WHERE disponible = 1 ORDER BY id_producto LIMIT 1 OFFSET 1')->fetchColumn();

    if (!$productoUno || !$productoDos) {
        throw new RuntimeException('No hay suficientes productos disponibles para ejecutar las pruebas HU6.');
    }

    // Clientes de prueba y sesiones independientes
    $clienteA = crearClientePrueba($modeloCliente, $sufijo, 'a');
    $clienteB = crearClientePrueba($modeloCliente, $sufijo, 'b');
    $clientesCreados[] = $clienteA['id_cliente'];
    $clientesCreados[] = $clienteB['id_cliente'];

    [$codigoLoginA, $respuestaLoginA, $crudoLoginA] = iniciarSesion($baseUrl, $clienteA, $archivoCookiesA);
    [$codigoLoginB, $respuestaLoginB, $crudoLoginB] = iniciarSesion($baseUrl, $clienteB, $archivoCookiesB);

    if ($codigoLoginA !== 200 || empty($respuestaLoginA['exito'])) {
        throw new RuntimeException("No se pudo iniciar sesión del cliente A: {$crudoLoginA}");
    }
    if ($codigoLoginB !== 200 || empty($respuestaLoginB['exito'])) {
        throw new RuntimeException("No se pudo iniciar sesión del cliente B: {$crudoLoginB}");
    }

    // Pedido del cliente A con dos productos
    [$codigoPedidoA, $respuestaPedidoA, $crudoPedidoA] = crearPedidoEnLinea($baseUrl, $archivoCookiesA, [
        ['id_producto' => (int)$productoUno, 'cantidad' => 1.5],
        ['id_producto' => (int)$productoDos, 'cantidad' => 2],
    ]);

    if ($codigoPedidoA !== 201 || empty($respuestaPedidoA['id_pedido'])) {
        throw new RuntimeException("No se pudo crear el pedido del cliente A: {$crudoPedidoA}");
    }
    $idPedidoA = (int)$respuestaPedidoA['id_pedido'];
    $pedidosCreados[] = $idPedidoA;

    // Pedido del cliente B
    [$codigoPedidoB, $respuestaPedidoB, $crudoPedidoB] = crearPedidoEnLinea($baseUrl, $archivoCookiesB, [
        ['id_producto' => (int)$productoDos, 'cantidad' => 1],
    ]);

    if ($codigoPedidoB !== 201 || empty($respuestaPedidoB['id_pedido'])) {
        throw new RuntimeException("No se pudo crear el pedido del cliente B: {$crudoPedidoB}");
    }
    $idPedidoB = (int)$respuestaPedidoB['id_pedido'];
    $pedidosCreados[] = $idPedidoB;

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 1: CA-002 (Solo se muestran los pedidos del cliente autenticado)
    // ─────────────────────────────────────────────────────────────────────────
    echo "[PRUEBA 1] Verificando CA-002: aislamiento del historial por cliente...\n";

    [$codigoListaA, $respuestaListaA, $crudoListaA] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/track_order.php',
        'GET',
        null,
        $archivoCookiesA
    );

    $idsVisiblesA = [];
    if ($codigoListaA === 200 && !empty($respuestaListaA['exito'])) {
        foreach (($respuestaListaA['pedidos'] ?? []) as $pedido) {
            $idsVisiblesA[] = (int)$pedido['id_pedido'];
        }
    }

    if (in_array($idPedidoA, $idsVisiblesA, true) && !in_array($idPedidoB, $idsVisiblesA, true)) {
        echo "  -> ÉXITO: El cliente A solo visualiza su propio pedido.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Pedidos visibles para A: " . json_encode($idsVisiblesA) . ". Respuesta: {$crudoListaA}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 2: CA-002 (Acceso directo a un pedido ajeno)
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 2] Verificando CA-002: bloqueo de acceso a un pedido ajeno...\n";

    [$codigoAjeno, $respuestaAjeno, $crudoAjeno] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/track_order.php?id_pedido=' . $idPedidoB,
        'GET',
        null,
        $archivoCookiesA
    );

    if ($codigoAjeno === 404 && empty($respuestaAjeno['exito'])) {
        echo "  -> ÉXITO: El pedido del cliente B no es accesible para el cliente A (HTTP 404).\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoAjeno}, Respuesta: {$crudoAjeno}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 3: CA-001 (Estado actual del pedido)
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 3] Verificando CA-001: estado actual del pedido...\n";

    $estadoPedidoA = null;
    if ($codigoListaA === 200 && !empty($respuestaListaA['exito'])) {
        foreach (($respuestaListaA['pedidos'] ?? []) as $pedido) {
            if ((int)$pedido['id_pedido'] === $idPedidoA) {
                $estadoPedidoA = $pedido['estado'];
                break;
            }
        }
    }

    if ($estadoPedidoA === 'Pendiente') {
        echo "  -> ÉXITO: Se muestra el estado actual 'Pendiente' del pedido.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Estado obtenido: " . var_export($estadoPedidoA, true) . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 4: CA-001 (Actualización del estado visible)
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 4] Verificando CA-001: actualización del estado en el seguimiento...\n";

    $pdo->prepare("UPDATE pedido SET estado = 'En Ruta' WHERE id_pedido = :id_pedido")
        ->execute([':id_pedido' => $idPedidoA]);

    [$codigoActualizado, $respuestaActualizado, $crudoActualizado] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/track_order.php?id_pedido=' . $idPedidoA,
        'GET',
        null,
        $archivoCookiesA
    );

    $estadoActualizado = $respuestaActualizado['pedido']['estado'] ?? null;

    if ($codigoActualizado === 200 && $estadoActualizado === 'En Ruta') {
        echo "  -> ÉXITO: El seguimiento refleja el nuevo estado 'En Ruta'.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoActualizado}, Respuesta: {$crudoActualizado}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 5: CA-003 (Datos completos para repetir el pedido)
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 5] Verificando CA-003: productos del pedido para 'Repetir pedido'...\n";

    $detalles = $respuestaActualizado['pedido']['detalles'] ?? [];
    $cantidades = [];
    foreach ($detalles as $detalle) {
        $cantidades[(int)$detalle['id_producto']] = (float)$detalle['cantidad'];
    }

    $detallesCompletos = count($detalles) === 2
        && ($cantidades[(int)$productoUno] ?? null) === 1.5
        && ($cantidades[(int)$productoDos] ?? null) === 2.0;

    if ($detallesCompletos) {
        echo "  -> ÉXITO: El detalle conserva productos y cantidades para repetir el pedido.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Detalle obtenido: " . json_encode($detalles, JSON_UNESCAPED_UNICODE) . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 6: Pantalla independiente "Mis pedidos"
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 6] Verificando la pantalla independiente 'Mis pedidos'...\n";

    $manejadorHtml = curl_init($baseUrl . '/?ruta=/mis-pedidos');
    curl_setopt_array($manejadorHtml, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE     => $archivoCookiesA,
        CURLOPT_COOKIEJAR      => $archivoCookiesA,
    ]);
    $htmlInterfaz = (string)curl_exec($manejadorHtml);
    $codigoHtml = (int)curl_getinfo($manejadorHtml, CURLINFO_HTTP_CODE);
    curl_close($manejadorHtml);

    if (
        $codigoHtml === 200
        && str_contains($htmlInterfaz, 'Seguimiento de mis Pedidos')
        && str_contains($htmlInterfaz, 'id="contenedor-mis-pedidos"')
        && str_contains($htmlInterfaz, 'trackingUI.js')
    ) {
        echo "  -> ÉXITO: La vista de pedidos incluye la sección 'Mis pedidos' y su script.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoHtml}: no se encontró la sección o el script de seguimiento.\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 7: Acceso sin sesión
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 7] Verificando rechazo de acceso sin sesión...\n";

    [$codigoSinSesion, $respuestaSinSesion, $crudoSinSesion] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/track_order.php'
    );

    if ($codigoSinSesion === 401 && empty($respuestaSinSesion['exito'])) {
        echo "  -> ÉXITO: El endpoint rechazó la consulta sin sesión (HTTP 401).\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoSinSesion}, Respuesta: {$crudoSinSesion}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 8: un cliente no puede cancelar el pedido de otro
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 8] Verificando que un cliente no pueda cancelar un pedido ajeno...\n";

    [$codigoCancelarAjeno, $respuestaCancelarAjeno, $crudoCancelarAjeno] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/cancel_client_order.php',
        'POST',
        ['id_pedido' => $idPedidoB],
        $archivoCookiesA,
        ['X-Requested-With: XMLHttpRequest']
    );

    $consultaPedidoB = $pdo->prepare('SELECT estado FROM pedido WHERE id_pedido = :id_pedido');
    $consultaPedidoB->execute([':id_pedido' => $idPedidoB]);

    if (
        $codigoCancelarAjeno === 404
        && empty($respuestaCancelarAjeno['exito'])
        && $consultaPedidoB->fetchColumn() === 'Pendiente'
    ) {
        echo "  -> ÉXITO: El pedido ajeno no se canceló (HTTP 404).\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoCancelarAjeno}, Respuesta: {$crudoCancelarAjeno}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 9: el cliente cancela su pedido pendiente
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 9] Verificando la cancelación de un pedido pendiente por el cliente...\n";

    [$codigoCancelarB, $respuestaCancelarB, $crudoCancelarB] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/cancel_client_order.php',
        'POST',
        ['id_pedido' => $idPedidoB],
        $archivoCookiesB,
        ['X-Requested-With: XMLHttpRequest']
    );

    $consultaCanceladoB = $pdo->prepare(
        'SELECT estado, motivo_rechazo, fecha_cancelacion FROM pedido WHERE id_pedido = :id_pedido'
    );
    $consultaCanceladoB->execute([':id_pedido' => $idPedidoB]);
    $pedidoCanceladoB = $consultaCanceladoB->fetch(PDO::FETCH_ASSOC);

    [, $respuestaSeguimientoB] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/track_order.php?id_pedido=' . $idPedidoB,
        'GET',
        null,
        $archivoCookiesB
    );

    if (
        $codigoCancelarB === 200
        && !empty($respuestaCancelarB['exito'])
        && $pedidoCanceladoB['estado'] === 'Cancelado'
        && $pedidoCanceladoB['motivo_rechazo'] === 'Cancelado por el cliente'
        && $pedidoCanceladoB['fecha_cancelacion'] !== null
        && ($respuestaSeguimientoB['pedido']['estado'] ?? null) === 'Cancelado'
    ) {
        echo "  -> ÉXITO: El cliente canceló su pedido y el seguimiento muestra 'Cancelado'.\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoCancelarB}, Respuesta: {$crudoCancelarB}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prueba 10: no se puede cancelar un pedido que ya está en proceso
    // ─────────────────────────────────────────────────────────────────────────
    echo "\n[PRUEBA 10] Verificando que no se pueda cancelar un pedido 'En Proceso'...\n";

    $pdo->prepare("UPDATE pedido SET estado = 'En Proceso' WHERE id_pedido = :id_pedido")
        ->execute([':id_pedido' => $idPedidoA]);

    [$codigoCancelarEnProceso, $respuestaCancelarEnProceso, $crudoCancelarEnProceso] = peticionJson(
        $baseUrl . '/modules/3_ecommerce/api/cancel_client_order.php',
        'POST',
        ['id_pedido' => $idPedidoA],
        $archivoCookiesA,
        ['X-Requested-With: XMLHttpRequest']
    );

    $consultaEstadoA = $pdo->prepare('SELECT estado FROM pedido WHERE id_pedido = :id_pedido');
    $consultaEstadoA->execute([':id_pedido' => $idPedidoA]);

    if (
        $codigoCancelarEnProceso === 409
        && empty($respuestaCancelarEnProceso['exito'])
        && str_contains((string)($respuestaCancelarEnProceso['mensaje'] ?? ''), 'proceso')
        && $consultaEstadoA->fetchColumn() === 'En Proceso'
    ) {
        echo "  -> ÉXITO: El pedido en proceso no se canceló (HTTP 409).\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoCancelarEnProceso}, Respuesta: {$crudoCancelarEnProceso}\n";
    }

} catch (Throwable $error) {
    echo "\n  -> ERROR GENERAL: " . $error->getMessage() . "\n";
} finally {
    // Limpieza de pedidos, detalles y clientes de prueba
    if (!empty($pedidosCreados)) {
        $marcadores = implode(',', array_fill(0, count($pedidosCreados), '?'));

        $pdo->prepare("DELETE FROM detalle_pedido WHERE id_pedido IN ({$marcadores})")
            ->execute($pedidosCreados);
        $pdo->prepare("DELETE FROM pedido WHERE id_pedido IN ({$marcadores})")
            ->execute($pedidosCreados);
    }

    foreach ($clientesCreados as $idCliente) {
        $pdo->prepare('DELETE FROM direccion WHERE id_cliente = :id_cliente')
            ->execute([':id_cliente' => $idCliente]);
        $pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id_cliente')
            ->execute([':id_cliente' => $idCliente]);
    }

    foreach ([$archivoCookiesA, $archivoCookiesB] as $archivoCookies) {
        if (file_exists($archivoCookies)) {
            unlink($archivoCookies);
        }
    }
}

echo "\n=======================================================\n";
echo " RESULTADO HU6: {$exitos}/{$totalPruebas} pruebas exitosas\n";
echo "=======================================================\n\n";

exit($exitos === $totalPruebas ? 0 : 1);

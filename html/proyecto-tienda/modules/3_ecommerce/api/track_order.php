<?php
/**
 * modules/3_ecommerce/api/track_order.php
 *
 * Endpoint de seguimiento de pedidos del cliente autenticado (HU6).
 *
 * Funcionalidad:
 *  - Devuelve el historial completo de pedidos del cliente en sesión.
 *  - Permite consultar un pedido específico con ?id_pedido=N para el detalle
 *    o para la acción "Repetir pedido".
 *  - Restringe estrictamente la consulta al id_cliente de la sesión, impidiendo
 *    que un cliente acceda a pedidos de otro (CA-002).
 *
 * Respuesta exitosa:
 *  { "exito": true, "pedidos": [...] }
 *  { "exito": true, "pedido": {...} }
 *
 * Restricciones (AGENTS.md):
 *  - Consultas preparadas con PDO.
 *  - Sin texto de contraseñas ni datos sensibles en la respuesta.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/EcommerceModel.php';

/**
 * Envía una respuesta JSON y detiene la ejecución.
 *
 * @param int   $codigoHttp Código HTTP de la respuesta.
 * @param array $cuerpo     Datos a codificar.
 * @return never
 */
function responderJson(int $codigoHttp, array $cuerpo): never
{
    http_response_code($codigoHttp);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Normaliza los datos de un pedido para consumo del frontend.
 *
 * @param array<string, mixed> $pedido Pedido obtenido del modelo.
 * @return array<string, mixed>
 */
function formatearPedido(array $pedido): array
{
    $pedido['id_pedido'] = (int)$pedido['id_pedido'];
    $pedido['numero_pedido'] = 'PED-' . str_pad((string)$pedido['id_pedido'], 6, '0', STR_PAD_LEFT);
    $pedido['total_pedido'] = (float)$pedido['total_pedido'];

    $partesDireccion = array_filter([
        $pedido['calle'] ?? null,
        $pedido['colonia'] ?? null,
        $pedido['municipio'] ?? null,
        $pedido['codigo_postal'] ?? null,
    ], static fn($parte): bool => $parte !== null && trim((string)$parte) !== '');

    $pedido['direccion_completa'] = implode(', ', array_map('trim', $partesDireccion));

    $detalles = [];
    foreach ($pedido['detalles'] ?? [] as $detalle) {
        $detalles[] = [
            'id_producto'     => (int)$detalle['id_producto'],
            'nombre'          => $detalle['nombre'],
            'unidad'          => $detalle['unidad'],
            'cantidad'        => (float)$detalle['cantidad'],
            'precio_unitario' => (float)$detalle['precio_unitario'],
            'subtotal'        => (float)$detalle['subtotal'],
        ];
    }
    $pedido['detalles'] = $detalles;

    return $pedido;
}

// ─────────────────────────────────────────────────────────────────────────────
// Validación de método, sesión y rol de cliente
// ─────────────────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderJson(405, ['exito' => false, 'mensaje' => 'Método HTTP no permitido']);
}

$rolSesion = strtolower((string)($_SESSION['rol'] ?? ''));
$esCliente = (($_SESSION['tipo_usuario'] ?? '') === 'cliente') || $rolSesion === 'cliente';

if (empty($_SESSION['id_usuario']) || !$esCliente) {
    $codigo = empty($_SESSION['id_usuario']) ? 401 : 403;
    responderJson($codigo, [
        'exito' => false,
        'mensaje' => $codigo === 401 ? 'Sesión no válida' : 'Acceso exclusivo para clientes',
    ]);
}

$idCliente = (int)($_SESSION['id_cliente'] ?? $_SESSION['id_usuario']);

try {
    $modeloEcommerce = new EcommerceModel();

    // Consulta de un pedido específico del cliente autenticado
    if (isset($_GET['id_pedido']) && $_GET['id_pedido'] !== '') {
        $idPedido = (int)$_GET['id_pedido'];
        if ($idPedido <= 0) {
            responderJson(400, ['exito' => false, 'mensaje' => 'Identificador de pedido inválido']);
        }

        $pedido = $modeloEcommerce->obtenerPedidoCliente($idPedido, $idCliente);
        if ($pedido === null) {
            responderJson(404, ['exito' => false, 'mensaje' => 'Pedido no encontrado']);
        }

        responderJson(200, [
            'exito'  => true,
            'pedido' => formatearPedido($pedido),
        ]);
    }

    // Historial completo del cliente
    $pedidos = $modeloEcommerce->obtenerPedidosCliente($idCliente);
    $pedidosFormateados = array_map('formatearPedido', $pedidos);

    responderJson(200, [
        'exito'   => true,
        'pedidos' => $pedidosFormateados,
    ]);

} catch (PDOException $error) {
    error_log('track_order.php — PDOException: ' . $error->getMessage());
    responderJson(500, ['exito' => false, 'mensaje' => 'Error al consultar los pedidos']);

} catch (Throwable $error) {
    error_log('track_order.php — Error: ' . $error->getMessage());
    responderJson(500, ['exito' => false, 'mensaje' => 'Error interno del servidor']);
}

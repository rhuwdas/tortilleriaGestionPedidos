<?php
/**
 * modules/3_ecommerce/api/cancel_client_order.php
 *
 * Endpoint para que el cliente cancele un pedido desde "Seguimiento de mis Pedidos".
 * Solo se permite mientras el pedido no haya salido a ruta (Pendiente o En Proceso).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require_once __DIR__ . '/EcommerceModel.php';

/**
 * Envía una respuesta JSON y detiene la ejecución.
 *
 * @return never
 */
function responderCancelacionCliente(bool $exito, string $mensaje, int $codigoHttp): never
{
    http_response_code($codigoHttp);
    echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderCancelacionCliente(false, 'Método HTTP no permitido', 405);
}

$rolSesion = strtolower((string)($_SESSION['rol'] ?? ''));
$esCliente = (($_SESSION['tipo_usuario'] ?? '') === 'cliente') || $rolSesion === 'cliente';

if (empty($_SESSION['id_usuario']) || !$esCliente) {
    $codigo = empty($_SESSION['id_usuario']) ? 401 : 403;
    responderCancelacionCliente(
        false,
        $codigo === 401 ? 'Sesión no válida' : 'Acceso exclusivo para clientes',
        $codigo
    );
}

$datosEntrada = json_decode(file_get_contents('php://input'), true);

if (!is_array($datosEntrada)) {
    responderCancelacionCliente(false, 'Datos inválidos', 400);
}

$idPedido = filter_var($datosEntrada['id_pedido'] ?? null, FILTER_VALIDATE_INT);
if (!$idPedido || $idPedido <= 0) {
    responderCancelacionCliente(false, 'Identificador de pedido inválido', 400);
}

$idCliente = (int)($_SESSION['id_cliente'] ?? $_SESSION['id_usuario']);

try {
    $modeloEcommerce = new EcommerceModel();

    if ($modeloEcommerce->cancelarPedidoCliente((int)$idPedido, $idCliente)) {
        responderCancelacionCliente(true, 'Pedido cancelado correctamente.', 200);
    }

    // Se consulta el pedido para devolver un mensaje claro sin exponer pedidos ajenos.
    $pedido = $modeloEcommerce->obtenerPedidoCliente((int)$idPedido, $idCliente);

    if ($pedido === null) {
        responderCancelacionCliente(false, 'Pedido no encontrado', 404);
    }

    $estado = (string)($pedido['estado'] ?? '');

    if ($estado === 'En Proceso') {
        responderCancelacionCliente(
            false,
            'El pedido ya está en proceso y no se puede cancelar.',
            409
        );
    }

    if ($estado === 'En Ruta') {
        responderCancelacionCliente(
            false,
            'El pedido ya está en ruta y no se puede cancelar.',
            409
        );
    }

    if ($estado === 'Entregado') {
        responderCancelacionCliente(false, 'El pedido ya fue entregado y no se puede cancelar.', 409);
    }

    if ($estado === 'Cancelado') {
        responderCancelacionCliente(false, 'El pedido ya fue cancelado.', 409);
    }

    responderCancelacionCliente(false, 'El pedido no se puede cancelar en su estado actual.', 409);
} catch (PDOException $error) {
    error_log('cancel_client_order.php — PDOException: ' . $error->getMessage());
    responderCancelacionCliente(false, 'No fue posible cancelar el pedido.', 500);
} catch (Throwable $error) {
    error_log('cancel_client_order.php — Throwable: ' . $error->getMessage());
    responderCancelacionCliente(false, 'No fue posible cancelar el pedido.', 500);
}

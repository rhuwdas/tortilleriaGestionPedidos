<?php
/**
 * modules/4_pos/api/manage_online_order.php
 * Acepta o cancela un pedido que todavía no ha sido cobrado (HU7 y HU8).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require_once __DIR__ . '/PosModel.php';

function responderGestionPedido(bool $exito, string $mensaje, int $codigo): never
{
    http_response_code($codigo);
    echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderGestionPedido(false, 'Método no permitido.', 405);
}

if (
    ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
    || ($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
    || !in_array($_SESSION['rol'] ?? '', ['Vendedor', 'Cajero'], true)
    || (int)($_SESSION['id_empleado'] ?? 0) <= 0
) {
    responderGestionPedido(false, 'No tienes permiso para gestionar pedidos.', 403);
}

$datos = json_decode(file_get_contents('php://input'), true);
$idPedido = filter_var($datos['id_pedido'] ?? null, FILTER_VALIDATE_INT);
$accion = $datos['accion'] ?? '';
$motivoEntrada = $datos['motivo'] ?? '';

if (!is_string($motivoEntrada)) {
    responderGestionPedido(false, 'El motivo debe ser texto.', 422);
}

$motivo = trim($motivoEntrada);
$esCancelacion = in_array($accion, ['cancelar', 'rechazar'], true);

if (!$idPedido || !in_array($accion, ['aceptar', 'cancelar', 'rechazar'], true)) {
    responderGestionPedido(false, 'Los datos del pedido no son válidos.', 400);
}

if ($esCancelacion && $motivo === '') {
    responderGestionPedido(false, 'El campo de motivo no puede estar vacío.', 422);
}

$caracteresMotivo = preg_match_all('/./us', $motivo, $coincidencias);
if ($caracteresMotivo === false || $caracteresMotivo > 255) {
    responderGestionPedido(false, 'El motivo no puede exceder 255 caracteres.', 422);
}

try {
    $modeloPos = new PosModel();
    $actualizado = $modeloPos->resolverPedidoNoPagado(
        (int)$idPedido,
        (int)$_SESSION['id_empleado'],
        $esCancelacion ? 'cancelar' : 'aceptar',
        $esCancelacion ? $motivo : null
    );

    if (!$actualizado) {
        responderGestionPedido(false, 'El pedido ya fue cobrado, atendido o no está disponible.', 409);
    }

    responderGestionPedido(
        true,
        $accion === 'aceptar' ? 'Pedido aceptado.' : 'Pedido cancelado.',
        200
    );
} catch (Throwable $error) {
    error_log('manage_online_order.php: ' . $error->getMessage());
    responderGestionPedido(false, 'No fue posible actualizar el pedido.', 500);
}
<?php
/** Confirma una entrega asignada al repartidor autenticado (HU10). */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/LogisticsModel.php';

function responderConfirmacionEntrega(bool $exito, string $mensaje, int $codigo = 200): never
{
	http_response_code($codigo);
	echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	responderConfirmacionEntrega(false, 'Método no permitido.', 405);
}
if (
	($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
	|| empty($_SESSION['id_usuario'])
	|| ($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
	|| ($_SESSION['rol'] ?? '') !== 'Repartidor'
	|| (int)($_SESSION['id_repartidor'] ?? 0) <= 0
) {
	responderConfirmacionEntrega(false, 'No tienes permiso para confirmar esta entrega.', 403);
}

$datos = json_decode(file_get_contents('php://input'), true);
$idPedido = filter_var($datos['id_pedido'] ?? null, FILTER_VALIDATE_INT);
if (!$idPedido || $idPedido <= 0) {
	responderConfirmacionEntrega(false, 'Identificador de pedido inválido.', 422);
}

try {
	$modeloLogistica = new LogisticsModel();
	if (!$modeloLogistica->confirmarEntrega((int)$idPedido, (int)$_SESSION['id_repartidor'])) {
		responderConfirmacionEntrega(false, 'La entrega no está asignada a tu ruta o ya fue confirmada.', 409);
	}
	responderConfirmacionEntrega(true, 'Entrega confirmada.');
} catch (Throwable $error) {
	error_log('confirm_delivery.php: ' . $error->getMessage());
	responderConfirmacionEntrega(false, 'No fue posible confirmar la entrega.', 500);
}
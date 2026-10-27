<?php
/**
 * modules/4_pos/api/receive_online_orders.php
 * Lista pedidos pendientes de cualquier canal para el panel de ventas (HU7/HU8).
 */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require_once __DIR__ . '/PosModel.php';

function responderRecepcion(bool $exito, string $mensaje = '', array $datos = [], int $codigo = 200): never
{
	http_response_code($codigo);
	echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	responderRecepcion(false, 'Método no permitido.', [], 405);
}

if (
	($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
	|| ($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
	|| !in_array($_SESSION['rol'] ?? '', ['Vendedor', 'Cajero'], true)
	|| (int)($_SESSION['id_empleado'] ?? 0) <= 0
) {
	responderRecepcion(false, 'No tienes permiso para consultar esta bandeja.', [], 403);
}

try {
	$modeloPos = new PosModel();
	responderRecepcion(true, '', ['pedidos' => $modeloPos->obtenerPedidosPendientes()]);
} catch (Throwable $error) {
	error_log('receive_online_orders.php: ' . $error->getMessage());
	responderRecepcion(false, 'No fue posible consultar los pedidos.', [], 500);
}

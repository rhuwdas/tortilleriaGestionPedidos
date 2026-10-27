<?php
/** Consulta detalle de cobro y registra pago en efectivo (HU9). */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../6_cash/api/CashModel.php';

function responderPago(bool $exito, string $mensaje, array $datos = [], int $codigo = 200): never
{
	http_response_code($codigo);
	echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
	exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
	responderPago(false, 'Método no permitido.', [], 405);
}
if (
	($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
	|| ($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
	|| !in_array($_SESSION['rol'] ?? '', ['Vendedor', 'Cajero'], true)
	|| (int)($_SESSION['id_empleado'] ?? 0) <= 0
) {
	responderPago(false, 'No tienes permiso para procesar cobros.', [], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$idPedido = filter_var($_GET['id_pedido'] ?? null, FILTER_VALIDATE_INT);
	if (!$idPedido || $idPedido <= 0) {
		responderPago(false, 'Identificador de pedido inválido.', [], 400);
	}

	try {
		$modeloCaja = new CashModel();
		$pedido = $modeloCaja->obtenerPedidoParaCobro((int)$idPedido);
		if ($pedido === null) {
			responderPago(false, 'Pedido no encontrado.', [], 404);
		}
		if ($pedido['pagado']) {
			responderPago(false, 'El pedido ya fue cobrado; no se puede modificar ni volver a cobrar.', [], 409);
		}
		if (!in_array($pedido['estado'], ['Pendiente', 'En Proceso'], true) || $pedido['detalles'] === []) {
			responderPago(false, 'El pedido no está disponible para cobro.', [], 409);
		}
		responderPago(true, '', ['pedido' => $pedido]);
	} catch (Throwable $error) {
		error_log('process_payment.php GET: ' . $error->getMessage());
		responderPago(false, 'No fue posible consultar el pedido.', [], 500);
	}
}

$datos = json_decode(file_get_contents('php://input'), true);
$idPedido = filter_var($datos['id_pedido'] ?? null, FILTER_VALIDATE_INT);
$efectivoEntrada = $datos['efectivo_recibido'] ?? null;
if (!$idPedido || $idPedido <= 0 || !is_numeric($efectivoEntrada) || !is_finite((float)$efectivoEntrada) || (float)$efectivoEntrada < 0) {
	responderPago(false, 'El pedido y el efectivo recibido deben ser válidos.', [], 422);
}

try {
	$modeloCaja = new CashModel();
	$ticket = $modeloCaja->cobrarPedido(
		(int)$idPedido,
		(int)$_SESSION['id_empleado'],
		(float)$efectivoEntrada
	);
	$ticket['numero_venta'] = 'VTA-' . str_pad((string)$ticket['id_venta'], 6, '0', STR_PAD_LEFT);
	$ticket['numero_pedido'] = 'PED-' . str_pad((string)$ticket['id_pedido'], 6, '0', STR_PAD_LEFT);
	responderPago(true, 'Pago registrado correctamente.', ['ticket' => $ticket], 201);
} catch (EfectivoInsuficienteException $error) {
	$faltante = round($error->total - $error->efectivoRecibido, 2);
	responderPago(false, 'Efectivo insuficiente. Faltan $' . number_format($faltante, 2) . '.', [
		'total' => $error->total,
		'efectivo_recibido' => $error->efectivoRecibido,
		'faltante' => $faltante,
	], 422);
} catch (PedidoYaCobradoException $error) {
	responderPago(false, $error->getMessage(), [], 409);
} catch (InvalidArgumentException $error) {
	responderPago(false, $error->getMessage(), [], 422);
} catch (Throwable $error) {
	error_log('process_payment.php POST: ' . $error->getMessage());
	responderPago(false, 'No fue posible registrar el pago.', [], 500);
}

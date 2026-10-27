<?php
/** Registra pedidos de mostrador o teléfono en la bandeja central (HU8). */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/PosModel.php';
require_once __DIR__ . '/../../../core/MunicipiosCobertura.php';

function responderCrearPedidoPos(bool $exito, string $mensaje, array $datos = [], int $codigo = 200): never
{
	http_response_code($codigo);
	echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	responderCrearPedidoPos(false, 'Método no permitido.', [], 405);
}

if (
	($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
	|| ($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
	|| !in_array($_SESSION['rol'] ?? '', ['Vendedor', 'Cajero'], true)
	|| (int)($_SESSION['id_empleado'] ?? 0) <= 0
) {
	responderCrearPedidoPos(false, 'No tienes permiso para registrar pedidos.', [], 403);
}

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
	responderCrearPedidoPos(false, 'Los datos del pedido no son válidos.', [], 400);
}

$canal = $datos['canal'] ?? '';
$tipoEntrega = $datos['tipo_entrega'] ?? '';
$items = $datos['items'] ?? [];
$idCliente = filter_var($datos['id_cliente'] ?? null, FILTER_VALIDATE_INT);
$clienteNuevo = $datos['cliente_nuevo'] ?? null;
$fechaEntrega = $datos['fecha_hora_entrega_solicitada'] ?? '';
$direccion = $datos['direccion'] ?? null;

if (!in_array($canal, ['Mostrador', 'Teléfono'], true)) {
	responderCrearPedidoPos(false, 'Selecciona Mostrador o Teléfono como canal del pedido.', [], 422);
}
if (!in_array($tipoEntrega, ['Domicilio', 'Recoger en local'], true)) {
	responderCrearPedidoPos(false, 'Selecciona un tipo de entrega válido.', [], 422);
}
if (
	$tipoEntrega === 'Domicilio'
	&& (!is_array($direccion) || trim((string)($direccion['calle'] ?? '')) === '' || trim((string)($direccion['municipio'] ?? '')) === '')
) {
	responderCrearPedidoPos(false, 'Para entrega a domicilio captura la calle y selecciona el municipio.', [], 422);
}
if (
	$tipoEntrega === 'Domicilio'
	&& is_array($direccion)
	&& !municipioTieneCobertura((string)($direccion['municipio'] ?? ''))
) {
	responderCrearPedidoPos(false, 'El municipio está fuera de la cobertura de entrega (solo municipios cercanos a Xoxocotlán, Oaxaca).', [], 422);
}
if (!is_array($items) || $items === []) {
	responderCrearPedidoPos(false, 'Agrega al menos un producto al pedido.', [], 422);
}
if ((!$idCliente || $idCliente <= 0) && !is_array($clienteNuevo)) {
	responderCrearPedidoPos(false, 'Selecciona un cliente o captura sus datos.', [], 422);
}
if (is_array($clienteNuevo)) {
	$nombreCliente = trim((string)($clienteNuevo['nombre'] ?? ''));
	$apellidosCliente = trim((string)($clienteNuevo['apellidos'] ?? ''));
	if ($nombreCliente === '' || $apellidosCliente === '') {
		responderCrearPedidoPos(false, 'El nombre y los apellidos del cliente son obligatorios.', [], 422);
	}
	$longitudNombre = preg_match_all('/./us', $nombreCliente, $coincidenciasNombre);
	$longitudApellidos = preg_match_all('/./us', $apellidosCliente, $coincidenciasApellidos);
	if ($longitudNombre === false || $longitudApellidos === false || $longitudNombre > 150 || $longitudApellidos > 150) {
		responderCrearPedidoPos(false, 'El nombre del cliente excede el límite permitido.', [], 422);
	}
}
if (!is_string($fechaEntrega) || trim($fechaEntrega) === '') {
	responderCrearPedidoPos(false, 'Selecciona fecha y hora de entrega.', [], 422);
}

$fechaInterpretada = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $fechaEntrega);
$erroresFecha = DateTimeImmutable::getLastErrors();
if (
	$fechaInterpretada === false
	|| ($erroresFecha !== false && ($erroresFecha['warning_count'] > 0 || $erroresFecha['error_count'] > 0))
	|| $fechaInterpretada <= new DateTimeImmutable()
) {
	responderCrearPedidoPos(false, 'La fecha y hora de entrega debe ser futura.', [], 422);
}
$minutosEntrega = ((int)$fechaInterpretada->format('H') * 60) + (int)$fechaInterpretada->format('i');
if ($minutosEntrega < 420 || $minutosEntrega > 1020) {
	responderCrearPedidoPos(false, 'La hora de entrega debe estar dentro del horario de 07:00 a 17:00.', [], 422);
}

try {
	$modeloPos = new PosModel();
	$idPedido = $modeloPos->crearPedidoPresencial([
		'id_cliente' => $idCliente ?: null,
		'cliente_nuevo' => $clienteNuevo,
		'items' => $items,
		'canal' => $canal,
		'tipo_entrega' => $tipoEntrega,
		'direccion' => $direccion,
		'fecha_hora_entrega_solicitada' => $fechaInterpretada->format('Y-m-d H:i:s'),
	], (int)$_SESSION['id_empleado']);

	responderCrearPedidoPos(true, 'Pedido registrado en la bandeja.', [
		'id_pedido' => $idPedido,
		'numero_pedido' => 'PED-' . str_pad((string)$idPedido, 6, '0', STR_PAD_LEFT),
	], 201);
} catch (ExistenciasInsuficientesException $error) {
	error_log('create_pos_order.php — inventario insuficiente: ' . $error->getMessage());
	responderCrearPedidoPos(false, EcommerceModel::MENSAJE_INVENTARIO_INSUFICIENTE, [], 422);
} catch (InvalidArgumentException $error) {
	responderCrearPedidoPos(false, $error->getMessage(), [], 422);
} catch (Throwable $error) {
	error_log('create_pos_order.php: ' . $error->getMessage());
	responderCrearPedidoPos(false, 'No fue posible registrar el pedido.', [], 500);
}

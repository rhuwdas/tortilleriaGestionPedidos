<?php
/** Devuelve únicamente la ruta del repartidor autenticado (HU10). */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/LogisticsModel.php';

function responderRutaEntrega(bool $exito, array $datos = [], string $mensaje = '', int $codigo = 200): never
{
	http_response_code($codigo);
	echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	responderRutaEntrega(false, [], 'Método no permitido.', 405);
}
if (empty($_SESSION['id_usuario'])) {
	responderRutaEntrega(false, [], 'Sesión no válida.', 401);
}
if (
	($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
	|| ($_SESSION['rol'] ?? '') !== 'Repartidor'
	|| (int)($_SESSION['id_repartidor'] ?? 0) <= 0
) {
	responderRutaEntrega(false, [], 'Acceso exclusivo para repartidores.', 403);
}

try {
	$modeloLogistica = new LogisticsModel();
	$entregas = $modeloLogistica->obtenerEntregasAsignadas((int)$_SESSION['id_repartidor']);
	foreach ($entregas as &$entrega) {
		$entrega['id_pedido'] = (int)$entrega['id_pedido'];
		$entrega['numero_pedido'] = 'PED-' . str_pad((string)$entrega['id_pedido'], 6, '0', STR_PAD_LEFT);
		$entrega['direccion_completa'] = implode(', ', array_filter([
			$entrega['calle'],
			$entrega['colonia'],
			$entrega['municipio'],
			$entrega['codigo_postal'],
		]));
	}
	unset($entrega);
	responderRutaEntrega(true, ['entregas' => $entregas]);
} catch (Throwable $error) {
	error_log('get_delivery_route.php: ' . $error->getMessage());
	responderRutaEntrega(false, [], 'No fue posible consultar la ruta.', 500);
}
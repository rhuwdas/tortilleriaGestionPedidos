<?php
/** Busca clientes registrados para captura rápida de pedidos HU8. */

if (session_status() === PHP_SESSION_NONE) {
	 session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/PosModel.php';

function responderBusquedaClientes(bool $exito, array $datos = [], string $mensaje = '', int $codigo = 200): never
{
	http_response_code($codigo);
	echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	responderBusquedaClientes(false, [], 'Método no permitido.', 405);
}

if (
	($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
	|| ($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
	|| !in_array($_SESSION['rol'] ?? '', ['Vendedor', 'Cajero'], true)
	|| (int)($_SESSION['id_empleado'] ?? 0) <= 0
) {
	responderBusquedaClientes(false, [], 'No tienes permiso para buscar clientes.', 403);
}

$termino = trim((string)($_GET['termino'] ?? ''));
$longitudTermino = preg_match_all('/./us', $termino, $coincidencias);
if ($longitudTermino === false || $longitudTermino < 2) {
	responderBusquedaClientes(true, ['clientes' => []]);
}

try {
	$modeloPos = new PosModel();
	responderBusquedaClientes(true, ['clientes' => $modeloPos->buscarClientes($termino)]);
} catch (Throwable $error) {
	error_log('search_pos_customers.php: ' . $error->getMessage());
	responderBusquedaClientes(false, [], 'No fue posible buscar clientes.', 500);
}
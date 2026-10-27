<?php
/**
 * modules/3_ecommerce/api/create_online_order.php
 * 
 * Endpoint para crear pedido en línea (HU5)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/EcommerceModel.php';
require_once __DIR__ . '/../../1_auth/api/ClienteModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método HTTP no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idCliente = $_SESSION['id_cliente'] ?? $_SESSION['id_usuario'] ?? null;
if (!$idCliente) {
    http_response_code(401);
    echo json_encode(['exito' => false, 'mensaje' => 'Sesión no válida'], JSON_UNESCAPED_UNICODE);
    exit;
}

$contenidoEntrada = file_get_contents('php://input');
$datosEntrada = json_decode($contenidoEntrada, true);

if (!is_array($datosEntrada)) {
    echo json_encode(['exito' => false, 'mensaje' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = $datosEntrada['items'] ?? [];
$tipoEntrega = $datosEntrada['tipo_entrega'] ?? 'Domicilio';
$fechaHoraEntrega = $datosEntrada['fecha_hora_entrega_solicitada'] ?? date('Y-m-d H:i:s', strtotime('+2 hours'));
$direccion = $datosEntrada['direccion'] ?? null;
$idDireccionGuardada = $datosEntrada['id_direccion'] ?? null;

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'El carrito está vacío'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $modeloEcommerce = new EcommerceModel();
    
    // Validar existencias
    $validacion = $modeloEcommerce->validarExistencias($items);
    if (!$validacion['valido']) {
        http_response_code(422);
        echo json_encode([
            'exito' => false, 
            'mensaje' => implode(', ', $validacion['errores'])
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $idDireccion = null;
    
    // Manejar dirección para entrega a domicilio
    if ($tipoEntrega === 'Domicilio') {
        if ($idDireccionGuardada && $idDireccionGuardada !== 'nueva') {
            $idDireccion = (int)$idDireccionGuardada;
        } elseif ($direccion && !empty($direccion['calle'])) {
            // Guardar nueva dirección
            $modeloCliente = new ClienteModel();
            $idDireccion = $modeloEcommerce->guardarDireccionPedido((int)$idCliente, $direccion);
        } else {
            // Verificar si tiene direcciones guardadas
            $modeloCliente = new ClienteModel();
            $perfil = $modeloCliente->obtenerPerfilCliente((int)$idCliente);
            if (empty($perfil['direcciones'])) {
                http_response_code(422);
                echo json_encode([
                    'exito' => false, 
                    'mensaje' => 'Se requiere una dirección de entrega para pedidos a domicilio'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $idDireccion = $perfil['direcciones'][0]['id_direccion'];
        }
    }
    
    // Crear pedido
    $idPedido = $modeloEcommerce->crearPedidoOnline([
        'id_cliente' => (int)$idCliente,
        'items' => $items,
        'tipo_entrega' => $tipoEntrega,
        'fecha_hora_entrega_solicitada' => date('Y-m-d H:i:s', strtotime($fechaHoraEntrega)),
        'id_direccion' => $idDireccion
    ]);
    
    http_response_code(201);
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Pedido creado exitosamente',
        'id_pedido' => $idPedido,
        'numero_pedido' => 'PED-' . str_pad($idPedido, 6, '0', STR_PAD_LEFT)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $error) {
    error_log('Error al crear pedido: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al procesar el pedido'
    ], JSON_UNESCAPED_UNICODE);
}

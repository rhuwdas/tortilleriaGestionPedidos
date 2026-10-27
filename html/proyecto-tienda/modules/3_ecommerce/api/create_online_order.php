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
$fechaHoraEntrada = $datosEntrada['fecha_hora_entrega_solicitada'] ?? '';
$direccion = $datosEntrada['direccion'] ?? null;
$idDireccionGuardada = $datosEntrada['id_direccion'] ?? null;

if (!is_string($fechaHoraEntrada)) {
    http_response_code(422);
    echo json_encode(['exito' => false, 'mensaje' => 'La fecha y hora de entrega no son válidas'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (trim($fechaHoraEntrada) === '') {
    $fechaHoraEntrega = new DateTimeImmutable('+2 hours');
    $horaPredeterminada = (int)$fechaHoraEntrega->format('Hi');
    if ($horaPredeterminada < 700) {
        $fechaHoraEntrega = $fechaHoraEntrega->setTime(10, 0);
    } elseif ($horaPredeterminada > 1700) {
        $fechaHoraEntrega = $fechaHoraEntrega->modify('+1 day')->setTime(10, 0);
    }
} else {
    $fechaHoraEntrada = trim($fechaHoraEntrada);
    $fechaHoraEntrega = null;
    foreach (['!Y-m-d\\TH:i', '!Y-m-d H:i:s', '!Y-m-d H:i'] as $formatoFecha) {
        $fechaInterpretada = DateTimeImmutable::createFromFormat($formatoFecha, $fechaHoraEntrada);
        $erroresFecha = DateTimeImmutable::getLastErrors();
        if (
            $fechaInterpretada !== false
            && ($erroresFecha === false || ($erroresFecha['warning_count'] === 0 && $erroresFecha['error_count'] === 0))
        ) {
            $fechaHoraEntrega = $fechaInterpretada;
            break;
        }
    }
}

if (!$fechaHoraEntrega || $fechaHoraEntrega <= new DateTimeImmutable()) {
    http_response_code(422);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Selecciona una fecha y hora de entrega futura entre 07:00 y 17:00.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$minutosEntrega = ((int)$fechaHoraEntrega->format('H') * 60) + (int)$fechaHoraEntrega->format('i');
if ($minutosEntrega < 420 || $minutosEntrega > 1020) {
    http_response_code(422);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'La hora de entrega debe estar dentro del horario de 07:00 a 17:00.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'El carrito está vacío'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($tipoEntrega, ['Domicilio', 'Recoger en local'], true)) {
    http_response_code(422);
    echo json_encode(['exito' => false, 'mensaje' => 'Tipo de entrega no válido'], JSON_UNESCAPED_UNICODE);
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
        'fecha_hora_entrega_solicitada' => $fechaHoraEntrega->format('Y-m-d H:i:s'),
        'id_direccion' => $idDireccion
    ]);
    
    http_response_code(201);
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Pedido creado exitosamente',
        'id_pedido' => $idPedido,
        'numero_pedido' => 'PED-' . str_pad($idPedido, 6, '0', STR_PAD_LEFT)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (ExistenciasInsuficientesException $error) {
    // El inventario cambió entre la pre-validación y la confirmación transaccional
    http_response_code(422);
    echo json_encode([
        'exito' => false,
        'mensaje' => implode(' ', $error->obtenerErrores())
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $error) {
    error_log('Error al crear pedido: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al procesar el pedido'
    ], JSON_UNESCAPED_UNICODE);
}

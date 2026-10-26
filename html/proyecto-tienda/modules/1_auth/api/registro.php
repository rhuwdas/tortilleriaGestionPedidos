<?php
/**
 * modules/1_auth/api/registro.php
 *
 * Endpoint backend para el registro de nuevos clientes (HU4).
 * Responsabilidades:
 *  - Procesar peticiones POST con los datos del formulario de registro.
 *  - Validar obligatoriedad y formato de: nombre, apellidos, correo, teléfono y contraseña.
 *  - Verificar que el correo electrónico sea único en la base de datos (CA-HU4-001).
 *  - Restringir estrictamente el teléfono a 10 dígitos numéricos (CA-HU4-002).
 *  - Persistir el cliente con hash seguro de contraseña (password_hash).
 *  - Registrar la dirección de entrega opcional vinculada al cliente (CA-003).
 *  - Iniciar la sesión del cliente automáticamente y devolver respuesta JSON estandarizada.
 *
 * Cumple con agents.md:
 *  - Consultas parametrizadas con PDO en ClienteModel.
 *  - Sin contraseñas en texto plano.
 *  - Nombres completos en español.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/ClienteModel.php';

// Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Método HTTP no permitido. Se requiere POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener los datos entrantes (soporta tanto JSON como multipart/x-www-form-urlencoded)
$contenidoEntrada = file_get_contents('php://input');
$datosEntrada = json_decode($contenidoEntrada, true);

if (!is_array($datosEntrada)) {
    $datosEntrada = $_POST;
}

// Extracción y saneamiento básico de datos
$nombre = trim($datosEntrada['nombre'] ?? '');
$apellidos = trim($datosEntrada['apellidos'] ?? '');
$email = trim($datosEntrada['email'] ?? '');
$telefono = trim($datosEntrada['telefono'] ?? '');
$contrasena = $datosEntrada['contrasena'] ?? '';

// Datos de dirección opcional
$calle = trim($datosEntrada['calle'] ?? '');
$colonia = trim($datosEntrada['colonia'] ?? '');
$municipio = trim($datosEntrada['municipio'] ?? '');
$codigoPostal = trim($datosEntrada['codigo_postal'] ?? '');
$referencia = trim($datosEntrada['referencia'] ?? '');

// ─────────────────────────────────────────────────────────────────────────────
// 1. Validaciones de presencia y obligatoriedad
// ─────────────────────────────────────────────────────────────────────────────
if ($nombre === '' || $apellidos === '' || $email === '' || $telefono === '' || $contrasena === '') {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Por favor, completa todos los campos obligatorios.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. Validación de formato de teléfono (estrictamente 10 dígitos) (CA-HU4-002)
// ─────────────────────────────────────────────────────────────────────────────
if (!preg_match('/^\d{10}$/', $telefono)) {
    http_response_code(422);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El teléfono debe contener 10 dígitos.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. Validación de formato de correo electrónico
// ─────────────────────────────────────────────────────────────────────────────
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El correo electrónico no tiene un formato válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 3.5 Validación de cobertura de dirección de entrega (flujo alterno)
// ─────────────────────────────────────────────────────────────────────────────
if ($calle !== '' && $municipio !== '') {
    $municipiosCobertura = ['oaxaca', 'toluca', 'metepec', 'zinacantepec', 'san mateo atenco', 'lerma', 'centro'];
    $municipioNormalizado = mb_strtolower(trim($municipio), 'UTF-8');
    if (!in_array($municipioNormalizado, $municipiosCobertura, true)) {
        http_response_code(422);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'La dirección está fuera de alcance.',
            'codigo' => 'DIRECCION_FUERA_DE_ALCANCE'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. Verificación de unicidad de correo electrónico en BD (CA-HU4-001)
// ─────────────────────────────────────────────────────────────────────────────
try {
    $modeloCliente = new ClienteModel();

    if ($modeloCliente->existeCorreo($email)) {
        http_response_code(409);
        echo json_encode([
            'exito' => false,
            'mensaje' => 'El correo ingresado ya se encuentra registrado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Preparar datos de dirección si al menos se proporcionó la calle
    $datosDireccion = null;
    if ($calle !== '') {
        $datosDireccion = [
            'calle' => $calle,
            'colonia' => $colonia,
            'municipio' => $municipio,
            'codigo_postal' => $codigoPostal,
            'referencia' => $referencia
        ];
    }

    // Registrar cliente y dirección en la base de datos
    $idCliente = $modeloCliente->registrarCliente([
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'email' => $email,
        'telefono' => $telefono,
        'contrasena' => $contrasena
    ], $datosDireccion);

    // Iniciar sesión automática del cliente (CA-003)
    $_SESSION['id_cliente'] = $idCliente;
    $_SESSION['id_usuario'] = $idCliente;
    $_SESSION['nombre_usuario'] = $nombre . ' ' . $apellidos;
    $_SESSION['email_usuario'] = $email;
    $_SESSION['rol'] = 'Cliente';

    // Obtener el perfil recién creado y su dirección asociada
    $perfilCreado = $modeloCliente->obtenerPerfilCliente($idCliente);

    http_response_code(201);
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Registro completado con éxito.',
        'id_cliente' => $idCliente,
        'perfil' => $perfilCreado,
        'redireccion' => '/proyecto-tienda/modules/2_catalog/ui/catalogo.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $error) {
    error_log('Error en endpoint de registro: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Ocurrió un error en el servidor al procesar el registro.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

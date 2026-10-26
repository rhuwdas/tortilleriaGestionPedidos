<?php
/**
 * tests/prueba_aceptacion_hu4.php
 *
 * Script de Pruebas de Aceptación para la Historia de Usuario 4 (HU4 - Registro de Cliente).
 * Valida de forma automatizada los criterios de aceptación:
 *  - CA-001 / CA-HU4-001: Validación estricta de correo único.
 *  - CA-002 / CA-HU4-002: Restricción estricta de teléfono a 10 dígitos.
 *  - CA-003: Creación automática de perfil con dirección asociada y hash seguro.
 *
 * Ejecución:
 *  docker exec mi_xampp_apache php /var/www/html/proyecto-tienda/tests/prueba_aceptacion_hu4.php
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/1_auth/api/ClienteModel.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$modelo = new ClienteModel();

$exitos = 0;
$totalPruebas = 3;

echo "\n=======================================================\n";
echo " INICIANDO PRUEBAS DE ACEPTACIÓN - HU4: REGISTRO CLIENTE\n";
echo "=======================================================\n\n";

// ─────────────────────────────────────────────────────────────────────────────
// Prueba 1: CA-001 (Validación de correo único)
// ─────────────────────────────────────────────────────────────────────────────
echo "[PRUEBA 1] Verificando CA-001: Unicidad de correo electrónico...\n";

// El correo 'juan@email.com' ya existe en la base de datos precargada
$correoExistente = 'juan@email.com';
$existeEnModelo = $modelo->existeCorreo($correoExistente);

if ($existeEnModelo) {
    // Probar el endpoint simulando petición POST
    $ch = curl_init('http://localhost/proyecto-tienda/modules/1_auth/api/registro.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'nombre' => 'Juan Duplicado',
        'apellidos' => 'Prueba',
        'email' => $correoExistente,
        'telefono' => '7221112233',
        'contrasena' => 'secreto123'
    ]));
    $respuesta = curl_exec($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $datos = json_decode($respuesta, true);

    if ($codigoHttp === 409 && isset($datos['mensaje']) && str_contains($datos['mensaje'], 'El correo ingresado ya se encuentra registrado')) {
        echo "  -> ÉXITO: El sistema rechazó el correo duplicado con HTTP 409 y mensaje: '{$datos['mensaje']}'\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Código HTTP {$codigoHttp}, Respuesta: {$respuesta}\n";
    }
} else {
    echo "  -> ERROR PREVIO: '{$correoExistente}' no se encontró en la BD inicial.\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// Prueba 2: CA-002 (Teléfono restringido a 10 dígitos)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n[PRUEBA 2] Verificando CA-002: Validación de teléfono de 10 dígitos...\n";

$ch = curl_init('http://localhost/proyecto-tienda/modules/1_auth/api/registro.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'nombre' => 'Teléfono',
    'apellidos' => 'Inválido',
    'email' => 'telefono.invalido@example.com',
    'telefono' => '12345', // Inválido: solo 5 dígitos
    'contrasena' => 'secreto123'
]));
$respuesta = curl_exec($ch);
$codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$datos = json_decode($respuesta, true);

if ($codigoHttp === 422 && isset($datos['mensaje']) && str_contains($datos['mensaje'], 'El teléfono debe contener 10 dígitos')) {
    echo "  -> ÉXITO: El sistema rechazó el teléfono de longitud inválida con HTTP 422 y mensaje: '{$datos['mensaje']}'\n";
    $exitos++;
} else {
    echo "  -> FALLO: Código HTTP {$codigoHttp}, Respuesta: {$respuesta}\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// Prueba 3: CA-003 (Creación automática del perfil y dirección guardada)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n[PRUEBA 3] Verificando CA-003: Creación de perfil y dirección guardada...\n";

$correoNuevo = 'cliente.aceptacion.' . time() . '@example.com';
$telefonoNuevo = '7229988776';

$ch = curl_init('http://localhost/proyecto-tienda/modules/1_auth/api/registro.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'nombre' => 'Roberto',
    'apellidos' => 'Hernández',
    'email' => $correoNuevo,
    'telefono' => $telefonoNuevo,
    'contrasena' => 'ClaveSegura2026!',
    'calle' => 'Av. Independencia 101',
    'colonia' => 'Centro',
    'municipio' => 'Oaxaca',
    'codigo_postal' => '68000',
    'referencia' => 'Frente al parque'
]));
$respuesta = curl_exec($ch);
$codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$datos = json_decode($respuesta, true);

if ($codigoHttp === 201 && !empty($datos['id_cliente'])) {
    $idCliente = (int)$datos['id_cliente'];

    // Verificar en la BD la existencia del cliente, la contraseña encriptada y la dirección
    $perfil = $modelo->obtenerPerfilCliente($idCliente);
    $sentenciaPass = $pdo->prepare('SELECT password_hash FROM cliente WHERE id_cliente = :id');
    $sentenciaPass->execute([':id' => $idCliente]);
    $hashBd = $sentenciaPass->fetchColumn();

    $contrasenaEncriptada = !empty($hashBd) && password_verify('ClaveSegura2026!', $hashBd);
    $direccionGuardada = !empty($perfil['direcciones']) && $perfil['direcciones'][0]['calle'] === 'Av. Independencia 101';

    if ($contrasenaEncriptada && $direccionGuardada) {
        echo "  -> ÉXITO: Cliente registrado (ID: {$idCliente}) con contraseña encriptada (BCRYPT) y dirección guardada.\n";
        echo "     Perfil verificado: Nombre: {$perfil['nombre']} {$perfil['apellidos']}, Email: {$perfil['email']}\n";
        echo "     Dirección vinculada: {$perfil['direcciones'][0]['calle']}, Col. {$perfil['direcciones'][0]['colonia']}\n";
        $exitos++;
    } else {
        echo "  -> FALLO: Falló la verificación en base de datos. Contraseña encriptada: " . ($contrasenaEncriptada ? 'SÍ' : 'NO') . ", Dirección guardada: " . ($direccionGuardada ? 'SÍ' : 'NO') . "\n";
    }

    // Limpieza de datos de prueba
    $pdo->prepare('DELETE FROM direccion WHERE id_cliente = :id')->execute([':id' => $idCliente]);
    $pdo->prepare('DELETE FROM cliente WHERE id_cliente = :id')->execute([':id' => $idCliente]);
} else {
    echo "  -> FALLO: Código HTTP {$codigoHttp}, Respuesta: {$respuesta}\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// Resumen Final
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=======================================================\n";
echo " RESUMEN: {$exitos}/{$totalPruebas} PRUEBAS SUPERADAS EXITOSAMENTE\n";
echo "=======================================================\n\n";

if ($exitos === $totalPruebas) {
    exit(0);
} else {
    exit(1);
}

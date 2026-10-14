<?php
/**
 * modules/1_auth/api/login.php
 *
 * Endpoint de autenticación (HU1).
 * Procesa la solicitud POST enviada por authService.js, valida las credenciales
 * contra la base de datos y devuelve una respuesta JSON.
 *
 * Flujo:
 *  1. Verificar que la solicitud sea POST y AJAX (X-Requested-With).
 *  2. Leer y sanear los datos JSON del cuerpo de la solicitud.
 *  3. Buscar al usuario por correo usando AuthModel (PDO prepared statement).
 *  4. Verificar la contraseña con password_verify().
 *  5. Verificar que la cuenta esté activa.
 *  6. Crear la sesión PHP y devolver la URL de redirección según el rol.
 *  7. En cualquier fallo: responder con mensaje genérico (no revelar cuál campo falló).
 *
 * Respuesta JSON exitosa:
 *  { "exito": true, "redireccion": "/proyecto-tienda/dashboard" }
 *
 * Respuesta JSON de error:
 *  { "exito": false, "mensaje": "Correo o contraseña incorrectos." }
 *
 * Restricciones (AGENTS.md):
 *  - Sin concatenación de variables en SQL.
 *  - Contraseñas solo con password_verify().
 *  - Sin texto plano de contraseña en logs ni respuestas.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Inicialización: sesión y núcleo del sistema
// ─────────────────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar las clases del núcleo necesarias para este endpoint
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/AuthModel.php';

// ─────────────────────────────────────────────────────────────────────────────
// Configuración de la respuesta HTTP
// ─────────────────────────────────────────────────────────────────────────────

// Todas las respuestas de este endpoint son JSON
header('Content-Type: application/json; charset=UTF-8');

// Evitar que el navegador o proxies cacheen respuestas de autenticación
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// ─────────────────────────────────────────────────────────────────────────────
// Constantes de configuración
// ─────────────────────────────────────────────────────────────────────────────

/** Mensaje genérico de error: no revela si el correo o la contraseña fallaron (HU1). */
const MENSAJE_CREDENCIALES_INVALIDAS = 'Correo o contraseña incorrectos.';

/** Rutas de redirección por rol (HU1 — tarea 1.5).
 *  Los valores deben coincidir exactamente con la columna 'rol' de la tabla rol. */
const RUTAS_POR_ROL = [
    'Administrador' => '/proyecto-tienda/?ruta=/dashboard',
    'Vendedor'      => '/proyecto-tienda/?ruta=/dashboard',
    'Cajero'        => '/proyecto-tienda/?ruta=/dashboard',
    'cliente'       => '/proyecto-tienda/?ruta=/catalogo',
];

/** Ruta de redirección por defecto si el rol no está mapeado. */
const RUTA_DEFAULT = '/proyecto-tienda/?ruta=/dashboard';

// ─────────────────────────────────────────────────────────────────────────────
// Función auxiliar: enviar respuesta JSON y terminar la ejecución
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Codifica y envía una respuesta JSON al cliente, luego detiene la ejecución.
 *
 * @param bool   $exito    true si la operación fue exitosa, false en caso contrario.
 * @param string $mensaje  Mensaje descriptivo (en caso de error).
 * @param array  $extras   Datos adicionales a incluir en la respuesta (ej. redireccion).
 * @return never
 */
function responderJson(bool $exito, string $mensaje = '', array $extras = []): never
{
    $cuerpo = array_merge(['exito' => $exito, 'mensaje' => $mensaje], $extras);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Validación del método HTTP y tipo de solicitud
// ─────────────────────────────────────────────────────────────────────────────

// Solo aceptar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    responderJson(false, 'Método no permitido.');
}

// Verificar que sea una solicitud AJAX (enviada por authService.js)
$esAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
if (!$esAjax) {
    http_response_code(403); // Forbidden
    responderJson(false, 'Acceso no permitido.');
}

// ─────────────────────────────────────────────────────────────────────────────
// Lectura y validación del cuerpo JSON
// ─────────────────────────────────────────────────────────────────────────────

// Leer el cuerpo raw de la solicitud y decodificar JSON
$cuerpoRaw = file_get_contents('php://input');
$datos     = json_decode($cuerpoRaw, true);

// Verificar que el JSON sea válido y contenga los campos requeridos
if (
    !is_array($datos)
    || empty($datos['correo'])
    || empty($datos['contrasena'])
) {
    http_response_code(400); // Bad Request
    responderJson(false, 'Los campos correo y contraseña son obligatorios.');
}

// Sanear la entrada: eliminar espacios extremos del correo
$correoEntrada     = trim($datos['correo']);
$contrasenaEntrada = $datos['contrasena']; // No hacer trim a la contraseña (puede tener espacios intencionales)

// ─────────────────────────────────────────────────────────────────────────────
// Lógica de autenticación
// ─────────────────────────────────────────────────────────────────────────────

try {
    $modeloAuth = new AuthModel();

    // Paso 1: Buscar al usuario por correo electrónico (PDO prepared statement)
    $usuario = $modeloAuth->buscarPorCorreo($correoEntrada);

    // Paso 2: Verificar existencia del usuario Y la contraseña en una sola condición.
    // Se usa password_verify() para comparar contra el hash almacenado.
    // IMPORTANTE: el mensaje de error es genérico para no revelar cuál campo falló (HU1).
    if ($usuario === null || !password_verify($contrasenaEntrada, $usuario['contrasena_hash'])) {
        http_response_code(401); // Unauthorized
        responderJson(false, MENSAJE_CREDENCIALES_INVALIDAS);
    }

    // Paso 3: Verificar que la cuenta esté activa (no desactivada por el administrador)
    if ((int)$usuario['activo'] !== 1) {
        http_response_code(403); // Forbidden
        responderJson(false, 'Tu cuenta está desactivada. Contacta al administrador.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Autenticación exitosa: crear sesión y redirigir por rol (tarea 1.5)
    // ─────────────────────────────────────────────────────────────────────────

    // Regenerar el ID de sesión para prevenir ataques de fijación de sesión
    session_regenerate_id(true);

    // Almacenar datos mínimos del usuario en la sesión (sin contraseña)
    $_SESSION['id_usuario'] = (int)$usuario['id'];
    $_SESSION['nombre']     = $usuario['nombre'];
    $_SESSION['correo']     = $usuario['correo'];
    $_SESSION['rol']        = $usuario['rol'];

    // Determinar la URL de redirección según el rol del usuario
    $redireccion = RUTAS_POR_ROL[$usuario['rol']] ?? RUTA_DEFAULT;

    // Responder con éxito y la URL de redirección
    http_response_code(200);
    responderJson(true, '', ['redireccion' => $redireccion]);

} catch (RuntimeException $e) {
    // Error de conexión a la base de datos (lanzado por Database.php)
    error_log('login.php — Error de BD: ' . $e->getMessage());
    http_response_code(500);
    responderJson(false, 'Error interno del servidor. Intenta más tarde.');

} catch (PDOException $e) {
    // Error inesperado de PDO
    error_log('login.php — PDOException: ' . $e->getMessage());
    http_response_code(500);
    responderJson(false, 'Error interno del servidor. Intenta más tarde.');
}

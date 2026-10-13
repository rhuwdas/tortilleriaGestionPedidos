<?php
/**
 * modules/1_auth/api/recovery.php
 *
 * Endpoint de recuperación de contraseña (HU2).
 * Gestiona 4 acciones enviadas vía POST desde recuperar.php:
 *
 *  1. solicitar_codigo  — Verifica el correo, genera el OTP y lo envía por correo.
 *  2. validar_codigo    — Valida el OTP ingresado; si es correcto redirige al paso 5.
 *  3. reenviar_codigo   — Regenera y reenvía un nuevo OTP al mismo correo en sesión.
 *  4. cambiar_contrasena — Valida requisitos, hashea y guarda la nueva contraseña.
 *
 * Flujo de redirección:
 *  - solicitar_codigo correcto  → recuperar.php?paso=2
 *  - solicitar_codigo falla     → recuperar.php?paso=3  (correo no registrado)
 *  - validar_codigo correcto    → recuperar.php?paso=5
 *  - validar_codigo falla       → recuperar.php?paso=4  (código incorrecto/expirado)
 *  - cambiar_contrasena correcta → login.php  (con mensaje de éxito en sesión)
 *  - cambiar_contrasena falla   → recuperar.php?paso=5  (con error en sesión)
 *
 * Restricciones (AGENTS.md):
 *  - Sin Composer ni frameworks.
 *  - Todas las consultas SQL con PDO prepared statements.
 *  - Contraseñas solo con password_hash() / password_verify().
 *  - Solo acepta POST + formularios enviados desde el propio dominio.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Inicialización
// ─────────────────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/RecoveryModel.php';
require_once __DIR__ . '/MailService.php';

// ─────────────────────────────────────────────────────────────────────────────
// Rutas de redirección
// ─────────────────────────────────────────────────────────────────────────────

const RUTA_PASO_1 = '/proyecto-tienda/modules/1_auth/ui/recuperar.php?paso=1';
const RUTA_PASO_2 = '/proyecto-tienda/modules/1_auth/ui/recuperar.php?paso=2';
const RUTA_PASO_3 = '/proyecto-tienda/modules/1_auth/ui/recuperar.php?paso=3';
const RUTA_PASO_4 = '/proyecto-tienda/modules/1_auth/ui/recuperar.php?paso=4';
const RUTA_PASO_5 = '/proyecto-tienda/modules/1_auth/ui/recuperar.php?paso=5';
const RUTA_LOGIN  = '/proyecto-tienda/modules/1_auth/ui/login.php';

// ─────────────────────────────────────────────────────────────────────────────
// Función auxiliar: redirigir y detener ejecución
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Redirige al usuario a una URL y detiene la ejecución del script.
 *
 * @param string $url URL de destino.
 * @return never
 */
function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Validación del método HTTP
// ─────────────────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si alguien accede directamente por GET, volver al paso 1
    redirigir(RUTA_PASO_1);
}

// ─────────────────────────────────────────────────────────────────────────────
// Leer la acción solicitada
// ─────────────────────────────────────────────────────────────────────────────

$accion = trim($_POST['accion'] ?? '');

// Acciones permitidas
$accionesPermitidas = ['solicitar_codigo', 'validar_codigo', 'reenviar_codigo', 'cambiar_contrasena'];
if (!in_array($accion, $accionesPermitidas, true)) {
    redirigir(RUTA_PASO_1);
}

// ─────────────────────────────────────────────────────────────────────────────
// Instanciar modelo
// ─────────────────────────────────────────────────────────────────────────────

try {
    $modelo = new RecoveryModel();
} catch (RuntimeException $e) {
    error_log('recovery.php — Error al instanciar RecoveryModel: ' . $e->getMessage());
    redirigir(RUTA_PASO_1);
}

// ─────────────────────────────────────────────────────────────────────────────
// Acción 1: solicitar_codigo
// Verifica correo en BD, genera OTP, guarda en recuperacion_password y envía email.
// ─────────────────────────────────────────────────────────────────────────────

if ($accion === 'solicitar_codigo') {
    $correo = trim($_POST['correo'] ?? '');

    // Validar que el campo no esté vacío
    if (empty($correo)) {
        redirigir(RUTA_PASO_1);
    }

    // Guardar el correo en sesión para mostrarlo en el paso 2
    $_SESSION['correo_recuperacion'] = $correo;

    try {
        // Verificar si el correo existe en empleado o cliente
        if (!$modelo->correoRegistrado($correo)) {
            // Correo no registrado: redirigir al paso 3 (<<extend>>)
            redirigir(RUTA_PASO_3);
        }

        // Generar y guardar el código OTP (6 dígitos, expira en 10 min)
        $codigo = $modelo->generarYGuardarCodigo($correo);

        // Enviar el código por correo electrónico vía SMTP
        $mailService = new MailService();
        $mailService->enviarCodigoRecuperacion($correo, $codigo);

    } catch (RuntimeException $e) {
        // Error de BD o SMTP: registrar y redirigir al paso 1
        error_log('recovery.php — solicitar_codigo: ' . $e->getMessage());
        redirigir(RUTA_PASO_1);
    }

    // Todo correcto: redirigir al paso 2 (ingresar código)
    redirigir(RUTA_PASO_2);
}

// ─────────────────────────────────────────────────────────────────────────────
// Acción 2: validar_codigo
// Verifica que el OTP sea correcto, esté vigente y no haya sido usado.
// ─────────────────────────────────────────────────────────────────────────────

if ($accion === 'validar_codigo') {
    $codigo = trim($_POST['codigo'] ?? '');
    $correo = $_SESSION['correo_recuperacion'] ?? '';

    // Si no hay correo en sesión, volver al paso 1
    if (empty($correo)) {
        redirigir(RUTA_PASO_1);
    }

    // Validar formato: exactamente 6 dígitos
    if (!preg_match('/^\d{6}$/', $codigo)) {
        redirigir(RUTA_PASO_4);
    }

    try {
        $esValido = $modelo->validarCodigo($correo, $codigo);
    } catch (RuntimeException $e) {
        error_log('recovery.php — validar_codigo: ' . $e->getMessage());
        redirigir(RUTA_PASO_4);
    }

    if (!$esValido) {
        // Código incorrecto o expirado: redirigir al paso 4 (<<extend>>)
        redirigir(RUTA_PASO_4);
    }

    // Código correcto: marcar en sesión que el código fue validado
    // para que solo se pueda cambiar la contraseña si se pasó por este paso
    $_SESSION['codigo_validado'] = true;

    // Redirigir al paso 5 (nueva contraseña)
    redirigir(RUTA_PASO_5);
}

// ─────────────────────────────────────────────────────────────────────────────
// Acción 3: reenviar_codigo
// Regenera un nuevo OTP para el correo en sesión y lo reenvía.
// ─────────────────────────────────────────────────────────────────────────────

if ($accion === 'reenviar_codigo') {
    $correo = $_SESSION['correo_recuperacion'] ?? '';

    if (empty($correo)) {
        redirigir(RUTA_PASO_1);
    }

    try {
        // Verificar que el correo siga registrado
        if (!$modelo->correoRegistrado($correo)) {
            redirigir(RUTA_PASO_3);
        }

        // Generar nuevo código (invalida el anterior automáticamente)
        $codigo = $modelo->generarYGuardarCodigo($correo);

        // Reenviar por correo
        $mailService = new MailService();
        $mailService->enviarCodigoRecuperacion($correo, $codigo);

    } catch (RuntimeException $e) {
        error_log('recovery.php — reenviar_codigo: ' . $e->getMessage());
        // Si falla el reenvío, volver al paso 2 (sin mostrar error específico)
        redirigir(RUTA_PASO_2);
    }

    // Nuevo código enviado: volver al paso 2
    redirigir(RUTA_PASO_2);
}

// ─────────────────────────────────────────────────────────────────────────────
// Acción 4: cambiar_contrasena
// Valida requisitos de la nueva contraseña, la hashea y la guarda en BD.
// ─────────────────────────────────────────────────────────────────────────────

if ($accion === 'cambiar_contrasena') {
    $correo             = $_SESSION['correo_recuperacion'] ?? '';
    $codigoValidado     = $_SESSION['codigo_validado']     ?? false;
    $nuevaContrasena    = $_POST['nueva_contrasena']        ?? '';
    $confirmarContrasena = $_POST['confirmar_contrasena']   ?? '';

    // Verificar que se llegó aquí pasando por la validación del código
    if (empty($correo) || !$codigoValidado) {
        redirigir(RUTA_PASO_1);
    }

    // Validar que los campos no estén vacíos
    if (empty($nuevaContrasena) || empty($confirmarContrasena)) {
        $_SESSION['error_contrasena'] = 'Todos los campos son obligatorios.';
        redirigir(RUTA_PASO_5);
    }

    // Validar coincidencia
    if ($nuevaContrasena !== $confirmarContrasena) {
        $_SESSION['error_contrasena'] = 'Las contraseñas no coinciden.';
        redirigir(RUTA_PASO_5);
    }

    // Validar longitud mínima: 8 caracteres
    if (strlen($nuevaContrasena) < 8) {
        $_SESSION['error_contrasena'] = 'La contraseña debe tener al menos 8 caracteres.';
        redirigir(RUTA_PASO_5);
    }

    // Validar al menos una mayúscula
    if (!preg_match('/[A-Z]/', $nuevaContrasena)) {
        $_SESSION['error_contrasena'] = 'La contraseña debe contener al menos una letra mayúscula.';
        redirigir(RUTA_PASO_5);
    }

    // Validar al menos un número
    if (!preg_match('/[0-9]/', $nuevaContrasena)) {
        $_SESSION['error_contrasena'] = 'La contraseña debe contener al menos un número.';
        redirigir(RUTA_PASO_5);
    }

    try {
        $actualizado = $modelo->actualizarContrasena($correo, $nuevaContrasena);
    } catch (RuntimeException $e) {
        error_log('recovery.php — cambiar_contrasena: ' . $e->getMessage());
        $_SESSION['error_contrasena'] = 'Error interno. Intenta nuevamente.';
        redirigir(RUTA_PASO_5);
    }

    if (!$actualizado) {
        $_SESSION['error_contrasena'] = 'No se pudo actualizar la contraseña. Intenta de nuevo.';
        redirigir(RUTA_PASO_5);
    }

    // Contraseña actualizada: limpiar datos de sesión de recuperación
    unset(
        $_SESSION['correo_recuperacion'],
        $_SESSION['codigo_validado'],
        $_SESSION['error_contrasena']
    );

    // Redirigir al login con mensaje de éxito en sesión
    $_SESSION['mensaje_exito'] = 'Contraseña actualizada correctamente. Ahora puedes iniciar sesión.';
    redirigir(RUTA_LOGIN);
}

<?php
/**
 * modules/1_auth/api/logout.php
 *
 * Endpoint de cierre de sesión (HU1 — tarea 1.5).
 * Destruye la sesión activa del usuario y lo redirige al login.
 *
 * Flujo:
 *  1. Iniciar la sesión si no está activa (para poder destruirla).
 *  2. Limpiar todas las variables de sesión.
 *  3. Eliminar la cookie de sesión del navegador.
 *  4. Destruir la sesión en el servidor.
 *  5. Redirigir al login.
 */

// Iniciar la sesión para poder destruirla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paso 1: Limpiar todas las variables de sesión almacenadas
$_SESSION = [];

// Paso 2: Eliminar la cookie de sesión del navegador del cliente
// Esto garantiza que el navegador no reenvíe el ID de sesión antiguo
if (ini_get('session.use_cookies')) {
    $parametrosCookie = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Fecha en el pasado para forzar la expiración
        $parametrosCookie['path'],
        $parametrosCookie['domain'],
        $parametrosCookie['secure'],
        $parametrosCookie['httponly']
    );
}

// Paso 3: Destruir todos los datos de la sesión en el servidor
session_destroy();

// Paso 4: Redirigir al login
header('Location: /proyecto-tienda/modules/1_auth/ui/login.php');
exit;

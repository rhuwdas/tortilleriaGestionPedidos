<?php
/**
 * modules/1_auth/ui/login.php
 *
 * Vista de inicio de sesión (HU1).
 * Muestra la tarjeta de login centrada con:
 *  - Logo de la tortillería
 *  - Campo de correo electrónico
 *  - Campo de contraseña con botón de visibilidad (mostrar/ocultar)
 *  - Botón "Iniciar Sesión" (deshabilitado hasta llenar ambos campos)
 *  - Enlace a recuperación de contraseña
 *  - Zona de mensaje de error para credenciales inválidas
 *
 * Restricciones (AGENTS.md):
 *  - Sin JavaScript inline: toda la lógica JS está en authService.js (cargado con defer).
 *  - Bootstrap 5 + CSS personalizado.
 *  - Fuente principal Arial.
 */

// Si el usuario ya tiene sesión activa, redirigir al dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION['id_usuario'])) {
    header('Location: /proyecto-tienda/?ruta=/dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Tortillería</title>

    <!-- Bootstrap 5 CDN -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons CDN (para ojo de visibilidad) -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Estilos personalizados del login -->
    <link rel="stylesheet" href="/proyecto-tienda/modules/1_auth/ui/login.css">
</head>
<body class="bg-login d-flex align-items-center justify-content-center min-vh-100">

    <!-- Tarjeta de inicio de sesión centrada -->
    <div class="card shadow-lg login-card">
        <div class="card-body p-4 p-md-5">

            <!-- Logo de la tortillería -->
            <div class="text-center mb-4">
                <img
                    src="/proyecto-tienda/public/assets/img/logo.png"
                    alt="Logo Tortillería"
                    class="login-logo mb-3"
                    onerror="this.style.display='none'; document.getElementById('logo-texto').style.display='block';"
                >
                <!-- Fallback de texto si el logo no existe aún -->
                <h1 id="logo-texto" class="login-titulo-marca" style="display:none;">
                    🫓 Tortillería
                </h1>
                <h2 class="login-subtitulo">Sistema de Gestión</h2>
                <p class="text-muted small">Ingresa tus credenciales para continuar</p>
            </div>

            <?php
            // Mostrar mensaje de éxito si viene de recuperación de contraseña
            if (!empty($_SESSION['mensaje_exito'])):
            ?>
            <div class="alert alert-success" role="alert" aria-live="polite">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['mensaje_exito'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php
                unset($_SESSION['mensaje_exito']);
            endif;
            ?>

            <!-- Alerta de error (oculta por defecto, se muestra vía JS) -->
            <div
                id="alerta-error"
                class="alert alert-danger d-none"
                role="alert"
                aria-live="polite"
            >
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="alerta-error-mensaje">Correo o contraseña incorrectos.</span>
            </div>

            <!-- Formulario de inicio de sesión -->
            <form id="formulario-login" novalidate autocomplete="off">

                <!-- Campo: Correo electrónico -->
                <div class="mb-3">
                    <label for="campo-correo" class="form-label fw-semibold">
                        Usuario / Correo electrónico
                    </label>
                    <input
                        type="email"
                        id="campo-correo"
                        name="correo"
                        class="form-control"
                        placeholder="usuario@correo.com"
                        maxlength="150"
                        required
                        aria-required="true"
                        aria-describedby="error-correo"
                    >
                    <!-- Mensaje de validación para correo vacío -->
                    <div id="error-correo" class="invalid-feedback">
                        El correo electrónico es obligatorio.
                    </div>
                </div>

                <!-- Campo: Contraseña con botón de visibilidad -->
                <div class="mb-4">
                    <label for="campo-contrasena" class="form-label fw-semibold">
                        Contraseña
                    </label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="campo-contrasena"
                            name="contrasena"
                            class="form-control"
                            placeholder="••••••••"
                            maxlength="255"
                            required
                            aria-required="true"
                            aria-describedby="error-contrasena"
                        >
                        <!-- Botón para mostrar/ocultar contraseña -->
                        <button
                            type="button"
                            id="boton-visibilidad"
                            class="btn btn-outline-secondary"
                            aria-label="Mostrar u ocultar contraseña"
                            title="Mostrar u ocultar contraseña"
                        >
                            <i id="icono-visibilidad" class="bi bi-eye"></i>
                        </button>
                    </div>
                    <!-- Mensaje de validación para contraseña vacía -->
                    <div id="error-contrasena" class="invalid-feedback d-block" style="display:none !important;">
                        La contraseña es obligatoria.
                    </div>
                </div>

                <!-- Botón de envío: deshabilitado hasta que ambos campos estén llenos -->
                <div class="d-grid mb-3">
                    <button
                        type="submit"
                        id="boton-ingresar"
                        class="btn btn-primary btn-lg"
                        disabled
                        aria-disabled="true"
                    >
                        <span id="boton-texto">Iniciar Sesión</span>
                        <!-- Spinner de carga (oculto por defecto) -->
                        <span
                            id="boton-spinner"
                            class="spinner-border spinner-border-sm ms-2 d-none"
                            role="status"
                            aria-hidden="true"
                        ></span>
                    </button>
                </div>

                <!-- Enlace a recuperación de contraseña -->
                <div class="text-center">
                    <a
                        href="/proyecto-tienda/modules/1_auth/ui/recuperar.php"
                        class="link-secondary small"
                    >
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (incluye Popper) -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"
        defer
    ></script>

    <!-- Servicio de autenticación del cliente (lógica JS del login) -->
    <script src="/proyecto-tienda/modules/1_auth/ui/authService.js" defer></script>

</body>
</html>

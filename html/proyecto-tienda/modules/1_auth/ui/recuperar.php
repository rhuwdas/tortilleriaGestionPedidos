<?php
/**
 * modules/1_auth/ui/recuperar.php
 *
 * Vista de recuperación de contraseña (HU2).
 * Gestiona 4 estados/pantallas en una sola vista mediante el parámetro GET "paso":
 *
 *  Paso 1 (default) — Solicitud de correo:
 *      Formulario con campo de correo y botón "Enviar código".
 *
 *  Paso 2 — Validación de código:
 *      Campo para ingresar el código de 6 dígitos recibido por correo.
 *      Botones "Verificar" y "Reenviar código".
 *
 *  Paso 3 (<<extend>>) — Error: correo no registrado:
 *      Hereda estructura del Paso 1. Campo rojo + mensaje de error.
 *
 *  Paso 4 (<<extend>>) — Error: código expirado o incorrecto:
 *      Hereda estructura del Paso 2. Alerta de error destacada.
 *
 * Restricciones (AGENTS.md):
 *  - Sin JavaScript inline.
 *  - Bootstrap 5 + CSS login.css reutilizado.
 *  - Fuente Arial.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya tiene sesión activa, redirigir al dashboard
if (!empty($_SESSION['id_usuario'])) {
    header('Location: /proyecto-tienda/?ruta=/dashboard');
    exit;
}

// Determinar el paso/estado actual de la pantalla
// paso=1: solicitud correo (default)
// paso=2: validación código
// paso=3: error correo no registrado (extend de paso 1)
// paso=4: error código incorrecto/expirado (extend de paso 2)
$paso = isset($_GET['paso']) ? (int)$_GET['paso'] : 1;

// Validar que el paso sea un valor permitido; si no, volver al paso 1
if (!in_array($paso, [1, 2, 3, 4], true)) {
    $paso = 1;
}

// Correo almacenado en sesión para mostrarlo en el paso 2 sin re-enviarlo
$correoSesion = $_SESSION['correo_recuperacion'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña — Tortillería</title>

    <!-- Bootstrap 5 CDN -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- CSS compartido del módulo de autenticación -->
    <link rel="stylesheet" href="/proyecto-tienda/modules/1_auth/ui/login.css">
</head>
<body class="bg-login d-flex align-items-center justify-content-center min-vh-100">

    <div class="card shadow-lg login-card">
        <div class="card-body p-4 p-md-5">

            <!-- Cabecera: Logo de la tortillería -->
            <div class="text-center mb-4">
                <img
                    src="/proyecto-tienda/public/assets/img/logo.png"
                    alt="Logo Tortillería"
                    class="login-logo mb-3"
                    onerror="this.style.display='none'; document.getElementById('logo-texto').style.display='block';"
                >
                <h1 id="logo-texto" class="login-titulo-marca" style="display:none;">
                    🫓 Tortillería
                </h1>
            </div>

            <?php if ($paso === 1 || $paso === 3): ?>
            <!-- ═══════════════════════════════════════════════════════
                 PASO 1 — Solicitud de correo electrónico
                 PASO 3 — <<extend>>: Error correo no registrado
                 (Hereda la misma estructura de Paso 1)
                 ═══════════════════════════════════════════════════════ -->

                <h2 class="login-subtitulo text-center mb-1">Recuperar Contraseña</h2>
                <p class="text-muted text-center small mb-4">
                    Ingresa tu correo electrónico registrado para recibir un código de recuperación.
                </p>

                <?php if ($paso === 3): ?>
                <!-- Alerta de error: correo no registrado (<<extend>> Paso 3) -->
                <div class="alert alert-danger" role="alert" aria-live="polite">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    El correo electrónico ingresado no está registrado en el sistema.
                </div>
                <?php endif; ?>

                <form
                    id="formulario-recuperacion"
                    action="/proyecto-tienda/modules/1_auth/api/recovery.php"
                    method="POST"
                    novalidate
                >
                    <!-- Token CSRF oculto -->
                    <input type="hidden" name="accion" value="solicitar_codigo">

                    <!-- Campo: Correo electrónico -->
                    <div class="mb-4">
                        <label for="campo-correo-recuperacion" class="form-label fw-semibold">
                            Correo electrónico
                        </label>
                        <input
                            type="email"
                            id="campo-correo-recuperacion"
                            name="correo"
                            class="form-control <?= $paso === 3 ? 'is-invalid' : '' ?>"
                            placeholder="usuario@correo.com"
                            maxlength="150"
                            required
                            aria-required="true"
                            value="<?= htmlspecialchars($correoSesion, ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <?php if ($paso === 3): ?>
                        <div class="invalid-feedback">
                            El correo electrónico ingresado no está registrado en el sistema.
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botón principal -->
                    <div class="d-grid mb-3">
                        <button type="submit" id="boton-enviar-codigo" class="btn btn-primary btn-lg" disabled>
                            <span id="boton-texto-recuperacion">Enviar código</span>
                            <span
                                id="spinner-recuperacion"
                                class="spinner-border spinner-border-sm ms-2 d-none"
                                role="status"
                                aria-hidden="true"
                            ></span>
                        </button>
                    </div>

                    <!-- Enlace volver al login -->
                    <div class="text-center">
                        <a href="/proyecto-tienda/modules/1_auth/ui/login.php" class="link-secondary small">
                            <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
                        </a>
                    </div>
                </form>

            <?php elseif ($paso === 2 || $paso === 4): ?>
            <!-- ═══════════════════════════════════════════════════════
                 PASO 2 — Validación del código de 6 dígitos
                 PASO 4 — <<extend>>: Error código incorrecto/expirado
                 (Hereda la misma estructura de Paso 2)
                 ═══════════════════════════════════════════════════════ -->

                <h2 class="login-subtitulo text-center mb-1">Validar Código</h2>
                <p class="text-muted text-center small mb-4">
                    Hemos enviado un código de 6 dígitos a tu correo
                    <?php if ($correoSesion): ?>
                        <strong><?= htmlspecialchars($correoSesion, ENT_QUOTES, 'UTF-8') ?></strong>.
                    <?php else: ?>
                        electrónico.
                    <?php endif; ?>
                </p>

                <?php if ($paso === 4): ?>
                <!-- Alerta de error: código incorrecto o expirado (<<extend>> Paso 4) -->
                <div class="alert alert-danger" role="alert" aria-live="polite">
                    <i class="bi bi-clock-history me-2"></i>
                    El código ingresado es incorrecto o ha expirado. Solicita uno nuevo.
                </div>
                <?php endif; ?>

                <form
                    id="formulario-codigo"
                    action="/proyecto-tienda/modules/1_auth/api/recovery.php"
                    method="POST"
                    novalidate
                >
                    <input type="hidden" name="accion" value="validar_codigo">

                    <!-- Campo: Código de verificación de 6 dígitos -->
                    <div class="mb-4">
                        <label for="campo-codigo" class="form-label fw-semibold">
                            Código de verificación
                        </label>
                        <input
                            type="text"
                            id="campo-codigo"
                            name="codigo"
                            class="form-control form-control-lg text-center <?= $paso === 4 ? 'is-invalid' : '' ?>"
                            placeholder="000000"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            required
                            aria-required="true"
                            autocomplete="one-time-code"
                        >
                        <?php if ($paso === 4): ?>
                        <div class="invalid-feedback text-center">
                            El código ingresado es incorrecto o ha expirado.
                        </div>
                        <?php endif; ?>
                        <div class="form-text text-center">
                            Ingresa los 6 dígitos recibidos en tu correo.
                        </div>
                    </div>

                    <!-- Botones: Verificar y Reenviar código -->
                    <div class="d-grid gap-2 mb-3">
                        <button
                            type="submit"
                            id="boton-verificar"
                            class="btn btn-primary btn-lg"
                            disabled
                        >
                            <span id="boton-texto-verificar">Verificar</span>
                            <span
                                id="spinner-verificar"
                                class="spinner-border spinner-border-sm ms-2 d-none"
                                role="status"
                                aria-hidden="true"
                            ></span>
                        </button>

                        <!-- Reenviar código: envío POST a recovery.php con acción reenviar -->
                        <form
                            action="/proyecto-tienda/modules/1_auth/api/recovery.php"
                            method="POST"
                            class="d-grid"
                        >
                            <input type="hidden" name="accion" value="reenviar_codigo">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-repeat me-1"></i>Reenviar código
                            </button>
                        </form>
                    </div>

                    <!-- Enlace volver al paso 1 -->
                    <div class="text-center">
                        <a
                            href="/proyecto-tienda/modules/1_auth/ui/recuperar.php?paso=1"
                            class="link-secondary small"
                        >
                            <i class="bi bi-arrow-left me-1"></i>Cambiar correo electrónico
                        </a>
                    </div>
                </form>

            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmE5sRFtZoMPxnQXVFf0vZQ9kXa"
        crossorigin="anonymous"
        defer
    ></script>

    <!-- Servicio JS de recuperación -->
    <script src="/proyecto-tienda/modules/1_auth/ui/recoveryService.js" defer></script>

</body>
</html>

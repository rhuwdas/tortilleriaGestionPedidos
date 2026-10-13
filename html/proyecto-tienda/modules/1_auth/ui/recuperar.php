<?php
/**
 * modules/1_auth/ui/recuperar.php
 *
 * Vista de recuperación de contraseña (HU2).
 * Gestiona 5 estados/pantallas en una sola vista mediante el parámetro GET "paso":
 *
 *  Paso 1 (default) — Solicitud de correo:
 *      Formulario con campo de correo y botón "Enviar código".
 *
 *  Paso 2 — Validación de código OTP:
 *      Campo único de 6 dígitos. Botones "Verificar" y "Reenviar código".
 *
 *  Paso 3 — Error: correo no registrado (<<extend>> desde paso 1):
 *      Misma estructura del Paso 1 con banner de error #FEE2E2 / #DC2626.
 *
 *  Paso 4 — Error: código expirado o incorrecto (<<extend>> desde paso 2):
 *      Misma estructura del Paso 2 con alerta de error.
 *
 *  Paso 5 — Nueva contraseña:
 *      Formulario con campo nueva contraseña + confirmación.
 *      Validación: mín. 8 caracteres, al menos una mayúscula y un número.
 *
 * Restricciones (AGENTS.md):
 *  - Sin JavaScript inline.
 *  - Bootstrap 5 + login.css reutilizado.
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
$paso = isset($_GET['paso']) ? (int)$_GET['paso'] : 1;

// Validar que el paso sea un valor permitido; si no, volver al paso 1
if (!in_array($paso, [1, 2, 3, 4, 5], true)) {
    $paso = 1;
}

// Correo almacenado en sesión para mostrarlo en el paso 2/4/5 sin re-enviarlo
$correoSesion = $_SESSION['correo_recuperacion'] ?? '';

// Mensaje de error de contraseña (viene desde recovery.php vía sesión)
$errorContrasena = $_SESSION['error_contrasena'] ?? '';
unset($_SESSION['error_contrasena']);
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
            <!-- ═══════════════════════════════════════════════════════════════
                 PASO 1 — Solicitud de correo electrónico
                 PASO 3 — <<extend>>: correo no registrado (banner de error)
                 ═══════════════════════════════════════════════════════════════ -->

                <h2 class="login-subtitulo text-center mb-1">Recuperar Contraseña</h2>
                <p class="text-muted text-center small mb-4">
                    Ingresa tu correo electrónico registrado para recibir un código de verificación.
                </p>

                <?php if ($paso === 3): ?>
                <!-- Banner de error: correo no registrado -->
                <div
                    class="alert d-flex align-items-start gap-2 mb-3"
                    role="alert"
                    aria-live="polite"
                    style="background:#FEE2E2;border-left:4px solid #DC2626;color:#DC2626;
                           border-radius:8px;padding:12px 16px;"
                >
                    <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1"></i>
                    <span>El correo electrónico ingresado no está registrado en el sistema.</span>
                </div>
                <?php endif; ?>

                <form
                    id="formulario-recuperacion"
                    action="/proyecto-tienda/modules/1_auth/api/recovery.php"
                    method="POST"
                    novalidate
                >
                    <input type="hidden" name="accion" value="solicitar_codigo">

                    <!-- Campo: correo electrónico -->
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
                        <button
                            type="submit"
                            id="boton-enviar-codigo"
                            class="btn btn-primary btn-lg"
                            disabled
                            aria-disabled="true"
                        >
                            <span id="boton-texto-recuperacion">Enviar código</span>
                            <span
                                id="spinner-recuperacion"
                                class="spinner-border spinner-border-sm ms-2 d-none"
                                role="status"
                                aria-hidden="true"
                            ></span>
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="/proyecto-tienda/modules/1_auth/ui/login.php" class="link-secondary small">
                            <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
                        </a>
                    </div>
                </form>

            <?php elseif ($paso === 2 || $paso === 4): ?>
            <!-- ═══════════════════════════════════════════════════════════════
                 PASO 2 — Validación del código de 6 dígitos
                 PASO 4 — <<extend>>: código incorrecto o expirado
                 ═══════════════════════════════════════════════════════════════ -->

                <h2 class="login-subtitulo text-center mb-1">Validar Código</h2>
                <p class="text-muted text-center small mb-4">
                    Hemos enviado un código de 6 dígitos a
                    <?php if ($correoSesion): ?>
                        <strong><?= htmlspecialchars($correoSesion, ENT_QUOTES, 'UTF-8') ?></strong>.
                    <?php else: ?>
                        tu correo electrónico.
                    <?php endif; ?>
                </p>

                <?php if ($paso === 4): ?>
                <!-- Banner de error: código incorrecto/expirado -->
                <div
                    class="alert d-flex align-items-start gap-2 mb-3"
                    role="alert"
                    aria-live="polite"
                    style="background:#FEE2E2;border-left:4px solid #DC2626;color:#DC2626;
                           border-radius:8px;padding:12px 16px;"
                >
                    <i class="bi bi-clock-history flex-shrink-0 mt-1"></i>
                    <span>El código ingresado es incorrecto o ha expirado. Solicita uno nuevo.</span>
                </div>
                <?php endif; ?>

                <form
                    id="formulario-codigo"
                    action="/proyecto-tienda/modules/1_auth/api/recovery.php"
                    method="POST"
                    novalidate
                >
                    <input type="hidden" name="accion" value="validar_codigo">

                    <!-- Campo: código de verificación (6 dígitos) -->
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
                        <div class="form-text text-center">Ingresa los 6 dígitos recibidos en tu correo.</div>
                    </div>

                    <!-- Botón: Verificar -->
                    <div class="d-grid mb-2">
                        <button
                            type="submit"
                            id="boton-verificar"
                            class="btn btn-primary btn-lg"
                            disabled
                            aria-disabled="true"
                        >
                            <span id="boton-texto-verificar">Verificar</span>
                            <span
                                id="spinner-verificar"
                                class="spinner-border spinner-border-sm ms-2 d-none"
                                role="status"
                                aria-hidden="true"
                            ></span>
                        </button>
                    </div>
                </form>

                <!-- Formulario independiente: Reenviar código -->
                <form
                    action="/proyecto-tienda/modules/1_auth/api/recovery.php"
                    method="POST"
                    class="d-grid mb-3"
                >
                    <input type="hidden" name="accion" value="reenviar_codigo">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-repeat me-1"></i>Reenviar código
                    </button>
                </form>

                <div class="text-center">
                    <a
                        href="/proyecto-tienda/modules/1_auth/ui/recuperar.php?paso=1"
                        class="link-secondary small"
                    >
                        <i class="bi bi-arrow-left me-1"></i>Cambiar correo electrónico
                    </a>
                </div>

            <?php elseif ($paso === 5): ?>
            <!-- ═══════════════════════════════════════════════════════════════
                 PASO 5 — Nueva contraseña
                 Código OTP validado correctamente; el usuario ingresa su nueva
                 contraseña (mín. 8 chars, al menos 1 mayúscula y 1 número).
                 ═══════════════════════════════════════════════════════════════ -->

                <h2 class="login-subtitulo text-center mb-1">Nueva Contraseña</h2>
                <p class="text-muted text-center small mb-4">
                    Ingresa y confirma tu nueva contraseña.
                </p>

                <?php if ($errorContrasena): ?>
                <!-- Banner de error de contraseña (viene del backend) -->
                <div
                    class="alert d-flex align-items-start gap-2 mb-3"
                    role="alert"
                    aria-live="polite"
                    style="background:#FEE2E2;border-left:4px solid #DC2626;color:#DC2626;
                           border-radius:8px;padding:12px 16px;"
                >
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <span><?= htmlspecialchars($errorContrasena, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>

                <form
                    id="formulario-nueva-contrasena"
                    action="/proyecto-tienda/modules/1_auth/api/recovery.php"
                    method="POST"
                    novalidate
                >
                    <input type="hidden" name="accion" value="cambiar_contrasena">

                    <!-- Campo: nueva contraseña -->
                    <div class="mb-3">
                        <label for="campo-nueva-contrasena" class="form-label fw-semibold">
                            Nueva contraseña
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                id="campo-nueva-contrasena"
                                name="nueva_contrasena"
                                class="form-control"
                                placeholder="Mín. 8 caracteres"
                                maxlength="255"
                                required
                                aria-required="true"
                                aria-describedby="requisitos-contrasena"
                            >
                            <button
                                type="button"
                                id="boton-vis-nueva"
                                class="btn btn-outline-secondary"
                                aria-label="Mostrar u ocultar contraseña"
                                title="Mostrar u ocultar contraseña"
                            >
                                <i id="icono-vis-nueva" class="bi bi-eye"></i>
                            </button>
                        </div>
                        <!-- Indicador de requisitos en tiempo real -->
                        <div id="requisitos-contrasena" class="form-text mt-2">
                            <span id="req-longitud" class="me-3">
                                <i class="bi bi-x-circle text-danger"></i> Mín. 8 caracteres
                            </span>
                            <span id="req-mayuscula" class="me-3">
                                <i class="bi bi-x-circle text-danger"></i> Al menos 1 mayúscula
                            </span>
                            <span id="req-numero">
                                <i class="bi bi-x-circle text-danger"></i> Al menos 1 número
                            </span>
                        </div>
                    </div>

                    <!-- Campo: confirmar contraseña -->
                    <div class="mb-4">
                        <label for="campo-confirmar-contrasena" class="form-label fw-semibold">
                            Confirmar contraseña
                        </label>
                        <input
                            type="password"
                            id="campo-confirmar-contrasena"
                            name="confirmar_contrasena"
                            class="form-control"
                            placeholder="Repite tu contraseña"
                            maxlength="255"
                            required
                            aria-required="true"
                            aria-describedby="mensaje-coincidencia"
                        >
                        <div id="mensaje-coincidencia" class="form-text"></div>
                    </div>

                    <!-- Botón: Guardar contraseña -->
                    <div class="d-grid mb-3">
                        <button
                            type="submit"
                            id="boton-guardar-contrasena"
                            class="btn btn-primary btn-lg"
                            disabled
                            aria-disabled="true"
                        >
                            <span id="boton-texto-guardar">Guardar contraseña</span>
                            <span
                                id="spinner-guardar"
                                class="spinner-border spinner-border-sm ms-2 d-none"
                                role="status"
                                aria-hidden="true"
                            ></span>
                        </button>
                    </div>
                </form>

            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"
        defer
    ></script>

    <!-- Servicio JS de recuperación -->
    <script src="/proyecto-tienda/modules/1_auth/ui/recoveryService.js" defer></script>

</body>
</html>

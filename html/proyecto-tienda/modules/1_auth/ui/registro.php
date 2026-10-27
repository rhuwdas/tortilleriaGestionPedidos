<?php
/**
 * modules/1_auth/ui/registro.php
 *
 * Interfaz de registro para nuevos clientes (HU4).
 * Permite capturar los datos de la cuenta de cliente:
 *  - Nombre y apellidos
 *  - Teléfono (10 dígitos)
 *  - Correo electrónico (único)
 *  - Contraseña
 *  - Dirección de entrega opcional (calle, colonia, municipio, código postal, referencias)
 *
 * Cumple con los lineamientos de design.md y agents.md:
 *  - Paleta Terracota (#EA580C) y Verde Oliva (#16A34A).
 *  - Sin JavaScript en línea: lógica desacoplada en registroService.js con atributo defer.
 *  - Campos accesibles con atributos ARIA y etiquetas descriptivas.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirigir si ya existe una sesión activa
if (!empty($_SESSION['id_usuario']) || !empty($_SESSION['id_cliente'])) {
    $rolSesion = strtolower(trim((string)($_SESSION['rol'] ?? '')));
    $rutaInicio = match ($rolSesion) {
        'cliente'              => '/catalogo',
        'vendedor', 'cajero'   => '/pos',
        'repartidor'           => '/logistica',
        default                => '/dashboard',
    };

    header('Location: /proyecto-tienda/?ruta=' . $rutaInicio);
    exit;
}

require_once __DIR__ . '/../../../core/MunicipiosCobertura.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cliente — Tortillería</title>

    <!-- Bootstrap 5 CDN -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons CDN -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Estilos específicos de la pantalla de registro -->
    <link rel="stylesheet" href="/proyecto-tienda/modules/1_auth/ui/registro.css">
</head>
<body>

    <main class="contenedor-registro">
        <div class="tarjeta-registro">

            <!-- Encabezado de la pantalla -->
            <header class="text-center mb-4">
                <h1 class="titulo-registro">Registro de Cliente</h1>
                <p class="subtitulo-registro">Crea tu cuenta para realizar pedidos en línea y recibir tus productos</p>
            </header>

            <!-- Banner de alerta de error general -->
            <div id="alerta-error-general" class="alerta-error-registro d-none" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <span id="mensaje-error-general">Por favor, completa todos los campos obligatorios.</span>
            </div>

            <!-- Banner de alerta de éxito -->
            <div id="alerta-exito-general" class="alert alert-success d-none" role="alert" aria-live="polite">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span id="mensaje-exito-general">Registro exitoso. Redirigiendo...</span>
            </div>

            <!-- Formulario de registro -->
            <form id="formulario-registro" novalidate autocomplete="off">

                <!-- Fila: Nombre y Apellidos -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="campo-nombre" class="etiqueta-formulario">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="campo-nombre"
                            name="nombre"
                            class="form-control control-formulario"
                            placeholder="Ej. Juan"
                            maxlength="150"
                            required
                            aria-required="true"
                        >
                        <div id="error-nombre" class="texto-error-inline">
                            El nombre es obligatorio.
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <label for="campo-apellidos" class="etiqueta-formulario">
                            Apellidos <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="campo-apellidos"
                            name="apellidos"
                            class="form-control control-formulario"
                            placeholder="Ej. Pérez López"
                            maxlength="150"
                            required
                            aria-required="true"
                        >
                        <div id="error-apellidos" class="texto-error-inline">
                            Los apellidos son obligatorios.
                        </div>
                    </div>
                </div>

                <!-- Campo: Correo Electrónico -->
                <div class="mb-3">
                    <label for="campo-correo" class="etiqueta-formulario">
                        Correo Electrónico <span class="text-danger">*</span>
                    </label>
                    <input
                        type="email"
                        id="campo-correo"
                        name="email"
                        class="form-control control-formulario"
                        placeholder="ejemplo@correo.com"
                        maxlength="100"
                        required
                        aria-required="true"
                    >
                    <div id="error-correo" class="texto-error-inline">
                        Este correo ya se encuentra registrado. Recupera tu contraseña.
                    </div>
                </div>

                <!-- Campo: Teléfono -->
                <div class="mb-3">
                    <label for="campo-telefono" class="etiqueta-formulario">
                        Teléfono (10 dígitos) <span class="text-danger">*</span>
                    </label>
                    <input
                        type="tel"
                        id="campo-telefono"
                        name="telefono"
                        class="form-control control-formulario"
                        placeholder="Ej. 7221234567"
                        maxlength="10"
                        inputmode="numeric"
                        required
                        aria-required="true"
                    >
                    <div id="error-telefono" class="texto-error-inline">
                        El formato del teléfono no es válido (ej. 10 dígitos).
                    </div>
                </div>

                <!-- Campo: Contraseña -->
                <div class="mb-3">
                    <label for="campo-contrasena" class="etiqueta-formulario">
                        Contraseña <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="campo-contrasena"
                            name="contrasena"
                            class="form-control control-formulario"
                            placeholder="Mín. 8 caracteres"
                            maxlength="255"
                            required
                            aria-required="true"
                            aria-describedby="requisitos-contrasena"
                        >
                        <button
                            type="button"
                            id="boton-visibilidad-contrasena"
                            class="btn btn-outline-secondary"
                            style="border-color: #D1D5DB; border-radius: 0 8px 8px 0;"
                            aria-label="Mostrar u ocultar contraseña"
                            title="Mostrar u ocultar contraseña"
                        >
                            <i id="icono-visibilidad-contrasena" class="bi bi-eye"></i>
                        </button>
                    </div>

                    <!-- Indicador de requisitos en tiempo real (replicado de recuperar contraseña) -->
                    <div id="requisitos-contrasena" class="requisitos-contrasena-lista">
                        <span id="req-longitud" class="requisito-item text-muted">
                            <i class="bi bi-x-circle text-danger"></i> Mín. 8 caracteres
                        </span>
                        <span id="req-mayuscula" class="requisito-item text-muted">
                            <i class="bi bi-x-circle text-danger"></i> Al menos 1 mayúscula
                        </span>
                        <span id="req-numero" class="requisito-item text-muted">
                            <i class="bi bi-x-circle text-danger"></i> Al menos 1 número
                        </span>
                    </div>

                    <div id="error-contrasena" class="texto-error-inline">
                        La contraseña es obligatoria y debe cumplir los requisitos.
                    </div>
                </div>

                <!-- Campo: Confirmar Contraseña -->
                <div class="mb-3">
                    <label for="campo-confirmar-contrasena" class="etiqueta-formulario">
                        Confirmar Contraseña <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="campo-confirmar-contrasena"
                            name="confirmar_contrasena"
                            class="form-control control-formulario"
                            placeholder="Repite tu contraseña"
                            maxlength="255"
                            required
                            aria-required="true"
                            aria-describedby="mensaje-coincidencia"
                        >
                        <button
                            type="button"
                            id="boton-visibilidad-confirmar"
                            class="btn btn-outline-secondary"
                            style="border-color: #D1D5DB; border-radius: 0 8px 8px 0;"
                            aria-label="Mostrar u ocultar confirmación de contraseña"
                            title="Mostrar u ocultar contraseña"
                        >
                            <i id="icono-visibilidad-confirmar" class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div id="mensaje-coincidencia" class="form-text mt-1"></div>
                    <div id="error-confirmar-contrasena" class="texto-error-inline">
                        Las contraseñas no coinciden.
                    </div>
                </div>

                <!-- Sección opcional: Dirección de Entrega -->
                <div class="seccion-direccion-opcional">
                    <div class="titulo-seccion-opcional">
                        <i class="bi bi-geo-alt-fill text-secondary"></i>
                        <span>Dirección de Entrega (Opcional)</span>
                    </div>

                    <div class="mb-3">
                        <label for="campo-calle" class="etiqueta-formulario">Calle y Número</label>
                        <input
                            type="text"
                            id="campo-calle"
                            name="calle"
                            class="form-control control-formulario"
                            placeholder="Ej. Av. Hidalgo #123"
                            maxlength="150"
                        >
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="campo-colonia" class="etiqueta-formulario">Colonia</label>
                            <input
                                type="text"
                                id="campo-colonia"
                                name="colonia"
                                class="form-control control-formulario"
                                placeholder="Ej. Centro"
                                maxlength="100"
                            >
                        </div>
                        <div class="col-sm-6">
                            <label for="campo-municipio" class="etiqueta-formulario">Municipio</label>
                            <select
                                id="campo-municipio"
                                name="municipio"
                                class="form-select control-formulario"
                                aria-describedby="ayuda-municipio"
                            >
                                <option value="">Selecciona un municipio</option>
                                <?php foreach (obtenerMunicipiosCobertura() as $municipioCobertura): ?>
                                    <option value="<?= htmlspecialchars($municipioCobertura, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($municipioCobertura, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="ayuda-municipio" class="form-text">
                                Municipios cercanos a Xoxocotlán, Oaxaca.
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-sm-4">
                            <label for="campo-codigo-postal" class="etiqueta-formulario">Código Postal</label>
                            <input
                                type="text"
                                id="campo-codigo-postal"
                                name="codigo_postal"
                                class="form-control control-formulario"
                                placeholder="Ej. 50000"
                                maxlength="10"
                            >
                        </div>
                        <div class="col-sm-8">
                            <label for="campo-referencia" class="etiqueta-formulario">Referencias</label>
                            <input
                                type="text"
                                id="campo-referencia"
                                name="referencia"
                                class="form-control control-formulario"
                                placeholder="Ej. Entre calle 1 y 2, portón blanco"
                                maxlength="255"
                            >
                        </div>
                    </div>
                </div>

                <!-- Botón de acción principal -->
                <div class="mb-3">
                    <button
                        type="submit"
                        id="boton-registrarse"
                        class="boton-primario-registro"
                    >
                        <span id="texto-boton-registrarse">Registrarse</span>
                        <span
                            id="spinner-registro"
                            class="spinner-border spinner-border-sm ms-2 d-none"
                            role="status"
                            aria-hidden="true"
                        ></span>
                    </button>
                </div>

                <!-- Enlace secundario para iniciar sesión -->
                <div class="text-center">
                    <span class="text-muted small">¿Ya tienes cuenta?</span>
                    <a
                        href="/proyecto-tienda/modules/1_auth/ui/login.php"
                        class="enlace-secundario small ms-1"
                    >
                        Inicia sesión
                    </a>
                </div>

            </form>
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"
        defer
    ></script>

    <!-- Servicio de validación y envío de registro (desacoplado) -->
    <script src="/proyecto-tienda/modules/1_auth/ui/registroService.js" defer></script>

</body>
</html>

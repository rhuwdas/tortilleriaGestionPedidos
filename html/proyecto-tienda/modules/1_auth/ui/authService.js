/**
 * modules/1_auth/ui/authService.js
 *
 * Servicio de autenticación del cliente (HU1).
 * Responsabilidades:
 *  1. Habilitar/deshabilitar el botón "Iniciar Sesión" según el estado de los campos.
 *  2. Alternar visibilidad de la contraseña (botón ojo).
 *  3. Enviar el formulario de forma asíncrona (Fetch API) al backend.
 *  4. Mostrar mensajes de error visuales (alerta y bordes rojos).
 *
 * Restricciones (AGENTS.md):
 *  - Vanilla JavaScript, sin frameworks.
 *  - Sin inline JS: este archivo se carga desde login.php con el atributo defer.
 *  - Las peticiones HTTP se realizan mediante la Fetch API nativa.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Referencias al DOM (disponibles porque el script se carga con defer)
// ─────────────────────────────────────────────────────────────────────────────

/** @type {HTMLFormElement} Formulario de inicio de sesión */
const formularioLogin = document.getElementById('formulario-login');

/** @type {HTMLInputElement} Campo de correo electrónico */
const campoCorreo = document.getElementById('campo-correo');

/** @type {HTMLInputElement} Campo de contraseña */
const campoContrasena = document.getElementById('campo-contrasena');

/** @type {HTMLButtonElement} Botón de envío del formulario */
const botonIngresar = document.getElementById('boton-ingresar');

/** @type {HTMLButtonElement} Botón para alternar visibilidad de contraseña */
const botonVisibilidad = document.getElementById('boton-visibilidad');

/** @type {HTMLElement} Ícono del botón de visibilidad */
const iconoVisibilidad = document.getElementById('icono-visibilidad');

/** @type {HTMLElement} Contenedor de la alerta de error */
const alertaError = document.getElementById('alerta-error');

/** @type {HTMLElement} Mensaje de texto dentro de la alerta */
const alertaErrorMensaje = document.getElementById('alerta-error-mensaje');

/** @type {HTMLElement} Texto del botón de envío */
const botonTexto = document.getElementById('boton-texto');

/** @type {HTMLElement} Spinner de carga dentro del botón */
const botonSpinner = document.getElementById('boton-spinner');

// ─────────────────────────────────────────────────────────────────────────────
// Función: verificarCampos
// Habilita o deshabilita el botón "Iniciar Sesión" según si ambos campos
// contienen texto. Cumple la tarea 1.2 del sprint.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Evalúa si ambos campos (correo y contraseña) tienen contenido.
 * Habilita el botón de envío solo cuando ambos están llenos.
 *
 * @returns {void}
 */
const verificarCampos = () => {
    const correoLleno    = campoCorreo.value.trim().length > 0;
    const contrasenaLlena = campoContrasena.value.length > 0;

    const habilitarBoton = correoLleno && contrasenaLlena;

    botonIngresar.disabled = !habilitarBoton;
    botonIngresar.setAttribute('aria-disabled', String(!habilitarBoton));
};

// Escuchar cambios en ambos campos para actualizar el estado del botón en tiempo real
campoCorreo.addEventListener('input', verificarCampos);
campoContrasena.addEventListener('input', verificarCampos);

// ─────────────────────────────────────────────────────────────────────────────
// Función: alternarVisibilidad
// Alterna el tipo del campo contraseña entre "password" y "text".
// Cambia el ícono del botón según el estado actual.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Alterna la visibilidad de los caracteres del campo contraseña.
 *
 * @returns {void}
 */
const alternarVisibilidad = () => {
    const estaOculta = campoContrasena.type === 'password';

    // Cambiar el tipo del campo para mostrar u ocultar los caracteres
    campoContrasena.type = estaOculta ? 'text' : 'password';

    // Actualizar el ícono: ojo abierto = visible, ojo tachado = oculta
    iconoVisibilidad.className = estaOculta ? 'bi bi-eye-slash' : 'bi bi-eye';

    // Actualizar el aria-label para accesibilidad
    botonVisibilidad.setAttribute(
        'aria-label',
        estaOculta ? 'Ocultar contraseña' : 'Mostrar contraseña'
    );
};

botonVisibilidad.addEventListener('click', alternarVisibilidad);

// ─────────────────────────────────────────────────────────────────────────────
// Funciones auxiliares: UI de error y estado del botón
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Muestra la alerta de error con el mensaje indicado y marca
 * los campos del formulario con borde rojo (is-invalid).
 *
 * @param {string} mensaje Texto a mostrar en la alerta.
 * @returns {void}
 */
const mostrarError = (mensaje) => {
    // Mostrar alerta superior
    alertaErrorMensaje.textContent = mensaje;
    alertaError.classList.remove('d-none');

    // Marcar ambos campos con borde rojo (no revelar cuál falló — requisito HU1)
    campoCorreo.classList.add('is-invalid');
    campoContrasena.classList.add('is-invalid');
};

/**
 * Oculta la alerta de error y limpia las marcas de validación de los campos.
 *
 * @returns {void}
 */
const ocultarError = () => {
    alertaError.classList.add('d-none');
    campoCorreo.classList.remove('is-invalid');
    campoContrasena.classList.remove('is-invalid');
};

/**
 * Muestra el estado de carga en el botón mientras se procesa la solicitud.
 *
 * @returns {void}
 */
const activarCarga = () => {
    botonIngresar.disabled = true;
    botonTexto.textContent = 'Verificando...';
    botonSpinner.classList.remove('d-none');
};

/**
 * Restaura el botón de envío a su estado normal (sin carga).
 *
 * @returns {void}
 */
const desactivarCarga = () => {
    botonIngresar.disabled = false;
    botonTexto.textContent = 'Iniciar Sesión';
    botonSpinner.classList.add('d-none');
};

// ─────────────────────────────────────────────────────────────────────────────
// Función: enviarFormulario
// Maneja el evento submit del formulario, envía los datos al backend
// mediante Fetch API y gestiona la respuesta (éxito o error).
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Intercepta el envío del formulario, realiza la petición POST asíncrona
 * al endpoint de login del backend y procesa la respuesta JSON.
 *
 * Flujo:
 *  1. Prevenir el envío HTML estándar del formulario.
 *  2. Validar campos vacíos en el cliente.
 *  3. Enviar credenciales al backend vía Fetch (POST + JSON).
 *  4. En éxito: redirigir al dashboard según el rol devuelto.
 *  5. En error: mostrar el mensaje de error sin revelar cuál campo falló.
 *
 * @param {SubmitEvent} evento Evento de envío del formulario.
 * @returns {Promise<void>}
 */
const enviarFormulario = async (evento) => {
    // Prevenir el comportamiento predeterminado (recarga de página)
    evento.preventDefault();

    // Limpiar errores previos antes de una nueva validación
    ocultarError();

    const correo     = campoCorreo.value.trim();
    const contrasena = campoContrasena.value;

    // Validación de campos vacíos en el cliente (doble verificación)
    if (!correo || !contrasena) {
        mostrarError('Todos los campos son obligatorios.');
        return;
    }

    // Activar estado de carga en el botón
    activarCarga();

    try {
        // Enviar credenciales al endpoint del backend
        const respuesta = await fetch('/proyecto-tienda/modules/1_auth/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin', // Incluir cookies de sesión PHP
            body: JSON.stringify({ correo, contrasena }),
        });

        // Parsear la respuesta JSON del backend
        const datos = await respuesta.json();

        if (respuesta.ok && datos.exito) {
            // Autenticación exitosa: redirigir según el rol del usuario
            window.location.href = datos.redireccion ?? '/proyecto-tienda/';
        } else {
            // Error de autenticación: mostrar mensaje genérico (no revelar cuál campo falló)
            mostrarError(datos.mensaje ?? 'Correo o contraseña incorrectos.');
            desactivarCarga();
        }

    } catch (errorRed) {
        // Error de red o falla inesperada del servidor
        mostrarError('Error de conexión. Intenta nuevamente.');
        desactivarCarga();
    }
};

// Registrar el manejador del evento submit
formularioLogin.addEventListener('submit', enviarFormulario);

// Ocultar el error cuando el usuario empiece a corregir los campos
campoCorreo.addEventListener('input', ocultarError);
campoContrasena.addEventListener('input', ocultarError);

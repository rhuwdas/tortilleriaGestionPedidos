/**
 * modules/1_auth/ui/registroService.js
 *
 * Servicio de validación e interacción para el registro de clientes (HU4).
 * Responsabilidades (Tarea 4.2):
 *  1. Restringir estrictamente la entrada del teléfono a 10 dígitos numéricos.
 *  2. Validar en tiempo real y al enviar el formato del correo electrónico.
 *  3. Validar presencia de campos requeridos (nombre, apellidos, contraseña).
 *  4. Alternar la visibilidad de la contraseña.
 *  5. Gestionar la presentación visual accesible de errores en la interfaz.
 *
 * Restricciones (AGENTS.md):
 *  - JavaScript Vanilla ES6+ sin librerías externas.
 *  - Sin scripts inline: cargado con atributo defer desde registro.php.
 *  - Nombres descriptivos en español sin abreviaturas.
 */

document.addEventListener('DOMContentLoaded', () => {

    // ─────────────────────────────────────────────────────────────────────────
    // Referencias al DOM
    // ─────────────────────────────────────────────────────────────────────────
    const formularioRegistro = document.getElementById('formulario-registro');
    const campoNombre = document.getElementById('campo-nombre');
    const campoApellidos = document.getElementById('campo-apellidos');
    const campoCorreo = document.getElementById('campo-correo');
    const campoTelefono = document.getElementById('campo-telefono');
    const campoContrasena = document.getElementById('campo-contrasena');
    const botonVisibilidadContrasena = document.getElementById('boton-visibilidad-contrasena');
    const iconoVisibilidadContrasena = document.getElementById('icono-visibilidad-contrasena');
    const botonRegistrarse = document.getElementById('boton-registrarse');

    // Mensajes de error en línea
    const errorNombre = document.getElementById('error-nombre');
    const errorApellidos = document.getElementById('error-apellidos');
    const errorCorreo = document.getElementById('error-correo');
    const errorTelefono = document.getElementById('error-telefono');
    const errorContrasena = document.getElementById('error-contrasena');
    const alertaErrorGeneral = document.getElementById('alerta-error-general');
    const mensajeErrorGeneral = document.getElementById('mensaje-error-general');

    // Expresión regular estándar para validación estricta de correo electrónico
    const expresionCorreo = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Alternar visibilidad de contraseña
    // ─────────────────────────────────────────────────────────────────────────
    if (botonVisibilidadContrasena && campoContrasena && iconoVisibilidadContrasena) {
        botonVisibilidadContrasena.addEventListener('click', () => {
            const esPassword = campoContrasena.getAttribute('type') === 'password';
            campoContrasena.setAttribute('type', esPassword ? 'text' : 'password');
            iconoVisibilidadContrasena.className = esPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            campoContrasena.focus();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Restricción estricta de teléfono a 10 dígitos numéricos
    // ─────────────────────────────────────────────────────────────────────────
    if (campoTelefono) {
        // Filtrar inmediatamente caracteres no numéricos al escribir y truncar a 10
        campoTelefono.addEventListener('input', () => {
            const valorFiltrado = campoTelefono.value.replace(/\D/g, '').slice(0, 10);
            campoTelefono.value = valorFiltrado;

            // Ocultar error si ya completó los 10 dígitos
            if (valorFiltrado.length === 10) {
                limpiarErrorCampo(campoTelefono, errorTelefono);
            }
        });

        // Validar al salir del campo
        campoTelefono.addEventListener('blur', () => {
            validarTelefono();
        });
    }

    /**
     * Valida que el campo de teléfono contenga exactamente 10 dígitos numéricos.
     * @returns {boolean} True si es válido, False de lo contrario.
     */
    const validarTelefono = () => {
        const valor = campoTelefono.value.trim();
        if (valor.length === 0) {
            mostrarErrorCampo(campoTelefono, errorTelefono, 'El teléfono es obligatorio.');
            return false;
        }

        if (valor.length !== 10 || !/^\d{10}$/.test(valor)) {
            mostrarErrorCampo(campoTelefono, errorTelefono, 'El teléfono debe contener 10 dígitos.');
            return false;
        }

        limpiarErrorCampo(campoTelefono, errorTelefono);
        return true;
    };

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Validación de formato de correo electrónico
    // ─────────────────────────────────────────────────────────────────────────
    if (campoCorreo) {
        campoCorreo.addEventListener('input', () => {
            if (expresionCorreo.test(campoCorreo.value.trim())) {
                limpiarErrorCampo(campoCorreo, errorCorreo);
            }
        });

        campoCorreo.addEventListener('blur', () => {
            validarCorreo();
        });
    }

    /**
     * Valida que el correo electrónico no esté vacío y cumpla el formato de email.
     * @returns {boolean} True si es válido, False de lo contrario.
     */
    const validarCorreo = () => {
        const valor = campoCorreo.value.trim();
        if (valor.length === 0) {
            mostrarErrorCampo(campoCorreo, errorCorreo, 'El correo electrónico es obligatorio.');
            return false;
        }

        if (!expresionCorreo.test(valor)) {
            mostrarErrorCampo(campoCorreo, errorCorreo, 'Ingresa un correo electrónico con formato válido.');
            return false;
        }

        limpiarErrorCampo(campoCorreo, errorCorreo);
        return true;
    };

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Funciones auxiliares para visualización de errores
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Muestra el estilo de error y el mensaje explicativo de un campo.
     * @param {HTMLInputElement} campo
     * @param {HTMLElement} elementoError
     * @param {string} mensaje
     */
    const mostrarErrorCampo = (campo, elementoError, mensaje) => {
        campo.classList.add('is-invalid');
        campo.style.borderColor = '#DC2626';
        if (elementoError) {
            elementoError.textContent = mensaje;
            elementoError.style.display = 'block';
        }
    };

    /**
     * Limpia los estilos de error y oculta el mensaje explicativo.
     * @param {HTMLInputElement} campo
     * @param {HTMLElement} elementoError
     */
    const limpiarErrorCampo = (campo, elementoError) => {
        campo.classList.remove('is-invalid');
        campo.style.borderColor = '#D1D5DB';
        if (elementoError) {
            elementoError.style.display = 'none';
        }
    };

    /**
     * Valida un campo de texto genérico para asegurar que no esté vacío.
     * @param {HTMLInputElement} campo
     * @param {HTMLElement} elementoError
     * @param {string} mensaje
     * @returns {boolean}
     */
    const validarCampoRequerido = (campo, elementoError, mensaje) => {
        if (campo.value.trim().length === 0) {
            mostrarErrorCampo(campo, elementoError, mensaje);
            return false;
        }
        limpiarErrorCampo(campo, elementoError);
        return true;
    };

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Validación integral previa al envío del formulario
    // ─────────────────────────────────────────────────────────────────────────
    if (formularioRegistro) {
        formularioRegistro.addEventListener('submit', (evento) => {
            evento.preventDefault();

            // Ocultar alerta general previa
            if (alertaErrorGeneral) {
                alertaErrorGeneral.classList.add('d-none');
            }

            const nombreValido = validarCampoRequerido(campoNombre, errorNombre, 'El nombre es obligatorio.');
            const apellidosValidos = validarCampoRequerido(campoApellidos, errorApellidos, 'Los apellidos son obligatorios.');
            const correoValido = validarCorreo();
            const telefonoValido = validarTelefono();
            const contrasenaValida = validarCampoRequerido(campoContrasena, errorContrasena, 'La contraseña es obligatoria.');

            if (!nombreValido || !apellidosValidos || !correoValido || !telefonoValido || !contrasenaValida) {
                if (alertaErrorGeneral && mensajeErrorGeneral) {
                    mensajeErrorGeneral.textContent = 'Por favor, completa todos los campos obligatorios correctamente.';
                    alertaErrorGeneral.classList.remove('d-none');
                }
                return;
            }

            // Los datos son válidos del lado del cliente.
            // La conexión con el endpoint backend se desarrolla en las siguientes tareas (4.3 / 4.4).
            console.log('Validación en cliente exitosa. Listo para enviar al endpoint backend.');
        });
    }

});

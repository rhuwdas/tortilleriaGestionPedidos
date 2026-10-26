/**
 * modules/1_auth/ui/registroService.js
 *
 * Servicio de validación e interacción para el registro de clientes (HU4).
 * Responsabilidades (Tareas 4.2 y 4.5):
 *  1. Restringir estrictamente la entrada del teléfono a 10 dígitos numéricos.
 *  2. Validar en tiempo real y al enviar el formato del correo electrónico.
 *  3. Validar presencia de campos requeridos (nombre, apellidos, contraseña).
 *  4. Alternar la visibilidad de la contraseña.
 *  5. Enviar la petición POST asíncrona mediante Fetch API.
 *  6. Gestionar el manejo de errores en interfaz para los flujos alternos:
 *     - Correo duplicado ("El correo ingresado ya se encuentra registrado") (CA-HU4-001).
 *     - Teléfono inválido ("El teléfono debe contener 10 dígitos") (CA-HU4-002).
 *     - Dirección fuera de alcance ("La dirección está fuera de alcance").
 *     - Campos faltantes ("Por favor, completa todos los campos obligatorios.").
 *  7. Manejar el estado de carga y retroalimentación de éxito (CA-003).
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
    const spinnerRegistro = document.getElementById('spinner-registro');
    const textoBotonRegistrarse = document.getElementById('texto-boton-registrarse');

    // Campos de dirección opcional
    const campoCalle = document.getElementById('campo-calle');
    const campoColonia = document.getElementById('campo-colonia');
    const campoMunicipio = document.getElementById('campo-municipio');
    const campoCodigoPostal = document.getElementById('campo-codigo-postal');
    const campoReferencia = document.getElementById('campo-referencia');

    // Mensajes de error y alertas
    const errorNombre = document.getElementById('error-nombre');
    const errorApellidos = document.getElementById('error-apellidos');
    const errorCorreo = document.getElementById('error-correo');
    const errorTelefono = document.getElementById('error-telefono');
    const errorContrasena = document.getElementById('error-contrasena');
    const alertaErrorGeneral = document.getElementById('alerta-error-general');
    const mensajeErrorGeneral = document.getElementById('mensaje-error-general');
    const alertaExitoGeneral = document.getElementById('alerta-exito-general');
    const mensajeExitoGeneral = document.getElementById('mensaje-exito-general');

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
     * Muestra la alerta de error general superior.
     * @param {string} mensaje
     */
    const mostrarAlertaGeneral = (mensaje) => {
        if (alertaErrorGeneral && mensajeErrorGeneral) {
            mensajeErrorGeneral.textContent = mensaje;
            alertaErrorGeneral.classList.remove('d-none');
            alertaErrorGeneral.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    /**
     * Oculta las alertas generales.
     */
    const ocultarAlertas = () => {
        if (alertaErrorGeneral) {
            alertaErrorGeneral.classList.add('d-none');
        }
        if (alertaExitoGeneral) {
            alertaExitoGeneral.classList.add('d-none');
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

    /**
     * Controla el estado visual de carga en el botón de envío.
     * @param {boolean} cargando
     */
    const alternarEstadoCargando = (cargando) => {
        if (!botonRegistrarse) return;
        botonRegistrarse.disabled = cargando;
        if (spinnerRegistro) {
            spinnerRegistro.classList.toggle('d-none', !cargando);
        }
        if (textoBotonRegistrarse) {
            textoBotonRegistrarse.textContent = cargando ? 'Registrando...' : 'Registrarse';
        }
    };

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Envío asíncrono y manejo de flujos alternos (Tarea 4.5)
    // ─────────────────────────────────────────────────────────────────────────
    if (formularioRegistro) {
        formularioRegistro.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            ocultarAlertas();

            // Validación del lado del cliente
            const nombreValido = validarCampoRequerido(campoNombre, errorNombre, 'El nombre es obligatorio.');
            const apellidosValidos = validarCampoRequerido(campoApellidos, errorApellidos, 'Los apellidos son obligatorios.');
            const correoValido = validarCorreo();
            const telefonoValido = validarTelefono();
            const contrasenaValida = validarCampoRequerido(campoContrasena, errorContrasena, 'La contraseña es obligatoria.');

            if (!nombreValido || !apellidosValidos || !correoValido || !telefonoValido || !contrasenaValida) {
                mostrarAlertaGeneral('Por favor, completa todos los campos obligatorios.');
                return;
            }

            // Preparar carga útil (payload)
            const cargaUtil = {
                nombre: campoNombre.value.trim(),
                apellidos: campoApellidos.value.trim(),
                email: campoCorreo.value.trim(),
                telefono: campoTelefono.value.trim(),
                contrasena: campoContrasena.value,
                calle: campoCalle ? campoCalle.value.trim() : '',
                colonia: campoColonia ? campoColonia.value.trim() : '',
                municipio: campoMunicipio ? campoMunicipio.value.trim() : '',
                codigo_postal: campoCodigoPostal ? campoCodigoPostal.value.trim() : '',
                referencia: campoReferencia ? campoReferencia.value.trim() : ''
            };

            alternarEstadoCargando(true);

            try {
                const respuesta = await fetch('/proyecto-tienda/modules/1_auth/api/registro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json; charset=UTF-8',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(cargaUtil)
                });

                const resultado = await respuesta.json();

                if (respuesta.ok && resultado.exito) {
                    // Éxito: Mostrar mensaje y redirigir
                    if (alertaExitoGeneral && mensajeExitoGeneral) {
                        mensajeExitoGeneral.textContent = resultado.mensaje || 'Registro exitoso. Redirigiendo...';
                        alertaExitoGeneral.classList.remove('d-none');
                    }
                    setTimeout(() => {
                        window.location.href = resultado.redireccion || '/proyecto-tienda/modules/2_catalog/ui/catalogo.php';
                    }, 1200);
                    return;
                }

                // Manejo de flujos alternos y errores según código de estado o mensaje
                if (respuesta.status === 409 || (resultado.mensaje && resultado.mensaje.includes('correo'))) {
                    // Flujo alterno: Correo duplicado (CA-HU4-001)
                    const mensajeCorreo = 'El correo ingresado ya se encuentra registrado.';
                    mostrarErrorCampo(campoCorreo, errorCorreo, mensajeCorreo);
                    mostrarAlertaGeneral(mensajeCorreo);
                    campoCorreo.focus();

                } else if (respuesta.status === 422 && resultado.mensaje && resultado.mensaje.includes('teléfono')) {
                    // Flujo alterno: Teléfono inválido (CA-HU4-002)
                    const mensajeTelefono = 'El teléfono debe contener 10 dígitos.';
                    mostrarErrorCampo(campoTelefono, errorTelefono, mensajeTelefono);
                    mostrarAlertaGeneral(mensajeTelefono);
                    campoTelefono.focus();

                } else if (resultado.codigo === 'DIRECCION_FUERA_DE_ALCANCE' || (resultado.mensaje && resultado.mensaje.includes('fuera de alcance'))) {
                    // Flujo alterno: Dirección fuera de alcance
                    const mensajeDireccion = 'La dirección está fuera de alcance.';
                    if (campoMunicipio) {
                        campoMunicipio.classList.add('is-invalid');
                        campoMunicipio.style.borderColor = '#DC2626';
                    }
                    mostrarAlertaGeneral(mensajeDireccion);

                } else {
                    // Error genérico o campos incompletos
                    mostrarAlertaGeneral(resultado.mensaje || 'Por favor, completa todos los campos obligatorios.');
                }

            } catch (error) {
                console.error('Error en la petición de registro:', error);
                mostrarAlertaGeneral('No se pudo establecer conexión con el servidor. Intenta de nuevo.');
            } finally {
                alternarEstadoCargando(false);
            }
        });
    }

});

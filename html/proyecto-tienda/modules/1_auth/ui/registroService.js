/**
 * modules/1_auth/ui/registroService.js
 *
 * Servicio de validación e interacción para el registro de clientes (HU4).
 * Responsabilidades:
 *  1. Restringir estrictamente la entrada del teléfono a 10 dígitos numéricos.
 *  2. Validar en tiempo real y al enviar el formato del correo electrónico.
 *  3. Validar robustez de contraseña en tiempo real (mín. 8 caracteres, 1 mayúscula, 1 número)
 *     replicando las reglas de la pantalla de recuperación de contraseña.
 *  4. Validar confirmación y coincidencia de contraseña.
 *  5. Alternar la visibilidad de contraseña y confirmación.
 *  6. Enviar la petición POST asíncrona mediante Fetch API.
 *  7. Gestionar el manejo de errores en interfaz para los flujos alternos:
 *     - Correo duplicado en base de datos.
 *     - Teléfono duplicado en base de datos.
 *     - Nombre y apellidos duplicados en base de datos.
 *     - Dirección fuera de alcance.
 *     - Formato de teléfono inválido (10 dígitos).
 *     - Campos faltantes.
 *  8. Estado de carga y retroalimentación de éxito.
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
    const campoConfirmarContrasena = document.getElementById('campo-confirmar-contrasena');
    const botonVisibilidadContrasena = document.getElementById('boton-visibilidad-contrasena');
    const iconoVisibilidadContrasena = document.getElementById('icono-visibilidad-contrasena');
    const botonVisibilidadConfirmar = document.getElementById('boton-visibilidad-confirmar');
    const iconoVisibilidadConfirmar = document.getElementById('icono-visibilidad-confirmar');
    const botonRegistrarse = document.getElementById('boton-registrarse');
    const spinnerRegistro = document.getElementById('spinner-registro');
    const textoBotonRegistrarse = document.getElementById('texto-boton-registrarse');

    // Indicadores de requisitos de contraseña (replicados de recuperación)
    const reqLongitud = document.getElementById('req-longitud');
    const reqMayuscula = document.getElementById('req-mayuscula');
    const reqNumero = document.getElementById('req-numero');
    const mensajeCoincidencia = document.getElementById('mensaje-coincidencia');

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
    const errorConfirmarContrasena = document.getElementById('error-confirmar-contrasena');
    const alertaErrorGeneral = document.getElementById('alerta-error-general');
    const mensajeErrorGeneral = document.getElementById('mensaje-error-general');
    const alertaExitoGeneral = document.getElementById('alerta-exito-general');
    const mensajeExitoGeneral = document.getElementById('mensaje-exito-general');

    // Expresión regular estándar para validación estricta de correo electrónico
    const expresionCorreo = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Alternar visibilidad de contraseñas
    // ─────────────────────────────────────────────────────────────────────────
    const configurarBotonVisibilidad = (boton, campo, icono) => {
        if (!boton || !campo || !icono) return;
        boton.addEventListener('click', () => {
            const esPassword = campo.getAttribute('type') === 'password';
            campo.setAttribute('type', esPassword ? 'text' : 'password');
            icono.className = esPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            campo.focus();
        });
    };

    configurarBotonVisibilidad(botonVisibilidadContrasena, campoContrasena, iconoVisibilidadContrasena);
    configurarBotonVisibilidad(botonVisibilidadConfirmar, campoConfirmarContrasena, iconoVisibilidadConfirmar);

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Validación y restricción de teléfono (10 dígitos numéricos)
    // ─────────────────────────────────────────────────────────────────────────
    if (campoTelefono) {
        campoTelefono.addEventListener('input', () => {
            const valorFiltrado = campoTelefono.value.replace(/\D/g, '').slice(0, 10);
            campoTelefono.value = valorFiltrado;

            if (valorFiltrado.length === 10) {
                limpiarErrorCampo(campoTelefono, errorTelefono);
            }
        });

        campoTelefono.addEventListener('blur', () => {
            validarTelefono();
        });
    }

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
    // 3. Validación de correo electrónico
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
    // 4. Validación de robustez de contraseña (mín. 8 chars, 1 mayúscula, 1 número)
    // ─────────────────────────────────────────────────────────────────────────
    const actualizarIndicadorRequisito = (elemento, cumple, texto) => {
        if (!elemento) return;
        if (cumple) {
            elemento.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i> ${texto}`;
            elemento.classList.remove('text-muted');
            elemento.classList.add('text-success');
        } else {
            elemento.innerHTML = `<i class="bi bi-x-circle text-danger"></i> ${texto}`;
            elemento.classList.remove('text-success');
            elemento.classList.add('text-muted');
        }
    };

    const evaluarContrasena = () => {
        const contrasena = campoContrasena ? campoContrasena.value : '';
        const confirmacion = campoConfirmarContrasena ? campoConfirmarContrasena.value : '';

        const cumpleLongitud = contrasena.length >= 8;
        const cumpleMayuscula = /[A-Z]/.test(contrasena);
        const cumpleNumero = /[0-9]/.test(contrasena);

        actualizarIndicadorRequisito(reqLongitud, cumpleLongitud, 'Mín. 8 caracteres');
        actualizarIndicadorRequisito(reqMayuscula, cumpleMayuscula, 'Al menos 1 mayúscula');
        actualizarIndicadorRequisito(reqNumero, cumpleNumero, 'Al menos 1 número');

        // Validación de coincidencia con el campo de confirmación
        if (campoConfirmarContrasena && confirmacion.length > 0) {
            const coinciden = contrasena === confirmacion;
            if (coinciden) {
                if (mensajeCoincidencia) {
                    mensajeCoincidencia.textContent = '✓ Las contraseñas coinciden.';
                    mensajeCoincidencia.className = 'form-text text-success fw-medium';
                }
                limpiarErrorCampo(campoConfirmarContrasena, errorConfirmarContrasena);
            } else {
                if (mensajeCoincidencia) {
                    mensajeCoincidencia.textContent = 'Las contraseñas no coinciden.';
                    mensajeCoincidencia.className = 'form-text text-danger fw-medium';
                }
            }
        } else if (mensajeCoincidencia) {
            mensajeCoincidencia.textContent = '';
        }

        return cumpleLongitud && cumpleMayuscula && cumpleNumero;
    };

    if (campoContrasena) {
        campoContrasena.addEventListener('input', () => {
            evaluarContrasena();
            if (evaluarContrasena()) {
                limpiarErrorCampo(campoContrasena, errorContrasena);
            }
        });
    }

    if (campoConfirmarContrasena) {
        campoConfirmarContrasena.addEventListener('input', () => {
            evaluarContrasena();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Funciones auxiliares de estilo y errores
    // ─────────────────────────────────────────────────────────────────────────
    const mostrarErrorCampo = (campo, elementoError, mensaje) => {
        if (!campo) return;
        campo.classList.add('is-invalid');
        campo.style.borderColor = '#DC2626';
        if (elementoError) {
            elementoError.textContent = mensaje;
            elementoError.style.display = 'block';
        }
    };

    const limpiarErrorCampo = (campo, elementoError) => {
        if (!campo) return;
        campo.classList.remove('is-invalid');
        campo.style.borderColor = '#D1D5DB';
        if (elementoError) {
            elementoError.style.display = 'none';
        }
    };

    const mostrarAlertaGeneral = (mensaje) => {
        if (alertaErrorGeneral && mensajeErrorGeneral) {
            mensajeErrorGeneral.textContent = mensaje;
            alertaErrorGeneral.classList.remove('d-none');
            alertaErrorGeneral.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    const ocultarAlertas = () => {
        if (alertaErrorGeneral) alertaErrorGeneral.classList.add('d-none');
        if (alertaExitoGeneral) alertaExitoGeneral.classList.add('d-none');
    };

    const validarCampoRequerido = (campo, elementoError, mensaje) => {
        if (!campo || campo.value.trim().length === 0) {
            mostrarErrorCampo(campo, elementoError, mensaje);
            return false;
        }
        limpiarErrorCampo(campo, elementoError);
        return true;
    };

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
    // 6. Envío del formulario y validación integral
    // ─────────────────────────────────────────────────────────────────────────
    if (formularioRegistro) {
        formularioRegistro.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            ocultarAlertas();

            // Validación de campos del cliente
            const nombreValido = validarCampoRequerido(campoNombre, errorNombre, 'El nombre es obligatorio.');
            const apellidosValidos = validarCampoRequerido(campoApellidos, errorApellidos, 'Los apellidos son obligatorios.');
            const correoValido = validarCorreo();
            const telefonoValido = validarTelefono();
            const contrasenaValida = evaluarContrasena();

            if (!contrasenaValida) {
                mostrarErrorCampo(campoContrasena, errorContrasena, 'La contraseña debe tener mín. 8 caracteres, al menos 1 mayúscula y 1 número.');
            }

            let confirmacionValida = true;
            if (campoConfirmarContrasena) {
                if (campoConfirmarContrasena.value !== campoContrasena.value) {
                    mostrarErrorCampo(campoConfirmarContrasena, errorConfirmarContrasena, 'Las contraseñas no coinciden.');
                    confirmacionValida = false;
                } else {
                    limpiarErrorCampo(campoConfirmarContrasena, errorConfirmarContrasena);
                }
            }

            if (!nombreValido || !apellidosValidos || !correoValido || !telefonoValido || !contrasenaValida || !confirmacionValida) {
                mostrarAlertaGeneral('Por favor, completa todos los campos obligatorios correctamente.');
                return;
            }

            // Preparar carga útil (payload)
            const cargaUtil = {
                nombre: campoNombre.value.trim(),
                apellidos: campoApellidos.value.trim(),
                email: campoCorreo.value.trim(),
                telefono: campoTelefono.value.trim(),
                contrasena: campoContrasena.value,
                confirmar_contrasena: campoConfirmarContrasena ? campoConfirmarContrasena.value : '',
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
                    // Éxito: retroalimentación visual y redirección
                    if (alertaExitoGeneral && mensajeExitoGeneral) {
                        mensajeExitoGeneral.textContent = resultado.mensaje || 'Registro exitoso. Redirigiendo...';
                        alertaExitoGeneral.classList.remove('d-none');
                    }
                    setTimeout(() => {
                        window.location.href = resultado.redireccion || '/proyecto-tienda/modules/2_catalog/ui/catalogo.php';
                    }, 1200);
                    return;
                }

                // Manejo de flujos alternos y verificación en base de datos
                if (resultado.campo === 'email' || (resultado.mensaje && resultado.mensaje.includes('correo'))) {
                    // Validación en BD: Correo ya registrado
                    const mensaje = 'El correo ingresado ya se encuentra registrado.';
                    mostrarErrorCampo(campoCorreo, errorCorreo, mensaje);
                    mostrarAlertaGeneral(mensaje);
                    campoCorreo.focus();

                } else if (resultado.campo === 'telefono' || (resultado.mensaje && resultado.mensaje.includes('teléfono'))) {
                    // Validación en BD o formato: Teléfono ya registrado o inválido
                    const mensaje = resultado.mensaje || 'El número de teléfono ya se encuentra registrado.';
                    mostrarErrorCampo(campoTelefono, errorTelefono, mensaje);
                    mostrarAlertaGeneral(mensaje);
                    campoTelefono.focus();

                } else if (resultado.campo === 'nombre' || (resultado.mensaje && resultado.mensaje.includes('nombre y apellidos'))) {
                    // Validación en BD: Nombre y apellidos ya registrados
                    const mensaje = 'Ya existe un cliente registrado con este nombre y apellidos.';
                    mostrarErrorCampo(campoNombre, errorNombre, mensaje);
                    mostrarErrorCampo(campoApellidos, errorApellidos, mensaje);
                    mostrarAlertaGeneral(mensaje);
                    campoNombre.focus();

                } else if (resultado.codigo === 'DIRECCION_FUERA_DE_ALCANCE' || (resultado.mensaje && resultado.mensaje.includes('fuera de alcance'))) {
                    // Flujo alterno: Dirección fuera de alcance
                    const mensaje = 'La dirección está fuera de alcance.';
                    if (campoMunicipio) {
                        campoMunicipio.classList.add('is-invalid');
                        campoMunicipio.style.borderColor = '#DC2626';
                    }
                    mostrarAlertaGeneral(mensaje);

                } else if (resultado.campo === 'contrasena' || (resultado.mensaje && resultado.mensaje.includes('contraseña'))) {
                    // Error de validación de contraseña
                    mostrarErrorCampo(campoContrasena, errorContrasena, resultado.mensaje);
                    mostrarAlertaGeneral(resultado.mensaje);
                    campoContrasena.focus();

                } else {
                    // Error genérico
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

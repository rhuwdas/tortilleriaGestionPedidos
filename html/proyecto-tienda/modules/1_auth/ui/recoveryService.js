/**
 * modules/1_auth/ui/recoveryService.js
 *
 * Servicio JS del flujo de recuperación de contraseña (HU2).
 *
 * Responsabilidades por paso:
 *  Paso 1 / 3 — Habilitar "Enviar código" cuando el campo correo tenga valor.
 *  Paso 2 / 4 — Habilitar "Verificar" cuando el código tenga exactamente 6 dígitos.
 *               Filtrar caracteres no numéricos en tiempo real.
 *  Paso 5     — Validar nueva contraseña (mín. 8 chars, 1 mayúscula, 1 número),
 *               comparar confirmación y habilitar botón solo cuando todo sea válido.
 *               Toggle de visibilidad de contraseña.
 *
 * Restricciones (AGENTS.md):
 *  - Vanilla JavaScript, sin frameworks ni bibliotecas externas.
 *  - Sin JS inline: cargado desde recuperar.php con defer.
 */

// ─────────────────────────────────────────────────────────────────────────────
// PASO 1 / 3 — Formulario de solicitud de correo
// ─────────────────────────────────────────────────────────────────────────────

const campoCorreoRecuperacion = document.getElementById('campo-correo-recuperacion');
const botonEnviarCodigo       = document.getElementById('boton-enviar-codigo');

if (campoCorreoRecuperacion && botonEnviarCodigo) {
    /**
     * Habilita el botón "Enviar código" solo cuando el campo correo tiene contenido.
     */
    const verificarCorreo = () => {
        const lleno = campoCorreoRecuperacion.value.trim().length > 0;
        botonEnviarCodigo.disabled = !lleno;
        botonEnviarCodigo.setAttribute('aria-disabled', String(!lleno));
    };

    campoCorreoRecuperacion.addEventListener('input', verificarCorreo);
    // Ejecutar al cargar por si el campo ya tiene valor (reintento en paso 3)
    verificarCorreo();
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 2 / 4 — Formulario de validación del código OTP
// ─────────────────────────────────────────────────────────────────────────────

const campoCodigo    = document.getElementById('campo-codigo');
const botonVerificar = document.getElementById('boton-verificar');

if (campoCodigo && botonVerificar) {
    /**
     * Habilita "Verificar" solo con exactamente 6 dígitos.
     * Filtra cualquier carácter que no sea dígito en tiempo real.
     */
    const verificarCodigo = () => {
        // Eliminar cualquier carácter no numérico
        campoCodigo.value = campoCodigo.value.replace(/\D/g, '');

        const codigoCompleto = campoCodigo.value.length === 6;
        botonVerificar.disabled = !codigoCompleto;
        botonVerificar.setAttribute('aria-disabled', String(!codigoCompleto));
    };

    campoCodigo.addEventListener('input', verificarCodigo);
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 5 — Formulario de nueva contraseña
// ─────────────────────────────────────────────────────────────────────────────

const campoNuevaContrasena      = document.getElementById('campo-nueva-contrasena');
const campoConfirmarContrasena  = document.getElementById('campo-confirmar-contrasena');
const botonGuardarContrasena    = document.getElementById('boton-guardar-contrasena');
const mensajeCoincidencia       = document.getElementById('mensaje-coincidencia');

// Indicadores de requisitos
const reqLongitud  = document.getElementById('req-longitud');
const reqMayuscula = document.getElementById('req-mayuscula');
const reqNumero    = document.getElementById('req-numero');

// Botón de toggle de visibilidad
const botonVisNueva = document.getElementById('boton-vis-nueva');
const iconoVisNueva = document.getElementById('icono-vis-nueva');

if (campoNuevaContrasena && botonGuardarContrasena) {

    /**
     * Actualiza el ícono y texto de un indicador de requisito.
     *
     * @param {HTMLElement} elemento Elemento span del requisito.
     * @param {boolean}     cumplido true si el requisito se cumple.
     */
    const actualizarRequisito = (elemento, cumplido) => {
        if (!elemento) return;
        const icono = elemento.querySelector('i');
        if (cumplido) {
            icono.className = 'bi bi-check-circle text-success';
        } else {
            icono.className = 'bi bi-x-circle text-danger';
        }
    };

    /**
     * Evalúa los requisitos de la nueva contraseña y actualiza indicadores.
     * Habilita el botón "Guardar" solo cuando todos los requisitos se cumplen
     * y las contraseñas coinciden.
     */
    const evaluarContrasena = () => {
        const valor      = campoNuevaContrasena.value;
        const confirmado = campoConfirmarContrasena ? campoConfirmarContrasena.value : '';

        // Validar cada requisito
        const cumpleLongitud  = valor.length >= 8;
        const cumpleMayuscula = /[A-Z]/.test(valor);
        const cumpleNumero    = /[0-9]/.test(valor);
        const coincide        = valor === confirmado && confirmado.length > 0;

        // Actualizar indicadores visuales
        actualizarRequisito(reqLongitud,  cumpleLongitud);
        actualizarRequisito(reqMayuscula, cumpleMayuscula);
        actualizarRequisito(reqNumero,    cumpleNumero);

        // Mensaje de coincidencia en el campo de confirmación
        if (mensajeCoincidencia && confirmado.length > 0) {
            if (coincide) {
                mensajeCoincidencia.textContent = '✓ Las contraseñas coinciden.';
                mensajeCoincidencia.className   = 'form-text text-success';
                campoConfirmarContrasena.classList.remove('is-invalid');
                campoConfirmarContrasena.classList.add('is-valid');
            } else {
                mensajeCoincidencia.textContent = 'Las contraseñas no coinciden.';
                mensajeCoincidencia.className   = 'form-text text-danger';
                campoConfirmarContrasena.classList.remove('is-valid');
                campoConfirmarContrasena.classList.add('is-invalid');
            }
        } else if (mensajeCoincidencia) {
            mensajeCoincidencia.textContent = '';
            campoConfirmarContrasena.classList.remove('is-valid', 'is-invalid');
        }

        // Habilitar botón solo si todos los requisitos y la coincidencia se cumplen
        const habilitarBoton = cumpleLongitud && cumpleMayuscula && cumpleNumero && coincide;
        botonGuardarContrasena.disabled = !habilitarBoton;
        botonGuardarContrasena.setAttribute('aria-disabled', String(!habilitarBoton));
    };

    campoNuevaContrasena.addEventListener('input', evaluarContrasena);

    if (campoConfirmarContrasena) {
        campoConfirmarContrasena.addEventListener('input', evaluarContrasena);
    }

    // Toggle visibilidad de contraseña nueva
    if (botonVisNueva && iconoVisNueva) {
        botonVisNueva.addEventListener('click', () => {
            const estaOculta = campoNuevaContrasena.type === 'password';
            campoNuevaContrasena.type = estaOculta ? 'text' : 'password';
            iconoVisNueva.className   = estaOculta ? 'bi bi-eye-slash' : 'bi bi-eye';
            botonVisNueva.setAttribute(
                'aria-label',
                estaOculta ? 'Ocultar contraseña' : 'Mostrar contraseña'
            );
        });
    }
}

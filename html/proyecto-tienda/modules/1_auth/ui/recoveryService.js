/**
 * modules/1_auth/ui/recoveryService.js
 *
 * Servicio de cliente para la pantalla de recuperación de contraseña (HU2).
 * Responsabilidades:
 *  - Habilitar el botón "Enviar código" solo cuando el campo correo está lleno (Paso 1/3).
 *  - Habilitar el botón "Verificar" solo cuando se ingresen exactamente 6 dígitos (Paso 2/4).
 *  - Validar que el código solo contenga números.
 *
 * Restricciones (AGENTS.md):
 *  - Vanilla JavaScript, sin frameworks.
 *  - Sin inline JS: cargado desde recuperar.php con defer.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Paso 1 / 3 — Formulario de solicitud de correo
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

    // Ejecutar al cargar por si el campo ya tiene un valor (ej. reintento paso 3)
    verificarCorreo();
}

// ─────────────────────────────────────────────────────────────────────────────
// Paso 2 / 4 — Formulario de validación de código
// ─────────────────────────────────────────────────────────────────────────────

const campoCodigo    = document.getElementById('campo-codigo');
const botonVerificar = document.getElementById('boton-verificar');

if (campoCodigo && botonVerificar) {
    /**
     * Habilita el botón "Verificar" solo cuando el campo tiene exactamente 6 dígitos.
     * También filtra cualquier carácter no numérico ingresado.
     */
    const verificarCodigo = () => {
        // Eliminar cualquier carácter que no sea dígito
        campoCodigo.value = campoCodigo.value.replace(/\D/g, '');

        const codigoCompleto = campoCodigo.value.length === 6;
        botonVerificar.disabled = !codigoCompleto;
        botonVerificar.setAttribute('aria-disabled', String(!codigoCompleto));
    };

    campoCodigo.addEventListener('input', verificarCodigo);
}

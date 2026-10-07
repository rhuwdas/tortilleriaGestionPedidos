/**
 * core/ApiManager.js
 *
 * Cliente HTTP centralizado para comunicación asíncrona con los endpoints
 * del backend PHP mediante la Fetch API.
 *
 * Proporciona métodos reutilizables para GET y POST, manejo uniforme de errores
 * y serialización de datos. Todos los módulos del frontend deben usar este
 * cliente en lugar de hacer llamadas Fetch directas.
 *
 * Restricciones (AGENTS.md):
 *  - Vanilla JavaScript, sin frameworks ni librerías externas.
 *  - Sin JavaScript en línea (inline JS): este archivo se carga con defer.
 *  - Cargado desde HTML con: <script src="/core/ApiManager.js" defer></script>
 */

const ApiManager = (() => {

    /**
     * Ruta base de la API. Apunta a la raíz del proyecto en el servidor.
     * Todos los endpoints se construyen relativos a esta ruta.
     *
     * @type {string}
     */
    const URL_BASE = '/proyecto-tienda';

    /**
     * Cabeceras HTTP comunes para todas las solicitudes.
     * Se utiliza JSON como formato de intercambio de datos.
     *
     * @type {HeadersInit}
     */
    const CABECERAS_COMUNES = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest', // Identificar solicitudes AJAX en el backend
    };

    /**
     * Realiza una solicitud HTTP GET al endpoint especificado.
     *
     * @param {string} endpoint  Ruta relativa del endpoint (ej. '/modules/1_auth/api/login.php').
     * @param {Object} [params]  Parámetros opcionales que se añaden como query string.
     * @returns {Promise<Object>} Promesa que resuelve con el objeto JSON de la respuesta.
     *
     * @example
     * ApiManager.obtener('/modules/2_catalog/api/productos.php', { categoria: 'maiz' })
     *   .then(datos => console.log(datos))
     *   .catch(error => console.error(error));
     */
    const obtener = async (endpoint, params = {}) => {
        // Construir el query string a partir del objeto de parámetros
        const queryString = Object.keys(params).length
            ? '?' + new URLSearchParams(params).toString()
            : '';

        const url = `${URL_BASE}${endpoint}${queryString}`;

        try {
            const respuesta = await fetch(url, {
                method: 'GET',
                headers: CABECERAS_COMUNES,
                credentials: 'same-origin', // Incluir cookies de sesión en la solicitud
            });

            return await _procesarRespuesta(respuesta);

        } catch (error) {
            // Relanzar el error con contexto adicional para facilitar el diagnóstico
            throw new Error(`ApiManager.obtener [${endpoint}]: ${error.message}`);
        }
    };

    /**
     * Realiza una solicitud HTTP POST al endpoint especificado.
     *
     * @param {string} endpoint  Ruta relativa del endpoint (ej. '/modules/1_auth/api/login.php').
     * @param {Object} [cuerpo]  Datos a enviar en el cuerpo de la solicitud (serializados a JSON).
     * @returns {Promise<Object>} Promesa que resuelve con el objeto JSON de la respuesta.
     *
     * @example
     * ApiManager.publicar('/modules/1_auth/api/login.php', { correo, contrasena })
     *   .then(respuesta => {
     *     if (respuesta.exito) { window.location.href = '/dashboard'; }
     *   })
     *   .catch(error => console.error(error));
     */
    const publicar = async (endpoint, cuerpo = {}) => {
        const url = `${URL_BASE}${endpoint}`;

        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                headers: CABECERAS_COMUNES,
                credentials: 'same-origin', // Incluir cookies de sesión en la solicitud
                body: JSON.stringify(cuerpo),
            });

            return await _procesarRespuesta(respuesta);

        } catch (error) {
            throw new Error(`ApiManager.publicar [${endpoint}]: ${error.message}`);
        }
    };

    /**
     * Realiza una solicitud HTTP POST enviando datos como FormData (multipart/form-data).
     * Útil para formularios con archivos adjuntos o cuando el backend espera $_POST estándar.
     *
     * @param {string}   endpoint    Ruta relativa del endpoint.
     * @param {FormData} formulario  Objeto FormData con los datos del formulario.
     * @returns {Promise<Object>}    Promesa que resuelve con el objeto JSON de la respuesta.
     *
     * @example
     * const formData = new FormData(document.getElementById('formulario-login'));
     * ApiManager.publicarFormulario('/modules/1_auth/api/login.php', formData)
     *   .then(respuesta => console.log(respuesta));
     */
    const publicarFormulario = async (endpoint, formulario) => {
        const url = `${URL_BASE}${endpoint}`;

        try {
            // No se especifica Content-Type: el navegador lo asigna automáticamente
            // con el boundary correcto para multipart/form-data
            const respuesta = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                body: formulario,
            });

            return await _procesarRespuesta(respuesta);

        } catch (error) {
            throw new Error(`ApiManager.publicarFormulario [${endpoint}]: ${error.message}`);
        }
    };

    /**
     * Procesa la respuesta HTTP: verifica el estado HTTP y deserializa el JSON.
     * En caso de error HTTP (4xx, 5xx), extrae el mensaje de error del cuerpo
     * si está disponible, o lanza un error genérico con el código de estado.
     *
     * @param {Response} respuesta Objeto Response de la Fetch API.
     * @returns {Promise<Object>}  Datos deserializados del cuerpo JSON.
     * @throws {Error}             Si el servidor devuelve un estado HTTP de error.
     * @private
     */
    const _procesarRespuesta = async (respuesta) => {
        let datos;

        try {
            datos = await respuesta.json();
        } catch {
            // La respuesta no contiene JSON válido
            datos = null;
        }

        if (!respuesta.ok) {
            // Extraer el mensaje de error del cuerpo JSON si está disponible
            const mensajeError = datos?.mensaje ?? datos?.error ?? `Error HTTP ${respuesta.status}`;
            throw new Error(mensajeError);
        }

        return datos;
    };

    /**
     * Interfaz pública del módulo ApiManager.
     * Solo se exponen los métodos necesarios para los módulos del frontend.
     */
    return {
        obtener,
        publicar,
        publicarFormulario,
    };

})();

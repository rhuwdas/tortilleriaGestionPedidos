/**
 * modules/2_catalog/ui/catalogUI.js
 *
 * Filtrado por categoría/disponibilidad y búsqueda en tiempo real (HU3 — tarea 3.3).
 *
 * Responsabilidades:
 *  - Escuchar el evento 'catalogoCargado' emitido por catalog.js para poblar
 *    el selector de categorías con los datos reales de la BD.
 *  - Registrar los listeners de los controles de filtro:
 *      · #campoBusqueda     — input de texto (búsqueda en tiempo real con debounce)
 *      · #selectCategoria   — select de categoría
 *      · #switchDisponible  — checkbox "Solo disponibles"
 *  - Invocar Catalogo.filtrarYRenderizar() cada vez que cambia cualquier control.
 *
 * Restricciones (AGENTS.md):
 *  - Vanilla JavaScript, sin frameworks.
 *  - Sin JavaScript en línea. Cargado con defer después de catalog.js.
 */

const CatalogoUI = (() => {

    // ── Constantes ────────────────────────────────────────────────────────────

    /**
     * Tiempo de espera en milisegundos antes de aplicar el filtro de texto.
     * Evita hacer búsquedas en cada pulsación de tecla.
     */
    const DEBOUNCE_MS = 280;

    // ── Referencias al DOM ────────────────────────────────────────────────────

    const campoBusqueda    = document.getElementById('campoBusqueda');
    const selectCategoria  = document.getElementById('selectCategoria');
    const switchDisponible = document.getElementById('switchDisponible');

    // ── Estado interno ────────────────────────────────────────────────────────

    /** Temporizador para el debounce del campo de búsqueda. */
    let temporizadorDebounce = null;

    // ── Funciones privadas ────────────────────────────────────────────────────

    /**
     * Recopila los valores actuales de todos los controles de filtro
     * y los pasa a Catalogo.filtrarYRenderizar().
     */
    const aplicarFiltros = () => {
        Catalogo.filtrarYRenderizar({
            busqueda:       campoBusqueda?.value    ?? '',
            categoria:      selectCategoria?.value  ?? '',
            soloDisponible: switchDisponible?.checked ?? false,
        });
    };

    /**
     * Puebla el <select> de categorías con las opciones recibidas desde la BD.
     * Conserva la opción "Todas" que ya está en el HTML.
     *
     * @param {string[]} categorias Lista de nombres de categoría.
     */
    const poblarSelectCategorias = (categorias) => {
        if (!selectCategoria) return;

        // Eliminar opciones previas excepto la primera ("Todas")
        while (selectCategoria.options.length > 1) {
            selectCategoria.remove(1);
        }

        categorias.forEach((categoria) => {
            const opcion = document.createElement('option');
            opcion.value       = categoria;
            opcion.textContent = categoria;
            selectCategoria.appendChild(opcion);
        });
    };

    // ── Registro de eventos ────────────────────────────────────────────────────

    /**
     * Registra todos los listeners de los controles de filtro.
     * Se invoca desde inicializar() tras cargar el DOM.
     */
    const registrarEventos = () => {

        // ── Búsqueda en tiempo real con debounce ────────────────────────────
        if (campoBusqueda) {
            campoBusqueda.addEventListener('input', () => {
                clearTimeout(temporizadorDebounce);
                temporizadorDebounce = setTimeout(aplicarFiltros, DEBOUNCE_MS);
            });

            // Limpiar búsqueda con Escape
            campoBusqueda.addEventListener('keydown', (evento) => {
                if (evento.key === 'Escape') {
                    campoBusqueda.value = '';
                    aplicarFiltros();
                }
            });
        }

        // ── Filtro por categoría ────────────────────────────────────────────
        if (selectCategoria) {
            selectCategoria.addEventListener('change', aplicarFiltros);
        }

        // ── Toggle solo disponibles ─────────────────────────────────────────
        if (switchDisponible) {
            switchDisponible.addEventListener('change', aplicarFiltros);
        }

        // ── Escuchar el evento de datos cargados emitido por catalog.js ─────
        document.addEventListener('catalogoCargado', (evento) => {
            const { categorias = [] } = evento.detail;
            poblarSelectCategorias(categorias);
        });
    };

    // ── Inicialización ────────────────────────────────────────────────────────

    /**
     * Inicializa el módulo al cargar el DOM.
     */
    const inicializar = () => {
        registrarEventos();
    };

    document.addEventListener('DOMContentLoaded', inicializar);

    // No expone interfaz pública: solo reacciona a eventos del DOM.

})();

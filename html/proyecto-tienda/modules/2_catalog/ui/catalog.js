/**
 * modules/2_catalog/ui/catalog.js
 *
 * Lógica principal del Catálogo Web (HU3 — tareas 3.1, 3.4, 3.5).
 *
 * Responsabilidades:
 *  - Cargar los productos desde el endpoint get_catalog.php al iniciar la página.
 *  - Renderizar las tarjetas de producto en el grid (#gridProductos).
 *  - Mostrar el modal de detalle al hacer clic en una tarjeta (tarea 3.4).
 *  - Mostrar la alerta de "Agotado" / sin resultados (tarea 3.5).
 *
 * Restricciones (AGENTS.md):
 *  - Vanilla JavaScript, sin frameworks.
 *  - Sin JavaScript en línea. Cargado con defer.
 *  - Usa ApiManager para todas las peticiones HTTP.
 *
 * Expone el objeto global `Catalogo` para que catalogUI.js pueda invocar
 * el re-renderizado cuando cambian los filtros.
 */

const Catalogo = (() => {

    // ── Constantes ────────────────────────────────────────────────────────────

    /** Ruta relativa al endpoint del catálogo. */
    const ENDPOINT_CATALOGO = '/modules/2_catalog/api/get_catalog.php';

    /** Imagen de marcador de posición cuando un producto no tiene foto. */
    const IMAGEN_PLACEHOLDER = 'https://placehold.co/400x300/f5e6d3/c2714f?text=Sin+imagen';

    // ── Referencias al DOM ────────────────────────────────────────────────────

    /** Contenedor del grid de tarjetas. */
    const gridProductos = document.getElementById('gridProductos');

    /** Indicador de carga. */
    const estadoCarga = document.getElementById('estadoCarga');

    /** Alerta de agotado / sin resultados (tarea 3.5). */
    const alertaAgotado = document.getElementById('alertaAgotado');
    const alertaAgotadoMensaje = document.getElementById('alertaAgotadoMensaje');

    /** Elementos del modal de detalle (tarea 3.4). */
    const modalDetalle       = document.getElementById('modalDetalle');
    const modalNombre        = document.getElementById('modalDetalleNombre');
    const modalCategoria     = document.getElementById('modalDetalleCategoria');
    const modalDescripcion   = document.getElementById('modalDetalleDescripcion');
    const modalPrecio        = document.getElementById('modalDetallePrecio');
    const modalImagen        = document.getElementById('modalDetalleImagen');
    const modalDisponibilidad = document.getElementById('modalDetalleDisponibilidad');

    /** Instancia de Bootstrap Modal (inicializada tras cargar el DOM). */
    let instanciaModal = null;

    // ── Estado interno ────────────────────────────────────────────────────────

    /**
     * Copia local de todos los productos cargados desde el servidor.
     * catalogUI.js filtra sobre esta lista sin volver a hacer peticiones.
     * @type {Array<Object>}
     */
    let todosLosProductos = [];

    // ── Funciones privadas ────────────────────────────────────────────────────

    /**
     * Formatea un número como precio en pesos mexicanos.
     * @param {number|string} precio
     * @returns {string} Ej. "$12.50"
     */
    const formatearPrecio = (precio) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
        }).format(Number(precio));
    };

    /**
     * Construye el HTML de una tarjeta de producto.
     * Si el producto no está disponible, agrega la clase y la insignia de agotado (tarea 3.5).
     *
     * @param {Object} producto Datos del producto devueltos por el endpoint.
     * @returns {string} HTML de la tarjeta.
     */
    const crearTarjeta = (producto) => {
        const estaAgotado = Number(producto.disponible) !== 1;
        const imagenSrc   = producto.imagen_url ? producto.imagen_url : IMAGEN_PLACEHOLDER;
        const imagenAlt   = `Imagen de ${producto.nombre}`;

        // Insignia de disponibilidad
        const insigniaDisponible = estaAgotado
            ? '<span class="insignia-agotado"><i class="bi bi-x-circle me-1"></i>Agotado</span>'
            : '<span class="insignia-disponible"><i class="bi bi-check-circle me-1"></i>Disponible</span>';

        // Opacidad reducida y cursor deshabilitado en tarjetas agotadas
        const claseAgotado = estaAgotado ? ' tarjeta-agotada' : '';

        return `
        <article
            class="tarjeta-producto${claseAgotado}"
            role="listitem"
            data-id="${producto.id}"
            data-nombre="${producto.nombre}"
            data-descripcion="${producto.descripcion ?? ''}"
            data-precio="${producto.precio}"
            data-categoria="${producto.categoria ?? ''}"
            data-imagen="${imagenSrc}"
            data-disponible="${producto.disponible}"
            tabindex="0"
            aria-label="Producto: ${producto.nombre}"
        >
            <!-- Imagen del producto -->
            <div class="tarjeta-imagen-wrapper">
                <img
                    src="${imagenSrc}"
                    alt="${imagenAlt}"
                    class="tarjeta-imagen"
                    loading="lazy"
                    onerror="this.src='${IMAGEN_PLACEHOLDER}'"
                >
                ${insigniaDisponible}
            </div>

            <!-- Información del producto -->
            <div class="tarjeta-cuerpo">
                <span class="tarjeta-categoria">${producto.categoria ?? ''}</span>
                <h3 class="tarjeta-nombre">${producto.nombre}</h3>
                <p class="tarjeta-descripcion">${producto.descripcion ?? 'Sin descripción disponible.'}</p>
                <div class="tarjeta-pie">
                    <span class="tarjeta-precio">${formatearPrecio(producto.precio)}</span>
                    <button
                        class="btn-ver-detalle"
                        aria-label="Ver detalle de ${producto.nombre}"
                    >
                        <i class="bi bi-eye me-1"></i>Ver detalle
                    </button>
                </div>
            </div>
        </article>`;
    };

    /**
     * Muestra la alerta de agotado/sin resultados con un mensaje personalizado (tarea 3.5).
     * @param {string} mensaje Texto a mostrar en la alerta.
     */
    const mostrarAlerta = (mensaje) => {
        alertaAgotadoMensaje.textContent = mensaje;
        alertaAgotado.classList.remove('d-none');
    };

    /**
     * Oculta la alerta de agotado/sin resultados.
     */
    const ocultarAlerta = () => {
        alertaAgotado.classList.add('d-none');
    };

    /**
     * Renderiza una lista de productos en el grid.
     * Si la lista está vacía, muestra la alerta de sin resultados.
     *
     * @param {Array<Object>} productos Lista de productos a renderizar.
     */
    const renderizarProductos = (productos) => {
        // Limpiar el grid antes de renderizar
        gridProductos.innerHTML = '';
        ocultarAlerta();

        if (productos.length === 0) {
            // Tarea 3.5 — flujo alterno: ningún producto coincide con los filtros
            mostrarAlerta('No se encontraron productos con los filtros seleccionados.');
            gridProductos.classList.add('d-none');
            return;
        }

        // Insertar tarjetas en el grid
        gridProductos.innerHTML = productos.map(crearTarjeta).join('');
        gridProductos.classList.remove('d-none');

        // Registrar evento de clic en cada tarjeta (abre el modal de detalle)
        gridProductos.querySelectorAll('.tarjeta-producto').forEach((tarjeta) => {
            tarjeta.addEventListener('click', manejarClicTarjeta);
            // Accesibilidad: abrir modal también con Enter o Espacio
            tarjeta.addEventListener('keydown', (evento) => {
                if (evento.key === 'Enter' || evento.key === ' ') {
                    evento.preventDefault();
                    manejarClicTarjeta({ currentTarget: tarjeta });
                }
            });
        });

        // Registrar evento en los botones "Ver detalle" (evita burbujeo doble)
        gridProductos.querySelectorAll('.btn-ver-detalle').forEach((boton) => {
            boton.addEventListener('click', (evento) => {
                evento.stopPropagation();
                manejarClicTarjeta({ currentTarget: boton.closest('.tarjeta-producto') });
            });
        });
    };

    /**
     * Maneja el clic en una tarjeta de producto: extrae los datos del dataset
     * y abre el modal de detalle (tarea 3.4).
     *
     * @param {Event} evento Evento de clic.
     */
    const manejarClicTarjeta = (evento) => {
        const tarjeta = evento.currentTarget;
        const { nombre, descripcion, precio, categoria, imagen, disponible } = tarjeta.dataset;

        // Poblar el modal con los datos del producto
        modalNombre.textContent      = nombre;
        modalCategoria.textContent   = categoria || '';
        modalDescripcion.textContent = descripcion || 'Sin descripción disponible.';
        modalPrecio.textContent      = formatearPrecio(precio);
        modalImagen.src              = imagen;
        modalImagen.alt              = `Imagen de ${nombre}`;

        // Estado de disponibilidad en el modal (tarea 3.5)
        if (Number(disponible) === 1) {
            modalDisponibilidad.innerHTML = `
                <span class="insignia-disponible fs-6">
                    <i class="bi bi-check-circle-fill me-1"></i>Disponible
                </span>`;
        } else {
            modalDisponibilidad.innerHTML = `
                <div class="alerta-agotado-modal">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Este producto está <strong>agotado</strong> actualmente.
                    Contacta al negocio para consultar disponibilidad.
                </div>`;
        }

        // Mostrar el modal de Bootstrap
        instanciaModal.show();
    };

    // ── Funciones públicas ────────────────────────────────────────────────────

    /**
     * Carga los productos desde el endpoint y los almacena en `todosLosProductos`.
     * Renderiza la lista completa al terminar.
     * Llamado por catalogUI.js al cambiar filtros para re-filtrar sin nueva petición.
     *
     * @returns {Promise<void>}
     */
    const cargarProductos = async () => {
        // Mostrar spinner de carga
        estadoCarga.classList.remove('d-none');
        gridProductos.classList.add('d-none');
        ocultarAlerta();

        try {
            const respuesta = await ApiManager.obtener(ENDPOINT_CATALOGO);

            if (respuesta.exito) {
                todosLosProductos = respuesta.productos ?? [];
                // Notificar a catalogUI.js que ya hay datos (puebla el selector de categorías)
                document.dispatchEvent(new CustomEvent('catalogoCargado', {
                    detail: { categorias: respuesta.categorias ?? [] },
                }));
                // Renderizar todos los productos sin filtro inicial
                renderizarProductos(todosLosProductos);
            } else {
                mostrarAlerta('No se pudo cargar el catálogo. Intenta más tarde.');
            }

        } catch (error) {
            console.error('Catalogo.cargarProductos:', error.message);
            mostrarAlerta('Error de conexión al cargar el catálogo. Intenta más tarde.');
        } finally {
            // Ocultar spinner siempre al terminar
            estadoCarga.classList.add('d-none');
        }
    };

    /**
     * Filtra la lista local de productos según los criterios recibidos y re-renderiza.
     * Llamado externamente por catalogUI.js.
     *
     * @param {Object} criterios {
     *   @type {string} busqueda       Texto libre.
     *   @type {string} categoria      Categoría seleccionada ('' = todas).
     *   @type {boolean} soloDisponible true para mostrar solo disponibles.
     * }
     */
    const filtrarYRenderizar = (criterios) => {
        const { busqueda = '', categoria = '', soloDisponible = false } = criterios;
        const textoBusqueda = busqueda.trim().toLowerCase();

        const productosFiltrados = todosLosProductos.filter((producto) => {
            // Coincidencia por texto en nombre o descripción
            const coincideTexto = textoBusqueda === ''
                || producto.nombre.toLowerCase().includes(textoBusqueda)
                || (producto.descripcion ?? '').toLowerCase().includes(textoBusqueda);

            // Coincidencia por categoría
            const coincideCategoria = categoria === ''
                || producto.categoria === categoria;

            // Filtro de disponibilidad
            const coincideDisponible = !soloDisponible
                || Number(producto.disponible) === 1;

            return coincideTexto && coincideCategoria && coincideDisponible;
        });

        // Actualizar contador de resultados
        const contador = document.getElementById('contadorResultados');
        if (contador) {
            contador.textContent = productosFiltrados.length > 0
                ? `${productosFiltrados.length} producto(s)`
                : '';
        }

        renderizarProductos(productosFiltrados);
    };

    // ── Inicialización ────────────────────────────────────────────────────────

    /**
     * Inicializa el módulo al cargar el DOM.
     * Crea la instancia del modal de Bootstrap y carga los productos.
     */
    const inicializar = () => {
        // Crear instancia del modal de Bootstrap
        if (modalDetalle) {
            instanciaModal = new bootstrap.Modal(modalDetalle);
        }

        // Cargar productos desde el servidor
        cargarProductos();
    };

    // Esperar a que el DOM esté completamente listo
    document.addEventListener('DOMContentLoaded', inicializar);

    // ── Interfaz pública ──────────────────────────────────────────────────────

    return {
        filtrarYRenderizar,
        cargarProductos,
    };

})();

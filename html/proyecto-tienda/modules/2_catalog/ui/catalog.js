/**
 * modules/2_catalog/ui/catalog.js
 *
 * Lógica principal del Catálogo Web (HU3 — tareas 3.1, 3.4, 3.5).
 *
 * Campos reales devueltos por el endpoint:
 *  - id          (int)
 *  - nombre      (string)
 *  - precio_kilo (decimal)
 *  - unidad      (string)  ej. "kilo", "pieza"
 *  - disponible  (0|1)
 *
 * Restricciones (AGENTS.md):
 *  - Vanilla JavaScript, sin frameworks.
 *  - Sin JavaScript en línea. Cargado con defer.
 *  - Usa ApiManager para todas las peticiones HTTP.
 */

const Catalogo = (() => {

    // ── Constantes ────────────────────────────────────────────────────────────

    const ENDPOINT_CATALOGO  = '/modules/2_catalog/api/get_catalog.php';
    const IMAGEN_PLACEHOLDER = 'https://placehold.co/400x300/f5e6d3/c2714f?text=Tortillería';

    // ── Referencias al DOM ────────────────────────────────────────────────────

    const gridProductos        = document.getElementById('gridProductos');
    const estadoCarga          = document.getElementById('estadoCarga');
    const alertaAgotado        = document.getElementById('alertaAgotado');
    const alertaAgotadoMensaje = document.getElementById('alertaAgotadoMensaje');

    // Modal de detalle (tarea 3.4)
    const modalDetalle          = document.getElementById('modalDetalle');
    const modalNombre           = document.getElementById('modalDetalleNombre');
    const modalUnidad           = document.getElementById('modalDetalleCategoria');
    const modalPrecio           = document.getElementById('modalDetallePrecio');
    const modalImagen           = document.getElementById('modalDetalleImagen');
    const modalDisponibilidad   = document.getElementById('modalDetalleDisponibilidad');

    let instanciaModal    = null;
    let todosLosProductos = [];

    // ── Utilidades ────────────────────────────────────────────────────────────

    /**
     * Formatea precio como moneda mexicana.
     * @param {number|string} precio
     * @returns {string} Ej. "$12.50"
     */
    const formatearPrecio = (precio) =>
        new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
        }).format(Number(precio));

    // ── Renderizado ───────────────────────────────────────────────────────────

    /**
     * Construye el HTML de una tarjeta de producto.
     * @param {Object} producto
     * @returns {string} HTML de la tarjeta.
     */
    const crearTarjeta = (producto) => {
        const estaAgotado = Number(producto.disponible) !== 1;

        const insignia = estaAgotado
            ? '<span class="insignia-agotado"><i class="bi bi-x-circle me-1"></i>Agotado</span>'
            : '<span class="insignia-disponible"><i class="bi bi-check-circle me-1"></i>Disponible</span>';

        const claseAgotado = estaAgotado ? ' tarjeta-agotada' : '';
        const etiquetaUnidad = producto.unidad
            ? `<span class="tarjeta-categoria">por ${producto.unidad}</span>`
            : '';

        return `
        <article
            class="tarjeta-producto${claseAgotado}"
            role="listitem"
            data-id="${producto.id}"
            data-nombre="${producto.nombre}"
            data-precio="${producto.precio_kilo}"
            data-unidad="${producto.unidad ?? ''}"
            data-disponible="${producto.disponible}"
            tabindex="0"
            aria-label="Producto: ${producto.nombre}"
        >
            <div class="tarjeta-imagen-wrapper">
                <img
                    src="${IMAGEN_PLACEHOLDER}"
                    alt="Imagen de ${producto.nombre}"
                    class="tarjeta-imagen"
                    loading="lazy"
                >
                ${insignia}
            </div>
            <div class="tarjeta-cuerpo">
                ${etiquetaUnidad}
                <h3 class="tarjeta-nombre">${producto.nombre}</h3>
                <div class="tarjeta-pie">
                    <span class="tarjeta-precio">${formatearPrecio(producto.precio_kilo)}</span>
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

    const mostrarAlerta = (mensaje) => {
        if (!alertaAgotadoMensaje || !alertaAgotado) return;
        alertaAgotadoMensaje.textContent = mensaje;
        alertaAgotado.classList.remove('d-none');
    };

    const ocultarAlerta = () => {
        if (alertaAgotado) alertaAgotado.classList.add('d-none');
    };

    /**
     * Renderiza la lista de productos en el grid.
     * @param {Array} productos
     */
    const renderizarProductos = (productos) => {
        if (!gridProductos) return;
        gridProductos.innerHTML = '';
        ocultarAlerta();

        if (productos.length === 0) {
            mostrarAlerta('No se encontraron productos con los filtros seleccionados.');
            gridProductos.classList.add('d-none');
            return;
        }

        gridProductos.innerHTML = productos.map(crearTarjeta).join('');
        gridProductos.classList.remove('d-none');

        // Evento de clic en tarjetas → abre modal de detalle
        gridProductos.querySelectorAll('.tarjeta-producto').forEach((tarjeta) => {
            tarjeta.addEventListener('click', manejarClicTarjeta);
            tarjeta.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    manejarClicTarjeta({ currentTarget: tarjeta });
                }
            });
        });

        gridProductos.querySelectorAll('.btn-ver-detalle').forEach((boton) => {
            boton.addEventListener('click', (e) => {
                e.stopPropagation();
                manejarClicTarjeta({ currentTarget: boton.closest('.tarjeta-producto') });
            });
        });
    };

    /**
     * Abre el modal de detalle con los datos del producto clicado (tarea 3.4).
     * @param {Event} evento
     */
    const manejarClicTarjeta = (evento) => {
        const tarjeta = evento.currentTarget;
        const { nombre, precio, unidad, disponible } = tarjeta.dataset;

        if (modalNombre)  modalNombre.textContent  = nombre;
        if (modalUnidad)  modalUnidad.textContent  = unidad ? `Venta por ${unidad}` : '';
        if (modalPrecio)  modalPrecio.textContent  = formatearPrecio(precio);
        if (modalImagen) {
            modalImagen.src = IMAGEN_PLACEHOLDER;
            modalImagen.alt = `Imagen de ${nombre}`;
        }

        // Estado de disponibilidad (tarea 3.5)
        if (modalDisponibilidad) {
            if (Number(disponible) === 1) {
                modalDisponibilidad.innerHTML =
                    `<span class="insignia-disponible fs-6">
                        <i class="bi bi-check-circle-fill me-1"></i>Disponible
                    </span>`;
            } else {
                modalDisponibilidad.innerHTML =
                    `<div class="alerta-agotado-modal">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Este producto está <strong>agotado</strong> actualmente.
                    </div>`;
            }
        }

        if (instanciaModal) instanciaModal.show();
    };

    // ── Carga de datos ────────────────────────────────────────────────────────

    /**
     * Carga los productos desde el endpoint y renderiza el grid.
     */
    const cargarProductos = async () => {
        if (estadoCarga) estadoCarga.classList.remove('d-none');
        if (gridProductos) gridProductos.classList.add('d-none');
        ocultarAlerta();

        try {
            const respuesta = await ApiManager.obtener(ENDPOINT_CATALOGO);

            if (respuesta && respuesta.exito) {
                todosLosProductos = respuesta.productos ?? [];

                // Notificar a catalogUI.js con las unidades disponibles
                document.dispatchEvent(new CustomEvent('catalogoCargado', {
                    detail: { unidades: respuesta.unidades ?? [] },
                }));

                renderizarProductos(todosLosProductos);
            } else {
                mostrarAlerta('No se pudo cargar el catálogo. Intenta más tarde.');
            }

        } catch (error) {
            console.error('Catalogo.cargarProductos:', error.message);
            mostrarAlerta('Error de conexión al cargar el catálogo. Intenta más tarde.');
        } finally {
            if (estadoCarga) estadoCarga.classList.add('d-none');
        }
    };

    // ── Filtrado público (llamado por catalogUI.js) ───────────────────────────

    /**
     * Filtra la lista local y re-renderiza sin hacer nuevas peticiones.
     * @param {Object} criterios { busqueda, unidad, soloDisponible }
     */
    const filtrarYRenderizar = (criterios) => {
        const { busqueda = '', unidad = '', soloDisponible = false } = criterios;
        const texto = busqueda.trim().toLowerCase();

        const filtrados = todosLosProductos.filter((p) => {
            const coincideTexto     = texto === '' || p.nombre.toLowerCase().includes(texto);
            const coincideUnidad    = unidad === '' || p.unidad === unidad;
            const coincideDisponible = !soloDisponible || Number(p.disponible) === 1;
            return coincideTexto && coincideUnidad && coincideDisponible;
        });

        const contador = document.getElementById('contadorResultados');
        if (contador) {
            contador.textContent = filtrados.length > 0 ? `${filtrados.length} producto(s)` : '';
        }

        renderizarProductos(filtrados);
    };

    // ── Inicialización ────────────────────────────────────────────────────────

    const inicializar = () => {
        if (modalDetalle) {
            instanciaModal = new bootstrap.Modal(modalDetalle);
        }
        cargarProductos();
    };

    document.addEventListener('DOMContentLoaded', inicializar);

    return { filtrarYRenderizar, cargarProductos };

})();

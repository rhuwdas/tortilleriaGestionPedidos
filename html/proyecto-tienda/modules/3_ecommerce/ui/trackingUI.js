/**
 * modules/3_ecommerce/ui/trackingUI.js
 *
 * Interfaz de seguimiento de pedidos del cliente (HU6).
 * Responsabilidades:
 *  - Consultar el historial de pedidos del cliente autenticado.
 *  - Mostrar el estado actual en tiempo real mediante actualización periódica.
 *  - Renderizar el detalle de productos, totales y dirección de entrega.
 *  - Emitir el evento "pedido:repetir" para recargar los productos en el carrito.
 *
 * Restricciones (AGENTS.md):
 *  - JavaScript desacoplado del HTML, cargado con defer.
 *  - El backend restringe los pedidos al cliente en sesión.
 */

document.addEventListener('DOMContentLoaded', () => {
    const contenedor = document.getElementById('contenedor-mis-pedidos');
    if (!contenedor) {
        return;
    }

    const botonActualizar = document.getElementById('boton-actualizar-pedidos');
    const alerta = document.getElementById('alerta-pedidos');
    const intervaloActualizacionMs = 15000;

    const clasesPorEstado = {
        'Pendiente': 'badge-estado-pendiente',
        'En Proceso': 'badge-estado-en-proceso',
        'En Ruta': 'badge-estado-en-ruta',
        'Entregado': 'badge-estado-entregado',
        'Cancelado': 'badge-estado-cancelado'
    };

    let cargando = false;

    /**
     * Crea un elemento HTML con clase y texto opcionales.
     */
    function crearElemento(etiqueta, clase = '', texto = '') {
        const elemento = document.createElement(etiqueta);
        if (clase) {
            elemento.className = clase;
        }
        if (texto) {
            elemento.textContent = texto;
        }
        return elemento;
    }

    /**
     * Muestra un mensaje en la alerta de la sección.
     */
    function mostrarAlerta(mensaje, tipo = 'danger') {
        if (!alerta) {
            return;
        }
        alerta.textContent = mensaje;
        alerta.className = `alert alert-${tipo} alerta-pedido`;
    }

    function ocultarAlerta() {
        if (!alerta) {
            return;
        }
        alerta.className = 'alert alerta-pedido d-none';
        alerta.textContent = '';
    }

    /**
     * Formatea una fecha de MySQL para mostrarla en español.
     */
    function formatearFecha(valor) {
        if (!valor) {
            return 'No especificada';
        }

        const fecha = new Date(String(valor).replace(' ', 'T'));
        if (Number.isNaN(fecha.getTime())) {
            return String(valor);
        }

        return fecha.toLocaleString('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatearCantidad(valor) {
        return Number(valor).toLocaleString('es-MX', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    /**
     * Convierte un pedido en una tarjeta visual con su estado y acciones.
     */
    function crearTarjetaPedido(pedido) {
        const tarjeta = crearElemento('article', 'tarjeta-pedido');

        const encabezado = crearElemento('div', 'encabezado-pedido');
        const bloqueInformacion = crearElemento('div');

        const numeroPedido = crearElemento('div', 'numero-pedido', `Pedido ${pedido.numero_pedido}`);
        const fechaRealizado = crearElemento(
            'div',
            'meta-pedido',
            `Realizado: ${formatearFecha(pedido.fecha_pedido)}`
        );
        const fechaEntrega = crearElemento(
            'div',
            'meta-pedido',
            `Entrega solicitada: ${formatearFecha(pedido.fecha_hora_entrega_solicitada)}`
        );

        bloqueInformacion.append(numeroPedido, fechaRealizado, fechaEntrega);

        const claseEstado = clasesPorEstado[pedido.estado] || 'badge-estado-pendiente';
        const insigniaEstado = crearElemento('span', `badge-estado ${claseEstado}`, pedido.estado);

        encabezado.append(bloqueInformacion, insigniaEstado);
        tarjeta.appendChild(encabezado);

        const tipoEntrega = crearElemento(
            'div',
            'meta-pedido mb-2',
            pedido.tipo_entrega === 'Domicilio' && pedido.direccion_completa
                ? `Entrega a domicilio: ${pedido.direccion_completa}`
                : `Tipo de entrega: ${pedido.tipo_entrega}`
        );
        tarjeta.appendChild(tipoEntrega);

        if (pedido.estado === 'Cancelado' && pedido.motivo_rechazo) {
            tarjeta.appendChild(
                crearElemento('div', 'motivo-rechazo-pedido', `Motivo de cancelación: ${pedido.motivo_rechazo}`)
            );
        }

        const listaProductos = crearElemento('ul', 'lista-productos-pedido');
        const detalles = Array.isArray(pedido.detalles) ? pedido.detalles : [];

        if (detalles.length === 0) {
            const filaVacia = crearElemento('li');
            filaVacia.appendChild(crearElemento('span', 'text-muted', 'Sin productos registrados'));
            listaProductos.appendChild(filaVacia);
        } else {
            detalles.forEach((detalle) => {
                const fila = crearElemento('li');
                const descripcion = crearElemento(
                    'span',
                    '',
                    `${formatearCantidad(detalle.cantidad)} ${detalle.unidad} de ${detalle.nombre}`
                );
                const subtotal = crearElemento(
                    'span',
                    'fw-semibold',
                    `$${Number(detalle.subtotal).toFixed(2)}`
                );
                fila.append(descripcion, subtotal);
                listaProductos.appendChild(fila);
            });
        }
        tarjeta.appendChild(listaProductos);

        const pie = crearElemento('div', 'pie-pedido');
        const total = crearElemento('div', 'total-pedido', `Total: $${Number(pedido.total_pedido).toFixed(2)}`);

        const botonRepetir = crearElemento('button', 'btn btn-sm btn-primary');
        botonRepetir.type = 'button';
        botonRepetir.appendChild(crearElemento('i', 'bi bi-arrow-repeat me-1'));
        botonRepetir.appendChild(document.createTextNode('Repetir pedido'));
        botonRepetir.disabled = detalles.length === 0;
        botonRepetir.addEventListener('click', () => repetirPedido(pedido));

        pie.append(total, botonRepetir);
        tarjeta.appendChild(pie);

        return tarjeta;
    }

    /**
     * Conserva el pedido seleccionado y vuelve al checkout para repetirlo.
     */
    function repetirPedido(pedido) {
        sessionStorage.setItem('pedidoParaRepetir', JSON.stringify(pedido));
        window.location.href = '/proyecto-tienda/?ruta=/ecommerce#formulario-agregar-item';
    }

    function renderizarPedidos(pedidos) {
        contenedor.replaceChildren();

        if (pedidos.length === 0) {
            const estadoVacio = crearElemento('div', 'estado-vacio-pedidos');
            estadoVacio.appendChild(crearElemento('i', 'bi bi-basket d-block fs-2 mb-2'));
            estadoVacio.appendChild(
                document.createTextNode('Aún no has realizado ningún pedido')
            );
            contenedor.appendChild(estadoVacio);
            return;
        }

        pedidos.forEach((pedido) => {
            contenedor.appendChild(crearTarjetaPedido(pedido));
        });
    }

    async function cargarPedidos() {
        if (cargando) {
            return;
        }

        cargando = true;

        try {
            const datos = await ApiManager.obtener('/modules/3_ecommerce/api/track_order.php');

            if (!datos.exito) {
                throw new Error(datos.mensaje || 'No se pudieron consultar los pedidos.');
            }

            renderizarPedidos(Array.isArray(datos.pedidos) ? datos.pedidos : []);
            ocultarAlerta();
        } catch (error) {
            if (error.message.includes('Sesión no válida')) {
                window.location.href = '/proyecto-tienda/?ruta=/login';
                return;
            }
            mostrarAlerta(error.message || 'No se pudieron consultar los pedidos.');
        } finally {
            cargando = false;
        }
    }

    if (botonActualizar) {
        botonActualizar.addEventListener('click', cargarPedidos);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            cargarPedidos();
        }
    });

    cargarPedidos();
    setInterval(() => {
        if (!document.hidden) {
            cargarPedidos();
        }
    }, intervaloActualizacionMs);
});

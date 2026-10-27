/**
 * modules/3_ecommerce/ui/trackingUI.js
 *
 * Interfaz de seguimiento de pedidos del cliente (HU6).
 * Responsabilidades:
 *  - Consultar el historial de pedidos del cliente autenticado.
 *  - Mostrar el estado actual en tiempo real mediante actualización periódica.
 *  - Renderizar el detalle de productos, totales y dirección de entrega.
 *  - Emitir el evento "pedido:repetir" para recargar los productos en el carrito.
 *  - Permitir cancelar el pedido mientras siga Pendiente (aún no esté En Proceso).
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

    function formatearFechaPaso(valor) {
        if (!valor) {
            return '';
        }

        const fecha = new Date(String(valor).replace(' ', 'T'));
        if (Number.isNaN(fecha.getTime())) {
            return '';
        }

        return fecha.toLocaleString('es-MX', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Dibuja la línea de tiempo del pedido:
     * Pendiente → En Proceso → En Ruta → Entregado / Cancelado.
     */
    function crearLineaTiempoPedido(pedido) {
        const pasosNormales = ['Pendiente', 'En Proceso', 'En Ruta', 'Entregado'];
        const estaCancelado = pedido.estado === 'Cancelado';
        const pasos = estaCancelado
            ? ['Pendiente', 'En Proceso', 'En Ruta', 'Cancelado']
            : pasosNormales;
        const indiceActual = pasos.indexOf(pedido.estado);

        const linea = crearElemento('div', 'linea-tiempo-pedido');
        linea.setAttribute('role', 'list');
        linea.setAttribute('aria-label', `Línea de tiempo del pedido: ${pedido.estado}`);

        pasos.forEach((paso, indice) => {
            const item = crearElemento('div', 'paso-tiempo');
            item.setAttribute('role', 'listitem');

            let estadoPaso = 'pendiente';
            if (estaCancelado) {
                const completado = indice === 0
                    || (indice === 1 && Boolean(pedido.fecha_aceptacion))
                    || (indice === 2 && Boolean(pedido.fecha_entrega));

                if (indice === pasos.length - 1) {
                    estadoPaso = 'cancelado actual';
                } else if (completado) {
                    estadoPaso = 'completado';
                }
            } else if (indice < indiceActual) {
                estadoPaso = 'completado';
            } else if (indice === indiceActual) {
                estadoPaso = indice === pasos.length - 1 ? 'completado actual' : 'actual';
            }

            estadoPaso.split(' ').forEach((clase) => item.classList.add(clase));
            if (estadoPaso.includes('actual')) {
                item.setAttribute('aria-current', 'step');
            }

            const marcador = crearElemento('span', 'marcador-tiempo');
            if (estadoPaso.includes('completado')) {
                marcador.appendChild(crearElemento('i', 'bi bi-check-lg'));
            } else if (estadoPaso.includes('cancelado')) {
                marcador.appendChild(crearElemento('i', 'bi bi-x-lg'));
            }
            item.appendChild(marcador);
            item.appendChild(crearElemento('span', 'etiqueta-tiempo', paso));

            let fechaPaso = '';
            if (paso === 'Pendiente') {
                fechaPaso = pedido.fecha_pedido;
            } else if (paso === 'En Proceso') {
                fechaPaso = pedido.fecha_aceptacion;
            } else if (paso === 'Entregado') {
                fechaPaso = pedido.fecha_entrega;
            } else if (paso === 'Cancelado') {
                fechaPaso = pedido.fecha_cancelacion;
            }

            const fechaFormateada = formatearFechaPaso(fechaPaso);
            if (fechaFormateada) {
                item.appendChild(crearElemento('span', 'fecha-tiempo', fechaFormateada));
            }

            linea.appendChild(item);
        });

        return linea;
    }

    /**
     * Indica si el cliente puede cancelar el pedido desde el seguimiento.
     * Solo se permite mientras el pedido siga Pendiente, antes de que el
     * personal lo acepte y lo pase a En Proceso.
     */
    function puedeCancelarPedido(pedido) {
        return pedido.estado === 'Pendiente';
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
        tarjeta.appendChild(crearLineaTiempoPedido(pedido));

        const tipoEntrega = crearElemento(
            'div',
            'meta-pedido mb-2',
            pedido.tipo_entrega === 'Domicilio' && pedido.direccion_completa
                ? `Entrega a domicilio: ${pedido.direccion_completa}`
                : `Tipo de entrega: ${pedido.tipo_entrega}`
        );
        tarjeta.appendChild(tipoEntrega);

        if (pedido.tipo_entrega === 'Domicilio' && pedido.direccion_completa) {
            const bloqueMapa = crearElemento('div', 'mapa-pedido');
            const botonMapa = crearElemento('button', 'btn btn-sm btn-outline-primary boton-mapa-pedido');
            botonMapa.type = 'button';
            botonMapa.setAttribute('aria-expanded', 'false');
            botonMapa.appendChild(crearElemento('i', 'bi bi-map me-1'));
            const etiquetaMapa = crearElemento('span', '', 'Ver mapa de entrega');
            botonMapa.appendChild(etiquetaMapa);

            const contenedorMapa = crearElemento('div', 'mapa-pedido-contenedor d-none');
            botonMapa.addEventListener('click', () => {
                const seMostrara = contenedorMapa.classList.contains('d-none');

                if (seMostrara && contenedorMapa.dataset.cargado !== '1') {
                    const marcoMapa = document.createElement('iframe');
                    marcoMapa.title = `Mapa de entrega del pedido ${pedido.numero_pedido}`;
                    marcoMapa.loading = 'lazy';
                    marcoMapa.referrerPolicy = 'no-referrer-when-downgrade';
                    marcoMapa.src = `https://www.google.com/maps?q=${encodeURIComponent(pedido.direccion_completa)}&hl=es&z=16&output=embed`;
                    contenedorMapa.appendChild(marcoMapa);
                    contenedorMapa.dataset.cargado = '1';
                }

                contenedorMapa.classList.toggle('d-none', !seMostrara);
                botonMapa.setAttribute('aria-expanded', String(seMostrara));
                etiquetaMapa.textContent = seMostrara ? 'Ocultar mapa' : 'Ver mapa de entrega';
            });

            bloqueMapa.append(botonMapa, contenedorMapa);
            tarjeta.appendChild(bloqueMapa);
        }

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

        if (puedeCancelarPedido(pedido)) {
            const botonCancelar = crearElemento('button', 'btn btn-sm btn-outline-danger');
            botonCancelar.type = 'button';
            botonCancelar.setAttribute('aria-label', `Cancelar el pedido ${pedido.numero_pedido}`);
            botonCancelar.appendChild(crearElemento('i', 'bi bi-x-circle me-1'));
            botonCancelar.appendChild(document.createTextNode('Cancelar pedido'));
            botonCancelar.addEventListener('click', () => cancelarPedidoCliente(pedido, botonCancelar));
            pie.appendChild(botonCancelar);
        }

        tarjeta.appendChild(pie);

        return tarjeta;
    }

    /**
     * Cancela un pedido del cliente desde el seguimiento.
     * El backend solo lo permite si aún está Pendiente.
     */
    async function cancelarPedidoCliente(pedido, botonCancelar) {
        const confirmado = window.confirm(
            `¿Seguro que deseas cancelar el pedido ${pedido.numero_pedido}? Esta acción no se puede deshacer.`
        );

        if (!confirmado) {
            return;
        }

        botonCancelar.disabled = true;

        try {
            const respuesta = await ApiManager.publicar(
                '/modules/3_ecommerce/api/cancel_client_order.php',
                { id_pedido: pedido.id_pedido }
            );

            await cargarPedidos();
            mostrarAlerta(respuesta.mensaje || 'Pedido cancelado correctamente.', 'success');
        } catch (error) {
            botonCancelar.disabled = false;
            mostrarAlerta(error.message || 'No se pudo cancelar el pedido.');
        }
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

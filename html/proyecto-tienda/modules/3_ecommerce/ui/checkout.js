/**
 * modules/3_ecommerce/ui/checkout.js
 * 
 * Servicio para gestión de carrito y pedido en línea (HU5)
 * Responsabilidades:
 *  - Gestión del carrito de compras
 *  - Cálculo automático de totales
 *  - Selección de tipo de entrega
 *  - Validación y envío de pedido
 *  - Modal de confirmación
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // Referencias al DOM
    const formularioAgregarItem = document.getElementById('formulario-agregar-item');
    const selectorProducto = document.getElementById('selector-producto');
    const campoCantidad = document.getElementById('campo-cantidad');
    const infoPrecioProducto = document.getElementById('info-precio-producto');
    const etiquetaPrecioUnitario = document.getElementById('etiqueta-precio-unitario');
    const cuerpoTablaCarrito = document.getElementById('cuerpo-tabla-carrito');
    const filaCarritoVacio = document.getElementById('fila-carrito-vacio');
    
    // Opciones de entrega
    const opcionDomicilio = document.getElementById('opcion-domicilio');
    const opcionLocal = document.getElementById('opcion-local');
    const bloqueDireccionEntrega = document.getElementById('bloque-direccion-entrega');
    const selectorDireccionGuardada = document.getElementById('selector-direccion-guardada');
    const formularioNuevaDireccion = document.getElementById('formulario-nueva-direccion');
    
    // Resumen
    const resumenCantidadArticulos = document.getElementById('resumen-cantidad-articulos');
    const resumenSubtotal = document.getElementById('resumen-subtotal');
    const resumenTotal = document.getElementById('resumen-total');
    const botonRevisarPedido = document.getElementById('boton-revisar-pedido');
    
    // Modal
    const modalConfirmacionPedido = new bootstrap.Modal(document.getElementById('modal-confirmacion-pedido'));
    const listaResumenModal = document.getElementById('lista-resumen-modal');
    const modalTipoEntrega = document.getElementById('modal-tipo-entrega');
    const modalFilaDireccion = document.getElementById('modal-fila-direccion');
    const modalDireccion = document.getElementById('modal-direccion');
    const modalFechaEntrega = document.getElementById('modal-fecha-entrega');
    const modalTotalPagar = document.getElementById('modal-total-pagar');
    const botonConfirmarFinal = document.getElementById('boton-confirmar-final');
    const textoConfirmarFinal = document.getElementById('texto-confirmar-final');
    const spinnerConfirmarFinal = document.getElementById('spinner-confirmar-final');
    
    // Alertas
    const alertaErrorPedido = document.getElementById('alerta-error-pedido');
    const mensajeErrorPedido = document.getElementById('mensaje-error-pedido');
    const alertaExitoPedido = document.getElementById('alerta-exito-pedido');
    const mensajeExitoPedido = document.getElementById('mensaje-exito-pedido');
    
    // Campos de dirección
    const campoFechaEntrega = document.getElementById('campo-fecha-entrega');
    
    let carrito = [];
    let tipoEntregaSeleccionado = 'Domicilio';

    function validarHorarioEntrega() {
        const valorFecha = campoFechaEntrega?.value;
        const fechaEntrega = valorFecha ? new Date(valorFecha) : null;

        if (!fechaEntrega || Number.isNaN(fechaEntrega.getTime())) {
            mostrarError('Selecciona una fecha y hora de entrega.');
            return false;
        }

        if (fechaEntrega <= new Date()) {
            mostrarError('La fecha y hora de entrega debe ser futura.');
            return false;
        }

        const minutosEntrega = fechaEntrega.getHours() * 60 + fechaEntrega.getMinutes();
        if (minutosEntrega < 420 || minutosEntrega > 1020) {
            mostrarError('La hora de entrega debe estar dentro del horario de 07:00 a 17:00.');
            return false;
        }

        return true;
    }
    
    // Mostrar precio unitario al seleccionar producto
    if (selectorProducto) {
        selectorProducto.addEventListener('change', () => {
            const opcionSeleccionada = selectorProducto.options[selectorProducto.selectedIndex];
            if (opcionSeleccionada && opcionSeleccionada.value) {
                const precio = opcionSeleccionada.getAttribute('data-precio');
                if (precio) {
                    etiquetaPrecioUnitario.textContent = '$' + parseFloat(precio).toFixed(2);
                    infoPrecioProducto.classList.remove('d-none');
                }
            } else {
                infoPrecioProducto.classList.add('d-none');
            }
        });
    }
    
    // Agregar producto al carrito
    if (formularioAgregarItem) {
        formularioAgregarItem.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const opcionSeleccionada = selectorProducto.options[selectorProducto.selectedIndex];
            if (!opcionSeleccionada || !opcionSeleccionada.value) {
                mostrarError('Selecciona un producto');
                return;
            }
            
            const idProducto = parseInt(opcionSeleccionada.value);
            const nombreProducto = opcionSeleccionada.getAttribute('data-nombre');
            const precioProducto = parseFloat(opcionSeleccionada.getAttribute('data-precio'));
            const unidadProducto = opcionSeleccionada.getAttribute('data-unidad');
            const cantidad = parseFloat(campoCantidad.value);
            
            if (isNaN(cantidad) || cantidad <= 0) {
                mostrarError('Ingresa una cantidad válida');
                return;
            }
            
            // Verificar si ya existe en carrito
            const existente = carrito.find(item => item.id_producto === idProducto);
            if (existente) {
                existente.cantidad += cantidad;
            } else {
                carrito.push({
                    id_producto: idProducto,
                    nombre: nombreProducto,
                    precio_unitario: precioProducto,
                    unidad: unidadProducto,
                    cantidad: cantidad
                });
            }
            
            actualizarCarrito();
            formularioAgregarItem.reset();
            selectorProducto.selectedIndex = 0;
            infoPrecioProducto.classList.add('d-none');
            ocultarAlertas();
        });
    }
    
    // Actualizar visualización del carrito
    function actualizarCarrito() {
        // Limpiar tabla
        const filasProducto = cuerpoTablaCarrito.querySelectorAll('.fila-producto-carrito');
        filasProducto.forEach(fila => fila.remove());
        
        if (carrito.length === 0) {
            if (filaCarritoVacio) filaCarritoVacio.classList.remove('d-none');
            botonRevisarPedido.disabled = true;
        } else {
            if (filaCarritoVacio) filaCarritoVacio.classList.add('d-none');
            botonRevisarPedido.disabled = false;
            
            let subtotal = 0;
            let totalArticulos = 0;
            
            carrito.forEach((item, index) => {
                const itemSubtotal = item.cantidad * item.precio_unitario;
                subtotal += itemSubtotal;
                totalArticulos += item.cantidad;
                
                const fila = document.createElement('tr');
                fila.className = 'fila-producto-carrito';
                fila.innerHTML = `
                    <td>
                        <div class="fw-semibold">${escapeHtml(item.nombre)}</div>
                        <small class="text-muted">${escapeHtml(item.unidad)}</small>
                    </td>
                    <td class="text-center">${item.cantidad.toFixed(1)}</td>
                    <td class="text-end">$${item.precio_unitario.toFixed(2)}</td>
                    <td class="text-end fw-semibold">$${itemSubtotal.toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarDelCarrito(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                cuerpoTablaCarrito.appendChild(fila);
            });
            
            resumenCantidadArticulos.textContent = totalArticulos.toFixed(1);
            resumenSubtotal.textContent = '$' + subtotal.toFixed(2);
            resumenTotal.textContent = '$' + subtotal.toFixed(2);
        }
    }
    
    // Eliminar del carrito
    window.eliminarDelCarrito = function(index) {
        carrito.splice(index, 1);
        actualizarCarrito();
    };
    
    // Selección de tipo de entrega
    if (opcionDomicilio) {
        opcionDomicilio.addEventListener('click', () => {
            tipoEntregaSeleccionado = 'Domicilio';
            opcionDomicilio.classList.add('seleccionada');
            opcionLocal.classList.remove('seleccionada');
            if (bloqueDireccionEntrega) bloqueDireccionEntrega.style.display = 'block';
        });
    }
    
    if (opcionLocal) {
        opcionLocal.addEventListener('click', () => {
            tipoEntregaSeleccionado = 'Recoger en local';
            opcionLocal.classList.add('seleccionada');
            opcionDomicilio.classList.remove('seleccionada');
            if (bloqueDireccionEntrega) bloqueDireccionEntrega.style.display = 'none';
        });
    }
    
    // Selector de dirección guardada
    if (selectorDireccionGuardada) {
        selectorDireccionGuardada.addEventListener('change', () => {
            if (selectorDireccionGuardada.value === 'nueva') {
                if (formularioNuevaDireccion) formularioNuevaDireccion.classList.remove('d-none');
            } else {
                if (formularioNuevaDireccion) formularioNuevaDireccion.classList.add('d-none');
            }
        });
    }
    
    // Revisar y mostrar modal de confirmación
    if (botonRevisarPedido) {
        botonRevisarPedido.addEventListener('click', () => {
            if (carrito.length === 0) {
                mostrarError('El carrito está vacío');
                return;
            }

            if (!validarHorarioEntrega()) {
                return;
            }
            
            // Llenar lista de productos en modal
            listaResumenModal.innerHTML = '';
            let subtotal = 0;
            carrito.forEach(item => {
                const itemSubtotal = item.cantidad * item.precio_unitario;
                subtotal += itemSubtotal;
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <div>
                        <div class="fw-semibold">${escapeHtml(item.nombre)}</div>
                        <small class="text-muted">${item.cantidad.toFixed(1)} ${escapeHtml(item.unidad)} x $${item.precio_unitario.toFixed(2)}</small>
                    </div>
                    <span class="fw-semibold">$${itemSubtotal.toFixed(2)}</span>
                `;
                listaResumenModal.appendChild(li);
            });
            
            // Configurar datos del modal
            modalTipoEntrega.textContent = tipoEntregaSeleccionado;
            modalTotalPagar.textContent = '$' + subtotal.toFixed(2);
            
            if (campoFechaEntrega && campoFechaEntrega.value) {
                const fecha = new Date(campoFechaEntrega.value);
                modalFechaEntrega.textContent = fecha.toLocaleString('es-MX');
            }
            
            if (tipoEntregaSeleccionado === 'Domicilio') {
                modalFilaDireccion.style.display = 'flex';
                let direccionTexto = 'Sin dirección especificada';
                
                if (selectorDireccionGuardada && selectorDireccionGuardada.value !== 'nueva') {
                    const opcionDir = selectorDireccionGuardada.options[selectorDireccionGuardada.selectedIndex];
                    if (opcionDir) {
                        direccionTexto = opcionDir.textContent;
                    }
                } else if (document.getElementById('campo-direccion-calle')?.value) {
                    const calle = document.getElementById('campo-direccion-calle').value;
                    const colonia = document.getElementById('campo-direccion-colonia')?.value || '';
                    const municipio = document.getElementById('campo-direccion-municipio')?.value || '';
                    direccionTexto = `${calle}, ${colonia}, ${municipio}`.replace(/, ,/g, ',').replace(/^,|,$/g, '');
                }
                modalDireccion.textContent = direccionTexto;
            } else {
                modalFilaDireccion.style.display = 'none';
            }
            
            modalConfirmacionPedido.show();
            ocultarAlertas();
        });
    }
    
    // Confirmar pedido final
    if (botonConfirmarFinal) {
        botonConfirmarFinal.addEventListener('click', async () => {
            if (!validarHorarioEntrega()) {
                return;
            }

            // Preparar datos
            const datosPedido = {
                items: carrito.map(item => ({
                    id_producto: item.id_producto,
                    cantidad: item.cantidad
                })),
                tipo_entrega: tipoEntregaSeleccionado,
                fecha_hora_entrega_solicitada: campoFechaEntrega ? campoFechaEntrega.value : null
            };
            
            if (tipoEntregaSeleccionado === 'Domicilio') {
                const usarDireccionGuardada = selectorDireccionGuardada
                    && selectorDireccionGuardada.value
                    && selectorDireccionGuardada.value !== 'nueva';

                if (usarDireccionGuardada) {
                    datosPedido.id_direccion = selectorDireccionGuardada.value;
                } else {
                    const calle = document.getElementById('campo-direccion-calle')?.value.trim() || '';
                    const municipio = document.getElementById('campo-direccion-municipio')?.value.trim() || '';

                    if (!calle || !municipio) {
                        mostrarError('Captura la calle y el municipio para la entrega a domicilio.');
                        return;
                    }

                    datosPedido.direccion = {
                        calle: calle,
                        colonia: document.getElementById('campo-direccion-colonia')?.value.trim() || '',
                        municipio: municipio,
                        codigo_postal: '',
                        referencia: document.getElementById('campo-direccion-referencia')?.value.trim() || ''
                    };
                }
            }
            
            botonConfirmarFinal.disabled = true;
            if (spinnerConfirmarFinal) spinnerConfirmarFinal.classList.remove('d-none');
            if (textoConfirmarFinal) textoConfirmarFinal.textContent = 'Enviando...';
            
            try {
                const respuesta = await fetch('/proyecto-tienda/modules/3_ecommerce/api/create_online_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(datosPedido)
                });
                
                const resultado = await respuesta.json();
                
                if (respuesta.ok && resultado.exito) {
                    modalConfirmacionPedido.hide();
                    mostrarExito(`¡Pedido ${resultado.numero_pedido} creado exitosamente! Te redirigiremos a tus pedidos.`);
                    carrito = [];
                    actualizarCarrito();
                    setTimeout(() => {
                        window.location.href = '/proyecto-tienda/?ruta=/mis-pedidos';
                    }, 2000);
                } else {
                    mostrarError(resultado.mensaje || 'Error al crear el pedido');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('No se pudo conectar con el servidor');
            } finally {
                botonConfirmarFinal.disabled = false;
                if (spinnerConfirmarFinal) spinnerConfirmarFinal.classList.add('d-none');
                if (textoConfirmarFinal) textoConfirmarFinal.textContent = 'Confirmar y Enviar Pedido';
            }
        });
    }
    
    function mostrarError(mensaje) {
        if (alertaErrorPedido && mensajeErrorPedido) {
            mensajeErrorPedido.textContent = mensaje;
            alertaErrorPedido.classList.remove('d-none');
            alertaErrorPedido.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    function mostrarExito(mensaje) {
        if (alertaExitoPedido && mensajeExitoPedido) {
            mensajeExitoPedido.textContent = mensaje;
            alertaExitoPedido.classList.remove('d-none');
            alertaExitoPedido.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    function ocultarAlertas() {
        if (alertaErrorPedido) alertaErrorPedido.classList.add('d-none');
        if (alertaExitoPedido) alertaExitoPedido.classList.add('d-none');
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Convierte una fecha al formato requerido por un input datetime-local.
     */
    function formatearFechaParaInput(fecha) {
        const fechaAjustada = new Date(fecha.getTime() - fecha.getTimezoneOffset() * 60000);
        return fechaAjustada.toISOString().slice(0, 16);
    }

    // Recargar en el carrito los productos de un pedido anterior (HU6 - Repetir pedido)
    document.addEventListener('pedido:repetir', (evento) => {
        const pedido = evento.detail?.pedido;
        if (!pedido || !Array.isArray(pedido.detalles) || pedido.detalles.length === 0) {
            mostrarError('El pedido seleccionado no tiene productos para repetir.');
            return;
        }

        carrito = pedido.detalles.map((detalle) => {
            const idProducto = Number(detalle.id_producto);
            const opcionActual = selectorProducto
                ? Array.from(selectorProducto.options).find((opcion) => Number(opcion.value) === idProducto)
                : null;

            return {
                id_producto: idProducto,
                nombre: opcionActual ? opcionActual.getAttribute('data-nombre') : detalle.nombre,
                precio_unitario: opcionActual
                    ? parseFloat(opcionActual.getAttribute('data-precio'))
                    : Number(detalle.precio_unitario),
                unidad: opcionActual ? opcionActual.getAttribute('data-unidad') : detalle.unidad,
                cantidad: Number(detalle.cantidad)
            };
        });

        tipoEntregaSeleccionado = pedido.tipo_entrega === 'Recoger en local'
            ? 'Recoger en local'
            : 'Domicilio';

        if (tipoEntregaSeleccionado === 'Recoger en local') {
            if (opcionLocal) opcionLocal.classList.add('seleccionada');
            if (opcionDomicilio) opcionDomicilio.classList.remove('seleccionada');
            if (bloqueDireccionEntrega) bloqueDireccionEntrega.style.display = 'none';
        } else {
            if (opcionDomicilio) opcionDomicilio.classList.add('seleccionada');
            if (opcionLocal) opcionLocal.classList.remove('seleccionada');
            if (bloqueDireccionEntrega) bloqueDireccionEntrega.style.display = 'block';
        }

        // Conservar la dirección de entrega del pedido anterior cuando sea posible.
        if (tipoEntregaSeleccionado === 'Domicilio') {
            const opcionDireccion = selectorDireccionGuardada && pedido.id_direccion
                ? Array.from(selectorDireccionGuardada.options).find(
                    (opcion) => Number(opcion.value) === Number(pedido.id_direccion)
                )
                : null;

            if (selectorDireccionGuardada && opcionDireccion) {
                selectorDireccionGuardada.value = opcionDireccion.value;
                if (formularioNuevaDireccion) formularioNuevaDireccion.classList.add('d-none');
            } else {
                if (selectorDireccionGuardada) selectorDireccionGuardada.value = 'nueva';
                if (formularioNuevaDireccion) formularioNuevaDireccion.classList.remove('d-none');

                const camposDireccion = {
                    'campo-direccion-calle': pedido.calle,
                    'campo-direccion-colonia': pedido.colonia,
                    'campo-direccion-municipio': pedido.municipio,
                    'campo-direccion-referencia': pedido.referencia
                };

                Object.entries(camposDireccion).forEach(([idCampo, valor]) => {
                    const campo = document.getElementById(idCampo);
                    if (campo && valor !== null && valor !== undefined) {
                        campo.value = valor;
                    }
                });
            }
        }

        // Solo se modifica la fecha de entrega; el resto del pedido se conserva.
        if (campoFechaEntrega) {
            const fechaSugerida = new Date(Date.now() + 60 * 60 * 1000);
            campoFechaEntrega.value = formatearFechaParaInput(fechaSugerida);
            campoFechaEntrega.min = formatearFechaParaInput(new Date());
        }

        actualizarCarrito();
        ocultarAlertas();
    });

    const pedidoPendiente = sessionStorage.getItem('pedidoParaRepetir');
    if (pedidoPendiente) {
        sessionStorage.removeItem('pedidoParaRepetir');
        try {
            document.dispatchEvent(new CustomEvent('pedido:repetir', {
                detail: { pedido: JSON.parse(pedidoPendiente) }
            }));
        } catch {
            mostrarError('No se pudo recuperar el pedido seleccionado.');
        }
    }

    // Inicializar
    if (bloqueDireccionEntrega) bloqueDireccionEntrega.style.display = 'block';
    actualizarCarrito();
});

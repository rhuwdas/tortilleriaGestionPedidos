/** Interfaz de cobro, cálculo de cambio e impresión de ticket (HU9). */
(() => {
	const contenedor = document.getElementById('caja');
	const idPedido = Number(contenedor.dataset.idPedido);
	const alerta = document.getElementById('alerta-caja');
	const panelCobro = document.getElementById('panel-cobro');
	const campoEfectivo = document.getElementById('efectivo-recibido');
	const botonCobrar = document.getElementById('boton-registrar-cobro');
	const avisoFaltante = document.getElementById('aviso-faltante');
	const montoCambio = document.getElementById('monto-cambio');
	const ticket = document.getElementById('ticket-impresion');

	let pedidoActual = null;
	let pagoRegistrado = false;

	const mostrarAlerta = (mensaje, tipo = 'danger') => {
		alerta.textContent = mensaje;
		alerta.className = `alert alert-${tipo}`;
	};

	const formatoMoneda = (monto) => `$${Number(monto).toFixed(2)}`;

	const pintarPedido = (pedido) => {
		pedidoActual = pedido;
		document.getElementById('numero-pedido-caja').textContent = `PED-${String(pedido.id_pedido).padStart(6, '0')}`;
		document.getElementById('estado-pedido-caja').textContent = pedido.estado;
		document.getElementById('total-cobro').textContent = formatoMoneda(pedido.total);

		const datosCliente = document.getElementById('datos-cliente-caja');
		const nombreCliente = document.createElement('p');
		nombreCliente.textContent = `Cliente: ${pedido.nombre_cliente} ${pedido.apellidos_cliente}`.trim();
		const telefono = document.createElement('p');
		telefono.textContent = `Teléfono: ${pedido.telefono || 'No registrado'}`;
		const canal = document.createElement('p');
		canal.textContent = `Origen: ${pedido.canal} · Entrega: ${pedido.tipo_entrega}`;
		datosCliente.replaceChildren(nombreCliente, telefono, canal);
		if (pedido.tipo_entrega === 'Domicilio') {
			const direccion = document.createElement('p');
			direccion.textContent = `Dirección: ${[pedido.calle, pedido.colonia, pedido.municipio].filter(Boolean).join(', ')}`;
			datosCliente.append(direccion);
		}

		const cuerpo = document.getElementById('productos-cobro');
		cuerpo.replaceChildren();
		pedido.detalles.forEach((detalle) => {
			const fila = document.createElement('tr');
			const producto = document.createElement('td');
			producto.textContent = `${detalle.nombre} · $${Number(detalle.precio_unitario).toFixed(2)} / ${detalle.unidad}`;
			const cantidad = document.createElement('td');
			cantidad.className = 'text-end';
			cantidad.textContent = String(detalle.cantidad);
			const subtotal = document.createElement('td');
			subtotal.className = 'text-end';
			subtotal.textContent = formatoMoneda(detalle.subtotal);
			fila.append(producto, cantidad, subtotal);
			cuerpo.append(fila);
		});
		panelCobro.classList.remove('d-none');
		document.getElementById('estado-carga-caja').textContent = '';
		actualizarCambio();
	};

	const actualizarCambio = () => {
		if (!pedidoActual || pagoRegistrado) return;
		const efectivo = Number(campoEfectivo.value);
		const total = Number(pedidoActual.total);
		const valido = Number.isFinite(efectivo) && efectivo >= total;
		const faltante = Math.max(total - (Number.isFinite(efectivo) ? efectivo : 0), 0);
		montoCambio.textContent = formatoMoneda(valido ? efectivo - total : 0);
		avisoFaltante.classList.toggle('d-none', valido || !(efectivo > 0));
		if (!valido && efectivo > 0) avisoFaltante.textContent = `Faltan ${formatoMoneda(faltante)}.`;
		botonCobrar.disabled = !valido || pagoRegistrado;
	};

	const imprimirTicket = (datosTicket) => {
		document.getElementById('ticket-numero-venta').textContent = datosTicket.numero_venta;
		document.getElementById('ticket-numero-pedido').textContent = datosTicket.numero_pedido;
		document.getElementById('ticket-fecha').textContent = new Date(datosTicket.fecha_venta.replace(' ', 'T')).toLocaleString('es-MX');
		document.getElementById('ticket-cliente').textContent = `${pedidoActual.nombre_cliente} ${pedidoActual.apellidos_cliente}`.trim();
		document.getElementById('ticket-total').textContent = formatoMoneda(datosTicket.total);
		document.getElementById('ticket-recibido').textContent = formatoMoneda(datosTicket.efectivo_recibido);
		document.getElementById('ticket-cambio').textContent = formatoMoneda(datosTicket.cambio);

		const cuerpoTicket = document.getElementById('ticket-productos');
		cuerpoTicket.replaceChildren();
		datosTicket.detalles.forEach((detalle) => {
			const fila = document.createElement('tr');
			[fila.insertCell(), fila.insertCell(), fila.insertCell()].forEach((celda) => celda.textContent = '');
			fila.cells[0].textContent = detalle.nombre;
			fila.cells[1].textContent = String(detalle.cantidad);
			fila.cells[2].textContent = formatoMoneda(detalle.subtotal);
			cuerpoTicket.append(fila);
		});

		ticket.classList.remove('d-none');
		panelCobro.classList.add('d-none');
		alerta.className = 'alert alert-success';
		alerta.textContent = 'Pago registrado. El ticket está listo para imprimir.';
		window.setTimeout(() => window.print(), 250);
	};

	const cargarPedido = async () => {
		if (!idPedido) {
			document.getElementById('estado-carga-caja').textContent = '';
			mostrarAlerta('Selecciona un pedido desde la bandeja para cobrarlo.');
			return;
		}
		try {
			const respuesta = await CajaService.obtenerPedido(idPedido);
			pintarPedido(respuesta.pedido);
		} catch (error) {
			document.getElementById('estado-carga-caja').textContent = '';
			mostrarAlerta(error.message);
		}
	};

	campoEfectivo.addEventListener('input', actualizarCambio);
	botonCobrar.addEventListener('click', async () => {
		if (!pedidoActual || botonCobrar.disabled) return;
		botonCobrar.disabled = true;
		botonCobrar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Procesando…';
		try {
			const respuesta = await CajaService.registrarPago(idPedido, Number(campoEfectivo.value));
			pagoRegistrado = true;
			imprimirTicket(respuesta.ticket);
		} catch (error) {
			mostrarAlerta(error.message);
			botonCobrar.innerHTML = '<i class="bi bi-receipt me-1" aria-hidden="true"></i>Registrar venta e imprimir ticket';
			actualizarCambio();
		}
	});

	document.getElementById('boton-imprimir-ticket').addEventListener('click', () => window.print());
	cargarPedido();
})();
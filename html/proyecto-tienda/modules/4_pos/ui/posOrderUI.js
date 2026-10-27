/** Captura de pedidos presenciales y telefónicos (HU8). */
(() => {
	const botonVistas = document.querySelectorAll('[data-vista-pos]');
	const seccionBandeja = document.getElementById('seccion-bandeja');
	const seccionCaptura = document.getElementById('seccion-nuevo-pedido');
	const campoBusqueda = document.getElementById('buscar-cliente-pos');
	const listaClientes = document.getElementById('resultados-clientes-pos');
	const clienteSeleccionado = document.getElementById('cliente-seleccionado-pos');
	const formularioClienteNuevo = document.getElementById('formulario-cliente-nuevo-pos');
	const productoSelector = document.getElementById('producto-pos');
	const cantidadCampo = document.getElementById('cantidad-pos');
	const cuerpoTicket = document.getElementById('detalle-ticket-pos');
	const totalTicket = document.getElementById('total-ticket-pos');
	const botonRegistrar = document.getElementById('registrar-pedido-pos');
	const alerta = document.getElementById('alerta-pedido-presencial');
	const selectorEntrega = document.getElementById('entrega-pedido-pos');
	const bloqueDireccion = document.getElementById('direccion-entrega-pos');

	let clienteActual = null;
	let articulosTicket = [];
	let temporizadorBusqueda = null;
	let numeroSolicitudBusqueda = 0;
	let guardandoPedido = false;

	const cambiarVista = (nombreVista) => {
		const mostrarBandeja = nombreVista === 'bandeja';
		seccionBandeja.classList.toggle('d-none', !mostrarBandeja);
		seccionCaptura.classList.toggle('d-none', mostrarBandeja);
		botonVistas.forEach((boton) => {
			const activo = boton.dataset.vistaPos === nombreVista;
			boton.classList.toggle('activo', activo);
			boton.setAttribute('aria-pressed', String(activo));
		});
	};

	const mostrarAlerta = (mensaje, tipo = 'danger') => {
		alerta.textContent = mensaje;
		alerta.className = `alert alert-${tipo}`;
	};

	const limpiarAlerta = () => {
		alerta.textContent = '';
		alerta.className = 'alert d-none';
	};

	const limpiarCliente = () => {
		clienteActual = null;
		clienteSeleccionado.textContent = '';
		clienteSeleccionado.classList.add('d-none');
		campoBusqueda.disabled = false;
	};

	const seleccionarCliente = (cliente) => {
		clienteActual = { id_cliente: Number(cliente.id_cliente) };
		clienteSeleccionado.textContent = `Cliente: ${cliente.nombre} ${cliente.apellidos}${cliente.telefono ? ` · ${cliente.telefono}` : ''}`;
		clienteSeleccionado.classList.remove('d-none');
		campoBusqueda.value = '';
		campoBusqueda.disabled = true;
		listaClientes.replaceChildren();
		listaClientes.classList.add('d-none');
		formularioClienteNuevo.classList.add('d-none');
		actualizarDisponibilidadRegistro();
	};

	const renderizarClientes = (clientes) => {
		listaClientes.replaceChildren();
		if (clientes.length === 0) {
			const vacio = document.createElement('p');
			vacio.className = 'resultado-cliente-vacio';
			vacio.textContent = 'No se encontraron clientes.';
			listaClientes.append(vacio);
			listaClientes.classList.remove('d-none');
			return;
		}

		clientes.forEach((cliente) => {
			const boton = document.createElement('button');
			boton.type = 'button';
			boton.className = 'resultado-cliente';
			boton.dataset.idCliente = cliente.id_cliente;
			boton.dataset.nombre = cliente.nombre;
			boton.dataset.apellidos = cliente.apellidos;
			boton.dataset.telefono = cliente.telefono || '';
			boton.textContent = `${cliente.nombre} ${cliente.apellidos}${cliente.telefono ? ` · ${cliente.telefono}` : ''}`;
			listaClientes.append(boton);
		});
		listaClientes.classList.remove('d-none');
	};

	const buscarClientes = async () => {
		const termino = campoBusqueda.value.trim();
		const solicitud = ++numeroSolicitudBusqueda;
		if (termino.length < 2) {
			listaClientes.replaceChildren();
			listaClientes.classList.add('d-none');
			return;
		}

		try {
			const respuesta = await PosService.buscarClientes(termino);
			if (solicitud === numeroSolicitudBusqueda) {
				renderizarClientes(respuesta.clientes || []);
			}
		} catch (error) {
			if (solicitud === numeroSolicitudBusqueda) mostrarAlerta(error.message);
		}
	};

	const actualizarDisponibilidadRegistro = () => {
		botonRegistrar.disabled = guardandoPedido || !clienteActual || articulosTicket.length === 0;
	};

	const renderizarTicket = () => {
		cuerpoTicket.replaceChildren();
		let total = 0;

		if (articulosTicket.length === 0) {
			const filaVacia = document.createElement('tr');
			const celdaVacia = document.createElement('td');
			celdaVacia.colSpan = 3;
			celdaVacia.className = 'text-center text-muted py-3';
			celdaVacia.textContent = 'Agrega productos al ticket.';
			filaVacia.append(celdaVacia);
			cuerpoTicket.append(filaVacia);
		}

		articulosTicket.forEach((articulo, indice) => {
			const fila = document.createElement('tr');
			const descripcion = document.createElement('td');
			const nombre = document.createElement('strong');
			nombre.textContent = articulo.nombre;
			const cantidad = document.createElement('small');
			cantidad.className = 'd-block text-muted';
			cantidad.textContent = `${articulo.cantidad} ${articulo.unidad} × $${articulo.precio.toFixed(2)}`;
			descripcion.append(nombre, cantidad);

			const subtotal = articulo.cantidad * articulo.precio;
			total += subtotal;
			const celdaSubtotal = document.createElement('td');
			celdaSubtotal.className = 'text-end';
			celdaSubtotal.textContent = `$${subtotal.toFixed(2)}`;

			const celdaAccion = document.createElement('td');
			const quitar = document.createElement('button');
			quitar.type = 'button';
			quitar.className = 'btn btn-sm btn-outline-danger';
			quitar.dataset.quitarArticulo = String(indice);
			quitar.setAttribute('aria-label', `Quitar ${articulo.nombre}`);
			quitar.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
			celdaAccion.append(quitar);
			fila.append(descripcion, celdaSubtotal, celdaAccion);
			cuerpoTicket.append(fila);
		});

		totalTicket.textContent = `$${total.toFixed(2)}`;
		actualizarDisponibilidadRegistro();
	};

	botonVistas.forEach((boton) => boton.addEventListener('click', () => cambiarVista(boton.dataset.vistaPos)));

	campoBusqueda.addEventListener('input', () => {
		window.clearTimeout(temporizadorBusqueda);
		temporizadorBusqueda = window.setTimeout(buscarClientes, 250);
	});

	listaClientes.addEventListener('click', (evento) => {
		const boton = evento.target.closest('.resultado-cliente');
		if (!boton) return;
		seleccionarCliente({
			id_cliente: Number(boton.dataset.idCliente),
			nombre: boton.dataset.nombre,
			apellidos: boton.dataset.apellidos,
			telefono: boton.dataset.telefono,
		});
	});

	document.getElementById('limpiar-cliente-pos').addEventListener('click', () => {
		limpiarCliente();
		actualizarDisponibilidadRegistro();
		campoBusqueda.focus();
	});

	document.getElementById('mostrar-cliente-nuevo').addEventListener('click', () => {
		limpiarCliente();
		formularioClienteNuevo.classList.toggle('d-none');
		listaClientes.classList.add('d-none');
		if (!formularioClienteNuevo.classList.contains('d-none')) {
			document.getElementById('nombre-cliente-pos').focus();
		}
	});

	document.getElementById('usar-cliente-nuevo').addEventListener('click', () => {
		const nombre = document.getElementById('nombre-cliente-pos').value.trim();
		const apellidos = document.getElementById('apellidos-cliente-pos').value.trim();
		const telefono = document.getElementById('telefono-cliente-pos').value.trim();
		if (!nombre || !apellidos) {
			mostrarAlerta('Captura el nombre y los apellidos del cliente.');
			return;
		}

		clienteActual = { cliente_nuevo: { nombre, apellidos, telefono } };
		clienteSeleccionado.textContent = `Cliente nuevo: ${nombre} ${apellidos}${telefono ? ` · ${telefono}` : ''}`;
		clienteSeleccionado.classList.remove('d-none');
		formularioClienteNuevo.classList.add('d-none');
		campoBusqueda.value = '';
		campoBusqueda.disabled = true;
		limpiarAlerta();
		actualizarDisponibilidadRegistro();
	});

	document.getElementById('agregar-producto-pos').addEventListener('click', () => {
		const opcion = productoSelector.selectedOptions[0];
		const cantidad = Number(cantidadCampo.value);
		if (!opcion?.value || !Number.isFinite(cantidad) || cantidad <= 0) {
			mostrarAlerta('Selecciona un producto e indica una cantidad válida.');
			return;
		}

		const idProducto = Number(opcion.value);
		const existente = articulosTicket.find((articulo) => articulo.id_producto === idProducto);
		if (existente) {
			existente.cantidad += cantidad;
		} else {
			articulosTicket.push({
				id_producto: idProducto,
				nombre: opcion.textContent.split(' · $')[0],
					precio: Number(opcion.dataset.precio),
					unidad: opcion.dataset.unidad,
					cantidad,
			});
		}
		productoSelector.value = '';
		cantidadCampo.value = '1';
		limpiarAlerta();
		renderizarTicket();
	});

	selectorEntrega.addEventListener('change', () => {
		bloqueDireccion.classList.toggle('d-none', selectorEntrega.value !== 'Domicilio');
	});

	cuerpoTicket.addEventListener('click', (evento) => {
		const boton = evento.target.closest('[data-quitar-articulo]');
		if (!boton) return;
		articulosTicket.splice(Number(boton.dataset.quitarArticulo), 1);
		renderizarTicket();
	});

	botonRegistrar.addEventListener('click', async () => {
		if (!clienteActual || articulosTicket.length === 0) {
			mostrarAlerta('Selecciona un cliente y agrega productos al ticket.');
			return;
		}

		const fechaEntrega = document.getElementById('fecha-entrega-pos').value;
		if (!fechaEntrega || new Date(fechaEntrega) <= new Date()) {
			mostrarAlerta('La fecha y hora de entrega debe ser futura.');
			return;
		}
		const horaEntrega = new Date(fechaEntrega);
		const minutosEntrega = horaEntrega.getHours() * 60 + horaEntrega.getMinutes();
		if (minutosEntrega < 420 || minutosEntrega > 1020) {
			mostrarAlerta('La hora de entrega debe estar dentro del horario de 07:00 a 17:00.');
			return;
		}
		const tipoEntrega = selectorEntrega.value;
		const direccion = tipoEntrega === 'Domicilio'
			? {
				calle: document.getElementById('calle-entrega-pos').value.trim(),
				colonia: document.getElementById('colonia-entrega-pos').value.trim(),
				municipio: document.getElementById('municipio-entrega-pos').value.trim(),
				referencia: document.getElementById('referencia-entrega-pos').value.trim(),
			}
			: null;
		if (tipoEntrega === 'Domicilio' && (!direccion.calle || !direccion.municipio)) {
			mostrarAlerta('Para entrega a domicilio captura la calle y selecciona el municipio.');
			return;
		}

		const datosPedido = {
			...clienteActual,
			canal: document.getElementById('canal-pedido-pos').value,
			tipo_entrega: tipoEntrega,
			fecha_hora_entrega_solicitada: fechaEntrega,
			direccion,
			items: articulosTicket.map(({ id_producto, cantidad }) => ({ id_producto, cantidad })),
		};

		guardandoPedido = true;
		actualizarDisponibilidadRegistro();
		try {
			const respuesta = await PosService.crearPedido(datosPedido);
			const idPedido = Number(respuesta.id_pedido);

			if (!idPedido) {
				throw new Error('El pedido se registró, pero no se recibió su identificador para cobrarlo.');
			}

			// Al confirmar el pedido se navega directo a la caja para cobrarlo.
			window.location.href = `/proyecto-tienda/?ruta=/caja&id_pedido=${idPedido}`;
		} catch (error) {
			mostrarAlerta(error.message || 'No se pudo registrar el pedido.');
		} finally {
			guardandoPedido = false;
			actualizarDisponibilidadRegistro();
		}
	});

	renderizarTicket();
})();
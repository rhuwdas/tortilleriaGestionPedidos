/** Interfaz de recepción y notificación de pedidos web (HU7). */
(() => {
	const listaPedidos = document.getElementById('lista-pedidos');
	const alertaPedidosNuevos = document.getElementById('alerta-pedidos-nuevos');
	const mensajeEstado = document.getElementById('mensaje-estado');
	const contadorPedidos = document.getElementById('contador-pedidos');
	const botonSonido = document.getElementById('boton-sonido');
	const dialogoRechazo = document.getElementById('dialogo-rechazo');
	const formularioRechazo = document.getElementById('formulario-rechazo');
	const campoMotivo = document.getElementById('motivo-rechazo');
	const mensajeAccion = document.getElementById('mensaje-accion');

	let pedidosConocidos = new Set();
	let cargaInicial = true;
	let sonidoActivo = false;
	let idPedidoPorRechazar = null;
	let cargando = false;
	let temporizadorAlerta = null;

	const formatearFecha = (fecha) => {
		if (!fecha) return 'Sin horario especificado';
		const fechaValida = new Date(fecha.replace(' ', 'T'));
		if (Number.isNaN(fechaValida.getTime())) return 'Horario no disponible';
		return new Intl.DateTimeFormat('es-MX', {
			dateStyle: 'medium',
			timeStyle: 'short',
		}).format(fechaValida);
	};

	const crearTexto = (etiqueta, valor, clase = '') => {
		const elemento = document.createElement('p');
		elemento.className = clase;
		const nombreCampo = document.createElement('strong');
		nombreCampo.textContent = `${etiqueta}: `;
		elemento.append(nombreCampo, document.createTextNode(valor || 'No especificado'));
		return elemento;
	};

	const crearTarjetaPedido = (pedido) => {
		const tarjeta = document.createElement('article');
		tarjeta.className = 'pedido-entrante';
		tarjeta.dataset.idPedido = pedido.id_pedido;

		const encabezado = document.createElement('div');
		encabezado.className = 'pedido-encabezado';
		const titulo = document.createElement('h2');
		titulo.textContent = `Pedido PED-${String(pedido.id_pedido).padStart(6, '0')}`;
		const canal = document.createElement('span');
		canal.className = 'etiqueta-canal';
		canal.textContent = pedido.canal;
		const tipoEntrega = document.createElement('span');
		tipoEntrega.className = 'etiqueta-entrega';
		tipoEntrega.textContent = pedido.tipo_entrega;
		const estado = document.createElement('span');
		estado.className = 'etiqueta-estado-pedido';
		estado.textContent = pedido.estado;
		encabezado.append(titulo, canal, tipoEntrega, estado);

		const datosCliente = document.createElement('div');
		datosCliente.className = 'pedido-datos';
		datosCliente.append(
			crearTexto('Cliente', `${pedido.nombre_cliente} ${pedido.apellidos_cliente}`.trim()),
			crearTexto('Teléfono', pedido.telefono),
			crearTexto('Entrega solicitada', formatearFecha(pedido.fecha_hora_entrega_solicitada))
		);

		if (pedido.tipo_entrega === 'Domicilio') {
			const direccion = [pedido.calle, pedido.colonia, pedido.municipio].filter(Boolean).join(', ');
			datosCliente.append(crearTexto('Dirección', direccion));
		}

		const detalles = document.createElement('ul');
		detalles.className = 'pedido-productos';
		(pedido.detalles || []).forEach((detalle) => {
			const articulo = document.createElement('li');
			const subtotal = Number(detalle.cantidad) * Number(detalle.precio_unitario);
			articulo.textContent = `${detalle.producto}: ${detalle.cantidad} x $${Number(detalle.precio_unitario).toFixed(2)} = $${subtotal.toFixed(2)}`;
			detalles.append(articulo);
		});

		const total = (pedido.detalles || []).reduce(
			(suma, detalle) => suma + Number(detalle.cantidad) * Number(detalle.precio_unitario),
			0
		);
		const totalPedido = document.createElement('p');
		totalPedido.className = 'pedido-total';
		totalPedido.textContent = `Total: $${total.toFixed(2)}`;

		const acciones = document.createElement('div');
		acciones.className = 'pedido-acciones';
		if (pedido.estado === 'Pendiente' && pedido.canal === 'Web') {
			const botonAceptar = document.createElement('button');
			botonAceptar.type = 'button';
			botonAceptar.className = 'btn btn-success';
			botonAceptar.dataset.accion = 'aceptar';
			botonAceptar.textContent = 'Aceptar';
			const botonRechazar = document.createElement('button');
			botonRechazar.type = 'button';
			botonRechazar.className = 'btn btn-outline-danger';
			botonRechazar.dataset.accion = 'rechazar';
			botonRechazar.textContent = 'Rechazar';
			acciones.append(botonAceptar, botonRechazar);
		} else {
			const botonCobrar = document.createElement('button');
			botonCobrar.type = 'button';
			botonCobrar.className = 'btn btn-primary';
			botonCobrar.dataset.accion = 'cobrar';
			botonCobrar.innerHTML = '<i class="bi bi-cash-coin me-1" aria-hidden="true"></i>Cobrar';
			acciones.append(botonCobrar);
		}

		tarjeta.append(encabezado, datosCliente, detalles, totalPedido, acciones);
		return tarjeta;
	};

	const reproducirAlerta = () => {
		if (!sonidoActivo || !window.AudioContext) return;
		const contexto = new AudioContext();
		const oscilador = contexto.createOscillator();
		const volumen = contexto.createGain();
		oscilador.type = 'sine';
		oscilador.frequency.value = 880;
		volumen.gain.setValueAtTime(0.18, contexto.currentTime);
		volumen.gain.exponentialRampToValueAtTime(0.001, contexto.currentTime + 0.45);
		oscilador.connect(volumen);
		volumen.connect(contexto.destination);
		oscilador.start();
		oscilador.stop(contexto.currentTime + 0.45);
		oscilador.addEventListener('ended', () => contexto.close(), { once: true });
	};

	const mostrarPedidos = (pedidos) => {
		listaPedidos.replaceChildren();
		contadorPedidos.textContent = String(pedidos.length);

		if (pedidos.length === 0) {
			const vacio = document.createElement('p');
			vacio.className = 'bandeja-vacia';
			vacio.textContent = 'No hay pedidos pendientes.';
			listaPedidos.append(vacio);
			return;
		}

		pedidos.forEach((pedido) => listaPedidos.append(crearTarjetaPedido(pedido)));
	};

	const cargarPedidos = async () => {
		if (cargando) return;
		cargando = true;
		try {
			const respuesta = await PosService.obtenerPedidosPendientes();
			const pedidos = respuesta.pedidos || [];
			const idsActuales = new Set(pedidos.map((pedido) => Number(pedido.id_pedido)));
			const nuevos = cargaInicial
				? []
				: pedidos.filter((pedido) => !pedidosConocidos.has(Number(pedido.id_pedido)));

			mostrarPedidos(pedidos);
			pedidosConocidos = idsActuales;
			cargaInicial = false;
			mensajeEstado.textContent = `Actualizado ${new Intl.DateTimeFormat('es-MX', {
				hour: '2-digit',
				minute: '2-digit',
				second: '2-digit',
			}).format(new Date())}`;

			if (nuevos.length > 0) {
				window.clearTimeout(temporizadorAlerta);
				alertaPedidosNuevos.classList.remove('d-none');
				alertaPedidosNuevos.textContent = nuevos.length === 1
					? 'Hay un nuevo pedido.'
					: `Hay ${nuevos.length} nuevos pedidos.`;
				reproducirAlerta();
				temporizadorAlerta = window.setTimeout(() => {
					alertaPedidosNuevos.classList.add('d-none');
				}, 10000);
			} else if (pedidos.length === 0) {
				window.clearTimeout(temporizadorAlerta);
				alertaPedidosNuevos.classList.add('d-none');
			}
		} catch (error) {
			mensajeEstado.textContent = 'No se pudo actualizar la bandeja.';
			mensajeAccion.textContent = error.message;
			mensajeAccion.classList.remove('d-none', 'alert-success');
			mensajeAccion.classList.add('alert-danger');
		} finally {
			cargando = false;
		}
	};

	const resolverPedido = async (idPedido, accion, motivo = '') => {
		mensajeAccion.classList.add('d-none');
		try {
			const respuesta = await PosService.resolverPedido(idPedido, accion, motivo);
			mensajeAccion.textContent = respuesta.mensaje;
			mensajeAccion.classList.remove('d-none', 'alert-danger');
			mensajeAccion.classList.add('alert-success');
			await cargarPedidos();
		} catch (error) {
			mensajeAccion.textContent = error.message;
			mensajeAccion.classList.remove('d-none', 'alert-success');
			mensajeAccion.classList.add('alert-danger');
			await cargarPedidos();
		}
	};

	listaPedidos.addEventListener('click', async (evento) => {
		const boton = evento.target.closest('button[data-accion]');
		if (!boton) return;

		const tarjeta = boton.closest('[data-id-pedido]');
		const idPedido = Number(tarjeta.dataset.idPedido);
		if (boton.dataset.accion === 'aceptar') {
			boton.disabled = true;
			await resolverPedido(idPedido, 'aceptar');
			return;
		}
		if (boton.dataset.accion === 'cobrar') {
			window.location.href = `/proyecto-tienda/?ruta=/caja&id_pedido=${idPedido}`;
			return;
		}

		idPedidoPorRechazar = idPedido;
		campoMotivo.value = '';
		dialogoRechazo.showModal();
		campoMotivo.focus();
	});

	formularioRechazo.addEventListener('submit', async (evento) => {
		evento.preventDefault();
		const motivo = campoMotivo.value.trim();
		if (!motivo) {
			campoMotivo.setCustomValidity('El campo de motivo no puede estar vacío.');
			campoMotivo.reportValidity();
			return;
		}
		campoMotivo.setCustomValidity('');
		const botonConfirmar = formularioRechazo.querySelector('[type="submit"]');
		botonConfirmar.disabled = true;
		await resolverPedido(idPedidoPorRechazar, 'rechazar', motivo);
		botonConfirmar.disabled = false;
		dialogoRechazo.close();
		idPedidoPorRechazar = null;
	});

	campoMotivo.addEventListener('input', () => campoMotivo.setCustomValidity(''));

	botonSonido.addEventListener('click', () => {
		sonidoActivo = !sonidoActivo;
		botonSonido.setAttribute('aria-pressed', String(sonidoActivo));
		botonSonido.innerHTML = sonidoActivo
			? '<i class="bi bi-volume-up-fill" aria-hidden="true"></i> Sonido activado'
			: '<i class="bi bi-volume-mute-fill" aria-hidden="true"></i> Activar sonido';
		if (sonidoActivo) reproducirAlerta();
	});

	document.getElementById('cerrar-dialogo-rechazo').addEventListener('click', () => {
		dialogoRechazo.close();
		idPedidoPorRechazar = null;
	});

	document.addEventListener('pedido:registrado', cargarPedidos);
	cargarPedidos();
	window.setInterval(cargarPedidos, 8000);
})();

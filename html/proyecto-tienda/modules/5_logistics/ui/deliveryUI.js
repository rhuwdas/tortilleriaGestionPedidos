/** Interfaz móvil de rutas y confirmación de entrega (HU10). */
(() => {
	const listaEntregas = document.getElementById('lista-entregas');
	const estadoRuta = document.getElementById('estado-ruta');
	const alertaRuta = document.getElementById('alerta-ruta');
	const botonActualizar = document.getElementById('actualizar-ruta');
	let cargando = false;

	const elemento = (etiqueta, clase, texto) => {
		const nodo = document.createElement(etiqueta);
		nodo.className = clase;
		nodo.textContent = texto;
		return nodo;
	};

	const moneda = (monto) => `$${Number(monto).toFixed(2)}`;

	const formatearFecha = (fecha) => {
		if (!fecha) return 'Horario no especificado';
		const valor = new Date(String(fecha).replace(' ', 'T'));
		if (Number.isNaN(valor.getTime())) return String(fecha);
		return valor.toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
	};

	const crearTarjetaEntrega = (entrega) => {
		const tarjeta = document.createElement('article');
		tarjeta.className = 'tarjeta-entrega';

		const encabezado = document.createElement('div');
		encabezado.className = 'encabezado-entrega';
		const numero = elemento('h2', 'numero-entrega', entrega.numero_pedido);
		const hora = elemento('time', 'hora-entrega', formatearFecha(entrega.fecha_hora_entrega_solicitada));
		encabezado.append(numero, hora);

		const cliente = elemento('p', 'dato-entrega', `${entrega.nombre_cliente} ${entrega.apellidos_cliente}`.trim());
		const telefono = document.createElement('a');
		telefono.className = 'dato-entrega enlace-telefono';
		if (entrega.telefono) telefono.href = `tel:${entrega.telefono.replace(/[^\d+]/g, '')}`;
		telefono.textContent = entrega.telefono ? `Teléfono: ${entrega.telefono}` : 'Teléfono no registrado';

		const direccion = elemento('p', 'dato-entrega direccion-entrega', entrega.direccion_completa || 'Dirección no disponible');
		if (entrega.referencia) {
			const referencia = elemento('p', 'referencia-entrega', `Referencia: ${entrega.referencia}`);
			tarjeta.append(encabezado, cliente, telefono, direccion, referencia);
		} else {
			tarjeta.append(encabezado, cliente, telefono, direccion);
		}

		const productos = document.createElement('ul');
		productos.className = 'productos-entrega';
		(entrega.detalles || []).forEach((detalle) => {
			const fila = document.createElement('li');
			fila.append(
				elemento('span', '', `${detalle.cantidad} ${detalle.unidad} · ${detalle.producto}`),
				elemento('strong', '', moneda(detalle.subtotal))
			);
			productos.append(fila);
		});
		tarjeta.append(productos);

		const pie = document.createElement('div');
		pie.className = 'pie-entrega';
		const cobro = elemento(
			'div',
			entrega.monto_cobrar > 0 ? 'monto-entrega' : 'monto-entrega pagado',
			entrega.monto_cobrar > 0 ? `Por cobrar: ${moneda(entrega.monto_cobrar)}` : 'Pedido pagado'
		);
		const enlaceMapa = document.createElement('a');
		enlaceMapa.className = 'boton-mapa';
		enlaceMapa.href = MapRoutes.crearEnlace(entrega);
		enlaceMapa.target = '_blank';
		enlaceMapa.rel = 'noopener noreferrer';
		enlaceMapa.append(
			elemento('i', 'bi bi-map', ''),
			document.createTextNode('Abrir mapa')
		);
		pie.append(cobro, enlaceMapa);

		const botonConfirmar = document.createElement('button');
		botonConfirmar.type = 'button';
		botonConfirmar.className = 'boton-confirmar-entrega';
		botonConfirmar.dataset.idPedido = String(entrega.id_pedido);
		botonConfirmar.append(
			elemento('i', 'bi bi-check2-circle', ''),
			document.createTextNode('Confirmar entrega')
		);
		tarjeta.append(pie, botonConfirmar);
		return tarjeta;
	};

	const mostrarEntregas = (entregas) => {
		listaEntregas.replaceChildren();
		if (entregas.length === 0) {
			listaEntregas.append(elemento('p', 'sin-entregas', 'No tienes entregas pendientes'));
			return;
		}
		entregas.forEach((entrega) => listaEntregas.append(crearTarjetaEntrega(entrega)));
	};

	const cargarRuta = async () => {
		if (cargando) return;
		cargando = true;
		botonActualizar.disabled = true;
		try {
			const respuesta = await ApiManager.obtener('/modules/5_logistics/api/get_delivery_route.php');
			mostrarEntregas(respuesta.entregas || []);
			estadoRuta.textContent = `${(respuesta.entregas || []).length} entregas asignadas`;
			alertaRuta.className = 'alerta-ruta d-none';
		} catch (error) {
			estadoRuta.textContent = 'No se pudo actualizar la ruta.';
			alertaRuta.textContent = error.message;
			alertaRuta.className = 'alerta-ruta';
		} finally {
			cargando = false;
			botonActualizar.disabled = false;
		}
	};

	listaEntregas.addEventListener('click', async (evento) => {
		const boton = evento.target.closest('[data-id-pedido]');
		if (!boton || !window.confirm('¿Confirmas que esta entrega se completó?')) return;

		boton.disabled = true;
		try {
			const respuesta = await ApiManager.publicar('/modules/5_logistics/api/confirm_delivery.php', {
				id_pedido: Number(boton.dataset.idPedido),
			});
			alertaRuta.textContent = respuesta.mensaje;
			alertaRuta.className = 'alerta-ruta exito';
			await cargarRuta();
		} catch (error) {
			alertaRuta.textContent = error.message;
			alertaRuta.className = 'alerta-ruta';
			boton.disabled = false;
		}
	});

	botonActualizar.addEventListener('click', cargarRuta);
	cargarRuta();
	window.setInterval(() => {
		if (!document.hidden) cargarRuta();
	}, 30000);
})();

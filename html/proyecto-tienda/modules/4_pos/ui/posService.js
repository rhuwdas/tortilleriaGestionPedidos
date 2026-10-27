/** Servicios de bandeja para la recepción de pedidos web (HU7). */
const PosService = {
	async obtenerPedidosPendientes() {
		return ApiManager.obtener('/modules/4_pos/api/receive_online_orders.php');
	},

	async resolverPedido(idPedido, accion, motivo = '') {
		return ApiManager.publicar('/modules/4_pos/api/manage_online_order.php', {
			id_pedido: idPedido,
			accion,
			motivo,
		});
	},

	async buscarClientes(termino) {
		return ApiManager.obtener('/modules/4_pos/api/search_pos_customers.php', { termino });
	},

	async crearPedido(datosPedido) {
		return ApiManager.publicar('/modules/4_pos/api/create_pos_order.php', datosPedido);
	},

	async obtenerPedidoParaCobro(idPedido) {
		return ApiManager.obtener('/modules/4_pos/api/process_payment.php', { id_pedido: idPedido });
	},

	async cobrarPedido(idPedido, efectivoRecibido) {
		return ApiManager.publicar('/modules/4_pos/api/process_payment.php', {
			id_pedido: idPedido,
			efectivo_recibido: efectivoRecibido,
		});
	},
};

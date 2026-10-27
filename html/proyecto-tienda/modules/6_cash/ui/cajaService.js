/** Cliente de servicios de cobro (HU9). */
const CajaService = {
	obtenerPedido(idPedido) {
		return ApiManager.obtener('/modules/4_pos/api/process_payment.php', { id_pedido: idPedido });
	},

	registrarPago(idPedido, efectivoRecibido) {
		return ApiManager.publicar('/modules/4_pos/api/process_payment.php', {
			id_pedido: idPedido,
			efectivo_recibido: efectivoRecibido,
		});
	},
};
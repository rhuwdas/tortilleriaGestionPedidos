<?php
/** Vista de cobro en efectivo e impresión de ticket (HU9). */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (
	($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
	|| !in_array($_SESSION['rol'] ?? '', ['Vendedor', 'Cajero'], true)
	|| (int)($_SESSION['id_empleado'] ?? 0) <= 0
) {
	http_response_code(403);
	exit('Acceso reservado para personal de ventas.');
}

$idPedido = filter_var($_GET['id_pedido'] ?? null, FILTER_VALIDATE_INT);
$nombreUsuario = htmlspecialchars(
	$_SESSION['nombre_usuario'] ?? $_SESSION['nombre'] ?? 'Personal de ventas',
	ENT_QUOTES,
	'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Caja — Cobro y Venta</title>
	<link
		rel="stylesheet"
		href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
		crossorigin="anonymous"
	>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="/proyecto-tienda/modules/6_cash/ui/caja.css">
	<script src="/proyecto-tienda/core/ApiManager.js" defer></script>
	<script src="/proyecto-tienda/modules/6_cash/ui/cajaService.js" defer></script>
	<script src="/proyecto-tienda/modules/6_cash/ui/cajaUI.js" defer></script>
</head>
<body>
	<header class="barra-caja">
		<div class="container-fluid contenido-caja d-flex align-items-center justify-content-between flex-wrap gap-3">
			<a class="marca-caja" href="/proyecto-tienda/?ruta=/pos">
				<i class="bi bi-arrow-left-circle" aria-hidden="true"></i>
				<span>Regresar a pedidos</span>
			</a>
			<div class="usuario-caja">
				<span><i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= $nombreUsuario ?></span>
				<a href="/proyecto-tienda/?ruta=/logout" class="btn btn-sm btn-outline-light">
					<i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Salir
				</a>
			</div>
		</div>
	</header>

	<main id="caja" class="container-fluid contenido-caja pagina-caja" data-id-pedido="<?= $idPedido ? (int)$idPedido : 0 ?>">
		<div class="encabezado-caja">
			<div>
				<p class="sobrelinea-caja">PUNTO DE VENTA</p>
				<h1>Caja — Cobro y Venta</h1>
			</div>
			<p id="estado-carga-caja" class="text-muted mb-0" aria-live="polite">Cargando pedido…</p>
		</div>

		<div id="alerta-caja" class="alert d-none" role="alert" aria-live="polite"></div>

		<div id="panel-cobro" class="row g-4 d-none">
			<section class="col-lg-7" aria-labelledby="titulo-resumen-cobro">
				<div class="panel-caja">
					<div class="resumen-encabezado">
						<div>
							<p class="sobrelinea-caja mb-1">PEDIDO <span id="numero-pedido-caja"></span></p>
							<h2 id="titulo-resumen-cobro">Resumen de cobro</h2>
						</div>
						<span id="estado-pedido-caja" class="estado-caja"></span>
					</div>
					<div id="datos-cliente-caja" class="datos-cliente-caja"></div>
					<div class="table-responsive">
						<table class="table tabla-cobro">
							<thead><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Importe</th></tr></thead>
							<tbody id="productos-cobro"></tbody>
						</table>
					</div>
					<div class="total-cobro"><span>Total a pagar</span><strong id="total-cobro">$0.00</strong></div>
				</div>
			</section>

			<section class="col-lg-5" aria-labelledby="titulo-pago-efectivo">
				<div class="panel-caja panel-pago">
					<p class="sobrelinea-caja">PAGO EN EFECTIVO</p>
					<h2 id="titulo-pago-efectivo">Efectivo recibido</h2>
					<label for="efectivo-recibido" class="form-label">Monto recibido</label>
					<div class="input-group input-group-lg">
						<span class="input-group-text">$</span>
						<input id="efectivo-recibido" class="form-control" type="number" min="0" step="0.01" inputmode="decimal" autocomplete="off">
					</div>
					<div id="resultado-cambio" class="resultado-cambio" aria-live="polite">
						<span>Cambio</span><strong id="monto-cambio">$0.00</strong>
					</div>
					<p id="aviso-faltante" class="aviso-faltante d-none" role="alert"></p>
					<button id="boton-registrar-cobro" type="button" class="btn btn-success btn-lg w-100 mt-3" disabled>
						<i class="bi bi-receipt me-1" aria-hidden="true"></i>Registrar venta e imprimir ticket
					</button>
				</div>
			</section>
		</div>

		<section id="ticket-impresion" class="ticket-impresion d-none" aria-label="Ticket de venta">
			<div class="ticket-negocio">TORTILLERÍA DON MAÍZ</div>
			<p class="ticket-subtitulo">Comprobante de venta</p>
			<div class="ticket-separador"></div>
			<p>Venta: <strong id="ticket-numero-venta"></strong></p>
			<p>Pedido: <strong id="ticket-numero-pedido"></strong></p>
			<p>Fecha: <span id="ticket-fecha"></span></p>
			<p>Cliente: <span id="ticket-cliente"></span></p>
			<div class="ticket-separador"></div>
			<table><thead><tr><th>Producto</th><th>Cant.</th><th>Importe</th></tr></thead><tbody id="ticket-productos"></tbody></table>
			<div class="ticket-separador"></div>
			<div class="ticket-linea"><span>Total</span><strong id="ticket-total"></strong></div>
			<div class="ticket-linea"><span>Recibido</span><span id="ticket-recibido"></span></div>
			<div class="ticket-linea"><span>Cambio</span><strong id="ticket-cambio"></strong></div>
			<p class="ticket-gracias">Gracias por su compra</p>
			<button id="boton-imprimir-ticket" type="button" class="btn btn-primary no-imprimir">
				<i class="bi bi-printer me-1" aria-hidden="true"></i>Imprimir nuevamente
			</button>
		</section>
	</main>
</body>
</html>
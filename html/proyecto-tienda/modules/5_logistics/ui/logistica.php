<?php
/** Vista móvil de entregas asignadas al repartidor autenticado (HU10). */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (
	empty($_SESSION['id_usuario'])
	|| ($_SESSION['tipo_usuario'] ?? '') !== 'empleado'
	|| ($_SESSION['rol'] ?? '') !== 'Repartidor'
	|| (int)($_SESSION['id_repartidor'] ?? 0) <= 0
) {
	http_response_code(403);
	exit('Acceso exclusivo para repartidores con cuenta vinculada.');
}

$nombreRepartidor = htmlspecialchars(
	$_SESSION['nombre_usuario'] ?? $_SESSION['nombre'] ?? 'Repartidor',
	ENT_QUOTES,
	'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Ruta de Entrega — Tortillería</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="/proyecto-tienda/modules/5_logistics/ui/logistica.css">
	<script src="/proyecto-tienda/core/ApiManager.js" defer></script>
	<script src="/proyecto-tienda/modules/5_logistics/ui/mapRoutes.js" defer></script>
	<script src="/proyecto-tienda/modules/5_logistics/ui/deliveryUI.js" defer></script>
</head>
<body>
	<header class="barra-repartidor">
		<a class="marca-repartidor" href="/proyecto-tienda/?ruta=/logistica">
			<i class="bi bi-bicycle" aria-hidden="true"></i>
			<span>Tortillería Don Maíz</span>
		</a>
		<div class="cuenta-repartidor">
			<span><i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= $nombreRepartidor ?></span>
			<a href="/proyecto-tienda/?ruta=/logout" aria-label="Cerrar sesión" title="Cerrar sesión">
				<i class="bi bi-box-arrow-right" aria-hidden="true"></i>
			</a>
		</div>
	</header>

	<main class="contenido-ruta">
		<section class="encabezado-ruta" aria-labelledby="titulo-ruta">
			<div>
				<p class="sobrelinea-ruta">ENTREGAS ASIGNADAS</p>
				<h1 id="titulo-ruta">Ruta de Entrega</h1>
				<p id="estado-ruta" class="estado-ruta" aria-live="polite">Cargando entregas…</p>
			</div>
			<button id="actualizar-ruta" class="boton-actualizar" type="button" aria-label="Actualizar ruta" title="Actualizar ruta">
				<i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
			</button>
		</section>

		<div id="alerta-ruta" class="alerta-ruta d-none" role="alert" aria-live="polite"></div>
		<section id="lista-entregas" class="lista-entregas" aria-label="Entregas asignadas">
			<p class="cargando-ruta">Cargando entregas…</p>
		</section>
	</main>
</body>
</html>
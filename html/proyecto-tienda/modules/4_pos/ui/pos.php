<?php
/** Vista de recepción de pedidos web para Vendedor y Cajero (HU7). */

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

require_once __DIR__ . '/../../../core/Database.php';
$pdo = Database::obtenerInstancia()->obtenerConexion();
$productosPos = $pdo->query(
    'SELECT id_producto, nombre, precio_kilo, unidad
       FROM producto
      WHERE disponible = 1
      ORDER BY nombre'
)->fetchAll(PDO::FETCH_ASSOC);
$fechaEntregaInicial = new DateTimeImmutable('+1 hour');
if ((int)$fechaEntregaInicial->format('Hi') > 1700) {
    $fechaEntregaInicial = $fechaEntregaInicial->modify('+1 day')->setTime(10, 0);
} elseif ((int)$fechaEntregaInicial->format('Hi') < 700) {
    $fechaEntregaInicial = $fechaEntregaInicial->setTime(10, 0);
}

$nombreUsuario = htmlspecialchars(
    $_SESSION['nombre_usuario'] ?? $_SESSION['nombre'] ?? 'Personal de ventas',
    ENT_QUOTES,
    'UTF-8'
);
$rolUsuario = htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción de Pedidos en Línea — Tortillería</title>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/proyecto-tienda/modules/4_pos/ui/pos.css">
    <script src="/proyecto-tienda/core/ApiManager.js" defer></script>
    <script src="/proyecto-tienda/modules/4_pos/ui/posService.js" defer></script>
    <script src="/proyecto-tienda/modules/4_pos/ui/posUI.js" defer></script>
    <script src="/proyecto-tienda/modules/4_pos/ui/posOrderUI.js" defer></script>
</head>
<body>
    <header class="barra-superior">
        <div class="container-fluid px-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <a class="marca" href="/proyecto-tienda/?ruta=/pos">
                <i class="bi bi-basket2-fill" aria-hidden="true"></i>
                <span>Tortillería Don Maíz</span>
            </a>
            <div class="usuario-activo">
                <span><i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= $nombreUsuario ?> · <?= $rolUsuario ?></span>
                <a href="/proyecto-tienda/?ruta=/logout" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Salir
                </a>
            </div>
        </div>
    </header>

    <main class="container-fluid contenido-bandeja">
        <nav class="selector-vista-pos" aria-label="Operaciones de venta">
            <button type="button" class="boton-vista-pos activo" data-vista-pos="bandeja" aria-pressed="true">
                <i class="bi bi-inboxes" aria-hidden="true"></i> Pedidos pendientes
            </button>
            <button type="button" class="boton-vista-pos" data-vista-pos="captura" aria-pressed="false">
                <i class="bi bi-plus-circle" aria-hidden="true"></i> Nuevo pedido
            </button>
        </nav>

        <section id="seccion-bandeja">
        <section class="encabezado-bandeja" aria-labelledby="titulo-bandeja">
            <div>
                <p class="sobrelinea">PEDIDOS PENDIENTES</p>
                <h1 id="titulo-bandeja">Bandeja de pedidos</h1>
                <p class="texto-actualizacion" id="mensaje-estado" aria-live="polite">Conectando con la bandeja…</p>
            </div>
            <div class="controles-bandeja">
                <span class="contador-pedidos"><span id="contador-pedidos">0</span> pendientes</span>
                <button type="button" class="btn btn-outline-secondary" id="boton-sonido" aria-pressed="false">
                    <i class="bi bi-volume-mute-fill" aria-hidden="true"></i> Activar sonido
                </button>
            </div>
        </section>

        <div id="alerta-pedidos-nuevos" class="alert alert-warning d-none" role="status" aria-live="assertive"></div>
        <div id="mensaje-accion" class="alert d-none" role="status" aria-live="polite"></div>

        <section id="lista-pedidos" class="lista-pedidos" aria-label="Pedidos pendientes">
            <p class="bandeja-cargando">Cargando pedidos…</p>
        </section>
        </section>

        <section id="seccion-nuevo-pedido" class="d-none" aria-labelledby="titulo-nuevo-pedido">
            <div class="encabezado-bandeja">
                <div>
                    <p class="sobrelinea">MOSTRADOR Y TELÉFONO</p>
                    <h1 id="titulo-nuevo-pedido">Nuevo pedido</h1>
                </div>
            </div>

            <div id="alerta-pedido-presencial" class="alert d-none" role="status" aria-live="polite"></div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="panel-pedido-pos" aria-labelledby="titulo-cliente-pos">
                        <h2 id="titulo-cliente-pos">Cliente</h2>
                        <label for="buscar-cliente-pos" class="form-label">Buscar por nombre o teléfono</label>
                        <div class="input-group">
                            <input id="buscar-cliente-pos" class="form-control" type="search" autocomplete="off" placeholder="Escribe al menos 2 caracteres">
                            <button type="button" id="limpiar-cliente-pos" class="btn btn-outline-secondary" aria-label="Cambiar cliente">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="resultados-clientes-pos" class="resultados-clientes d-none" role="listbox" aria-label="Clientes encontrados"></div>
                        <p id="cliente-seleccionado-pos" class="cliente-seleccionado d-none" role="status"></p>
                        <button type="button" id="mostrar-cliente-nuevo" class="btn btn-link px-0">
                            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Registrar cliente nuevo
                        </button>

                        <div id="formulario-cliente-nuevo-pos" class="row g-2 d-none">
                            <div class="col-md-6">
                                <label for="nombre-cliente-pos" class="form-label">Nombre</label>
                                <input id="nombre-cliente-pos" class="form-control" maxlength="150" autocomplete="given-name">
                            </div>
                            <div class="col-md-6">
                                <label for="apellidos-cliente-pos" class="form-label">Apellidos</label>
                                <input id="apellidos-cliente-pos" class="form-control" maxlength="150" autocomplete="family-name">
                            </div>
                            <div class="col-md-6">
                                <label for="telefono-cliente-pos" class="form-label">Teléfono</label>
                                <input id="telefono-cliente-pos" class="form-control" maxlength="20" autocomplete="tel" inputmode="tel">
                            </div>
                            <div class="col-12">
                                <button type="button" id="usar-cliente-nuevo" class="btn btn-outline-primary">
                                    Usar este cliente
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="panel-pedido-pos" aria-labelledby="titulo-productos-pos">
                        <h2 id="titulo-productos-pos">Productos</h2>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-7">
                                <label for="producto-pos" class="form-label">Producto</label>
                                <select id="producto-pos" class="form-select">
                                    <option value="">Selecciona producto</option>
                                    <?php foreach ($productosPos as $producto): ?>
                                        <option
                                            value="<?= (int)$producto['id_producto'] ?>"
                                            data-precio="<?= htmlspecialchars((string)$producto['precio_kilo'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-unidad="<?= htmlspecialchars($producto['unidad'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?> · $<?= number_format((float)$producto['precio_kilo'], 2) ?> / <?= htmlspecialchars($producto['unidad'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 col-6">
                                <label for="cantidad-pos" class="form-label">Cantidad</label>
                                <input id="cantidad-pos" class="form-control" type="number" min="0.5" step="0.5" value="1">
                            </div>
                            <div class="col-md-2 col-6">
                                <button type="button" id="agregar-producto-pos" class="btn btn-primary w-100" aria-label="Agregar producto">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="panel-pedido-pos resumen-pos" aria-labelledby="titulo-ticket-pos">
                        <h2 id="titulo-ticket-pos">Ticket en curso</h2>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="canal-pedido-pos" class="form-label">Origen</label>
                                <select id="canal-pedido-pos" class="form-select">
                                    <option value="Mostrador">Mostrador</option>
                                    <option value="Teléfono">Teléfono</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="entrega-pedido-pos" class="form-label">Entrega</label>
                                <select id="entrega-pedido-pos" class="form-select">
                                    <option value="Recoger en local">Recoger en local</option>
                                    <option value="Domicilio">Domicilio</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="fecha-entrega-pos" class="form-label">Fecha y hora solicitada</label>
                                <input id="fecha-entrega-pos" class="form-control" type="datetime-local" required value="<?= $fechaEntregaInicial->format('Y-m-d\\TH:i') ?>">
                            </div>
                            <div id="direccion-entrega-pos" class="col-12 row g-2 d-none">
                                <div class="col-12">
                                    <label for="calle-entrega-pos" class="form-label">Calle y número</label>
                                    <input id="calle-entrega-pos" class="form-control" maxlength="150" autocomplete="street-address">
                                </div>
                                <div class="col-md-6">
                                    <label for="colonia-entrega-pos" class="form-label">Colonia</label>
                                    <input id="colonia-entrega-pos" class="form-control" maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <label for="municipio-entrega-pos" class="form-label">Municipio</label>
                                    <input id="municipio-entrega-pos" class="form-control" maxlength="100">
                                </div>
                                <div class="col-12">
                                    <label for="referencia-entrega-pos" class="form-label">Referencias</label>
                                    <input id="referencia-entrega-pos" class="form-control" maxlength="255">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm tabla-ticket-pos">
                                <thead>
                                    <tr><th>Producto</th><th class="text-end">Subtotal</th><th><span class="visually-hidden">Quitar</span></th></tr>
                                </thead>
                                <tbody id="detalle-ticket-pos">
                                    <tr id="ticket-vacio-pos"><td colspan="3" class="text-center text-muted py-3">Agrega productos al ticket.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="total-ticket-pos"><span>Total</span><strong id="total-ticket-pos">$0.00</strong></div>
                        <button type="button" id="registrar-pedido-pos" class="btn btn-success w-100 mt-3" disabled>
                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Confirmar y registrar pedido
                        </button>
                    </section>
                </div>
            </div>
        </section>
    </main>

    <dialog id="dialogo-rechazo" class="dialogo-rechazo" aria-labelledby="titulo-rechazo">
        <form id="formulario-rechazo">
            <h2 id="titulo-rechazo">Rechazar pedido</h2>
            <label for="motivo-rechazo" class="form-label">Motivo del rechazo</label>
            <textarea id="motivo-rechazo" class="form-control" rows="4" maxlength="255" required></textarea>
            <div class="acciones-dialogo">
                <button type="button" class="btn btn-outline-secondary" id="cerrar-dialogo-rechazo">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar rechazo</button>
            </div>
        </form>
    </dialog>
</body>
</html>
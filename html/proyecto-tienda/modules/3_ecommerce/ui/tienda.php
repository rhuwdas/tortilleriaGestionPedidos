<?php
/**
 * modules/3_ecommerce/ui/tienda.php
 *
 * Vista principal de Realizar Pedido en Línea (HU5).
 * Permite a los clientes:
 *  - Seleccionar productos del catálogo disponible y especificar cantidades en Kilos.
 *  - Gestionar el carrito de compras (agregar, modificar cantidades, eliminar artículos).
 *  - Seleccionar el tipo de entrega: "A domicilio" o "Recoger en local" (CA-001).
 *  - Definir fecha y hora deseada de entrega.
 *  - Visualizar en tiempo real subtotales, totales y forma de pago.
 *  - Acceder al resumen detallado antes de confirmar la compra (CA-002).
 *
 * Restricciones (AGENTS.md):
 *  - Sin JavaScript inline: lógica en checkout.js con atributo defer.
 *  - Bootstrap 5 + CSS modular ecommerce.css.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirigir al login si no hay sesión activa
if (empty($_SESSION['id_usuario']) && empty($_SESSION['id_cliente'])) {
    header('Location: /proyecto-tienda/?ruta=/login');
    exit;
}

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../1_auth/api/ClienteModel.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$idCliente = $_SESSION['id_cliente'] ?? $_SESSION['id_usuario'];
$nombreUsuario = htmlspecialchars($_SESSION['nombre_usuario'] ?? $_SESSION['nombre'] ?? 'Cliente', ENT_QUOTES, 'UTF-8');

// Obtener catálogo de productos disponibles desde la vista oficial
$sentenciaProductos = $pdo->query('SELECT id_producto, producto AS nombre, categoria, precio_kilo, unidad FROM vista_catalogo_web ORDER BY categoria, producto');
$productosDisponibles = $sentenciaProductos ? $sentenciaProductos->fetchAll(PDO::FETCH_ASSOC) : [];

// Obtener direcciones guardadas del cliente si existen
$modeloCliente = new ClienteModel();
$perfilCliente = $modeloCliente->obtenerPerfilCliente((int)$idCliente);
$direccionesGuardadas = $perfilCliente['direcciones'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Pedido en Línea — Tortillería</title>

    <!-- Bootstrap 5 CSS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Estilos específicos del e-commerce y pedidos -->
    <link rel="stylesheet" href="/proyecto-tienda/modules/3_ecommerce/ui/ecommerce.css">
</head>
<body>

    <!-- ══════════════════════════════════════════════════════════════════════════
         CABECERA / NAVBAR
         ══════════════════════════════════════════════════════════════════════════ -->
    <header class="navbar-ecommerce">
        <div class="container-fluid px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="marca-ecommerce">
                <i class="bi bi-cart3"></i>
                <span>Tortillería Don Maíz — Pedidos Web</span>
            </div>

            <nav class="d-flex align-items-center gap-3">
                <a href="/proyecto-tienda/?ruta=/catalogo" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-grid me-1"></i>Ver Catálogo
                </a>
                <a href="/proyecto-tienda/?ruta=/ecommerce#mis-pedidos" id="enlace-mis-pedidos" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-clock-history me-1"></i>Mis Pedidos
                </a>
                <span class="text-light small d-none d-md-inline">
                    <i class="bi bi-person-circle me-1"></i><?= $nombreUsuario ?>
                </span>
                <a href="/proyecto-tienda/?ruta=/logout" class="btn btn-sm btn-light text-primary fw-semibold">
                    <i class="bi bi-box-arrow-right me-1"></i>Salir
                </a>
            </nav>
        </div>
    </header>

    <!-- ══════════════════════════════════════════════════════════════════════════
         CONTENIDO PRINCIPAL
         ══════════════════════════════════════════════════════════════════════════ -->
    <main class="container-fluid px-4 py-4">

        <!-- Banner de alerta de error -->
        <div id="alerta-error-pedido" class="alert alert-danger alerta-pedido d-none" role="alert" aria-live="polite">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span id="mensaje-error-pedido"></span>
        </div>

        <!-- Banner de alerta de éxito -->
        <div id="alerta-exito-pedido" class="alert alert-success alerta-pedido d-none" role="alert" aria-live="polite">
            <i class="bi bi-check-circle-fill me-2"></i>
            <span id="mensaje-exito-pedido"></span>
        </div>

        <div class="row g-4">

            <!-- ── Columna Izquierda: Selección de Productos y Carrito ─────── -->
            <div class="col-lg-7">

                <!-- Panel: Selección de Producto -->
                <div class="tarjeta-panel">
                    <h2 class="titulo-panel">
                        <i class="bi bi-plus-circle"></i> Agregar Productos al Pedido
                    </h2>

                    <form id="formulario-agregar-item" class="row g-3 align-items-end" novalidate>
                        <div class="col-md-6">
                            <label for="selector-producto" class="form-label fw-semibold">Producto</label>
                            <select id="selector-producto" class="form-select" required aria-required="true">
                                <option value="" disabled selected>Selecciona un producto...</option>
                                <?php foreach ($productosDisponibles as $producto): ?>
                                    <option
                                        value="<?= (int)$producto['id_producto'] ?>"
                                        data-precio="<?= htmlspecialchars($producto['precio_kilo'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-nombre="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-unidad="<?= htmlspecialchars($producto['unidad'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?> — $<?= number_format((float)$producto['precio_kilo'], 2) ?> / <?= htmlspecialchars($producto['unidad'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="campo-cantidad" class="form-label fw-semibold">Cantidad (Kilos)</label>
                            <input
                                type="number"
                                id="campo-cantidad"
                                class="form-control"
                                min="0.5"
                                max="100"
                                step="0.5"
                                value="1"
                                required
                                aria-required="true"
                            >
                        </div>

                        <div class="col-md-3">
                            <button type="submit" id="boton-agregar-carrito" class="btn btn-primary w-100" style="background-color: #1a56a0; border-color: #1a56a0;">
                                <i class="bi bi-cart-plus me-1"></i>Agregar
                            </button>
                        </div>
                    </form>

                    <!-- Detalle de precio unitario dinámico -->
                    <div id="info-precio-producto" class="mt-2 text-muted small d-none">
                        Precio unitario: <span id="etiqueta-precio-unitario" class="precio-unitario-insignia">$0.00</span>
                    </div>
                </div>

                <!-- Panel: Carrito de Compras -->
                <div class="tarjeta-panel">
                    <h2 class="titulo-panel">
                        <i class="bi bi-cart-check"></i> Carrito de Compras
                    </h2>

                    <div class="table-responsive">
                        <table class="table table-hover tabla-carrito">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center" style="width: 120px;">Cantidad</th>
                                    <th class="text-end" style="width: 120px;">Precio Unit.</th>
                                    <th class="text-end" style="width: 120px;">Subtotal</th>
                                    <th class="text-center" style="width: 60px;">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-tabla-carrito">
                                <tr id="fila-carrito-vacio">
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-basket me-2"></i>El carrito está vacío. Agrega productos arriba para comenzar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- ── Columna Derecha: Configuración de Entrega y Resumen ──────── -->
            <div class="col-lg-5">

                <!-- Panel: Selector de Tipo de Entrega (CA-001) -->
                <div class="tarjeta-panel">
                    <h2 class="titulo-panel">
                        <i class="bi bi-truck"></i> Tipo de Entrega
                    </h2>

                    <div class="contenedor-tipo-entrega">
                        <!-- Opción: A domicilio -->
                        <div
                            id="opcion-domicilio"
                            class="opcion-entrega seleccionada"
                            data-tipo="Domicilio"
                            role="button"
                            tabindex="0"
                            aria-label="Entrega a domicilio"
                        >
                            <i class="bi bi-house-door-fill icono-tipo-entrega"></i>
                            <div class="texto-tipo-entrega">
                                <h6>A Domicilio</h6>
                                <small>Te lo llevamos a tu ubicación</small>
                            </div>
                        </div>

                        <!-- Opción: Recoger en local -->
                        <div
                            id="opcion-local"
                            class="opcion-entrega"
                            data-tipo="Recoger en local"
                            role="button"
                            tabindex="0"
                            aria-label="Recoger en local"
                        >
                            <i class="bi bi-shop icono-tipo-entrega"></i>
                            <div class="texto-tipo-entrega">
                                <h6>Recoger en Local</h6>
                                <small>Pasa por tu pedido a la sucursal</small>
                            </div>
                        </div>
                    </div>

                    <!-- Sección dinámica: Dirección de entrega (para A domicilio) -->
                    <div id="bloque-direccion-entrega">
                        <label class="form-label fw-semibold">Dirección de Entrega</label>

                        <?php if (!empty($direccionesGuardadas)): ?>
                            <select id="selector-direccion-guardada" class="form-select mb-2">
                                <?php foreach ($direccionesGuardadas as $dir): ?>
                                    <option value="<?= (int)$dir['id_direccion'] ?>" data-calle="<?= htmlspecialchars($dir['calle'], ENT_QUOTES, 'UTF-8') ?>" data-municipio="<?= htmlspecialchars($dir['municipio'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($dir['calle'] . ', ' . $dir['colonia'] . ', ' . $dir['municipio'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="nueva">+ Capturar nueva dirección...</option>
                            </select>
                        <?php endif; ?>

                        <!-- Formulario para ingresar o editar dirección -->
                        <div id="formulario-nueva-direccion" class="<?= !empty($direccionesGuardadas) ? 'd-none' : '' ?> bg-light p-3 rounded border">
                            <div class="mb-2">
                                <input type="text" id="campo-direccion-calle" class="form-control form-control-sm" placeholder="Calle y número *" required>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <input type="text" id="campo-direccion-colonia" class="form-control form-control-sm" placeholder="Colonia">
                                </div>
                                <div class="col-6">
                                    <input type="text" id="campo-direccion-municipio" class="form-control form-control-sm" placeholder="Municipio *" value="Oaxaca" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <input type="text" id="campo-direccion-referencia" class="form-control form-control-sm" placeholder="Referencias de entrega">
                            </div>
                        </div>
                    </div>

                    <!-- Selector de fecha y hora deseada -->
                    <div class="mt-3">
                        <label for="campo-fecha-entrega" class="form-label fw-semibold">Fecha y Hora Deseada</label>
                        <input
                            type="datetime-local"
                            id="campo-fecha-entrega"
                            class="form-control"
                            value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>"
                            min="<?= date('Y-m-d\TH:i') ?>"
                        >
                        <small class="text-muted">Horario de entrega: Lunes a Domingo de 7:00 a 17:00 hrs.</small>
                    </div>

                    <!-- Forma de pago estática (regla de negocio: efectivo) -->
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Método de Pago</label>
                        <div class="form-control bg-light text-muted">
                            <i class="bi bi-cash-stack me-2 text-success"></i>Efectivo (al recibir o en mostrador)
                        </div>
                    </div>
                </div>

                <!-- Panel: Resumen Dinámico y Confirmación -->
                <div class="tarjeta-panel">
                    <h2 class="titulo-panel">
                        <i class="bi bi-receipt"></i> Resumen de Compra
                    </h2>

                    <div class="resumen-totales mb-3">
                        <div class="fila-resumen">
                            <span>Artículos en carrito:</span>
                            <span id="resumen-cantidad-articulos" class="fw-semibold">0</span>
                        </div>
                        <div class="fila-resumen">
                            <span>Subtotal:</span>
                            <span id="resumen-subtotal" class="fw-semibold">$0.00</span>
                        </div>
                        <div class="fila-resumen">
                            <span>Costo de envío:</span>
                            <span id="resumen-envio" class="fw-semibold text-success">Gratis</span>
                        </div>
                        <div class="fila-total">
                            <span>Total a pagar:</span>
                            <span id="resumen-total">$0.00</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        id="boton-revisar-pedido"
                        class="btn-accion-pedido"
                        disabled
                    >
                        <i class="bi bi-bag-check me-2"></i>Confirmar Pedido
                    </button>
                </div>

            </div>

        </div>

    </main>

    <!-- ══════════════════════════════════════════════════════════════════════════
         MODAL DE RESUMEN Y CONFIRMACIÓN DETALLADA (CA-002)
         ══════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modal-confirmacion-pedido" tabindex="-1" aria-labelledby="tituloModalConfirmacion" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #1a56a0; color: #ffffff;">
                    <h5 class="modal-title" id="tituloModalConfirmacion">
                        <i class="bi bi-shield-check me-2"></i>Confirmar Detalle de Pedido
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Por favor, revisa detalladamente tu pedido antes de confirmar la compra:</p>

                    <h6 class="fw-bold mb-2">Productos seleccionados:</h6>
                    <ul id="lista-resumen-modal" class="list-group list-group-flush mb-3 border rounded">
                        <!-- Rellenado por JS -->
                    </ul>

                    <div class="bg-light p-3 rounded mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><strong>Tipo de entrega:</strong></span>
                            <span id="modal-tipo-entrega">A domicilio</span>
                        </div>
                        <div id="modal-fila-direccion" class="d-flex justify-content-between small mb-1">
                            <span><strong>Destino:</strong></span>
                            <span id="modal-direccion" class="text-end">Av. Juárez #123</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span><strong>Fecha/Hora solicitada:</strong></span>
                            <span id="modal-fecha-entrega">Hoy 12:00</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span><strong>Forma de pago:</strong></span>
                            <span>Efectivo</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background-color: #eff6ff;">
                        <span class="fs-6 fw-bold text-primary">Total a pagar:</span>
                        <span id="modal-total-pagar" class="fs-4 fw-bold text-primary">$0.00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Modificar</button>
                    <button type="button" id="boton-confirmar-final" class="btn btn-primary" style="background-color: #1a56a0;">
                        <span id="texto-confirmar-final">Confirmar y Enviar Pedido</span>
                        <span id="spinner-confirmar-final" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"
        defer
    ></script>

    <!-- Script de checkout y gestión de carrito (desacoplado) -->
    <script src="/proyecto-tienda/modules/3_ecommerce/ui/checkout.js" defer></script>

</body>
</html>

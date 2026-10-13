<?php
/**
 * modules/2_catalog/ui/catalogo.php
 *
 * Vista principal del Catálogo Web (HU3 — tarea 3.1).
 * Renderiza la cabecera, barra de búsqueda, controles de filtro,
 * grid de tarjetas de productos y la sección de información del negocio.
 *
 * Esta vista es servida por Router.php en la ruta protegida /catalogo.
 * El contenido dinámico (tarjetas de producto) es generado por catalog.js
 * al consumir el endpoint get_catalog.php.
 *
 * Restricciones (AGENTS.md):
 *  - Sin JavaScript en línea.
 *  - Scripts cargados con defer al final del body.
 *  - Bootstrap 5 vía CDN.
 */

// Iniciar sesión si aún no está activa (el Router ya la gestiona, pero por seguridad)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nombre del usuario en sesión para el saludo en cabecera
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? 'Cliente', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo — Tortillería</title>

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

    <!-- Estilos del catálogo (HU3 — tarea 3.1) -->
    <link rel="stylesheet" href="/proyecto-tienda/modules/2_catalog/ui/catalogo.css">
</head>
<body>

<!-- ══════════════════════════════════════════════════════════════════════════
     CABECERA / NAVBAR
     ══════════════════════════════════════════════════════════════════════════ -->
<header class="navbar-catalogo">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">

        <!-- Logotipo / nombre del negocio -->
        <div class="navbar-marca d-flex align-items-center gap-2">
            <span class="navbar-icono"><i class="bi bi-basket2-fill"></i></span>
            <span class="navbar-titulo">Tortillería Don Maíz</span>
        </div>

        <!-- Barra de búsqueda (tarea 3.3) -->
        <div class="barra-busqueda-wrapper flex-grow-1">
            <div class="input-group input-group-sm barra-busqueda">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input
                    type="search"
                    id="campoBusqueda"
                    class="form-control border-start-0"
                    placeholder="Buscar producto…"
                    aria-label="Buscar producto"
                    autocomplete="off"
                >
            </div>
        </div>

        <!-- Saludo + logout -->
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-saludo">
                <i class="bi bi-person-circle me-1"></i><?= $nombreUsuario ?>
            </span>
            <a href="/proyecto-tienda/?ruta=/logout" class="btn btn-sm btn-outline-claro">
                <i class="bi bi-box-arrow-right me-1"></i>Salir
            </a>
        </div>
    </div>
</header>

<!-- ══════════════════════════════════════════════════════════════════════════
     BARRA DE FILTROS (tarea 3.3)
     ══════════════════════════════════════════════════════════════════════════ -->
<section class="barra-filtros" aria-label="Filtros del catálogo">
    <div class="container-fluid px-4 d-flex align-items-center flex-wrap gap-3">

        <!-- Selector de categoría -->
        <div class="d-flex align-items-center gap-2">
            <label for="selectCategoria" class="filtro-etiqueta mb-0">
                <i class="bi bi-tag me-1"></i>Categoría
            </label>
            <select id="selectCategoria" class="form-select form-select-sm filtro-select" aria-label="Filtrar por categoría">
                <option value="">Todas</option>
                <!-- Poblado dinámicamente por catalogUI.js -->
            </select>
        </div>

        <!-- Toggle solo disponibles -->
        <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
            <input
                class="form-check-input filtro-switch"
                type="checkbox"
                role="switch"
                id="switchDisponible"
                aria-label="Mostrar solo productos disponibles"
            >
            <label class="form-check-label filtro-etiqueta mb-0" for="switchDisponible">
                Solo disponibles
            </label>
        </div>

        <!-- Contador de resultados -->
        <span id="contadorResultados" class="filtro-contador ms-auto" aria-live="polite"></span>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════════════════
     NAVEGACIÓN DE SECCIONES
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="nav-secciones" aria-label="Secciones del catálogo">
    <div class="container-fluid px-4">
        <ul class="nav nav-tabs nav-tabs-catalogo" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link active"
                    id="tab-productos"
                    data-bs-toggle="tab"
                    data-bs-target="#seccionProductos"
                    type="button"
                    role="tab"
                    aria-controls="seccionProductos"
                    aria-selected="true"
                >
                    <i class="bi bi-grid me-1"></i>Productos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link"
                    id="tab-info"
                    data-bs-toggle="tab"
                    data-bs-target="#seccionInfo"
                    type="button"
                    role="tab"
                    aria-controls="seccionInfo"
                    aria-selected="false"
                >
                    <i class="bi bi-info-circle me-1"></i>Información del negocio
                </button>
            </li>
        </ul>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════════════════════
     CONTENIDO PRINCIPAL
     ══════════════════════════════════════════════════════════════════════════ -->
<main class="contenido-principal">
    <div class="container-fluid px-4">
        <div class="tab-content">

            <!-- ── Sección: Productos ──────────────────────────────────────── -->
            <section
                class="tab-pane fade show active"
                id="seccionProductos"
                role="tabpanel"
                aria-labelledby="tab-productos"
            >
                <!-- Alerta de agotado / sin resultados (tarea 3.5) — oculta por defecto -->
                <div
                    id="alertaAgotado"
                    class="alerta-agotado d-none"
                    role="alert"
                    aria-live="assertive"
                >
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span id="alertaAgotadoMensaje">No hay productos disponibles con los filtros seleccionados.</span>
                </div>

                <!-- Estado de carga -->
                <div id="estadoCarga" class="estado-carga" aria-label="Cargando productos" aria-live="polite">
                    <div class="spinner-border text-terracota" role="status">
                        <span class="visually-hidden">Cargando…</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando productos…</p>
                </div>

                <!-- Grid de tarjetas (tarea 3.1 / 3.4) — poblado por catalog.js -->
                <div
                    id="gridProductos"
                    class="grid-productos d-none"
                    role="list"
                    aria-label="Catálogo de productos"
                >
                    <!-- Las tarjetas son insertadas dinámicamente por catalog.js -->
                </div>
            </section>

            <!-- ── Sección: Información del negocio ────────────────────────── -->
            <section
                class="tab-pane fade"
                id="seccionInfo"
                role="tabpanel"
                aria-labelledby="tab-info"
            >
                <div class="info-negocio">
                    <h2 class="info-titulo">
                        <i class="bi bi-shop me-2"></i>Acerca del Negocio
                    </h2>

                    <div class="row g-4 mt-1">

                        <!-- Horario y teléfono -->
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-card-icono">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div>
                                    <h3 class="info-card-titulo">Horario de atención</h3>
                                    <p class="mb-1">Lunes a sábado: <strong>6:00 a.m. – 2:00 p.m.</strong></p>
                                    <p class="mb-0">Domingo: <strong>6:00 a.m. – 12:00 p.m.</strong></p>
                                </div>
                            </div>
                            <div class="info-card mt-3">
                                <div class="info-card-icono">
                                    <i class="bi bi-telephone"></i>
                                </div>
                                <div>
                                    <h3 class="info-card-titulo">Teléfono de contacto</h3>
                                    <p class="mb-0">
                                        <a href="tel:+529991234567" class="info-enlace">+52 999 123 4567</a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Dirección y mapa -->
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-card-icono">
                                    <i class="bi bi-geo-alt"></i>
                                </div>
                                <div>
                                    <h3 class="info-card-titulo">Dirección</h3>
                                    <p class="mb-0">
                                        Calle Maíz #42, Col. Centro,<br>
                                        Mérida, Yucatán, México, C.P. 97000
                                    </p>
                                </div>
                            </div>
                            <!-- Marcador de posición del mapa -->
                            <div class="mapa-placeholder mt-3" aria-label="Mapa de ubicación del negocio" role="img">
                                <i class="bi bi-map"></i>
                                <span>Mapa de ubicación</span>
                            </div>
                        </div>

                    </div><!-- /.row -->
                </div><!-- /.info-negocio -->
            </section>

        </div><!-- /.tab-content -->
    </div><!-- /.container-fluid -->
</main>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL DE DETALLE DE PRODUCTO (tarea 3.4)
     ══════════════════════════════════════════════════════════════════════════ -->
<div
    class="modal fade"
    id="modalDetalle"
    tabindex="-1"
    aria-labelledby="modalDetalleTitulo"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-detalle">

            <!-- Encabezado -->
            <div class="modal-header modal-detalle-header">
                <h2 class="modal-title fs-5" id="modalDetalleTitulo">Detalle del producto</h2>
                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar detalle del producto"
                ></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body modal-detalle-body">
                <div class="row g-4">

                    <!-- Imagen -->
                    <div class="col-md-5 d-flex align-items-center justify-content-center">
                        <img
                            id="modalDetalleImagen"
                            src=""
                            alt=""
                            class="modal-imagen"
                        >
                    </div>

                    <!-- Información -->
                    <div class="col-md-7">
                        <span id="modalDetalleCategoria" class="badge-categoria mb-2 d-inline-block"></span>
                        <h3 id="modalDetalleNombre" class="modal-nombre"></h3>
                        <p id="modalDetalleDescripcion" class="modal-descripcion text-muted"></p>
                        <div class="modal-precio-wrapper">
                            <span class="modal-precio-etiqueta">Precio:</span>
                            <span id="modalDetallePrecio" class="modal-precio"></span>
                        </div>
                        <!-- Estado de disponibilidad (tarea 3.5) -->
                        <div id="modalDetalleDisponibilidad" class="modal-disponibilidad mt-3"></div>
                    </div>

                </div><!-- /.row -->
            </div><!-- /.modal-body -->

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     SCRIPTS (defer — sin inline JS, AGENTS.md)
     ══════════════════════════════════════════════════════════════════════════ -->

<!-- Bootstrap 5 Bundle (Popper incluido) -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"
    defer
></script>

<!-- Cliente HTTP centralizado -->
<script src="/proyecto-tienda/core/ApiManager.js" defer></script>

<!-- Lógica principal del catálogo (renderizado de tarjetas, modal, alerta) -->
<script src="/proyecto-tienda/modules/2_catalog/ui/catalog.js" defer></script>

<!-- Lógica de filtros y búsqueda en tiempo real -->
<script src="/proyecto-tienda/modules/2_catalog/ui/catalogUI.js" defer></script>

</body>
</html>

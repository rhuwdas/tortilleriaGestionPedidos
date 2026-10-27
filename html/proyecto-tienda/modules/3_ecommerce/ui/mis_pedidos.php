<?php
/** Pantalla independiente de seguimiento e historial de pedidos (HU6). */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id_usuario']) || empty($_SESSION['id_cliente'])) {
    header('Location: /proyecto-tienda/?ruta=/login');
    exit;
}

$nombreUsuario = htmlspecialchars(
    $_SESSION['nombre_usuario'] ?? $_SESSION['nombre'] ?? 'Cliente',
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos — Tortillería</title>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/proyecto-tienda/modules/3_ecommerce/ui/ecommerce.css">
    <script src="/proyecto-tienda/core/ApiManager.js" defer></script>
    <script src="/proyecto-tienda/modules/3_ecommerce/ui/trackingUI.js" defer></script>
</head>
<body>
    <header class="navbar-ecommerce">
        <div class="container-fluid px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <a href="/proyecto-tienda/?ruta=/catalogo" class="marca-ecommerce text-decoration-none">
                <i class="bi bi-basket2-fill" aria-hidden="true"></i>
                <span>Tortillería Don Maíz</span>
            </a>
            <nav class="d-flex align-items-center gap-3 flex-wrap" aria-label="Navegación de cliente">
                <a href="/proyecto-tienda/?ruta=/ecommerce" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-cart3 me-1" aria-hidden="true"></i>Comprar
                </a>
                <span class="text-light small d-none d-md-inline">
                    <i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= $nombreUsuario ?>
                </span>
                <a href="/proyecto-tienda/?ruta=/logout" class="btn btn-sm btn-light text-primary fw-semibold">
                    <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Salir
                </a>
            </nav>
        </div>
    </header>

    <main class="container-fluid px-4 py-4">
        <section aria-labelledby="titulo-mis-pedidos">
            <div class="tarjeta-panel">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h1 id="titulo-mis-pedidos" class="titulo-panel mb-0">
                        <i class="bi bi-clock-history" aria-hidden="true"></i> Seguimiento de mis Pedidos
                    </h1>
                    <button type="button" id="boton-actualizar-pedidos" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Actualizar
                    </button>
                </div>

                <div id="alerta-pedidos" class="alert alerta-pedido d-none" role="alert" aria-live="polite"></div>
                <div id="contenedor-mis-pedidos" aria-live="polite">
                    <div class="text-center py-4 text-muted">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Cargando tus pedidos...
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
<?php
/**
 * modules/2_catalog/api/get_catalog.php
 *
 * Endpoint GET del catálogo de productos (HU3 — tarea 3.2).
 * Devuelve un JSON con los productos y las categorías disponibles.
 *
 * Parámetros GET opcionales:
 *  - categoria      (string) Filtra por categoría.
 *  - soloDisponible (1|0)   Filtra solo productos con disponible = 1.
 *  - busqueda       (string) Texto libre para buscar en nombre/descripción.
 *
 * Respuesta exitosa (200):
 *  {
 *    "exito": true,
 *    "productos": [ { id, nombre, descripcion, precio, categoria, imagen_url, disponible }, … ],
 *    "categorias": [ "Tortillas", "Sopes", … ]
 *  }
 *
 * Respuesta de error (500):
 *  { "exito": false, "mensaje": "Error interno del servidor." }
 *
 * Restricciones (AGENTS.md):
 *  - Solo se acepta GET.
 *  - Solo solicitudes AJAX (X-Requested-With: XMLHttpRequest).
 *  - Sin concatenación de variables en SQL.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Inicialización: sesión y núcleo del sistema
// ─────────────────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/ProductModel.php';

// ─────────────────────────────────────────────────────────────────────────────
// Configuración de la respuesta HTTP
// ─────────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// ─────────────────────────────────────────────────────────────────────────────
// Función auxiliar: enviar JSON y detener ejecución
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Codifica y envía una respuesta JSON al cliente, luego detiene la ejecución.
 *
 * @param bool   $exito   true si la operación fue exitosa.
 * @param array  $extras  Datos adicionales a incluir en la respuesta.
 * @param string $mensaje Mensaje descriptivo (en caso de error).
 * @return never
 */
function responderJson(bool $exito, array $extras = [], string $mensaje = ''): never
{
    $cuerpo = array_merge(['exito' => $exito, 'mensaje' => $mensaje], $extras);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Validación del método HTTP y tipo de solicitud
// ─────────────────────────────────────────────────────────────────────────────

// Solo aceptar solicitudes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    responderJson(false, [], 'Método no permitido.');
}

// Verificar que sea una solicitud AJAX
$esAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
if (!$esAjax) {
    http_response_code(403);
    responderJson(false, [], 'Acceso no permitido.');
}

// ─────────────────────────────────────────────────────────────────────────────
// Lectura y saneamiento de los parámetros GET
// ─────────────────────────────────────────────────────────────────────────────

$filtros = [];

// Filtro de categoría — cadena de texto
if (!empty($_GET['categoria'])) {
    $filtros['categoria'] = trim($_GET['categoria']);
}

// Filtro de disponibilidad — solo aceptar '1' como verdadero
if (isset($_GET['soloDisponible']) && $_GET['soloDisponible'] === '1') {
    $filtros['soloDisponible'] = true;
}

// Filtro de búsqueda libre — cadena de texto
if (!empty($_GET['busqueda'])) {
    $filtros['busqueda'] = trim($_GET['busqueda']);
}

// ─────────────────────────────────────────────────────────────────────────────
// Consulta y respuesta
// ─────────────────────────────────────────────────────────────────────────────

try {
    $modelo     = new ProductModel();
    $productos  = $modelo->obtenerTodos($filtros);
    $categorias = $modelo->obtenerCategorias();

    http_response_code(200);
    responderJson(true, [
        'productos'  => $productos,
        'categorias' => $categorias,
    ]);

} catch (RuntimeException $e) {
    // Error de conexión a la base de datos
    error_log('get_catalog.php — Error de BD: ' . $e->getMessage());
    http_response_code(500);
    responderJson(false, [], 'Error interno del servidor. Intenta más tarde.');

} catch (PDOException $e) {
    // Error inesperado de PDO
    error_log('get_catalog.php — PDOException: ' . $e->getMessage());
    http_response_code(500);
    responderJson(false, [], 'Error interno del servidor. Intenta más tarde.');
}

<?php
/**
 * core/Router.php
 *
 * Enrutador central del sistema.
 * Mapea las rutas (URL relativas) a los controladores/vistas correspondientes
 * dentro de los módulos. Redirige al login a cualquier ruta no autenticada
 * que no esté en la lista de rutas públicas.
 *
 * Uso típico en index.php:
 *   require_once __DIR__ . '/core/Router.php';
 *   $enrutador = new Router();
 *   $enrutador->despachar();
 *
 * Restricciones (AGENTS.md):
 *  - Sin frameworks ni Composer.
 *  - Patrón MVC estricto.
 */

class Router
{
    /**
     * @var array<string, string> Mapa de rutas públicas (no requieren sesión activa).
     *                             Formato: 'ruta_url' => 'ruta_archivo_php'
     */
    private array $rutasPublicas = [
        '/'          => 'modules/1_auth/ui/login.php',
        '/login'     => 'modules/1_auth/ui/login.php',
        '/recuperar' => 'modules/1_auth/ui/recuperar.php',
        '/registro'  => 'modules/1_auth/ui/registro.php',
    ];

    /**
     * @var array<string, string> Mapa de rutas protegidas (requieren sesión activa).
     *                             Formato: 'ruta_url' => 'ruta_archivo_php'
     */
    private array $rutasProtegidas = [
        '/dashboard'  => 'modules/dashboard/ui/dashboard.php',
        '/logout'     => 'modules/1_auth/api/logout.php',
        '/catalogo'   => 'modules/2_catalog/ui/catalogo.php',
        '/ecommerce'  => 'modules/3_ecommerce/ui/tienda.php',
        '/mis-pedidos' => 'modules/3_ecommerce/ui/mis_pedidos.php',
        '/pos'        => 'modules/4_pos/ui/pos.php',
        '/logistica'  => 'modules/5_logistics/ui/logistica.php',
        '/caja'       => 'modules/6_cash/ui/caja.php',
        '/reportes'   => 'modules/7_reports/ui/reportes.php',
    ];

    /**
     * @var array<string, array<int, string>> Roles autorizados por ruta.
     *                                        Las rutas no listadas solo requieren sesión.
     *                                        Los nombres se comparan en minúsculas.
     */
    private array $rolesPorRuta = [
        '/catalogo'    => ['cliente'],
        '/ecommerce'   => ['cliente'],
        '/mis-pedidos' => ['cliente'],
        '/pos'         => ['vendedor', 'cajero'],
        '/caja'        => ['vendedor', 'cajero'],
        '/logistica'   => ['repartidor'],
    ];

    /**
     * @var string Ruta base del proyecto dentro del servidor web.
     *             Permite ajustar el enrutador si el proyecto no está en la raíz.
     */
    private string $rutaBase;

    /**
     * Constructor: detecta la ruta base del proyecto automáticamente.
     */
    public function __construct()
    {
        // Determinar la ruta base del proyecto (directorio donde está index.php)
        $this->rutaBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    }

    /**
     * Despacha la solicitud entrante al archivo PHP correspondiente según la URL.
     *
     * Flujo:
     *  1. Obtiene la URI actual y la limpia.
     *  2. Si la ruta es pública, incluye el archivo directamente.
     *  3. Si la ruta es protegida, verifica sesión y rol antes de incluir.
     *  4. Si la ruta no existe, redirige al login (comportamiento seguro por defecto).
     *
     * @return void
     */
    public function despachar(): void
    {
        // Obtener la ruta de la URL actual (soportando tanto path como parámetro ?ruta=)
        if (!empty($_GET['ruta'])) {
            $ruta = '/' . ltrim($_GET['ruta'], '/');
        } else {
            $uriCompleta = $_SERVER['REQUEST_URI'] ?? '/';
            $ruta = '/' . trim(str_replace($this->rutaBase, '', parse_url($uriCompleta, PHP_URL_PATH)), '/');
            $ruta = str_replace('/index.php', '', $ruta);
        }

        // Normalizar: ruta vacía equivale a la raíz
        if ($ruta === '' || $ruta === '//') {
            $ruta = '/';
        }

        // --- Caso 1: Ruta pública ---
        if (array_key_exists($ruta, $this->rutasPublicas)) {
            $archivoDestino = __DIR__ . '/../' . $this->rutasPublicas[$ruta];
            $this->incluirArchivo($archivoDestino);
            return;
        }

        // --- Caso 2: Ruta protegida ---
        if (array_key_exists($ruta, $this->rutasProtegidas)) {
            // Verificar que exista una sesión activa antes de servir la vista
            $this->verificarSesion();
            // Verificar que el rol de la sesión tenga permiso sobre la ruta
            $this->verificarPermiso($ruta);
            $archivoDestino = __DIR__ . '/../' . $this->rutasProtegidas[$ruta];
            $this->incluirArchivo($archivoDestino);
            return;
        }

        // --- Caso 3: Ruta no registrada — redirigir al login por seguridad ---
        $this->redirigir('/login');
    }

    /**
     * Registra una ruta pública en tiempo de ejecución.
     *
     * @param string $ruta    Ruta URL (ej. '/contacto').
     * @param string $archivo Ruta relativa al archivo PHP desde la raíz del proyecto.
     * @return void
     */
    public function agregarRutaPublica(string $ruta, string $archivo): void
    {
        $this->rutasPublicas[$ruta] = $archivo;
    }

    /**
     * Registra una ruta protegida en tiempo de ejecución.
     *
     * @param string $ruta    Ruta URL (ej. '/perfil').
     * @param string $archivo Ruta relativa al archivo PHP desde la raíz del proyecto.
     * @return void
     */
    public function agregarRutaProtegida(string $ruta, string $archivo): void
    {
        $this->rutasProtegidas[$ruta] = $archivo;
    }

    /**
     * Verifica si existe una sesión activa con usuario autenticado.
     * Si no hay sesión, redirige al login y detiene la ejecución.
     *
     * @return void
     */
    private function verificarSesion(): void
    {
        // Iniciar sesión solo si aún no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si no existe el identificador de usuario en sesión, redirigir al login
        if (empty($_SESSION['id_usuario'])) {
            $this->redirigir('/login');
        }
    }

    /**
     * Verifica que el rol de la sesión esté autorizado para la ruta solicitada.
     *
     * Las rutas sin restricción de rol solo requieren una sesión válida. Las
     * cuentas de repartidor además deben tener un registro vinculado, y las de
     * ventas un id_empleado válido, para evitar sesiones incompletas.
     *
     * @param string $ruta Ruta URL solicitada.
     * @return void
     */
    private function verificarPermiso(string $ruta): void
    {
        if (!isset($this->rolesPorRuta[$ruta])) {
            return;
        }

        $rol = $this->obtenerRolNormalizado();

        if (!in_array($rol, $this->rolesPorRuta[$ruta], true)) {
            $this->denegarAcceso();
        }

        if ($rol === 'repartidor' && (int)($_SESSION['id_repartidor'] ?? 0) <= 0) {
            $this->denegarAcceso();
        }

        if (in_array($rol, ['vendedor', 'cajero'], true) && (int)($_SESSION['id_empleado'] ?? 0) <= 0) {
            $this->denegarAcceso();
        }

        if ($rol === 'cliente' && (int)($_SESSION['id_cliente'] ?? 0) <= 0) {
            $this->denegarAcceso();
        }
    }

    /**
     * Obtiene el rol de la sesión en minúsculas y sin espacios.
     *
     * @return string Rol normalizado (cliente, vendedor, cajero, repartidor, etc.).
     */
    private function obtenerRolNormalizado(): string
    {
        $rol = strtolower(trim((string)($_SESSION['rol'] ?? '')));

        if ($rol === 'cliente' || ($_SESSION['tipo_usuario'] ?? '') === 'cliente') {
            return 'cliente';
        }

        return $rol;
    }

    /**
     * Responde con HTTP 403 cuando la sesión no tiene permiso sobre la ruta.
     *
     * @return never
     */
    private function denegarAcceso(): never
    {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $inicio = htmlspecialchars($this->rutaBase . '/', ENT_QUOTES, 'UTF-8');

        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>Acceso denegado — Tortillería</title></head><body>'
            . '<main style="max-width:640px;margin:10vh auto;font-family:Arial,sans-serif;padding:0 1rem;">'
            . '<h1>403 — Acceso denegado</h1>'
            . '<p>Tu cuenta no tiene permisos para acceder a esta sección.</p>'
            . '<p><a href="' . $inicio . '">Volver al inicio</a></p>'
            . '</main></body></html>';

        exit;
    }

    /**
     * Incluye un archivo PHP de forma segura, verificando que exista.
     * Si el archivo no existe, redirige al login.
     *
     * @param string $rutaAbsoluta Ruta absoluta del archivo a incluir.
     * @return void
     */
    private function incluirArchivo(string $rutaAbsoluta): void
    {
        if (file_exists($rutaAbsoluta)) {
            require_once $rutaAbsoluta;
        } else {
            // El archivo no existe: registrar el error y redirigir al login
            error_log("Router: archivo no encontrado -> {$rutaAbsoluta}");
            $this->redirigir('/login');
        }
    }

    /**
     * Redirige al usuario a una ruta interna del sistema y detiene la ejecución.
     *
     * @param string $ruta Ruta URL de destino (ej. '/login').
     * @return void
     */
    private function redirigir(string $ruta): void
    {
        header('Location: ' . $this->rutaBase . $ruta);
        exit;
    }
}

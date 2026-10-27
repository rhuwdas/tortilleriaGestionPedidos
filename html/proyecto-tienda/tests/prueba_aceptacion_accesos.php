<?php
/**
 * Pruebas de aceptación del control de acceso por rol (HU1–HU10).
 *
 * Verifica las rutas públicas y que cada cuenta solo pueda abrir las
 * pantallas que le corresponden según la matriz de acceso del proyecto.
 */

require_once __DIR__ . '/../core/Database.php';

$pdo = Database::obtenerInstancia()->obtenerConexion();
$baseUrl = 'http://localhost/proyecto-tienda';
$sufijo = time() . random_int(100, 999);
$contrasena = 'ClaveAccesos2026!';
$cookies = [];
$empleados = [];
$repartidores = [];
$clientes = [];
$exitos = 0;
$totalPruebas = 0;

/**
 * Ejecuta una petición y devuelve código HTTP, URL de redirección y cuerpo.
 *
 * @return array{0: int, 1: string, 2: string}
 */
function peticionAcceso(string $url, ?string $archivoCookies = null, string $metodo = 'GET', ?array $cuerpo = null): array
{
    $manejador = curl_init($url);
    curl_setopt_array($manejador, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest',
        ],
    ]);
    if ($cuerpo !== null) {
        curl_setopt($manejador, CURLOPT_POSTFIELDS, json_encode($cuerpo));
    }
    if ($archivoCookies !== null) {
        curl_setopt($manejador, CURLOPT_COOKIEJAR, $archivoCookies);
        curl_setopt($manejador, CURLOPT_COOKIEFILE, $archivoCookies);
    }

    $respuesta = (string)curl_exec($manejador);
    $codigo = (int)curl_getinfo($manejador, CURLINFO_HTTP_CODE);
    $redireccion = (string)(curl_getinfo($manejador, CURLINFO_REDIRECT_URL) ?? '');
    curl_close($manejador);

    return [$codigo, $redireccion, $respuesta];
}

/**
 * Comprueba una condición y actualiza el contador de pruebas.
 */
function comprobarAcceso(bool $condicion, string $descripcion): void
{
    global $exitos, $totalPruebas;
    $totalPruebas++;
    if ($condicion) {
        $exitos++;
        echo "  -> ÉXITO: {$descripcion}\n";
    } else {
        echo "  -> FALLO: {$descripcion}\n";
    }
}

/**
 * Inicia sesión y devuelve la respuesta JSON decodificada.
 *
 * @return array{codigo: int, datos: array<string, mixed>}
 */
function iniciarSesionAcceso(string $baseUrl, string $correo, string $contrasena, string $archivoCookies): array
{
    [$codigo, , $crudo] = peticionAcceso(
        $baseUrl . '/modules/1_auth/api/login.php',
        $archivoCookies,
        'POST',
        ['correo' => $correo, 'contrasena' => $contrasena]
    );

    return ['codigo' => $codigo, 'datos' => json_decode($crudo, true) ?: []];
}

try {
    $roles = [];
    foreach ($pdo->query('SELECT id_rol, rol FROM rol')->fetchAll(PDO::FETCH_ASSOC) as $rol) {
        $roles[strtolower($rol['rol'])] = (int)$rol['id_rol'];
    }

    foreach (['repartidor', 'vendedor', 'cajero'] as $rolNecesario) {
        if (!isset($roles[$rolNecesario])) {
            throw new RuntimeException("Falta el rol {$rolNecesario} en la base de datos.");
        }
    }

    $insertarEmpleado = $pdo->prepare(
        'INSERT INTO empleado (nombre, apellidos, id_rol, usuario, password_hash, email, activo)
         VALUES (:nombre, :apellidos, :id_rol, :usuario, :password_hash, :email, 1)'
    );

    foreach (['Vendedor' => 'vendedor', 'Cajero' => 'cajero', 'Repartidor' => 'repartidor'] as $etiqueta => $clave) {
        $correo = "accesos.{$clave}.{$sufijo}@example.com";
        $insertarEmpleado->execute([
            ':nombre' => $etiqueta,
            ':apellidos' => 'Prueba Accesos',
            ':id_rol' => $roles[$clave],
            ':usuario' => "accesos{$clave}{$sufijo}",
            ':password_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
            ':email' => $correo,
        ]);
        $empleados[$clave] = (int)$pdo->lastInsertId();
        $cookies[$clave] = sys_get_temp_dir() . "/accesos_{$clave}_{$sufijo}.txt";
    }

    $pdo->prepare(
        'INSERT INTO repartidor (id_empleado, nombre, apellidos, telefono, estado)
         VALUES (:id_empleado, :nombre, :apellidos, :telefono, \'Disponible\')'
    )->execute([
        ':id_empleado' => $empleados['repartidor'],
        ':nombre' => 'Repartidor',
        ':apellidos' => 'Prueba Accesos',
        ':telefono' => '55-0000-0001',
    ]);
    $repartidores[] = (int)$pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO cliente (nombre, apellidos, email, password_hash, activo)
         VALUES (:nombre, :apellidos, :email, :password_hash, 1)'
    )->execute([
        ':nombre' => 'Cliente',
        ':apellidos' => 'Prueba Accesos',
        ':email' => "accesos.cliente.{$sufijo}@example.com",
        ':password_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
    ]);
    $clientes[] = (int)$pdo->lastInsertId();
    $cookies['cliente'] = sys_get_temp_dir() . "/accesos_cliente_{$sufijo}.txt";

    // Cuenta de repartidor sin vínculo: el login debe rechazarse (HU10).
    $insertarEmpleado->execute([
        ':nombre' => 'Repartidor',
        ':apellidos' => 'Sin Vínculo',
        ':id_rol' => $roles['repartidor'],
        ':usuario' => "accesossinvinculo{$sufijo}",
        ':password_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
        ':email' => "accesos.sinvinculo.{$sufijo}@example.com",
    ]);
    $empleados['sin_vinculo'] = (int)$pdo->lastInsertId();
    $cookies['sin_vinculo'] = sys_get_temp_dir() . "/accesos_sin_vinculo_{$sufijo}.txt";

    echo "[PRUEBA 1] Rutas públicas accesibles sin sesión...\n";
    foreach (['/' => 'inicio', '/?ruta=/login' => 'login', '/?ruta=/recuperar' => 'recuperación', '/?ruta=/registro' => 'registro'] as $ruta => $nombre) {
        [$codigo] = peticionAcceso($baseUrl . $ruta);
        comprobarAcceso($codigo === 200, "{$nombre} responde 200 sin sesión (HTTP {$codigo}).");
    }

    echo "\n[PRUEBA 2] Rutas protegidas sin sesión redirigen al login...\n";
    foreach (['/catalogo', '/pos', '/logistica'] as $ruta) {
        [$codigo, $redireccion] = peticionAcceso($baseUrl . '/?ruta=' . $ruta);
        comprobarAcceso(
            $codigo === 302 && str_contains($redireccion, '/login'),
            "{$ruta} redirige al login (HTTP {$codigo})."
        );
    }

    $sesiones = [];
    $resultadosLogin = [
        'cliente' => iniciarSesionAcceso($baseUrl, "accesos.cliente.{$sufijo}@example.com", $contrasena, $cookies['cliente']),
        'vendedor' => iniciarSesionAcceso($baseUrl, "accesos.vendedor.{$sufijo}@example.com", $contrasena, $cookies['vendedor']),
        'cajero' => iniciarSesionAcceso($baseUrl, "accesos.cajero.{$sufijo}@example.com", $contrasena, $cookies['cajero']),
        'repartidor' => iniciarSesionAcceso($baseUrl, "accesos.repartidor.{$sufijo}@example.com", $contrasena, $cookies['repartidor']),
    ];

    echo "\n[PRUEBA 3] El login redirige a la pantalla inicial de cada rol...\n";
    foreach ([
        'cliente' => 'ruta=/catalogo',
        'vendedor' => 'ruta=/pos',
        'cajero' => 'ruta=/pos',
        'repartidor' => 'ruta=/logistica',
    ] as $rol => $destino) {
        $resultado = $resultadosLogin[$rol];
        comprobarAcceso(
            $resultado['codigo'] === 200
            && !empty($resultado['datos']['exito'])
            && str_contains((string)($resultado['datos']['redireccion'] ?? ''), $destino),
            "{$rol} inicia sesión y es enviado a {$destino}."
        );
    }

    echo "\n[PRUEBA 4] El cliente solo accede a catálogo, e-commerce y mis pedidos...\n";
    foreach (['/catalogo', '/ecommerce', '/mis-pedidos'] as $ruta) {
        [$codigo] = peticionAcceso($baseUrl . '/?ruta=' . $ruta, $cookies['cliente']);
        comprobarAcceso($codigo === 200, "cliente puede abrir {$ruta} (HTTP {$codigo}).");
    }
    foreach (['/pos', '/caja', '/logistica'] as $ruta) {
        [$codigo] = peticionAcceso($baseUrl . '/?ruta=' . $ruta, $cookies['cliente']);
        comprobarAcceso($codigo === 403, "cliente NO puede abrir {$ruta} (HTTP {$codigo}).");
    }

    echo "\n[PRUEBA 5] Vendedor y cajero acceden a POS y caja, no a las vistas de cliente...\n";
    foreach (['vendedor', 'cajero'] as $rolVentas) {
        foreach (['/pos', '/caja'] as $ruta) {
            [$codigo] = peticionAcceso($baseUrl . '/?ruta=' . $ruta, $cookies[$rolVentas]);
            comprobarAcceso($codigo === 200, "{$rolVentas} puede abrir {$ruta} (HTTP {$codigo}).");
        }
        foreach (['/catalogo', '/ecommerce', '/mis-pedidos', '/logistica'] as $ruta) {
            [$codigo] = peticionAcceso($baseUrl . '/?ruta=' . $ruta, $cookies[$rolVentas]);
            comprobarAcceso($codigo === 403, "{$rolVentas} NO puede abrir {$ruta} (HTTP {$codigo}).");
        }
    }

    echo "\n[PRUEBA 6] El repartidor solo accede a logística...\n";
    [$codigoLogistica] = peticionAcceso($baseUrl . '/?ruta=/logistica', $cookies['repartidor']);
    comprobarAcceso($codigoLogistica === 200, "repartidor puede abrir /logistica (HTTP {$codigoLogistica}).");
    foreach (['/catalogo', '/ecommerce', '/mis-pedidos', '/pos', '/caja'] as $ruta) {
        [$codigo] = peticionAcceso($baseUrl . '/?ruta=' . $ruta, $cookies['repartidor']);
        comprobarAcceso($codigo === 403, "repartidor NO puede abrir {$ruta} (HTTP {$codigo}).");
    }

    echo "\n[PRUEBA 7] Una cuenta de repartidor sin vínculo no puede iniciar sesión...\n";
    $sinVinculo = iniciarSesionAcceso(
        $baseUrl,
        "accesos.sinvinculo.{$sufijo}@example.com",
        $contrasena,
        $cookies['sin_vinculo']
    );
    comprobarAcceso(
        $sinVinculo['codigo'] === 403 && empty($sinVinculo['datos']['exito']),
        "el login sin vínculo se rechaza con HTTP {$sinVinculo['codigo']}."
    );
} catch (Throwable $error) {
    echo "\nERROR GENERAL: {$error->getMessage()}\n";
} finally {
    if ($repartidores !== []) {
        $marcadores = implode(',', array_fill(0, count($repartidores), '?'));
        $pdo->prepare("DELETE FROM repartidor WHERE id_repartidor IN ({$marcadores})")->execute($repartidores);
    }
    if ($empleados !== []) {
        $marcadores = implode(',', array_fill(0, count($empleados), '?'));
        $pdo->prepare("DELETE FROM empleado WHERE id_empleado IN ({$marcadores})")->execute(array_values($empleados));
    }
    if ($clientes !== []) {
        $marcadores = implode(',', array_fill(0, count($clientes), '?'));
        $pdo->prepare("DELETE FROM cliente WHERE id_cliente IN ({$marcadores})")->execute($clientes);
    }
    foreach ($cookies as $archivoCookies) {
        if (file_exists($archivoCookies)) {
            unlink($archivoCookies);
        }
    }
}

echo "\nRESULTADO ACCESOS: {$exitos}/{$totalPruebas} pruebas exitosas\n";
exit($exitos === $totalPruebas ? 0 : 1);

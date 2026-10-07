<?php
/**
 * core/Database.php
 *
 * Clase Singleton para la gestión de la conexión PDO a la base de datos.
 * Lee las credenciales desde las variables de entorno definidas en el archivo .env,
 * garantizando una única instancia de conexión durante todo el ciclo de vida
 * de la solicitud (patrón Singleton).
 *
 * Restricciones (AGENTS.md):
 *  - Sin Composer ni frameworks externos.
 *  - Consultas siempre mediante PDO con prepared statements.
 *  - La base de datos ya existe; no se crea desde este script.
 */

class Database
{
    /**
     * @var Database|null Única instancia de la clase (patrón Singleton).
     */
    private static ?Database $instancia = null;

    /**
     * @var PDO Objeto de conexión PDO.
     */
    private PDO $conexion;

    /**
     * Constructor privado: carga el .env y establece la conexión PDO.
     * Es privado para impedir la instanciación directa desde fuera de la clase.
     */
    private function __construct()
    {
        // Cargar variables de entorno desde el archivo .env ubicado en la raíz del proyecto
        $this->cargarEnv(__DIR__ . '/../.env');

        $host     = $_ENV['DB_HOST']  ?? 'db';
        $puerto   = $_ENV['DB_PORT']  ?? '3306';
        $nombreBd = $_ENV['DB_NAME']  ?? 'tortilleria';
        $usuario  = $_ENV['DB_USER']  ?? 'root';
        $contrasena = $_ENV['DB_PASS'] ?? '';

        // DSN (Data Source Name) para la conexión MySQL/MariaDB
        $dsn = "mysql:host={$host};port={$puerto};dbname={$nombreBd};charset=utf8mb4";

        // Opciones de PDO recomendadas para seguridad y manejo de errores
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lanza excepciones en errores SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Resultados como arrays asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Usar prepared statements nativos
        ];

        try {
            $this->conexion = new PDO($dsn, $usuario, $contrasena, $opciones);
        } catch (PDOException $e) {
            // Registrar el error sin exponer credenciales al usuario final
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            throw new RuntimeException('No se pudo establecer la conexión con la base de datos.');
        }
    }

    /**
     * Impide la clonación de la instancia (refuerza el patrón Singleton).
     */
    private function __clone() {}

    /**
     * Impide la deserialización de la instancia (refuerza el patrón Singleton).
     *
     * @throws RuntimeException
     */
    public function __wakeup(): void
    {
        throw new RuntimeException('No se puede deserializar la instancia de Database.');
    }

    /**
     * Devuelve la única instancia de la clase Database.
     * Si no existe, la crea (lazy initialization).
     *
     * @return Database
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }

        return self::$instancia;
    }

    /**
     * Devuelve el objeto PDO activo para ejecutar consultas.
     *
     * @return PDO
     */
    public function obtenerConexion(): PDO
    {
        return $this->conexion;
    }

    /**
     * Carga las variables de entorno desde un archivo .env al superglobal $_ENV.
     * Solo procesa líneas con formato CLAVE=VALOR; ignora comentarios (#) y líneas vacías.
     *
     * @param string $rutaArchivo Ruta absoluta al archivo .env.
     * @return void
     */
    private function cargarEnv(string $rutaArchivo): void
    {
        // Verificar que el archivo .env exista antes de intentar leerlo
        if (!file_exists($rutaArchivo)) {
            error_log("Advertencia: El archivo .env no fue encontrado en: {$rutaArchivo}");
            return;
        }

        // Leer todas las líneas del archivo .env
        $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {
            // Ignorar líneas de comentario (empiezan con #) y líneas sin el signo '='
            if (str_starts_with(trim($linea), '#') || !str_contains($linea, '=')) {
                continue;
            }

            // Separar clave y valor en la primera ocurrencia del signo '='
            [$clave, $valor] = explode('=', $linea, 2);

            $clave = trim($clave);
            $valor = trim($valor);

            // Registrar en $_ENV y putenv solo si la variable no está ya definida
            if (!array_key_exists($clave, $_ENV)) {
                $_ENV[$clave] = $valor;
                putenv("{$clave}={$valor}");
            }
        }
    }
}

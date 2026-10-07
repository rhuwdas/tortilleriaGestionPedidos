<?php
/**
 * modules/2_catalog/api/ProductModel.php
 *
 * Modelo del catálogo de productos (HU3).
 * Encapsula todas las consultas SQL relacionadas con la obtención de productos.
 * Todas las consultas usan PDO con prepared statements (AGENTS.md).
 *
 * Métodos disponibles:
 *  - obtenerTodos(array $filtros): array
 *  - obtenerCategorias(): array
 */

class ProductModel
{
    /**
     * @var PDO Instancia de conexión PDO obtenida desde el Singleton Database.
     */
    private PDO $pdo;

    /**
     * Constructor: obtiene la conexión PDO del Singleton.
     */
    public function __construct()
    {
        $this->pdo = Database::obtenerInstancia()->obtenerConexion();
    }

    /**
     * Devuelve el listado de productos aplicando filtros opcionales de categoría
     * y disponibilidad. Admite también búsqueda por texto en nombre o descripción.
     *
     * @param array $filtros {
     *   @type string $categoria      Nombre de categoría a filtrar (opcional).
     *   @type bool   $soloDisponible true para devolver sólo productos disponibles (opcional).
     *   @type string $busqueda       Texto libre para buscar en nombre/descripción (opcional).
     * }
     * @return array Listado de productos como arrays asociativos.
     */
    public function obtenerTodos(array $filtros = []): array
    {
        // ── Construcción dinámica de la cláusula WHERE ──────────────────────
        $condiciones = [];
        $parametros  = [];

        // Filtro por categoría
        if (!empty($filtros['categoria'])) {
            $condiciones[] = 'p.categoria = :categoria';
            $parametros[':categoria'] = trim($filtros['categoria']);
        }

        // Filtro de disponibilidad: solo productos con stock > 0
        if (!empty($filtros['soloDisponible'])) {
            $condiciones[] = 'p.disponible = 1';
        }

        // Filtro de búsqueda por texto (nombre o descripción)
        if (!empty($filtros['busqueda'])) {
            $condiciones[] = '(p.nombre LIKE :busqueda OR p.descripcion LIKE :busqueda)';
            $parametros[':busqueda'] = '%' . trim($filtros['busqueda']) . '%';
        }

        // Ensamblar la sentencia SQL completa
        $clausulaWhere = count($condiciones) > 0
            ? 'WHERE ' . implode(' AND ', $condiciones)
            : '';

        $sql = "SELECT
                    p.id,
                    p.nombre,
                    p.descripcion,
                    p.precio,
                    p.categoria,
                    p.imagen_url,
                    p.disponible
                FROM productos p
                {$clausulaWhere}
                ORDER BY p.categoria ASC, p.nombre ASC";

        // Preparar y ejecutar la consulta
        $sentencia = $this->pdo->prepare($sql);

        foreach ($parametros as $clave => $valor) {
            // Determinar el tipo PDO correcto para cada parámetro
            $tipoPdo = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $sentencia->bindValue($clave, $valor, $tipoPdo);
        }

        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Devuelve la lista de categorías distintas registradas en la tabla productos.
     * Se usa para poblar el selector de filtro en el catálogo.
     *
     * @return array Lista de strings con los nombres de categoría.
     */
    public function obtenerCategorias(): array
    {
        $sentencia = $this->pdo->prepare(
            'SELECT DISTINCT categoria
               FROM productos
              WHERE categoria IS NOT NULL
                AND categoria != \'\'
              ORDER BY categoria ASC'
        );

        $sentencia->execute();

        // Devolver solo los valores del campo, no los arrays completos
        return $sentencia->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

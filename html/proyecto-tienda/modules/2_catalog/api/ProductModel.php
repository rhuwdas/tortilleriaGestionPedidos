<?php
/**
 * modules/2_catalog/api/ProductModel.php
 *
 * Modelo del catálogo de productos (HU3).
 * Encapsula todas las consultas SQL relacionadas con la obtención de productos.
 * Todas las consultas usan PDO con prepared statements (AGENTS.md).
 *
 * Tabla real en BD: producto
 *   - id_producto  INT PK
 *   - nombre       VARCHAR(100)
 *   - precio_kilo  DECIMAL(10,2)
 *   - unidad       VARCHAR(30)
 *   - disponible   TINYINT(1)
 *
 * Vista disponible: vista_catalogo_web (solo productos con disponible = 1)
 */
class ProductModel
{
    /** @var PDO Instancia de conexión PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerInstancia()->obtenerConexion();
    }

    /**
     * Devuelve el listado de productos con filtros opcionales.
     *
     * @param array $filtros {
     *   @type bool   $soloDisponible true para devolver solo productos disponibles.
     *   @type string $busqueda       Texto libre para buscar en nombre.
     * }
     * @return array Listado de productos como arrays asociativos.
     */
    public function obtenerTodos(array $filtros = []): array
    {
        $condiciones = [];
        $parametros  = [];

        // Filtro de disponibilidad
        if (!empty($filtros['soloDisponible'])) {
            $condiciones[] = 'p.disponible = 1';
        }

        // Filtro de búsqueda por nombre
        if (!empty($filtros['busqueda'])) {
            $condiciones[] = 'p.nombre LIKE :busqueda';
            $parametros[':busqueda'] = '%' . trim($filtros['busqueda']) . '%';
        }

        $clausulaWhere = count($condiciones) > 0
            ? 'WHERE ' . implode(' AND ', $condiciones)
            : '';

        $sql = "SELECT
                    p.id_producto   AS id,
                    p.nombre,
                    p.precio_kilo,
                    p.unidad,
                    p.disponible
                FROM producto p
                {$clausulaWhere}
                ORDER BY p.nombre ASC";

        $sentencia = $this->pdo->prepare($sql);

        foreach ($parametros as $clave => $valor) {
            $sentencia->bindValue($clave, $valor, PDO::PARAM_STR);
        }

        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Devuelve solo los productos disponibles (disponible = 1).
     * Usa la vista vista_catalogo_web de la BD.
     *
     * @return array Listado de productos disponibles.
     */
    public function obtenerDisponibles(): array
    {
        $sentencia = $this->pdo->prepare(
            'SELECT id_producto AS id, producto AS nombre, precio_kilo, unidad, disponible
               FROM vista_catalogo_web
              ORDER BY producto ASC'
        );
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Devuelve la lista de unidades distintas para el filtro del catálogo.
     *
     * @return array Lista de strings con las unidades (ej. "kilo", "pieza").
     */
    public function obtenerUnidades(): array
    {
        $sentencia = $this->pdo->prepare(
            'SELECT DISTINCT unidad
               FROM producto
              WHERE unidad IS NOT NULL
              ORDER BY unidad ASC'
        );
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

<?php
/**
 * modules/3_ecommerce/api/EcommerceModel.php
 * 
 * Modelo para la gestión de pedidos en línea (HU5).
 * Responsabilidades:
 *  - Crear pedidos en línea con detalle de productos
 *  - Validar existencia de productos y cantidades
 *  - Gestionar direcciones de entrega
 *  - Obtener pedidos del cliente
 */

require_once __DIR__ . '/../../../core/Database.php';

/**
 * Excepción lanzada cuando el inventario de materias primas no cubre la
 * cantidad solicitada al confirmar un pedido en línea.
 */
class ExistenciasInsuficientesException extends RuntimeException
{
    /** @var array<int, string> Errores de inventario detectados */
    private array $errores;

    public function __construct(array $errores)
    {
        parent::__construct(implode(', ', $errores));
        $this->errores = $errores;
    }

    /** @return array<int, string> */
    public function obtenerErrores(): array
    {
        return $this->errores;
    }
}

class EcommerceModel
{
    /** @var PDO Conexión activa a la base de datos */
    private PDO $conexionBaseDatos;

    public function __construct()
    {
        $this->conexionBaseDatos = Database::obtenerInstancia()->obtenerConexion();
    }

    /**
     * Obtiene un producto por su ID
     */
    public function obtenerProducto(int $idProducto): ?array
    {
        $sentencia = $this->conexionBaseDatos->prepare(
            'SELECT id_producto, nombre, precio_kilo, unidad, disponible 
             FROM producto 
             WHERE id_producto = :id_producto'
        );
        $sentencia->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $sentencia->execute();
        
        $producto = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $producto !== false ? $producto : null;
    }

    /**
     * Obtiene las materias primas y cantidades necesarias para elaborar un producto.
     *
     * @param int  $idProducto    Identificador del producto.
     * @param bool $bloquearFilas Bloquea las filas para evitar condiciones de carrera
     *                            cuando se ejecuta dentro de una transacción.
     * @return array<int, array<string, mixed>>
     */
    private function obtenerRequerimientosInventario(int $idProducto, bool $bloquearFilas = false): array
    {
        $consulta = 'SELECT mp.id_materia_prima,
                            mp.nombre AS materia_prima,
                            mp.stock,
                            mp.unidad_medida,
                            pm.cantidad_necesaria
                       FROM producto_materiaprima pm
                       INNER JOIN materiaprima mp ON pm.id_materia_prima = mp.id_materia_prima
                      WHERE pm.id_producto = :id_producto
                      ORDER BY mp.id_materia_prima';

        if ($bloquearFilas) {
            $consulta .= ' FOR UPDATE';
        }

        $sentencia = $this->conexionBaseDatos->prepare($consulta);
        $sentencia->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Valida la existencia de los productos y que el inventario de materias primas
     * alcance para cubrir las cantidades solicitadas (HU5 - CA-HU5-001).
     *
     * @param array $items         Artículos del carrito con id_producto y cantidad.
     * @param bool  $bloquearFilas Bloquear filas de inventario dentro de una transacción.
     * @return array{valido: bool, errores: array<int, string>}
     */
    public function validarExistencias(array $items, bool $bloquearFilas = false): array
    {
        $resultado = ['valido' => true, 'errores' => []];
        $cantidadesPorProducto = [];
        $cantidadInvalida = false;

        // Agrupar cantidades para validar una sola vez por producto
        foreach ($items as $item) {
            $idProducto = (int)($item['id_producto'] ?? 0);
            $cantidad = (float)($item['cantidad'] ?? 0);

            if ($idProducto <= 0 || $cantidad <= 0) {
                $cantidadInvalida = true;
                continue;
            }

            $cantidadesPorProducto[$idProducto] = ($cantidadesPorProducto[$idProducto] ?? 0) + $cantidad;
        }

        if ($cantidadInvalida) {
            $resultado['valido'] = false;
            $resultado['errores'][] = 'Hay productos con cantidad inválida en el carrito.';
        }

        foreach ($cantidadesPorProducto as $idProducto => $cantidadSolicitada) {
            $producto = $this->obtenerProducto($idProducto);

            if (!$producto) {
                $resultado['valido'] = false;
                $resultado['errores'][] = 'Producto no encontrado.';
                continue;
            }

            if ((int)$producto['disponible'] !== 1) {
                $resultado['valido'] = false;
                $resultado['errores'][] = "El producto {$producto['nombre']} no está disponible.";
                continue;
            }

            $requerimientos = $this->obtenerRequerimientosInventario($idProducto, $bloquearFilas);

            foreach ($requerimientos as $requerimiento) {
                $cantidadNecesaria = (float)$requerimiento['cantidad_necesaria'] * $cantidadSolicitada;
                $stockDisponible = (float)$requerimiento['stock'];

                if ($cantidadNecesaria > $stockDisponible) {
                    $resultado['valido'] = false;
                    $resultado['errores'][] = sprintf(
                        'Inventario insuficiente para %s: se requieren %.2f %s de %s y solo hay %.2f %s disponibles.',
                        $producto['nombre'],
                        $cantidadNecesaria,
                        $requerimiento['unidad_medida'],
                        $requerimiento['materia_prima'],
                        $stockDisponible,
                        $requerimiento['unidad_medida']
                    );
                    break;
                }
            }
        }

        return $resultado;
    }

    /**
     * Crea un pedido en línea
     */
    public function crearPedidoOnline(array $datosPedido): int
    {
        $this->conexionBaseDatos->beginTransaction();
        
        try {
            // Validar inventario dentro de la transacción y bloquear las filas
            // para evitar que dos pedidos consuman el mismo stock (HU5 - tarea 5.4).
            $validacionInventario = $this->validarExistencias($datosPedido['items'], true);
            if (!$validacionInventario['valido']) {
                throw new ExistenciasInsuficientesException($validacionInventario['errores']);
            }

            // Obtener un empleado por defecto para atender pedidos web
            $sentenciaEmpleado = $this->conexionBaseDatos->query("SELECT id_empleado FROM empleado WHERE activo = 1 LIMIT 1");
            $empleado = $sentenciaEmpleado->fetch(PDO::FETCH_ASSOC);
            $idEmpleado = $empleado ? (int)$empleado['id_empleado'] : 1;
            
            // Insertar pedido
            $sentenciaPedido = $this->conexionBaseDatos->prepare(
                'INSERT INTO pedido (fecha_pedido, estado, id_cliente, id_empleado, id_direccion, 
                                    canal, fecha_hora_entrega_solicitada, tipo_entrega)
                 VALUES (NOW(), "Pendiente", :id_cliente, :id_empleado, :id_direccion,
                         "Web", :fecha_hora_entrega, :tipo_entrega)'
            );
            
            $sentenciaPedido->bindValue(':id_cliente', (int)$datosPedido['id_cliente'], PDO::PARAM_INT);
            $sentenciaPedido->bindValue(':id_empleado', $idEmpleado, PDO::PARAM_INT);
            $sentenciaPedido->bindValue(':id_direccion', $datosPedido['id_direccion'] ? (int)$datosPedido['id_direccion'] : null, PDO::PARAM_INT);
            $sentenciaPedido->bindValue(':fecha_hora_entrega', $datosPedido['fecha_hora_entrega_solicitada'], PDO::PARAM_STR);
            $sentenciaPedido->bindValue(':tipo_entrega', $datosPedido['tipo_entrega'], PDO::PARAM_STR);
            $sentenciaPedido->execute();
            
            $idPedido = (int)$this->conexionBaseDatos->lastInsertId();
            
            // Insertar detalle del pedido
            $sentenciaDetalle = $this->conexionBaseDatos->prepare(
                'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario)
                 VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario)'
            );
            
            foreach ($datosPedido['items'] as $item) {
                $producto = $this->obtenerProducto((int)$item['id_producto']);
                if ($producto) {
                    $sentenciaDetalle->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                    $sentenciaDetalle->bindValue(':id_producto', (int)$item['id_producto'], PDO::PARAM_INT);
                    $sentenciaDetalle->bindValue(':cantidad', (float)$item['cantidad'], PDO::PARAM_STR);
                    $sentenciaDetalle->bindValue(':precio_unitario', (float)$producto['precio_kilo'], PDO::PARAM_STR);
                    $sentenciaDetalle->execute();
                }
            }
            
            $this->conexionBaseDatos->commit();
            return $idPedido;
            
        } catch (Exception $excepcion) {
            if ($this->conexionBaseDatos->inTransaction()) {
                $this->conexionBaseDatos->rollBack();
            }
            throw $excepcion;
        }
    }

    /**
     * Obtiene el historial de pedidos de un cliente con sus partidas detalladas.
     * La consulta siempre filtra por id_cliente para evitar fuga de datos entre clientes.
     *
     * @param int $idCliente Identificador del cliente autenticado.
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPedidosCliente(int $idCliente): array
    {
        $sentencia = $this->conexionBaseDatos->prepare(
            'SELECT p.id_pedido,
                    p.fecha_pedido,
                    p.estado,
                    p.canal,
                    p.tipo_entrega,
                    p.fecha_hora_entrega_solicitada,
                    p.motivo_rechazo,
                    p.id_direccion,
                    d.calle,
                    d.colonia,
                    d.municipio,
                    d.codigo_postal,
                    d.referencia,
                    COALESCE((
                        SELECT SUM(dp.cantidad * dp.precio_unitario)
                          FROM detalle_pedido dp
                         WHERE dp.id_pedido = p.id_pedido
                    ), 0) AS total_pedido
               FROM pedido p
               LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
              WHERE p.id_cliente = :id_cliente
              ORDER BY p.fecha_pedido DESC, p.id_pedido DESC'
        );
        $sentencia->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sentencia->execute();

        $pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        if (empty($pedidos)) {
            return [];
        }

        $idsPedidos = array_map(static fn(array $pedido): int => (int)$pedido['id_pedido'], $pedidos);
        $detallesPorPedido = $this->obtenerDetallesPorPedidos($idsPedidos);

        foreach ($pedidos as &$pedido) {
            $pedido['detalles'] = $detallesPorPedido[(int)$pedido['id_pedido']] ?? [];
        }
        unset($pedido);

        return $pedidos;
    }

    /**
     * Obtiene un pedido específico del cliente autenticado con sus partidas.
     *
     * @param int $idPedido  Identificador del pedido solicitado.
     * @param int $idCliente Identificador del cliente autenticado.
     * @return array<string, mixed>|null
     */
    public function obtenerPedidoCliente(int $idPedido, int $idCliente): ?array
    {
        $sentencia = $this->conexionBaseDatos->prepare(
            'SELECT p.id_pedido,
                    p.fecha_pedido,
                    p.estado,
                    p.canal,
                    p.tipo_entrega,
                    p.fecha_hora_entrega_solicitada,
                    p.motivo_rechazo,
                    p.id_direccion,
                    d.calle,
                    d.colonia,
                    d.municipio,
                    d.codigo_postal,
                    d.referencia,
                    COALESCE((
                        SELECT SUM(dp.cantidad * dp.precio_unitario)
                          FROM detalle_pedido dp
                         WHERE dp.id_pedido = p.id_pedido
                    ), 0) AS total_pedido
               FROM pedido p
               LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
              WHERE p.id_pedido = :id_pedido
                AND p.id_cliente = :id_cliente
              LIMIT 1'
        );
        $sentencia->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
        $sentencia->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sentencia->execute();

        $pedido = $sentencia->fetch(PDO::FETCH_ASSOC);
        if ($pedido === false) {
            return null;
        }

        $detallesPorPedido = $this->obtenerDetallesPorPedidos([(int)$pedido['id_pedido']]);
        $pedido['detalles'] = $detallesPorPedido[(int)$pedido['id_pedido']] ?? [];

        return $pedido;
    }

    /**
     * Obtiene en una sola consulta las partidas de varios pedidos.
     *
     * @param array<int, int> $idsPedidos Identificadores de pedidos.
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function obtenerDetallesPorPedidos(array $idsPedidos): array
    {
        if (empty($idsPedidos)) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($idsPedidos), '?'));
        $sentencia = $this->conexionBaseDatos->prepare(
            "SELECT dp.id_pedido,
                    dp.id_producto,
                    dp.cantidad,
                    dp.precio_unitario,
                    pr.nombre,
                    pr.unidad,
                    (dp.cantidad * dp.precio_unitario) AS subtotal
               FROM detalle_pedido dp
               INNER JOIN producto pr ON dp.id_producto = pr.id_producto
              WHERE dp.id_pedido IN ({$marcadores})
              ORDER BY dp.id_pedido, dp.detalle_pedido"
        );
        $sentencia->execute($idsPedidos);

        $detallesPorPedido = [];
        foreach ($sentencia->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
            $detallesPorPedido[(int)$detalle['id_pedido']][] = $detalle;
        }

        return $detallesPorPedido;
    }

    /**
     * Guarda o actualiza dirección para pedido a domicilio
     */
    public function guardarDireccionPedido(int $idCliente, array $datosDireccion): int
    {
        $sentencia = $this->conexionBaseDatos->prepare(
            'INSERT INTO direccion (id_cliente, calle, colonia, municipio, codigo_postal, referencia)
             VALUES (:id_cliente, :calle, :colonia, :municipio, :codigo_postal, :referencia)'
        );
        
        $sentencia->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sentencia->bindValue(':calle', trim($datosDireccion['calle']), PDO::PARAM_STR);
        $sentencia->bindValue(':colonia', trim($datosDireccion['colonia'] ?? ''), PDO::PARAM_STR);
        $sentencia->bindValue(':municipio', trim($datosDireccion['municipio'] ?? ''), PDO::PARAM_STR);
        $sentencia->bindValue(':codigo_postal', trim($datosDireccion['codigo_postal'] ?? ''), PDO::PARAM_STR);
        $sentencia->bindValue(':referencia', trim($datosDireccion['referencia'] ?? ''), PDO::PARAM_STR);
        $sentencia->execute();
        
        return (int)$this->conexionBaseDatos->lastInsertId();
    }
}

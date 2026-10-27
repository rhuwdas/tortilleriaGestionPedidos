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
     * Valida existencia de productos en el carrito
     */
    public function validarExistencias(array $items): array
    {
        $resultado = ['valido' => true, 'errores' => []];
        
        foreach ($items as $item) {
            $producto = $this->obtenerProducto((int)$item['id_producto']);
            if (!$producto) {
                $resultado['valido'] = false;
                $resultado['errores'][] = "Producto no encontrado";
                continue;
            }
            if ((int)$producto['disponible'] !== 1) {
                $resultado['valido'] = false;
                $resultado['errores'][] = "El producto {$producto['nombre']} no está disponible";
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
     * Obtiene pedidos de un cliente
     */
    public function obtenerPedidosCliente(int $idCliente): array
    {
        $sentencia = $this->conexionBaseDatos->prepare(
            'SELECT p.*, 
                    GROUP_CONCAT(CONCAT(dp.cantidad, " ", pr.unidad, " de ", pr.nombre) SEPARATOR "; ") as productos
             FROM pedido p
             LEFT JOIN detalle_pedido dp ON p.id_pedido = dp.id_pedido
             LEFT JOIN producto pr ON dp.id_producto = pr.id_producto
             WHERE p.id_cliente = :id_cliente
             GROUP BY p.id_pedido
             ORDER BY p.fecha_pedido DESC'
        );
        $sentencia->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sentencia->execute();
        
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
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

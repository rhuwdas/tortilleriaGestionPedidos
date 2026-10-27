<?php
/**
 * modules/1_auth/api/AuthModel.php
 *
 * Modelo de autenticación para la base de datos 'tortilleria'.
 * Consulta tanto la tabla 'empleado' (con su rol asociado) como la tabla 'cliente'.
 * 
 * Cumple con agents.md:
 *  - Consultas preparadas con PDO para evitar inyección SQL.
 *  - Nombres completos en español y tipos estrictos de PHP.
 *  - Verificación segura con password_verify() en el controlador/endpoint.
 */

class AuthModel
{
    /** @var PDO Conexión PDO activa obtenida mediante Singleton */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerInstancia()->obtenerConexion();
    }

    /**
     * Busca un empleado en la base de datos por su correo electrónico o nombre de usuario.
     * Incluye el rol asociado desde la tabla 'rol'.
     *
     * @param string $identificador Correo electrónico o nombre de usuario del empleado.
     * @return array|null Fila con los datos del empleado o null si no existe.
     */
    public function buscarEmpleado(string $identificador): ?array
    {
        $identificadorLimpio = trim($identificador);

        $sentencia = $this->pdo->prepare(
            'SELECT e.id_empleado AS id, 
                    e.nombre, 
                    e.apellidos, 
                    e.email AS correo, 
                    e.usuario, 
                    e.password_hash AS contrasena_hash, 
                    r.rol AS rol, 
                    e.activo,
                    rd.id_repartidor,
                    \'empleado\' AS tipo_usuario
               FROM empleado e
               JOIN rol r ON e.id_rol = r.id_rol
                LEFT JOIN repartidor rd ON rd.id_empleado = e.id_empleado
              WHERE LOWER(TRIM(e.email)) = LOWER(:identificador_email)
                 OR LOWER(TRIM(e.usuario)) = LOWER(:identificador_usuario)
              LIMIT 1'
        );

        $sentencia->bindValue(':identificador_email',   $identificadorLimpio, PDO::PARAM_STR);
        $sentencia->bindValue(':identificador_usuario', $identificadorLimpio, PDO::PARAM_STR);
        $sentencia->execute();

        $empleado = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $empleado !== false ? $empleado : null;
    }

    /**
     * Busca un cliente en la base de datos por su correo electrónico o nombre de usuario.
     * Asigna el rol estandarizado 'Cliente' para la gestión de permisos y navegación.
     *
     * @param string $identificador Correo electrónico o nombre de usuario del cliente.
     * @return array|null Fila con los datos del cliente o null si no existe.
     */
    public function buscarCliente(string $identificador): ?array
    {
        $identificadorLimpio = trim($identificador);

        $sentencia = $this->pdo->prepare(
            'SELECT c.id_cliente AS id, 
                    c.nombre, 
                    c.apellidos, 
                    c.email AS correo, 
                    c.usuario, 
                    c.password_hash AS contrasena_hash, 
                    \'Cliente\' AS rol, 
                    c.activo,
                    \'cliente\' AS tipo_usuario
               FROM cliente c
              WHERE LOWER(TRIM(c.email)) = LOWER(:identificador_email)
                 OR (c.usuario IS NOT NULL AND c.usuario != \'\' AND LOWER(TRIM(c.usuario)) = LOWER(:identificador_usuario))
              LIMIT 1'
        );

        $sentencia->bindValue(':identificador_email',   $identificadorLimpio, PDO::PARAM_STR);
        $sentencia->bindValue(':identificador_usuario', $identificadorLimpio, PDO::PARAM_STR);
        $sentencia->execute();

        $cliente = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $cliente !== false ? $cliente : null;
    }

    /**
     * Busca un usuario por correo o nombre de usuario, primero en empleado y luego en cliente.
     * Mantiene compatibilidad con llamadas existentes a AuthModel::buscarPorCorreo.
     *
     * @param string $correo Correo electrónico o nombre de usuario ingresado.
     * @return array|null Fila del usuario o null si no existe en ninguna de las dos tablas.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        $empleado = $this->buscarEmpleado($correo);
        if ($empleado !== null) {
            return $empleado;
        }

        return $this->buscarCliente($correo);
    }

    /**
     * Actualiza la fecha y hora del último inicio de sesión para un empleado.
     *
     * @param int $idEmpleado Identificador único del empleado.
     * @return void
     */
    public function actualizarUltimoLoginEmpleado(int $idEmpleado): void
    {
        try {
            $sentencia = $this->pdo->prepare(
                'UPDATE empleado SET ultimo_login = NOW() WHERE id_empleado = :id_empleado'
            );
            $sentencia->bindValue(':id_empleado', $idEmpleado, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $error) {
            // Registrar el error sin romper el flujo de autenticación del usuario
            error_log('AuthModel::actualizarUltimoLoginEmpleado - ' . $error->getMessage());
        }
    }
}

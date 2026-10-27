<?php
/**
 * modules/1_auth/api/AuthModel.php
 *
 * Modelo de autenticación basado en la vista vista_usuarios_general.
 * Enriquece las cuentas de empleado con el id_repartidor vinculado cuando aplica.
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
        $usuario = $this->buscarPorCorreo($identificador);
        return $usuario !== null && $usuario['tipo_usuario'] === 'empleado' ? $usuario : null;
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
        $usuario = $this->buscarPorCorreo($identificador);
        return $usuario !== null && $usuario['tipo_usuario'] === 'cliente' ? $usuario : null;
    }

    /**
    * Busca y normaliza una cuenta de la vista unificada de usuarios.
     *
     * @param string $correo Correo electrónico o nombre de usuario ingresado.
     * @return array|null Fila del usuario o null si no existe en ninguna de las dos tablas.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        $identificadorLimpio = trim($correo);
        $sentencia = $this->pdo->prepare(
                "SELECT v.id_usuario AS id,
                        v.nombre_completo AS nombre,
                        '' AS apellidos,
                        v.email AS correo,
                        v.usuario,
                        v.password_hash AS contrasena_hash,
                        v.activo,
                        CASE WHEN LOWER(v.tipo_usuario) = 'cliente' THEN 'cliente' ELSE 'empleado' END AS tipo_usuario,
                        COALESCE(v.rol, 'Cliente') AS rol,
                        r.id_repartidor
                   FROM vista_usuarios_general v
                   LEFT JOIN repartidor r
                     ON LOWER(v.tipo_usuario) = 'empleado'
                    AND r.id_empleado = v.id_usuario
                  WHERE LOWER(TRIM(COALESCE(v.email, ''))) = LOWER(:identificador_email)
                     OR LOWER(TRIM(COALESCE(v.usuario, ''))) = LOWER(:identificador_usuario)
                  ORDER BY CASE WHEN LOWER(v.tipo_usuario) = 'empleado' THEN 0 ELSE 1 END
                  LIMIT 1"
        );
        $sentencia->bindValue(':identificador_email', $identificadorLimpio, PDO::PARAM_STR);
        $sentencia->bindValue(':identificador_usuario', $identificadorLimpio, PDO::PARAM_STR);
        $sentencia->execute();

        $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $usuario !== false ? $usuario : null;
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

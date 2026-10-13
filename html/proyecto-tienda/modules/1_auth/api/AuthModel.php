<?php
/**
 * modules/1_auth/api/AuthModel.php
 *
 * Modelo de autenticación actualizado para la base de datos 'tortilleria'.
 */

class AuthModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerInstancia()->obtenerConexion();
    }

    /**
     * Busca un empleado en la base de datos por su correo electrónico.
     * 
     * @param string $correo Correo electrónico ingresado.
     * @return array|null Fila del empleado con su rol o null si no existe.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        // Consulta adaptada a las tablas 'empleado' y 'rol' de tu base de datos
        $sentencia = $this->pdo->prepare(
            'SELECT e.id_empleado AS id, 
                    e.nombre, 
                    e.apellidos, 
                    e.email AS correo, 
                    e.password_hash AS contrasena_hash, 
                    r.rol AS rol, 
                    e.activo
               FROM empleado e
               JOIN rol r ON e.id_rol = r.id_rol
              WHERE e.email = :correo
              LIMIT 1'
        );

        $sentencia->bindValue(':correo', trim($correo), PDO::PARAM_STR);
        $sentencia->execute();

        $usuario = $sentencia->fetch();
        return $usuario !== false ? $usuario : null;
    }
}

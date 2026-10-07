<?php
/**
 * modules/1_auth/api/AuthModel.php
 *
 * Modelo de autenticación (HU1).
 * Encapsula todas las consultas SQL relacionadas con la autenticación de usuarios.
 * Todas las consultas usan PDO con prepared statements (AGENTS.md).
 *
 * Métodos disponibles:
 *  - buscarPorCorreo(string $correo): ?array
 */

class AuthModel
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
        // Obtener la única instancia de la conexión PDO
        $this->pdo = Database::obtenerInstancia()->obtenerConexion();
    }

    /**
     * Busca un usuario en la base de datos por su correo electrónico.
     * Devuelve todos los campos del usuario si existe, o null si no se encuentra.
     *
     * @param string $correo Correo electrónico ingresado por el usuario.
     * @return array|null    Fila del usuario (asociativa) o null si no existe.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        // Consulta preparada: nunca concatenar variables en SQL (AGENTS.md)
        $sentencia = $this->pdo->prepare(
            'SELECT id, nombre, correo, contrasena_hash, rol, activo
               FROM usuarios
              WHERE correo = :correo
              LIMIT 1'
        );

        // Vincular el parámetro de forma segura
        $sentencia->bindValue(':correo', trim($correo), PDO::PARAM_STR);
        $sentencia->execute();

        // Devolver la fila encontrada o null si no existe
        $usuario = $sentencia->fetch();
        return $usuario !== false ? $usuario : null;
    }
}

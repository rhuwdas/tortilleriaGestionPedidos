<?php
/**
 * modules/1_auth/api/ClienteModel.php
 *
 * Modelo para la gestión y persistencia de cuentas de cliente en la base de datos 'tortilleria'.
 * Responsabilidades:
 *  - Verificar la unicidad del correo electrónico del cliente con consultas preparadas PDO.
 *  - Registrar nuevos clientes asegurando el almacenamiento seguro de contraseñas (password_hash).
 *  - Registrar la dirección de entrega opcional vinculada al cliente dentro de una transacción.
 *
 * Cumple con las restricciones de agents.md y database.md:
 *  - Sin consultas directas ni concatenación SQL.
 *  - Nombres completos en español y tipos estrictos de PHP.
 */

require_once __DIR__ . '/../../../core/Database.php';

class ClienteModel
{
    /** @var PDO Conexión activa a la base de datos */
    private PDO $conexionBaseDatos;

    public function __construct()
    {
        $this->conexionBaseDatos = Database::obtenerInstancia()->obtenerConexion();
    }

    /**
     * Verifica si un correo electrónico ya se encuentra registrado en la tabla cliente.
     *
     * @param string $correo Correo electrónico a verificar.
     * @return bool True si ya existe, false si está disponible.
     */
    public function existeCorreo(string $correo): bool
    {
        $consulta = $this->conexionBaseDatos->prepare(
            'SELECT COUNT(*) FROM cliente WHERE LOWER(email) = LOWER(:email)'
        );
        $consulta->bindValue(':email', trim($correo), PDO::PARAM_STR);
        $consulta->execute();

        return (int)$consulta->fetchColumn() > 0;
    }

    /**
     * Registra un nuevo cliente y opcionalmente su dirección de entrega en una sola transacción.
     *
     * @param array $datosCliente Datos personales y credenciales del cliente.
     * @param array|null $datosDireccion Datos de la dirección opcional o null si no se proporcionó.
     * @return int Identificador único del cliente recién registrado.
     * @throws Exception Si ocurre un fallo durante la inserción en base de datos.
     */
    public function registrarCliente(array $datosCliente, ?array $datosDireccion = null): int
    {
        // Iniciar transacción para garantizar atomicidad entre cliente y dirección
        $this->conexionBaseDatos->beginTransaction();

        try {
            // Generar hash seguro de la contraseña
            $contrasenaHash = password_hash($datosCliente['contrasena'], PASSWORD_BCRYPT);

            // Determinar nombre de usuario si no se suministra explícitamente (usar correo)
            $nombreUsuario = !empty($datosCliente['usuario']) ? trim($datosCliente['usuario']) : trim($datosCliente['email']);

            // Insertar registro de cliente
            $sentenciaCliente = $this->conexionBaseDatos->prepare(
                'INSERT INTO cliente (nombre, apellidos, razon_social, telefono, email, usuario, password_hash, activo)
                 VALUES (:nombre, :apellidos, :razon_social, :telefono, :email, :usuario, :password_hash, 1)'
            );

            $sentenciaCliente->bindValue(':nombre', trim($datosCliente['nombre']), PDO::PARAM_STR);
            $sentenciaCliente->bindValue(':apellidos', trim($datosCliente['apellidos']), PDO::PARAM_STR);
            $sentenciaCliente->bindValue(':razon_social', $datosCliente['razon_social'] ?? null, PDO::PARAM_STR);
            $sentenciaCliente->bindValue(':telefono', trim($datosCliente['telefono']), PDO::PARAM_STR);
            $sentenciaCliente->bindValue(':email', trim($datosCliente['email']), PDO::PARAM_STR);
            $sentenciaCliente->bindValue(':usuario', $nombreUsuario, PDO::PARAM_STR);
            $sentenciaCliente->bindValue(':password_hash', $contrasenaHash, PDO::PARAM_STR);
            $sentenciaCliente->execute();

            $idClienteGenerado = (int)$this->conexionBaseDatos->lastInsertId();

            // Si se suministraron datos de dirección opcional, registrarlos vinculados al cliente
            if ($datosDireccion !== null && !empty(trim($datosDireccion['calle'] ?? ''))) {
                $sentenciaDireccion = $this->conexionBaseDatos->prepare(
                    'INSERT INTO direccion (id_cliente, calle, colonia, municipio, codigo_postal, referencia)
                     VALUES (:id_cliente, :calle, :colonia, :municipio, :codigo_postal, :referencia)'
                );

                $sentenciaDireccion->bindValue(':id_cliente', $idClienteGenerado, PDO::PARAM_INT);
                $sentenciaDireccion->bindValue(':calle', trim($datosDireccion['calle']), PDO::PARAM_STR);
                $sentenciaDireccion->bindValue(':colonia', trim($datosDireccion['colonia'] ?? ''), PDO::PARAM_STR);
                $sentenciaDireccion->bindValue(':municipio', trim($datosDireccion['municipio'] ?? ''), PDO::PARAM_STR);
                $sentenciaDireccion->bindValue(':codigo_postal', trim($datosDireccion['codigo_postal'] ?? ''), PDO::PARAM_STR);
                $sentenciaDireccion->bindValue(':referencia', trim($datosDireccion['referencia'] ?? ''), PDO::PARAM_STR);
                $sentenciaDireccion->execute();
            }

            // Confirmar transacción
            $this->conexionBaseDatos->commit();

            return $idClienteGenerado;

        } catch (Exception $excepcion) {
            // Revertir cambios en caso de cualquier error
            if ($this->conexionBaseDatos->inTransaction()) {
                $this->conexionBaseDatos->rollBack();
            }
            throw $excepcion;
        }
    }

    /**
     * Busca un cliente activo por su correo electrónico.
     *
     * @param string $correo Correo electrónico del cliente.
     * @return array|null Registro del cliente o null si no se encuentra.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        $sentencia = $this->conexionBaseDatos->prepare(
            'SELECT id_cliente, nombre, apellidos, telefono, email, usuario, password_hash, activo
             FROM cliente
             WHERE LOWER(email) = LOWER(:email) AND activo = 1
             LIMIT 1'
        );
        $sentencia->bindValue(':email', trim($correo), PDO::PARAM_STR);
        $sentencia->execute();

        $cliente = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $cliente !== false ? $cliente : null;
    }
}

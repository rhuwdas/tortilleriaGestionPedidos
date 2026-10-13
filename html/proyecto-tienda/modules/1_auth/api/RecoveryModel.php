<?php
/**
 * modules/1_auth/api/RecoveryModel.php
 *
 * Modelo de recuperación de contraseña (HU2).
 * Gestiona todas las operaciones de base de datos relacionadas con el
 * flujo de recuperación: búsqueda de correo, inserción del código OTP,
 * validación y actualización de contraseña.
 *
 * Tabla principal: recuperacion_password
 *   - id_recuperacion  INT AUTO_INCREMENT PK
 *   - email            VARCHAR(120)
 *   - codigo           CHAR(6)
 *   - expira_en        DATETIME
 *   - usado            TINYINT(1)  DEFAULT 0
 *   - creado_en        DATETIME    DEFAULT CURRENT_TIMESTAMP
 *
 * Restricciones (AGENTS.md):
 *  - Sin Composer ni frameworks externos.
 *  - Todas las consultas con PDO prepared statements.
 *  - Contraseñas solo con password_hash() / password_verify().
 */
class RecoveryModel
{
    /** @var PDO Conexión PDO activa */
    private PDO $pdo;

    /** Minutos de vigencia del código OTP (requisito HU2) */
    private const MINUTOS_EXPIRACION = 10;

    public function __construct()
    {
        $this->pdo = Database::obtenerInstancia()->obtenerConexion();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Verificación de correo
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifica si el correo existe en la tabla empleado o cliente.
     *
     * @param string $correo Correo electrónico a buscar.
     * @return bool true si existe en alguna de las dos tablas.
     */
    public function correoRegistrado(string $correo): bool
    {
        // Buscar en empleado
        $sentencia = $this->pdo->prepare(
            'SELECT COUNT(*) FROM empleado WHERE email = :correo LIMIT 1'
        );
        $sentencia->bindValue(':correo', $correo, PDO::PARAM_STR);
        $sentencia->execute();
        if ((int)$sentencia->fetchColumn() > 0) {
            return true;
        }

        // Buscar en cliente
        $sentencia = $this->pdo->prepare(
            'SELECT COUNT(*) FROM cliente WHERE email = :correo LIMIT 1'
        );
        $sentencia->bindValue(':correo', $correo, PDO::PARAM_STR);
        $sentencia->execute();
        return (int)$sentencia->fetchColumn() > 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generación y almacenamiento del código OTP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Genera un código OTP de 6 dígitos numéricos aleatorios,
     * lo almacena en recuperacion_password con expiración de 10 minutos
     * y devuelve el código generado.
     *
     * Se invalidan los códigos anteriores vigentes del mismo correo
     * para evitar múltiples códigos activos simultáneos.
     *
     * @param string $correo Correo del usuario que solicita recuperación.
     * @return string Código OTP de 6 dígitos.
     */
    public function generarYGuardarCodigo(string $correo): string
    {
        // Invalidar códigos anteriores vigentes del mismo correo
        $this->invalidarCodigosAnteriores($correo);

        // Generar código OTP de exactamente 6 dígitos
        $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Calcular fecha/hora de expiración (ahora + 10 minutos)
        $expiraEn = date('Y-m-d H:i:s', strtotime('+' . self::MINUTOS_EXPIRACION . ' minutes'));

        // Insertar registro en la tabla recuperacion_password
        $sentencia = $this->pdo->prepare(
            'INSERT INTO recuperacion_password (email, codigo, expira_en, usado)
             VALUES (:correo, :codigo, :expira_en, 0)'
        );
        $sentencia->bindValue(':correo',    $correo,   PDO::PARAM_STR);
        $sentencia->bindValue(':codigo',    $codigo,   PDO::PARAM_STR);
        $sentencia->bindValue(':expira_en', $expiraEn, PDO::PARAM_STR);
        $sentencia->execute();

        return $codigo;
    }

    /**
     * Marca como usados todos los códigos anteriores vigentes del correo dado.
     * Evita que coexistan múltiples códigos activos para el mismo usuario.
     *
     * @param string $correo Correo del usuario.
     * @return void
     */
    private function invalidarCodigosAnteriores(string $correo): void
    {
        $sentencia = $this->pdo->prepare(
            'UPDATE recuperacion_password
                SET usado = 1
              WHERE email = :correo
                AND usado = 0
                AND expira_en > NOW()'
        );
        $sentencia->bindValue(':correo', $correo, PDO::PARAM_STR);
        $sentencia->execute();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación del código OTP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Valida que el código OTP ingresado sea correcto, esté vigente y no haya sido usado.
     * Si es válido, lo marca como usado para evitar reutilización.
     *
     * @param string $correo Correo del usuario.
     * @param string $codigo Código OTP de 6 dígitos ingresado por el usuario.
     * @return bool true si el código es válido; false si expiró, es incorrecto o ya fue usado.
     */
    public function validarCodigo(string $correo, string $codigo): bool
    {
        // Buscar registro vigente, correcto y no usado
        $sentencia = $this->pdo->prepare(
            'SELECT id_recuperacion
               FROM recuperacion_password
              WHERE email     = :correo
                AND codigo    = :codigo
                AND usado     = 0
                AND expira_en > NOW()
              ORDER BY creado_en DESC
              LIMIT 1'
        );
        $sentencia->bindValue(':correo', $correo, PDO::PARAM_STR);
        $sentencia->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        if ($fila === false) {
            // Código no encontrado, expirado o ya usado
            return false;
        }

        // Marcar el código como usado para evitar reutilización
        $this->marcarCodigoUsado((int)$fila['id_recuperacion']);
        return true;
    }

    /**
     * Marca un código específico como usado por su ID.
     *
     * @param int $idRecuperacion ID del registro en recuperacion_password.
     * @return void
     */
    private function marcarCodigoUsado(int $idRecuperacion): void
    {
        $sentencia = $this->pdo->prepare(
            'UPDATE recuperacion_password SET usado = 1 WHERE id_recuperacion = :id'
        );
        $sentencia->bindValue(':id', $idRecuperacion, PDO::PARAM_INT);
        $sentencia->execute();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Actualización de contraseña
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Actualiza la contraseña del usuario (empleado o cliente) con hash bcrypt.
     * Busca primero en empleado; si no existe, busca en cliente.
     *
     * @param string $correo         Correo del usuario.
     * @param string $nuevaContrasena Contraseña nueva en texto plano (se hashea aquí).
     * @return bool true si se actualizó al menos un registro.
     */
    public function actualizarContrasena(string $correo, string $nuevaContrasena): bool
    {
        // Generar hash seguro con bcrypt (costo por defecto = 10)
        $hash = password_hash($nuevaContrasena, PASSWORD_BCRYPT);

        $actualizado = false;

        // Intentar actualizar en empleado
        $sentenciaEmpleado = $this->pdo->prepare(
            'UPDATE empleado SET password_hash = :hash WHERE email = :correo'
        );
        $sentenciaEmpleado->bindValue(':hash',   $hash,   PDO::PARAM_STR);
        $sentenciaEmpleado->bindValue(':correo', $correo, PDO::PARAM_STR);
        $sentenciaEmpleado->execute();
        if ($sentenciaEmpleado->rowCount() > 0) {
            $actualizado = true;
        }

        // Intentar actualizar en cliente (puede existir en ambas tablas)
        $sentenciaCliente = $this->pdo->prepare(
            'UPDATE cliente SET password_hash = :hash WHERE email = :correo'
        );
        $sentenciaCliente->bindValue(':hash',   $hash,   PDO::PARAM_STR);
        $sentenciaCliente->bindValue(':correo', $correo, PDO::PARAM_STR);
        $sentenciaCliente->execute();
        if ($sentenciaCliente->rowCount() > 0) {
            $actualizado = true;
        }

        return $actualizado;
    }
}

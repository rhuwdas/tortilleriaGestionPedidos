<?php
/**
 * modules/1_auth/api/MailService.php
 *
 * Servicio de envío de correo electrónico por SMTP (HU2).
 * Implementa el protocolo SMTP con STARTTLS usando sockets nativos de PHP
 * sin necesidad de Composer ni PHPMailer.
 *
 * Lee las credenciales SMTP desde el archivo .env:
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM, SMTP_FROM_NAME
 *
 * Restricciones (AGENTS.md):
 *  - Sin Composer ni librerías externas.
 *  - Credenciales leídas desde $_ENV (ya cargado por Database.php).
 */
class MailService
{
    /** @var string Servidor SMTP */
    private string $host;

    /** @var int Puerto SMTP (587 para STARTTLS) */
    private int $puerto;

    /** @var string Usuario SMTP (correo institucional) */
    private string $usuario;

    /** @var string Contraseña SMTP */
    private string $contrasena;

    /** @var string Correo remitente */
    private string $remitente;

    /** @var string Nombre del remitente */
    private string $nombreRemitente;

    /**
     * Constructor: carga la configuración SMTP desde las variables de entorno.
     * Requiere que Database.php haya cargado el .env previamente.
     */
    public function __construct()
    {
        $this->host            = $_ENV['SMTP_HOST']      ?? 'smtp.gmail.com';
        $this->puerto          = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->usuario         = $_ENV['SMTP_USER']      ?? '';
        $this->contrasena      = $_ENV['SMTP_PASS']      ?? '';
        $this->remitente       = $_ENV['SMTP_FROM']      ?? $this->usuario;
        $this->nombreRemitente = $_ENV['SMTP_FROM_NAME'] ?? 'Tortillería Sistema';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Método público principal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía el código OTP al correo del usuario.
     *
     * @param string $destinatario Correo del usuario que solicita recuperación.
     * @param string $codigo       Código OTP de 6 dígitos.
     * @return bool true si el correo se envió correctamente.
     * @throws RuntimeException Si falla la conexión o autenticación SMTP.
     */
    public function enviarCodigoRecuperacion(string $destinatario, string $codigo): bool
    {
        $asunto  = 'Código de recuperación — Tortillería';
        $cuerpo  = $this->construirCuerpoCorreo($codigo);
        return $this->enviarSmtp($destinatario, $asunto, $cuerpo);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Construcción del cuerpo del correo
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construye el cuerpo HTML del correo con el código OTP.
     *
     * @param string $codigo Código OTP de 6 dígitos.
     * @return string HTML del correo.
     */
    private function construirCuerpoCorreo(string $codigo): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="UTF-8"></head>
        <body style="font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px;">
          <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:10px;
                      padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
            <h1 style="font-size:1.4rem;color:#1a56a0;margin-bottom:8px;">
              🫓 Tortillería — Recuperación de Contraseña
            </h1>
            <p style="color:#2c3e50;font-size:0.95rem;line-height:1.5;">
              Has solicitado recuperar tu contraseña. Utiliza el siguiente código de
              verificación para continuar:
            </p>
            <div style="text-align:center;margin:24px 0;">
              <span style="display:inline-block;font-size:2.4rem;font-weight:bold;
                           letter-spacing:10px;color:#1a56a0;background:#e8eef6;
                           padding:16px 28px;border-radius:8px;">{$codigo}</span>
            </div>
            <p style="color:#6c757d;font-size:0.85rem;line-height:1.5;">
              Este código es válido por <strong>10 minutos</strong>.<br>
              Si no solicitaste este cambio, ignora este mensaje.
            </p>
            <hr style="border:none;border-top:1px solid #e0e0e0;margin:20px 0;">
            <p style="color:#aaa;font-size:0.75rem;text-align:center;margin:0;">
              Sistema de Gestión — Tortillería &copy; 2026
            </p>
          </div>
        </body>
        </html>
        HTML;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Protocolo SMTP con STARTTLS (sin Composer)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Establece conexión SMTP, negocia STARTTLS, se autentica y envía el mensaje.
     *
     * @param string $destinatario Correo del destinatario.
     * @param string $asunto       Asunto del correo.
     * @param string $cuerpoHtml   Cuerpo HTML del correo.
     * @return bool true si el servidor SMTP aceptó el mensaje.
     * @throws RuntimeException En caso de error de conexión, TLS o autenticación.
     */
    private function enviarSmtp(string $destinatario, string $asunto, string $cuerpoHtml): bool
    {
        // 1. Abrir socket TCP al servidor SMTP
        $socket = @fsockopen($this->host, $this->puerto, $codigoError, $mensajeError, 15);
        if ($socket === false) {
            throw new RuntimeException(
                "MailService: No se pudo conectar a {$this->host}:{$this->puerto} — {$mensajeError}"
            );
        }

        // 2. Leer el saludo inicial del servidor (220)
        $this->leerRespuesta($socket, 220);

        // 3. EHLO — presentarse al servidor
        $this->enviarComando($socket, "EHLO {$this->host}", 250);

        // 4. STARTTLS — iniciar cifrado TLS
        $this->enviarComando($socket, 'STARTTLS', 220);

        // 5. Actualizar el stream a TLS (crypto)
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            throw new RuntimeException('MailService: No se pudo establecer la conexión TLS.');
        }

        // 6. Segundo EHLO después de TLS
        $this->enviarComando($socket, "EHLO {$this->host}", 250);

        // 7. AUTH LOGIN — autenticación con base64
        $this->enviarComando($socket, 'AUTH LOGIN', 334);
        $this->enviarComando($socket, base64_encode($this->usuario), 334);
        $this->enviarComando($socket, base64_encode($this->contrasena), 235);

        // 8. MAIL FROM — declarar remitente
        $this->enviarComando($socket, "MAIL FROM:<{$this->remitente}>", 250);

        // 9. RCPT TO — declarar destinatario
        $this->enviarComando($socket, "RCPT TO:<{$destinatario}>", 250);

        // 10. DATA — inicio del cuerpo del mensaje
        $this->enviarComando($socket, 'DATA', 354);

        // 11. Encabezados y cuerpo MIME
        $nombreRemitenteSeguro = $this->codificarEncabezado($this->nombreRemitente);
        $asuntoSeguro          = $this->codificarEncabezado($asunto);
        $fechaRfc               = date('r');
        $idMensaje              = '<' . time() . '.' . rand(1000, 9999) . '@' . $this->host . '>';

        $mensaje  = "Date: {$fechaRfc}\r\n";
        $mensaje .= "From: {$nombreRemitenteSeguro} <{$this->remitente}>\r\n";
        $mensaje .= "To: <{$destinatario}>\r\n";
        $mensaje .= "Subject: {$asuntoSeguro}\r\n";
        $mensaje .= "Message-ID: {$idMensaje}\r\n";
        $mensaje .= "MIME-Version: 1.0\r\n";
        $mensaje .= "Content-Type: text/html; charset=UTF-8\r\n";
        $mensaje .= "Content-Transfer-Encoding: base64\r\n";
        $mensaje .= "\r\n";
        $mensaje .= chunk_split(base64_encode($cuerpoHtml));
        $mensaje .= "\r\n.\r\n"; // Fin del bloque DATA

        fwrite($socket, $mensaje);
        $this->leerRespuesta($socket, 250);

        // 12. QUIT — cerrar sesión SMTP
        $this->enviarComando($socket, 'QUIT', 221);
        fclose($socket);

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utilidades SMTP
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía un comando SMTP y verifica que la respuesta tenga el código esperado.
     *
     * @param resource $socket         Socket TCP abierto.
     * @param string   $comando        Comando SMTP a enviar.
     * @param int      $codigoEsperado Código de respuesta SMTP esperado.
     * @return string Respuesta completa del servidor.
     * @throws RuntimeException Si el código de respuesta no coincide con el esperado.
     */
    private function enviarComando($socket, string $comando, int $codigoEsperado): string
    {
        fwrite($socket, $comando . "\r\n");
        return $this->leerRespuesta($socket, $codigoEsperado);
    }

    /**
     * Lee la respuesta del servidor SMTP y valida el código de estado.
     *
     * @param resource $socket         Socket TCP abierto.
     * @param int      $codigoEsperado Código SMTP esperado (ej. 250, 334, 220...).
     * @return string Línea de respuesta del servidor.
     * @throws RuntimeException Si el código recibido no coincide con el esperado.
     */
    private function leerRespuesta($socket, int $codigoEsperado): string
    {
        $respuesta = '';
        // El servidor puede enviar respuestas multi-línea (código + guion = continúa)
        while ($linea = fgets($socket, 515)) {
            $respuesta .= $linea;
            // La respuesta termina cuando el 4° carácter es un espacio (no guion)
            if (substr($linea, 3, 1) === ' ') {
                break;
            }
        }

        $codigoRecibido = (int)substr($respuesta, 0, 3);
        if ($codigoRecibido !== $codigoEsperado) {
            throw new RuntimeException(
                "MailService: Respuesta inesperada del servidor SMTP. " .
                "Esperado: {$codigoEsperado}, Recibido: {$codigoRecibido}. Respuesta: {$respuesta}"
            );
        }

        return $respuesta;
    }

    /**
     * Codifica un encabezado de correo en Base64 (RFC 2047) para soporte de UTF-8.
     *
     * @param string $texto Texto a codificar.
     * @return string Encabezado codificado en formato =?UTF-8?B?...?=
     */
    private function codificarEncabezado(string $texto): string
    {
        return '=?UTF-8?B?' . base64_encode($texto) . '?=';
    }
}

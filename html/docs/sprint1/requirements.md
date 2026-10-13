# Requisitos del Sprint 1: Acceso, Recuperación y Catálogo Web

## Módulos y Roles Involucrados
- **Roles:** Administrador, Cajero, Cliente, Usuario general.
- **Módulos:** `1_auth` (Acceso y Recuperación), `2_catalog` (Catálogo en Sitio Web).

---

## Requisito 1 (HU1): Inicio de sesión al Sistema
**Historia:** Como usuario del sistema, requiero credenciales para ingresar al sistema de forma segura.

### Requisitos EARS y Criterios de Aceptación:
1. **[Event-driven]** Cuando el usuario ingresa un usuario/correo y contraseña correctos, el sistema valida el hash con `password_verify()` y concede acceso redirigiendo según su rol correspondiente.
2. **[Event-driven]** Cuando el usuario introduce credenciales incorrectas, el sistema debe mostrar el mensaje de error: *"Usuario o contraseña incorrectos"*.
3. **[Event-driven]** Cuando algún campo obligatorio se encuentra vacío, el sistema debe impedir el envío del formulario y marcar los campos faltantes.
4. **[State-driven]** Mientras la cuenta del usuario permanezca desactivada (`activo = 0`), el sistema debe impedir el acceso de forma estricta.
5. **[Event-driven]** Cuando una persona sin una sesión activa intente acceder a rutas protegidas internas, el sistema debe redirigirla automáticamente a la pantalla de inicio de sesión (`login.php`).

---

## Requisito 2 (HU2): Recuperar Contraseña
**Historia:** Como usuario del sistema, quiero recuperar mi contraseña para reingresar al sistema en caso de olvido.
## Requisito 2 (HU2): Recuperar Contraseña
**Historia:** Como usuario del sistema, quiero recuperar mi contraseña para reingresar al sistema en caso de olvido.

### Criterios de Aceptación:
1. El código de verificación generado consta de exactamente 6 dígitos numéricos aleatorios y se vincula al correo registrado del usuario.
2. **[Backend / Envío de Correo]** El sistema debe procesar la solicitud generando el código, registrándolo en la tabla correspondiente de la base de datos con un tiempo de expiración estricto de 10 minutos, y realizando el envío de la notificación por correo electronico.
3. La nueva contraseña ingresada debe cumplir con un mínimo de 8 caracteres, incluyendo mayúsculas y números.
4. **Excepciones:** 
   - Si el correo no existe en la base de datos, el sistema muestra: *"El correo no está registrado"*.
   - Si el código ha caducado o es incorrecto, muestra: *"El código ha caducado, solicite uno nuevo"* o *"El código es inválido"*.

---

## Requisito 3 (HU3): Catálogo en Sitio Web
**Historia:** Como cliente, quiero ver el catálogo público de la tortillería para conocer los productos que se ofrecen.

### Criterios de Aceptación:
1. El sitio web mostrará únicamente los productos que se encuentren marcados como disponibles (`disponible = 1`) en la base de datos.
2. Cada tarjeta de producto (card) debe mostrar su precio vigente por kilo o pieza, imagen descriptiva y nombre oficial.
3. La interfaz debe ser completamente adaptable (responsive) a teléfonos móviles, tabletas y computadoras.
4. Incluye una sección informativa con los horarios de atención, teléfono, dirección y mapa del local.
Tareas del Sprint 1: Acceso, Recuperación y Catálogo (HU1, HU2 y HU3)

Reglas: hacer una tarea a la vez, probarla en el navegador y hacer commit antes de pasar a la siguiente siguiendo la nomenclatura de commits y ramas (sprt1ta1hu1-...).

1. HU1: Acceso al Sistema

    [x] 1.1 Diseñar y maquetar la interfaz de la pantalla de inicio de sesión incluyendo el logo de la tortillería, campos de usuario/correo, contraseña, y el botón interactivo de visibilidad (mostrar/ocultar caracteres).

    [x] 1.2 Programar la validación en el cliente (JavaScript) para asegurar que el botón de "Iniciar Sesión" permanezca deshabilitado o bloqueado hasta que ambos campos obligatorios estén completamente llenos.

    [x] 1.3 Implementar en el backend la lógica de autenticación segura utilizando consultas preparadas PDO y verificación de contraseñas cifradas (password_verify).

    [x] 1.4 Programar el manejo de excepciones y flujos alternos para mostrar mensajes claros de error ("Usuario o contraseña incorrectos" o "Campo obligatorio").

    [x] 1.5 Configurar el sistema de sesiones y la redirección automática basada estrictamente en el rol del usuario para desplegar únicamente el menú correspondiente.

    [x] 1.6 Realizar pruebas unitarias y de integración en el navegador validando campos vacíos, visibilidad de contraseña, credenciales erróneas y redirección por rol.

2. HU2: Recuperar Contraseña

    [x] 2.1 Análisis de requerimientos de seguridad, encriptación, vigencia del token de 10 minutos y validación de correo institucional (`l24920200@voaxaca.tecnm.mx`).
    
    [x] 2.2 Programar la lógica backend para la generación del código aleatorio de 6 dígitos y su almacenamiento temporal con expiración en la tabla `recuperacion_password`.

    [x] 2.3 **Programar la configuración y el servicio de envío del código de recuperación por correo electrónico** (utilizando las credenciales SMTP institucionales del `.env` sin requerir Composer).

    [x] 2.4 Diseño de la interfaz gráfica y vistas del flujo de recuperación en la carpeta `modules/1_auth/ui/` (pantalla de solicitud e ingreso de código).

    [x] 2.5 Implementación de la lógica de validación de código de 6 dígitos, manejo de excepciones (código expirado o correo no registrado) y establecimiento de la nueva contraseña con cifrado seguro (`password_hash`).

    [x] 2.6 Pruebas de integración de todo el flujo de recuperación con la Base de Datos y simulación de envío de correo en entorno local.

3. HU3: Catálogo Web

    [ ] 3.1 Diseñar la interfaz visual del Catálogo Web (cuadrícula/lista de productos, tarjetas e imágenes).

    [ ] 3.2 Programar la consulta a la base de datos para obtener los productos y su precio.

    [ ] 3.3 Implementar la funcionalidad de búsqueda y filtrado por categoría/disponibilidad.

    [ ] 3.4 Desarrollar la vista modular o ventana modal para "Ver detalle del producto" (descripción, fotos, precio).

    [ ] 3.5 Programar la alerta/mensaje informativo para el flujo alterno (Sin disponibilidad / Agotado).

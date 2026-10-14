# proyecto: Sistema Web y Punto de Venta - Tortillería

## descripcion: Sistema web y punto de venta para la gestión de pedidos, catálogo y control interno de la tortillería.

## comandos
- docker compose up -d (para levantar los contenedores)

## stack (tecnologias)
- backend: PHP (con PDO)
- base de datos: MySQL (en contenedor Docker, puerto 3303, base de datos `tortilleria`)
- frontend: HTML5, CSS3, JavaScript (Vanilla ES6+) y Bootstrap v5

## convenciones (como se debe escribir el código)
- Arquitectura modular basada en carpetas `api/` (backend/endpoints) y `ui/` (vistas e interfaces de usuario).
- Consultas SQL siempre parametrizadas utilizando PDO con consultas preparadas para evitar inyección SQL.
- Encriptación y verificación de contraseñas obligatoriamente mediante `password_hash()` y `password_verify()`. Nunca almacenar contraseñas en texto plano.

### Frontend
- El JavaScript debe escribirse en archivos independientes dentro de las subcarpetas `ui/` de cada módulo (ej. `authService.js`).
- Prohibido insertar JavaScript en línea dentro de archivos PHP o HTML.
- Cargar los scripts externos utilizando el atributo `defer` para garantizar que el DOM se procese completamente antes de su ejecución.

## Nomenclatura de Commits
- Todo commit debe seguir estrictamente el formato: `s<Sprint>t<Tarea>hu<Historia>` (Ejemplo: `s1t1hu1` para Sprint 1, Tarea 1, Historia de Usuario 1).

## Reglas
- Implementar estrictamente lo solicitado en las tareas del sprint.
- No modificar archivos de la carpeta `docs/` ni este archivo `agents.md`.
- Seguir los requisitos y el diseño de la carpeta del sprint correspondiente.
- No utilizar Composer dentro del proyecto.
- No crear la base de datos mediante código, ya que se encuentra preconfigurada de manera independiente en el SGBD de Docker.

## Nomenclatura de Código
- Clases: PascalCase (`UsuarioController`)
- Archivos de clases: Coincidente con el nombre de la clase (`UsuarioController.php`)
- Métodos y variables: camelCase (`obtenerUsuario`)
- Constantes: UPPER_SNAKE_CASE (`MAX_INTENTOS_LOGIN`)
- Tablas y columnas de MySQL: snake_case (`fecha_creacion`)
- Nombres de funciones, variables y archivos completamente en español, sin abreviaturas.

## Documentación
- Comentarios explicativos en español en líneas clave de código.
- Actualizar el README en caso de incorporar un módulo de sistema nuevo.
# Tortillería — Sistema de Gestión de Pedidos

Sistema web modular para la gestión integral de una tortillería: autenticación de usuarios, catálogo de productos, e-commerce, punto de venta (POS), logística de entregas, caja y reportes.

**Stack:** PHP 8.2 (Vanilla, MVC estricto, sin Composer) · MySQL/MariaDB · HTML5 · CSS3 · Bootstrap 5 · JavaScript Vanilla  
**Entorno:** Docker Compose — stack LAMP (Apache + MySQL + phpMyAdmin)

---

## Tabla de contenidos

1. [Estructura del repositorio](#estructura-del-repositorio)
2. [Entorno Docker](#entorno-docker)
3. [Módulos](#módulos)
4. [Núcleo (`core/`)](#núcleo-core)
5. [Configuración (`.env`)](#configuración-env)
6. [Convenciones de desarrollo](#convenciones-de-desarrollo)
7. [Seguridad](#seguridad)
8. [Sprint 1 — Estado de avance](#sprint-1--estado-de-avance)

---

## Estructura del repositorio

```
tortilleriaGestionPedidos/
├── compose.yml             # Docker Compose: web (Apache+PHP), db (MySQL), phpmyadmin
├── Dockerfile              # Imagen PHP 8.2 Apache con extensiones pdo_mysql y mysqli
├── .gitignore
├── README.md               # Este archivo
└── html/
    ├── agents.md           # Directivas operativas para agentes de IA
    ├── docs/
    │   ├── sprint1/
    │   │   ├── requirements.md   # Requisitos funcionales del Sprint 1
    │   │   ├── task.md           # Tareas y responsables del Sprint 1
    │   │   └── desing.md         # Especificaciones de diseño (HU1, HU2, HU3)
    │   ├── sprint2/
    │   └── sprint3/
    └── proyecto-tienda/          # Raíz del servidor web (montada en /var/www/html)
        ├── index.php             # Punto de entrada; inicia sesión y despacha el Router
        ├── .env                  # Variables de entorno (credenciales BD) — NO subir al repo
        ├── core/
        │   ├── Database.php      # Singleton PDO — conexión a la base de datos
        │   ├── Router.php        # Enrutador central (rutas públicas y protegidas)
        │   └── ApiManager.js     # Cliente HTTP centralizado (Fetch API)
        └── modules/
            ├── 1_auth/           # HU1 & HU2: Login, logout y recuperación de contraseña
            ├── 2_catalog/        # HU3: Catálogo de productos
            ├── 3_ecommerce/      # Pedidos en línea y seguimiento
            ├── 4_pos/            # Punto de venta presencial
            ├── 5_logistics/      # Rutas de entrega y confirmación
            ├── 6_cash/           # Caja (apertura/cierre)
            └── 7_reports/        # Reportes
```

Cada módulo sigue la misma convención interna:

```
módulo/
├── ui/     # Vistas PHP + JS de presentación
└── api/    # Endpoints PHP + Modelo (PDO)
```

---

## Entorno Docker

El archivo `compose.yml` levanta tres servicios:

| Servicio | Imagen | Puerto local | Descripción |
|---|---|---|---|
| `web` | Dockerfile (PHP 8.2 Apache) | `80` | Servidor Apache con PHP 8.2, extensiones `pdo_mysql` y `mysqli`, `mod_rewrite` habilitado |
| `db` | `mysql:latest` | `3303` | Base de datos MySQL — base `tortilleria`, usuario `root` |
| `phpmyadmin` | `phpmyadmin:latest` | `8080` | Interfaz gráfica para administrar la base de datos |

```bash
# Encender contenedores
docker compose up -d

# Apagar contenedores
docker compose down

# Ver logs en tiempo real
docker compose logs -f

# Acceder a la terminal del contenedor web
docker compose exec web bash
```

> La carpeta `./html` se monta en `/var/www/html` del contenedor `web`.  
> La base de datos ya existe en el SGBD — no se crea desde el código.

---

## Módulos

### 1. Autenticación (`1_auth`) — HU1 & HU2

| Archivo | Descripción |
|---|---|
| `ui/login.php` | Formulario de inicio de sesión |
| `ui/recuperar.php` | Formulario de recuperación de contraseña |
| `ui/authService.js` | Lógica JS del login (usa `ApiManager`) |
| `ui/recoveryService.js` | Lógica JS de recuperación de contraseña |
| `api/login.php` | Endpoint POST: valida credenciales, crea sesión, redirige por rol |
| `api/logout.php` | Endpoint: destruye la sesión activa |
| `api/recovery.php` | Endpoint: gestiona la recuperación de contraseña |
| `api/AuthModel.php` | Modelo: `buscarPorCorreo()` con prepared statement |

**Flujo de login (HU1):**
1. `authService.js` envía `{ correo, contrasena }` vía `ApiManager.publicar()`.
2. `login.php` valida método HTTP (`POST`) y encabezado `X-Requested-With: XMLHttpRequest`.
3. Se busca el usuario con `AuthModel::buscarPorCorreo()` (prepared statement PDO).
4. Se verifica la contraseña con `password_verify()`.
5. Se llama `session_regenerate_id(true)` y se almacena `id_usuario`, `nombre`, `correo`, `rol` en sesión.
6. Respuesta JSON: `{ "exito": true, "redireccion": "/ruta" }` — `admin` → `/dashboard`, `cliente` → `/catalogo`.

El rol `Repartidor` usa una cuenta de empleado vinculada a un registro de la tabla `repartidor`; el login guarda el `id_repartidor` asociado y redirige a `/logistica`. Para una base existente, ejecutar una vez `database/migrations/hu10_repartidor_auth.sql`, crear la cuenta de empleado con rol `Repartidor` y vincularla al registro correspondiente. No se asigna una contraseña predeterminada.

**Flujo de recuperación (HU2):**
- Pantalla 1: ingreso de correo → envío de código de 6 dígitos.
- Pantalla 2: validación del código OTP (6 celdas individuales con auto-foco).
- Estados de error: correo no registrado / código expirado o incorrecto (banner `#FEE2E2` / `#DC2626`).

### 2. Catálogo (`2_catalog`) — HU3

| Archivo | Descripción |
|---|---|
| `api/get_catalog.php` | Endpoint GET: devuelve el listado de productos |
| `api/manage_product.php` | Endpoint POST: crea / actualiza / elimina productos |
| `api/ProductModel.php` | Modelo de producto (PDO) |
| `ui/catalog.js` | Lógica de renderizado del catálogo |
| `ui/catalogUI.js` | Componentes de UI del catálogo |

**Funcionalidades (HU3):**
- Cuadrícula (grid) de tarjetas con imagen, descripción y precio.
- Búsqueda y filtrado por categoría / disponibilidad.
- Modal de detalle del producto (descripción, fotos, precio).
- Alerta de "Sin disponibilidad / Agotado" (flujo alterno).
- Sección de información del negocio: horario, teléfono, dirección y mapa.

**Paleta de colores (HU3):**
- Naranja cálido / Terracota — botones de acción principal, títulos destacados.
- Verde oliva / Natural — etiquetas de disponibilidad, precios, estados de éxito.
- Blanco y gris claro — fondos y estructura general.
- Rojo de alerta — exclusivo para productos agotados y errores.

### 3. E-commerce (`3_ecommerce`)

| Archivo | Descripción |
|---|---|
| `api/create_online_order.php` | Endpoint POST: registra un pedido en línea |
| `api/track_order.php` | Endpoint GET: estado de un pedido |
| `api/EcommerceModel.php` | Modelo de pedido en línea (PDO) |
| `ui/checkout.js` | Lógica del proceso de compra |
| `ui/trackingUI.js` | UI de seguimiento de pedidos |
| `ui/mis_pedidos.php` | Pantalla independiente del historial y seguimiento de pedidos (HU6) |

### 4. Punto de venta (`4_pos`)

| Archivo | Descripción |
|---|---|
| `api/create_pos_order.php` | Endpoint POST: registra una venta en POS |
| `api/process_payment.php` | Endpoint GET/POST: consulta el cobro y registra efectivo, cambio y ticket (HU9) |
| `api/receive_online_orders.php` | Endpoint GET: bandeja de pedidos web pendientes (HU7) |
| `api/manage_online_order.php` | Endpoint POST: acepta o rechaza pedidos web con motivo obligatorio (HU7) |
| `api/search_pos_customers.php` | Endpoint GET: búsqueda de clientes para captura POS (HU8) |
| `api/create_pos_order.php` | Endpoint POST: registra pedidos de mostrador o teléfono en estado pendiente (HU8) |
| `api/PosModel.php` | Modelo POS (PDO) |
| `ui/pos.php` | Bandeja y captura de pedidos para Vendedor y Cajero |
| `ui/posUI.js` | UI del POS |
| `ui/posService.js` | Lógica de servicios del POS |
| `ui/posOrderUI.js` | Búsqueda de cliente, ticket y captura de pedidos (HU8) |

La vista `/caja` permite cobrar en efectivo los pedidos pendientes o en proceso. Calcula el cambio, registra venta, detalle y pago de forma atómica y genera un ticket imprimible; los pedidos ya pagados no pueden cobrarse de nuevo (HU9).

La bandeja de recepción actualiza los pedidos pendientes automáticamente, avisa visualmente cuando llega uno nuevo y permite activar una alerta sonora. Al aceptar, el pedido pasa a **En Proceso**; al rechazar, se registra el motivo y el estado **Cancelado**, visibles para el cliente en su seguimiento.

### 5. Logística (`5_logistics`)

| Archivo | Descripción |
|---|---|
| `api/get_delivery_route.php` | Endpoint GET: ruta de entrega del día |
| `api/confirm_delivery.php` | Endpoint POST: marca entrega como completada |
| `api/LogisticsModel.php` | Modelo de logística (PDO) |
| `ui/logistica.php` | Vista móvil de entregas asignadas al repartidor autenticado (HU10) |
| `ui/deliveryUI.js` | UI de ruta, saldos y confirmación de entrega |
| `ui/mapRoutes.js` | Enlaces directos de navegación a Google Maps |
| `ui/logistica.css` | Estilos responsivos de la ruta móvil |

La ruta se filtra en servidor por `id_repartidor`, solo incluye entregas domiciliarias en estado **En Ruta** y registra `fecha_entrega` al confirmar. Para domicilios con geolocalización abre navegación a coordenadas; si no hay coordenadas, abre la dirección como destino.

### 6. Caja (`6_cash`)

| Archivo | Descripción |
|---|---|
| `api/close_cash_register.php` | Endpoint POST: registra cierre de caja |
| `api/CashModel.php` | Registra venta, partidas y pago en una transacción (PDO) |
| `ui/caja.php` | Pantalla de cobro en efectivo y ticket imprimible |
| `ui/cajaService.js` | Cliente de servicios de cobro |
| `ui/cajaUI.js` | Cálculo de cambio y generación del ticket |
| `ui/caja.css` | Estilos de Caja e impresión |
| `ui/cashUI.js` | UI de caja |

### 7. Reportes (`7_reports`)

| Archivo | Descripción |
|---|---|
| `api/get_reports.php` | Endpoint GET: datos de reportes |
| `api/ReportModel.php` | Modelo de reportes (PDO) |
| `ui/reportesui.js` | UI de reportes |
| `ui/reporteServices.js` | Lógica de servicios de reportes |

---

## Núcleo (`core/`)

### `Database.php`

Singleton que gestiona la conexión PDO. Lee credenciales desde `.env`.

```php
$pdo = Database::obtenerInstancia()->obtenerConexion();
```

- Lanza `RuntimeException` si falla la conexión (sin exponer detalles al cliente).
- `PDO::ATTR_EMULATE_PREPARES => false` — prepared statements nativos.

### `Router.php`

Mapea URIs a archivos PHP del proyecto.

| Tipo | Rutas |
|---|---|
| Públicas (sin sesión) | `/`, `/login`, `/recuperar` |
| Protegidas (requieren `$_SESSION['id_usuario']`) | `/dashboard`, `/catalogo`, `/ecommerce`, `/pos`, `/logistica`, `/caja`, `/reportes`, `/logout` |

Ruta no registrada → redirige a `/login`.

### `ApiManager.js`

Cliente HTTP centralizado para el frontend. Todos los módulos JS deben usarlo en lugar de llamadas Fetch directas.

```js
// GET con parámetros opcionales
const datos = await ApiManager.obtener('/modules/2_catalog/api/get_catalog.php', { categoria: 'maiz' });

// POST (JSON)
const resp = await ApiManager.publicar('/modules/1_auth/api/login.php', { correo, contrasena });

// POST (FormData — para subida de archivos)
const resp = await ApiManager.publicarFormulario('/modules/2_catalog/api/manage_product.php', formData);
```

Incluye `X-Requested-With: XMLHttpRequest` en todas las solicitudes para validación AJAX en el backend.

---

## Configuración (`.env`)

Ubicación: `html/proyecto-tienda/.env`

```env
DB_HOST=db
DB_PORT=3306
DB_NAME=tortilleria
DB_USER=root
DB_PASS=secreto
```

> `.env` está en `.gitignore` — nunca subir al repositorio.

---

## Convenciones de desarrollo

### Nomenclatura de ramas y commits

| Elemento | Formato | Ejemplo |
|---|---|---|
| Rama | `s{Sprint}t{Tarea}hu{HU_ID}-descripcion-corta` | `s1t1hu1-analisis-bd` |
| Commit | `sprt{Sprint}ta{Tarea}hu{HU}: descripción` | `sprt1ta1hu1: Creación de script DDL` |

### Nomenclatura de código

| Elemento | Convención | Ejemplo |
|---|---|---|
| Clases PHP | `PascalCase` | `AuthModel` |
| Métodos y variables | `camelCase` | `buscarPorCorreo`, `idUsuario` |
| Constantes | `UPPER_SNAKE_CASE` | `MENSAJE_CREDENCIALES_INVALIDAS` |
| Tablas y columnas MySQL | `snake_case` | `contrasena_hash`, `fecha_creacion` |
| Idioma | Español en todo | variables, comentarios, tablas |

### Reglas de desarrollo

- Implementar estrictamente lo solicitado en la tarea activa — sin refactorizaciones ni código muerto no pedidos.
- No modificar `docs/` ni `agents.md` salvo instrucción explícita.
- Todo JS en archivos separados cargados con `defer` — prohibido inline JS en PHP/HTML.
- No usar Composer. No crear la base de datos desde código (ya existe en el SGBD).
- Una tarea a la vez: probar en el navegador y hacer commit antes de pasar a la siguiente.

---

## Seguridad

- Contraseñas con `password_hash()` y `password_verify()` — nunca en texto plano.
- Todas las consultas SQL usan prepared statements PDO — sin concatenación de variables.
- Los endpoints validan método HTTP y encabezado `X-Requested-With` para rechazar accesos directos.
- Errores de BD registrados con `error_log()` sin exponer detalles al cliente.
- `session_regenerate_id(true)` tras cada login exitoso — previene fijación de sesión.
- Mensajes de error de autenticación genéricos — no revelan si falla el correo o la contraseña.

### Códigos HTTP

| Código | Significado |
|---|---|
| `200 OK` | Solicitud exitosa |
| `201 Created` | Recurso creado |
| `400 Bad Request` | Datos de entrada inválidos o faltantes |
| `401 Unauthorized` | Credenciales inválidas |
| `403 Forbidden` | Permisos insuficientes para el rol |
| `500 Internal Server Error` | Fallo de conexión PDO o excepción no controlada |

---

## Sprint 1 — Estado de avance

### HU1: Acceso al Sistema

| # | Tarea | Responsable | Estimación | Estado |
|---|---|---|---|---|
| 1.1 | Análisis y diseño del modelo relacional | Citlali Hernandez Ruiz | 16 h | ✅ Completado |
| 1.2 | Script SQL DDL (CREATE TABLE, PKs, FKs) | Citlali Hernandez Ruiz | 16 h | ✅ Completado |
| 1.3 | Datos iniciales DML (roles, puestos, admin inicial) | Citlali Hernandez Ruiz | 8 h | ⏳ Pendiente |
| 1.4 | Pruebas de integridad referencial en MariaDB | Citlali Hernandez Ruiz | 8 h | ⏳ Pendiente |

### HU2: Recuperar Contraseña

| # | Tarea | Responsable | Estimación | Estado |
|---|---|---|---|---|
| 2.1 | Análisis de requerimientos de seguridad y roles | Emmanuel Fernando Chimil Pacheco | 8 h | ⏳ Pendiente |
| 2.2 | Diseño de interfaz gráfica y vistas | Emmanuel Fernando Chimil Pacheco | 8 h | ⏳ Pendiente |
| 2.3 | Lógica de autenticación, BCrypt y redirección por rol | Emmanuel Fernando Chimil Pacheco | 16 h | ⏳ Pendiente |
| 2.4 | Pruebas de integración con BD | Emmanuel Fernando Chimil Pacheco | 16 h | ⏳ Pendiente |

### HU3: Catálogo Web

| # | Tarea | Responsable | Estimación | Estado |
|---|---|---|---|---|
| 3.1 | Diseño de interfaz del catálogo (grid, tarjetas, psicología del color) | Emmanuel Fernando Chimil Pacheco | 16 h | ⏳ Pendiente |
| 3.2 | Consulta BD para obtener productos y precios | Emmanuel Fernando Chimil Pacheco | 16 h | ⏳ Pendiente |
| 3.3 | Búsqueda y filtrado por categoría/disponibilidad | Emmanuel Fernando Chimil Pacheco | 16 h | ⏳ Pendiente |
| 3.4 | Modal de detalle del producto (descripción, fotos, precio) | Emmanuel Fernando Chimil Pacheco | 8 h | ⏳ Pendiente |
| 3.5 | Alerta de flujo alterno (Sin disponibilidad / Agotado) | Emmanuel Fernando Chimil Pacheco | 8 h | ⏳ Pendiente |

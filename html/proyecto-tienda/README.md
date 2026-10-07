# Tortillería — Sistema de Gestión de Pedidos

Sistema web para la gestión integral de una tortillería: autenticación de usuarios, catálogo de productos, e-commerce, punto de venta (POS), logística de entregas, caja y reportes.

## Tecnologías

- **Backend:** PHP (sin frameworks), PDO + MySQL/MariaDB
- **Frontend:** Vanilla JavaScript (sin frameworks), HTML, CSS
- **Arquitectura:** MVC modular, sin Composer

## Estructura del proyecto

```
proyecto-tienda/
├── index.php               # Punto de entrada; inicia sesión y despacha el Router
├── .env                    # Variables de entorno (credenciales BD, configuración)
├── core/
│   ├── Database.php        # Singleton PDO — conexión a la base de datos
│   ├── Router.php          # Enrutador central; distingue rutas públicas y protegidas
│   └── ApiManager.js       # Cliente HTTP centralizado (Fetch API) para el frontend
└── modules/
    ├── 1_auth/             # Autenticación (login, logout, recuperación de contraseña)
    ├── 2_catalog/          # Catálogo de productos (consulta y gestión)
    ├── 3_ecommerce/        # Pedidos en línea y seguimiento
    ├── 4_pos/              # Punto de venta presencial
    ├── 5_logistics/        # Rutas de entrega y confirmación
    ├── 6_cash/             # Caja (apertura/cierre)
    └── 7_reports/          # Reportes
```

Cada módulo sigue la misma convención interna:

```
módulo/
├── ui/     # Vistas PHP + JS de presentación
└── api/    # Endpoints PHP + Modelo (PDO)
```

## Módulos

### 1. Autenticación (`1_auth`)

| Archivo | Descripción |
|---|---|
| `ui/login.php` | Formulario de inicio de sesión |
| `ui/recuperar.php` | Formulario de recuperación de contraseña |
| `ui/authService.js` | Lógica JS del formulario de login (usa `ApiManager`) |
| `ui/recoveryService.js` | Lógica JS de recuperación de contraseña |
| `api/login.php` | Endpoint POST: valida credenciales, crea sesión, redirige por rol |
| `api/logout.php` | Endpoint: destruye la sesión activa |
| `api/recovery.php` | Endpoint: gestiona la recuperación de contraseña |
| `api/AuthModel.php` | Modelo: `buscarPorCorreo()` con prepared statement |

**Flujo de login:**
1. `authService.js` envía `{ correo, contrasena }` vía `ApiManager.publicar()`.
2. `login.php` valida método HTTP y encabezado AJAX.
3. Se busca el usuario con `AuthModel::buscarPorCorreo()`.
4. Se verifica la contraseña con `password_verify()`.
5. Se regenera el ID de sesión y se almacena `id_usuario`, `nombre`, `correo`, `rol`.
6. Se responde con `{ exito: true, redireccion: "/ruta" }` según el rol (`admin` → `/dashboard`, `cliente` → `/catalogo`).

### 2. Catálogo (`2_catalog`)

Consulta y administración del catálogo de productos.

| Archivo | Descripción |
|---|---|
| `api/get_catalog.php` | Endpoint GET: devuelve el listado de productos |
| `api/manage_product.php` | Endpoint POST: crea / actualiza / elimina productos |
| `api/ProductModel.php` | Modelo de producto (PDO) |
| `ui/catalog.js` | Lógica de renderizado del catálogo |
| `ui/catalogUI.js` | Componentes de UI del catálogo |

### 3. E-commerce (`3_ecommerce`)

Pedidos realizados desde la tienda en línea.

| Archivo | Descripción |
|---|---|
| `api/create_online_order.php` | Endpoint POST: registra un nuevo pedido en línea |
| `api/track_order.php` | Endpoint GET: devuelve el estado de un pedido |
| `api/EcommerceModel.php` | Modelo de pedido en línea (PDO) |
| `ui/checkout.js` | Lógica del proceso de compra |
| `ui/trackingUI.js` | UI de seguimiento de pedidos |

### 4. Punto de venta (`4_pos`)

Ventas presenciales en mostrador.

| Archivo | Descripción |
|---|---|
| `api/create_pos_order.php` | Endpoint POST: registra una venta en POS |
| `api/process_payment.php` | Endpoint POST: procesa el pago de una venta |
| `api/receive_online_orders.php` | Endpoint GET: obtiene pedidos en línea pendientes |
| `api/PosModel.php` | Modelo POS (PDO) |
| `ui/posUI.js` | UI del POS |
| `ui/posService.js` | Lógica de servicios del POS |

### 5. Logística (`5_logistics`)

Gestión de rutas y confirmación de entregas a domicilio.

| Archivo | Descripción |
|---|---|
| `api/get_delivery_route.php` | Endpoint GET: devuelve la ruta de entrega del día |
| `api/confirm_delivery.php` | Endpoint POST: marca una entrega como completada |
| `api/LogisticsModel.php` | Modelo de logística (PDO) |
| `ui/deliveryUI.js` | UI de gestión de entregas |
| `ui/mapRoutes.js` | Visualización de rutas en mapa |

### 6. Caja (`6_cash`)

Control de apertura y cierre de caja.

| Archivo | Descripción |
|---|---|
| `api/close_cash_register.php` | Endpoint POST: registra el cierre de caja |
| `api/CashModel.php` | Modelo de caja (PDO) |
| `ui/cashUI.js` | UI de caja |

### 7. Reportes (`7_reports`)

Generación de reportes de ventas y operaciones.

| Archivo | Descripción |
|---|---|
| `api/get_reports.php` | Endpoint GET: devuelve datos de reportes |
| `api/ReportModel.php` | Modelo de reportes (PDO) |
| `ui/reportesui.js` | UI de reportes |
| `ui/reporteServices.js` | Lógica de servicios de reportes |

## Núcleo (`core/`)

### `Database.php`

Singleton que gestiona la conexión PDO. Lee las credenciales desde `.env`.

```php
$pdo = Database::obtenerInstancia()->obtenerConexion();
```

- Lanza `RuntimeException` si falla la conexión (sin exponer credenciales).
- `ATTR_EMULATE_PREPARES => false` para prepared statements nativos.

### `Router.php`

Enrutador central que mapea URIs a archivos PHP.

- **Rutas públicas** (sin sesión): `/`, `/login`, `/recuperar`.
- **Rutas protegidas** (requieren `$_SESSION['id_usuario']`): `/dashboard`, `/catalogo`, `/ecommerce`, `/pos`, `/logistica`, `/caja`, `/reportes`, `/logout`.
- Ruta no registrada → redirige a `/login`.

### `ApiManager.js`

Cliente HTTP para el frontend. Todos los módulos JS deben usarlo en lugar de llamadas Fetch directas.

```js
// GET
const datos = await ApiManager.obtener('/modules/2_catalog/api/get_catalog.php');

// POST (JSON)
const resp = await ApiManager.publicar('/modules/1_auth/api/login.php', { correo, contrasena });

// POST (FormData)
const resp = await ApiManager.publicarFormulario('/modules/2_catalog/api/manage_product.php', formData);
```

Incluye el encabezado `X-Requested-With: XMLHttpRequest` en todas las solicitudes.

## Configuración (`.env`)

El archivo `.env` debe estar en la raíz del proyecto con las siguientes variables:

```env
DB_HOST=db
DB_PORT=3306
DB_NAME=tortilleria
DB_USER=root
DB_PASS=secreto
```

> No subir este archivo al repositorio. Agrega `.env` a `.gitignore`.

## Seguridad

- Contraseñas almacenadas con `password_hash()` y verificadas con `password_verify()`.
- Todas las consultas SQL usan prepared statements (PDO) — sin concatenación de variables.
- Los endpoints validan método HTTP y encabezado `X-Requested-With` para rechazar accesos directos.
- Los errores de BD se registran con `error_log()` sin exponer detalles al cliente.
- Se llama `session_regenerate_id(true)` tras cada login exitoso para prevenir fijación de sesión.
- Los mensajes de error de autenticación son genéricos (no revelan si falla el correo o la contraseña).

## Restricciones de desarrollo

- Sin Composer ni frameworks (PHP o JS).
- Sin JavaScript en línea — todo JS va en archivos separados cargados con `defer`.
- Patrón MVC estricto por módulo.
- La base de datos ya existe; no se crea desde el código.

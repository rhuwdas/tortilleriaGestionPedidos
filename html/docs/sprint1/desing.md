# Design Specifications & Agent Prompt Guide (Production Grade)

This document contains the production-level design specifications, architecture guidelines, and structured prompts for **HU1** and **HU2**, intended for consumption by an AI agent or automated UI generator. All components must adhere to high-fidelity production standards, including accessibility (WCAG AA), responsive layout rules, tokenized design systems, and robust error handling.

## 1. Design System & Global Tokens

* **Color Palette:**

  * Primary: `#2563EB` (Blue 600 - Main Actions/Links)

  * Primary Hover: `#1D4ED8` (Blue 700)

  * Neutral Dark (Text): `#1F2937` (Gray 800)

  * Neutral Muted (Labels/Borders): `#6B7280` (Gray 500)

  * Surface Background: `#F9FAFB` (Gray 50)

  * Card/Modal Background: `#FFFFFF` (White)

  * Error/Destructive: `#DC2626` (Red 600)

  * Error Background: `#FEE2E2` (Red 100)

  * Success: `#16A34A` (Green 600)

* **Typography (Inter / System Font Stack):**

  * Heading 1: `24px` / Bold / Line-height `32px`

  * Body Text: `14px` / Regular / Line-height `20px`

  * Caption / Helper: `12px` / Regular / Line-height `16px`

* **Spacing & Layout:**

  * Base grid unit: `8px`

  * Border Radius: `8px` for inputs/cards, `6px` for buttons.

  * Shadows: `0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)`

## 2. HU1: Production Prompt & Component Specification

### Agent Prompt for HU1

```
Task: Generate the high-fidelity production layout and component states for User Story 1 (HU1 - Core Authentication / Login or Main Action flow, based on previous context).
Requirements:
1. Component Hierarchy: Header, main container form card, input fields with floating/top labels, primary call-to-action button, and secondary link options.
2. States: Default, Focus (with #2563EB ring-2 outline), Filled, Error (inline message below input with #DC2626 text), and Loading (spinner inside button).
3. Accessibility: Ensure aria-labels, proper contrast ratios, and keyboard navigation order.
4. Output format: Clean semantic layout structure with exact padding, margins, and token mappings.

```

### HU1 Component Architecture

* **Container Card:** Max-width `400px`, padding `32px`, centered vertically and horizontally on viewport.

* **Inputs:** Height `44px`, border `1px solid #D1D5DB`, padding-inline `12px`.

* **Button:** Height `44px`, background `#2563EB`, text color `#FFFFFF`, font-weight `600`.

## 3. HU2: Production Prompt & Component Specification (Recuperar Contraseña)

### Agent Prompt for HU2

```
Role: Senior UI/UX Engineer & Design System Architect.
Task: Generate high-fidelity production-ready screens and component states for User Story 2 (HU2 - Password Recovery Flow) as defined in the use case model.
Flow & Screens to Generate:
1. Screen 1 - Request Recovery (Initial View):
   - Title: "Recuperar Contraseña"
   - Description text explaining the process.
   - Email input field with validation state.
   - Primary button: "Enviar código"
2. Screen 2 - Code Validation / Entry View:
   - Title: "Validar Código"
   - Instruction: "Hemos enviado un código de 6 dígitos a tu correo."
   - OTP input fields (6 separate boxed inputs or 1 structured mask).
   - Buttons: "Verificar" (Primary) and "Reenviar código" (Text/Link button).
3. Screen 3 - Error State (Email Not Found / <>):
   - Inline or banner alert styled with #FEE2E2 background and #DC2626 text.
   - Message: "El correo electrónico ingresado no está registrado en el sistema."
4. Screen 4 - Error State (Expired / Incorrect Code / <>):
   - Alert notification banner.
   - Message: "El código ingresado es incorrecto o ha expirado. Solicita uno nuevo."

Technical Constraints:
- Use production styles, tokens, flexbox/grid layout, smooth state transitions, and accessible markup.

```

### HU2 Component Architecture & Interaction Rules

* **OTP Input Blocks:** 6 individual cells of width `40px`, height `48px`, centered text, auto-focus shifting on key entry.

* **Alert Banner:** Flex layout with leading icon (AlertCircle), padding `12px 16px`, rounded corners `8px`, border-left `4px solid #DC2626`.

* **Transitions:** Fade-in animation for error states (`opacity 0.2s ease-in-out`).


## HU3 

Diseño del Sistema: Catálogo e Información del Negocio (HU3)
1. Diseño Visual y Psicología del Color

    Paleta de Colores Institucional:

        Naranja Cálido / Terracota (Color Primario): Transmite energía, apetito, cercania y calidez, ideal para negocios de alimentos de consumo diario (tortillerías y productos orgánicos). Se usa en botones de acción principal, logotipos y títulos destacados.

        Verde Oliva / Natural (Color de Apoyo): Asociado con lo fresco, natural y saludable. Se utiliza en etiquetas de disponibilidad, insignias de precios y estados de éxito.

        Blanco y Gris Claro (Fondos): Proporcionan limpieza, legibilidad y un diseño minimalista que deja destacar las imágenes de los productos.

        Rojo de Alerta: Reservado exclusivamente para errores, productos agotados o advertencias de disponibilidad (<<extend>>).

    Tipografía: Fuente principal Arial; títulos y encabezados en Arial Bold.

    Estructura: Diseño responsive adaptado para e-commerce (web y móvil) con cuadrículas limpias y espacios en blanco equilibrados para Figma.

2. Pantallas y Flujo de Interacción (HU3: Consultar Catálogo)
1. Pantalla Principal del Catálogo

    Título / Cabecera:

        Nombre de la tienda / negocio alineado a la izquierda o centro.

        Barra de búsqueda interactiva de productos con ícono de lupa.

    Sección 1: Cuadrícula (Grid) de "Productos disponibles" (<<include>>):

        Tarjetas de producto (cards) con bordes sutiles y sombra ligera.

        "Mostrar imagen y descripción": Imagen contenedora (marcador de posición) con bordes redondeados y texto breve del producto.

        "Mostrar precio": Etiqueta clara con formato de moneda, destacada visualmente.

    Sección 2: Navegación:

        Enlace o pestaña de navegación clara hacia la sección de "Información del negocio".

2. Pantalla de Información del Negocio (<<extend>> desde "Consultar catálogo")

    Título: "Acerca del Negocio" o "Información".

    Bloque 1:

        "Mostrar horario y teléfono": Bloque de texto descriptivo con las horas de atención al cliente y número de contacto con íconos representativos.

    Bloque 2:

        "Mostrar dirección y mapa": Marcador de posición visual simulando un mapa de ubicación geográfica y texto detallado con la dirección física del establecimiento.


## 4. Agent Integration Guidelines

* When rendering or translating these specs into code or wireframes, maintain consistency with the token names specified in Section 1.

* Ensure all interactive elements have hover, active, and disabled states defined.




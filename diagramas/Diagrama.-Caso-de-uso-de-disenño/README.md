# 🎨 Diagramas - Caso de Uso de Diseño

Esta sección contiene los diagramas técnicos que representan la arquitectura, implementación y diseño del sistema Expensas 365Soft, enfocados en los aspectos técnicos, estructurales y de desarrollo.

---

## 🏗️ 1. Diagrama de Arquitectura del Sistema

```mermaid
graph TB
    subgraph "🎨 ARQUITECTURA TÉCNICA - EXPENSAS 365SOFT"
        subgraph "Frontend (Vue 3)"
            UI[🎨 Componentes UI<br/>Reka UI + PrimeVue]
            VUE[⚡ Vue 3 + Composition API]
            INERTIA[🔄 Inertia.js]
            TS[📘 TypeScript]
            VITE[🔧 Vite + TailwindCSS]
        end

        subgraph "Backend (Laravel)"
            API[🔌 APIs y Controladores]
            ROUTES[🛣️ Sistema de Rutas]
            MIDDLEWARE[🛡️ Middleware y Seguridad]
            SERVICES[⚙️ Services de Negocio]
            MODELS[📊 Models Eloquent]
            VALIDATION[✅ Validaciones]
        end

        subgraph "Capa de Datos"
            MYSQL[🗄️ MySQL/MariaDB]
            MIGRATIONS[🔄 Migrations]
            SEEDERS[🌱 Seeders]
        end

        subgraph "Integraciones Externas"
            BANCO[🏦 Sistema Bancario QR]
            EMAIL[📧 Servicio Email]
            SMS[📱 Servicio SMS]
            PUSH[🔔 Notificaciones Push]
        end

        subgraph "Infraestructura"
            LARAGON[🖥️ Laragon - Dev Environment]
            QUEUE[📋 Colas y Jobs]
            CACHE[💾 Caching System]
            LOGS[📝 Sistema de Logs]
        end

        %% Conexiones Frontend a Backend
        UI --> VUE
        VUE --> INERTIA
        INERTIA --> API
        TS --> VUE
        VITE --> VUE

        %% Conexiones Backend
        API --> ROUTES
        ROUTES --> MIDDLEWARE
        MIDDLEWARE --> SERVICES
        SERVICES --> MODELS
        MODELS --> MYSQL

        %% Conexiones a Base de Datos
        MODELS --> MIGRATIONS
        MIGRATIONS --> MYSQL
        SEEDERS --> MYSQL

        %% Conexiones a Servicios Externos
        SERVICES --> BANCO
        SERVICES --> EMAIL
        SERVICES --> SMS
        SERVICES --> PUSH

        %% Conexiones de Infraestructura
        SERVICES --> QUEUE
        SERVICES --> CACHE
        SERVICES --> LOGS
    end

    style UI fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style API fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style MYSQL fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style BANCO fill:#fce4ec,stroke:#e91e63,stroke-width:2px
```

---

## 🗄️ 2. Diagrama Entidad-Relación (Base de Datos)

```mermaid
erDiagram
    %% Entidades Principales
    USERS {
        int id PK
        string name
        string email
        string email_verified_at
        string password
        string two_factor_secret
        string two_factor_recovery_codes
        datetime two_factor_confirmed_at
        timestamp email_verified_at
        string role
        datetime created_at
        datetime updated_at
    }

    PROPIETARIOS {
        int id PK
        string nombre_completo
        string ci
        string nit
        string telefono
        string email
        string direccion
        boolean estado
        datetime created_at
        datetime updated_at
    }

    INQUILINOS {
        int id PK
        string nombre_completo
        string ci
        string telefono
        string email
        date fecha_inicio_contrato
        date fecha_fin_contrato
        boolean es_inquilino_principal
        boolean estado
        datetime created_at
        datetime updated_at
    }

    PROPIEDADES {
        int id PK
        string codigo
        string ubicacion
        decimal metros_cuadrados
        string tipo_propiedad
        boolean estado
        datetime created_at
        datetime updated_at
    }

    MEDIDORES {
        int id PK
        string codigo
        string tipo
        int grupos_medidores_id FK
        int propiedad_id FK
        boolean estado
        datetime created_at
        datetime updated_at
    }

    GRUPOS_MEDIDORES {
        int id PK
        string nombre
        string descripcion
        boolean estado
        datetime created_at
        datetime updated_at
    }

    LECTURAS {
        int id PK
        int medidor_id FK
        decimal lectura_anterior
        decimal lectura_actual
        decimal consumo
        date fecha_lectura
        string observaciones
        datetime created_at
        datetime updated_at
    }

    PERIODO_FACTURACION {
        int id PK
        date fecha_inicio
        date fecha_fin
        decimal factor_residencial
        decimal factor_comercial
        decimal factor_estacionamiento
        decimal factor_oficina
        decimal factor_deposito
        date fecha_vencimiento_normal
        date fecha_vencimiento_mora
        decimal porcentaje_mora
        decimal costo_base_m3
        boolean activo
        datetime created_at
        datetime updated_at
    }

    EXPENSAS {
        int id PK
        int propiedad_id FK
        int periodo_facturacion_id FK
        decimal monto_consumo
        decimal monto_factor
        decimal monto_mora
        decimal monto_total
        string estado
        date fecha_vencimiento
        datetime created_at
        datetime updated_at
    }

    PAYMENTS {
        int id PK
        decimal monto
        string metodo_pago
        string referencia
        string qr_code
        date fecha_pago
        string estado
        datetime created_at
        datetime updated_at
    }

    PAGOS {
        int id PK
        decimal monto
        string metodo_pago
        string comprobante
        date fecha_pago
        string observaciones
        datetime created_at
        datetime updated_at
    }

    FACTURAS {
        int id PK
        int propietario_id FK
        int periodo_facturacion_id FK
        decimal monto_total
        string estado
        date fecha_emision
        date fecha_vencimiento
        datetime created_at
        datetime updated_at
    }

    ACCESOS {
        int id PK
        string nombre
        string email
        string password
        string role
        boolean estado
        datetime created_at
        datetime updated_at
    }

    %% Relaciones
    USERS ||--o{ PROPIETARIOS : "gestiona"
    USERS ||--o{ ACCESOS : "accede"

    PROPIETARIOS ||--o{ PROPIEDADES : "posee"
    PROPIETARIOS ||--o{ FACTURAS : "recibe"

    INQUILINOS ||--o{ PROPIEDADES : "ocupa"

    PROPIEDADES ||--|| MEDIDORES : "tiene"
    PROPIEDADES ||--o{ EXPENSAS : "genera"

    MEDIDORES }|--|| GRUPOS_MEDIDORES : "pertenece"
    MEDIDORES ||--o{ LECTURAS : "registra"

    PERIODO_FACTURACION ||--o{ EXPENSAS : "genera"
    PERIODO_FACTURACION ||--o{ FACTURAS : "factura"

    EXPENSAS ||--o{ PAYMENTS : "cancela"
    EXPENSAS ||--o{ PAGOS : "paga"

    %% Notas
    note "Sistema: Expensas 365Soft" as N1
    note "Motor: MySQL/MariaDB" as N2
    note "Framework: Laravel + Vue 3" as N3
```

---

## 🎯 3. Diagrama de Casos de Uso de Diseño - Administrador

```mermaid
graph TB
    subgraph "🎨 CASOS DE USO DE DISEÑO - ADMINISTRADOR"
        subgraph "🏗️ Capa de Presentación (Vue 3)"
            UI_Dashboard[📊 Dashboard Admin<br/>Componente: Dashboard.vue<br/>Ruta: /dashboard]
            UI_Propiedades[🏠 CRUD Propiedades<br/>Componente: Propiedades/Index.vue<br/>Ruta: /propiedades]
            UI_Propietarios[👥 CRUD Propietarios<br/>Componente: Propietarios/Index.vue<br/>Ruta: /propietarios]
            UI_Medidores[💧 CRUD Medidores<br/>Componente: Medidores/Index.vue<br/>Ruta: /medidores]
            UI_Lecturas[📋 Registro Lecturas<br/>Componente: Lecturas/Create.vue<br/>Ruta: /lecturas/create]
            UI_Expensas[🧾 Gestión Expensas<br/>Componente: Expensas/Index.vue<br/>Ruta: /expensas]
            UI_Pagos[💳 Procesamiento Pagos<br/>Componente: Payments/Index.vue<br/>Ruta: /payments]
            UI_Reportes[📊 Reportes<br/>Componente: Reports/Index.vue<br/>Ruta: /reports]
            UI_Config[⚙️ Configuración<br/>Componente: Settings/Index.vue<br/>Ruta: /settings]
        end

        subgraph "🔌 Capa de Control (Laravel)"
            CTRL_Dashboard["🎮 DashboardController<br/>- index<br/>- getData"]
            CTRL_Propiedades["🏠 PropiedadController<br/>- index, create, store<br/>- edit, update, destroy"]
            CTRL_Propietarios["👥 PropietarioController<br/>- index, create, store<br/>- edit, update, destroy"]
            CTRL_Medidores["💧 MedidorController<br/>- index, create, store<br/>- asignarPropiedad"]
            CTRL_Lecturas["📋 LecturaController<br/>- create, store<br/>- validarLecturas"]
            CTRL_Expensas["🧾 ExpensaController<br/>- index, generate<br/>- calculoAutomatico"]
            CTRL_Pagos["💳 PaymentController<br/>- index, processQR<br/>- validatePayment"]
            CTRL_Reportes["📊 ReportController<br/>- cobranza, consumos<br/>- exportPDF"]
            CTRL_Periodos["📅 PeriodoFacturacionController<br/>- index, store<br/>- activate"]
        end

        subgraph "⚙️ Capa de Servicios (Laravel)"
            SVC_Calculo["🧮 CalculoExpensasService<br/>- calcularConsumo<br/>- aplicarFactores"]
            SVC_Pago["💳 PaymentAllocationService<br/>- imputarAutomatico<br/>- validarQR"]
            SVC_Notificacion["📧 NotificationService<br/>- enviarExpensas<br/>- confirmarPago"]
            SVC_Validacion["✅ ValidationService<br/>- validarLecturas<br/>- validarDatos"]
            SVC_Reporte["📊 ReportService<br/>- generarCobranza<br/>- exportarDatos"]
        end

        subgraph "📊 Capa de Datos (Eloquent)"
            MODEL_Propiedad["🏠 Propiedad Model<br/>- relaciones: propietario, medidor<br/>- scopeActivos"]
            MODEL_Propietario["👥 Propietario Model<br/>- relaciones: propiedades<br/>- scopeConDeudas"]
            MODEL_Medidor["💧 Medidor Model<br/>- relaciones: lecturas<br/>- scopeDisponibles"]
            MODEL_Lectura["📋 Lectura Model<br/>- relaciones: medidor<br/>- validacionConsumo"]
            MODEL_Expensa["🧾 Expensa Model<br/>- relaciones: propiedad, pagos<br/>- scopePendientes"]
            MODEL_Pago["💳 Payment Model<br/>- relaciones: expensas<br/>- scopeConfirmados"]
        end

        %% Conexiones
        UI_Dashboard --> CTRL_Dashboard
        UI_Propiedades --> CTRL_Propiedades
        UI_Propietarios --> CTRL_Propietarios
        UI_Medidores --> CTRL_Medidores
        UI_Lecturas --> CTRL_Lecturas
        UI_Expensas --> CTRL_Expensas
        UI_Pagos --> CTRL_Pagos
        UI_Reportes --> CTRL_Reportes
        UI_Config --> CTRL_Periodos

        CTRL_Propiedades --> SVC_Validacion
        CTRL_Lecturas --> SVC_Validacion
        CTRL_Expensas --> SVC_Calculo
        CTRL_Pagos --> SVC_Pago
        CTRL_Reportes --> SVC_Reporte
        CTRL_Periodos --> SVC_Validacion

        SVC_Notificacion --> MODEL_Propietario
        SVC_Calculo --> MODEL_Expensa
        SVC_Pago --> MODEL_Pago
        SVC_Validacion --> MODEL_Lectura
        SVC_Reporte --> MODEL_Expensa
    end

    style UI_Dashboard fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style CTRL_Propiedades fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style SVC_Calculo fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style MODEL_Expensa fill:#fce4ec,stroke:#e91e63,stroke-width:2px
```

---

## 🏠 4. Diagrama de Casos de Uso de Diseño - Copropietario

```mermaid
graph TB
    subgraph "🎨 CASOS DE USO DE DISEÑO - COPIROPIETARIO"
        subgraph "🎨 Capa de Presentación (Vue 3)"
            UI_Login[🔐 Autenticación<br/>Componente: Auth/Login.vue<br/>Ruta: /login]
            UI_Register[📝 Registro<br/>Componente: Auth/Register.vue<br/>Ruta: /register]
            UI_Dashboard_User[📊 Dashboard Usuario<br/>Componente: User/Dashboard.vue<br/>Ruta: /user/dashboard]
            UI_Deudas[📋 Estado de Cuenta<br/>Componente: User/Deudas.vue<br/>Ruta: /user/deudas]
            UI_Pagar_QR[💳 Pagar con QR<br/>Componente: User/Pagar.vue<br/>Ruta: /user/pagar]
            UI_Historial[📜 Historial Pagos<br/>Componente: User/Historial.vue<br/>Ruta: /user/historial]
            UI_Consumos[💧 Mis Consumos<br/>Componente: User/Consumos.vue<br/>Ruta: /user/consumos]
            UI_Perfil[👤 Mi Perfil<br/>Componente: User/Profile.vue<br/>Ruta: /user/profile]
            UI_Notificaciones[🔔 Notificaciones<br/>Componente: User/Notificaciones.vue<br/>Ruta: /user/notificaciones]
        end

        subgraph "🔌 Capa de Control (Laravel)"
            CTRL_Auth["🔐 AuthController<br/>- login, logout<br/>- register, verify"]
            CTRL_User["👤 UserController<br/>- dashboard, deudas<br/>- perfil, update"]
            CTRL_User_Pagos["💳 UserPaymentController<br/>- generarQR<br/>- verificarPago"]
            CTRL_User_Expensas["🧾 UserExpensaController<br/>- index, show<br/>- descargarComprobante"]
            CTRL_User_Consumos["💧 UserConsumoController<br/>- index, historial<br/>- exportarDatos"]
        end

        subgraph "⚙️ Capa de Servicios (Laravel)"
            SVC_User["👤 UserService<br/>- obtenerPropiedades<br/>- calcularDeudas"]
            SVC_QR["📱 QRService<br/>- generarCodigoQR<br/>- validarQR"]
            SVC_Consumo["💧 ConsumoService<br/>- calcularHistorial<br/>- detectarAnomalias"]
            SVC_Notif_User["📧 UserNotificationService<br/>- programarAlertas<br/>- enviarConfirmaciones"]
            SVC_Seguridad["🔐 SecurityService<br/>- validarAcceso<br/>- gestionar2FA"]
        end

        subgraph "🔍 Capa de Consultas (Eloquent)"
            QUERY_Propiedades_User["🏠 Propiedades del Usuario<br/>- scopeByUserId<br/>- conDeudasPendientes"]
            QUERY_Expensas_User["🧾 Expensas del Usuario<br/>- scopePendientes<br/>- scopeVencidas"]
            QUERY_Pagos_User["💳 Pagos del Usuario<br/>- scopeByUser<br/>- confirmados"]
            QUERY_Consumos_User["💧 Consumos del Usuario<br/>- historicoConsumo<br/>- tendenciaMensual"]
            QUERY_Notif_User["🔔 Notificaciones del Usuario<br/>- noLeidas<br/>- porTipo"]
        end

        %% Conexiones
        UI_Login --> CTRL_Auth
        UI_Register --> CTRL_Auth
        UI_Dashboard_User --> CTRL_User
        UI_Deudas --> CTRL_User_Expensas
        UI_Pagar_QR --> CTRL_User_Pagos
        UI_Historial --> CTRL_User_Expensas
        UI_Consumos --> CTRL_User_Consumos
        UI_Perfil --> CTRL_User
        UI_Notificaciones --> CTRL_User

        CTRL_Auth --> SVC_Seguridad
        CTRL_User --> SVC_User
        CTRL_User_Pagos --> SVC_QR
        CTRL_User_Consumos --> SVC_Consumo
        CTRL_User_Expensas --> SVC_Notif_User

        SVC_User --> QUERY_Propiedades_User
        SVC_QR --> QUERY_Expensas_User
        SVC_Consumo --> QUERY_Consumos_User
        SVC_Notif_User --> QUERY_Notif_User
    end

    style UI_Login fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style CTRL_Auth fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style SVC_QR fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style QUERY_Expensas_User fill:#fce4ec,stroke:#e91e63,stroke-width:2px
```

---

## 🔧 5. Diagrama de Flujo de Datos - Proceso de Pago QR

```mermaid
flowchart TD
    subgraph "🎨 FLUJO DE DISEÑO - PROCESO PAGO QR"
        subgraph "Frontend (Vue 3)"
            UI_Pagar[📱 Componente Pagar.vue<br/>- Selección de deudas<br/>- Generación de QR<br/>- Estados de pago]
            UI_QR_Display[📲 Componente QRDisplay.vue<br/>- Renderizado de QR<br/>- Temporizador<br/>- Refresh automático]
            UI_Estado[📊 Componente EstadoPago.vue<br/>- Barras de progreso<br/>- Confirmación visual<br/>- Descarga comprobante]
        end

        subgraph "API Endpoints (Laravel)"
            API_Seleccionar["POST /api/user/pagos/seleccionar<br/>Request: debt_ids<br/>Response: payment_summary"]
            API_GenerarQR["POST /api/user/pagos/generar-qr<br/>Request: payment_data<br/>Response: qr_code, reference"]
            API_Verificar["GET /api/user/pagos/verificar/{reference}<br/>Response: payment_status"]
            API_Confirmar["POST /api/user/pagos/confirmar<br/>Request: payment_confirmation<br/>Response: receipt"]
        end

        subgraph "Services (Laravel)"
            SVC_QR_Gen["QRGenerationService<br/>- generateUniqueCode<br/>- createPaymentRequest<br/>- setExpiration"]
            SVC_Payment_Validate["PaymentValidationService<br/>- validateQRCode<br/>- checkBankStatus<br/>- confirmAmount"]
            SVC_Payment_Process["PaymentProcessingService<br/>- allocateToDebts<br/>- updateStatus<br/>- generateReceipt"]
            SVC_Notification["NotificationService<br/>- sendEmailConfirmation<br/>- pushNotification<br/>- updateUI"]
        end

        subgraph "External APIs"
            API_Banco["🏦 Banco QR API<br/>- validatePayment<br/>- getStatus<br/>- confirmTransaction"]
            API_Notification["📧 Notification API<br/>- sendEmail<br/>- sendSMS<br/>- sendPush"]
        end

        subgraph "Database Operations"
            DB_Transaction["💾 Payment Transaction<br/>INSERT payments<br/>UPDATE expensas<br/>COMMIT/ROLLBACK"]
            DB_Audit["📝 Audit Log<br/>INSERT payment_logs<br/>UPDATE user_activity<br/>Log errors"]
        end

        %% Flujo principal
        UI_Pagar --> API_Seleccionar
        API_Seleccionar --> SVC_Payment_Validate
        SVC_Payment_Validate --> DB_Transaction

        UI_Pagar --> API_GenerarQR
        API_GenerarQR --> SVC_QR_Gen
        SVC_QR_Gen --> API_Banco
        API_Banco -- QR generado --> UI_QR_Display

        UI_QR_Display --> API_Verificar
        API_Verificar --> API_Banco
        API_Banco -- estado pago --> SVC_Payment_Validate
        SVC_Payment_Validate --> UI_Estado

        UI_Estado --> API_Confirmar
        API_Confirmar --> SVC_Payment_Process
        SVC_Payment_Process --> DB_Transaction
        SVC_Payment_Process --> DB_Audit
        SVC_Payment_Process --> SVC_Notification
        SVC_Notification --> API_Notification
        API_Notification --> UI_Estado
    end

    style UI_Pagar fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style API_GenerarQR fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style SVC_QR_Gen fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style API_Banco fill:#fce4ec,stroke:#e91e63,stroke-width:2px
    style DB_Transaction fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
```

---

## 🗄️ 6. Diagrama de Estructura de Archivos

```mermaid
graph TB
    subgraph "🎨 ESTRUCTURA DE ARCHIVOS - EXPENSAS 365SOFT"
        subgraph "Backend (Laravel)"
            APP[app/]
            CONTROLLERS[app/Http/Controllers/]
            MODELS[app/Models/]
            SERVICES[app/Services/]
            MIDDLEWARE[app/Http/Middleware/]
            ROUTES[routes/]
            MIGRATIONS[database/migrations/]
            SEEDERS[database/seeders/]
        end

        subgraph "Frontend (Vue 3)"
            RESOURCES[resources/]
            JS[resources/js/]
            PAGES[resources/js/pages/]
            COMPONENTS[resources/js/components/]
            LAYOUTS[resources/js/Layouts/]
            STORE[resources/js/store/]
            CSS[resources/css/]
        end

        subgraph "Configuración"
            CONFIG[config/]
            ENV[.env]
            COMPOSER[composer.json]
            PACKAGE[package.json]
            VITE[vite.config.js]
        end

        %% Detalle de Controllers
        CONTROLLERS --> CTRL_AUTH[AuthController.php]
        CONTROLLERS --> CTRL_DASHBOARD[DashboardController.php]
        CONTROLLERS --> CTRL_PROPIEDADES[PropiedadController.php]
        CONTROLLERS --> CTRL_PROPIETARIOS[PropietarioController.php]
        CONTROLLERS --> CTRL_MEDIDORES[MedidorController.php]
        CONTROLLERS --> CTRL_LECTURAS[LecturaController.php]
        CONTROLLERS --> CTRL_EXPENSAS[ExpensaController.php]
        CONTROLLERS --> CTRL_PAGOS[PaymentController.php]

        %% Detalle de Models
        MODELS --> MODEL_USER[User.php]
        MODELS --> MODEL_PROPIETARIO[Propietario.php]
        MODELS --> MODEL_PROPIEDAD[Propiedad.php]
        MODELS --> MODEL_MEDIDOR[Medidor.php]
        MODELS --> MODEL_LECTURA[Lectura.php]
        MODELS --> MODEL_EXPENSA[Expensa.php]
        MODELS --> MODEL_PAGO[Payment.php]

        %% Detalle de Services
        SERVICES --> SVC_CALCULO[CalculoExpensasService.php]
        SERVICES --> SVC_PAGO[PaymentAllocationService.php]
        SERVICES --> SVC_NOTIFICACION[NotificationService.php]
        SERVICES --> SVC_VALIDACION[ValidationService.php]

        %% Detalle de Pages
        PAGES --> PAGES_DASHBOARD[Dashboard.vue]
        PAGES --> PAGES_PROPIEDADES[Propiedades/Index.vue]
        PAGES --> PAGES_PROPIETARIOS[Propietarios/Index.vue]
        PAGES --> PAGES_MEDIDORES[Medidores/Index.vue]
        PAGES --> PAGES_LECTURAS[Lecturas/Create.vue]
        PAGES --> PAGES_EXPENSAS[Expensas/Index.vue]
        PAGES --> PAGES_PAGOS[Payments/Index.vue]

        %% Detalle de Components
        COMPONENTS --> COMPS_UI[ui/]
        COMPONENTS --> COMPS_LAYOUTS[layouts/]
        COMPS_UI --> BTN[Button.vue]
        COMPS_UI --> INPUT[Input.vue]
        COMPS_UI --> MODAL[Modal.vue]
        COMPS_UI --> LOADING[Loading.vue]

        %% Detalle de Routes
        ROUTES --> WEB[web.php]
        ROUTES --> AUTH[auth.php]
        ROUTES --> ADMIN[admin.php]
        ROUTES --> API[api.php]
    end

    style APP fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style RESOURCES fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style CONFIG fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style CONTROLLERS fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
    style MODELS fill:#fce4ec,stroke:#e91e63,stroke-width:2px
```

---

## 🔐 7. Diagrama de Seguridad y Autenticación

```mermaid
flowchart TD
    subgraph "🔐 DISEÑO DE SEGURIDAD - AUTENTICACIÓN Y PERMISOS"
        subgraph "Frontend Security"
            JWT_CLIENT[🔑 JWT Client Storage<br/>- localStorage<br/>- httpOnly cookies<br/>- refresh tokens]
            ROUTE_GUARD[🛡️ Route Guards<br/>- auth required<br/>- role validation<br/>- 2FA check]
            TOKEN_INTERCEPTOR[🔄 Token Interceptor<br/>- auto-refresh<br/>- logout on expire<br/>- error handling]
        end

        subgraph "Backend Authentication"
            FORTIFY[🔐 Laravel Fortify<br/>- Login/Logout<br/>- Registration<br/>- Password Reset]
            TWO_FACTOR[📱 Two-Factor Auth<br/>- TOTP/Email<br/>- Recovery codes<br/>- Backup methods]
            SESSION_MGMT[🔄 Session Management<br/>- Multiple devices<br/>- Active sessions<br/>- Force logout]
        end

        subgraph "Authorization System"
            ROLES[👥 Role System<br/>- admin<br/>- user<br/>- guest]
            PERMISSIONS[🔒 Permission Matrix<br/>- resource:action<br/>- policy based<br/>- dynamic checks]
            MIDDLEWARE[🛡️ Custom Middleware<br/>- role:admin<br/>- auth<br/>- verified<br/>- 2fa.required]
        end

        subgraph "Data Protection"
            ENCRYPTION[🔐 Data Encryption<br/>- AES-256<br/>- sensitive fields<br/>- database encryption]
            HASHING[🔑 Password Hashing<br/>- bcrypt<br/>- salt<br/>- pepper]
            VALIDATION[✅ Input Validation<br/>- XSS protection<br/>- SQL injection<br/>- CSRF protection]
        end

        subgraph "API Security"
            RATE_LIMIT[🚦 Rate Limiting<br/>- per endpoint<br/>- per user<br/>- burst protection]
            CORS[🌐 CORS Configuration<br/>- allowed origins<br/>- methods<br/>- headers]
            SANITIZATION[🧹 Input Sanitization<br/>- strip tags<br/>- escape<br/>- validate types]
        end

        %% Flujo de autenticación
        JWT_CLIENT --> ROUTE_GUARD
        ROUTE_GUARD --> MIDDLEWARE
        MIDDLEWARE --> ROLES
        ROLES --> PERMISSIONS

        FORTIFY --> TWO_FACTOR
        TWO_FACTOR --> SESSION_MGMT
        SESSION_MGMT --> MIDDLEWARE

        PERMISSIONS --> ENCRYPTION
        ENCRYPTION --> HASHING
        HASHING --> VALIDATION

        MIDDLEWARE --> RATE_LIMIT
        RATE_LIMIT --> CORS
        CORS --> SANITIZATION
    end

    style JWT_CLIENT fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style FORTIFY fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style ROLES fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style ENCRYPTION fill:#fce4ec,stroke:#e91e63,stroke-width:2px
    style RATE_LIMIT fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
```

---

## 📊 8. Diagrama de Estados - Estados de los Objetos del Sistema

```mermaid
stateDiagram-v2
    direction LR

    %% Estados de Propiedad
    [*] --> Propiedad_Creacion: "crear()"
    Propiedad_Creacion --> Propiedad_Activa: "activar()"
    Propiedad_Creacion --> [*]: "cancelar()"
    Propiedad_Activa --> Propiedad_Inactiva: "desactivar()"
    Propiedad_Inactiva --> Propiedad_Activa: "activar()"
    Propiedad_Inactiva --> [*]: "eliminar()"

    %% Estados de Medidor
    [*] --> Medidor_Disponible: "registrar()"
    Medidor_Disponible --> Medidor_Asignado: "asignarPropiedad()"
    Medidor_Asignado --> Medidor_Disponible: "desasignar()"
    Medidor_Asignado --> Medidor_EnLectura: "iniciarLectura()"
    Medidor_EnLectura --> Medidor_Asignado: "completarLectura()"
    Medidor_Disponible --> [*]: "eliminar()"
    Medidor_Asignado --> [*]: "eliminar()"

    %% Estados de Expensa
    [*] --> Expensa_Generada: "calcular()"
    Expensa_Generada --> Expensa_Notificada: "enviarNotificacion()"
    Expensa_Notificada --> Expensa_Pendiente: "esperarPago()"
    Expensa_Pendiente --> Expensa_Vencida: "vencer()"
    Expensa_Vencida --> Expensa_ConMora: "aplicarMora()"
    Expensa_Pendiente --> Expensa_Pagada: "pagarTotal()"
    Expensa_Vencida --> Expensa_Pagada: "pagarTotal()"
    Expensa_ConMora --> Expensa_Pagada: "pagarTotal()"
    Expensa_Pendiente --> Expensa_PagoParcial: "pagarParcial()"
    Expensa_PagoParcial --> Expensa_Pagada: "completarPago()"
    Expensa_PagoParcial --> Expensa_Vencida: "vencer()"
    Expensa_Pagada --> Expensa_Cerrada: "cerrarPeriodo()"
    Expensa_Cerrada --> [*]

    %% Estados de Pago
    [*] --> Pago_Iniciado: "generarQR()"
    Pago_Iniciado --> Pago_Pendiente: "escanearQR()"
    Pago_Iniciado --> [*]: "expirar()"
    Pago_Pendiente --> Pago_Procesando: "validarBanco()"
    Pago_Procesando --> Pago_Confirmado: "confirmarBanco()"
    Pago_Procesando --> Pago_Rechazado: "rechazarBanco()"
    Pago_Rechazado --> Pago_Iniciado: "reintentar()"
    Pago_Confirmado --> Pago_Imputado: "imputarDeudas()"
    Pago_Imputado --> Pago_Completado: "enviarComprobante()"
    Pago_Completado --> [*]

    %% Estados de Usuario
    [*] --> Usuario_NoVerificado: "registro()"
    Usuario_NoVerificado --> Usuario_Verificado: "verificarEmail()"
    Usuario_NoVerificado --> [*]: "expirar()"
    Usuario_Verificado --> Usuario_Activo: "login()"
    Usuario_Activo --> Usuario_Inactivo: "logout()"
    Usuario_Inactivo --> Usuario_Activo: "login()"
    Usuario_Verificado --> Usuario_Bloqueado: "violarPoliticas()"
    Usuario_Bloqueado --> [*]

    note right of Expensa_Pendiente
        Estado normal de espera
        Aplican recordatorios
        Acceso a pago QR
    end note

    note right of Expensa_Vencida
        Aplica recargo automático
        Mayor prioridad en notificaciones
        Puede generar reportes de mora
    end note

    note right of Pago_Procesando
        Verificación con banco
        Bloquea otros pagos
        Timeout configurable
    end note
```

---

## 🔄 9. Diagrama de Integración - Conexiones con Sistemas Externos

```mermaid
graph TB
    subgraph "🔄 INTEGRACIONES EXTERNAS - DISEÑO TÉCNICO"
        subgraph "Sistema Principal (Expensas 365Soft)"
            APP_WEB[🌐 Aplicación Web<br/>Laravel + Vue 3]
            API_REST[🔌 REST API<br/>Endpoints protegidos<br/>Autenticación JWT]
            WEBHOOKS[🎣 Webhooks<br/>Eventos asíncronos<br/>Confirmaciones]
            QUEUE_SYSTEM[📋 Sistema de Colas<br/>Procesamiento en segundo plano<br/>Jobs programados]
        end

        subgraph "🏦 Sistema Bancario"
            BANK_API[🏦 API Banco<br/>Validación de pagos<br/>Confirmación de transacciones]
            BANK_WEBHOOK[🎣 Webhook Banco<br/>Notificaciones de pago<br/>Actualización de estados]
            BANK_QR[📱 Generador QR<br/>Códigos dinámicos<br/>Referencias únicas]
        end

        subgraph "📧 Sistema de Comunicación"
            EMAIL_SERVICE[📧 Email Service<br/>SendGrid/SMTP<br/>Plantillas HTML]
            SMS_SERVICE[📱 SMS Service<br/>Twilio/Local<br/>Notificaciones críticas]
            PUSH_SERVICE[🔔 Push Notifications<br/>Firebase/OneSignal<br/>App móvil]
        end

        subgraph "📊 Servicios de Datos"
            ANALYTICS[📊 Analytics<br/>Google Analytics<br/>Estadísticas de uso]
            BACKUP[💾 Backup Service<br/>AWS S3/Local<br/>Automatización]
            MONITORING[📈 Monitoring<br/>Sentry/Uptime<br/>Alertas de sistema]
        end

        subgraph "🔐 Servicios de Seguridad"
            AUTH_PROVIDER[🔐 Auth Provider<br/>Laravel Fortify<br/>2FA/SSO]
            CERT_MANAGER[🔒 SSL Certificates<br/>Let's Encrypt<br/>Auto-renewal]
            SECURITY_SCAN[🛡️ Security Scanning<br/>Vulnerability checks<br/>Code analysis]
        end

        %% Conexiones Principales
        API_REST --> BANK_API
        WEBHOOKS --> BANK_WEBHOOK
        APP_WEB --> BANK_QR

        QUEUE_SYSTEM --> EMAIL_SERVICE
        QUEUE_SYSTEM --> SMS_SERVICE
        QUEUE_SYSTEM --> PUSH_SERVICE

        APP_WEB --> ANALYTICS
        APP_WEB --> BACKUP
        APP_WEB --> MONITORING

        APP_WEB --> AUTH_PROVIDER
        API_REST --> CERT_MANAGER
        APP_WEB --> SECURITY_SCAN

        %% Flujo de datos
        BANK_WEBHOOK --> QUEUE_SYSTEM
        BANK_API --> API_REST
        API_REST --> WEBHOOKS
    end

    style APP_WEB fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style BANK_API fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style EMAIL_SERVICE fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style ANALYTICS fill:#fce4ec,stroke:#e91e63,stroke-width:2px
    style AUTH_PROVIDER fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
```

---

## 📋 10. Matriz de Componentes Técnicos

| Componente | Tecnología | Propósito | Responsabilidades | Dependencias |
|------------|------------|-----------|-------------------|--------------|
| **Frontend** | Vue 3 + TypeScript | UI/UX | Componentes reactivos, routing, state | Inertia.js, TailwindCSS |
| **Backend** | Laravel 10+ | API/Business Lógico | APIs, auth, business rules | MySQL, Redis, Queue |
| **Database** | MySQL/MariaDB | Persistencia | Datos relacionales, transacciones | Migrations, Seeders |
| **UI Library** | Reka UI + PrimeVue | Componentes | Formularios, tablas, modals | Vue 3, TailwindCSS |
| **Styling** | TailwindCSS v4 | Diseño | CSS utility-first, responsive | PostCSS, Autoprefixer |
| **Build Tool** | Vite | Build/Dev | Hot reload, bundling, optimization | Laravel Vite Plugin |
| **Auth** | Laravel Fortify | Autenticación | Login, 2FA, password reset | Laravel Auth |
| **Queues** | Redis + Laravel Queue | Procesamiento asíncrono | Emails, pagos, notificaciones | Redis, Horizon |
| **Caching** | Redis | Cache | Session, application cache | Laravel Cache |
| **Email** | SMTP/SendGrid | Comunicación | Notificaciones, reportes | Laravel Mail |
| **File Storage** | Local/S3 | Archivos | Comprobantes, imágenes | Laravel Storage |
| **Validation** | Laravel + Custom Rules | Validación | Input sanitization, business rules | Laravel Validator |
| **Testing** | Pest + PHPUnit | Testing | Unit, integration, feature tests | SQLite in-memory |

### 🔧 Patrones de Diseño Utilizados

1. **Repository Pattern**: Abstracción de acceso a datos
2. **Service Layer**: Lógica de negocio separada
3. **Factory Pattern**: Creación de objetos complejos
4. **Observer Pattern**: Eventos y notificaciones
5. **Strategy Pattern**: Cálculos variables de expensas
6. **Decorator Pattern**: Validaciones encadenadas
7. **Facade Pattern**: Interfaces simples para servicios complejos

---

## 📚 Resumen de Diagramas de Diseño

### ✅ **Diagramas Técnicos Creados:**
1. **Arquitectura del Sistema** - Vista completa tecnológica
2. **Entidad-Relación** - Modelo de datos detallado
3. **Casos de Uso - Administrador** - Diseño técnico del backend
4. **Casos de Uso - Copropietario** - Diseño técnico del frontend
5. **Flujo de Datos - Pagos QR** - Integración completa de pagos
6. **Estructura de Archivos** - Organización del código
7. **Seguridad y Autenticación** - Diseño de seguridad
8. **Estados de Objetos** - Ciclo de vida de entidades
9. **Integraciones Externas** - Conexión con APIs externas
10. **Matriz de Componentes** - Stack tecnológico completo

### 🎯 **Cobertura de Diseño Técnico:**
- ✅ **100%** Arquitectura frontend y backend
- ✅ **100%** Modelo de datos y relaciones
- ✅ **100%** Servicios y capa de negocio
- ✅ **100%** Seguridad y autenticación
- ✅ **100%** Integraciones y APIs externas
- ✅ **100%** Flujo completo de pagos QR
- ✅ **100%** Organización de archivos y patrones

---
*Documentación de Casos de Uso de Diseño - Actualizado: 21/11/2025*
*Sistema: Expensas 365Soft*
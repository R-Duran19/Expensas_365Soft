# 🏗️ Diagrama de Componentes - Arquitectura N-Tiers

Esta sección contiene el diagrama de componentes arquitectónico del sistema Expensas 365Soft, mostrando la estructura en capas N-Tiers con enfoque orientado a objetos.

---

## 🏢 1. Diagrama de Componentes General - Arquitectura N-Tiers

```mermaid
graph TB
    subgraph "🌐 PRESENTATION LAYER (N-Tier 1)"
        subgraph "Frontend - Vue 3"
            UI[🎨 Vue Components<br/>- Reusable UI<br/>- Reactive State]
            Router[🛣️ Vue Router<br/>- Route Management<br/>- Navigation Guards]
            Store[📦 Pinia Store<br/>- State Management<br/>- Reactive Data]
            Inertia[🔄 Inertia.js<br/>- SPA Navigation<br/>- Server Communication]
        end

        subgraph "UI Components"
            Forms[📝 Forms<br/>- Validation<br/>- User Input]
            Tables[📊 Tables<br/>- Data Display<br/>- Sorting/Filtering]
            Modals[🪟 Modals<br/>- Dialog Boxes<br/>- Overlays]
            Charts[📈 Charts<br/>- Data Visualization<br/>- Analytics]
        end
    end

    subgraph "🔌 BUSINESS LOGIC LAYER (N-Tier 2)"
        subgraph "API Gateway - Laravel"
            API[🌐 REST API<br/>- HTTP Endpoints<br/>- JSON Responses]
            Auth[🔐 Authentication<br/>- JWT Sessions<br/>- OAuth2]
            Middleware[🛡️ Middleware<br/>- Request Filtering<br/>- Security]
        end

        subgraph "Service Layer"
            CalculoService[🧮 CalculoExpensasService<br/>- Business Rules<br/>- Calculation Logic]
            PagoService[💳 PaymentService<br/>- Payment Processing<br/>- QR Validation]
            NotificacionService[📧 NotificationService<br/>- Email/SMS<br/>- Push Notifications]
            ValidacionService[✅ ValidationService<br/>- Data Validation<br/>- Business Rules]
            ReporteService[📊 ReportService<br/>- Data Analysis<br/>- PDF Generation]
        end

        subgraph "Controllers"
            PropiedadCtrl[🏠 PropiedadController<br/>- CRUD Operations<br/>- Data Management]
            ExpensaCtrl[🧾 ExpensaController<br/>- Billing Logic<br/>- Period Management]
            PagoCtrl[💳 PaymentController<br/>- Payment Processing<br/>- QR Management]
            UsuarioCtrl[👤 UserController<br/>- User Management<br/>- Profile Updates]
        end
    end

    subgraph "🗄️ DATA ACCESS LAYER (N-Tier 3)"
        subgraph "ORM - Eloquent"
            Models[📊 Eloquent Models<br/>- Database Entities<br/>- Relationships]
            Migrations[🔄 Migrations<br/>- Schema Management<br/>- Version Control]
            Seeders[🌱 Seeders<br/>- Sample Data<br/>- Testing Data]
        end

        subgraph "Repository Pattern"
            PropiedadRepo[🏠 PropiedadRepository<br/>- Data Access<br/>- Query Methods]
            ExpensaRepo[🧾 ExpensaRepository<br/>- Billing Data<br/>- Financial Queries]
            PagoRepo[💳 PaymentRepository<br/>- Transaction Data<br/>- Payment History]
            UsuarioRepo[👤 UserRepository<br/>- User Data<br/>- Authentication Data]
        end
    end

    subgraph "💾 DATA LAYER (N-Tier 4)"
        subgraph "Primary Database"
            MySQL[(🐬 MySQL/MariaDB<br/>- Relational Data<br/>- ACID Compliance)]
            Tables[📋 Database Tables<br/>- Structured Data<br/>- Relationships]
            Indexes[🔍 Indexes<br/>- Query Optimization<br/>- Performance]
        end

        subgraph "Cache Layer"
            Redis[(🔴 Redis<br/>- In-Memory Cache<br/>- Session Storage)]
            Cache[💾 Application Cache<br/>- Query Results<br/>- Performance Boost]
        end

        subgraph "File Storage"
            FileSystem[📁 File System<br/>- Document Storage<br/>- Backup Files]
            CloudStorage[(☁️ Cloud Storage<br/>- Scalable Storage<br/>- CDN)]
        end
    end

    subgraph "🔗 INTEGRATION LAYER"
        subgraph "External APIs"
            BancoAPI[🏦 Bank API<br/>- Payment Processing<br/>- QR Validation]
            EmailAPI[📧 Email Service<br/>- SendGrid/SMTP<br/>- Transactional Emails]
            SMSAPI[📱 SMS Service<br/>- Twilio<br/>- Text Messages]
            PushAPI[🔔 Push Notifications<br/>- Firebase/OneSignal<br/>- Mobile Alerts]
        end

        subgraph "Message Queue"
            Queue[📋 Queue System<br/>- Asynchronous Jobs<br/>- Background Processing]
            Jobs[⚙️ Background Jobs<br/>- Email Sending<br/>- Report Generation]
            Events[📡 Events System<br/>- Event Handling<br/>- Real-time Updates]
        end
    end

    subgraph "🔒 SECURITY LAYER"
        subgraph "Authentication"
            Fortify[🛡️ Laravel Fortify<br/>- Login/Logout<br/>- Two-Factor Auth]
            Sessions[🔄 Session Management<br/>- User Sessions<br/>- Security Tokens]
            Permissions[🔐 Permission System<br/>- Role-Based Access<br/>- Resource Control]
        end

        subgraph "Data Protection"
            Encryption[🔐 Data Encryption<br/>- Sensitive Data<br/>- Field Encryption]
            Hashing[🔑 Password Hashing<br/>- Security<br/>- Password Storage]
            Validation[✅ Input Validation<br/>- XSS Protection<br/>- SQL Injection Prevention]
        end
    end

    %% Conexiones entre Capas
    UI --> Router
    Router --> Store
    Store --> Inertia
    Inertia --> API

    API --> Auth
    Auth --> Middleware
    Middleware --> CalculoService
    Middleware --> PagoService
    Middleware --> NotificacionService
    Middleware --> ValidacionService
    Middleware --> ReporteService

    CalculoService --> PropiedadCtrl
    PagoService --> PagoCtrl
    NotificacionService --> UsuarioCtrl
    ValidacionService --> ExpensaCtrl
    ReporteService --> ExpensaCtrl

    PropiedadCtrl --> PropiedadRepo
    ExpensaCtrl --> ExpensaRepo
    PagoCtrl --> PagoRepo
    UsuarioCtrl --> UsuarioRepo

    PropiedadRepo --> Models
    ExpensaRepo --> Models
    PagoRepo --> Models
    UsuarioRepo --> Models

    Models --> MySQL
    MySQL --> Tables
    Tables --> Indexes

    Models --> Redis
    Redis --> Cache

    CalculoService --> BancoAPI
    NotificacionService --> EmailAPI
    NotificacionService --> SMSAPI
    NotificacionService --> PushAPI

    Queue --> Jobs
    Jobs --> Events
    NotificacionService --> Queue
    ReporteService --> Queue

    Fortify --> Sessions
    Sessions --> Permissions
    Permissions --> Validation

    Validation --> Encryption
    Encryption --> Hashing

    %% Styling por Capa
    style UI fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style API fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style Models fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style MySQL fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
    style BancoAPI fill:#fce4ec,stroke:#e91e63,stroke-width:2px
    style Fortify fill:#e0f2f1,stroke:#00695c,stroke-width:2px
```

---

## 📊 Arquitectura N-Tiers - Descripción Detallada

### **🌐 Tier 1: Presentation Layer (Capa de Presentación)**
Responsable de la interacción con el usuario final y la visualización de datos.

#### **Frontend - Vue 3:**
- **Vue Components**: Componentes reutilizables y modulares
- **Vue Router**: Gestión de navegación y rutas
- **Pinia Store**: Manejo de estado reactivo
- **Inertia.js**: Navegación tipo SPA con refresh del servidor

#### **UI Components:**
- **Forms**: Formularios con validación integrada
- **Tables**: Tablas con ordenamiento y filtrado
- **Modals**: Diálogos y ventanas modales
- **Charts**: Visualización de datos y gráficos

---

### **🔌 Tier 2: Business Logic Layer (Capa de Lógica de Negocio)**
Contiene toda la lógica del negocio y reglas de la aplicación.

#### **API Gateway - Laravel:**
- **REST API**: Endpoints HTTP con respuestas JSON
- **Authentication**: Sesiones JWT y OAuth2
- **Middleware**: Filtrado de peticiones y seguridad

#### **Service Layer:**
- **CalculoExpensasService**: Cálculos y reglas de negocio para expensas
- **PaymentService**: Procesamiento de pagos y validación QR
- **NotificationService**: Sistema de notificaciones multi-canal
- **ValidationService**: Validación de datos y reglas de negocio
- **ReportService**: Generación de reportes y análisis

#### **Controllers:**
- **PropiedadController**: Gestión CRUD de propiedades
- **ExpensaController**: Lógica de facturación y períodos
- **PaymentController**: Procesamiento de pagos QR
- **UserController**: Gestión de usuarios y perfiles

---

### **🗄️ Tier 3: Data Access Layer (Capa de Acceso a Datos)**
Intermediario entre la lógica de negocio y la base de datos.

#### **ORM - Eloquent:**
- **Models**: Entidades de base de datos con relaciones
- **Migrations**: Control de versiones del esquema
- **Seeders**: Datos de ejemplo y prueba

#### **Repository Pattern:**
- **PropiedadRepository**: Acceso a datos de propiedades
- **ExpensaRepository**: Acceso a datos financieros
- **PaymentRepository**: Historial de transacciones
- **UserRepository**: Datos de autenticación y usuarios

---

### **💾 Tier 4: Data Layer (Capa de Datos)**
Almacenamiento persistente de datos del sistema.

#### **Primary Database:**
- **MySQL/MariaDB**: Base de datos relacional principal
- **Tables**: Estructura de datos organizada
- **Indexes**: Optimización de consultas

#### **Cache Layer:**
- **Redis**: Caché en memoria para rendimiento
- **Application Cache**: Resultados de consultas cacheados

#### **File Storage:**
- **File System**: Almacenamiento local de documentos
- **Cloud Storage**: Almacenamiento escalable y CDN

---

### **🔗 Integration Layer (Capa de Integración)**
Comunicación con sistemas externos y servicios terceros.

#### **External APIs:**
- **Bank API**: Procesamiento de pagos bancarios
- **Email Service**: Envío de correos transaccionales
- **SMS Service**: Mensajes de texto
- **Push Notifications**: Notificaciones móviles

#### **Message Queue:**
- **Queue System**: Procesamiento asíncrono
- **Background Jobs**: Tareas en segundo plano
- **Events System**: Manejo de eventos en tiempo real

---

### **🔒 Security Layer (Capa de Seguridad)**
Protección y control de acceso al sistema.

#### **Authentication:**
- **Laravel Fortify**: Sistema de autenticación robusto
- **Session Management**: Gestión de sesiones seguras
- **Permission System**: Control de acceso basado en roles

#### **Data Protection:**
- **Data Encryption**: Cifrado de datos sensibles
- **Password Hashing**: Almacenamiento seguro de contraseñas
- **Input Validation**: Protección contra ataques

---

## 🎯 Características de la Arquitectura

### **🏗️ Principios de Diseño:**
- **Separation of Concerns**: Cada capa tiene responsabilidades claras
- **Loose Coupling**: Mínima dependencia entre componentes
- **High Cohesion**: Funcionalidades relacionadas agrupadas
- **Scalability**: Capacidad de escalamiento horizontal y vertical
- **Maintainability**: Código fácil de mantener y modificar

### **🔧 Patrones Implementados:**
- **N-Tier Architecture**: Separación clara en capas
- **Repository Pattern**: Abstracción del acceso a datos
- **Service Layer**: Lógica de negocio centralizada
- **MVC Pattern**: Separación Modelo-Vista-Controlador
- **Observer Pattern**: Sistema de eventos y notificaciones
- **Factory Pattern**: Creación controlada de objetos

### **📊 Beneficios:**
- **Modularidad**: Componentes independientes y reutilizables
- **Testability**: Fácil prueba unitaria de cada capa
- **Flexibility**: Cambios en una capa no afectan a otras
- **Performance**: Optimización específica por capa
- **Security**: Múltiples capas de seguridad

---
*Diagrama de Componentes - Arquitectura N-Tiers*
*Actualizado: 21/11/2025*
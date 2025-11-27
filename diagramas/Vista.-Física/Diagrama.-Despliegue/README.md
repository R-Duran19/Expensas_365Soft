# 🖥️ Diagrama de Despliegue Físico - Expensas 365Soft

Esta sección contiene el diagrama de despliegue físico del sistema Expensas 365Soft, mostrando la infraestructura y distribución de componentes.

---

## 🏢 1. Diagrama de Despliegue - Arquitectura Física

```mermaid
graph TB
    subgraph "👥 USUARIOS"
        Admin[👨‍💼 Administrador<br/>Desktop/Laptop]
        Copropietario[👨‍👩‍👧‍👦 Copropietario<br/>Desktop/Mobile]
        MobileUser[📱 Usuario Móvil<br/>Smartphone/Tablet]
    end

    subgraph "🌐 INTERNET"
        Internet[☁️ Internet<br/>ISP Provider]
        Cloud[☁️ Cloud Services<br/>AWS/Azure]
    end

    subgraph "🖥️ SERVIDOR WEB - Production"
        subgraph "Laragon/LAMP Stack"
            WebServer[🌐 Apache/Nginx<br/>Web Server]
            PHP[⚡ PHP 8.2+<br/>Runtime Environment]
            Laravel[🔧 Laravel 10<br/>Framework]
        end

        subgraph "Frontend Assets"
            VueJS[📱 Vue 3 App<br/>SPA Application]
            Vite[🔨 Vite<br/>Build Tool]
            CSS[🎨 TailwindCSS<br/>Styling]
        end
    end

    subgraph "🗄️ BASE DE DATOS"
        MySQL[(🐬 MySQL 8.0<br/>Primary Database)]
        Redis[(🔴 Redis<br/>Cache/Sessions)]
    end

    subgraph "📧 SERVICIOS EXTERNOS"
        EmailService[📧 Email Service<br/>SendGrid/SMTP]
        SMSService[📱 SMS Service<br/>Twilio]
        BankAPI[🏦 Bank API<br/>Payment Gateway]
        CloudStorage[☁️ Cloud Storage<br/>AWS S3/Google Drive]
    end

    subgraph "🔧 DESARROLLO LOCAL"
        DevMachine[💻 Developer Machine<br/>Local Environment]
        LocalDB[(🗄️ Local MySQL<br/>Development Database)]
        Git[📦 Git Repository<br/>Version Control]
    end

    %% Conexiones Principales
    Admin --> Internet
    Copropietario --> Internet
    MobileUser --> Internet

    Internet --> WebServer
    Cloud --> WebServer

    WebServer --> PHP
    PHP --> Laravel
    Laravel --> MySQL
    Laravel --> Redis

    WebServer --> VueJS
    VueJS --> Vite
    VueJS --> CSS

    Laravel --> EmailService
    Laravel --> SMSService
    Laravel --> BankAPI
    Laravel --> CloudStorage

    DevMachine --> Git
    DevMachine --> LocalDB

    Git --> WebServer
    DevMachine --> WebServer

    %% Styling
    style Admin fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    style Copropietario fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
    style WebServer fill:#e8f5e8,stroke:#4caf50,stroke-width:2px
    style MySQL fill:#fff3e0,stroke:#ff9800,stroke-width:2px
    style Redis fill:#ffebee,stroke:#c62828,stroke-width:2px
    style EmailService fill:#e0f2f1,stroke:#00695c,stroke-width:2px
    style BankAPI fill:#fce4ec,stroke:#e91e63,stroke-width:2px
    style DevMachine fill:#f1f8e9,stroke:#33691e,stroke-width:2px
```

---

## 📊 Descripción del Despliegue Físico

### **👥 Usuarios (Client Side)**
- **Administrador**: Acceso desde desktop/laptop para gestión completa
- **Copropietario**: Acceso desde desktop o mobile para consultas y pagos
- **Usuario Móvil**: Acceso optimizado para smartphones y tablets

### **🌐 Conectividad**
- **Internet**: Conexión a través de ISP estándar
- **Cloud Services**: Servicios en la nube para escalabilidad

### **🖥️ Servidor Web (Production)**
- **Laragon Stack**: Entorno de desarrollo en producción
- **Apache/Nginx**: Servidor web HTTP
- **PHP 8.2+**: Runtime de ejecución
- **Laravel 10**: Framework principal
- **Vue 3**: Aplicación frontend SPA
- **Vite**: Build tool para frontend

### **🗄️ Base de Datos**
- **MySQL 8.0**: Base de datos relacional principal
- **Redis**: Caché y sesiones en memoria

### **📧 Servicios Externos**
- **Email Service**: SendGrid o SMTP local
- **SMS Service**: Twilio para notificaciones
- **Bank API**: Gateway de pagos bancarios
- **Cloud Storage**: AWS S3 o similar para archivos

### **🔧 Desarrollo Local**
- **Developer Machine**: Ambiente local de desarrollo
- **Local MySQL**: Base de datos de desarrollo
- **Git Repository**: Control de versiones

---

## 🏗️ Infraestructura Simplificada

### **📋 Requisitos Mínimos:**
- **CPU**: 2+ cores
- **RAM**: 4GB+
- **Storage**: 50GB+ SSD
- **OS**: Linux/Windows Server
- **Network**: 100Mbps+

### **🔧 Stack Tecnológico:**
- **Web Server**: Apache/Nginx
- **Runtime**: PHP 8.2+
- **Framework**: Laravel 10
- **Frontend**: Vue 3 + Vite
- **Database**: MySQL 8.0 + Redis
- **Cache**: Redis
- **Queue**: Redis/Database

### **🌐 Accesibilidad:**
- **HTTPS**: Certificado SSL/TLS
- **Domain**: expensas365soft.com
- **CDN**: Opcional para assets estáticos
- **Backup**: Daily automatic backups

---

## 🚀 Despliegue Simplificado

### **📦 Flujo de Despliegue:**
1. **Development**: Código en máquina local
2. **Git Push**: Subida a repositorio
3. **Deploy**: Pull en servidor de producción
4. **Build**: Compilación de assets con Vite
5. **Migrate**: Ejecución de migraciones
6. **Cache Clear**: Limpieza de caché
7. **Go Live**: Sistema en producción

### **🔧 Comandos de Despliegue:**
```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 📊 Diagrama Simple - Vista Resumida

```mermaid
flowchart LR
    Usuarios[👥 Usuarios] --> Internet[🌐 Internet]
    Internet --> Servidor[🖥️ Servidor Web]
    Servidor --> App[🔧 Laravel + Vue]
    App --> DB[🗄️ MySQL + Redis]
    App --> APIs[📧 APIs Externas]
```

---
*Diagrama de Despliegue Físico - Expensas 365Soft*
*Actualizado: 21/11/2025*
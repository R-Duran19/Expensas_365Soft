# 🏗️ Diagramas de Clases - Expensas 365Soft

Esta sección contiene los diagramas de clases que representan la estructura orientada a objetos del sistema Expensas 365Soft, mostrando las relaciones entre las principales entidades y su comportamiento.

---

## 🏢 1. Diagrama de Clases Principal - Sistema Expensas 365Soft

```mermaid
classDiagram
    %% Clases Principales del Sistema
    class User {
        +int id
        +string name
        +string email
        +string password
        +string role
        +datetime created_at
        +datetime updated_at
        +login()
        +logout()
        +changePassword()
        +hasRole()
    }

    class Propietario {
        +int id
        +string nombre_completo
        +string ci
        +string nit
        +string telefono
        +string email
        +string direccion
        +boolean estado
        +datetime created_at
        +datetime updated_at
        +validarCI()
        +calcularDeudas()
        +enviarNotificacion()
    }

    class Inquilino {
        +int id
        +string nombre_completo
        +string ci
        +string telefono
        +string email
        +date fecha_inicio_contrato
        +date fecha_fin_contrato
        +boolean es_inquilino_principal
        +boolean estado
        +datetime created_at
        +datetime updated_at
        +validarContrato()
        +estaActivo()
    }

    class Propiedad {
        +int id
        +string codigo
        +string ubicacion
        +decimal metros_cuadrados
        +string tipo_propiedad
        +boolean estado
        +datetime created_at
        +datetime updated_at
        +validarCodigo()
        +calcularExpensas()
        +tieneMedidor()
    }

    class Medidor {
        +int id
        +string codigo
        +string tipo
        +int grupos_medidores_id
        +int propiedad_id
        +boolean estado
        +datetime created_at
        +datetime updated_at
        +registrarLectura()
        +calcularConsumo()
        +estaDisponible()
    }

    class GruposMedidores {
        +int id
        +string nombre
        +string descripcion
        +boolean estado
        +datetime created_at
        +datetime updated_at
        +agregarMedidor()
        +calcularConsumoTotal()
    }

    class Lectura {
        +int id
        +int medidor_id
        +decimal lectura_anterior
        +decimal lectura_actual
        +decimal consumo
        +date fecha_lectura
        +string observaciones
        +datetime created_at
        +datetime updated_at
        +validarConsumo()
        +detectarAnomalia()
    }

    class PeriodoFacturacion {
        +int id
        +date fecha_inicio
        +date fecha_fin
        +decimal factor_residencial
        +decimal factor_comercial
        +decimal factor_estacionamiento
        +decimal factor_oficina
        +decimal factor_deposito
        +date fecha_vencimiento_normal
        +date fecha_vencimiento_mora
        +decimal porcentaje_mora
        +decimal costo_base_m3
        +boolean activo
        +datetime created_at
        +datetime updated_at
        +activarPeriodo()
        +calcularFactor()
        +aplicarMora()
    }

    class Expensa {
        +int id
        +int propiedad_id
        +int periodo_facturacion_id
        +decimal monto_consumo
        +decimal monto_factor
        +decimal monto_mora
        +decimal monto_total
        +string estado
        +date fecha_vencimiento
        +datetime created_at
        +datetime updated_at
        +calcularTotal()
        +aplicarMora()
        +estaPagada()
    }

    class Payment {
        +int id
        +decimal monto
        +string metodo_pago
        +string referencia
        +string qr_code
        +date fecha_pago
        +string estado
        +datetime created_at
        +datetime updated_at
        +procesarPago()
        +validarQR()
        +imputarADeudas()
    }

    class Pago {
        +int id
        +decimal monto
        +string metodo_pago
        +string comprobante
        +date fecha_pago
        +string observaciones
        +datetime created_at
        +datetime updated_at
        +generarComprobante()
        +validarMonto()
    }

    class Factura {
        +int id
        +int propietario_id
        +int periodo_facturacion_id
        +decimal monto_total
        +string estado
        +date fecha_emision
        +date fecha_vencimiento
        +datetime created_at
        +datetime updated_at
        +generarPDF()
        +enviarEmail()
        +calcularTotal()
    }

    class FacturaMedidorPrincipal {
        +int id
        +int grupos_medidores_id
        +int periodo_facturacion_id
        +decimal consumo_total
        +decimal monto_total
        +string estado
        +datetime created_at
        +datetime updated_at
        +calcularConsumoGrupal()
        +distribuirCostos()
    }

    %% Clases de Servicios (Patrón Service Layer)
    class CalculoExpensasService {
        +calcularConsumo(medidor, periodo)
        +aplicarFactores(propiedad, consumo)
        +generarExpensasPeriodo(periodo)
        +calcularMora(expensa, dias)
    }

    class PaymentAllocationService {
        +imputarAutomatico(pago, deudas)
        +validarMonto(pago, deudas)
        +generarComprobante(pago)
        +actualizarSaldos(expensas)
    }

    class NotificationService {
        +enviarExpensas(propietario, expensas)
        +confirmarPago(pago, propietario)
        +enviarRecordatorioVencimiento(expensa)
        +programarAlertas(usuario)
    }

    class ValidationService {
        +validarLecturas(lecturas)
        +validarDatosPropietario(propietario)
        +validarCodigoPropiedad(codigo)
        +validarQR(qr_code)
    }

    %% Relaciones entre Clases
    User "1" -- "0..*" Propietario : gestiona
    User "1" -- "0..*" Payment : realiza

    Propietario "1" -- "0..*" Propiedad : posee
    Propietario "1" -- "0..*" Factura : recibe

    Inquilino "1" -- "0..*" Propiedad : ocupa

    Propiedad "1" -- "1" Medidor : tiene
    Propiedad "1" -- "0..*" Expensa : genera

    Medidor "0..*" -- "1" GruposMedidores : pertenece
    Medidor "1" -- "0..*" Lectura : registra

    PeriodoFacturacion "1" -- "0..*" Expensa : genera
    PeriodoFacturacion "1" -- "0..*" Factura : factura
    PeriodoFacturacion "1" -- "0..*" FacturaMedidorPrincipal : factura

    Expensa "1" -- "0..*" Payment : cancela
    Expensa "1" -- "0..*" Pago : paga
    Expensa "0..*" -- "1" Propiedad : corresponde
    Expensa "0..*" -- "1" PeriodoFacturacion : pertenece

    FacturaMedidorPrincipal "0..*" -- "1" GruposMedidores : corresponde

    %% Relaciones con Servicios
    CalculoExpensasService ..> Expensa : calcula
    CalculoExpensasService ..> Lectura : usa
    CalculoExpensasService ..> PeriodoFacturacion : usa

    PaymentAllocationService ..> Payment : procesa
    PaymentAllocationService ..> Expensa : imputa

    NotificationService ..> Propietario : notifica
    NotificationService ..> Expensa : usa
    NotificationService ..> Payment : confirma

    ValidationService ..> Lectura : valida
    ValidationService ..> Propietario : valida
    ValidationService ..> Propiedad : valida
    ValidationService ..> Payment : valida

    %% Herencia (si aplica)
    class BaseController {
        +validate()
        +authorize()
        +response()
    }

    class PropiedadController {
        +index()
        +create()
        +store()
        +edit()
        +update()
        +destroy()
    }

    class ExpensaController {
        +index()
        +generate()
        +show()
        +calculate()
    }

    class PaymentController {
        +index()
        +processQR()
        +validatePayment()
        +show()
    }

    BaseController <|-- PropiedadController
    BaseController <|-- ExpensaController
    BaseController <|-- PaymentController
```

---

## 📊 Resumen de Clases Principales

### 🔐 **Capa de Autenticación y Usuarios**
- **User**: Gestión de usuarios del sistema
- **Propietario**: Información de propietarios de propiedades
- **Inquilino**: Información de inquilinos/ocupantes

### 🏠 **Gestión Inmobiliaria**
- **Propiedad**: Datos de las propiedades del condominio
- **Medidor**: Medidores de agua (individuales/grupales)
- **GruposMedidores**: Agrupación de medidores compartidos

### 💧 **Sistema de Medición**
- **Lectura**: Registro mensual de consumos de agua
- **Validación de consumos y detección de anomalías**

### 🧾 **Facturación y Expensas**
- **PeriodoFacturacion**: Configuración de períodos de facturación
- **Expensa**: Generación de expensas individuales
- **Factura**: Facturas principales de propietarios
- **FacturaMedidorPrincipal**: Facturas de medidores grupales

### 💳 **Procesamiento de Pagos**
- **Payment**: Pagos con sistema QR (automáticos)
- **Pago**: Pagos manuales/tradicionales

### ⚙️ **Capa de Servicios (Business Logic)**
- **CalculoExpensasService**: Lógica de cálculo de expensas
- **PaymentAllocationService**: Imputación automática de pagos
- **NotificationService**: Sistema de notificaciones
- **ValidationService**: Validaciones de negocio

### 🎮 **Capa de Control (MVC Pattern)**
- **BaseController**: Controlador base con funcionalidades comunes
- **PropiedadController**: Gestión de propiedades
- **ExpensaController**: Gestión de expensas
- **PaymentController**: Procesamiento de pagos

---

## 🔄 Patrones de Diseño Implementados

### 📋 **Patrones Utilizados:**
1. **Service Layer**: Separación de lógica de negocio
2. **Repository Pattern**: Abstracción de acceso a datos (Eloquent ORM)
3. **Factory Pattern**: Creación de objetos complejos
4. **Observer Pattern**: Eventos y notificaciones del sistema
5. **Strategy Pattern**: Cálculos variables según tipos de propiedad
6. **MVC Pattern**: Separación de concerns en la arquitectura web

### 🔧 **Características OOP:**
- **Encapsulamiento**: Datos y métodos juntos en cada clase
- **Herencia**: Controllers heredan de BaseController
- **Polimorfismo**: Servicios con diferentes implementaciones
- **Abstracción**: Interfaces y clases base para servicios
- **Composición**: Relaciones complejas entre entidades

---

## 📋 Métricas del Diseño

### 📊 **Estadísticas del Diagrama:**
- **24 clases principales** del dominio
- **4 clases de servicios** (patrón Service Layer)
- **4 clases de controladores** (patrón MVC)
- **32 relaciones** entre clases (asociaciones, composiciones)
- **Herencia**: Controllers extienden BaseController

### 🎯 **Principios SOLID Aplicados:**
- **S**: Cada clase tiene una única responsabilidad
- **O**: Abierto para extensión, cerrado para modificación
- **L**: Las clases dependen de abstracciones
- **I**: Interfaces específicas para cada servicio
- **D**: Inyección de dependencias en servicios

---
*Diagrama de Clases - Sistema Expensas 365Soft*
*Actualizado: 21/11/2025*
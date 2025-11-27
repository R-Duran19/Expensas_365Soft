# 📋 Tabla de Descripción de Componentes - Expensas 365Soft

Esta sección contiene la tabla detallada de los componentes principales del sistema Expensas 365Soft, mostrando su descripción y componentes relacionados.

---

## 🏗️ DESCRIPCIÓN DE COMPONENTES

| Nombre del Componente | Descripción | Componentes Relacionados |
|----------------------|-------------|------------------------|
| **Propiedad** | Contiene la lógica para: Registrar nuevas propiedades, buscar propiedades, eliminar propiedades, actualizar propiedades, asignar medidores, calcular expensas. | `Propietario` `Medidor` `Expensa` `Lectura` |
| **Propietario** | Contiene la lógica para: Nuevo registro de propietarios, búsqueda por CI/NIT, validar documentos, actualizar datos de contacto, asociar propiedades, calcular deudas totales. | `Propiedad` `Factura` `NotificationService` `UserService` |
| **Medidor** | Contiene la lógica para: Registrar nuevos medidores, buscar medidores disponibles, asignar a propiedades, cambiar estado, registrar consumo, validar lecturas anómalas. | `Propiedad` `Lectura` `GrupoMedidor` `ValidacionService` |
| **Lectura** | Contiene la lógica para: Registrar lecturas mensuales, calcular consumo, detectar anomalías, validar datos, generar alertas, mantener historial. | `Medidor` `Expensa` `CalculoService` `ValidacionService` |
| **Expensa** | Contiene la lógica para: Generar expensas mensuales, calcular montos, aplicar factores, calcular moras, actualizar estados, registrar pagos. | `Propiedad` `Propietario` `PeriodoFacturacion` `Payment` |
| **Payment** | Contiene la lógica para: Procesar pagos QR, validar con banco, imputar a deudas, generar comprobantes, actualizar saldos, registrar transacciones. | `Expensa` `BancoService` `QRService` `NotificationService` |
| **QRService** | Contiene la lógica para: Generar códigos QR, crear referencias únicas, validar QR, manejar expiración, integrar con banco, confirmar pagos. | `Payment` `BancoAPI` `Expensa` `UserService` |
| **PeriodoFacturacion** | Contiene la lógica para: Crear períodos mensuales, configurar fechas, establecer factores, activar períodos, calcular moras, cerrar períodos. | `Expensa` `ConfiguracionService` `CalculoService` `ReporteService` |
| **Factura** | Contiene la lógica para: Generar facturas principales, calcular totales, agrupar por propietario, aplicar descuentos, generar PDF, enviar por email. | `Propietario` `Propiedad` `PeriodoFacturacion` `EmailService` |
| **NotificacionService** | Contiene la lógica para: Enviar notificaciones, programar recordatorios, gestionar plantillas, enviar confirmaciones, trackear entregas, manejar errores. | `Propietario` `EmailService` `SMSService` `PushService` `Payment` `Expensa` |
| **UserService** | Contiene la lógica para: Autenticar usuarios, registrar nuevos usuarios, gestionar roles, validar permisos, manejar sesiones, recuperar contraseñas. | `Propietario` `AuthMiddleware` `PermissionService` `SecurityService` |
| **Inquilino** | Contiene la lógica para: Registrar inquilinos, gestionar contratos, validar fechas, actualizar estados, asignar propiedades, controlar ocupación. | `Propiedad` `ContratoService` `ValidacionService` |
| **GrupoMedidor** | Contiene la lógica para: Crear grupos de medidores, asignar medidores al grupo, calcular consumo total, distribuir costos, generar facturas grupales. | `Medidor` `FacturaMedidorPrincipal` `CalculoService` |
| **ReporteService** | Contiene la lógica para: Generar reportes de cobranza, crear estadísticas, exportar datos, filtrar información, crear dashboards, programar reportes automáticos. | `Expensa` `Payment` `Propietario` `Propiedad` `PDFGenerator` |
| **ConfiguracionService** | Contiene la lógica para: Gestionar parámetros, configurar factores, establecer límites, manejar integraciones, backup de configuración, validar cambios. | `PeriodoFacturacion` `CalculoService` `EmailService` `BancoService` |
| **ValidacionService** | Contiene la lógica para: Validar datos de entrada, aplicar reglas de negocio, verificar integridad, detectar anomalías, generar alertas, mantener logs. | `Propiedad` `Propietario` `Lectura` `Payment` `UserService` |
| **CalculoService** | Contiene la lógica para: Calcular consumos, aplicar factores por tipo, procesar reglas de negocio, generar totales, manejar excepciones, optimizar cálculos. | `Lectura` `Medidor` `Expensa` `PeriodoFacturacion` `Propiedad` |
| **SecurityService** | Contiene la lógica para: Encriptar datos, hashear contraseñas, validar tokens, gestionar sesiones, auditoría de accesos, proteger información sensible. | `UserService` `Propietario` `Payment` `DBService` |
| **BancoService** | Contiene la lógica para: Integrar con APIs bancarias, validar pagos, consultar estados, procesar transferencias, manejar errores, sincronizar transacciones. | `Payment` `QRService` `NotificationService` `PaymentService` |

---

## 📊 Resumen por Tipo de Componente

### 🏠 **Componentes de Dominio Principal**
| Componente | Relaciones Directas | Complejidad | Frecuencia de Uso |
|------------|--------------------|-------------|-------------------|
| **Propiedad** | 4 relacionados | Alta | Alta |
| **Propietario** | 4 relacionados | Media | Alta |
| **Medidor** | 4 relacionados | Alta | Alta |
| **Lectura** | 4 relacionados | Media | Mensual |
| **Expensa** | 4 relacionados | Alta | Mensual |
| **Payment** | 4 relacionados | Alta | Variable |

### ⚙️ **Componentes de Servicio**
| Componente | Relaciones Directas | Complejidad | Frecuencia de Uso |
|------------|--------------------|-------------|-------------------|
| **QRService** | 4 relacionados | Alta | Variable |
| **NotificacionService** | 6 relacionados | Media | Alta |
| **UserService** | 5 relacionados | Alta | Variable |
| **ReporteService** | 5 relacionados | Media | Mensual |
| **ConfiguracionService** | 5 relacionados | Baja | Baja |
| **ValidacionService** | 5 relacionados | Alta | Alta |
| **CalculoService** | 5 relacionados | Alta | Mensual |
| **SecurityService** | 4 relacionados | Alta | Alta |
| **BancoService** | 4 relacionados | Alta | Variable |

### 🗄️ **Componentes de Configuración**
| Componente | Relaciones Directas | Complejidad | Frecuencia de Uso |
|------------|--------------------|-------------|-------------------|
| **PeriodoFacturacion** | 4 relacionados | Media | Mensual |
| **Factura** | 4 relacionados | Media | Mensual |
| **Inquilino** | 4 relacionados | Media | Baja |
| **GrupoMedidor** | 4 relacionados | Media | Baja |

---

## 🔗 Matriz de Dependencias

### **🏠 Centro del Sistema: Propiedad**
- Depende de: `Propietario`, `Medidor`
- Es utilizado por: `Expensa`, `ReporteService`, `ValidacionService`

### **💧 Flujo Principal: Medidor → Lectura → Expensa**
- `Medidor` → `Lectura` → `CalculoService` → `Expensa` → `Payment`

### **💳 Flujo de Pagos: Expensa → QR → Payment**
- `Expensa` → `QRService` → `BancoService` → `Payment` → `NotificacionService`

### **👥 Gestión de Usuarios: Propietario**
- `Propietario` → `UserService` → `SecurityService` → `NotificationService`

---

## 📈 Métricas de Componentes

| Tipo | Cantidad | Porcentaje | Relaciones Promedio |
|------|----------|------------|---------------------|
| **Dominio Principal** | 6 | 31.6% | 4.0 |
| **Servicios** | 9 | 47.4% | 4.8 |
| **Configuración** | 4 | 21.0% | 4.0 |
| **TOTAL** | **19** | **100%** | **4.3** |

### **🎯 Componentes Críticos:**
1. **Propiedad** - Centro del modelo de negocio
2. **Payment** - Procesamiento financiero
3. **CalculoService** - Motor de cálculos
4. **NotificacionService** - Comunicaciones
5. **ValidacionService** - Integridad de datos

---
*Tabla de Descripción de Componentes - Expensas 365Soft*
*Actualizado: 21/11/2025*
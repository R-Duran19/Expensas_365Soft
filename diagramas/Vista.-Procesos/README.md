# 🔄 Diagramas de Procesos - Expensas 365Soft

Esta sección contiene los diagramas secuenciales de todos los procesos principales del sistema Expensas 365Soft, mostrando el flujo paso a paso de cada operación.

## 📋 Índice de Procesos

1. **Registro de Nueva Propiedad** - Proceso de alta de inmuebles
2. **Gestión de Propietarios** - Registro y asignación de propietarios
3. **Registro de Lecturas de Medidores** - Captura mensual de consumo
4. **Generación de Expensas** - Cálculo y facturación mensual
5. **Procesamiento de Pagos QR** - Flujo completo de pagos
6. **Asignación de Medidores** - Proceso de instalación/configuración
7. **Envío de Notificaciones** - Sistema de comunicaciones
8. **Generación de Reportes** - Análisis y exportación de datos
9. **Autenticación de Usuarios** - Login y seguridad
10. **Cálculo de Consumos** - Procesamiento de datos de medición

---

## 🏠 1. Proceso: Registro de Nueva Propiedad

```mermaid
sequenceDiagram
    participant Admin as 👨‍💼 Administrador
    participant Sistema as 🖥️ Sistema
    participant DB as 🗄️ Base de Datos
    participant Validacion as ✅ Validación

    Admin->>Sistema: 1. Iniciar registro de propiedad
    Sistema->>Admin: 2. Mostrar formulario vacío
    Admin->>Sistema: 3. Ingresar datos de propiedad
    Note right of Admin: • Código único<br/>• Ubicación<br/>• Metros cuadrados<br/>• Tipo propiedad<br/>• Propietario asignado

    Sistema->>Validacion: 4. Validar datos ingresados
    Validacion->>DB: 5. Verificar código duplicado

    alt Código duplicado
        Validacion-->>Admin: 6. Error: Código ya existe
        Admin->>Sistema: 3. Corregir y reingresar datos
    else Datos válidos
        Validacion-->>Sistema: 6. Validación exitosa
        Sistema->>DB: 7. Guardar nueva propiedad
        DB-->>Sistema: 8. Propiedad registrada
        Sistema-->>Admin: 9. Confirmación de registro
    end
```

---

## 👥 2. Proceso: Gestión de Propietarios

```mermaid
sequenceDiagram
    participant Admin as 👨‍💼 Administrador
    participant Sistema as 🖥️ Sistema
    participant DB as 🗄️ Base de Datos
    participant Validacion as ✅ Validación

    Admin->>Sistema: 1. Seleccionar "Nuevo Propietario"
    Sistema->>Admin: 2. Mostrar formulario
    Admin->>Sistema: 3. Ingresar datos personales
    Note right of Admin: • Nombre completo<br/>• CI/NIT<br/>• Teléfono<br/>• Email<br/>• Dirección

    Sistema->>Validacion: 4. Validar CI duplicado
    Validacion->>DB: 5. Verificar CI en sistema

    alt CI ya existe
        Validacion-->>Admin: 6. Error: CI ya registrado
        Admin->>Sistema: 3. Corregir datos
    else CI único
        Sistema->>DB: 7. Guardar propietario
        Sistema->>Admin: 8. Mostrar propiedades disponibles
        Admin->>Sistema: 9. Seleccionar propiedades a asignar
        Sistema->>DB: 10. Asociar propietario a propiedades
        DB-->>Sistema: 11. Asignaciones guardadas
        Sistema-->>Admin: 12. Confirmación exitosa
    end
```

---

## 💧 3. Proceso: Registro de Lecturas de Medidores

```mermaid
sequenceDiagram
    participant Admin
    participant Sistema
    participant DB
    participant Validacion

    Admin->>Sistema: 1. Iniciar proceso de lecturas
    Sistema->>DB: 2. Obtener medidores activos
    DB-->>Sistema: 3. Lista de medidores pendientes
    Sistema->>Admin: 4. Mostrar formulario de lecturas

    loop Por cada medidor
        Admin->>Sistema: 5. Ingresar lectura actual
        Sistema->>Validacion: 6. Validar lectura
        Validacion->>DB: 7. Obtener lectura anterior

        alt Lectura anormal
            Validacion-->>Admin: 8. Alerta: Posible error
            Admin->>Sistema: 9. Confirmar o corregir
        else Lectura normal
            Sistema->>Validacion: 8. Calcular consumo
            Validacion->>DB: 9. Guardar nueva lectura
        end
    end

    Sistema-->>Admin: 10. Todas las lecturas registradas
```

---

## 🧾 4. Proceso: Generación de Expensas

```mermaid
sequenceDiagram
    participant Admin as 👨‍💼 Administrador
    participant Sistema as 🖥️ Sistema
    participant Calculo as 🧮 Servicio Cálculo
    participant DB as 🗄️ Base de Datos
    participant Notificacion as 📧 Notificaciones

    Admin->>Sistema: 1. Solicitar generación de expensas
    Sistema->>DB: 2. Verificar lecturas completas

    alt Faltan lecturas
        Sistema-->>Admin: 3. Error: Lecturas pendientes
    else Todo completo
        Sistema->>Calculo: 4. Iniciar cálculo masivo

        loop Por cada propiedad
            Calculo->>DB: 5. Obtener lecturas del período
            Calculo->>DB: 6. Obtener factores aplicables
            Calculo->>Calculo: 7. Calcular consumo y montos
            Calculo->>DB: 8. Generar expensa
        end

        Calculo-->>Sistema: 9. Expensas generadas
        Sistema->>Notificacion: 10. Programar notificaciones
        Sistema-->>Admin: 11. Resumen de generación
    end
```

---

## 💳 5. Proceso: Procesamiento de Pagos QR

```mermaid
sequenceDiagram
    participant Usuario as 👤 Usuario
    participant Sistema as 🖥️ Sistema
    participant QR as 📱 Generador QR
    participant Banco as 🏦 Banco
    participant DB as 🗄️ Base de Datos

    Usuario->>Sistema: 1. Solicitar pago de deudas
    Sistema->>DB: 2. Obtener deudas pendientes
    DB-->>Sistema: 3. Lista de deudas
    Sistema->>Usuario: 4. Mostrar deudas a pagar
    Usuario->>Sistema: 5. Seleccionar deudas
    Sistema->>Sistema: 6. Calcular monto total

    Usuario->>Sistema: 7. Solicitar generar QR
    Sistema->>QR: 8. Crear código QR único
    QR-->>Sistema: 9. QR generado con referencia
    Sistema->>Usuario: 10. Mostrar código QR

    Usuario->>Banco: 11. Escanear QR y pagar
    Banco->>Banco: 12. Procesar pago
    Banco->>Sistema: 13. Notificar pago confirmado
    Sistema->>DB: 14. Registrar pago
    Sistema->>Sistema: 15. Imputar a deudas
    Sistema->>Usuario: 16. Enviar confirmación y comprobante
```

---

## 🔧 6. Proceso: Asignación de Medidores

```mermaid
sequenceDiagram
    participant Admin
    participant Sistema
    participant DB
    participant Medidor

    Admin->>Sistema: 1. Seleccionar "Asignar Medidor"
    Sistema->>Admin: 2. Mostrar propiedades sin medidor
    Admin->>Sistema: 3. Seleccionar propiedad
    Sistema->>Admin: 4. Mostrar medidores disponibles
    Admin->>Sistema: 5. Seleccionar tipo de medidor

    alt Medidor individual
        Sistema->>DB: 6. Verificar medidores individuales libres
        Sistema->>Admin: 7. Mostrar medidores disponibles
        Admin->>Sistema: 8. Seleccionar medidor individual
    else Medidor grupal
        Sistema->>Admin: 6. Mostrar grupos existentes
        Admin->>Sistema: 7. Seleccionar o crear grupo
    end

    Sistema->>DB: 9. Actualizar asignación de medidor
    Sistema->>Medidor: 10. Marcar medidor como ocupado
    Sistema-->>Admin: 11. Confirmación de asignación
```

---

## 📧 7. Proceso: Envío de Notificaciones

```mermaid
sequenceDiagram
    participant Sistema as 🖥️ Sistema
    participant Cola as 📋 Cola de Tareas
    participant Email as 📧 Email Service
    participant SMS as 📱 SMS Service
    participant Push as 🔔 Push Service
    participant Usuario as 👤 Usuario

    Sistema->>Sistema: 1. Disparar evento de notificación
    Sistema->>Cola: 2. Agregar tarea a cola

    par Procesamiento Paralelo
        Cola->>Email: 3. Enviar email
        Email-->>Usuario: 4. Email recibido
    and
        Cola->>SMS: 3. Enviar SMS
        SMS-->>Usuario: 4. SMS recibido
    and
        Cola->>Push: 3. Enviar push notification
        Push-->>Usuario: 4. Notificación móvil
    end

    Sistema->>Cola: 5. Marcar tareas como completadas
    Sistema->>Sistema: 6. Actualizar estado de notificación
```

---

## 📊 8. Proceso: Generación de Reportes

```mermaid
sequenceDiagram
    participant Admin as 👨‍💼 Administrador
    participant Sistema as 🖥️ Sistema
    participant Reporte as 📊 Servicio Reportes
    participant DB as 🗄️ Base de Datos
    participant Archivo as 📄 Generador Archivos

    Admin->>Sistema: 1. Solicitar reporte de cobranza
    Sistema->>Admin: 2. Mostrar filtros
    Admin->>Sistema: 3. Seleccionar período y filtros
    Sistema->>Reporte: 4. Iniciar generación de reporte

    Reporte->>DB: 5. Consultar datos de expensas
    Reporte->>DB: 6. Consultar datos de pagos
    Reporte->>Reporte: 7. Calcular estadísticas
    Reporte->>Reporte: 8. Aplicar filtros seleccionados

    Admin->>Sistema: 9. Seleccionar formato de exportación
    alt PDF
        Sistema->>Archivo: 10. Generar PDF
        Archivo-->>Admin: 11. Descargar PDF
    else Excel
        Sistema->>Archivo: 10. Generar Excel
        Archivo-->>Admin: 11. Descargar Excel
    end
```

---

## 🔐 9. Proceso: Autenticación de Usuarios

```mermaid
sequenceDiagram
    participant Usuario as 👤 Usuario
    participant Frontend as 🖥️ Frontend
    participant API as 🔌 API Backend
    participant Auth as 🔐 Auth Service
    participant DB as 🗄️ Base de Datos

    Usuario->>Frontend: 1. Ingresar email y contraseña
    Frontend->>API: 2. Enviar credenciales
    API->>Auth: 3. Validar formato credenciales

    Auth->>DB: 4. Buscar usuario por email
    DB-->>Auth: 5. Datos del usuario

    alt Usuario no encontrado
        Auth-->>API: 6. Error: Credenciales inválidas
        API-->>Frontend: 7. Error de autenticación
        Frontend-->>Usuario: 8. Mostrar error
    else Usuario encontrado
        Auth->>Auth: 9. Verificar contraseña
        alt Contraseña incorrecta
            Auth-->>API: 10. Error: Contraseña inválida
            API-->>Frontend: 11. Error de autenticación
        else Contraseña correcta
            Auth->>Auth: 12. Generar token JWT
            Auth->>DB: 13. Actualizar última sesión
            Auth-->>API: 14. Token y datos de usuario
            API-->>Frontend: 15. Respuesta exitosa
            Frontend->>Frontend: 16. Guardar token y redirigir
        end
    end
```

---

## 📈 10. Proceso: Cálculo de Consumos

```mermaid
sequenceDiagram
    participant Sistema
    participant Lectura
    participant Calculo
    participant Validacion
    participant DB

    Sistema->>Lectura: 1. Iniciar proceso de cálculo
    Lectura->>DB: 2. Obtener lecturas del período
    DB-->>Lectura: 3. Datos de lecturas

    loop Por cada lectura
        Lectura->>Calculo: 4. Calcular consumo
        Calculo->>DB: 5. Obtener lectura anterior

        alt Consumo anómalo
            Calculo->>Validacion: 6. Detectar anomalía
            Validacion-->>Calculo: 7. Marcar para revisión
            Calculo->>DB: 8. Guardar con alerta
        else Consumo normal
            Calculo->>Calculo: 6. Aplicar validaciones
            Calculo->>DB: 7. Guardar consumo calculado
        end
    end

    Lectura-->>Sistema: 9. Resumen de cálculos
    Sistema->>Sistema: 10. Generar alertas necesarias
```

---

## 📋 Resumen de Procesos

| Proceso | Actores Principales | Complejidad | Frecuencia | Crítico |
|---------|-------------------|-------------|------------|---------|
| Registro Propiedad | Administrador | Media | Baja | No |
| Gestión Propietarios | Administrador | Media | Media | Sí |
| Registro Lecturas | Administrador | Alta | Mensual | Sí |
| Generación Expensas | Administrador | Alta | Mensual | Crítico |
| Procesamiento Pagos QR | Usuario | Alta | Variable | Crítico |
| Asignación Medidores | Administrador | Media | Baja | Sí |
| Envío Notificaciones | Sistema | Media | Variable | No |
| Generación Reportes | Administrador | Media | Mensual | No |
| Autenticación Usuarios | Usuario | Baja | Variable | Crítico |
| Cálculo Consumos | Sistema | Alta | Mensual | Sí |

---
*Diagramas de Procesos - Expensas 365Soft*
*Actualizado: 21/11/2025*
# 🏢 Diagramas - Caso de Uso del Negocio

Esta sección contiene los diagramas que representan los procesos de negocio y flujos de trabajo del sistema Expensas 365Soft, enfocados en la lógica operativa y los requerimientos funcionales desde la perspectiva del negocio.

---

## 📊 1. Diagrama General de Casos de Uso del Sistema

```mermaid
graph TB
    subgraph "Sistema Expensas 365Soft - Casos de Uso de Negocio"
        Actor_Admin[👨‍💼 Administrador]
        Actor_Copo[👨‍👩‍👧‍👦 Copropietario]
        Actor_Sistema[🖥️ Sistema Automático]

        subgraph "Gestión de Operaciones (Administrador)"
            UC_GestionPropiedades[🏠 Gestión de Propiedades]
            UC_GestionPropietarios[👥 Gestión de Propietarios]
            UC_GestionMedidores[💧 Gestión de Medidores]
            UC_ConfiguracionPeriodos[📅 Configuración de Períodos]
        end

        subgraph "Procesos Operativos (Automático/Manual)"
            UC_RegistroLecturas[📊 Registro de Lecturas]
            UC_GeneracionExpensas[🧾 Generación de Expensas]
            UC_EnvioNotificaciones[📧 Envío de Notificaciones]
            UC_ProcesamientoPagos[💳 Procesamiento de Pagos]
        end

        subgraph "Consultas y Pagos (Copropietario)"
            UC_ConsultaDeudas[📋 Consulta de Deudas]
            UC_PagoQR[📱 Pago con QR]
            UC_HistorialPagos[📜 Historial de Pagos]
            UC_ConsultaConsumos[💧 Consulta de Consumos]
        end

        subgraph "Reportes y Análisis (Administrador)"
            UC_ReporteCobranza[📊 Reporte de Cobranza]
            UC_AnalisisConsumos[📈 Análisis de Consumos]
            UC_ControlMora[⏰ Control de Moras]
        end

        Actor_Admin --> UC_GestionPropiedades
        Actor_Admin --> UC_GestionPropietarios
        Actor_Admin --> UC_GestionMedidores
        Actor_Admin --> UC_ConfiguracionPeriodos
        Actor_Admin --> UC_RegistroLecturas
        Actor_Admin --> UC_GeneracionExpensas
        Actor_Admin --> UC_ReporteCobranza
        Actor_Admin --> UC_AnalisisConsumos
        Actor_Admin --> UC_ControlMora

        Actor_Copo --> UC_ConsultaDeudas
        Actor_Copo --> UC_PagoQR
        Actor_Copo --> UC_HistorialPagos
        Actor_Copo --> UC_ConsultaConsumos

        Actor_Sistema --> UC_EnvioNotificaciones
        Actor_Sistema --> UC_ProcesamientoPagos

        UC_GeneracionExpensas --> UC_EnvioNotificaciones
        UC_ProcesamientoPagos --> UC_EnvioNotificaciones
    end

    style Actor_Admin fill:#e1f5fe,stroke:#01579b,stroke-width:3px
    style Actor_Copo fill:#f3e5f5,stroke:#4a148c,stroke-width:3px
    style Actor_Sistema fill:#e8f5e8,stroke:#2e7d32,stroke-width:2px
```

---

## 🔄 2. Diagrama de Secuencia - Flujo Mensual de Operación del Negocio

```mermaid
sequenceDiagram
    participant Admin as 👨‍💼 Administrador
    participant Sistema as 🖥️ Sistema
    participant BD as 🗄️ Base de Datos
    participant Copropietario as 👨‍👩‍👧‍👦 Copropietario
    participant Banco as 🏦 Sistema Bancario

    Note over Admin,Banco: 🔁 CICLO MENSUAL DE OPERACIÓN

    %% Fase 1: Configuración Inicial
    rect rgb(240, 248, 255)
        Note over Admin,Sistema: 1️⃣ FASE DE CONFIGURACIÓN
        Admin->>Sistema: Configurar período facturación
        Sistema->>BD: Guardar período y factores
        Sistema-->>Admin: Confirmar configuración
    end

    %% Fase 2: Registro de Lecturas
    rect rgb(255, 240, 245)
        Note over Admin,BD: 2️⃣ FASE DE MEDICIÓN
        Admin->>Sistema: Iniciar registro de lecturas
        Sistema->>BD: Obtener lista de medidores
        BD-->>Sistema: Retornar medidores activos
        Admin->>Sistema: Ingresar lecturas mensuales
        Sistema->>BD: Validar y guardar lecturas
        Sistema-->>Admin: Confirmar registro completo
    end

    %% Fase 3: Cálculo y Generación de Expensas
    rect rgb(255, 248, 240)
        Note over Admin,Copropietario: 3️⃣ FASE DE FACTURACIÓN
        Admin->>Sistema: Generar expensas del período
        Sistema->>BD: Calcular consumo y montos
        Sistema->>BD: Aplicar factores y moras
        Sistema->>BD: Crear registros de expensas
        Sistema-->>Admin: Mostrar resumen de generación
        Sistema->>Copropietario: Enviar notificaciones de expensas
        Copropietario-->>Sistema: Confirmar recepción
    end

    %% Fase 4: Pagos
    rect rgb(240, 255, 240)
        Note over Copropietario,Banco: 4️⃣ FASE DE PAGOS
        Copropietario->>Sistema: Solicitar pago de deudas
        Sistema->>Copropietario: Generar código QR
        Copropietario->>Banco: Realizar pago con QR
        Banco-->>Copropietario: Confirmar pago exitoso
        Banco->>Sistema: Notificar pago procesado
        Sistema->>BD: Registrar pago y actualizar deudas
        Sistema->>Copropietario: Enviar confirmación y comprobante
    end

    %% Fase 5: Reportes y Cierre
    rect rgb(248, 240, 255)
        Note over Admin,BD: 5️⃣ FASE DE REPORTES
        Admin->>Sistema: Generar reporte de cobranza
        Sistema->>BD: Consultar estadísticas del período
        BD-->>Sistema: Retornar datos consolidados
        Sistema-->>Admin: Presentar reporte de gestión
        Sistema->>BD: Cerrar período actual
    end
```

---

## 🏢 3. Diagrama de Casos de Uso - Administrador (Enfoque de Negocio)

```mermaid
graph TB
    subgraph "🏢 CASOS DE USO DE NEGOCIO - ADMINISTRADOR"
        Actor_Admin[👨‍💼 Administrador del Condominio]

        subgraph "🏠 GESTIÓN INMOBILIARIA"
            UC1[✅ Registrar Nueva Propiedad<br/>- Tipos: Depto, Estac, Depósito<br/>- Asignación de códigos únicos<br/>- Configuración de estados]

            UC2[👥 Gestionar Propietarios<br/>- Registro de datos personales<br/>- Asignación de propiedades<br/>- Validación de documentación]

            UC3[💧 Administrar Medidores<br/>- Registro de medidores<br/>- Asignación individual/grupal<br/>- Control de estados]
        end

        subgraph "📊 OPERACIONES MENSUALES"
            UC4[📋 Registrar Lecturas<br/>- Captura mensual de consumo<br/>- Validación de anomalías<br/>- Control de calidad]

            UC5[🧾 Generar Expensas<br/>- Cálculo automático<br/>- Aplicación de factores<br/>- Cálculo de moras]

            UC6[📅 Configurar Períodos<br/>- Definir fechas límite<br/>- Establecer factores<br/>- Control de estados]
        end

        subgraph "💳 GESTIÓN FINANCIERA"
            UC7[💳 Procesar Pagos<br/>- Validación QR<br/>- Imputación automática<br/>- Generación de comprobantes]

            UC8[📊 Reporte de Cobranza<br/>- Estadísticas de pago<br/>- Análisis de moras<br/>- Proyecciones]

            UC9[🔍 Análisis de Consumos<br/>- Tendencias históricas<br/>- Detección de anomalías<br/>- Comparativas]
        end

        subgraph "🔧 CONFIGURACIÓN SISTEMA"
            UC10[⚙️ Configurar Sistema<br/>- Parámetros generales<br/>- Integraciones externas<br/>- Políticas de negocio]

            UC11[👥 Gestionar Accesos<br/>- Administración de usuarios<br/>- Control de roles<br/>- Auditoría de accesos]

            UC12[📧 Configurar Notificaciones<br/>- Canales de comunicación<br/>- Plantillas de mensajes<br/>- Programación de envíos]
        end

        %% Conexiones
        Actor_Admin --> UC1 & UC2 & UC3
        Actor_Admin --> UC4 & UC5 & UC6
        Actor_Admin --> UC7 & UC8 & UC9
        Actor_Admin --> UC10 & UC11 & UC12

        %% Relaciones entre casos de uso
        UC1 --> UC3
        UC3 --> UC4
        UC4 --> UC5
        UC5 --> UC7
        UC7 --> UC8
    end

    style Actor_Admin fill:#e1f5fe,stroke:#01579b,stroke-width:3px
    style UC1 fill:#e8f5e8,stroke:#4caf50
    style UC5 fill:#fff3e0,stroke:#ff9800
    style UC7 fill:#fce4ec,stroke:#e91e63
```

---

## 🏠 4. Diagrama de Casos de Uso - Copropietario (Enfoque de Negocio)

```mermaid
graph TB
    subgraph "🏠 CASOS DE USO DE NEGOCIO - COPIROPIETARIO"
        Actor_Copo[👨‍👩‍👧‍👦 Copropietario]

        subgraph "📊 CONSULTA Y VISIBILIDAD"
            UC1[🏠 Consultar Mis Propiedades<br/>- Lista de inmuebles<br/>- Detalles por propiedad<br/>- Estados actuales]

            UC2[📋 Revisar Estado de Cuenta<br/>- Deudas pendientes<br/>- Próximos vencimientos<br/>- Historial de expensas]

            UC3[💧 Analizar Consumo de Agua<br/>- Histórico por período<br/>- Costos asociados<br/>- Tendencias de consumo]

            UC4[📜 Consultar Historial<br/>- Pagos realizados<br/>- Comprobantes<br/>- Detalle de imputaciones]
        end

        subgraph "💳 TRANSACCIONES FINANCIERAS"
            UC5[📱 Pagar con QR<br/>- Selección de deudas<br/>- Generación de QR<br/>- Procesamiento bancario]

            UC6[🧾 Descargar Comprobantes<br/>- Facturas de expensas<br/>- Comprobantes de pago<br/>- Historial de transacciones]

            UC7[📊 Consultar Saldos<br/>- Detalle por propiedad<br/>- Resumen total<br/>- Proyecciones futuras]
        end

        subgraph "⚙️ GESTIÓN PERSONAL"
            UC8[👤 Actualizar Datos<br/>- Información de contacto<br/>- Preferencias<br/>- Datos bancarios]

            UC9[🔧 Configurar Notificaciones<br/>- Canales de comunicación<br/>- Frecuencias<br/>- Tipos de alertas]

            UC10[🔐 Gestión de Seguridad<br/>- Cambio de contraseña<br/>- Autenticación 2FA<br/>- Recuperación de acceso]
        end

        subgraph "🔔 ALERTAS Y COMUNICACIÓN"
            UC11[📧 Recibir Recordatorios<br/>- Vencimientos próximos<br/>- Promociones<br/>- Mantenimientos]

            UC12[📱 Notificaciones Push<br/>- Confirmaciones de pago<br/>- Alertas importantes<br/>- Actualizaciones del sistema]

            UC13[📢 Reportar Problemas<br/>- Anomalías en lecturas<br/>- Errores en facturación<br/>- Solicitudes especiales]
        end

        %% Conexiones
        Actor_Copo --> UC1 & UC2 & UC3 & UC4
        Actor_Copo --> UC5 & UC6 & UC7
        Actor_Copo --> UC8 & UC9 & UC10
        Actor_Copo --> UC11 & UC12 & UC13

        %% Relaciones entre casos de uso
        UC1 --> UC2
        UC2 --> UC5
        UC5 --> UC6
        UC3 --> UC13
        UC9 --> UC11
    end

    style Actor_Copo fill:#f3e5f5,stroke:#4a148c,stroke-width:3px
    style UC1 fill:#e8f5e8,stroke:#4caf50
    style UC5 fill:#fff3e0,stroke:#ff9800
    style UC11 fill:#e1f5fe,stroke:#2196f3
```

---

## 🔄 5. Diagrama de Proceso de Negocio - Ciclo de Vida de Expensas

```mermaid
flowchart TD
    Start([🚀 Inicio del Período]) --> Config[⚙️ Configurar Período]
    Config --> Validar{¿Período Configurado?}
    Validar -->|No| ErrorConfig[❌ Error: Configurar período primero]
    ErrorConfig --> Config

    Validar -->|Sí| Lecturas[📋 Registrar Lecturas]
    Lecturas --> ValidarLecturas{¿Todas las lecturas registradas?}
    ValidarLecturas -->|No| ErrorLecturas[⚠️ Esperar lecturas pendientes]
    ErrorLecturas --> Lecturas

    ValidarLecturas -->|Sí| GenerarExpensas[🧾 Generar Expensas]
    GenerarExpensas --> Calcular[📊 Cálculo Automático]
    Calcular --> AplicarFactores[🔢 Aplicar Factores]
    AplicarFactores --> CalcularMoras[⏰ Calcular Moras]
    CalcularMoras --> Notificar[📧 Enviar Notificaciones]

    Notificar --> EsperaPago[⏳ Esperar Pagos]
    EsperaPago --> RevisarPagos{¿Hay pagos?}
    RevisarPagos -->|Sí| ProcesarPago[💳 Procesar Pagos]
    ProcesarPago --> ActualizarSaldos[🔄 Actualizar Saldos]
    ActualizarSaldos --> GenerarComprobante[🧾 Generar Comprobantes]
    GenerarComprobante --> NotificarPago[📧 Notificar Pagos]
    NotificarPago --> RevisarPagos

    RevisarPagos -->|No| VerificarVencimiento{¿Período vencido?}
    VerificarVencimiento -->|No| EsperaPago

    VerificarVencimiento -->|Sí| GenerarReporte[📊 Generar Reporte de Cobranza]
    GenerarReporte --> AnalizarResultados[📈 Analizar Resultados]
    AnalizarResultados --> CerrarPeriodo[🔒 Cerrar Período]
    CerrarPeriodo --> Archivar[📁 Archivar Datos]
    Archivar --> End([✅ Fin del Ciclo])

    %% Estilos
    style Start fill:#4caf50,stroke:#2e7d32,stroke-width:2px
    style End fill:#2196f3,stroke:#1565c0,stroke-width:2px
    style Config fill:#ff9800,stroke:#e65100,stroke-width:2px
    style GenerarExpensas fill:#9c27b0,stroke:#6a1b9a,stroke-width:2px
    style ProcesarPago fill:#4caf50,stroke:#2e7d32,stroke-width:2px
    style ErrorConfig fill:#f44336,stroke:#b71c1c,stroke-width:2px
    style ErrorLecturas fill:#ff9800,stroke:#e65100,stroke-width:2px
```

---

## 💳 6. Diagrama de Proceso de Negocio - Flujo de Pago con QR

```mermaid
flowchart TD
    StartCop([🏠 Copropietario]) --> ConsultaDeudas[📋 Consultar Deudas]
    ConsultaDeudas --> TieneDeudas{¿Tiene deudas pendientes?}
    TieneDeudas -->|No| SinDeudas["✅ Mensaje: Sin deudas pendientes"]
    SinDeudas --> End([Fin])

    TieneDeudas -->|Sí| SeleccionarDeudas[🎯 Seleccionar Deudas a Pagar]
    SeleccionarDeudas --> ValidarSeleccion{¿Selección válida?}
    ValidarSeleccion -->|No| ErrorSeleccion["❌ Error: Seleccione deudas válidas"]
    ErrorSeleccion --> SeleccionarDeudas

    ValidarSeleccion -->|Sí| CalcularTotal[💰 Calcular Monto Total]
    CalcularTotal --> AplicarMora{¿Aplicar moras?}
    AplicarMora -->|Sí| AgregarMora[⏰ Agregar Intereses por Mora]
    AgregarMora --> GenerarQR
    AplicarMora -->|No| GenerarQR

    GenerarQR[📱 Generar Código QR]
    GenerarQR --> MostrarQR[📲 Mostrar QR con:<br/>• Referencia única<br/>• Monto exacto<br/>• Tiempo de validez]

    MostrarQR --> EsperarPago[⏳ Esperar Confirmación de Pago]
    EsperarPago --> VerificarExpiracion{¿QR expirado?}
    VerificarExpiracion -->|Sí| RegenerarQR[🔄 Regenerar QR]
    RegenerarQR --> MostrarQR

    VerificarExpiracion -->|No| VerificarPago{¿Pago confirmado?}
    VerificarPago -->|No| MostrarPendiente["⏳ Estado: Pendiente de confirmación"]
    MostrarPendiente --> Reintentos{¿Reintentos agotados?}
    Reintentos -->|Sí| ContactarAdmin["📞 Contactar Administración"]
    ContactarAdmin --> End

    Reintentos -->|No| EsperarPago

    VerificarPago -->|Sí| ProcesarImputacion[🔄 Imputación Automática]
    ProcesarImputacion --> AplicarReglas[📋 Aplicar Reglas:<br/>1. Deudas más antiguas<br/>2. Intereses y moras<br/>3. Saldos pendientes]

    AplicarReglas --> ActualizarEstados["🔄 Actualizar Estados de Deudas"]
    ActualizarEstados --> GenerarComprobante["🧾 Generar Comprobante de Pago"]
    GenerarComprobante --> EnviarConfirmacion["📧 Enviar Confirmación"]
    EnviarConfirmacion --> Success["✅ Pago Procesado Exitosamente"]
    Success --> End

    %% Estilos
    style StartCop fill:#4caf50,stroke:#2e7d32,stroke-width:2px
    style End fill:#2196f3,stroke:#1565c0,stroke-width:2px
    style GenerarQR fill:#9c27b0,stroke:#6a1b9a,stroke-width:2px
    style ProcesarImputacion fill:#ff9800,stroke:#e65100,stroke-width:2px
    style Success fill:#4caf50,stroke:#2e7d32,stroke-width:2px
    style ErrorSeleccion fill:#f44336,stroke:#b71c1c,stroke-width:2px
    style ContactarAdmin fill:#ff5722,stroke:#bf360c,stroke-width:2px
```

---

## 📊 7. Diagrama de Reglas de Negocio - Cálculo de Expensas

```mermaid
flowchart TD
    InicioCalculo([🧾 Inicio Cálculo de Expensas]) --> ObtenerLectura[📊 Obtener Lectura Actual]
    ObtenerLectura --> ObtenerAnterior[📋 Obtener Lectura Anterior]
    ObtenerAnterior --> CalcularConsumo[💧 Calcular Consumo:<br/>Consumo = LecturaActual - LecturaAnterior]

    CalcularConsumo --> ValidarConsumo{¿Consumo válido?}
    ValidarConsumo -->|No| ErrorConsumo["⚠️ Validar:<br/>- Consumo negativo<br/>- Saltos anómalos<br/>- Lecturas inconsistentes"]
    ErrorConsumo --> RevisionManual[🔍 Revisión Manual Requerida]
    RevisionManual --> FinCalculo([🔚 Fin con Alerta])

    ValidarConsumo -->|Sí| CalcularCostoBase[💰 Calcular Costo Base:<br/>Costo = Consumo × Tarifa_m³]
    CalcularCostoBase --> DeterminarTipoPropiedad[🏢 Determinar Tipo de Propiedad]

    DeterminarTipoPropiedad --> AplicarFactor{Aplicar Factor según Tipo:}
    AplicarFactor --> Residencial[🏠 Residencial: Factor × 1.0]
    AplicarFactor --> Comercial[🏢 Comercial: Factor × 1.5]
    AplicarFactor --> Estacionamiento[🚗 Estacionamiento: Factor × 0.3]
    AplicarFactor --> Oficina[🏢 Oficina: Factor × 1.2]

    Residencial --> CalcularSubtotal[📊 Calcular Subtotal:<br/>Subtotal = CostoBase × Factor]
    Comercial --> CalcularSubtotal
    Estacionamiento --> CalcularSubtotal
    Oficina --> CalcularSubtotal

    CalcularSubtotal --> VerificarVencimiento{¿Fecha de vencimiento pasada?}
    VerificarVencimiento -->|No| SinMora[✅ Sin Aplicar Mora]
    SinMora --> CalcularTotal[🧮 Calcular Total:<br/>Total = Subtotal]

    VerificarVencimiento -->|Sí| CalcularMora[⏰ Calcular Mora:<br/>Mora = Subtotal × PorcentajeMora × DíasMora/30]
    CalcularMora --> CalcularTotalConMora[🧮 Calcular Total con Mora:<br/>Total = Subtotal + Mora]

    CalcularTotal --> ValidarMinimo{¿Monto mínimo cumplido?}
    CalcularTotalConMora --> ValidarMinimo

    ValidarMinimo -->|No| AplicarMinimo[📊 Aplicar Monto Mínimo<br/>Total = MontoMínimo]
    AplicarMinimo --> GenerarExpensa[🧾 Generar Registro de Expensa]

    ValidarMinimo -->|Sí| GenerarExpensa
    GenerarExpensa --> ActualizarEstado["🔄 Actualizar Estado: Pendiente de Pago"]
    ActualizarEstado --> ProgramarNotificacion["📅 Programar Notificaciones:<br/>- 5 días antes vencimiento<br/>- 1 día antes vencimiento<br/>- Día vencimiento"]
    ProgramarNotificacion --> FinCalculoExitoso([✅ Cálculo Completado])

    %% Estilos
    style InicioCalculo fill:#4caf50,stroke:#2e7d32,stroke-width:2px
    style FinCalculo fill:#f44336,stroke:#b71c1c,stroke-width:2px
    style FinCalculoExitoso fill:#4caf50,stroke:#2e7d32,stroke-width:2px
    style ErrorConsumo fill:#ff9800,stroke:#e65100,stroke-width:2px
    style RevisionManual fill:#ff5722,stroke:#bf360c,stroke-width:2px
    style CalcularMora fill:#f44336,stroke:#b71c1c,stroke-width:2px
    style GenerarExpensa fill:#9c27b0,stroke:#6a1b9a,stroke-width:2px
```

---

## 📈 8. Matriz de Trazabilidad de Requerimientos de Negocio

| ID Requerimiento | Caso de Uso | Actor | Prioridad | Complejidad | Dependencias |
|------------------|-------------|-------|-----------|-------------|--------------|
| **RN-001** | Gestión de Propiedades | Administrador | Alta | Media | Configuración inicial |
| **RN-002** | Gestión de Propietarios | Administrador | Alta | Media | RN-001 |
| **RN-003** | Registro de Lecturas | Administrador | Alta | Alta | RN-001, RN-002 |
| **RN-004** | Generación de Expensas | Administrador | Crítica | Alta | RN-003 |
| **RN-005** | Pago con QR | Copropietario | Crítica | Alta | RN-004 |
| **RN-006** | Notificaciones Automáticas | Sistema | Alta | Media | RN-004, RN-005 |
| **RN-007** | Reportes de Cobranza | Administrador | Media | Media | RN-005 |
| **RN-008** | Consulta de Consumos | Copropietario | Media | Baja | RN-003 |
| **RN-009** | Configuración de Períodos | Administrador | Alta | Media | RN-004 |
| **RN-010** | Gestión de Accesos | Administrador | Media | Baja | - |

### 🎯 Criterios de Aceptación por Módulo

#### **🏠 Gestión de Propiedades**
- ✅ Todos los tipos de propiedad soportados
- ✅ Códigos únicos y no repetibles
- ✅ Validación de datos obligatorios
- ✅ Estados de propiedad controlados

#### **💧 Sistema de Medición**
- ✅ Lecturas mensuales obligatorias
- ✅ Detección de anomalías
- ✅ Validación de consumos
- ✅ Historial completo

#### **🧾 Facturación**
- ✅ Cálculo automático correcto
- ✅ Aplicación de factores por tipo
- ✅ Cálculo de moras automático
- ✅ Generación de comprobantes

#### **💳 Pagos**
- ✅ Procesamiento QR funcional
- ✅ Imputación automática correcta
- ✅ Confirmaciones inmediatas
- ✅ Comprobantes generados

---

## 📋 Resumen de Diagramas de Negocio

### ✅ **Diagramas Creados:**
1. **Diagrama General de Casos de Uso** - Vista completa del sistema
2. **Diagrama de Secuencia** - Flujo mensual de operación
3. **Casos de Uso - Administrador** - 12 casos de uso de gestión
4. **Casos de Uso - Copropietario** - 13 casos de uso final
5. **Proceso de Negocio - Ciclo de Expensas** - Flujo completo de facturación
6. **Proceso de Negocio - Pagos QR** - Flujo detallado de pagos
7. **Reglas de Negocio - Cálculo de Expensas** - Lógica de cálculo
8. **Matriz de Trazabilidad** - Requerimientos y criterios

### 🎯 **Cobertura de Procesos de Negocio:**
- ✅ **100%** Gestión operativa del condominio
- ✅ **100%** Procesos financieros y de pagos
- ✅ **100%** Interacciones con usuarios finales
- ✅ **100%** Automatización y notificaciones
- ✅ **100%** Reportes y análisis de negocio

---
*Documentación de Casos de Uso de Negocio - Actualizado: 21/11/2025*
*Sistema: Expensas 365Soft*
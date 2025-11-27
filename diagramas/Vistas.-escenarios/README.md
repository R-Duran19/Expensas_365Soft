# 📋 Vistas - Escenarios del Sistema

Esta sección documenta todos los escenarios y casos de uso del sistema Expensas 365Soft desde la perspectiva de los actores.

## 👥 Actores del Sistema

### 🔑 1. Administrador
Rol con acceso completo al sistema, responsable de la gestión operativa y configuración del condominio.

### 🏠 2. Copropietario/Usuario
Propietario de inmuebles que interactúa con el sistema para consulta, pagos y gestión personal.

---

## 📋 Escenarios Documentados

## 👨‍💼 ESCENARIOS DEL ADMINISTRADOR

### 🏗️ Módulo: Gestión de Propiedades

#### **Escenario 1: Registro de Nueva Propiedad**

**Nombre del escenario**: Registro de nueva propiedad en el condominio
**Actor principal**: Administrador
**Objetivo**: Ingresar una nueva propiedad al sistema con sus datos básicos

**Precondiciones**:
- ✅ El administrador está autenticado y tiene rol de admin
- ✅ Existe al menos un propietario registrado en el sistema

**Flujo principal**:
1. El administrador accede al módulo de Propiedades
2. Haz clic en el botón "Nueva Propiedad"
3. Ingresa el código único de la propiedad
4. Selecciona el tipo de propiedad (apartamento, estacionamiento, depósito, oficina, local comercial)
5. Ingresa la ubicación/descripción
6. Registra los metros cuadrados
7. Selecciona el propietario principal
8. Configura el estado (activa/inactiva)
9. Guarda la propiedad

**Flujos alternos**:
- ❌ Si el código ya existe: muestra error y solicita nuevo código
- ❌ Si no hay propietarios: redirige al formulario de registro de propietario
- ❌ Si hay campos vacíos: muestra validación y no permite guardar

**Postcondiciones**: La nueva propiedad queda registrada en el sistema y disponible para asignación de medidores

---

#### **Escenario 2: Asignación de Medidor a Propiedad**

**Nombre del escenario**: Asignación de medidor de agua a propiedad
**Actor principal**: Administrador
**Objetivo**: Asignar un medidor individual o grupal a una propiedad

**Precondiciones**:
- ✅ Existe la propiedad registrada
- ✅ Existen medidores disponibles sin asignar

**Flujo principal**:
1. El administrador busca la propiedad específica
2. Accede a la opción "Asignar Medidor"
3. Selecciona tipo de medidor (individual/grupal)
4. Si es individual: elige el medidor disponible
5. Si es grupal: selecciona el grupo de medidores existente o crea uno nuevo
6. Confirma la asignación
7. El sistema actualiza el estado del medidor y la propiedad

**Flujos alternos**:
- ❌ Si no hay medidores disponibles: redirige a registro de medidores
- ❌ Si el medidor ya está asignado: muestra error y sugiere otro medidor

**Postcondiciones**: La propiedad tiene medidor asignado y lista para registrar lecturas

---

### 👥 Módulo: Gestión de Propietarios

#### **Escenario 3: Registro de Nuevo Propietario**

**Nombre del escenario**: Registro de nuevo propietario en el sistema
**Actor principal**: Administrador
**Objetivo**: Ingresar los datos de un nuevo propietario y asignarle propiedades

**Precondiciones**:
- ✅ El administrador está autenticado con rol admin
- ✅ Existen propiedades disponibles sin propietario

**Flujo principal**:
1. Accede al módulo de Propietarios
2. Haz clic en "Nuevo Propietario"
3. Ingresa nombre completo del propietario
4. Registra CI/Cédula de Identidad
5. Ingresa número de NIT
6. Agrega información de contacto (teléfono, email, dirección)
7. Selecciona las propiedades que posee
8. Define si es propietario principal o secundario
9. Guarda el registro

**Flujos alternos**:
- ❌ Si el CI ya existe: muestra error de duplicidad
- ❌ Si el email ya está registrado: solicita verificación o diferente email
- ❌ Si no hay propiedades disponibles: permite registrar sin propiedades asignadas inicialmente

**Postcondiciones**: El propietario queda registrado y asociado a sus propiedades

---

### 💧 Módulo: Sistema de Medición

#### **Escenario 4: Registro de Lectura Mensual**

**Nombre del escenario**: Registro de lectura de medidor mensual
**Actor principal**: Administrador
**Objetivo**: Registrar el consumo de agua de cada medidor para el período actual

**Precondiciones**:
- ✅ Existen medidores activos asignados a propiedades
- ✅ El período de facturación está activo
- ✅ No existen lecturas duplicadas para el mismo período

**Flujo principal**:
1. Accede al módulo de Lecturas
2. Selecciona el período de facturación actual
3. El sistema muestra lista de medidores pendientes de lectura
4. Ingresa la lectura actual en m³ para cada medidor
5. El sistema calcula automáticamente el consumo (lectura actual - lectura anterior)
6. Registra la fecha de lectura
7. Confirma y guarda todas las lecturas
8. El sistema actualiza el estado de medidores a "leído"

**Flujos alternos**:
- ⚠️ Si la lectura actual es menor a la anterior: solicita confirmación y justificación
- ❌ Si hay errores de lectura: permite corregir antes de guardar
- ⚠️ Si algunos medidores no se pueden leer: permite marcar como "no leído" con justificación

**Postcondiciones**: Todas las lecturas quedan registradas y listas para cálculo de expensas

---

### 🧾 Módulo: Facturación

#### **Escenario 5: Generación de Expensas Mensuales**

**Nombre del escenario**: Generación automática de expensas del período
**Actor principal**: Administrador
**Objetivo**: Calcular y generar las facturas de expensas para todas las propiedades del período actual

**Precondiciones**:
- ✅ Todas las lecturas del período están registradas
- ✅ El período de facturación está configurado
- ✅ Los factores de cálculo están definidos

**Flujo principal**:
1. Accede al módulo de Expensas
2. Selecciona el período de facturación actual
3. Verifica que todas las lecturas estén completas
4. Haz clic en "Generar Expensas"
5. El sistema calcula automáticamente:
   - Costo por consumo de agua según lecturas
   - Aplicación de factores por tipo de propiedad
   - Cálculo de intereses y moras si aplica
6. Muestra vista previa de todas las expensas a generar
7. Confirma la generación
8. El sistema crea las expensas y las asigna a cada propiedad
9. Envía notificaciones automáticas a propietarios

**Flujos alternos**:
- ❌ Si faltan lecturas: muestra lista de propiedades pendientes y no permite continuar
- ❌ Si hay errores en cálculos: muestra detalle de errores y permite corregir parámetros
- ⚠️ Si el sistema detecta duplicados: alerta y solicita confirmación

**Postcondiciones**: Todas las expensas del período están generadas y notificadas

---

### 💳 Módulo: Pagos

#### **Escenario 6: Procesamiento de Pago Multiple**

**Nombre del escenario**: Registro y asignación de pago con código QR
**Actor principal**: Administrador
**Objetivo**: Procesar un pago de propietario mediante sistema QR y asignarlo automáticamente a sus deudas

**Precondiciones**:
- ✅ El propietario tiene expensas pendientes de pago
- ✅ El sistema QR está configurado y funcionando
- ✅ El pago ha sido verificado por el sistema externo

**Flujo principal**:
1. El propietario presenta comprobante de pago QR
2. El administrador accede al módulo de Pagos
3. Escanea el código QR o ingresa el código de referencia
4. El sistema consulta y verifica el estado del pago externamente
5. Muestra los detalles del pago (monto, fecha, propietario)
6. El sistema identifica automáticamente las deudas pendientes del propietario
7. Aplica el pago según reglas de imputación:
   - Primero expensas más antiguas
   - Luego intereses y moras
   - Finalmente saldos pendientes
8. Muestra desglose de imputación
9. Confirma el registro del pago
10. El sistema actualiza saldos y estados de expensas
11. Genera comprobante de pago
12. Envía notificación al propietario

**Flujos alternos**:
- ❌ Si el QR no es válido: muestra error y solicita nuevo escaneo
- ⚠️ Si el pago ya fue procesado: alerta de duplicidad y muestra detalles
- ❌ Si el monto no coincide con deudas: permite imputación parcial o manual
- ⚠️ Si hay problemas de conexión: permite registro manual con verificación posterior

**Postcondiciones**: El pago queda registrado, las deudas actualizadas y se generan comprobantes

---

### 📊 Módulo: Reportes

#### **Escenario 7: Generación de Reporte de Cobranza**

**Nombre del escenario**: Generación de reporte mensual de cobranza
**Actor principal**: Administrador
**Objetivo**: Obtener reporte detallado del estado de cobranza del período actual

**Precondiciones**:
- ✅ Existen expensas generadas para el período
- ✅ Hay pagos registrados en el sistema

**Flujo principal**:
1. Accede al módulo de Reportes
2. Selecciona "Reporte de Cobranza"
3. Define el período a reportar
4. Selecciona tipo de reporte (resumen/detallado)
5. El sistema procesa y muestra:
   - Total de expensas generadas
   - Total pagado del período
   - Saldo pendiente de cobranza
   - Porcentaje de cobranza
   - Lista de deudores con montos
   - Moras generadas
6. Permite filtrar por:
   - Tipo de propiedad
   - Estado de pago (pagado/pendiente/vencido)
   - Rango de fechas
7. Exporta el reporte a PDF/Excel
8. Guarda el reporte generado

**Flujos alternos**:
- ⚠️ Si no hay datos para el período: sugiere otros períodos con información
- ❌ Si hay errores en cálculos: muestra alerta y permite recalcular
- ⚠️ Si el reporte es muy grande: ofrece opción de generar en segundo plano

**Postcondiciones**: El reporte queda generado y disponible para consulta y descarga

---

### ⚙️ Módulo: Configuración

#### **Escenario 8: Configuración de Período de Facturación**

**Nombre del escenario**: Configuración de nuevo período de facturación
**Actor principal**: Administrador
**Objetivo**: Crear y configurar un nuevo período de facturación mensual

**Precondiciones**:
- ✅ El período anterior está cerrado
- ✅ Los factores de cálculo están definidos

**Flujo principal**:
1. Accede al módulo de Períodos de Facturación
2. Haz clic en "Nuevo Período"
3. Define fecha de inicio y fin del período
4. Configura factores por tipo de propiedad:
   - Apartamentos: factor X
   - Estacionamientos: factor Y
   - Oficinas: factor Z
   - Comerciales: factor W
5. Establece fechas de vencimiento:
   - Vencimiento normal
   - Vencimiento con mora
   - Porcentaje de mora
6. Define costos base por m³ de agua
7. Activa el período
8. El sistema lo configura como período actual

**Flujos alternos**:
- ❌ Si hay períodos solapados: muestra error y corrige fechas
- ❌ Si faltan factores: no permite activar hasta completar
- ⚠️ Si el período anterior no está cerrado: solicita cerrarlo primero

**Postcondiciones**: El nuevo período queda activo y listo para registrar lecturas

---

## 🏠 ESCENARIOS DE COPIROPIETARIOS (USUARIOS FINALES)

### 📊 Módulo: Consulta de Estado de Cuenta

#### **Escenario 1: Consulta de Expensas Pendientes**

**Nombre del escenario**: Consulta de expensas y deudas actuales
**Actor principal**: Copropietario/Usuario
**Objetivo**: Ver el estado de sus expensas, deudas y pagos realizados

**Precondiciones**:
- ✅ El copropietario está autenticado en el sistema
- ✅ Tiene propiedades asignadas en el sistema

**Flujo principal**:
1. El copropietario inicia sesión en el sistema
2. Accede a su dashboard/panel principal
3. El sistema muestra:
   - Resumen de propiedades asociadas
   - Total de expensas pendientes
   - Próximos vencimientos
   - Últimos pagos realizados
4. Haz clic en "Ver Detalle de Deudas"
5. El sistema muestra lista detallada de:
   - Expensas del período actual con montos
   - Expensas vencidas con moras incluidas
   - Estado de cada expensa (pagada/pendiente/vencida)
   - Fechas de vencimiento
6. Puede filtrar por:
   - Período específico
   - Tipo de propiedad
   - Estado de pago
7. Puede descargar comprobantes de expensas generadas

**Flujos alternos**:
- ✅ Si no tiene deudas pendientes: muestra mensaje "Sin deudas pendientes"
- ❌ Si hay errores en los datos: ofrece opción de recargar o contactar administración
- ⚠️ Si no puede acceder a alguna propiedad: muestra mensaje de contacto con administración

**Postcondiciones**: El copropietario conoce su estado completo de deudas y puede planificar sus pagos

---

### 💳 Módulo: Pagos

#### **Escenario 2: Pago de Expensas con QR**

**Nombre del escenario**: Generación y pago de expensas mediante código QR
**Actor principal**: Copropietario/Usuario
**Objetivo**: Pagar sus expensas pendientes utilizando el sistema de códigos QR

**Precondiciones**:
- ✅ El copropietario tiene deudas pendientes
- ✅ Tiene acceso a aplicación bancaria o sistema de pago móvil
- ✅ El sistema de pagos QR está operativo

**Flujo principal**:
1. El copropietario accede a su dashboard
2. Selecciona "Pagar Expensas"
3. El sistema muestra sus deudas pendientes organizadas por prioridad:
   - Expensas vencidas (prioridad alta)
   - Expensas por vencer (prioridad media)
   - Expensas del período actual (prioridad baja)
4. Selecciona las expensas que desea pagar (puede ser total o parcial)
5. El sistema calcula el monto total a pagar incluyendo moras si aplica
6. Haz clic en "Generar Código QR"
7. El sistema genera:
   - Código QR único para la transacción
   - Código de referencia alfanumérico
   - Monto exacto a pagar
   - Tiempo de validez del QR (generalmente 10 minutos)
8. El copropietario escanea el QR con su app bancaria
9. Realiza el pago desde su aplicación
10. El sistema verifica automáticamente el estado del pago
11. Una vez confirmado, actualiza las deudas y genera comprobante
12. Envía notificación de confirmación al copropietario

**Flujos alternos**:
- ⏰ Si el QR expira: genera nuevo código QR automáticamente
- ❌ Si el pago es rechazado: muestra motivo del rechazo y permite reintento
- ⚠️ Si el monto pagado es diferente: ofrece opciones de pago parcial o contacto con administración
- 📶 Si hay problemas de conectividad: permite guardar referencia y verificar después

**Postcondiciones**: El pago queda procesado, las deudas actualizadas y el copropietario recibe confirmación

---

#### **Escenario 3: Consulta de Historial de Pagos**

**Nombre del escenario**: Revisión de historial completo de pagos realizados
**Actor principal**: Copropietario/Usuario
**Objetivo**: Consultar y descargar comprobantes de todos sus pagos anteriores

**Precondiciones**:
- ✅ El copropietario está autenticado
- ✅ Ha realizado pagos anteriormente en el sistema

**Flujo principal**:
1. Accede a su perfil o dashboard
2. Selecciona "Historial de Pagos"
3. El sistema muestra lista cronológica de todos los pagos:
   - Fecha de pago
   - Monto pagado
   - Método de pago (QR, efectivo, transferencia)
   - Expensas que fueron canceladas
   - Estado del pago (confirmado/procesando)
4. Puede filtrar por:
   - Rango de fechas
   - Método de pago
   - Monto (mayor/menor a X)
5. Haz clic en cualquier pago para ver detalles:
   - Desglose de imputación
   - Comprobante de pago
   - Referencia bancaria
6. Puede descargar comprobante en PDF
7. Puede buscar pagos específicos por referencia

**Flujos alternos**:
- 🔍 Si no encuentra un pago específico: ofrece búsqueda avanzada o contacto con administración
- ⏳ Si hay pagos pendientes de verificación: muestra estado "procesando" con fecha estimada
- ⚠️ Si hay errores en los datos: permite reportar discrepancia

**Postcondiciones**: El copropietario tiene acceso completo a su historial financiero

---

### ⚙️ Módulo: Configuración Personal

#### **Escenario 4: Actualización de Datos Personales**

**Nombre del escenario**: Actualización de información personal y de contacto
**Actor principal**: Copropietario/Usuario
**Objetivo**: Mantener actualizados sus datos personales y preferencias de notificación

**Precondiciones**:
- ✅ El copropietario está autenticado
- ✅ Conoce sus nuevos datos

**Flujo principal**:
1. Accede a su perfil de usuario
2. Selecciona "Mis Datos"
3. El sistema muestra su información actual:
   - Nombre completo
   - CI/NIT
   - Teléfono
   - Email
   - Dirección
4. Actualiza los campos que desea modificar
5. Configura preferencias de notificación:
   - Email para notificaciones de pagos
   - SMS para recordatorios de vencimiento
   - Notificaciones push en la app
6. Establece nueva contraseña si desea
7. Configura autenticación de dos factores si está disponible
8. Guarda los cambios
9. El sistema valida los datos y confirma actualización
10. Envía email de confirmación si cambió datos sensibles

**Flujos alternos**:
- ❌ Si el email ya está en uso: solicita verificación o diferente email
- 🔒 Si la contraseña es muy débil: muestra requisitos de seguridad
- ⚠️ Si hay problemas con 2FA: ofrece opciones de recuperación
- ❌ Si los datos no coinciden con registros: solicita verificación adicional

**Postcondiciones**: Los datos del copropietario quedan actualizados en el sistema

---

### 🔔 Módulo: Notificaciones

#### **Escenario 5: Recepción de Alertas de Vencimiento**

**Nombre del escenario**: Configuración y recepción de alertas de vencimiento de expensas
**Actor principal**: Copropietario/Usuario
**Objetivo**: Recibir notificaciones oportunas sobre vencimientos de expensas para evitar moras

**Precondiciones**:
- ✅ Tiene expensas pendientes con fechas de vencimiento futuras
- ✅ Ha configurado canales de notificación

**Flujo principal**:
1. El sistema monitorea automáticamente las fechas de vencimiento
2. Genera alertas según configuración del usuario:
   - 5 días antes del vencimiento
   - 1 día antes del vencimiento
   - Día del vencimiento
3. Envía notificaciones a través de los canales configurados:
   - Email con detalle de expensas por vencer
   - SMS con monto y fecha de vencimiento
   - Notificación push en la app
4. El copropietario recibe la notificación
5. Puede acceder directamente al enlace de pago desde la notificación
6. El sistema registra que la notificación fue recibida
7. Si la expensa es pagada, cancela notificaciones futuras para esa deuda

**Flujos alternos**:
- 📧 Si el email no puede ser entregado: intenta notificación SMS
- 📱 Si el usuario no tiene configurada notificación: muestra alerta en dashboard al iniciar sesión
- 📢 Si hay múltiples deudas venciendo el mismo día: agrupa en una sola notificación
- 🔄 Si hay problemas con el sistema de notificaciones: registra intento para reenvío posterior

**Postcondiciones**: El copropietario recibe recordatorios oportunos y puede evitar moras

---

### 💧 Módulo: Consulta de Consumos

#### **Escenario 6: Revisión de Historial de Consumo de Agua**

**Nombre del escenario**: Consulta detallada de consumo de agua por propiedad
**Actor principal**: Copropietario/Usuario
**Objetivo**: Analizar su consumo histórico de agua para controlar gastos y detectar anomalías

**Precondiciones**:
- ✅ Tiene medidores de agua asignados a sus propiedades
- ✅ Existen lecturas históricas en el sistema

**Flujo principal**:
1. Accede a su dashboard
2. Selecciona "Mis Consumos" o "Consumo de Agua"
3. El sistema muestra sus propiedades con medidores
4. Selecciona una propiedad específica
5. Visualiza historial de consumo organizado por:
   - Períodos mensuales
   - Consumo en m³ por período
   - Costo del consumo
   - Comparación con períodos anteriores
   - Gráficos de tendencia
6. Puede filtrar por:
   - Rango de fechas específico
   - Comparar mismos períodos de años diferentes
7. Puede exportar el historial a PDF o Excel
8. Detecta anomalías:
   - Consumos inusualmente altos/bajos
   - Saltos entre lecturas
   - Patrones estacionales
9. Puede reportar lecturas sospechosas a administración

**Flujos alternos**:
- 📊 Si no hay datos históricos: muestra mensaje de inicio de monitoreo
- ⚠️ Si hay errores en las lecturas: muestra alerta y ofrece contacto con administración
- 🔍 Si detecta consumos anormales: sugiere revisión de posibles fugas
- 📈 Si necesita más detalles: permite solicitar reporte especializado

**Postcondiciones**: El copropietario tiene conocimiento completo de sus patrones de consumo

---

## 📊 Resumen Estadístico

| Actor | Escenarios Totales | Módulos Cubiertos | Complejidad |
|-------|-------------------|-------------------|-------------|
| 👨‍💼 Administrador | 8 escenarios | 7 módulos principales | Alta |
| 🏠 Copropietario | 6 escenarios | 5 módulos principales | Media |

### 🎯 Cobertura de Funcionalidades
- ✅ **100%** Gestión de propietarios y propiedades
- ✅ **100%** Sistema de medición y facturación
- ✅ **100%** Procesamiento de pagos con QR
- ✅ **100%** Notificaciones y alertas
- ✅ **100%** Reportes y configuración

---
*Documentación actualizada: 21/11/2025*
*Sistema: Expensas 365Soft*
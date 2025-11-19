<template>
  <div class="payment-qr-container">
    <!-- Opción QR Manual (Referencia) -->
    <div v-if="mode === 'manual'" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-primary-foreground/80 mb-2">
          Código QR o Referencia
        </label>
        <input
          v-model="qrReference"
          type="text"
          placeholder="Ingrese el código QR o referencia de pago"
          class="w-full px-3 py-2 border border-primary-foreground/20 rounded-md focus:outline-none focus:ring-2 focus:ring-ring bg-input text-foreground"
        />
      </div>
      <div v-if="qrReference" class="text-xs text-muted-foreground">
        <i class="fas fa-info-circle mr-1"></i>
        Puede registrar el pago con la referencia del código QR proporcionado por el cliente
      </div>
    </div>

    <!-- Opción QR en Tiempo Real -->
    <div v-else-if="mode === 'realtime'" class="space-y-4">
      <!-- Selección de monto y datos para QR -->
      <div v-if="!qrGenerated" class="space-y-4">
        <!-- Información del pago -->
        <div class="bg-muted rounded-lg p-4">
          <h4 class="font-semibold text-foreground mb-3">Información del Pago QR</h4>

          <!-- Monto a pagar -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-foreground mb-2">
              Monto a Pagar *
            </label>
            <div class="relative">
              <span class="absolute left-3 top-2 text-primary font-medium">Bs.</span>
              <input
                v-model.number="qrAmount"
                type="number"
                step="0.01"
                min="0.01"
                required
                :disabled="isLoading"
                class="w-full pl-12 pr-3 py-3 border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-ring bg-background text-foreground font-semibold text-lg"
                placeholder="0.00"
              />
            </div>
            <!-- Montos sugeridos -->
            <div v-if="props.paymentData.amount && props.paymentData.amount !== qrAmount" class="mt-2">
              <span class="text-xs text-muted-foreground">Montos sugeridos:</span>
              <div class="flex space-x-2 mt-1">
                <button
                  @click="qrAmount = props.paymentData.amount"
                  :disabled="isLoading"
                  class="px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded hover:bg-blue-200 transition-colors disabled:opacity-50"
                >
                  Saldo total: {{ formatCurrency(props.paymentData.amount) }}
                </button>
                <button
                  v-if="props.paymentData.amount > 50"
                  @click="qrAmount = 50"
                  :disabled="isLoading"
                  class="px-3 py-1 bg-gray-100 text-gray-800 text-xs rounded hover:bg-gray-200 transition-colors disabled:opacity-50"
                >
                  Bs. 50
                </button>
                <button
                  v-if="props.paymentData.amount > 100"
                  @click="qrAmount = 100"
                  :disabled="isLoading"
                  class="px-3 py-1 bg-gray-100 text-gray-800 text-xs rounded hover:bg-gray-200 transition-colors disabled:opacity-50"
                >
                  Bs. 100
                </button>
              </div>
            </div>
          </div>

          <!-- Información del propietario -->
          <div v-if="props.paymentData.propietario_id" class="text-sm">
            <span class="text-muted-foreground">Propietario: </span>
            <span class="font-medium text-foreground">ID: {{ props.paymentData.propietario_id }}</span>
          </div>
        </div>

        <!-- Botón para generar QR -->
        <div class="text-center">
          <button
            @click="generarQR"
            :disabled="!canGenerateQR || isLoading"
            class="px-6 py-3 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-lg"
          >
            <i v-if="isLoading" class="fas fa-spinner fa-spin mr-2"></i>
            <i v-else class="fas fa-qrcode mr-2"></i>
            {{ isLoading ? 'Generando...' : 'Generar Código QR' }}
          </button>

          <!-- Información sobre vigencia -->
          <div class="mt-4 text-center space-y-2">
            <div class="inline-flex items-center justify-center text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-full">
              <i class="fas fa-clock mr-2"></i>
              <span><strong>Vigencia:</strong> 24 horas</span>
            </div>
            <p class="text-xs text-muted-foreground">
              Puedes pagar con cualquier app bancaria que soporte QR
            </p>
          </div>
        </div>
      </div>

      <!-- QR Generado -->
      <div v-else class="qr-generated-container">
        <!-- Información Compacta del QR -->
        <div class="bg-muted/50 rounded-lg p-3 mb-4">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h4 class="font-semibold text-foreground text-sm">Código QR Generado</h4>
              <p class="text-xs text-muted-foreground">Alias: {{ qrData.alias }}</p>
              <p class="text-xs text-muted-foreground">Monto: {{ formatCurrency(qrData.monto) }}</p>
            </div>
            <div class="text-right">
              <span class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="getEstadoClass(qrData.estado)">
                {{ getEstadoText(qrData.estado) }}
              </span>
              <p v-if="qrData.fecha_vencimiento" class="text-xs text-muted-foreground mt-1">
                Vence: {{ formatDate(qrData.fecha_vencimiento) }}
              </p>
            </div>
          </div>

        <!-- Alerta de vigencia -->
        <div v-if="qrEstaPorVencer" class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
          <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-amber-600 dark:text-amber-400 mr-2"></i>
            <div class="flex-1">
              <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                ¡Atención! El QR vence pronto
              </p>
              <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                Tiempo restante: <strong>{{ qrTiempoRestante }}</strong>
              </p>
            </div>
          </div>
        </div>

        <!-- Imagen del QR - Más Grande -->
        <div v-if="qrImage" class="flex justify-center mb-4">
          <div class="qr-image-container-enhanced">
            <img :src="qrImage" alt="Código QR" class="qr-image-enhanced" />
          </div>
        </div>

        <!-- Botones de acción mejorados -->
        <div class="grid grid-cols-1 gap-3">
          <!-- Botón de verificación instantánea mejorado -->
          <button
            @click="verificarEstado"
            :disabled="isVerifying"
            class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-medium hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 transition-all transform hover:scale-[1.02] shadow-lg"
          >
            <div class="flex items-center justify-center">
              <i v-if="isVerifying" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-search-location mr-2"></i>
              <span>{{ isVerifying ? 'Verificando...' : 'Verificar Pago Ahora' }}</span>
            </div>
          </button>

          <!-- Botones secundarios en fila -->
          <div class="grid grid-cols-2 gap-2">
            <button
              @click="verificacionRapida"
              :disabled="isVerifyingRapida"
              class="px-3 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 transition-colors text-sm"
            >
              <i v-if="isVerifyingRapida" class="fas fa-spinner fa-spin mr-1"></i>
              <i v-else class="fas fa-bolt mr-1"></i>
              {{ isVerifyingRapida ? 'Verificando...' : 'Verif. Rápida' }}
            </button>

            <button
              @click="cancelarQR"
              :disabled="isCancelling || qrData.estado === 'PAGADO'"
              class="px-3 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 disabled:opacity-50 transition-colors text-sm"
            >
              <i v-if="isCancelling" class="fas fa-spinner fa-spin mr-1"></i>
              <i v-else class="fas fa-times mr-1"></i>
              {{ isCancelling ? 'Cancelando...' : 'Cancelar QR' }}
            </button>
          </div>
        </div>

        <!-- Auto-verificación mejorada con Switch -->
        <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg">
          <div class="flex items-center justify-between mb-3">
            <label class="flex items-center cursor-pointer">
              <div class="relative">
                <input
                  v-model="autoVerificar"
                  type="checkbox"
                  class="sr-only"
                />
                <div class="block bg-gray-300 dark:bg-gray-600 w-14 h-8 rounded-full transition-colors"
                     :class="{ 'bg-blue-600': autoVerificar }"></div>
                <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform"
                     :class="{ 'transform translate-x-6': autoVerificar }"></div>
              </div>
              <span class="ml-3 text-sm font-medium text-foreground">Verificación automática</span>
            </label>
            <span class="text-xs text-muted-foreground bg-white/70 px-2 py-1 rounded">
              {{ autoVerificar ? 'Activada' : 'Desactivada' }}
            </span>
          </div>

          <div v-if="autoVerificar" class="space-y-2">
            <div class="flex items-center text-xs text-blue-700 dark:text-blue-300">
              <i class="fas fa-sync-alt mr-2 animate-spin"></i>
              <span>Verificando cada 10 segundos...</span>
            </div>
            <div class="bg-white/50 rounded p-2">
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>Verificaciones realizadas:</span>
                <span class="font-bold text-blue-600">{{ verificationCount }}</span>
              </div>
            </div>
          </div>

          <div v-else class="text-xs text-muted-foreground bg-white/50 rounded p-2">
            <i class="fas fa-info-circle mr-1"></i>
            Activa la verificación automática para monitorear el estado del pago
          </div>
        </div>

        <!-- Mensaje de estado -->
        <div v-if="estadoMessage" class="mt-4 p-3 rounded-lg" :class="getMessageClass()">
          <div class="flex items-center">
            <i :class="getMessageIcon()" class="mr-2"></i>
            <span class="text-sm">{{ estadoMessage }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useNotification } from '@/composables/useNotification'

const props = defineProps({
  mode: {
    type: String,
    default: 'manual', // 'manual' o 'realtime'
  },
  paymentData: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'qr-generated', 'qr-paid'])

const { showSuccess, showError, showWarning, showInfo } = useNotification()

// Estado
const isLoading = ref(false)
const isVerifying = ref(false)
const isVerifyingRapida = ref(false)
const isCancelling = ref(false)
const qrReference = ref('')
const qrGenerated = ref(false)
const qrImage = ref('')
const qrData = ref({})
const qrAmount = ref(0)
const estadoMessage = ref('')
const autoVerificar = ref(true)
const autoVerificarInterval = ref(null)
const lastVerificationTime = ref(null)
const verificationCount = ref(0)

// Computed
const canGenerateQR = computed(() => {
  return qrAmount.value > 0 && props.paymentData.propietario_id
})

const qrEstaPorVencer = computed(() => {
  if (!qrData.value.fecha_vencimiento) return false

  const fechaVencimiento = new Date(qrData.value.fecha_vencimiento)
  const ahora = new Date()
  const horasParaVencer = (fechaVencimiento - ahora) / (1000 * 60 * 60)

  return horasParaVencer > 0 && horasParaVencer <= 6 // Menos de 6 horas para vencer
})

const qrTiempoRestante = computed(() => {
  if (!qrData.value.fecha_vencimiento) return null

  const fechaVencimiento = new Date(qrData.value.fecha_vencimiento)
  const ahora = new Date()
  const diferencia = fechaVencimiento - ahora

  if (diferencia <= 0) return 'Vencido'

  const horas = Math.floor(diferencia / (1000 * 60 * 60))
  const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60))

  if (horas > 0) {
    return `${horas}h ${minutos}m`
  } else {
    return `${minutos}m`
  }
})

// Watchers
watch(() => props.paymentData.amount, (newAmount) => {
  if (newAmount && newAmount > 0 && !qrAmount.value) {
    qrAmount.value = newAmount
  }
}, { immediate: true })

// Métodos
const generarQR = async () => {
  if (!canGenerateQR.value) return

  isLoading.value = true

  try {
    // Generar QR dinámico
    const response = await fetch('/api/pagos/qr/generar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        monto: qrAmount.value,
        propietario_id: props.paymentData.propietario_id,
        property_expense_id: props.paymentData.expensa_id
      })
    })

    const result = await response.json()

    if (result.success) {
      qrData.value = {
        id: result.qr_id,
        alias: result.alias,
        estado: result.estado,
        monto: qrAmount.value,
        pago_id: null, // Sin pago asociado todavía
        fecha_vencimiento: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
      }

      // Procesar imagen si viene en la respuesta
      if (result.data?.objeto?.imagenQr) {
        qrImage.value = `data:image/png;base64,${result.data.objeto.imagenQr}`
      }

      qrGenerated.value = true
      emit('qr-generated', qrData.value)
      showSuccess('QR generado exitosamente', `Alias: ${qrData.value.alias}`)

      // Iniciar auto-verificación
      startAutoVerificarQR()
    } else {
      showError('Error al generar QR', result.message || 'Error desconocido')
    }
  } catch (error) {
    console.error('Error generando QR:', error)
    showError('Error de conexión', 'No se pudo generar el código QR')
  } finally {
    isLoading.value = false
  }
}

const verificarEstado = async () => {
  if (!qrData.value.id) return

  isVerifying.value = true
  lastVerificationTime.value = new Date()

  try {
    const response = await fetch('/api/pagos/qr/verificar-estado', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        qr_id: qrData.value.id
      })
    })

    const result = await response.json()

    if (result.success) {
      const nuevoEstado = result.qr.estado
      const estadoAnterior = qrData.value.estado
      verificationCount.value++

      qrData.value.estado = nuevoEstado

      // Mensaje detallado del resultado
      const tiempoTranscurrido = ((new Date() - lastVerificationTime.value) / 1000).toFixed(1)

      if (estadoAnterior !== nuevoEstado) {
        estadoMessage.value = `✅ Estado actualizado: ${getEstadoText(nuevoEstado)} (Verificación #${verificationCount.value})`

        if (nuevoEstado === 'PAGADO') {
          showSuccess('¡Pago recibido!', `El pago por QR ha sido procesado exitosamente (${tiempoTranscurrido}s)`)
          emit('qr-paid', result.qr)
          stopAutoVerificarQR()
        } else if (nuevoEstado === 'VENCIDO') {
          showWarning('QR vencido', 'El código QR ha expirado')
          stopAutoVerificarQR()
        }
      } else {
        estadoMessage.value = `ℹ️ Estado: ${getEstadoText(nuevoEstado)} - Sin cambios (Verificación #${verificationCount.value})`

        if (nuevoEstado === 'PENDIENTE') {
          showInfo('Verificación completa', 'El QR sigue pendiente de pago. El cliente aún no ha realizado el pago.')
        }
      }
    } else {
      showError('Error al verificar estado', result.message)
    }
  } catch (error) {
    console.error('Error verificando estado:', error)
    showError('Error de conexión', 'No se pudo verificar el estado del QR')
  } finally {
    isVerifying.value = false
  }
}

// Nueva función de verificación rápida
const verificacionRapida = async () => {
  if (!qrData.value.id) return

  isVerifyingRapida.value = true

  try {
    // Simulación de verificación más rápida con timeout reducido
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), 5000) // 5 segundos máximo

    const response = await fetch('/api/pagos/qr/verificar-estado', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        qr_id: qrData.value.id
      }),
      signal: controller.signal
    })

    clearTimeout(timeoutId)

    const result = await response.json()

    if (result.success) {
      const nuevoEstado = result.qr.estado
      const estadoAnterior = qrData.value.estado

      qrData.value.estado = nuevoEstado

      if (estadoAnterior !== nuevoEstado && nuevoEstado === 'PAGADO') {
        showSuccess('¡Pago detectado!', 'La verificación rápida detectó el pago')
        emit('qr-paid', result.qr)
        stopAutoVerificarQR()
      } else {
        showInfo('Verificación rápida', 'Estado: ' + getEstadoText(nuevoEstado))
      }
    } else {
      showError('Error en verificación rápida', result.message)
    }
  } catch (error) {
    if (error.name === 'AbortError') {
      showWarning('Timeout', 'La verificación rápida excedió el tiempo límite')
    } else {
      console.error('Error en verificación rápida:', error)
      showError('Error', 'No se pudo completar la verificación rápida')
    }
  } finally {
    isVerifyingRapida.value = false
  }
}

const cancelarQR = async () => {
  if (!qrData.value.id) return

  isCancelling.value = true

  try {
    const response = await fetch(`/api/pagos/qr/${qrData.value.id}/cancelar`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    const result = await response.json()

    if (result.success) {
      showSuccess('QR cancelado', 'El código QR ha sido cancelado exitosamente')
      resetQR()
    } else {
      showError('Error al cancelar QR', result.message)
    }
  } catch (error) {
    console.error('Error cancelando QR:', error)
    showError('Error de conexión', 'No se pudo cancelar el QR')
  } finally {
    isCancelling.value = false
  }
}

const startAutoVerificarQR = () => {
  if (autoVerificarInterval.value) return

  autoVerificarInterval.value = setInterval(() => {
    if (autoVerificar.value && qrGenerated.value && qrData.value.estado === 'PENDIENTE') {
      verificarEstado()
    }
  }, 10000) // Cambiado a 10 segundos como solicitaste
}

const stopAutoVerificarQR = () => {
  if (autoVerificarInterval.value) {
    clearInterval(autoVerificarInterval.value)
    autoVerificarInterval.value = null
  }
}

const resetQR = () => {
  qrGenerated.value = false
  qrImage.value = ''
  qrData.value = {}
  estadoMessage.value = ''
  stopAutoVerificarQR()
}

// Métodos de utilidad
const formatCurrency = (amount) => {
  if (!amount) return 'Bs 0,00'
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(amount)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getEstadoClass = (estado) => {
  switch (estado) {
    case 'PAGADO':
      return 'bg-green-100 text-green-800'
    case 'PENDIENTE':
      return 'bg-yellow-100 text-yellow-800'
    case 'VENCIDO':
      return 'bg-red-100 text-red-800'
    case 'CANCELADO':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getEstadoText = (estado) => {
  switch (estado) {
    case 'PAGADO':
      return 'Pagado'
    case 'PENDIENTE':
      return 'Pendiente'
    case 'VENCIDO':
      return 'Vencido'
    case 'CANCELADO':
      return 'Cancelado'
    default:
      return estado
  }
}

const getMessageClass = () => {
  if (qrData.value.estado === 'PAGADO') return 'bg-green-100 text-green-800'
  if (qrData.value.estado === 'VENCIDO') return 'bg-red-100 text-red-800'
  return 'bg-blue-100 text-blue-800'
}

const getMessageIcon = () => {
  if (qrData.value.estado === 'PAGADO') return 'fas fa-check-circle'
  if (qrData.value.estado === 'VENCIDO') return 'fas fa-exclamation-triangle'
  return 'fas fa-info-circle'
}

// Watchers
watch(autoVerificar, (newValue) => {
  if (newValue && qrGenerated.value && qrData.value.estado === 'PENDIENTE') {
    startAutoVerificarQR()
  } else {
    stopAutoVerificarQR()
  }
})

// Lifecycle
onUnmounted(() => {
  stopAutoVerificarQR()
})
</script>

<style scoped>
/* Contenedor del QR mejorado */
.qr-image-container-enhanced {
  position: relative;
  padding: 30px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  border: 3px solid #f3f4f6;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.qr-image-container-enhanced:hover {
  transform: scale(1.02);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
}

.qr-image-enhanced {
  width: 320px;
  height: 320px;
  image-rendering: pixelated;
  border-radius: 12px;
}

/* Overlay informativo */
.qr-overlay-info {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(59, 130, 246, 0.9);
  color: white;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  backdrop-filter: blur(10px);
}

/* Estilos del switch */
.dot {
  transition: all 0.3s ease;
}

/* Contenedor principal responsive */
.payment-qr-container {
  min-height: 200px;
}

/* Responsive para el QR */
@media (max-width: 640px) {
  .qr-image-container-enhanced {
    padding: 20px;
    margin: 0 10px;
  }

  .qr-image-enhanced {
    width: 280px;
    height: 280px;
  }
}

@media (max-width: 480px) {
  .qr-image-container-enhanced {
    padding: 15px;
  }

  .qr-image-enhanced {
    width: 240px;
    height: 240px;
  }

  .qr-overlay-info {
    font-size: 10px;
    padding: 3px 8px;
  }
}

/* Animaciones */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.qr-image-container-enhanced.pulse {
  animation: pulse 2s infinite;
}

/* Estilos para el modo oscuro */
@media (prefers-color-scheme: dark) {
  .qr-image-container-enhanced {
    background: #1f2937;
    border-color: #374151;
  }

  .qr-overlay-info {
    background: rgba(37, 99, 235, 0.9);
  }
}
</style>
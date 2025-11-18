<template>
  <AppLayout>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-semibold text-gray-900">Registrar Nuevo Pago</h1>
      <Link href="/pagos" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>
        Volver
      </Link>
    </div>

    <!-- Información del Período Activo -->
    <div v-if="activePeriod" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center">
        <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>
        <div>
          <h3 class="font-semibold text-blue-900">Período Activo</h3>
          <p class="text-blue-700">
            {{ getPeriodName(activePeriod.year, activePeriod.month) }}
            <span v-if="activePeriod.status" class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
              {{ activePeriod.status }}
            </span>
          </p>
        </div>
      </div>
    </div>

    <!-- Alerta si no hay período activo -->
    <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div class="flex items-center">
        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
        <div>
          <h3 class="font-semibold text-yellow-900">Sin Período Activo</h3>
          <p class="text-yellow-700">No hay un período activo para registrar pagos. Por favor, abra un período primero.</p>
        </div>
      </div>
    </div>

    <!-- Layout en 3 Columnas -->
    <div v-if="activePeriod" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Columna Izquierda: Selección de Propietario -->
      <div class="lg:col-span-1">
        <div class="bg-card rounded-lg shadow p-6">
          <div class="flex items-center mb-4">
            <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center mr-3">
              <span class="text-primary font-semibold text-sm">1</span>
            </div>
            <h2 class="text-lg font-semibold text-foreground">Seleccionar Propietario</h2>
          </div>

          <div>
            <label class="block text-sm font-medium-foreground mb-2">
              Propietario *
            </label>
            <select
              v-model="form.propietario_id"
              required
              @change="onOwnerChange"
              class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring bg-input text-foreground"
            >
              <option value="">Seleccionar propietario...</option>
              <option v-for="owner in propietarios" :key="owner.id" :value="owner.id">
                {{ owner.nombre_completo }}
              </option>
            </select>
          </div>

          <!-- Información del propietario seleccionado -->
          <div v-if="form.propietario_id" class="mt-4 p-3 bg-muted rounded-lg">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-user text-primary"></i>
              </div>
              <div>
                <p class="font-medium text-foreground">
                  {{ propietarios.find(o => o.id === form.propietario_id)?.nombre_completo }}
                </p>
                <p class="text-sm text-muted-foreground">CI: {{ propietarios.find(o => o.id === form.propietario_id)?.ci }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Columna Central: Información de la Expensa -->
      <div class="lg:col-span-1">
        <div v-if="currentExpense" class="bg-card rounded-lg shadow p-6">
          <div class="flex items-center mb-4">
            <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center mr-3">
              <span class="text-primary font-semibold text-sm">2</span>
            </div>
            <h2 class="text-lg font-semibold text-foreground">Detalles de Expensa</h2>
          </div>

          <div class="space-y-4">
            <!-- Propiedad -->
            <div class="bg-muted rounded-lg p-3">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-muted-foreground">Propiedad</span>
                <span class="font-bold text-foreground">{{ currentExpense.propiedad?.codigo || 'N/A' }}</span>
              </div>
            </div>

            <!-- Montos -->
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-sm text-muted-foreground">Monto original:</span>
                <span class="font-semibold text-foreground">{{ formatCurrency(currentExpense.total_amount) }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-muted-foreground">Pagado hasta ahora:</span>
                <span class="font-semibold text-green-600">{{ formatCurrency(currentExpense.total_amount - currentExpense.balance) }}</span>
              </div>
              <div class="border-t border-border pt-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-foreground">Saldo pendiente:</span>
                  <span class="text-lg font-bold" :class="currentExpense.balance > 0 ? 'text-red-600' : 'text-green-600'">
                    {{ formatCurrency(currentExpense.balance) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Barra de progreso -->
            <div v-if="currentExpense.total_amount > 0" class="space-y-2">
              <div class="flex justify-between text-xs text-muted-foreground">
                <span>Progreso de pago</span>
                <span>{{ getPaymentPercentage() }}%</span>
              </div>
              <div class="w-full bg-muted rounded-full h-3">
                <div
                  class="bg-primary h-3 rounded-full transition-all duration-500"
                  :style="{ width: Math.min(getPaymentPercentage(), 100) + '%' }"
                ></div>
              </div>
            </div>

            <!-- Estado -->
            <div class="flex justify-center">
              <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full" :class="getStatusClass(currentExpense.status)">
                {{ getStatusText(currentExpense.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Alerta si no tiene expensa -->
        <div v-else-if="form.propietario_id && !loadingExpense" class="bg-muted border border-border rounded-lg p-6">
          <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
            <div>
              <h3 class="font-semibold text-foreground">Sin Expensa del Período</h3>
              <p class="text-muted-foreground text-sm mt-1">
                El propietario seleccionado no tiene una expensa generada para el período actual.
              </p>
              <p class="text-muted-foreground text-xs mt-2">
                Por favor, genere las expensas del período antes de registrar pagos.
              </p>
            </div>
          </div>
        </div>

        <!-- Indicador de carga -->
        <div v-else-if="loadingExpense" class="bg-card rounded-lg shadow p-12">
          <div class="text-center">
            <i class="fas fa-spinner fa-spin text-primary text-2xl mb-3"></i>
            <p class="text-muted-foreground">Cargando información de expensa...</p>
          </div>
        </div>
      </div>

      <!-- Columna Derecha: Información de Pago (Card Principal) -->
      <div class="lg:col-span-1">
        <div v-if="currentExpense" class="bg-primary border-2 border-primary/20 rounded-xl shadow-xl p-6 text-primary-foreground">
          <div class="flex items-center mb-6">
            <div class="w-8 h-8 bg-primary-foreground/20 rounded-full flex items-center justify-center mr-3">
              <span class="text-primary-foreground font-semibold text-sm">3</span>
            </div>
            <h2 class="text-xl font-bold">Información del Pago</h2>
          </div>

          <!-- Formulario de pago -->
          <form @submit.prevent="submitPayment" class="space-y-4">
            <!-- Tipo de Pago -->
            <div>
              <label class="block text-sm font-medium text-primary-foreground/80 mb-2">Tipo de Pago *</label>
              <select
                v-model="form.payment_type_id"
                required
                @change="onPaymentTypeChange"
                class="w-full px-3 py-2 border border-primary-foreground/20 rounded-md focus:outline-none focus:ring-2 focus:ring-ring bg-primary-foreground/90 text-primary"
              >
                <option value="">Seleccionar...</option>
                <option v-for="type in paymentTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <!-- Monto del Pago -->
            <div>
              <label class="block text-sm font-medium text-primary-foreground/80 mb-2">
                Monto a Pagar *
              </label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-primary font-medium">Bs.</span>
                <input
                  v-model.number="form.amount"
                  type="number"
                  step="0.01"
                  min="0.01"
                  required
                  @input="onAmountChange"
                  class="w-full pl-12 pr-3 py-3 border border-primary-foreground/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-ring bg-primary-foreground/90 text-primary font-semibold text-lg"
                  placeholder="0.00"
                />
              </div>
              <!-- Indicador de deuda -->
              <div class="mt-1 text-xs text-primary-foreground/70">
                Saldo pendiente: {{ formatCurrency(currentExpense.balance) }}
              </div>
            </div>

            <!-- Fecha de Pago -->
            <div>
              <label class="block text-sm font-medium text-primary-foreground/80 mb-2">Fecha de Pago *</label>
              <input
                v-model="form.payment_date"
                type="date"
                required
                class="w-full px-3 py-2 border border-primary-foreground/20 rounded-md focus:outline-none focus:ring-2 focus:ring-ring bg-primary-foreground/90 text-primary"
              />
            </div>

            <!-- QR Payment Section (Placeholder para futura funcionalidad) -->
            <div v-if="form.payment_type_id && paymentTypes.find(t => t.id === form.payment_type_id)?.code === 'QR'" class="bg-primary-foreground/10 rounded-lg p-4 border border-primary-foreground/30">
              <div class="flex items-center justify-center py-6">
                <div class="text-center">
                  <div class="w-16 h-16 bg-primary-foreground/20 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-qrcode text-2xl text-primary-foreground"></i>
                  </div>
                  <p class="text-primary-foreground/90 text-sm">QR Payment Integration</p>
                  <p class="text-primary-foreground/70 text-xs mt-1">Próximamente disponible</p>
                  <div class="mt-3 p-2 bg-primary-foreground/10 rounded text-xs">
                    <p class="text-primary-foreground/80">API del Banco → QR Dinámico → Pago Instantáneo</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Referencia -->
            <div v-if="form.payment_type_id && paymentTypes.find(t => t.id === form.payment_type_id)?.code !== 'QR'">
              <label class="block text-sm font-medium text-primary-foreground/80 mb-2">Referencia</label>
              <input
                v-model="form.reference"
                type="text"
                :placeholder="getReferencePlaceholder()"
                class="w-full px-3 py-2 border border-primary-foreground/20 rounded-md focus:outline-none focus:ring-2 focus:ring-ring bg-primary-foreground/90 text-primary"
              />
            </div>

            <!-- Resumen del Pago -->
            <div v-if="form.amount > 0" class="bg-primary-foreground/10 rounded-lg p-4 space-y-3">
              <h4 class="font-semibold text-primary-foreground">Resumen</h4>

              <div class="flex justify-between items-center text-sm">
                <span class="text-primary-foreground/70">Saldo pendiente:</span>
                <span class="font-medium text-primary-foreground">{{ formatCurrency(currentExpense.balance) }}</span>
              </div>

              <div class="flex justify-between items-center bg-primary-foreground/10 p-2 rounded">
                <span class="font-semibold text-primary-foreground">Monto a pagar:</span>
                <span class="font-bold text-xl text-primary-foreground">{{ formatCurrency(form.amount) }}</span>
              </div>

              <div v-if="willHaveCredit()" class="bg-green-500/20 rounded p-2">
                <div class="flex items-center text-sm text-green-100">
                  <i class="fas fa-check-circle mr-2"></i>
                  <span>Saldo a favor: {{ formatCurrency(getCreditAmount()) }}</span>
                </div>
              </div>
              <div v-else-if="Math.abs(calculateRemainingBalance()) < 0.01" class="bg-green-500/20 rounded p-2">
                <div class="flex items-center text-sm text-green-100">
                  <i class="fas fa-check-circle mr-2"></i>
                  <span>Pago completo</span>
                </div>
              </div>
              <div v-else class="bg-yellow-500/20 rounded p-2">
                <div class="flex items-center text-sm text-yellow-100">
                  <i class="fas fa-info-circle mr-2"></i>
                  <span>Saldo restante: {{ formatCurrency(calculateRemainingBalance()) }}</span>
                </div>
              </div>
            </div>

            <!-- Botones -->
            <div class="flex space-x-3 pt-4">
              <Link
                href="/pagos"
                class="flex-1 px-4 py-3 border border-primary-foreground/30 rounded-lg text-center text-sm font-medium text-primary-foreground hover:bg-primary-foreground/10 transition-colors"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                :disabled="submitting || !isValidForm()"
                class="flex-1 px-4 py-3 bg-primary-foreground text-primary rounded-lg text-sm font-bold hover:bg-primary-foreground/90 disabled:bg-muted disabled:text-muted-foreground transition-colors"
              >
                <i class="fas fa-check mr-2"></i>
                {{ submitting ? 'Procesando...' : 'Registrar Pago' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Notas adicionales (debajo del card de pago) -->
        <div v-if="currentExpense" class="mt-6 bg-card rounded-lg shadow p-6">
          <label class="block text-sm font-medium-foreground mb-2">
            <i class="fas fa-sticky-note mr-2 text-muted-foreground"></i>
            Notas adicionales
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring bg-input text-foreground text-sm"
            placeholder="Notas sobre el pago..."
          ></textarea>
        </div>
      </div>
    </div>
  </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { fetchWithCsrf, refreshCsrfToken } from '@/utils/csrf'
import { useNotification } from '@/composables/useNotification'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  paymentTypes: Array,
  propietarios: Array,
  nextReceiptNumber: String,
  activePeriod: Object,
  selectedOwner: Object,
  currentExpense: Object
})

const { showSuccess, showError, showWarning, showInfo } = useNotification()

const form = reactive({
  receipt_number: props.nextReceiptNumber || '',
  propietario_id: '',
  propiedad_id: '',
  payment_type_id: '',
  amount: '',
  payment_date: new Date().toISOString().split('T')[0],
  reference: '',
  notes: ''
})

const currentExpense = ref(props.currentExpense || null)
const loadingExpense = ref(false)
const submitting = ref(false)

const getPeriodName = (year, month) => {
  const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                     'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
  return `${monthNames[month - 1]} ${year}`
}

const getReferencePlaceholder = () => {
  const paymentType = props.paymentTypes?.find(t => t.id === form.payment_type_id)
  if (!paymentType) return 'Número de referencia'

  switch (paymentType.code) {
    case 'TRANSFER':
      return 'Número de transferencia'
    case 'QR':
      return 'Código QR o referencia'
    case 'CASH':
      return 'N/A (efectivo)'
    default:
      return 'Número de referencia'
  }
}

const onOwnerChange = async () => {
  currentExpense.value = null
  form.propiedad_id = ''
  form.amount = ''

  if (form.propietario_id) {
    await loadCurrentExpense()
  }
}

const loadCurrentExpense = async () => {
  if (!form.propietario_id) return

  loadingExpense.value = true

  try {
    const response = await fetch(`/api/pagos/propietario/${form.propietario_id}/expensa-actual`)
    const result = await response.json()

    if (result.success) {
      currentExpense.value = result.expense
      form.propiedad_id = result.expense.propiedad_id
      form.amount = result.expense.balance > 0 ? result.expense.balance : result.expense.total_amount
    } else {
      currentExpense.value = null
    }
  } catch (error) {
    console.error('Error cargando expensa:', error)
    currentExpense.value = null
  } finally {
    loadingExpense.value = false
  }
}

const onPaymentTypeChange = () => {
  form.reference = ''
}

const onAmountChange = () => {
  // Validar que el monto sea positivo
  if (form.amount < 0) {
    form.amount = 0
  }
}

const calculateRemainingBalance = () => {
  if (!currentExpense.value) return 0
  return Math.max(0, currentExpense.value.balance - form.amount)
}

const willHaveCredit = () => {
  if (!currentExpense.value) return false
  return form.amount > currentExpense.value.balance
}

const getCreditAmount = () => {
  if (!currentExpense.value) return 0
  return Math.max(0, form.amount - currentExpense.value.balance)
}

const getRemainingBalanceColor = () => {
  const remaining = calculateRemainingBalance()
  if (remaining === 0) return 'text-green-600'
  if (remaining > 0) return 'text-red-600'
  return 'text-blue-600'
}

const getStatusClass = (status) => {
  switch (status) {
    case 'paid':
      return 'bg-green-100 text-green-800'
    case 'partial':
      return 'bg-yellow-100 text-yellow-800'
    case 'pending':
      return 'bg-blue-100 text-blue-800'
    case 'overdue':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status) => {
  switch (status) {
    case 'paid':
      return 'Pagada'
    case 'partial':
      return 'Parcial'
    case 'pending':
      return 'Pendiente'
    case 'overdue':
      return 'Vencida'
    default:
      return 'Desconocido'
  }
}

const getPaymentPercentage = () => {
  if (!currentExpense.value || currentExpense.value.total_amount <= 0) return 0

  const paidAmount = currentExpense.value.total_amount - currentExpense.value.balance
  return Math.round((paidAmount / currentExpense.value.total_amount) * 100)
}

const isValidForm = () => {
  return form.propietario_id &&
         form.payment_type_id &&
         form.amount > 0 &&
         form.payment_date &&
         currentExpense.value
}

const submitPayment = async () => {
  if (!isValidForm()) return

  submitting.value = true

  try {
    // Usar el helper con manejo automático de CSRF
    const response = await fetchWithCsrf('/pagos', {
      method: 'POST',
      body: JSON.stringify(form)
    })

    // Manejar respuestas que no son JSON (como page expired)
    const text = await response.text()
    let result

    try {
      result = JSON.parse(text)
    } catch (e) {
      // Si no es JSON, probablemente es una página HTML de error
      if (text.includes('419') || text.includes('Page Expired') || text.includes('CSRF') || text.includes('token mismatch')) {
        showError('Sesión expirada', 'La sesión ha expirado o el token CSRF es inválido.')

        // Intentar recargar el token CSRF sin recargar toda la página
        try {
          await refreshCsrfToken()
          showSuccess('Token recargado', 'Token CSRF recargado. Por favor, intenta registrar el pago nuevamente.')
          return
        } catch (refreshError) {
          showError('Error al recargar token', 'No se pudo recargar el token. Recargando la página completa...')
          setTimeout(() => {
            window.location.reload()
          }, 2000)
          return
        }
      } else {
        console.error('Respuesta no JSON:', text)
        showError('Error inesperado', 'El servidor devolvió una respuesta inesperada. Recargando la página...')
        setTimeout(() => {
          window.location.reload()
        }, 2000)
        return
      }
    }

    // Si la respuesta es un error de CSRF, intentar refrescar el token
    if (!result.success && result.message && result.message.includes('CSRF')) {
      showError('Error de token CSRF', 'Intentando recargar el token...')
      try {
        await refreshCsrfToken()
        showSuccess('Token recargado', 'Por favor, intenta registrar el pago nuevamente.')
        return
      } catch (refreshError) {
        showError('Error al recargar token', 'No se pudo recargar el token. Recargando la página completa...')
        setTimeout(() => {
          window.location.reload()
        }, 2000)
        return
      }
    }

    if (result.success) {
      // Mostrar notificación de éxito principal
      showSuccess('Pago registrado exitosamente', `Recibo: ${result.receipt_number}`)

      // Redirigir al detalle del pago después de mostrar la notificación
      setTimeout(() => {
        router.visit(`/pagos/${result.payment_id}`)
      }, 3000)
    } else {
      showError('Error al registrar pago', result.message || 'Ocurrió un error desconocido')
    }
  } catch (error) {
    console.error('Error en la petición:', error)

    // Verificar si es un error de red o del servidor
    if (error.name === 'TypeError' || error.message.includes('fetch')) {
      showError('Error de conexión', 'No se pudo conectar al servidor. Verifica tu conexión a internet.')
    } else {
      showError('Error al procesar', 'Ocurrió un error al registrar el pago. Por favor, intenta nuevamente.')
    }
  } finally {
    submitting.value = false
  }
}

const formatCurrency = (amount) => {
  if (!amount) return 'Bs 0,00'
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(amount)
}

onMounted(() => {
  // Si hay un propietario seleccionado, precargar sus datos
  if (props.selectedOwner) {
    form.propietario_id = props.selectedOwner.id
  }

  // Si ya hay una expensa cargada, precargar el monto
  if (currentExpense.value) {
    form.propiedad_id = currentExpense.value.propiedad_id
    form.amount = currentExpense.value.balance > 0 ? currentExpense.value.balance : currentExpense.value.total_amount
  }
})
</script>
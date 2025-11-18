<template>
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

    <!-- Formulario -->
    <div v-if="activePeriod" class="bg-white rounded-lg shadow">
      <form @submit.prevent="submitPayment" class="space-y-6 p-6">
        <!-- Selección de Propietario -->
        <div class="border-b pb-4">
          <h2 class="text-lg font-medium text-gray-900">1. Seleccionar Propietario</h2>
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Propietario *
            </label>
            <select
              v-model="form.propietario_id"
              required
              @change="onOwnerChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Seleccionar propietario...</option>
              <option v-for="owner in propietarios" :key="owner.id" :value="owner.id">
                {{ owner.nombre_completo }}
              </option>
            </select>
          </div>
        </div>

        <!-- Información de la Expensa Actual -->
        <div v-if="currentExpense" class="border-b pb-4">
          <h2 class="text-lg font-medium text-gray-900">2. Expensa del Período</h2>
          <div class="mt-4 bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <!-- Propiedad -->
              <div>
                <span class="text-sm font-medium text-gray-500">Propiedad:</span>
                <p class="text-sm text-gray-900 font-semibold">
                  {{ currentExpense.propiedad?.codigo || 'N/A' }}
                </p>
              </div>

              <!-- Monto original de la expensa -->
              <div>
                <span class="text-sm font-medium text-gray-500">Monto original:</span>
                <p class="text-lg font-bold text-gray-700">
                  {{ formatCurrency(currentExpense.total_amount) }}
                </p>
              </div>

              <!-- Total pagado hasta ahora -->
              <div>
                <span class="text-sm font-medium text-gray-500">Pagado hasta ahora:</span>
                <p class="text-lg font-bold text-green-600">
                  {{ formatCurrency(currentExpense.total_amount - currentExpense.balance) }}
                </p>
              </div>

              <!-- Saldo pendiente -->
              <div>
                <span class="text-sm font-medium text-gray-500">Saldo pendiente:</span>
                <p class="text-lg font-bold" :class="currentExpense.balance > 0 ? 'text-red-600' : 'text-green-600'">
                  {{ formatCurrency(currentExpense.balance) }}
                </p>
              </div>
            </div>

            <!-- Barra de progreso visual -->
            <div v-if="currentExpense.total_amount > 0" class="mt-4">
              <div class="flex justify-between text-xs text-gray-600 mb-1">
                <span>Progreso de pago</span>
                <span>{{ getPaymentPercentage() }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: Math.min(getPaymentPercentage(), 100) + '%' }"
                ></div>
              </div>
            </div>

            <!-- Estado de la expensa -->
            <div v-if="currentExpense.status !== 'paid'" class="mt-3">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(currentExpense.status)">
                {{ getStatusText(currentExpense.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Alerta si no tiene expensa -->
        <div v-else-if="form.propietario_id && !loadingExpense" class="border-b pb-4">
          <h2 class="text-lg font-medium text-gray-900">2. Expensa del Período</h2>
          <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center">
              <i class="fas fa-exclamation-circle text-yellow-600 mr-3"></i>
              <div>
                <p class="text-yellow-800">
                  El propietario seleccionado no tiene una expensa generada para el período actual.
                </p>
                <p class="text-yellow-600 text-sm mt-1">
                  Por favor, genere las expensas del período antes de registrar pagos.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Información del Pago -->
        <div v-if="currentExpense" class="border-b pb-4">
          <h2 class="text-lg font-medium text-gray-900">3. Información del Pago</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Número de Recibo
              </label>
              <input
                v-model="form.receipt_number"
                type="text"
                readonly
                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Fecha de Pago *
              </label>
              <input
                v-model="form.payment_date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Tipo de Pago *
              </label>
              <select
                v-model="form.payment_type_id"
                required
                @change="onPaymentTypeChange"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Seleccionar...</option>
                <option v-for="type in paymentTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Monto del Pago *
                <span v-if="currentExpense && form.amount > currentExpense.balance" class="text-yellow-600 text-xs ml-2">
                  <i class="fas fa-exclamation-triangle mr-1"></i>Excede el saldo pendiente
                </span>
              </label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">Bs.</span>
                <input
                  v-model.number="form.amount"
                  type="number"
                  step="0.01"
                  min="0.01"
                  required
                  @input="onAmountChange"
                  :class="[
                    'w-full pl-12 pr-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500',
                    form.amount > currentExpense.balance ? 'border-yellow-400 bg-yellow-50' : 'border-gray-300'
                  ]"
                />
              </div>
              <!-- Indicador de deuda pendiente -->
              <div v-if="currentExpense" class="mt-1 text-xs text-gray-600">
                Saldo pendiente: {{ formatCurrency(currentExpense.balance) }}
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Referencia
              </label>
              <input
                v-model="form.reference"
                type="text"
                :placeholder="getReferencePlaceholder()"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Previsualización del resultado -->
          <div v-if="form.amount > 0" class="mt-4 p-4 bg-blue-50 rounded-lg">
            <h4 class="font-medium text-blue-900 mb-3">Resumen del Pago:</h4>
            <div class="text-sm space-y-2">
              <!-- Deuda original -->
              <div class="flex justify-between items-center pb-2 border-b">
                <span class="text-gray-600">Deuda original:</span>
                <span class="font-medium text-gray-900">
                  {{ formatCurrency(currentExpense.total_amount) }}
                </span>
              </div>

              <!-- Saldo pendiente antes del pago -->
              <div class="flex justify-between items-center">
                <span class="text-gray-600">Saldo pendiente:</span>
                <span class="font-medium text-red-600">
                  {{ formatCurrency(currentExpense.balance) }}
                </span>
              </div>

              <!-- Monto recibido -->
              <div class="flex justify-between items-center bg-white p-2 rounded border">
                <span class="font-semibold text-gray-900">Monto recibido:</span>
                <span class="font-bold text-blue-600 text-base">
                  {{ formatCurrency(form.amount) }}
                </span>
              </div>

              <!-- Diferencia (pago excedido) -->
              <div v-if="form.amount > currentExpense.balance" class="flex justify-between items-center p-2 bg-yellow-100 rounded">
                <span class="text-yellow-800">
                  <i class="fas fa-arrow-up mr-1"></i>Pago excedido:
                </span>
                <span class="font-bold text-yellow-900">
                  +{{ formatCurrency(form.amount - currentExpense.balance) }}
                </span>
              </div>

              <!-- Resultado final -->
              <div class="flex justify-between items-center pt-2 border-t">
                <span class="font-semibold text-gray-900">Saldo final:</span>
                <span class="font-bold" :class="getRemainingBalanceColor()">
                  {{ calculateRemainingBalance() >= 0 ? formatCurrency(calculateRemainingBalance()) : '+' + formatCurrency(Math.abs(calculateRemainingBalance())) }}
                </span>
              </div>
            </div>

            <!-- Mensaje de saldo a favor -->
            <div v-if="willHaveCredit()" class="mt-3 p-3 bg-green-100 border border-green-200 rounded-lg">
              <div class="flex items-start">
                <i class="fas fa-check-circle text-green-600 mr-2 mt-0.5"></i>
                <div class="text-green-800">
                  <p class="font-medium">Pago con saldo a favor</p>
                  <p class="text-sm">
                    El propietario abonará {{ formatCurrency(form.amount) }} y quedará con un
                    <strong>saldo a favor de {{ formatCurrency(getCreditAmount()) }}</strong>
                    para futuras expensas.
                  </p>
                </div>
              </div>
            </div>

            <!-- Mensaje de pago exacto -->
            <div v-else-if="Math.abs(calculateRemainingBalance()) < 0.01" class="mt-3 p-3 bg-green-100 border border-green-200 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                <span class="text-green-800 font-medium">Pago exacto - Liquidación completa</span>
              </div>
            </div>

            <!-- Mensaje de pago parcial -->
            <div v-else class="mt-3 p-3 bg-yellow-100 border border-yellow-200 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                <span class="text-yellow-800">
                  Quedará un saldo pendiente de {{ formatCurrency(calculateRemainingBalance()) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notas -->
        <div v-if="currentExpense">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Notas (Opcional)
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Notas adicionales sobre el pago..."
          ></textarea>
        </div>

        <!-- Botones -->
        <div v-if="currentExpense" class="flex justify-end space-x-3 pt-4">
          <Link
            href="/pagos"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="submitting || !isValidForm()"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            <i class="fas fa-save mr-2"></i>
            {{ submitting ? 'Registrando...' : 'Registrar Pago' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { fetchWithCsrf, refreshCsrfToken } from '@/utils/csrf'

const props = defineProps({
  paymentTypes: Array,
  propietarios: Array,
  nextReceiptNumber: String,
  activePeriod: Object,
  selectedOwner: Object,
  currentExpense: Object
})

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
        alert('La sesión ha expirado o el token CSRF es inválido. Intentando recargar el token...')

        // Intentar recargar el token CSRF sin recargar toda la página
        try {
          await refreshCsrfToken()
          alert('Token recargado. Por favor, intenta registrar el pago nuevamente.')
          return
        } catch (refreshError) {
          alert('Error recargando el token. Recargando la página completa...')
          window.location.reload()
          return
        }
      } else {
        console.error('Respuesta no JSON:', text)
        alert('Error inesperado del servidor. Por favor, recarga la página.')
        window.location.reload()
        return
      }
    }

    // Si la respuesta es un error de CSRF, intentar refrescar el token
    if (!result.success && result.message && result.message.includes('CSRF')) {
      alert('Error de token CSRF. Intentando recargar...')
      try {
        await refreshCsrfToken()
        alert('Token recargado. Por favor, intenta registrar el pago nuevamente.')
        return
      } catch (refreshError) {
        alert('Error recargando el token. Recargando la página completa...')
        window.location.reload()
        return
      }
    }

    if (result.success) {
      // Construir mensaje de éxito con información del período
      let message = `Pago registrado exitosamente!\n\nRecibo: ${result.receipt_number}\n${result.allocation_result.message}`

      // Agregar información del período si está disponible
      if (result.allocation_result.period_summary) {
        const period = result.allocation_result.period_summary
        message += `\n\n\n--- Resumen del Período ---\n`
        message += `Período: ${period.period_name}\n`
        message += `Total Generado: ${formatCurrency(period.total_generated)}\n`
        message += `Total Cobrado (antes): ${formatCurrency(period.total_collected_before)}\n`
        message += `Total Cobrado (después): ${formatCurrency(period.total_collected_after)}\n`
        message += `Porcentaje de Cobro: ${period.collection_percentage.toFixed(2)}%\n`
        message += `Saldo Pendiente: ${formatCurrency(period.pending_amount)}`
      }

      alert(message)

      // Redirigir al detalle del pago
      router.visit(`/pagos/${result.payment_id}`)
    } else {
      alert(result.message || 'Error al registrar el pago')
    }
  } catch (error) {
    console.error('Error en la petición:', error)

    // Verificar si es un error de red o del servidor
    if (error.name === 'TypeError' || error.message.includes('fetch')) {
      alert('Error de conexión. Verifica tu conexión a internet.')
    } else {
      alert('Error al registrar el pago. Por favor, recarga la página e intenta nuevamente.')
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
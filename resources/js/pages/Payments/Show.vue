<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Detalle del Pago</h1>
        <p class="text-gray-600 mt-1">Recibo: {{ payment.receipt_number }}</p>
      </div>
      <div class="flex space-x-3">
        <Link href="/pagos" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
          <i class="fas fa-arrow-left mr-2"></i>
          Volver
        </Link>
        <button
          v-if="payment.status === 'active'"
          @click="confirmCancelPayment"
          class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
        >
          <i class="fas fa-times mr-2"></i>
          Anular Pago
        </button>
      </div>
    </div>

    <!-- Información Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Información del Pago -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Información del Pago</h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Datos del Recibo</h3>
                <dl class="space-y-2">
                  <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Número de Recibo:</dt>
                    <dd class="text-sm text-gray-600">{{ payment.receipt_number }}</dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Fecha de Pago:</dt>
                    <dd class="text-sm text-gray-600">{{ formatDate(payment.payment_date) }}</dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Fecha de Registro:</dt>
                    <dd class="text-sm text-gray-600">{{ formatDateTime(payment.registered_at) }}</dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Monto:</dt>
                    <dd class="text-sm font-semibold text-lg text-blue-600">
                      {{ formatCurrency(payment.amount) }}
                    </dd>
                  </div>
                </dl>
              </div>

              <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Método de Pago</h3>
                <dl class="space-y-2">
                  <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Tipo:</dt>
                    <dd class="text-sm text-gray-600">{{ payment.payment_type?.name || 'N/A' }}</dd>
                  </div>
                  <div v-if="payment.reference" class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Referencia:</dt>
                    <dd class="text-sm text-gray-600">{{ payment.reference }}</dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Estado:</dt>
                    <dd>
                      <span
                        :class="[
                          'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                          payment.status === 'active'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800'
                        ]"
                      >
                        {{ payment.status === 'active' ? 'Activo' : 'Anulado' }}
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <div v-if="payment.notes" class="mt-6 pt-6 border-t border-gray-200">
              <h3 class="text-sm font-medium text-gray-500 mb-2">Notas</h3>
              <p class="text-sm text-gray-600">{{ payment.notes }}</p>
            </div>

            <!-- Información de Anulación -->
            <div v-if="payment.status === 'cancelled'" class="mt-6 pt-6 border-t border-gray-200">
              <h3 class="text-sm font-medium text-gray-500 mb-2">Información de Anulación</h3>
              <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <dl class="space-y-2">
                  <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Anulado por:</dt>
                    <dd class="text-sm text-gray-600">{{ payment.cancelled_by?.name || 'N/A' }}</dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-900">Fecha de anulación:</dt>
                    <dd class="text-sm text-gray-600">{{ formatDateTime(payment.cancelled_at) }}</dd>
                  </div>
                  <div v-if="payment.cancellation_reason">
                    <dt class="text-sm font-medium text-gray-900">Motivo:</dt>
                    <dd class="text-sm text-gray-600 mt-1">{{ payment.cancellation_reason }}</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Información del Propietario y Propiedad -->
      <div>
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Propietario y Propiedad</h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Propietario</h3>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="font-medium text-gray-900">
                    {{ payment.propietario?.nombre_completo || 'N/A' }}
                  </p>
                  <p v-if="payment.propietario?.ci" class="text-sm text-gray-600">
                    CI: {{ payment.propietario.ci }}
                  </p>
                  <p v-if="payment.propietario?.telefono" class="text-sm text-gray-600">
                    Tel: {{ payment.propietario.telefono }}
                  </p>
                </div>
              </div>

              <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Propiedad</h3>
                <div class="bg-blue-50 rounded-lg p-3">
                  <p class="font-medium text-gray-900">
                    {{ payment.propiedad?.codigo || 'N/A' }}
                  </p>
                  <p v-if="payment.propiedad?.ubicacion" class="text-sm text-gray-600">
                    {{ payment.propiedad.ubicacion }}
                  </p>
                  <p v-if="payment.propiedad?.metros_cuadrados" class="text-sm text-gray-600">
                    {{ payment.propiedad.metros_cuadrados }} m²
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Imputaciones del Pago -->
    <div v-if="payment.allocations?.length > 0" class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Imputaciones del Pago</h2>
        <p class="text-sm text-gray-600 mt-1">
          Distribución del monto entre las expensas del propietario
        </p>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Período
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Propiedad
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Monto Imputado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Saldo Restante
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado de la Expensa
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="allocation in payment.allocations"
              :key="allocation.id"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ getPeriodName(allocation.property_expense?.expense_period) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ allocation.property_expense?.propiedad?.codigo || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                {{ formatCurrency(allocation.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatCurrency(allocation.property_expense?.balance || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                    getStatusClass(allocation.property_expense?.status)
                  ]"
                >
                  {{ getStatusText(allocation.property_expense?.status) }}
                </span>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr>
              <td colspan="2" class="px-6 py-3 text-sm font-medium text-gray-900">
                Total Imputado
              </td>
              <td class="px-6 py-3 text-sm font-bold text-green-600">
                {{ formatCurrency(totalAllocated) }}
              </td>
              <td colspan="2" class="px-6 py-3"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Resumen Financiero -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
              <i class="fas fa-dollar-sign text-blue-600 text-sm"></i>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Monto del Pago</h3>
            <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(payment.amount) }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
              <i class="fas fa-check-circle text-green-600 text-sm"></i>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Total Imputado</h3>
            <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(totalAllocated) }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
              <i class="fas fa-exclamation-circle text-yellow-600 text-sm"></i>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Saldo no Imputado</h3>
            <p class="text-lg font-semibold text-gray-900">
              {{ formatCurrency(payment.amount - totalAllocated) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación de anulación -->
    <div
      v-if="showCancelModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Anular Pago
          </h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500">
              ¿Está seguro que desea anular el pago {{ payment.receipt_number }}?
            </p>
            <p class="text-sm text-gray-500 mt-2">
              Esta acción revertirá todas las imputaciones y creará un movimiento de caja de reversión.
            </p>
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Motivo de anulación
              </label>
              <textarea
                v-model="cancellationReason"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Ingrese el motivo..."
              ></textarea>
            </div>
          </div>
          <div class="items-center px-4 py-3">
            <button
              @click="cancelPayment"
              :disabled="!cancellationReason || cancelling"
              class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              {{ cancelling ? 'Anulando...' : 'Anular' }}
            </button>
            <button
              @click="showCancelModal = false"
              class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-24 hover:bg-gray-400"
            >
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  payment: Object
})

const showCancelModal = ref(false)
const cancellationReason = ref('')
const cancelling = ref(false)

const totalAllocated = computed(() => {
  if (!props.payment.allocations) return 0
  return props.payment.allocations.reduce((total, allocation) => {
    return total + parseFloat(allocation.amount || 0)
  }, 0)
})

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('es-BO')
}

const formatDateTime = (dateTimeString) => {
  if (!dateTimeString) return 'N/A'
  return new Date(dateTimeString).toLocaleString('es-BO')
}

const formatCurrency = (amount) => {
  if (!amount) return 'Bs 0,00'
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(amount)
}

const getPeriodName = (expensePeriod) => {
  if (!expensePeriod) return 'N/A'
  return `${expensePeriod.year}-${String(expensePeriod.month).padStart(2, '0')}`
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
    case 'cancelled':
      return 'Cancelada'
    default:
      return 'Desconocido'
  }
}

const confirmCancelPayment = () => {
  cancellationReason.value = ''
  showCancelModal.value = true
}

const cancelPayment = async () => {
  if (!cancellationReason.value) return

  cancelling.value = true

  try {
    const response = await fetch(`/api/pagos/${props.payment.id}/anular`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({
        cancellation_reason: cancellationReason.value
      })
    })

    const result = await response.json()

    if (result.success) {
      showCancelModal.value = false
      router.reload()
    } else {
      alert(result.message || 'Error al anular el pago')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error al anular el pago')
  } finally {
    cancelling.value = false
  }
}
</script>
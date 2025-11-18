<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-semibold text-gray-900">Pagos Registrados</h1>
      <Link href="/pagos/seleccionar-propietario" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>
        Registrar Pago
      </Link>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Búsqueda</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Recibo, propietario..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="debounceSearch"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="applyFilters"
          >
            <option value="">Todos</option>
            <option value="active">Activo</option>
            <option value="cancelled">Anulado</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Pago</label>
          <select
            v-model="filters.payment_type_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="applyFilters"
          >
            <option value="">Todos</option>
            <option v-for="type in paymentTypes" :key="type.id" :value="type.id">
              {{ type.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
          <input
            v-model="filters.date_from"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="applyFilters"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
          <input
            v-model="filters.date_to"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="applyFilters"
          />
        </div>

        <div class="flex items-end">
          <button
            @click="clearFilters"
            class="w-full px-3 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
          >
            Limpiar
          </button>
        </div>
      </div>
    </div>

    <!-- Tabla de Pagos -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Recibo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Propietario
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Propiedad
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Monto
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tipo Pago
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ payment.receipt_number }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(payment.payment_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.propietario?.nombre_completo || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.propiedad?.codigo || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatCurrency(payment.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.payment_type?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
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
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <Link
                    :href="`/pagos/${payment.id}`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    <i class="fas fa-eye"></i>
                  </Link>
                  <button
                    v-if="payment.status === 'active'"
                    @click="confirmCancelPayment(payment)"
                    class="text-red-600 hover:text-red-900"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="goToPage(payments.prev_page_url)"
            :disabled="!payments.prev_page_url"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Anterior
          </button>
          <button
            @click="goToPage(payments.next_page_url)"
            :disabled="!payments.next_page_url"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Siguiente
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Mostrando
              <span class="font-medium">{{ payments.from || 0 }}</span>
              a
              <span class="font-medium">{{ payments.to || 0 }}</span>
              de
              <span class="font-medium">{{ payments.total }}</span>
              resultados
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                v-for="link in paginationLinks"
                :key="link.label"
                @click="goToPage(link.url)"
                :disabled="!link.url || link.active"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  link.active
                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                  !link.url ? 'cursor-not-allowed opacity-50' : ''
                ]"
                v-html="link.label"
              />
            </nav>
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
              ¿Está seguro que desea anular el pago {{ selectedPayment?.receipt_number }}?
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
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

const props = defineProps({
  payments: Object,
  paymentTypes: Array,
  filters: Object
})

const showCancelModal = ref(false)
const selectedPayment = ref(null)
const cancellationReason = ref('')
const cancelling = ref(false)

const filters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  payment_type_id: props.filters?.payment_type_id || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || ''
})

const paginationLinks = computed(() => {
  if (!props.payments?.links) return []

  return props.payments.links.filter(link =>
    link.label === '...' ||
    link.label === 'pagination.previous' ||
    link.label === 'pagination.next' ||
    !isNaN(link.label)
  ).map(link => ({
    ...link,
    label: link.label === 'pagination.previous' ? '«' :
            link.label === 'pagination.next' ? '»' :
            link.label
  }))
})

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('es-BO')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(amount)
}

const applyFilters = () => {
  router.get('/pagos', filters, {
    preserveState: true,
    preserveScroll: true
  })
}

const debounceSearch = debounce(applyFilters, 300)

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  applyFilters()
}

const goToPage = (url) => {
  if (url) {
    router.visit(url, {
      preserveState: true,
      preserveScroll: true
    })
  }
}

const confirmCancelPayment = (payment) => {
  selectedPayment.value = payment
  cancellationReason.value = ''
  showCancelModal.value = true
}

const cancelPayment = async () => {
  if (!selectedPayment.value || !cancellationReason.value) return

  cancelling.value = true

  try {
    const response = await fetch(`/api/pagos/${selectedPayment.value.id}/anular`, {
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
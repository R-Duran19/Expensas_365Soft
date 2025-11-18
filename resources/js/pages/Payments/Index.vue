<template>
  <AppLayout>
  <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Pagos Registrados</h1>
      <Link href="/pagos/seleccionar-propietario" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-center sm:text-left">
        <i class="fas fa-plus mr-2"></i>
        <span class="hidden sm:inline">Registrar Pago</span>
        <span class="sm:hidden">Registrar</span>
      </Link>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-3 sm:p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
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

    <!-- Tabla de Pagos - Desktop -->
    <div class="bg-white rounded-lg shadow overflow-hidden hidden lg:block">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Recibo
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Propietario
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Propiedad
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Monto
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">
                Tipo Pago
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50">
              <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ payment.receipt_number }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(payment.payment_date) }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.propietario?.nombre_completo || 'N/A' }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ payment.propiedad?.codigo || 'N/A' }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ formatCurrency(payment.amount) }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 hidden xl:table-cell">
                {{ payment.payment_type?.name || 'N/A' }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
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
              <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-center">
               <div class="flex justify-center space-x-2">
                <Link
                :href="`/pagos/${payment.id}`"
                class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 hover:bg-blue-100 hover:text-blue-800 rounded-full transition-colors duration-200 shadow-sm"
                title="Ver detalles del pago"
                >
                <EyeIcon class="w-4 h-4" /> </Link>

                 <button
                    v-if="payment.status === 'active'"
                    @click="confirmCancelPayment(payment)"
                    class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 hover:bg-red-100 hover:text-red-800 rounded-full transition-colors duration-200 shadow-sm"
                    title="Anular pago"
                   >
                  <XIcon class="w-4 h-4" /> </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Vista Mobile - Cards -->
    <div class="lg:hidden space-y-3">
      <div v-if="payments.data.length === 0" class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
        <i class="fas fa-receipt text-4xl mb-3 text-gray-300"></i>
        <p>No se encontraron pagos</p>
      </div>

      <div v-for="payment in payments.data" :key="payment.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
        <!-- Header del card -->
        <div class="flex justify-between items-start mb-3">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <h3 class="font-semibold text-gray-900">{{ payment.receipt_number }}</h3>
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded-full',
                  payment.status === 'active'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'
                ]"
              >
                {{ payment.status === 'active' ? 'Activo' : 'Anulado' }}
              </span>
            </div>
            <p class="text-sm text-gray-500">{{ formatDate(payment.payment_date) }}</p>
          </div>
          <div class="flex space-x-1">
            <Link
              :href="`/pagos/${payment.id}`"
              class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-full transition-colors"
              title="Ver detalles"
            >
              <EyeIcon class="w-4 h-4" />
            </Link>
            <button
              v-if="payment.status === 'active'"
              @click="confirmCancelPayment(payment)"
              class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 hover:bg-red-100 rounded-full transition-colors"
              title="Anular pago"
            >
              <XIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Información principal -->
        <div class="space-y-2">
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Propietario:</span>
            <span class="text-sm font-medium text-gray-900">{{ payment.propietario?.nombre_completo || 'N/A' }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Propiedad:</span>
            <span class="text-sm font-medium text-gray-900">{{ payment.propiedad?.codigo || 'N/A' }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Monto:</span>
            <span class="text-lg font-bold text-green-600">{{ formatCurrency(payment.amount) }}</span>
          </div>
          <div v-if="payment.payment_type" class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Tipo:</span>
            <span class="text-sm text-gray-900">{{ payment.payment_type.name }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Paginación -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-3 py-4 sm:px-4 sm:py-3">
      <!-- Mobile: Simple pagination -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Info -->
        <div class="text-sm text-gray-600 text-center sm:text-left">
          <span class="font-medium">{{ payments.from || 0 }}</span>
          a
          <span class="font-medium">{{ payments.to || 0 }}</span>
          de
          <span class="font-medium">{{ payments.total }}</span>
          pagos
        </div>

        <!-- Mobile navigation -->
        <div class="flex justify-center sm:hidden">
          <div class="flex items-center space-x-2">
            <button
              @click="goToPage(payments.prev_page_url)"
              :disabled="!payments.prev_page_url"
              class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="fas fa-chevron-left mr-1"></i>
              Anterior
            </button>
            <span class="text-sm text-gray-600 px-2">
              {{ payments.current_page }} / {{ payments.last_page }}
            </span>
            <button
              @click="goToPage(payments.next_page_url)"
              :disabled="!payments.next_page_url"
              class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
              <i class="fas fa-chevron-right ml-1"></i>
            </button>
          </div>
        </div>

        <!-- Desktop navigation -->
        <div class="hidden sm:flex sm:items-center sm:justify-center">
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <button
              @click="goToPage(payments.prev_page_url)"
              :disabled="!payments.prev_page_url"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="fas fa-chevron-left"></i>
            </button>

            <!-- Page numbers -->
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

            <button
              @click="goToPage(payments.next_page_url)"
              :disabled="!payments.next_page_url"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
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
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import AppLayout from '@/layouts/AppLayout.vue'
import { EyeIcon, XIcon } from 'lucide-vue-next'

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
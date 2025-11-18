<template>
  <AppLayout :title="'Expensas del Período'">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-semibold text-gray-900">
                Expensas - {{ period.name }}
              </h1>
              <p class="mt-1 text-sm text-gray-600">
                Gestiona las expensas generadas para este período
              </p>
            </div>
            <div class="flex items-center space-x-3">
              <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="getStatusClass(period.status)"
              >
                {{ getStatusText(period.status) }}
              </span>
              <Link
                :href="`/property-expenses/create`"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Generar Expensas
              </Link>
            </div>
          </div>
        </div>

        <!-- Estadísticas -->
        <div class="px-6 py-4">
          <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900">{{ stats.total_properties }}</div>
              <div class="text-sm text-gray-500">Total Propiedades</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">{{ stats.generated_expenses }}</div>
              <div class="text-sm text-gray-500">Expensas Generadas</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">Bs {{ formatCurrency(stats.total_amount) }}</div>
              <div class="text-sm text-gray-500">Monto Total</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-500">Bs {{ formatCurrency(stats.total_collected) }}</div>
              <div class="text-sm text-gray-500">Monto Cobrado</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-yellow-600">{{ stats.pending_count }}</div>
              <div class="text-sm text-gray-500">Pendientes</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-500">{{ stats.paid_count }}</div>
              <div class="text-sm text-gray-500">Pagadas</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Búsqueda -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Buscar Propiedad o Propietario
              </label>
              <div class="relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  v-model="filters.search"
                  type="text"
                  placeholder="Código, ubicación o propietario..."
                  class="w-full pl-10 border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500"
                  @input="search"
                />
              </div>
            </div>

            <!-- Estado -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Estado
              </label>
              <select
                v-model="filters.status"
                class="w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500"
                @change="search"
              >
                <option value="">Todos los estados</option>
                <option value="pending">Pendiente</option>
                <option value="partial">Pago Parcial</option>
                <option value="paid">Pagada</option>
                <option value="overdue">Vencida</option>
                <option value="cancelled">Cancelada</option>
              </select>
            </div>

            <!-- Botones -->
            <div class="flex items-end space-x-2">
              <button
                @click="resetFilters"
                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Limpiar Filtros
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Expensas -->
      <div class="bg-white shadow-sm rounded-lg">
        <div class="overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Propiedad
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Propietario
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Facturar a
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Base
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Agua
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deuda Anterior
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Total
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Pagado
                  </th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="expense in expenses.data" :key="expense.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ expense.propiedad_codigo }}</div>
                      <div class="text-sm text-gray-500">{{ expense.propiedad_ubicacion }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ expense.propietario_nombre }}</div>
                    <div v-if="expense.inquilino_nombre" class="text-sm text-gray-500">
                      Inquilino: {{ expense.inquilino_nombre }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                      :class="expense.facturar_a === 'inquilino' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                    >
                      {{ expense.facturar_a === 'inquilino' ? 'Inquilino' : 'Propietario' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                    Bs {{ formatCurrency(expense.base_amount) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                    Bs {{ formatCurrency(expense.water_amount) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                    Bs {{ formatCurrency(expense.previous_debt) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right text-gray-900">
                    Bs {{ formatCurrency(expense.total_amount) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                    <span :class="expense.paid_amount > 0 ? 'text-green-600 font-medium' : 'text-gray-500'">
                      Bs {{ formatCurrency(expense.paid_amount) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span
                      class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                      :class="getExpenseStatusClass(expense.status)"
                    >
                      {{ getExpenseStatusText(expense.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                    <div class="flex items-center justify-center space-x-2">
                      <Link
                        :href="`/property-expenses/${expense.id}`"
                        class="text-blue-600 hover:text-blue-900"
                        title="Ver detalles"
                      >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </Link>
                      <button
                        v-if="expense.status !== 'paid'"
                        @click="editExpense(expense)"
                        class="text-indigo-600 hover:text-indigo-900"
                        title="Editar expensa"
                      >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        v-if="expense.status === 'pending'"
                        @click="deleteExpense(expense)"
                        class="text-red-600 hover:text-red-900"
                        title="Eliminar expensa"
                      >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Paginación -->
        <div v-if="expenses.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <!-- Mobile pagination -->
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Mostrando
                  <span class="font-medium">{{ expenses.from }}</span>
                  a
                  <span class="font-medium">{{ expenses.to }}</span>
                  de
                  <span class="font-medium">{{ expenses.total }}</span>
                  resultados
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <!-- Previous page link -->
                  <Link
                    v-if="expenses.prev_page_url"
                    :href="expenses.prev_page_url"
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                  </Link>
                  <span
                    v-else
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                  </span>

                  <!-- Next page link -->
                  <Link
                    v-if="expenses.next_page_url"
                    :href="expenses.next_page_url"
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </Link>
                  <span
                    v-else
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </span>
                </nav>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-if="expenses.data.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No hay expensas</h3>
          <p class="mt-1 text-sm text-gray-500">
            No se encontraron expensas para este período con los filtros aplicados.
          </p>
          <div class="mt-6">
            <Link
              :href="`/property-expenses/create`"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Generar Expensas
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  period: {
    type: Object,
    required: true
  },
  expenses: {
    type: Object,
    required: true
  },
  stats: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const filters = ref({
  search: props.filters.search || '',
  status: props.filters.status || ''
})

// Métodos
const search = debounce(() => {
  const params = new URLSearchParams(window.location.search)

  if (filters.value.search) {
    params.set('search', filters.value.search)
  } else {
    params.delete('search')
  }

  if (filters.value.status) {
    params.set('status', filters.value.status)
  } else {
    params.delete('status')
  }

  router.get(`${window.location.pathname}?${params.toString()}`, {}, {
    preserveState: true,
    preserveScroll: true
  })
}, 300)

const resetFilters = () => {
  filters.value = {
    search: '',
    status: ''
  }
  search()
}

const editExpense = (expense) => {
  // Implementar edición de expensa
  console.log('Editar expensa:', expense)
}

const deleteExpense = (expense) => {
  if (confirm('¿Estás seguro de que deseas eliminar esta expensa? Esta acción no se puede deshacer.')) {
    router.delete(`/property-expenses/${expense.id}`, {
      onSuccess: () => {
        // Éxito
      },
      onError: (errors) => {
        alert('Error al eliminar expensa: ' + (errors.message || 'Error desconocido'))
      }
    })
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

const getStatusClass = (status) => {
  switch (status) {
    case 'open':
      return 'bg-green-100 text-green-800'
    case 'closed':
      return 'bg-red-100 text-red-800'
    case 'cancelled':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status) => {
  switch (status) {
    case 'open':
      return 'Abierto'
    case 'closed':
      return 'Cerrado'
    case 'cancelled':
      return 'Cancelado'
    default:
      return status
  }
}

const getExpenseStatusClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'partial':
      return 'bg-blue-100 text-blue-800'
    case 'paid':
      return 'bg-green-100 text-green-800'
    case 'overdue':
      return 'bg-red-100 text-red-800'
    case 'cancelled':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getExpenseStatusText = (status) => {
  switch (status) {
    case 'pending':
      return 'Pendiente'
    case 'partial':
      return 'Pago Parcial'
    case 'paid':
      return 'Pagada'
    case 'overdue':
      return 'Vencida'
    case 'cancelled':
      return 'Cancelada'
    default:
      return status
  }
}
</script>
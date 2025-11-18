<template>
  <AppLayout>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-semibold text-gray-900">Seleccionar Propietario para Pago</h1>
      <Link href="/pagos" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>
        Volver
      </Link>
    </div>

    <!-- Información del Período Activo -->
    <div v-if="activePeriod" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>
          <div>
            <h3 class="font-semibold text-blue-900">Período Activo</h3>
            <p class="text-blue-700">
              {{ getPeriodName(activePeriod.year, activePeriod.month) }}
            </p>
          </div>
        </div>
        <div class="text-right">
          <span class="text-sm text-blue-600">Propietarios con expensas:</span>
          <p class="text-2xl font-bold text-blue-900">{{ pagination.total }}</p>
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

    <!-- Buscador y Paginación -->
    <div v-if="activePeriod" class="bg-white rounded-lg shadow p-4">
      <div class="mb-4">
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <div class="relative">
              <input
                :value="searchTerm"
                @input="search($event.target.value)"
                type="text"
                placeholder="Buscar por nombre, CI o código de propiedad..."
                class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
          </div>
          <div class="text-sm text-gray-600">
            {{ pagination.total }} propietarios encontrados
          </div>
        </div>
      </div>

      <!-- Debug Info (temporal) -->
      <div class="bg-yellow-50 border border-yellow-200 rounded p-2 mb-4 text-xs">
        <strong>Debug:</strong>
        Total: {{ pagination.total }},
        Páginas: {{ pagination.total_pages }},
        Actual: {{ pagination.current_page }},
        Owners: {{ owners.length }}
      </div>

      <!-- Paginación - Movida aquí debajo del buscador -->
      <div v-if="pagination.total_pages > 1" class="border-t border-gray-200 pt-4">
        <div class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <!-- Mobile pagination -->
            <button
              v-if="pagination.current_page > 1"
              @click="goToPage(pagination.current_page - 1)"
              :disabled="loading"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Anterior
            </button>
            <button
              v-if="pagination.current_page < pagination.total_pages"
              @click="goToPage(pagination.current_page + 1)"
              :disabled="loading"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Mostrando
                <span class="font-medium">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</span>
                a
                <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
                de
                <span class="font-medium">{{ pagination.total }}</span>
                resultados
                <span class="text-gray-500">• Página {{ pagination.current_page }} de {{ pagination.total_pages }}</span>
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Previous page link -->
                <button
                  v-if="pagination.current_page > 1"
                  @click="goToPage(pagination.current_page - 1)"
                  :disabled="loading"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>
                <button
                  v-else
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>

                <!-- Page numbers -->
                <template v-for="page in getVisiblePages()" :key="page">
                  <span
                    v-if="page === '...'"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                  >
                    ...
                  </span>
                  <button
                    v-else-if="page !== pagination.current_page"
                    @click="goToPage(page)"
                    :disabled="loading"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ page }}
                  </button>
                  <span
                    v-else
                    class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600"
                  >
                    {{ page }}
                  </span>
                </template>

                <!-- Next page link -->
                <button
                  v-if="pagination.current_page < pagination.total_pages"
                  @click="goToPage(pagination.current_page + 1)"
                  :disabled="loading"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
                <button
                  v-else
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de Propietarios -->
    <div v-if="activePeriod && !loading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="divide-y divide-gray-200">
        <div
          v-for="owner in owners"
          :key="owner.id"
          class="p-4 hover:bg-gray-50 cursor-pointer transition-colors"
          @click="selectOwner(owner)"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-blue-600"></i>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">{{ owner.nombre_completo }}</h3>
                <p class="text-sm text-gray-600">CI: {{ owner.ci }}</p>
                <div class="flex items-center space-x-4 mt-1">
                  <span class="text-xs text-gray-500">
                    <i class="fas fa-home mr-1"></i>
                    {{ owner.propiedades_count }} propiedad(es)
                  </span>
                  <span v-if="owner.telefono" class="text-xs text-gray-500">
                    <i class="fas fa-phone mr-1"></i>
                    {{ owner.telefono }}
                  </span>
                </div>
              </div>
            </div>

            <div class="text-right">
              <div class="text-sm text-gray-500">Deuda Total:</div>
              <div class="text-lg font-bold" :class="getDebtColor(owner.total_debt)">
                {{ formatCurrency(owner.total_debt) }}
              </div>
              <div class="text-xs text-gray-500">
                {{ owner.expenses_count }} expensa(s)
              </div>
            </div>
          </div>

          <!-- Detalle de expensas -->
          <div v-if="owner.expenses && owner.expenses.length > 0" class="mt-4 pt-4 border-t border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div
                v-for="expense in owner.expenses"
                :key="expense.id"
                class="flex items-center justify-between text-sm bg-gray-50 rounded p-2"
              >
                <div>
                  <span class="font-medium">{{ expense.propiedad?.codigo || 'N/A' }}</span>
                  <span class="text-gray-500 ml-2">{{ expense.propiedad?.ubicacion || '' }}</span>
                </div>
                <div class="text-right">
                  <span class="font-medium" :class="getDebtColor(expense.balance)">
                    {{ formatCurrency(expense.balance) }}
                  </span>
                  <div>
                    <span
                      class="px-1 inline-flex text-xs leading-5 font-semibold rounded"
                      :class="getStatusClass(expense.status)"
                    >
                      {{ getStatusText(expense.status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mensaje si no hay resultados -->
      <div v-if="owners.length === 0" class="text-center py-12">
        <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
        <p class="text-gray-600">No se encontraron propietarios con expensas</p>
        <p class="text-sm text-gray-500 mt-2">
          {{ searchTerm ? 'Intenta con otra búsqueda' : 'No hay expensas generadas para el período actual' }}
        </p>
      </div>

      <!-- Indicador cuando hay solo una página -->
      <div v-else-if="pagination.total > 0" class="text-center py-4 border-t border-gray-200 text-sm text-gray-600">
        Mostrando {{ owners.length }} de {{ pagination.total }} propietarios
      </div>
    </div>

    <!-- Estado de carga -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-12">
      <div class="text-center">
        <i class="fas fa-spinner fa-spin text-blue-600 text-4xl mb-4"></i>
        <p class="text-gray-600">Cargando propietarios...</p>
      </div>
    </div>
  </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  activePeriod: Object
})

const owners = ref([])
const loading = ref(true)
const searchTerm = ref('')
const currentPage = ref(1)
const pagination = ref({
  current_page: 1,
  total_pages: 1,
  per_page: 15,
  total: 0,
  has_more: false
})

// Función para buscar con debounce
let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    loadOwnersWithExpenses()
  }, 300)
}

// Función para ir a una página específica
const goToPage = (page) => {
  currentPage.value = page
  loadOwnersWithExpenses()
}

// Watch para cambiar búsqueda
const search = (value) => {
  searchTerm.value = value
  handleSearch()
}

// Función para obtener páginas visibles (similar a la de Index.vue)
const getVisiblePages = () => {
  const current = pagination.value.current_page
  const last = pagination.value.total_pages
  const delta = 2 // número de páginas a mostrar antes y después de la actual

  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }

  let range = []
  let rangeWithDots = []
  let l

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      range.push(i)
    }
  }

  range.forEach((i) => {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    l = i
  })

  return rangeWithDots
}

const loadOwnersWithExpenses = async () => {
  if (!props.activePeriod) {
    loading.value = false
    return
  }

  loading.value = true

  try {
    const params = new URLSearchParams({
      page: currentPage.value,
      search: searchTerm.value
    })

    const response = await fetch(`/api/pagos/propietarios-con-expensas/${props.activePeriod.id}?${params}`)
    const result = await response.json()

    if (result.success) {
      console.log('Paginación recibida:', result.pagination)
      console.log('Total propietarios:', result.owners.length, 'de', result.pagination.total)

      // Siempre reemplazar los datos para paginación tradicional
      owners.value = result.owners
      pagination.value = result.pagination
    } else {
      console.error('Error cargando propietarios:', result.message)
      owners.value = []
      pagination.value = {
        current_page: 1,
        total_pages: 1,
        per_page: 15,
        total: 0,
        has_more: false
      }
    }
  } catch (error) {
    console.error('Error en la petición:', error)
    owners.value = []
  } finally {
    loading.value = false
  }
}

const selectOwner = (owner) => {
  router.visit(`/pagos/crear/${owner.id}`)
}

const getPeriodName = (year, month) => {
  const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                     'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
  return `${monthNames[month - 1]} ${year}`
}

const getDebtColor = (amount) => {
  if (amount > 0) return 'text-red-600'
  if (amount === 0) return 'text-green-600'
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

const formatCurrency = (amount) => {
  if (!amount) return 'Bs 0,00'
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(amount)
}

onMounted(() => {
  loadOwnersWithExpenses()
})
</script>
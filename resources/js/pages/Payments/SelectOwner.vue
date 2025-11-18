<template>
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
          <p class="text-2xl font-bold text-blue-900">{{ filteredOwners.length }}</p>
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

    <!-- Buscador -->
    <div v-if="activePeriod" class="bg-white rounded-lg shadow p-4">
      <div class="flex items-center space-x-4">
        <div class="flex-1">
          <div class="relative">
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Buscar por nombre, CI o código de propiedad..."
              class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
          </div>
        </div>
        <div class="text-sm text-gray-600">
          {{ filteredOwners.length }} propietarios encontrados
        </div>
      </div>
    </div>

    <!-- Lista de Propietarios -->
    <div v-if="activePeriod && !loading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="divide-y divide-gray-200">
        <div
          v-for="owner in filteredOwners"
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
      <div v-if="filteredOwners.length === 0" class="text-center py-12">
        <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
        <p class="text-gray-600">No se encontraron propietarios con expensas</p>
        <p class="text-sm text-gray-500 mt-2">
          {{ searchTerm ? 'Intenta con otra búsqueda' : 'No hay expensas generadas para el período actual' }}
        </p>
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
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  activePeriod: Object
})

const owners = ref([])
const loading = ref(true)
const searchTerm = ref('')

const filteredOwners = computed(() => {
  if (!searchTerm.value) return owners.value

  const search = searchTerm.value.toLowerCase()
  return owners.value.filter(owner =>
    owner.nombre_completo.toLowerCase().includes(search) ||
    owner.ci?.toLowerCase().includes(search) ||
    owner.propiedades?.some(prop => prop.codigo.toLowerCase().includes(search))
  )
})

const loadOwnersWithExpenses = async () => {
  if (!props.activePeriod) {
    loading.value = false
    return
  }

  try {
    const response = await fetch(`/api/pagos/propietarios-con-expensas/${props.activePeriod.id}`)
    const result = await response.json()

    if (result.success) {
      owners.value = result.owners
    } else {
      console.error('Error cargando propietarios:', result.message)
      owners.value = []
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
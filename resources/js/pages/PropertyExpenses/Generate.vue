<template>
  <AppLayout :title="'Generar Expensas'">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <div class="bg-white shadow-sm rounded-lg">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h1 class="text-2xl font-semibold text-gray-900">Generar Expensas Mensuales</h1>
          <p class="mt-1 text-sm text-gray-600">
            Genera expensas automáticamente para el período activo
          </p>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mx-6 mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex">
            <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div class="text-sm text-red-700">{{ error }}</div>
          </div>
        </div>

        <!-- Formulario -->
        <div v-else class="px-6 py-6 space-y-6">
          <!-- Información del Período Activo -->
          <div v-if="period" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-900 mb-2">Período Activo</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <span class="text-blue-600">Período:</span>
                <span class="ml-2 font-medium text-blue-900">{{ period.name }}</span>
              </div>
              <div>
                <span class="text-blue-600">Estado:</span>
                <span class="ml-2 font-medium" :class="getStatusClass(period.status)">
                  {{ getStatusText(period.status) }}
                </span>
              </div>
              <div>
                <span class="text-blue-600">Propiedades:</span>
                <span class="ml-2 font-medium text-blue-900">{{ period.properties_count }}</span>
              </div>
              <div>
                <span class="text-blue-600">Total Generado:</span>
                <span class="ml-2 font-medium text-blue-900">Bs {{ formatCurrency(period.total_generated) }}</span>
              </div>
            </div>
          </div>

          <!-- Factores de Cálculo -->
          <div v-if="period" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Factor Departamento -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Factor Departamento
              </label>
              <div class="relative rounded-md shadow-sm">
                <input
                  v-model="form.factor_departamento"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500 pl-3"
                  :disabled="isGenerating"
                  placeholder="2.1 (por defecto)"
                />
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Factor para departamentos, parqueos y bauleras
              </p>
              <p v-if="form.errors.factor_departamento" class="mt-1 text-sm text-red-600">
                {{ form.errors.factor_departamento }}
              </p>
            </div>

            <!-- Factor Comercial -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Factor Comercial
              </label>
              <div class="relative rounded-md shadow-sm">
                <input
                  v-model="form.factor_comercial"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500 pl-3"
                  :disabled="isGenerating"
                  placeholder="3.5 (por defecto)"
                />
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Factor para locales comerciales y oficinas
              </p>
              <p v-if="form.errors.factor_comercial" class="mt-1 text-sm text-red-600">
                {{ form.errors.factor_comercial }}
              </p>
            </div>
          </div>

          <!-- Información de Factores de Agua -->
          <div v-if="period" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-900 mb-2">Factores de Agua del Período</h3>
            <p class="text-sm text-blue-700">
              Los factores de agua se calculan automáticamente desde las facturas de los medidores principales del edificio.
            </p>
            <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
              <div class="flex justify-between">
                <span class="text-blue-600">Factor Comercial:</span>
                <span class="font-medium text-blue-900">Se calculará automáticamente</span>
              </div>
              <div class="flex justify-between">
                <span class="text-blue-600">Factor Domiciliario:</span>
                <span class="font-medium text-blue-900">Se calculará automáticamente</span>
              </div>
            </div>
          </div>

    
          <!-- Botón de Generación -->
          <div class="flex justify-end">
            <button
              @click="generateExpenses"
              :disabled="!canGenerate || isGenerating"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg
                v-if="isGenerating"
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
              {{ isGenerating ? 'Generando Expensas...' : 'Generar Expensas para Todas las Propiedades' }}
            </button>
          </div>

          <!-- Progress Bar -->
          <div v-if="isGenerating" class="bg-blue-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-blue-900">Progreso</span>
              <span class="text-sm text-blue-700">{{ progress.current }} de {{ progress.total }}</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2">
              <div
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: progressPercentage + '%' }"
              ></div>
            </div>
            <p class="mt-2 text-sm text-blue-700">{{ progress.message }}</p>
          </div>

          <!-- Resultados -->
          <div v-if="results" class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-green-900 mb-3">✅ Generación Completada</h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <div class="text-center">
                <div class="text-2xl font-bold text-green-900">{{ results.generated_expenses }}</div>
                <div class="text-sm text-green-700">Expensas Generadas</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ results.skipped_properties }}</div>
                <div class="text-sm text-yellow-700">Propiedades Omitidas</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-900">Bs {{ formatCurrency(results.total_amount) }}</div>
                <div class="text-sm text-green-700">Monto Total</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">{{ results.errors.length }}</div>
                <div class="text-sm text-gray-700">Errores</div>
              </div>
            </div>

            <!-- Lista de propiedades generadas -->
            <div v-if="results.details.length > 0" class="mt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">Últimas Expensas Generadas</h4>
              <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Propiedad</th>
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Propietario</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Base</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Agua</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="detail in results.details.slice(0, 10)" :key="detail.property_code">
                      <td class="px-3 py-2 text-sm text-gray-900">{{ detail.property_code }}</td>
                      <td class="px-3 py-2 text-sm text-gray-500">{{ detail.owner }}</td>
                      <td class="px-3 py-2 text-sm text-right text-gray-900">Bs {{ formatCurrency(detail.base_amount) }}</td>
                      <td class="px-3 py-2 text-sm text-right text-gray-900">Bs {{ formatCurrency(detail.water_amount) }}</td>
                      <td class="px-3 py-2 text-sm text-right font-medium text-gray-900">Bs {{ formatCurrency(detail.total_amount) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-if="results.details.length > 10" class="mt-2 text-sm text-gray-500">
                ... y {{ results.details.length - 10 }} propiedades más
              </p>
            </div>

            <!-- Errores -->
            <div v-if="results.errors.length > 0" class="mt-4">
              <h4 class="text-sm font-medium text-red-900 mb-2">⚠️ Errores encontrados</h4>
              <div class="bg-red-50 border border-red-200 rounded-md p-3">
                <ul class="text-sm text-red-700 space-y-1">
                  <li v-for="(error, index) in results.errors.slice(0, 5)" :key="index">
                    • {{ error }}
                  </li>
                </ul>
                <p v-if="results.errors.length > 5" class="mt-2 text-sm text-red-600">
                  ... y {{ results.errors.length - 5 }} errores más
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useNotification } from '@/composables/useNotification'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  period: {
    type: Object,
    default: null
  },
  error: {
    type: String,
    default: null
  }
})

const { showSuccess, showError, showWarning, showInfo } = useNotification()

// Estado
const isGenerating = ref(false)
const results = ref(null)
const progress = ref({
  current: 0,
  total: 0,
  message: ''
})

// Formulario
const form = useForm({
  period_id: props.period?.id || '',
  factor_departamento: 2.1,
  factor_comercial: 3.5
})

// Computed
const canGenerate = computed(() => {
  return props.period &&
         form.factor_departamento > 0 &&
         form.factor_comercial > 0 &&
         props.period.can_generate
})

const progressPercentage = computed(() => {
  if (progress.value.total === 0) return 0
  return Math.round((progress.value.current / progress.value.total) * 100)
})

// Métodos
const generateExpenses = async () => {
  if (!canGenerate.value) return

  isGenerating.value = true
  results.value = null
  progress.value = {
    current: 0,
    total: 0,
    message: 'Iniciando generación de expensas...'
  }

  try {
    // Preparar los datos manualmente
    const requestData = {
      period_id: props.period.id,
      factor_departamento: form.factor_departamento,
      factor_comercial: form.factor_comercial
    }

    progress.value.message = 'Enviando solicitud...'

    // Usar fetch directamente para evitar problemas con Inertia
    const response = await fetch('/property-expenses/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(requestData)
    })

    const data = await response.json()

    // Simular progreso mientras se procesa
    simulateProgress()

    if (data.status === 'success' || data.success) {
      const result = data.results || data

      // Actualizar resultados locales
      results.value = result
      progress.value.message = '✅ Generación completada exitosamente'

      // Mostrar notificación de éxito principal
      showSuccess(
        'Expensas generadas exitosamente',
        `${result.generated_expenses} expensas generadas por Bs ${formatCurrency(result.total_amount)}`
      )

      // Mostrar advertencias si hay propiedades omitidas
      if (result.skipped_properties > 0) {
        setTimeout(() => {
          showWarning(
            'Propiedades omitidas',
            `${result.skipped_properties} propiedades fueron omitidas`
          )
        }, 500)
      }

      // Mostrar errores si los hay
      if (result.errors.length > 0) {
        setTimeout(() => {
          showError(
            'Errores encontrados',
            `Se encontraron ${result.errors.length} errores durante la generación`
          )
        }, 1000)
      }

      // Si no se generaron expensas pero hay propiedades omitidas
      if (result.generated_expenses === 0 && result.skipped_properties > 0) {
        setTimeout(() => {
          showInfo(
            'Información',
            'Todas las propiedades ya tienen expensas generadas para este período'
          )
        }, 1500)
      }

    } else {
      // Manejar errores del servidor
      progress.value.message = '❌ Error en la generación'
      const errorMessage = data.message || 'Error al generar las expensas'
      showError('Error', errorMessage)
    }

  } catch (error) {
    console.error('Error generando expensas:', error)
    progress.value.message = '❌ Error inesperado'

    // Determinar tipo de error
    if (error.message?.includes('Failed to fetch')) {
      showError('Error de conexión', 'No se pudo conectar al servidor. Verifica tu conexión a internet.')
    } else if (error.message?.includes('JSON')) {
      showError('Error de respuesta', 'El servidor devolvió una respuesta inesperada.')
    } else {
      showError('Error inesperado', 'Ocurrió un error inesperado. Por favor, intenta nuevamente.')
    }

  } finally {
    isGenerating.value = false
  }
}

const simulateProgress = () => {
  const totalSteps = props.period?.properties_count || 50
  progress.value.total = totalSteps

  const interval = setInterval(() => {
    if (progress.value.current < progress.value.total && isGenerating.value) {
      progress.value.current += 1
      progress.value.message = `Generando expensa para propiedad ${progress.value.current} de ${progress.value.total}...`
    } else {
      clearInterval(interval)
    }
  }, 100)
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
      return 'text-green-600'
    case 'closed':
      return 'text-red-600'
    case 'cancelled':
      return 'text-gray-600'
    default:
      return 'text-gray-600'
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

// Watcher para asegurar que el period_id se mantenga actualizado
watch(() => props.period?.id, (newId) => {
  if (newId) {
    form.period_id = newId
  }
}, { immediate: true })
</script>
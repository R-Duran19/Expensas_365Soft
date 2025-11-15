<template>
  <AppLayout :title="'Expensas - Selección de Período'">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h1 class="text-2xl font-semibold text-gray-900">Gestión de Expensas</h1>
          <p class="mt-1 text-sm text-gray-600">
            Selecciona un período para ver y gestionar las expensas generadas
          </p>
        </div>
      </div>

      <!-- Lista de Períodos -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="period in periods"
          :key="period.id"
          class="bg-white shadow-sm rounded-lg hover:shadow-md transition-shadow cursor-pointer border"
          :class="{
            'border-gray-200 hover:border-blue-300': period.can_generate,
            'border-gray-200 opacity-75': !period.can_generate
          }"
          @click="selectPeriod(period)"
        >
          <!-- Card Header -->
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-900">{{ period.name }}</h3>
              <span
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                :class="getStatusClass(period.status)"
              >
                {{ getStatusText(period.status) }}
              </span>
            </div>
          </div>

          <!-- Card Content -->
          <div class="px-6 py-4">
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Expensas Generadas</span>
                <span class="text-sm font-medium text-gray-900">{{ period.properties_count }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Monto Total</span>
                <span class="text-sm font-medium text-gray-900">Bs {{ formatCurrency(period.total_generated) }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Propiedades</span>
                <span class="text-sm font-medium text-gray-900">{{ period.properties_count }}</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 space-y-2">
              <button
                @click.stop="viewExpenses(period)"
                class="w-full inline-flex justify-center items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Ver Expensas
              </button>

              <button
                v-if="period.can_generate"
                @click.stop="generateExpenses(period)"
                class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Generar Expensas
              </button>

              <button
                v-else
                disabled
                class="w-full inline-flex justify-center items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-400 bg-gray-50 cursor-not-allowed"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Período Cerrado
              </button>
            </div>
          </div>
        </div>

        <!-- Card para crear nuevo período -->
        <Link
          href="/expense-periods"
          class="bg-white shadow-sm rounded-lg hover:shadow-md transition-shadow cursor-pointer border border-dashed border-gray-300 flex flex-col items-center justify-center p-6 hover:border-blue-400 group"
        >
          <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 group-hover:bg-blue-100 mb-4">
              <svg class="h-6 w-6 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-900">Crear Nuevo Período</h3>
            <p class="mt-1 text-sm text-gray-500 group-hover:text-blue-600">Generar un nuevo período de facturación</p>
          </div>
        </Link>
      </div>

      <!-- Acción Principal: Generar Expensas -->
      <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="px-6 py-4 border-b border-blue-200">
          <h2 class="text-lg font-medium text-blue-900">Generación de Expensas</h2>
          <p class="mt-1 text-sm text-blue-700">
            Genera expensas consolidadas para todos los propietarios del período
          </p>
        </div>
        <div class="px-6 py-4">
          <Link
            href="/property-expenses/create"
            class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Generar Expensas Masivas
          </Link>
        </div>
      </div>

      <!-- Acciones Generales -->
      <div class="mt-6 bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Otras Acciones</h2>
        </div>
        <div class="px-6 py-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button
              @click="createManualExpense"
              class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Crear Expensa Manual
            </button>

            <button
              @click="manageFactorWater"
              class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Gestionar Factores
            </button>

            <Link
              href="/lecturas"
              class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Gestionar Lecturas
            </Link>
          </div>
        </div>
      </div>

      <!-- Información Adicional -->
      <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Información importante</h3>
            <div class="mt-2 text-sm text-blue-700">
              <ul class="list-disc list-inside space-y-1">
                <li>Las expensas se generan consolidadas por propietario, incluyendo todas sus propiedades</li>
                <li>Para generar expensas con consumo de agua, primero registra las facturas de los medidores principales del edificio</li>
                <li>Los factores de cálculo se obtienen automáticamente de la tabla factores_calculo según el tipo de propiedad</li>
                <li>Las expensas pendientes pueden ser editadas o eliminadas antes de ser pagadas</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  periods: {
    type: Array,
    required: true
  }
})

// Métodos
const selectPeriod = (period) => {
  router.get(`/property-expenses?period_id=${period.id}`)
}

const viewExpenses = (period) => {
  router.get(`/property-expenses?period_id=${period.id}`)
}

const generateExpenses = (period) => {
  router.get(`/property-expenses/create?period_id=${period.id}`)
}

const createManualExpense = () => {
  // Implementar creación manual de expensa
  console.log('Crear expensa manual')
}

const manageFactorWater = () => {
  // Implementar gestión de factores
  console.log('Gestionar factores')
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
</script>
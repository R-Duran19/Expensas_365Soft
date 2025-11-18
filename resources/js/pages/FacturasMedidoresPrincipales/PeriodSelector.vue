<template>
  <AppLayout :title="'Facturas de Medidores Principales'">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h1 class="text-2xl font-semibold text-gray-900">Facturas de Medidores Principales</h1>
          <p class="mt-1 text-sm text-gray-600">
            Registra las facturas del edificio para calcular los factores de consumo de agua
          </p>
        </div>
      </div>

      <!-- Información importante -->
      <!-- <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">¿Qué son las facturas de medidores principales?</h3>
            <div class="mt-2 text-sm text-blue-700">
              <p>Son las facturas que recibe el edificio del servicio de agua. Estas facturas se usan para calcular el factor
              de costo por metro cúbico que se aplica a cada propiedad.</p>
              <ul class="mt-2 list-disc list-inside space-y-1">
                <li><strong>Medidor Comercial:</strong> Generalmente 1 solo medidor para locales/oficinas</li>
                <li><strong>Medidores Domiciliarios:</strong> Pueden ser 2 o más medidores para departamentos/casas</li>
                <li><strong>Cálculo de factores:</strong> Importe total ÷ Consumo total en m³</li>
              </ul>
            </div>
          </div>
        </div>
      </div> -->
            <!-- Períodos disponibles para registrar -->
      <div v-if="periodos_disponibles.length > 0">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Períodos Disponibles para Registrar</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="periodo in periodos_disponibles"
            :key="periodo.mes_periodo"
            class="bg-white shadow-sm rounded-lg border border-dashed border-gray-300 hover:border-blue-400 transition-colors cursor-pointer"
            @click="registrarFacturasPeriodo(periodo.mes_periodo)"
          >
            <div class="px-6 py-8 text-center">
              <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-1">{{ periodo.periodo_formateado }}</h3>
              <p class="text-sm text-gray-500">Registrar facturas de este período</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Períodos con facturas registradas -->
      <div v-if="periodos_con_facturas.length > 0" class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Períodos con Facturas Registradas</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="periodo in periodos_con_facturas"
            :key="periodo.mes_periodo"
            class="bg-white shadow-sm rounded-lg border border-gray-200 hover:shadow-md transition-shadow cursor-pointer"
            @click="verFacturasPeriodo(periodo.mes_periodo)"
          >
            <div class="px-6 py-4 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">{{ periodo.periodo_formateado }}</h3>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Registrado
                </span>
              </div>
            </div>
            <div class="px-6 py-4">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-500">Total Facturas:</span>
                  <span class="ml-2 font-medium text-gray-900">{{ periodo.resumen.total_facturas }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Comerciales:</span>
                  <span class="ml-2 font-medium text-gray-900">{{ periodo.resumen.comerciales }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Domiciliarios:</span>
                  <span class="ml-2 font-medium text-gray-900">{{ periodo.resumen.domiciliarias }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Factor Comercial:</span>
                  <span class="ml-2 font-medium text-blue-600">Bs {{ periodo.resumen.factor_comercial.toFixed(4) }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Importe Total:</span>
                  <span class="ml-2 font-medium text-gray-900">Bs {{ formatCurrency(periodo.resumen.importe_total_comercial + periodo.resumen.importe_total_domiciliario) }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Factor Domiciliario:</span>
                  <span class="ml-2 font-medium text-blue-600">Bs {{ periodo.resumen.factor_domiciliario.toFixed(4) }}</span>
                </div>
              </div>

              <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="grid grid-cols-2 gap-4 text-xs text-gray-600">
                  <div>
                    Consumo Comercial: <span class="font-medium">{{ periodo.resumen.consumo_total_comercial }} m³</span>
                  </div>
                  <div>
                    Consumo Domiciliario: <span class="font-medium">{{ periodo.resumen.consumo_total_domiciliario }} m³</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>



      <!-- No hay períodos disponibles -->
      <div v-if="periodos_con_facturas.length === 0 && periodos_disponibles.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay períodos disponibles</h3>
        <p class="mt-1 text-sm text-gray-500">
          No hay períodos de facturación disponibles para registrar facturas.
        </p>
        <div class="mt-6">
          <Link
            href="/expense-periods"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Crear Nuevo Período
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  periodos_con_facturas: {
    type: Array,
    required: true
  },
  periodos_disponibles: {
    type: Array,
    required: true
  }
})

// Métodos
const verFacturasPeriodo = (mesPeriodo) => {
  router.get(`/facturas-medidores-principales?mes_periodo=${mesPeriodo}`)
}

const registrarFacturasPeriodo = (mesPeriodo) => {
  router.get(`/facturas-medidores-principales/create?mes_periodo=${mesPeriodo}`)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}
</script>
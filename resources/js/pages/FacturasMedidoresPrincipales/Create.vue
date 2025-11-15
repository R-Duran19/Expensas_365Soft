<template>
  <AppLayout :title="'Registrar Facturas - ' + periodo_formateado">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-semibold text-gray-900">
                Registrar Facturas - {{ periodo_formateado }}
              </h1>
              <p class="mt-1 text-sm text-gray-600">
                Registra las facturas de los medidores principales del edificio
              </p>
            </div>
            <Link
              :href="`/facturas-medidores-principales`"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              Volver
            </Link>
          </div>
        </div>
      </div>

      <!-- Facturas Existentes -->
      <div v-if="facturas_existentes.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">Facturas ya registradas</h3>
            <div class="mt-2 text-sm text-yellow-700">
              <p>Este período ya tiene {{ facturas_existentes.length }} factura(s) registrada(s). Puedes actualizarlas o agregar nuevas.</p>
              <div class="mt-2 space-y-1">
                <div v-for="factura in facturas_existentes" :key="factura.id" class="flex items-center justify-between bg-yellow-100 rounded p-2">
                  <div>
                    <span class="font-medium">{{ factura.numero_medidor }}</span>
                    <span class="ml-2 text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded">{{ factura.tipo_formateado }}</span>
                    <span class="ml-2">Bs {{ formatCurrency(factura.importe_bs) }}</span>
                    <span class="ml-2 text-gray-600">{{ factura.consumo_m3 }} m³</span>
                  </div>
                  <div class="text-xs text-gray-600">
                    Factor: Bs {{ factura.factor_calculado?.toFixed(4) || 'N/A' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="submitForm">
        <div class="bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Datos de las Facturas</h2>
          </div>
          <div class="px-6 py-4 space-y-6">
            <!-- Facturas Dinámicas -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">Facturas de Medidores</label>
                <button
                  type="button"
                  @click="agregarFactura"
                  class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Agregar Factura
                </button>
              </div>

              <div v-for="(factura, index) in form.facturas" :key="index" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-sm font-medium text-gray-900">Factura #{{ index + 1 }}</h3>
                  <button
                    v-if="form.facturas.length > 1"
                    type="button"
                    @click="eliminarFactura(index)"
                    class="text-red-600 hover:text-red-800 text-sm"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                  <!-- Número de Medidor -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Número de Medidor *
                    </label>
                    <input
                      v-model="factura.numero_medidor"
                      type="text"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="Ej: MED-001"
                      required
                    />
                  </div>

                  <!-- Tipo -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Tipo *
                    </label>
                    <select
                      v-model="factura.tipo"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    >
                      <option value="">Seleccionar tipo</option>
                      <option value="comercial">Comercial</option>
                      <option value="domiciliario">Domiciliario</option>
                    </select>
                  </div>

                  <!-- Importe -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Importe (Bs) *
                    </label>
                    <div class="relative rounded-md shadow-sm">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">Bs</span>
                      </div>
                      <input
                        v-model.number="factura.importe_bs"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-full pl-8 pr-12 border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500"
                        placeholder="0.00"
                        required
                      />
                    </div>
                  </div>

                  <!-- Consumo -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Consumo (m³) *
                    </label>
                    <div class="relative rounded-md shadow-sm">
                      <input
                        v-model.number="factura.consumo_m3"
                        type="number"
                        step="0.001"
                        min="0"
                        class="w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500"
                        placeholder="0.000"
                        required
                      />
                      <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">m³</span>
                      </div>
                    </div>
                  </div>

                  <!-- Fecha Emisión -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Fecha de Emisión
                    </label>
                    <input
                      v-model="factura.fecha_emision"
                      type="date"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>

                  <!-- Fecha Vencimiento -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Fecha de Vencimiento
                    </label>
                    <input
                      v-model="factura.fecha_vencimiento"
                      type="date"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>

                  <!-- Observaciones -->
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Observaciones
                    </label>
                    <textarea
                      v-model="factura.observaciones"
                      rows="1"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="Notas adicionales sobre la factura..."
                    ></textarea>
                  </div>
                </div>

                <!-- Factor calculado (solo lectura) -->
                <div v-if="factura.importe_bs && factura.consumo_m3 && factura.consumo_m3 > 0" class="mt-3 p-3 bg-blue-50 rounded-md">
                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-blue-800">Factor Calculado:</span>
                    <span class="text-sm font-bold text-blue-900">
                      Bs {{ ((factura.importe_bs / factura.consumo_m3).toFixed(4)) }} / m³
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Resumen -->
        <div class="bg-white shadow-sm rounded-lg mt-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Resumen del Período</h2>
          </div>
          <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ resumen.total_facturas }}</div>
                <div class="text-sm text-gray-500">Total Facturas</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">Bs {{ formatCurrency(resumen.importe_total_comercial + resumen.importe_total_domiciliario) }}</div>
                <div class="text-sm text-gray-500">Importe Total</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ resumen.consumo_total_comercial + resumen.consumo_total_domiciliario }} m³</div>
                <div class="text-sm text-gray-500">Consumo Total</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ resumen.factor_domiciliario > 0 ? resumen.factor_domiciliario.toFixed(4) : 'N/A' }}</div>
                <div class="text-sm text-gray-500">Factor Domiciliario</div>
              </div>
            </div>

            <!-- Factores Actuales del Período -->
            <div v-if="periodo_facturacion && (periodo_facturacion.factor_comercial || periodo_facturacion.factor_domiciliario)" class="mt-4 pt-4 border-t border-gray-200">
              <h3 class="text-sm font-medium text-gray-900 mb-2">Factores Actuales del Período:</h3>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-500">Factor Comercial:</span>
                  <span class="font-medium text-blue-600">
                    Bs {{ periodo_facturacion.factor_comercial?.toFixed(4) || 'No registrado' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Factor Domiciliario:</span>
                  <span class="font-medium text-blue-600">
                    Bs {{ periodo_facturacion.factor_domiciliario?.toFixed(4) || 'No registrado' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones -->
        <div class="mt-6 flex justify-end space-x-3">
          <Link
            :href="`/facturas-medidores-principales`"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg
              v-if="isSubmitting"
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline"
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
            {{ isSubmitting ? 'Guardando...' : 'Guardar Facturas' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  mes_periodo: {
    type: String,
    required: true
  },
  periodo_formateado: {
    type: String,
    required: true
  },
  facturas_existentes: {
    type: Array,
    default: () => []
  },
  periodo_facturacion: {
    type: Object,
    default: null
  }
})

const isSubmitting = ref(false)

// Formulario
const form = ref({
  mes_periodo: props.mes_periodo,
  facturas: [
    {
      numero_medidor: '',
      tipo: '',
      importe_bs: null,
      consumo_m3: null,
      fecha_emision: '',
      fecha_vencimiento: '',
      observaciones: ''
    }
  ]
})

// Resumen calculado
const resumen = computed(() => {
  const facturas = form.value.facturas.filter(f => f.importe_bs && f.consumo_m3)

  const resumen = {
    total_facturas: facturas.length,
    comerciales: facturas.filter(f => f.tipo === 'comercial').length,
    domiciliarias: facturas.filter(f => f.tipo === 'domiciliario').length,
    importe_total_comercial: facturas.filter(f => f.tipo === 'comercial').reduce((sum, f) => sum + f.importe_bs, 0),
    importe_total_domiciliario: facturas.filter(f => f.tipo === 'domiciliario').reduce((sum, f) => sum + f.importe_bs, 0),
    consumo_total_comercial: facturas.filter(f => f.tipo === 'comercial').reduce((sum, f) => sum + f.consumo_m3, 0),
    consumo_total_domiciliario: facturas.filter(f => f.tipo === 'domiciliario').reduce((sum, f) => sum + f.consumo_m3, 0),
  }

  // Calcular factores
  resumen.factor_comercial = resumen.consumo_total_comercial > 0
    ? resumen.importe_total_comercial / resumen.consumo_total_comercial
    : 0

  resumen.factor_domiciliario = resumen.consumo_total_domiciliario > 0
    ? resumen.importe_total_domiciliario / resumen.consumo_total_domiciliario
    : 0

  return resumen
})

// Métodos
const agregarFactura = () => {
  form.value.facturas.push({
    numero_medidor: '',
    tipo: '',
    importe_bs: null,
    consumo_m3: null,
    fecha_emision: '',
    fecha_vencimiento: '',
    observaciones: ''
  })
}

const eliminarFactura = (index) => {
  form.value.facturas.splice(index, 1)
}

const submitForm = async () => {
  isSubmitting.value = true

  try {
    const response = await router.post('/facturas-medidores-principales', form.value, {
      onSuccess: (page) => {
        // Redirigir a la vista del período
        router.get(`/facturas-medidores-principales?mes_periodo=${props.mes_periodo}`)
      },
      onError: (errors) => {
        console.error('Errores de validación:', errors)
      },
      onFinish: () => {
        isSubmitting.value = false
      }
    })
  } catch (error) {
    console.error('Error al guardar:', error)
    isSubmitting.value = false
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}
</script>
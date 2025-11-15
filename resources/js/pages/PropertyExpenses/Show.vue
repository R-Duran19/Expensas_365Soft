<template>
  <AppLayout :title="'Detalle de Expensa'">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <Link
                :href="`/property-expenses?period_id=${expense.id}`"
                class="text-blue-600 hover:text-blue-800 flex items-center"
              >
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Volver
              </Link>
              <h1 class="text-2xl font-semibold text-gray-900">
                Expensa - {{ expense.propiedad.codigo }}
              </h1>
            </div>
            <div class="flex items-center space-x-3">
              <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                :class="getExpenseStatusClass(expense.status)"
              >
                {{ getExpenseStatusText(expense.status) }}
              </span>
              <button
                v-if="expense.status !== 'paid'"
                @click="editExpense"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información Principal -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Detalles de Propiedad -->
          <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Información de la Propiedad</h2>
            </div>
            <div class="px-6 py-4">
              <dl class="grid grid-cols-2 gap-4">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Código</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.propiedad.codigo }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Ubicación</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.propiedad.ubicacion }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Tipo de Propiedad</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.propiedad.tipo_propiedad }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Metros Cuadrados</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.propiedad.metros_cuadrados }} m²</dd>
                </div>
              </dl>
            </div>
          </div>

          <!-- Desglose de Montos -->
          <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Desglose de Montos</h2>
            </div>
            <div class="px-6 py-4">
              <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                  <span class="text-sm font-medium text-gray-700">Expensa Base (m² × factor)</span>
                  <span class="text-sm font-semibold text-gray-900">Bs {{ formatCurrency(expense.desglose.base_amount) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                  <span class="text-sm font-medium text-gray-700">Consumo de Agua</span>
                  <span class="text-sm font-semibold text-gray-900">Bs {{ formatCurrency(expense.desglose.water_amount) }}</span>
                </div>
                <div v-if="expense.desglose.other_amount > 0" class="flex justify-between items-center py-2 border-b border-gray-200">
                  <span class="text-sm font-medium text-gray-700">Otros Conceptos</span>
                  <span class="text-sm font-semibold text-gray-900">Bs {{ formatCurrency(expense.desglose.other_amount) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                  <span class="text-sm font-medium text-gray-700">Deuda Anterior</span>
                  <span class="text-sm font-semibold text-gray-900">Bs {{ formatCurrency(expense.desglose.previous_debt) }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                  <span class="text-base font-bold text-gray-900">Total a Pagar</span>
                  <span class="text-base font-bold text-gray-900">Bs {{ formatCurrency(expense.desglose.total_amount) }}</span>
                </div>
                <div v-if="expense.desglose.paid_amount > 0" class="flex justify-between items-center py-2 bg-green-50 px-4 rounded-md">
                  <span class="text-sm font-medium text-green-700">Pagado</span>
                  <span class="text-sm font-semibold text-green-700">Bs {{ formatCurrency(expense.desglose.paid_amount) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 bg-blue-50 px-4 rounded-md">
                  <span class="text-sm font-medium text-blue-700">Saldo Pendiente</span>
                  <span class="text-sm font-semibold text-blue-700">Bs {{ formatCurrency(expense.desglose.balance) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Desglose Detallado por Propiedad -->
          <div v-if="expense.property_details && expense.property_details.length > 0" class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Desglose Detallado por Propiedad</h2>
              <p class="mt-1 text-sm text-gray-600">
                Detalle individual de cada propiedad incluida en esta expensa consolidada
              </p>
            </div>
            <div class="px-6 py-4">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Concepto
                      </th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        N° / Código
                      </th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        m²
                      </th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Factor Expensas
                      </th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Factor Agua
                      </th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Consumo m³
                      </th>
                      <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Expensa Base
                      </th>
                      <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Agua
                      </th>
                      <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total Prop.
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="detail in expense.property_details" :key="detail.id" class="hover:bg-gray-50">
                      <td class="px-3 py-4 text-sm text-gray-900">
                        <div>
                          <div class="font-medium text-gray-900">{{ detail.propiedad.tipo_propiedad }}</div>
                          <div class="text-gray-500 text-xs">{{ detail.propiedad.ubicacion }}</div>
                        </div>
                      </td>
                      <td class="px-3 py-4 text-sm text-gray-900">
                        <div class="font-mono">{{ detail.propiedad.codigo }}</div>
                        <div v-if="detail.agua.medidor_codigo" class="text-xs text-gray-500">
                          Medidor: {{ detail.agua.medidor_codigo }}
                        </div>
                      </td>
                      <td class="px-3 py-4 text-sm text-gray-900 text-right">
                        {{ detail.propiedad.metros_cuadrados }}
                      </td>
                      <td class="px-3 py-4 text-sm text-gray-900 text-right">
                        {{ detail.factores.factor_expensas }}
                      </td>
                      <td class="px-3 py-4 text-sm text-gray-900 text-right">
                        <span v-if="detail.agua.has_water_meter" class="text-blue-600">
                          {{ detail.factores.factor_agua }}
                        </span>
                        <span v-else class="text-gray-400">N/A</span>
                      </td>
                      <td class="px-3 py-4 text-sm text-gray-900 text-right">
                        <span v-if="detail.agua.has_water_meter" class="font-medium">
                          {{ detail.agua.consumption_m3 }}
                        </span>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                      <td class="px-3 py-4 text-sm text-gray-900 text-right font-medium">
                        Bs {{ formatCurrency(detail.montos.base_amount) }}
                      </td>
                      <td class="px-3 py-4 text-sm text-gray-900 text-right font-medium">
                        <span v-if="detail.agua.has_water_meter" class="text-blue-600">
                          Bs {{ formatCurrency(detail.montos.water_amount) }}
                        </span>
                        <span v-else class="text-gray-400">Bs 0.00</span>
                      </td>
                      <td class="px-3 py-4 text-sm text-gray-900 text-right font-bold">
                        Bs {{ formatCurrency(detail.montos.total_amount) }}
                      </td>
                    </tr>
                  </tbody>
                  <tfoot class="bg-gray-50">
                    <tr>
                      <td colspan="6" class="px-3 py-3 text-sm font-medium text-gray-900 text-right">
                        TOTALES:
                      </td>
                      <td class="px-3 py-3 text-sm font-bold text-gray-900 text-right">
                        Bs {{ formatCurrency(expense.property_details.reduce((sum, d) => sum + d.montos.base_amount, 0)) }}
                      </td>
                      <td class="px-3 py-3 text-sm font-bold text-blue-600 text-right">
                        Bs {{ formatCurrency(expense.property_details.reduce((sum, d) => sum + d.montos.water_amount, 0)) }}
                      </td>
                      <td class="px-3 py-3 text-sm font-bold text-gray-900 text-right">
                        Bs {{ formatCurrency(expense.property_details.reduce((sum, d) => sum + d.montos.total_amount, 0)) }}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <!-- Resumen de información adicional -->
              <div v-if="expense.property_details.some(d => d.agua.has_water_meter)" class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Información de Medidores</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div v-for="detail in expense.property_details.filter(d => d.agua.has_water_meter)" :key="'medidor-' + detail.id" class="text-xs text-blue-700">
                    <strong>{{ detail.propiedad.codigo }}:</strong>
                    {{ detail.agua.readings_summary }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Información de Agua -->
          <div v-if="expense.agua.consumption > 0" class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Consumo de Agua</h2>
            </div>
            <div class="px-6 py-4">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Lectura Anterior</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.agua.previous_reading }} m³</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Lectura Actual</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.agua.current_reading }} m³</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Consumo del Mes</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.agua.consumption }} m³</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Factor Agua</dt>
                  <dd class="mt-1 text-sm text-gray-900">Bs {{ formatCurrency(expense.agua.factor) }} / m³</dd>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagos Realizados -->
          <div v-if="expense.payment_allocations.length > 0" class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Pagos Realizados</h2>
            </div>
            <div class="px-6 py-4">
              <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo de Pago</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="allocation in expense.payment_allocations" :key="allocation.id">
                      <td class="px-3 py-2 text-sm text-gray-900">{{ allocation.payment.payment_date }}</td>
                      <td class="px-3 py-2 text-sm text-gray-900">{{ allocation.payment.payment_type }}</td>
                      <td class="px-3 py-2 text-sm text-right font-medium text-gray-900">
                        Bs {{ formatCurrency(allocation.amount) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Responsable del Pago -->
          <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Responsable del Pago</h2>
            </div>
            <div class="px-6 py-4">
              <div class="space-y-3">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Facturar a</dt>
                  <dd class="mt-1">
                    <span
                      class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                      :class="expense.facturar_a === 'inquilino' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                    >
                      {{ expense.facturar_a === 'inquilino' ? 'Inquilino' : 'Propietario' }}
                    </span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Propietario</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.propietario }}</dd>
                </div>
                <div v-if="expense.inquilino">
                  <dt class="text-sm font-medium text-gray-500">Inquilino</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.inquilino }}</dd>
                </div>
              </div>
            </div>
          </div>

          <!-- Información del Período -->
          <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Información del Período</h2>
            </div>
            <div class="px-6 py-4">
              <div class="space-y-3">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Período</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.period }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Fecha de Vencimiento</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.due_date || 'No definida' }}</dd>
                </div>
                <div v-if="expense.paid_at">
                  <dt class="text-sm font-medium text-gray-500">Fecha de Pago</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.paid_at }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ expense.created_at }}</dd>
                </div>
              </div>
            </div>
          </div>

          <!-- Historial de Lecturas -->
          <div v-if="expense.water_readings.length > 0" class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Historial de Lecturas</h2>
            </div>
            <div class="px-6 py-4">
              <div class="space-y-3">
                <div v-for="reading in expense.water_readings" :key="reading.fecha" class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ reading.fecha }}</div>
                    <div class="text-xs text-gray-500">{{ reading.periodo }}</div>
                  </div>
                  <div class="text-right">
                    <div class="text-sm font-medium text-gray-900">{{ reading.lectura }} m³</div>
                    <div class="text-xs text-gray-500">{{ reading.consumo }} m³ consumidos</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notas -->
          <div v-if="expense.notes" class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Notas</h2>
            </div>
            <div class="px-6 py-4">
              <p class="text-sm text-gray-700">{{ expense.notes }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  expense: {
    type: Object,
    required: true
  }
})

const editExpense = () => {
  // Implementar edición de expensa
  console.log('Editar expensa:', props.expense.id)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
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
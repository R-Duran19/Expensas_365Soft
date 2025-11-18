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
          <!-- <div class="bg-white shadow-sm rounded-lg">
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
          </div> -->

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
                      <td colspan="4" class="px-3 py-3 text-sm font-medium text-gray-900 text-right">
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
              <!-- <div v-if="expense.property_details.some(d => d.agua.has_water_meter)" class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Información de Medidores</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div v-for="detail in expense.property_details.filter(d => d.agua.has_water_meter)" :key="'medidor-' + detail.id" class="text-xs text-blue-700">
                    <strong>{{ detail.propiedad.codigo }}:</strong>
                    {{ detail.agua.readings_summary }}
                  </div>
                </div>
              </div> -->
            </div>
          </div>

          <!-- Detalle de Consumo de Agua -->
          <div v-if="expense.property_details.some(d => d.agua.has_water_meter)" class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <div>
                  <h2 class="text-lg font-medium text-gray-900">Detalle de Consumo de Agua</h2>
                  <p class="mt-1 text-sm text-gray-600">
                    Desglose detallado del consumo de agua por propiedad
                  </p>
                </div>
                <div v-if="expense.water_factors" class="text-right">
                  <div class="text-xs text-gray-500">Factores del Período:</div>
                  <div class="text-sm font-medium">
                    <span class="text-orange-600">Comercial: {{ expense.water_factors.factor_comercial }}</span>
                    <span class="mx-2 text-gray-400">|</span>
                    <span class="text-green-600">Domiciliario: {{ expense.water_factors.factor_domiciliario }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="px-6 py-4">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                  <thead class="bg-blue-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                        Propiedad
                      </th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                        Medidor
                      </th>
                      <th class="px-4 py-3 text-center text-xs font-medium text-blue-700 uppercase tracking-wider">
                        Lectura Anterior
                      </th>
                      <th class="px-4 py-3 text-center text-xs font-medium text-blue-700 uppercase tracking-wider">
                        Lectura Actual
                      </th>
                      <th class="px-4 py-3 text-center text-xs font-medium text-blue-700 uppercase tracking-wider">
                        Consumo m³
                      </th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                        Factor Aplicado
                      </th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                        Subtotal Agua
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="detail in expense.property_details.filter(d => d.agua.has_water_meter)" :key="'agua-' + detail.id" class="hover:bg-blue-50">
                      <td class="px-4 py-3 text-sm">
                        <div class="font-medium text-gray-900">{{ detail.propiedad.codigo }}</div>
                        <div class="text-xs text-gray-500">{{ detail.propiedad.tipo_propiedad }}</div>
                      </td>
                      <td class="px-4 py-3 text-sm">
                        <div class="font-mono text-gray-900">{{ detail.agua.medidor_codigo }}</div>
                        <div class="text-xs" :class="detail.agua.es_comercial ? 'text-orange-600' : 'text-green-600'">
                          {{ detail.agua.es_comercial ? 'Comercial' : 'Domiciliario' }}
                        </div>
                      </td>
                      <td class="px-4 py-3 text-sm text-center text-gray-900">
                        {{ detail.agua.previous_reading || '-' }}
                      </td>
                      <td class="px-4 py-3 text-sm text-center text-gray-900">
                        {{ detail.agua.current_reading }}
                      </td>
                      <td class="px-4 py-3 text-sm text-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          {{ detail.agua.consumption_m3 }} m³
                        </span>
                      </td>
                      <td class="px-4 py-3 text-sm text-right font-mono">
                        {{ getFactorForProperty(detail) }}
                      </td>
                      <td class="px-4 py-3 text-sm text-right font-bold text-blue-600">
                        Bs {{ formatCurrency(detail.montos.water_amount) }}
                      </td>
                    </tr>
                  </tbody>
                  <tfoot class="bg-blue-100">
                    <tr>
                      <td colspan="6" class="px-4 py-3 text-sm font-bold text-blue-900 text-right">
                        TOTAL AGUA:
                      </td>
                      <td class="px-4 py-3 text-sm font-bold text-blue-900 text-right">
                        Bs {{ formatCurrency(expense.property_details.filter(d => d.agua.has_water_meter).reduce((sum, d) => sum + d.montos.water_amount, 0)) }}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <!-- Estadísticas de Consumo -->
              <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-blue-900">Propiedades con Medidor</div>
                      <div class="text-lg font-bold text-blue-600">
                        {{ expense.property_details.filter(d => d.agua.has_water_meter).length }} / {{ expense.property_details.length }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-green-900">Consumo Total</div>
                      <div class="text-lg font-bold text-green-600">
                        {{ expense.property_details.filter(d => d.agua.has_water_meter).reduce((sum, d) => sum + d.agua.consumption_m3, 0) }} m³
                      </div>
                    </div>
                  </div>
                </div>
                <div class="bg-orange-50 rounded-lg p-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-orange-900">Promedio por Propiedad</div>
                      <div class="text-lg font-bold text-orange-600">
                        {{ (expense.property_details.filter(d => d.agua.has_water_meter).reduce((sum, d) => sum + d.agua.consumption_m3, 0) / expense.property_details.filter(d => d.agua.has_water_meter).length).toFixed(1) }} m³
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Desglose de Montos -->
          <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Desglose de Montos</h2>
              <p class="mt-1 text-sm text-gray-600">
                Resumen de los conceptos incluidos en esta expensa consolidada
              </p>
            </div>
            <div class="px-6 py-4">
              <div class="space-y-4">
                <div class="flex justify-between items-center py-3 px-4 border-l-4 border-blue-500 bg-blue-50 rounded">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <div>
                      <div class="text-sm font-medium text-gray-900">Expensa Base (m² × factor)</div>
                      <div class="text-xs text-gray-500">Suma de expensas comunes por propiedad</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-gray-900">Bs {{ formatCurrency(expense.desglose.base_amount) }}</div>
                  </div>
                </div>

                <div v-if="expense.desglose.water_amount > 0" class="flex justify-between items-center py-3 px-4 border-l-4 border-cyan-500 bg-cyan-50 rounded">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 text-cyan-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                      <div class="text-sm font-medium text-gray-900">Consumo de Agua</div>
                      <div class="text-xs text-gray-500">Basado en lecturas de medidores y factores del período</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-cyan-600">Bs {{ formatCurrency(expense.desglose.water_amount) }}</div>
                    <div class="text-xs text-gray-500">
                      {{ expense.property_details.filter(d => d.agua.has_water_meter).reduce((sum, d) => sum + d.agua.consumption_m3, 0) }} m³
                    </div>
                  </div>
                </div>

                <div v-if="expense.desglose.other_amount > 0" class="flex justify-between items-center py-3 px-4 border-l-4 border-purple-500 bg-purple-50 rounded">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010-2.828l-7-7A1.994 1.994 0 003 12V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10a4 4 0 004 4z" />
                    </svg>
                    <div>
                      <div class="text-sm font-medium text-gray-900">Otros Conceptos</div>
                      <div class="text-xs text-gray-500">Cargos adicionales o servicios extraordinarios</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-purple-600">Bs {{ formatCurrency(expense.desglose.other_amount) }}</div>
                  </div>
                </div>

                <div v-if="expense.desglose.previous_debt > 0" class="flex justify-between items-center py-3 px-4 border-l-4 border-orange-500 bg-orange-50 rounded">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                      <div class="text-sm font-medium text-gray-900">Deuda Anterior</div>
                      <div class="text-xs text-gray-500">Saldo pendiente de períodos anteriores</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-orange-600">Bs {{ formatCurrency(expense.desglose.previous_debt) }}</div>
                  </div>
                </div>

                <div class="flex justify-between items-center py-4 px-4 border-l-4 border-green-500 bg-green-50 rounded-lg">
                  <div class="flex items-center">
                    <svg class="h-6 w-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                      <div class="text-base font-bold text-gray-900">Total a Pagar</div>
                      <div class="text-xs text-gray-600">Monto total de la expensa consolidada</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-xl font-bold text-green-600">Bs {{ formatCurrency(expense.desglose.total_amount) }}</div>
                  </div>
                </div>

                <!-- Estado de Pagos -->
                <div v-if="expense.desglose.paid_amount > 0 || expense.desglose.balance > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                  <div v-if="expense.desglose.paid_amount > 0" class="bg-green-100 border border-green-300 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium text-green-700">Pagado</span>
                      </div>
                      <span class="text-lg font-bold text-green-700">Bs {{ formatCurrency(expense.desglose.paid_amount) }}</span>
                    </div>
                  </div>

                  <div class="bg-blue-100 border border-blue-300 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium text-blue-700">Saldo Pendiente</span>
                      </div>
                      <span class="text-lg font-bold text-blue-700">Bs {{ formatCurrency(expense.desglose.balance) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagos del Período Actual -->
          <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">Pagos del Período</h2>
                <div class="text-sm text-gray-500">
                  <div>
                    Expensa del período: <span class="font-bold text-gray-600">Bs {{ formatCurrency(expense.total_amount) }}</span>
                  </div>
                  <div>
                    Total pagado: <span class="font-bold text-green-600">Bs {{ formatCurrency(getCurrentPeriodPaid()) }}</span>
                  </div>
                  <div v-if="getCreditForNextPeriod() > 0">
                    Saldo a favor para próximo período: <span class="font-bold text-orange-600">Bs {{ formatCurrency(getCreditForNextPeriod()) }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="px-6 py-4">
              <div v-if="getCurrentPeriodPayments().length > 0" class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Recibo</th>
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo de Pago</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="payment in getCurrentPeriodPayments()" :key="payment.id" class="hover:bg-gray-50">
                      <td class="px-3 py-2 text-sm text-gray-900">
                        {{ formatDate(payment.payment_date) }}
                      </td>
                      <td class="px-3 py-2 text-sm">
                        <span
                          :class="payment.receipt_number === 'SIN-RECIBO'
                            ? 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
                            : 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800'"
                        >
                          {{ payment.receipt_number }}
                        </span>
                      </td>
                      <td class="px-3 py-2 text-sm text-gray-900">
                        <div class="font-medium">{{ payment.payment_type || 'Sin tipo' }}</div>
                      </td>
                      <td class="px-3 py-2 text-sm text-right font-medium text-green-600">
                        Bs {{ formatCurrency(payment.amount) }}
                      </td>
                      <td class="px-3 py-2 text-sm text-gray-500">
                        {{ payment.reference || '-' }}
                      </td>
                    </tr>
                  </tbody>
                  <tfoot class="bg-gray-50">
                    <tr>
                      <td colspan="3" class="px-3 py-3 text-sm font-bold text-gray-900 text-right">
                        TOTAL PAGADO:
                      </td>
                      <td colspan="2" class="px-3 py-3 text-sm font-bold text-green-600 text-right">
                        Bs {{ formatCurrency(getCurrentPeriodPaid()) }}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="mt-2">No hay pagos registrados para este período</p>
              </div>

              <!-- Resumen de Crédito -->
              <div v-if="getCreditForNextPeriod() > 0" class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-start">
                  <svg class="h-5 w-5 text-green-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="text-green-800">
                    <p class="font-medium">Saldo a Favor para Próximo Período</p>
                    <p class="text-sm mt-1">
                      El propietario tiene <strong>Bs {{ formatCurrency(getCreditForNextPeriod()) }}</strong> disponibles
                      para aplicar automáticamente en el siguiente período.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Resumen de Deuda Pendiente -->
              <div v-if="getCurrentPeriodPaid() < expense.total_amount" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start">
                  <svg class="h-5 w-5 text-red-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div class="text-red-800">
                    <p class="font-medium">Saldo Pendiente</p>
                    <p class="text-sm mt-1">
                      Resta pagar <strong>Bs {{ formatCurrency(expense.total_amount - getCurrentPeriodPaid()) }}</strong>
                      para completar el pago de este período.
                    </p>
                  </div>
                </div>
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

const getFactorForProperty = (detail) => {
  // Si no hay factores de agua, retornar el valor guardado (para compatibilidad)
  if (!props.expense.water_factors) {
    return detail.agua.factor_aplicado || 'N/A'
  }

  // Determinar el tipo de propiedad para seleccionar el factor correcto
  const esComercial = detail.agua.es_comercial

  if (esComercial) {
    return props.expense.water_factors.factor_comercial || 'N/A'
  } else {
    return props.expense.water_factors.factor_domiciliario || 'N/A'
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

// Nuevas funciones para el sistema simplificado de pagos
const getCurrentPeriodPayments = () => {
  // Simula la llamada al método del modelo
  // En una implementación real, esto vendría del backend
  if (props.expense.current_period_payments) {
    return props.expense.current_period_payments
  }

  // Por ahora, usamos las allocations existentes para obtener los payments
  const payments = props.expense.payment_allocations?.map(allocation => allocation.payment) || []
  return [...new Map(payments.map(p => [p.id, p])).values()] // Eliminar duplicados
}

const getCurrentPeriodPaid = () => {
  // Calcula el total pagado en el período actual
  const payments = getCurrentPeriodPayments()
  return payments.reduce((sum, payment) => {
    return sum + (payment.amount || 0)
  }, 0)
}

const getCreditForNextPeriod = () => {
  const totalPaid = getCurrentPeriodPaid()
  const creditAvailable = totalPaid - props.expense.total_amount
  return Math.max(0, creditAvailable)
}

const formatDate = (dateString) => {
  if (!dateString) return 'Sin fecha'

  try {
    const date = new Date(dateString)

    // Verificar si la fecha es válida
    if (isNaN(date.getTime())) return 'Fecha inválida'

    return date.toLocaleDateString('es-BO', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch (error) {
    console.warn('Error al formatear fecha:', dateString, error)
    return 'Fecha inválida'
  }
}
</script>
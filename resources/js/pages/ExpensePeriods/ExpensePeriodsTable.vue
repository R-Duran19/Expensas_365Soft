<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Eye, Lock, AlertTriangle, Receipt, Plus } from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

// ==========================================
// TIPOS
// ==========================================
interface ExpensePeriod {
  id: number;
  year: number;
  month: number;
  period_date: string;
  status: 'open' | 'closed';
  total_generated: number;
  total_collected: number;
  notes: string | null;
  closed_at: string | null;
  property_expenses_count: number;
  cash_transactions_count: number;
  created_at: string;
}

interface PaginatedPeriods {
  data: ExpensePeriod[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
  periods: PaginatedPeriods;
}

const props = defineProps<Props>();
const page = usePage();
const { showSuccess, showError } = useNotification();

// Estado para diálogos
const showConfirmDialog = ref(false);
const showCreateNextPeriodDialog = ref(false);
const periodToClose = ref<ExpensePeriod | null>(null);
const nextPeriodData = ref<any>(null);

// ==========================================
// HELPERS
// ==========================================
const getMonthName = (month: number): string => {
  const months = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];
  return months[month - 1] || '';
};

const getPeriodName = (period: ExpensePeriod): string => {
  return `${getMonthName(period.month)} ${period.year}`;
};

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2,
  }).format(amount);
};

// ==========================================
// MÉTODOS
// ==========================================
const viewPeriod = (period: ExpensePeriod) => {
  router.visit(`/expense-periods/${period.id}`);
};

const viewReceipts = (period: ExpensePeriod) => {
  router.visit(`/expense-periods/${period.id}/receipts`);
};

const closePeriod = (period: ExpensePeriod) => {
  periodToClose.value = period;
  showConfirmDialog.value = true;
};

const confirmClosePeriod = () => {
  if (!periodToClose.value) return;

  router.post(
    `/expense-periods/${periodToClose.value.id}/close`,
    {},
    {
      preserveScroll: true,
      onSuccess: (resPage) => {
        showSuccess('Período cerrado exitosamente');
        showConfirmDialog.value = false;

        // Verificar si se debe mostrar diálogo para crear siguiente período
        const flash = (resPage.props.flash as any) || {};
        if (flash.showCreateNextPeriod && flash.nextPeriod) {
          nextPeriodData.value = flash.nextPeriod;
          showCreateNextPeriodDialog.value = true;
        }

        periodToClose.value = null;
      },
      onError: (errors) => {
        showError(errors.error || 'Error al cerrar el período');
      },
    }
  );
};

const createNextPeriod = () => {
  if (!nextPeriodData.value) return;

  router.post('/expense-periods', nextPeriodData.value, {
    onSuccess: () => {
      showSuccess('Siguiente período creado exitosamente');
      showCreateNextPeriodDialog.value = false;
      nextPeriodData.value = null;
    },
    onError: (errors) => {
      showError(errors.error || 'Error al crear el siguiente período');
    }
  });
};

const cancelClosePeriod = () => {
  showConfirmDialog.value = false;
  periodToClose.value = null;
};

const goToPage = (url: string | null) => {
  if (url) {
    router.visit(url, { preserveState: true, preserveScroll: true });
  }
};

// ==========================================
// FUNCIONES DE CÁLCULO DE COBROS
// ==========================================
const getCollectionPercentage = (period: ExpensePeriod): number => {
  if (period.total_generated <= 0) return 0;
  return Math.round((period.total_collected / period.total_generated) * 100);
};

const getCollectionPercentageClass = (period: ExpensePeriod): string => {
  const percentage = getCollectionPercentage(period);

  if (percentage >= 100) return 'text-green-600 font-bold';
  if (percentage >= 80) return 'text-blue-600 font-semibold';
  if (percentage >= 50) return 'text-yellow-600';
  return 'text-red-600';
};

const getCollectionTooltip = (period: ExpensePeriod): string => {
  const percentage = getCollectionPercentage(period);
  const pending = period.total_generated - period.total_collected;

  if (percentage >= 100) {
    return `Se ha cobrado ${percentage}% del total generado. Incluye pagos excedidos que generan saldos a favor.`;
  } else if (pending > 0) {
    return `Se ha cobrado ${percentage}% del total. Pendiente por cobrar: ${formatCurrency(pending)}`;
  } else {
    return `Se ha cobrado ${percentage}% del total generado.`;
  }
};
</script>

<template>
  <div class="px-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
      <!-- Vista Desktop: Tabla tradicional -->
      <div class="hidden lg:block">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Período
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Fecha
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Estado
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Total Generado
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Total Cobrado
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  % Cobro
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Propiedades
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="periods.data.length === 0">
                <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                  No hay períodos registrados
                </td>
              </tr>
              <tr
                v-for="period in periods.data"
                :key="period.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ getPeriodName(period) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ new Date(period.period_date).toLocaleDateString('es-BO') }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <Badge
                    :variant="period.status === 'open' ? 'default' : 'secondary'"
                    :class="period.status === 'open' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'"
                  >
                    {{ period.status === 'open' ? 'Abierto' : 'Cerrado' }}
                  </Badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900 dark:text-white">
                    {{ formatCurrency(period.total_generated) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm">
                    <span
                      :class="getCollectionPercentageClass(period)"
                      :title="getCollectionTooltip(period)"
                    >
                      {{ formatCurrency(period.total_collected) }}
                    </span>
                    <div v-if="period.total_collected > period.total_generated" class="text-xs text-orange-600 mt-1">
                      +{{ formatCurrency(period.total_collected - period.total_generated) }}
                      <span class="text-gray-500">(pagos excedidos)</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm">
                    <span
                      :class="getCollectionPercentageClass(period)"
                      :title="getCollectionTooltip(period)"
                    >
                      {{ getCollectionPercentage(period) }}%
                    </span>
                    <div v-if="period.total_collected > period.total_generated" class="text-xs text-orange-600 mt-1">
                      >100%
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ period.property_expenses_count }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex items-center gap-2">
                    <!-- <Button
                      variant="ghost"
                      size="sm"
                      @click="viewPeriod(period)"
                      title="Ver detalle"
                    >
                      <Eye class="h-4 w-4" />
                    </Button> -->
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="viewReceipts(period)"
                      title="Ver recibos del período"
                      class="text-blue-600 hover:text-blue-700"
                    >
                      <Receipt class="h-4 w-4" />
                    </Button>
                    <Button
                      v-if="period.status === 'open'"
                      variant="ghost"
                      size="sm"
                      @click="closePeriod(period)"
                      title="Cerrar período"
                      class="text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300"
                    >
                      <Lock class="h-4 w-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Vista Mobile: Cards -->
      <div class="lg:hidden p-4 space-y-4">
        <div v-if="periods.data.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          No hay períodos registrados
        </div>

        <div
          v-for="period in periods.data"
          :key="period.id"
          class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3"
        >
          <!-- Header con período y estado -->
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ getPeriodName(period) }}
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ new Date(period.period_date).toLocaleDateString('es-BO') }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <Badge
                :variant="period.status === 'open' ? 'default' : 'secondary'"
                :class="period.status === 'open' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'"
                class="text-xs"
              >
                {{ period.status === 'open' ? 'Abierto' : 'Cerrado' }}
              </Badge>
            </div>
          </div>

          <!-- Montos -->
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-600 rounded p-3">
              <p class="text-xs text-gray-500 dark:text-gray-300">Generado</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ formatCurrency(period.total_generated) }}
              </p>
            </div>
            <div class="bg-white dark:bg-gray-600 rounded p-3">
              <p class="text-xs text-gray-500 dark:text-gray-300">Cobrado</p>
              <p class="text-sm font-semibold" :class="getCollectionPercentageClass(period)">
                {{ formatCurrency(period.total_collected) }}
              </p>
              <div class="text-xs" :class="getCollectionPercentageClass(period)">
                {{ getCollectionPercentage(period) }}%
              </div>
              <div v-if="period.total_collected > period.total_generated" class="text-xs text-orange-600 mt-1">
                +{{ formatCurrency(period.total_collected - period.total_generated) }}
              </div>
            </div>
          </div>

          <!-- Info adicional y acciones -->
          <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
            <div class="text-xs text-gray-500 dark:text-gray-400">
              {{ period.property_expenses_count }} propiedades
            </div>
            <div class="flex items-center gap-1">
              <!-- <Button
                variant="ghost"
                size="sm"
                @click="viewPeriod(period)"
                title="Ver detalle"
                class="h-8 w-8 p-0"
              >
                <Eye class="h-3 w-3" />
              </Button> -->
              <Button
                variant="ghost"
                size="sm"
                @click="viewReceipts(period)"
                title="Ver recibos del período"
                class="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
              >
                <Receipt class="h-3 w-3" />
              </Button>
              <Button
                v-if="period.status === 'open'"
                variant="ghost"
                size="sm"
                @click="closePeriod(period)"
                title="Cerrar período"
                class="h-8 w-8 p-0 text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300"
              >
                <Lock class="h-3 w-3" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="periods.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <!-- Desktop: Layout horizontal -->
        <div class="hidden sm:flex items-center justify-between">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            Mostrando {{ periods.data.length }} de {{ periods.total }} períodos
          </div>
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="periods.current_page === 1"
              @click="goToPage(periods.links.find(link => link.label === '&laquo; Previous')?.url || null)"
            >
              Anterior
            </Button>
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Página {{ periods.current_page }} de {{ periods.last_page }}
            </span>
            <Button
              variant="outline"
              size="sm"
              :disabled="periods.current_page === periods.last_page"
              @click="goToPage(periods.links.find(link => link.label === 'Next &raquo;')?.url || null)"
            >
              Siguiente
            </Button>
          </div>
        </div>

        <!-- Mobile: Layout vertical -->
        <div class="sm:hidden space-y-3">
          <div class="text-sm text-gray-700 dark:text-gray-300 text-center">
            Mostrando {{ periods.data.length }} de {{ periods.total }} períodos
          </div>
          <div class="flex items-center justify-center gap-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="periods.current_page === 1"
              @click="goToPage(periods.links.find(link => link.label === '&laquo; Previous')?.url || null)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </Button>
            <span class="text-sm text-gray-700 dark:text-gray-300 px-2">
              {{ periods.current_page }}/{{ periods.last_page }}
            </span>
            <Button
              variant="outline"
              size="sm"
              :disabled="periods.current_page === periods.last_page"
              @click="goToPage(periods.links.find(link => link.label === 'Next &raquo;')?.url || null)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Diálogo de confirmación para cerrar período -->
    <Dialog v-model:open="showConfirmDialog">
      <DialogContent class="sm:max-w-[425px] dark:bg-gray-900">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <AlertTriangle class="h-5 w-5 text-orange-500" />
            Cerrar Período
          </DialogTitle>
        </DialogHeader>

        <div class="py-4">
          <p class="text-sm text-gray-700 dark:text-gray-300">
            ¿Está seguro de cerrar el período
            <span class="font-semibold">
              {{ periodToClose ? getPeriodName(periodToClose) : '' }}
            </span>?
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            Una vez cerrado, no se podrá modificar.
          </p>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            @click="cancelClosePeriod"
            :disabled="false"
          >
            Cancelar
          </Button>
          <Button
            variant="destructive"
            @click="confirmClosePeriod"
            class="bg-orange-600 hover:bg-orange-700 text-white"
          >
            Cerrar Período
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Diálogo para crear siguiente período -->
    <Dialog v-model:open="showCreateNextPeriodDialog">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Plus class="h-5 w-5 text-green-600" />
            Crear Siguiente Período
          </DialogTitle>
        </DialogHeader>

        <div class="py-4" v-if="nextPeriodData">
          <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <div class="flex items-start">
              <svg class="h-5 w-5 text-green-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="text-green-800">
                <p class="font-medium">Período anterior cerrado correctamente</p>
                <p class="text-sm mt-1">Ahora puedes crear el siguiente período automáticamente.</p>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Año</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-md">
                  {{ nextPeriodData.year }}
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Mes</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-md">
                  {{ getMonthName(nextPeriodData.month) }}
                </div>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Fecha del Período</label>
              <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-md">
                {{ new Date(nextPeriodData.period_date).toLocaleDateString('es-BO') }}
              </div>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            @click="showCreateNextPeriodDialog = false"
          >
            Cancelar
          </Button>
          <Button
            @click="createNextPeriod"
            class="bg-green-600 hover:bg-green-700"
          >
            Crear Período
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>


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
import { Eye, Lock, AlertTriangle } from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';
import { ref } from 'vue';

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
const { showSuccess, showError } = useNotification();

// Estado para el diálogo de confirmación
const showConfirmDialog = ref(false);
const periodToClose = ref<ExpensePeriod | null>(null);

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
      onSuccess: () => {
        showSuccess('Período cerrado exitosamente');
        showConfirmDialog.value = false;
        periodToClose.value = null;
      },
      onError: (errors) => {
        showError(errors.error || 'Error al cerrar el período');
      },
    }
  );
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
</script>

<template>
  <div class="px-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
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
              Propiedades
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-if="periods.data.length === 0">
            <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
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
              <div class="text-sm text-gray-900 dark:text-white">
                {{ formatCurrency(period.total_collected) }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ period.property_expenses_count }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <div class="flex items-center gap-2">
                <Button
                  variant="ghost"
                  size="sm"
                  @click="viewPeriod(period)"
                  title="Ver detalle"
                >
                  <Eye class="h-4 w-4" />
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

      <!-- Paginación -->
      <div v-if="periods.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
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
  </div>
</template>


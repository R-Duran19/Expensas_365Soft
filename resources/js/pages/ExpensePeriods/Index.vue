<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import ExpensePeriodsHeader from './ExpensePeriodsHeader.vue';
import ExpensePeriodsTable from './ExpensePeriodsTable.vue';
import ExpensePeriodFormDialog from './ExpensePeriodFormDialog.vue';
import { ref, computed } from 'vue';

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

// ==========================================
// BREADCRUMBS
// ==========================================
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Períodos de Expensas', href: '/expense-periods' },
];

// ==========================================
// ESTADO
// ==========================================
const showCreateDialog = ref(false);

// ==========================================
// COMPUTED
// ==========================================
const hasOpenPeriod = computed(() => {
  return props.periods.data.some(period => period.status === 'open');
});

// ==========================================
// MÉTODOS
// ==========================================
const handleCreate = () => {
  showCreateDialog.value = false;
  // Usar visit para recargar solo la data sin recargar la página completa
  router.visit('/expense-periods', {
    method: 'get',
    preserveScroll: true,
    only: ['periods'],
    onSuccess: () => {
      console.log('Datos recargados exitosamente');
    },
    onError: (errors) => {
      console.error('Error al recargar datos:', errors);
    }
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Períodos de Expensas" />

    <div class="space-y-4 sm:space-y-6 min-h-0">
      <ExpensePeriodsHeader
        :has-open-period="hasOpenPeriod"
        @create-period="showCreateDialog = true"
      />

      <div class="flex-1 min-h-0">
        <ExpensePeriodsTable :periods="props.periods" />
      </div>

      <ExpensePeriodFormDialog
        v-model:open="showCreateDialog"
        @save="handleCreate"
        @cancel="showCreateDialog = false"
      />
    </div>
  </AppLayout>
</template>


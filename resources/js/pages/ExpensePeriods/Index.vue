<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ExpensePeriodsHeader from './ExpensePeriodsHeader.vue';
import ExpensePeriodsTable from './ExpensePeriodsTable.vue';
import ExpensePeriodFormDialog from './ExpensePeriodFormDialog.vue';
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

defineProps<Props>();

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
// MÉTODOS
// ==========================================
const handleCreate = () => {
  showCreateDialog.value = false;
  // Recargar la página para mostrar los nuevos datos
  window.location.reload();
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Períodos de Expensas" />
    
    <div class="space-y-6">
      <ExpensePeriodsHeader 
        @create-period="showCreateDialog = true"
      />

      <ExpensePeriodsTable :periods="periods" />

      <ExpensePeriodFormDialog 
        v-model:open="showCreateDialog"
        @save="handleCreate"
        @cancel="showCreateDialog = false"
      />
    </div>
  </AppLayout>
</template>


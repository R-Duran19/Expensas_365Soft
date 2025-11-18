<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
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
import { ArrowLeft, Receipt, FileText, Calendar, DollarSign } from 'lucide-vue-next';

interface Payment {
  id: number;
  receipt_number: string;
  amount: number;
  payment_date: string;
  reference: string | null;
  notes: string | null;
  created_at: string;
  payment_type: {
    id: number;
    name: string;
  };
  propietario: {
    id: number;
    nombre_completo: string;
  };
  propiedad: {
    id: number;
    codigo: string;
    ubicacion: string;
  };
}

interface ExpensePeriod {
  id: number;
  year: number;
  month: number;
  period_date: string;
  status: 'open' | 'closed';
  total_generated: number;
  total_collected: number;
  closed_at: string | null;
}

interface Props {
  period: ExpensePeriod;
  receipts: Payment[];
  statistics: {
    total_receipts: number;
    total_amount: number;
    payment_types: Record<string, { count: number; total: number }>;
  };
}

const props = defineProps<Props>();

// Formateo de moneda
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2,
  }).format(amount);
};

// Formateo de fecha
const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

// Nombre del mes
const getMonthName = (month: number): string => {
  const months = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];
  return months[month - 1] || '';
};

// Nombre del período
const getPeriodName = (period: ExpensePeriod): string => {
  return `${getMonthName(period.month)} ${period.year}`;
};

// Ver detalle de un pago específico
const viewPaymentDetail = (paymentId: number) => {
  router.visit(`/pagos/${paymentId}`);
};
</script>

<template>
  <AppLayout :title="`Recibos - ${getPeriodName(period)}`">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <Link
                href="/expense-periods"
                class="text-blue-600 hover:text-blue-800 flex items-center"
              >
                <ArrowLeft class="h-5 w-5 mr-2" />
                Volver a Períodos
              </Link>
              <div class="flex items-center">
                <Receipt class="h-6 w-6 mr-3 text-blue-600" />
                <h1 class="text-2xl font-semibold text-gray-900">
                  Recibos - {{ getPeriodName(period) }}
                </h1>
              </div>
            </div>
            <div class="flex items-center space-x-3">
              <Badge
                :variant="period.status === 'open' ? 'default' : 'secondary'"
                :class="period.status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              >
                {{ period.status === 'open' ? 'Abierto' : 'Cerrado' }}
              </Badge>
              <Button
                variant="outline"
                @click="router.visit(`/expense-periods/${period.id}`)"
              >
                <FileText class="h-4 w-4 mr-2" />
                Ver Detalle del Período
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <Receipt class="h-8 w-8 text-blue-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Recibos</p>
              <p class="text-2xl font-bold text-gray-900">{{ statistics.total_receipts }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DollarSign class="h-8 w-8 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Recaudado</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(statistics.total_amount) }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <Calendar class="h-8 w-8 text-orange-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Período</p>
              <p class="text-lg font-bold text-gray-900">{{ getPeriodName(period) }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <FileText class="h-8 w-8 text-purple-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Tipos de Pago</p>
              <p class="text-lg font-bold text-gray-900">{{ Object.keys(statistics.payment_types).length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Recibos -->
      <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Detalle de Recibos</h2>
        </div>
        <div class="px-6 py-4">
          <div v-if="receipts.length > 0" class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="w-[100px]">Fecha</TableHead>
                  <TableHead>Recibo</TableHead>
                  <TableHead>Propietario</TableHead>
                  <TableHead>Propiedad</TableHead>
                  <TableHead>Tipo Pago</TableHead>
                  <TableHead class="text-right">Monto</TableHead>
                  <TableHead>Referencia</TableHead>
                  <TableHead class="w-[80px]">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="receipt in receipts"
                  :key="receipt.id"
                  class="hover:bg-gray-50"
                >
                  <TableCell class="text-sm">
                    {{ formatDate(receipt.payment_date) }}
                  </TableCell>
                  <TableCell>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ receipt.receipt_number }}
                    </span>
                  </TableCell>
                  <TableCell class="text-sm font-medium text-gray-900">
                    {{ receipt.propietario.nombre_completo }}
                  </TableCell>
                  <TableCell class="text-sm text-gray-500">
                    <div>
                      <div class="font-medium">{{ receipt.propiedad.codigo }}</div>
                      <div class="text-xs">{{ receipt.propiedad.ubicacion }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="receipt.payment_type?.name ? 'default' : 'secondary'">
                      {{ receipt.payment_type?.name || 'N/A' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-sm text-right font-medium text-green-600">
                    {{ formatCurrency(receipt.amount) }}
                  </TableCell>
                  <TableCell class="text-sm text-gray-500">
                    {{ receipt.reference || '-' }}
                  </TableCell>
                  <TableCell>
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="viewPaymentDetail(receipt.id)"
                      title="Ver detalle del pago"
                    >
                      <FileText class="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <div v-else class="text-center py-12 text-gray-500">
            <Receipt class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay recibos registrados</h3>
            <p class="mt-1 text-sm text-gray-500">
              No se encontraron recibos para este período.
            </p>
          </div>
        </div>
      </div>

      <!-- Resumen por Tipo de Pago -->
      <div v-if="Object.keys(statistics.payment_types).length > 0" class="mt-6 bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Resumen por Tipo de Pago</h2>
        </div>
        <div class="px-6 py-4">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="(data, type) in statistics.payment_types"
              :key="type"
              class="border border-gray-200 rounded-lg p-4"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-sm font-medium text-gray-900">{{ type }}</h3>
                  <p class="text-xs text-gray-500">{{ data.count }} recibo(s)</p>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold text-green-600">{{ formatCurrency(data.total) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
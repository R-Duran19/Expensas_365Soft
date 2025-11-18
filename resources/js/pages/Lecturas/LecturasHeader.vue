<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Plus, FileSpreadsheet, Filter, X, Gauge, Receipt, Menu } from 'lucide-vue-next';

interface Filtros {
  period_id?: number;
  medidor_id?: number;
  fecha_desde?: string;
  fecha_hasta?: string;
}

interface Periodo {
  id: number;
  nombre: string;
  mes_periodo: string;
}

interface PeriodoActivo {
  id: number;
  nombre: string;
  mes_periodo: string;
}

interface Props {
  periodos: Periodo[];
  filtros: Filtros;
  periodoActivo?: PeriodoActivo | null;
}

const props = withDefaults(defineProps<Props>(), {
  periodoActivo: null
});
const emit = defineEmits<{
  'create-lectura': [];
}>();

const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
};

// ==========================================
// ESTADO LOCAL DE FILTROS
// ==========================================
const filtrosLocal = ref<Filtros>({
  period_id: props.filtros.period_id || props.periodoActivo?.id,
  medidor_id: props.filtros.medidor_id,
  fecha_desde: props.filtros.fecha_desde,
  fecha_hasta: props.filtros.fecha_hasta,
});

// ==========================================
// COMPUTED
// ==========================================
const hayFiltrosActivos = computed(() => {
  return !!(
    filtrosLocal.value.period_id ||
    filtrosLocal.value.medidor_id ||
    filtrosLocal.value.fecha_desde ||
    filtrosLocal.value.fecha_hasta
  );
});

const cantidadFiltrosActivos = computed(() => {
  let count = 0;
  if (filtrosLocal.value.period_id) count++;
  if (filtrosLocal.value.medidor_id) count++;
  if (filtrosLocal.value.fecha_desde) count++;
  if (filtrosLocal.value.fecha_hasta) count++;
  return count;
});

// ==========================================
// MÉTODOS
// ==========================================
const aplicarFiltros = () => {
  const filtrosLimpios: Filtros = {};

  if (filtrosLocal.value.period_id) {
    filtrosLimpios.period_id = filtrosLocal.value.period_id;
  }
  if (filtrosLocal.value.medidor_id) {
    filtrosLimpios.medidor_id = filtrosLocal.value.medidor_id;
  }
  if (filtrosLocal.value.fecha_desde) {
    filtrosLimpios.fecha_desde = filtrosLocal.value.fecha_desde;
  }
  if (filtrosLocal.value.fecha_hasta) {
    filtrosLimpios.fecha_hasta = filtrosLocal.value.fecha_hasta;
  }

  router.get('/lecturas', filtrosLimpios, {
    preserveState: true,
    preserveScroll: true,
  });
};

const limpiarFiltros = () => {
  filtrosLocal.value = {
    period_id: props.periodoActivo?.id,
    medidor_id: undefined,
    fecha_desde: undefined,
    fecha_hasta: undefined,
  };
  router.get('/lecturas', {
    period_id: props.periodoActivo?.id
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const formatPeriodo = (periodo: string): string => {
  try {
    const [year, month] = periodo.split('-');
    const fecha = new Date(parseInt(year), parseInt(month) - 1);
    return fecha.toLocaleDateString('es-BO', { month: 'long', year: 'numeric' });
  } catch {
    return periodo;
  }
};

// ==========================================
// WATCHERS & LIFECYCLE
// ==========================================
// Auto-aplicar filtro del período activo si no hay filtros activos
onMounted(() => {
  if (props.periodoActivo && !props.filtros.period_id) {
    // Aplicar filtro automáticamente solo si no hay otros filtros activos
    setTimeout(() => {
      aplicarFiltros();
    }, 100); // Pequeño delay para asegurar que el componente está montado
  }
});
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
        <!-- Título y descripción -->
        <div class="flex-1 space-y-1">
          <CardTitle class="flex items-center gap-2">
            <Gauge class="h-5 w-5 sm:h-6 sm:w-6" />
            <span class="text-lg sm:text-xl">Lecturas de Medidores</span>
          </CardTitle>
          <CardDescription class="text-sm sm:text-base">
            Gestiona las lecturas mensuales de consumo de agua
          </CardDescription>
        </div>

        <!-- Desktop: Todos los botones visibles -->
        <div class="hidden sm:flex sm:items-center sm:gap-2 sm:flex-shrink-0">
          <!-- Botón Facturas de Medidores Principales -->
          <Link href="/facturas-medidores-principales">
            <Button variant="outline" size="sm" class="border-blue-200 text-blue-700 hover:bg-blue-50 whitespace-nowrap">
              <Receipt class="h-4 w-4 mr-2" />
              <span class="hidden lg:inline">Fact. Medidores</span>
              <span class="lg:hidden">Facturas</span>
            </Button>
          </Link>

          <!-- Botón Registro Masivo -->
          <Link href="/lecturas/create">
            <Button variant="outline" size="sm" class="whitespace-nowrap">
              <FileSpreadsheet class="h-4 w-4 mr-2" />
              <span class="hidden lg:inline">Registro Masivo</span>
              <span class="lg:hidden">Masivo</span>
            </Button>
          </Link>

          <!-- Botón Nueva Lectura -->
          <Button @click="emit('create-lectura')" size="sm" class="whitespace-nowrap">
            <Plus class="h-4 w-4 mr-2" />
            <span class="hidden lg:inline">Nueva Lectura</span>
            <span class="lg:hidden">Nueva</span>
          </Button>
        </div>

        <!-- Mobile: Botón de menú hamburguesa -->
        <div class="sm:hidden">
          <Button
            @click="toggleMobileMenu"
            variant="outline"
            size="sm"
            class="p-2"
          >
            <Menu class="h-5 w-5" />
          </Button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div
        v-if="mobileMenuOpen"
        class="sm:hidden mt-4 p-4 bg-white border border-gray-200 rounded-lg shadow-lg"
      >
        <div class="space-y-3">
          <Link href="/facturas-medidores-principales" class="block">
            <Button variant="outline" class="w-full justify-start border-blue-200 text-blue-700 hover:bg-blue-50" size="sm">
              <Receipt class="h-4 w-4 mr-3" />
              Facturas Medidores
            </Button>
          </Link>

          <Link href="/lecturas/create" class="block">
            <Button variant="outline" class="w-full justify-start" size="sm">
              <FileSpreadsheet class="h-4 w-4 mr-3" />
              Registro Masivo
            </Button>
          </Link>

          <Button @click="emit('create-lectura')" class="w-full justify-start" size="sm">
            <Plus class="h-4 w-4 mr-3" />
            Nueva Lectura
          </Button>
        </div>
      </div>
    </CardHeader>

    <CardContent>
       <!-- Mensaje de período activo o de ayuda -->
      <div
        v-if="!hayFiltrosActivos && periodoActivo"
        class="mb-4 p-3 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-md"
      >
        <p class="text-sm text-green-800 dark:text-green-200">
          ✅ <strong>Período Activo:</strong> {{ periodoActivo.nombre }} - Aplicando filtro automáticamente
        </p>
      </div>
      <div
        v-else-if="!hayFiltrosActivos"
        class="mb-4 p-3 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-md"
      >
        <p class="text-sm text-blue-800 dark:text-blue-200">
          💡 <strong>Tip:</strong> Selecciona un período para ver las lecturas correspondientes
        </p>
      </div>
      <!-- Filtros -->
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <!-- Filtro por Período -->
          <div class="space-y-2">
            <Label for="periodo">Período</Label>
            <select
              v-model="filtrosLocal.period_id"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
              <option value="">Seleccione un período</option>
              <option v-for="periodo in periodos" :key="periodo.id" :value="periodo.id">
                {{ periodo.nombre }}
              </option>
            </select>
            </div>

          <!-- Filtro por Fecha Desde -->
          <div class="space-y-2">
            <Label for="fecha_desde">Desde</Label>
            <Input
              id="fecha_desde"
              v-model="filtrosLocal.fecha_desde"
              type="date"
            />
          </div>

          <!-- Filtro por Fecha Hasta -->
          <div class="space-y-2">
            <Label for="fecha_hasta">Hasta</Label>
            <Input
              id="fecha_hasta"
              v-model="filtrosLocal.fecha_hasta"
              type="date"
            />
          </div>

          <!-- Botones de acción -->
          <div class="flex items-end gap-2">
            <Button
              @click="aplicarFiltros"
              class="flex-1"
              variant="secondary"
            >
              <Filter class="h-4 w-4 mr-2" />
              Filtrar
            </Button>
            <Button
              v-if="hayFiltrosActivos"
              @click="limpiarFiltros"
              variant="outline"
              size="icon"
              title="Limpiar filtros"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Indicador de filtros activos -->
        <div v-if="hayFiltrosActivos" class="flex items-center gap-2">
          <Badge variant="secondary" class="gap-1">
            <Filter class="h-3 w-3" />
            {{ cantidadFiltrosActivos }} filtro(s) activo(s)
          </Badge>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
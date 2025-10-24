<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Plus, FileSpreadsheet, Filter, X, Gauge } from 'lucide-vue-next';

interface Filtros {
  periodo?: string;
  medidor_id?: number;
  fecha_desde?: string;
  fecha_hasta?: string;
}

interface Props {
  periodos: string[];
  filtros: Filtros;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'create-lectura': [];
}>();

// ==========================================
// ESTADO LOCAL DE FILTROS
// ==========================================
const filtrosLocal = ref<Filtros>({
  periodo: props.filtros.periodo || '',
  medidor_id: props.filtros.medidor_id,
  fecha_desde: props.filtros.fecha_desde,
  fecha_hasta: props.filtros.fecha_hasta,
});

// ==========================================
// COMPUTED
// ==========================================
const hayFiltrosActivos = computed(() => {
  return !!(
    (filtrosLocal.value.periodo && filtrosLocal.value.periodo !== '') ||
    filtrosLocal.value.medidor_id ||
    filtrosLocal.value.fecha_desde ||
    filtrosLocal.value.fecha_hasta
  );
});

const cantidadFiltrosActivos = computed(() => {
  let count = 0;
  if (filtrosLocal.value.periodo && filtrosLocal.value.periodo !== '') count++;
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
  
  if (filtrosLocal.value.periodo && filtrosLocal.value.periodo !== '') {
    filtrosLimpios.periodo = filtrosLocal.value.periodo;
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
    periodo: '',
    medidor_id: undefined,
    fecha_desde: undefined,
    fecha_hasta: undefined,
  };
  router.get('/lecturas', {}, {
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
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <CardTitle class="flex items-center gap-2">
            <Gauge class="h-6 w-6" />
            Lecturas de Medidores
          </CardTitle>
          <CardDescription>
            Gestiona las lecturas mensuales de consumo de agua
          </CardDescription>
        </div>

        <div class="flex items-center gap-2">
          <!-- Botón Registro Masivo usando Link de Inertia -->
          <Link href="/lecturas/create">
            <Button variant="outline" size="default">
              <FileSpreadsheet class="h-4 w-4 mr-2" />
              Registro Masivo
            </Button>
          </Link>

          <!-- Botón Nueva Lectura -->
          <Button @click="emit('create-lectura')">
            <Plus class="h-4 w-4 mr-2" />
            Nueva Lectura
          </Button>
        </div>
      </div>
    </CardHeader>

    <CardContent>
      <!-- Filtros -->
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Filtro por Período -->
          <div class="space-y-2">
            <Label for="periodo">Período</Label>
            <select 
              v-model="filtrosLocal.periodo"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
              <option value="">Todos los períodos</option>
              <option v-for="periodo in periodos" :key="periodo" :value="periodo">
                {{ formatPeriodo(periodo) }}
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
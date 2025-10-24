<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
  Table, 
  TableBody, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Eye, Trash2, FileText, Gauge } from 'lucide-vue-next';
import { Search } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';

// ==========================================
// TIPOS
// ==========================================
interface TipoPropiedad {
  id: number;
  nombre: string;
  es_comercial: boolean;
}

interface Propiedad {
  id: number;
  codigo: string;
  nombre: string;
  ubicacion: string;
  tipo_propiedad: TipoPropiedad;
}

interface Medidor {
  id: number;
  numero_medidor: string;
  ubicacion: string | null;
  tipo: 'domiciliario' | 'comercial';
  propiedad: Propiedad;
}

interface Usuario {
  id: number;
  name: string;
}

interface Lectura {
  id: number;
  medidor_id: number;
  lectura_actual: number;
  lectura_anterior: number | null;
  consumo: number;
  fecha_lectura: string;
  mes_periodo: string;
  observaciones: string | null;
  medidor: Medidor;
  usuario: Usuario;
  created_at: string;
}

interface PaginatedLecturas {
  data: Lectura[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
  lecturas: PaginatedLecturas;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'edit-lectura': [lectura: Lectura];
}>();

// ==========================================
// ESTADO
// ==========================================
const lecturaAEliminar = ref<Lectura | null>(null);
const showDeleteDialog = ref(false);

// ==========================================
// MÉTODOS
// ==========================================
const formatFecha = (fecha: string): string => {
  return new Date(fecha).toLocaleDateString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const formatNumber = (value: number | null | undefined, decimals: number = 3): string => {
  if (value === null || value === undefined) return 'N/A';
  return Number(value).toFixed(decimals);
};

const formatPeriodo = (periodo: string): string => {
  try {
    const [year, month] = periodo.split('-');
    const fecha = new Date(parseInt(year), parseInt(month) - 1);
    return fecha.toLocaleDateString('es-BO', { month: 'short', year: 'numeric' });
  } catch {
    return periodo;
  }
};

const getTipoVariant = (tipo: string): 'default' | 'secondary' => {
  return tipo === 'comercial' ? 'secondary' : 'default';
};

const getConsumoColor = (consumo: number): string => {
  if (consumo === 0) return 'text-muted-foreground';
  if (consumo < 10) return 'text-green-600 dark:text-green-400';
  if (consumo < 30) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
};

const confirmarEliminar = (lectura: Lectura) => {
  lecturaAEliminar.value = lectura;
  showDeleteDialog.value = true;
};

const eliminarLectura = () => {
  if (!lecturaAEliminar.value) return;

  router.delete(`/lecturas/${lecturaAEliminar.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      lecturaAEliminar.value = null;
    },
  });
};
const searchTerm = ref('');

const lecturasFiltradas = computed(() => {
  if (!searchTerm.value) return props.lecturas.data;
  
  const search = searchTerm.value.toLowerCase();
  return props.lecturas.data.filter(lectura => {
    // Verificar que existan antes de usar toLowerCase()
    const numeroMedidor = lectura.medidor?.numero_medidor?.toLowerCase() || '';
    const codigoPropiedad = lectura.medidor?.propiedad?.codigo?.toLowerCase() || '';
    const nombrePropiedad = lectura.medidor?.propiedad?.nombre?.toLowerCase() || '';
    const tipo = lectura.medidor?.tipo?.toLowerCase() || '';
    
    return numeroMedidor.includes(search) ||
           codigoPropiedad.includes(search) ||
           nombrePropiedad.includes(search) ||
           tipo.includes(search);
  });
});
</script>

<template>
  <!-- Buscador -->
<div class="flex gap-4 mb-4">
  <div class="flex-1 relative">
    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
    <Input
      v-model="searchTerm"
      placeholder="Buscar por medidor, propiedad o tipo..."
      class="pl-9"
    />
  </div>
</div>
  <div class="rounded-lg border bg-card">
    <!-- Tabla -->
    <div class="overflow-x-auto">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Medidor / Propiedad</TableHead>
            <TableHead>Tipo</TableHead>
            <TableHead>Período</TableHead>
            <TableHead class="text-right">Lectura Anterior</TableHead>
            <TableHead class="text-right">Lectura Actual</TableHead>
            <TableHead class="text-right">Consumo (m³)</TableHead>
            <TableHead>Fecha</TableHead>
            <TableHead>Registrado por</TableHead>
            <TableHead class="text-right">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <!-- Filas de datos -->
          <TableRow v-for="lectura in lecturasFiltradas" :key="lectura.id">
            <!-- Medidor / Propiedad -->
            <TableCell>
              <div class="flex items-start gap-2">
                <Gauge class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-sm font-medium">{{ lectura.medidor.numero_medidor }}</p>
                  <p class="text-xs text-muted-foreground truncate">
                    {{ lectura.medidor.propiedad.codigo }} - {{ lectura.medidor.propiedad.nombre }}
                  </p>
                </div>
              </div>
            </TableCell>

            <!-- Tipo -->
            <TableCell>
              <Badge :variant="getTipoVariant(lectura.medidor.tipo)">
                {{ lectura.medidor.tipo === 'comercial' ? 'Comercial' : 'Domiciliario' }}
              </Badge>
            </TableCell>

            <!-- Período -->
            <TableCell>
              <span class="text-sm font-medium">{{ formatPeriodo(lectura.mes_periodo) }}</span>
            </TableCell>

            <!-- Lectura Anterior -->
            <TableCell class="text-right">
              <span class="text-sm text-muted-foreground">{{ formatNumber(lectura.lectura_anterior) }}</span>
            </TableCell>

            <!-- Lectura Actual -->
            <TableCell class="text-right">
              <span class="text-sm font-medium">{{ formatNumber(lectura.lectura_actual) }}</span>
            </TableCell>

            <!-- Consumo -->
            <TableCell class="text-right">
              <span class="text-sm font-bold" :class="getConsumoColor(lectura.consumo)">
                {{ formatNumber(lectura.consumo) }} m³
              </span>
            </TableCell>

            <!-- Fecha -->
            <TableCell>
              <span class="text-sm text-muted-foreground">{{ formatFecha(lectura.fecha_lectura) }}</span>
            </TableCell>

            <!-- Registrado por -->
            <TableCell>
              <span class="text-sm text-muted-foreground">{{ lectura.usuario.name }}</span>
            </TableCell>

            <!-- Acciones -->
            <TableCell class="text-right">
              <div class="flex items-center justify-end gap-1">
                <Button
                  variant="ghost"
                  size="icon"
                  @click="emit('edit-lectura', lectura)"
                  title="Ver detalle"
                >
                  <Eye class="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  @click="confirmarEliminar(lectura)"
                  title="Eliminar"
                  class="text-destructive hover:text-destructive"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </TableCell>
          </TableRow>

          <!-- Estado vacío -->
          <!-- Estado vacío -->
<TableRow v-if="lecturasFiltradas.length === 0">
  <TableCell colspan="9" class="h-48 text-center">
    <div class="flex flex-col items-center justify-center text-muted-foreground">
      <FileText class="h-12 w-12 mb-4" />
      <p class="text-lg font-medium">
        {{ searchTerm ? 'No se encontraron resultados' : 'No hay lecturas registradas' }}
      </p>
      <p class="text-sm mt-1">
        {{ searchTerm ? 'Intenta con otros términos de búsqueda' : 'Comienza registrando la primera lectura' }}
      </p>
    </div>
  </TableCell>
</TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Paginación -->
    <div v-if="lecturas.total > 0" class="border-t px-4 py-4">
      <div class="flex items-center justify-between">
        <div class="text-sm text-muted-foreground">
          Mostrando
          <span class="font-medium text-foreground">{{ (lecturas.current_page - 1) * lecturas.per_page + 1 }}</span>
          a
          <span class="font-medium text-foreground">{{ Math.min(lecturas.current_page * lecturas.per_page, lecturas.total) }}</span>
          de
          <span class="font-medium text-foreground">{{ lecturas.total }}</span>
          resultados
        </div>

        <div class="flex gap-1">
          <Button
            v-for="link in lecturas.links"
            :key="link.label"
            variant="outline"
            size="sm"
            :disabled="!link.url"
            :class="[
              link.active && 'bg-primary text-primary-foreground hover:bg-primary/90'
            ]"
            @click="link.url && router.visit(link.url)"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <!-- Dialog de confirmación de eliminación -->
    <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>¿Eliminar esta lectura?</AlertDialogTitle>
          <AlertDialogDescription>
            Esta acción no se puede deshacer. La lectura será eliminada permanentemente.
          </AlertDialogDescription>
        </AlertDialogHeader>

        <div v-if="lecturaAEliminar" class="my-4 p-4 bg-muted rounded-md space-y-2">
          <div class="flex items-center gap-2">
            <Gauge class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm">
              <strong>Medidor:</strong> {{ lecturaAEliminar.medidor.numero_medidor }}
            </span>
          </div>
          <p class="text-sm">
            <strong>Período:</strong> {{ formatPeriodo(lecturaAEliminar.mes_periodo) }}
          </p>
          <p class="text-sm">
            <strong>Consumo:</strong> {{ formatNumber(lecturaAEliminar.consumo) }} m³
          </p>
        </div>

        <div class="rounded-md bg-yellow-50 dark:bg-yellow-950 p-3 border border-yellow-200 dark:border-yellow-800">
          <p class="text-xs text-yellow-800 dark:text-yellow-200">
            ⚠️ Solo se puede eliminar si es la última lectura del medidor.
          </p>
        </div>

        <AlertDialogFooter>
          <AlertDialogCancel>Cancelar</AlertDialogCancel>
          <AlertDialogAction @click="eliminarLectura" class="bg-destructive hover:bg-destructive/90">
            Eliminar
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
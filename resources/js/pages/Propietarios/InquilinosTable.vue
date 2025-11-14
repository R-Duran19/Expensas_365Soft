<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Eye,
  Pencil,
  Trash2,
  Calendar,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight
} from 'lucide-vue-next';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { AlertTriangle } from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';
import axios from 'axios';

interface TipoPropiedad {
  id: number;
  nombre: string;
}

interface Propiedad {
  id: number;
  codigo: string;
  ubicacion: string;
  metros_cuadrados: number;
  tipo_propiedad: TipoPropiedad;
}

interface Inquilino {
  id: number;
  nombre_completo: string;
  ci?: string;
  telefono?: string;
  email?: string;
  activo: boolean;
  observaciones?: string;
  created_at: string;
  propiedades_count?: number;
  propiedades?: Array<Propiedad & {
    pivot: {
      fecha_inicio_contrato: string;
      fecha_fin_contrato?: string;
      es_inquilino_principal: boolean;
      observaciones?: string;
    };
  }>;
}

interface PaginatedInquilinos {
  data: Inquilino[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface Props {
  inquilinos: PaginatedInquilinos;
}

const props = defineProps<Props>();

const { showSuccess, showError } = useNotification();

const showEditDialog = ref(false);
const selectedInquilino = ref<Inquilino | null>(null);
const showConfirmDialog = ref(false);
const inquilinoToDelete = ref<Inquilino | null>(null);

const editInquilino = async (inquilino: Inquilino) => {
  try {
    const response = await axios.get(`/inquilinos/${inquilino.id}`);
    selectedInquilino.value = response.data.inquilino;
    showEditDialog.value = true;
  } catch (error) {
    showError('Error al cargar los datos del inquilino');
    console.error(error);
  }
};

const deleteInquilino = (inquilino: Inquilino) => {
  inquilinoToDelete.value = inquilino;
  showConfirmDialog.value = true;
};

const confirmDelete = () => {
  if (!inquilinoToDelete.value) return;

  router.delete(`/inquilinos/${inquilinoToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showSuccess('Inquilino eliminado exitosamente');
      showConfirmDialog.value = false;
      inquilinoToDelete.value = null;
    },
    onError: (errors) => {
      const errorMessage = errors.error || 'No se pudo eliminar el inquilino';
      showError(errorMessage);
    }
  });
};

const cancelDelete = () => {
  showConfirmDialog.value = false;
  inquilinoToDelete.value = null;
};

const goToPage = (page: number) => {
  router.get('/propietarios', {
    page,
    activeTab: 'inquilinos'
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const contratoVigente = (inquilino: Inquilino): boolean => {
  if (!inquilino.propiedades || inquilino.propiedades.length === 0) return false;

  return inquilino.propiedades.some(prop => {
    const hoy = new Date();
    const inicio = new Date(prop.pivot.fecha_inicio_contrato);

    if (inicio > hoy) return false;

    if (prop.pivot.fecha_fin_contrato) {
      const fin = new Date(prop.pivot.fecha_fin_contrato);
      if (fin < hoy) return false;
    }

    return true;
  });
};

const getPropiedadesInfo = (inquilino: Inquilino): string => {
  if (!inquilino.propiedades || inquilino.propiedades.length === 0) {
    return 'Sin propiedades';
  }

  const activas = inquilino.propiedades.filter(prop => {
    const hoy = new Date();
    const inicio = new Date(prop.pivot.fecha_inicio_contrato);
    if (inicio > hoy) return false;

    if (prop.pivot.fecha_fin_contrato) {
      const fin = new Date(prop.pivot.fecha_fin_contrato);
      if (fin < hoy) return false;
    }

    return true;
  });

  if (activas.length === 0) {
    return 'Sin contratos vigentes';
  }

  return `${activas.length} ${activas.length === 1 ? 'propiedad' : 'propiedades'} (${activas.map(p => p.codigo).join(', ')})`;
};

const closeEditDialog = () => {
  showEditDialog.value = false;
  selectedInquilino.value = null;
};
</script>

<template>
  <div class="space-y-4">
    <!-- Vista Desktop: Tabla tradicional -->
    <div class="hidden lg:block">
      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nombre Completo</TableHead>
              <TableHead>CI</TableHead>
              <TableHead>Propiedades</TableHead>
              <TableHead class="text-center">Teléfono</TableHead>
              <TableHead class="text-center">Contratos Activos</TableHead>
              <TableHead class="text-center">Estado</TableHead>
              <TableHead class="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="inquilinos.data.length === 0">
              <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                No se encontraron inquilinos
              </TableCell>
            </TableRow>
            <TableRow v-for="inquilino in inquilinos.data" :key="inquilino.id">
              <TableCell class="font-medium">
                {{ inquilino.nombre_completo }}
              </TableCell>
              <TableCell>
                {{ inquilino.ci || '-' }}
              </TableCell>
              <TableCell>
                <div class="space-y-1">
                  <div class="font-medium">{{ getPropiedadesInfo(inquilino) }}</div>
                  <div class="text-sm text-muted-foreground">
                    Total: {{ inquilino.propiedades_count || 0 }} {{ (inquilino.propiedades_count || 0) === 1 ? 'propiedad' : 'propiedades' }}
                  </div>
                </div>
              </TableCell>
              <TableCell class="text-center">
                {{ inquilino.telefono || '-' }}
              </TableCell>
              <TableCell class="text-center">
                <div class="space-y-1">
                  <div v-if="inquilino.propiedades && inquilino.propiedades.length > 0" class="text-sm">
                    <div v-for="(prop, index) in inquilino.propiedades.slice(0, 2)" :key="prop.id" class="space-y-1">
                      <div class="font-medium">{{ prop.codigo }}</div>
                      <div class="text-xs text-muted-foreground">
                        {{ formatDate(prop.pivot.fecha_inicio_contrato) }}
                        <span v-if="prop.pivot.fecha_fin_contrato">
                          - {{ formatDate(prop.pivot.fecha_fin_contrato) }}
                        </span>
                        <span v-else class="text-xs">Indefinido</span>
                        <Badge v-if="prop.pivot.es_inquilino_principal" variant="secondary" class="ml-1 text-xs">
                          Principal
                        </Badge>
                      </div>
                    </div>
                    <div v-if="inquilino.propiedades.length > 2" class="text-xs text-muted-foreground">
                      +{{ inquilino.propiedades.length - 2 }} más
                    </div>
                  </div>
                  <div v-else class="text-sm text-muted-foreground">
                    Sin contratos
                  </div>
                </div>
              </TableCell>
              <TableCell class="text-center">
                <div class="space-y-1">
                  <Badge :variant="inquilino.activo ? 'default' : 'destructive'">
                    {{ inquilino.activo ? 'Activo' : 'Inactivo' }}
                  </Badge>
                  <Badge
                    v-if="inquilino.activo"
                    :variant="contratoVigente(inquilino) ? 'secondary' : 'outline'"
                  >
                    {{ contratoVigente(inquilino) ? 'Vigente' : 'Vencido' }}
                  </Badge>
                </div>
              </TableCell>
              <TableCell class="text-right">
                <div class="flex justify-end gap-2">
                  <Button
                    @click="editInquilino(inquilino)"
                    variant="ghost"
                    size="icon"
                    title="Editar"
                  >
                    <Pencil class="h-4 w-4" />
                  </Button>
                  <Button
                    @click="deleteInquilino(inquilino)"
                    variant="ghost"
                    size="icon"
                    title="Eliminar"
                    class="text-destructive hover:text-destructive"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>

    <!-- Vista Mobile: Cards -->
    <div class="lg:hidden space-y-4">
      <div v-if="inquilinos.data.length === 0" class="text-center py-8 text-muted-foreground">
        No se encontraron inquilinos
      </div>

      <div
        v-for="inquilino in inquilinos.data"
        :key="inquilino.id"
        class="bg-white dark:bg-gray-800 rounded-lg border p-4 space-y-3"
      >
        <!-- Header con nombre y estado -->
        <div class="flex items-start justify-between">
          <div class="flex-1 min-w-0">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
              {{ inquilino.nombre_completo }}
            </h3>
            <div class="mt-1 flex items-center gap-2 flex-wrap">
              <Badge :variant="inquilino.activo ? 'default' : 'destructive'" class="text-xs">
                {{ inquilino.activo ? 'Activo' : 'Inactivo' }}
              </Badge>
              <Badge
                v-if="inquilino.activo"
                :variant="contratoVigente(inquilino) ? 'secondary' : 'outline'"
                class="text-xs"
              >
                {{ contratoVigente(inquilino) ? 'Vigente' : 'Vencido' }}
              </Badge>
              <Badge variant="outline" class="text-xs">
                {{ inquilino.propiedades_count || 0 }} propiedades
              </Badge>
            </div>
          </div>
        </div>

        <!-- Información de contacto -->
        <div class="grid grid-cols-1 gap-2 text-sm">
          <div v-if="inquilino.ci" class="flex">
            <span class="text-gray-500 dark:text-gray-400 w-12">CI:</span>
            <span class="text-gray-900 dark:text-white truncate">{{ inquilino.ci }}</span>
          </div>
          <div v-if="inquilino.telefono" class="flex">
            <span class="text-gray-500 dark:text-gray-400 w-12">Tel:</span>
            <span class="text-gray-900 dark:text-white truncate">{{ inquilino.telefono }}</span>
          </div>
        </div>

        <!-- Propiedades y contratos -->
        <div class="space-y-2">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white">Propiedades y Contratos</h4>
          <div v-if="inquilino.propiedades && inquilino.propiedades.length > 0" class="space-y-2">
            <div
              v-for="prop in inquilino.propiedades.slice(0, 3)"
              :key="prop.id"
              class="bg-gray-50 dark:bg-gray-700 rounded p-2 text-xs"
            >
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ prop.codigo }}</span>
                <div class="flex items-center gap-1">
                  <Badge v-if="prop.pivot.es_inquilino_principal" variant="secondary" class="text-[10px] px-1 py-0">
                    Principal
                  </Badge>
                </div>
              </div>
              <div class="text-gray-600 dark:text-gray-300 mt-1">
                {{ formatDate(prop.pivot.fecha_inicio_contrato) }}
                <span v-if="prop.pivot.fecha_fin_contrato">
                  - {{ formatDate(prop.pivot.fecha_fin_contrato) }}
                </span>
                <span v-else> - Indefinido</span>
              </div>
            </div>
            <div v-if="inquilino.propiedades.length > 3" class="text-xs text-gray-500 dark:text-gray-400">
              +{{ inquilino.propiedades.length - 3 }} propiedades más
            </div>
          </div>
          <div v-else class="text-xs text-gray-500 dark:text-gray-400 italic">
            Sin propiedades asignadas
          </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
          <Button
            @click="editInquilino(inquilino)"
            variant="outline"
            size="sm"
            class="flex-1"
          >
            <Pencil class="h-3 w-3 mr-1" />
            <span class="text-xs">Editar</span>
          </Button>
          <Button
            @click="deleteInquilino(inquilino)"
            variant="ghost"
            size="sm"
            class="text-destructive hover:text-destructive px-3"
          >
            <Trash2 class="h-3 w-3 mr-1" />
            <span class="text-xs">Eliminar</span>
          </Button>
        </div>
      </div>
    </div>

    <!-- Paginación responsive -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t">
      <!-- Desktop: info completa -->
      <div class="hidden sm:block text-sm text-muted-foreground">
        Mostrando {{ (inquilinos.current_page - 1) * inquilinos.per_page + 1 }}
        a {{ Math.min(inquilinos.current_page * inquilinos.per_page, inquilinos.total) }}
        de {{ inquilinos.total }} resultados
      </div>

      <!-- Mobile: info compacta -->
      <div class="sm:hidden text-sm text-muted-foreground text-center">
        {{ (inquilinos.current_page - 1) * inquilinos.per_page + 1 }}-{{
          Math.min(inquilinos.current_page * inquilinos.per_page, inquilinos.total)
        }} de {{ inquilinos.total }}
      </div>

      <!-- Controles de paginación -->
      <div class="flex items-center gap-1 sm:gap-2">
        <Button
          @click="goToPage(1)"
          variant="outline"
          size="sm"
          :disabled="inquilinos.current_page === 1"
          class="hidden sm:inline-flex"
        >
          <ChevronsLeft class="h-4 w-4" />
        </Button>
        <Button
          @click="goToPage(inquilinos.current_page - 1)"
          variant="outline"
          size="sm"
          :disabled="inquilinos.current_page === 1"
        >
          <ChevronLeft class="h-4 w-4" />
        </Button>

        <span class="text-sm px-2 py-1 min-w-[80px] text-center">
          <span class="hidden sm:inline">Página {{ inquilinos.current_page }} de {{ inquilinos.last_page }}</span>
          <span class="sm:hidden">{{ inquilinos.current_page }}/{{ inquilinos.last_page }}</span>
        </span>

        <Button
          @click="goToPage(inquilinos.current_page + 1)"
          variant="outline"
          size="sm"
          :disabled="inquilinos.current_page === inquilinos.last_page"
        >
          <ChevronRight class="h-4 w-4" />
        </Button>
        <Button
          @click="goToPage(inquilinos.last_page)"
          variant="outline"
          size="sm"
          :disabled="inquilinos.current_page === inquilinos.last_page"
          class="hidden sm:inline-flex"
        >
          <ChevronsRight class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Diálogo de confirmación para eliminar -->
    <Dialog v-model:open="showConfirmDialog">
      <DialogContent class="sm:max-w-[425px] max-w-[95vw] mx-4 dark:bg-gray-900">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-lg">
            <AlertTriangle class="h-5 w-5 text-red-500" />
            Eliminar Inquilino
          </DialogTitle>
        </DialogHeader>

        <div class="py-4">
          <p class="text-sm text-gray-700 dark:text-gray-300">
            ¿Está seguro de eliminar al inquilino
            <span class="font-semibold">
              {{ inquilinoToDelete?.nombre_completo }}
            </span>?
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            Esta acción no se puede deshacer.
          </p>
        </div>

        <DialogFooter class="flex-col sm:flex-row gap-2">
          <Button
            variant="outline"
            @click="cancelDelete"
            class="w-full sm:w-auto"
          >
            Cancelar
          </Button>
          <Button
            variant="destructive"
            @click="confirmDelete"
            class="w-full sm:w-auto"
          >
            Eliminar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
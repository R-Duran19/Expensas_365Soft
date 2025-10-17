<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Eye, 
  Pencil, 
  Trash2, 
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
import PropietarioFormDialog from './PropietarioFormDialog.vue';
import { useNotification } from '@/composables/useNotification';
import { useConfirm } from 'primevue/useconfirm';
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
  pivot?: {
    fecha_inicio: string;
    es_propietario_principal: boolean;
  };
}

interface Propietario {
  id: number;
  nombre_completo: string;
  ci?: string;
  nit?: string;
  telefono?: string;
  email?: string;
  direccion_externa?: string;
  fecha_registro: string;
  activo: boolean;
  observaciones?: string;
  propiedades?: Propiedad[];
  propiedades_count?: number;
}

interface PaginatedPropietarios {
  data: Propietario[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface Props {
  propietarios: PaginatedPropietarios;
  selectedPropietario?: Propietario | null;
  showPropiedadesDialog?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:show-propiedades-dialog': [value: boolean];
  'update:selected-propietario': [value: Propietario | null];
}>();

const { showSuccess, showError } = useNotification();
const confirm = useConfirm();

const showEditDialog = ref(false);

const localSelectedPropietario = ref<Propietario | null>(null);
const localShowPropiedadesDialog = ref(false);

// Sincronizar con las props del padre
watch(() => props.selectedPropietario, (newVal) => {
  localSelectedPropietario.value = newVal;
});

watch(() => props.showPropiedadesDialog, (newVal) => {
  localShowPropiedadesDialog.value = newVal ?? false;
});

// Emitir cambios al padre
watch(localShowPropiedadesDialog, (newVal) => {
  emit('update:show-propiedades-dialog', newVal);
});

watch(localSelectedPropietario, (newVal) => {
  emit('update:selected-propietario', newVal);
});

const editPropietario = async (propietario: Propietario) => {
  try {
    // Hacer una petición para obtener los datos completos del propietario
    const response = await axios.get(`/propietarios/${propietario.id}`);
    localSelectedPropietario.value = response.data.propietario;
    showEditDialog.value = true;
  } catch (error) {
    showError('Error al cargar los datos del propietario');
    console.error(error);
  }
};

const viewPropiedades = (propietario: Propietario) => {
  router.get(`/propietarios/${propietario.id}/propiedades`);
};

const deletePropietario = (propietario: Propietario) => {
  confirm.require({
    message: `¿Estás seguro de eliminar a ${propietario.nombre_completo}?`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    accept: () => {
      router.delete(`/propietarios/${propietario.id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          // Verificar si hay errores en la respuesta
          if (page.props.errors && page.props.errors.error) {
            showError(page.props.errors.error);
          } else {
            showSuccess('Propietario eliminado exitosamente');
          }
        },
        onError: (errors) => {
          const errorMessage = errors.error || 'No se pudo eliminar el propietario';
          showError(errorMessage);
        }
      });
    }
  });
};

const goToPage = (page: number) => {
  router.get('/propietarios', { page }, {
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

const closePropiedadesDialog = () => {
  localShowPropiedadesDialog.value = false;
  localSelectedPropietario.value = null;
};

const closeEditDialog = () => {
  showEditDialog.value = false;
  localSelectedPropietario.value = null;
};
</script>

<template>
  <div class="space-y-4">
    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Nombre Completo</TableHead>
            <TableHead>CI</TableHead>
            <TableHead>Teléfono</TableHead>
            <TableHead>Email</TableHead>
            <TableHead class="text-center">Propiedades</TableHead>
            <TableHead class="text-center">Estado</TableHead>
            <TableHead class="text-center">Fecha Registro</TableHead>
            <TableHead class="text-right">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="propietarios.data.length === 0">
            <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
              No se encontraron propietarios
            </TableCell>
          </TableRow>
          <TableRow v-for="propietario in propietarios.data" :key="propietario.id">
            <TableCell class="font-medium">
              {{ propietario.nombre_completo }}
            </TableCell>
            <TableCell>
              {{ propietario.ci || '-' }}
            </TableCell>
            <TableCell>
              {{ propietario.telefono || '-' }}
            </TableCell>
            <TableCell>
              {{ propietario.email || '-' }}
            </TableCell>
            <TableCell class="text-center">
              <Badge variant="secondary">
                {{ propietario.propiedades_count || 0 }}
              </Badge>
            </TableCell>
            <TableCell class="text-center">
              <Badge :variant="propietario.activo ? 'default' : 'destructive'">
                {{ propietario.activo ? 'Activo' : 'Inactivo' }}
              </Badge>
            </TableCell>
            <TableCell class="text-center">
              {{ formatDate(propietario.fecha_registro) }}
            </TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button 
                  @click="viewPropiedades(propietario)"
                  variant="ghost" 
                  size="icon"
                  title="Ver propiedades"
                >
                  <Eye class="h-4 w-4" />
                </Button>
                <Button 
                  @click="editPropietario(propietario)"
                  variant="ghost" 
                  size="icon"
                  title="Editar"
                >
                  <Pencil class="h-4 w-4" />
                </Button>
                <Button 
                  @click="deletePropietario(propietario)"
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

    <!-- Paginación -->
    <div class="flex items-center justify-between">
      <div class="text-sm text-muted-foreground">
        Mostrando {{ (propietarios.current_page - 1) * propietarios.per_page + 1 }} 
        a {{ Math.min(propietarios.current_page * propietarios.per_page, propietarios.total) }} 
        de {{ propietarios.total }} resultados
      </div>
      <div class="flex items-center gap-2">
        <Button
          @click="goToPage(1)"
          variant="outline"
          size="icon"
          :disabled="propietarios.current_page === 1"
        >
          <ChevronsLeft class="h-4 w-4" />
        </Button>
        <Button
          @click="goToPage(propietarios.current_page - 1)"
          variant="outline"
          size="icon"
          :disabled="propietarios.current_page === 1"
        >
          <ChevronLeft class="h-4 w-4" />
        </Button>
        <span class="text-sm">
          Página {{ propietarios.current_page }} de {{ propietarios.last_page }}
        </span>
        <Button
          @click="goToPage(propietarios.current_page + 1)"
          variant="outline"
          size="icon"
          :disabled="propietarios.current_page === propietarios.last_page"
        >
          <ChevronRight class="h-4 w-4" />
        </Button>
        <Button
          @click="goToPage(propietarios.last_page)"
          variant="outline"
          size="icon"
          :disabled="propietarios.current_page === propietarios.last_page"
        >
          <ChevronsRight class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Dialogs -->
    <PropietarioFormDialog 
      v-model:open="showEditDialog"
      :propietario="localSelectedPropietario"
      @close="closeEditDialog"
    />    
  </div>
</template>
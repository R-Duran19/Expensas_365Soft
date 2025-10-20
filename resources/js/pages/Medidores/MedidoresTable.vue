<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Search, Edit, Trash2, Gauge, Building2, Plus } from 'lucide-vue-next';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useConfirm } from 'primevue/useconfirm';
import { router } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';

interface TipoPropiedad {
  id: number;
  nombre: string;
  requiere_medidor: boolean;
}

interface Propiedad {
  id: number;
  codigo: string;
  ubicacion: string;
  tipo_propiedad: TipoPropiedad;
}

interface Lectura {
  id: number;
  lectura_actual: number;
  fecha_lectura: string;
  mes_periodo: string;
}

interface Medidor {
  id: number;
  numero_medidor: string;
  ubicacion: string | null;
  activo: boolean;
  observaciones: string | null;
  propiedad: Propiedad;
  ultima_lectura?: Lectura;
}

interface Props {
  medidores: Medidor[];
}

const props = defineProps<Props>();
const { showSuccess, showError } = useNotification();
const confirm = useConfirm();

defineEmits<{
  editMedidor: [medidor: Medidor];
  createMedidor: [];
}>();

const searchTerm = ref('');

// Función para obtener el tipo de medidor basado en el tipo de propiedad
const getTipoMedidor = (medidor: Medidor): 'domiciliario' | 'comercial' => {
  const tipoPropiedad = medidor.propiedad.tipo_propiedad;
  
  // IDs comerciales: 3 y 4 (Local Comercial, Oficina)
  // ID domiciliario: 5 (Departamento)
  const tiposComerciales = [3, 4];
  const tiposDomiciliarios = [5];
  
  if (tiposComerciales.includes(tipoPropiedad.id)) {
    return 'comercial';
  }
  
  if (tiposDomiciliarios.includes(tipoPropiedad.id)) {
    return 'domiciliario';
  }
  
  // Por defecto basarse en el nombre si el ID no coincide
  const nombreLower = tipoPropiedad.nombre.toLowerCase();
  if (nombreLower.includes('comercial') || nombreLower.includes('oficina')) {
    return 'comercial';
  }
  
  return 'domiciliario';
};

// Filtrar medidores
const medidoresFiltrados = computed(() => {
  if (!searchTerm.value) return props.medidores;
  
  const search = searchTerm.value.toLowerCase();
  return props.medidores.filter(medidor =>
    medidor.numero_medidor.toLowerCase().includes(search) ||
    medidor.propiedad.codigo.toLowerCase().includes(search) ||
    medidor.propiedad.ubicacion.toLowerCase().includes(search) ||
    medidor.propiedad.tipo_propiedad.nombre.toLowerCase().includes(search)
  );
});

const eliminarMedidor = (medidor: Medidor) => {
  confirm.require({
    message: `¿Estás seguro de eliminar el medidor ${medidor.numero_medidor}?`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    accept: () => {
      router.delete(`/medidores/${medidor.id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          showSuccess('Medidor eliminado exitosamente');
        },
        onError: (errors) => {
          const errorMessage = errors.error || 'No se pudo eliminar el medidor';
          showError(errorMessage);
        }
      });
    }
  });
};

const getTipoBadgeVariant = (tipo: 'domiciliario' | 'comercial') => {
  return tipo === 'domiciliario' ? 'default' : 'secondary';
};

const getTipoText = (tipo: 'domiciliario' | 'comercial') => {
  return tipo === 'domiciliario' ? 'Domiciliario' : 'Comercial';
};
</script>

<template>
  <div class="space-y-4">
    <!-- Buscador -->
    <div class="flex gap-4">
      <div class="flex-1 relative">
        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
        <Input
          v-model="searchTerm"
          placeholder="Buscar por número de medidor, código, ubicación o tipo de propiedad..."
          class="pl-9"
        />
      </div>
    </div>

    <!-- Tabla de Medidores -->
    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Número de Medidor</TableHead>
            <TableHead>Propiedad</TableHead>
            <TableHead>Ubicación</TableHead>
            <TableHead>Tipo</TableHead>
            <TableHead>Última Lectura</TableHead>
            <TableHead class="text-center">Estado</TableHead>
            <TableHead class="text-right">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="medidoresFiltrados.length === 0">
            <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
              <div class="flex flex-col items-center">
                <Building2 class="h-8 w-8 mb-2" />
                <p>{{ searchTerm ? 'No se encontraron medidores' : 'No hay medidores registrados' }}</p>
                <Button 
                  v-if="!searchTerm"
                  @click="$emit('createMedidor')" 
                  variant="outline" 
                  class="mt-2"
                >
                  <Plus class="h-4 w-4 mr-2" />
                  Crear primer medidor
                </Button>
              </div>
            </TableCell>
          </TableRow>
          
          <TableRow v-for="medidor in medidoresFiltrados" :key="medidor.id">
            <TableCell class="font-medium">
              <div class="flex items-center gap-2">
                <Gauge class="h-4 w-4 text-muted-foreground" />
                {{ medidor.numero_medidor }}
              </div>
            </TableCell>
            <TableCell>
              <div>
                <p class="font-medium">{{ medidor.propiedad.codigo }}</p>
                <p class="text-sm text-muted-foreground">
                  {{ medidor.propiedad.tipo_propiedad.nombre }}
                </p>
              </div>
            </TableCell>
            <TableCell>
              <span v-if="medidor.ubicacion" class="text-sm">
                {{ medidor.ubicacion }}
              </span>
              <span v-else class="text-sm text-muted-foreground">
                {{ medidor.propiedad.ubicacion }}
              </span>
            </TableCell>
            <TableCell>
              <Badge :variant="getTipoBadgeVariant(getTipoMedidor(medidor))">
                {{ getTipoText(getTipoMedidor(medidor)) }}
              </Badge>
            </TableCell>
            <TableCell>
              <div v-if="medidor.ultima_lectura">
                <p class="font-medium">{{ medidor.ultima_lectura.lectura_actual }} m³</p>
                <p class="text-sm text-muted-foreground">
                  {{ new Date(medidor.ultima_lectura.fecha_lectura).toLocaleDateString('es-BO') }}
                </p>
              </div>
              <span v-else class="text-muted-foreground text-sm">Sin lecturas</span>
            </TableCell>
            <TableCell class="text-center">
              <Badge :variant="medidor.activo ? 'default' : 'destructive'">
                {{ medidor.activo ? 'Activo' : 'Inactivo' }}
              </Badge>
            </TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button 
                  @click="$emit('editMedidor', medidor)"
                  variant="ghost" 
                  size="icon"
                  title="Editar"
                >
                  <Edit class="h-4 w-4" />
                </Button>
                <Button 
                  @click="eliminarMedidor(medidor)"
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
</template>
<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { useNotification } from '@/composables/useNotification';
import { Gauge, Building2, Info, X } from 'lucide-vue-next';

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

interface Medidor {
  id: number;
  numero_medidor: string;
  ubicacion: string | null;
  propiedad_id: number;
  activo: boolean;
  observaciones: string | null;
  propiedad: Propiedad;
}

interface Props {
  open: boolean;
  medidor?: Medidor | null;
  propiedades: Propiedad[];
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'close']);

const { showSuccess, showError } = useNotification();
const isEditing = computed(() => !!props.medidor);

const form = useForm({
  numero_medidor: '',
  ubicacion: '',
  propiedad_id: '',
  activo: true,
  observaciones: ''
});

// Para el selector nativo
const propiedadSeleccionadaObj = ref<Propiedad | null>(null);
const searchQuery = ref('');
const showDropdown = ref(false);

// Propiedades disponibles (sin medidor asignado)
const propiedadesDisponibles = computed(() => {
  return props.propiedades;
});

// Filtrar propiedades basado en búsqueda
const propiedadesFiltradas = computed(() => {
  if (!searchQuery.value) {
    return propiedadesDisponibles.value.slice(0, 20);
  }
  
  const query = searchQuery.value.toLowerCase();
  return propiedadesDisponibles.value.filter((propiedad) => {
    return (
      propiedad.codigo.toLowerCase().includes(query) ||
      propiedad.ubicacion.toLowerCase().includes(query) ||
      propiedad.tipo_propiedad.nombre.toLowerCase().includes(query)
    );
  }).slice(0, 20);
});

// Manejar búsqueda
const handleSearch = () => {
  showDropdown.value = true;
};

// Seleccionar propiedad
const selectPropiedad = (propiedad: Propiedad) => {
  propiedadSeleccionadaObj.value = propiedad;
  form.propiedad_id = String(propiedad.id);
  searchQuery.value = '';
  showDropdown.value = false;
};

// Limpiar selección
const clearPropiedad = () => {
  propiedadSeleccionadaObj.value = null;
  form.propiedad_id = '';
  searchQuery.value = '';
  showDropdown.value = false;
};

// Calcular el tipo de medidor basado en la propiedad seleccionada
const tipoMedidorCalculado = computed(() => {
  if (!propiedadSeleccionadaObj.value) return null;
  
  const tipoPropiedad = propiedadSeleccionadaObj.value.tipo_propiedad;
  const tiposComerciales = [3, 4]; // Local Comercial, Oficina
  const tiposDomiciliarios = [5]; // Departamento
  
  if (tiposComerciales.includes(tipoPropiedad.id)) {
    return { tipo: 'comercial', label: 'Comercial', factor: '3.5' };
  }
  
  if (tiposDomiciliarios.includes(tipoPropiedad.id)) {
    return { tipo: 'domiciliario', label: 'Domiciliario', factor: '2.1' };
  }
  
  // Fallback por nombre
  const nombreLower = tipoPropiedad.nombre.toLowerCase();
  if (nombreLower.includes('comercial') || nombreLower.includes('oficina')) {
    return { tipo: 'comercial', label: 'Comercial', factor: '3.5' };
  }
  
  return { tipo: 'domiciliario', label: 'Domiciliario', factor: '2.1' };
});

// Cerrar dropdown al hacer click fuera
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (!target.closest('#propiedad_search') && !target.closest('.absolute')) {
    showDropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

watch(() => props.open, (newVal) => {
  if (newVal) {
    if (props.medidor) {
      // Modo edición
      form.numero_medidor = props.medidor.numero_medidor;
      form.ubicacion = props.medidor.ubicacion || '';
      form.propiedad_id = String(props.medidor.propiedad_id);
      form.activo = props.medidor.activo;
      form.observaciones = props.medidor.observaciones || '';
      
      // Establecer la propiedad seleccionada
      propiedadSeleccionadaObj.value = props.medidor.propiedad;
      searchQuery.value = '';
    } else {
      // Modo creación
      form.reset();
      form.activo = true;
      propiedadSeleccionadaObj.value = null;
      searchQuery.value = '';
    }
    showDropdown.value = false;
  }
});

const closeDialog = () => {
  emit('update:open', false);
  emit('close');
  form.reset();
  form.clearErrors();
  propiedadSeleccionadaObj.value = null;
  searchQuery.value = '';
  showDropdown.value = false;
};

const submit = () => {
  if (!form.propiedad_id) {
    showError('Debes seleccionar una propiedad');
    return;
  }

  const url = isEditing.value 
    ? `/medidores/${props.medidor!.id}`
    : '/medidores';
  
  const method = isEditing.value ? 'put' : 'post';

  form[method](url, {
    onSuccess: () => {
      showSuccess(
        isEditing.value 
          ? 'Medidor actualizado exitosamente' 
          : 'Medidor creado exitosamente'
      );
      closeDialog();
    },
    onError: (errors) => {
      showError('Por favor corrige los errores en el formulario');
      console.error(errors);
    }
  });
};
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)" :modal="false" closable="false">
    <DialogContent class="sm:max-w-[550px]">
      <DialogHeader>
        <DialogTitle>
          <div class="flex items-center gap-2">
            <Gauge class="h-5 w-5" />
            {{ isEditing ? 'Editar Medidor' : 'Nuevo Medidor' }}
          </div>
        </DialogTitle>
        <DialogDescription>
          {{ isEditing ? 'Actualiza la información del medidor' : 'Completa los datos para registrar un nuevo medidor' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
        <!-- Número de Medidor -->
        <div>
          <Label for="numero_medidor">
            Número de Medidor <span class="text-destructive">*</span>
          </Label>
          <Input
            id="numero_medidor"
            v-model="form.numero_medidor"
            :class="{ 'border-destructive': form.errors.numero_medidor }"
            placeholder="Ej: MED-001"
            required
          />
          <p v-if="form.errors.numero_medidor" class="text-sm text-destructive mt-1">
            {{ form.errors.numero_medidor }}
          </p>
        </div>

        <!-- Selector de Propiedad NATIVO -->
        <div>
          <Label for="propiedad_search">
            Propiedad <span class="text-destructive">*</span>
          </Label>
          
          <div class="relative">
            <!-- Input de búsqueda -->
            <Input
              id="propiedad_search"
              v-model="searchQuery"
              @input="handleSearch"
              @focus="showDropdown = true"
              :placeholder="propiedadSeleccionadaObj ? propiedadSeleccionadaObj.codigo : 'Buscar propiedad...'"
              :disabled="isEditing"
              autocomplete="off"
              :class="{ 'border-destructive': form.errors.propiedad_id }"
            />
            
            <!-- Dropdown de resultados -->
            <div
              v-if="showDropdown && propiedadesFiltradas.length > 0"
              class="absolute z-50 w-full mt-1 bg-white border-2 border-border rounded-lg shadow-lg max-h-[300px] overflow-y-auto"
            >
              <div
                v-for="propiedad in propiedadesFiltradas"
                :key="propiedad.id"
                @click="selectPropiedad(propiedad)"
                class="flex items-start gap-2 p-3 cursor-pointer hover:bg-accent transition-colors"
              >
                <Building2 class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-sm truncate">{{ propiedad.codigo }}</p>
                  <p class="text-xs text-muted-foreground truncate">{{ propiedad.ubicacion }}</p>
                  <p class="text-xs text-muted-foreground">{{ propiedad.tipo_propiedad.nombre }}</p>
                </div>
              </div>
            </div>
            
            <!-- Mensaje cuando no hay resultados -->
            <div
              v-if="showDropdown && searchQuery && propiedadesFiltradas.length === 0"
              class="absolute z-50 w-full mt-1 bg-white border-2 border-border rounded-lg shadow-lg p-3 text-sm text-muted-foreground text-center"
            >
              No se encontraron propiedades
            </div>
          </div>
          
          <!-- Propiedad seleccionada -->
          <div v-if="propiedadSeleccionadaObj && !isEditing" class="mt-2 p-2 bg-muted rounded-md">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Building2 class="h-4 w-4 text-muted-foreground" />
                <div>
                  <p class="text-sm font-medium">{{ propiedadSeleccionadaObj.codigo }}</p>
                  <p class="text-xs text-muted-foreground">{{ propiedadSeleccionadaObj.ubicacion }}</p>
                </div>
              </div>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                @click="clearPropiedad"
                class="h-6 w-6"
              >
                <X class="h-4 w-4" />
              </Button>
            </div>
          </div>
          
          <p v-if="form.errors.propiedad_id" class="text-sm text-destructive mt-1">
            {{ form.errors.propiedad_id }}
          </p>
          <p v-if="!isEditing" class="text-xs text-muted-foreground mt-1">
            Solo se muestran propiedades que requieren medidor
          </p>
        </div>

        <!-- Tipo de Medidor (calculado automáticamente) -->
        <div v-if="tipoMedidorCalculado" class="bg-muted/50 p-3 rounded-md border">
          <div class="flex items-start gap-2">
            <Info class="h-4 w-4 text-muted-foreground mt-0.5 flex-shrink-0" />
            <div class="flex-1">
              <p class="text-sm font-medium">Tipo de Medidor (automático)</p>
              <div class="flex items-center gap-2 mt-1">
                <Badge :variant="tipoMedidorCalculado.tipo === 'domiciliario' ? 'default' : 'secondary'">
                  {{ tipoMedidorCalculado.label }}
                </Badge>
                <span class="text-xs text-muted-foreground">
                  Factor: {{ tipoMedidorCalculado.factor }}
                </span>
              </div>
              <p class="text-xs text-muted-foreground mt-1">
                Se calcula según el tipo de propiedad seleccionada
              </p>
            </div>
          </div>
        </div>

        <!-- Ubicación -->
        <div>
          <Label for="ubicacion">Ubicación Específica del Medidor</Label>
          <Input
            id="ubicacion"
            v-model="form.ubicacion"
            placeholder="Ej: Piso 5 - Pasillo izquierdo"
            :class="{ 'border-destructive': form.errors.ubicacion }"
          />
          <p v-if="form.errors.ubicacion" class="text-sm text-destructive mt-1">
            {{ form.errors.ubicacion }}
          </p>
          <p class="text-xs text-muted-foreground mt-1">
            Opcional. Si no se especifica, se usará la ubicación de la propiedad
          </p>
        </div>

        <!-- Observaciones -->
        <div>
          <Label for="observaciones">Observaciones</Label>
          <Textarea
            id="observaciones"
            v-model="form.observaciones"
            placeholder="Notas adicionales sobre el medidor..."
            rows="3"
            :class="{ 'border-destructive': form.errors.observaciones }"
          />
          <p v-if="form.errors.observaciones" class="text-sm text-destructive mt-1">
            {{ form.errors.observaciones }}
          </p>
        </div>

        <!-- Estado (solo en edición) -->
        <div v-if="isEditing" class="flex items-center space-x-2">
          <input
            id="activo"
            v-model="form.activo"
            type="checkbox"
            class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary"
          />
          <Label for="activo" class="cursor-pointer">
            Medidor activo
          </Label>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="closeDialog">
            Cancelar
          </Button>
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
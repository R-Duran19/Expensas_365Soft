<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ isEditing ? 'Editar Propietario' : 'Nuevo Propietario' }}
        </DialogTitle>
        <DialogDescription>
          {{ isEditing ? 'Actualiza la información del propietario' : 'Completa los datos para registrar un nuevo propietario' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Información Básica del Propietario -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold">Información Personal</h3>
          
          <div class="grid grid-cols-2 gap-4">
            <!-- Nombre Completo -->
            <div class="col-span-2">
              <Label for="nombre_completo">
                Nombre Completo <span class="text-destructive">*</span>
              </Label>
              <Input
                id="nombre_completo"
                v-model="form.nombre_completo"
                :class="{ 'border-destructive': form.errors.nombre_completo }"
                placeholder="Ej: Juan Pérez García"
                required
              />
              <p v-if="form.errors.nombre_completo" class="text-sm text-destructive mt-1">
                {{ form.errors.nombre_completo }}
              </p>
            </div>

            <!-- CI -->
            <div>
              <Label for="ci">Carnet de Identidad</Label>
              <Input
                id="ci"
                v-model="form.ci"
                placeholder="Ej: 1234567 LP"
                :class="{ 'border-destructive': form.errors.ci }"
              />
              <p v-if="form.errors.ci" class="text-sm text-destructive mt-1">
                {{ form.errors.ci }}
              </p>
            </div>

            <!-- NIT -->
            <div>
              <Label for="nit">NIT</Label>
              <Input
                id="nit"
                v-model="form.nit"
                placeholder="Ej: 1234567012"
              />
            </div>

            <!-- Teléfono -->
            <div>
              <Label for="telefono">Teléfono</Label>
              <Input
                id="telefono"
                v-model="form.telefono"
                placeholder="Ej: 77123456"
              />
            </div>

            <!-- Email -->
            <div>
              <Label for="email">Email</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="ejemplo@correo.com"
                :class="{ 'border-destructive': form.errors.email }"
              />
              <p v-if="form.errors.email" class="text-sm text-destructive mt-1">
                {{ form.errors.email }}
              </p>
            </div>

            <!-- Fecha Registro -->
            <div>
              <Label for="fecha_registro">Fecha de Registro</Label>
              <Input
                id="fecha_registro"
                v-model="form.fecha_registro"
                type="date"
              />
            </div>

            <!-- Dirección Externa -->
            <div class="col-span-2">
              <Label for="direccion_externa">Dirección Externa</Label>
              <Input
                id="direccion_externa"
                v-model="form.direccion_externa"
                placeholder="Calle, zona, ciudad"
              />
            </div>
          </div>
        </div>

        <!-- Gestión de Propiedades -->
        <div class="border-t pt-6">
          <div class="flex items-center justify-between mb-4">
            <Label class="font-semibold">Propiedades Asignadas</Label>
            <Button 
              type="button" 
              variant="outline" 
              size="sm"
              @click="abrirSelectorPropiedades"
            >
              <Building2 class="h-4 w-4 mr-2" />
              {{ propiedadesSeleccionadas.length > 0 ? 'Gestionar Propiedades' : 'Asignar Propiedades' }}
            </Button>
          </div>

          <!-- Resumen de propiedades seleccionadas -->
          <div v-if="propiedadesSeleccionadas.length > 0" class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span>Total de propiedades: {{ propiedadesSeleccionadas.length }}</span>              
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <div 
                v-for="prop in propiedadesSeleccionadas" 
                :key="prop.id"
                class="border rounded-lg p-2 text-sm"
                :class="{ 'border-primary bg-primary/5': prop.es_principal }"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium">{{ prop.codigo }}</p>
                    <p class="text-muted-foreground">{{ prop.ubicacion }}</p>
                  </div>
                  <div class="text-right">
                    <Badge v-if="prop.es_principal" variant="default" class="text-xs">
                      Principal
                    </Badge>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-6 border-2 border-dashed rounded-lg">
            <Building2 class="h-8 w-8 mx-auto text-muted-foreground mb-2" />
            <p class="text-muted-foreground text-sm">
              No hay propiedades asignadas
            </p>
            <Button 
              type="button" 
              variant="outline" 
              size="sm" 
              class="mt-2"
              @click="abrirSelectorPropiedades"
            >
              Asignar Propiedades
            </Button>
          </div>
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

<!-- Dialog para seleccionar propiedades -->
  <SeleccionarPropiedadesDialog
    v-model:open="showSelectorPropiedades"
    :propiedades="propiedadesDisponibles"
    :propiedades-existentes="propiedadesSeleccionadas"
    @confirmar="onPropiedadesSeleccionadas"
  />
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { useNotification } from '@/composables/useNotification';
import { Building2 } from 'lucide-vue-next';
import SeleccionarPropiedadesDialog from './SeleccionarPropiedadesDialog.vue';
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

interface Propietario {
  id: number;
  nombre_completo: string;
  ci?: string;
  nit?: string;
  telefono?: string;
  email?: string;
  direccion_externa?: string;
  fecha_registro: string;
  observaciones?: string;
  propiedades?: PropiedadConPivot[];
}

interface PropiedadConPivot extends Propiedad {
  pivot: {
    fecha_inicio: string;
    es_propietario_principal: boolean;
  };
}

interface PropiedadSeleccionada {
  id: number;
  codigo: string;
  ubicacion: string;
  fecha_inicio: string;
  es_principal: boolean;
}

interface Props {
  open: boolean;
  propietario?: Propietario | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'close']);

const { showSuccess, showError } = useNotification();
const isEditing = computed(() => !!props.propietario);
const showSelectorPropiedades = ref(false);
const cargandoPropiedades = ref(false);
const propiedadesDisponibles = ref<Propiedad[]>([]);
const propiedadesSeleccionadas = ref<PropiedadSeleccionada[]>([]);

const form = useForm({
  nombre_completo: '',
  ci: '',
  nit: '',
  telefono: '',
  email: '',
  direccion_externa: '',
  fecha_registro: new Date().toISOString().split('T')[0],
  propiedades: [] as Array<{
    propiedad_id: number;
    fecha_inicio: string;
    es_propietario_principal: boolean;
  }>
});

// Cargar propiedades existentes al editar
const cargarPropiedadesExistentes = () => {
  if (props.propietario?.propiedades && props.propietario.propiedades.length > 0) {
    propiedadesSeleccionadas.value = props.propietario.propiedades.map(prop => ({
      id: prop.id,
      codigo: prop.codigo,
      ubicacion: prop.ubicacion,
      fecha_inicio: prop.pivot.fecha_inicio,
      es_principal: prop.pivot.es_propietario_principal
    }));
  } else {
    propiedadesSeleccionadas.value = [];
  }
};

// Cargar propiedades disponibles desde la API
const cargarPropiedadesDisponibles = async () => {
  try {
    cargandoPropiedades.value = true;
    const response = await axios.get('/propietarios/propiedades-disponibles');
    propiedadesDisponibles.value = response.data.propiedades;
  } catch (error) {
    showError('Error al cargar las propiedades disponibles');
    console.error(error);
  } finally {
    cargandoPropiedades.value = false;
  }
};

watch(() => props.open, (newVal) => {
  if (newVal) {
    if (props.propietario) {
      // Modo edición
      form.nombre_completo = props.propietario.nombre_completo;
      form.ci = props.propietario.ci || '';
      form.nit = props.propietario.nit || '';
      form.telefono = props.propietario.telefono || '';
      form.email = props.propietario.email || '';
      form.direccion_externa = props.propietario.direccion_externa || '';
      form.fecha_registro = props.propietario.fecha_registro;
      
      cargarPropiedadesExistentes();
    } else {
      // Modo creación
      form.reset();
      propiedadesSeleccionadas.value = [];
    }
  }
});

// Métodos
const abrirSelectorPropiedades = async () => {
  // Cargar propiedades solo cuando se abre el selector
  if (propiedadesDisponibles.value.length === 0) {
    await cargarPropiedadesDisponibles();
  }
  showSelectorPropiedades.value = true;
};

const onPropiedadesSeleccionadas = (propiedades: PropiedadSeleccionada[]) => {
  propiedadesSeleccionadas.value = propiedades;
};

const closeDialog = () => {
  emit('update:open', false);
  emit('close');
  form.reset();
  form.clearErrors();
  propiedadesSeleccionadas.value = [];
  propiedadesDisponibles.value = []; // Limpiar propiedades disponibles
};

const validarPropiedades = (): boolean => {
  if (propiedadesSeleccionadas.value.length === 0) return true;
     
  const tienePrincipal = propiedadesSeleccionadas.value.some(p => p.es_principal);
  if (!tienePrincipal) {
    showError('Debe asignar una propiedad como principal');
    return false;
  }
  
  return true;
};

const submit = () => {
  // Validar propiedades si hay asignadas
  if (propiedadesSeleccionadas.value.length > 0 && !validarPropiedades()) {
    return;
  }

  // Preparar datos de propiedades para el formulario
  form.propiedades = propiedadesSeleccionadas.value.map(prop => ({
    propiedad_id: prop.id,
    fecha_inicio: prop.fecha_inicio,
    es_propietario_principal: prop.es_principal
  }));

  const url = isEditing.value 
    ? `/propietarios/${props.propietario!.id}`
    : '/propietarios';
  
  const method = isEditing.value ? 'put' : 'post';

  form[method](url, {
    onSuccess: () => {
      showSuccess(
        isEditing.value 
          ? 'Propietario actualizado exitosamente' 
          : 'Propietario creado exitosamente'
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
<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useNotification } from '@/composables/useNotification';

interface Propiedad {
  id: number;
  codigo: string;
  ubicacion: string;
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
}

interface Props {
  open: boolean;
  propietario?: Propietario | null;
  propiedades?: Propiedad[];
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'close']);

const { showSuccess, showError } = useNotification();

const isEditing = computed(() => !!props.propietario);
const asignarPropiedad = ref(false);

const form = useForm({
  nombre_completo: '',
  ci: '',
  nit: '',
  telefono: '',
  email: '',
  direccion_externa: '',
  fecha_registro: new Date().toISOString().split('T')[0],
  activo: true,
  observaciones: '',
  propiedad_id: null as number | null,
  porcentaje_participacion: 100,
  fecha_inicio_propiedad: new Date().toISOString().split('T')[0],
});

watch(() => props.open, (newVal) => {
  if (newVal) {
    if (props.propietario) {
      form.nombre_completo = props.propietario.nombre_completo;
      form.ci = props.propietario.ci || '';
      form.nit = props.propietario.nit || '';
      form.telefono = props.propietario.telefono || '';
      form.email = props.propietario.email || '';
      form.direccion_externa = props.propietario.direccion_externa || '';
      form.fecha_registro = props.propietario.fecha_registro;
      form.activo = props.propietario.activo;
      form.observaciones = props.propietario.observaciones || '';
      asignarPropiedad.value = false;
    } else {
      form.reset();
      asignarPropiedad.value = false;
    }
  }
});

const closeDialog = () => {
  emit('update:open', false);
  emit('close');
  form.reset();
  form.clearErrors();
  asignarPropiedad.value = false;
};

const submit = () => {
  const url = isEditing.value 
    ? `/propietarios/${props.propietario!.id}`
    : '/propietarios';
  
  const method = isEditing.value ? 'put' : 'post';

  form.transform((data) => ({
    ...data,
    propiedad_id: asignarPropiedad.value ? data.propiedad_id : null,
    porcentaje_participacion: asignarPropiedad.value ? data.porcentaje_participacion : null,
    fecha_inicio_propiedad: asignarPropiedad.value ? data.fecha_inicio_propiedad : null,
  }))[method](url, {
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

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>
          {{ isEditing ? 'Editar Propietario' : 'Nuevo Propietario' }}
        </DialogTitle>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
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
              :class="{ 'border-destructive': form.errors.nit }"
            />
          </div>

          <!-- Teléfono -->
          <div>
            <Label for="telefono">Teléfono</Label>
            <Input
              id="telefono"
              v-model="form.telefono"
              placeholder="Ej: 77123456"
              :class="{ 'border-destructive': form.errors.telefono }"
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

          <!-- Dirección Externa -->
          <div class="col-span-2">
            <Label for="direccion_externa">Dirección Externa</Label>
            <Input
              id="direccion_externa"
              v-model="form.direccion_externa"
              placeholder="Calle, zona, ciudad"
            />
          </div>

          <!-- Fecha Registro -->
          <div>
            <Label for="fecha_registro">Fecha de Registro</Label>
            <Input
              id="fecha_registro"
              v-model="form.fecha_registro"
              type="date"
              :class="{ 'border-destructive': form.errors.fecha_registro }"
            />
          </div>

          <!-- Estado Activo -->
          <div class="flex items-center space-x-2 pt-8">
            <Checkbox 
              id="activo" 
              :checked="form.activo"
              @update:checked="(val) => form.activo = val"
            />
            <Label for="activo" class="cursor-pointer">
              Propietario Activo
            </Label>
          </div>

          <!-- Observaciones -->
          <div class="col-span-2">
            <Label for="observaciones">Observaciones</Label>
            <Textarea
              id="observaciones"
              v-model="form.observaciones"
              placeholder="Notas adicionales..."
              rows="3"
            />
          </div>
        </div>

        <!-- Asignar Propiedad (solo al crear) -->
        <div v-if="!isEditing && propiedades && propiedades.length > 0" class="border-t pt-4">
          <div class="flex items-center space-x-2 mb-4">
            <Checkbox 
              id="asignar_propiedad" 
              :checked="asignarPropiedad"
              @update:checked="(val) => asignarPropiedad = val"
            />
            <Label for="asignar_propiedad" class="cursor-pointer font-semibold">
              Asignar propiedad al crear
            </Label>
          </div>

          <div v-if="asignarPropiedad" class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
              <Label for="propiedad_id">Propiedad</Label>
              <Select v-model="form.propiedad_id">
                <SelectTrigger id="propiedad_id">
                  <SelectValue placeholder="Selecciona una propiedad" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="prop in propiedades" 
                    :key="prop.id" 
                    :value="String(prop.id)"
                  >
                    {{ prop.codigo }} - {{ prop.ubicacion }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label for="porcentaje">% Participación</Label>
              <Input
                id="porcentaje"
                v-model.number="form.porcentaje_participacion"
                type="number"
                min="0"
                max="100"
                step="0.01"
              />
            </div>

            <div class="col-span-3">
              <Label for="fecha_inicio_propiedad">Fecha de Inicio</Label>
              <Input
                id="fecha_inicio_propiedad"
                v-model="form.fecha_inicio_propiedad"
                type="date"
              />
            </div>
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
</template>
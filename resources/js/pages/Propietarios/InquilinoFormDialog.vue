<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { X, Plus, Search } from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';
import SeleccionarPropiedadesInquilinoDialog from './SeleccionarPropiedadesInquilinoDialog.vue';

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

interface PropiedadAsignada {
  propiedad_id: number;
  fecha_inicio_contrato: string;
  fecha_fin_contrato?: string;
  es_inquilino_principal: boolean;
  observaciones?: string;
}

interface Inquilino {
  id?: number;
  nombre_completo: string;
  ci?: string;
  telefono?: string;
  email?: string;
  activo: boolean;
  observaciones?: string;
  propiedades?: Array<Propiedad & { pivot: PropiedadAsignada }>;
}

interface Props {
  open: boolean;
  inquilino?: Inquilino | null;
  propiedades: Propiedad[];
}

const props = withDefaults(defineProps<Props>(), {
  inquilino: null,
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  'save': [];
  'cancel': [];
}>();

const { showSuccess, showError } = useNotification();

const form = ref({
  nombre_completo: props.inquilino?.nombre_completo || '',
  ci: props.inquilino?.ci || '',
  telefono: props.inquilino?.telefono || '',
  email: props.inquilino?.email || '',
  activo: props.inquilino?.activo ?? true,
  observaciones: props.inquilino?.observaciones || '',
});

const propiedadesAsignadas = ref<PropiedadAsignada[]>([]);

const isLoading = ref(false);
const errors = ref<Record<string, string>>({});
const showPropiedadesDialog = ref(false);

const isEdit = computed(() => !!props.inquilino?.id);

// Inicializar propiedades asignadas si estamos editando
watch(() => props.inquilino, (newInquilino) => {
  if (newInquilino?.propiedades) {
    propiedadesAsignadas.value = newInquilino.propiedades.map(prop => ({
      propiedad_id: prop.id,
      fecha_inicio_contrato: prop.pivot.fecha_inicio_contrato,
      fecha_fin_contrato: prop.pivot.fecha_fin_contrato || undefined,
      es_inquilino_principal: prop.pivot.es_inquilino_principal,
            observaciones: prop.pivot.observaciones || undefined,
    }));
  } else {
    propiedadesAsignadas.value = [];
  }
});

const resetForm = () => {
  form.value = {
    nombre_completo: props.inquilino?.nombre_completo || '',
    ci: props.inquilino?.ci || '',
    telefono: props.inquilino?.telefono || '',
    email: props.inquilino?.email || '',
    activo: props.inquilino?.activo ?? true,
    observaciones: props.inquilino?.observaciones || '',
  };

  if (props.inquilino?.propiedades) {
    propiedadesAsignadas.value = props.inquilino.propiedades.map(prop => ({
      propiedad_id: prop.id,
      fecha_inicio_contrato: prop.pivot.fecha_inicio_contrato,
      fecha_fin_contrato: prop.pivot.fecha_fin_contrato || undefined,
      es_inquilino_principal: prop.pivot.es_inquilino_principal,
            observaciones: prop.pivot.observaciones || undefined,
    }));
  } else {
    propiedadesAsignadas.value = [];
  }

  errors.value = {};
};

watch(() => props.inquilino, () => {
  resetForm();
});

const close = () => {
  emit('update:open', false);
  emit('cancel');
  resetForm();
};

const abrirSelectorPropiedades = () => {
  showPropiedadesDialog.value = true;
};

const confirmarSeleccionPropiedades = (seleccionadas: any[]) => {
  // Convertir las propiedades seleccionadas al formato del formulario
  propiedadesAsignadas.value = seleccionadas.map(prop => ({
    propiedad_id: prop.id,
    fecha_inicio_contrato: prop.fecha_inicio_contrato,
    fecha_fin_contrato: prop.fecha_fin_contrato || undefined,
    es_inquilino_principal: prop.es_inquilino_principal,
    observaciones: prop.observaciones || undefined,
  }));
};

const eliminarPropiedad = (index: number) => {
  propiedadesAsignadas.value.splice(index, 1);

  // Si eliminamos la propiedad principal, hacer la primera principal
  if (propiedadesAsignadas.value.length > 0 && !propiedadesAsignadas.value.some(p => p.es_inquilino_principal)) {
    propiedadesAsignadas.value[0].es_inquilino_principal = true;
  }
};

const getPropiedadNombre = (propiedadId: number): string => {
  const propiedad = props.propiedades.find(p => p.id === propiedadId);
  return propiedad ? `${propiedad.codigo} - ${propiedad.tipo_propiedad.nombre}` : '';
};

const validarPropiedades = (): string[] => {
  const errores: string[] = [];

  if (propiedadesAsignadas.value.length === 0) {
    errores.push('Debe asignar al menos una propiedad');
  }

  // Verificar propiedades duplicadas
  const propiedadIds = propiedadesAsignadas.value.map(p => p.propiedad_id).filter(id => id > 0);
  const duplicados = propiedadIds.filter((id, index) => propiedadIds.indexOf(id) !== index);
  if (duplicados.length > 0) {
    errores.push('No puede asignar la misma propiedad más de una vez');
  }

  // Verificar que solo haya una propiedad principal sin fecha_fin
  const principalesActivas = propiedadesAsignadas.value.filter(p =>
    p.es_inquilino_principal && (!p.fecha_fin_contrato || new Date(p.fecha_fin_contrato) > new Date())
  );
  if (principalesActivas.length > 1) {
    errores.push('Solo puede haber una propiedad principal activa');
  }

  
  return errores;
};

const submit = () => {
  isLoading.value = true;
  errors.value = {};

  // Validar propiedades
  const erroresPropiedades = validarPropiedades();
  if (erroresPropiedades.length > 0) {
    errors.value = { propiedades: erroresPropiedades.join('. ') };
    isLoading.value = false;
    return;
  }

  const data = {
    ...form.value,
    propiedades: propiedadesAsignadas.value,
  };

  const url = isEdit.value
    ? `/inquilinos/${props.inquilino?.id}`
    : '/inquilinos';

  const method = isEdit.value ? 'put' : 'post';

  router[method](url, data, {
    preserveScroll: true,
    onSuccess: () => {
      showSuccess(isEdit.value ? 'Inquilino actualizado exitosamente' : 'Inquilino creado exitosamente');
      emit('save');
      close();
    },
    onError: (pageErrors) => {
      console.error('Error en formulario de inquilino:', pageErrors);

      if (pageErrors.error) {
        showError(pageErrors.error);
      } else if (pageErrors.propiedades) {
        showError(pageErrors.propiedades);
      } else {
        showError(isEdit.value ? 'Error al actualizar el inquilino' : 'Error al crear el inquilino');
      }

      // Mostrar errores específicos del formulario
      if (typeof pageErrors === 'object' && pageErrors !== null) {
        const formattedErrors = Object.fromEntries(
          Object.entries(pageErrors).map(([key, value]) => [
            key,
            Array.isArray(value) ? value.join('. ') : (typeof value === 'string' ? value : String(value)),
          ])
        );
        errors.value = formattedErrors;
      }
    },
    onFinish: () => {
      isLoading.value = false;
    },
  });
};

const handleOpenChange = (value: boolean) => {
  emit('update:open', value);
  if (!value) {
    resetForm();
  }
};

const handlePrincipalChange = (index: number, checked: boolean) => {
  if (checked) {
    // Si se marca como principal, desmarcar todas las demás
    propiedadesAsignadas.value.forEach((p, i) => {
      if (i !== index) {
        p.es_inquilino_principal = false;
      }
    });
  }
};
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="sm:max-w-[900px] max-w-[95vw] max-h-[90vh] overflow-y-auto mx-4 dark:bg-gray-900">
      <DialogHeader class="pb-4">
        <DialogTitle class="text-lg sm:text-xl">
          {{ isEdit ? 'Editar Inquilino' : 'Crear Nuevo Inquilino' }}
        </DialogTitle>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
        <!-- Datos Personales -->
        <div class="space-y-3 sm:space-y-4">
          <h3 class="text-base sm:text-lg font-semibold">Datos Personales</h3>

          <!-- Nombre y CI en misma fila -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <div class="col-span-1 sm:col-span-2 space-y-2">
              <Label for="nombre_completo" class="text-sm font-medium">Nombre Completo *</Label>
              <Input
                id="nombre_completo"
                v-model="form.nombre_completo"
                type="text"
                required
                :class="{ 'border-destructive': errors.nombre_completo }"
                class="h-10 sm:h-11"
              />
              <p v-if="errors.nombre_completo" class="text-sm text-destructive">
                {{ errors.nombre_completo }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="ci" class="text-sm font-medium">CI</Label>
              <Input
                id="ci"
                v-model="form.ci"
                type="text"
                maxlength="20"
                :class="{ 'border-destructive': errors.ci }"
                class="h-10 sm:h-11"
              />
              <p v-if="errors.ci" class="text-sm text-destructive">
                {{ errors.ci }}
              </p>
            </div>
          </div>

          <!-- Teléfono y Email en misma fila -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <div class="space-y-2">
              <Label for="telefono" class="text-sm font-medium">Teléfono</Label>
              <Input
                id="telefono"
                v-model="form.telefono"
                type="tel"
                maxlength="20"
                :class="{ 'border-destructive': errors.telefono }"
                class="h-10 sm:h-11"
              />
              <p v-if="errors.telefono" class="text-sm text-destructive">
                {{ errors.telefono }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="email" class="text-sm font-medium">Email</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                :class="{ 'border-destructive': errors.email }"
                class="h-10 sm:h-11"
              />
              <p v-if="errors.email" class="text-sm text-destructive">
                {{ errors.email }}
              </p>
            </div>
          </div>

          <!-- Activo y Observaciones -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <div class="flex items-center space-x-2 h-10">
              <Checkbox id="activo" v-model:checked="form.activo" />
              <Label for="activo" class="text-sm font-medium">Activo</Label>
            </div>

            <div class="space-y-2">
              <Label for="observaciones" class="text-sm font-medium">Observaciones (opcional)</Label>
              <Textarea
                id="observaciones"
                v-model="form.observaciones"
                rows="2"
                placeholder="Agregar observaciones sobre el inquilino..."
                class="resize-none"
              />
            </div>
          </div>
        </div>

        <!-- Propiedades Asignadas -->
        <div class="space-y-3 sm:space-y-4 border-t pt-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-base sm:text-lg font-semibold">Propiedades Asignadas</h3>
            <Button
              type="button"
              @click="abrirSelectorPropiedades"
              variant="outline"
              size="sm"
              class="w-full sm:w-auto h-10"
            >
              <Plus class="mr-2 h-4 w-4 flex-shrink-0" />
              <span class="truncate">Seleccionar Propiedades</span>
            </Button>
          </div>

          <div v-if="errors.propiedades" class="text-sm text-destructive bg-red-50 dark:bg-red-900/20 p-3 rounded-md">
            {{ errors.propiedades }}
          </div>

          <div
            v-if="propiedadesAsignadas.length === 0"
            class="text-center py-6 sm:py-8 text-muted-foreground border-2 border-dashed rounded-lg"
          >
            <p class="text-sm sm:text-base">No hay propiedades asignadas</p>
            <p class="text-xs sm:text-sm mt-1">
              Haga clic en "Seleccionar Propiedades" para asignar una o más propiedades al inquilino
            </p>
          </div>

          <div v-else class="space-y-3 sm:space-y-4">
            <div
              v-for="(propiedad, index) in propiedadesAsignadas"
              :key="index"
              class="border rounded-lg p-3 sm:p-4 space-y-3"
            >
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                  <div class="min-w-0 flex-1">
                    <h4 class="font-medium text-sm sm:text-base truncate">{{ getPropiedadNombre(propiedad.propiedad_id) }}</h4>
                    <p class="text-xs sm:text-sm text-muted-foreground">
                      {{ propiedad.es_inquilino_principal ? 'Inquilino Principal' : 'Inquilino Adicional' }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <div
                    v-if="propiedad.es_inquilino_principal"
                    class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded text-xs"
                  >
                    Principal
                  </div>
                  <Button
                    type="button"
                    @click="eliminarPropiedad(index)"
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive h-8 w-8 p-0 flex-shrink-0"
                  >
                    <X class="h-3 w-3 sm:h-4 sm:w-4" />
                  </Button>
                </div>
              </div>

              <!-- Fechas y Observaciones -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div class="space-y-2">
                  <Label :for="`inicio-${index}`" class="text-sm font-medium">Inicio Contrato</Label>
                  <Input
                    :id="`inicio-${index}`"
                    v-model="propiedad.fecha_inicio_contrato"
                    type="date"
                    class="h-10 sm:h-11"
                  />
                </div>

                <div class="space-y-2">
                  <Label :for="`fin-${index}`" class="text-sm font-medium">Fin Contrato</Label>
                  <Input
                    :id="`fin-${index}`"
                    v-model="propiedad.fecha_fin_contrato"
                    type="date"
                    class="h-10 sm:h-11"
                  />
                </div>
              </div>

              <div v-if="propiedad.observaciones" class="space-y-2">
                <Label :for="`obs-${index}`" class="text-sm font-medium">Observaciones</Label>
                <Input
                  :id="`obs-${index}`"
                  :value="propiedad.observaciones"
                  readonly
                  class="bg-gray-50 dark:bg-gray-700 text-sm h-10"
                />
              </div>
            </div>
          </div>
        </div>

        <DialogFooter class="flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-4">
          <Button
            type="button"
            variant="outline"
            @click="close"
            :disabled="isLoading"
            class="w-full sm:w-auto order-2 sm:order-1"
          >
            Cancelar
          </Button>
          <Button
            type="submit"
            :disabled="isLoading"
            class="w-full sm:w-auto order-1 sm:order-2"
          >
            {{ isLoading ? (isEdit ? 'Actualizando...' : 'Creando...') : (isEdit ? 'Actualizar inquilino' : 'Crear inquilino') }}
          </Button>
        </DialogFooter>
      </form>

      <!-- Diálogo para seleccionar propiedades -->
      <SeleccionarPropiedadesInquilinoDialog
        v-model:open="showPropiedadesDialog"
        :propiedades="propiedades"
        :propiedades-existentes="propiedadesAsignadas.map(p => ({
          id: p.propiedad_id,
          codigo: getPropiedadNombre(p.propiedad_id),
          ubicacion: props.propiedades.find(pr => pr.id === p.propiedad_id)?.ubicacion || '',
          fecha_inicio_contrato: p.fecha_inicio_contrato,
          fecha_fin_contrato: p.fecha_fin_contrato || undefined,
          es_inquilino_principal: p.es_inquilino_principal,
          observaciones: p.observaciones || undefined,
        }))"
        @confirmar="confirmarSeleccionPropiedades"
      />
    </DialogContent>
  </Dialog>
</template>
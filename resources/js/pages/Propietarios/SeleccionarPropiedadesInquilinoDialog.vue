<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Seleccionar Propiedades para Inquilino</DialogTitle>
        <DialogDescription>
          Selecciona las propiedades que el inquilino va a ocupar
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- Buscador -->
        <div class="relative">
          <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
          <Input
            v-model="searchTerm"
            placeholder="Buscar por código, ubicación o tipo..."
            class="pl-9"
          />
        </div>

        <!-- Lista de propiedades disponibles -->
        <div class="border rounded-lg">
          <div class="max-h-64 overflow-y-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="w-12"></TableHead>
                  <TableHead>Código</TableHead>
                  <TableHead>Ubicación</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead class="text-right">m²</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="propiedad in propiedadesFiltradas"
                  :key="propiedad.id"
                  class="cursor-pointer hover:bg-muted/50"
                  :class="{ 'bg-muted/50': isSelected(propiedad.id) }"
                  @click="togglePropiedad(propiedad)"
                >
                  <TableCell>
                    <Checkbox
                      :modelValue="isSelected(propiedad.id)"
                      @update:modelValue="(val) => val ? seleccionarPropiedad(propiedad) : deseleccionarPropiedad(propiedad.id)"
                      :binary="true"
                    />
                  </TableCell>
                  <TableCell class="font-medium">
                    {{ propiedad.codigo }}
                  </TableCell>
                  <TableCell>
                    {{ propiedad.ubicacion }}
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ propiedad.tipo_propiedad.nombre }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-right">
                    {{ propiedad.metros_cuadrados }}
                  </TableCell>
                </TableRow>

                <TableRow v-if="propiedadesFiltradas.length === 0">
                  <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                    No se encontraron propiedades
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>

        <!-- Propiedades seleccionadas -->
        <div v-if="propiedadesSeleccionadas.length > 0" class="space-y-3">
          <Label>Propiedades Seleccionadas ({{ propiedadesSeleccionadas.length }})</Label>

          <div v-for="prop in propiedadesSeleccionadas" :key="prop.id"
               class="border rounded-lg p-3 space-y-3">
            <div class="flex justify-between items-start">
              <div class="flex items-center gap-2">
                <Building2 class="h-4 w-4 text-muted-foreground" />
                <div>
                  <p class="font-medium">{{ prop.codigo }}</p>
                  <p class="text-sm text-muted-foreground">{{ prop.ubicacion }}</p>
                </div>
              </div>

              <Button
                @click="deseleccionarPropiedad(prop.id)"
                variant="ghost"
                size="icon"
                class="h-8 w-8 text-destructive"
              >
                <X class="h-4 w-4" />
              </Button>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <Label :for="`fecha_inicio-${prop.id}`" class="text-xs">Fecha Inicio Contrato</Label>
                <Input
                  :id="`fecha_inicio-${prop.id}`"
                  v-model="prop.fecha_inicio_contrato"
                  type="date"
                  class="h-8 text-sm"
                  required
                />
              </div>

              <div>
                <Label :for="`fecha_fin-${prop.id}`" class="text-xs">Fecha Fin Contrato (opcional)</Label>
                <Input
                  :id="`fecha_fin-${prop.id}`"
                  v-model="prop.fecha_fin_contrato"
                  type="date"
                  class="h-8 text-sm"
                />
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <Checkbox
                  :id="`principal-${prop.id}`"
                  :checked="prop.es_inquilino_principal"
                  @update:checked="(checked) => marcarComoPrincipal(prop.id, checked)"
                />
                <Label :for="`principal-${prop.id}`" class="text-sm">Inquilino Principal</Label>
              </div>

              <div v-if="prop.observaciones" class="text-xs text-muted-foreground">
                {{ prop.observaciones.substring(0, 50) }}{{ prop.observaciones.length > 50 ? '...' : '' }}
              </div>
            </div>
          </div>

        </div>

        <div v-else class="text-center py-8 border-2 border-dashed rounded-lg">
          <Building2 class="h-8 w-8 mx-auto text-muted-foreground mb-2" />
          <p class="text-muted-foreground text-sm">
            No hay propiedades seleccionadas
          </p>
        </div>
      </div>

      <DialogFooter>
        <Button type="button" variant="outline" @click="cancelar">
          Cancelar
        </Button>
        <Button
          type="button"
          @click="confirmarSeleccion"
          :disabled="!esSeleccionValida"
        >
          Confirmar Selección
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Search, X, Building2 } from 'lucide-vue-next';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useNotification } from '@/composables/useNotification';

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

interface PropiedadSeleccionada {
  id: number;
  codigo: string;
  ubicacion: string;
  fecha_inicio_contrato: string;
  fecha_fin_contrato?: string;
  es_inquilino_principal: boolean;
  observaciones?: string;
}

interface Props {
  open: boolean;
  propiedades?: Propiedad[];
  propiedadesExistentes?: PropiedadSeleccionada[];
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'confirmar']);

const { showError } = useNotification();
const searchTerm = ref('');
const propiedadesSeleccionadas = ref<PropiedadSeleccionada[]>([]);

watch(() => props.open, (newVal) => {
  if (newVal) {
    if (props.propiedadesExistentes && props.propiedadesExistentes.length > 0) {
      propiedadesSeleccionadas.value = [...props.propiedadesExistentes];
    } else {
      propiedadesSeleccionadas.value = [];
    }
    searchTerm.value = '';
  }
});

// Filtrar propiedades
const propiedadesFiltradas = computed(() => {
  if (!props.propiedades) return [];

  let filtered = props.propiedades.filter(prop =>
    !propiedadesSeleccionadas.value.some(selected => selected.id === prop.id)
  );

  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase();
    filtered = filtered.filter(prop =>
      prop.codigo.toLowerCase().includes(search) ||
      prop.ubicacion.toLowerCase().includes(search) ||
      prop.tipo_propiedad.nombre.toLowerCase().includes(search)
    );
  }

  return filtered;
});

const esSeleccionValida = computed(() => {
  if (propiedadesSeleccionadas.value.length === 0) return false;

  // Validar que tenga al menos una propiedad principal
  const tienePrincipal = propiedadesSeleccionadas.value.some(p => p.es_inquilino_principal);
  if (!tienePrincipal) return false;

  // Validar que todas tengan fecha de inicio
  const todasConFecha = propiedadesSeleccionadas.value.every(p => p.fecha_inicio_contrato);
  if (!todasConFecha) return false;

  return true;
});

// Métodos de selección
const isSelected = (propiedadId: number) => {
  return propiedadesSeleccionadas.value.some(p => p.id === propiedadId);
};

const seleccionarPropiedad = (propiedad: Propiedad) => {
  if (isSelected(propiedad.id)) return;

  const nuevaPropiedad: PropiedadSeleccionada = {
    id: propiedad.id,
    codigo: propiedad.codigo,
    ubicacion: propiedad.ubicacion,
    fecha_inicio_contrato: new Date().toISOString().split('T')[0],
    fecha_fin_contrato: undefined,
    es_inquilino_principal: propiedadesSeleccionadas.value.length === 0
  };

  propiedadesSeleccionadas.value.push(nuevaPropiedad);
};

const deseleccionarPropiedad = (propiedadId: number) => {
  const fuePrincipal = propiedadesSeleccionadas.value.find(p => p.id === propiedadId)?.es_inquilino_principal;

  propiedadesSeleccionadas.value = propiedadesSeleccionadas.value.filter(
    p => p.id !== propiedadId
  );

  // Si eliminamos el principal y quedan propiedades, asignar nuevo principal
  if (fuePrincipal && propiedadesSeleccionadas.value.length > 0) {
    propiedadesSeleccionadas.value[0].es_inquilino_principal = true;
  }
};

const togglePropiedad = (propiedad: Propiedad) => {
  if (isSelected(propiedad.id)) {
    deseleccionarPropiedad(propiedad.id);
  } else {
    seleccionarPropiedad(propiedad);
  }
};

const marcarComoPrincipal = (propiedadId: number, checked: boolean) => {
  if (checked) {
    propiedadesSeleccionadas.value.forEach(p => {
      p.es_inquilino_principal = p.id === propiedadId;
    });
  }
};

const cancelar = () => {
  emit('update:open', false);
};

const confirmarSeleccion = () => {
  if (propiedadesSeleccionadas.value.length === 0) {
    showError('Debe seleccionar al menos una propiedad');
    return;
  }

  if (!esSeleccionValida.value) {
    if (!propiedadesSeleccionadas.value.some(p => p.es_inquilino_principal)) {
      showError('Debe asignar una propiedad como principal');
    } else {
      showError('Todas las propiedades deben tener fecha de inicio de contrato');
    }
    return;
  }

  emit('confirmar', propiedadesSeleccionadas.value);
  emit('update:open', false);
};
</script>
<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Building2, MapPin, Ruler, Calendar } from 'lucide-vue-next';

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
    fecha_fin?: string;
    es_propietario_principal: boolean;
  };
}

interface Propietario {
  id: number;
  nombre_completo: string;
  ci?: string;
  telefono?: string;
  email?: string;
  propiedades?: Propiedad[];
  propiedades_count?: number;
}

interface Props {
  open: boolean;
  propietario: Propietario | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'close']);

const propiedadesActivas = computed(() => {
  if (!props.propietario?.propiedades) return [];
  return props.propietario.propiedades.filter(p => !p.pivot?.fecha_fin);
});

const propiedadesHistoricas = computed(() => {
  if (!props.propietario?.propiedades) return [];
  return props.propietario.propiedades.filter(p => p.pivot?.fecha_fin);
});

const closeDialog = () => {
  emit('update:open', false);
  emit('close');
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="sm:max-w-[800px] max-h-[80vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Propiedades de {{ propietario?.nombre_completo }}</DialogTitle>
      </DialogHeader>

      <div v-if="propietario" class="space-y-6">
        <!-- Información del Propietario -->
        <div class="bg-muted/50 rounded-lg p-4 space-y-2">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-muted-foreground">CI:</span>
              <span class="ml-2 font-medium">{{ propietario.ci || 'No registrado' }}</span>
            </div>
            <div>
              <span class="text-muted-foreground">Teléfono:</span>
              <span class="ml-2 font-medium">{{ propietario.telefono || 'No registrado' }}</span>
            </div>
            <div class="col-span-2">
              <span class="text-muted-foreground">Email:</span>
              <span class="ml-2 font-medium">{{ propietario.email || 'No registrado' }}</span>
            </div>
          </div>
        </div>

        <!-- Propiedades Activas -->
        <div v-if="propiedadesActivas.length > 0">
          <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
            <Building2 class="h-5 w-5" />
            Propiedades Activas
            <Badge variant="default">{{ propiedadesActivas.length }}</Badge>
          </h3>
          
          <div class="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Código</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead>Ubicación</TableHead>
                  <TableHead class="text-center">m²</TableHead>
                  <TableHead class="text-center">% Part.</TableHead>
                  <TableHead class="text-center">Principal</TableHead>
                  <TableHead>Desde</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="propiedad in propiedadesActivas" :key="propiedad.id">
                  <TableCell class="font-medium">
                    {{ propiedad.codigo }}
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ propiedad.tipo_propiedad.nombre }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <MapPin class="h-3 w-3 text-muted-foreground" />
                      {{ propiedad.ubicacion }}
                    </div>
                  </TableCell>
                  <TableCell class="text-center">
                    <div class="flex items-center justify-center gap-1">
                      <Ruler class="h-3 w-3 text-muted-foreground" />
                      {{ propiedad.metros_cuadrados }}
                    </div>
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge 
                      :variant="propiedad.pivot?.es_propietario_principal ? 'default' : 'secondary'"
                      class="text-xs"
                    >
                      {{ propiedad.pivot?.es_propietario_principal ? 'Sí' : 'No' }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-1 text-sm">
                      <Calendar class="h-3 w-3 text-muted-foreground" />
                      {{ formatDate(propiedad.pivot?.fecha_inicio || '') }}
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>

        <!-- Propiedades Históricas -->
        <div v-if="propiedadesHistoricas.length > 0">
          <h3 class="text-lg font-semibold mb-3 flex items-center gap-2 text-muted-foreground">
            Propiedades Históricas
            <Badge variant="secondary">{{ propiedadesHistoricas.length }}</Badge>
          </h3>
          
          <div class="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Código</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead>Ubicación</TableHead>
                  <TableHead>Período</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="propiedad in propiedadesHistoricas" :key="propiedad.id">
                  <TableCell class="font-medium">
                    {{ propiedad.codigo }}
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {{ propiedad.tipo_propiedad.nombre }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <MapPin class="h-3 w-3 text-muted-foreground" />
                      {{ propiedad.ubicacion }}
                    </div>
                  </TableCell>
                  <TableCell class="text-sm text-muted-foreground">
                    {{ formatDate(propiedad.pivot?.fecha_inicio || '') }} - 
                    {{ formatDate(propiedad.pivot?.fecha_fin || '') }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>

        <!-- Sin Propiedades -->
        <div 
          v-if="propiedadesActivas.length === 0 && propiedadesHistoricas.length === 0"
          class="text-center py-8"
        >
          <Building2 class="h-12 w-12 mx-auto text-muted-foreground/50 mb-3" />
          <p class="text-muted-foreground">
            Este propietario no tiene propiedades asignadas
          </p>
        </div>

        <!-- Footer con Botón Cerrar -->
        <div class="flex justify-end pt-4 border-t">
          <Button @click="closeDialog">
            Cerrar
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
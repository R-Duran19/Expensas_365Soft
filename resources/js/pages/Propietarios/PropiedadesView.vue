<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Building2, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import PropietarioFormDialog from './PropietarioFormDialog.vue'; // Ajusta la ruta según tu estructura

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
  pivot: {
    fecha_inicio: string;
    fecha_fin?: string;
    es_propietario_principal: boolean;
    observaciones?: string;
  };
}

interface Propietario {
  id: number;
  nombre_completo: string;
  ci?: string;
  nit?: string;
  telefono?: string;
  email?: string;
  propiedades: Propiedad[];
}

interface Props {
  propietario: Propietario;
    propiedades?: Propiedad[];
}

defineProps<Props>();

const showEditDialog = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Propietarios', href: '/propietarios' },
  { title: 'Propiedades', href: '#' },
];

const goBack = () => {
  router.get('/propietarios');
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const gestionarPropiedades = () => {
  showEditDialog.value = true;
};

const closeEditDialog = () => {
  showEditDialog.value = false;
  // Recargar la página para ver los cambios
  router.reload({ only: ['propietario'] });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Propiedades de ${propietario.nombre_completo}`" />
    
    <div class="py-6">
      <div class="mb-6">
        <Button @click="goBack" variant="ghost" class="mb-4">
          <ArrowLeft class="h-4 w-4 mr-2" />
          Volver a Propietarios
        </Button>
        
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold">Propiedades de {{ propietario.nombre_completo }}</h1>
            <p class="text-muted-foreground">
              CI: {{ propietario.ci || 'No especificado' }} | 
              Teléfono: {{ propietario.telefono || 'No especificado' }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <Badge variant="secondary">
              {{ propietario.propiedades.length }} propiedades
            </Badge>
            <Button @click="gestionarPropiedades" class="flex items-center gap-2">
              <Building2 class="h-4 w-4" />
              <Plus class="h-4 w-4" />
              Gestionar Propiedades
            </Button>
          </div>
        </div>
      </div>

      <div v-if="propietario.propiedades.length === 0" class="text-center py-8 border-2 border-dashed rounded-lg">
        <Building2 class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
        <p class="text-muted-foreground mb-4">Este propietario no tiene propiedades asignadas.</p>
        <Button @click="gestionarPropiedades">
          <Plus class="h-4 w-4 mr-2" />
          Asignar Primera Propiedad
        </Button>
      </div>

      <div v-else class="grid gap-4">
        <div v-for="propiedad in propietario.propiedades" :key="propiedad.id" 
             class="border rounded-lg p-4"
             :class="{ 'border-primary bg-primary/5': propiedad.pivot.es_propietario_principal }">
          <div class="flex justify-between items-start">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-semibold">{{ propiedad.codigo }}</h3>
                <Badge v-if="propiedad.pivot.es_propietario_principal" variant="default">
                  Principal
                </Badge>
              </div>
              <p class="text-sm text-muted-foreground">{{ propiedad.ubicacion }}</p>
              <p class="text-sm">{{ propiedad.tipo_propiedad.nombre }} - {{ propiedad.metros_cuadrados }} m²</p>
            </div>
            <div class="text-right">
              <Badge :variant="propiedad.pivot.fecha_fin ? 'destructive' : 'default'">
                {{ propiedad.pivot.fecha_fin ? 'Inactiva' : 'Activa' }}
              </Badge>
            </div>
          </div>
          
          <div class="mt-3 text-sm text-muted-foreground">
            <p>Fecha inicio: {{ formatDate(propiedad.pivot.fecha_inicio) }}</p>
            <p v-if="propiedad.pivot.fecha_fin">
              Fecha fin: {{ formatDate(propiedad.pivot.fecha_fin) }}
            </p>
            <p v-if="propiedad.pivot.observaciones">
              Observaciones: {{ propiedad.pivot.observaciones }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialog para gestionar propiedades -->
    <PropietarioFormDialog 
      v-model:open="showEditDialog"
      :propietario="propietario"
      @close="closeEditDialog"
    />
  </AppLayout>
</template>
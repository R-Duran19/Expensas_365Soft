<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import PropietariosTable from './Propietarios/PropietariosTable.vue';
import PropietariosHeader from './Propietarios/PropietariosHeader.vue';
import { ref, watch } from 'vue';

interface TipoPropiedad {
  id: number;
  nombre: string;
  descripcion?: string;
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
  filters?: {
    search?: string;
    activo?: boolean;
  };
  show_propiedades_dialog?: boolean;
  propietario?: Propietario; // Propietario individual cargado
}

const props = defineProps<Props>();

// Variables para controlar el dialog
const selectedPropietario = ref<Propietario | null>(null);
const showPropiedadesDialog = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Propietarios', href: '/propietarios' },
];

// Watcher para abrir el dialog automáticamente cuando venga el flag
watch(() => props.show_propiedades_dialog, (newVal) => {
  if (newVal && props.propietario) {
    selectedPropietario.value = props.propietario;
    showPropiedadesDialog.value = true;
    
    // Limpiar la URL para quitar el flag
    router.get('/propietarios', {}, { 
      preserveState: true, 
      preserveScroll: true 
    });
  }
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Propietarios" />
    <div class="py-6">
      <PropietariosHeader :filters="filters" />
      <PropietariosTable 
        :propietarios="propietarios" 
        :selected-propietario="selectedPropietario"
        :show-propiedades-dialog="showPropiedadesDialog"
        @update:show-propiedades-dialog="showPropiedadesDialog = $event"
        @update:selected-propietario="selectedPropietario = $event"
      />
    </div>
  </AppLayout>
</template>
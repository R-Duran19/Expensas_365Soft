<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PropietariosTable from './Propietarios/PropietariosTable.vue';
import PropietariosHeader from './Propietarios/PropietariosHeader.vue';

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
    porcentaje_participacion: number;
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
  propiedades?: Propiedad[];
  filters?: {
    search?: string;
    activo?: boolean;
  };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Propietarios', href: '/propietarios' },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Propietarios" />
    <div class="py-6">
      <PropietariosHeader :filters="filters" :propiedades="propiedades" />
      <PropietariosTable :propietarios="propietarios" :propiedades="propiedades" />
    </div>
  </AppLayout>
</template>
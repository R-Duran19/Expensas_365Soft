<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import LecturasHeader from './Lecturas/LecturasHeader.vue';
import LecturasTable from './Lecturas/LecturasTable.vue';
import LecturaFormDialog from './Lecturas/LecturaFormDialog.vue';
import { ref } from 'vue';

// ==========================================
// TIPOS
// ==========================================
interface TipoPropiedad {
  id: number;
  nombre: string;
  es_comercial: boolean;
}

interface Propiedad {
  id: number;
  codigo: string;
  nombre: string;
  ubicacion: string;
  tipo_propiedad: TipoPropiedad;
}

interface Medidor {
  id: number;
  numero_medidor: string;
  ubicacion: string | null;
  tipo: 'domiciliario' | 'comercial';
  propiedad: Propiedad;
  ultima_lectura?: number;
  fecha_ultima_lectura?: string;
}

interface Usuario {
  id: number;
  name: string;
  email: string;
}

interface Lectura {
  id: number;
  medidor_id: number;
  lectura_actual: number;
  lectura_anterior: number | null;
  consumo: number;
  fecha_lectura: string;
  mes_periodo: string;
  observaciones: string | null;
  medidor: Medidor;
  usuario: Usuario;
  created_at: string;
}

interface PaginatedLecturas {
  data: Lectura[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Filtros {
  periodo?: string;
  medidor_id?: number;
  fecha_desde?: string;
  fecha_hasta?: string;
}

interface Props {
  lecturas: PaginatedLecturas;
  periodos: string[]; // Array de períodos: ["2025-07", "2025-06", ...]
  filtros: Filtros;
}

defineProps<Props>();

// ==========================================
// BREADCRUMBS
// ==========================================
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Lecturas', href: '/lecturas' },
];

// ==========================================
// ESTADO
// ==========================================
const showLecturaDialog = ref(false);
const selectedLectura = ref<Lectura | null>(null);

// ==========================================
// MÉTODOS
// ==========================================
const openLecturaDialog = (lectura: Lectura | null = null) => {
  selectedLectura.value = lectura;
  showLecturaDialog.value = true;
};

const closeLecturaDialog = () => {
  showLecturaDialog.value = false;
  selectedLectura.value = null;
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Lecturas" />
    
    <div class="space-y-6">
      <!-- Header con filtros y acciones -->
      <LecturasHeader 
        :periodos="periodos"
        :filtros="filtros"
        @create-lectura="openLecturaDialog()"
      />

      <!-- Tabla de lecturas -->
      <LecturasTable 
        :lecturas="lecturas"
        @edit-lectura="openLecturaDialog"
      />

      <!-- Dialog para crear/editar lectura individual -->
      <LecturaFormDialog 
        v-model:open="showLecturaDialog"
        :lectura="selectedLectura"
        @close="closeLecturaDialog"
      />
    </div>
  </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import MedidoresHeader from './Medidores/MedidoresHeader.vue';
import MedidoresTable from './Medidores/MedidoresTable.vue';
import MedidorFormDialog from './Medidores/MedidorFormDialog.vue';
import { ref } from 'vue';

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

interface Lectura {
  id: number;
  lectura_actual: number;
  fecha_lectura: string;
  mes_periodo: string;
}

interface Medidor {
  id: number;
  numero_medidor: string;
  tipo: 'domiciliario' | 'comercial'; // Se calcula en el backend
  ubicacion: string | null;
  activo: boolean;
  observaciones: string | null;
  propiedad: Propiedad;
  ultima_lectura?: Lectura;
}

interface PaginatedData<T> {
  data: T[];
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
}

interface Props {
  medidores: PaginatedData<Medidor>;
  propiedades: Propiedad[]; // Solo propiedades que requieren medidor
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Medidores', href: '/medidores' },
];

const showMedidorDialog = ref(false);
const selectedMedidor = ref<Medidor | null>(null);

const openMedidorDialog = (medidor: Medidor | null = null) => {
  selectedMedidor.value = medidor;
  showMedidorDialog.value = true;
};

const closeMedidorDialog = () => {
  showMedidorDialog.value = false;
  selectedMedidor.value = null;
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Medidores" />
    <div class="space-y-6">
      <MedidoresHeader @create-medidor="openMedidorDialog()" />
      <MedidoresTable
        :medidores="medidores.data"
        :pagination="medidores"
        @edit-medidor="openMedidorDialog"
        @create-medidor="openMedidorDialog()"
      />

      <!-- Dialog para crear/editar medidor -->
      <MedidorFormDialog 
        v-model:open="showMedidorDialog"
        :medidor="selectedMedidor"
        :propiedades="propiedades"
        @close="closeMedidorDialog"
      />
    </div>
  </AppLayout>
</template>
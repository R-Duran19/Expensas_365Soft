<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import PropietariosTable from './Propietarios/PropietariosTable.vue';
import PropietariosHeader from './Propietarios/PropietariosHeader.vue';
import InquilinosTable from './Propietarios/InquilinosTable.vue';
import PropietarioFormDialog from './Propietarios/PropietarioFormDialog.vue';
import InquilinoFormDialog from './Propietarios/InquilinoFormDialog.vue';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Plus, Home, Users } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

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

interface Inquilino {
  id: number;
  propiedad_id: number;
  nombre_completo: string;
  ci?: string;
  telefono?: string;
  email?: string;
  fecha_inicio_contrato: string;
  fecha_fin_contrato?: string;
  activo: boolean;
  observaciones?: string;
  created_at: string;
  propiedad: Propiedad;
}

interface PaginatedPropietarios {
  data: Propietario[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface PaginatedInquilinos {
  data: Inquilino[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface Props {
  propietarios?: PaginatedPropietarios;
  inquilinos?: PaginatedInquilinos;
  filters?: {
    search?: string;
    activo?: boolean;
    contrato_vigente?: boolean;
  };
  show_propiedades_dialog?: boolean;
  propietario?: Propietario;
  activeTab?: string;
}

const props = withDefaults(defineProps<Props>(), {
  propietarios: undefined,
  inquilinos: undefined,
  activeTab: 'propietarios',
});

const page = usePage();

// Variables para controlar los dialogs
const selectedPropietario = ref<Propietario | null>(null);
const showPropiedadesDialog = ref(false);
const showPropietarioDialog = ref(false);
const showInquilinoDialog = ref(false);

// Estado para las tabs
const activeTab = ref(props.activeTab);

// Propiedades disponibles para inquilinos
const propiedadesDisponibles = ref<Propiedad[]>([]);

// Cargar propiedades disponibles cuando se abre el diálogo de inquilino
const loadPropiedadesDisponibles = async () => {
  try {
    const response = await axios.get('/propietarios/propiedades-disponibles');
    propiedadesDisponibles.value = response.data.propiedades;
  } catch (error) {
    console.error('Error al cargar propiedades disponibles:', error);
  }
};

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

// Watcher para sincronizar activeTab con la URL
watch(activeTab, (newTab) => {
  const url = new URL(window.location.href);
  if (newTab === 'propietarios') {
    url.searchParams.delete('activeTab');
  } else {
    url.searchParams.set('activeTab', newTab);
  }

  // Actualizar la URL sin recargar la página
  window.history.replaceState({}, '', url.toString());
});

// Handlers
const handleCreatePropietario = () => {
  selectedPropietario.value = null;
  showPropietarioDialog.value = true;
};

const handleCreateInquilino = () => {
  loadPropiedadesDisponibles();
  showInquilinoDialog.value = true;
};

const handleSave = () => {
  // Recargar los datos sin recargar la página completa
  const currentTab = activeTab.value;

  router.visit('/propietarios', {
    method: 'get',
    data: {
      activeTab: currentTab
    },
    preserveScroll: true,
    only: ['propietarios', 'inquilinos'],
    onSuccess: () => {
      console.log('Datos recargados exitosamente');
    },
    onError: (errors) => {
      console.error('Error al recargar datos:', errors);
    }
  });
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Propietarios e Inquilinos', href: '/propietarios' },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Propietarios e Inquilinos" />
    <div class="px-4 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-6">
      <!-- Header responsive -->
      <div class="space-y-4 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
        <!-- Título y descripción -->
        <div class="text-center sm:text-left">
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white break-words">
            Propietarios e Inquilinos
          </h1>
          <p class="mt-2 text-sm sm:text-base text-gray-700 dark:text-gray-300 leading-relaxed">
            Administra los propietarios e inquilinos de las propiedades
          </p>
        </div>

        <!-- Botones de acción responsive -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center sm:justify-end">
          <Button
            @click="handleCreatePropietario"
            class="w-full sm:w-auto min-h-[44px] px-4 sm:px-6"
          >
            <Home class="mr-2 h-4 w-4 flex-shrink-0" />
            <span class="truncate">Nuevo Propietario</span>
          </Button>
          <Button
            @click="handleCreateInquilino"
            variant="outline"
            class="w-full sm:w-auto min-h-[44px] px-4 sm:px-6"
          >
            <Users class="mr-2 h-4 w-4 flex-shrink-0" />
            <span class="truncate">Nuevo Inquilino</span>
          </Button>
        </div>
      </div>

      <!-- Tabs responsivas -->
      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList class="grid w-full grid-cols-2 h-auto">
          <TabsTrigger
            value="propietarios"
            class="flex items-center justify-center gap-2 py-3 px-4 text-sm sm:text-base h-auto min-h-[44px]"
          >
            <Home class="h-4 w-4 flex-shrink-0" />
            <span class="hidden sm:inline">Propietarios</span>
            <span class="sm:hidden">Prop.</span>
          </TabsTrigger>
          <TabsTrigger
            value="inquilinos"
            class="flex items-center justify-center gap-2 py-3 px-4 text-sm sm:text-base h-auto min-h-[44px]"
          >
            <Users class="h-4 w-4 flex-shrink-0" />
            <span class="hidden sm:inline">Inquilinos</span>
            <span class="sm:hidden">Inq.</span>
          </TabsTrigger>
        </TabsList>

        <TabsContent value="propietarios" class="space-y-4 mt-6">
          <PropietariosTable
            v-if="propietarios"
            :propietarios="propietarios"
            :selected-propietario="selectedPropietario"
            :show-propiedades-dialog="showPropiedadesDialog"
            @update:show-propiedades-dialog="showPropiedadesDialog = $event"
            @update:selected-propietario="selectedPropietario = $event"
          />
        </TabsContent>

        <TabsContent value="inquilinos" class="space-y-4 mt-6">
          <InquilinosTable v-if="inquilinos" :inquilinos="inquilinos" />
        </TabsContent>
      </Tabs>

      <!-- Dialogs -->
      <PropietarioFormDialog
        v-model:open="showPropietarioDialog"
        :propietario="selectedPropietario"
        @save="handleSave"
      />

      <InquilinoFormDialog
        v-model:open="showInquilinoDialog"
        :propiedades="propiedadesDisponibles"
        @save="handleSave"
      />
    </div>
  </AppLayout>
</template>
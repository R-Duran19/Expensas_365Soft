<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Search, X } from 'lucide-vue-next';
import AccesosCreateForm from './AccesosCreateForm.vue';

interface Role {
  id: number;
  nombre: string;
  descripcion?: string;
}

const props = defineProps<{
  roles: Role[];
  filters?: {
    search?: string;
  };
}>();

const showCreateDialog = ref(false);
const searchQuery = ref(props.filters?.search || '');

// Variable para el timeout
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// Función de búsqueda con debounce
const performSearch = (query: string) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  
  searchTimeout = setTimeout(() => {
    router.get('/accesos', 
      { search: query },
      {
        preserveState: true,
        preserveScroll: true,
        only: ['usuarios', 'filters'],
        replace: true,
      }
    );
  }, 500);
};

// Watch para búsqueda automática mientras se escribe
watch(searchQuery, (newValue) => {
  performSearch(newValue);
});

// Búsqueda inmediata al presionar Enter
const handleSearch = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  router.get('/accesos', 
    { search: searchQuery.value },
    {
      preserveState: true,
      preserveScroll: true,
      only: ['usuarios', 'filters'],
    }
  );
};

// Limpiar búsqueda
const clearSearch = () => {
  searchQuery.value = '';
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  router.get('/accesos', 
    {},
    {
      preserveState: true,
      preserveScroll: true,
      only: ['usuarios', 'filters'],
    }
  );
};

const handleCreate = () => {
  showCreateDialog.value = false;
};
</script>

<template>
  <div class="px-6 pb-6">
    <div class="flex items-center justify-between pb-4 border-b-2 border-gray-200 dark:border-gray-700">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Gestión de Accesos
        </h1>
        <p class="mt-2 text-base text-gray-700 dark:text-gray-300">
          Administra usuarios y sus roles en el sistema
        </p>
      </div>
      <Button @click="showCreateDialog = true">
        Nuevo Usuario
      </Button>
    </div>

    <div class="flex items-center gap-4 p-4 mt-4 rounded-lg bg-gray-50 dark:bg-gray-800">
      <div class="relative flex-1 max-w-md">
        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
        <Input
          v-model="searchQuery"
          placeholder="Buscar por nombre, email o rol..."
          class="pl-10 pr-10"
          @keyup.enter="handleSearch"
        />
        <!-- Botón para limpiar búsqueda -->
        <button
          v-if="searchQuery"
          @click="clearSearch"
          class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          type="button"
          title="Limpiar búsqueda"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
      
      <!-- Indicador de resultados -->
      <div v-if="searchQuery" class="text-sm text-gray-600 dark:text-gray-400">
        Buscando: <span class="font-medium">{{ searchQuery }}</span>
      </div>
    </div>

    <Dialog v-model:open="showCreateDialog">
      <DialogContent class="sm:max-w-[500px] dark:bg-gray-900" :show-close="false">
        <DialogHeader>
          <DialogTitle>Crear Nuevo Usuario</DialogTitle>
        </DialogHeader>
        <AccesosCreateForm 
          :roles="roles"
          @save="handleCreate" 
          @cancel="showCreateDialog = false" 
        />
      </DialogContent>
    </Dialog>
  </div>
</template>
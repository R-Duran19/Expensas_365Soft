<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Search, Plus, X } from 'lucide-vue-next';
import PropietarioFormDialog from './PropietarioFormDialog.vue';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

interface Propiedad {
  id: number;
  codigo: string;
  ubicacion: string;
}

interface Props {
  filters?: {
    search?: string;
    activo?: boolean;
  };
  propiedades?: Propiedad[];
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');
const activo = ref<string>(props.filters?.activo !== undefined ? String(props.filters.activo) : 'all');
const showCreateDialog = ref(false);

// Debounce para búsqueda
let searchTimeout: ReturnType<typeof setTimeout>;

watch(search, (value) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/propietarios', 
      { 
        search: value || undefined,
        activo: activo.value !== 'all' ? activo.value : undefined
      }, 
      { 
        preserveState: true,
        preserveScroll: true 
      }
    );
  }, 300);
});

watch(activo, (value) => {
  router.get('/propietarios', 
    { 
      search: search.value || undefined,
      activo: value !== 'all' ? value : undefined
    }, 
    { 
      preserveState: true,
      preserveScroll: true 
    }
  );
});

const clearFilters = () => {
  search.value = '';
  activo.value = 'all';
  router.get('/propietarios');
};

const hasActiveFilters = () => {
  return search.value !== '' || activo.value !== 'all';
};
</script>

<template>
  <div class="mb-6 space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Propietarios</h2>
        <p class="text-muted-foreground">
          Gestiona los propietarios y sus propiedades del edificio
        </p>
      </div>
      <Button @click="showCreateDialog = true" size="default">
        <Plus class="mr-2 h-4 w-4" />
        Nuevo Propietario
      </Button>
    </div>

    <!-- Filtros -->
    <div class="flex items-end gap-4">
      <div class="flex-1">
        <Label for="search">Buscar</Label>
        <div class="relative">
          <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            id="search"
            v-model="search"
            placeholder="Buscar por nombre, CI o email..."
            class="pl-8"
          />
        </div>
      </div>

      <div class="w-48">
        <Label for="estado">Estado</Label>
        <Select v-model="activo">
          <SelectTrigger id="estado">
            <SelectValue placeholder="Todos" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todos</SelectItem>
            <SelectItem value="true">Activos</SelectItem>
            <SelectItem value="false">Inactivos</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Button 
        v-if="hasActiveFilters()" 
        @click="clearFilters" 
        variant="outline"
      >
        <X class="mr-2 h-4 w-4" />
        Limpiar
      </Button>
    </div>

    <!-- Dialog Crear/Editar -->
    <PropietarioFormDialog 
      v-model:open="showCreateDialog"
      :propiedades="propiedades"
    />
  </div>
</template>
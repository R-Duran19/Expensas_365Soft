<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Gauge, Plus, FileText, Menu, X } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

defineEmits<{
  createMedidor: [];
}>();

const mobileMenuOpen = ref(false);

const irALecturas = () => {
  router.get('/lecturas');
  mobileMenuOpen.value = false;
};

const irAFacturasMedidores = () => {
  router.get('/facturas-medidores-principales');
  mobileMenuOpen.value = false;
};

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
};
</script>

<template>
  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
    <!-- Título y descripción -->
    <div class="flex-1">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Gestión de Medidores</h1>
      <p class="text-sm sm:text-base text-muted-foreground mt-1">
        Administra los medidores de agua del edificio
      </p>
    </div>

    <!-- Desktop: Todos los botones visibles -->
    <div class="hidden sm:flex sm:items-center sm:gap-2 sm:flex-shrink-0">
      <Button
        @click="irAFacturasMedidores"
        variant="outline"
        size="sm"
        class="whitespace-nowrap"
      >
        <FileText class="h-4 w-4 mr-2" />
        <span class="hidden lg:inline">Facturas Medidores</span>
        <span class="lg:hidden">Facturas</span>
      </Button>
      <Button
        @click="irALecturas"
        variant="outline"
        size="sm"
        class="whitespace-nowrap"
      >
        <Gauge class="h-4 w-4 mr-2" />
        <span class="hidden lg:inline">Registrar Lecturas</span>
        <span class="lg:hidden">Lecturas</span>
      </Button>
      <Button
        @click="$emit('createMedidor')"
        size="sm"
        class="whitespace-nowrap"
      >
        <Plus class="h-4 w-4 mr-2" />
        <span class="hidden lg:inline">Nuevo Medidor</span>
        <span class="lg:hidden">Nuevo</span>
      </Button>
    </div>

    <!-- Mobile: Botón de menú hamburguesa -->
    <div class="sm:hidden">
      <Button
        @click="toggleMobileMenu"
        variant="outline"
        size="sm"
        class="p-2"
      >
        <Menu v-if="!mobileMenuOpen" class="h-5 w-5" />
        <X v-else class="h-5 w-5" />
      </Button>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div
    v-if="mobileMenuOpen"
    class="sm:hidden mt-4 p-4 bg-white border border-gray-200 rounded-lg shadow-lg"
  >
    <div class="space-y-3">
      <Button
        @click="irAFacturasMedidores"
        variant="outline"
        class="w-full justify-start"
        size="sm"
      >
        <FileText class="h-4 w-4 mr-3" />
        Facturas Medidores
      </Button>
      <Button
        @click="irALecturas"
        variant="outline"
        class="w-full justify-start"
        size="sm"
      >
        <Gauge class="h-4 w-4 mr-3" />
        Registrar Lecturas
      </Button>
      <Button
        @click="$emit('createMedidor')"
        class="w-full justify-start"
        size="sm"
      >
        <Plus class="h-4 w-4 mr-3" />
        Nuevo Medidor
      </Button>
    </div>
  </div>
</template>
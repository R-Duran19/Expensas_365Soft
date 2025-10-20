<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Gauge, Plus } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

defineEmits<{
  createMedidor: [];
}>();

const irALecturas = () => {
  router.get('/lecturas');
};
</script>

<template>
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-2xl font-bold">Gestión de Medidores</h1>
      <p class="text-muted-foreground">
        Administra los medidores de agua del edificio
      </p>
    </div>
    <div class="flex gap-2">
      <Button @click="irALecturas" variant="outline">
        <Gauge class="h-4 w-4 mr-2" />
        Registrar Lecturas
      </Button>
      <Button @click="$emit('createMedidor')">
        <Plus class="h-4 w-4 mr-2" />
        Nuevo Medidor
      </Button>
    </div>
  </div>
</template>
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-vue-next';

defineEmits<{
  'create-period': [];
}>();
</script>

<template>
  <div class="px-6 pb-6">
    <div class="flex items-center justify-between pb-4 border-b-2 border-gray-200 dark:border-gray-700">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Períodos de Expensas
        </h1>
        <p class="mt-2 text-base text-gray-700 dark:text-gray-300">
          Administra los períodos de expensas (abiertos y cerrados)
        </p>
      </div>
      <Button @click="$emit('create-period')">
        <Plus class="mr-2 h-4 w-4" />
        Nuevo Período
      </Button>
    </div>
  </div>
</template>


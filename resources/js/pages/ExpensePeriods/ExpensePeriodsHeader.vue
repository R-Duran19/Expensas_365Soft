<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Plus, Lock } from 'lucide-vue-next';

interface Props {
  hasOpenPeriod?: boolean;
}

defineProps<Props>();

defineEmits<{
  'create-period': [];
}>();
</script>

<template>
  <div class="px-4 sm:px-6 pb-6">
    <div class="flex flex-col gap-4 pb-4 border-b-2 border-gray-200 dark:border-gray-700">
      <!-- Header con título y descripción -->
      <div class="text-center sm:text-left">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white break-words">
          Períodos de Expensas
        </h1>
        <p class="mt-2 text-sm sm:text-base text-gray-700 dark:text-gray-300 leading-relaxed">
          Administra los períodos de expensas (abiertos y cerrados)
        </p>
      </div>

      <!-- Botón de acción responsive -->
      <div class="flex justify-center sm:justify-end">
        <Button
          @click="$emit('create-period')"
          :disabled="hasOpenPeriod"
          :variant="hasOpenPeriod ? 'secondary' : 'default'"
          :title="hasOpenPeriod ? 'Debe cerrar el período actual antes de crear uno nuevo' : 'Crear nuevo período'"
          class="w-full sm:w-auto min-h-[44px] px-4 sm:px-6"
        >
          <Lock v-if="hasOpenPeriod" class="mr-2 h-4 w-4 flex-shrink-0" />
          <Plus v-else class="mr-2 h-4 w-4 flex-shrink-0" />
          <span class="truncate">
            {{ hasOpenPeriod ? 'Período Abierto' : 'Nuevo Período' }}
          </span>
        </Button>
      </div>
    </div>
  </div>
</template>


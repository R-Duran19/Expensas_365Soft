<script setup lang="ts">
import { computed, inject } from 'vue';
import type { Ref } from 'vue';

interface TabsContext {
  activeTab: Ref<string>;
}

const { activeTab } = inject<TabsContext>('tabs')!;

interface Props {
  value: string;
  class?: string;
}

const props = defineProps<Props>();

const isActive = computed(() => activeTab.value === props.value);
</script>

<template>
  <div
    v-if="isActive"
    :class="props.class"
    class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
  >
    <slot />
  </div>
</template>
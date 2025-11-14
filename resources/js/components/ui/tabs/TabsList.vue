<script setup lang="ts">
import { inject } from 'vue';
import type { Ref } from 'vue';

interface TabsContext {
  activeTab: Ref<string>;
  setActiveTab: (value: string) => void;
}

const { activeTab, setActiveTab } = inject<TabsContext>('tabs')!;

interface Props {
  class?: string;
}

const props = defineProps<Props>();
</script>

<template>
  <div :class="props.class" class="inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground">
    <slot />
  </div>
</template>
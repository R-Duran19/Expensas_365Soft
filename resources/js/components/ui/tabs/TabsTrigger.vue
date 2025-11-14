<script setup lang="ts">
import { computed, inject } from 'vue';
import type { Ref } from 'vue';

interface TabsContext {
  activeTab: Ref<string>;
  setActiveTab: (value: string) => void;
}

const { activeTab, setActiveTab } = inject<TabsContext>('tabs')!;

interface Props {
  value: string;
  class?: string;
}

const props = defineProps<Props>();

const isActive = computed(() => activeTab.value === props.value);

const onClick = () => {
  setActiveTab(props.value);
};
</script>

<template>
  <button
    :class="[
      'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
      isActive
        ? 'bg-background text-foreground shadow-sm'
        : 'transparent hover:bg-background/50',
      props.class
    ]"
    @click="onClick"
  >
    <slot />
  </button>
</template>
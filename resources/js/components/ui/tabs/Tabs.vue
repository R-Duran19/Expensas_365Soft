<script setup lang="ts">
import { provide } from 'vue';
import type { Ref } from 'vue';

interface TabsContext {
  activeTab: Ref<string>;
  setActiveTab: (value: string) => void;
}

interface Props {
  defaultValue: string;
  modelValue?: string;
  class?: string;
}

interface Emits {
  (e: 'update:modelValue', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  defaultValue: '',
});

const emit = defineEmits<Emits>();

import { ref, watch } from 'vue';

const activeTab = ref(props.modelValue || props.defaultValue);

const setActiveTab = (value: string) => {
  activeTab.value = value;
  emit('update:modelValue', value);
};

watch(() => props.modelValue, (newValue) => {
  if (newValue !== undefined) {
    activeTab.value = newValue;
  }
});

provide<TabsContext>('tabs', {
  activeTab,
  setActiveTab,
});
</script>

<template>
  <div :class="props.class">
    <slot />
  </div>
</template>
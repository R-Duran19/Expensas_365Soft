<template>
  <textarea
    v-bind="$attrs"
    :value="value"
    @input="onInput"
    class="w-full rounded-md border px-3 py-2 text-sm"
  />
</template>

<script setup lang="ts">
import { toRef } from 'vue';
const props = defineProps<{ modelValue?: string | number }>();
const emit = defineEmits<{ (e: 'update:modelValue', val: string): void }>();
const value = toRef(props, 'modelValue');
const onInput = (e: Event) => {
  const target = e.target as HTMLTextAreaElement | null;
  emit('update:modelValue', target ? target.value : '');
};
</script>

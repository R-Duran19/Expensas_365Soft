<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface CheckboxProps {
  class?: HTMLAttributes['class']
  checked?: boolean
  id?: string
}

const props = defineProps<CheckboxProps>()
const emit = defineEmits<{
  'update:checked': [checked: boolean]
}>()

const handleChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:checked', target.checked)
}
</script>

<template>
  <input
    type="checkbox"
    :id="id"
    :checked="checked"
    @change="handleChange"
    :class="cn(
      'h-4 w-4 rounded border border-primary text-primary focus:ring-primary focus:ring-offset-2',
      props.class
    )"
  />
</template>
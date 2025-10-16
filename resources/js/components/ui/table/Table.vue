<template>
  <div class="w-full overflow-x-auto">
    <table class="w-full min-w-full divide-y" v-bind="$attrs">
      <slot />
    </table>
  </div>
</template>

<script setup>
// simple wrapper for table element; attributes are forwarded to the table
</script>


<script setup lang="ts">
import AccesosTableRow from './AccesosTableRow.vue';

interface Role {
  id: number;
  nombre: string;
}

interface Usuario {
  id: number;
  name: string;
  email: string;
  estado: number;
  role: Role | null;
  created_at: string;
}

defineProps<{
  usuarios: Usuario[];
  roles: Role[];
}>();
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mx-6">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Nombre
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Email
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Rol
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Estado
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Acciones
          </th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        <AccesosTableRow
          v-for="usuario in usuarios"
          :key="usuario.id"
          :usuario="usuario"
          :roles="roles"
        />
      </tbody>
    </table>
  </div>
</template>
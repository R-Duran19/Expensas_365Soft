<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AccesosHeader from '@/pages/Accesos/AccesosHeader.vue';
import AccesosTable from '@/pages/Accesos/AccesosTable.vue';

interface Role {
  id: number;
  nombre: string;
  descripcion?: string;
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
  filters?: {
    search?: string;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Accesos', href: '/accesos' },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Gestión de Accesos" />
    <div class="py-6">
      <AccesosHeader :roles="roles" :filters="filters" />
      <AccesosTable :usuarios="usuarios" :roles="roles" />
    </div>
  </AppLayout>
</template>
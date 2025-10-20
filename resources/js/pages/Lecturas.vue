<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { terrenos } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Terrenos',
        href: terrenos().url,
    },
];
</script>

<template>
     <AppLayout :breadcrumbs="breadcrumbs">
        terrenos
     </AppLayout>
</template>
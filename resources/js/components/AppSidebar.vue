<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { categorias, dashboard, proyectos, terrenos, accesos } from '@/routes';
import type { NavItem } from '@/types';
import { Folder, LayoutGrid, MapPin, Tag, LockKeyhole } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';
import { useAuth } from '@/composables/useAuth';

const { hasAnyRole } = useAuth();

const allMainNavItems: NavItem[] = [
    {
        title: 'Panel de Control',
        href: dashboard().url, 
        icon: LayoutGrid,
    },
    {
        title: 'Proyectos',
        href: proyectos().url, 
        icon: Folder,
        roles: ['admin'],
    },
    {
        title: 'Terrenos',
        href: terrenos().url, 
        icon: MapPin,
        roles: ['admin'],
    },
    {
        title: 'Categorias',
        href: categorias().url, 
        icon: Tag,
        roles: ['admin'],
    },
   {
        title: 'Accesos',
        href: accesos().url, 
        icon: LockKeyhole,
        roles: ['admin'],
    },
];

const mainNavItems = computed(() => {
    return allMainNavItems.filter(item => hasAnyRole(item.roles || []));
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <AppLogo />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
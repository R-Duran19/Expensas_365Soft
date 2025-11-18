<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const expandedItems = ref<Set<string>>(new Set());

// Auto-expandir items que tienen hijos activos
const initializeExpandedItems = () => {
    const newExpanded = new Set<string>();

    // Recorrer los items del componente y expandir aquellos que tengan hijos activos
    props.items.forEach(item => {
        if (item.children && hasActiveChild(item)) {
            newExpanded.add(item.title);
        }
    });

    expandedItems.value = newExpanded;
};

const isItemExpanded = (item: NavItem) => {
    return expandedItems.value.has(item.title) || hasActiveChild(item);
};

const toggleItem = (item: NavItem) => {
    if (item.children) {
        if (expandedItems.value.has(item.title)) {
            expandedItems.value.delete(item.title);
        } else {
            expandedItems.value.add(item.title);
        }
    }
};

const isItemActive = (item: NavItem) => {
    if (!item.href) return false;
    return urlIsActive(item.href, page.url);
};

const hasActiveChild = (item: NavItem) => {
    if (!item.children) return false;
    return item.children.some(child => isItemActive(child));
};

// Inicializar los menús expandidos cuando la página cambia
watch(() => page.url, () => {
    initializeExpandedItems();
}, { immediate: true });
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Plataforma</SidebarGroupLabel>

        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <!-- Item con hijos (desplegable) -->
                <template v-if="item.children">
                    <SidebarMenuButton
                        @click="toggleItem(item)"
                        :is-active="hasActiveChild(item)"
                        :tooltip="item.title"
                        class="cursor-pointer"
                    >
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <component
                            :is="isItemExpanded(item) ? ChevronDown : ChevronRight"
                            class="ml-auto h-4 w-4"
                        />
                    </SidebarMenuButton>

                    <!-- Submenú desplegable -->
                    <SidebarMenuSub v-if="isItemExpanded(item)">
                        <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                            <SidebarMenuSubButton
                                as-child
                                :is-active="isItemActive(child)"
                            >
                                <Link :href="child.href!">
                                    <component :is="child.icon" />
                                    <span>{{ child.title }}</span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </template>

                <!-- Item sin hijos (link normal) -->
                <template v-else>
                    <SidebarMenuButton
                        as-child
                        :is-active="isItemActive(item)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href!">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </template>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

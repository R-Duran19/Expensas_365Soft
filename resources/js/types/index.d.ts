// resources/js/types/index.d.ts

import type { Component } from 'vue';
import type { LucideIcon } from 'lucide-vue-next';

// Usuario
export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    role?: UserRole | null;
}

// Rol del usuario
export interface UserRole {
    id: number;
    nombre: string;
    name: string;
    descripcion?: string;
    activo?: boolean;
}

// Autenticación
export interface Auth {
    user: User | null;
}

// Props de página base de Inertia
export interface PageProps {
    auth: Auth;
    name: string;
    quote: {
        message: string;
        author: string;
    };
    sidebarOpen: boolean;
    [key: string]: any; // Permite propiedades adicionales
}

// Item de navegación
export interface NavItem {
    title: string;
    href: string;
    icon: LucideIcon | Component; // Cambiado para aceptar LucideIcon
    roles?: string[];
    badge?: string | number;
    isActive?: boolean;
}

// Item de breadcrumb
export interface BreadcrumbItem {
    title: string;
    href: string;
    current?: boolean;
}

// Alias para compatibilidad
export type BreadcrumbItemType = BreadcrumbItem;

// Tipo helper para extender PageProps en páginas específicas
export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = 
    PageProps & T;
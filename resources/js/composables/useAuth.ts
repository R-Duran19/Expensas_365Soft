import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { PageProps } from '@/types';

export function useAuth() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user);

    /**
     * Verifica si el usuario está autenticado
     */
    const isAuthenticated = computed(() => !!user.value);

    /**
     * Verifica si el usuario tiene un rol específico
     */
    const hasRole = (roleName: string): boolean => {
        if (!user.value?.role) return false;
        return user.value.role.nombre === roleName;
    };

    /**
     * Verifica si el usuario tiene alguno de los roles especificados
     */
    const hasAnyRole = (roles: string[]): boolean => {
        if (!roles || roles.length === 0) return true;
        if (!user.value?.role) return false;
        return roles.includes(user.value.role.nombre);
    };

    /**
     * Verifica si el usuario tiene todos los roles especificados
     */
    const hasAllRoles = (roles: string[]): boolean => {
        if (!roles || roles.length === 0) return true;
        if (!user.value?.role) return false;
        return roles.length === 1 && roles.includes(user.value.role.nombre);
    };

    /**
     * Verifica si el usuario es admin
     */
    const isAdmin = computed(() => hasRole('admin'));

    /**
     * Obtiene el nombre del rol actual
     */
    const currentRole = computed(() => user.value?.role?.nombre || null);

    /**
     * Obtiene el objeto rol completo
     */
    const role = computed(() => user.value?.role || null);

    return {
        user,
        role,
        isAuthenticated,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        isAdmin,
        currentRole,
    };
}
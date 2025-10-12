<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { accesos } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';


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
    email_verified_at: string | null;
    created_at: string;
}

const confirm = useConfirm();
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accesos',
        href: accesos().url,
    },
];

const usuarios = ref<Usuario[]>([]);
const roles = ref<Role[]>([]);
const loading = ref(false);
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const selectedUser = ref<Usuario | null>(null);
const searchQuery = ref('');

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: null as number | null,
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: null as number | null,
});

const filteredUsuarios = computed(() => {
    if (!searchQuery.value) return usuarios.value;
    
    const query = searchQuery.value.toLowerCase();
    return usuarios.value.filter(usuario => 
        usuario.name.toLowerCase().includes(query) ||
        usuario.email.toLowerCase().includes(query) ||
        usuario.role?.nombre.toLowerCase().includes(query)
    );
});

const cargarUsuarios = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/accesos/listar', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        usuarios.value = data.usuarios || [];
        roles.value = data.roles || [];
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo cargar los usuarios',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const openCreateDialog = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateDialog.value = true;
};

const openEditDialog = (usuario: Usuario) => {
    selectedUser.value = usuario;
    editForm.name = usuario.name;
    editForm.email = usuario.email;
    editForm.role_id = usuario.role?.id || null;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.clearErrors();
    showEditDialog.value = true;
};

const confirmDelete = (usuario: Usuario) => {
    confirm.require({
        message: `¿Estás seguro de eliminar al usuario ${usuario.name}?`,
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancelar',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Eliminar',
            severity: 'danger'
        },
        accept: async () => {
            try {
                await axios.delete(`/accesos/${usuario.id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                await cargarUsuarios();
                toast.add({
                    severity: 'success',
                    summary: 'Usuario Eliminado',
                    detail: 'El usuario ha sido eliminado exitosamente',
                    life: 3000
                });
            } catch (error: any) {
                console.error('Error al eliminar:', error);
                const message = error.response?.data?.message || 'No se pudo eliminar el usuario';
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: message,
                    life: 3000
                });
            }
        }
    });
};

const confirmToggleStatus = (usuario: Usuario) => {
    const accion = usuario.estado === 1 ? 'desactivar' : 'activar';
    const mensaje = usuario.estado === 1 
        ? `¿Desactivar a ${usuario.name}? No podrá acceder al sistema.`
        : `¿Activar a ${usuario.name}? Podrá acceder nuevamente al sistema.`;
    
    confirm.require({
        message: mensaje,
        header: `Confirmar ${accion}`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancelar',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: accion === 'desactivar' ? 'Desactivar' : 'Activar',
            severity: accion === 'desactivar' ? 'warn' : 'success'
        },
        accept: async () => {
            try {
                await axios.post(`/accesos/${usuario.id}/toggle-status`, {}, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                await cargarUsuarios();
                toast.add({
                    severity: 'success',
                    summary: accion === 'desactivar' ? 'Usuario Desactivado' : 'Usuario Activado',
                    detail: `El usuario ha sido ${accion}do exitosamente`,
                    life: 3000
                });
            } catch (error: any) {
                console.error('Error al cambiar estado:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response?.data?.message || 'No se pudo cambiar el estado',
                    life: 3000
                });
            }
        }
    });
};

const submitCreate = async () => {
    createForm.clearErrors();
    
    try {
        await axios.post('/accesos', {
            name: createForm.name,
            email: createForm.email,
            password: createForm.password,
            password_confirmation: createForm.password_confirmation,
            role_id: createForm.role_id,
        }, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        showCreateDialog.value = false;
        createForm.reset();
        await cargarUsuarios();
        
        toast.add({
            severity: 'success',
            summary: 'Usuario Creado',
            detail: 'El usuario ha sido creado exitosamente',
            life: 3000
        });
    } catch (error: any) {
        console.error('Error al crear:', error);
        
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(key => {
                createForm.setError(key as any, errors[key][0]);
            });
        } else {
            const message = error.response?.data?.message || 'No se pudo crear el usuario';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: message,
                life: 3000
            });
        }
    }
};

const submitEdit = async () => {
    if (!selectedUser.value) return;
    
    editForm.clearErrors();
    
    try {
        const payload: any = {
            name: editForm.name,
            email: editForm.email,
            role_id: editForm.role_id,
        };
        
        if (editForm.password) {
            payload.password = editForm.password;
            payload.password_confirmation = editForm.password_confirmation;
        }
        
        await axios.put(`/accesos/${selectedUser.value.id}`, payload, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        showEditDialog.value = false;
        editForm.reset();
        selectedUser.value = null;
        await cargarUsuarios();
        
        toast.add({
            severity: 'success',
            summary: 'Usuario Actualizado',
            detail: 'Los cambios han sido guardados',
            life: 3000
        });
    } catch (error: any) {
        console.error('Error al actualizar:', error);
        
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(key => {
                editForm.setError(key as any, errors[key][0]);
            });
        } else {
            const message = error.response?.data?.message || 'No se pudo actualizar el usuario';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: message,
                life: 3000
            });
        }
    }
};

const getRoleSeverity = (roleName: string) => {
    const severities: Record<string, 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast'> = {
        admin: 'danger',
        editor: 'info',
        test: 'warn',
    };
    return severities[roleName] || 'secondary';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

onMounted(() => {
    cargarUsuarios();
});
</script>

<template>
    <Head title="Gestión de Accesos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 bg-white rounded-lg shadow-sm dark:bg-gray-900">
            <div class="flex items-center justify-between pb-4 border-b-2 border-gray-200 dark:border-gray-700">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Gestión de Accesos
                    </h1>
                    <p class="mt-2 text-base text-gray-700 dark:text-gray-300">
                        Administra usuarios y sus roles en el sistema
                    </p>
                </div>
                <Button 
                    label="Nuevo Usuario" 
                    icon="pi pi-plus" 
                    @click="openCreateDialog"
                    :disabled="loading"
                    class="font-semibold"
                />
            </div>

            <div class="flex items-center gap-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <IconField class="flex-1 max-w-md">
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText
                        v-model="searchQuery"
                        placeholder="Buscar por nombre, email o rol..."
                        class="w-full"
                        :disabled="loading"
                    />
                </IconField>
                <span class="px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-200 rounded-full dark:text-gray-200 dark:bg-gray-700">
                    {{ filteredUsuarios.length }} usuario(s)
                </span>
                <Button
                    icon="pi pi-refresh"
                    severity="secondary"
                    outlined
                    @click="cargarUsuarios"
                    :loading="loading"
                    v-tooltip.top="'Recargar'"
                />
            </div>

            <DataTable 
                :value="filteredUsuarios" 
                :loading="loading"
                paginator 
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20, 50]"
                stripedRows
                class="shadow-md"
            >
                <Column field="name" header="Nombre" sortable>
                    <template #body="{ data }">
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ data.name }}
                        </span>
                    </template>
                </Column>
                
                <Column field="email" header="Email" sortable>
                    <template #body="{ data }">
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ data.email }}
                        </span>
                    </template>
                </Column>
                
                <Column field="role.nombre" header="Rol" sortable>
                    <template #body="{ data }">
                        <Tag 
                            v-if="data.role"
                            :value="data.role.nombre"
                            :severity="getRoleSeverity(data.role.nombre)"
                            class="font-semibold"
                        />
                        <span v-else class="italic text-gray-500 dark:text-gray-400">
                            Sin rol
                        </span>
                    </template>
                </Column>

                <Column field="estado" header="Estado" sortable>
                    <template #body="{ data }">
                    <Tag 
                    :value="data.estado === 1 ? 'Activo' : 'Inactivo'"
                    :severity="data.estado === 1 ? 'success' : 'danger'"
                    :icon="data.estado === 1 ? 'pi pi-check-circle' : 'pi pi-times-circle'"
                    class="font-semibold"
                    />
                    </template>
                </Column>
                
                
                <Column field="created_at" header="Fecha de Registro" sortable>
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ formatDate(data.created_at) }}
                        </span>
                    </template>
                </Column>
                
               <Column header="Acciones" :style="{ width: '160px' }">
                <template #body="{ data }">
                        <div class="flex justify-center gap-2">
                            <Button
                                :icon="data.estado === 1 ? 'pi pi-ban' : 'pi pi-check'"
                                :severity="data.estado === 1 ? 'warn' : 'success'"
                                text
                                rounded
                                @click="confirmToggleStatus(data)"
                                :v-tooltip.top="data.estado === 1 ? 'Desactivar' : 'Activar'"
                            />

                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                @click="openEditDialog(data)"
                                v-tooltip.top="'Editar'"
                            />

                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                @click="confirmDelete(data)"
                                v-tooltip.top="'Eliminar'"
                            />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="py-12 text-center">
                        <i class="mb-4 text-5xl text-gray-400 pi pi-users"></i>
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">
                            No se encontraron usuarios
                        </p>
                    </div>
                </template>
            </DataTable>

            <Dialog 
                v-model:visible="showCreateDialog" 
                modal 
                header="Crear Nuevo Usuario"
                :style="{ width: '32rem' }"
            >
                <form @submit.prevent="submitCreate" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <label for="create-name" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Nombre completo
                        </label>
                        <InputText
                            id="create-name"
                            v-model="createForm.name"
                            placeholder="Juan Pérez"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.name }"
                        />
                        <small v-if="createForm.errors.name" class="font-medium text-red-600">
                            {{ createForm.errors.name }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label for="create-email" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Correo electrónico
                        </label>
                        <InputText
                            id="create-email"
                            v-model="createForm.email"
                            type="email"
                            placeholder="juan@ejemplo.com"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.email }"
                        />
                        <small v-if="createForm.errors.email" class="font-medium text-red-600">
                            {{ createForm.errors.email }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label for="create-role" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Rol
                        </label>
                        <Dropdown
                            id="create-role"
                            v-model="createForm.role_id"
                            :options="roles"
                            optionLabel="nombre"
                            optionValue="id"
                            placeholder="Selecciona un rol"
                            class="w-full"
                            :class="{ 'p-invalid': createForm.errors.role_id }"
                        >
                            <template #option="{ option }">
                                <div>
                                    <div class="font-semibold">{{ option.nombre }}</div>
                                    <small v-if="option.descripcion" class="text-gray-600">
                                        {{ option.descripcion }}
                                    </small>
                                </div>
                            </template>
                        </Dropdown>
                        <small v-if="createForm.errors.role_id" class="font-medium text-red-600">
                            {{ createForm.errors.role_id }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label for="create-password" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Contraseña
                        </label>
                        <Password
                            id="create-password"
                            v-model="createForm.password"
                            toggleMask
                            :feedback="false"
                            class="w-full"
                            inputClass="w-full"
                            :class="{ 'p-invalid': createForm.errors.password }"
                        />
                        <small v-if="createForm.errors.password" class="font-medium text-red-600">
                            {{ createForm.errors.password }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label for="create-password-confirmation" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Confirmar contraseña
                        </label>
                        <Password
                            id="create-password-confirmation"
                            v-model="createForm.password_confirmation"
                            toggleMask
                            :feedback="false"
                            class="w-full"
                            inputClass="w-full"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Button
                            type="button"
                            label="Cancelar"
                            severity="secondary"
                            outlined
                            @click="showCreateDialog = false"
                        />
                        <Button
                            type="submit"
                            :label="createForm.processing ? 'Creando...' : 'Crear Usuario'"
                            :loading="createForm.processing"
                        />
                    </div>
                </form>
            </Dialog>

            <Dialog 
                v-model:visible="showEditDialog" 
                modal 
                header="Editar Usuario"
                :style="{ width: '32rem' }"
            >
                <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <label for="edit-name" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Nombre completo
                        </label>
                        <InputText
                            id="edit-name"
                            v-model="editForm.name"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.name }"
                        />
                        <small v-if="editForm.errors.name" class="font-medium text-red-600">
                            {{ editForm.errors.name }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label for="edit-email" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Correo electrónico
                        </label>
                        <InputText
                            id="edit-email"
                            v-model="editForm.email"
                            type="email"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.email }"
                        />
                        <small v-if="editForm.errors.email" class="font-medium text-red-600">
                            {{ editForm.errors.email }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label for="edit-role" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Rol
                        </label>
                        <Dropdown
                            id="edit-role"
                            v-model="editForm.role_id"
                            :options="roles"
                            optionLabel="nombre"
                            optionValue="id"
                            placeholder="Selecciona un rol"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.role_id }"
                        />
                        <small v-if="editForm.errors.role_id" class="font-medium text-red-600">
                            {{ editForm.errors.role_id }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label for="edit-password" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Nueva contraseña (opcional)
                        </label>
                        <Password
                            id="edit-password"
                            v-model="editForm.password"
                            toggleMask
                            :feedback="false"
                            placeholder="Dejar vacío para no cambiar"
                            class="w-full"
                            inputClass="w-full"
                            :class="{ 'p-invalid': editForm.errors.password }"
                        />
                        <small v-if="editForm.errors.password" class="font-medium text-red-600">
                            {{ editForm.errors.password }}
                        </small>
                    </div>

                    <div class="space-y-2">
                        <label for="edit-password-confirmation" class="block font-semibold text-gray-700 dark:text-gray-200">
                            Confirmar contraseña
                        </label>
                        <Password
                            id="edit-password-confirmation"
                            v-model="editForm.password_confirmation"
                            toggleMask
                            :feedback="false"
                            placeholder="Dejar vacío para no cambiar"
                            class="w-full"
                            inputClass="w-full"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Button
                            type="button"
                            label="Cancelar"
                            severity="secondary"
                            outlined
                            @click="showEditDialog = false"
                        />
                        <Button
                            type="submit"
                            :label="editForm.processing ? 'Guardando...' : 'Guardar Cambios'"
                            :loading="editForm.processing"
                        />
                    </div>
                </form>
            </Dialog>
            <Toast position="top-right" />
            <ConfirmDialog />
        </div>
    </AppLayout>
</template>
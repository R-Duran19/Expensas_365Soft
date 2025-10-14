<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { useNotification } from '@/composables/useNotification';

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
}

const props = defineProps<{
  usuario: Usuario;
  roles: Role[];
  open: boolean;
}>();

const emit = defineEmits(['update:open']);

const { showSuccess, showError } = useNotification();

const form = useForm({
  name: props.usuario.name,
  email: props.usuario.email,
  role_id: props.usuario.role?.id || null,
  password: '',
  password_confirmation: '',
});

const handleSubmit = () => {
  form.put(`/accesos/${props.usuario.id}`, {
    onSuccess: () => {
      emit('update:open', false);
      showSuccess(
        'Usuario actualizado',
        `Los datos de ${props.usuario.name} han sido actualizados exitosamente.`
      );
      window.location.reload();
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);
      showError(
        'Error al actualizar',
        'Por favor, verifica los datos ingresados e intenta nuevamente.'
      );
    }
  });
};

const handleCancel = () => {
  emit('update:open', false);
  form.reset();
};
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[500px] dark:bg-gray-900" :show-close="false">
      <DialogHeader>
        <DialogTitle>Editar Usuario</DialogTitle>
      </DialogHeader>
      
      <form @submit.prevent="handleSubmit" class="space-y-6 px-2">
        <!-- Nombre -->
        <div class="space-y-2">
          <Label for="edit-name">Nombre completo</Label>
          <Input
            id="edit-name"
            v-model="form.name"
            required
            class="dark:bg-gray-800 dark:border-gray-700"
          />
          <p v-if="form.errors.name" class="text-sm text-red-600">
            {{ form.errors.name }}
          </p>
        </div>

        <!-- Email -->
        <div class="space-y-2">
          <Label for="edit-email">Correo electrónico</Label>
          <Input
            id="edit-email"
            v-model="form.email"
            type="email"
            required
            class="dark:bg-gray-800 dark:border-gray-700"
          />
          <p v-if="form.errors.email" class="text-sm text-red-600">
            {{ form.errors.email }}
          </p>
        </div>

        <!-- Rol -->
        <div class="space-y-2">
          <Label for="edit-role">Rol</Label>
          <select
            id="edit-role"
            v-model="form.role_id"
            required
            class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
          >
            <option :value="null" disabled>Selecciona un rol</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">
              {{ role.nombre }}
            </option>
          </select>
          <p v-if="form.errors.role_id" class="text-sm text-red-600">
            {{ form.errors.role_id }}
          </p>
        </div>

        <!-- Nueva Contraseña -->
        <div class="space-y-2">
          <Label for="edit-password">Nueva contraseña (opcional)</Label>
          <Input
            id="edit-password"
            v-model="form.password"
            type="password"
            placeholder="Dejar vacío para no cambiar"
            class="dark:bg-gray-800 dark:border-gray-700"
          />
          <p v-if="form.errors.password" class="text-sm text-red-600">
            {{ form.errors.password }}
          </p>
        </div>

        <!-- Confirmar Contraseña -->
        <div class="space-y-2">
          <Label for="edit-password-confirmation">Confirmar contraseña</Label>
          <Input
            id="edit-password-confirmation"
            v-model="form.password_confirmation"
            type="password"
            placeholder="Dejar vacío para no cambiar"
            class="dark:bg-gray-800 dark:border-gray-700"
          />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="handleCancel">
            Cancelar
          </Button>
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
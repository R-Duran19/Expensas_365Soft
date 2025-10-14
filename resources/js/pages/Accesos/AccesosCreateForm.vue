<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useNotification } from '@/composables/useNotification';

interface Role {
  id: number;
  nombre: string;
  descripcion?: string;
}

defineProps<{
  roles: Role[];
}>();

const emit = defineEmits(['save', 'cancel']);

const { showSuccess, showError } = useNotification();

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role_id: null as number | null,
});

const handleSubmit = () => {
  form.post('/accesos', {
    onSuccess: () => {
      emit('save');
      showSuccess(
        'Usuario creado',
        `El usuario ${form.name} ha sido creado exitosamente.`
      );
      form.reset();
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);
      showError(
        'Error al crear usuario',
        'Por favor, verifica los datos ingresados e intenta nuevamente.'
      );
    }
  });
};

const handleCancel = () => {
  emit('cancel');
  form.reset();
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6 px-2">
    <!-- Nombre -->
    <div class="space-y-2">
      <Label for="create-name">Nombre completo</Label>
      <Input
        id="create-name"
        v-model="form.name"
        placeholder="Juan Pérez"
        required
        class="dark:bg-gray-800 dark:border-gray-700"
      />
      <p v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400">
        {{ form.errors.name }}
      </p>
    </div>

    <!-- Email -->
    <div class="space-y-2">
      <Label for="create-email">Correo electrónico</Label>
      <Input
        id="create-email"
        v-model="form.email"
        type="email"
        placeholder="juan@ejemplo.com"
        required
        class="dark:bg-gray-800 dark:border-gray-700"
      />
      <p v-if="form.errors.email" class="text-sm text-red-600 dark:text-red-400">
        {{ form.errors.email }}
      </p>
    </div>

    <!-- Rol -->
    <div class="space-y-2">
      <Label for="create-role">Rol</Label>
      <select
        id="create-role"
        v-model="form.role_id"
        required
        class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
      >
        <option :value="null" disabled>Selecciona un rol</option>
        <option 
          v-for="role in roles" 
          :key="role.id" 
          :value="role.id"
          class="dark:bg-gray-800 dark:text-gray-100"
        >
          {{ role.nombre }}
        </option>
      </select>
      <p v-if="form.errors.role_id" class="text-sm text-red-600 dark:text-red-400">
        {{ form.errors.role_id }}
      </p>
    </div>

    <!-- Contraseña -->
    <div class="space-y-2">
      <Label for="create-password">Contraseña</Label>
      <Input
        id="create-password"
        v-model="form.password"
        type="password"
        placeholder="••••••••"
        required
        class="dark:bg-gray-800 dark:border-gray-700"
      />
      <p v-if="form.errors.password" class="text-sm text-red-600 dark:text-red-400">
        {{ form.errors.password }}
      </p>
    </div>

    <!-- Confirmar Contraseña -->
    <div class="space-y-2">
      <Label for="create-password-confirmation">Confirmar contraseña</Label>
      <Input
        id="create-password-confirmation"
        v-model="form.password_confirmation"
        type="password"
        placeholder="••••••••"
        required
        class="dark:bg-gray-800 dark:border-gray-700"
      />
    </div>

    <!-- Botones -->
    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
      <Button
        type="button"
        variant="outline"
        @click="handleCancel"
      >
        Cancelar
      </Button>
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? 'Creando...' : 'Crear Usuario' }}
      </Button>
    </div>
  </form>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useNotification } from '@/composables/useNotification';

interface Props {
  open: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  'save': [];
  'cancel': [];
}>();

const { showSuccess, showError } = useNotification();

// ==========================================
// ESTADO DEL FORMULARIO
// ==========================================
const form = ref({
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
  period_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const isLoading = ref(false);

// ==========================================
// COMPUTED
// ==========================================
const monthName = computed(() => {
  const months = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];
  return months[form.value.month - 1] || '';
});

// ==========================================
// MÉTODOS
// ==========================================
const close = () => {
  emit('update:open', false);
  emit('cancel');
  resetForm();
};

const resetForm = () => {
  form.value = {
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
    period_date: new Date().toISOString().split('T')[0],
    notes: '',
  };
};

const submit = () => {
  isLoading.value = true;

  router.post(
    '/expense-periods',
    form.value,
    {
      preserveScroll: true,
      onSuccess: (page) => {
        showSuccess('Período creado exitosamente');
        emit('save');
        close();
      },
      onError: (errors) => {
        if (errors.error) {
          showError(errors.error);
        } else {
          showError('Error al crear el período');
        }
      },
      onFinish: () => {
        isLoading.value = false;
      },
    }
  );
};

// Resetear formulario cuando se abre el diálogo
const handleOpenChange = (value: boolean) => {
  emit('update:open', value);
  if (!value) {
    resetForm();
  }
};
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="sm:max-w-[500px] dark:bg-gray-900">
      <DialogHeader>
        <DialogTitle>Crear Nuevo Período</DialogTitle>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="year">Año</Label>
            <Input
              id="year"
              v-model.number="form.year"
              type="number"
              min="2020"
              max="2100"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="month">Mes</Label>
            <Input
              id="month"
              v-model.number="form.month"
              type="number"
              min="1"
              max="12"
              required
            />
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ monthName }}
            </p>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="period_date">Fecha del Período</Label>
          <Input
            id="period_date"
            v-model="form.period_date"
            type="date"
            required
          />
        </div>

        <div class="space-y-2">
          <Label for="notes">Notas (opcional)</Label>
          <Textarea
            id="notes"
            v-model="form.notes"
            rows="3"
            placeholder="Agregar notas sobre este período..."
          />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="close" :disabled="isLoading">
            Cancelar
          </Button>
          <Button type="submit" :disabled="isLoading">
            {{ isLoading ? 'Creando...' : 'Crear Período' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>


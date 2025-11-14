<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
  year: new Date().getFullYear().toString(),
  month: (new Date().getMonth() + 1).toString(),
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
  const monthIndex = parseInt(form.value.month) - 1;
  return months[monthIndex] || '';
});

const months = [
  { value: 1, label: 'Enero' },
  { value: 2, label: 'Febrero' },
  { value: 3, label: 'Marzo' },
  { value: 4, label: 'Abril' },
  { value: 5, label: 'Mayo' },
  { value: 6, label: 'Junio' },
  { value: 7, label: 'Julio' },
  { value: 8, label: 'Agosto' },
  { value: 9, label: 'Septiembre' },
  { value: 10, label: 'Octubre' },
  { value: 11, label: 'Noviembre' },
  { value: 12, label: 'Diciembre' }
];

const years = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];
  // Años desde 2020 hasta 5 años en el futuro
  for (let year = currentYear + 5; year >= 2020; year--) {
    years.push(year);
  }
  return years;
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
    year: new Date().getFullYear().toString(),
    month: (new Date().getMonth() + 1).toString(),
    period_date: new Date().toISOString().split('T')[0],
    notes: '',
  };
};

const submit = () => {
  isLoading.value = true;

  // Convertir valores a números para el backend
  const formData = {
    ...form.value,
    year: parseInt(form.value.year),
    month: parseInt(form.value.month),
  };

  // Usar Inertia con enfoque diferente
  router.post(
    '/expense-periods',
    formData,
    {
      onSuccess: () => {
        // Mostrar notificación de éxito
        const periodInfo = `${monthName.value} ${form.value.year}`;
        showSuccess(`Período "${periodInfo}" creado exitosamente`);

        // Cerrar diálogo
        close();

        // Notificar al padre que recargue los datos
        emit('save');
      },
      onError: (errors) => {
        console.log('Errors:', errors);
        if (errors.error) {
          showError(errors.error);
        } else if (errors.year || errors.month || errors.period_date) {
          const firstError = errors.year || errors.month || errors.period_date;
          showError(Array.isArray(firstError) ? firstError[0] : firstError || 'Error en los datos');
        } else {
          showError('Error al crear el período. Verifique los datos e intente nuevamente.');
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
    <DialogContent class="sm:max-w-[500px] max-w-[95vw] dark:bg-gray-900 mx-4">
      <DialogHeader class="pb-4">
        <DialogTitle class="text-lg sm:text-xl">Crear Nuevo Período</DialogTitle>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
        <!-- Grid responsive para año y mes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
          <div class="space-y-2">
            <Label for="year" class="text-sm font-medium">Año</Label>
            <Select v-model="form.year" required>
              <SelectTrigger class="h-10 sm:h-11">
                <SelectValue placeholder="Seleccionar año" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="year in years"
                  :key="year"
                  :value="year.toString()"
                >
                  {{ year }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="month" class="text-sm font-medium">Mes</Label>
            <Select v-model="form.month" required>
              <SelectTrigger class="h-10 sm:h-11">
                <SelectValue placeholder="Seleccionar mes" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="month in months"
                  :key="month.value"
                  :value="month.value.toString()"
                >
                  {{ month.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {{ monthName }}
            </p>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="period_date" class="text-sm font-medium">Fecha del Período</Label>
          <Input
            id="period_date"
            v-model="form.period_date"
            type="date"
            required
            class="h-10 sm:h-11"
          />
        </div>

        <div class="space-y-2">
          <Label for="notes" class="text-sm font-medium">Notas (opcional)</Label>
          <Textarea
            id="notes"
            v-model="form.notes"
            rows="3"
            placeholder="Agregar notas sobre este período..."
            class="resize-none min-h-[80px] sm:min-h-[100px]"
          />
        </div>

        <!-- Footer responsive -->
        <DialogFooter class="flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-4">
          <Button
            type="button"
            variant="outline"
            @click="close"
            :disabled="isLoading"
            class="w-full sm:w-auto order-2 sm:order-1"
          >
            Cancelar
          </Button>
          <Button
            type="submit"
            :disabled="isLoading"
            class="w-full sm:w-auto order-1 sm:order-2"
          >
            {{ isLoading ? 'Creando...' : 'Crear Período' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>


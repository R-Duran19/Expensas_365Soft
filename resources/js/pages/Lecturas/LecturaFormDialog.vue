<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useNotification } from '@/composables/useNotification';
import { Gauge, Info, AlertCircle, TrendingUp, Calendar, Droplets } from 'lucide-vue-next';
import axios from 'axios';

// ==========================================
// TIPOS
// ==========================================
interface Propiedad {
  id: number;
  codigo: string;
  nombre: string;
}

interface Medidor {
  id: number;
  numero_medidor: string;
  ubicacion: string | null;
  tipo: 'domiciliario' | 'comercial';
  propiedad: Propiedad;
}

interface Lectura {
  id: number;
  medidor_id: number;
  lectura_actual: number;
  lectura_anterior: number | null;
  consumo: number;
  fecha_lectura: string;
  period_id: number | null;
  observaciones: string | null;
  medidor: Medidor;
}

interface UltimaLecturaResponse {
  lectura_anterior: number;
  fecha_ultima_lectura: string | null;
  period_id_anterior: number | null;
}

interface PeriodoActivo {
  id: number;
  nombre: string;
  mes_periodo: string;
}

interface Props {
  open: boolean;
  lectura?: Lectura | null;
  periodoActivo?: PeriodoActivo | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'close']);

// ==========================================
// COMPOSABLES
// ==========================================
const { showSuccess, showError } = useNotification();

// ==========================================
// ESTADO
// ==========================================
const isEditing = computed(() => !!props.lectura);

const form = useForm({
  medidor_id: null as number | null,
  lectura_actual: '',
  fecha_lectura: new Date().toISOString().split('T')[0],
  period_id: props.periodoActivo?.id || null,
  observaciones: ''
});

const loadingUltimaLectura = ref(false);
const ultimaLecturaInfo = ref<UltimaLecturaResponse | null>(null);

// Medidores disponibles
const medidores = ref<Medidor[]>([]);
const loadingMedidores = ref(false);

// ==========================================
// COMPUTED
// ==========================================
const medidorSeleccionado = computed(() => {
  if (!form.medidor_id) return null;
  return medidores.value.find(m => m.id === form.medidor_id);
});

const consumoCalculado = computed(() => {
  const lecturaActual = parseFloat(form.lectura_actual);
  
  if (!lecturaActual || isNaN(lecturaActual) || !ultimaLecturaInfo.value) {
    return 0;
  }
  
  // Asegurar que lectura_anterior sea un número
  const lecturaAnterior = ultimaLecturaInfo.value.lectura_anterior ?? 0;
  
  return lecturaActual - lecturaAnterior;
});

const consumoColor = computed(() => {
  const consumo = consumoCalculado.value;
  if (consumo < 0) return 'text-red-600';
  if (consumo === 0) return 'text-gray-500';
  if (consumo <= 10) return 'text-green-600';
  if (consumo <= 30) return 'text-blue-600';
  if (consumo <= 50) return 'text-yellow-600';
  return 'text-orange-600';
});

const consumoBadgeVariant = computed(() => {
  const consumo = consumoCalculado.value;
  if (consumo < 0) return 'destructive';
  if (consumo === 0) return 'secondary';
  return 'default';
});

const tieneConsumoAlto = computed(() => consumoCalculado.value > 50);
const tieneConsumoNegativo = computed(() => consumoCalculado.value < 0);

// ==========================================
// WATCHERS
// ==========================================
watch(() => props.open, async (newVal, oldVal) => {
  if (newVal) {
    if (props.lectura) {
      // Modo ver
      form.medidor_id = props.lectura.medidor_id;
      form.lectura_actual = props.lectura.lectura_actual.toString();
      form.fecha_lectura = props.lectura.fecha_lectura;
      form.period_id = props.lectura.period_id;
      form.observaciones = props.lectura.observaciones ?? '';

      ultimaLecturaInfo.value = {
        lectura_anterior: props.lectura.lectura_anterior ?? 0,
        fecha_ultima_lectura: null,
        period_id_anterior: null,
      };
    } else {
      // Modo creación
      form.reset();
      form.fecha_lectura = new Date().toISOString().split('T')[0];
      form.period_id = props.periodoActivo?.id || null;
      ultimaLecturaInfo.value = {
        lectura_anterior: 0,
        fecha_ultima_lectura: null,
        period_id_anterior: null
      };
      await cargarMedidores();
    }
  } else if (oldVal && !newVal) {
    // Limpieza suave al cerrar
    setTimeout(() => {
      if (!props.open) {
        ultimaLecturaInfo.value = {
          lectura_anterior: 0,
          fecha_ultima_lectura: null,
          period_id_anterior: null
        };
        form.lectura_actual = '';
      }
    }, 300);
  }
});

watch(() => form.medidor_id, (medidorId, oldMedidorId) => {
  // Solo obtener última lectura si cambió y no estamos en modo edición
  if (medidorId && medidorId !== oldMedidorId && !isEditing.value && props.open) {
    obtenerUltimaLectura(medidorId);
  }
});


// ==========================================
// MÉTODOS
// ==========================================
const cargarMedidores = async () => {
  loadingMedidores.value = true;
  try {
    const response = await axios.get('/api/medidores/activos');
    medidores.value = response.data;
  } catch (error) {
    console.error('Error cargando medidores:', error);
    showError('Error al cargar los medidores');
  } finally {
    loadingMedidores.value = false;
  }
};

const obtenerUltimaLectura = async (medidorId: number) => {
  loadingUltimaLectura.value = true;
  ultimaLecturaInfo.value = null;
  form.lectura_actual = '';
  
  try {
    const response = await axios.get(`/api/medidores/${medidorId}/ultima-lectura`);
    
    // Convertir a número si viene como string
    const lecturaAnterior = response.data.lectura_anterior 
      ? parseFloat(response.data.lectura_anterior) 
      : 0;
    
    ultimaLecturaInfo.value = {
      lectura_anterior: lecturaAnterior,
      fecha_ultima_lectura: response.data.fecha_ultima_lectura,
      period_id_anterior: response.data.period_id_anterior
    };
  } catch (error) {
    console.error('Error obteniendo última lectura:', error);
    ultimaLecturaInfo.value = {
      lectura_anterior: 0,
      fecha_ultima_lectura: null,
      period_id_anterior: null
    };
    showError('No se pudo obtener la última lectura');
  } finally {
    loadingUltimaLectura.value = false;
  }
};

const formatPeriodo = (periodo: string): string => {
  try {
    const [year, month] = periodo.split('-');
    const fecha = new Date(parseInt(year), parseInt(month) - 1);
    return fecha.toLocaleDateString('es-BO', { month: 'long', year: 'numeric' });
  } catch {
    return periodo;
  }
};

const formatFecha = (fecha: string): string => {
  try {
    return new Date(fecha).toLocaleDateString('es-BO', { 
      day: '2-digit', 
      month: 'long', 
      year: 'numeric' 
    });
  } catch {
    return fecha;
  }
};

const closeDialog = () => {
  // Limpiar el formulario
  form.reset();

  // Limpiar información de última lectura
  ultimaLecturaInfo.value = null;

  // Restablecer valores por defecto
  form.fecha_lectura = new Date().toISOString().split('T')[0];
  form.period_id = props.periodoActivo?.id || null;

  // Cerrar el diálogo
  emit('update:open', false);
  emit('close');
};

const submit = () => {
  if (isEditing.value) {
    closeDialog();
    return;
  }

  if (!form.medidor_id) {
    showError('Debes seleccionar un medidor');
    return;
  }

  if (!props.periodoActivo) {
    showError('No hay un período activo. Contacta al administrador.');
    return;
  }

  form.post('/lecturas', {
    preserveScroll: true,
    onSuccess: () => {
      showSuccess('Lectura registrada exitosamente');
      closeDialog();
    },
    onError: (errors) => {
      showError('Por favor corrige los errores en el formulario');
      console.error(errors);
    }
  });
};
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)" :modal="false" closable="false">
    <DialogContent class="sm:max-w-[900px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          <div class="flex items-center gap-2">
            <Gauge class="h-5 w-5 text-primary" />
            {{ isEditing ? 'Ver Detalle de Lectura' : 'Registrar Nueva Lectura' }}
          </div>
        </DialogTitle>
        <DialogDescription>
          {{ isEditing ? 'Información detallada de la lectura registrada' : 'Selecciona el medidor e ingresa la lectura actual' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Selector de Medidor -->
        <div class="space-y-2">
          <Label for="medidor_id" class="text-base font-semibold">
            Seleccionar Medidor <span class="text-destructive">*</span>
          </Label>
          
          <!-- Modo ver -->
          <div v-if="isEditing" class="p-4 bg-muted rounded-lg border-2">
            <div class="flex items-start gap-3">
              <Gauge class="h-5 w-5 text-primary mt-0.5" />
              <div class="flex-1">
                <p class="font-semibold text-lg">{{ lectura?.medidor.numero_medidor }}</p>
                <p class="text-sm text-muted-foreground mt-1">
                  {{ lectura?.medidor.propiedad.codigo }} - {{ lectura?.medidor.propiedad.nombre }}
                </p>
                <Badge v-if="lectura?.medidor.ubicacion" variant="outline" class="mt-2">
                  {{ lectura.medidor.ubicacion }}
                </Badge>
              </div>
            </div>
          </div>

          <!-- Modo crear -->
          <div v-else>
            <select
              id="medidor_id"
              v-model="form.medidor_id"
              :disabled="loadingMedidores"
              class="flex h-11 w-full rounded-lg border-2 border-input bg-background px-4 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              :class="{ 'border-destructive': form.errors.medidor_id }"
              required
            >
              <option :value="null">
                {{ loadingMedidores ? 'Cargando medidores...' : 'Seleccione un medidor' }}
              </option>
              <option v-for="medidor in medidores" :key="medidor.id" :value="medidor.id">
                {{ medidor.numero_medidor }} - {{ medidor.propiedad.codigo }} - {{ medidor.propiedad.nombre }}
              </option>
            </select>
            
            <p v-if="form.errors.medidor_id" class="text-sm text-destructive mt-1">
              {{ form.errors.medidor_id }}
            </p>

            <!-- Info del medidor seleccionado -->
            <div v-if="medidorSeleccionado" class="mt-3 p-3 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
              <div class="flex items-center gap-2 text-sm">
                <Info class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                <span class="text-blue-900 dark:text-blue-100">
                  <strong>Tipo:</strong> {{ medidorSeleccionado.tipo === 'domiciliario' ? 'Domiciliario' : 'Comercial' }}
                  <span v-if="medidorSeleccionado.ubicacion" class="ml-3">
                    <strong>Ubicación:</strong> {{ medidorSeleccionado.ubicacion }}
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de Lecturas -->
        <div v-if="!isEditing && ultimaLecturaInfo" class="space-y-3">
          <Label class="text-base font-semibold">Información de Lectura</Label>
          
          <div class="rounded-lg border-2 overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/50">
                  <TableHead class="font-semibold">Lectura Anterior</TableHead>
                  <TableHead class="font-semibold">Lectura Actual</TableHead>
                  <TableHead class="font-semibold text-right">Consumo</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow>
                  <!-- Lectura Anterior -->
                  <TableCell class="align-top py-4">
                    <div class="space-y-2">
                      <div class="flex items-center gap-2">
                        <Droplets class="h-5 w-5 text-blue-600" />
                        <span class="text-2xl font-bold text-blue-600">
                        {{ Number(ultimaLecturaInfo.lectura_anterior || 0).toFixed(3) }}
                        </span>
                        <span class="text-sm font-medium text-muted-foreground">m³</span>
                      </div>
                      <div v-if="ultimaLecturaInfo.fecha_ultima_lectura" class="text-xs text-muted-foreground flex items-center gap-1">
                        <Calendar class="h-3 w-3" />
                        {{ formatFecha(ultimaLecturaInfo.fecha_ultima_lectura) }}
                      </div>
                    </div>
                  </TableCell>

                  <!-- Lectura Actual -->
                  <TableCell class="align-top py-4">
                    <div class="space-y-2">
                      <div class="flex items-center gap-2">
                        <Input
                          v-model="form.lectura_actual"
                          type="number"
                          :readonly="isEditing"
                          min="0"
                          step="0.001"
                          placeholder="0.000"
                          class="text-2xl font-bold h-14 text-green-600 border-2"
                          :class="{ 
                            'border-destructive': form.errors.lectura_actual || tieneConsumoNegativo,
                            'border-green-500': form.lectura_actual && !tieneConsumoNegativo,
                            'bg-muted cursor-not-allowed': isEditing
                          }"
                          required
                        />
                        <span class="text-sm font-medium text-muted-foreground whitespace-nowrap">m³</span>
                      </div>
                      <p v-if="form.errors.lectura_actual" class="text-sm text-destructive">
                        {{ form.errors.lectura_actual }}
                      </p>
                    </div>
                  </TableCell>

                  <!-- Consumo -->
                  <TableCell class="align-top py-4 text-right">
                    <div v-if="form.lectura_actual" class="space-y-2">
                      <div class="flex items-center justify-end gap-2">
                        <TrendingUp :class="['h-5 w-5', consumoColor]" />
                        <span :class="['text-2xl font-bold', consumoColor]">
                          {{ consumoCalculado.toFixed(3) }}
                        </span>
                        <span class="text-sm font-medium text-muted-foreground">m³</span>
                      </div>
                      <Badge :variant="consumoBadgeVariant" class="text-xs">
                        {{ consumoCalculado >= 0 ? 'Consumo' : 'Error' }}
                      </Badge>
                    </div>
                    <div v-else class="text-muted-foreground">
                      <span class="text-lg">-</span>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Alertas de validación -->
          <Alert v-if="tieneConsumoNegativo" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <AlertDescription>
              La lectura actual no puede ser menor que la anterior. Verifica el valor ingresado.
            </AlertDescription>
          </Alert>

          <Alert v-else-if="tieneConsumoAlto" class="bg-yellow-50 border-yellow-300 dark:bg-yellow-950 dark:border-yellow-800">
            <AlertCircle class="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
            <AlertDescription class="text-yellow-800 dark:text-yellow-200">
              <strong>Consumo alto detectado ({{ consumoCalculado.toFixed(3) }} m³).</strong> 
              Verifica que la lectura sea correcta antes de continuar.
            </AlertDescription>
          </Alert>
        </div>

        <!-- Fecha y Período -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="fecha_lectura" class="text-base font-semibold">
              Fecha de Lectura <span class="text-destructive">*</span>
            </Label>
            <Input
              id="fecha_lectura"
              v-model="form.fecha_lectura"
              type="date"
              :readonly="isEditing"
              :max="new Date().toISOString().split('T')[0]"
              class="h-11 border-2"
              :class="{
                'border-destructive': form.errors.fecha_lectura,
                'bg-muted cursor-not-allowed': isEditing
              }"
              required
            />
            <p class="text-xs text-muted-foreground">
              {{ formatFecha(form.fecha_lectura) }}
            </p>
            <p v-if="form.errors.fecha_lectura" class="text-sm text-destructive">
              {{ form.errors.fecha_lectura }}
            </p>
          </div>

          <div class="space-y-2">
            <Label class="text-base font-semibold">
              Período de Facturación <span class="text-destructive">*</span>
            </Label>
            <div v-if="props.periodoActivo" class="h-11 px-4 py-2 bg-muted border-2 border-border rounded-lg flex items-center">
              <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-muted-foreground" />
                <span class="text-base font-medium">{{ props.periodoActivo.nombre }}</span>
                <Badge variant="secondary" class="ml-2">Activo</Badge>
              </div>
            </div>
            <div v-else class="h-11 px-4 py-2 bg-red-50 border-2 border-red-200 rounded-lg flex items-center">
              <div class="flex items-center gap-2 text-red-600">
                <AlertCircle class="h-4 w-4" />
                <span class="text-base">No hay período activo</span>
              </div>
            </div>
            <p class="text-xs text-muted-foreground">
              Las lecturas se registran automáticamente en el período activo actual
            </p>
            <p v-if="form.errors.period_id" class="text-sm text-destructive">
              {{ form.errors.period_id }}
            </p>
          </div>
        </div>

        <!-- Observaciones -->
        <div class="space-y-2">
          <Label for="observaciones" class="text-base font-semibold">Observaciones</Label>
          <Textarea
            id="observaciones"
            v-model="form.observaciones"
            :readonly="isEditing"
            rows="3"
            placeholder="Información adicional sobre esta lectura (opcional)..."
            class="resize-none border-2"
            :class="{ 
              'border-destructive': form.errors.observaciones,
              'bg-muted cursor-not-allowed': isEditing
            }"
          />
          <p v-if="form.errors.observaciones" class="text-sm text-destructive">
            {{ form.errors.observaciones }}
          </p>
        </div>

        <DialogFooter class="gap-2">
          <Button type="button" variant="outline" @click="closeDialog" size="lg">
            {{ isEditing ? 'Cerrar' : 'Cancelar' }}
          </Button>
          <Button
            v-if="!isEditing"
            type="submit"
            size="lg"
            :disabled="form.processing || tieneConsumoNegativo || loadingUltimaLectura || !form.lectura_actual || !props.periodoActivo"
          >
            <Gauge class="h-4 w-4 mr-2" />
            {{ form.processing ? 'Registrando...' : 'Registrar Lectura' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
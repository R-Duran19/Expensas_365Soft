<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { ArrowLeft, Save, Trash2, AlertCircle, CheckCircle2, Search } from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification'; // Importar el composable


interface Medidor {
  id: number;
  numero_medidor: string;
  ubicacion: string;
  propiedad: string;
  ultima_lectura: number | null;
  fecha_ultima_lectura: string | null;
  tipo: string;
}

interface LecturaItem {
  medidor_id: number;
  lectura_actual: number | null;
  observaciones: string;
  valida: boolean;
  error?: string;
}

interface Props {
  medidores: Medidor[];
  mesActual: string;
}

const props = defineProps<Props>();
const { showSuccess, showError, showWarning, showInfo } = useNotification(); // Usar el composable

// ==========================================
// BREADCRUMBS
// ==========================================
const breadcrumbs: BreadcrumbItemType[] = [
  { title: 'Lecturas', href: '/lecturas' },
  { title: 'Registro Masivo', href: '/lecturas/create' },
];

// ==========================================
// ESTADO
// ==========================================
const fechaLectura = ref(new Date().toISOString().split('T')[0]);
const mesPeriodo = ref(props.mesActual);
const busqueda = ref('');
const cargandoLectura = ref<number | null>(null);
const enviando = ref(false); // Nuevo estado para controlar envío

const lecturas = ref<Map<number, LecturaItem>>(new Map());

// Inicializar lecturas vacías para todos los medidores
props.medidores.forEach(medidor => {
  lecturas.value.set(medidor.id, {
    medidor_id: medidor.id,
    lectura_actual: null,
    observaciones: '',
    valida: true
  });
});

// ==========================================
// COMPUTED
// ==========================================
const medidoresFiltrados = computed(() => {
  if (!busqueda.value) return props.medidores;
  
  const termino = busqueda.value.toLowerCase();
  return props.medidores.filter(m => 
    m.numero_medidor.toLowerCase().includes(termino) ||
    m.propiedad.toLowerCase().includes(termino) ||
    m.ubicacion.toLowerCase().includes(termino)
  );
});

const lecturasValidas = computed(() => {
  return Array.from(lecturas.value.values()).filter(l => 
    l.lectura_actual !== null && l.valida
  );
});

const lecturasInvalidas = computed(() => {
  return Array.from(lecturas.value.values()).filter(l => 
    l.lectura_actual !== null && !l.valida
  );
});

const totalLecturas = computed(() => lecturasValidas.value.length);

const formularioValido = computed(() => {
  return fechaLectura.value && 
         mesPeriodo.value && 
         totalLecturas.value > 0 &&
         lecturasInvalidas.value.length === 0; // No hay lecturas inválidas
});

// ==========================================
// MÉTODOS
// ==========================================
const obtenerMedidor = (medidorId: number): Medidor | undefined => {
  return props.medidores.find(m => m.id === medidorId);
};

const validarLectura = (medidorId: number) => {
  const lectura = lecturas.value.get(medidorId);
  const medidor = obtenerMedidor(medidorId);
  
  if (!lectura || !medidor) return;

  // Reset validación
  lectura.valida = true;
  delete lectura.error;

  if (lectura.lectura_actual === null) {
    return;
  }

  // Validar que sea número positivo
  if (lectura.lectura_actual < 0) {
    lectura.valida = false;
    lectura.error = 'La lectura no puede ser negativa';
    return;
  }

  // Validar que no sea menor a la anterior
  if (medidor.ultima_lectura !== null && lectura.lectura_actual < medidor.ultima_lectura) {
    lectura.valida = false;
    lectura.error = `No puede ser menor a ${medidor.ultima_lectura}`;
    return;
  }

  // Alertar si el consumo es muy alto (más de 100 m³)
  if (medidor.ultima_lectura !== null) {
    const consumo = lectura.lectura_actual - medidor.ultima_lectura;
    if (consumo > 100) {
      lectura.error = `⚠️ Consumo alto: ${consumo} m³`;
      // Mostrar advertencia pero no marcar como inválida
      showWarning(`Consumo alto detectado en medidor ${medidor.numero_medidor}: ${consumo} m³`);
    }
  }
};

const calcularConsumo = (medidorId: number): number | null => {
  const lectura = lecturas.value.get(medidorId);
  const medidor = obtenerMedidor(medidorId);
  
  if (!lectura || !medidor || lectura.lectura_actual === null) {
    return null;
  }

  const lecturaAnterior = medidor.ultima_lectura || 0;
  return lectura.lectura_actual - lecturaAnterior;
};

const limpiarLectura = (medidorId: number) => {
  const lectura = lecturas.value.get(medidorId);
  if (lectura) {
    lectura.lectura_actual = null;
    lectura.observaciones = '';
    lectura.valida = true;
    delete lectura.error;
  }
};

const cargarUltimaLectura = async (medidorId: number) => {
  try {
    cargandoLectura.value = medidorId;
    
    const response = await fetch(`/api/medidores/${medidorId}/ultima-lectura`);
    if (!response.ok) {
      throw new Error('Error en la respuesta del servidor');
    }
    
    const data = await response.json();
    
    const medidor = obtenerMedidor(medidorId);
    if (medidor && data.lectura_anterior) {
      // Actualizar la última lectura en el objeto medidor
      medidor.ultima_lectura = data.lectura_anterior;
      medidor.fecha_ultima_lectura = data.fecha_ultima_lectura;
      
      showSuccess(`Última lectura cargada: ${data.lectura_anterior} m³`);
    } else {
      showInfo('No se encontró lectura anterior para este medidor');
    }
  } catch (error) {
    showError('Error al cargar última lectura');
    console.error(error);
  } finally {
    cargandoLectura.value = null;
  }
};

const validarAntesDeEnviar = (): boolean => {
  // Validar campos requeridos
  if (!fechaLectura.value) {
    showError('La fecha de lectura es requerida');
    return false;
  }

  if (!mesPeriodo.value) {
    showError('El período es requerido');
    return false;
  }

  // Validar que haya al menos una lectura
  if (totalLecturas.value === 0) {
    showError('Debe ingresar al menos una lectura válida');
    return false;
  }

  // Validar que no haya lecturas inválidas
  if (lecturasInvalidas.value.length > 0) {
    showError(`Hay ${lecturasInvalidas.value.length} lectura(s) con errores. Corríjalas antes de continuar.`);
    return false;
  }

  return true;
};

const guardarLecturas = async () => {
  if (!validarAntesDeEnviar()) {
    return;
  }

  enviando.value = true;

  try {
    const lecturasParaEnviar = lecturasValidas.value.map(l => ({
      medidor_id: l.medidor_id,
      lectura_actual: l.lectura_actual,
      observaciones: l.observaciones || null
    }));

    await router.post('/lecturas/masivo', {
      fecha_lectura: fechaLectura.value,
      mes_periodo: mesPeriodo.value,
      lecturas: lecturasParaEnviar
    }, {
      onSuccess: () => {
        showSuccess(`¡${totalLecturas.value} lectura(s) registradas exitosamente!`);
        router.visit('/lecturas');
      },
      onError: (errors) => {
        let mensajeError = 'Error al registrar las lecturas';
        
        if (errors.lecturas) {
          mensajeError = `Errores en las lecturas: ${errors.lecturas}`;
        } else if (errors.fecha_lectura) {
          mensajeError = `Error en fecha: ${errors.fecha_lectura}`;
        } else if (errors.mes_periodo) {
          mensajeError = `Error en período: ${errors.mes_periodo}`;
        }
        
        showError(mensajeError);
        
        // Mostrar errores específicos si existen
        if (errors && typeof errors === 'object') {
          Object.entries(errors).forEach(([key, value]) => {
            if (key !== 'lecturas' && key !== 'fecha_lectura' && key !== 'mes_periodo') {
              console.error(`Error en ${key}:`, value);
            }
          });
        }
      }
    });
  } catch (error) {
    showError('Error de conexión al enviar las lecturas');
    console.error('Error:', error);
  } finally {
    enviando.value = false;
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Registro Masivo de Lecturas" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Configuración General -->
                <Card>
                    <CardHeader>
                        <CardTitle>Configuración General</CardTitle>
                        <CardDescription>
                            Define la fecha y período para todas las lecturas
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <Label for="fecha_lectura">Fecha de Lectura *</Label>
                                <Input id="fecha_lectura" v-model="fechaLectura" type="date"
                                    :max="new Date().toISOString().split('T')[0]" required />
                            </div>

                            <div class="space-y-2">
                                <Label for="mes_periodo">Período *</Label>
                                <Input id="mes_periodo" v-model="mesPeriodo" type="month" required />
                                <p class="text-sm text-muted-foreground">
                                    {{ formatPeriodo(mesPeriodo) }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>Estadísticas</Label>
                                <div class="flex items-center gap-4 pt-2">
                                    <Badge variant="secondary" class="text-base">
                                        <CheckCircle2 class="h-4 w-4 mr-1" />
                                        {{ totalLecturas }} lecturas
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tabla de Lecturas -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Medidores</CardTitle>
                                <CardDescription>
                                    Ingresa las lecturas para cada medidor
                                </CardDescription>
                            </div>
                            <!-- Buscador -->
                          <div class="px-6 pb-4">
                            <div class="relative">
                              <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                              <Input
                                v-model="busqueda"
                                placeholder="Buscar por medidor, propiedad o ubicación..."
                                class="pl-9"
                              />
                            </div>
                          </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-32">Medidor</TableHead>
                                        <TableHead>Propiedad</TableHead>
                                        <TableHead>Ubicación</TableHead>
                                        <TableHead class="text-right w-32">Lectura Anterior</TableHead>
                                        <TableHead class="w-40">Lectura Actual *</TableHead>
                                        <TableHead class="text-right w-32">Consumo</TableHead>
                                        <!-- <TableHead class="w-48">Observaciones</TableHead> -->
                                        <TableHead class="w-24"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="medidor in medidoresFiltrados" :key="medidor.id"
                                        :class="{ 'bg-red-50': !lecturas.get(medidor.id)?.valida }">
                                        <TableCell class="font-medium">
                                            {{ medidor.numero_medidor }}
                                        </TableCell>
                                        <TableCell>{{ medidor.propiedad }}</TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ medidor.ubicacion }}
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex flex-col items-end">
                                                <span class="font-medium">
                                                    {{ medidor.ultima_lectura ?? 0 }} m³
                                                </span>
                                                <span v-if="medidor.fecha_ultima_lectura"
                                                    class="text-xs text-muted-foreground">
                                                    {{ new
                                                    Date(medidor.fecha_ultima_lectura).toLocaleDateString('es-BO') }}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Input v-model.number="lecturas.get(medidor.id)!.lectura_actual"
                                                type="number" min="0" step="1" placeholder="0"
                                                @input="validarLectura(medidor.id)"
                                                :class="{ 'border-red-500': !lecturas.get(medidor.id)?.valida }" />
                                            <p v-if="lecturas.get(medidor.id)?.error" class="text-xs mt-1"
                                                :class="lecturas.get(medidor.id)?.valida ? 'text-yellow-600' : 'text-red-600'">
                                                {{ lecturas.get(medidor.id)?.error }}
                                            </p>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <span v-if="calcularConsumo(medidor.id) !== null" class="font-medium"
                                                :class="{
                              'text-green-600': calcularConsumo(medidor.id)! >= 0 && calcularConsumo(medidor.id)! <= 50,
                              'text-yellow-600': calcularConsumo(medidor.id)! > 50 && calcularConsumo(medidor.id)! <= 100,
                              'text-red-600': calcularConsumo(medidor.id)! > 100
                            }">
                                                {{ calcularConsumo(medidor.id) }} m³
                                            </span>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </TableCell>
                                        <!-- <TableCell>
                                            <Input v-model="lecturas.get(medidor.id)!.observaciones"
                                                placeholder="Opcional" maxlength="100" />
                                        </TableCell> -->
                                        <TableCell>
                                            <Button v-if="lecturas.get(medidor.id)?.lectura_actual !== null"
                                                variant="ghost" size="icon" @click="limpiarLectura(medidor.id)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="medidoresFiltrados.length === 0">
                                        <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
                                            No se encontraron medidores
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div v-if="totalLecturas === 0"
                            class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md flex items-start gap-2">
                            <AlertCircle class="h-5 w-5 text-yellow-600 mt-0.5" />
                            <div>
                                <p class="text-sm font-medium text-yellow-800">
                                    No hay lecturas ingresadas
                                </p>
                                <p class="text-sm text-yellow-700">
                                    Ingresa al menos una lectura para poder guardar
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Botones de Acción -->
                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="router.visit('/lecturas')">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Cancelar
                    </Button>
                    <Button @click="guardarLecturas" :disabled="!formularioValido || enviando">
                        <Save class="h-4 w-4 mr-2" />
                        {{ enviando ? 'Guardando...' : `Guardar ${totalLecturas} Lectura(s)` }}
                    </Button>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
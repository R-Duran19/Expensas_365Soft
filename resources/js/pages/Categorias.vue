<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { categorias } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

// PrimeVue
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Toolbar from 'primevue/toolbar';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Categorias', href: categorias().url },
];

// Toast & Confirm
const toast = useToast();
const confirm = useConfirm();

// Estado
const categoriasData = ref<Array<any>>([]);
const loading = ref(false);
const showDialog = ref(false);
const form = reactive({ 
  id: null as number | null, 
  nombre: '', 
  idproyecto: null as number | null, 
  estado: true 
});
const filtro = ref('');

// Filtrado
const categoriasFiltradas = computed(() => {
  if (!filtro.value) return categoriasData.value;
  return categoriasData.value.filter(c =>
    c.nombre.toLowerCase().includes(filtro.value.toLowerCase())
  );
});

// Cargar categorías
const cargarCategorias = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/categorias_terrenos');
    categoriasData.value = data.data;
  } catch (error) {
    console.error(error);
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'No se pudo cargar categorías',
      life: 3000 
    });
  } finally {
    loading.value = false;
  }
};

// Guardar categoría
const guardarCategoria = async () => {
  if (!form.nombre.trim()) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Advertencia', 
      detail: 'El nombre es requerido',
      life: 3000 
    });
    return;
  }

  try {
    if (form.id) {
      await axios.put(`/categorias_terrenos/${form.id}`, form);
      toast.add({ 
        severity: 'success', 
        summary: 'Actualizado', 
        detail: 'Categoría actualizada correctamente',
        life: 3000 
      });
    } else {
      await axios.post('/categorias_terrenos', form);
      toast.add({ 
        severity: 'success', 
        summary: 'Creado', 
        detail: 'Categoría creada correctamente',
        life: 3000 
      });
    }
    showDialog.value = false;
    await cargarCategorias();
    limpiarFormulario();
  } catch (error) {
    console.error(error);
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'No se pudo guardar la categoría',
      life: 3000 
    });
  }
};

// Editar categoría
const editarCategoria = (categoria: any) => {
  form.id = categoria.id;
  form.nombre = categoria.nombre;
  form.idproyecto = categoria.idproyecto;
  form.estado = categoria.estado;
  showDialog.value = true;
};

// Activar / desactivar con confirmación
const toggleEstado = (categoria: any) => {
  confirm.require({
    message: `¿Está seguro de ${categoria.estado ? 'desactivar' : 'activar'} la categoría "${categoria.nombre}"?`,
    header: 'Confirmación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí',
    rejectLabel: 'No',
    accept: async () => {
      try {
        await axios.patch(
          `/categorias_terrenos/${categoria.estado ? 'desactivar' : 'activar'}/${categoria.id}`
        );
        toast.add({
          severity: 'success',
          summary: 'Éxito',
          detail: `Categoría ${categoria.estado ? 'desactivada' : 'activada'} correctamente`,
          life: 3000
        });
        await cargarCategorias();
      } catch (error) {
        console.error(error);
        toast.add({ 
          severity: 'error', 
          summary: 'Error', 
          detail: 'No se pudo cambiar el estado',
          life: 3000 
        });
      }
    }
  });
};

// Nueva categoría
const nuevaCategoria = () => {
  limpiarFormulario();
  showDialog.value = true;
};

// Limpiar formulario
const limpiarFormulario = () => {
  form.id = null;
  form.nombre = '';
  form.idproyecto = null;
  form.estado = true;
};

// Exportar CSV
const exportar = () => {
  let csv = 'Nombre,Proyecto,Estado\n';
  categoriasData.value.forEach(c => {
    csv += `${c.nombre},${c.proyecto?.nombre || ''},${c.estado ? 'Activo' : 'Inactivo'}\n`;
  });
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', 'categorias_terrenos.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  toast.add({ 
    severity: 'info', 
    summary: 'Exportado', 
    detail: 'Archivo descargado correctamente',
    life: 3000 
  });
};

// Template para estado usando Tag de PrimeVue
const estadoBodyTemplate = (rowData: any) => {
  return rowData.estado 
    ? { severity: 'success', value: 'Activo' }
    : { severity: 'danger', value: 'Inactivo' };
};

onMounted(() => cargarCategorias());
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Categorías de Terrenos" />

    <Card>
      <!-- Toolbar de PrimeVue -->
      <template #content>
        <Toolbar class="mb-4">
          <template #start>
            <div class="flex gap-2 align-items-center">
              <span class="p-input-icon-left">
                <i class="pi pi-search" />
                <InputText 
                  v-model="filtro" 
                  placeholder="Buscar por nombre..." 
                  style="width: 300px"
                />
              </span>
              <small class="text-muted">
                {{ categoriasFiltradas.length }} categorías encontradas
              </small>
            </div>
          </template>

          <template #end>
            <div class="flex gap-2">
              <Button 
                label="Exportar" 
                icon="pi pi-file-export" 
                severity="secondary"
                @click="exportar"
                :disabled="categoriasData.length === 0"
              />
              <Button 
                label="Nueva Categoría" 
                icon="pi pi-plus" 
                severity="success"
                @click="nuevaCategoria"
              />
            </div>
          </template>
        </Toolbar>

        <!-- DataTable de PrimeVue -->
        <DataTable 
          :value="categoriasFiltradas" 
          :loading="loading"
          striped-rows
          paginator
          :rows="10"
          :rows-per-page-options="[5, 10, 25, 50]"
          paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          current-page-report-template="Mostrando {first} a {last} de {totalRecords} categorías"
          responsive-layout="scroll"
          :global-filter-fields="['nombre', 'proyecto.nombre']"
        >
          <template #empty>
            <div class="p-4 text-center">
              <i class="pi pi-inbox" style="font-size: 3rem; color: var(--text-color-secondary)"></i>
              <p class="mt-3">No hay categorías disponibles</p>
            </div>
          </template>

          <template #loading>
            Cargando categorías...
          </template>

          <Column field="nombre" header="Nombre" sortable>
            <template #body="{ data }">
              <strong>{{ data.nombre }}</strong>
            </template>
          </Column>

          <Column field="proyecto.nombre" header="Proyecto" sortable>
            <template #body="{ data }">
              {{ data.proyecto?.nombre || '—' }}
            </template>
          </Column>

          <Column field="estado" header="Estado" sortable style="width: 120px">
            <template #body="{ data }">
              <Tag 
                :value="estadoBodyTemplate(data).value" 
                :severity="estadoBodyTemplate(data).severity"
              />
            </template>
          </Column>

          <Column header="Acciones" style="width: 150px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button 
                  icon="pi pi-pencil" 
                  severity="info"
                  text
                  rounded
                  @click="editarCategoria(data)"
                  v-tooltip.top="'Editar'"
                />
                <Button 
                  :icon="data.estado ? 'pi pi-times' : 'pi pi-check'"
                  :severity="data.estado ? 'danger' : 'success'"
                  text
                  rounded
                  @click="toggleEstado(data)"
                  v-tooltip.top="data.estado ? 'Desactivar' : 'Activar'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog de PrimeVue -->
    <Dialog 
      v-model:visible="showDialog" 
      :header="form.id ? 'Editar Categoría' : 'Nueva Categoría'"
      modal
      :style="{ width: '500px' }"
      :draggable="false"
    >
      <div class="flex gap-3 mt-3 flex-column">
        <div class="flex gap-2 flex-column">
          <label for="nombre" class="font-semibold">Nombre de la Categoría *</label>
          <InputText 
            id="nombre"
            v-model="form.nombre" 
            placeholder="Ingrese el nombre de la categoría" 
            autofocus
            :invalid="!form.nombre && form.nombre !== ''"
          />
        </div>

        <div class="flex gap-2 flex-column">
          <label for="idproyecto" class="font-semibold">ID Proyecto</label>
          <InputText 
            id="idproyecto"
            v-model="form.idproyecto" 
            placeholder="Ingrese el ID del proyecto (opcional)"
            type="number"
          />
        </div>
      </div>

      <template #footer>
        <Button 
          label="Cancelar" 
          icon="pi pi-times" 
          text
          @click="showDialog = false"
        />
        <Button 
          label="Guardar" 
          icon="pi pi-check" 
          @click="guardarCategoria"
          :disabled="!form.nombre.trim()"
        />
      </template>
    </Dialog>

    <!-- Toast de PrimeVue -->
    <Toast position="top-right" />

    <!-- ConfirmDialog de PrimeVue -->
    <ConfirmDialog />
  </AppLayout>
</template>

<style scoped>
/* Estilos adicionales si es necesario */
.text-muted {
  color: var(--text-color-secondary);
}
</style>
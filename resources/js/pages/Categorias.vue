<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Card from 'primevue/card';
import Message from 'primevue/message';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';

const { appearance } = useAppearance();
</script>

<template>

    <Head title="Test de Tema Completo" />
    <AppLayout>
        <div class="min-h-screen p-8 space-y-12">

            <!-- HEADER -->
            <div class="flex items-center justify-between bg-card rounded-lg p-6 shadow-soft border-2 border-border">
                <div>
                    <h1 class="text-4xl font-bold text-foreground mb-2">
                        Guía de Variables de Tema
                    </h1>
                    <p class="text-muted-foreground">
                        Modo actual: <strong>{{ appearance }}</strong>
                    </p>
                </div>
                <AppearanceTabs />
            </div>

            <!-- SECCIÓN 1: VARIABLES DE FONDO -->
            <Card>
                <template #title>
                    <h2 class="text-2xl font-semibold">1. Variables de Fondo</h2>
                </template>
                <template #content>
                    <div class="space-y-6">
                        <p class="text-muted-foreground text-sm">Estos colores cambian según el tema (claro/oscuro)</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Background -->
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Fondo Principal</p>
                                <div class="h-24 rounded-lg border-2 border-border"
                                    style="background-color: var(--background)"></div>
                                <code class="text-xs text-muted-foreground">var(--background)</code>
                                <p class="text-xs text-muted-foreground">Modo claro: #dceaff | Modo oscuro: #1e293b</p>
                            </div>

                            <!-- Card -->
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Fondo Tarjeta</p>
                                <div class="h-24 rounded-lg border-2 border-border"
                                    style="background-color: var(--card)"></div>
                                <code class="text-xs text-muted-foreground">var(--card)</code>
                                <p class="text-xs text-muted-foreground">Modo claro: #288fb8 | Modo oscuro: #334155</p>
                            </div>

                            <!-- Popover -->
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Fondo Popover</p>
                                <div class="h-24 rounded-lg border-2 border-border"
                                    style="background-color: var(--popover)"></div>
                                <code class="text-xs text-muted-foreground">var(--popover)</code>
                                <p class="text-xs text-muted-foreground">Modo claro: #ffffff | Modo oscuro: #334155</p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- SECCIÓN 2: VARIABLES DE TEXTO -->
            <Card>
                <template #title>
                    <h2 class="text-2xl font-semibold">2. Variables de Texto</h2>
                </template>
                <template #content>
                    <div class="space-y-6">
                        <p class="text-muted-foreground text-sm">Colores de texto que contrastan con los fondos</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Foreground -->
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Texto Principal</p>
                                <div class="h-20 rounded-lg border-2 border-border p-4 flex items-center justify-center"
                                    style="background-color: var(--card); color: var(--foreground)">
                                    <span style="color: var(--foreground)">Este es el texto principal</span>
                                </div>
                                <code class="text-xs text-muted-foreground">var(--foreground)</code>
                            </div>

                            <!-- Muted Foreground -->
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Texto Secundario</p>
                                <div class="h-20 rounded-lg border-2 border-border p-4 flex items-center justify-center"
                                    style="background-color: var(--card); color: var(--muted-foreground)">
                                    <span style="color: var(--muted-foreground)">Este es texto más subtle</span>
                                </div>
                                <code class="text-xs text-muted-foreground">var(--muted-foreground)</code>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- SECCIÓN 3: COLORES DE ACENTO -->
            <Card>
                <template #title>
                    <h2 class="text-2xl font-semibold">3. Colores de Acento (Primario, Secundario, Error, Warning)</h2>
                </template>
                <template #content>
                    <div class="space-y-8">
                        <p class="text-muted-foreground text-sm">Los 4 colores principales para acciones y estados</p>

                        <!-- Paleta de colores -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Primario (Azul)</p>
                                <div class="h-24 rounded-lg border-2 border-border"
                                    style="background-color: var(--primary)"></div>
                                <code class="text-xs text-muted-foreground">var(--primary)</code>
                                <p class="text-xs text-muted-foreground">Claro: #3b82f6 | Oscuro: #60a5fa</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Secundario (Verde)</p>
                                <div class="h-24 rounded-lg border-2 border-border"
                                    style="background-color: var(--secondary)"></div>
                                <code class="text-xs text-muted-foreground">var(--secondary)</code>
                                <p class="text-xs text-muted-foreground">Claro: #10b981 | Oscuro: #34d399</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Destructivo (Rojo)</p>
                                <div class="h-24 rounded-lg border-2 border-border"
                                    style="background-color: var(--destructive)"></div>
                                <code class="text-xs text-muted-foreground">var(--destructive)</code>
                                <p class="text-xs text-muted-foreground">Claro: #ef4444 | Oscuro: #f87171</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Advertencia (Ámbar)</p>
                                <div class="h-24 rounded-lg border-2 border-border"
                                    style="background-color: var(--warning)"></div>
                                <code class="text-xs text-muted-foreground">var(--warning)</code>
                                <p class="text-xs text-muted-foreground">Claro: #f59e0b | Oscuro: #fbbf24</p>
                            </div>
                        </div>

                        <!-- Botones con variables -->
                        <div class="space-y-3">
                            <p class="text-sm font-semibold text-foreground">Ejemplo: Botones</p>
                            <div class="flex flex-wrap gap-3">
                                <Button label="Primario" />
                                <Button label="Secundario" severity="secondary" />
                                <Button label="Éxito" severity="success" />
                                <Button label="Peligro" severity="danger" />
                                <Button label="Advertencia" severity="warn" />
                                <Button label="Outlined" outlined />
                                <Button label="Text" text />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- SECCIÓN 4: VARIABLES DE UTILIDAD -->
            <Card>
                <template #title>
                    <h2 class="text-2xl font-semibold">4. Variables de Utilidad</h2>
                </template>
                <template #content>
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Borde</p>
                                <div class="h-20 rounded-lg p-4 flex items-center"
                                    style="border: 2px solid var(--border); background-color: var(--card)">
                                    <span class="text-sm" style="color: var(--muted-foreground)">Con
                                        var(--border)</span>
                                </div>
                                <code class="text-xs text-muted-foreground">var(--border)</code>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Input (Focus)</p>
                                <InputText placeholder="Haz focus aquí" class="w-full" />
                                <code class="text-xs text-muted-foreground">var(--input) + var(--ring)</code>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-foreground">Accent</p>
                                <div class="h-20 rounded-lg p-4 flex items-center"
                                    style="background-color: var(--accent); color: var(--accent-foreground)">
                                    <span class="text-sm">Hover o resalte</span>
                                </div>
                                <code class="text-xs text-muted-foreground">var(--accent)</code>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- SECCIÓN 5: GRADILLA DE COLORES TAILWIND -->
            <Card>
                <template #title>
                    <h2 class="text-2xl font-semibold">5. Gradilla de Colores Tailwind (primary-50 a primary-950)</h2>
                </template>
                <template #content>
                    <div class="space-y-6">
                        <!-- Primario -->
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-foreground">Color Primario (Azul)</p>
                            <div class="grid grid-cols-11 gap-1">
                                <div v-for="shade in ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950']"
                                    :key="shade"
                                    class="h-16 rounded-lg border border-border flex items-center justify-center text-xs font-semibold"
                                    :style="{ backgroundColor: `var(--color-primary-${shade})` }">
                                    {{ shade }}
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground">Usa: <code
                                    class="bg-muted px-2 py-1 rounded">bg-primary-500</code> o <code
                                    class="bg-muted px-2 py-1 rounded">text-primary-600</code></p>
                        </div>

                        <!-- Secundario -->
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-foreground">Color Secundario (Verde)</p>
                            <div class="grid grid-cols-11 gap-1">
                                <div v-for="shade in ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950']"
                                    :key="shade"
                                    class="h-16 rounded-lg border border-border flex items-center justify-center text-xs font-semibold"
                                    :style="{ backgroundColor: `var(--color-secondary-${shade})` }">
                                    {{ shade }}
                                </div>
                            </div>
                        </div>

                        <!-- Error -->
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-foreground">Color Error (Rojo)</p>
                            <div class="grid grid-cols-11 gap-1">
                                <div v-for="shade in ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950']"
                                    :key="shade"
                                    class="h-16 rounded-lg border border-border flex items-center justify-center text-xs font-semibold"
                                    :style="{ backgroundColor: `var(--color-error-${shade})` }">
                                    {{ shade }}
                                </div>
                            </div>
                        </div>

                        <!-- Warning -->
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-foreground">Color Warning (Ámbar)</p>
                            <div class="grid grid-cols-11 gap-1">
                                <div v-for="shade in ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950']"
                                    :key="shade"
                                    class="h-16 rounded-lg border border-border flex items-center justify-center text-xs font-semibold"
                                    :style="{ backgroundColor: `var(--color-warning-${shade})` }">
                                    {{ shade }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- SECCIÓN 6: EJEMPLOS PRÁCTICOS -->
            <Card>
                <template #title>
                    <h2 class="text-2xl font-semibold">6. Ejemplos Prácticos de Uso</h2>
                </template>
                <template #content>
                    <div class="space-y-6">
                        <!-- Alert Success -->
                        <div class="p-4 rounded-lg border-l-4"
                            style="background-color: var(--secondary); border-left-color: var(--secondary); color: var(--secondary-foreground)">
                            <p class="font-semibold">✓ Éxito</p>
                            <p class="text-sm opacity-90">Usa <code>var(--secondary)</code> +
                                <code>var(--secondary-foreground)</code>
                            </p>
                        </div>

                        <!-- Alert Error -->
                        <div class="p-4 rounded-lg border-l-4"
                            style="background-color: var(--destructive); border-left-color: var(--destructive); color: var(--destructive-foreground)">
                            <p class="font-semibold">✗ Error</p>
                            <p class="text-sm opacity-90">Usa <code>var(--destructive)</code> +
                                <code>var(--destructive-foreground)</code>
                            </p>
                        </div>

                        <!-- Alert Warning -->
                        <div class="p-4 rounded-lg border-l-4"
                            style="background-color: var(--warning); border-left-color: var(--warning); color: var(--warning-foreground)">
                            <p class="font-semibold">⚠ Advertencia</p>
                            <p class="text-sm opacity-90">Usa <code>var(--warning)</code> +
                                <code>var(--warning-foreground)</code>
                            </p>
                        </div>

                        <!-- Card personalizada -->
                        <div class="p-6 rounded-lg border-2"
                            style="background-color: var(--card); border-color: var(--border); color: var(--card-foreground)">
                            <h3 class="text-lg font-semibold mb-2" style="color: var(--foreground)">Card Personalizada
                            </h3>
                            <p class="mb-4" style="color: var(--muted-foreground)">Esta card usa todas las variables de
                                tema</p>
                            <div class="flex gap-2">
                                <button class="px-4 py-2 rounded-lg font-semibold"
                                    style="background-color: var(--primary); color: var(--primary-foreground)">
                                    Acción
                                </button>
                                <button class="px-4 py-2 rounded-lg font-semibold border-2"
                                    style="border-color: var(--primary); color: var(--primary); background-color: transparent">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- SECCIÓN 7: CÓDIGO DE EJEMPLO -->
            <Card>
                <template #title>
                    <h2 class="text-2xl font-semibold">7. Cómo Usar en Tu Código</h2>
                </template>
                <template #content>
                    <div class="space-y-4">
                        <div class="border-l-4 border-primary pl-4 py-2 space-y-2">
                            <p class="font-semibold text-foreground text-sm">Opción 1: Style binding (Vue)</p>
                            <pre class="bg-muted p-3 rounded text-xs text-foreground overflow-x-auto"><code>&lt;div :style="{ 
  backgroundColor: 'var(--card)',
  color: 'var(--foreground)',
  borderColor: 'var(--border)'
}"&gt;
  Contenido
&lt;/div&gt;</code></pre>
                        </div>

                        <div class="border-l-4 border-primary pl-4 py-2 space-y-2">
                            <p class="font-semibold text-foreground text-sm">Opción 2: Tailwind con variables</p>
                            <pre class="bg-muted p-3 rounded text-xs text-foreground overflow-x-auto"><code>&lt;div class="bg-[--card] text-[--foreground] 
         border-2 border-[--border] rounded-lg p-4"&gt;
  Contenido
&lt;/div&gt;</code></pre>
                        </div>

                        <div class="border-l-4 border-primary pl-4 py-2 space-y-2">
                            <p class="font-semibold text-foreground text-sm">Opción 3: CSS puro</p>
                            <pre class="bg-muted p-3 rounded text-xs text-foreground overflow-x-auto"><code>.mi-card {
  background-color: var(--card);
  color: var(--card-foreground);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}</code></pre>
                        </div>

                        <div class="border-l-4 border-primary pl-4 py-2 space-y-2">
                            <p class="font-semibold text-foreground text-sm">Opción 4: PrimeVue (automático)</p>
                            <pre class="bg-muted p-3 rounded text-xs text-foreground overflow-x-auto"><code>&lt;Button label="Click" severity="primary" /&gt;
&lt;Card&gt;
  &lt;template #title&gt;Título&lt;/template&gt;
  &lt;template #content&gt;Contenido&lt;/template&gt;
&lt;/Card&gt;</code></pre>
                            <p class="text-xs text-muted-foreground">PrimeVue respeta automáticamente tus variables
                                definidas en presets.ts</p>
                        </div>
                    </div>
                </template>
            </Card>
            <div class="flex">
                <Button 
                label="Volver a Categorías" 
                icon="pi pi-arrow-left" 
                class="px-2 py-1 font-semibold"
                style="background-color: var(--primary); color: var(--primary-foreground) "/>


            </div>

        </div>
    </AppLayout>
</template>
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    paciente: Object,
    fechasDisponibles: {
        type: Array,
        default: () => [],
    },
    filtros: {
        type: Object,
        default: () => ({}),
    },
    comparacion: {
        type: Object,
        default: null,
    },
    datosGrafica: {
        type: Array,
        default: () => [],
    },
    metasNutricionales: {
        type: Object,
        default: () => ({}),
    },
});

const fechaA = ref(props.filtros?.fecha_a ?? '');
const fechaB = ref(props.filtros?.fecha_b ?? '');
const canvasRef = ref(null);
let chartInstance = null;
const pestañaActiva = ref('calorias'); // 'calorias' o 'macros'

const expandirNotaA = ref(false);
const expandirNotaB = ref(false);
const expandirCambioA = ref(false);
const expandirCambioB = ref(false);

const puedeComparar = computed(() => {
    return Boolean(fechaA.value && fechaB.value && fechaA.value !== fechaB.value);
});

const fechasIguales = computed(() => {
    return Boolean(fechaA.value && fechaB.value && fechaA.value === fechaB.value);
});

const comparar = () => {
    if (!puedeComparar.value) {
        return;
    }

    router.get(
        route('nutricionista.seguimientos.comparativo', props.paciente.cod_pacientes),
        {
            fecha_a: fechaA.value,
            fecha_b: fechaB.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const limpiar = () => {
    fechaA.value = '';
    fechaB.value = '';

    router.get(
        route('nutricionista.seguimientos.comparativo', props.paciente.cod_pacientes),
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

const similitud = computed(() => {
    return Number(props.comparacion?.similitud || 0);
});

const huboCambioSignificativo = computed(() => {
    return Boolean(props.comparacion?.hubo_cambio_significativo);
});

const estadoCaso = computed(() => {
    if (!props.comparacion) {
        return {
            label: 'Comparativa pendiente',
            tone: 'neutral',
        };
    }
    return huboCambioSignificativo.value
        ? { label: 'Variación clínica', tone: 'warning' }
        : { label: 'Evolución estable', tone: 'success' };
});

const tonoSimilitud = computed(() => {
    if (similitud.value >= 80) return 'success';
    if (similitud.value >= 50) return 'warning';
    return 'danger';
});

const dialColorClass = computed(() => {
    const pct = similitud.value;
    if (pct >= 80) return 'text-emerald-500';
    if (pct >= 50) return 'text-amber-500';
    return 'text-rose-500';
});

const dialBgColorClass = computed(() => {
    const pct = similitud.value;
    if (pct >= 80) return 'bg-emerald-50/50 border-emerald-100 text-emerald-900';
    if (pct >= 50) return 'bg-amber-50/50 border-amber-100 text-amber-900';
    return 'bg-rose-50/50 border-rose-100 text-rose-900';
});

const textoCorto = (texto, limite = 160) => {
    if (!texto) {
        return 'Sin descripción registrada.';
    }

    return texto.length > limite
        ? `${texto.slice(0, limite)}...`
        : texto;
};

const initChart = () => {
    if (!canvasRef.value || props.datosGrafica.length === 0) return;

    if (chartInstance) {
        chartInstance.destroy();
    }

    const ctx = canvasRef.value.getContext('2d');
    const labels = props.datosGrafica.map(d => d.fecha);

    if (pestañaActiva.value === 'calorias') {
        const caloriasReal = props.datosGrafica.map(d => d.calorias);
        const metaCalorias = Array(labels.length).fill(props.metasNutricionales?.calorias ?? 2000);

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Calorías Consumidas (kcal)',
                        data: caloriasReal,
                        borderColor: '#0ea5e9', // sky-500
                        backgroundColor: 'rgba(14, 165, 233, 0.05)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointBackgroundColor: '#0ea5e9',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Meta Calórica Clínica (kcal)',
                        data: metaCalorias,
                        borderColor: '#ef4444', // red-500
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0,
                        borderWidth: 1.5,
                        pointRadius: 0,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Inter, sans-serif', size: 12, weight: '500' },
                            color: '#475569',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: { family: 'Inter, sans-serif', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Inter, sans-serif', size: 12 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                    }
                }
            }
        });
    } else {
        // Pestaña Macronutrientes
        const proteinas = props.datosGrafica.map(d => d.proteinas);
        const carbohidratos = props.datosGrafica.map(d => d.carbohidratos);
        const grasas = props.datosGrafica.map(d => d.grasas);

        const metaPro = Array(labels.length).fill(props.metasNutricionales?.proteina ?? 75);
        const metaCar = Array(labels.length).fill(props.metasNutricionales?.carbohidratos ?? 275);
        const metaGra = Array(labels.length).fill(props.metasNutricionales?.grasa ?? 65);

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Carbohidratos Consumidos (g)',
                        data: carbohidratos,
                        borderColor: '#8b5cf6', // violet-500
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#8b5cf6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 3,
                    },
                    {
                        label: 'Meta Carbohidratos (g)',
                        data: metaCar,
                        borderColor: 'rgba(139, 92, 246, 0.35)',
                        borderDash: [4, 4],
                        fill: false,
                        pointRadius: 0,
                        borderWidth: 1.2,
                    },
                    {
                        label: 'Proteínas Consumidas (g)',
                        data: proteinas,
                        borderColor: '#06b6d4', // cyan-500
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#06b6d4',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 3,
                    },
                    {
                        label: 'Meta Proteínas (g)',
                        data: metaPro,
                        borderColor: 'rgba(6, 182, 212, 0.35)',
                        borderDash: [4, 4],
                        fill: false,
                        pointRadius: 0,
                        borderWidth: 1.2,
                    },
                    {
                        label: 'Grasas Consumidas (g)',
                        data: grasas,
                        borderColor: '#f43f5e', // rose-500
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#f43f5e',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 3,
                    },
                    {
                        label: 'Meta Grasas (g)',
                        data: metaGra,
                        borderColor: 'rgba(244, 63, 94, 0.35)',
                        borderDash: [4, 4],
                        fill: false,
                        pointRadius: 0,
                        borderWidth: 1.2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Inter, sans-serif', size: 11, weight: '500' },
                            color: '#475569',
                            boxWidth: 10,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: { family: 'Inter, sans-serif', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Inter, sans-serif', size: 12 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                    }
                }
            }
        });
    }
};

onMounted(() => {
    initChart();
});

watch(pestañaActiva, () => {
    initChart();
});
</script>

<template>
    <Head title="Comparativo de seguimientos" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Seguimiento clínico"
                title="Comparativo de evolución"
                description="Cruza y compara el historial de notas clínicas y gráficos de ingesta de nutrientes."
            >
                <template #actions>
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur transition-all duration-300 hover:shadow-md">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 px-1">
                            Acciones de Consulta
                        </p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link
                                :href="route('nutricionista.seguimientos.create', paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-cyan-600 px-4 py-2 text-xs font-bold text-white hover:bg-cyan-700 transition"
                            >
                                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Nuevo seguimiento
                            </Link>

                            <Link
                                :href="route('nutricionista.seguimientos.historial', paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200/80 hover:bg-slate-100 hover:text-slate-900 transition"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Ver historial
                            </Link>

                            <Link
                                :href="route('nutricionista.seguimientos.consolidado', paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200/60 hover:bg-emerald-100 transition"
                            >
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Ver consolidado
                            </Link>
                        </div>
                    </div>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                
                <!-- Tarjeta Resumen del Paciente Principal (Consistente con las otras vistas) -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm motion-card-enter">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-cyan-500 to-emerald-400 text-white font-black text-lg shadow-sm">
                                {{ paciente.usuario?.nombre_completo ? paciente.usuario.nombre_completo.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'P' }}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                                    {{ paciente.usuario?.nombre_completo || 'Paciente no disponible' }}
                                </h2>
                                <p class="text-sm text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                    {{ paciente.usuario?.cor_usu || 'Correo no disponible' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-slate-500 font-semibold bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">
                                Diagnóstico
                            </span>
                            <ClinicalBadge
                                :label="estadoCaso.label"
                                :tone="estadoCaso.tone"
                                class="shadow-sm border animate-pulse"
                            />
                        </div>
                    </div>
                </div>

                <!-- Contenido Principal: Dos Columnas -->
                <div class="grid gap-6 xl:grid-cols-[5fr_7fr] items-start">
                    
                    <!-- COLUMNA IZQUIERDA: Configuración y Similitud (Sticky) -->
                    <aside class="space-y-6 xl:sticky xl:top-6">
                        
                        <!-- Configurador de Fechas -->
                        <div class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden motion-card-enter motion-card-enter-delay-1">
                            <div class="border-b border-slate-100 bg-slate-50/50 p-5 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-cyan-600 block">Cruzar Información</span>
                                    <h3 class="text-base font-bold text-slate-900 mt-0.5">Selección de Controles</h3>
                                </div>
                                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200/40">
                                    {{ fechasDisponibles.length }} registros
                                </span>
                            </div>

                            <div class="p-5 space-y-4">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Control Anterior (Seguimiento A)</label>
                                        <select
                                            v-model="fechaA"
                                            class="block w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-500/10 transition"
                                        >
                                            <option value="">Selecciona fecha</option>
                                            <option
                                                v-for="fecha in fechasDisponibles"
                                                :key="`a-${fecha}`"
                                                :value="fecha"
                                            >
                                                {{ fecha }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Control Reciente (Seguimiento B)</label>
                                        <select
                                            v-model="fechaB"
                                            class="block w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-500/10 transition"
                                        >
                                            <option value="">Selecciona fecha</option>
                                            <option
                                                v-for="fecha in fechasDisponibles"
                                                :key="`b-${fecha}`"
                                                :value="fecha"
                                            >
                                                {{ fecha }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Mensaje de Alerta en Selección -->
                                <div v-if="fechasIguales || fechasDisponibles.length < 2" class="pt-1">
                                    <div v-if="fechasIguales" class="flex gap-2 rounded-xl bg-amber-50 border border-amber-100 p-3 text-xs text-amber-800 font-medium leading-relaxed">
                                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        <span>Selecciona dos fechas diferentes para poder realizar la comparación textual de evolución.</span>
                                    </div>
                                    <div v-if="fechasDisponibles.length < 2" class="flex gap-2 rounded-xl bg-slate-50 border border-slate-200/60 p-3 text-xs text-slate-600 font-medium leading-relaxed">
                                        <svg class="w-4 h-4 text-slate-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25.041-.02a.75.75 0 1 1 1.063 1.063L12 12.75 11.25 13.5M12 7.5h.008v.008H12V7.5zM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                        </svg>
                                        <span>Se necesitan al menos dos registros alimenticios y de control para poder comparar tendencias y realizar similitud textual.</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between border-t border-slate-100 pt-4 gap-2">
                                    <button
                                        type="button"
                                        class="motion-btn rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 transition"
                                        @click="limpiar"
                                    >
                                        Limpiar
                                    </button>
                                    <button
                                        type="button"
                                        class="motion-btn rounded-xl bg-cyan-600 hover:bg-cyan-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5"
                                        :disabled="!puedeComparar"
                                        @click="comparar"
                                    >
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                        </svg>
                                        Comparar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Dial Circular de Similitud Textual (Si existe comparación) -->
                        <div v-if="comparacion" class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden motion-card-enter">
                            <div class="border-b border-slate-100 bg-slate-50/50 p-5">
                                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-violet-600 block">Lectura Analítica</span>
                                <h3 class="text-base font-bold text-slate-900 mt-0.5">Similitud en Evolución</h3>
                            </div>

                            <div class="p-5 space-y-4">
                                <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl border border-slate-100 bg-slate-50/30">
                                    
                                    <!-- Dial SVG de Similitud -->
                                    <div class="relative flex-shrink-0 flex items-center justify-center">
                                        <div class="p-2.5 rounded-full border transition-all duration-300" :class="dialBgColorClass">
                                            <svg class="w-20 h-20" viewBox="0 0 36 36">
                                                <path class="text-slate-200/60" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                                <path class="transition-all duration-700 ease-out" 
                                                      :class="dialColorClass" 
                                                      stroke-width="3.2" 
                                                      :stroke-dasharray="similitud + ', 100'" 
                                                      stroke-linecap="round" 
                                                      transform="rotate(-90 18 18)" 
                                                      stroke="currentColor" 
                                                      fill="none" 
                                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <span class="text-lg font-black tracking-tighter text-slate-900" style="line-height: 1;">
                                                    {{ similitud }}%
                                                </span>
                                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Simil</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Detalles del Análisis Textual de Similitud -->
                                    <div class="space-y-1.5 flex-1 text-center sm:text-left">
                                        <div class="flex flex-wrap justify-center sm:justify-start items-center gap-2">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Variación:</span>
                                            <ClinicalBadge :label="huboCambioSignificativo ? 'Cambio Detectado' : 'Estable'" :tone="huboCambioSignificativo ? 'warning' : 'success'" class="border" />
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed font-semibold">
                                            {{ huboCambioSignificativo 
                                                ? 'La comparación textual refleja variaciones notables en el cuadro evolutivo del paciente.' 
                                                : 'Las notas clínicas guardan alta estabilidad y consistencia entre ambos controles.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Metas Clínicas de Referencia -->
                        <div class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden motion-card-enter">
                            <div class="border-b border-slate-100 bg-slate-50/50 p-5">
                                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-600 block">Soporte Nutricional</span>
                                <h3 class="text-base font-bold text-slate-900 mt-0.5">Metas Asignadas</h3>
                            </div>

                            <div class="p-5 grid grid-cols-2 gap-3 text-center">
                                <div class="rounded-xl bg-slate-50/50 py-3 border border-slate-100">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Meta Calórica</span>
                                    <p class="text-xs font-bold text-slate-800 mt-0.5">{{ metasNutricionales.calorias || 2000 }} kcal</p>
                                </div>
                                <div class="rounded-xl bg-slate-50/50 py-3 border border-slate-100">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Meta Proteína</span>
                                    <p class="text-xs font-bold text-slate-800 mt-0.5">{{ metasNutricionales.proteina || 75 }} g</p>
                                </div>
                                <div class="rounded-xl bg-slate-50/50 py-3 border border-slate-100">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Meta Carbos</span>
                                    <p class="text-xs font-bold text-slate-800 mt-0.5">{{ metasNutricionales.carbohidratos || 275 }} g</p>
                                </div>
                                <div class="rounded-xl bg-slate-50/50 py-3 border border-slate-100">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Meta Grasas</span>
                                    <p class="text-xs font-bold text-slate-800 mt-0.5">{{ metasNutricionales.grasa || 65 }} g</p>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- COLUMNA DERECHA: Gráfico y Cruce Textual -->
                    <main class="space-y-6">
                        
                        <!-- Gráfico de Ingesta y Meta -->
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4 motion-card-enter">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-cyan-600 block">Tendencias Clínicas</span>
                                    <h3 class="text-base font-bold text-slate-900 mt-0.5">Ingesta Registrada vs Metas Clínicas</h3>
                                </div>
                                
                                <!-- Selectores del tipo de gráfico -->
                                <div v-if="datosGrafica.length > 0" class="flex rounded-xl bg-slate-100 p-1">
                                    <button
                                        type="button"
                                        @click="pestañaActiva = 'calorias'"
                                        class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition"
                                        :class="pestañaActiva === 'calorias' ? 'bg-white text-slate-800 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'"
                                    >
                                        Calorías
                                    </button>
                                    <button
                                        type="button"
                                        @click="pestañaActiva = 'macros'"
                                        class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition"
                                        :class="pestañaActiva === 'macros' ? 'bg-white text-slate-800 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'"
                                    >
                                        Macronutrientes
                                    </button>
                                </div>
                            </div>

                            <!-- Gráfico Canvas -->
                            <div v-if="datosGrafica.length > 0" class="relative min-h-[300px] md:min-h-[350px] w-full">
                                <canvas ref="canvasRef"></canvas>
                            </div>

                            <!-- Estado Sin Gráficas de Ingesta -->
                            <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-12 px-6 text-center space-y-2">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-slate-700">Sin datos de ingesta alimenticia</h4>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                    El paciente no cuenta con registros de alimentos aceptados u observados en este rango de fechas para graficar su comportamiento calórico.
                                </p>
                            </div>
                        </div>

                        <!-- Cruce Textual (Seguimiento A vs Seguimiento B) -->
                        <div v-if="comparacion" class="space-y-6">
                            
                            <div class="grid gap-6 md:grid-cols-2">
                                
                                <!-- Tarjeta Control Anterior (Seguimiento A) -->
                                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Control Anterior</span>
                                        <ClinicalBadge
                                            :label="comparacion.seguimiento_a.fec_seg || 'Sin fecha'"
                                            tone="info"
                                            class="border font-bold text-xs"
                                        />
                                    </div>

                                    <!-- Evolución y notas -->
                                    <div class="space-y-2">
                                        <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z" />
                                            </svg>
                                            Notas de Evolución
                                        </h4>
                                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 leading-relaxed text-sm text-slate-700 whitespace-pre-line font-medium">
                                            {{ expandirNotaA ? comparacion.seguimiento_a.res_seg : textoCorto(comparacion.seguimiento_a.res_seg, 160) }}
                                            
                                            <button 
                                                v-if="comparacion.seguimiento_a.res_seg && comparacion.seguimiento_a.res_seg.length > 160"
                                                type="button"
                                                @click="expandirNotaA = !expandirNotaA"
                                                class="mt-3 text-xs font-bold text-cyan-600 hover:text-cyan-700 hover:underline transition flex items-center gap-0.5"
                                            >
                                                <span>{{ expandirNotaA ? 'Ocultar detalles' : 'Leer nota completa' }}</span>
                                                <svg class="w-3.5 h-3.5 transform transition-transform duration-300" :class="expandirNotaA ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Cambios sugeridos (si existen) -->
                                    <div v-if="comparacion.seguimiento_a.cam_seg" class="space-y-2">
                                        <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                            </svg>
                                            Cambios Clínicos Sugeridos
                                        </h4>
                                        <div class="rounded-2xl border border-violet-100 bg-violet-50/10 p-4 leading-relaxed text-sm text-slate-700 whitespace-pre-line font-medium">
                                            {{ expandirCambioA ? comparacion.seguimiento_a.cam_seg : textoCorto(comparacion.seguimiento_a.cam_seg, 160) }}
                                            
                                            <button 
                                                v-if="comparacion.seguimiento_a.cam_seg && comparacion.seguimiento_a.cam_seg.length > 160"
                                                type="button"
                                                @click="expandirCambioA = !expandirCambioA"
                                                class="mt-3 text-xs font-bold text-violet-600 hover:text-violet-700 hover:underline transition flex items-center gap-0.5"
                                            >
                                                <span>{{ expandirCambioA ? 'Ocultar detalles' : 'Leer nota completa' }}</span>
                                                <svg class="w-3.5 h-3.5 transform transition-transform duration-300" :class="expandirCambioA ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tarjeta Control Reciente (Seguimiento B) -->
                                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Control Reciente</span>
                                        <ClinicalBadge
                                            :label="comparacion.seguimiento_b.fec_seg || 'Sin fecha'"
                                            tone="info"
                                            class="border font-bold text-xs"
                                        />
                                    </div>

                                    <!-- Evolución y notas -->
                                    <div class="space-y-2">
                                        <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z" />
                                            </svg>
                                            Notas de Evolución
                                        </h4>
                                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 leading-relaxed text-sm text-slate-700 whitespace-pre-line font-medium">
                                            {{ expandirNotaB ? comparacion.seguimiento_b.res_seg : textoCorto(comparacion.seguimiento_b.res_seg, 160) }}
                                            
                                            <button 
                                                v-if="comparacion.seguimiento_b.res_seg && comparacion.seguimiento_b.res_seg.length > 160"
                                                type="button"
                                                @click="expandirNotaB = !expandirNotaB"
                                                class="mt-3 text-xs font-bold text-cyan-600 hover:text-cyan-700 hover:underline transition flex items-center gap-0.5"
                                            >
                                                <span>{{ expandirNotaB ? 'Ocultar detalles' : 'Leer nota completa' }}</span>
                                                <svg class="w-3.5 h-3.5 transform transition-transform duration-300" :class="expandirNotaB ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Cambios sugeridos (si existen) -->
                                    <div v-if="comparacion.seguimiento_b.cam_seg" class="space-y-2">
                                        <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                            </svg>
                                            Cambios Clínicos Sugeridos
                                        </h4>
                                        <div class="rounded-2xl border border-violet-100 bg-violet-50/10 p-4 leading-relaxed text-sm text-slate-700 whitespace-pre-line font-medium">
                                            {{ expandirCambioB ? comparacion.seguimiento_b.cam_seg : textoCorto(comparacion.seguimiento_b.cam_seg, 160) }}
                                            
                                            <button 
                                                v-if="comparacion.seguimiento_b.cam_seg && comparacion.seguimiento_b.cam_seg.length > 160"
                                                type="button"
                                                @click="expandirCambioB = !expandirCambioB"
                                                class="mt-3 text-xs font-bold text-violet-600 hover:text-violet-700 hover:underline transition flex items-center gap-0.5"
                                            >
                                                <span>{{ expandirCambioB ? 'Ocultar detalles' : 'Leer nota completa' }}</span>
                                                <svg class="w-3.5 h-3.5 transform transition-transform duration-300" :class="expandirCambioB ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado Pendiente de Comparativa -->
                        <div v-else class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm text-center py-12 space-y-3">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.656 48.656 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7C4.797 9.547 4.75 10.768 4.75 12s.047 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.092-1.209.138-2.43.138-3.662Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10.5h.008v.008H9V10.5Zm6 0h.008v.008H15V10.5Zm-6 4h.008v.008H9v-.008Zm6 0h.008v.008H15v-.008Z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-slate-700">Cruzar Notas Clínicas Pendiente</h4>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                Selecciona dos fechas de control en el panel de la izquierda y haz clic en "Comparar" para auditar las variaciones de evolución y el porcentaje de similitud textual.
                            </p>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

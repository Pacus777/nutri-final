<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import { Head, Link } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    nutricionista: Object,
    resumen: {
        type: Object,
        default: () => ({}),
    },
    pacientesAsignados: {
        type: Array,
        default: () => [],
    },
    objetivosNutricionales: {
        type: Array,
        default: () => [],
    },
    estadisticasNutricionista: {
        type: Object,
        default: () => ({}),
    },
    pacientesSinRegistrosRecientes: {
        type: Array,
        default: () => [],
    },
    pacientesAsignadosPlanes: {
        type: Array,
        default: () => [],
    },
    todosObjetivos: {
        type: Array,
        default: () => [],
    },
    todosPlanes: {
        type: Array,
        default: () => [],
    },
});

const cargando = ref(true);
const objetivosChartCanvas = ref(null);
const planesChartCanvas = ref(null);
const adherenciaChartCanvas = ref(null);

let objetivosChart = null;
let planesChart = null;
let adherenciaChart = null;

const adherenciaStats = computed(() => {
    const counts = { verde: 0, amarillo: 0, rojo: 0 };
    (props.pacientesAsignados || []).forEach((item) => {
        const estado = item.adherencia?.estado;
        if (estado === 'verde') {
            counts.verde++;
        } else if (estado === 'amarillo') {
            counts.amarillo++;
        } else if (estado === 'rojo') {
            counts.rojo++;
        }
    });
    return counts;
});

const hayDatosAdherencia = computed(() => (adherenciaStats.value.verde + adherenciaStats.value.amarillo + adherenciaStats.value.rojo) > 0);

const estadisticasNutricionista = computed(() => props.estadisticasNutricionista ?? {});
const pacientesSinRegistrosRecientes = computed(() => props.pacientesSinRegistrosRecientes ?? []);
const pacientesRecientes = computed(() => (props.pacientesAsignados || []).slice(0, 5));
const objetivosRecientes = computed(() => (props.objetivosNutricionales || []).slice(0, 4));

const registrosPendientes = computed(() => Number(props.resumen?.registros_pendientes ?? 0));
const pacientesAsignadosTotal = computed(() => Number(props.resumen?.pacientes_asignados ?? 0));
const objetivosActivosTotal = computed(() => Number(props.resumen?.objetivos_activos ?? 0));
const planesVigentesTotal = computed(() => Number(props.resumen?.planes_vigentes ?? 0));
const validacionesMesActual = computed(() => Number(props.resumen?.validaciones_mes_actual ?? 0));
const pacientesConPlanVigente = computed(() => Number(props.resumen?.pacientes_con_plan_vigente ?? 0));

const tienePendientes = computed(() => registrosPendientes.value > 0);

const estadoTrabajoDiario = computed(() => {
    if (tienePendientes.value) {
        return {
            label: 'Con pendientes',
            tone: 'amber',
            mensaje: `Tienes ${registrosPendientes.value} registro${registrosPendientes.value === 1 ? '' : 's'} alimenticio${registrosPendientes.value === 1 ? '' : 's'} pendiente${registrosPendientes.value === 1 ? '' : 's'} de validación.`,
        };
    }

    return {
        label: 'Al día',
        tone: 'emerald',
        mensaje: 'No tienes registros alimenticios pendientes de validación en este momento.',
    };
});

const objetivosPorEstado = computed(() => Object.entries(estadisticasNutricionista.value.objetivos_por_estado ?? {}).map(([estado, cantidad]) => ({
    estado,
    cantidad: Number(cantidad ?? 0),
    color: estado === 'activo' || estado === 'vigente'
        ? '#16a34a'
        : estado === 'cumplido'
            ? '#0ea5e9'
            : estado === 'pausado'
                ? '#f59e0b'
                : '#ef4444',
})));

const planesPorEstado = computed(() => Object.entries(estadisticasNutricionista.value.planes_por_estado ?? {}).map(([estado, cantidad]) => ({
    estado,
    cantidad: Number(cantidad ?? 0),
    color: estado === 'vigente' || estado === 'activo'
        ? '#8b5cf6'
        : estado === 'cerrado'
            ? '#14b8a6'
            : '#f97316',
})));

const totalObjetivosEstados = computed(() => objetivosPorEstado.value.reduce((total, item) => total + item.cantidad, 0));
const totalPlanesEstados = computed(() => planesPorEstado.value.reduce((total, item) => total + item.cantidad, 0));
const hayDatosObjetivos = computed(() => totalObjetivosEstados.value > 0);
const hayDatosPlanes = computed(() => totalPlanesEstados.value > 0);

const nombreNutricionista = computed(() => props.nutricionista?.usuario?.nombre_completo || 'Nutricionista');
const correoNutricionista = computed(() => props.nutricionista?.usuario?.cor_usu || 'Sin correo registrado');
const especialidadNutricionista = computed(() => props.nutricionista?.esp_nut || 'Especialista en nutrición clínica');

const quickActions = computed(() => [
    {
        title: 'Validar registros',
        description: 'Revisa los pendientes y prioriza los casos críticos.',
        href: route('nutricionista.validaciones-informacion.pendientes'),
        tone: 'emerald',
    },
    {
        title: 'Mis pacientes',
        description: 'Acceso directo a la cartera activa asignada.',
        href: route('nutricionista.pacientes.index'),
        tone: 'sky',
    },
    {
        title: 'Contextos',
        description: 'Consulta antecedentes y restricciones por paciente.',
        href: route('contextos-paciente.index'),
        tone: 'amber',
    },
    {
        title: 'Historial',
        description: 'Abre la trazabilidad de seguimiento por paciente.',
        href: historialRapidoHref.value,
        tone: 'violet',
    },
]);

const resumenRapido = computed(() => [
    {
        label: 'Pacientes activos',
        value: pacientesAsignadosTotal.value,
        helper: 'Cartera operativa actual',
        tone: 'emerald',
    },
    {
        label: 'Pendientes por validar',
        value: registrosPendientes.value,
        helper: estadoTrabajoDiario.value.mensaje,
        tone: tienePendientes.value ? 'amber' : 'sky',
    },
    {
        label: 'Objetivos activos',
        value: objetivosActivosTotal.value,
        helper: 'Objetivos en seguimiento',
        tone: 'violet',
    },
    {
        label: 'Planes vigentes',
        value: planesVigentesTotal.value,
        helper: 'Planes actualmente en curso',
        tone: 'slate',
    },
]);

const normalizarUrlHistoria = (item) => {
    if (!item?.cod_pacientes) {
        return route('nutricionista.pacientes.index');
    }

    return route('nutricionista.seguimientos.historial', item.cod_pacientes);
};

const nombrePacienteAsignado = (item) => item.paciente?.nombre_completo || item.paciente?.usuario?.nombre_completo || 'Paciente sin nombre';
const correoPacienteAsignado = (item) => item.paciente?.cor_usu || item.paciente?.usuario?.cor_usu || 'Sin correo registrado';

const historialRapidoHref = computed(() => {
    const primerPaciente = props.pacientesAsignados?.[0];

    if (!primerPaciente?.cod_pacientes) {
        return route('nutricionista.pacientes.index');
    }

    return route('nutricionista.seguimientos.historial', primerPaciente.cod_pacientes);
});

const renderCharts = async () => {
    await nextTick();

    if (objetivosChart) {
        objetivosChart.destroy();
        objetivosChart = null;
    }

    if (planesChart) {
        planesChart.destroy();
        planesChart = null;
    }

    if (adherenciaChart) {
        adherenciaChart.destroy();
        adherenciaChart = null;
    }

    if (objetivosChartCanvas.value && hayDatosObjetivos.value) {
        objetivosChart = new Chart(objetivosChartCanvas.value, {
            type: 'doughnut',
            data: {
                labels: objetivosPorEstado.value.map((item) => item.estado),
                datasets: [{
                    data: objetivosPorEstado.value.map((item) => item.cantidad),
                    backgroundColor: objetivosPorEstado.value.map((item) => item.color),
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,
                            font: { family: 'Outfit, sans-serif' }
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const total = totalObjetivosEstados.value || 1;
                                const value = Number(context.raw ?? 0);
                                const percentage = ((value / total) * 100).toFixed(1);

                                return `${context.label}: ${value} (${percentage}%)`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (planesChartCanvas.value && hayDatosPlanes.value) {
        planesChart = new Chart(planesChartCanvas.value, {
            type: 'bar',
            data: {
                labels: planesPorEstado.value.map((item) => item.estado),
                datasets: [{
                    label: 'Planes',
                    data: planesPorEstado.value.map((item) => item.cantidad),
                    backgroundColor: planesPorEstado.value.map((item) => item.color),
                    borderRadius: 10,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                return `${context.label}: ${context.raw}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#475569', font: { family: 'Outfit, sans-serif' } },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#475569',
                            font: { family: 'Outfit, sans-serif' }
                        },
                        grid: {
                            color: '#e2e8f0',
                        },
                    },
                },
            },
        });
    }

    if (adherenciaChartCanvas.value && hayDatosAdherencia.value) {
        adherenciaChart = new Chart(adherenciaChartCanvas.value, {
            type: 'doughnut',
            data: {
                labels: ['Excelente adherencia', 'Irregular', 'Inactivos / Sin registros'],
                datasets: [{
                    data: [adherenciaStats.value.verde, adherenciaStats.value.amarillo, adherenciaStats.value.rojo],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,
                            font: { family: 'Outfit, sans-serif' }
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const total = (adherenciaStats.value.verde + adherenciaStats.value.amarillo + adherenciaStats.value.rojo) || 1;
                                const value = Number(context.raw ?? 0);
                                const percentage = ((value / total) * 100).toFixed(1);

                                return `${context.label}: ${value} (${percentage}%)`;
                            },
                        },
                    },
                },
            },
        });
    }
};

onMounted(() => {
    renderCharts();
    setTimeout(() => {
        cargando.value = false;
    }, 350);
});

watch([objetivosPorEstado, planesPorEstado, adherenciaStats], () => {
    renderCharts();
}, { deep: true });

onBeforeUnmount(() => {
    if (objetivosChart) {
        objetivosChart.destroy();
    }

    if (planesChart) {
        planesChart.destroy();
    }

    if (adherenciaChart) {
        adherenciaChart.destroy();
    }
});
</script>


<template>
    <Head title="Dashboard Clínico del Nutricionista" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/10">Módulo de Nutrición</span>
                        <span class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-inset ring-slate-600/10">Panel Profesional</span>
                    </div>
                    <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 leading-none">Dashboard Clínico</h2>
                    <p class="mt-2 text-sm text-slate-500 font-semibold">{{ especialidadNutricionista }} · {{ correoNutricionista }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-sm border border-slate-200">
                        <span class="h-2 w-2 rounded-full" :class="tienePendientes ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'"></span>
                        {{ estadoTrabajoDiario.label }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 border border-emerald-200">{{ pacientesAsignadosTotal }} Pacientes Asignados</span>
                </div>
            </div>
        </template>
        <div v-if="cargando" class="py-8 bg-slate-50/50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <Skeleton type="hero" />
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"><Skeleton type="card" :count="4" /></div>
                <div class="grid gap-6 lg:grid-cols-3"><Skeleton type="chart" /><Skeleton type="chart" /><Skeleton type="chart" /></div>
            </div>
        </div>
        <div v-else class="py-8 bg-slate-50/30 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- 1. Welcome banner (slate-900 / emerald-950 gradient with active pending counter card) -->
                <section class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 p-8 text-white shadow-[0_20px_50px_rgba(6,95,70,0.15)]">
                    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-emerald-500/10 blur-3xl"></div>
                    <div class="absolute -left-10 -bottom-10 h-40 w-40 rounded-full bg-emerald-400/10 blur-3xl"></div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-3">
                            <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-400">Centro Operativo Profesional</p>
                            <h3 class="text-3xl font-black tracking-tight text-white md:text-4xl">Dra. {{ nombreNutricionista }}</h3>
                            <p class="max-w-2xl text-sm leading-relaxed text-emerald-50/80 font-semibold">{{ estadoTrabajoDiario.mensaje }}</p>
                            <div class="flex flex-wrap gap-2 pt-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3.5 py-1 text-xs font-bold text-emerald-200 backdrop-blur">
                                    <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                                    {{ correoNutricionista }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3.5 py-1 text-xs font-bold text-emerald-200 backdrop-blur">
                                    <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 02 2h10a2 2 0 0 02-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 02 2h2a2 2 0 0 02-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" /></svg>
                                    Validaciones del mes: {{ validacionesMesActual }}
                                </span>
                            </div>
                        </div>
                        <div class="shrink-0 flex flex-col items-start md:items-end gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md min-w-[200px] shadow-sm">
                                <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-emerald-300">Monitoreo Clínico</p>
                                <div class="mt-2 flex items-baseline gap-2">
                                    <span class="text-3xl font-black text-white">{{ registrosPendientes }}</span>
                                    <span class="text-xs text-emerald-200/70 font-semibold">Validaciones pendientes</span>
                                </div>
                            </div>
                            <Link v-if="tienePendientes" :href="route('nutricionista.validaciones-informacion.pendientes')" class="motion-btn inline-flex items-center gap-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 px-5 py-3 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Validar Registros Pendientes
                            </Link>
                        </div>
                    </div>
                </section>
                <!-- 2. Metrics cards (4 columns) -->
                <section class="grid gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <article class="relative overflow-hidden rounded-3xl border border-slate-200/60 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-100 group">
                        <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-emerald-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pacientes Asignados</span>
                            <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white border border-emerald-100/50 shadow-sm"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg></div>
                        </div>
                        <div class="relative z-10 mt-4"><p class="text-4xl font-black tracking-tight text-slate-900">{{ pacientesAsignadosTotal }}</p><p class="mt-2 text-xs font-semibold text-slate-500">Cartera activa bajo supervisión</p></div>
                    </article>
                    <article class="relative overflow-hidden rounded-3xl border border-slate-200/60 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-100 group">
                        <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-amber-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Por Validar</span>
                            <div class="rounded-xl p-2.5 transition-colors border shadow-sm" :class="tienePendientes ? 'bg-amber-50 text-amber-600 border-amber-100 group-hover:bg-amber-500 group-hover:text-white' : 'bg-slate-50 text-slate-400 border-slate-200/50'"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div>
                        </div>
                        <div class="relative z-10 mt-4"><p class="text-4xl font-black tracking-tight" :class="tienePendientes ? 'text-amber-600' : 'text-slate-900'">{{ registrosPendientes }}</p><p class="mt-2 text-xs font-semibold text-slate-500">{{ tienePendientes ? 'Registros alimenticios por evaluar' : 'Todo al día con pacientes' }}</p></div>
                    </article>
                    <article class="relative overflow-hidden rounded-3xl border border-slate-200/60 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-sky-100 group">
                        <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-sky-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Objetivos en Curso</span>
                            <div class="rounded-xl bg-sky-50 p-2.5 text-sky-600 transition-colors group-hover:bg-sky-500 group-hover:text-white border border-sky-100/50 shadow-sm"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                        </div>
                        <div class="relative z-10 mt-4"><p class="text-4xl font-black tracking-tight text-slate-900">{{ objetivosActivosTotal }}</p><p class="mt-2 text-xs font-semibold text-slate-500">Metas y logros definidos</p></div>
                    </article>
                    <article class="relative overflow-hidden rounded-3xl border border-slate-200/60 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-violet-100 group">
                        <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-violet-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Planes Vigentes</span>
                            <div class="rounded-xl bg-violet-50 p-2.5 text-violet-600 transition-colors group-hover:bg-violet-600 group-hover:text-white border border-violet-100/50 shadow-sm"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                        </div>
                        <div class="relative z-10 mt-4"><p class="text-4xl font-black tracking-tight text-slate-900">{{ planesVigentesTotal }}</p><p class="mt-2 text-xs font-semibold text-slate-500">Planes nutricionales activos</p></div>
                    </article>
                </section>
                <!-- 3. Layout Grid (Left: Priorities & Analytics. Right: Quick Actions & Recents) -->
                <div class="grid gap-6 grid-cols-1 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-6">
                        <!-- 3.1 Priorities (Riesgo de desapego) -->
                        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="rounded-lg bg-rose-50 p-2 text-rose-600 border border-rose-100/50 shadow-sm animate-pulse">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-955">Prioridades Clínicas</h3>
                                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Pacientes con riesgo de desapego o sin registros alimenticios recientes</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 border border-rose-100">{{ pacientesSinRegistrosRecientes.length }} Alertas</span>
                            </div>
                            <div class="mt-4 divide-y divide-slate-100">
                                <div v-if="pacientesSinRegistrosRecientes.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
                                    <div class="rounded-full bg-emerald-50 p-4 text-emerald-500 mb-3 border border-emerald-100/50 shadow-sm"><svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                                    <p class="text-sm font-bold text-slate-900">¡Excelente! Cartera bajo control</p>
                                    <p class="text-xs text-slate-500 mt-1">Todos tus pacientes se mantienen constantes con sus registros.</p>
                                </div>
                                <div v-for="p in pacientesSinRegistrosRecientes" :key="p.cod_pacientes" class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 first:pt-0 last:pb-0 group">
                                    <div class="flex items-start gap-3">
                                        <div class="rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-500 h-10 w-10 flex items-center justify-center text-white font-black text-sm shrink-0 border border-rose-100 shadow-sm">{{ p.nombre_completo ? p.nombre_completo.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() : 'P' }}</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="font-bold text-slate-900 text-sm group-hover:text-emerald-700 transition-colors">{{ p.nombre_completo }}</h4>
                                                <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide text-rose-700 border border-rose-100">{{ p.dias_sin_registrar !== null ? `${p.dias_sin_registrar} días sin registrar` : 'Sin registros previos' }}</span>
                                            </div>
                                            <p class="text-xs font-semibold text-slate-400">{{ p.cor_usu }}</p>
                                            <p v-if="p.ultimo_registro_fecha" class="text-[10px] text-slate-500 font-bold flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                Último registro detectado el: {{ p.ultimo_registro_fecha }}
                                            </p>
                                        </div>
                                    </div>
                                    <Link :href="normalizarUrlHistoria(p)" class="motion-btn inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-50 hover:bg-rose-600 hover:text-white px-4 py-2.5 text-xs font-bold text-slate-700 border border-slate-200 transition-all duration-300 group-hover:border-rose-300">
                                        Abrir Historia
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </Link>
                                </div>
                            </div>
                        </section>
                        <!-- 3.2 Analytics Charts -->
                        <section class="space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-emerald-50 p-1.5 text-emerald-600 border border-emerald-100/50 shadow-sm"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" /></svg></span>
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 leading-none">Centro de Analíticas Clínicas</h3>
                                    <p class="text-xs font-semibold text-slate-500 mt-1">Supervisión en tiempo real de progresos, metas y constancia dietética</p>
                                </div>
                            </div>
                            <div class="grid gap-6 md:grid-cols-3">
                                <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm flex flex-col justify-between transition-all duration-300 hover:shadow-md">
                                    <div>
                                        <div class="border-b border-slate-100 pb-3">
                                            <p class="text-[9px] font-black uppercase tracking-wider text-emerald-700">Analítica de Metas</p>
                                            <h4 class="mt-1 font-bold text-slate-800 text-sm">Objetivos por Estado</h4>
                                        </div>
                                        <div class="mt-4 h-56 relative">
                                            <canvas ref="objetivosChartCanvas"></canvas>
                                            <div v-if="!hayDatosObjetivos" class="absolute inset-0 flex items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-xs text-slate-400 font-semibold p-4 text-center">Sin objetivos clínicos asignados</div>
                                        </div>
                                    </div>
                                </article>
                                <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm flex flex-col justify-between transition-all duration-300 hover:shadow-md">
                                    <div>
                                        <div class="border-b border-slate-100 pb-3">
                                            <p class="text-[9px] font-black uppercase tracking-wider text-violet-700">Operaciones Dietéticas</p>
                                            <h4 class="mt-1 font-bold text-slate-800 text-sm">Planes por Estado</h4>
                                        </div>
                                        <div class="mt-4 h-56 relative">
                                            <canvas ref="planesChartCanvas"></canvas>
                                            <div v-if="!hayDatosPlanes" class="absolute inset-0 flex items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-xs text-slate-400 font-semibold p-4 text-center">Sin planes de alimentación en vigencia</div>
                                        </div>
                                    </div>
                                </article>
                                <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm flex flex-col justify-between transition-all duration-300 hover:shadow-md">
                                    <div>
                                        <div class="border-b border-slate-100 pb-3">
                                            <p class="text-[9px] font-black uppercase tracking-wider text-rose-700">Analítica de Apego</p>
                                            <h4 class="mt-1 font-bold text-slate-800 text-sm">Adherencia Semanal</h4>
                                        </div>
                                        <div class="mt-4 h-56 relative">
                                            <canvas ref="adherenciaChartCanvas"></canvas>
                                            <div v-if="!hayDatosAdherencia" class="absolute inset-0 flex items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-xs text-slate-400 font-semibold p-4 text-center">Sin datos suficientes de registros</div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>

                    <!-- Right column -->
                    <div class="space-y-6">
                        <!-- Quick actions modules -->
                        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                            <div class="border-b border-slate-100 pb-3">
                                <h3 class="text-md font-bold text-slate-950">Módulos de Gestión Rápida</h3>
                                <p class="text-xs text-slate-400 font-semibold mt-1">Acciones y derivaciones clínicas directas</p>
                            </div>
                            <div class="mt-4 grid gap-3">
                                <Link 
                                    v-for="action in quickActions" 
                                    :key="action.title"
                                    :href="action.href"
                                    class="group flex items-start gap-3 rounded-2xl border p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm"
                                    :class="{
                                        'border-emerald-100 bg-emerald-50/20 hover:border-emerald-300 hover:bg-emerald-50/40': action.tone === 'emerald',
                                        'border-sky-100 bg-sky-50/20 hover:border-sky-300 hover:bg-sky-50/40': action.tone === 'sky',
                                        'border-amber-100 bg-amber-50/20 hover:border-amber-300 hover:bg-amber-50/40': action.tone === 'amber',
                                        'border-violet-100 bg-violet-50/20 hover:border-violet-300 hover:bg-violet-50/40': action.tone === 'violet',
                                    }"
                                >
                                    <div class="rounded-xl p-2 shrink-0 transition-all duration-300 group-hover:scale-110 shadow-sm border border-slate-200/10" 
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': action.tone === 'emerald',
                                            'bg-sky-100 text-sky-700': action.tone === 'sky',
                                            'bg-amber-100 text-amber-700': action.tone === 'amber',
                                            'bg-violet-100 text-violet-700': action.tone === 'violet',
                                        }"
                                    >
                                        <svg v-if="action.tone === 'emerald'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else-if="action.tone === 'sky'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                        <svg v-else-if="action.tone === 'amber'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </div>
                                    <div class="text-left">
                                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-800 transition-colors">{{ action.title }}</h4>
                                        <p class="mt-1 text-xs font-semibold leading-relaxed text-slate-500">{{ action.description }}</p>
                                        <span class="mt-2.5 inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 group-hover:text-emerald-700 transition-colors">Entrar al módulo <span class="transition duration-300 group-hover:translate-x-0.5">↗</span></span>
                                    </div>
                                </Link>
                            </div>
                        </section>
                        <!-- Patients List -->
                        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-md font-bold text-slate-950">Pacientes Asignados</h3>
                                    <p class="text-xs text-slate-400 font-semibold mt-1">Actividad reciente e informes de adherencia</p>
                                </div>
                                <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-500 border border-slate-200">{{ pacientesRecientes.length }} pacientes</span>
                            </div>
                            <div class="mt-4 space-y-3">
                                <div v-for="item in pacientesRecientes" :key="item.cod_pacientes" class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition-all duration-300 hover:border-slate-300 hover:bg-slate-50 group shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-2.5">
                                            <div class="rounded-2xl bg-gradient-to-tr from-cyan-500 to-emerald-400 h-9 w-9 flex items-center justify-center text-white font-black text-xs shrink-0 border border-emerald-100 shadow-sm">{{ nombrePacienteAsignado(item).split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() }}</div>
                                            <div>
                                                <p class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition-colors">{{ nombrePacienteAsignado(item) }}</p>
                                                <p class="text-[10px] font-semibold text-slate-400 mt-0.5">{{ correoPacienteAsignado(item) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between pt-3 border-t border-slate-200/50">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wide border" :class="{'bg-emerald-50 text-emerald-700 border-emerald-200': item.adherencia?.estado === 'verde', 'bg-amber-50 text-amber-700 border-amber-200': item.adherencia?.estado === 'amarillo', 'bg-rose-50 text-rose-700 border-rose-200': item.adherencia?.estado === 'rojo'}">
                                            <span class="h-1.5 w-1.5 rounded-full animate-pulse" :class="{'bg-emerald-500': item.adherencia?.estado === 'verde', 'bg-amber-500': item.adherencia?.estado === 'amarillo', 'bg-rose-500': item.adherencia?.estado === 'rojo'}"></span>
                                            {{ item.adherencia?.label || 'Sin registros' }}
                                        </span>
                                        <Link :href="normalizarUrlHistoria(item)" class="text-[11px] font-black text-emerald-700 hover:text-emerald-800 transition-colors inline-flex items-center gap-0.5">Ver Historia <span>↗</span></Link>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
                <!-- bottom section -->
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Estado de Cobertura</p>
                            <h3 class="mt-1 text-md font-bold text-slate-900">Cobertura y Seguimiento Terapéutico</h3>
                            <p class="text-xs text-slate-500 font-semibold mt-1">Monitoreo integrado de pacientes asignados con planes y dietas activas.</p>
                        </div>
                        <div class="inline-flex items-center gap-1.5 rounded-xl bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 border border-slate-200 shadow-sm"><svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>Pacientes con Plan Vigente: <span class="text-emerald-700 font-black">{{ pacientesConPlanVigente }}</span></div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

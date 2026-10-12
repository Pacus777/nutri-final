<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ObjetivosClinicosWidget from '@/Components/Paciente/ObjetivosClinicosWidget.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    paciente: Object,
    recomendaciones: {
        type: Array,
        default: () => [],
    },
    resumenSemanal: {
        type: Object,
        default: () => ({}),
    },
    objetivosActivos: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const hoy = new Date().toISOString().slice(0, 10);

const cargando = ref(true);
const fechaUi = new Date().toLocaleDateString('es-BO', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

const nombrePaciente = computed(() => {
    return props.paciente?.usuario?.nombre_completo || 'Paciente';
});

const saludo = computed(() => {
    const hora = new Date().getHours();

    if (hora < 12) {
        return 'Buenos días';
    }

    if (hora < 19) {
        return 'Buenas tardes';
    }

    return 'Buenas noches';
});

const fraseDia = computed(() => {
    const frases = [
        'Un registro claro hoy, vale por una semana de dudas.',
        'Pequeñas acciones constantes generan grandes resultados.',
        'Tu historial es la base para mejores decisiones nutricionales.',
    ];

    return frases[new Date().getDate() % frases.length];
});

// Estados locales de las recomendaciones persistidos en localStorage
const estadosRecomendaciones = ref({});

const pesoChartCanvas = ref(null);
let pesoChart = null;

const renderPesoChart = () => {
    if (!pesoChartCanvas.value || !props.paciente?.historial_peso || props.paciente.historial_peso.length === 0) return;

    const ctx = pesoChartCanvas.value.getContext('2d');
    const labels = props.paciente.historial_peso.map(d => d.fecha);
    const pesos = props.paciente.historial_peso.map(d => d.peso);
    const imcs = props.paciente.historial_peso.map(d => d.imc);

    if (pesoChart) {
        pesoChart.destroy();
    }

    pesoChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Mi Peso (kg)',
                    data: pesos,
                    borderColor: '#10b981', // emerald-500
                    backgroundColor: 'rgba(16, 185, 129, 0.03)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'yPeso',
                },
                {
                    label: 'Mi IMC',
                    data: imcs,
                    borderColor: '#6366f1', // indigo-500
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderDash: [5, 5],
                    yAxisID: 'yImc',
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
                        font: { family: 'Inter, sans-serif', size: 12, weight: '600' },
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
                yPeso: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Peso (kg)',
                        color: '#10b981',
                        font: { family: 'Inter, sans-serif', size: 11, weight: 'bold' }
                    },
                    grid: { color: '#f1f5f9' },
                    ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                },
                yImc: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'IMC',
                        color: '#6366f1',
                        font: { family: 'Inter, sans-serif', size: 11, weight: 'bold' }
                    },
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                }
            }
        }
    });
};

const ultimaRecomendacion = computed(() => {
    return props.recomendaciones && props.recomendaciones.length > 0
        ? props.recomendaciones[0]
        : null;
});

const cargarEstadosLocal = () => {
    try {
        const guardados = localStorage.getItem(`estados_rec_${props.paciente.cod_pacientes}`);
        if (guardados) {
            estadosRecomendaciones.value = JSON.parse(guardados);
        }
    } catch (e) {
        console.error('No se pudieron cargar los estados locales', e);
    }
};

const abrirRecomendacionesModal = (selectedId = null) => {
    const event = new CustomEvent('open-inbox', {
        detail: {
            tab: 'recomendaciones',
            selectedId: selectedId
        }
    });
    window.dispatchEvent(event);
};

const handleRecomendacionActualizada = () => {
    cargarEstadosLocal();
};

onMounted(() => {
    cargarEstadosLocal();
    window.addEventListener('recomendacion-actualizada', handleRecomendacionActualizada);
    setTimeout(() => {
        cargando.value = false;
        nextTick(() => {
            renderPesoChart();
        });
    }, 350);
});

onUnmounted(() => {
    if (pesoChart) {
        pesoChart.destroy();
    }
    window.removeEventListener('recomendacion-actualizada', handleRecomendacionActualizada);
});

const cambiarEstado = (id, nuevoEstado) => {
    // Si hace clic en el que ya está activo, reestablece a 'pendiente'
    const actual = estadosRecomendaciones.value[id] || 'pendiente';
    const destino = actual === nuevoEstado ? 'pendiente' : nuevoEstado;
    
    estadosRecomendaciones.value[id] = destino;
    try {
        localStorage.setItem(
            `estados_rec_${props.paciente.cod_pacientes}`,
            JSON.stringify(estadosRecomendaciones.value)
        );
        window.dispatchEvent(new CustomEvent('recomendacion-actualizada'));
    } catch (e) {
        console.error('No se pudieron guardar los estados locales', e);
    }
};

const obtenerEstadoInfo = (id) => {
    const estado = estadosRecomendaciones.value[id] || 'pendiente';
    const mapping = {
        pendiente: { label: 'Pendiente', color: 'bg-slate-100 text-slate-600 border-slate-200' },
        leida: { label: 'Leída', color: 'bg-blue-50 text-blue-700 border-blue-200' },
        en_practica: { label: 'En Práctica', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        consulta: { label: 'Tengo Dudas', color: 'bg-amber-50 text-amber-700 border-amber-200' },
    };
    return mapping[estado] || mapping.pendiente;
};
</script>

<template>
    <Head title="Dashboard paciente" />

    <AuthenticatedLayout>

        <!-- Muestra el Skeleton Loader mientras carga -->
        <div v-if="cargando" class="auth-shell py-8 bg-slate-50/50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <Skeleton type="hero" />
                <Skeleton type="chart" />
                <div class="grid gap-4 md:grid-cols-3">
                    <Skeleton type="card" :count="3" />
                </div>
            </div>
        </div>

        <!-- Muestra el Dashboard Real con animaciones fluidas una vez cargado -->
        <div v-else class="auth-shell py-8 bg-slate-50/50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- HERO SECTION -->
                <section class="premium-hero premium-hero-motion reveal-item reveal-1 relative overflow-hidden rounded-3xl p-6 sm:p-8">
                    <div class="hero-orb hero-orb-a"></div>
                    <div class="hero-orb hero-orb-b"></div>
                    <div class="hero-orb hero-orb-c"></div>

                    <div class="relative z-10 grid gap-6 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">
                                {{ saludo }}
                            </p>
                            <h3 class="mt-2 text-3xl font-black tracking-tight text-slate-900">
                                {{ nombrePaciente }}
                            </h3>
                            <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-600">
                                Este espacio es para tu seguimiento diario. Registra comidas, revisa tu historial y mejora la calidad de tu control nutricional.
                            </p>

                            <div class="mt-5 rounded-2xl border border-emerald-100 bg-white/80 px-4 py-3 text-xs font-bold text-slate-700 backdrop-blur inline-flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.467 5.99 5.99 0 0 0-1.925 3.546 5.974 5.974 0 0 1-2.133-1A3.75 3.75 0 0 0 12 18Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25V4.5m0 15v2.25m-7.5-7.5h2.25m10.5 0h2.25" />
                                </svg>
                                <span>{{ fraseDia }}</span>
                            </div>
                        </div>

                        <div class="glass-slice rounded-2xl p-5 border border-white/60">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Hoy</p>
                            <p class="mt-2 text-lg font-black capitalize text-slate-800">{{ fechaUi }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ hoy }}</p>
                            <p class="mt-3 text-xs text-slate-500 leading-relaxed">
                                Recomendación: registra tus alimentos el mismo día para asegurar la máxima precisión.
                            </p>

                            <div class="mt-4 flex gap-2">
                                <span class="inline-flex rounded-full border border-emerald-200/50 bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                                    Constancia
                                </span>
                                <span class="inline-flex rounded-full border border-sky-200/50 bg-sky-50 px-2.5 py-0.5 text-[10px] font-bold text-sky-700">
                                    Hábitos
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECCIÓN: RESUMEN SEMANAL MOTIVACIONAL (Sleek Spotify-Wrapped inspired style) -->
                <section class="reveal-item reveal-2 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm overflow-hidden relative">
                    <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-gradient-to-br from-emerald-400/10 to-teal-400/10 blur-3xl pointer-events-none"></div>
                    
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-slate-100 pb-5">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700">
                                <svg class="h-3.5 w-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v5.625c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 0 1 3 18.75v-5.625ZM18 14.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v4.125c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 0 1 18 18.75v-4.125ZM10.5 7.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v10.875c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V7.875Z" />
                                </svg>
                                Resumen Semanal
                            </span>
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">Tu Balance de Hábitos</h3>
                            <p class="text-xs text-slate-500 font-medium leading-relaxed">Una perspectiva de tu constancia y equilibrio nutricional durante los últimos 7 días.</p>
                        </div>
                        
                        <!-- Racha Badge -->
                        <div v-if="props.resumenSemanal?.racha_actual > 0" class="flex items-center gap-2 bg-amber-50 border border-amber-200/60 px-3.5 py-1.5 rounded-2xl self-start md:self-auto shadow-sm shrink-0 whitespace-nowrap">
                            <svg class="h-4 w-4 text-amber-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.467 5.99 5.99 0 0 0-1.925 3.546 5.974 5.974 0 0 1-2.133-1A3.75 3.75 0 0 0 12 18Z" />
                            </svg>
                            <div class="text-left leading-none min-w-0">
                                <span class="block text-[8px] sm:text-[9px] font-black uppercase tracking-wider text-amber-700/80">Racha Activa</span>
                                <span class="block text-xs sm:text-sm font-black text-slate-900 mt-0.5 whitespace-nowrap">
                                    {{ props.resumenSemanal.racha_actual }} {{ props.resumenSemanal.racha_actual === 1 ? 'día' : 'días' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-[1.3fr_0.7fr] mt-6">
                        <div class="space-y-6">
                            <!-- Mensaje Motivacional -->
                            <div class="bg-gradient-to-r from-slate-50 to-emerald-50/20 border border-slate-200/80 rounded-2xl p-5 relative overflow-hidden flex items-start gap-4">
                                <div class="rounded-xl bg-emerald-100/60 p-2 text-emerald-700 shrink-0 shadow-inner">
                                    <svg class="h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.467 5.99 5.99 0 0 0-1.925 3.546 5.974 5.974 0 0 1-2.133-1A3.75 3.75 0 0 0 12 18Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25V4.5m0 15v2.25m-7.5-7.5h2.25m10.5 0h2.25" />
                                    </svg>
                                </div>
                                <p class="text-sm text-slate-700 font-semibold leading-relaxed">
                                    {{ props.resumenSemanal?.mensaje_motivacional }}
                                </p>
                            </div>

                            <!-- 7 Days Grid -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Historial Diario de Registro</h4>
                                <div class="grid grid-cols-7 gap-1 sm:gap-2">
                                    <div 
                                        v-for="dia in props.resumenSemanal?.dias_stats" 
                                        :key="dia.fecha" 
                                        class="flex flex-col items-center justify-between p-1.5 sm:p-2.5 rounded-xl sm:rounded-2xl border transition-all duration-300 min-w-0"
                                        :class="dia.completo 
                                            ? 'bg-emerald-50/40 border-emerald-200/60 text-emerald-800' 
                                            : 'bg-slate-50/50 border-slate-100 text-slate-400'"
                                    >
                                        <span class="text-[10px] sm:text-xs font-extrabold truncate">{{ dia.nombre_dia }}</span>
                                        <div class="my-1 sm:my-2 h-6 w-6 sm:h-7 sm:w-7 rounded-full flex items-center justify-center font-bold text-[10px] sm:text-xs"
                                            :class="dia.completo 
                                                ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20' 
                                                : 'bg-slate-100 text-slate-400'"
                                        >
                                            <span v-if="dia.completo">✓</span>
                                            <span v-else class="text-[9px]">•</span>
                                        </div>
                                        <div class="text-center min-w-0 w-full">
                                            <span class="block text-[9px] sm:text-xs font-black truncate leading-none">
                                                {{ dia.completo ? Math.round(dia.calorias) : '-' }}
                                            </span>
                                            <span class="block text-[7px] sm:text-[8px] uppercase tracking-wider font-extrabold mt-0.5 opacity-60">
                                                {{ dia.completo ? 'kcal' : 'vacío' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Column -->
                        <div class="border border-slate-100 bg-slate-50/40 rounded-3xl p-5 flex flex-col justify-between gap-5">
                            <div class="space-y-4">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Indicadores Clave</h4>
                                
                                <!-- Calorie consumption card -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px] sm:text-xs font-bold text-slate-600 gap-2">
                                        <span class="truncate">Consumo Promedio</span>
                                        <span class="shrink-0">{{ Math.round(props.resumenSemanal?.promedio_calorias) }} / {{ Math.round(props.resumenSemanal?.meta_calorias) }} kcal</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                        <div 
                                            class="h-full rounded-full bg-gradient-to-r transition-all duration-500"
                                            :class="props.resumenSemanal?.promedio_calorias > props.resumenSemanal?.meta_calorias ? 'from-rose-500 to-red-500' : 'from-emerald-500 to-teal-500'"
                                            :style="{ width: Math.min((props.resumenSemanal?.promedio_calorias / (props.resumenSemanal?.meta_calorias || 1)) * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Adherencia indicator -->
                                <div class="flex items-center justify-between border-t border-slate-200/60 pt-3">
                                    <div class="text-left">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Tu Estado Clínico</p>
                                        <p class="text-xs font-bold text-slate-700 mt-0.5">Adherencia actual</p>
                                    </div>
                                    <span 
                                        class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider shadow-sm border"
                                        :class="{
                                            'bg-green-100 text-green-800 border-green-200': props.resumenSemanal?.adherencia?.color === 'success',
                                            'bg-yellow-100 text-yellow-800 border-yellow-200': props.resumenSemanal?.adherencia?.color === 'warning',
                                            'bg-red-100 text-red-800 border-red-200': props.resumenSemanal?.adherencia?.color === 'danger',
                                        }"
                                    >
                                        {{ props.resumenSemanal?.adherencia?.label }}
                                    </span>
                                </div>

                                <!-- Days registered -->
                                <div class="flex items-center justify-between border-t border-slate-200/60 pt-3">
                                    <div class="text-left">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Completitud semanal</p>
                                        <p class="text-xs font-bold text-slate-700 mt-0.5">Días con registros</p>
                                    </div>
                                    <span class="text-sm font-extrabold text-slate-800">
                                        {{ props.resumenSemanal?.dias_registrados }} de 7
                                    </span>
                                </div>
                            </div>

                            <div class="text-center pt-2">
                                <Link 
                                    :href="route('paciente.registros-alimenticios.historial', props.paciente.cod_pacientes)"
                                    class="inline-flex items-center gap-1 text-[11px] font-black uppercase tracking-wider text-emerald-600 hover:text-emerald-700 hover:underline"
                                >
                                    Ver desglose histórico ➔
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECCIÓN: OBJETIVOS CLÍNICOS Y METAS DE HÁBITO -->
                <section class="reveal-item reveal-2">
                    <ObjetivosClinicosWidget 
                        :objetivos="props.objetivosActivos" 
                        :paciente-id="props.paciente?.cod_pacientes"
                    />
                </section>

                <!-- SECCIÓN: GRÁFICA DE EVOLUCIÓN FÍSICA PERSONAL -->
                <section class="reveal-item reveal-3 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm overflow-hidden relative">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-5">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700">
                                <svg class="h-3.5 w-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                                </svg>
                                Mi Progreso
                            </span>
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">Evolución de Peso e IMC</h3>
                            <p class="text-xs text-slate-500 font-medium leading-relaxed">Visualiza los cambios en tu peso y tu Índice de Masa Corporal (IMC) a lo largo del tiempo.</p>
                        </div>
                        <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full self-start sm:self-auto">
                            {{ paciente.historial_peso?.length || 0 }} registros físicos
                        </span>
                    </div>

                    <!-- Canvas o Estado Vacío -->
                    <div v-if="paciente.historial_peso && paciente.historial_peso.length > 0" class="mt-6 relative min-h-[300px] w-full">
                        <canvas ref="pesoChartCanvas"></canvas>
                    </div>
                    <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-10 px-5 text-center">
                        <p class="text-sm font-bold text-slate-500">Aún no hay mediciones físicas registradas.</p>
                        <p class="text-xs text-slate-400 mt-1">Tu progreso se mostrará en este gráfico interactivo cuando tu nutricionista registre tus controles.</p>
                    </div>
                </section>

                <!-- MAIN ACTIONS CARD GRID -->
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article class="premium-card reveal-item reveal-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.16em] text-orange-600">Acción Principal</span>
                            <h4 class="mt-2 text-base font-bold text-slate-900">Registrar alimentos de hoy</h4>
                            <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                                Agrega lo que consumiste hoy por horario, tipo de comida, cantidad y unidad.
                            </p>
                        </div>
                        <div class="mt-4">
                            <Link
                                :href="route('paciente.registros-alimenticios.create', { paciente: paciente.cod_pacientes, fecha: hoy })"
                                class="motion-btn inline-flex items-center rounded-xl bg-orange-600 hover:bg-orange-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition"
                            >
                                Registrar hoy
                            </Link>
                        </div>
                    </article>

                    <article class="premium-card reveal-item reveal-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-[0.16em] text-sky-600">Bitácora</span>
                            <h4 class="mt-2 text-base font-bold text-slate-900">Historial alimenticio</h4>
                            <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                                Revisa tus registros anteriores y consulta qué alimentos registraste cada día.
                            </p>
                        </div>
                        <div class="mt-4">
                            <Link
                                :href="route('paciente.registros-alimenticios.historial', paciente.cod_pacientes)"
                                class="motion-btn inline-flex items-center rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2 text-xs font-bold text-white shadow-sm transition"
                            >
                                Ver historial
                            </Link>
                        </div>
                    </article>

                    <article class="premium-card reveal-item reveal-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-600">Apoyo Profesional</span>
                                <span 
                                    v-if="ultimaRecomendacion"
                                    class="text-[9px] font-bold border px-2 py-0.5 rounded-full"
                                    :class="obtenerEstadoInfo(ultimaRecomendacion.cod_seguimientos_nutricionales).color"
                                >
                                    {{ obtenerEstadoInfo(ultimaRecomendacion.cod_seguimientos_nutricionales).label }}
                                </span>
                            </div>
                            <h4 class="mt-2 text-base font-bold text-slate-900">Última Indicación Médica</h4>
                            
                            <div v-if="ultimaRecomendacion" class="mt-3 space-y-2">
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Emitida el {{ ultimaRecomendacion.fecha }}</span>
                                </div>
                                <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed font-semibold">
                                    {{ ultimaRecomendacion.recomendaciones || 'Indicaciones escritas de control.' }}
                                </p>
                            </div>
                            <div v-else class="mt-4 p-3 bg-slate-50 border border-dashed border-slate-200 rounded-xl text-center">
                                <p class="text-[11px] text-slate-400 font-semibold">Sin indicaciones registradas aún.</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button
                                type="button"
                                @click="abrirRecomendacionesModal(ultimaRecomendacion ? ultimaRecomendacion.cod_seguimientos_nutricionales : null)"
                                class="motion-btn inline-flex items-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition w-full justify-center"
                            >
                                <span>Ver todas las indicaciones</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </article>
                </section>



                <!-- INFO CARDS BOTTOM GRID -->
                <section class="grid gap-4 lg:grid-cols-3">
                    <article class="premium-card reveal-item reveal-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Slices informativos</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="info-slice rounded-xl border border-slate-200/60 p-4">
                                <p class="text-sm font-semibold text-slate-900">Registro claro</p>
                                <p class="mt-1 text-sm text-slate-600">Incluye cantidades y unidad para mejores recomendaciones.</p>
                            </div>
                            <div class="info-slice rounded-xl border border-slate-200/60 p-4">
                                <p class="text-sm font-semibold text-slate-900">Constancia</p>
                                <p class="mt-1 text-sm text-slate-600">Un registro diario corto aporta más valor que uno mensual tardío.</p>
                            </div>
                            <div class="info-slice rounded-xl border border-slate-200/60 p-4">
                                <p class="text-sm font-semibold text-slate-900">Metas Diarias</p>
                                <p class="mt-1 text-sm text-slate-600">Marca con el botón (+) en la sección de objetivos clínicos cada vez que cumplas una pauta.</p>
                            </div>
                            <div class="info-slice rounded-xl border border-slate-200/60 p-4">
                                <p class="text-sm font-semibold text-slate-900">Atención Nutricional</p>
                                <p class="mt-1 text-sm text-slate-600">Tu progreso es revisado continuamente por tu nutricionista para calibrar tu plan.</p>
                            </div>
                        </div>
                    </article>

                    <article class="premium-card reveal-item reveal-6 rounded-2xl border border-cyan-200 bg-white p-5 shadow-sm flex flex-col justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-700">Consejo del día</p>
                            <h4 class="mt-2 text-lg font-semibold text-slate-900">Registra al cerrar cada comida</h4>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Toma menos de un minuto y evita olvidar detalles importantes para tu evolución y el cálculo de tus macros.
                            </p>
                        </div>
                        <div class="mt-4 rounded-xl border border-cyan-100 bg-cyan-50/50 p-3 text-xs text-cyan-700 leading-relaxed">
                            Tip: si estás apurado, usa la función de <strong>copiar del día anterior</strong> para cargar tu base habitual en un segundo.
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.premium-hero {
    position: relative;
    border: 1px solid rgba(16, 185, 129, 0.2);
    background:
        radial-gradient(120% 120% at 0% 0%, rgba(16, 185, 129, 0.16) 0%, rgba(16, 185, 129, 0) 42%),
        radial-gradient(110% 110% at 100% 100%, rgba(14, 165, 233, 0.14) 0%, rgba(14, 165, 233, 0) 45%),
        linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(250, 253, 255, 0.92));
    box-shadow:
        0 18px 40px rgba(15, 23, 42, 0.08),
        0 1px 0 rgba(255, 255, 255, 0.7) inset;
}

.premium-hero-motion {
    animation: heroBreath 7.5s ease-in-out infinite;
}

.premium-hero-motion::after {
    content: '';
    position: absolute;
    inset: -140% -60%;
    background: linear-gradient(110deg, rgba(255, 255, 255, 0) 42%, rgba(255, 255, 255, 0.24) 50%, rgba(255, 255, 255, 0) 58%);
    pointer-events: none;
    mix-blend-mode: screen;
    animation: heroShine 8.5s linear infinite;
}

.hero-orb {
    position: absolute;
    border-radius: 9999px;
    filter: blur(22px);
    opacity: 0.7;
    animation: orbFloat 9s ease-in-out infinite;
}

.hero-orb-a {
    width: 180px;
    height: 180px;
    right: -56px;
    top: -48px;
    background: rgba(16, 185, 129, 0.22);
}

.hero-orb-b {
    width: 210px;
    height: 210px;
    left: -70px;
    bottom: -80px;
    background: rgba(14, 165, 233, 0.18);
    animation-delay: 1.6s;
}

.hero-orb-c {
    width: 120px;
    height: 120px;
    left: 42%;
    top: -42px;
    background: rgba(245, 158, 11, 0.18);
    animation-delay: 0.8s;
}

.glass-slice {
    border: 1px solid rgba(255, 255, 255, 0.8);
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.9), rgba(248, 252, 255, 0.84));
    box-shadow:
        0 10px 24px rgba(15, 23, 42, 0.07),
        0 1px 0 rgba(255, 255, 255, 0.75) inset;
}

.premium-card {
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    box-shadow:
        0 10px 24px rgba(15, 23, 42, 0.06),
        0 1px 0 rgba(255, 255, 255, 0.8) inset;
}

.info-slice {
    background: linear-gradient(165deg, #f8fafc, #f1f5f9);
}

.reveal-item {
    opacity: 0;
    transform: translateY(10px) scale(0.99);
    animation: revealUp 520ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.reveal-1 { animation-delay: 40ms; }
.reveal-2 { animation-delay: 90ms; }
.reveal-3 { animation-delay: 150ms; }
.reveal-4 { animation-delay: 210ms; }
.reveal-5 { animation-delay: 270ms; }
.reveal-6 { animation-delay: 330ms; }

@keyframes revealUp {
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes orbFloat {
    0%, 100% {
        transform: translate3d(0, 0, 0);
    }
    50% {
        transform: translate3d(0, -8px, 0);
    }
}

@keyframes heroBreath {
    0%,
    100% {
        transform: translateY(0px) scale(1);
        box-shadow:
            0 18px 40px rgba(15, 23, 42, 0.08),
            0 1px 0 rgba(255, 255, 255, 0.7) inset;
    }
    50% {
        transform: translateY(-3px) scale(1.003);
        box-shadow:
            0 22px 46px rgba(15, 23, 42, 0.11),
            0 1px 0 rgba(255, 255, 255, 0.75) inset;
    }
}

@keyframes heroShine {
    0% {
        transform: translateX(-40%) rotate(3deg);
    }
    100% {
        transform: translateX(40%) rotate(3deg);
    }
}
</style>

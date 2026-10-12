<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import PatientSmartAnalysisPanel from '@/Components/NutricionistaUI/PatientSmartAnalysisPanel.vue';
import PatientKMeansAnalysisPanel from '@/Components/NutricionistaUI/PatientKMeansAnalysisPanel.vue';
import PatientDecisionTreeRiskPanel from '@/Components/NutricionistaUI/PatientDecisionTreeRiskPanel.vue';
import UnifiedSmartAnalysisDashboard from '@/Components/NutricionistaUI/UnifiedSmartAnalysisDashboard.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    paciente: Object,
    historial_peso: {
        type: Array,
        default: () => [],
    },
    objetivos_disponibles: {
        type: Array,
        default: () => [],
    },
    evaluacion_inicial: {
        type: Object,
        default: null,
    },
    analisisInteligente: {
        type: Object,
        default: null,
    },
    motor_analisis_inteligente: {
        type: Object,
        default: () => ({}),
    },
    modelo_kmeans_candidato: {
        type: Object,
        default: null,
    },
    prediccion_kmeans_paciente: {
        type: Object,
        default: null,
    },
    modelo_dt_candidato: {
        type: Object,
        default: null,
    },
    prediccion_dt_paciente: {
        type: Object,
        default: null,
    },
    patrones_disponibles: {
        type: Array,
        default: () => [],
    },
    etiqueta_experimental_ml: {
        type: String,
        default: 'Modelo experimental — entrenado con datos sintéticos controlados. Resultado orientativo, pendiente de validación con datos clínicos reales.',
    },
    contextos_resumen: {
        type: Object,
        default: () => ({
            total: 0,
            activos: 0,
            alta_prioridad: 0,
            ultimo: null,
            criticos: [],
            recientes: [],
        }),
    },
    objetivos_resumen: {
        type: Object,
        default: () => ({
            total: 0,
            activos: 0,
            pausados: 0,
            finalizados: 0,
            ultimo: null,
            recientes: [],
        }),
    },
});

const mostrarModal = ref(false);
const tabActiva = ref('motor_inteligente');

const form = useForm({
    cod_objetivos_nutricionales: '',
});

const objetivosDisponibles = computed(() => props.objetivos_disponibles ?? []);
const evaluacionInicial = computed(() => props.evaluacion_inicial);

const contextosResumen = computed(() => props.contextos_resumen || {
    total: 0,
    activos: 0,
    alta_prioridad: 0,
    ultimo: null,
    criticos: [],
    recientes: [],
});

const tieneContextos = computed(() => Number(contextosResumen.value.total || 0) > 0);
const contextosActivos = computed(() => Number(contextosResumen.value.activos || 0));
const ultimoContexto = computed(() => contextosResumen.value.ultimo || null);
const contextosRecientes = computed(() => contextosResumen.value.recientes || []);
const contextosCriticos = computed(() => contextosResumen.value.criticos || []);
const tieneContextosCriticos = computed(() => contextosCriticos.value.length > 0);
const totalContextosCriticos = computed(() => Number(contextosResumen.value.alta_prioridad || contextosCriticos.value.length || 0));

const expandirContextoCritico = ref({});
const expandirContextoReciente = ref({});
const expandirObjetivoUltimo = ref(false);
const expandirObjetivoReciente = ref({});

const toggleExpandir = (refObj, index) => {
    refObj.value[index] = !refObj.value[index];
};

const prioridadLabel = (prioridad) => {
    const labels = {
        baja: 'Baja',
        media: 'Media',
        alta: 'Alta',
    };

    return labels[prioridad] || 'Media';
};

const prioridadContextoClasses = (prioridad) => {
    if (prioridad === 'alta') {
        return 'bg-rose-100 text-rose-700 ring-rose-200';
    }

    if (prioridad === 'media') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    return 'bg-slate-100 text-slate-600 ring-slate-200';
};

const contextosHistorialUrl = computed(() => {
    return route('nutricionista.contextos-paciente.historial', props.paciente.cod_pacientes);
});

const contextoCrearUrl = computed(() => {
    return route('nutricionista.contextos-paciente.create-paciente', props.paciente.cod_pacientes);
});

const estadoContextoClasses = (estado) => {
    return estado === 'activo'
        ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
        : 'bg-slate-100 text-slate-600 ring-slate-200';
};

const abrirModal = () => {
    mostrarModal.value = true;
};

const cerrarModal = () => {
    mostrarModal.value = false;
};

const asignarObjetivo = () => {
    if (!form.cod_objetivos_nutricionales) {
        return;
    }

    form.post(route('nutricionista.pacientes.objetivos.asignar', {
        paciente: props.paciente.cod_pacientes,
        objetivo: form.cod_objetivos_nutricionales,
    }), {
        onSuccess: () => {
            form.reset('cod_objetivos_nutricionales');
            mostrarModal.value = false;
        },
    });
};

const nombreCompleto = (usuario) => {
    if (!usuario) {
        return 'No disponible';
    }

    return usuario.nom_usu && usuario.ape_usu
        ? `${usuario.nom_usu} ${usuario.ape_usu}`
        : usuario.nom_usu || usuario.nombre_completo || 'No disponible';
};

const estadoEvaluacion = computed(() => {
    return evaluacionInicial.value?.est_evi || null;
});

const evaluacionExiste = computed(() => Boolean(evaluacionInicial.value));

const puedeEditarEvaluacion = computed(() => {
    return ['borrador', 'pendiente_revision', 'activa'].includes(estadoEvaluacion.value);
});

const estadoEvaluacionLabel = computed(() => {
    const labels = {
        borrador: 'Borrador',
        pendiente_revision: 'Pendiente de revisión',
        activa: 'Activa',
        cerrada: 'Cerrada',
    };

    return labels[estadoEvaluacion.value] || 'Sin evaluación';
});

const estadoEvaluacionClasses = computed(() => {
    const classes = {
        borrador: 'bg-slate-100 text-slate-700 ring-slate-200',
        pendiente_revision: 'bg-amber-100 text-amber-800 ring-amber-200',
        activa: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        cerrada: 'bg-red-100 text-red-800 ring-red-200',
    };

    return classes[estadoEvaluacion.value] || 'bg-slate-100 text-slate-700 ring-slate-200';
});

const evaluacionPrincipalUrl = computed(() => {
    if (!evaluacionExiste.value) {
        return route('nutricionista.evaluaciones-iniciales.create-paciente', props.paciente.cod_pacientes);
    }

    return route('nutricionista.evaluaciones-iniciales.show-paciente', props.paciente.cod_pacientes);
});

const evaluacionEditarUrl = computed(() => {
    if (!evaluacionExiste.value) {
        return '';
    }

    return route(
        'nutricionista.evaluaciones-iniciales.edit',
        evaluacionInicial.value.cod_evaluaciones_iniciales,
    );
});

const textoBotonEvaluacion = computed(() => {
    if (!evaluacionExiste.value) {
        return 'Crear evaluación inicial';
    }

    if (estadoEvaluacion.value === 'borrador') {
        return 'Completar evaluación';
    }

    if (estadoEvaluacion.value === 'pendiente_revision') {
        return 'Revisar evaluación';
    }

    if (estadoEvaluacion.value === 'cerrada') {
        return 'Ver checkpoint inicial';
    }

    return 'Ver evaluación inicial';
});

const objetivosResumen = computed(() => props.objetivos_resumen || {
    total: 0,
    activos: 0,
    pausados: 0,
    finalizados: 0,
    ultimo: null,
    recientes: [],
});

const tieneObjetivos = computed(() => Number(objetivosResumen.value.total || 0) > 0);
const objetivosActivos = computed(() => Number(objetivosResumen.value.activos || 0));
const objetivosPausados = computed(() => Number(objetivosResumen.value.pausados || 0));
const objetivosFinalizados = computed(() => Number(objetivosResumen.value.finalizados || 0));
const ultimoObjetivo = computed(() => objetivosResumen.value.ultimo || null);
const objetivosRecientes = computed(() => objetivosResumen.value.recientes || []);

const objetivosPacienteUrl = computed(() => {
    return route('nutricionista.objetivos-nutricionales.paciente', props.paciente.cod_pacientes);
});

const objetivoCrearUrl = computed(() => {
    return route('nutricionista.objetivos-nutricionales.create-paciente', props.paciente.cod_pacientes);
});

const seguimientoCrearUrl = computed(() => {
    return route('nutricionista.seguimientos.create', props.paciente.cod_pacientes);
});

const objetivoShowUrl = (objetivo) => {
    return route('nutricionista.objetivos-nutricionales.show', objetivo.cod_objetivos_nutricionales);
};

const estadoObjetivoClasses = (estado) => {
    if (estado === 'activo') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (estado === 'pausado') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    if (estado === 'cumplido') {
        return 'bg-cyan-100 text-cyan-700 ring-cyan-200';
    }

    if (estado === 'cerrado') {
        return 'bg-slate-200 text-slate-700 ring-slate-300';
    }

    return 'bg-rose-100 text-rose-700 ring-rose-200';
};

const pesoChartCanvas = ref(null);
let pesoChart = null;

const renderPesoChart = () => {
    if (!pesoChartCanvas.value || !props.historial_peso || props.historial_peso.length === 0) return;

    const ctx = pesoChartCanvas.value.getContext('2d');
    const labels = props.historial_peso.map(d => d.fecha);
    const pesos = props.historial_peso.map(d => d.peso);
    const imcs = props.historial_peso.map(d => d.imc);

    if (pesoChart) {
        pesoChart.destroy();
    }

    pesoChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Peso (kg)',
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
                    label: 'IMC',
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

onMounted(() => {
    nextTick(() => {
        renderPesoChart();
    });
});

onUnmounted(() => {
    if (pesoChart) {
        pesoChart.destroy();
    }
});

const textoCorto = (texto, limite = 160) => {
    if (!texto) {
        return 'Sin descripción registrada.';
    }

    return texto.length > limite
        ? `${texto.slice(0, limite)}...`
        : texto;
};
</script>

<template>
    <Head title="Ficha del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Panel del nutricionista"
                title="Ficha clínica del paciente"
                description="Vista central para revisar la evolución, contextos clínicos, metas de nutrición y acciones rápidas de consulta."
            >
                <template #actions>
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur transition-all duration-300 hover:shadow-md">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 px-1">
                            Operaciones Clínicas
                        </p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link
                                :href="seguimientoCrearUrl"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-cyan-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-cyan-700 transition"
                            >
                                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Registrar Seguimiento
                            </Link>

                            <Link
                                :href="route('nutricionista.pacientes.index')"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 ring-1 ring-slate-200/80 hover:bg-slate-100 hover:text-slate-900 transition"
                            >
                                <svg class="h-4 w-4 text-slate-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                </svg>
                                Volver al Listado
                            </Link>
                        </div>
                    </div>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                
                <!-- Tarjeta Resumen del Paciente Principal (Identidad Unificada) -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm motion-card-enter">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-cyan-500 to-emerald-400 text-white font-black text-lg shadow-sm">
                                {{ paciente.usuario?.nombre_completo ? paciente.usuario.nombre_completo.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'P' }}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                                    {{ nombreCompleto(paciente.usuario) }}
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
                            <ClinicalBadge
                                v-if="paciente.est_pac"
                                :label="paciente.est_pac"
                                tone="success"
                                class="shadow-sm border uppercase font-bold"
                            />
                            <ClinicalBadge
                                v-if="tieneContextosCriticos"
                                :label="totalContextosCriticos + ' contextos críticos'"
                                tone="danger"
                                class="shadow-sm border animate-pulse"
                            />
                        </div>
                    </div>

                    <!-- Fila de Estadísticas Físicas Clínicas -->
                    <div class="mt-6 grid gap-4 grid-cols-2 md:grid-cols-4 pt-6 border-t border-slate-100">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/30 p-4 flex items-center gap-3 transition hover:bg-emerald-50/10 hover:border-emerald-100 duration-250">
                            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100/50 shadow-sm">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <!-- Balance scale icon representing weight -->
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17m0-17L6 7m6-4l6 4M4 10a4 4 0 004 4h8a4 4 0 004-4M8 14v4a2 2 0 002 2h4a2 2 0 002-2v-4" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Peso Actual</span>
                                <p class="text-lg font-black text-slate-800">{{ paciente.pes_pac || '--' }} <span class="text-xs font-semibold text-slate-500">kg</span></p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/30 p-4 flex items-center gap-3 transition hover:bg-cyan-50/10 hover:border-cyan-100 duration-250">
                            <div class="p-2.5 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100/50 shadow-sm">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <!-- Ruler icon representing height -->
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 4v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2zM9 6h4M9 10h6M9 14h4M9 18h6" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Estatura</span>
                                <p class="text-lg font-black text-slate-800">{{ paciente.estatura_pac || '--' }} <span class="text-xs font-semibold text-slate-500">m</span></p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/30 p-4 flex items-center gap-3 transition hover:bg-violet-50/10 hover:border-violet-100 duration-250">
                            <div class="p-2.5 rounded-xl bg-violet-50 text-violet-600 border border-violet-100/50 shadow-sm">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <!-- Calendar icon representing age -->
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Edad</span>
                                <p class="text-lg font-black text-slate-800">{{ paciente.edad_pac ?? '--' }} <span class="text-xs font-semibold text-slate-500">años</span></p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/30 p-4 flex items-center gap-3 transition duration-250"
                             :class="tieneContextosCriticos ? 'hover:bg-rose-50/10 hover:border-rose-200' : 'hover:bg-amber-50/10 hover:border-amber-200'">
                            <div class="p-2.5 rounded-xl shadow-sm border" 
                                 :class="tieneContextosCriticos ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-amber-50 text-amber-600 border-amber-100'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <!-- Shield/Alert icon representing active contexts/health alerts -->
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Contextos Activos</span>
                                <p class="text-lg font-black text-slate-800">{{ contextosActivos }} <span class="text-xs font-semibold text-slate-500">vigentes</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BARRA DE PESTAÑAS CLÍNICAS (Sleek, Modern Tabs with active glow states) -->
                <div class="flex flex-wrap gap-2 animate-fade-in bg-white/60 p-1.5 rounded-2xl border border-slate-200/60 backdrop-blur shadow-sm">
                    <button 
                        type="button"
                        @click="tabActiva = 'motor_inteligente'"
                        class="flex items-center gap-2 px-5 py-3 rounded-xl font-black text-xs uppercase tracking-wider transition-all duration-300 ring-1"
                        :class="tabActiva === 'motor_inteligente' 
                            ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/30 ring-indigo-500' 
                            : 'text-indigo-700 bg-indigo-50/70 hover:bg-indigo-100/80 ring-indigo-200/60'"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                        Análisis Nutricional Inteligente
                        <span class="ml-1 inline-flex items-center rounded-full bg-white/20 px-2 py-0.5 text-[9px] font-black">
                            Motor v1.0
                        </span>
                    </button>

                    <button 
                        type="button"
                        @click="tabActiva = 'diagnostico'"
                        class="flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300"
                        :class="tabActiva === 'diagnostico' 
                            ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' 
                            : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/80'"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                        </svg>
                        Diagnóstico General
                    </button>

                    <button 
                        type="button"
                        @click="tabActiva = 'evaluacion'"
                        class="flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300"
                        :class="tabActiva === 'evaluacion' 
                            ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20' 
                            : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/80'"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H9.75M9.75 8.25h4.5m-.75 11.25c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H5.25M16.5 13.5h3.875c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H16.5M16.5 16.5h3.375c.621 0 1.125-.504 1.125-1.125v-1.625" />
                        </svg>
                        Evaluación y Contexto
                    </button>

                    <button 
                        type="button"
                        @click="tabActiva = 'objetivos'"
                        class="flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300"
                        :class="tabActiva === 'objetivos' 
                            ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20' 
                            : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/80'"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3M12 5.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm0 13.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm9-6.75a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0M3 12a2.25 2.25 0 1 1 4.5 0A2.25 2.25 0 0 1 3 12Z" />
                        </svg>
                        Objetivos Clínicos
                    </button>

                    <button 
                        type="button"
                        @click="tabActiva = 'patron_kmeans'"
                        class="flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300"
                        :class="tabActiva === 'patron_kmeans' 
                            ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' 
                            : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/80'"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                        </svg>
                        Patrón Ingesta (K-Means)
                        <span v-if="prediccion_kmeans_paciente" class="ml-1 inline-flex items-center rounded-full bg-amber-100/80 px-2 py-0.5 text-[9px] font-black text-amber-900">
                            {{ prediccion_kmeans_paciente.resultado_principal_pml }}
                        </span>
                    </button>

                    <button 
                        type="button"
                        @click="tabActiva = 'riesgo_arbol'"
                        class="flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300"
                        :class="tabActiva === 'riesgo_arbol' 
                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' 
                            : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/80'"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        Estimación Riesgo (Árbol ML)
                        <span v-if="prediccion_dt_paciente" class="ml-1 inline-flex items-center rounded-full bg-indigo-100/80 px-2 py-0.5 text-[9px] font-black text-indigo-900">
                            {{ prediccion_dt_paciente.resultado_principal_pml }}
                        </span>
                    </button>
                </div>

                <!-- TAB 0: ANÁLISIS NUTRICIONAL INTELIGENTE UNIFICADO (FASE 2G) -->
                <div v-show="tabActiva === 'motor_inteligente'" class="space-y-6 animate-fade-in">
                    <UnifiedSmartAnalysisDashboard
                        :paciente="paciente"
                        :motor-data="motor_analisis_inteligente"
                        @cambiar-tab="(tab) => tabActiva = tab"
                    />
                </div>

                <!-- TAB 1: DIAGNÓSTICO GENERAL -->
                <div v-show="tabActiva === 'diagnostico'" class="space-y-6 animate-fade-in">
                    <div class="grid gap-6 lg:grid-cols-[5fr_7fr] items-start">
                        
                        <!-- Panel de Datos Clínicos y Menú Rápido -->
                        <div class="space-y-6">
                            <!-- Datos Básicos del Paciente (Rediseño con detalles Emerald) -->
                            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-4">
                                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                                    <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100/50 shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-800">
                                        Datos Básicos de Registro
                                    </h3>
                                </div>

                                <div class="space-y-3.5 text-xs font-semibold text-slate-600 leading-relaxed">
                                    <p class="flex justify-between border-b border-slate-100 pb-2.5">
                                        <span class="text-slate-400">Cédula:</span> 
                                        <span class="text-slate-800 font-bold">{{ paciente.usuario?.ci_usu || 'No disponible' }}</span>
                                    </p>
                                    <p class="flex justify-between border-b border-slate-100 pb-2.5">
                                        <span class="text-slate-400">Sexo:</span> 
                                        <span class="text-slate-800 font-bold">{{ paciente.sex_pac || 'No registrado' }}</span>
                                    </p>
                                    <p class="flex justify-between border-b border-slate-100 pb-2.5">
                                        <span class="text-slate-400">Tipo de Sangre:</span> 
                                        <span class="text-slate-800 font-bold">{{ paciente.tip_san_pac || 'No disponible' }}</span>
                                    </p>
                                    <p class="flex justify-between border-b border-slate-100 pb-2.5 flex-col gap-1.5">
                                        <span class="text-slate-400">Alergias Alimentarias:</span> 
                                        <span class="text-xs font-bold bg-rose-50 border border-rose-100/80 text-rose-850 rounded-xl px-3 py-1.5 leading-relaxed mt-0.5">
                                            {{ paciente.ale_pac || 'No registradas' }}
                                        </span>
                                    </p>
                                    <p class="flex justify-between items-center">
                                        <span class="text-slate-400">Estado de Cuenta:</span> 
                                        <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 shadow-sm">
                                            {{ paciente.est_pac || 'Activo' }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Nutricionista Asignado (Rediseño con detalles Emerald) -->
                            <div
                                v-if="paciente.nutricionista_actual"
                                class="rounded-3xl border border-emerald-100/80 bg-gradient-to-br from-emerald-50/40 to-teal-50/20 p-6 shadow-sm flex flex-col justify-between"
                            >
                                <div>
                                    <span class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Seguimiento Clínico</span>
                                    <h3 class="mt-2 text-base font-bold text-slate-800 leading-tight">
                                        {{ nombreCompleto(paciente.nutricionista_actual.nutricionista?.usuario) }}
                                    </h3>
                                    <p class="text-xs text-slate-500 font-semibold mt-1">
                                        Especialidad: {{ paciente.nutricionista_actual.nutricionista?.esp_nut || 'Nutrición General' }}
                                    </p>
                                </div>
                                <div class="mt-6 border-t border-emerald-100/50 pt-3 flex items-center justify-between text-[11px] font-bold text-emerald-800">
                                    <span>Asignado el:</span>
                                    <span>{{ paciente.nutricionista_actual.fec_anp }}</span>
                                </div>
                            </div>

                            <!-- Panel de Control Rápido Clínico (Rediseño con detalles Emerald) -->
                            <div class="rounded-3xl border border-emerald-100/80 bg-slate-50/40 p-6 space-y-4 shadow-sm">
                                <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                                    <div class="p-1 rounded-lg bg-emerald-50 text-emerald-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25a2.25 2.25 0 0 1-2.25 2.25h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-600">Acceso Rápido</h3>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <Link
                                        :href="seguimientoCrearUrl"
                                        class="motion-btn rounded-xl bg-emerald-600 hover:bg-emerald-700 p-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-white shadow-sm hover:shadow-emerald-600/10 transition flex items-center justify-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Seguimiento
                                    </Link>
                                    <Link
                                        :href="objetivoCrearUrl"
                                        class="motion-btn rounded-xl bg-violet-600 hover:bg-violet-700 p-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-white shadow-sm hover:shadow-violet-600/10 transition flex items-center justify-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Objetivo
                                    </Link>
                                    <Link
                                        :href="contextoCrearUrl"
                                        class="motion-btn rounded-xl bg-amber-600 hover:bg-amber-700 p-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-white shadow-sm hover:shadow-amber-600/10 transition flex items-center justify-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        Alerta
                                    </Link>
                                    <Link
                                        :href="objetivosPacienteUrl"
                                        class="motion-btn rounded-xl bg-indigo-600 hover:bg-indigo-700 p-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-white shadow-sm hover:shadow-indigo-600/10 transition flex items-center justify-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 0a9 9 0 1 1 18 0 9 9 0 0 1-18 0Z" />
                                        </svg>
                                        Ver Objetivos
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfica e IA Panel -->
                        <div class="space-y-6">
                            <!-- Gráfica de Evolución de Peso e IMC -->
                            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                    <div>
                                        <span class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Evolución Clínica</span>
                                        <h3 class="mt-0.5 text-base font-bold text-slate-800 leading-tight">Historial de Peso e IMC</h3>
                                        <p class="text-xs text-slate-500 font-semibold mt-0.5">Tendencia de masa corporal e Índice de Masa Corporal.</p>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50 px-2.5 py-1 rounded-full border border-slate-150 w-fit">
                                        {{ historial_peso.length }} mediciones
                                    </span>
                                </div>

                                <!-- Canvas o Estado Vacío -->
                                <div v-if="historial_peso.length > 0" class="relative min-h-[300px] w-full">
                                    <canvas ref="pesoChartCanvas"></canvas>
                                </div>
                                <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-10 px-5 text-center space-y-2">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-500">Aún no hay historial de peso registrado.</p>
                                    <p class="text-xs text-slate-400">Los datos se reflejarán cuando se registren controles o evaluaciones.</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Panel de Análisis Inteligente por IA -->
                    <PatientSmartAnalysisPanel
                        :paciente="paciente"
                        :analisis="analisisInteligente"
                    />
                </div>

                <!-- TAB 2: EVALUACIÓN Y CONTEXTO -->
                <div v-show="tabActiva === 'evaluacion'" class="space-y-6 animate-fade-in">
                    
                    <!-- Banner de Alertas Críticas si existen -->
                    <ClinicalSection
                        v-if="tieneContextosCriticos"
                        eyebrow="Contextos críticos activos"
                        title="Atención clínica prioritaria"
                        description="Este paciente tiene alertas de salud de alta prioridad vigentes. Revísalas cuidadosamente."
                        tone="danger"
                    >
                        <template #actions>
                            <ClinicalBadge
                                :label="totalContextosCriticos + ' crítico(s)'"
                                tone="danger"
                                class="border"
                            />
                        </template>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <article
                                v-for="(ctx, index) in contextosCriticos"
                                :key="ctx.cod_contextos_paciente"
                                class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm space-y-3 transition hover:shadow-md"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <ClinicalBadge
                                            :label="ctx.tip_cxp || 'Alerta'"
                                            tone="danger"
                                            class="border"
                                        />
                                        <ClinicalBadge
                                            :label="'Prioridad ' + prioridadLabel(ctx.pri_cxp)"
                                            tone="danger"
                                        />
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-bold">
                                        {{ ctx.fec_cxp || 'Sin fecha' }}
                                    </span>
                                </div>

                                <p class="text-sm leading-relaxed text-slate-700 font-medium whitespace-pre-line">
                                    {{ expandirContextoCritico[index] ? ctx.des_cxp : textoCorto(ctx.des_cxp, 160) }}
                                </p>

                                <button 
                                    v-if="ctx.des_cxp && ctx.des_cxp.length > 160"
                                    type="button"
                                    @click="toggleExpandir(expandirContextoCritico, index)"
                                    class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline transition flex items-center gap-0.5"
                                >
                                    <span>{{ expandirContextoCritico[index] ? 'Ocultar' : 'Leer nota completa' }}</span>
                                    <svg class="w-3.5 h-3.5 transform transition-transform duration-300" :class="expandirContextoCritico[index] ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </article>
                        </div>
                    </ClinicalSection>

                    <div class="grid gap-6 lg:grid-cols-[7fr_5fr] items-start">
                        
                        <!-- Checkpoint Inicial (Rediseño con detalles Sky/Cyan) -->
                        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-slate-100 pb-4">
                                <div>
                                    <span class="text-[9px] font-black uppercase tracking-widest text-cyan-600">Evaluación Inicial</span>
                                    <h3 class="mt-0.5 text-base font-bold text-slate-800 leading-tight">Checkpoint Inicial del Paciente</h3>
                                    <p class="text-xs text-slate-500 font-semibold mt-0.5">Registro del estado físico e historia al comenzar el control.</p>
                                </div>

                                <span
                                    class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-bold ring-1 border shadow-sm"
                                    :class="estadoEvaluacionClasses"
                                >
                                    {{ estadoEvaluacionLabel }}
                                </span>
                            </div>

                            <div v-if="evaluacionExiste" class="grid gap-3 grid-cols-3">
                                <div class="rounded-2xl bg-slate-50/60 border border-slate-100 p-4 text-center transition hover:bg-slate-50 duration-200">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Fecha</span>
                                    <p class="mt-1.5 text-sm font-black text-slate-800">{{ evaluacionInicial.fec_evi || 'No registrada' }}</p>
                                </div>

                                <div class="rounded-2xl bg-slate-50/60 border border-slate-100 p-4 text-center transition hover:bg-slate-50 duration-200">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Peso Inicial</span>
                                    <p class="mt-1.5 text-sm font-black text-slate-800">{{ evaluacionInicial.peso_evi || '--' }} <span class="text-xs font-semibold text-slate-500">kg</span></p>
                                </div>

                                <div class="rounded-2xl bg-slate-50/60 border border-slate-100 p-4 text-center transition hover:bg-slate-50 duration-200">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">IMC Inicial</span>
                                    <p class="mt-1.5 text-sm font-black text-slate-800">{{ evaluacionInicial.imc_evi || '--' }}</p>
                                </div>
                            </div>

                            <div v-if="evaluacionExiste" class="rounded-2xl border border-slate-150 bg-slate-50/30 p-4">
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block mb-1">Objetivo Inicial Declarado</span>
                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 font-semibold">
                                    {{ evaluacionInicial.objective_inicial_evi || 'Sin objetivo inicial registrado.' }}
                                </p>
                            </div>

                            <div v-else class="rounded-2xl border border-dashed border-cyan-200 bg-cyan-50/10 p-6 text-center space-y-2">
                                <p class="font-bold text-cyan-900">Este paciente aún no tiene evaluación inicial.</p>
                                <p class="text-xs text-cyan-700/95 leading-relaxed max-w-sm mx-auto font-semibold">
                                    Registra su checkpoint inicial antes de definir objetivos y planes nutricionales.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100">
                                <Link
                                    :href="evaluacionPrincipalUrl"
                                    class="motion-btn rounded-xl bg-cyan-600 hover:bg-cyan-700 px-4.5 py-2.5 text-xs font-bold text-white shadow-sm transition"
                                >
                                    {{ textoBotonEvaluacion }}
                                </Link>

                                <Link
                                    v-if="evaluacionExiste && puedeEditarEvaluacion"
                                    :href="evaluacionEditarUrl"
                                    class="motion-btn rounded-xl bg-slate-50 hover:bg-slate-100 px-4.5 py-2.5 text-xs font-bold text-slate-700 border border-slate-200 shadow-sm transition"
                                >
                                    Editar Evaluación
                                </Link>
                            </div>
                        </div>

                        <!-- Sidebar / Línea de Tiempo de Contextos (Rediseño con detalles Sky/Cyan) -->
                        <div class="space-y-6">
                            <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-4">
                                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                                    <div class="p-1.5 rounded-lg bg-cyan-50 text-cyan-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-800">
                                        Contextos de Salud Recientes
                                    </h3>
                                </div>
                                
                                <div v-if="contextosRecientes.length > 0" class="relative border-l-2 border-slate-200 ml-3 py-1 space-y-4">
                                    <div 
                                        v-for="(ctx, index) in contextosRecientes" 
                                        :key="ctx.cod_contextos_paciente"
                                        class="relative pl-6"
                                    >
                                        <!-- Dynamic priority line dot -->
                                        <div class="absolute -left-[6px] top-1.5 flex h-2.5 w-2.5 items-center justify-center rounded-full bg-white shadow-sm"
                                             :class="ctx.pri_cxp === 'alta' ? 'border border-rose-500' : ctx.pri_cxp === 'media' ? 'border border-amber-500' : 'border border-slate-400'">
                                            <div class="h-1 w-1 rounded-full"
                                                 :class="ctx.pri_cxp === 'alta' ? 'bg-rose-500' : ctx.pri_cxp === 'media' ? 'bg-amber-500' : 'bg-slate-400'"></div>
                                        </div>

                                        <div class="rounded-2xl border border-slate-150 bg-slate-50/40 p-4 space-y-2 hover:bg-slate-50 transition duration-200">
                                            <div class="flex items-center justify-between text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                                <span class="text-cyan-700 font-bold bg-cyan-50 px-2 py-0.5 rounded border border-cyan-100/50">{{ ctx.tip_cxp }}</span>
                                                <span>{{ ctx.fec_cxp }}</span>
                                            </div>
                                            <p class="text-xs text-slate-700 leading-relaxed font-semibold">
                                                {{ expandirContextoReciente[index] ? ctx.des_cxp : textoCorto(ctx.des_cxp, 100) }}
                                            </p>

                                            <button 
                                                v-if="ctx.des_cxp && ctx.des_cxp.length > 100"
                                                type="button"
                                                @click="toggleExpandir(expandirContextoReciente, index)"
                                                class="text-[10px] font-bold text-cyan-600 hover:text-cyan-700 hover:underline transition flex items-center"
                                            >
                                                <span>{{ expandirContextoReciente[index] ? 'Ocultar' : 'Leer nota completa' }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-slate-400 font-medium text-center py-6">
                                    No hay contextos clínicos registrados.
                                </div>

                                <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                                    <Link
                                        :href="contextoCrearUrl"
                                        class="motion-btn rounded-xl bg-amber-600 hover:bg-amber-700 px-3.5 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-white shadow-sm transition flex items-center gap-1"
                                    >
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Registrar Alerta
                                    </Link>
                                    <Link
                                        :href="contextosHistorialUrl"
                                        class="motion-btn rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 px-3.5 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-700 shadow-sm transition flex items-center gap-1"
                                    >
                                        <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Historial
                                    </Link>
                                </div>
                            </div>

                            <ClinicalAlert
                                title="Tipos de Contexto"
                                tone="info"
                                class="border"
                            >
                                <div class="space-y-3 text-xs leading-relaxed font-semibold text-slate-700">
                                    <p>
                                        <strong>Evaluación Inicial:</strong> Representa el estado físico, peso, IMC y metas que declaró el paciente al iniciar.
                                    </p>
                                    <p>
                                        <strong>Contextos Activos:</strong> Apuntes clínicos dinámicos del expediente (diagnósticos, cirugías, patologías, alergias) que condicionan el control alimenticio.
                                    </p>
                                </div>
                            </ClinicalAlert>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: OBJETIVOS CLÍNICOS -->
                <div v-show="tabActiva === 'objetivos'" class="space-y-6 animate-fade-in">
                    <div class="grid gap-6 lg:grid-cols-[7fr_5fr] items-start">
                        
                        <!-- Metas Clínicas y Listado (Rediseño con detalles Violet) -->
                        <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-6">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-slate-100 pb-4">
                                <div>
                                    <span class="text-[9px] font-black uppercase tracking-widest text-violet-600">Objetivos Nutricionales</span>
                                    <h3 class="mt-0.5 text-base font-bold text-slate-800 leading-tight">Metas Clínicas Asignadas</h3>
                                    <p class="text-xs text-slate-500 font-semibold mt-0.5">Define y monitorea metas para evaluar el progreso en los controles de seguimiento.</p>
                                </div>

                                <div class="flex flex-wrap gap-1.5 shrink-0 w-fit">
                                    <span class="inline-flex rounded-full bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 shadow-sm">
                                        {{ objetivosActivos }} activos
                                    </span>
                                    <span class="inline-flex rounded-full bg-amber-50 border border-amber-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700 shadow-sm">
                                        {{ objetivosPausados }} pausados
                                    </span>
                                </div>
                            </div>

                            <!-- Métricas Rápidas -->
                            <div class="grid gap-3 grid-cols-3 text-center">
                                <div class="rounded-2xl bg-slate-50/50 p-4 border border-slate-150 transition hover:bg-slate-50 duration-200">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Registrados</span>
                                    <p class="mt-1.5 text-xl font-black text-slate-800">{{ objetivosResumen.total }}</p>
                                </div>
                                <div class="rounded-2xl bg-violet-50/30 p-4 border border-violet-100/60 transition hover:bg-violet-50/50 duration-200">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-violet-750">En Curso</span>
                                    <p class="mt-1.5 text-xl font-black text-violet-800">{{ objetivosActivos }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50/50 p-4 border border-slate-150 transition hover:bg-slate-50 duration-200">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Finalizados</span>
                                    <p class="mt-1.5 text-xl font-black text-slate-800">{{ objetivosFinalizados }}</p>
                                </div>
                            </div>

                            <!-- Listado de Objetivos -->
                            <div v-if="tieneObjetivos" class="space-y-4 pt-2">
                                
                                <!-- Último Objetivo Destacado -->
                                <div v-if="ultimoObjetivo" class="rounded-2xl border border-violet-200 bg-violet-50/20 p-5 space-y-3 relative overflow-hidden transition-all duration-300 hover:shadow-md">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider ring-1 border shadow-sm" :class="estadoObjetivoClasses(ultimoObjetivo.est_obe)">
                                            Último: {{ ultimoObjetivo.est_obe }}
                                        </span>
                                        <span class="text-xs text-slate-400 font-bold">{{ ultimoObjetivo.fec_obe }}</span>
                                    </div>
                                    
                                    <p class="text-sm font-semibold text-slate-700 leading-relaxed">
                                        {{ expandirObjetivoUltimo ? ultimoObjetivo.des_obe : textoCorto(ultimoObjetivo.des_obe, 160) }}
                                    </p>

                                    <button 
                                        v-if="ultimoObjetivo.des_obe && ultimoObjetivo.des_obe.length > 160"
                                        type="button"
                                        @click="expandirObjetivoUltimo = !expandirObjetivoUltimo"
                                        class="text-xs font-bold text-violet-700 hover:text-violet-900 transition flex items-center gap-0.5"
                                    >
                                        <span>{{ expandirObjetivoUltimo ? 'Ocultar detalles' : 'Leer descripción completa' }}</span>
                                        <svg class="w-3.5 h-3.5 transform transition-transform duration-300" :class="expandirObjetivoUltimo ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <div class="pt-2 border-t border-violet-100/50">
                                        <Link :href="objetivoShowUrl(ultimoObjetivo)" class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-violet-700 hover:text-violet-900 transition">
                                            Ver Expediente Completo
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                            </svg>
                                        </Link>
                                    </div>
                                </div>

                                <!-- Grid de Objetivos Recientes -->
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div 
                                        v-for="(obj, index) in objetivosRecientes" 
                                        :key="obj.cod_objetivos_nutricionales" 
                                        class="rounded-2xl border bg-white p-4 flex flex-col justify-between space-y-3 shadow-sm hover:shadow-md transition duration-200"
                                        :class="obj.est_obe === 'activo' ? 'border-l-4 border-l-emerald-500 border-y-slate-200 border-r-slate-200' 
                                               : obj.est_obe === 'pausado' ? 'border-l-4 border-l-amber-500 border-y-slate-200 border-r-slate-200' 
                                               : obj.est_obe === 'cumplido' ? 'border-l-4 border-l-cyan-500 border-y-slate-200 border-r-slate-200' 
                                               : 'border-l-4 border-l-slate-400 border-y-slate-200 border-r-slate-200'"
                                    >
                                        <div class="space-y-2.5">
                                            <div class="flex items-center justify-between text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                                <span class="rounded-full px-2 py-0.5 ring-1 border" :class="estadoObjetivoClasses(obj.est_obe)">{{ obj.est_obe }}</span>
                                                <span>{{ obj.fec_obe }}</span>
                                            </div>
                                            <p class="text-xs text-slate-700 leading-relaxed font-semibold">
                                                {{ expandirObjetivoReciente[index] ? obj.des_obe : textoCorto(obj.des_obe, 120) }}
                                            </p>

                                            <button 
                                                v-if="obj.des_obe && obj.des_obe.length > 120"
                                                type="button"
                                                @click="toggleExpandir(expandirObjetivoReciente, index)"
                                                class="text-[10px] font-bold text-violet-600 hover:text-violet-700 transition flex items-center gap-0.5"
                                            >
                                                <span>{{ expandirObjetivoReciente[index] ? 'Ocultar' : 'Leer más' }}</span>
                                                <svg class="w-3.5 h-3.5 transform transition-transform duration-300" :class="expandirObjetivoReciente[index] ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </div>
                                        <Link :href="objetivoShowUrl(obj)" class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-violet-700 hover:text-violet-900 transition mt-2">
                                            Detalle
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="rounded-2xl border border-dashed border-violet-200 bg-violet-50/20 p-6 text-center space-y-2">
                                <p class="font-bold text-violet-950">Este paciente aún no tiene objetivos definidos.</p>
                                <p class="text-xs text-violet-900/90 leading-relaxed max-w-sm mx-auto">
                                    Crea el primer objetivo clínico para orientar el plan nutricional y sus mediciones.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                                <Link
                                    :href="objetivoCrearUrl"
                                    class="motion-btn rounded-xl bg-violet-600 hover:bg-violet-700 px-4.5 py-2.5 text-xs font-bold text-white shadow-sm transition flex items-center gap-1"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Crear Objetivo
                                </Link>
                                <Link
                                    :href="objetivosPacienteUrl"
                                    class="motion-btn rounded-xl bg-slate-50 hover:bg-slate-100 px-4.5 py-2.5 text-xs font-bold text-slate-700 border border-slate-200 shadow-sm transition flex items-center gap-1"
                                >
                                    <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 0a9 9 0 1 1 18 0 9 9 0 0 1-18 0Z" />
                                    </svg>
                                    Ver Todos
                                </Link>
                            </div>
                        </div>

                        <!-- Sidebar / Acciones de Metas -->
                        <div class="space-y-6">
                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/50 space-y-4">
                                <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Acciones del Control</h3>
                                <p class="text-xs text-slate-500 font-medium leading-relaxed">Operaciones sobre los objetivos del paciente.</p>
                                <div class="flex flex-col gap-2 pt-1">
                                    <button
                                        type="button"
                                        class="motion-btn w-full rounded-xl bg-violet-50 hover:bg-violet-100 border border-violet-200 px-3 py-3 text-center text-xs font-bold text-violet-800 transition flex items-center justify-center gap-1"
                                        @click="abrirModal"
                                    >
                                        <svg class="w-3.5 h-3.5 text-violet-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                        </svg>
                                        Vincular Objetivo Existente
                                    </button>
                                </div>
                            </div>

                            <ClinicalAlert
                                title="Uso Clínico de Metas"
                                tone="violet"
                                class="border"
                            >
                                <div class="space-y-3 text-xs leading-relaxed font-semibold text-slate-700">
                                    <p>
                                        Los objetivos clínicos vinculan las necesidades detectadas en el paciente con metas tangibles de ingesta nutricional.
                                    </p>
                                    <p>
                                        Cuando un objetivo sea cumplido, recuerda cambiar su estado a "cumplido" para archivar y liberar la capacidad del plan.
                                    </p>
                                </div>
                            </ClinicalAlert>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: PATRÓN DE INGESTA K-MEANS (FASE 2C.2) -->
                <div v-show="tabActiva === 'patron_kmeans'" class="space-y-6 animate-fade-in">
                    <PatientKMeansAnalysisPanel
                        :paciente="paciente"
                        :modelo="modelo_kmeans_candidato"
                        :prediccion="prediccion_kmeans_paciente"
                        :patrones-disponibles="patrones_disponibles"
                        :etiqueta-experimental="etiqueta_experimental_ml"
                    />
                </div>

                <!-- TAB 5: ESTIMACIÓN EXPERIMENTAL DE RIESGO - DECISION TREE (FASE 2F) -->
                <div v-show="tabActiva === 'riesgo_arbol'" class="space-y-6 animate-fade-in">
                    <PatientDecisionTreeRiskPanel
                        :paciente="paciente"
                        :modelo="modelo_dt_candidato"
                        :prediccion="prediccion_dt_paciente"
                    />
                </div>
            </div>
        </div>

        <!-- Modal Estilizado (Vincular Objetivo Existente) -->
        <div v-if="mostrarModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl border border-slate-200 motion-card-enter">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-violet-600">Vinculación de Metas</span>
                        <h3 class="mt-1.5 text-lg font-bold text-slate-900">Asignar objetivo existente</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Selecciona una meta ya registrada en la base del sistema para vincularla a este paciente.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="motion-btn rounded-xl bg-slate-50 border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                        @click="cerrarModal"
                    >
                        Cerrar
                    </button>
                </div>

                <!-- Lista de objetivos en Radio con bordes premium -->
                <div v-if="objetivosDisponibles.length > 0" class="mt-4 space-y-3 max-h-[300px] overflow-y-auto pr-1">
                    <label
                        v-for="objetivo in objetivosDisponibles"
                        :key="objetivo.cod_objetivos_nutricionales"
                        class="flex cursor-pointer items-start gap-4 rounded-2xl border p-4 transition-all duration-200"
                        :class="form.cod_objetivos_nutricionales === objetivo.cod_objetivos_nutricionales 
                            ? 'border-violet-500 bg-violet-50/40 ring-2 ring-violet-500/20' 
                            : 'border-slate-200 hover:border-violet-350 hover:bg-violet-50/10'"
                    >
                        <input
                            v-model="form.cod_objetivos_nutricionales"
                            type="radio"
                            name="cod_objetivos_nutricionales"
                            :value="objetivo.cod_objetivos_nutricionales"
                            class="mt-1 focus:ring-violet-500 text-violet-600"
                        >

                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-slate-800 text-sm">
                                        {{ objetivo.des_obe }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400 font-semibold">
                                        Fecha registro: {{ objetivo.fec_obe || '-' }}
                                    </p>
                                </div>

                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 border"
                                      :class="estadoObjetivoClasses(objetivo.est_obe)">
                                    {{ objetivo.est_obe }}
                                </span>
                            </div>

                            <p class="mt-2 text-[10px] text-slate-500 font-semibold bg-slate-50 px-2 py-1 rounded w-fit border border-slate-100">
                                Asignado previamente a: {{ objetivo.paciente?.usuario?.nombre_completo || 'Sin asignar' }}
                            </p>
                        </div>
                    </label>
                </div>

                <div
                    v-if="objetivosDisponibles.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs font-semibold text-slate-400 mt-4"
                >
                    No hay objetivos activos disponibles para asignar en la base de datos.
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        class="motion-btn rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition"
                        @click="cerrarModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="motion-btn rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-violet-700 shadow-sm transition disabled:opacity-50"
                        :disabled="!form.cod_objetivos_nutricionales"
                        @click="asignarObjetivo"
                    >
                        Asignar Objetivo
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

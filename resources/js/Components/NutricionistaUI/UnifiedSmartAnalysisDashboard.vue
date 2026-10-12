<script setup>
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    paciente: {
        type: Object,
        required: true,
    },
    motorData: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['cambiarTab']);

// Modales para gestión de recomendaciones
const recomendacionSeleccionada = ref(null);
const mostrarModalEditarAprobar = ref(false);
const mostrarModalCrearManual = ref(false);

const formAprobar = useForm({
    des_rec: '',
});

const formDescartar = useForm({});

const formManual = useForm({
    des_rec: '',
    tip_rec: 'indicacion_clinica',
    categoria_rec: 'criterio_profesional',
});

const formReanalizar = useForm({});

const abrirAprobarDirecto = (rec) => {
    formAprobar.des_rec = rec.des_rec;
    formAprobar.post(route('nutricionista.recomendaciones.aprobar', rec.cod_recomendaciones_nutricionales), {
        preserveScroll: true,
    });
};

const abrirModalEditar = (rec) => {
    recomendacionSeleccionada.value = rec;
    formAprobar.des_rec = rec.des_rec;
    mostrarModalEditarAprobar.value = true;
};

const guardarEdicionAprobacion = () => {
    if (!recomendacionSeleccionada.value) return;
    formAprobar.post(route('nutricionista.recomendaciones.aprobar', recomendacionSeleccionada.value.cod_recomendaciones_nutricionales), {
        preserveScroll: true,
        onSuccess: () => {
            mostrarModalEditarAprobar.value = false;
            recomendacionSeleccionada.value = null;
        },
    });
};

const descartarSugerencia = (rec) => {
    if (confirm('¿Deseas descartar esta sugerencia asistida?')) {
        formDescartar.post(route('nutricionista.recomendaciones.descartar', rec.cod_recomendaciones_nutricionales), {
            preserveScroll: true,
        });
    }
};

const guardarRecomendacionManual = () => {
    formManual.post(route('nutricionista.recomendaciones.store-manual', props.paciente.cod_pacientes), {
        preserveScroll: true,
        onSuccess: () => {
            mostrarModalCrearManual.value = false;
            formManual.reset();
        },
    });
};

const reanalizarMotor = () => {
    formReanalizar.post(route('nutricionista.motor-analisis.reanalizar', props.paciente.cod_pacientes), {
        preserveScroll: true,
    });
};

const indicadores = computed(() => props.motorData?.indicadores || {});
const alertas = computed(() => props.motorData?.alertas_nutricionales || props.motorData?.alertas_analiticas || props.motorData?.alertas_clinicas || []);
const kmeans = computed(() => props.motorData?.kmeans || {});
const decisionTree = computed(() => props.motorData?.decision_tree || {});
const recomendaciones = computed(() => props.motorData?.recomendaciones || { pendientes: [], aprobadas: [] });
const historialEvolucion = computed(() => props.motorData?.historial_evolucion || []);

const claseRiesgoDT = computed(() => {
    const raw = decisionTree.value?.clase_predicha || '';
    if (raw.toLowerCase().includes('alto')) return 'alto';
    if (raw.toLowerCase().includes('moderado')) return 'moderado';
    if (raw.toLowerCase().includes('bajo')) return 'bajo';
    return null;
});

const claseConfig = computed(() => {
    switch (claseRiesgoDT.value) {
        case 'alto':
            return {
                label: 'Riesgo Alto',
                badgeTone: 'danger',
                border: 'border-rose-200',
                bgBadge: 'bg-rose-100 text-rose-800 border-rose-200',
                textTone: 'text-rose-700',
            };
        case 'moderado':
            return {
                label: 'Riesgo Moderado',
                badgeTone: 'warning',
                border: 'border-amber-200',
                bgBadge: 'bg-amber-100 text-amber-800 border-amber-200',
                textTone: 'text-amber-700',
            };
        case 'bajo':
            return {
                label: 'Riesgo Bajo',
                badgeTone: 'success',
                border: 'border-emerald-200',
                bgBadge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                textTone: 'text-emerald-700',
            };
        default:
            return {
                label: 'No disponible',
                badgeTone: 'neutral',
                border: 'border-slate-200',
                bgBadge: 'bg-slate-100 text-slate-700 border-slate-200',
                textTone: 'text-slate-600',
            };
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- BANNER PERMANENTE DE ESTADO EXPERIMENTAL Y AISLAMIENTO -->
        <div class="rounded-3xl border-2 border-indigo-200 bg-gradient-to-r from-indigo-50/90 via-sky-50/40 to-white p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-indigo-900">
                                Motor Inteligente Nutricional v1.0
                            </span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 border border-slate-200">
                                Estado: VALIDACION
                            </span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 border border-slate-200">
                                Origen: sintetico_controlado
                            </span>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 leading-relaxed pt-0.5">
                            “Modelo experimental orquestado (K-Means + Árbol de Decisión + Reglas Clínicas). Las estimaciones y sugerencias son orientativas y no constituyen diagnóstico ni prescripción automática.”
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="reanalizarMotor"
                        :disabled="formReanalizar.processing"
                        class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition disabled:opacity-50"
                    >
                        <svg class="h-3.5 w-3.5" :class="{ 'animate-spin': formReanalizar.processing }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Reanalizar Ventana
                    </button>
                </div>
            </div>
        </div>

        <!-- FILA DE TARJETAS EJECUTIVAS RESUMEN (4 COLUMNAS) -->
        <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
            <!-- 1. Ventana y Calidad -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-[10px] font-black uppercase tracking-wider">Período de Análisis</span>
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-black text-slate-800">
                        {{ motorData.periodo?.fec_inicio || '--' }} al {{ motorData.periodo?.fec_fin || '--' }}
                    </h4>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Adherencia: <span class="font-bold text-slate-800">{{ motorData.periodo?.adherencia || '0%' }}</span> ({{ motorData.periodo?.dias_registrados || 0 }}/7 días)
                    </p>
                </div>
                <div class="pt-1">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider border"
                        :class="motorData.periodo?.calidad_ventana === 'suficiente' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                    >
                        Calidad {{ motorData.periodo?.calidad_ventana || 'Insuficiente' }}
                    </span>
                </div>
            </div>

            <!-- 2. Patrón K-Means -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-[10px] font-black uppercase tracking-wider">Patrón Ingesta (K-Means)</span>
                    <button
                        type="button"
                        @click="$emit('cambiarTab', 'patron_kmeans')"
                        class="text-[10px] font-bold text-amber-600 hover:text-amber-800 hover:underline"
                    >
                        Ver Detalle →
                    </button>
                </div>
                <div>
                    <h4 class="text-base font-black text-slate-800">
                        {{ kmeans.resultado_principal || 'Sin calcular' }}
                    </h4>
                    <p class="text-xs text-slate-500 font-medium mt-0.5 truncate">
                        {{ kmeans.patron_vinculado?.nom_pat || 'Sin patrón vinculado' }}
                    </p>
                </div>
                <div class="pt-1 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Dist. Centroide:</span>
                    <span class="font-mono font-bold text-slate-800">{{ kmeans.distancia_centroide !== null ? kmeans.distancia_centroide : '--' }}</span>
                </div>
            </div>

            <!-- 3. Estimación Árbol de Decisión -->
            <div class="rounded-3xl border bg-white p-5 shadow-sm space-y-3" :class="claseConfig.border">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-[10px] font-black uppercase tracking-wider">Riesgo Árbol (ML)</span>
                    <button
                        type="button"
                        @click="$emit('cambiarTab', 'riesgo_arbol')"
                        class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline"
                    >
                        Ver Reglas →
                    </button>
                </div>
                <div>
                    <h4 class="text-base font-black" :class="claseConfig.textTone">
                        {{ decisionTree.resultado_formateado || 'No disponible' }}
                    </h4>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Probabilidad estimada: <span class="font-bold text-slate-800">{{ decisionTree.probabilidad_clase ? Math.round(decisionTree.probabilidad_clase * 100) + '%' : '--' }}</span>
                    </p>
                </div>
                <div class="pt-1">
                    <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider border" :class="claseConfig.bgBadge">
                        {{ claseConfig.label }}
                    </span>
                </div>
            </div>

            <!-- 4. Validación Profesional -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-[10px] font-black uppercase tracking-wider">Etiqueta Profesional</span>
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-base font-black text-slate-800">
                        {{ motorData.validacion_profesional?.nivel_riesgo_profesional ? 'Riesgo ' + motorData.validacion_profesional.nivel_riesgo_profesional.toUpperCase() : 'Sin etiquetar' }}
                    </h4>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        {{ motorData.validacion_profesional?.tipo_riesgo_profesional || 'Juicio profesional pendiente' }}
                    </p>
                </div>
                <div class="pt-1">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider border"
                        :class="motorData.validacion_profesional ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                    >
                        {{ motorData.validacion_profesional ? 'Validado por Nutricionista' : 'Pendiente' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- SECCIÓN PRINCIPAL: RESUMEN DE INGESTA VS ALERTAS CLÍNICAS -->
        <div class="grid gap-6 lg:grid-cols-[7fr_5fr] items-start">
            <!-- COLUMNA IZQUIERDA: INDICADORES NUTRICIONALES OBJETIVOS -->
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Indicadores Objetivos de la Ventana</h3>
                        <p class="text-[11px] text-slate-500">Variables agregadas y densidades por 1000 kcal calculadas</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600 border border-slate-200">
                        Feature Engineering v1.0
                    </span>
                </div>

                <!-- Grid de Métricas Principales -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Calorías / Día</span>
                        <p class="text-lg font-black text-slate-800 mt-0.5">{{ Math.round(indicadores.promedio_calorias || 0) }} <span class="text-xs font-semibold text-slate-500">kcal</span></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">% Proteína</span>
                        <p class="text-lg font-black text-slate-800 mt-0.5">{{ indicadores.pct_proteina || 0 }}%</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">% Carbohidratos</span>
                        <p class="text-lg font-black text-slate-800 mt-0.5">{{ indicadores.pct_carbohidratos || 0 }}%</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">% Grasa</span>
                        <p class="text-lg font-black text-slate-800 mt-0.5">{{ indicadores.pct_grasa || 0 }}%</p>
                    </div>
                </div>

                <!-- Densidades Derivadas y Regularidad -->
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-2.5 text-xs">
                    <div class="flex justify-between items-center text-slate-600">
                        <span class="font-semibold">Azúcar / 1000 kcal:</span>
                        <span class="font-mono font-bold" :class="(indicadores.azucar_por_1000kcal || 0) > 44.1 ? 'text-rose-600' : 'text-slate-800'">
                            {{ indicadores.azucar_por_1000kcal || 0 }} g <span class="text-[10px] text-slate-400">(umbral modelo: 44.1)</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span class="font-semibold">Sodio / 1000 kcal:</span>
                        <span class="font-mono font-bold" :class="(indicadores.sodio_por_1000kcal || 0) > 1465.71 ? 'text-rose-600' : 'text-slate-800'">
                            {{ indicadores.sodio_por_1000kcal || 0 }} mg <span class="text-[10px] text-slate-400">(umbral modelo: 1465.7)</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span class="font-semibold">Fibra / 1000 kcal:</span>
                        <span class="font-mono font-bold" :class="(indicadores.fibra_por_1000kcal || 0) < 11.95 ? 'text-amber-600' : 'text-emerald-700'">
                            {{ indicadores.fibra_por_1000kcal || 0 }} g <span class="text-[10px] text-slate-400">(umbral modelo: 11.95)</span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600 pt-1 border-t border-slate-200/60">
                        <span class="font-semibold">Variabilidad Horaria:</span>
                        <span class="font-mono font-bold text-slate-800">
                            {{ indicadores.variabilidad_horaria_min || 0 }} min / día
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span class="font-semibold">Frecuencia Desayuno / Cena:</span>
                        <span class="font-bold text-slate-800">
                            {{ Math.round((indicadores.frecuencia_desayuno || 0) * 100) }}% / {{ Math.round((indicadores.frecuencia_cena || 0) * 100) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: ALERTAS DETERMINÍSTICAS -->
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Alertas Nutricionales</h3>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black" :class="alertas.length > 0 ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-600'">
                        {{ alertas.length }} detectada(s)
                    </span>
                </div>

                <div v-if="alertas.length > 0" class="space-y-2.5 max-h-[280px] overflow-y-auto pr-1">
                    <div
                        v-for="(alt, idx) in alertas"
                        :key="idx"
                        class="rounded-2xl p-3 border text-xs space-y-1 transition"
                        :class="alt.nivel === 'alerta' ? 'bg-rose-50/50 border-rose-200 text-rose-900' : 'bg-amber-50/50 border-amber-200 text-amber-900'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs">{{ alt.titulo }}</span>
                            <span class="text-[9px] font-black uppercase tracking-wider opacity-75">{{ alt.categoria }}</span>
                        </div>
                        <p class="text-[11px] leading-relaxed opacity-90">{{ alt.descripcion }}</p>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-slate-400 text-xs">
                    No se detectaron alertas críticas en los indicadores de esta ventana.
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE RECOMENDACIONES NUTRICIONALES ASISTIDAS -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Recomendaciones Nutricionales Asistidas</h3>
                    <p class="text-xs text-slate-500">Sugerencias algorítmicas sujetas a aprobación, edición o descarte profesional.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="mostrarModalCrearManual = true"
                        class="motion-btn inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Redactar Recomendación Manual
                    </button>
                </div>
            </div>

            <!-- PENDIENTES DE REVISIÓN -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400">
                        Sugerencias Pendientes de Revisión ({{ recomendaciones.pendientes.length }})
                    </span>
                </div>

                <div v-if="recomendaciones.pendientes.length > 0" class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="rec in recomendaciones.pendientes"
                        :key="rec.cod_recomendaciones_nutricionales"
                        class="rounded-2xl border-2 border-indigo-100 bg-gradient-to-b from-indigo-50/30 to-white p-4 space-y-3 shadow-sm hover:border-indigo-300 transition"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="rounded-md bg-indigo-100 px-2 py-0.5 text-[10px] font-black uppercase text-indigo-800">
                                {{ rec.categoria_rec || rec.tip_rec }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-semibold">{{ rec.fec_rec }}</span>
                        </div>

                        <p class="text-xs text-slate-800 font-medium leading-relaxed">
                            {{ rec.des_rec }}
                        </p>

                        <div v-if="rec.justificacion_rec" class="rounded-xl bg-slate-50 p-2 text-[10px] text-slate-600 border border-slate-100">
                            <span class="font-bold text-slate-700">Motivo:</span> {{ rec.justificacion_rec }}
                        </div>

                        <!-- Botones de Acción Profesional -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="descartarSugerencia(rec)"
                                class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition"
                            >
                                Descartar
                            </button>
                            <button
                                type="button"
                                @click="abrirModalEditar(rec)"
                                class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition"
                            >
                                Editar
                            </button>
                            <button
                                type="button"
                                @click="abrirAprobarDirecto(rec)"
                                class="rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm transition"
                            >
                                Aprobar
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-6 text-slate-400 text-xs bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                    No hay sugerencias pendientes. Todas las recomendaciones han sido revisadas o redactadas manualmente.
                </div>
            </div>

            <!-- RECOMENDACIONES YA APROBADAS / HISTÓRICAS -->
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">
                    Recomendaciones Clínicas Validadas y Activas ({{ recomendaciones.aprobadas.length }})
                </span>

                <div v-if="recomendaciones.aprobadas.length > 0" class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                    <div
                        v-for="rec in recomendaciones.aprobadas"
                        :key="rec.cod_recomendaciones_nutricionales"
                        class="rounded-2xl border border-slate-200 bg-white p-4 flex items-start justify-between gap-4 shadow-sm"
                    >
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider border"
                                    :class="rec.estado_rec === 'editada' ? 'bg-cyan-50 text-cyan-800 border-cyan-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                                >
                                    {{ rec.estado_rec === 'editada' ? 'Aprobada con edición' : 'Aprobada' }}
                                </span>
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                    Origen: {{ rec.origen_rec }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-semibold">
                                    Fecha: {{ rec.fec_rec }}
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-slate-800 leading-relaxed pt-1">
                                {{ rec.des_rec }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right text-[10px] text-slate-400 font-medium">
                            {{ rec.nutricionista?.usuario?.nombre_completo || 'Profesional asignado' }}
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-6 text-slate-400 text-xs">
                    No hay recomendaciones aprobadas aún para este paciente.
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE HISTORIAL DE EVOLUCIÓN POR VENTANAS TEMPORALES -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Evolución Histórica por Ventanas Temporales</h3>
                    <p class="text-xs text-slate-500">Trazabilidad de patrones, riesgo estimado y validación profesional a lo largo del tiempo.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600 border border-slate-200">
                    {{ historialEvolucion.length }} ventana(s)
                </span>
            </div>

            <div v-if="historialEvolucion.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 uppercase text-[10px] font-black tracking-wider">
                            <th class="py-3 px-3">Ventana</th>
                            <th class="py-3 px-3">Adherencia</th>
                            <th class="py-3 px-3">Patrón K-Means</th>
                            <th class="py-3 px-3">Riesgo Árbol</th>
                            <th class="py-3 px-3">Prob. Estimada</th>
                            <th class="py-3 px-3">Etiqueta Profesional</th>
                            <th class="py-3 px-3 text-right">Recs Aprobadas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in historialEvolucion" :key="item.snapshot_id" class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-3 font-semibold text-slate-800">
                                {{ item.fec_inicio }} al {{ item.fec_fin }}
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-700">{{ item.adherencia }}</span>
                                <span class="text-[10px] text-slate-400 block">({{ item.dias_registrados }}/7 días)</span>
                            </td>
                            <td class="py-3 px-3 font-bold text-slate-800">
                                {{ item.patron_kmeans }}
                            </td>
                            <td class="py-3 px-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase"
                                    :class="item.riesgo_decision_tree.includes('Alto') ? 'bg-rose-100 text-rose-800' : (item.riesgo_decision_tree.includes('Moderado') ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')"
                                >
                                    {{ item.riesgo_decision_tree }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-mono font-semibold text-slate-700">
                                {{ item.probabilidad_riesgo || '--' }}
                            </td>
                            <td class="py-3 px-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="item.etiqueta_profesional !== 'Sin etiquetar' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-500'"
                                >
                                    {{ item.etiqueta_profesional }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-slate-800">
                                {{ item.recomendaciones_aprobadas }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-6 text-slate-400 text-xs">
                No hay historial de ventanas registrado para este paciente.
            </div>

            <p class="text-[11px] text-slate-400 italic pt-2">
                * Nota: Las variaciones entre ventanas corresponden a ventanas temporales continuas y deben interpretarse bajo el contexto clínico global del paciente.
            </p>
        </div>

        <!-- MODAL: EDITAR Y APROBAR RECOMENDACIÓN -->
        <div v-if="mostrarModalEditarAprobar" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl border border-slate-200 motion-card-enter space-y-4">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600">Revisión Profesional</span>
                        <h4 class="text-base font-bold text-slate-900 mt-0.5">Editar y Aprobar Recomendación</h4>
                    </div>
                    <button type="button" @click="mostrarModalEditarAprobar = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold">
                        Cerrar
                    </button>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-700">Texto de la Recomendación Clínica:</label>
                    <textarea
                        v-model="formAprobar.des_rec"
                        rows="4"
                        class="w-full rounded-2xl border border-slate-200 p-3 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-800"
                        placeholder="Redacta o ajusta la recomendación..."
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="mostrarModalEditarAprobar = false"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="guardarEdicionAprobacion"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 shadow-sm transition"
                    >
                        Guardar y Aprobar
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: CREAR RECOMENDACIÓN MANUAL -->
        <div v-if="mostrarModalCrearManual" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl border border-slate-200 motion-card-enter space-y-4">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600">Prescripción Directa</span>
                        <h4 class="text-base font-bold text-slate-900 mt-0.5">Redactar Recomendación Manual</h4>
                    </div>
                    <button type="button" @click="mostrarModalCrearManual = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold">
                        Cerrar
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Categoría:</label>
                        <input
                            v-model="formManual.categoria_rec"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-800 focus:ring-indigo-500"
                            placeholder="Ej: control_azucar, hidratacion, habitos..."
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Descripción / Indicación:</label>
                        <textarea
                            v-model="formManual.des_rec"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-200 p-3 text-xs text-slate-800 focus:ring-indigo-500"
                            placeholder="Escribe la recomendación profesional para el paciente..."
                        ></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="mostrarModalCrearManual = false"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="guardarRecomendacionManual"
                        :disabled="!formManual.des_rec"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 shadow-sm transition disabled:opacity-50"
                    >
                        Registrar Recomendación
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

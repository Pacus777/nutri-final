<script setup>
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    paciente: {
        type: Object,
        required: true,
    },
    modelo: {
        type: Object,
        default: null,
    },
    prediccion: {
        type: Object,
        default: null,
    },
    patronesDisponibles: {
        type: Array,
        default: () => [],
    },
    etiquetaExperimental: {
        type: String,
        default: 'Modelo experimental — entrenado con datos sintéticos controlados. Resultado orientativo, pendiente de validación con datos clínicos reales.',
    },
});

// Modal de Validación de Cluster
const clusterSeleccionadoParaValidar = ref(null);
const mostrarModalValidacionCluster = ref(false);
const creandoNuevoPatron = ref(false);

const formValidacionCluster = useForm({
    cod_patrones_alimenticios: '',
    nuevo_nombre_patron: '',
    nueva_descripcion_patron: '',
    descripcion_profesional_clm: '',
    estado_validacion_clm: 'validado',
});

// Modal de Validación de Inferencia Individual
const mostrarModalValidarPrediccion = ref(false);
const formValidacionPrediccion = useForm({
    estado_validacion_pml: 'validado_correcto',
    resultado_validado_pml: '',
    obs_validacion_pml: '',
});

const abrirModalValidarCluster = (cluster) => {
    clusterSeleccionadoParaValidar.value = cluster;
    formValidacionCluster.cod_patrones_alimenticios = cluster.cod_patrones_alimenticios_clm || '';
    formValidacionCluster.nuevo_nombre_patron = '';
    formValidacionCluster.nueva_descripcion_patron = '';
    formValidacionCluster.descripcion_profesional_clm = cluster.descripcion_profesional_clm || '';
    formValidacionCluster.estado_validacion_clm = cluster.estado_validacion_clm === 'pendiente' ? 'validado' : cluster.estado_validacion_clm;
    creandoNuevoPatron.value = false;
    mostrarModalValidacionCluster.value = true;
};

const guardarValidacionCluster = () => {
    if (!clusterSeleccionadoParaValidar.value) return;

    formValidacionCluster.post(route('nutricionista.ml.clusters.validar', clusterSeleccionadoParaValidar.value.cod_clusters_modelo), {
        onSuccess: () => {
            mostrarModalValidacionCluster.value = false;
            clusterSeleccionadoParaValidar.value = null;
        },
    });
};

const abrirModalValidarPrediccion = () => {
    if (!props.prediccion) return;
    formValidacionPrediccion.estado_validacion_pml = props.prediccion.estado_validacion_pml || 'validado_correcto';
    formValidacionPrediccion.resultado_validado_pml = props.prediccion.resultado_validado_pml || props.prediccion.resultado_principal_pml;
    formValidacionPrediccion.obs_validacion_pml = props.prediccion.obs_validacion_pml || '';
    mostrarModalValidarPrediccion.value = true;
};

const guardarValidacionPrediccion = () => {
    if (!props.prediccion) return;
    formValidacionPrediccion.post(route('nutricionista.ml.predicciones.validar', props.prediccion.cod_predicciones_ml), {
        onSuccess: () => {
            mostrarModalValidarPrediccion.value = false;
        },
    });
};

const distanciasCentroides = computed(() => {
    return props.prediccion?.explicabilidad_pml?.distancias_centroides || {};
});

const indicadoresVector = computed(() => {
    return props.prediccion?.explicabilidad_pml?.indicadores_principales || {};
});

const centroideAsignadoValores = computed(() => {
    return props.prediccion?.explicabilidad_pml?.centroide_asignado_valores || {};
});

const estadoClusterTone = (estado) => {
    if (estado === 'validado') return 'success';
    if (estado === 'ajustado') return 'info';
    return 'warning';
};
</script>

<template>
    <div class="space-y-6">
        <!-- BANNER DE ETIQUETADO VISUAL OBLIGATORIO -->
        <div class="rounded-3xl border-2 border-amber-300 bg-gradient-to-r from-amber-50 via-orange-50/60 to-amber-50 p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-md shadow-amber-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-amber-200/80 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-900">
                            Fase 2C — Experimentación Offline
                        </span>
                        <span class="text-xs font-bold text-amber-800">
                            Ambiente de Validación
                        </span>
                    </div>
                    <p class="text-sm font-bold text-slate-800 leading-snug">
                        {{ etiquetaExperimental }}
                    </p>
                    <p class="text-xs text-slate-600 font-medium">
                        El modelo se encuentra en estado de <strong class="text-amber-900 font-bold">validación técnica</strong>. Los clusters no constituyen diagnósticos médicos automáticos y requieren evaluación profesional del nutricionista.
                    </p>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 1: PREDICCIÓN DEL PACIENTE -->
        <div v-if="prediccion" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Inferencia de Patrón Alimenticio</span>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-3 mt-1">
                        {{ prediccion.resultado_principal_pml }}
                        <ClinicalBadge
                            :label="'Estado: ' + prediccion.estado_validacion_pml"
                            :tone="prediccion.estado_validacion_pml === 'validado_correcto' ? 'success' : 'warning'"
                            class="text-xs font-bold border uppercase"
                        />
                    </h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Snapshot #{{ prediccion.snapshot?.cod_features_nutricionales }} (Período: {{ prediccion.snapshot?.fec_inicio_fnu }} al {{ prediccion.snapshot?.fec_fin_fnu }} | Adherencia: {{ (Number(prediccion.snapshot?.adherencia_registro_fnu || 0) * 100).toFixed(1) }}%)
                    </p>
                </div>

                <button
                    type="button"
                    @click="abrirModalValidarPrediccion"
                    class="motion-btn flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Validar Predicción del Paciente
                </button>
            </div>

            <!-- Análisis de proximidad a centroides (Fase 2C.3) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                        </svg>
                        Análisis de proximidad a centroides
                    </h4>
                    <span class="text-[10px] text-slate-400 font-semibold">Distancia euclidiana sobre espacio estandarizado (menor = mayor cercanía)</span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="(dist, clusterName) in distanciasCentroides"
                    :key="clusterName"
                    class="rounded-2xl p-4 border transition duration-200"
                    :class="clusterName === prediccion.resultado_principal_pml
                        ? 'border-emerald-300 bg-emerald-50/50 shadow-sm ring-2 ring-emerald-400/20'
                        : 'border-slate-200 bg-slate-50/30'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold" :class="clusterName === prediccion.resultado_principal_pml ? 'text-emerald-800' : 'text-slate-600'">
                            {{ clusterName }}
                        </span>
                        <span
                            v-if="clusterName === prediccion.resultado_principal_pml"
                            class="rounded-full bg-emerald-200 text-emerald-800 text-[9px] font-black px-2 py-0.5"
                        >
                            Asignado
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <span class="text-2xl font-black" :class="clusterName === prediccion.resultado_principal_pml ? 'text-emerald-900' : 'text-slate-800'">
                            {{ dist }}
                        </span>
                        <span class="text-[10px] font-semibold text-slate-400">dist. euclídea</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparativa de Indicadores: Paciente vs Centroide del Cluster -->
            <div class="rounded-2xl border border-slate-150 bg-slate-50/40 p-5 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    Métricas del Snapshot vs Centroide Promedio del {{ prediccion.resultado_principal_pml }}
                </h4>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-left">
                        <thead class="border-b border-slate-200 text-[10px] font-bold uppercase text-slate-400">
                            <tr>
                                <th class="py-2 px-3">Métrica Nutricional</th>
                                <th class="py-2 px-3 text-emerald-700 font-black">Valor Paciente</th>
                                <th class="py-2 px-3 text-slate-600">Media del Cluster</th>
                                <th class="py-2 px-3 text-slate-400">Unidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                            <tr>
                                <td class="py-2 px-3 text-slate-900 font-bold">Promedio Calórico Diario</td>
                                <td class="py-2 px-3 text-emerald-700 font-black">{{ indicadoresVector.promedio_calorias_fnu }}</td>
                                <td class="py-2 px-3 text-slate-600">{{ centroideAsignadoValores.promedio_calorias_fnu || '--' }}</td>
                                <td class="py-2 px-3 text-slate-400">kcal/día</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 text-slate-900 font-bold">Distribución Macronutrientes (P / C / G)</td>
                                <td class="py-2 px-3 text-emerald-700 font-black">
                                    {{ indicadoresVector.pct_proteina_fnu }}% / {{ indicadoresVector.pct_carbohidratos_fnu }}% / {{ indicadoresVector.pct_grasa_fnu }}%
                                </td>
                                <td class="py-2 px-3 text-slate-600">
                                    {{ centroideAsignadoValores.pct_proteina_fnu || '--' }}% / {{ centroideAsignadoValores.pct_carbohidratos_fnu || '--' }}% / {{ centroideAsignadoValores.pct_grasa_fnu || '--' }}%
                                </td>
                                <td class="py-2 px-3 text-slate-400">% energía</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 text-slate-900 font-bold">Densidad de Fibra</td>
                                <td class="py-2 px-3 text-emerald-700 font-black">{{ indicadoresVector.fibra_por_1000kcal }}</td>
                                <td class="py-2 px-3 text-slate-600">{{ centroideAsignadoValores.fibra_por_1000kcal || '--' }}</td>
                                <td class="py-2 px-3 text-slate-400">g / 1000 kcal</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 text-slate-900 font-bold">Densidad de Azúcar</td>
                                <td class="py-2 px-3 text-emerald-700 font-black">{{ indicadoresVector.azucar_por_1000kcal }}</td>
                                <td class="py-2 px-3 text-slate-600">{{ centroideAsignadoValores.azucar_por_1000kcal || '--' }}</td>
                                <td class="py-2 px-3 text-slate-400">g / 1000 kcal</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 text-slate-900 font-bold">Densidad de Sodio</td>
                                <td class="py-2 px-3 text-emerald-700 font-black">{{ indicadoresVector.sodio_por_1000kcal }}</td>
                                <td class="py-2 px-3 text-slate-600">{{ centroideAsignadoValores.sodio_por_1000kcal || '--' }}</td>
                                <td class="py-2 px-3 text-slate-400">mg / 1000 kcal</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 text-slate-900 font-bold">Frecuencia Desayuno / Cena / Snack</td>
                                <td class="py-2 px-3 text-emerald-700 font-black">
                                    {{ indicadoresVector.frecuencia_desayuno_fnu }} / {{ indicadoresVector.frecuencia_cena_fnu }} / {{ indicadoresVector.frecuencia_snack_fnu }}
                                </td>
                                <td class="py-2 px-3 text-slate-600">
                                    {{ centroideAsignadoValores.frecuencia_desayuno_fnu || '--' }} / {{ centroideAsignadoValores.frecuencia_cena_fnu || '--' }} / {{ centroideAsignadoValores.frecuencia_snack_fnu || '--' }}
                                </td>
                                <td class="py-2 px-3 text-slate-400">fracción días [0-1]</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 text-slate-900 font-bold">Variabilidad Horaria</td>
                                <td class="py-2 px-3 text-emerald-700 font-black">{{ indicadoresVector.variabilidad_horaria_min_fnu }}</td>
                                <td class="py-2 px-3 text-slate-600">{{ centroideAsignadoValores.variabilidad_horaria_min_fnu || '--' }}</td>
                                <td class="py-2 px-3 text-slate-400">minutos</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: INTERPRETACIÓN Y GESTIÓN DE CLUSTERS DEL MODELO -->
        <div v-if="modelo" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Arquitectura del Modelo de Clustering</span>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2 mt-0.5">
                        {{ modelo.nombre_mml }} ({{ modelo.version_mml }})
                        <ClinicalBadge
                            :label="modelo.est_mml"
                            tone="warning"
                            class="text-[10px] font-bold uppercase border"
                        />
                    </h3>
                    <p class="text-xs text-slate-500 font-medium">
                        Silhouette Score: <strong class="text-slate-800">{{ modelo.metricas_rendimiento_mml?.silhouette_score || '0.4462' }}</strong> |
                        Muestras de calibración: <strong class="text-slate-800">{{ modelo.numero_muestras_mml }} sintéticas</strong> |
                        Pipeline Features: <strong class="text-slate-800">{{ modelo.version_features_mml }}</strong>
                    </p>
                </div>
            </div>

            <!-- Listado de Clusters y Validaciones Profesionales -->
            <div class="grid gap-5 md:grid-cols-2">
                <div
                    v-for="cluster in modelo.clusters"
                    :key="cluster.cod_clusters_modelo"
                    class="rounded-2xl border border-slate-200 bg-slate-50/30 p-5 flex flex-col justify-between space-y-4 hover:border-slate-300 transition"
                >
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2 border-b border-slate-200/70 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-white font-black text-xs">
                                    C{{ cluster.numero_cluster_clm }}
                                </span>
                                <h4 class="text-sm font-bold text-slate-900">
                                    Cluster {{ cluster.numero_cluster_clm }}
                                </h4>
                            </div>
                            <ClinicalBadge
                                :label="cluster.estado_validacion_clm"
                                :tone="estadoClusterTone(cluster.estado_validacion_clm)"
                                class="text-[10px] font-bold uppercase border"
                            />
                        </div>

                        <!-- Patrón de Negocio Asociado -->
                        <div class="rounded-xl bg-white border border-slate-200/80 p-3 space-y-1">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Patrón Clínico Asociado</span>
                            <p class="text-xs font-black text-slate-800">
                                {{ cluster.patron_alimenticio?.nom_pat || 'Sin patrón alimenticio asignado todavía' }}
                            </p>
                        </div>

                        <!-- Descripción Automática (Estadística) -->
                        <div class="space-y-1">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Descripción Estadística</span>
                            <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                {{ cluster.descripcion_automatica_clm }}
                            </p>
                        </div>

                        <!-- Interpretación Profesional del Nutricionista -->
                        <div class="space-y-1 rounded-xl bg-emerald-50/40 border border-emerald-100 p-3">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-emerald-800">Interpretación Profesional</span>
                            <p class="text-xs text-slate-800 font-medium leading-relaxed italic">
                                "{{ cluster.descripcion_profesional_clm || 'Pendiente de redacción por el nutricionista.' }}"
                            </p>
                            <span v-if="cluster.fec_validacion_clm" class="block text-[9px] text-slate-400 font-bold mt-1">
                                Validado: {{ cluster.fec_validacion_clm }}
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="abrirModalValidarCluster(cluster)"
                        class="motion-btn w-full rounded-xl bg-slate-900 py-2.5 text-center text-xs font-bold text-white hover:bg-slate-800 transition"
                    >
                        Editar Interpretación / Asignar Patrón
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: VALIDACIÓN PROFESIONAL DE CLUSTER -->
        <div
            v-if="mostrarModalValidacionCluster"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl space-y-5 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Validación Profesional</span>
                        <h3 class="text-base font-black text-slate-900">
                            Interpretar Cluster {{ clusterSeleccionadoParaValidar?.numero_cluster_clm }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="mostrarModalValidacionCluster = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="guardarValidacionCluster" class="space-y-4">
                    <!-- Patrón Alimenticio -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700">Patrón Alimenticio de Negocio</label>
                            <button
                                type="button"
                                @click="creandoNuevoPatron = !creandoNuevoPatron"
                                class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700"
                            >
                                {{ creandoNuevoPatron ? 'Seleccionar existente' : '+ Crear nuevo patrón' }}
                            </button>
                        </div>

                        <div v-if="!creandoNuevoPatron">
                            <select
                                v-model="formValidacionCluster.cod_patrones_alimenticios"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">-- Sin patrón alimenticio asociado --</option>
                                <option
                                    v-for="pat in patronesDisponibles"
                                    :key="pat.cod_patrones_alimenticios"
                                    :value="pat.cod_patrones_alimenticios"
                                >
                                    {{ pat.nom_pat }}
                                </option>
                            </select>
                        </div>
                        <div v-else class="space-y-2 rounded-xl bg-slate-50 p-3 border border-slate-200">
                            <input
                                v-model="formValidacionCluster.nuevo_nombre_patron"
                                type="text"
                                placeholder="Nombre del nuevo patrón (ej: Dieta Alta en Proteína y Regular)"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-800"
                            />
                            <textarea
                                v-model="formValidacionCluster.nueva_descripcion_patron"
                                placeholder="Descripción del patrón de alimentación..."
                                rows="2"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-800"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Interpretación Médica/Nutricional Profesional -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700">
                            Interpretación Profesional del Nutricionista
                        </label>
                        <p class="text-[10px] text-slate-500">
                            Redacte la caracterización alimentaria del grupo evitando diagnósticos o términos absolutistas.
                        </p>
                        <textarea
                            v-model="formValidacionCluster.descripcion_profesional_clm"
                            rows="3"
                            required
                            placeholder="Describa el comportamiento alimenticio observado en este grupo de ingesta..."
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <!-- Estado de Validación -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700">Estado de Validación del Cluster</label>
                        <select
                            v-model="formValidacionCluster.estado_validacion_clm"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-800"
                        >
                            <option value="validado">Validado (Confirmado profesionalmente)</option>
                            <option value="ajustado">Ajustado (Modificado con observaciones)</option>
                            <option value="pendiente">Pendiente de revisión</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="mostrarModalValidacionCluster = false"
                            class="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formValidacionCluster.processing"
                            class="motion-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition"
                        >
                            Guardar Validación del Cluster
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: VALIDACIÓN DE PREDICCIÓN INDIVIDUAL -->
        <div
            v-if="mostrarModalValidarPrediccion"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl space-y-5 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Revisión Clínica Individual</span>
                        <h3 class="text-base font-black text-slate-900">
                            Validar Inferencia para {{ paciente.usuario?.nombre_completo || 'Paciente' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="mostrarModalValidarPrediccion = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="guardarValidacionPrediccion" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700">Dictamen de Validación</label>
                        <select
                            v-model="formValidacionPrediccion.estado_validacion_pml"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-800"
                        >
                            <option value="validado_correcto">Validado Correcto (Coincide con conducta real)</option>
                            <option value="corregido">Corregido (Se ajusta cluster manualmente)</option>
                            <option value="descartado">Descartado (Datos no representativos)</option>
                        </select>
                    </div>

                    <div v-if="formValidacionPrediccion.estado_validacion_pml === 'corregido'" class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700">Cluster Asignado Corregido</label>
                        <input
                            v-model="formValidacionPrediccion.resultado_validado_pml"
                            type="text"
                            placeholder="ej: Cluster 1"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-800"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700">Observaciones Clínicas del Nutricionista</label>
                        <textarea
                            v-model="formValidacionPrediccion.obs_validacion_pml"
                            rows="3"
                            placeholder="Añada notas sobre la representatividad del snapshot o consideraciones..."
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium text-slate-800"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="mostrarModalValidarPrediccion = false"
                            class="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formValidacionPrediccion.processing"
                            class="motion-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition"
                        >
                            Guardar Dictamen Clínico
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

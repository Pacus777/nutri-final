<script setup>
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import { computed } from 'vue';

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
});

const explicabilidad = computed(() => props.prediccion?.explicabilidad_pml || {});
const caminoDecision = computed(() => explicabilidad.value?.camino_decision || []);
const variablesInvolucradas = computed(() => explicabilidad.value?.variables_involucradas || {});
const probabilidades = computed(() => props.prediccion?.probabilidades_clases_pml || explicabilidad.value?.probabilidades || {});

const clasePredicha = computed(() => {
    const raw = props.prediccion?.resultado_principal_pml || explicabilidad.value?.clase_predicha || '';
    if (raw.toLowerCase().includes('alto')) return 'alto';
    if (raw.toLowerCase().includes('moderado')) return 'moderado';
    return 'bajo';
});

const claseConfig = computed(() => {
    switch (clasePredicha.value) {
        case 'alto':
            return {
                label: 'Riesgo Alto',
                badgeTone: 'danger',
                bgGradient: 'from-rose-500/10 via-rose-50 to-white',
                border: 'border-rose-300',
                textTone: 'text-rose-700',
                badgeBg: 'bg-rose-600',
                iconBg: 'bg-rose-100 text-rose-600',
                barColor: 'bg-rose-500',
            };
        case 'moderado':
            return {
                label: 'Riesgo Moderado',
                badgeTone: 'warning',
                bgGradient: 'from-amber-500/10 via-amber-50 to-white',
                border: 'border-amber-300',
                textTone: 'text-amber-700',
                badgeBg: 'bg-amber-500',
                iconBg: 'bg-amber-100 text-amber-600',
                barColor: 'bg-amber-500',
            };
        default:
            return {
                label: 'Riesgo Bajo',
                badgeTone: 'success',
                bgGradient: 'from-emerald-500/10 via-emerald-50 to-white',
                border: 'border-emerald-300',
                textTone: 'text-emerald-700',
                badgeBg: 'bg-emerald-600',
                iconBg: 'bg-emerald-100 text-emerald-600',
                barColor: 'bg-emerald-500',
            };
    }
});

const probBajo = computed(() => Math.round(((probabilidades.value?.bajo ?? 0) * 100)));
const probModerado = computed(() => Math.round(((probabilidades.value?.moderado ?? 0) * 100)));
const probAlto = computed(() => Math.round(((probabilidades.value?.alto ?? 0) * 100)));

const scoreConfianzaPct = computed(() => {
    if (props.prediccion?.score_confianza_pml !== undefined && props.prediccion?.score_confianza_pml !== null) {
        return Math.round(Number(props.prediccion.score_confianza_pml) * 100);
    }
    return 0;
});
</script>

<template>
    <div class="space-y-6">
        <!-- BANNER DE AVISO PERMANENTE OBLIGATORIO -->
        <div class="rounded-3xl border-2 border-indigo-200 bg-gradient-to-r from-indigo-50/80 via-sky-50/50 to-white p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-indigo-900">
                            Modelo Experimental · Árbol de Decisión
                        </span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                            Estado: {{ modelo?.est_mml?.toUpperCase() || 'VALIDACION' }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                            Dataset: {{ modelo?.origen_dataset_mml || 'sintetico_controlado' }}
                        </span>
                    </div>
                    <p class="text-xs font-semibold text-slate-700 leading-relaxed pt-1">
                        “Modelo experimental entrenado con datos sintéticos controlados. La estimación es orientativa y no constituye un diagnóstico ni sustituye la valoración profesional.”
                    </p>
                </div>
            </div>
        </div>

        <!-- ESTADO CUANDO NO HAY PREDICCIÓN NI SNAPSHOT -->
        <div v-if="!prediccion" class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 mb-3">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <h4 class="text-base font-bold text-slate-800">Sin Snapshot de Features v1.0</h4>
            <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto">
                No se encontraron registros de ingesta suficientes en ventanas de 7 días para procesar la estimación de riesgo con el Decision Tree.
            </p>
        </div>

        <!-- CONTENIDO PRINCIPAL DE ESTIMACIÓN EXPERIMENTAL -->
        <div v-else class="space-y-6">
            <!-- TARJETA PRINCIPAL DE ESTIMACIÓN Y PROBABILIDADES -->
            <div class="grid gap-6 lg:grid-cols-[5fr_7fr] items-start">
                <!-- TARJETA IZQUIERDA: ESTIMACIÓN Y MÉTRICAS -->
                <div class="rounded-3xl border bg-white p-6 shadow-sm space-y-5" :class="claseConfig.border">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-wider text-slate-400">
                                Estimación de Riesgo Nutricional
                            </span>
                            <h3 class="text-2xl font-black mt-0.5" :class="claseConfig.textTone">
                                {{ claseConfig.label }}
                            </h3>
                        </div>
                        <div class="text-right">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Probabilidad Estimada
                            </span>
                            <span class="text-2xl font-black text-slate-800">
                                {{ scoreConfianzaPct }}%
                            </span>
                        </div>
                    </div>

                    <!-- DESGLOSE DE PROBABILIDADES ENTRE CLASES -->
                    <div class="space-y-3">
                        <span class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                            Distribución de Probabilidades del Modelo
                        </span>

                        <!-- Barra visual acumulativa -->
                        <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden flex shadow-inner">
                            <div class="bg-emerald-500 transition-all duration-500" :style="{ width: `${probBajo}%` }" title="Bajo"></div>
                            <div class="bg-amber-500 transition-all duration-500" :style="{ width: `${probModerado}%` }" title="Moderado"></div>
                            <div class="bg-rose-500 transition-all duration-500" :style="{ width: `${probAlto}%` }" title="Alto"></div>
                        </div>

                        <!-- Leyendas -->
                        <div class="grid grid-cols-3 gap-2 pt-1 text-center">
                            <div class="rounded-xl bg-emerald-50/80 p-2 border border-emerald-100">
                                <span class="block text-[10px] font-bold uppercase text-emerald-700">Riesgo Bajo</span>
                                <span class="text-sm font-black text-emerald-800">{{ probBajo }}%</span>
                            </div>
                            <div class="rounded-xl bg-amber-50/80 p-2 border border-amber-100">
                                <span class="block text-[10px] font-bold uppercase text-amber-700">Riesgo Moderado</span>
                                <span class="text-sm font-black text-amber-800">{{ probModerado }}%</span>
                            </div>
                            <div class="rounded-xl bg-rose-50/80 p-2 border border-rose-100">
                                <span class="block text-[10px] font-bold uppercase text-rose-700">Riesgo Alto</span>
                                <span class="text-sm font-black text-rose-800">{{ probAlto }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- METADATOS DE LA VENTANA CLÍNICA -->
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span class="font-semibold">Ventana Analizada:</span>
                            <span class="font-bold text-slate-800">
                                {{ prediccion?.snapshot?.fec_inicio_fnu }} al {{ prediccion?.snapshot?.fec_fin_fnu }}
                            </span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span class="font-semibold">Adherencia de Registro:</span>
                            <span class="font-bold text-slate-800">
                                {{ Math.round((prediccion?.snapshot?.adherencia_registro_fnu || 0) * 100) }}% ({{ prediccion?.snapshot?.dias_registrados_fnu || 0 }}/7 días)
                            </span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span class="font-semibold">Calidad de Ventana:</span>
                            <span class="font-bold" :class="prediccion?.snapshot?.adherencia_registro_fnu >= 0.7143 ? 'text-emerald-600' : 'text-amber-600'">
                                {{ prediccion?.snapshot?.calidad_ventana || 'Suficiente' }}
                            </span>
                        </div>
                        <div class="flex justify-between text-slate-600 pt-1 border-t border-slate-200/60">
                            <span class="font-semibold">Versión de Modelo:</span>
                            <span class="font-mono font-bold text-slate-700">{{ explicabilidad?.version_modelo || modelo?.version_mml }}</span>
                        </div>
                    </div>
                </div>

                <!-- TARJETA DERECHA: EXPLICABILIDAD Y CAMINO DE DECISIÓN DEL ÁRBOL -->
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Explicabilidad: Camino de Decisión Concreto</h4>
                            <p class="text-[11px] text-slate-500">Ruta de reglas evaluadas en el árbol para los datos de esta paciente</p>
                        </div>
                    </div>

                    <!-- PASOS EVALUADOS EN EL ÁRBOL -->
                    <div v-if="caminoDecision.length > 0" class="space-y-3">
                        <div
                            v-for="(paso, idx) in caminoDecision"
                            :key="idx"
                            class="rounded-2xl border p-3.5 transition bg-slate-50/60 border-slate-200/80 hover:bg-indigo-50/30"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white text-xs font-black shadow-sm">
                                    {{ idx + 1 }}
                                </div>
                                <div class="space-y-1 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-1">
                                        <span class="text-xs font-bold text-slate-800 font-mono">
                                            {{ paso.feature }}
                                        </span>
                                        <span class="rounded-md bg-white px-2 py-0.5 text-[10px] font-black border border-slate-200 text-indigo-700">
                                            Valor: {{ paso.valor_paciente }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 font-medium leading-relaxed">
                                        Condición cumplida: <span class="font-bold text-slate-800">{{ paso.condicion_cumplida }}</span> (Umbral: {{ paso.umbral_modelo }})
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- RESULTADO FINAL DEL CAMINO -->
                        <div class="rounded-2xl p-4 border flex items-center justify-between" :class="[claseConfig.border, 'bg-gradient-to-r ' + claseConfig.bgGradient]">
                            <div class="flex items-center gap-3">
                                <div class="h-3 w-3 rounded-full animate-ping" :class="claseConfig.barColor"></div>
                                <span class="text-xs font-black uppercase tracking-wider text-slate-700">
                                    Conclusión del Árbol:
                                </span>
                            </div>
                            <span class="text-sm font-black uppercase tracking-wider" :class="claseConfig.textTone">
                                {{ claseConfig.label }} ({{ scoreConfianzaPct }}%)
                            </span>
                        </div>
                    </div>

                    <div v-else class="text-center py-6 text-slate-400 text-xs">
                        No hay registros de reglas detalladas en el camino.
                    </div>

                    <!-- VARIABLES INVOLUCRADAS -->
                    <div class="pt-2 border-t border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                            Variables Involucradas en la Estimación
                        </span>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div
                                v-for="(val, key) in variablesInvolucradas"
                                :key="key"
                                class="rounded-xl bg-slate-50 p-2.5 border border-slate-100 text-xs"
                            >
                                <span class="block text-[10px] font-medium text-slate-500 font-mono truncate" :title="key">
                                    {{ key }}
                                </span>
                                <span class="text-xs font-black text-slate-800 mt-0.5 block">
                                    {{ val }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

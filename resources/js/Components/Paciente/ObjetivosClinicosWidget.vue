<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    objetivos: {
        type: Array,
        default: () => [],
    },
    pacienteId: {
        type: [Number, String],
        required: true,
    },
    titulo: {
        type: String,
        default: 'Objetivos Clínicos y Metas de Hábito',
    },
    subtitulo: {
        type: String,
        default: 'Monitorea tu adherencia. Cada vez que cumplas una acción de tu meta, pulsa el botón + para registrar tu avance.',
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

// Helper defensivo para garantizar siempre números válidos y nunca NaN
const numVal = (v, fallback = 0) => {
    if (v === null || v === undefined || v === '') return fallback;
    const n = Number(v);
    return isNaN(n) ? fallback : n;
};

// Clon reactivo local para actualizaciones optimistas instantáneas
const localObjetivos = ref([]);

const sincronizarObjetivos = () => {
    localObjetivos.value = (props.objetivos || []).map(obj => {
        const actual = numVal(obj.progreso_actual, 0);
        const meta = Math.max(1, numVal(obj.meta_progreso, 7));
        const pctCalculado = Math.min(100, Math.max(0, Math.round((actual / meta) * 100)));
        const pctFinal = obj.porcentaje !== undefined && !isNaN(Number(obj.porcentaje))
            ? numVal(obj.porcentaje, pctCalculado)
            : pctCalculado;

        return {
            ...obj,
            progreso_actual: actual,
            meta_progreso: meta,
            unidad_progreso: obj.unidad_progreso || 'veces',
            porcentaje: pctFinal,
        };
    });
};

watch(() => props.objetivos, sincronizarObjetivos, { immediate: true, deep: true });

// Estados de guardado por objetivo
const guardando = ref({});
const feedbackItem = ref({});

const totalObjetivos = computed(() => localObjetivos.value.length);
const objetivosCumplidos = computed(() => {
    return localObjetivos.value.filter(o => {
        const actual = numVal(o.progreso_actual, 0);
        const meta = numVal(o.meta_progreso, 7);
        return actual >= meta && meta > 0;
    }).length;
});

const getProgresoBarColor = (porcentaje) => {
    const pct = numVal(porcentaje, 0);
    if (pct >= 100) return 'from-emerald-500 to-teal-500';
    if (pct >= 60) return 'from-teal-500 to-emerald-400';
    if (pct >= 30) return 'from-amber-400 to-teal-400';
    return 'from-amber-400 to-amber-500';
};

const ajustarProgreso = async (obj, delta) => {
    if (guardando.value[obj.cod_objetivos_nutricionales]) return;

    const id = obj.cod_objetivos_nutricionales;
    const actual = numVal(obj.progreso_actual, 0);
    const meta = Math.max(1, numVal(obj.meta_progreso, 7));
    const nuevoProgreso = Math.max(0, actual + delta);
    if (nuevoProgreso === actual && delta < 0) return;

    // Respaldo para rollback en caso de error
    const progresoPrevio = actual;
    const porcentajePrevio = numVal(obj.porcentaje, 0);

    // Actualización optimista inmediata
    obj.progreso_actual = nuevoProgreso;
    obj.porcentaje = Math.min(100, Math.max(0, Math.round((nuevoProgreso / meta) * 100)));

    // Feedback visual
    feedbackItem.value[id] = delta > 0 ? '+1' : '-1';
    setTimeout(() => {
        if (feedbackItem.value[id]) {
            delete feedbackItem.value[id];
        }
    }, 1200);

    guardando.value[id] = true;

    try {
        let url;
        try {
            url = route('paciente.objetivos.progreso', [props.pacienteId, id]);
        } catch {
            url = `/pacientes/${props.pacienteId}/objetivos/${id}/progreso`;
        }

        const response = await axios.post(url, { delta });
        if (response.data && response.data.success) {
            // Extraer de forma resiliente tanto de la raíz como de response.data.objetivo
            const resObj = response.data.objetivo || response.data;
            const resProgreso = resObj.progreso_actual !== undefined
                ? numVal(resObj.progreso_actual, obj.progreso_actual)
                : (response.data.progreso_actual !== undefined ? numVal(response.data.progreso_actual, obj.progreso_actual) : obj.progreso_actual);

            const resMeta = resObj.meta_progreso !== undefined
                ? Math.max(1, numVal(resObj.meta_progreso, obj.meta_progreso))
                : (response.data.meta_progreso !== undefined ? Math.max(1, numVal(response.data.meta_progreso, obj.meta_progreso)) : obj.meta_progreso);

            const resPct = resObj.porcentaje !== undefined
                ? numVal(resObj.porcentaje, Math.round((resProgreso / resMeta) * 100))
                : (response.data.porcentaje !== undefined ? numVal(response.data.porcentaje, Math.round((resProgreso / resMeta) * 100)) : Math.round((resProgreso / resMeta) * 100));

            obj.progreso_actual = resProgreso;
            obj.meta_progreso = resMeta;
            obj.porcentaje = Math.min(100, Math.max(0, resPct));

            if (resObj.unidad_progreso) {
                obj.unidad_progreso = resObj.unidad_progreso;
            } else if (response.data.unidad_progreso) {
                obj.unidad_progreso = response.data.unidad_progreso;
            }
        }
    } catch (error) {
        // Rollback
        obj.progreso_actual = progresoPrevio;
        obj.porcentaje = porcentajePrevio;
        console.error('Error al actualizar el progreso del objetivo:', error);
    } finally {
        guardando.value[id] = false;
    }
};
</script>

<template>
    <div 
        class="rounded-[1.75rem] border border-slate-200 bg-white p-5 sm:p-6 shadow-sm relative overflow-hidden transition-all duration-300"
        :class="{ 'py-4 px-5': compact }"
    >
        <!-- Acento decorativo sutil en esquina -->
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-gradient-to-br from-emerald-400/10 to-teal-400/10 blur-2xl pointer-events-none"></div>

        <!-- Encabezado del widget -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4 relative z-10">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700">
                        <svg class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Seguimiento Clínico
                    </span>

                    <span 
                        v-if="totalObjetivos > 0"
                        class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full"
                    >
                        {{ objetivosCumplidos }} de {{ totalObjetivos }} completados
                    </span>
                </div>

                <h3 
                    class="font-black text-slate-900 tracking-tight"
                    :class="compact ? 'text-base sm:text-lg' : 'text-lg sm:text-xl'"
                >
                    {{ titulo }}
                </h3>
                <p v-if="subtitulo" class="text-xs text-slate-500 font-medium leading-relaxed max-w-2xl">
                    {{ subtitulo }}
                </p>
            </div>

            <!-- Resumen de tasa de cumplimiento global -->
            <div 
                v-if="totalObjetivos > 0"
                class="flex items-center gap-2 bg-slate-50 border border-slate-200/80 px-3 py-1.5 rounded-2xl self-start sm:self-auto shrink-0"
            >
                <div class="text-right">
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-400">Tasa de Logro</span>
                    <span class="block text-xs sm:text-sm font-black text-slate-800">
                        {{ Math.round((objetivosCumplidos / totalObjetivos) * 100) }}%
                    </span>
                </div>
                <div class="h-8 w-8 rounded-full bg-emerald-100/60 flex items-center justify-center text-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Lista de Objetivos -->
        <div v-if="localObjetivos && localObjetivos.length > 0" class="mt-5 space-y-4 relative z-10">
            <div 
                v-for="obj in localObjetivos" 
                :key="obj.cod_objetivos_nutricionales"
                class="group rounded-2xl border transition-all duration-200 p-4 sm:p-5"
                :class="obj.progreso_actual >= obj.meta_progreso 
                    ? 'border-emerald-200/90 bg-emerald-50/30 hover:border-emerald-300' 
                    : 'border-slate-200/80 bg-slate-50/40 hover:border-slate-300 hover:bg-slate-50/80'"
            >
                <!-- Fila superior: Estado, Título y Badges -->
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2.5">
                    <div class="space-y-1 max-w-xl">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span 
                                class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md"
                                :class="obj.est_obe === 'activo' 
                                    ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' 
                                    : 'bg-amber-100 text-amber-800 border border-amber-200'"
                            >
                                {{ obj.est_obe }}
                            </span>

                            <span 
                                v-if="obj.progreso_actual >= obj.meta_progreso"
                                class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-emerald-600 text-white shadow-xs"
                            >
                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Meta Cumplida
                            </span>

                            <span v-if="obj.fec_obe" class="text-[10px] text-slate-400 font-medium">
                                Asignado: {{ obj.fec_obe }}
                            </span>
                        </div>

                        <!-- Descripción clínica del objetivo -->
                        <h4 class="text-sm sm:text-base font-bold text-slate-900 leading-snug">
                            {{ obj.des_obe }}
                        </h4>
                    </div>

                    <!-- Controles de Stepper (+ / –) -->
                    <div class="flex items-center gap-1.5 self-start sm:self-center shrink-0 mt-2 sm:mt-0 bg-white border border-slate-200 rounded-2xl p-1.5 shadow-xs">
                        <!-- Botón Decrementar (-) -->
                        <button
                            type="button"
                            @click="ajustarProgreso(obj, -1)"
                            :disabled="obj.progreso_actual <= 0 || guardando[obj.cod_objetivos_nutricionales]"
                            class="h-8 w-8 rounded-xl flex items-center justify-center text-slate-600 hover:bg-slate-100 hover:text-slate-900 disabled:opacity-30 disabled:pointer-events-none transition-all active:scale-95"
                            title="Deshacer un registro"
                            aria-label="Restar progreso"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                            </svg>
                        </button>

                        <!-- Indicador numérico de progreso -->
                        <div class="px-2 min-w-[75px] text-center select-none">
                            <div class="flex items-baseline justify-center gap-1">
                                <span class="text-sm font-black text-slate-900">
                                    {{ numVal(obj.progreso_actual, 0) }}
                                </span>
                                <span class="text-xs font-bold text-slate-400">
                                    / {{ numVal(obj.meta_progreso, 7) }}
                                </span>
                            </div>
                            <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-tight truncate">
                                {{ obj.unidad_progreso || 'veces' }}
                            </span>
                        </div>

                        <!-- Botón Incrementar (+) -->
                        <button
                            type="button"
                            @click="ajustarProgreso(obj, 1)"
                            :disabled="guardando[obj.cod_objetivos_nutricionales]"
                            class="relative h-8 w-8 rounded-xl flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-black shadow-sm transition-all active:scale-95 group/btn cursor-pointer"
                            title="Sumar cumplimiento a este objetivo"
                            aria-label="Incrementar progreso"
                        >
                            <!-- Spinner si está guardando -->
                            <svg 
                                v-if="guardando[obj.cod_objetivos_nutricionales]" 
                                class="animate-spin h-3.5 w-3.5 text-white" 
                                fill="none" 
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <!-- Icono + -->
                            <svg 
                                v-else 
                                class="h-4 w-4" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor" 
                                stroke-width="2.5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>

                            <!-- Badge flotante de micro-feedback (+1) -->
                            <span 
                                v-if="feedbackItem[obj.cod_objetivos_nutricionales]"
                                class="absolute -top-3 -right-2 bg-emerald-700 text-white text-[9px] font-black rounded-full px-1.5 py-0.2 shadow-md animate-bounce"
                            >
                                {{ feedbackItem[obj.cod_objetivos_nutricionales] }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Barra de Progreso y Métricas -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[11px] font-bold text-slate-500">
                            Avance acumulado
                        </span>
                        <div class="flex items-center gap-1.5 font-bold">
                            <span 
                                class="text-xs"
                                :class="numVal(obj.progreso_actual, 0) >= numVal(obj.meta_progreso, 7) ? 'text-emerald-700 font-black' : 'text-slate-700'"
                            >
                                {{ numVal(obj.porcentaje, 0) }}%
                            </span>
                            <span class="text-[10px] text-slate-400">
                                ({{ numVal(obj.progreso_actual, 0) }} de {{ numVal(obj.meta_progreso, 7) }} {{ obj.unidad_progreso || 'veces' }})
                            </span>
                        </div>
                    </div>

                    <!-- Contenedor Barra de Progreso -->
                    <div class="h-2.5 sm:h-3 w-full rounded-full bg-slate-200/80 overflow-hidden p-0.5 shadow-inner">
                        <div 
                            class="h-full rounded-full bg-gradient-to-r transition-all duration-500 ease-out"
                            :class="getProgresoBarColor(obj.porcentaje)"
                            :style="{ width: `${Math.min(numVal(obj.porcentaje, 0), 100)}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado vacío sin objetivos asignados -->
        <div 
            v-else 
            class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-8 px-4 text-center relative z-10"
        >
            <div class="mx-auto h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <p class="text-sm font-bold text-slate-700">Sin objetivos clínicos asignados actualmente</p>
            <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                Tu profesional de nutrición establecerá metas clínicas y de hábitos personalizadas en tu próxima consulta.
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Transición suave para barra de progreso */
</style>

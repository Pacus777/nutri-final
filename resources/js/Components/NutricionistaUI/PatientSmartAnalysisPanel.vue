<script setup>
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    paciente: {
        type: Object,
        default: null,
    },
    analisis: {
        type: Object,
        default: null,
    },
    defaultOpen: {
        type: Boolean,
        default: false,
    },
});

const datos = computed(() => {
    return props.analisis || {
        modo: 'prototipo',
        periodo: 'Últimos 28 días',
        registros_analizados: 18,
        alimentos_procesados: 74,
        variables_generadas: 12,
        calidad_datos: 'Suficiente para análisis preliminar',
        riesgo: {
            nivel: 'Moderado',
            probabilidad: 68,
            interpretacion: 'Posible tendencia a desequilibrio por alto consumo de carbohidratos e irregularidad en horarios.',
        },
        patron: {
            cluster: 'Perfil B',
            nombre: 'Consumo alto en carbohidratos con horarios irregulares',
            similitud: 82,
            descripcion: 'El paciente se agrupa con perfiles que concentran calorías en pocas comidas y presentan baja regularidad en el desayuno.',
        },
        variables: [
            {
                nombre: 'Promedio calórico diario',
                valor: '2340 kcal',
                lectura: 'Sobre rango estimado',
                estado: 'warning',
            },
            {
                nombre: 'Distribución de carbohidratos',
                valor: '58%',
                lectura: 'Tendencia alta',
                estado: 'warning',
            },
            {
                nombre: 'Frecuencia de desayuno',
                valor: '3/7 días',
                lectura: 'Baja regularidad',
                estado: 'danger',
            },
            {
                nombre: 'Proteína promedio',
                valor: '72 g',
                lectura: 'Aceptable',
                estado: 'success',
            },
            {
                nombre: 'Variabilidad horaria',
                valor: 'Alta',
                lectura: 'Rutina irregular',
                estado: 'warning',
            },
        ],
        arbol_decision: [
            {
                condicion: 'Carbohidratos promedio > 55%',
                resultado: 'Sí',
                estado: 'warning',
            },
            {
                condicion: 'Desayuno registrado menos de 4 días/semana',
                resultado: 'Sí',
                estado: 'warning',
            },
            {
                condicion: 'Variabilidad horaria alta',
                resultado: 'Sí',
                estado: 'warning',
            },
            {
                condicion: 'Proteína promedio bajo mínimo estimado',
                resultado: 'No',
                estado: 'success',
            },
        ],
        recomendaciones: [
            'Regularizar horarios de desayuno durante la semana.',
            'Reducir concentración calórica en cena.',
            'Aumentar fibra en desayuno y almuerzo.',
            'Mantener seguimiento durante 14 días para confirmar tendencia.',
        ],
    };
});

const detalleAbierto = ref(props.defaultOpen);

const alternarDetalle = () => {
    detalleAbierto.value = !detalleAbierto.value;
};

const esModoPrototipo = computed(() => datos.value.modo === 'prototipo');

const esModoRealPreliminar = computed(() => datos.value.modo === 'real_preliminar');

const etiquetaModoAnalisis = computed(() => {
    if (esModoRealPreliminar.value) {
        return 'Datos reales preliminares';
    }

    if (esModoPrototipo.value) {
        return 'Vista prototipo';
    }

    return 'Análisis inteligente';
});

const tonoModoAnalisis = computed(() => {
    if (esModoRealPreliminar.value) {
        return 'success';
    }

    if (esModoPrototipo.value) {
        return 'warning';
    }

    return 'info';
});

const sinDatosValidados = computed(() => {
    return Number(datos.value.registros_analizados || 0) === 0
        || String(datos.value.riesgo?.nivel || '').toLowerCase().includes('no estimado');
});

const tonoRiesgo = computed(() => {
    const nivel = String(datos.value.riesgo?.nivel || '').toLowerCase();

    if (nivel.includes('alto')) return 'danger';
    if (nivel.includes('moderado')) return 'warning';
    if (nivel.includes('bajo')) return 'success';

    return 'info';
});

const nombrePaciente = computed(() => {
    return props.paciente?.usuario?.nombre_completo
        || props.paciente?.usuario?.nom_usu
        || 'Paciente seleccionado';
});

const variablesEnAlerta = computed(() => {
    return datos.value.variables.filter((variable) => {
        return ['warning', 'danger'].includes(variable.estado);
    });
});

const resumenAnalitico = computed(() => {
    return {
        periodo: datos.value.periodo,
        registros: datos.value.registros_analizados,
        variables: datos.value.variables_generadas,
        patron: datos.value.patron?.nombre || 'Sin patrón detectado',
        cluster: datos.value.patron?.cluster || 'Sin cluster',
        similitud: datos.value.patron?.similitud || 0,
        riesgo: datos.value.riesgo?.nivel || 'No estimado',
        probabilidad: datos.value.riesgo?.probabilidad || 0,
    };
});

const datasetAnalitico = computed(() => {
    return datos.value.dataset || {
        registros_totales_periodo: datos.value.registros_analizados || 0,
        registros_usados: datos.value.registros_analizados || 0,
        registros_excluidos: 0,
        aceptados: 0,
        observados: 0,
        rechazados: 0,
        pendientes: 0,
        criterio_uso: 'El análisis usa registros aceptados y observados.',
    };
});

const flujoAnalitico = [
    'Historial alimenticio',
    'Variables derivadas',
    'Clustering K-means',
    'Árbol de decisión',
    'Riesgo estimado',
];
</script>

<template>
    <ClinicalSection
        eyebrow="Análisis inteligente del paciente"
        title="Resumen analítico del comportamiento alimenticio"
        :description="`Análisis aplicado al historial alimenticio de ${nombrePaciente}. Sirve como apoyo para crear objetivos, registrar seguimientos y priorizar recomendaciones.`"
        tone="info"
    >
        <template #actions>
            <ClinicalBadge
                :label="etiquetaModoAnalisis"
                :tone="tonoModoAnalisis"
            />

            <ClinicalBadge
                label="Apoyo a la decisión"
                tone="info"
            />
        </template>

        <ClinicalAlert
            v-if="esModoPrototipo"
            title="Módulo preparado para conexión analítica"
            tone="warning"
        >
            Esta vista muestra cómo se integrará el análisis cuando el backend entregue variables derivadas,
            agrupamiento K-means y predicción por árbol de decisión. No reemplaza el criterio profesional del nutricionista.
        </ClinicalAlert>

        <ClinicalAlert
            v-else-if="sinDatosValidados"
            title="Sin datos validados suficientes"
            tone="warning"
        >
            El análisis inteligente todavía no puede estimar un riesgo confiable porque no existen registros aceptados u observados suficientes.
            Valida registros alimenticios para alimentar el dataset analítico del paciente.
        </ClinicalAlert>

        <ClinicalAlert
            v-else-if="esModoRealPreliminar"
            title="Análisis preliminar con datos reales"
            tone="success"
        >
            El sistema está usando registros alimenticios aceptados u observados para generar variables derivadas,
            detectar un patrón preliminar y estimar riesgo mediante reglas interpretables.
        </ClinicalAlert>

        <div class="mt-5 grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Periodo analizado -->
            <div class="rounded-3xl border border-cyan-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-cyan-300 flex items-start justify-between gap-3 group relative overflow-hidden">
                <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-cyan-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                <div class="relative z-10 space-y-1">
                    <span class="text-[9px] font-black uppercase tracking-widest text-cyan-600">Periodo analizado</span>
                    <p class="text-xl lg:text-2xl font-black text-slate-800">{{ resumenAnalitico.periodo }}</p>
                    <p class="text-[10px] text-slate-400 font-semibold">Historial alimenticio</p>
                </div>
                <div class="relative z-10 p-2 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100/50 shadow-sm transition-colors duration-350 group-hover:bg-cyan-550 group-hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5" />
                    </svg>
                </div>
            </div>

            <!-- Registros procesados -->
            <div class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-300 flex items-start justify-between gap-3 group relative overflow-hidden">
                <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-emerald-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                <div class="relative z-10 space-y-1">
                    <span class="text-[9px] font-black uppercase tracking-widest text-emerald-600">Registros procesados</span>
                    <p class="text-xl lg:text-2xl font-black text-slate-800">{{ resumenAnalitico.registros }}</p>
                    <p class="text-[10px] text-slate-400 font-semibold">Días registrados</p>
                </div>
                <div class="relative z-10 p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100/50 shadow-sm transition-colors duration-350 group-hover:bg-emerald-550 group-hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H9.75M9.75 8.25h4.5" />
                    </svg>
                </div>
            </div>

            <!-- Patrón detectado -->
            <div class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-300 flex items-start justify-between gap-3 group relative overflow-hidden">
                <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-amber-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                <div class="relative z-10 space-y-1">
                    <span class="text-[9px] font-black uppercase tracking-widest text-amber-600">Patrón detectado</span>
                    <p class="text-xl lg:text-2xl font-black text-slate-800 leading-tight">{{ resumenAnalitico.cluster }}</p>
                    <p class="mt-1 text-[10px] text-amber-605 font-bold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100/30 w-fit">{{ resumenAnalitico.similitud }}% similitud</p>
                </div>
                <div class="relative z-10 p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-100/50 shadow-sm transition-colors duration-350 group-hover:bg-amber-500 group-hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
            </div>

            <!-- Riesgo estimado -->
            <div class="rounded-3xl border p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md flex items-start justify-between gap-3 group relative overflow-hidden"
                 :class="tonoRiesgo === 'danger' ? 'border-rose-250 hover:border-rose-350 bg-white' 
                        : tonoRiesgo === 'warning' ? 'border-amber-250 hover:border-amber-350 bg-white' 
                        : 'border-emerald-250 hover:border-emerald-350 bg-white'">
                <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full transition-all duration-500 group-hover:scale-[3.5]"
                     :class="tonoRiesgo === 'danger' ? 'bg-rose-50/50' : tonoRiesgo === 'warning' ? 'bg-amber-50/50' : 'bg-emerald-50/50'"></div>
                <div class="relative z-10 space-y-1">
                    <span class="text-[9px] font-black uppercase tracking-widest"
                          :class="tonoRiesgo === 'danger' ? 'text-rose-600' : tonoRiesgo === 'warning' ? 'text-amber-600' : 'text-emerald-600'">Riesgo estimado</span>
                    <p class="text-xl lg:text-2xl font-black text-slate-800">{{ resumenAnalitico.riesgo }}</p>
                    <p class="text-[10px] font-semibold text-slate-450">{{ resumenAnalitico.probabilidad }}% estimado</p>
                </div>
                <div class="relative z-10 p-2 rounded-xl shadow-sm border transition-colors duration-305"
                     :class="tonoRiesgo === 'danger' ? 'bg-rose-50 text-rose-600 border-rose-100/50 group-hover:bg-rose-500 group-hover:text-white' 
                            : tonoRiesgo === 'warning' ? 'bg-amber-50 text-amber-600 border-amber-100/50 group-hover:bg-amber-500 group-hover:text-white' 
                            : 'bg-emerald-50 text-emerald-600 border-emerald-100/50 group-hover:bg-emerald-500 group-hover:text-white'">
                    <svg class="w-5 h-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="mt-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-cyan-600">Métrica del Dataset</span>
                    <h4 class="mt-1 text-base font-bold text-slate-800">
                        Registros para Ciencia de Datos
                    </h4>
                    <p class="mt-1.5 text-xs font-semibold leading-relaxed text-slate-500 max-w-2xl">
                        {{ datasetAnalitico.criterio_uso }}
                    </p>
                </div>

                <span class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-bold text-emerald-800 shadow-sm shrink-0 w-fit">
                    {{ datasetAnalitico.registros_usados }} usado{{ datasetAnalitico.registros_usados === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="mt-5 grid gap-3 grid-cols-2 md:grid-cols-5">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/25 p-4 transition duration-200 hover:bg-emerald-50/40">
                    <div class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Aceptados
                    </div>
                    <p class="mt-2 text-2xl font-black text-emerald-800">
                        {{ datasetAnalitico.aceptados }}
                    </p>
                </div>

                <div class="rounded-2xl border border-amber-100 bg-amber-50/25 p-4 transition duration-200 hover:bg-amber-50/40">
                    <div class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider text-amber-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Observados
                    </div>
                    <p class="mt-2 text-2xl font-black text-amber-800">
                        {{ datasetAnalitico.observados }}
                    </p>
                </div>

                <div class="rounded-2xl border border-rose-100 bg-rose-50/25 p-4 transition duration-200 hover:bg-rose-50/40">
                    <div class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider text-rose-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                        Rechazados
                    </div>
                    <p class="mt-2 text-2xl font-black text-rose-800">
                        {{ datasetAnalitico.rechazados }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/25 p-4 transition duration-200 hover:bg-slate-50/50">
                    <div class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        Pendientes
                    </div>
                    <p class="mt-2 text-2xl font-black text-slate-800">
                        {{ datasetAnalitico.pendientes }}
                    </p>
                </div>

                <div class="rounded-2xl border border-cyan-100 bg-cyan-50/25 p-4 transition duration-200 hover:bg-cyan-50/40">
                    <div class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider text-cyan-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-cyan-400"></span>
                        Excluidos
                    </div>
                    <p class="mt-2 text-2xl font-black text-cyan-800">
                        {{ datasetAnalitico.registros_excluidos }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-cyan-600">Proceso computacional</span>
                    <h4 class="mt-1 text-base font-bold text-slate-800">
                        Flujo Analítico Aplicado
                    </h4>
                    <p class="mt-1.5 text-xs font-semibold leading-relaxed text-slate-500 max-w-2xl">
                        El sistema convierte el historial en variables, detecta un patrón mediante clustering y estima el riesgo con un árbol de decisión interpretable.
                    </p>
                </div>

                <button
                    type="button"
                    class="motion-btn rounded-xl bg-cyan-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-cyan-700 hover:shadow-cyan-600/10 transition shrink-0"
                    @click="alternarDetalle"
                >
                    {{ detalleAbierto ? 'Ocultar Detalle Técnico' : 'Ver Detalle Técnico' }}
                </button>
            </div>

            <!-- Pipeline step connection flow -->
            <div class="mt-6 flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-2">
                <template v-for="(paso, index) in flujoAnalitico" :key="paso">
                    <div class="flex-1 rounded-2xl border border-cyan-100 bg-cyan-50/20 p-4 text-center shadow-sm transition hover:bg-cyan-50/40 duration-200">
                        <div class="mx-auto flex h-7 w-7 items-center justify-center rounded-full bg-cyan-600 text-[10px] font-black text-white shadow-sm">
                            {{ index + 1 }}
                        </div>
                        <p class="mt-2 text-xs font-bold text-cyan-900">
                            {{ paso }}
                        </p>
                    </div>

                    <!-- Flow arrow between cards (hidden on last step) -->
                    <div v-if="index < flujoAnalitico.length - 1" class="hidden md:flex items-center justify-center text-cyan-300 font-bold shrink-0 px-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </div>
                </template>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- Clustering card -->
            <div class="rounded-3xl border border-amber-200/80 bg-amber-50/15 p-6 shadow-sm flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 border-b border-amber-100/40 pb-2">
                        <div class="p-1 rounded-lg bg-amber-50 text-amber-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-amber-700">Resultado de clustering</span>
                    </div>

                    <h4 class="text-base font-bold text-slate-800">
                        {{ datos.patron.nombre }}
                    </h4>

                    <p class="text-xs font-semibold leading-relaxed text-slate-600">
                        {{ datos.patron.descripcion }}
                    </p>
                </div>

                <div class="flex items-center justify-between border-t border-amber-100/30 pt-3">
                    <div class="flex gap-2">
                        <span class="rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-[10px] font-bold text-amber-700 shadow-sm">
                            {{ datos.patron.cluster }}
                        </span>
                        <span class="rounded-full bg-cyan-50 border border-cyan-200 px-2.5 py-0.5 text-[10px] font-bold text-cyan-700 shadow-sm">
                            {{ datos.patron.similitud }}% Similitud
                        </span>
                    </div>
                </div>
            </div>

            <!-- Lectura predictiva card -->
            <div class="rounded-3xl border border-rose-200/80 bg-rose-50/15 p-6 shadow-sm flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 border-b border-rose-100/40 pb-2">
                        <div class="p-1 rounded-lg bg-rose-50 text-rose-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-rose-700">Lectura predictiva</span>
                    </div>

                    <h4 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        Riesgo Nutricional: 
                        <span class="px-2 py-0.5 rounded text-xs font-bold ring-1 border"
                              :class="tonoRiesgo === 'danger' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-amber-50 border-amber-200 text-amber-700'">
                            {{ datos.riesgo.nivel }}
                        </span>
                    </h4>

                    <p class="text-xs font-semibold leading-relaxed text-slate-600">
                        {{ datos.riesgo.interpretacion }}
                    </p>
                </div>

                <!-- Risk visual progress bar -->
                <div class="space-y-2 border-t border-rose-100/30 pt-3">
                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-500">
                        <span>Probabilidad de Alerta:</span>
                        <span :class="tonoRiesgo === 'danger' ? 'text-rose-700' : 'text-amber-700'">{{ datos.riesgo.probabilidad }}%</span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden shadow-inner border border-slate-200/50">
                        <div class="h-full rounded-full transition-all duration-700 ease-out shadow animate-pulse"
                             :style="{ width: datos.riesgo.probabilidad + '%' }"
                             :class="tonoRiesgo === 'danger' ? 'bg-gradient-to-r from-rose-500 to-red-600' : 'bg-gradient-to-r from-amber-400 to-amber-500'"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="detalleAbierto">

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            Ingeniería de características
                        </p>

                        <h4 class="mt-1 text-lg font-black text-slate-900">
                            Variables nutricionales derivadas
                        </h4>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            El sistema convierte registros alimenticios en indicadores analíticos útiles para ciencia de datos.
                        </p>
                    </div>

                    <ClinicalBadge
                        :label="datos.calidad_datos"
                        tone="info"
                    />
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Variable</th>
                                <th class="px-4 py-3 text-left font-semibold">Valor</th>
                                <th class="px-4 py-3 text-left font-semibold">Lectura</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="variable in datos.variables"
                                :key="variable.nombre"
                            >
                                <td class="px-4 py-3 font-semibold text-slate-900">
                                    {{ variable.nombre }}
                                </td>

                                <td class="px-4 py-3 text-slate-700">
                                    {{ variable.valor }}
                                </td>

                                <td class="px-4 py-3">
                                    <ClinicalBadge
                                        :label="variable.lectura"
                                        :tone="variable.estado"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">
                    Agrupamiento K-means
                </p>

                <h4 class="mt-1 text-lg font-black text-amber-950">
                    Patrón alimenticio detectado
                </h4>

                <div class="mt-4 rounded-2xl bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <ClinicalBadge
                            :label="datos.patron.cluster"
                            tone="warning"
                        />

                        <ClinicalBadge
                            :label="`${datos.patron.similitud}% similitud`"
                            tone="info"
                        />
                    </div>

                    <h5 class="mt-3 text-base font-bold text-slate-900">
                        {{ datos.patron.nombre }}
                    </h5>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ datos.patron.descripcion }}
                    </p>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="rounded-3xl border border-violet-200 bg-violet-50/10 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-700">
                    Árbol de decisión
                </p>

                <h4 class="mt-1 text-lg font-black text-violet-950">
                    Ruta usada para estimar el riesgo
                </h4>

                <p class="mt-2 text-sm leading-6 text-slate-500 font-medium">
                    Se muestran reglas interpretables para explicar por qué el sistema llegó al nivel de riesgo.
                </p>

                <div class="mt-5 space-y-3">
                    <article
                        v-for="(paso, index) in datos.arbol_decision"
                        :key="paso.condicion"
                        class="rounded-2xl bg-white p-4 border border-slate-100 shadow-sm flex items-center justify-between gap-3 hover:border-violet-200 transition duration-200"
                    >
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-violet-50 text-[10px] font-black text-violet-700 border border-violet-100">
                                {{ index + 1 }}
                            </span>
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Regla de decisión</span>
                                <p class="mt-0.5 text-xs font-bold text-slate-800 leading-relaxed">
                                    {{ paso.condicion }}
                                </p>
                            </div>
                        </div>

                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase ring-1 border shrink-0"
                              :class="paso.resultado.toLowerCase() === 'sí' || paso.resultado.toLowerCase() === 'yes'
                                     ? 'bg-rose-50 border-rose-200 text-rose-700' 
                                     : 'bg-emerald-50 border-emerald-200 text-emerald-700'">
                            {{ paso.resultado }}
                        </span>
                    </article>
                </div>
            </div>

            <div class="rounded-3xl border border-rose-200 bg-rose-50/10 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex flex-wrap items-start justify-between gap-3 pb-3 border-b border-rose-100/30">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">
                                Resultado predictivo
                            </p>

                            <h4 class="mt-1 text-lg font-black text-rose-950">
                                Riesgo nutricional estimado
                            </h4>
                        </div>

                        <ClinicalBadge
                            :label="`Riesgo ${datos.riesgo.nivel}`"
                            :tone="tonoRiesgo"
                        />
                    </div>

                    <!-- Visual SVG gauge circle for predict risk probability percentage -->
                    <div class="mt-5 flex flex-col items-center justify-center p-6 bg-white rounded-3xl border border-slate-100 shadow-sm">
                        <div class="relative flex items-center justify-center">
                            <svg class="w-32 h-32" viewBox="0 0 36 36">
                                <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="transition-all duration-700 ease-out text-rose-500 animate-pulse" 
                                      stroke-width="3.2" 
                                      :stroke-dasharray="datos.riesgo.probabilidad + ', 100'" 
                                      stroke-linecap="round" 
                                      transform="rotate(-90 18 18)" 
                                      stroke="currentColor" 
                                      fill="none" 
                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-3xl font-black tracking-tighter text-slate-800">{{ datos.riesgo.probabilidad }}%</span>
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Riesgo</span>
                            </div>
                        </div>
                        <p class="mt-4 text-xs font-bold text-slate-800">Probabilidad de Desequilibrio</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500 max-w-xs text-center font-semibold">{{ datos.riesgo.interpretacion }}</p>
                    </div>
                </div>

                <ClinicalAlert
                    class="mt-5"
                    title="Recomendaciones asistidas por datos"
                    tone="warning"
                >
                    <ul class="list-disc space-y-1 pl-5 text-xs font-semibold text-slate-700 leading-relaxed">
                        <li
                            v-for="recomendacion in datos.recomendaciones"
                            :key="recomendacion"
                        >
                            {{ recomendacion }}
                        </li>
                    </ul>
                </ClinicalAlert>
            </div>
        </section>
        </div>
    </ClinicalSection>
</template>
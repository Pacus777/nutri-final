<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    pacientes: {
        type: Array,
        default: () => [],
    },
    estados: {
        type: Array,
        default: () => ['activo', 'cumplido', 'pausado', 'cancelado', 'cerrado'],
    },
    pacienteSeleccionado: {
        type: Object,
        default: null,
    },
    modo: {
        type: String,
        default: 'admin',
    },
    action: {
        type: String,
        default: '',
    },
    volverUrl: {
        type: String,
        default: '',
    },
    perfilClinico: {
        type: Object,
        default: () => ({
            evaluacion_inicial: null,
            paciente: {},
            contextos_activos: [],
            contextos_criticos: [],
            alertas: [],
            sugerencias_objetivo: [],
        }),
    },
    analisisInteligente: {
        type: Object,
        default: null,
    },
});

const busquedaPaciente = ref('');
const pasoActivo = ref(1);
const tabAnalisis = ref('patron');
const tabSugerencias = ref('ia');
const claseControlClaro = 'mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
const hoy = new Date().toISOString().slice(0, 10);

const esNutricionista = computed(() => props.modo === 'nutricionista');

const perfilClinico = computed(() => props.perfilClinico || {
    evaluacion_inicial: null,
    paciente: {},
    contextos_activos: [],
    contextos_criticos: [],
    alertas: [],
    sugerencias_objetivo: [],
});

const evaluacionInicial = computed(() => perfilClinico.value.evaluacion_inicial);
const contextosActivos = computed(() => perfilClinico.value.contextos_activos || []);
const contextosCriticos = computed(() => {
    return perfilClinico.value.contextos_criticos?.length
        ? perfilClinico.value.contextos_criticos
        : contextosActivos.value.filter((contexto) => contexto.es_critico || contexto.pri_cxp === 'alta');
});

const contextosNoCriticos = computed(() => {
    const idsCriticos = new Set(contextosCriticos.value.map((contexto) => contexto.cod_contextos_paciente));

    return contextosActivos.value.filter((contexto) => {
        return !idsCriticos.has(contexto.cod_contextos_paciente);
    });
});

const tieneContextosCriticos = computed(() => contextosCriticos.value.length > 0);
const alertasClinicas = computed(() => perfilClinico.value.alertas || []);
const sugerenciasObjetivo = computed(() => perfilClinico.value.sugerencias_objetivo || []);

const analisisObjetivo = computed(() => {
    return props.analisisInteligente || {
        modo: 'prototipo',
        periodo: 'Últimos 28 días',
        registros_analizados: 18,
        alimentos_procesados: 74,
        variables_generadas: 12,
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
        ],
    };
});

const datasetAnaliticoObjetivo = computed(() => {
    return analisisObjetivo.value.dataset || {
        registros_totales_periodo: analisisObjetivo.value.registros_analizados || 0,
        registros_usados: analisisObjetivo.value.registros_analizados || 0,
        registros_excluidos: 0,
        aceptados: 0,
        observados: 0,
        rechazados: 0,
        pendientes: 0,
        criterio_uso: 'El análisis usa registros aceptados y observados. Excluye registros rechazados o pendientes de validación.',
    };
});

const modoAnalisisObjetivo = computed(() => {
    if (analisisObjetivo.value.modo === 'real_preliminar') {
        return {
            label: 'Datos reales preliminares',
            tone: 'success',
            mensaje: 'El objetivo puede apoyarse en registros alimenticios aceptados u observados del paciente.',
        };
    }

    return {
        label: 'Vista prototipo',
        tone: 'warning',
        mensaje: 'El análisis se muestra como estructura visual preparada para conexión con datos reales.',
    };
});

const analisisObjetivoSinDatos = computed(() => {
    return Number(analisisObjetivo.value.registros_analizados || 0) === 0
        || String(analisisObjetivo.value.riesgo?.nivel || '').toLowerCase().includes('no estimado');
});

const tieneAnalisisObjetivo = computed(() => Boolean(pacienteSeleccionadoLocal.value));

const variablesAnaliticasEnAlerta = computed(() => {
    return (analisisObjetivo.value.variables || []).filter((variable) => {
        return ['warning', 'danger'].includes(variable.estado);
    });
});

const tonoRiesgoAnalitico = computed(() => {
    const nivel = String(analisisObjetivo.value.riesgo?.nivel || '').toLowerCase();

    if (nivel.includes('alto')) return 'danger';
    if (nivel.includes('moderado')) return 'warning';
    if (nivel.includes('bajo')) return 'success';

    return 'info';
});

const fichaPacienteUrl = computed(() => {
    if (!pacienteSeleccionadoLocal.value) {
        return volver.value;
    }

    return route('nutricionista.pacientes.show', pacienteSeleccionadoLocal.value.cod_pacientes);
});

const objetivoSugeridoPorAnalisis = computed(() => {
    const patron = analisisObjetivo.value.patron?.nombre || 'patrón alimenticio identificado';
    const riesgo = analisisObjetivo.value.riesgo?.nivel || 'riesgo nutricional estimado';

    return `Mejorar progresivamente el patrón alimenticio del paciente durante las próximas 4 semanas, priorizando regularidad de horarios, calidad del desayuno y control de carbohidratos, considerando el patrón detectado (${patron}) y el riesgo nutricional ${riesgo.toLowerCase()} estimado por el análisis inteligente.`;
});

const usarObjetivoAnalitico = () => {
    form.des_obe = objetivoSugeridoPorAnalisis.value;
};

const form = useForm({
    cod_pacientes_obe: props.pacienteSeleccionado?.cod_pacientes || '',
    des_obe: '',
    fec_obe: hoy,
    est_obe: 'activo',
});

const actionUrl = computed(() => {
    return props.action || route('objetivos-nutricionales.store');
});

const volver = computed(() => {
    return props.volverUrl || route('dashboard');
});

const pacienteSeleccionadoLocal = computed(() => {
    if (!form.cod_pacientes_obe) {
        return props.pacienteSeleccionado || null;
    }

    return props.pacientes.find((paciente) => String(paciente.cod_pacientes) === String(form.cod_pacientes_obe))
        ?? props.pacienteSeleccionado
        ?? null;
});

const pacientesFiltrados = computed(() => {
    const termino = busquedaPaciente.value.trim().toLowerCase();

    if (!termino) {
        return [];
    }

    return props.pacientes.filter((paciente) => {
        const nombre = String(paciente.usuario?.nombre_completo ?? '').toLowerCase();
        const correo = String(paciente.usuario?.cor_usu ?? '').toLowerCase();
        const ci = String(paciente.usuario?.ci_usu ?? '').toLowerCase();
        const codigo = String(paciente.cod_pacientes ?? '').toLowerCase();

        return [nombre, correo, ci, codigo].some((valor) => valor.includes(termino));
    });
});

const seleccionarPaciente = (paciente) => {
    form.cod_pacientes_obe = paciente.cod_pacientes;
    busquedaPaciente.value = '';
};

const quitarPaciente = () => {
    if (esNutricionista.value) {
        return;
    }

    form.cod_pacientes_obe = '';
    busquedaPaciente.value = '';
};

const usarSugerencia = (sugerencia) => {
    form.des_obe = sugerencia.texto;
};

const prioridadLabel = (prioridad) => {
    const labels = {
        baja: 'Baja',
        media: 'Media',
        alta: 'Alta',
    };

    return labels[prioridad] || 'Media';
};

const submit = () => {
    form.post(actionUrl.value, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Crear objetivo nutricional" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Objetivos nutricionales"
                title="Crear objetivo nutricional"
                description="Define una meta clínica clara considerando evaluación inicial, contextos activos, alertas y seguimiento profesional."
            >
                <template #actions>
                    <Link
                        :href="volver"
                        class="motion-btn rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 shadow-sm transition duration-200 hover:-translate-y-0.5"
                    >
                        Volver
                    </Link>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="py-8 animate-fade-in">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600">Asistente Clínico</p>
                            <h2 class="text-base font-black text-slate-900 leading-tight">Planificación del Objetivo</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 self-start md:self-auto">
                            <ClinicalBadge :label="`Paso ${pasoActivo} de 3`" tone="info" />
                            <ClinicalBadge v-if="pasoActivo === 1" label="Revisión de Antecedentes" tone="warning" />
                            <ClinicalBadge v-if="pasoActivo === 2" label="Redacción y Métricas" tone="success" />
                            <ClinicalBadge v-if="pasoActivo === 3" label="Validación de Seguridad" tone="danger" />
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-3 md:gap-4 relative">
                        <button 
                            type="button" 
                            @click="pasoActivo = 1"
                            class="flex items-center gap-3 p-3 rounded-2xl border text-left transition duration-200"
                            :class="pasoActivo === 1 
                                ? 'border-sky-200 bg-sky-50/40 text-sky-800 ring-2 ring-sky-100' 
                                : 'border-slate-100 bg-slate-50/50 hover:bg-slate-50 text-slate-400'"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full font-black text-xs transition duration-200"
                                :class="pasoActivo === 1 
                                    ? 'bg-sky-600 text-white' 
                                    : 'bg-slate-200 text-slate-500'"
                            >
                                1
                            </span>
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider">Paso 1</p>
                                <p class="text-xs font-bold text-slate-800 mt-0.5">Diagnóstico e IA</p>
                            </div>
                        </button>

                        <button 
                            type="button" 
                            @click="form.des_obe || pasoActivo > 1 ? pasoActivo = 2 : null"
                            class="flex items-center gap-3 p-3 rounded-2xl border text-left transition duration-200"
                            :class="pasoActivo === 2 
                                ? 'border-emerald-200 bg-emerald-50/40 text-emerald-800 ring-2 ring-emerald-100' 
                                : 'border-slate-100 bg-slate-50/50 hover:bg-slate-50 text-slate-400'"
                            :disabled="pasoActivo < 2 && !form.des_obe"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full font-black text-xs transition duration-200"
                                :class="pasoActivo === 2 
                                    ? 'bg-emerald-600 text-white' 
                                    : (pasoActivo > 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-500')"
                            >
                                <svg v-if="pasoActivo > 2" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <span v-else>2</span>
                            </span>
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider">Paso 2</p>
                                <p class="text-xs font-bold text-slate-800 mt-0.5">Formulación y Metas</p>
                            </div>
                        </button>

                        <button 
                            type="button" 
                            @click="form.des_obe ? pasoActivo = 3 : null"
                            class="flex items-center gap-3 p-3 rounded-2xl border text-left transition duration-200"
                            :class="pasoActivo === 3 
                                ? 'border-violet-200 bg-violet-50/40 text-violet-800 ring-2 ring-violet-100' 
                                : 'border-slate-100 bg-slate-50/50 hover:bg-slate-50 text-slate-400'"
                            :disabled="!form.des_obe"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full font-black text-xs transition duration-200"
                                :class="pasoActivo === 3 
                                    ? 'bg-violet-600 text-white' 
                                    : 'bg-slate-200 text-slate-500'"
                            >
                                3
                            </span>
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider">Paso 3</p>
                                <p class="text-xs font-bold text-slate-800 mt-0.5">Firma y Guardado</p>
                            </div>
                        </button>
                    </div>
                </div>

                <div v-show="pasoActivo === 1" class="space-y-6 animate-fade-in">
                    
                    <div v-if="!pacienteSeleccionadoLocal && !esNutricionista" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-4">
                        <div class="flex items-center justify-between gap-3 border-b pb-2">
                            <div>
                                <h3 class="text-base font-black text-slate-900">Buscar Paciente Asignado</h3>
                                <p class="text-xs text-slate-500 font-medium">Filtra por nombre, correo, cédula o código.</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-600">Requerido</span>
                        </div>
                        
                        <input
                            id="buscar_paciente"
                            v-model="busquedaPaciente"
                            type="text"
                            placeholder="Escribe nombre, correo, cédula o código..."
                            :class="claseControlClaro"
                        >
                        <InputError class="mt-2" :message="form.errors.cod_pacientes_obe" />

                        <div class="mt-4">
                            <div v-if="busquedaPaciente.trim() && pacientesFiltrados.length" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                <button
                                    v-for="paciente in pacientesFiltrados"
                                    :key="paciente.cod_pacientes"
                                    type="button"
                                    class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 text-left transition duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50/50"
                                    @click="seleccionarPaciente(paciente)"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-bold text-slate-900">{{ paciente.usuario?.nombre_completo }}</p>
                                            <p class="text-xs text-slate-500 font-medium mt-0.5">{{ paciente.usuario?.cor_usu }}</p>
                                        </div>
                                        <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-600 ring-1 ring-slate-200">#{{ paciente.cod_pacientes }}</span>
                                    </div>
                                </button>
                            </div>
                            <div v-else-if="busquedaPaciente.trim()" class="rounded-2xl border border-dashed border-slate-200 p-5 text-center text-xs text-slate-400 font-semibold">
                                No se encontraron pacientes para esa búsqueda.
                            </div>
                            <div v-else class="rounded-2xl border border-dashed border-slate-200 p-5 text-center text-xs text-slate-400 font-semibold">
                                Empieza a escribir para ver resultados en vivo.
                            </div>
                        </div>
                    </div>

                    <div v-if="pacienteSeleccionadoLocal" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b pb-3">
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Paciente Seleccionado</p>
                                <h3 class="text-lg font-black text-slate-900 mt-0.5">
                                    {{ perfilClinico.paciente?.usuario?.nombre_completo || pacienteSeleccionadoLocal.usuario?.nombre_completo || 'Paciente Asignado' }}
                                </h3>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">
                                    {{ perfilClinico.paciente?.usuario?.cor_usu || pacienteSeleccionadoLocal.usuario?.cor_usu }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 self-start sm:self-auto">
                                <button
                                    v-if="!esNutricionista"
                                    type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-600 hover:bg-slate-50 transition"
                                    @click="quitarPaciente"
                                >
                                    Cambiar Paciente
                                </button>
                                <ClinicalBadge v-if="tieneContextosCriticos" :label="`${contextosCriticos.length} crítico${contextosCriticos.length === 1 ? '' : 's'}`" tone="danger" />
                                <ClinicalBadge v-if="evaluacionInicial" label="Con Evaluación Inicial" tone="success" />
                                <ClinicalBadge v-else label="Sin Evaluación Inicial" tone="warning" />
                            </div>
                        </div>

                        <div class="grid gap-3 grid-cols-2 md:grid-cols-4 text-xs font-semibold text-slate-600 leading-relaxed">
                            <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3 flex flex-col justify-between">
                                <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Estatura</span>
                                <span class="text-slate-900 font-black mt-1 text-sm">{{ perfilClinico.paciente?.estatura_pac || '--' }} m</span>
                            </div>
                            <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3 flex flex-col justify-between">
                                <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Peso</span>
                                <span class="text-slate-900 font-black mt-1 text-sm">{{ perfilClinico.paciente?.pes_pac || '--' }} kg</span>
                            </div>
                            <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3 flex flex-col justify-between">
                                <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Tipo Sangre</span>
                                <span class="text-slate-900 font-black mt-1 text-sm">{{ perfilClinico.paciente?.tip_san_pac || 'No registrado' }}</span>
                            </div>
                            <div class="bg-rose-50/30 rounded-2xl border border-rose-100 p-3 flex flex-col justify-between">
                                <span class="text-rose-500 text-[10px] font-black uppercase tracking-wider">Alergias Alimentarias</span>
                                <span class="text-rose-700 font-black mt-1 text-xs truncate">{{ perfilClinico.paciente?.ale_pac || 'Ninguna' }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="pacienteSeleccionadoLocal" class="grid gap-6 lg:grid-cols-2">
                        
                        <div class="space-y-6">
                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-4">
                                <h3 class="text-base font-black text-slate-900 border-b pb-2">Riesgos y Alertas de Seguridad</h3>
                                <div v-if="alertasClinicas.length > 0" class="space-y-2.5">
                                    <ClinicalAlert
                                        v-for="(alerta, index) in alertasClinicas"
                                        :key="index"
                                        tone="warning"
                                    >
                                        {{ alerta }}
                                    </ClinicalAlert>
                                </div>
                                <div v-else class="text-xs text-slate-400 font-medium text-center py-6 border border-dashed border-slate-200 rounded-2xl bg-slate-50/20">
                                    No hay alertas de seguridad registradas para este paciente.
                                </div>
                            </div>

                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-4">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <h3 class="text-base font-black text-slate-900">Contextos Clínicos de Alta Prioridad</h3>
                                    <ClinicalBadge v-if="tieneContextosCriticos" :label="`${contextosCriticos.length} activo(s)`" tone="danger" />
                                </div>
                                <div v-if="tieneContextosCriticos" class="space-y-3 max-h-[300px] overflow-y-auto pr-1">
                                    <article
                                        v-for="contexto in contextosCriticos"
                                        :key="contexto.cod_contextos_paciente"
                                        class="rounded-2xl border border-rose-200/60 bg-rose-50/10 p-4 space-y-2"
                                    >
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <ClinicalBadge :label="contexto.tip_cxp || 'Contexto crítico'" tone="danger" />
                                            <ClinicalBadge :label="`Prioridad ${prioridadLabel(contexto.pri_cxp)}`" tone="danger" />
                                            <span class="text-[10px] text-slate-400 font-semibold ml-auto">{{ contexto.fec_cxp }}</span>
                                        </div>
                                        <p class="text-xs leading-relaxed text-slate-700 font-semibold whitespace-pre-line">
                                            {{ contexto.des_cxp }}
                                        </p>
                                    </article>
                                </div>
                                <div v-else class="text-xs text-slate-400 font-medium text-center py-6 border border-dashed border-slate-200 rounded-2xl bg-slate-50/20">
                                    No hay contextos clínicos de alta prioridad registrados.
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 flex flex-col justify-between">
                            <div>
                                <div class="flex flex-col gap-4 border-b pb-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-widest text-violet-700">Machine learning</p>
                                            <h3 class="text-base font-black text-slate-900 mt-0.5">Diagnóstico Analítico</h3>
                                        </div>
                                        <ClinicalBadge :label="`Riesgo ${analisisObjetivo.riesgo?.nivel || 'No Estimado'}`" :tone="tonoRiesgoAnalitico" />
                                    </div>

                                    <div class="flex rounded-xl bg-slate-100 p-1 self-start">
                                        <button 
                                            type="button"
                                            class="rounded-lg px-3 py-1.5 text-[10px] font-black uppercase tracking-wider transition"
                                            :class="tabAnalisis === 'patron' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                            @click="tabAnalisis = 'patron'"
                                        >
                                            Patrón K-Means
                                        </button>
                                        <button 
                                            type="button"
                                            class="rounded-lg px-3 py-1.5 text-[10px] font-black uppercase tracking-wider transition"
                                            :class="tabAnalisis === 'reglas' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                            @click="tabAnalisis = 'reglas'"
                                        >
                                            Árbol y Alertas
                                        </button>
                                    </div>
                                </div>

                                <div v-show="tabAnalisis === 'patron'" class="mt-4 space-y-4 animate-fade-in">
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50/20 p-4 space-y-2">
                                        <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider text-amber-700">
                                            <span>Clustering</span>
                                            <span>{{ analisisObjetivo.patron.similitud }}% similitud</span>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-900">{{ analisisObjetivo.patron.nombre }}</h4>
                                        <p class="text-xs text-slate-600 leading-relaxed">{{ analisisObjetivo.patron.descripcion }}</p>
                                    </div>

                                    <div class="space-y-2">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Dataset de registros del mes</p>
                                        <div class="grid grid-cols-5 gap-1.5">
                                            <div class="rounded-xl bg-emerald-50/50 p-2 text-center">
                                                <p class="text-[8px] font-black text-emerald-700 uppercase">Acep</p>
                                                <p class="text-sm font-black text-emerald-800 mt-0.5">{{ datasetAnaliticoObjetivo.aceptados }}</p>
                                            </div>
                                            <div class="rounded-xl bg-amber-50/50 p-2 text-center">
                                                <p class="text-[8px] font-black text-amber-700 uppercase">Obs</p>
                                                <p class="text-sm font-black text-amber-800 mt-0.5">{{ datasetAnaliticoObjetivo.observados }}</p>
                                            </div>
                                            <div class="rounded-xl bg-rose-50/50 p-2 text-center">
                                                <p class="text-[8px] font-black text-rose-700 uppercase">Rech</p>
                                                <p class="text-sm font-black text-rose-800 mt-0.5">{{ datasetAnaliticoObjetivo.rechazados }}</p>
                                            </div>
                                            <div class="rounded-xl bg-slate-50 p-2 text-center">
                                                <p class="text-[8px] font-black text-slate-500 uppercase">Pend</p>
                                                <p class="text-sm font-black text-slate-800 mt-0.5">{{ datasetAnaliticoObjetivo.pendientes }}</p>
                                            </div>
                                            <div class="rounded-xl bg-cyan-50/50 p-2 text-center">
                                                <p class="text-[8px] font-black text-cyan-700 uppercase">Excl</p>
                                                <p class="text-sm font-black text-cyan-800 mt-0.5">{{ datasetAnaliticoObjetivo.registros_excluidos }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-show="tabAnalisis === 'reglas'" class="mt-4 space-y-4 animate-fade-in">
                                    <div class="space-y-2">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Variables en Alerta Conductual</p>
                                        <div v-if="variablesAnaliticasEnAlerta.length > 0" class="flex flex-wrap gap-1.5">
                                            <ClinicalBadge
                                                v-for="variable in variablesAnaliticasEnAlerta"
                                                :key="variable.nombre"
                                                :label="`${variable.nombre}: ${variable.lectura}`"
                                                :tone="variable.estado"
                                            />
                                        </div>
                                        <div v-else class="text-xs text-slate-400 font-medium">Ninguna variable analítica en desvío.</div>
                                    </div>

                                    <div class="space-y-2">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Reglas Clínicas Ejecutadas</p>
                                        <div class="space-y-1.5 max-h-[140px] overflow-y-auto pr-1">
                                            <div 
                                                v-for="regla in analisisObjetivo.arbol_decision" 
                                                :key="regla.condicion"
                                                class="flex items-center justify-between gap-2 p-2 rounded-xl bg-slate-50 text-[11px] font-semibold text-slate-700 border border-slate-100"
                                            >
                                                <span class="truncate">{{ regla.condicion }}</span>
                                                <ClinicalBadge :label="regla.resultado" :tone="regla.estado" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-100">
                                <p class="text-[10px] leading-relaxed text-slate-500 font-semibold">
                                    * Este análisis se basa en el registro alimenticio del paciente. Utiliza estos datos para fundamentar el objetivo en el siguiente paso.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-show="pasoActivo === 2" class="animate-fade-in">
                    <div class="grid gap-6 lg:grid-cols-12">
                        
                        <div class="lg:col-span-7 space-y-6">
                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-5">
                                <h3 class="text-base font-black text-slate-900 border-b pb-2">Redactar Objetivo Nutricional</h3>
                                
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="fec_obe" value="Fecha de Vigencia" />
                                        <input
                                            id="fec_obe"
                                            v-model="form.fec_obe"
                                            type="date"
                                            class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-xs font-semibold text-slate-700"
                                        >
                                        <InputError class="mt-2" :message="form.errors.fec_obe" />
                                    </div>

                                    <div>
                                        <InputLabel for="est_obe" value="Estado Inicial" />
                                        <select
                                            id="est_obe"
                                            v-model="form.est_obe"
                                            class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-xs font-semibold text-slate-700"
                                            :disabled="esNutricionista"
                                        >
                                            <option v-for="estado in estados" :key="estado" :value="estado">
                                                {{ estado }}
                                            </option>
                                        </select>
                                        <p v-if="esNutricionista" class="mt-1.5 text-[10px] text-slate-400 font-medium">
                                            Los objetivos clínicos creados por el nutricionista inician siempre como "Activos".
                                        </p>
                                        <InputError class="mt-2" :message="form.errors.est_obe" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <InputLabel for="des_obe" value="Descripción del Objetivo Clínico" />
                                        <ClinicalBadge
                                            :label="`${String(form.des_obe || '').trim().length} caracteres`"
                                            :tone="String(form.des_obe || '').trim().length >= 40 ? 'success' : 'warning'"
                                        />
                                    </div>

                                    <textarea
                                        id="des_obe"
                                        v-model="form.des_obe"
                                        rows="6"
                                        class="mt-1.5 block w-full rounded-2xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 text-xs font-semibold text-slate-700 leading-relaxed"
                                        placeholder="Ejemplo: Incrementar progresivamente el consumo de agua a 2 litros diarios y regularizar los horarios del desayuno de lunes a viernes durante las próximas 4 semanas..."
                                    ></textarea>
                                    
                                    <p class="text-[10px] text-slate-400 font-semibold leading-relaxed">
                                        * Un buen objetivo clínico es específico, medible, tiene plazo (ej: 4 semanas) y ataca un desvío o patología detectada.
                                    </p>
                                    <InputError class="mt-2" :message="form.errors.des_obe" />
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-5 space-y-6">
                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 flex flex-col justify-between h-full">
                                <div>
                                    <div class="flex flex-col gap-4 border-b pb-4">
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-widest text-violet-700">Copiloto Clínico</p>
                                            <h3 class="text-base font-black text-slate-900 mt-0.5">Sugerencias Clínicas</h3>
                                        </div>

                                        <div class="flex rounded-xl bg-slate-100 p-1 self-start">
                                            <button 
                                                type="button"
                                                class="rounded-lg px-3 py-1.5 text-[10px] font-black uppercase tracking-wider transition"
                                                :class="tabSugerencias === 'ia' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                                @click="tabSugerencias = 'ia'"
                                            >
                                                Enfoque IA
                                            </button>
                                            <button 
                                                type="button"
                                                class="rounded-lg px-3 py-1.5 text-[10px] font-black uppercase tracking-wider transition"
                                                :class="tabSugerencias === 'clinica' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                                @click="tabSugerencias = 'clinica'"
                                            >
                                                Plantillas ({{ sugerenciasObjetivo.length }})
                                            </button>
                                        </div>
                                    </div>

                                    <div v-show="tabSugerencias === 'ia'" class="mt-4 space-y-4 animate-fade-in">
                                        <div class="rounded-2xl border border-violet-100 bg-violet-50/20 p-4 space-y-3">
                                            <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-wider text-violet-700">
                                                <span>Machine Learning Recomendación</span>
                                                <span class="rounded-full bg-violet-100 px-2 py-0.5">Precisión Óptima</span>
                                            </div>
                                            <p class="text-xs text-slate-700 font-semibold leading-relaxed">
                                                {{ objetivoSugeridoPorAnalisis }}
                                            </p>
                                            <button
                                                type="button"
                                                class="w-full rounded-xl bg-violet-600 hover:bg-violet-700 text-white py-2 text-xs font-black uppercase tracking-wider transition shadow-sm"
                                                @click="usarObjetivoAnalitico"
                                            >
                                                Inyectar Enfoque Sugerido
                                            </button>
                                        </div>
                                    </div>

                                    <div v-show="tabSugerencias === 'clinica'" class="mt-4 space-y-3 animate-fade-in max-h-[300px] overflow-y-auto pr-1">
                                        <div v-if="sugerenciasObjetivo.length > 0" class="space-y-3">
                                            <div 
                                                v-for="sugerencia in sugerenciasObjetivo" 
                                                :key="sugerencia.titulo"
                                                class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-2"
                                            >
                                                <div class="flex items-center justify-between border-b pb-1">
                                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">{{ sugerencia.titulo }}</span>
                                                </div>
                                                <p class="text-xs text-slate-700 font-semibold leading-relaxed">
                                                    {{ sugerencia.texto }}
                                                </p>
                                                <button
                                                    type="button"
                                                    class="rounded-xl border border-violet-200 bg-violet-50/60 hover:bg-violet-100 text-violet-800 text-[10px] font-black uppercase tracking-wider px-3 py-1.5 transition"
                                                    @click="usarSugerencia(sugerencia)"
                                                >
                                                    Usar Plantilla
                                                </button>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 font-medium text-center py-8">
                                            No hay plantillas específicas para el perfil del paciente actual.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div v-show="pasoActivo === 3" class="animate-fade-in">
                    <div class="grid gap-6 lg:grid-cols-12">
                        
                        <div class="lg:col-span-7 space-y-6">
                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-5">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <h3 class="text-base font-black text-slate-900">Resumen del Objetivo Formulando</h3>
                                    <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-800">
                                        {{ form.est_obe }}
                                    </span>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Objetivo redactado:</p>
                                        <div class="mt-2 rounded-2xl bg-slate-50 p-5 border border-slate-100">
                                            <p class="text-sm text-slate-800 leading-relaxed font-semibold whitespace-pre-line">
                                                {{ form.des_obe || 'Ninguno escrito aún.' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2 text-xs font-semibold text-slate-600">
                                        <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3 flex flex-col">
                                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Fecha de registro</span>
                                            <span class="text-slate-900 font-black mt-1">{{ form.fec_obe }}</span>
                                        </div>
                                        <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3 flex flex-col">
                                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Paciente Destino</span>
                                            <span class="text-slate-900 font-black mt-1 truncate">
                                                {{ perfilClinico.paciente?.usuario?.nombre_completo || pacienteSeleccionadoLocal?.usuario?.nombre_completo }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-5 space-y-6">
                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-5 flex flex-col justify-between h-full">
                                <div class="space-y-5">
                                    <div class="border-b pb-2">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-rose-600">Seguridad clínica</p>
                                        <h3 class="text-base font-black text-slate-900 mt-0.5">Control de Riesgos</h3>
                                    </div>

                                    <ClinicalAlert
                                        v-if="tieneContextosCriticos"
                                        title="Paciente con Contextos Críticos"
                                        tone="danger"
                                    >
                                        Este objetivo afectará a un paciente con alergias o patologías clínicas activas de prioridad alta. Asegúrese de que no sugiera alimentos o rutinas de riesgo.
                                    </ClinicalAlert>
                                    <ClinicalAlert
                                        v-else
                                        title="Canal Clínico Libre"
                                        tone="success"
                                    >
                                        El paciente no cuenta con contextos críticos activos que pongan en riesgo su plan nutricional.
                                    </ClinicalAlert>

                                    <div class="space-y-3">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Verificaciones Obligatorias</p>
                                        <div class="space-y-2 text-xs font-semibold text-slate-700">
                                            <label class="flex items-start gap-2.5 cursor-pointer">
                                                <input type="checkbox" required class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                                <span>He verificado las alergias y patologías del paciente.</span>
                                            </label>
                                            <label class="flex items-start gap-2.5 cursor-pointer">
                                                <input type="checkbox" required class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                                <span>El plazo y las metas son viables para su condición.</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-5 border-t border-slate-100">
                                    <form @submit.prevent="submit">
                                        <PrimaryButton
                                            type="submit"
                                            :disabled="form.processing || !form.des_obe"
                                            class="w-full justify-center motion-btn rounded-xl bg-violet-600 hover:bg-violet-750 text-white py-3 text-xs font-black uppercase tracking-wider transition shadow-md"
                                        >
                                            {{ form.processing ? 'Guardando objetivo...' : 'Guardar Objetivo Clínico ✓' }}
                                        </PrimaryButton>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100/80 flex items-center justify-between">
                    <button
                        v-if="pasoActivo > 1"
                        type="button"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-black uppercase tracking-wider text-slate-600 hover:bg-slate-50 transition"
                        @click="pasoActivo--"
                    >
                        ❮ Atrás
                    </button>
                    <div v-else></div> 

                    <button
                        v-if="pasoActivo < 3"
                        type="button"
                        class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 text-xs font-black uppercase tracking-wider transition shadow-sm"
                        :disabled="!pacienteSeleccionadoLocal || (pasoActivo === 2 && !form.des_obe.trim())"
                        :class="(!pacienteSeleccionadoLocal || (pasoActivo === 2 && !form.des_obe.trim())) ? 'opacity-40 cursor-not-allowed' : ''"
                        @click="pasoActivo++"
                    >
                        Siguiente Paso ❯
                    </button>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    resumen: {
        type: Object,
        default: () => ({}),
    },
    nutricionistas: {
        type: Array,
        default: () => [],
    },
    pacientes: {
        type: Array,
        default: () => [],
    },
    nutricionistaReport: {
        type: Object,
        default: null,
    },
    pacienteReport: {
        type: Object,
        default: null,
    },
    alimentacionReport: {
        type: Object,
        default: null,
    },
});

// Pestaña activa
const activeTab = ref('general'); // 'general', 'nutricionista', 'alimentacion', 'paciente'

// Estados de selección y carga
const selectedNutricionista = ref('');
const cargandoNutri = ref(false);

const selectedPaciente = ref('');
const cargandoPaciente = ref(false);

// Fechas por defecto: últimos 30 días
const hoy = new Date().toISOString().split('T')[0];
const haceMes = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
const fechaInicio = ref(haceMes);
const fechaFin = ref(hoy);
const cargandoAlimentacion = ref(false);

// Métodos de carga reactiva (Inertia Reload)
const cargarNutricionista = () => {
    if (!selectedNutricionista.value) return;
    cargandoNutri.value = true;
    router.reload({
        data: { nutricionista_id: selectedNutricionista.value },
        only: ['nutricionistaReport'],
        onFinish: () => {
            cargandoNutri.value = false;
        }
    });
};

const cargarPaciente = () => {
    if (!selectedPaciente.value) return;
    cargandoPaciente.value = true;
    router.reload({
        data: { paciente_id: selectedPaciente.value },
        only: ['pacienteReport'],
        onFinish: () => {
            cargandoPaciente.value = false;
        }
    });
};

const cargarAlimentacion = () => {
    if (!fechaInicio.value || !fechaFin.value) return;
    cargandoAlimentacion.value = true;
    router.reload({
        data: { fecha_inicio: fechaInicio.value, fecha_fin: fechaFin.value },
        only: ['alimentacionReport'],
        onFinish: () => {
            cargandoAlimentacion.value = false;
        }
    });
};

// Descargas PDF
const descargarGeneralPdf = () => {
    window.open(route('reportes.general.pdf'), '_blank');
};

const descargarNutricionistaPdf = () => {
    if (!selectedNutricionista.value) return;
    window.open(route('reportes.nutricionista.pdf', { nutricionista_id: selectedNutricionista.value }), '_blank');
};

const descargarPacientePdf = () => {
    if (!selectedPaciente.value) return;
    window.open(route('reportes.paciente.pdf', { paciente_id: selectedPaciente.value }), '_blank');
};

const descargarAlimentacionPdf = () => {
    if (!fechaInicio.value || !fechaFin.value) return;
    window.open(route('reportes.alimentacion.pdf', { fecha_inicio: fechaInicio.value, fecha_fin: fechaFin.value }), '_blank');
};

// Auxiliares
const fmt = (v) => (v ?? 0).toLocaleString('es-BO');

const macronutrientesPorcentaje = computed(() => {
    if (!props.alimentacionReport) return { carbs: 0, prot: 0, fat: 0 };
    const carbs = parseFloat(props.alimentacionReport.carbohidratos_promedio) || 0;
    const prot = parseFloat(props.alimentacionReport.proteinas_promedio) || 0;
    const fat = parseFloat(props.alimentacionReport.grasas_promedio) || 0;
    const total = carbs + prot + fat;
    if (total === 0) return { carbs: 0, prot: 0, fat: 0 };
    return {
        carbs: Math.round((carbs / total) * 100),
        prot: Math.round((prot / total) * 100),
        fat: Math.round((fat / total) * 100),
    };
});
</script>

<template>
    <Head title="Reportes del Sistema" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Gestión Ejecutiva</p>
                    <h2 class="mt-1 text-xl font-bold leading-tight text-slate-900 flex items-center gap-2">
                        <span>Reportes e Indicadores del Sistema</span>
                        <span class="rounded-full bg-slate-900 text-white px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wide">
                            Administración
                        </span>
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Genera documentos PDF y visualiza estadísticas en tiempo real sobre pacientes, nutricionistas e historial de consumo.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Tabs de Navegación de Reportes -->
                <div class="border-b border-slate-200 bg-white rounded-2xl p-2 shadow-sm flex flex-wrap gap-2">
                    <button
                        @click="activeTab = 'general'"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                        :class="activeTab === 'general' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
                    >
                        Reporte General
                    </button>
                    <button
                        @click="activeTab = 'nutricionista'"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                        :class="activeTab === 'nutricionista' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
                    >
                        Reporte por Nutricionista
                    </button>
                    <button
                        @click="activeTab = 'alimentacion'"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                        :class="activeTab === 'alimentacion' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
                    >
                        Consumo Alimentario
                    </button>
                    <button
                        @click="activeTab = 'paciente'"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                        :class="activeTab === 'paciente' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
                    >
                        Historial de Paciente
                    </button>
                </div>

                <!-- ════ TAB 1: REPORTE GENERAL (EXISTENTE REESTRUCTURADO) ════ -->
                <div v-if="activeTab === 'general'" class="grid gap-6 lg:grid-cols-[1fr_360px] animate-fadeIn">
                    <!-- Reporte General Principal -->
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="bg-slate-950 px-6 py-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Documento Consolidado</p>
                            <h3 class="mt-2 text-xl font-bold text-white">Reporte General de Operaciones</h3>
                            <p class="mt-2 text-xs text-slate-400 leading-relaxed">
                                Resumen estadístico integral del sistema: distribución demográfica de pacientes,
                                estado de asignaciones médicas, registros alimenticios ingresados y efectividad clínica global.
                            </p>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Tabla de Resumen Operativo -->
                            <div class="overflow-x-auto border border-slate-150 rounded-2xl bg-white text-xs">
                                <table class="w-full text-left text-slate-500">
                                    <thead class="bg-slate-50 text-[10px] uppercase text-slate-400 border-b border-slate-150 font-black">
                                        <tr>
                                            <th class="px-4 py-2.5">Entidad / Módulo</th>
                                            <th class="px-4 py-2.5 text-right">Total Registrados</th>
                                            <th class="px-4 py-2.5 text-right">Activos</th>
                                            <th class="px-4 py-2.5 text-right">Inactivos / Cerrados</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-800">Usuarios</td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-slate-900">{{ fmt(resumen.usuarios?.total) }}</td>
                                            <td class="px-4 py-2.5 text-right text-emerald-600 font-semibold">{{ fmt(resumen.usuarios?.activos) }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-500">{{ fmt(resumen.usuarios?.total - resumen.usuarios?.activos) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-800">Pacientes</td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-slate-900">{{ fmt(resumen.pacientes?.total) }}</td>
                                            <td class="px-4 py-2.5 text-right text-emerald-600 font-semibold">{{ fmt(resumen.pacientes?.activos) }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-500">{{ fmt(resumen.pacientes?.total - resumen.pacientes?.activos) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-800">Nutricionistas</td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-slate-900">{{ fmt(resumen.nutricionistas?.total) }}</td>
                                            <td class="px-4 py-2.5 text-right text-emerald-600 font-semibold">{{ fmt(resumen.nutricionistas?.activos) }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-500">{{ fmt(resumen.nutricionistas?.total - resumen.nutricionistas?.activos) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-800">Asignaciones</td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-slate-900">{{ fmt(resumen.asignaciones?.total) }}</td>
                                            <td class="px-4 py-2.5 text-right text-emerald-600 font-semibold">{{ fmt(resumen.asignaciones?.activas) }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-500">{{ fmt(resumen.asignaciones?.total - resumen.asignaciones?.activas) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5">
                                <p class="mb-3 text-xs font-black uppercase tracking-wider text-slate-500">Contenido Incluido en el PDF</p>
                                <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-slate-650">
                                    <li class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-violet-500"></span>
                                        Métricas demográficas y rangos de edad
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-violet-500"></span>
                                        Distribución de pacientes por sexo
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-violet-500"></span>
                                        Objetivos nutricionales y planes vigentes
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-violet-500"></span>
                                        Resumen de registros alimenticios
                                    </li>
                                </ul>
                            </div>

                            <button
                                type="button"
                                class="w-full rounded-2xl bg-slate-900 py-3 text-xs font-bold text-white hover:bg-slate-800 transition shadow-md active:scale-[0.99] cursor-pointer"
                                @click="descargarGeneralPdf"
                            >
                                Descargar Reporte Completo en PDF
                            </button>

                            <p class="text-center text-[10px] text-slate-400 font-medium">
                                Los datos reflejan el estado actual de la base de datos · {{ resumen.generado_en }}
                            </p>
                        </div>
                    </div>

                    <!-- Panel Lateral -->
                    <div class="space-y-4">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                            <p class="text-xs font-black uppercase tracking-wider text-slate-450">Estado Operativo</p>
                            <div class="divide-y divide-slate-100 text-xs space-y-2">
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-slate-500">Pacientes activos</span>
                                    <span class="font-bold text-slate-900">{{ fmt(resumen.pacientes?.activos) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-slate-500">Nutricionistas activos</span>
                                    <span class="font-bold text-slate-900">{{ fmt(resumen.nutricionistas?.activos) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-slate-500">Asignaciones activas</span>
                                    <span class="font-bold text-slate-900">{{ fmt(resumen.asignaciones?.activas) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-slate-500">Objetivos activos</span>
                                    <span class="font-bold text-slate-900">{{ fmt(resumen.objetivos?.total) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-slate-500">Planes nutricionales</span>
                                    <span class="font-bold text-slate-900">{{ fmt(resumen.planes?.total) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="resumen.pacientes?.edad_promedio" class="rounded-3xl border border-cyan-100 bg-cyan-50 p-5 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-wider text-cyan-600">Edad Promedio Pacientes</p>
                            <p class="mt-2 text-3xl font-black text-cyan-900">{{ resumen.pacientes.edad_promedio }} <span class="text-sm font-medium">años</span></p>
                        </div>
                    </div>
                </div>

                <!-- ════ TAB 2: REPORTE POR NUTRICIONISTA ════ -->
                <div v-if="activeTab === 'nutricionista'" class="space-y-6 animate-fadeIn">
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-6">
                        <div>
                            <h3 class="text-md font-bold text-slate-900">Estadísticas por Nutricionista</h3>
                            <p class="text-xs text-slate-500">Selecciona un nutricionista para visualizar su volumen de pacientes, efectividad y planes terapéuticos diseñados.</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 items-end">
                            <div class="flex-1 max-w-md space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-400">Nutricionista Profesional</label>
                                <select
                                    v-model="selectedNutricionista"
                                    @change="cargarNutricionista"
                                    class="w-full rounded-xl border-slate-200 text-xs focus:ring-slate-900 focus:border-slate-900"
                                >
                                    <option value="">Selecciona un profesional...</option>
                                    <option v-for="nut in nutricionistas" :key="nut.cod_nutricionistas" :value="nut.cod_nutricionistas">
                                        {{ nut.nombre }}
                                    </option>
                                </select>
                            </div>

                            <button
                                v-if="nutricionistaReport"
                                @click="descargarNutricionistaPdf"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 text-xs font-bold transition shadow-md cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descargar Reporte PDF
                            </button>
                        </div>

                        <!-- Estado de carga -->
                        <div v-if="cargandoNutri" class="py-12 flex justify-center items-center gap-2">
                            <span class="animate-spin h-5 w-5 border-2 border-slate-900 border-t-transparent rounded-full"></span>
                            <span class="text-xs text-slate-500 font-bold">Obteniendo métricas del profesional...</span>
                        </div>

                        <!-- Detalles del reporte -->
                        <div v-else-if="nutricionistaReport" class="grid grid-cols-1 lg:grid-cols-12 gap-6 pt-4 border-t border-slate-100">
                            <!-- Datos Generales -->
                            <div class="lg:col-span-4 space-y-4">
                                <div class="bg-slate-50 border border-slate-150 rounded-2xl p-5 space-y-3">
                                    <h4 class="text-xs font-black uppercase text-slate-400">Ficha del Profesional</h4>
                                    <div class="text-xs space-y-1.5">
                                        <p class="font-bold text-slate-900">{{ nutricionistaReport.info.nombre }}</p>
                                        <p class="text-slate-500">{{ nutricionistaReport.info.email }}</p>
                                        <p class="text-slate-550">Especialidad: <span class="font-medium text-slate-900">{{ nutricionistaReport.info.especialidad }}</span></p>
                                        <p class="text-slate-550">Estado: <span class="font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded text-[10px]">{{ nutricionistaReport.info.estado }}</span></p>
                                    </div>
                                </div>

                                <div class="bg-slate-50 border border-slate-150 rounded-2xl p-5 space-y-2.5 text-xs text-slate-700">
                                    <h4 class="text-[10px] font-black uppercase text-slate-400">Resumen Operativo</h4>
                                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                                        <span>Pacientes totales:</span>
                                        <span class="font-bold text-slate-900">{{ nutricionistaReport.stats.total_pacientes }}</span>
                                    </div>
                                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                                        <span>Pacientes activos:</span>
                                        <span class="font-bold text-emerald-600">{{ nutricionistaReport.stats.pacientes_activos }}</span>
                                    </div>
                                    <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                                        <span>Planes nutricionales:</span>
                                        <span class="font-bold text-slate-900">{{ nutricionistaReport.stats.total_planes }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span>Objetivos clínicos:</span>
                                        <span class="font-bold text-slate-900">{{ nutricionistaReport.stats.total_objetivos }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pacientes Asignados y Adherencia -->
                            <div class="lg:col-span-8 space-y-4">
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Pacientes Asignados y Cumplimiento</h4>
                                <div class="overflow-x-auto border border-slate-150 rounded-2xl bg-white">
                                    <table class="w-full text-xs text-left text-slate-500">
                                        <thead class="bg-slate-50 text-[10px] uppercase text-slate-400 border-b border-slate-150 font-black">
                                            <tr>
                                                <th class="px-4 py-3">Nombre</th>
                                                <th class="px-4 py-3">Edad / Sexo</th>
                                                <th class="px-4 py-3">Asignación</th>
                                                <th class="px-4 py-3 text-right">Adherencia</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="pac in nutricionistaReport.pacientes" :key="pac.cod_pacientes" class="hover:bg-slate-50/50">
                                                <td class="px-4 py-3">
                                                    <span class="font-bold text-slate-800">{{ pac.nombre }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600">
                                                    {{ pac.edad ?? '—' }} años · {{ pac.sexo }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-500">
                                                    {{ pac.fecha_asignacion }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span
                                                        class="px-2 py-0.5 rounded-full text-[9px] font-extrabold border"
                                                        :class="{
                                                            'bg-emerald-50 text-emerald-700 border-emerald-200': pac.adherencia.estado === 'verde',
                                                            'bg-amber-50 text-amber-700 border-amber-200': pac.adherencia.estado === 'amarillo',
                                                            'bg-rose-50 text-rose-700 border-rose-200': pac.adherencia.estado === 'rojo'
                                                        }"
                                                    >
                                                        {{ pac.adherencia.label }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr v-if="nutricionistaReport.pacientes.length === 0">
                                                <td colspan="4" class="px-4 py-8 text-center text-slate-400">Sin pacientes asignados actualmente.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-12 border-t border-slate-100 text-center text-slate-400 text-xs">
                            Por favor selecciona un nutricionista para ver su reporte interactivo.
                        </div>
                    </div>
                </div>

                <!-- ════ TAB 3: CONSUMO ALIMENTARIO ════ -->
                <div v-if="activeTab === 'alimentacion'" class="space-y-6 animate-fadeIn">
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-6">
                        <div>
                            <h3 class="text-md font-bold text-slate-900">Análisis Consolidado de Nutrición</h3>
                            <p class="text-xs text-slate-500">Analiza el consumo calórico promedio y desglose de macronutrientes registrado por los pacientes en el rango de fechas establecido.</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 items-end flex-wrap">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-400">Fecha Inicio</label>
                                <input
                                    type="date"
                                    v-model="fechaInicio"
                                    class="rounded-xl border-slate-200 text-xs focus:ring-slate-900 focus:border-slate-900"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-400">Fecha Fin</label>
                                <input
                                    type="date"
                                    v-model="fechaFin"
                                    class="rounded-xl border-slate-200 text-xs focus:ring-slate-900 focus:border-slate-900"
                                />
                            </div>

                            <button
                                @click="cargarAlimentacion"
                                class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 text-xs font-bold transition shadow-md cursor-pointer"
                            >
                                Calcular Métricas
                            </button>

                            <button
                                v-if="alimentacionReport && alimentacionReport.total_registros > 0"
                                @click="descargarAlimentacionPdf"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-4 py-2.5 text-xs font-bold transition shadow-sm cursor-pointer"
                            >
                                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Exportar PDF
                            </button>
                        </div>

                        <!-- Estado de carga -->
                        <div v-if="cargandoAlimentacion" class="py-12 flex justify-center items-center gap-2">
                            <span class="animate-spin h-5 w-5 border-2 border-slate-900 border-t-transparent rounded-full"></span>
                            <span class="text-xs text-slate-500 font-bold">Sumarizando consumo alimenticio del sistema...</span>
                        </div>

                        <!-- Detalles del reporte -->
                        <div v-else-if="alimentacionReport" class="space-y-6 pt-4 border-t border-slate-100">
                            <!-- Tabla de Composición Promedio -->
                            <div class="overflow-x-auto border border-slate-150 rounded-2xl bg-white text-xs">
                                <table class="w-full text-left text-slate-500">
                                    <thead class="bg-slate-50 text-[10px] uppercase text-slate-400 border-b border-slate-150 font-black">
                                        <tr>
                                            <th class="px-4 py-2.5">Nutriente / Componente</th>
                                            <th class="px-4 py-2.5 text-right">Promedio Diario Registrado</th>
                                            <th class="px-4 py-2.5 text-right">Métrica / Unidad</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-800">Calorías Totales</td>
                                            <td class="px-4 py-2.5 text-right font-black text-slate-900">{{ fmt(alimentacionReport.calorias_promedio) }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-500 font-semibold">kcal</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-800 flex items-center gap-2">
                                                <span class="h-2 w-2 rounded bg-emerald-500"></span>Carbohidratos
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-emerald-600">{{ fmt(alimentacionReport.carbohidratos_promedio) }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-500">gramos</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-800 flex items-center gap-2">
                                                <span class="h-2 w-2 rounded bg-violet-500"></span>Proteínas
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-violet-600">{{ fmt(alimentacionReport.proteinas_promedio) }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-500">gramos</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-slate-800 flex items-center gap-2">
                                                <span class="h-2 w-2 rounded bg-amber-500"></span>Grasas
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-amber-600">{{ fmt(alimentacionReport.grasas_promedio) }}</td>
                                            <td class="px-4 py-2.5 text-right text-slate-500">gramos</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="alimentacionReport.total_registros > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Gráfico de barra de macronutrientes -->
                                <div class="border border-slate-150 rounded-2xl p-5 space-y-4">
                                    <h4 class="text-xs font-black uppercase text-slate-400">Balance de Macronutrientes (%)</h4>
                                    
                                    <div class="w-full h-3.5 bg-slate-100 rounded-xl overflow-hidden flex">
                                        <div :style="{ width: macronutrientesPorcentaje.carbs + '%' }" class="bg-emerald-500 h-full transition-all duration-500" :title="'Carbohidratos: ' + macronutrientesPorcentaje.carbs + '%'"></div>
                                        <div :style="{ width: macronutrientesPorcentaje.prot + '%' }" class="bg-violet-500 h-full transition-all duration-500" :title="'Proteínas: ' + macronutrientesPorcentaje.prot + '%'"></div>
                                        <div :style="{ width: macronutrientesPorcentaje.fat + '%' }" class="bg-amber-500 h-full transition-all duration-500" :title="'Grasas: ' + macronutrientesPorcentaje.fat + '%'"></div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="flex items-center gap-1.5">
                                                <span class="h-2 w-2 rounded bg-emerald-500"></span>
                                                Carbohidratos ({{ macronutrientesPorcentaje.carbs }}%)
                                            </span>
                                            <span class="font-bold text-slate-800">{{ fmt(alimentacionReport.carbohidratos_promedio) }}g</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="flex items-center gap-1.5">
                                                <span class="h-2 w-2 rounded bg-violet-500"></span>
                                                Proteínas ({{ macronutrientesPorcentaje.prot }}%)
                                            </span>
                                            <span class="font-bold text-slate-800">{{ fmt(alimentacionReport.proteinas_promedio) }}g</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="flex items-center gap-1.5">
                                                <span class="h-2 w-2 rounded bg-amber-500"></span>
                                                Grasas ({{ macronutrientesPorcentaje.fat }}%)
                                            </span>
                                            <span class="font-bold text-slate-800">{{ fmt(alimentacionReport.grasas_promedio) }}g</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Alimentos Frecuentes -->
                                <div class="border border-slate-150 rounded-2xl p-5 space-y-4">
                                    <h4 class="text-xs font-black uppercase text-slate-400">Alimentos más Frecuentes</h4>
                                    <div class="divide-y divide-slate-100 text-xs">
                                        <div v-for="(ali, idx) in alimentacionReport.comidas_frecuentes" :key="idx" class="py-2.5 flex justify-between items-center">
                                            <span class="font-medium text-slate-700">{{ ali.nombre }}</span>
                                            <span class="font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded">{{ ali.total }} veces</span>
                                        </div>
                                        <div v-if="alimentacionReport.comidas_frecuentes.length === 0" class="py-6 text-center text-slate-400">
                                            Sin registros específicos para detallar.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center text-slate-500 text-xs py-8">
                                No se encontraron registros de comidas para las fechas indicadas.
                            </div>
                        </div>

                        <div v-else class="py-12 border-t border-slate-100 text-center text-slate-400 text-xs">
                            Establece el rango de fechas y presiona calcular.
                        </div>
                    </div>
                </div>

                <!-- ════ TAB 4: HISTORIAL DE PACIENTE INDIVIDUAL ════ -->
                <div v-if="activeTab === 'paciente'" class="space-y-6 animate-fadeIn">
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-6">
                        <div>
                            <h3 class="text-md font-bold text-slate-900">Historial Clínico y Nutricional</h3>
                            <p class="text-xs text-slate-500">Visualiza la ficha básica de un paciente individual, incluyendo objetivos actuales, adherencia a su plan y evolución de peso.</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 items-end">
                            <div class="flex-1 max-w-md space-y-1">
                                <label class="text-[10px] font-black uppercase text-slate-400">Paciente</label>
                                <select
                                    v-model="selectedPaciente"
                                    @change="cargarPaciente"
                                    class="w-full rounded-xl border-slate-200 text-xs focus:ring-slate-900 focus:border-slate-900"
                                >
                                    <option value="">Selecciona un paciente...</option>
                                    <option v-for="pac in pacientes" :key="pac.cod_pacientes" :value="pac.cod_pacientes">
                                        {{ pac.nombre }}
                                    </option>
                                </select>
                            </div>

                            <button
                                v-if="pacienteReport"
                                @click="descargarPacientePdf"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 text-xs font-bold transition shadow-md cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descargar Historial PDF
                            </button>
                        </div>

                        <!-- Estado de carga -->
                        <div v-if="cargandoPaciente" class="py-12 flex justify-center items-center gap-2">
                            <span class="animate-spin h-5 w-5 border-2 border-slate-900 border-t-transparent rounded-full"></span>
                            <span class="text-xs text-slate-500 font-bold">Obteniendo historial médico y nutricional...</span>
                        </div>

                        <!-- Detalles del reporte -->
                        <div v-else-if="pacienteReport" class="grid grid-cols-1 lg:grid-cols-12 gap-6 pt-4 border-t border-slate-100">
                            <!-- Datos Clinicos y Fisicos -->
                            <div class="lg:col-span-4 space-y-4">
                                <div class="bg-slate-50 border border-slate-150 rounded-2xl p-5 space-y-4">
                                    <h4 class="text-xs font-black uppercase text-slate-400">Información General</h4>
                                    <div class="text-xs space-y-2">
                                        <p class="text-slate-500">Nombre: <span class="font-bold text-slate-900">{{ pacienteReport.info.nombre }}</span></p>
                                        <p class="text-slate-500">Email: <span class="font-bold text-slate-900">{{ pacienteReport.info.email }}</span></p>
                                        <p class="text-slate-500">Edad: <span class="font-bold text-slate-900">{{ pacienteReport.info.edad ?? '—' }} años</span></p>
                                        <p class="text-slate-500">Sexo: <span class="font-bold text-slate-900">{{ pacienteReport.info.sexo }}</span></p>
                                        <p class="text-slate-500">Tipo de sangre: <span class="font-bold text-slate-900">{{ pacienteReport.info.tipo_sangre }}</span></p>
                                        <p class="text-slate-500">Alergias: <span class="font-bold text-slate-900">{{ pacienteReport.info.alergias }}</span></p>
                                    </div>
                                </div>

                                <div class="bg-slate-50 border border-slate-150 rounded-2xl p-5 space-y-4">
                                    <h4 class="text-xs font-black uppercase text-slate-400">Composición Física</h4>
                                    <div class="text-xs space-y-2">
                                        <p class="text-slate-500">Peso actual: <span class="font-bold text-slate-900">{{ pacienteReport.info.peso_actual ?? '—' }} kg</span></p>
                                        <p class="text-slate-500">Estatura: <span class="font-bold text-slate-900">{{ pacienteReport.info.estatura ?? '—' }} m</span></p>
                                        <p class="text-slate-550 flex items-center gap-2">
                                            <span>Adherencia actual:</span>
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[9px] font-extrabold border"
                                                :class="{
                                                    'bg-emerald-50 text-emerald-700 border-emerald-200': pacienteReport.adherencia.estado === 'verde',
                                                    'bg-amber-50 text-amber-700 border-amber-200': pacienteReport.adherencia.estado === 'amarillo',
                                                    'bg-rose-50 text-rose-700 border-rose-200': pacienteReport.adherencia.estado === 'rojo'
                                                }"
                                            >
                                                {{ pacienteReport.adherencia.label }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contextos clinicos y metas -->
                            <div class="lg:col-span-8 space-y-6">
                                <!-- Contextos Clinicos -->
                                <div class="space-y-3">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Antecedentes Clínicos / Patologías</h4>
                                    <div class="overflow-x-auto border border-slate-150 rounded-2xl bg-white text-xs">
                                        <table class="w-full text-left text-slate-500">
                                            <thead class="bg-slate-50 text-[10px] uppercase text-slate-400 border-b border-slate-150 font-black">
                                                <tr>
                                                    <th class="px-4 py-2.5">Fecha</th>
                                                    <th class="px-4 py-2.5">Tipo</th>
                                                    <th class="px-4 py-2.5">Prioridad</th>
                                                    <th class="px-4 py-2.5">Detalle</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <tr v-for="(ctx, idx) in pacienteReport.contextos" :key="idx">
                                                    <td class="px-4 py-2.5">{{ ctx.fecha }}</td>
                                                    <td class="px-4 py-2.5 font-bold text-slate-800">{{ ctx.tipo }}</td>
                                                    <td class="px-4 py-2.5">
                                                        <span
                                                            class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border"
                                                            :class="{
                                                                'bg-rose-50 text-rose-700 border-rose-200': ctx.prioridad === 'alta',
                                                                'bg-amber-50 text-amber-700 border-amber-200': ctx.prioridad === 'media',
                                                                'bg-slate-100 text-slate-650 border-slate-200': ctx.prioridad === 'baja'
                                                            }"
                                                        >
                                                            {{ ctx.prioridad }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2.5 text-slate-600">{{ ctx.descripcion }}</td>
                                                </tr>
                                                <tr v-if="pacienteReport.contextos.length === 0">
                                                    <td colspan="4" class="px-4 py-6 text-center text-slate-400">Sin condiciones clínicas registradas.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Historial de Peso -->
                                <div class="space-y-3">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Historial de Peso Registrado</h4>
                                    <div class="overflow-x-auto border border-slate-150 rounded-2xl bg-white text-xs">
                                        <table class="w-full text-left text-slate-500">
                                            <thead class="bg-slate-50 text-[10px] uppercase text-slate-400 border-b border-slate-150 font-black">
                                                <tr>
                                                    <th class="px-4 py-2.5">Fecha</th>
                                                    <th class="px-4 py-2.5 text-right">Peso (kg)</th>
                                                    <th class="px-4 py-2.5 text-right">IMC</th>
                                                    <th class="px-4 py-2.5 text-right">Origen</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <tr v-for="(pes, idx) in pacienteReport.historial_peso" :key="idx">
                                                    <td class="px-4 py-2.5">{{ pes.fecha }}</td>
                                                    <td class="px-4 py-2.5 text-right font-bold text-slate-800">{{ pes.peso }} kg</td>
                                                    <td class="px-4 py-2.5 text-right">{{ pes.imc ?? '—' }}</td>
                                                    <td class="px-4 py-2.5 text-right text-slate-400">{{ pes.origen }}</td>
                                                </tr>
                                                <tr v-if="pacienteReport.historial_peso.length === 0">
                                                    <td colspan="4" class="px-4 py-6 text-center text-slate-400">Sin historial de peso.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-12 border-t border-slate-100 text-center text-slate-400 text-xs">
                            Selecciona un paciente para visualizar su historial clínico y metas.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

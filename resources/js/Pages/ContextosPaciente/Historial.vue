<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    paciente: {
        type: Object,
        required: true,
    },
    contextos: {
        type: Array,
        default: () => [],
    },
    modo: {
        type: String,
        default: 'admin',
    },
    crearUrl: {
        type: String,
        default: '',
    },
    volverUrl: {
        type: String,
        default: '',
    },
});

const page = usePage();
const esNutricionista = computed(() => props.modo === 'nutricionista');

const filtroTexto = ref('');
const filtroEstado = ref('todos');
const filtroPrioridad = ref('todos');
const filtroTipo = ref('todos');

const crear = computed(() => {
    if (props.crearUrl) {
        return props.crearUrl;
    }

    return route('contextos-paciente.create', { paciente: props.paciente.cod_pacientes });
});

const volver = computed(() => {
    if (props.volverUrl) {
        return props.volverUrl;
    }

    return route('contextos-paciente.index');
});

const editUrl = (ctx) => {
    if (esNutricionista.value) {
        return route('nutricionista.contextos-paciente.edit', ctx.cod_contextos_paciente);
    }

    return route('contextos-paciente.edit', ctx.cod_contextos_paciente);
};

const inactivarUrl = (ctx) => {
    if (esNutricionista.value) {
        return route('nutricionista.contextos-paciente.inactivar', ctx.cod_contextos_paciente);
    }

    return route('contextos-paciente.inactivar', ctx.cod_contextos_paciente);
};

const inactivar = (ctx) => {
    if (!confirm('¿Seguro que deseas inactivar este contexto clínico?')) {
        return;
    }

    router.patch(inactivarUrl(ctx), {}, {
        preserveScroll: true,
    });
};

const colorEstado = (est) => {
    return est === 'activo'
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-gray-100 text-gray-500';
};

const prioridadLabel = (prioridad) => {
    const labels = {
        baja: 'Baja',
        media: 'Media',
        alta: 'Alta',
    };

    return labels[prioridad] || 'Media';
};

const colorPrioridad = (prioridad) => {
    if (prioridad === 'alta') {
        return 'bg-rose-100 text-rose-700';
    }

    if (prioridad === 'media') {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-slate-100 text-slate-600';
};

const prioridadTone = (prioridad) => {
    if (prioridad === 'alta') {
        return 'danger';
    }

    if (prioridad === 'media') {
        return 'warning';
    }

    return 'neutral';
};

const estadoTone = (estado) => {
    return estado === 'activo' ? 'success' : 'neutral';
};

const contextoEsCritico = (ctx) => {
    return ctx.pri_cxp === 'alta' && ctx.est_cxp === 'activo';
};

const tipoContextoLabel = (ctx) => {
    return ctx.tip_cxp || 'Contexto clínico';
};

const descripcionContexto = (ctx) => {
    return ctx.des_cxp || 'Sin descripción registrada.';
};

const totalAltaPrioridad = computed(() => {
    return props.contextos.filter((ctx) => ctx.pri_cxp === 'alta' && ctx.est_cxp === 'activo').length;
});

const totalActivos = computed(() => {
    return props.contextos.filter((ctx) => ctx.est_cxp === 'activo').length;
});

const totalInactivos = computed(() => {
    return props.contextos.filter((ctx) => ctx.est_cxp === 'inactivo').length;
});

const contextosFiltrados = computed(() => {
    const texto = filtroTexto.value.trim().toLowerCase();

    return props.contextos
        .filter((ctx) => {
            const coincideTexto = texto === ''
                ? true
                : [ctx.des_cxp, ctx.tip_cxp, ctx.fec_cxp].some((valor) => String(valor || '').toLowerCase().includes(texto));

            const coincideEstado = filtroEstado.value === 'todos'
                ? true
                : ctx.est_cxp === filtroEstado.value;

            const coincidePrioridad = filtroPrioridad.value === 'todos'
                ? true
                : ctx.pri_cxp === filtroPrioridad.value;

            const coincideTipo = filtroTipo.value === 'todos'
                ? true
                : ctx.tip_cxp === filtroTipo.value;

            return coincideTexto && coincideEstado && coincidePrioridad && coincideTipo;
        })
        .sort((a, b) => {
            const estadoA = a.est_cxp === 'activo' ? 1 : 0;
            const estadoB = b.est_cxp === 'activo' ? 1 : 0;

            if (estadoA !== estadoB) {
                return estadoB - estadoA;
            }

            const prioridadOrden = {
                alta: 3,
                media: 2,
                baja: 1,
            };

            const prioridadA = prioridadOrden[a.pri_cxp] || 0;
            const prioridadB = prioridadOrden[b.pri_cxp] || 0;

            if (prioridadA !== prioridadB) {
                return prioridadB - prioridadA;
            }

            return String(b.fec_cxp || '').localeCompare(String(a.fec_cxp || ''));
        });
});

const tiposDisponibles = computed(() => {
    return [...new Set(props.contextos.map((ctx) => ctx.tip_cxp).filter(Boolean))].sort();
});

const totalFiltrados = computed(() => contextosFiltrados.value.length);

const limpiarFiltros = () => {
    filtroTexto.value = '';
    filtroEstado.value = 'todos';
    filtroPrioridad.value = 'todos';
    filtroTipo.value = 'todos';
};

const nombrePaciente = (p) => {
    if (!p) return 'Paciente sin nombre';
    if (p.usuario?.nombre_completo) return p.usuario.nombre_completo;
    return `${p.usuario?.nom_usu ?? ''} ${p.usuario?.ape_usu ?? ''}`.trim() || 'Paciente sin nombre';
};

const obtenerIniciales = (nombre) => {
    if (!nombre || nombre === 'Paciente sin nombre') return 'P';
    return nombre
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const obtenerEstiloCategoria = (tipo) => {
    const estilos = {
        'Alergia': {
            bg: 'bg-rose-50/60 text-rose-800 border-rose-100/80',
            active: 'border-rose-500 bg-rose-50/90 text-rose-900 ring-4 ring-rose-500/10 shadow-lg shadow-rose-500/5',
            badge: 'bg-rose-100 text-rose-800 border-rose-200/50',
            iconBg: 'bg-rose-100 text-rose-600',
            iconColor: 'text-rose-600',
        },
        'Restricción alimentaria': {
            bg: 'bg-amber-50/60 text-amber-800 border-amber-100/80',
            active: 'border-amber-500 bg-amber-50/90 text-amber-900 ring-4 ring-amber-500/10 shadow-lg shadow-amber-500/5',
            badge: 'bg-amber-100 text-amber-800 border-amber-200/50',
            iconBg: 'bg-amber-100 text-amber-600',
            iconColor: 'text-amber-600',
        },
        'Patología': {
            bg: 'bg-rose-50/60 text-rose-800 border-rose-100/80',
            active: 'border-rose-500 bg-rose-50/90 text-rose-900 ring-4 ring-rose-500/10 shadow-lg shadow-rose-500/5',
            badge: 'bg-rose-100 text-rose-800 border-rose-200/50',
            iconBg: 'bg-rose-100 text-rose-600',
            iconColor: 'text-rose-600',
        },
        'Medicamento': {
            bg: 'bg-indigo-50/60 text-indigo-800 border-indigo-100/80',
            active: 'border-indigo-500 bg-indigo-50/90 text-indigo-900 ring-4 ring-indigo-500/10 shadow-lg shadow-indigo-500/5',
            badge: 'bg-indigo-100 text-indigo-850 border-indigo-200/50',
            iconBg: 'bg-indigo-100 text-indigo-600',
            iconColor: 'text-indigo-600',
        },
        'Hábitos alimentarios': {
            bg: 'bg-emerald-50/60 text-emerald-800 border-emerald-100/80',
            active: 'border-emerald-500 bg-emerald-50/90 text-emerald-900 ring-4 ring-emerald-500/10 shadow-lg shadow-emerald-500/5',
            badge: 'bg-emerald-100 text-emerald-850 border-emerald-200/50',
            iconBg: 'bg-emerald-100 text-emerald-600',
            iconColor: 'text-emerald-600',
        },
        'Actividad física': {
            bg: 'bg-sky-50/60 text-sky-800 border-sky-100/80',
            active: 'border-sky-500 bg-sky-50/90 text-sky-900 ring-4 ring-sky-500/10 shadow-lg shadow-sky-500/5',
            badge: 'bg-sky-100 text-sky-850 border-sky-200/50',
            iconBg: 'bg-sky-100 text-sky-600',
            iconColor: 'text-sky-600',
        },
        'Preferencia alimentaria': {
            bg: 'bg-teal-50/60 text-teal-800 border-teal-100/80',
            active: 'border-teal-500 bg-teal-50/90 text-teal-900 ring-4 ring-teal-500/10 shadow-lg shadow-teal-500/5',
            badge: 'bg-teal-100 text-teal-855 border-teal-200/50',
            iconBg: 'bg-teal-100 text-teal-600',
            iconColor: 'text-teal-600',
        },
        'Factor económico/social': {
            bg: 'bg-purple-50/60 text-purple-800 border-purple-100/80',
            active: 'border-purple-500 bg-purple-50/90 text-purple-900 ring-4 ring-purple-500/10 shadow-lg shadow-purple-500/5',
            badge: 'bg-purple-100 text-purple-850 border-purple-200/50',
            iconBg: 'bg-purple-100 text-purple-600',
            iconColor: 'text-purple-600',
        },
        'Antecedente familiar': {
            bg: 'bg-violet-50/60 text-violet-850 border-violet-100/80',
            active: 'border-violet-500 bg-violet-50/90 text-violet-900 ring-4 ring-violet-500/10 shadow-lg shadow-violet-500/5',
            badge: 'bg-violet-100 text-violet-850 border-violet-200/50',
            iconBg: 'bg-violet-100 text-violet-600',
            iconColor: 'text-violet-600',
        },
        'Observación clínica': {
            bg: 'bg-slate-50/60 text-slate-800 border-slate-100/80',
            active: 'border-slate-500 bg-slate-50/90 text-slate-900 ring-4 ring-slate-500/10 shadow-lg shadow-slate-500/5',
            badge: 'bg-slate-100 text-slate-700 border-slate-200/50',
            iconBg: 'bg-slate-100 text-slate-600',
            iconColor: 'text-slate-600',
        },
    };

    return estilos[tipo] || {
        bg: 'bg-slate-50/60 text-slate-800 border-slate-100/80',
        active: 'border-slate-400 bg-slate-50/90 text-slate-900 ring-4 ring-slate-400/10 shadow-lg shadow-slate-400/5',
        badge: 'bg-slate-100 text-slate-700 border-slate-200/50',
        iconBg: 'bg-slate-100 text-slate-600',
        iconColor: 'text-slate-500',
    };
};
</script>

<template>
    <Head title="Historial de contextos del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 text-lg font-bold text-white shadow-md shadow-emerald-500/15">
                        {{ obtenerIniciales(nombrePaciente(paciente)) }}
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">
                            Contextos Clínicos
                        </p>
                        <h2 class="mt-0.5 text-2xl font-extrabold tracking-tight leading-tight text-slate-900">
                            Historial Médico de {{ paciente.usuario?.nombre_completo || 'Paciente' }}
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500 flex items-center gap-2">
                            <span>{{ paciente.usuario?.cor_usu }}</span>
                            <span>•</span>
                            <span>CI: {{ paciente.usuario?.ci_usu ?? 'No disponible' }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="crear"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-600/10 hover:bg-emerald-700 hover:shadow-emerald-700/15 active:scale-95 transition-all"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Nuevo contexto
                    </Link>

                    <Link
                        :href="volver"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 active:scale-95 transition-all shadow-xs"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                
                <!-- Notificación Flash -->
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-2xl border border-green-200 bg-green-50 p-4 text-xs font-bold text-green-800 shadow-sm flex items-center gap-2 animate-pulse-subtle"
                >
                    <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                    {{ page.props.flash.success }}
                </div>

                <!-- PANEL DE ESTADÍSTICAS COMPONENTES -->
                <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    
                    <!-- Stat Card 1: Total -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition duration-200 relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total</span>
                            <span class="h-8 w-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-extrabold text-slate-900 leading-none">{{ contextos.length }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-bold uppercase tracking-wider">registrados</p>
                        </div>
                    </div>

                    <!-- Stat Card 2: Activos -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition duration-200 relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Activos</span>
                            <span class="h-8 w-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-extrabold text-emerald-600 leading-none">{{ totalActivos }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-bold uppercase tracking-wider">vigentes</p>
                        </div>
                    </div>

                    <!-- Stat Card 3: Inactivos -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition duration-200 relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Inactivos</span>
                            <span class="h-8 w-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-extrabold text-slate-500 leading-none">{{ totalInactivos }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-bold uppercase tracking-wider">históricos</p>
                        </div>
                    </div>

                    <!-- Stat Card 4: Críticos -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition duration-200 relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Críticos</span>
                            <span class="h-8 w-8 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600" :class="totalAltaPrioridad > 0 ? 'bg-rose-100 text-rose-700 animate-pulse' : ''">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-extrabold leading-none" :class="totalAltaPrioridad > 0 ? 'text-rose-600' : 'text-slate-900'">{{ totalAltaPrioridad }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-bold uppercase tracking-wider">altamente críticos</p>
                        </div>
                    </div>

                    <!-- Stat Card 5: Coincidentes -->
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition duration-200 relative overflow-hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filtrados</span>
                            <span class="h-8 w-8 rounded-xl bg-sky-50 flex items-center justify-center text-sky-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-extrabold text-sky-600 leading-none">{{ totalFiltrados }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-bold uppercase tracking-wider">coincidentes</p>
                        </div>
                    </div>
                </section>

                <!-- BARRA DE FILTROS CLÍNICOS AVANZADOS -->
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-600">Filtros Dinámicos</p>
                            <h3 class="mt-0.5 text-base font-bold text-slate-900">Buscar y priorizar expedientes</h3>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 active:scale-95 transition-all shadow-xs"
                            @click="limpiarFiltros"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Limpiar Filtros
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Buscar texto -->
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Buscar</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input
                                    v-model="filtroTexto"
                                    type="text"
                                    class="block w-full rounded-xl border-slate-300 pl-9 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-xs py-2.5 transition-all"
                                    placeholder="Palabra clave, tipo o fecha..."
                                />
                            </div>
                        </div>

                        <!-- Filtrar Estado -->
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Vigencia</label>
                            <select
                                v-model="filtroEstado"
                                class="block w-full rounded-xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-xs py-2.5"
                            >
                                <option value="todos">Todos los Estados</option>
                                <option value="activo">Vigentes (Activos)</option>
                                <option value="inactivo">Históricos (Inactivos)</option>
                            </select>
                        </div>

                        <!-- Filtrar Prioridad -->
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Prioridad</label>
                            <select
                                v-model="filtroPrioridad"
                                class="block w-full rounded-xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-xs py-2.5"
                            >
                                <option value="todos">Todas las Prioridades</option>
                                <option value="alta">Alta prioridad</option>
                                <option value="media">Media prioridad</option>
                                <option value="baja">Baja prioridad</option>
                            </select>
                        </div>

                        <!-- Filtrar Tipo -->
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Tipo de Contexto</label>
                            <select
                                v-model="filtroTipo"
                                class="block w-full rounded-xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-xs py-2.5"
                            >
                                <option value="todos">Todos los tipos</option>
                                <option
                                    v-for="tipo in tiposDisponibles"
                                    :key="tipo"
                                    :value="tipo"
                                >
                                    {{ tipo }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ALERTA ANTECEDENTE CRÍTICO PULSANTE -->
                <div
                    v-if="totalAltaPrioridad > 0"
                    class="rounded-3xl border border-rose-200 bg-rose-50/50 p-5 shadow-xs flex items-start gap-4"
                >
                    <span class="flex h-4 w-4 relative mt-1 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500"></span>
                    </span>
                    <div>
                        <h4 class="text-sm font-extrabold text-rose-950">Atención: Antecedentes Clínicos Críticos Detectados</h4>
                        <p class="text-xs text-rose-700 leading-relaxed mt-1 max-w-4xl">Este paciente cuenta con {{ totalAltaPrioridad }} antecedente{{ totalAltaPrioridad === 1 ? '' : 's' }} crítico{{ totalAltaPrioridad === 1 ? '' : 's' }} activo{{ totalAltaPrioridad === 1 ? '' : 's' }} en su ficha de seguimiento. Valida detalladamente estas alergias o patologías antes de configurar sus planes alimenticios o validaciones de ingestas.</p>
                    </div>
                </div>

                <!-- TIMELINE CRONOLÓGICO DE ANTECEDENTES -->
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-5 mb-8">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-600">Cronología Médica</p>
                            <h3 class="mt-0.5 text-lg font-extrabold text-slate-950">Línea de Tiempo del Paciente</h3>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-100/60">
                            {{ contextosFiltrados.length }} antecedente{{ contextosFiltrados.length === 1 ? '' : 's' }} en pantalla
                        </span>
                    </div>

                    <!-- CASO 1: Sin contextos clínicos registrados en absoluto -->
                    <div v-if="contextos.length === 0" class="flex flex-col items-center justify-center text-center py-16 px-4">
                        <div class="h-16 w-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 ring-4 ring-emerald-500/5">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h4 class="text-base font-extrabold text-slate-900">Historial Clínico Vacío</h4>
                        <p class="text-xs text-slate-500 mt-2 max-w-md leading-relaxed">Este paciente aún no cuenta con antecedentes registrados. Comienza agregando patologías, alergias o hábitos de alimentación para personalizar su acompañamiento nutricional.</p>
                        <Link
                            :href="crear"
                            class="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-600/10 hover:bg-emerald-700 transition-all active:scale-95"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Agregar Primer Contexto
                        </Link>
                    </div>

                    <!-- CASO 2: Filtros aplicados sin resultados coincidentes -->
                    <div v-else-if="contextosFiltrados.length === 0" class="flex flex-col items-center justify-center text-center py-16 px-4">
                        <div class="h-16 w-16 rounded-3xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4 ring-4 ring-amber-500/5">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </div>
                        <h4 class="text-base font-extrabold text-slate-900">Búsqueda sin Resultados</h4>
                        <p class="text-xs text-slate-500 mt-2 max-w-sm leading-relaxed">No se encontraron antecedentes médicos del paciente que coincidan con los filtros seleccionados actualmente.</p>
                        <button
                            type="button"
                            class="mt-5 text-xs font-bold text-emerald-600 hover:text-emerald-700 underline"
                            @click="limpiarFiltros"
                        >
                            Restablecer todos los filtros
                        </button>
                    </div>

                    <!-- CASO 3: Listado de contextos en timeline cronológico interactivo -->
                    <div v-else class="relative border-l-2 border-slate-100 ml-4 pl-6 sm:pl-8 space-y-8 py-3">
                        
                        <article
                            v-for="ctx in contextosFiltrados"
                            :key="ctx.cod_contextos_paciente"
                            class="relative flex flex-col md:flex-row gap-4 items-start group"
                        >
                            <!-- Nodo del Timeline interactivo -->
                            <span 
                                class="absolute -left-[45px] sm:-left-[53px] top-1.5 flex h-10 w-10 items-center justify-center rounded-2xl border-4 border-white shadow-md transition-all duration-300 select-none"
                                :class="obtenerEstiloCategoria(ctx.tip_cxp).iconBg"
                            >
                                <!-- Renderizado condicional de iconos SVG vectoriales del timeline -->
                                <svg v-if="ctx.tip_cxp === 'Patología'" class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <svg v-else-if="ctx.tip_cxp === 'Alergia'" class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <svg v-else-if="ctx.tip_cxp === 'Medicamento'" class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 9.172V5L8 4z" />
                                </svg>
                                <svg v-else-if="ctx.tip_cxp === 'Antecedente familiar'" class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg v-else-if="ctx.tip_cxp === 'Hábitos alimentarios'" class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else-if="ctx.tip_cxp === 'Actividad física'" class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <svg v-else-if="ctx.tip_cxp === 'Observación clínica'" class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <svg v-else class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </span>

                            <!-- Cuerpo de la Tarjeta del Timeline -->
                            <div 
                                class="flex-1 rounded-3xl border p-5 shadow-xs transition duration-200 group-hover:shadow-md relative"
                                :class="contextoEsCritico(ctx)
                                    ? 'border-rose-200 bg-rose-50/20 shadow-rose-100/5'
                                    : 'border-slate-200 bg-white'"
                            >
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0 flex-1 space-y-3.5">
                                        <!-- Fila superior: Badges e Información Temporal -->
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            <span 
                                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider shadow-xs border border-white/40"
                                                :class="obtenerEstiloCategoria(ctx.tip_cxp).badge"
                                            >
                                                {{ tipoContextoLabel(ctx) }}
                                            </span>

                                            <span 
                                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                                :class="colorPrioridad(ctx.pri_cxp) + ' border border-white/40 shadow-xs'"
                                            >
                                                Prioridad {{ prioridadLabel(ctx.pri_cxp) }}
                                            </span>

                                            <span 
                                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold capitalize shadow-xs"
                                                :class="colorEstado(ctx.est_cxp)"
                                            >
                                                {{ ctx.est_cxp || 'activo' }}
                                            </span>

                                            <span class="text-slate-400 font-medium ml-1">
                                                Fecha: {{ ctx.fec_cxp || 'Sin fecha' }}
                                            </span>
                                        </div>

                                        <!-- Banner interno de Advertencia para Antecedentes Críticos -->
                                        <div 
                                            v-if="contextoEsCritico(ctx)" 
                                            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800 flex items-start gap-2 shadow-xs"
                                        >
                                            <span class="flex h-2 w-2 rounded-full bg-rose-500 mt-1.5 shrink-0 animate-ping"></span>
                                            <div>
                                                <span class="font-extrabold">Antecedente Clínico Crítico Activo</span>
                                                <span class="block mt-0.5 text-[11px] text-rose-700 leading-normal">Esta condición médica es prioritaria para el diseño dietoterapéutico. Requiere estricta consideración y control de intolerancias/alergenos en cada validación de porción.</span>
                                            </div>
                                        </div>

                                        <!-- Descripción principal del expediente -->
                                        <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 pr-4">
                                            {{ descripcionContexto(ctx) }}
                                        </p>
                                    </div>

                                    <!-- Acciones Clínicas -->
                                    <div class="flex shrink-0 flex-wrap lg:flex-col gap-2 self-end lg:self-start lg:items-end">
                                        <Link
                                            :href="editUrl(ctx)"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-slate-950 hover:shadow-md transition active:scale-95"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            Editar
                                        </Link>

                                        <button
                                            v-if="ctx.est_cxp === 'activo'"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 hover:text-slate-900 transition active:scale-95 shadow-xs"
                                            @click="inactivar(ctx)"
                                        >
                                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Inactivar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

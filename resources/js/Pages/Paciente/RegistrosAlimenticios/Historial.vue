<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    paciente: Object,
    registros: {
        type: Array,
        default: () => [],
    },
});

const filtroTexto = ref('');
const filtroRevision = ref('todos');

const fechaHoy = computed(() => {
    const ahora = new Date();
    const anio = ahora.getFullYear();
    const mes = String(ahora.getMonth() + 1).padStart(2, '0');
    const dia = String(ahora.getDate()).padStart(2, '0');

    return `${anio}-${mes}-${dia}`;
});

const validacionActual = (registro) => {
    return registro.ultima_validacion_informacion
        || registro.ultimaValidacionInformacion
        || null;
};

const estadoRegistroVisible = (registro) => {
    if (registro.est_ral === 'cerrado') {
        return 'Enviado a revisión';
    }

    return 'Borrador';
};

const estadoRevisionCodigo = (registro) => {
    const validacion = validacionActual(registro);

    if (validacion?.est_vin) {
        return validacion.est_vin;
    }

    if (registro.est_ral === 'cerrado') {
        return 'pendiente_revision';
    }

    return 'borrador';
};

const estadoRevisionVisible = (registro) => {
    const estados = {
        borrador: 'Borrador',
        pendiente_revision: 'Pendiente de revisión',
        aceptado: 'Validado',
        observado: 'Observado',
        rechazado: 'Rechazado',
    };

    return estados[estadoRevisionCodigo(registro)] || 'Sin estado';
};

const claseRegistro = (registro) => {
    if (registro.est_ral === 'cerrado') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    return 'bg-slate-100 text-slate-700 ring-slate-200';
};

const claseRevision = (registro) => {
    const codigo = estadoRevisionCodigo(registro);

    if (codigo === 'aceptado') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (codigo === 'observado') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    if (codigo === 'rechazado') {
        return 'bg-rose-100 text-rose-700 ring-rose-200';
    }

    if (codigo === 'pendiente_revision') {
        return 'bg-blue-100 text-blue-700 ring-blue-200';
    }

    return 'bg-slate-100 text-slate-600 ring-slate-200';
};

const registrosFiltrados = computed(() => {
    const texto = filtroTexto.value.trim().toLowerCase();

    return props.registros.filter((registro) => {
        const coincideTexto = texto === ''
            ? true
            : String(registro.fec_ral ?? '').toLowerCase().includes(texto);

        const coincideRevision = filtroRevision.value === 'todos'
            ? true
            : estadoRevisionCodigo(registro) === filtroRevision.value;

        return coincideTexto && coincideRevision;
    });
});

const totalRegistros = computed(() => props.registros.length);

const totalBorradores = computed(() => {
    return props.registros.filter((registro) => estadoRevisionCodigo(registro) === 'borrador').length;
});

const totalPendientes = computed(() => {
    return props.registros.filter((registro) => estadoRevisionCodigo(registro) === 'pendiente_revision').length;
});

const totalValidados = computed(() => {
    return props.registros.filter((registro) => estadoRevisionCodigo(registro) === 'aceptado').length;
});

const totalObservados = computed(() => {
    return props.registros.filter((registro) => estadoRevisionCodigo(registro) === 'observado').length;
});

const totalRechazados = computed(() => {
    return props.registros.filter((registro) => estadoRevisionCodigo(registro) === 'rechazado').length;
});

const resumenResultados = computed(() => {
    return `Mostrando ${registrosFiltrados.value.length} de ${props.registros.length} días registrados`;
});

const usarFechaHoy = () => {
    filtroTexto.value = fechaHoy.value;
};

const limpiarFiltros = () => {
    filtroTexto.value = '';
    filtroRevision.value = 'todos';
};

const resumenObservacion = (registro) => {
    const observacion = validacionActual(registro)?.obs_vin;

    if (!observacion) {
        return null;
    }

    return observacion.length > 150
        ? `${observacion.slice(0, 150)}...`
        : observacion;
};

const obtenerPartesFecha = (fechaString) => {
    if (!fechaString) return { dia: '--', mes: '---' };
    
    const partes = fechaString.split('-');
    if (partes.length < 3) return { dia: '--', mes: '---' };
    
    const meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    const mesIndex = parseInt(partes[1], 10) - 1;
    const mesNombre = meses[mesIndex] || '---';
    const diaNumero = partes[2];
    
    return {
        dia: diaNumero,
        mes: mesNombre
    };
};
</script>

<template>
    <Head title="Historial de registros" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">
                        Historial de registros
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ paciente.usuario?.nombre_completo }} · {{ paciente.usuario?.cor_usu }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('paciente.registros-alimenticios.create', { paciente: paciente.cod_pacientes })"
                        class="motion-btn rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition"
                    >
                        Nuevo registro
                    </Link>

                    <Link
                        :href="route('paciente.registros-alimenticios.historial-calculos', paciente.cod_pacientes)"
                        class="motion-btn rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition"
                    >
                        Historial de cálculos
                    </Link>
                </div>
            </div>
        </template>

        <div class="auth-shell py-8 bg-slate-50/50 min-h-screen">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- TOP HEADER HERO BANNER -->
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-24 -top-24 w-60 h-60 rounded-full bg-gradient-to-br from-emerald-400/5 to-teal-400/5 blur-3xl pointer-events-none"></div>
                    
                    <div class="grid gap-6 md:grid-cols-[1.5fr_0.5fr] items-center">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600">Bitácora Nutricional</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">Registros Alimenticios Diarios</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500 max-w-xl">
                                Mantén el control de tus ingestas diarias. Revisa tus borradores pendientes, observa las revisiones clínicas de tu nutricionista y añade nuevas ingestas.
                            </p>
                        </div>
                        
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 text-center md:text-right shrink-0">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Usuario en Control</p>
                            <p class="text-sm font-black text-slate-800 truncate mt-0.5">{{ paciente.usuario?.nombre_completo }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ paciente.usuario?.cor_usu }}</p>
                        </div>
                    </div>
                </section>

                <!-- STATS COUNTER WIDGETS -->
                <section class="grid gap-3 grid-cols-2 sm:grid-cols-3 md:grid-cols-6">
                    <!-- Total -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md duration-300">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[9px] font-bold uppercase tracking-wider">Total</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-2xl font-black text-slate-800">{{ totalRegistros }}</p>
                    </div>

                    <!-- Borradores -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md duration-300">
                        <div class="flex items-center justify-between text-slate-400">
                            <span class="text-[9px] font-bold uppercase tracking-wider">Borradores</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-2xl font-black text-slate-600">{{ totalBorradores }}</p>
                    </div>

                    <!-- En Revisión -->
                    <div class="rounded-2xl border border-blue-100 bg-blue-50/10 p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md duration-300">
                        <div class="flex items-center justify-between text-blue-400">
                            <span class="text-[9px] font-bold uppercase tracking-wider">Revisión</span>
                            <svg class="h-4 w-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-2xl font-black text-blue-700">{{ totalPendientes }}</p>
                    </div>

                    <!-- Validados -->
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/10 p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md duration-300">
                        <div class="flex items-center justify-between text-emerald-500">
                            <span class="text-[9px] font-bold uppercase tracking-wider">Validados</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-2xl font-black text-emerald-700">{{ totalValidados }}</p>
                    </div>

                    <!-- Observados -->
                    <div class="rounded-2xl border border-amber-100 bg-amber-50/10 p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md duration-300">
                        <div class="flex items-center justify-between text-amber-500">
                            <span class="text-[9px] font-bold uppercase tracking-wider">Observados</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-2xl font-black text-amber-700">{{ totalObservados }}</p>
                    </div>

                    <!-- Rechazados -->
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/10 p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md duration-300">
                        <div class="flex items-center justify-between text-rose-400">
                            <span class="text-[9px] font-bold uppercase tracking-wider">Rechazados</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-2xl font-black text-rose-700">{{ totalRechazados }}</p>
                    </div>
                </section>

                <!-- FILTERS BLOCK -->
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-3 flex-1 min-w-0">
                            
                            <!-- Search by Date -->
                            <div class="relative flex-1 min-w-[200px]">
                                <span class="absolute left-3.5 top-3 text-slate-400">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <input
                                    v-model="filtroTexto"
                                    type="text"
                                    placeholder="Buscar fecha (ej: 2026-06-08)"
                                    class="pl-10 block w-full rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition py-2"
                                />
                            </div>

                            <!-- Filter by status -->
                            <div class="relative min-w-[180px]">
                                <select
                                    v-model="filtroRevision"
                                    class="block w-full rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition py-2 pl-3 pr-8"
                                >
                                    <option value="todos">Todos los estados</option>
                                    <option value="borrador">Borradores</option>
                                    <option value="pendiente_revision">Pendientes de revisión</option>
                                    <option value="aceptado">Validados</option>
                                    <option value="observado">Observados</option>
                                    <option value="rechazado">Rechazados</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                class="motion-btn rounded-xl border border-slate-250 bg-white hover:bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600 transition"
                                @click="usarFechaHoy"
                            >
                                Ver hoy
                            </button>
                            <button
                                type="button"
                                class="motion-btn rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition"
                                @click="limpiarFiltros"
                            >
                                Limpiar filtros
                            </button>
                        </div>
                    </div>
                </section>

                <!-- JOURNAL LOGS DIARY LIST -->
                <section class="space-y-4">
                    
                    <!-- Empty States -->
                    <div
                        v-if="registros.length === 0"
                        class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/30 p-12 text-center text-sm text-slate-500 space-y-4"
                    >
                        <svg class="h-12 w-12 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="font-semibold text-slate-700">Aún no tienes registros alimenticios</p>
                        <p class="text-xs text-slate-400">Comienza a registrar tu nutrición diaria para tener un seguimiento personalizado.</p>
                        <Link
                            :href="route('paciente.registros-alimenticios.create', { paciente: paciente.cod_pacientes })"
                            class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition"
                        >
                            Crear primer registro
                        </Link>
                    </div>

                    <div
                        v-else-if="registrosFiltrados.length === 0"
                        class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/30 p-12 text-center text-sm text-slate-500 space-y-2"
                    >
                        <p class="font-semibold text-slate-700">No se encontraron resultados</p>
                        <p class="text-xs text-slate-400">Intenta modificando la fecha o el estado en los filtros superiores.</p>
                        <button
                            type="button"
                            @click="limpiarFiltros"
                            class="mt-2 motion-btn rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition"
                        >
                            Restaurar filtros
                        </button>
                    </div>

                    <!-- List rendering -->
                    <div v-else class="space-y-4">
                        <article
                            v-for="registro in registrosFiltrados"
                            :key="registro.cod_registros_alimenticios"
                            class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md duration-300 flex flex-col md:flex-row gap-5 items-start md:items-center justify-between"
                        >
                            
                            <!-- Left: Calendar ticket date block -->
                            <div class="flex items-center gap-4 w-full md:w-auto">
                                <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-150 flex flex-col items-center justify-center shrink-0 shadow-inner">
                                    <span class="text-[10px] font-black text-slate-400 tracking-wider">
                                        {{ obtenerPartesFecha(registro.fec_ral).mes }}
                                    </span>
                                    <span class="text-2xl font-black text-slate-800 leading-none mt-0.5">
                                        {{ obtenerPartesFecha(registro.fec_ral).dia }}
                                    </span>
                                </div>
                                
                                <div class="min-w-0 flex-1">
                                    <!-- Badges of state -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded-full px-2.5 py-0.5 text-[9px] font-bold border"
                                            :class="claseRegistro(registro)"
                                        >
                                            {{ estadoRegistroVisible(registro) }}
                                        </span>

                                        <span
                                            class="rounded-full px-2.5 py-0.5 text-[9px] font-bold border"
                                            :class="claseRevision(registro)"
                                        >
                                            {{ estadoRevisionVisible(registro) }}
                                        </span>
                                    </div>

                                    <h4 class="mt-2 text-base font-bold text-slate-800 leading-snug">
                                        Registro Diario Alimenticio
                                    </h4>

                                    <!-- Quick facts metadata -->
                                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-400 font-semibold">
                                        <span class="flex items-center gap-1">
                                            <strong>ID:</strong> #{{ registro.cod_registros_alimenticios }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <strong>Alimentos:</strong> {{ registro.detalles_count }} ingesta{{ registro.detalles_count === 1 ? '' : 's' }}
                                        </span>
                                        <span v-if="validacionActual(registro)?.fec_vin" class="flex items-center gap-1">
                                            <strong>Revisado:</strong> {{ validacionActual(registro).fec_vin }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Clinical feedback inline block or Action buttons -->
                            <div class="w-full md:w-auto flex flex-col md:items-end gap-3 shrink-0">
                                
                                <!-- Clinical Feedback preview block if present -->
                                <div
                                    v-if="resumenObservacion(registro)"
                                    class="rounded-2xl border border-amber-100 bg-amber-50/45 p-3.5 text-xs text-slate-600 leading-relaxed font-semibold max-w-sm"
                                >
                                    <div class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider text-amber-700 mb-1">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                        </svg>
                                        Retroalimentación Clínica
                                    </div>
                                    <p class="whitespace-pre-line text-slate-700 italic">"{{ resumenObservacion(registro) }}"</p>
                                </div>

                                <!-- Action Buttons links -->
                                <div class="flex items-center gap-2 w-full justify-end md:w-auto">
                                    <Link
                                        v-if="registro.est_ral === 'activo' && !validacionActual(registro)"
                                        :href="route('paciente.registros-alimenticios.create', { paciente: paciente.cod_pacientes, fecha: registro.fec_ral })"
                                        class="motion-btn rounded-xl bg-orange-550 hover:bg-orange-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition"
                                    >
                                        Continuar borrador
                                    </Link>

                                    <Link
                                        :href="route('paciente.registros-alimenticios.show', registro.cod_registros_alimenticios)"
                                        class="motion-btn rounded-xl bg-slate-900 hover:bg-slate-850 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition"
                                    >
                                        Ver detalle
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-orange-550 {
    background-color: #ea580c;
}
.hover\:bg-orange-600:hover {
    background-color: #ea580c;
}
</style>
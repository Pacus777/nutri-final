<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    asignaciones: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const esRolNutricionista = computed(() => (page.props.auth?.rol_activo ?? '') === 'Nutricionista');
const busqueda = ref('');
const filtroEstado = ref('todos');
const vistaActiva = ref('tarjetas'); // 'tarjetas' | 'tabla'

const resumen = computed(() => ({
    total: props.asignaciones.length,
    activas: props.asignaciones.filter((asignacion) => asignacion.est_anp === 'activa').length,
    cerradas: props.asignaciones.filter((asignacion) => asignacion.est_anp === 'cerrada').length,
    reasignadas: props.asignaciones.filter((asignacion) => asignacion.est_anp === 'reasignada').length,
}));

const asignacionesFiltradas = computed(() => {
    const termino = busqueda.value.trim().toLowerCase();

    return props.asignaciones.filter((asignacion) => {
        const estado = String(asignacion.est_anp ?? '').toLowerCase();

        if (filtroEstado.value !== 'todos' && estado !== filtroEstado.value) {
            return false;
        }

        if (!termino) {
            return true;
        }

        const codigo = String(asignacion.cod_asignacion_nutricionista_paciente ?? '');
        const fecha = String(asignacion.fec_anp ?? '').toLowerCase();
        const pacienteNombre = String(asignacion.paciente?.usuario?.nom_usu ?? '').toLowerCase();
        const pacienteCorreo = String(asignacion.paciente?.usuario?.cor_usu ?? '').toLowerCase();
        const nutricionistaNombre = String(asignacion.nutricionista?.usuario?.nom_usu ?? '').toLowerCase();
        const nutricionistaCorreo = String(asignacion.nutricionista?.usuario?.cor_usu ?? '').toLowerCase();

        return [
            codigo,
            fecha,
            estado,
            pacienteNombre,
            pacienteCorreo,
            nutricionistaNombre,
            nutricionistaCorreo,
        ].some((valor) => valor.includes(termino));
    });
});

const tieneFiltrosActivos = computed(() => busqueda.value !== '' || filtroEstado.value !== 'todos');

const limpiarFiltros = () => {
    busqueda.value = '';
    filtroEstado.value = 'todos';
};

const claseEstado = (estado) => {
    if (estado === 'activa') {
        return 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 border border-emerald-200';
    }

    if (estado === 'cerrada') {
        return 'bg-slate-50 text-slate-700 ring-1 ring-inset ring-slate-600/20 border border-slate-200';
    }

    return 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 border border-amber-200';
};

const actualizarEstado = (codAsignacion, estado) => {
    router.patch(route('asignaciones.actualizar-estado', codAsignacion), {
        est_anp: estado,
    });
};
</script>

<template>
    <Head title="Asignaciones Clínicas" />

    <AuthenticatedLayout>
        <!-- Premium Header with clean medical design -->
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                            Coordinación
                        </span>
                        <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-600/10">
                            Vinculaciones Clínicas
                        </span>
                    </div>
                    <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 leading-none">
                        Asignaciones
                    </h2>
                    <p class="mt-2 text-sm text-slate-500 font-medium">
                        Monitoreo de relaciones terapéuticas entre el equipo de nutrición y pacientes de la institución.
                    </p>
                </div>

                <Link
                    :href="route('asignaciones.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Asignación
                </Link>
            </div>
        </template>

        <div class="py-8 bg-slate-50/30 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Flash Notification Messages -->
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-700 shadow-sm flex items-center gap-2"
                >
                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ page.props.flash.success }}
                </div>

                <!-- Sleek Metric/Summary Cards -->
                <section class="grid gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Card 1: Total -->
                    <article class="relative overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md group">
                        <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-slate-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Histórico</span>
                            <div class="rounded-xl bg-slate-50 p-2.5 text-slate-500 transition-colors group-hover:bg-slate-600 group-hover:text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-4">
                            <p class="text-4xl font-black tracking-tight text-slate-900">{{ resumen.total }}</p>
                            <p class="mt-2 text-xs font-semibold text-slate-500">Asignaciones totales generadas</p>
                        </div>
                    </article>

                    <!-- Card 2: Activas -->
                    <article class="relative overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md group">
                        <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-emerald-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Vinculaciones Activas</span>
                            <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-4">
                            <p class="text-4xl font-black tracking-tight text-emerald-600">{{ resumen.activas }}</p>
                            <p class="mt-2 text-xs font-semibold text-slate-500">Pacientes con terapeuta activo</p>
                        </div>
                    </article>

                    <!-- Card 3: Cerradas -->
                    <article class="relative overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md group">
                        <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-slate-100/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Casos Finalizados</span>
                            <div class="rounded-xl bg-slate-100 p-2.5 text-slate-600 transition-colors group-hover:bg-slate-700 group-hover:text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-4">
                            <p class="text-4xl font-black tracking-tight text-slate-600">{{ resumen.cerradas }}</p>
                            <p class="mt-2 text-xs font-semibold text-slate-500">Ciclos de tratamiento concluidos</p>
                        </div>
                    </article>

                    <!-- Card 4: Reasignadas -->
                    <article class="relative overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md group">
                        <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full bg-amber-50/50 transition-all duration-500 group-hover:scale-[3.5]"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Derivaciones</span>
                            <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-4">
                            <p class="text-4xl font-black tracking-tight text-amber-600">{{ resumen.reasignadas }}</p>
                            <p class="mt-2 text-xs font-semibold text-slate-500">Pacientes derivados a otro profesional</p>
                        </div>
                    </article>
                </section>

                <!-- High-Performance Filter & View Control Panel -->
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Buscar Asignación</label>
                            <div class="mt-2 relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="busqueda"
                                    type="text"
                                    placeholder="Buscar por código, fecha, paciente o nutricionista..."
                                    class="pl-10 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 placeholder-slate-400"
                                >
                            </div>
                        </div>

                        <div class="min-w-[180px]">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Estado</label>
                            <select v-model="filtroEstado" class="app-select mt-2">
                                <option value="todos">Todos los estados</option>
                                <option value="activa">Activa</option>
                                <option value="cerrada">Cerrada</option>
                                <option value="reasignada">Reasignada</option>
                            </select>
                        </div>

                        <!-- Grid/Table layout toggler (Peak SaaS UX!) -->
                        <div class="shrink-0">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Diseño</label>
                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 mt-2 h-[44px] justify-center">
                                <button
                                    @click="vistaActiva = 'tarjetas'"
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg transition duration-150 cursor-pointer"
                                    :class="vistaActiva === 'tarjetas' ? 'bg-white text-emerald-700 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'"
                                    title="Vista de Tarjetas Relacionales"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                </button>
                                <button
                                    @click="vistaActiva = 'tabla'"
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg transition duration-150 cursor-pointer"
                                    :class="vistaActiva === 'tabla' ? 'bg-white text-emerald-700 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'"
                                    title="Vista de Tabla Compacta Escalable"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button
                            v-if="tieneFiltrosActivos"
                            @click="limpiarFiltros"
                            type="button"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 cursor-pointer h-[44px] shrink-0"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                            </svg>
                            Limpiar
                        </button>
                    </div>
                </section>

                <!-- Empty State -->
                <div v-if="asignacionesFiltradas.length === 0" class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/50 p-12 text-center flex flex-col items-center justify-center">
                    <div class="rounded-full bg-slate-100 p-4 text-slate-400 mb-3">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-900">Sin asignaciones clínicas encontradas</p>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">No pudimos encontrar registros de vinculación con los filtros y términos de búsqueda provistos.</p>
                </div>

                <!-- Layout 1: Relational Grid Cards View -->
                <div v-else-if="vistaActiva === 'tarjetas'" class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="asignacion in asignacionesFiltradas"
                        :key="asignacion.cod_asignacion_nutricionista_paciente"
                        class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md flex flex-col justify-between group"
                    >
                        <!-- Card Header: ID, Date and Status -->
                        <div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-0.5 text-xs font-extrabold text-slate-700 ring-1 ring-inset ring-slate-600/10">
                                        Asignación #{{ asignacion.cod_asignacion_nutricionista_paciente }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ asignacion.fec_anp }}
                                    </span>
                                </div>

                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-extrabold uppercase tracking-wide border"
                                    :class="claseEstado(asignacion.est_anp)"
                                >
                                    {{ asignacion.est_anp }}
                                </span>
                            </div>

                            <!-- Relationship Visual Connection Flow -->
                            <div class="mt-6 flex items-center justify-between gap-2">
                                
                                <!-- Paciente Info avatar -->
                                <div class="flex flex-col items-center text-center max-w-[45%]">
                                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-violet-50 to-indigo-100 border border-violet-200 text-violet-800 font-black text-sm flex items-center justify-center shadow-inner group-hover:scale-105 transition-all duration-300">
                                        {{ asignacion.paciente?.usuario?.nom_usu ? asignacion.paciente.usuario.nom_usu.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase() : 'P' }}
                                    </div>
                                    <h4 class="mt-2.5 text-xs font-black text-slate-900 truncate w-full" :title="asignacion.paciente?.usuario?.nom_usu">
                                        {{ asignacion.paciente?.usuario?.nom_usu ?? 'Paciente' }}
                                    </h4>
                                    <p class="text-[9px] text-slate-400 font-bold truncate w-full" :title="asignacion.paciente?.usuario?.cor_usu">
                                        {{ asignacion.paciente?.usuario?.cor_usu ?? 'Sin correo' }}
                                    </p>
                                    <span class="mt-1.5 inline-flex rounded-md bg-violet-50 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wide text-violet-700 ring-1 ring-inset ring-violet-700/10">
                                        Paciente
                                    </span>
                                </div>

                                <!-- Middle connector design -->
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="relative w-full flex items-center justify-center">
                                        <span class="absolute h-0.5 w-full bg-dashed border-t border-slate-200"></span>
                                        <span class="relative z-10 h-2.5 w-2.5 rounded-full" :class="{
                                            'bg-emerald-500 shadow-lg shadow-emerald-500/50 animate-pulse': asignacion.est_anp === 'activa',
                                            'bg-slate-400': asignacion.est_anp === 'cerrada',
                                            'bg-amber-500 shadow-lg shadow-amber-500/50': asignacion.est_anp === 'reasignada'
                                        }"></span>
                                    </div>
                                    <span class="mt-2 text-[8px] font-extrabold uppercase tracking-widest text-slate-400 select-none">
                                        Vínculo
                                    </span>
                                </div>

                                <!-- Nutricionista Info avatar -->
                                <div class="flex flex-col items-center text-center max-w-[45%]">
                                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 text-emerald-800 font-black text-sm flex items-center justify-center shadow-inner group-hover:scale-105 transition-all duration-300">
                                        {{ asignacion.nutricionista?.usuario?.nom_usu ? asignacion.nutricionista.usuario.nom_usu.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase() : 'N' }}
                                    </div>
                                    <h4 class="mt-2.5 text-xs font-black text-slate-900 truncate w-full" :title="asignacion.nutricionista?.usuario?.nom_usu">
                                        Dr. {{ asignacion.nutricionista?.usuario?.nom_usu ?? 'Profesional' }}
                                    </h4>
                                    <p class="text-[9px] text-slate-400 font-bold truncate w-full" :title="asignacion.nutricionista?.usuario?.cor_usu">
                                        {{ asignacion.nutricionista?.usuario?.cor_usu ?? 'Sin correo' }}
                                    </p>
                                    <span class="mt-1.5 inline-flex rounded-md bg-emerald-50 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-700/10">
                                        Nutricionista
                                    </span>
                                </div>

                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <Link
                                    v-if="asignacion.paciente?.cod_pacientes"
                                    :href="route('asignaciones.historial-paciente', asignacion.paciente.cod_pacientes)"
                                    class="inline-flex items-center justify-center rounded-xl bg-slate-50 hover:bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 border border-slate-200 transition-colors"
                                    title="Ver Historial Clínico del Paciente"
                                >
                                    Historial
                                </Link>

                                <Link
                                    v-if="esRolNutricionista && asignacion.paciente?.cod_pacientes"
                                    :href="route('nutricionista.seguimientos.historial', asignacion.paciente.cod_pacientes)"
                                    class="inline-flex items-center justify-center rounded-xl bg-slate-50 hover:bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 border border-slate-200 transition-colors"
                                    title="Ver Seguimientos y Validaciones"
                                >
                                    Seguimientos
                                </Link>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button
                                    v-if="asignacion.est_anp === 'activa'"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition-colors cursor-pointer"
                                    @click="actualizarEstado(asignacion.cod_asignacion_nutricionista_paciente, 'cerrada')"
                                >
                                    Cerrar Caso
                                </button>

                                <button
                                    v-if="asignacion.est_anp === 'activa'"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700 transition-colors cursor-pointer"
                                    @click="actualizarEstado(asignacion.cod_asignacion_nutricionista_paciente, 'reasignada')"
                                >
                                    Derivar
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Layout 2: Compact High-Volume Table View (Escalable & Scan-friendly!) -->
                <div v-else class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-400">
                                    <th class="py-4 px-6 select-none">Código</th>
                                    <th class="py-4 px-6 select-none">Paciente</th>
                                    <th class="py-4 px-6 text-center select-none">Vínculo</th>
                                    <th class="py-4 px-6 select-none">Nutricionista</th>
                                    <th class="py-4 px-6 select-none">Estado</th>
                                    <th class="py-4 px-6 text-right select-none">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr 
                                    v-for="asignacion in asignacionesFiltradas" 
                                    :key="asignacion.cod_asignacion_nutricionista_paciente"
                                    class="hover:bg-slate-50/50 transition duration-150 group"
                                >
                                    <!-- ID & Date -->
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-extrabold text-slate-800">
                                            #{{ asignacion.cod_asignacion_nutricionista_paciente }}
                                        </span>
                                        <div class="mt-1 text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ asignacion.fec_anp }}
                                        </div>
                                    </td>
                                    
                                    <!-- Paciente -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-violet-50 to-indigo-100 border border-violet-200 text-violet-800 font-extrabold text-xs flex items-center justify-center shrink-0">
                                                {{ asignacion.paciente?.usuario?.nom_usu ? asignacion.paciente.usuario.nom_usu.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() : 'P' }}
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-black text-slate-900 leading-none truncate max-w-[150px]" :title="asignacion.paciente?.usuario?.nom_usu">
                                                    {{ asignacion.paciente?.usuario?.nom_usu ?? 'Paciente' }}
                                                </h4>
                                                <p class="text-[10px] text-slate-400 font-semibold mt-1 truncate max-w-[150px]" :title="asignacion.paciente?.usuario?.cor_usu">
                                                    {{ asignacion.paciente?.usuario?.cor_usu ?? 'Sin correo' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Connector Visual Icon -->
                                    <td class="py-4 px-6 text-center">
                                        <div class="inline-flex items-center justify-center gap-1 text-[9px] font-bold text-slate-400 select-none bg-slate-50 border border-slate-200/60 rounded-full px-2.5 py-1">
                                            <span class="h-1.5 w-1.5 rounded-full" :class="{
                                                'bg-emerald-500 animate-pulse': asignacion.est_anp === 'activa',
                                                'bg-slate-400': asignacion.est_anp === 'cerrada',
                                                'bg-amber-500': asignacion.est_anp === 'reasignada'
                                            }"></span>
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </div>
                                    </td>

                                    <!-- Nutricionista -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 text-emerald-800 font-extrabold text-xs flex items-center justify-center shrink-0">
                                                {{ asignacion.nutricionista?.usuario?.nom_usu ? asignacion.nutricionista.usuario.nom_usu.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() : 'N' }}
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-black text-slate-900 leading-none truncate max-w-[150px]" :title="asignacion.nutricionista?.usuario?.nom_usu">
                                                    Dr. {{ asignacion.nutricionista?.usuario?.nom_usu ?? 'Profesional' }}
                                                </h4>
                                                <p class="text-[10px] text-slate-400 font-semibold mt-1 truncate max-w-[150px]" :title="asignacion.nutricionista?.usuario?.cor_usu">
                                                    {{ asignacion.nutricionista?.usuario?.cor_usu ?? 'Sin correo' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Estado badge -->
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide border" :class="claseEstado(asignacion.est_anp)">
                                            {{ asignacion.est_anp }}
                                        </span>
                                    </td>

                                    <!-- Actions compacted -->
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Navigation links -->
                                            <Link
                                                v-if="asignacion.paciente?.cod_pacientes"
                                                :href="route('asignaciones.historial-paciente', asignacion.paciente.cod_pacientes)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 transition-colors"
                                                title="Historial de Asignaciones"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                            </Link>
                                            <Link
                                                v-if="esRolNutricionista && asignacion.paciente?.cod_pacientes"
                                                :href="route('nutricionista.seguimientos.historial', asignacion.paciente.cod_pacientes)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 transition-colors"
                                                title="Seguimientos Clínicos"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </Link>

                                            <!-- Divider -->
                                            <div v-if="asignacion.est_anp === 'activa'" class="h-4 w-px bg-slate-200 mx-1"></div>

                                            <!-- Direct action buttons -->
                                            <button
                                                v-if="asignacion.est_anp === 'activa'"
                                                @click="actualizarEstado(asignacion.cod_asignacion_nutricionista_paciente, 'cerrada')"
                                                type="button"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 px-2.5 py-1.5 text-[10px] font-bold text-slate-700 transition-colors cursor-pointer"
                                                title="Cerrar vinculación terapéutica"
                                            >
                                                Cerrar
                                            </button>
                                            <button
                                                v-if="asignacion.est_anp === 'activa'"
                                                @click="actualizarEstado(asignacion.cod_asignacion_nutricionista_paciente, 'reasignada')"
                                                type="button"
                                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50/50 hover:bg-rose-100 px-2.5 py-1.5 text-[10px] font-bold text-rose-700 transition-colors cursor-pointer"
                                                title="Derivar paciente a otro nutricionista"
                                            >
                                                Derivar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Errors handling -->
                <div
                    v-if="page.props.errors?.general"
                    class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3.5 text-sm text-red-700 shadow-sm flex items-center gap-2"
                >
                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    {{ page.props.errors.general }}
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

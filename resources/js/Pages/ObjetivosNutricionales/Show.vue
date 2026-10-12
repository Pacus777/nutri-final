<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    objetivo: Object,
    modo: {
        type: String,
        default: 'admin',
    },
    editarUrl: {
        type: String,
        default: '',
    },
    volverUrl: {
        type: String,
        default: '',
    },
    rutasEstado: {
        type: Object,
        default: () => ({}),
    },
    ajustesUrl: {
        type: String,
        default: '',
    },
    ajusteCrearUrl: {
        type: String,
        default: '',
    },
});

const esNutricionista = computed(() => props.modo === 'nutricionista');

const objetivoId = computed(() => props.objetivo?.cod_objetivos_nutricionales);
const estado = computed(() => props.objetivo?.est_obe || 'activo');

const editar = computed(() => {
    return props.editarUrl || route('objetivos-nutricionales.edit', objetivoId.value);
});

const volver = computed(() => {
    return props.volverUrl || route('dashboard');
});

const puedeEditar = computed(() => {
    return ['activo', 'pausado'].includes(estado.value);
});

const puedePausar = computed(() => estado.value === 'activo');
const puedeReactivar = computed(() => estado.value === 'pausado');
const puedeFinalizar = computed(() => ['activo', 'pausado'].includes(estado.value));

const puedeRegistrarAjuste = computed(() => {
    return ['activo', 'pausado'].includes(estado.value) && Boolean(props.ajusteCrearUrl);
});

const chipClase = (estadoObjetivo) => {
    if (estadoObjetivo === 'activo') return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    if (estadoObjetivo === 'cumplido') return 'bg-cyan-100 text-cyan-700 ring-cyan-200';
    if (estadoObjetivo === 'pausado') return 'bg-amber-100 text-amber-700 ring-amber-200';
    if (estadoObjetivo === 'cerrado') return 'bg-slate-200 text-slate-700 ring-slate-300';

    return 'bg-rose-100 text-rose-700 ring-rose-200';
};

const patchEstado = (url, mensajeConfirmacion = null) => {
    if (!url) return;

    if (mensajeConfirmacion && !confirm(mensajeConfirmacion)) {
        return;
    }

    router.patch(url, {}, {
        preserveScroll: true,
    });
};

const rutaEstado = (key) => {
    return props.rutasEstado?.[key] || null;
};
</script>

<template>
    <Head title="Detalle de objetivo" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Seguimiento clínico"
                title="Expediente del objetivo"
                description="Revisión detallada de la meta asignada, control de vigencia, historial clínico y auditoría de ajustes nutricionales."
            >
                <template #actions>
                    <Link
                        :href="volver"
                        class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="h-4 w-4 text-slate-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                        Volver
                    </Link>

                    <Link
                        v-if="puedeEditar"
                        :href="editar"
                        class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-700 shadow-sm transition duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                        Editar Meta
                    </Link>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="py-8 animate-fade-in">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                
                <!-- DASHBOARD LAYOUT DUAL -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- PANEL PRINCIPAL IZQUIERDO (7/12) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Core Objective Card -->
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-5">
                            <div class="flex items-start justify-between border-b pb-3">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Objetivo clínico</p>
                                    <h3 class="text-base font-black text-slate-900 mt-1">Definición de la Meta</h3>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                    :class="chipClase(objetivo.est_obe)"
                                >
                                    {{ objetivo.est_obe }}
                                </span>
                            </div>

                            <!-- Big quotation display -->
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 relative overflow-hidden">
                                <div class="absolute -right-2 -bottom-6 text-slate-200/50 font-black text-8xl select-none pointer-events-none">”</div>
                                <p class="text-sm font-semibold text-slate-700 leading-relaxed whitespace-pre-line relative z-10">
                                    {{ objetivo.des_obe }}
                                </p>
                            </div>

                            <div class="grid gap-3 grid-cols-1 sm:grid-cols-3 text-xs font-semibold text-slate-600 leading-relaxed">
                                <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3 flex flex-col justify-between">
                                    <span class="text-slate-400 text-[9px] font-black uppercase tracking-wider">Paciente</span>
                                    <span class="text-slate-950 font-black mt-1 truncate">
                                        {{ objetivo.paciente?.usuario?.nombre_completo || 'Objetivo global' }}
                                    </span>
                                </div>
                                <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3 flex flex-col justify-between">
                                    <span class="text-slate-400 text-[9px] font-black uppercase tracking-wider">Registrado Por</span>
                                    <span class="text-slate-950 font-black mt-1 truncate">
                                        {{ objetivo.nutricionista?.usuario?.nombre_completo || 'Sin creador' }}
                                    </span>
                                </div>
                                <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3 flex flex-col justify-between">
                                    <span class="text-slate-400 text-[9px] font-black uppercase tracking-wider">Fecha Registro</span>
                                    <span class="text-slate-950 font-black mt-1">
                                        {{ objetivo.fec_obe }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones del Plan Clínico -->
                        <div v-if="puedeFinalizar || puedePausar || puedeReactivar" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-4">
                            <h3 class="text-base font-black text-slate-900 border-b pb-2">Acciones del Plan Clínico</h3>
                            <p class="text-xs text-slate-500 font-medium leading-relaxed">Operaciones sobre el estado del objetivo. El cierre o la cancelación son definitivos.</p>
                            
                            <div class="flex flex-wrap gap-2.5 pt-2">
                                <!-- Marcar Cumplido -->
                                <button
                                    v-if="puedeFinalizar && rutaEstado('cumplido')"
                                    type="button"
                                    class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition duration-200 hover:-translate-y-0.5"
                                    @click="patchEstado(rutaEstado('cumplido'), '¿Marcar este objetivo como cumplido?')"
                                >
                                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Marcar Cumplido
                                </button>

                                <!-- Pausar -->
                                <button
                                    v-if="puedePausar && rutaEstado('pausar')"
                                    type="button"
                                    class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition duration-200 hover:-translate-y-0.5"
                                    @click="patchEstado(rutaEstado('pausar'))"
                                >
                                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                    </svg>
                                    Pausar Meta
                                </button>

                                <!-- Reactivar -->
                                <button
                                    v-if="puedeReactivar && rutaEstado('reactivar')"
                                    type="button"
                                    class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition duration-200 hover:-translate-y-0.5"
                                    @click="patchEstado(rutaEstado('reactivar'))"
                                >
                                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                    </svg>
                                    Reactivar Meta
                                </button>

                                <!-- Cerrar -->
                                <button
                                    v-if="puedeFinalizar && rutaEstado('cerrar')"
                                    type="button"
                                    class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition duration-200 hover:-translate-y-0.5"
                                    @click="patchEstado(rutaEstado('cerrar'), '¿Cerrar este objetivo? Luego no podrá editarse.')"
                                >
                                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    Cerrar Objetivo
                                </button>

                                <!-- Cancelar -->
                                <button
                                    v-if="puedeFinalizar && rutaEstado('cancelar')"
                                    type="button"
                                    class="motion-btn inline-flex items-center gap-1.5 rounded-xl bg-white border border-rose-200 hover:bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-700 shadow-sm transition duration-200 hover:-translate-y-0.5"
                                    @click="patchEstado(rutaEstado('cancelar'), '¿Cancelar este objetivo nutricional?')"
                                >
                                    <svg class="h-4 w-4 shrink-0 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL DE AUDITORÍA Y TIMELINE DERECHO (5/12) -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- Timeline Box -->
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100/80 space-y-5">
                            <div class="flex items-center justify-between border-b pb-2">
                                <h3 class="text-base font-black text-slate-900">Historial y Auditoría</h3>
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-600 ring-1 ring-slate-200">
                                    {{ objetivo.ajustes?.length || 0 }} cambios
                                </span>
                            </div>

                            <!-- Vertical Clinical Timeline -->
                            <div v-if="objetivo.ajustes?.length" class="flow-root relative pl-4 mt-2">
                                <div class="absolute left-[9px] top-2 bottom-2 w-0.5 bg-slate-100"></div>

                                <ul role="list" class="-mb-8">
                                    <li v-for="(ajuste, index) in objetivo.ajustes" :key="ajuste.cod_ajustes_objetivos_nutricionales" class="pb-8 relative">
                                        <div class="relative flex space-x-3.5">
                                            <div>
                                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white ring-4 ring-slate-100 border border-slate-300">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 space-y-2">
                                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider text-slate-400">
                                                    <span>{{ ajuste.fec_ajn }}</span>
                                                    <Link
                                                        :href="route('ajustes-objetivos-nutricionales.show', ajuste.cod_ajustes_objetivos_nutricionales)"
                                                        class="text-violet-600 hover:text-violet-800 transition"
                                                    >
                                                        Detalle ➔
                                                    </Link>
                                                </div>
                                                <div class="bg-slate-50/50 rounded-2xl border border-slate-100/70 p-3.5 space-y-2 shadow-sm">
                                                    <p class="text-xs text-slate-700 leading-relaxed font-semibold">
                                                        {{ ajuste.des_ajn }}
                                                    </p>
                                                    <div class="flex flex-wrap items-center justify-between gap-2 border-t pt-1.5 mt-1.5 text-[9px] font-semibold text-slate-500">
                                                        <span>Por: {{ ajuste.nutricionista?.usuario?.nombre_completo || 'Nutricionista' }}</span>
                                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500 bg-slate-100 rounded-md px-1.5 py-0.5">
                                                            {{ ajuste.estado_anterior_ajn }} ➔ {{ ajuste.estado_nuevo_ajn }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="text-center py-8 border border-dashed border-slate-200 rounded-2xl bg-slate-50/20 space-y-3">
                                <svg class="mx-auto h-8 w-8 text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <p class="text-xs text-slate-400 font-bold">
                                    Este objetivo se mantiene original.
                                </p>
                                <p class="text-[10px] text-slate-400 max-w-[200px] mx-auto leading-relaxed font-semibold">
                                    No hay modificaciones ni ajustes clínicos registrados desde su creación.
                                </p>
                            </div>
                        </div>

                        <!-- Registrar Ajuste Rápido -->
                        <ClinicalAlert
                            v-if="puedeRegistrarAjuste"
                            title="Ajuste de Meta Recomendado"
                            tone="warning"
                        >
                            <div class="space-y-3 text-xs leading-relaxed font-semibold text-slate-700">
                                <p>
                                    ¿El paciente requiere modificar su meta o reprogramar sus métricas? Registra una justificación clínica de ajuste.
                                </p>
                                <Link
                                    :href="ajusteCrearUrl"
                                    class="inline-flex rounded-xl bg-violet-50 hover:bg-violet-100 border border-violet-200 px-3 py-1.5 text-center text-[10px] font-black uppercase tracking-wider text-violet-800 transition"
                                >
                                    Registrar Ajuste Clínico
                                </Link>
                            </div>
                        </ClinicalAlert>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
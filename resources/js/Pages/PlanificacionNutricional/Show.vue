<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    paciente: Object,
    nutricionista_activo: Object,
    evaluacion_inicial_activa: Object,
    contextos: {
        type: Array,
        default: () => [],
    },
    objetivos_activos: {
        type: Array,
        default: () => [],
    },
    objetivos_historial: {
        type: Array,
        default: () => [],
    },
    plan_vigente: Object,
    planes_historial: {
        type: Array,
        default: () => [],
    },
});

const nombreCompleto = (usuario) => {
    if (!usuario) {
        return 'No disponible';
    }

    return usuario.nombre_completo || `${usuario.nom_usu ?? ''} ${usuario.ape_usu ?? ''}`.trim() || 'No disponible';
};

const chipClase = (estado) => {
    if (['activo', 'vigente'].includes(estado)) {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (['pausado', 'inactivo'].includes(estado)) {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    if (['cerrada', 'finalizado', 'reemplazado', 'cancelado'].includes(estado)) {
        return 'bg-rose-100 text-rose-700 ring-rose-200';
    }

    return 'bg-slate-100 text-slate-700 ring-slate-200';
};
</script>

<template>
    <Head title="Planificación nutricional" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Planificación nutricional integrada
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Resumen clínico completo del paciente y accesos de trabajo por módulo.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link :href="route('evaluaciones-iniciales.create', { paciente: paciente.cod_pacientes })" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Nueva evaluación</Link>
                    <Link :href="route('contextos-paciente.create', { paciente: paciente.cod_pacientes })" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Nuevo contexto</Link>
                    <Link :href="route('objetivos-nutricionales.create', { paciente: paciente.cod_pacientes })" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Nuevo objetivo</Link>
                    <Link :href="route('planes-nutricionales.create', { paciente: paciente.cod_pacientes })" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700">Nuevo plan</Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 p-6 text-white shadow-2xl sm:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-300">Paciente</p>
                            <h3 class="mt-2 text-3xl font-black tracking-tight">{{ nombreCompleto(paciente.usuario) }}</h3>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">
                                Consolida contextos, evaluación inicial, objetivos y planes para una lectura clínica unificada.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Estado paciente</p>
                                <p class="mt-2 text-lg font-semibold capitalize">{{ paciente.est_pac }}</p>
                                <p class="mt-1 text-sm text-slate-300">Edad: {{ paciente.edad ?? 'No disponible' }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Nutricionista activo</p>
                                <p class="mt-2 text-lg font-semibold">{{ nombreCompleto(nutricionista_activo?.usuario) }}</p>
                                <p class="mt-1 text-sm text-slate-300">{{ nutricionista_activo?.esp_nut || 'Sin especialidad' }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">Evaluación inicial</p>
                        <p class="mt-2 text-sm text-slate-600">{{ evaluacion_inicial_activa ? 'Activa' : 'No registrada' }}</p>
                        <p v-if="evaluacion_inicial_activa" class="mt-3 text-sm text-slate-700">IMC: <span class="font-semibold">{{ evaluacion_inicial_activa.imc_evi ?? 'N/D' }}</span></p>
                        <Link v-if="evaluacion_inicial_activa" :href="route('evaluaciones-iniciales.show', evaluacion_inicial_activa.cod_evaluaciones_iniciales)" class="mt-4 inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Ver detalle</Link>
                    </article>

                    <article class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600">Contextos</p>
                        <p class="mt-2 text-3xl font-black text-slate-900">{{ contextos.length }}</p>
                        <p class="mt-1 text-sm text-slate-600">Registros activos e históricos disponibles.</p>
                    </article>

                    <article class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-600">Objetivos activos</p>
                        <p class="mt-2 text-3xl font-black text-slate-900">{{ objetivos_activos.length }}</p>
                        <p class="mt-1 text-sm text-slate-600">Seguimiento de metas vigentes del paciente.</p>
                    </article>

                    <article class="rounded-2xl border border-cyan-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Plan vigente</p>
                        <p class="mt-2 text-sm text-slate-600">{{ plan_vigente ? 'Disponible' : 'Aun no creado' }}</p>
                        <p v-if="plan_vigente" class="mt-2 text-sm text-slate-700">Estado: <span class="font-semibold capitalize">{{ plan_vigente.est_pan }}</span></p>
                    </article>
                </section>

                <section class="grid gap-6 xl:grid-cols-2">
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h3 class="text-base font-semibold text-slate-900">Contextos del paciente</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs uppercase tracking-[0.12em] text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3">Fecha</th>
                                        <th class="px-5 py-3">Tipo</th>
                                        <th class="px-5 py-3">Estado</th>
                                        <th class="px-5 py-3">Descripción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="contexto in contextos" :key="contexto.cod_contextos_paciente">
                                        <td class="px-5 py-4 text-slate-600">{{ contexto.fec_cxp }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ contexto.tip_cxp || 'General' }}</td>
                                        <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold ring-1" :class="chipClase(contexto.est_cxp)">{{ contexto.est_cxp }}</span></td>
                                        <td class="px-5 py-4 text-slate-700">{{ contexto.des_cxp }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h3 class="text-base font-semibold text-slate-900">Evaluación y plan actual</h3>
                        </div>
                        <div class="space-y-4 p-5 text-sm text-slate-700">
                            <div v-if="evaluacion_inicial_activa" class="rounded-xl bg-emerald-50 p-4">
                                <p class="font-semibold text-emerald-700">Evaluación activa</p>
                                <p class="mt-2">Fecha: {{ evaluacion_inicial_activa.fec_evi }}</p>
                                <p>Objetivo inicial: {{ evaluacion_inicial_activa.objetivo_inicial_evi || 'No especificado' }}</p>
                                <p>Restricciones: {{ evaluacion_inicial_activa.restricciones_evi || 'Sin restricciones registradas' }}</p>
                            </div>

                            <div v-if="plan_vigente" class="rounded-xl bg-cyan-50 p-4">
                                <p class="font-semibold text-cyan-700">Plan vigente</p>
                                <p class="mt-2">Fecha: {{ plan_vigente.fec_pan }}</p>
                                <p>Estado: <span class="font-semibold capitalize">{{ plan_vigente.est_pan }}</span></p>
                                <p class="mt-2">{{ plan_vigente.des_pan }}</p>
                            </div>

                            <div v-if="!evaluacion_inicial_activa && !plan_vigente" class="rounded-xl border border-dashed border-slate-300 p-4 text-slate-500">
                                No hay evaluación inicial ni plan vigente registrados.
                            </div>
                        </div>
                    </article>
                </section>

                <section class="grid gap-6 xl:grid-cols-2">
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h3 class="text-base font-semibold text-slate-900">Objetivos activos</h3>
                        </div>
                        <div class="space-y-4 p-5">
                            <div v-for="objetivo in objetivos_activos" :key="objetivo.cod_objetivos_nutricionales" class="rounded-xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ objetivo.des_obe }}</p>
                                        <p class="text-sm text-slate-500">{{ objetivo.fec_obe }} · {{ objetivo.nutricionista?.usuario?.nombre_completo || 'Sin nutricionista' }}</p>
                                    </div>
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold ring-1" :class="chipClase(objetivo.est_obe)">{{ objetivo.est_obe }}</span>
                                </div>
                                <div v-if="objetivo.ajustes?.length" class="mt-4 space-y-2 text-sm text-slate-600">
                                    <p class="font-semibold text-slate-700">Ajustes recientes</p>
                                    <p v-for="ajuste in objetivo.ajustes.slice(0, 2)" :key="ajuste.cod_ajustes_objetivos_nutricionales">
                                        {{ ajuste.fec_ajn }} - {{ ajuste.des_ajn }}
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <Link :href="route('objetivos-nutricionales.show', objetivo.cod_objetivos_nutricionales)" class="text-sm font-semibold text-violet-700 hover:text-violet-900">Ver objetivo</Link>
                                </div>
                            </div>

                            <div v-if="!objetivos_activos.length" class="rounded-xl border border-dashed border-slate-300 p-4 text-slate-500">No hay objetivos activos.</div>
                        </div>
                    </article>

                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h3 class="text-base font-semibold text-slate-900">Planes nutricionales</h3>
                        </div>
                        <div class="space-y-4 p-5">
                            <div v-if="plan_vigente" class="rounded-xl bg-slate-50 p-4">
                                <p class="font-semibold text-slate-900">Plan vigente</p>
                                <p class="mt-2 text-sm text-slate-600">{{ plan_vigente.des_pan }}</p>
                                <Link :href="route('planes-nutricionales.show', plan_vigente.cod_planes_nutricionales)" class="mt-3 inline-flex text-sm font-semibold text-cyan-700 hover:text-cyan-900">Abrir plan</Link>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="font-semibold text-slate-900">Historial</p>
                                <div class="mt-3 space-y-3 text-sm text-slate-600">
                                    <div v-for="plan in planes_historial.slice(0, 4)" :key="plan.cod_planes_nutricionales" class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-slate-800">{{ plan.fec_pan }} · {{ plan.est_pan }}</p>
                                            <p class="text-xs text-slate-500">{{ plan.des_pan }}</p>
                                        </div>
                                        <Link :href="route('planes-nutricionales.show', plan.cod_planes_nutricionales)" class="text-xs font-semibold text-cyan-700 hover:text-cyan-900">Ver</Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

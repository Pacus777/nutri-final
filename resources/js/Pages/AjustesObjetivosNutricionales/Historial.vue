<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    objetivo: Object,
    ajustes: {
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

const crear = computed(() => {
    return props.crearUrl || route('ajustes-objetivos-nutricionales.create', {
        objetivo: props.objetivo.cod_objetivos_nutricionales,
    });
});

const volver = computed(() => {
    return props.volverUrl || route('objetivos-nutricionales.show', props.objetivo.cod_objetivos_nutricionales);
});

const showUrl = (ajuste) => {
    if (esNutricionista.value) {
        return route(
            'nutricionista.ajustes-objetivos-nutricionales.show',
            ajuste.cod_ajustes_objetivos_nutricionales,
        );
    }

    return route(
        'ajustes-objetivos-nutricionales.show',
        ajuste.cod_ajustes_objetivos_nutricionales,
    );
};

const motivoLabel = (motivo) => {
    const labels = {
        creacion_objetivo: 'Creación del objetivo',
        cambio_estado: 'Cambio de estado',
        cambio_descripcion: 'Cambio de descripción',
        ajuste_manual: 'Ajuste manual',
        seguimiento: 'Seguimiento',
    };

    return labels[motivo] || 'Ajuste';
};

const chipEstado = (estado) => {
    if (estado === 'activo') return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    if (estado === 'pausado') return 'bg-amber-100 text-amber-700 ring-amber-200';
    if (estado === 'cumplido') return 'bg-cyan-100 text-cyan-700 ring-cyan-200';
    if (estado === 'cerrado') return 'bg-slate-200 text-slate-700 ring-slate-300';

    return 'bg-rose-100 text-rose-700 ring-rose-200';
};
</script>

<template>
    <Head title="Historial de ajustes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">
                        Historial de ajustes
                    </p>

                    <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                        Objetivo nutricional
                    </h2>

                    <p class="mt-1 max-w-3xl text-sm text-gray-500">
                        {{ objetivo.des_obe }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="crear"
                        class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700"
                    >
                        Nuevo ajuste
                    </Link>

                    <Link
                        :href="volver"
                        class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <section class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Total ajustes
                        </p>

                        <p class="mt-2 text-3xl font-black text-slate-900">
                            {{ ajustes.length }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-violet-50 p-5 shadow-sm ring-1 ring-violet-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">
                            Estado actual
                        </p>

                        <p class="mt-2 text-2xl font-black capitalize text-violet-900">
                            {{ objetivo.est_obe || 'No disponible' }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Fecha objetivo
                        </p>

                        <p class="mt-2 text-2xl font-black text-slate-900">
                            {{ objetivo.fec_obe || '-' }}
                        </p>
                    </div>
                </section>

                <section
                    v-if="ajustes.length === 0"
                    class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center"
                >
                    <h3 class="text-lg font-bold text-slate-900">
                        Este objetivo aún no tiene ajustes registrados.
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Los cambios de estado o edición del objetivo aparecerán aquí automáticamente.
                    </p>

                    <Link
                        :href="crear"
                        class="mt-5 inline-flex rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700"
                    >
                        Registrar ajuste manual
                    </Link>
                </section>

                <section
                    v-else
                    class="space-y-4"
                >
                    <article
                        v-for="ajuste in ajustes"
                        :key="ajuste.cod_ajustes_objetivos_nutricionales"
                        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">
                                        {{ motivoLabel(ajuste.motivo_ajn) }}
                                    </span>

                                    <span class="text-xs text-slate-400">
                                        {{ ajuste.fec_ajn || 'Sin fecha' }}
                                    </span>
                                </div>

                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">
                                    {{ ajuste.des_ajn }}
                                </p>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span
                                        v-if="ajuste.estado_anterior_ajn"
                                        class="rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                        :class="chipEstado(ajuste.estado_anterior_ajn)"
                                    >
                                        Antes: {{ ajuste.estado_anterior_ajn }}
                                    </span>

                                    <span
                                        v-if="ajuste.estado_nuevo_ajn"
                                        class="rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                        :class="chipEstado(ajuste.estado_nuevo_ajn)"
                                    >
                                        Después: {{ ajuste.estado_nuevo_ajn }}
                                    </span>
                                </div>

                                <p class="mt-3 text-xs text-slate-500">
                                    Responsable:
                                    {{ ajuste.nutricionista?.usuario?.nombre_completo || 'No disponible' }}
                                </p>
                            </div>

                            <div class="shrink-0">
                                <Link
                                    :href="showUrl(ajuste)"
                                    class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                                >
                                    Ver detalle
                                </Link>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
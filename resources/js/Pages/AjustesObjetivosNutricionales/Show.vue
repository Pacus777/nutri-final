<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    ajuste: Object,
    modo: {
        type: String,
        default: 'admin',
    },
    volverUrl: {
        type: String,
        default: '',
    },
    objetivoUrl: {
        type: String,
        default: '',
    },
});

const objetivo = computed(() => props.ajuste?.objetivo || null);

const volver = computed(() => {
    if (props.volverUrl) {
        return props.volverUrl;
    }

    return objetivo.value
        ? route('ajustes-objetivos-nutricionales.historial', objetivo.value.cod_objetivos_nutricionales)
        : route('dashboard');
});

const abrirObjetivoUrl = computed(() => {
    if (props.objetivoUrl) {
        return props.objetivoUrl;
    }

    return objetivo.value
        ? route('objetivos-nutricionales.show', objetivo.value.cod_objetivos_nutricionales)
        : route('dashboard');
});

const motivoLabel = computed(() => {
    const labels = {
        creacion_objetivo: 'Creación del objetivo',
        cambio_estado: 'Cambio de estado',
        cambio_descripcion: 'Cambio de descripción',
        ajuste_manual: 'Ajuste manual',
        seguimiento: 'Seguimiento',
    };

    return labels[props.ajuste?.motivo_ajn] || 'Ajuste';
});

const chipEstado = (estado) => {
    if (estado === 'activo') return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    if (estado === 'pausado') return 'bg-amber-100 text-amber-700 ring-amber-200';
    if (estado === 'cumplido') return 'bg-cyan-100 text-cyan-700 ring-cyan-200';
    if (estado === 'cerrado') return 'bg-slate-200 text-slate-700 ring-slate-300';

    return 'bg-rose-100 text-rose-700 ring-rose-200';
};
</script>

<template>
    <Head title="Detalle de ajuste" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">
                        Ajuste de objetivo nutricional
                    </p>

                    <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                        Registro histórico del cambio aplicado
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Este registro forma parte de la trazabilidad del objetivo.
                    </p>
                </div>

                <Link
                    :href="volver"
                    class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                >
                    Volver al historial
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">
                                {{ motivoLabel }}
                            </span>

                            <h3 class="mt-4 text-2xl font-bold text-slate-900">
                                {{ ajuste.fec_ajn }}
                            </h3>

                            <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">
                                {{ ajuste.des_ajn }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Responsable
                            </p>

                            <p class="mt-2 font-semibold">
                                {{ ajuste.nutricionista?.usuario?.nombre_completo || 'No disponible' }}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">
                            Estado anterior
                        </p>

                        <p
                            class="mt-3 inline-flex rounded-full px-3 py-1 text-sm font-semibold capitalize ring-1"
                            :class="chipEstado(ajuste.estado_anterior_ajn)"
                        >
                            {{ ajuste.estado_anterior_ajn || 'No disponible' }}
                        </p>
                    </div>

                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">
                            Estado nuevo
                        </p>

                        <p
                            class="mt-3 inline-flex rounded-full px-3 py-1 text-sm font-semibold capitalize ring-1"
                            :class="chipEstado(ajuste.estado_nuevo_ajn)"
                        >
                            {{ ajuste.estado_nuevo_ajn || 'No disponible' }}
                        </p>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">
                            Descripción anterior
                        </p>

                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ ajuste.descripcion_anterior_ajn || 'No registrada.' }}
                        </p>
                    </div>

                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">
                            Descripción nueva
                        </p>

                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ ajuste.descripcion_nueva_ajn || 'No registrada.' }}
                        </p>
                    </div>
                </section>

                <section
                    v-if="objetivo"
                    class="rounded-3xl border border-violet-200 bg-violet-50 p-6 shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-700">
                        Objetivo relacionado
                    </p>

                    <h4 class="mt-2 text-lg font-semibold text-violet-950">
                        {{ objetivo.des_obe }}
                    </h4>

                    <p class="mt-2 text-sm text-violet-900">
                        Estado actual: {{ objetivo.est_obe }}
                    </p>

                    <Link
                        :href="abrirObjetivoUrl"
                        class="mt-4 inline-flex text-sm font-semibold text-violet-700 hover:text-violet-900"
                    >
                        Abrir objetivo
                    </Link>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
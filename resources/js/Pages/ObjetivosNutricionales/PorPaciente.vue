<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    paciente: {
        type: Object,
        required: true,
    },
    objetivos: {
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

const esNutricionista = computed(() => props.modo === 'nutricionista');

const crear = computed(() => {
    return props.crearUrl || route('objetivos-nutricionales.create', { paciente: props.paciente.cod_pacientes });
});

const volver = computed(() => {
    return props.volverUrl || route('dashboard');
});

const showUrl = (objetivo) => {
    if (esNutricionista.value) {
        return route('nutricionista.objetivos-nutricionales.show', objetivo.cod_objetivos_nutricionales);
    }

    return route('objetivos-nutricionales.show', objetivo.cod_objetivos_nutricionales);
};

const editUrl = (objetivo) => {
    if (esNutricionista.value) {
        return route('nutricionista.objetivos-nutricionales.edit', objetivo.cod_objetivos_nutricionales);
    }

    return route('objetivos-nutricionales.edit', objetivo.cod_objetivos_nutricionales);
};

const puedeEditar = (objetivo) => {
    return ['activo', 'pausado'].includes(objetivo.est_obe);
};

const chipClase = (estado) => {
    if (estado === 'activo') return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    if (estado === 'cumplido') return 'bg-cyan-100 text-cyan-700 ring-cyan-200';
    if (estado === 'pausado') return 'bg-amber-100 text-amber-700 ring-amber-200';
    if (estado === 'cerrado') return 'bg-slate-200 text-slate-700 ring-slate-300';

    return 'bg-rose-100 text-rose-700 ring-rose-200';
};

const objetivosActivos = computed(() => props.objetivos.filter((obj) => obj.est_obe === 'activo').length);
const objetivosPausados = computed(() => props.objetivos.filter((obj) => obj.est_obe === 'pausado').length);
const objetivosFinalizados = computed(() => props.objetivos.filter((obj) => ['cumplido', 'cerrado', 'cancelado'].includes(obj.est_obe)).length);
</script>

<template>
    <Head title="Objetivos del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">
                        Objetivos nutricionales
                    </p>

                    <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                        {{ paciente.usuario?.nombre_completo || 'Paciente' }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Historial de metas nutricionales asociadas al paciente.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="crear"
                        class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700"
                    >
                        Nuevo objetivo
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
                <section class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Total
                        </p>

                        <p class="mt-2 text-3xl font-black text-slate-900">
                            {{ objetivos.length }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-emerald-50 p-5 shadow-sm ring-1 ring-emerald-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                            Activos
                        </p>

                        <p class="mt-2 text-3xl font-black text-emerald-800">
                            {{ objetivosActivos }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-amber-50 p-5 shadow-sm ring-1 ring-amber-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                            Pausados
                        </p>

                        <p class="mt-2 text-3xl font-black text-amber-800">
                            {{ objetivosPausados }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-5 shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Finalizados
                        </p>

                        <p class="mt-2 text-3xl font-black text-slate-900">
                            {{ objetivosFinalizados }}
                        </p>
                    </div>
                </section>

                <section
                    v-if="objetivos.length === 0"
                    class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center"
                >
                    <h3 class="text-lg font-bold text-slate-900">
                        Este paciente aún no tiene objetivos registrados.
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Crea el primer objetivo nutricional para iniciar el seguimiento clínico.
                    </p>

                    <Link
                        :href="crear"
                        class="mt-5 inline-flex rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700"
                    >
                        Crear objetivo
                    </Link>
                </section>

                <section
                    v-else
                    class="space-y-4"
                >
                    <article
                        v-for="objetivo in objetivos"
                        :key="objetivo.cod_objetivos_nutricionales"
                        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                        :class="chipClase(objetivo.est_obe)"
                                    >
                                        {{ objetivo.est_obe }}
                                    </span>

                                    <span class="text-xs text-slate-400">
                                        {{ objetivo.fec_obe || 'Sin fecha' }}
                                    </span>
                                </div>

                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">
                                    {{ objetivo.des_obe }}
                                </p>

                                <p class="mt-3 text-xs text-slate-500">
                                    Nutricionista:
                                    {{ objetivo.nutricionista?.usuario?.nombre_completo || 'Sin creador registrado' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2">
                                <Link
                                    :href="showUrl(objetivo)"
                                    class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                                >
                                    Ver
                                </Link>

                                <Link
                                    v-if="puedeEditar(objetivo)"
                                    :href="editUrl(objetivo)"
                                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                >
                                    Editar
                                </Link>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
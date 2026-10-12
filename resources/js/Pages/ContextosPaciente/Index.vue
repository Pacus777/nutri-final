<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    pacientes: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const busqueda = ref('');

const pacientesFiltrados = computed(() => {
    const t = busqueda.value.trim().toLowerCase();
    if (!t) return props.pacientes;
    return props.pacientes.filter((p) => {
        const nombre = (p.usuario?.nombre_completo ?? '').toLowerCase();
        const correo = (p.usuario?.cor_usu ?? '').toLowerCase();
        const ci = (p.usuario?.ci_usu ?? '').toLowerCase();
        return nombre.includes(t) || correo.includes(t) || ci.includes(t);
    });
});

const totalContextos = computed(() => props.pacientes.reduce((acc, p) => acc + p.contextos.length, 0));
const totalActivos = computed(() =>
    props.pacientes.reduce((acc, p) => acc + p.contextos.filter((c) => c.est_cxp === 'activo').length, 0),
);

const inactivar = (cod) => {
    router.patch(route('contextos-paciente.inactivar', cod));
};

const colorEstado = (est) =>
    est === 'activo'
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-gray-100 text-gray-500';
</script>

<template>
    <Head title="Contextos clínicos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Fichas clínicas</p>
                    <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                        Contextos por paciente
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Antecedentes, hábitos y restricciones registrados para cada paciente.
                    </p>
                </div>
                <Link
                    :href="route('contextos-paciente.create')"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                >
                    Nuevo contexto
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

                <!-- Flash -->
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <!-- Resumen -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pacientes con contexto</p>
                        <p class="mt-2 text-3xl font-black text-slate-900">{{ pacientes.length }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total contextos</p>
                        <p class="mt-2 text-3xl font-black text-slate-900">{{ totalContextos }}</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Contextos activos</p>
                        <p class="mt-2 text-3xl font-black text-emerald-800">{{ totalActivos }}</p>
                    </div>
                </div>

                <!-- Buscador -->
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <input
                        v-model="busqueda"
                        type="text"
                        placeholder="Buscar paciente por nombre, correo o CI..."
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    />
                </div>

                <!-- Sin resultados -->
                <div
                    v-if="pacientes.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500"
                >
                    No hay contextos registrados todavía.
                    <Link :href="route('contextos-paciente.create')" class="ml-1 font-semibold text-amber-600 underline">
                        Crear el primero
                    </Link>
                </div>

                <div
                    v-else-if="pacientesFiltrados.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500"
                >
                    Sin resultados para "{{ busqueda }}".
                </div>

                <!-- Tarjetas por paciente -->
                <div v-else class="space-y-6">
                    <div
                        v-for="paciente in pacientesFiltrados"
                        :key="paciente.cod_pacientes"
                        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
                    >
                        <!-- Cabecera paciente -->
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-6 py-4">
                            <div>
                                <p class="font-semibold text-slate-900">
                                    {{ paciente.usuario?.nombre_completo ?? 'Sin nombre' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ paciente.usuario?.cor_usu }}
                                    <span v-if="paciente.usuario?.ci_usu"> · CI {{ paciente.usuario.ci_usu }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                    {{ paciente.contextos.length }} contexto{{ paciente.contextos.length !== 1 ? 's' : '' }}
                                </span>
                                <Link
                                    :href="route('contextos-paciente.create', { paciente: paciente.cod_pacientes })"
                                    class="rounded-lg border border-amber-300 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-50"
                                >
                                    + Nuevo
                                </Link>
                                <Link
                                    :href="route('contextos-paciente.historial', paciente.cod_pacientes)"
                                    class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                >
                                    Ver historial
                                </Link>
                            </div>
                        </div>

                        <!-- Lista de contextos -->
                        <div class="divide-y divide-slate-100">
                            <div
                                v-for="ctx in paciente.contextos"
                                :key="ctx.cod_contextos_paciente"
                                class="flex flex-wrap items-start justify-between gap-4 px-6 py-4"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                        <span
                                            v-if="ctx.tip_cxp"
                                            class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
                                        >
                                            {{ ctx.tip_cxp }}
                                        </span>
                                        <span
                                            :class="colorEstado(ctx.est_cxp)"
                                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                                        >
                                            {{ ctx.est_cxp }}
                                        </span>
                                        <span class="text-xs text-slate-400">{{ ctx.fec_cxp }}</span>
                                    </div>
                                    <p class="line-clamp-2 text-sm text-slate-700">{{ ctx.des_cxp }}</p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <Link
                                        :href="route('contextos-paciente.edit', ctx.cod_contextos_paciente)"
                                        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        v-if="ctx.est_cxp === 'activo'"
                                        type="button"
                                        class="rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                                        @click="inactivar(ctx.cod_contextos_paciente)"
                                    >
                                        Inactivar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

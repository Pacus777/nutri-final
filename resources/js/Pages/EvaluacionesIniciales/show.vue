<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    evaluacion: Object,
    paciente: {
        type: Object,
        default: null,
    },
    modo: {
        type: String,
        default: 'admin',
    },
});

const pacienteActual = computed(() => {
    return props.paciente || props.evaluacion?.paciente || null;
});

const nutricionistaActual = computed(() => {
    return props.evaluacion?.nutricionista || null;
});

const esNutricionista = computed(() => props.modo === 'nutricionista');

const evaluacionId = computed(() => props.evaluacion?.cod_evaluaciones_iniciales);

const pacienteId = computed(() => pacienteActual.value?.cod_pacientes);

const estado = computed(() => props.evaluacion?.est_evi || 'sin_estado');

const puedeEditar = computed(() => {
    return ['borrador', 'pendiente_revision', 'activa'].includes(estado.value);
});

const puedeAprobar = computed(() => {
    return esNutricionista.value && ['borrador', 'pendiente_revision'].includes(estado.value);
});

const puedeCerrar = computed(() => {
    return esNutricionista.value && estado.value !== 'cerrada';
});

const volverUrl = computed(() => {
    if (esNutricionista.value && pacienteId.value) {
        return route('nutricionista.pacientes.show', pacienteId.value);
    }

    return route('pacientes.index');
});

const editarUrl = computed(() => {
    if (esNutricionista.value && evaluacionId.value) {
        return route('nutricionista.evaluaciones-iniciales.edit', evaluacionId.value);
    }

    return '#';
});

const estadoLabel = computed(() => {
    const labels = {
        borrador: 'Borrador',
        pendiente_revision: 'Pendiente de revisión',
        activa: 'Activa',
        cerrada: 'Cerrada',
    };

    return labels[estado.value] || estado.value;
});

const estadoClasses = computed(() => {
    const classes = {
        borrador: 'bg-slate-100 text-slate-700 ring-slate-200',
        pendiente_revision: 'bg-amber-100 text-amber-800 ring-amber-200',
        activa: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        cerrada: 'bg-red-100 text-red-800 ring-red-200',
    };

    return classes[estado.value] || 'bg-slate-100 text-slate-700 ring-slate-200';
});

const aprobar = () => {
    if (!evaluacionId.value) return;

    router.patch(
        route('nutricionista.evaluaciones-iniciales.aprobar', evaluacionId.value),
        {},
        {
            preserveScroll: true,
        },
    );
};

const cerrar = () => {
    if (!evaluacionId.value) return;

    if (!confirm('¿Seguro que deseas cerrar esta evaluación inicial? Luego no podrá editarse.')) {
        return;
    }

    router.patch(
        route('nutricionista.evaluaciones-iniciales.cerrar', evaluacionId.value),
        {},
        {
            preserveScroll: true,
        },
    );
};

const valor = (dato, fallback = 'No registrado') => {
    return dato || fallback;
};
</script>

<template>
    <Head title="Evaluación inicial" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Evaluación inicial
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Checkpoint clínico inicial del paciente.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="volverUrl"
                        class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Volver
                    </Link>

                    <Link
                        v-if="esNutricionista && puedeEditar"
                        :href="editarUrl"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Editar
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-50 via-white to-sky-50 p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">
                                Estado inicial del paciente
                            </p>

                            <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">
                                {{ pacienteActual?.usuario?.nombre_completo || 'Paciente no disponible' }}
                            </h1>

                            <p class="mt-2 text-sm text-slate-600">
                                Fecha de evaluación:
                                <strong>{{ valor(evaluacion?.fec_evi) }}</strong>
                            </p>

                            <div class="mt-4">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                    :class="estadoClasses"
                                >
                                    {{ estadoLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-3xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-100">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Peso inicial
                                </p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">
                                    {{ valor(evaluacion?.peso_evi, '--') }}
                                </p>
                                <p class="text-xs text-slate-500">kg</p>
                            </div>

                            <div class="rounded-3xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-100">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Estatura
                                </p>
                                <p class="mt-2 text-3xl font-bold text-slate-900">
                                    {{ valor(evaluacion?.estatura_evi, '--') }}
                                </p>
                                <p class="text-xs text-slate-500">m</p>
                            </div>

                            <div class="rounded-3xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-100">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    IMC
                                </p>
                                <p class="mt-2 text-3xl font-bold text-emerald-700">
                                    {{ valor(evaluacion?.imc_evi, '--') }}
                                </p>
                                <p class="text-xs text-slate-500">calculado</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    v-if="esNutricionista"
                    class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                Acciones clínicas
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Revisa, aprueba o cierra la evaluación inicial según corresponda.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <PrimaryButton
                                v-if="puedeAprobar"
                                type="button"
                                @click="aprobar"
                            >
                                Aprobar evaluación
                            </PrimaryButton>

                            <DangerButton
                                v-if="puedeCerrar"
                                type="button"
                                @click="cerrar"
                            >
                                Cerrar evaluación
                            </DangerButton>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 lg:grid-cols-3">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100 lg:col-span-2">
                        <h3 class="text-lg font-bold text-slate-900">
                            Información inicial registrada
                        </h3>

                        <div class="mt-6 space-y-5">
                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Objetivo inicial
                                </p>
                                <div class="mt-2 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                                    {{ valor(evaluacion?.objetivo_inicial_evi, 'Sin objetivo inicial registrado.') }}
                                </div>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Restricciones
                                </p>
                                <div class="mt-2 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                                    {{ valor(evaluacion?.restricciones_evi, 'Sin restricciones registradas.') }}
                                </div>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Contexto general
                                </p>
                                <div class="mt-2 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                                    {{ valor(evaluacion?.contexto_general_evi, 'Sin contexto general registrado.') }}
                                </div>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Observaciones
                                </p>
                                <div class="mt-2 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                                    {{ valor(evaluacion?.obs_evi, 'Sin observaciones registradas.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                            <h3 class="text-lg font-bold text-slate-900">
                                Paciente
                            </h3>

                            <div class="mt-4 space-y-3 text-sm text-slate-700">
                                <p>
                                    <strong>Nombre:</strong>
                                    {{ pacienteActual?.usuario?.nombre_completo || 'No disponible' }}
                                </p>
                                <p>
                                    <strong>CI:</strong>
                                    <span v-if="pacienteActual?.usuario?.ci_usu">
                                        {{ pacienteActual.usuario.ci_usu }} {{ pacienteActual.usuario.ext_ci_usu }}
                                    </span>
                                    <span v-else>No disponible</span>
                                </p>
                                <p>
                                    <strong>Correo:</strong>
                                    {{ pacienteActual?.usuario?.cor_usu || 'No disponible' }}
                                </p>
                                <p>
                                    <strong>Sexo:</strong>
                                    {{ pacienteActual?.sex_pac || 'No registrado' }}
                                </p>
                                <p>
                                    <strong>Tipo de sangre:</strong>
                                    {{ pacienteActual?.tip_san_pac || 'No registrado' }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                            <h3 class="text-lg font-bold text-slate-900">
                                Nutricionista responsable
                            </h3>

                            <div class="mt-4 space-y-3 text-sm text-slate-700">
                                <p>
                                    <strong>Nombre:</strong>
                                    {{ nutricionistaActual?.usuario?.nombre_completo || 'Pendiente de asignar' }}
                                </p>
                                <p>
                                    <strong>Correo:</strong>
                                    {{ nutricionistaActual?.usuario?.cor_usu || 'No disponible' }}
                                </p>
                                <p>
                                    <strong>Especialidad:</strong>
                                    {{ nutricionistaActual?.esp_nut || 'No especificada' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
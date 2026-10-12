<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EvaluacionInicialStepper from '@/Components/EvaluacionesIniciales/EvaluacionInicialStepper.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    paciente: Object,
    evaluacion: {
        type: Object,
        default: null,
    },
    estados: {
        type: Array,
        default: () => [],
    },
});

const enviarRevisionUrl = props.evaluacion?.cod_evaluaciones_iniciales
    ? route('paciente.evaluacion-inicial.enviar-revision', props.evaluacion.cod_evaluaciones_iniciales)
    : '';
</script>

<template>
    <Head title="Mi evaluación inicial" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Mi evaluación inicial
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Completa tu información inicial para personalizar tu proceso nutricional.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <EvaluacionInicialStepper
                    modo="paciente"
                    :paciente="paciente"
                    :evaluacion="evaluacion"
                    :action="route('paciente.evaluacion-inicial.guardar')"
                    method="post"
                    :volver-url="route('dashboard.paciente')"
                    :enviar-revision-url="enviarRevisionUrl"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EvaluacionInicialStepper from '@/Components/EvaluacionesIniciales/EvaluacionInicialStepper.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    paciente: Object,
    nutricionista: Object,
    estados: {
        type: Array,
        default: () => [],
    },
    modo: {
        type: String,
        default: 'nutricionista',
    },
});
</script>

<template>
    <Head title="Crear evaluación inicial" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Crear evaluación inicial
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Registra el checkpoint clínico inicial del paciente asignado.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <EvaluacionInicialStepper
                    modo="nutricionista"
                    :paciente="paciente"
                    :nutricionista="nutricionista"
                    :action="route('nutricionista.evaluaciones-iniciales.store-paciente', paciente.cod_pacientes)"
                    method="post"
                    :volver-url="route('nutricionista.pacientes.show', paciente.cod_pacientes)"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
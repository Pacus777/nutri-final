<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EvaluacionInicialStepper from '@/Components/EvaluacionesIniciales/EvaluacionInicialStepper.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    evaluacion: Object,
    estados: {
        type: Array,
        default: () => [],
    },
    modo: {
        type: String,
        default: 'nutricionista',
    },
});

const paciente = computed(() => {
    return props.evaluacion?.paciente || null;
});

const actionUrl = computed(() => {
    if (props.modo === 'nutricionista') {
        return route(
            'nutricionista.evaluaciones-iniciales.update',
            props.evaluacion.cod_evaluaciones_iniciales,
        );
    }

    return route(
        'evaluaciones-iniciales.update',
        props.evaluacion.cod_evaluaciones_iniciales,
    );
});

const volverUrl = computed(() => {
    if (props.modo === 'nutricionista' && paciente.value?.cod_pacientes) {
        return route(
            'nutricionista.evaluaciones-iniciales.show-paciente',
            paciente.value.cod_pacientes,
        );
    }

    return route(
        'evaluaciones-iniciales.show',
        props.evaluacion.cod_evaluaciones_iniciales,
    );
});
</script>

<template>
    <Head title="Editar evaluación inicial" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Editar evaluación inicial
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Actualiza los datos del checkpoint clínico inicial.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <EvaluacionInicialStepper
                    modo="nutricionista"
                    :paciente="paciente"
                    :evaluacion="evaluacion"
                    :action="actionUrl"
                    method="put"
                    :volver-url="volverUrl"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
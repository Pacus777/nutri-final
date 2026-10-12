<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pacientes: Array,
    nutricionistas: Array,
    estados: Array,
    pacienteSeleccionado: [String, Number],
    nutricionistaSeleccionado: [String, Number],
});

const form = useForm({
    cod_pacientes_evi: props.pacienteSeleccionado || '',
    cod_nutricionistas_evi: props.nutricionistaSeleccionado || '',
    fec_evi: '',
    est_evi: 'activa',
    obs_evi: '',
});

const submit = () => {
    form.post(route('evaluaciones-iniciales.store'));
};

const nombrePaciente = (paciente) => {
    return paciente.usuario?.nombre_completo || 'Paciente sin nombre';
};

const nombreNutricionista = (nutricionista) => {
    return nutricionista.usuario?.nombre_completo || 'Nutricionista sin nombre';
};
</script>

<template>
    <Head title="Crear evaluación inicial" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Evaluación Inicial del Paciente
                </h2>

                <Link
                    :href="route('pacientes.index')"
                    class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="cod_pacientes_evi" value="Paciente" />
                                <select
                                    id="cod_pacientes_evi"
                                    v-model="form.cod_pacientes_evi"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                >
                                    <option value="">Seleccione un paciente</option>
                                    <option
                                        v-for="paciente in pacientes"
                                        :key="paciente.cod_pacientes"
                                        :value="paciente.cod_pacientes"
                                    >
                                        {{ nombrePaciente(paciente) }} - {{ paciente.usuario?.cor_usu }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.cod_pacientes_evi" />
                            </div>

                            <div>
                                <InputLabel for="cod_nutricionistas_evi" value="Nutricionista responsable" />
                                <select
                                    id="cod_nutricionistas_evi"
                                    v-model="form.cod_nutricionistas_evi"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                >
                                    <option value="">Seleccione un nutricionista</option>
                                    <option
                                        v-for="nutricionista in nutricionistas"
                                        :key="nutricionista.cod_nutricionistas"
                                        :value="nutricionista.cod_nutricionistas"
                                    >
                                        {{ nombreNutricionista(nutricionista) }} - {{ nutricionista.usuario?.cor_usu }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.cod_nutricionistas_evi" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="fec_evi" value="Fecha de evaluación" />
                                    <input
                                        id="fec_evi"
                                        type="date"
                                        v-model="form.fec_evi"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    />
                                    <InputError class="mt-2" :message="form.errors.fec_evi" />
                                </div>

                                <div>
                                    <InputLabel for="est_evi" value="Estado inicial" />
                                    <select
                                        id="est_evi"
                                        v-model="form.est_evi"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    >
                                        <option
                                            v-for="estado in estados"
                                            :key="estado"
                                            :value="estado"
                                        >
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_evi" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="obs_evi" value="Observación inicial" />
                                <textarea
                                    id="obs_evi"
                                    v-model="form.obs_evi"
                                    rows="5"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                />
                                <InputError class="mt-2" :message="form.errors.obs_evi" />
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">
                                    Guardar evaluación inicial
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
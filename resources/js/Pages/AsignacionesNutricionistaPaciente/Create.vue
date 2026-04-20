<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    nutricionistas: {
        type: Array,
        default: () => [],
    },
    pacientes: {
        type: Array,
        default: () => [],
    },
    estados: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    cod_nutricionistas_anp: '',
    cod_pacientes_anp: '',
    fec_anp: '',
    est_anp: 'activa',
});

const submit = () => {
    form.post(route('asignaciones.store'));
};
</script>

<template>
    <Head title="Crear asignación" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Registrar Asignación
                </h2>

                <Link
                    :href="route('asignaciones.index')"
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
                                <InputLabel for="cod_pacientes_anp" value="Paciente" />
                                <select
                                    id="cod_pacientes_anp"
                                    v-model="form.cod_pacientes_anp"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Seleccione un paciente</option>
                                    <option
                                        v-for="paciente in pacientes"
                                        :key="paciente.cod_pacientes"
                                        :value="paciente.cod_pacientes"
                                    >
                                        {{ paciente.usuario.nom_usu }} - {{ paciente.usuario.cor_usu }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.cod_pacientes_anp" />
                            </div>

                            <div>
                                <InputLabel for="cod_nutricionistas_anp" value="Nutricionista" />
                                <select
                                    id="cod_nutricionistas_anp"
                                    v-model="form.cod_nutricionistas_anp"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Seleccione un nutricionista</option>
                                    <option
                                        v-for="nutricionista in nutricionistas"
                                        :key="nutricionista.cod_nutricionistas"
                                        :value="nutricionista.cod_nutricionistas"
                                    >
                                        {{ nutricionista.usuario.nom_usu }} - {{ nutricionista.usuario.cor_usu }}
                                        <span v-if="nutricionista.esp_nut">
                                            | {{ nutricionista.esp_nut }}
                                        </span>
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.cod_nutricionistas_anp" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="fec_anp" value="Fecha de asignación" />
                                    <input
                                        id="fec_anp"
                                        type="date"
                                        v-model="form.fec_anp"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <InputError class="mt-2" :message="form.errors.fec_anp" />
                                </div>

                                <div>
                                    <InputLabel for="est_anp" value="Estado inicial" />
                                    <select
                                        id="est_anp"
                                        v-model="form.est_anp"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option
                                            v-for="estado in estados"
                                            :key="estado"
                                            :value="estado"
                                        >
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_anp" />
                                </div>
                            </div>

                            <div class="rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                Solo pueden seleccionarse pacientes y nutricionistas con perfil activo
                                y usuario activo. Un paciente solo puede tener una asignación activa a la vez.
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">
                                    Guardar asignación
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    usuarios: {
        type: Array,
        default: () => [],
    },
    estados: {
        type: Array,
        default: () => [],
    },
    sexos: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    cod_usuarios_pac: '',
    fec_pac: '',
    eda_pac: '',
    sex_pac: '',
    pes_pac: '',
    tal_pac: '',
    est_pac: 'activo',
});

const submit = () => {
    form.post(route('pacientes.store'));
};
</script>

<template>
    <Head title="Crear paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Registrar Paciente
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
                                <InputLabel for="cod_usuarios_pac" value="Usuario vinculado" />
                                <select
                                    id="cod_usuarios_pac"
                                    v-model="form.cod_usuarios_pac"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Seleccione un usuario</option>
                                    <option
                                        v-for="usuario in usuarios"
                                        :key="usuario.cod_usuarios"
                                        :value="usuario.cod_usuarios"
                                    >
                                        {{ usuario.nom_usu }} - {{ usuario.cor_usu }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.cod_usuarios_pac" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="fec_pac" value="Fecha de registro" />
                                    <input
                                        id="fec_pac"
                                        type="date"
                                        v-model="form.fec_pac"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <InputError class="mt-2" :message="form.errors.fec_pac" />
                                </div>

                                <div>
                                    <InputLabel for="eda_pac" value="Edad" />
                                    <input
                                        id="eda_pac"
                                        type="number"
                                        min="1"
                                        v-model="form.eda_pac"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <InputError class="mt-2" :message="form.errors.eda_pac" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="sex_pac" value="Sexo" />
                                <select
                                    id="sex_pac"
                                    v-model="form.sex_pac"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Seleccione una opción</option>
                                    <option
                                        v-for="sexo in sexos"
                                        :key="sexo"
                                        :value="sexo"
                                    >
                                        {{ sexo }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.sex_pac" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="pes_pac" value="Peso" />
                                    <input
                                        id="pes_pac"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        v-model="form.pes_pac"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <InputError class="mt-2" :message="form.errors.pes_pac" />
                                </div>

                                <div>
                                    <InputLabel for="tal_pac" value="Talla" />
                                    <input
                                        id="tal_pac"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        v-model="form.tal_pac"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <InputError class="mt-2" :message="form.errors.tal_pac" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="est_pac" value="Estado del perfil" />
                                <select
                                    id="est_pac"
                                    v-model="form.est_pac"
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
                                <InputError class="mt-2" :message="form.errors.est_pac" />
                            </div>

                            <div class="rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                Si el usuario seleccionado no tiene el rol <strong>Paciente</strong>,
                                el sistema se lo asignará automáticamente al guardar.
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">
                                    Guardar paciente
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
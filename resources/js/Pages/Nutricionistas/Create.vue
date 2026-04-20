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
});

const form = useForm({
    cod_usuarios_nut: '',
    esp_nut: '',
    est_nut: 'activo',
});

const submit = () => {
    form.post(route('nutricionistas.store'));
};
</script>

<template>
    <Head title="Crear nutricionista" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Registrar Nutricionista
                </h2>

                <Link
                    :href="route('nutricionistas.index')"
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
                                <InputLabel for="cod_usuarios_nut" value="Usuario vinculado" />
                                <select
                                    id="cod_usuarios_nut"
                                    v-model="form.cod_usuarios_nut"
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
                                <InputError class="mt-2" :message="form.errors.cod_usuarios_nut" />
                            </div>

                            <div>
                                <InputLabel for="esp_nut" value="Especialidad" />
                                <input
                                    id="esp_nut"
                                    type="text"
                                    v-model="form.esp_nut"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Ej.: Nutrición clínica, deportiva, pediátrica..."
                                />
                                <InputError class="mt-2" :message="form.errors.esp_nut" />
                            </div>

                            <div>
                                <InputLabel for="est_nut" value="Estado del perfil" />
                                <select
                                    id="est_nut"
                                    v-model="form.est_nut"
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
                                <InputError class="mt-2" :message="form.errors.est_nut" />
                            </div>

                            <div class="rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                Si el usuario seleccionado no tiene el rol <strong>Nutricionista</strong>,
                                el sistema se lo asignará automáticamente al guardar.
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">
                                    Guardar nutricionista
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
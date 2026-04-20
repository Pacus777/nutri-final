<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    estadosUsuario: {
        type: Array,
        default: () => [],
    },
    estadosNutricionista: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    nom_usu: '',
    cor_usu: '',
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: 'activo',
    esp_nut: '',
    est_nut: 'activo',
});

const submit = () => {
    form.post(route('nutricionistas.guardar-registro-rapido'));
};
</script>

<template>
    <Head title="Registro rápido de nutricionista" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Registro rápido de Nutricionista
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
                            <div class="rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                Este formulario crea el usuario y el perfil de nutricionista en un solo paso.
                            </div>

                            <div>
                                <InputLabel for="nom_usu" value="Nombre completo" />
                                <TextInput
                                    id="nom_usu"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.nom_usu"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.nom_usu" />
                            </div>

                            <div>
                                <InputLabel for="cor_usu" value="Correo" />
                                <TextInput
                                    id="cor_usu"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.cor_usu"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.cor_usu" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="cla_usu" value="Contraseña" />
                                    <TextInput
                                        id="cla_usu"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.cla_usu"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.cla_usu" />
                                </div>

                                <div>
                                    <InputLabel for="cla_usu_confirmation" value="Confirmar contraseña" />
                                    <TextInput
                                        id="cla_usu_confirmation"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.cla_usu_confirmation"
                                        required
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="est_usu" value="Estado del usuario" />
                                <select
                                    id="est_usu"
                                    v-model="form.est_usu"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="estado in estadosUsuario"
                                        :key="estado"
                                        :value="estado"
                                    >
                                        {{ estado }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.est_usu" />
                            </div>

                            <div class="space-y-6 rounded border border-blue-200 bg-blue-50 p-4">
                                <h3 class="text-lg font-semibold text-blue-800">
                                    Datos del perfil de nutricionista
                                </h3>

                                <div>
                                    <InputLabel for="esp_nut" value="Especialidad" />
                                    <TextInput
                                        id="esp_nut"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.esp_nut"
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
                                            v-for="estado in estadosNutricionista"
                                            :key="estado"
                                            :value="estado"
                                        >
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_nut" />
                                </div>
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
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    estados: {
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
    tipo_registro: 'Administrador',

    // Nutricionista
    esp_nut: '',
    est_nut: 'activo',

    // Paciente
    fec_pac: '',
    eda_pac: '',
    sex_pac: '',
    pes_pac: '',
    tal_pac: '',
    est_pac: 'activo',
});

const esNutricionista = computed(() => form.tipo_registro === 'Nutricionista');
const esPaciente = computed(() => form.tipo_registro === 'Paciente');

const submit = () => {
    form.post(route('usuarios.store'));
};
</script>

<template>
    <Head title="Crear usuario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Crear Registro
                </h2>

                <Link
                    :href="route('usuarios.index')"
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
                                <InputLabel for="tipo_registro" value="Tipo de registro" />
                                <select
                                    id="tipo_registro"
                                    v-model="form.tipo_registro"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="Administrador">Administrador</option>
                                    <option value="Nutricionista">Nutricionista</option>
                                    <option value="Paciente">Paciente</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.tipo_registro" />
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
                                        v-for="estado in estados"
                                        :key="estado"
                                        :value="estado"
                                    >
                                        {{ estado }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.est_usu" />
                            </div>

                            <div
                                v-if="esNutricionista"
                                class="space-y-6 rounded border border-blue-200 bg-blue-50 p-4"
                            >
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
                                        <option value="activo">activo</option>
                                        <option value="inactivo">inactivo</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_nut" />
                                </div>
                            </div>

                            <div
                                v-if="esPaciente"
                                class="space-y-6 rounded border border-green-200 bg-green-50 p-4"
                            >
                                <h3 class="text-lg font-semibold text-green-800">
                                    Datos del perfil de paciente
                                </h3>

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
                                        <option value="masculino">masculino</option>
                                        <option value="femenino">femenino</option>
                                        <option value="otro">otro</option>
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
                                        <option value="activo">activo</option>
                                        <option value="inactivo">inactivo</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_pac" />
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">
                                    Guardar registro
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
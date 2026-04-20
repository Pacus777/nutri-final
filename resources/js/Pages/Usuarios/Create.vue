<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    estados: Array,
});

const previewFoto = ref(null);

const form = useForm({
    nom_usu: '',
    ape_usu: '',
    ci_usu: '',
    ext_ci_usu: '',
    fec_nac_usu: '',
    fot_usu: null,
    cor_usu: '',
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: 'activo',
    tipo_registro: 'Administrador',

    // Nutricionista
    esp_nut: '',
    est_nut: 'activo',

    // Paciente
    sex_pac: '',
    pes_pac: '',
    estatura_pac: '',
    tip_san_pac: '',
    ale_pac: '',
    est_pac: 'activo',
});

const esNutricionista = computed(() => form.tipo_registro === 'Nutricionista');
const esPaciente = computed(() => form.tipo_registro === 'Paciente');

const handleFile = (event) => {
    const file = event.target.files[0];
    form.fot_usu = file ?? null;

    if (file) {
        previewFoto.value = URL.createObjectURL(file);
    } else {
        previewFoto.value = null;
    }
};

const submit = () => {
    form.post(route('usuarios.store'), {
        forceFormData: true,
    });
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
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
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

                            <!-- DATOS PERSONALES -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="nom_usu" value="Nombres" />
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
                                    <InputLabel for="ape_usu" value="Apellidos" />
                                    <TextInput
                                        id="ape_usu"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.ape_usu"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.ape_usu" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <div>
                                    <InputLabel for="ci_usu" value="CI" />
                                    <TextInput
                                        id="ci_usu"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.ci_usu"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.ci_usu" />
                                </div>

                                <div>
                                    <InputLabel for="ext_ci_usu" value="Extensión CI" />
                                    <TextInput
                                        id="ext_ci_usu"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.ext_ci_usu"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.ext_ci_usu" />
                                </div>

                                <div>
                                    <InputLabel for="fec_nac_usu" value="Fecha de nacimiento" />
                                    <input
                                        id="fec_nac_usu"
                                        type="date"
                                        v-model="form.fec_nac_usu"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <InputError class="mt-2" :message="form.errors.fec_nac_usu" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="fot_usu" value="Foto (opcional)" />
                                <input
                                    id="fot_usu"
                                    type="file"
                                    @change="handleFile"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.fot_usu" />

                                <div v-if="previewFoto" class="mt-4">
                                    <p class="mb-2 text-sm text-gray-600">Vista previa:</p>
                                    <img
                                        :src="previewFoto"
                                        alt="Vista previa"
                                        class="h-28 w-28 rounded object-cover border"
                                    />
                                </div>
                            </div>

                            <!-- DATOS DE ACCESO -->
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

                            <!-- PERFIL NUTRICIONISTA -->
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

                            <!-- PERFIL PACIENTE -->
                            <div
                                v-if="esPaciente"
                                class="space-y-6 rounded border border-green-200 bg-green-50 p-4"
                            >
                                <h3 class="text-lg font-semibold text-green-800">
                                    Datos del perfil de paciente
                                </h3>

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
                                        <InputLabel for="estatura_pac" value="Estatura" />
                                        <input
                                            id="estatura_pac"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            v-model="form.estatura_pac"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <InputError class="mt-2" :message="form.errors.estatura_pac" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="tip_san_pac" value="Tipo de sangre" />
                                    <TextInput
                                        id="tip_san_pac"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.tip_san_pac"
                                    />
                                    <InputError class="mt-2" :message="form.errors.tip_san_pac" />
                                </div>

                                <div>
                                    <InputLabel for="ale_pac" value="Alergias" />
                                    <textarea
                                        id="ale_pac"
                                        v-model="form.ale_pac"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="4"
                                    />
                                    <InputError class="mt-2" :message="form.errors.ale_pac" />
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
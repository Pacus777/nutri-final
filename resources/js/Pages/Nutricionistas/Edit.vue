<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    nutricionista: Object,
    estadosUsuario: Array,
    estadosNutricionista: Array,
});

const previewFoto = ref(
    props.nutricionista.usuario?.fot_usu
        ? `/storage/${props.nutricionista.usuario.fot_usu}`
        : null
);

const form = useForm({
    nom_usu: props.nutricionista.usuario?.nom_usu ?? '',
    ape_usu: props.nutricionista.usuario?.ape_usu ?? '',
    ci_usu: props.nutricionista.usuario?.ci_usu ?? '',
    ext_ci_usu: props.nutricionista.usuario?.ext_ci_usu ?? '',
    fec_nac_usu: props.nutricionista.usuario?.fec_nac_usu ?? '',
    fot_usu: null,
    cor_usu: props.nutricionista.usuario?.cor_usu ?? '',
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: props.nutricionista.usuario?.est_usu ?? 'activo',

    esp_nut: props.nutricionista.esp_nut ?? '',
    est_nut: props.nutricionista.est_nut ?? 'activo',
});

const handleFile = (event) => {
    const file = event.target.files[0];
    form.fot_usu = file ?? null;

    if (file) {
        previewFoto.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            _method: 'put',
        }))
        .post(route('nutricionistas.update', props.nutricionista.cod_nutricionistas), {
            forceFormData: true,
        });
};
</script>

<template>
    <Head title="Editar nutricionista" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Editar Nutricionista
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
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- DATOS PERSONALES -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="nom_usu" value="Nombres" />
                                    <TextInput
                                        id="nom_usu"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.nom_usu"
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
                                        alt="Foto de nutricionista"
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
                                />
                                <InputError class="mt-2" :message="form.errors.cor_usu" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="cla_usu" value="Nueva contraseña" />
                                    <TextInput
                                        id="cla_usu"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.cla_usu"
                                        placeholder="Opcional"
                                    />
                                    <InputError class="mt-2" :message="form.errors.cla_usu" />
                                </div>

                                <div>
                                    <InputLabel for="cla_usu_confirmation" value="Confirmar nueva contraseña" />
                                    <TextInput
                                        id="cla_usu_confirmation"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.cla_usu_confirmation"
                                        placeholder="Opcional"
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

                            <!-- PERFIL NUTRICIONISTA -->
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
                                    Actualizar nutricionista
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
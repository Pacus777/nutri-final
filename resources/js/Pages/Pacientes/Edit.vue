<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    paciente: Object,
    estadosUsuario: Array,
    estadosPaciente: Array,
    sexos: Array,
});

const previewFoto = ref(
    props.paciente.usuario?.fot_usu ? `/storage/${props.paciente.usuario.fot_usu}` : null
);

const form = useForm({
    nom_usu: props.paciente.usuario?.nom_usu ?? '',
    ape_usu: props.paciente.usuario?.ape_usu ?? '',
    ci_usu: props.paciente.usuario?.ci_usu ?? '',
    ext_ci_usu: props.paciente.usuario?.ext_ci_usu ?? '',
    fec_nac_usu: props.paciente.usuario?.fec_nac_usu ?? '',
    fot_usu: null,
    cor_usu: props.paciente.usuario?.cor_usu ?? '',
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: props.paciente.usuario?.est_usu ?? 'activo',

    sex_pac: props.paciente.sex_pac,
    pes_pac: props.paciente.pes_pac,
    estatura_pac: props.paciente.estatura_pac,
    tip_san_pac: props.paciente.tip_san_pac ?? '',
    ale_pac: props.paciente.ale_pac ?? '',
    est_pac: props.paciente.est_pac,
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
        .post(route('pacientes.update', props.paciente.cod_pacientes), {
            forceFormData: true,
        });
};
</script>

<template>
    <Head title="Editar paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Editar Paciente
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
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="nom_usu" value="Nombres" />
                                    <TextInput id="nom_usu" v-model="form.nom_usu" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.nom_usu" />
                                </div>

                                <div>
                                    <InputLabel for="ape_usu" value="Apellidos" />
                                    <TextInput id="ape_usu" v-model="form.ape_usu" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.ape_usu" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <div>
                                    <InputLabel for="ci_usu" value="CI" />
                                    <TextInput id="ci_usu" v-model="form.ci_usu" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.ci_usu" />
                                </div>

                                <div>
                                    <InputLabel for="ext_ci_usu" value="Extensión CI" />
                                    <TextInput id="ext_ci_usu" v-model="form.ext_ci_usu" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.ext_ci_usu" />
                                </div>

                                <div>
                                    <InputLabel for="fec_nac_usu" value="Fecha de nacimiento" />
                                    <input
                                        id="fec_nac_usu"
                                        type="date"
                                        v-model="form.fec_nac_usu"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    />
                                    <InputError class="mt-2" :message="form.errors.fec_nac_usu" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="fot_usu" value="Foto (opcional)" />
                                <input id="fot_usu" type="file" @change="handleFile" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.fot_usu" />

                                <div v-if="previewFoto" class="mt-4">
                                    <img :src="previewFoto" class="h-28 w-28 rounded object-cover border" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="cor_usu" value="Correo" />
                                <TextInput id="cor_usu" type="email" v-model="form.cor_usu" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.cor_usu" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="cla_usu" value="Nueva contraseña" />
                                    <TextInput id="cla_usu" type="password" v-model="form.cla_usu" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.cla_usu" />
                                </div>

                                <div>
                                    <InputLabel for="cla_usu_confirmation" value="Confirmar nueva contraseña" />
                                    <TextInput id="cla_usu_confirmation" type="password" v-model="form.cla_usu_confirmation" class="mt-1 block w-full" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="est_usu" value="Estado del usuario" />
                                <select id="est_usu" v-model="form.est_usu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option v-for="estado in estadosUsuario" :key="estado" :value="estado">
                                        {{ estado }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.est_usu" />
                            </div>

                            <div class="space-y-6 rounded border border-green-200 bg-green-50 p-4">
                                <h3 class="text-lg font-semibold text-green-800">
                                    Datos del perfil de paciente
                                </h3>

                                <div>
                                    <InputLabel for="sex_pac" value="Sexo" />
                                    <select id="sex_pac" v-model="form.sex_pac" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">Seleccione una opción</option>
                                        <option v-for="sexo in sexos" :key="sexo" :value="sexo">
                                            {{ sexo }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.sex_pac" />
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="pes_pac" value="Peso" />
                                        <input id="pes_pac" type="number" step="0.01" min="0.01" v-model="form.pes_pac"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                        <InputError class="mt-2" :message="form.errors.pes_pac" />
                                    </div>

                                    <div>
                                        <InputLabel for="estatura_pac" value="Estatura" />
                                        <input id="estatura_pac" type="number" step="0.01" min="0.01" v-model="form.estatura_pac"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                        <InputError class="mt-2" :message="form.errors.estatura_pac" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="tip_san_pac" value="Tipo de sangre" />
                                    <TextInput id="tip_san_pac" v-model="form.tip_san_pac" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.tip_san_pac" />
                                </div>

                                <div>
                                    <InputLabel for="ale_pac" value="Alergias" />
                                    <textarea id="ale_pac" v-model="form.ale_pac" rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                    <InputError class="mt-2" :message="form.errors.ale_pac" />
                                </div>

                                <div>
                                    <InputLabel for="est_pac" value="Estado del perfil" />
                                    <select id="est_pac" v-model="form.est_pac" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option v-for="estado in estadosPaciente" :key="estado" :value="estado">
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_pac" />
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">
                                    Actualizar paciente
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
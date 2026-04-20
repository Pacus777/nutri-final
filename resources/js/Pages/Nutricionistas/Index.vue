<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    nutricionistas: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const mostrandoModal = ref(false);
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
    esp_nut: '',
    est_nut: 'activo',
});

const abrirModal = () => {
    mostrandoModal.value = true;
};

const cerrarModal = () => {
    mostrandoModal.value = false;
    previewFoto.value = null;
    form.reset();
    form.clearErrors();
    form.est_usu = 'activo';
    form.est_nut = 'activo';
};

const handleFile = (event) => {
    const file = event.target.files[0];
    form.fot_usu = file ?? null;

    if (file) {
        previewFoto.value = URL.createObjectURL(file);
    } else {
        previewFoto.value = null;
    }
};

const submitRegistroRapido = () => {
    form.post(route('nutricionistas.guardar-registro-rapido'), {
        forceFormData: true,
        onSuccess: () => cerrarModal(),
    });
};

const nombreCompletoNutricionista = (nutricionista) => {
    if (nutricionista.usuario?.nombre_completo) {
        return nutricionista.usuario.nombre_completo;
    }

    const nombre = `${nutricionista.usuario?.nom_usu ?? ''} ${nutricionista.usuario?.ape_usu ?? ''}`.trim();

    return nombre || 'Sin usuario';
};

const cambiarEstado = (codNutricionista) => {
    router.patch(route('nutricionistas.cambiar-estado', codNutricionista));
};
</script>

<template>
    <Head title="Nutricionistas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Gestión de Nutricionistas
                </h2>

                <button
                    type="button"
                    @click="abrirModal"
                    class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                >
                    Nuevo nutricionista
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="nutricionistas.length === 0" class="text-gray-500">
                            No hay nutricionistas registrados.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-4 py-2 text-left">Código</th>
                                        <th class="border px-4 py-2 text-left">Nombre completo</th>
                                        <th class="border px-4 py-2 text-left">CI</th>
                                        <th class="border px-4 py-2 text-left">Correo</th>
                                        <th class="border px-4 py-2 text-left">Especialidad</th>
                                        <th class="border px-4 py-2 text-left">Estado perfil</th>
                                        <th class="border px-4 py-2 text-left">Estado usuario</th>
                                        <th class="border px-4 py-2 text-left">Roles</th>
                                        <th class="border px-4 py-2 text-left">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="nutricionista in nutricionistas"
                                        :key="nutricionista.cod_nutricionistas"
                                    >
                                        <td class="border px-4 py-2">
                                            {{ nutricionista.cod_nutricionistas }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{
                                                nutricionista.usuario?.nombre_completo
                                                    ? nutricionista.usuario.nombre_completo
                                                    : `${nutricionista.usuario?.nom_usu ?? ''} ${nutricionista.usuario?.ape_usu ?? ''}`.trim() || 'Sin usuario'
                                            }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            <span v-if="nutricionista.usuario?.ci_usu">
                                                {{ nutricionista.usuario.ci_usu }} {{ nutricionista.usuario.ext_ci_usu }}
                                            </span>
                                            <span v-else>Sin CI</span>
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ nutricionista.usuario?.cor_usu ?? 'Sin correo' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ nutricionista.esp_nut || 'No especificada' }}
                                        </td>

                                        <td class="border px-4 py-2 capitalize">
                                            {{ nutricionista.est_nut }}
                                        </td>

                                        <td class="border px-4 py-2 capitalize">
                                            {{ nutricionista.usuario?.est_usu ?? 'N/D' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            <span
                                                v-if="nutricionista.usuario?.roles?.length"
                                                class="flex flex-wrap gap-2"
                                            >
                                                <span
                                                    v-for="rol in nutricionista.usuario.roles"
                                                    :key="rol.cod_roles"
                                                    class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800"
                                                >
                                                    {{ rol.nom_rol }}
                                                </span>
                                            </span>
                                            <span v-else class="text-red-600">
                                                Sin roles
                                            </span>
                                        </td>

                                        <td class="border px-4 py-2">
                                            <div class="flex flex-col gap-2">
                                                <Link
                                                    :href="route('nutricionistas.show', nutricionista.cod_nutricionistas)"
                                                    class="text-blue-600 hover:underline"
                                                >
                                                    Ver detalle
                                                </Link>

                                                <Link
                                                    :href="route('nutricionistas.edit', nutricionista.cod_nutricionistas)"
                                                    class="text-indigo-600 hover:underline"
                                                >
                                                    Editar
                                                </Link>

                                                <button
                                                    type="button"
                                                    class="rounded px-2 py-1 text-xs text-white"
                                                    :class="nutricionista.est_nut === 'activo'
                                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                                        : 'bg-green-600 hover:bg-green-700'"
                                                    @click="cambiarEstado(nutricionista.cod_nutricionistas)"
                                                >
                                                    {{ nutricionista.est_nut === 'activo' ? 'Inactivar' : 'Activar' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="page.props.errors?.general"
                            class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700"
                        >
                            {{ page.props.errors.general }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="mostrandoModal" @close="cerrarModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800">
                    Registro rápido de nutricionista
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Aquí se crea el usuario y el perfil de nutricionista en un solo paso.
                </p>

                <form @submit.prevent="submitRegistroRapido" class="mt-6 space-y-6">
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
                                alt="Foto de usuario"
                                class="h-28 w-28 rounded object-cover border"
                            />
                        </div>
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
                            <option value="activo">activo</option>
                            <option value="inactivo">inactivo</option>
                            <option value="bloqueado">bloqueado</option>
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
                                <option value="activo">activo</option>
                                <option value="inactivo">inactivo</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.est_nut" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="cerrarModal"
                            class="rounded bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300"
                        >
                            Cancelar
                        </button>

                        <PrimaryButton :disabled="form.processing">
                            Guardar nutricionista
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
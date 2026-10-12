<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    query: {
        type: String,
        default: '',
    },
    resultados: {
        type: Array,
        default: () => [],
    },
    error_api: {
        type: String,
        default: null,
    },
});

const page = usePage();

const searchForm = useForm({
    q: props.query || '',
});

const importForm = useForm({
    fdc_id: '',
});

const submitSearch = () => {
    searchForm.get(route('paciente.registros-alimenticios.buscar-alimento'));
};

const importarDesdeUsda = (fdcId) => {
    importForm.fdc_id = fdcId;
    importForm.post(route('paciente.registros-alimenticios.importar-usda'), {
        preserveScroll: true,
        onFinish: () => {
            importForm.reset('fdc_id');
        },
    });
};
</script>

<template>
    <Head title="Buscar alimento para registro alimenticio" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        Buscar alimento
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Busca primero en caché local. Si no existe, consulta USDA.
                    </p>
                </div>
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

                <div
                    v-if="error_api"
                    class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700"
                >
                    {{ error_api }}
                </div>

                <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submitSearch" class="mb-8">
                        <div class="flex flex-col gap-4 md:flex-row">
                            <div class="flex-1">
                                <TextInput
                                    v-model="searchForm.q"
                                    type="text"
                                    class="!border-gray-300 !bg-white !text-gray-900"
                                    placeholder="Buscar alimento, por ejemplo: egg, rice, milk"
                                />
                                <InputError class="mt-2" :message="searchForm.errors.q" />
                            </div>

                            <PrimaryButton :disabled="searchForm.processing">
                                Buscar
                            </PrimaryButton>
                        </div>
                    </form>

                    <div v-if="!query" class="text-sm text-gray-500">
                        Ingresa un alimento para buscarlo.
                    </div>

                    <div v-else-if="resultados.length === 0" class="text-sm text-gray-500">
                        No se encontraron resultados para <strong>{{ query }}</strong>.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-4 py-2 text-left">Nombre</th>
                                    <th class="border px-4 py-2 text-left">Origen</th>
                                    <th class="border px-4 py-2 text-left">Marca</th>
                                    <th class="border px-4 py-2 text-left">Tipo</th>
                                    <th class="border px-4 py-2 text-left">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="resultado in resultados"
                                    :key="resultado.origen === 'local' ? resultado.cod_alimentos : resultado.fdc_id"
                                >
                                    <td class="border px-4 py-2">
                                        {{ resultado.nom_ali }}
                                    </td>

                                    <td class="border px-4 py-2">
                                        {{ resultado.origen }}
                                    </td>

                                    <td class="border px-4 py-2">
                                        {{ resultado.mar_ali || 'Sin marca' }}
                                    </td>

                                    <td class="border px-4 py-2">
                                        {{ resultado.tip_ali || 'No definido' }}
                                    </td>

                                    <td class="border px-4 py-2">
                                        <span
                                            v-if="resultado.origen === 'local'"
                                            class="rounded bg-green-100 px-3 py-1 text-sm text-green-800"
                                        >
                                            Código local: {{ resultado.cod_alimentos }}
                                        </span>

                                        <button
                                            v-else
                                            type="button"
                                            class="rounded bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700"
                                            @click="importarDesdeUsda(resultado.fdc_id)"
                                            :disabled="importForm.processing"
                                        >
                                            Importar a caché local
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Cuando el alimento ya existe localmente, puedes usar directamente su código local
                        en el formulario del registro alimenticio. Si no existe, impórtalo desde USDA.
                    </div>

                    <div class="mt-4">
                        <Link
                            href="javascript:history.back()"
                            class="text-sm text-indigo-600 hover:underline"
                        >
                            Volver
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
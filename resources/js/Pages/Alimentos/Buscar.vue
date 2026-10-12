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
    searchForm.get(route('alimentos.resultados'));
};

const importarAlimento = (fdcId) => {
    importForm.fdc_id = fdcId;
    importForm.post(route('alimentos.importar'), {
        preserveScroll: true,
        onFinish: () => {
            importForm.reset('fdc_id');
        },
    });
};

const tipoLegible = (tipo) => {
    if (!tipo) return 'No definido';

    const mapa = {
        Foundation: 'Foundation Food',
        'SR Legacy': 'SR Legacy',
        Branded: 'Branded Food',
        Survey: 'Survey Food',
    };

    return mapa[tipo] || tipo;
};
</script>

<template>
    <Head title="Buscar alimentos USDA" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        Buscar alimentos
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Fuente principal: USDA FoodData Central. Los alimentos importados se guardan en caché local.
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

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submitSearch" class="mb-8">
                            <div class="flex flex-col gap-4 md:flex-row">
                                <div class="flex-1">
                                    <TextInput
                                        v-model="searchForm.q"
                                        type="text"
                                        class="w-full !border-gray-300 !bg-white !text-gray-900 placeholder:!text-gray-400"
                                        placeholder="Buscar alimento, por ejemplo: egg, rice, chicken breast"
                                    />
                                    <InputError class="mt-2" :message="searchForm.errors.q" />
                                </div>

                                <div class="w-full md:w-auto">
                                    <PrimaryButton
                                        class="w-full md:w-auto"
                                        :disabled="searchForm.processing"
                                    >
                                        Buscar
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>

                        <div v-if="!query" class="text-gray-500">
                            Ingresa un alimento para buscar en USDA.
                        </div>

                        <div v-else-if="resultados.length === 0" class="text-gray-500">
                            No se encontraron resultados para: <strong>{{ query }}</strong>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-4 py-2 text-left">Descripción</th>
                                        <th class="border px-4 py-2 text-left">Tipo</th>
                                        <th class="border px-4 py-2 text-left">Marca</th>
                                        <th class="border px-4 py-2 text-left">Categoría</th>
                                        <th class="border px-4 py-2 text-left">Referencia USDA</th>
                                        <th class="border px-4 py-2 text-left">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="resultado in resultados"
                                        :key="resultado.fdc_id"
                                    >
                                        <td class="border px-4 py-2">
                                            {{ resultado.description || 'Sin descripción' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ tipoLegible(resultado.data_type) }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ resultado.brand_name || 'Sin marca' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ resultado.food_category || 'Sin categoría' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ resultado.fdc_id }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            <button
                                                type="button"
                                                class="rounded bg-green-600 px-3 py-1 text-sm text-white hover:bg-green-700"
                                                @click="importarAlimento(resultado.fdc_id)"
                                                :disabled="importForm.processing"
                                            >
                                                Importar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            Los resultados se consultan en USDA. Cuando importas un alimento, el sistema guarda
                            una copia local y su composición nutricional para reutilizarla después sin depender
                            siempre de la API.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
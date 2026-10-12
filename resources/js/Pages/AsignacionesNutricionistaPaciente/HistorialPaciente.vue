<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    paciente: Object,
    historial: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
</script>

<template>
    <Head title="Historial de asignaciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Historial de Asignaciones
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ paciente.usuario?.nom_usu ?? 'Paciente' }} - {{ paciente.usuario?.cor_usu ?? '' }}
                    </p>
                </div>

                <Link
                    :href="route('asignaciones.index')"
                    class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="paciente.asignacion_activa" class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                            Asignación activa actual: {{ paciente.asignacion_activa.nutricionista?.usuario?.nom_usu ?? 'No disponible' }}
                        </div>

                        <div v-if="historial.length === 0" class="text-gray-500">
                            Este paciente no tiene historial de asignaciones.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-4 py-2 text-left">Código</th>
                                        <th class="border px-4 py-2 text-left">Fecha</th>
                                        <th class="border px-4 py-2 text-left">Estado</th>
                                        <th class="border px-4 py-2 text-left">Nutricionista</th>
                                        <th class="border px-4 py-2 text-left">Correo</th>
                                        <th class="border px-4 py-2 text-left">Especialidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="asignacion in historial"
                                        :key="asignacion.cod_asignacion_nutricionista_paciente"
                                    >
                                        <td class="border px-4 py-2">
                                            {{ asignacion.cod_asignacion_nutricionista_paciente }}
                                        </td>
                                        <td class="border px-4 py-2">
                                            {{ asignacion.fec_anp }}
                                        </td>
                                        <td class="border px-4 py-2 capitalize">
                                            {{ asignacion.est_anp }}
                                        </td>
                                        <td class="border px-4 py-2">
                                            {{ asignacion.nutricionista?.usuario?.nom_usu ?? 'Sin nutricionista' }}
                                        </td>
                                        <td class="border px-4 py-2">
                                            {{ asignacion.nutricionista?.usuario?.cor_usu ?? 'Sin correo' }}
                                        </td>
                                        <td class="border px-4 py-2">
                                            {{ asignacion.nutricionista?.esp_nut || 'No especificada' }}
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
    </AuthenticatedLayout>
</template>
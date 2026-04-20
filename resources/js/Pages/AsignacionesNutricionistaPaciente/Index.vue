<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    asignaciones: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const actualizarEstado = (codAsignacion, estado) => {
    router.patch(route('asignaciones.actualizar-estado', codAsignacion), {
        est_anp: estado,
    });
};
</script>

<template>
    <Head title="Asignaciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Asignación Nutricionista - Paciente
                </h2>

                <Link
                    :href="route('asignaciones.create')"
                    class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                >
                    Nueva asignación
                </Link>
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
                        <div v-if="asignaciones.length === 0" class="text-gray-500">
                            No hay asignaciones registradas.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-4 py-2 text-left">Código</th>
                                        <th class="border px-4 py-2 text-left">Paciente</th>
                                        <th class="border px-4 py-2 text-left">Correo paciente</th>
                                        <th class="border px-4 py-2 text-left">Nutricionista</th>
                                        <th class="border px-4 py-2 text-left">Correo nutricionista</th>
                                        <th class="border px-4 py-2 text-left">Fecha</th>
                                        <th class="border px-4 py-2 text-left">Estado</th>
                                        <th class="border px-4 py-2 text-left">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="asignacion in asignaciones"
                                        :key="asignacion.cod_asignacion_nutricionista_paciente"
                                    >
                                        <td class="border px-4 py-2">
                                            {{ asignacion.cod_asignacion_nutricionista_paciente }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ asignacion.paciente?.usuario?.nom_usu ?? 'Sin paciente' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ asignacion.paciente?.usuario?.cor_usu ?? 'Sin correo' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ asignacion.nutricionista?.usuario?.nom_usu ?? 'Sin nutricionista' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ asignacion.nutricionista?.usuario?.cor_usu ?? 'Sin correo' }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            {{ asignacion.fec_anp }}
                                        </td>

                                        <td class="border px-4 py-2 capitalize">
                                            {{ asignacion.est_anp }}
                                        </td>

                                        <td class="border px-4 py-2">
                                            <div class="flex flex-col gap-2">
                                                <Link
                                                    v-if="asignacion.paciente?.cod_pacientes"
                                                    :href="route('asignaciones.historial-paciente', asignacion.paciente.cod_pacientes)"
                                                    class="text-blue-600 hover:underline"
                                                >
                                                    Ver historial
                                                </Link>

                                                <button
                                                    v-if="asignacion.est_anp === 'activa'"
                                                    type="button"
                                                    class="rounded bg-yellow-500 px-2 py-1 text-xs text-white hover:bg-yellow-600"
                                                    @click="actualizarEstado(asignacion.cod_asignacion_nutricionista_paciente, 'cerrada')"
                                                >
                                                    Cerrar
                                                </button>

                                                <button
                                                    v-if="asignacion.est_anp === 'activa'"
                                                    type="button"
                                                    class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700"
                                                    @click="actualizarEstado(asignacion.cod_asignacion_nutricionista_paciente, 'reasignada')"
                                                >
                                                    Reasignar
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
    </AuthenticatedLayout>
</template>
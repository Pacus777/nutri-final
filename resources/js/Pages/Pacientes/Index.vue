<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    pacientes: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const cambiarEstado = (codPaciente) => {
    router.patch(route('pacientes.cambiar-estado', codPaciente));
};
</script>

<template>
    <Head title="Pacientes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Gestión de Pacientes
                </h2>

                <Link
                    :href="route('pacientes.create')"
                    class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                >
                    Nuevo paciente
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
                        <div v-if="pacientes.length === 0" class="text-gray-500">
                            No hay pacientes registrados.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-4 py-2 text-left">Código</th>
                                        <th class="border px-4 py-2 text-left">Usuario</th>
                                        <th class="border px-4 py-2 text-left">Correo</th>
                                        <th class="border px-4 py-2 text-left">Fecha</th>
                                        <th class="border px-4 py-2 text-left">Edad</th>
                                        <th class="border px-4 py-2 text-left">Sexo</th>
                                        <th class="border px-4 py-2 text-left">Peso</th>
                                        <th class="border px-4 py-2 text-left">Talla</th>
                                        <th class="border px-4 py-2 text-left">Estado perfil</th>
                                        <th class="border px-4 py-2 text-left">Estado usuario</th>
                                        <th class="border px-4 py-2 text-left">Roles</th>
                                        <th class="border px-4 py-2 text-left">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="paciente in pacientes"
                                        :key="paciente.cod_pacientes"
                                    >
                                        <td class="border px-4 py-2">{{ paciente.cod_pacientes }}</td>
                                        <td class="border px-4 py-2">{{ paciente.usuario?.nom_usu ?? 'Sin usuario' }}</td>
                                        <td class="border px-4 py-2">{{ paciente.usuario?.cor_usu ?? 'Sin correo' }}</td>
                                        <td class="border px-4 py-2">{{ paciente.fec_pac }}</td>
                                        <td class="border px-4 py-2">{{ paciente.eda_pac }}</td>
                                        <td class="border px-4 py-2 capitalize">{{ paciente.sex_pac }}</td>
                                        <td class="border px-4 py-2">{{ paciente.pes_pac }}</td>
                                        <td class="border px-4 py-2">{{ paciente.tal_pac }}</td>
                                        <td class="border px-4 py-2 capitalize">{{ paciente.est_pac }}</td>
                                        <td class="border px-4 py-2 capitalize">{{ paciente.usuario?.est_usu ?? 'N/D' }}</td>

                                        <td class="border px-4 py-2">
                                            <span
                                                v-if="paciente.usuario?.roles?.length"
                                                class="flex flex-wrap gap-2"
                                            >
                                                <span
                                                    v-for="rol in paciente.usuario.roles"
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
                                                    :href="route('pacientes.show', paciente.cod_pacientes)"
                                                    class="text-blue-600 hover:underline"
                                                >
                                                    Ver ficha
                                                </Link>

                                                <Link
                                                    :href="route('pacientes.edit', paciente.cod_pacientes)"
                                                    class="text-indigo-600 hover:underline"
                                                >
                                                    Editar
                                                </Link>

                                                <button
                                                    type="button"
                                                    class="rounded px-2 py-1 text-xs text-white"
                                                    :class="paciente.est_pac === 'activo'
                                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                                        : 'bg-green-600 hover:bg-green-700'"
                                                    @click="cambiarEstado(paciente.cod_pacientes)"
                                                >
                                                    {{ paciente.est_pac === 'activo' ? 'Inactivar' : 'Activar' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
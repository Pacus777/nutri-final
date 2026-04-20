<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    nutricionistas: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

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

                <Link
                    :href="route('nutricionistas.create')"
                    class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                >
                    Nuevo nutricionista
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
                        <div v-if="nutricionistas.length === 0" class="text-gray-500">
                            No hay nutricionistas registrados.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-4 py-2 text-left">Código</th>
                                        <th class="border px-4 py-2 text-left">Usuario</th>
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
                                            {{ nutricionista.usuario?.nom_usu ?? 'Sin usuario' }}
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
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
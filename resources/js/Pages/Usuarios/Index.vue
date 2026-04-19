<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    usuarios: {
        type: Array,
        default: () => [],
    },
});

const cambiarEstado = (codUsuario, estado) => {
    router.patch(route('usuarios.cambiar-estado', codUsuario), {
        est_usu: estado,
    });
};

const page = usePage();
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Gestión de Usuarios
                </h2>

                <Link
                    :href="route('usuarios.create')"
                    class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                >
                    Nuevo usuario
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
                        <div v-if="usuarios.length === 0" class="text-gray-500">
                            No hay usuarios registrados.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-4 py-2 text-left">Código</th>
                                        <th class="border px-4 py-2 text-left">Nombre</th>
                                        <th class="border px-4 py-2 text-left">Correo</th>
                                        <th class="border px-4 py-2 text-left">Estado</th>
                                        <th class="border px-4 py-2 text-left">Roles</th>
                                        <th class="border px-4 py-2 text-left">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="usuario in usuarios" :key="usuario.cod_usuarios">
                                        <td class="border px-4 py-2">{{ usuario.cod_usuarios }}</td>
                                        <td class="border px-4 py-2">{{ usuario.nom_usu }}</td>
                                        <td class="border px-4 py-2">{{ usuario.cor_usu }}</td>
                                        <td class="border px-4 py-2 capitalize">{{ usuario.est_usu }}</td>
                                        <td class="border px-4 py-2">
                                            <span
                                                v-if="usuario.roles.length"
                                                class="flex flex-wrap gap-2"
                                            >
                                                <span
                                                    v-for="rol in usuario.roles"
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
                                                    :href="route('usuarios.edit', usuario.cod_usuarios)"
                                                    class="text-indigo-600 hover:underline"
                                                >
                                                    Editar
                                                </Link>

                                                <div class="flex flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        class="rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700"
                                                        @click="cambiarEstado(usuario.cod_usuarios, 'activo')"
                                                    >
                                                        Activar
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="rounded bg-yellow-500 px-2 py-1 text-xs text-white hover:bg-yellow-600"
                                                        @click="cambiarEstado(usuario.cod_usuarios, 'inactivo')"
                                                    >
                                                        Inactivar
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700"
                                                        @click="cambiarEstado(usuario.cod_usuarios, 'bloqueado')"
                                                    >
                                                        Bloquear
                                                    </button>
                                                </div>
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
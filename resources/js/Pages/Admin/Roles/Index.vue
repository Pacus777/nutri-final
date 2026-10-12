<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const claseEstado = (estado) => {
    if (estado === 'activo') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    return 'bg-rose-100 text-rose-700 ring-rose-200';
};
</script>

<template>
    <Head title="Roles" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Administración de roles
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Consulta los roles base del sistema y su estado actual.
                    </p>
                </div>

                <Link
                    :href="route('admin.usuarios.index')"
                    class="motion-btn rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-800"
                >
                    Volver a usuarios
                </Link>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <div v-if="roles.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No hay roles registrados.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Código</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Rol</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Descripción</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Estado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Usuarios</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="rol in roles" :key="rol.cod_roles" class="hover:bg-slate-50">
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ rol.cod_roles }}</td>
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ rol.nom_rol }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ rol.des_rol || 'Sin descripción' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1" :class="claseEstado(rol.est_rol)">
                                                {{ rol.est_rol }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ rol.usuarios_count }}</td>
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

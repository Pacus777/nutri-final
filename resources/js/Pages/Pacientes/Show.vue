<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    paciente: Object,
});
</script>

<template>
    <Head title="Ficha del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Ficha del Paciente
                </h2>

                <div class="flex gap-2">
                    <Link
                        :href="route('pacientes.edit', paciente.cod_pacientes)"
                        class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    >
                        Editar
                    </Link>

                    <Link
                        :href="route('pacientes.index')"
                        class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Datos del paciente
                        </h3>

                        <div class="mt-4 space-y-3 text-sm text-gray-700">
                            <p><strong>Código:</strong> {{ paciente.cod_pacientes }}</p>
                            <p><strong>Fecha de registro:</strong> {{ paciente.fec_pac }}</p>
                            <p><strong>Edad:</strong> {{ paciente.eda_pac }}</p>
                            <p><strong>Sexo:</strong> {{ paciente.sex_pac }}</p>
                            <p><strong>Peso:</strong> {{ paciente.pes_pac }}</p>
                            <p><strong>Talla:</strong> {{ paciente.tal_pac }}</p>
                            <p><strong>Estado del perfil:</strong> {{ paciente.est_pac }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Usuario vinculado
                        </h3>

                        <div class="mt-4 space-y-3 text-sm text-gray-700">
                            <p><strong>Nombre:</strong> {{ paciente.usuario?.nom_usu ?? 'No disponible' }}</p>
                            <p><strong>Correo:</strong> {{ paciente.usuario?.cor_usu ?? 'No disponible' }}</p>
                            <p><strong>Estado de usuario:</strong> {{ paciente.usuario?.est_usu ?? 'No disponible' }}</p>

                            <div>
                                <strong>Roles:</strong>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-for="rol in paciente.usuario?.roles ?? []"
                                        :key="rol.cod_roles"
                                        class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800"
                                    >
                                        {{ rol.nom_rol }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
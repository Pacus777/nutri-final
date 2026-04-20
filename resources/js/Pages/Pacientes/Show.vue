<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    paciente: Object,
});

const nombreCompleto = (usuario) => {
    if (!usuario) return 'No disponible';

    if (usuario.nombre_completo) {
        return usuario.nombre_completo;
    }

    const nombre = `${usuario.nom_usu ?? ''} ${usuario.ape_usu ?? ''}`.trim();
    return nombre || 'No disponible';
};
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
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Datos del paciente
                        </h3>

                        <div class="mt-4 space-y-3 text-sm text-gray-700">
                            <p><strong>Código:</strong> {{ paciente.cod_pacientes }}</p>
                            <p><strong>Sexo:</strong> {{ paciente.sex_pac }}</p>
                            <p><strong>Peso:</strong> {{ paciente.pes_pac }}</p>
                            <p><strong>Estatura:</strong> {{ paciente.estatura_pac }}</p>
                            <p><strong>Tipo de sangre:</strong> {{ paciente.tip_san_pac || 'No especificado' }}</p>
                            <p><strong>Alergias:</strong> {{ paciente.ale_pac || 'No registradas' }}</p>
                            <p><strong>Estado del perfil:</strong> {{ paciente.est_pac }}</p>
                            <p><strong>Fecha de registro:</strong> {{ paciente.fecha_registro || 'No disponible' }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Usuario vinculado
                        </h3>

                        <div
                            v-if="paciente.usuario?.fot_usu"
                            class="mb-4"
                        >
                            <img
                                :src="`/storage/${paciente.usuario.fot_usu}`"
                                alt="Foto de paciente"
                                class="h-28 w-28 rounded object-cover border"
                            />
                        </div>

                        <div class="mt-4 space-y-3 text-sm text-gray-700">
                            <p><strong>Nombre completo:</strong> {{ nombreCompleto(paciente.usuario) }}</p>
                            <p>
                                <strong>CI:</strong>
                                <span v-if="paciente.usuario?.ci_usu">
                                    {{ paciente.usuario.ci_usu }} {{ paciente.usuario.ext_ci_usu }}
                                </span>
                                <span v-else>No disponible</span>
                            </p>
                            <p><strong>Fecha de nacimiento:</strong> {{ paciente.usuario?.fec_nac_usu ?? 'No disponible' }}</p>
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
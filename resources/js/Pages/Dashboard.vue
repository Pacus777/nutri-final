<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const usuario = computed(() => page.props.auth?.user ?? null);
const rolActivo = computed(() => page.props.auth?.rol_activo ?? null);

const mensajeRol = computed(() => {
    switch (rolActivo.value) {
        case 'SuperAdmin':
            return 'Tienes acceso técnico y administrativo de alto nivel.';
        case 'Administrador':
            return 'Estás gestionando la operación administrativa del sistema.';
        case 'Nutricionista':
            return 'Estás operando como profesional de atención nutricional.';
        case 'Paciente':
            return 'Estás accediendo a tu espacio personal dentro del sistema.';
        default:
            return 'No hay un rol activo definido.';
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Bienvenido
                        </h3>
                        <p class="mt-2 text-gray-600">
                            {{ usuario?.nom_usu }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ usuario?.cor_usu }}
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Rol activo
                        </h3>
                        <p class="mt-2 text-indigo-700 font-medium">
                            {{ rolActivo ?? 'Sin rol activo' }}
                        </p>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ mensajeRol }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Estado actual del módulo
                    </h3>
                    <p class="mt-2 text-sm text-gray-600">
                        El sistema ya cuenta con autenticación personalizada, control por estado,
                        gestión de usuarios, validación por rol activo y cambio de rol en sesión.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
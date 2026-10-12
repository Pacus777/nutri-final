<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    asignacion: Object,
});

const nombreCompleto = (usuario) => {
    if (!usuario) {
        return 'No disponible';
    }

    return usuario.nom_usu && usuario.ape_usu
        ? `${usuario.nom_usu} ${usuario.ape_usu}`
        : usuario.nom_usu || 'No disponible';
};
</script>

<template>
    <Head title="Detalle de asignación" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Detalle de asignación</h2>
                    <p class="mt-1 text-sm text-gray-500">Revisión puntual de la relación paciente-nutricionista.</p>
                </div>
                <Link :href="route('asignaciones.index')" class="motion-btn rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-800">Volver</Link>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900 space-y-4">
                        <p><strong>Código:</strong> {{ asignacion.cod_asignacion_nutricionista_paciente }}</p>
                        <p><strong>Fecha:</strong> {{ asignacion.fec_anp }}</p>
                        <p><strong>Estado:</strong> {{ asignacion.est_anp }}</p>
                        <p><strong>Paciente:</strong> {{ nombreCompleto(asignacion.paciente?.usuario) }}</p>
                        <p><strong>Nutricionista:</strong> {{ nombreCompleto(asignacion.nutricionista?.usuario) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

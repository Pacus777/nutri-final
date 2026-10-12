<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    paciente: Object,
    planes: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Planes por paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Planes nutricionales del paciente</h2>
                    <p class="mt-1 text-sm text-gray-500">Listado histórico y acceso directo al plan vigente.</p>
                </div>
                <Link :href="route('planes-nutricionales.create', { paciente: paciente.cod_pacientes })" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700">Nuevo plan</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <p class="text-sm text-slate-600">{{ paciente.usuario?.nombre_completo || 'Paciente sin nombre' }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-[0.12em] text-slate-500">
                                <tr>
                                    <th class="px-6 py-3">Fecha</th>
                                    <th class="px-6 py-3">Estado</th>
                                    <th class="px-6 py-3">Nutricionista</th>
                                    <th class="px-6 py-3">Descripción</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="plan in planes" :key="plan.cod_planes_nutricionales">
                                    <td class="px-6 py-4 text-slate-600">{{ plan.fec_pan }}</td>
                                    <td class="px-6 py-4 capitalize text-slate-700">{{ plan.est_pan }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ plan.nutricionista?.usuario?.nombre_completo || 'No disponible' }}</td>
                                    <td class="px-6 py-4 text-slate-700">{{ plan.des_pan }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="route('planes-nutricionales.show', plan.cod_planes_nutricionales)" class="font-semibold text-cyan-700 hover:text-cyan-900">Ver</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    plan: Object,
});

const actionForm = useForm({});
const cerrar = () => actionForm.patch(route('planes-nutricionales.cerrar', props.plan.cod_planes_nutricionales));
const reemplazar = () => actionForm.patch(route('planes-nutricionales.reemplazar', props.plan.cod_planes_nutricionales));
</script>

<template>
    <Head title="Detalle de plan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Plan nutricional</h2>
                    <p class="mt-1 text-sm text-gray-500">Vista completa del plan y sus acciones de ciclo de vida.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('planes-nutricionales.edit', plan.cod_planes_nutricionales)" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Editar</Link>
                    <button type="button" @click="cerrar" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Cerrar</button>
                    <button type="button" @click="reemplazar" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Reemplazar</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Plan</p>
                            <h3 class="mt-2 text-2xl font-bold text-slate-900">{{ plan.fec_pan }}</h3>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{{ plan.est_pan }}</span>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-700">{{ plan.des_pan }}</p>
                </section>

                <section class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Paciente</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ plan.paciente?.usuario?.nombre_completo || 'No disponible' }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Nutricionista</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ plan.nutricionista?.usuario?.nombre_completo || 'No disponible' }}</p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

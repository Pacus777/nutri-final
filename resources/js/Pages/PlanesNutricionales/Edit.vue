<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    plan: Object,
    estados: {
        type: Array,
        default: () => ['activo', 'vigente', 'pausado', 'finalizado', 'reemplazado', 'cancelado'],
    },
});

const form = useForm({
    fec_pan: props.plan.fec_pan || '',
    des_pan: props.plan.des_pan || '',
    est_pan: props.plan.est_pan || 'vigente',
});

const submit = () => {
    form.put(route('planes-nutricionales.update', props.plan.cod_planes_nutricionales));
};
</script>

<template>
    <Head title="Editar plan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar plan nutricional</h2>
                    <p class="mt-1 text-sm text-gray-500">Mantén el histórico mientras actualizas el contenido del plan.</p>
                </div>
                <Link :href="route('planes-nutricionales.show', plan.cod_planes_nutricionales)" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Volver</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="fec_pan" value="Fecha" />
                                <input id="fec_pan" v-model="form.fec_pan" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                <InputError class="mt-2" :message="form.errors.fec_pan" />
                            </div>

                            <div>
                                <InputLabel for="est_pan" value="Estado" />
                                <select id="est_pan" v-model="form.est_pan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option v-for="estado in estados" :key="estado" :value="estado">{{ estado }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.est_pan" />
                            </div>

                            <div>
                                <InputLabel for="des_pan" value="Descripción" />
                                <textarea id="des_pan" v-model="form.des_pan" rows="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                                <InputError class="mt-2" :message="form.errors.des_pan" />
                            </div>

                            <div class="flex justify-end gap-3">
                                <Link :href="route('planes-nutricionales.show', plan.cod_planes_nutricionales)" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Cancelar</Link>
                                <PrimaryButton :disabled="form.processing">Guardar cambios</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

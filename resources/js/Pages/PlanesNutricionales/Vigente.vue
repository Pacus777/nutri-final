<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pacientes: Array,
    nutricionistas: Array,
    estados: Array,
    pacienteSeleccionado: [String, Number],
    nutricionistaSeleccionado: [String, Number],
});

const form = useForm({
    cod_pacientes_pan: props.pacienteSeleccionado || '',
    cod_nutricionistas_pan: props.nutricionistaSeleccionado || '',
    fec_pan: '',
    des_pan: '',
    est_pan: 'vigente',
});

const submit = () => {
    form.post(route('planes-nutricionales.store'));
};
</script>

<template>
    <Head title="Crear plan nutricional" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold">Nuevo Plan Nutricional</h2>
        </template>

        <div class="p-6 max-w-3xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <select v-model="form.cod_pacientes_pan" class="w-full">
                    <option v-for="p in pacientes" :value="p.cod_pacientes">
                        {{ p.usuario.nombre_completo }}
                    </option>
                </select>

                <textarea v-model="form.des_pan" rows="5" class="w-full"></textarea>

                <input type="date" v-model="form.fec_pan" class="w-full" />

                <PrimaryButton class="w-full">Guardar plan</PrimaryButton>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
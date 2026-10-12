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

                <div>
                    <label class="block text-sm font-medium">Paciente</label>
                    <select v-model="form.cod_pacientes_pan" class="w-full mt-1">
                        <option value="" disabled>Selecciona un paciente</option>
                        <option v-for="p in pacientes" :key="p.cod_pacientes" :value="p.cod_pacientes">
                            {{ p.usuario.nombre_completo }}
                        </option>
                    </select>
                    <p v-if="form.errors.cod_pacientes_pan" class="text-red-600 text-sm mt-1">{{ form.errors.cod_pacientes_pan }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Nutricionista</label>
                    <select v-model="form.cod_nutricionistas_pan" class="w-full mt-1">
                        <option value="" disabled>Selecciona un nutricionista</option>
                        <option v-for="n in nutricionistas" :key="n.cod_nutricionistas" :value="n.cod_nutricionistas">
                            {{ n.usuario.nombre_completo }}
                        </option>
                    </select>
                    <p v-if="form.errors.cod_nutricionistas_pan" class="text-red-600 text-sm mt-1">{{ form.errors.cod_nutricionistas_pan }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Descripción</label>
                    <textarea v-model="form.des_pan" rows="5" class="w-full mt-1"></textarea>
                    <p v-if="form.errors.des_pan" class="text-red-600 text-sm mt-1">{{ form.errors.des_pan }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Fecha</label>
                        <input type="date" v-model="form.fec_pan" class="w-full mt-1" />
                        <p v-if="form.errors.fec_pan" class="text-red-600 text-sm mt-1">{{ form.errors.fec_pan }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Estado</label>
                        <select v-model="form.est_pan" class="w-full mt-1">
                            <option value="" disabled>Selecciona un estado</option>
                            <option v-for="e in estados" :key="e" :value="e">{{ e }}</option>
                        </select>
                        <p v-if="form.errors.est_pan" class="text-red-600 text-sm mt-1">{{ form.errors.est_pan }}</p>
                    </div>
                </div>

                <PrimaryButton class="w-full">Guardar plan</PrimaryButton>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
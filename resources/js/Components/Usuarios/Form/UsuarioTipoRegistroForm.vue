<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },
    descripcionTipo: {
        type: String,
        required: true,
    },
});

const claseSelectClaro = 'app-select';
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
        <InputLabel for="tipo_registro" value="Tipo de registro" />

        <div class="relative">
            <select
                id="tipo_registro"
                v-model="form.tipo_registro"
                :class="claseSelectClaro"
            >
                <option value="Administrador">Administrador</option>
                <option value="Nutricionista">Nutricionista</option>
                <option value="Paciente">Paciente</option>
            </select>

            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </span>
        </div>

        <InputError class="mt-2" :message="form.errors.tipo_registro" />

        <p class="mt-2 text-xs text-slate-500">
            {{ descripcionTipo }}
        </p>
    </div>
</template>
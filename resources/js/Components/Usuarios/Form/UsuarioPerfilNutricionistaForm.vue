<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },
    descripcion: {
        type: String,
        default: 'Completa estos campos para habilitar el perfil profesional del nutricionista.',
    },
    bloquearEstado: {
        type: Boolean,
        default: false,
    },
});

const claseInputClaro =
    'mt-1 block w-full !rounded-xl !border-slate-300 !bg-white !text-slate-900 placeholder:!text-slate-400 focus:!border-emerald-500 focus:!ring-emerald-500';

const claseSelectClaro = 'app-select';
</script>

<template>
    <div class="space-y-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
        <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-800">
            Datos del perfil de nutricionista
        </h3>

        <div class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs text-blue-700">
            {{ descripcion }}
        </div>

        <div>
            <InputLabel for="esp_nut" value="Especialidad" />
            <TextInput
                id="esp_nut"
                type="text"
                :class="claseInputClaro"
                v-model="form.esp_nut"
            />
            <InputError class="mt-2" :message="form.errors.esp_nut" />
        </div>

        <div>
            <InputLabel for="est_nut" value="Estado del perfil" />

            <select
                id="est_nut"
                v-model="form.est_nut"
                :class="claseSelectClaro"
                :disabled="bloquearEstado"
            >
                <option value="activo">activo</option>
                <option value="inactivo">inactivo</option>
            </select>

            <p
                v-if="bloquearEstado"
                class="mt-1 text-xs text-slate-500"
            >
                Este estado se sincroniza automáticamente con el estado del usuario.
            </p>

            <InputError class="mt-2" :message="form.errors.est_nut" />
        </div>
    </div>
</template>
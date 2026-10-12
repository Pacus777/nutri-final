<script setup>
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import FormSection from '@/Components/UI/FormSection.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },
    estados: {
        type: Array,
        default: () => [],
    },
    modo: {
        type: String,
        default: 'crear',
    },
});

const mostrarContrasena = ref(false);
const mostrarConfirmacion = ref(false);

const claseInputClaro =
    'mt-1 block w-full !rounded-xl !border-slate-300 !bg-white !text-slate-900 placeholder:!text-slate-400 focus:!border-emerald-500 focus:!ring-emerald-500';

const claseControlClaro =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';

const claseSelectClaro = 'app-select';
</script>

<template>
    <FormSection title="Datos de acceso">
        <div>
            <InputLabel for="cor_usu" value="Correo" />
            <TextInput
                id="cor_usu"
                type="email"
                :class="claseInputClaro"
                v-model="form.cor_usu"
                required
            />
            <InputError class="mt-2" :message="form.errors.cor_usu" />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel
                    for="cla_usu"
                    :value="modo === 'editar' ? 'Nueva contraseña' : 'Contraseña'"
                />

                <div class="relative">
                    <input
                        id="cla_usu"
                        v-model="form.cla_usu"
                        :type="mostrarContrasena ? 'text' : 'password'"
                        :class="`${claseControlClaro} pr-10`"
                        :required="modo === 'crear'"
                        :placeholder="modo === 'editar' ? 'Opcional' : ''"
                    >

                    <button
                        type="button"
                        class="motion-btn absolute inset-y-0 right-2 flex items-center rounded px-2 text-slate-500 hover:text-slate-700"
                        @click="mostrarContrasena = !mostrarContrasena"
                    >
                        {{ mostrarContrasena ? 'Ocultar' : 'Ver' }}
                    </button>
                </div>

                <InputError class="mt-2" :message="form.errors.cla_usu" />
            </div>

            <div>
                <InputLabel
                    for="cla_usu_confirmation"
                    :value="modo === 'editar' ? 'Confirmar nueva contraseña' : 'Confirmar contraseña'"
                />

                <div class="relative">
                    <input
                        id="cla_usu_confirmation"
                        v-model="form.cla_usu_confirmation"
                        :type="mostrarConfirmacion ? 'text' : 'password'"
                        :class="`${claseControlClaro} pr-10`"
                        :required="modo === 'crear'"
                        :placeholder="modo === 'editar' ? 'Opcional' : ''"
                    >

                    <button
                        type="button"
                        class="motion-btn absolute inset-y-0 right-2 flex items-center rounded px-2 text-slate-500 hover:text-slate-700"
                        @click="mostrarConfirmacion = !mostrarConfirmacion"
                    >
                        {{ mostrarConfirmacion ? 'Ocultar' : 'Ver' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <InputLabel for="est_usu" value="Estado del usuario" />

            <select
                id="est_usu"
                v-model="form.est_usu"
                :class="claseSelectClaro"
            >
                <option
                    v-for="estado in estados"
                    :key="estado"
                    :value="estado"
                >
                    {{ estado }}
                </option>
            </select>

            <InputError class="mt-2" :message="form.errors.est_usu" />
        </div>
    </FormSection>
</template>
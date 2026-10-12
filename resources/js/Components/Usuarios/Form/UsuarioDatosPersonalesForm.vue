<script setup>
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import FormSection from '@/Components/UI/FormSection.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    previewInicial: {
        type: String,
        default: null,
    },
});

const previewFoto = ref(props.previewInicial);
const fechaNacimientoInput = ref(null);

const extensionesCi = [
    'LP',
    'CB',
    'SC',
    'OR',
    'PT',
    'TJ',
    'CH',
    'BE',
    'PD',
    'BN',
    'EX',
];

const claseInputClaro =
    'mt-1 block w-full !rounded-xl !border-slate-300 !bg-white !text-slate-900 placeholder:!text-slate-400 focus:!border-emerald-500 focus:!ring-emerald-500';

const claseControlClaro =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';

const claseSelectClaro = 'app-select';

const hoy = new Date().toISOString().slice(0, 10);

const limpiarSoloLetras = (campo) => {
    props.form[campo] = String(props.form[campo] ?? '')
        .replace(/[^\p{L}\s]/gu, '')
        .replace(/\s{2,}/g, ' ')
        .trimStart();
};

const limpiarSoloNumeros = () => {
    props.form.ci_usu = String(props.form.ci_usu ?? '')
        .replace(/\D/g, '')
        .slice(0, 12);
};

const handleFile = (event) => {
    const file = event.target.files[0];
    props.form.fot_usu = file ?? null;

    if (file) {
        previewFoto.value = URL.createObjectURL(file);
        return;
    }

    previewFoto.value = props.previewInicial;
};

const abrirSelectorFecha = () => {
    const input = fechaNacimientoInput.value;

    if (!input) {
        return;
    }

    input.focus();

    if (typeof input.showPicker === 'function') {
        input.showPicker();
    }
};
</script>

<template>
    <FormSection title="Datos personales">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel for="nom_usu" value="Nombres" />
                <TextInput
                    id="nom_usu"
                    type="text"
                    :class="claseInputClaro"
                    v-model="form.nom_usu"
                    maxlength="120"
                    autocomplete="given-name"
                    required
                    @input="limpiarSoloLetras('nom_usu')"
                />
                <InputError class="mt-2" :message="form.errors.nom_usu" />
            </div>

            <div>
                <InputLabel for="ape_usu" value="Apellidos" />
                <TextInput
                    id="ape_usu"
                    type="text"
                    :class="claseInputClaro"
                    v-model="form.ape_usu"
                    maxlength="120"
                    autocomplete="family-name"
                    required
                    @input="limpiarSoloLetras('ape_usu')"
                />
                <InputError class="mt-2" :message="form.errors.ape_usu" />
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div>
                <InputLabel for="ci_usu" value="CI" />
                <TextInput
                    id="ci_usu"
                    type="tel"
                    :class="claseInputClaro"
                    v-model="form.ci_usu"
                    minlength="8"
                    maxlength="12"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Solo números (8 a 12)"
                    required
                    @input="limpiarSoloNumeros"
                />
                <InputError class="mt-2" :message="form.errors.ci_usu" />
            </div>

            <div>
                <InputLabel for="ext_ci_usu" value="Extensión CI" />
                <div class="relative">
                    <select
                        id="ext_ci_usu"
                        :class="claseSelectClaro"
                        v-model="form.ext_ci_usu"
                        required
                    >
                        <option
                            v-for="extension in extensionesCi"
                            :key="extension"
                            :value="extension"
                        >
                            {{ extension }}
                        </option>
                    </select>
                </div>
                <InputError class="mt-2" :message="form.errors.ext_ci_usu" />
            </div>

            <div>
                <InputLabel for="fec_nac_usu" value="Fecha de nacimiento" />

                <div class="relative">
                    <input
                        ref="fechaNacimientoInput"
                        id="fec_nac_usu"
                        type="date"
                        v-model="form.fec_nac_usu"
                        :max="hoy"
                        :class="`${claseControlClaro} pr-10`"
                    >

                    <button
                        type="button"
                        class="motion-btn absolute inset-y-0 right-1.5 flex items-center rounded-lg px-2 text-slate-400 hover:text-slate-600"
                        @click="abrirSelectorFecha"
                        aria-label="Abrir selector de fecha"
                    >
                        📅
                    </button>
                </div>

                <p class="mt-1 text-xs text-slate-500">
                    Selecciona una fecha válida anterior o igual a hoy.
                </p>

                <InputError class="mt-2" :message="form.errors.fec_nac_usu" />
            </div>
        </div>

        <div class="mt-6">
            <InputLabel for="fot_usu" value="Foto (opcional)" />

            <input
                id="fot_usu"
                type="file"
                accept="image/*"
                @change="handleFile"
                class="mt-1 block w-full"
            >

            <p class="mt-1 text-xs text-slate-500">
                Solo imágenes. Tamaño máximo recomendado: 2 MB.
            </p>

            <InputError class="mt-2" :message="form.errors.fot_usu" />

            <div v-if="previewFoto" class="mt-4">
                <p class="mb-2 text-sm text-gray-600">
                    Vista previa:
                </p>

                <img
                    :src="previewFoto"
                    alt="Foto de usuario"
                    class="h-28 w-28 rounded border object-cover"
                >
            </div>
        </div>
    </FormSection>
</template>
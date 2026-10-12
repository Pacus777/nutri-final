<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    descripcion: {
        type: String,
        default: 'Registra estos datos para crear el perfil clínico base del paciente.',
    },
    bloquearEstado: {
        type: Boolean,
        default: false,
    },
});

const tiposSangre = [
    'A+',
    'A-',
    'B+',
    'B-',
    'AB+',
    'AB-',
    'O+',
    'O-',
];

const claseControlClaro =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';

const claseSelectClaro = 'app-select';

const limitarDecimal = (campo, maxEnteros = 3, maxDecimales = 2) => {
    const valor = String(props.form[campo] ?? '')
        .replace(/,/g, '.')
        .replace(/[^\d.]/g, '');

    const partes = valor.split('.');
    const enteros = (partes[0] ?? '').slice(0, maxEnteros);
    const decimales = (partes[1] ?? '').slice(0, maxDecimales);

    props.form[campo] = decimales.length > 0 ? `${enteros}.${decimales}` : enteros;
};

const limpiarAlergias = () => {
    props.form.ale_pac = String(props.form.ale_pac ?? '')
        .replace(/[\u0000-\u001F\u007F]/g, ' ')
        .replace(/\s{2,}/g, ' ')
        .trimStart()
        .slice(0, 1000);
};
</script>

<template>
    <div class="space-y-6 rounded-xl border border-green-200 bg-green-50 p-5">
        <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-green-800">
            Datos del perfil de paciente
        </h3>

        <div class="rounded-lg border border-green-200 bg-white px-3 py-2 text-xs text-green-700">
            {{ descripcion }}
        </div>

        <div>
            <InputLabel for="sex_pac" value="Sexo" />

            <select
                id="sex_pac"
                v-model="form.sex_pac"
                :class="claseSelectClaro"
            >
                <option value="">Seleccione una opción</option>
                <option value="masculino">masculino</option>
                <option value="femenino">femenino</option>
                <option value="otro">otro</option>
            </select>

            <InputError class="mt-2" :message="form.errors.sex_pac" />
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel for="pes_pac" value="Peso (kg)" />

                <input
                    id="pes_pac"
                    type="text"
                    inputmode="decimal"
                    v-model="form.pes_pac"
                    :class="claseControlClaro"
                    placeholder="Ej. 70.5"
                    @input="limitarDecimal('pes_pac', 3, 2)"
                >

                <InputError class="mt-2" :message="form.errors.pes_pac" />
            </div>

            <div>
                <InputLabel for="estatura_pac" value="Estatura (m)" />

                <input
                    id="estatura_pac"
                    type="text"
                    inputmode="decimal"
                    v-model="form.estatura_pac"
                    :class="claseControlClaro"
                    placeholder="Ej. 1.70"
                    @input="limitarDecimal('estatura_pac', 1, 2)"
                >

                <InputError class="mt-2" :message="form.errors.estatura_pac" />
            </div>
        </div>

        <div>
            <InputLabel for="tip_san_pac" value="Tipo de sangre" />

            <select
                id="tip_san_pac"
                v-model="form.tip_san_pac"
                :class="claseSelectClaro"
            >
                <option value="">Seleccione tipo de sangre</option>
                <option
                    v-for="tipo in tiposSangre"
                    :key="tipo"
                    :value="tipo"
                >
                    {{ tipo }}
                </option>
            </select>

            <InputError class="mt-2" :message="form.errors.tip_san_pac" />
        </div>

        <div>
            <InputLabel for="ale_pac" value="Alergias" />

            <textarea
                id="ale_pac"
                v-model="form.ale_pac"
                :class="claseControlClaro"
                rows="4"
                maxlength="1000"
                placeholder="Describe alergias relevantes"
                @input="limpiarAlergias"
            />

            <div class="mt-1 text-xs text-slate-500">
                {{ form.ale_pac?.length || 0 }}/1000 caracteres
            </div>

            <InputError class="mt-2" :message="form.errors.ale_pac" />
        </div>

        <div>
            <InputLabel for="est_pac" value="Estado del perfil" />

            <select
                id="est_pac"
                v-model="form.est_pac"
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

            <InputError class="mt-2" :message="form.errors.est_pac" />
        </div>
    </div>
</template>
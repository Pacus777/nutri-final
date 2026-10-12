<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    paciente: Object,
    estadosUsuario: Array,
    estadosPaciente: Array,
    sexos: Array,
});

const extensionesCi = ['LP', 'CB', 'SC', 'OR', 'PT', 'TJ', 'CH', 'BE', 'PD', 'BN', 'EX'];
const tiposSangre = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

const claseInputClaro =
    'mt-1 block w-full !rounded-xl !border-slate-300 !bg-white !text-slate-900 placeholder:!text-slate-400 focus:!border-emerald-500 focus:!ring-emerald-500';

const claseControlClaro =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';

const claseSelectClaro = 'app-select';

const hoy = new Date().toISOString().slice(0, 10);
const fechaNacimientoInput = ref(null);
const previewFoto = ref(
    props.paciente.usuario?.fot_usu ? `/storage/${props.paciente.usuario.fot_usu}` : null
);
const mostrarContrasena = ref(false);
const mostrarConfirmacion = ref(false);

const form = useForm({
    nom_usu: props.paciente.usuario?.nom_usu ?? '',
    ape_usu: props.paciente.usuario?.ape_usu ?? '',
    ci_usu: props.paciente.usuario?.ci_usu ?? '',
    ext_ci_usu: props.paciente.usuario?.ext_ci_usu ?? '',
    fec_nac_usu: props.paciente.usuario?.fec_nac_usu ?? '',
    fot_usu: null,
    cor_usu: props.paciente.usuario?.cor_usu ?? '',
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: props.paciente.usuario?.est_usu ?? 'activo',

    sex_pac: props.paciente.sex_pac ?? '',
    pes_pac: String(props.paciente.pes_pac ?? ''),
    estatura_pac: '',
    tip_san_pac: props.paciente.tip_san_pac ?? '',
    ale_pac: props.paciente.ale_pac ?? '',
    est_pac: props.paciente.est_pac ?? 'activo',
});

const estadoPerfilPacienteDesdeUsuario = (estadoUsuario) => (estadoUsuario === 'activo' ? 'activo' : 'inactivo');

const metrosACentimetros = (valorEntrada) => {
    const metros = Number(String(valorEntrada ?? '').replace(',', '.'));

    if (!Number.isFinite(metros) || metros <= 0) {
        return '';
    }

    // Compatibilidad si en algun dato historico ya viene almacenado en cm.
    if (metros > 3 && metros <= 300) {
        return String(Math.round(metros));
    }

    return String(Math.round(metros * 100));
};

form.estatura_pac = metrosACentimetros(props.paciente.estatura_pac);

watch(
    () => form.est_usu,
    (nuevoEstado) => {
        form.est_pac = estadoPerfilPacienteDesdeUsuario(nuevoEstado);
    },
    { immediate: true }
);

const tieneErrores = computed(() => Object.keys(form.errors).length > 0);

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

const limpiarSoloLetras = (campo) => {
    form[campo] = String(form[campo] ?? '')
        .replace(/[^\p{L}\s]/gu, '')
        .replace(/\s{2,}/g, ' ')
        .trimStart();
};

const limpiarSoloNumeros = () => {
    form.ci_usu = String(form.ci_usu ?? '').replace(/\D/g, '').slice(0, 12);
};

const limitarDecimal = (campo, maxEnteros = 3, maxDecimales = 2) => {
    const valor = String(form[campo] ?? '').replace(/,/g, '.').replace(/[^\d.]/g, '');
    const partes = valor.split('.');
    const enteros = (partes[0] ?? '').slice(0, maxEnteros);
    const decimales = (partes[1] ?? '').slice(0, maxDecimales);

    form[campo] = decimales.length > 0 ? `${enteros}.${decimales}` : enteros;
};

const limpiarCentimetros = () => {
    form.estatura_pac = String(form.estatura_pac ?? '').replace(/\D/g, '').slice(0, 3);
};

const centimetrosAMetros = (valorEntrada) => {
    const cm = Number(String(valorEntrada ?? '').replace(/\D/g, ''));

    if (!Number.isFinite(cm) || cm <= 0) {
        return '';
    }

    return (cm / 100).toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
};

const limpiarAlergias = () => {
    form.ale_pac = String(form.ale_pac ?? '')
        .replace(/[\u0000-\u001F\u007F]/g, ' ')
        .replace(/\s{2,}/g, ' ')
        .trimStart()
        .slice(0, 1000);
};

const handleFile = (event) => {
    const file = event.target.files[0];
    form.fot_usu = file ?? null;

    if (file) {
        previewFoto.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            estatura_pac: centimetrosAMetros(data.estatura_pac),
            est_pac: estadoPerfilPacienteDesdeUsuario(data.est_usu),
            _method: 'put',
        }))
        .post(route('pacientes.update', props.paciente.cod_pacientes), {
            forceFormData: true,
        });
};
</script>

<template>
    <Head title="Editar paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Editar Paciente
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Actualiza datos del usuario y perfil clinico con reglas de seguridad reforzadas.
                    </p>
                </div>

                <Link
                    :href="route('pacientes.index')"
                    class="motion-btn rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="auth-panel auth-glow auth-fade-up motion-lift relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_20%,rgba(34,197,94,0.14),transparent_24%),radial-gradient(circle_at_86%_80%,rgba(255,255,255,0.06),transparent_24%)]"></div>

                    <div class="relative z-10 grid gap-4 md:grid-cols-[1.15fr_0.85fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-green-400">
                                Edicion segura
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                Perfil de paciente
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Mantiene coherencia de estado entre usuario y paciente, y protege datos sensibles.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Paciente</p>
                            <div class="mt-3 space-y-2 text-sm text-slate-300">
                                <p>Codigo: <span class="font-semibold text-white">{{ paciente.cod_pacientes }}</span></p>
                                <p>Usuario: <span class="font-semibold text-white">{{ paciente.usuario?.nombre_completo || 'Sin nombre' }}</span></p>
                                <p>Correo: <span class="font-semibold text-white">{{ paciente.usuario?.cor_usu || 'Sin correo' }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900 sm:p-7">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-600">
                                    Datos del usuario
                                </h4>

                                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="nom_usu" value="Nombres" />
                                        <TextInput
                                            id="nom_usu"
                                            v-model="form.nom_usu"
                                            :class="claseInputClaro"
                                            required
                                            autocomplete="off"
                                            @input="limpiarSoloLetras('nom_usu')"
                                        />
                                        <InputError class="mt-2" :message="form.errors.nom_usu" />
                                    </div>

                                    <div>
                                        <InputLabel for="ape_usu" value="Apellidos" />
                                        <TextInput
                                            id="ape_usu"
                                            v-model="form.ape_usu"
                                            :class="claseInputClaro"
                                            required
                                            autocomplete="off"
                                            @input="limpiarSoloLetras('ape_usu')"
                                        />
                                        <InputError class="mt-2" :message="form.errors.ape_usu" />
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-3">
                                    <div>
                                        <InputLabel for="ci_usu" value="CI" />
                                        <TextInput
                                            id="ci_usu"
                                            v-model="form.ci_usu"
                                            :class="claseInputClaro"
                                            required
                                            inputmode="numeric"
                                            maxlength="12"
                                            autocomplete="off"
                                            @input="limpiarSoloNumeros"
                                        />
                                        <InputError class="mt-2" :message="form.errors.ci_usu" />
                                    </div>

                                    <div>
                                        <InputLabel for="ext_ci_usu" value="Extension CI" />
                                        <select
                                            id="ext_ci_usu"
                                            v-model="form.ext_ci_usu"
                                            :class="claseSelectClaro"
                                            required
                                        >
                                            <option value="">Seleccione extension</option>
                                            <option v-for="ext in extensionesCi" :key="ext" :value="ext">{{ ext }}</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.ext_ci_usu" />
                                    </div>

                                    <div>
                                        <InputLabel for="fec_nac_usu" value="Fecha de nacimiento" />
                                        <div class="mt-1 flex items-center gap-2">
                                            <input
                                                id="fec_nac_usu"
                                                ref="fechaNacimientoInput"
                                                type="date"
                                                v-model="form.fec_nac_usu"
                                                :max="hoy"
                                                :class="`${claseControlClaro} pr-3`"
                                                required
                                            >
                                            <button
                                                type="button"
                                                class="motion-btn rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                                                @click="abrirSelectorFecha"
                                            >
                                                Fecha
                                            </button>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.fec_nac_usu" />
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="fot_usu" value="Foto (opcional)" />
                                    <input
                                        id="fot_usu"
                                        type="file"
                                        @change="handleFile"
                                        class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-200"
                                    >
                                    <InputError class="mt-2" :message="form.errors.fot_usu" />

                                    <div v-if="previewFoto" class="mt-4">
                                        <img :src="previewFoto" alt="Foto de usuario" class="h-28 w-28 rounded-lg border border-slate-300 object-cover">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="cor_usu" value="Correo" />
                                    <TextInput id="cor_usu" type="email" v-model="form.cor_usu" :class="claseInputClaro" required />
                                    <InputError class="mt-2" :message="form.errors.cor_usu" />
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="cla_usu" value="Nueva contrasena" />
                                        <div class="relative mt-1">
                                            <TextInput
                                                id="cla_usu"
                                                :type="mostrarContrasena ? 'text' : 'password'"
                                                v-model="form.cla_usu"
                                                :class="`${claseInputClaro} pr-11`"
                                                minlength="8"
                                            />
                                            <button
                                                type="button"
                                                class="absolute inset-y-0 right-2 my-auto rounded px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                                @click="mostrarContrasena = !mostrarContrasena"
                                            >
                                                {{ mostrarContrasena ? 'Ocultar' : 'Ver' }}
                                            </button>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.cla_usu" />
                                    </div>

                                    <div>
                                        <InputLabel for="cla_usu_confirmation" value="Confirmar nueva contrasena" />
                                        <div class="relative mt-1">
                                            <TextInput
                                                id="cla_usu_confirmation"
                                                :type="mostrarConfirmacion ? 'text' : 'password'"
                                                v-model="form.cla_usu_confirmation"
                                                :class="`${claseInputClaro} pr-11`"
                                                minlength="8"
                                            />
                                            <button
                                                type="button"
                                                class="absolute inset-y-0 right-2 my-auto rounded px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                                @click="mostrarConfirmacion = !mostrarConfirmacion"
                                            >
                                                {{ mostrarConfirmacion ? 'Ocultar' : 'Ver' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="est_usu" value="Estado del usuario" />
                                    <select id="est_usu" v-model="form.est_usu" :class="claseSelectClaro" required>
                                        <option v-for="estado in estadosUsuario" :key="estado" :value="estado">
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">El estado del perfil del paciente se mantiene sincronizado con este valor.</p>
                                    <InputError class="mt-2" :message="form.errors.est_usu" />
                                </div>
                            </div>

                            <div class="space-y-6 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 sm:p-5">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                    Perfil del paciente
                                </h4>

                                <div>
                                    <InputLabel for="sex_pac" value="Sexo" />
                                    <select id="sex_pac" v-model="form.sex_pac" :class="claseSelectClaro" required>
                                        <option value="">Seleccione una opcion</option>
                                        <option v-for="sexo in sexos" :key="sexo" :value="sexo">
                                            {{ sexo }}
                                        </option>
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
                                            required
                                            @input="limitarDecimal('pes_pac', 3, 2)"
                                        >
                                        <InputError class="mt-2" :message="form.errors.pes_pac" />
                                    </div>

                                    <div>
                                        <InputLabel for="estatura_pac" value="Estatura (cm)" />
                                        <input
                                            id="estatura_pac"
                                            type="text"
                                            inputmode="numeric"
                                            v-model="form.estatura_pac"
                                            :class="claseControlClaro"
                                            placeholder="Ej. 170"
                                            required
                                            @input="limpiarCentimetros"
                                        >
                                        <p class="mt-1 text-xs text-slate-500">Ingresa la estatura en centimetros. Ejemplo: 170.</p>
                                        <InputError class="mt-2" :message="form.errors.estatura_pac" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="tip_san_pac" value="Tipo de sangre" />
                                        <select id="tip_san_pac" v-model="form.tip_san_pac" :class="claseSelectClaro">
                                            <option value="">Seleccione tipo de sangre</option>
                                            <option v-for="tipo in tiposSangre" :key="tipo" :value="tipo">{{ tipo }}</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.tip_san_pac" />
                                    </div>

                                    <div>
                                        <InputLabel for="est_pac" value="Estado del perfil" />
                                        <select id="est_pac" v-model="form.est_pac" :class="claseSelectClaro" required disabled>
                                            <option v-for="estado in estadosPaciente" :key="estado" :value="estado">
                                                {{ estado }}
                                            </option>
                                        </select>
                                        <p class="mt-1 text-xs text-slate-500">Este estado se sincroniza automaticamente con el estado del usuario.</p>
                                        <InputError class="mt-2" :message="form.errors.est_pac" />
                                    </div>
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
                                    <div class="mt-1 text-xs text-slate-500">{{ form.ale_pac?.length || 0 }}/1000 caracteres</div>
                                    <InputError class="mt-2" :message="form.errors.ale_pac" />
                                </div>
                            </div>

                            <div v-if="tieneErrores" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                Revisa los campos marcados antes de actualizar.
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing" class="motion-btn rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                    {{ form.processing ? 'Actualizando...' : 'Actualizar paciente' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

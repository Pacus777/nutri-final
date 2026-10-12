<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    nutricionista: Object,
    estadosUsuario: Array,
    estadosNutricionista: Array,
});

const previewFoto = ref(
    props.nutricionista.usuario?.fot_usu
        ? `/storage/${props.nutricionista.usuario.fot_usu}`
        : null
);
const mostrarContrasena = ref(false);
const mostrarConfirmacion = ref(false);
const fechaNacimientoInput = ref(null);

const extensionesCi = ['LP', 'CB', 'SC', 'OR', 'PT', 'TJ', 'CH', 'BE', 'PD', 'BN', 'EX'];

const claseInputClaro =
    'mt-1 block w-full !rounded-xl !border-slate-300 !bg-white !text-slate-900 placeholder:!text-slate-400 focus:!border-emerald-500 focus:!ring-emerald-500';

const claseControlClaro =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';

const claseSelectClaro = 'app-select';

const hoy = new Date().toISOString().slice(0, 10);

const form = useForm({
    nom_usu: props.nutricionista.usuario?.nom_usu ?? '',
    ape_usu: props.nutricionista.usuario?.ape_usu ?? '',
    ci_usu: props.nutricionista.usuario?.ci_usu ?? '',
    ext_ci_usu: props.nutricionista.usuario?.ext_ci_usu ?? '',
    fec_nac_usu: props.nutricionista.usuario?.fec_nac_usu ?? '',
    fot_usu: null,
    cor_usu: props.nutricionista.usuario?.cor_usu ?? '',
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: props.nutricionista.usuario?.est_usu ?? 'activo',
    esp_nut: props.nutricionista.esp_nut ?? '',
    est_nut: props.nutricionista.est_nut ?? 'activo',
});

const tieneErrores = computed(() => Object.keys(form.errors).length > 0);

const nombreCompleto = computed(() => {
    const usuario = props.nutricionista.usuario;

    if (!usuario) {
        return 'Nutricionista';
    }

    return usuario.nombre_completo || `${usuario.nom_usu ?? ''} ${usuario.ape_usu ?? ''}`.trim() || 'Nutricionista';
});

const limpiarSoloLetras = (campo) => {
    form[campo] = String(form[campo] ?? '')
        .replace(/[^\p{L}\s]/gu, '')
        .replace(/\s{2,}/g, ' ')
        .trimStart();
};

const limpiarSoloNumeros = () => {
    form.ci_usu = String(form.ci_usu ?? '').replace(/\D/g, '').slice(0, 12);
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
            _method: 'put',
        }))
        .post(route('nutricionistas.update', props.nutricionista.cod_nutricionistas), {
            forceFormData: true,
        });
};
</script>

<template>
    <Head title="Editar nutricionista" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Editar Nutricionista
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Actualiza datos personales, credenciales y perfil profesional.
                    </p>
                </div>

                <Link
                    :href="route('nutricionistas.index')"
                    class="motion-btn rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-800"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="auth-panel auth-glow auth-fade-up motion-lift relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_18%,rgba(34,197,94,0.12),transparent_22%),radial-gradient(circle_at_85%_82%,rgba(255,255,255,0.05),transparent_24%)]"></div>

                    <div class="relative z-10 grid gap-5 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-green-400">
                                Edición profesional
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                {{ nombreCompleto }}
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Actualiza datos personales, acceso y el estado del perfil profesional.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Estado del perfil</p>
                            <p class="mt-3 text-sm font-semibold text-white">{{ form.est_nut }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-300">Correo actual: {{ form.cor_usu }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <div v-if="tieneErrores" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4">
                            <p class="text-sm font-semibold text-rose-800">Revisa la información del formulario.</p>
                            <p class="mt-1 text-xs text-rose-700">Hay campos con errores. Corrige los mensajes marcados en rojo para continuar.</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <section class="rounded-xl border border-slate-200 p-5">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                                    Datos personales
                                </h3>

                                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
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
                                            placeholder="Solo numeros (8 a 12)"
                                            required
                                            @input="limpiarSoloNumeros"
                                        />
                                        <InputError class="mt-2" :message="form.errors.ci_usu" />
                                    </div>

                                    <div>
                                        <InputLabel for="ext_ci_usu" value="Extensión CI" />
                                        <select
                                            id="ext_ci_usu"
                                            v-model="form.ext_ci_usu"
                                            :class="claseSelectClaro"
                                            required
                                        >
                                            <option v-for="extension in extensionesCi" :key="extension" :value="extension">
                                                {{ extension }}
                                            </option>
                                        </select>
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
                                            />
                                            <button
                                                type="button"
                                                class="motion-btn absolute inset-y-0 right-1.5 flex items-center rounded-lg px-2 text-slate-400 hover:text-slate-600"
                                                @click="abrirSelectorFecha"
                                                aria-label="Abrir selector de fecha"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 012 0v1h4V2a1 1 0 112 0v1h1a2 2 0 012 2v2H3V5a2 2 0 012-2h1V2zm11 7H3v6a2 2 0 002 2h10a2 2 0 002-2V9z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">Selecciona una fecha válida anterior o igual a hoy.</p>
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
                                    />
                                    <p class="mt-1 text-xs text-slate-500">Solo imágenes. Tamaño máximo: 2 MB.</p>
                                    <InputError class="mt-2" :message="form.errors.fot_usu" />

                                    <div v-if="previewFoto" class="mt-4">
                                        <p class="mb-2 text-sm text-slate-600">Vista previa:</p>
                                        <img
                                            :src="previewFoto"
                                            alt="Foto de nutricionista"
                                            class="h-28 w-28 rounded-2xl border border-slate-200 object-cover shadow-sm"
                                        />
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-xl border border-slate-200 p-5">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                                    Datos de acceso
                                </h3>

                                <div class="mt-4">
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
                                        <InputLabel for="cla_usu" value="Nueva contraseña" />
                                        <div class="relative">
                                            <input
                                                id="cla_usu"
                                                v-model="form.cla_usu"
                                                :type="mostrarContrasena ? 'text' : 'password'"
                                                :class="`${claseControlClaro} pr-10`"
                                                placeholder="Opcional"
                                            />
                                            <button
                                                type="button"
                                                class="motion-btn absolute inset-y-0 right-2 flex items-center rounded px-2 text-slate-500 hover:text-slate-700"
                                                @click="mostrarContrasena = !mostrarContrasena"
                                            >
                                                <svg v-if="!mostrarContrasena" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 3C5 3 2 8 2 10s3 7 8 7 8-5 8-7-3-7-8-7zm0 11a4 4 0 110-8 4 4 0 010 8z" />
                                                    <path d="M10 7a3 3 0 100 6 3 3 0 000-6z" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3.293 2.293a1 1 0 011.414 0l12 12a1 1 0 01-1.414 1.414l-1.53-1.53A9.77 9.77 0 0110 15c-5 0-8-5-8-5a15.09 15.09 0 013.37-3.93L1.88 3.707a1 1 0 011.414-1.414zM10 5a5 5 0 014.9 4.06L11.95 6.11A3 3 0 008.11 9.95L6.39 8.23A5 5 0 0110 5zm0 8a3 3 0 01-2.95-2.43l3.38 3.38c-.14.03-.28.05-.43.05z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.cla_usu" />
                                    </div>

                                    <div>
                                        <InputLabel for="cla_usu_confirmation" value="Confirmar contraseña" />
                                        <div class="relative">
                                            <input
                                                id="cla_usu_confirmation"
                                                v-model="form.cla_usu_confirmation"
                                                :type="mostrarConfirmacion ? 'text' : 'password'"
                                                :class="`${claseControlClaro} pr-10`"
                                                placeholder="Opcional"
                                            />
                                            <button
                                                type="button"
                                                class="motion-btn absolute inset-y-0 right-2 flex items-center rounded px-2 text-slate-500 hover:text-slate-700"
                                                @click="mostrarConfirmacion = !mostrarConfirmacion"
                                            >
                                                <svg v-if="!mostrarConfirmacion" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 3C5 3 2 8 2 10s3 7 8 7 8-5 8-7-3-7-8-7zm0 11a4 4 0 110-8 4 4 0 010 8z" />
                                                    <path d="M10 7a3 3 0 100 6 3 3 0 000-6z" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3.293 2.293a1 1 0 011.414 0l12 12a1 1 0 01-1.414 1.414l-1.53-1.53A9.77 9.77 0 0110 15c-5 0-8-5-8-5a15.09 15.09 0 013.37-3.93L1.88 3.707a1 1 0 011.414-1.414zM10 5a5 5 0 014.9 4.06L11.95 6.11A3 3 0 008.11 9.95L6.39 8.23A5 5 0 0110 5zm0 8a3 3 0 01-2.95-2.43l3.38 3.38c-.14.03-.28.05-.43.05z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.cla_usu_confirmation" />
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <InputLabel for="est_usu" value="Estado del usuario" />
                                    <select
                                        id="est_usu"
                                        v-model="form.est_usu"
                                        :class="claseSelectClaro"
                                    >
                                        <option v-for="estado in estadosUsuario" :key="estado" :value="estado">
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_usu" />
                                </div>
                            </section>

                            <section class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-800">
                                    Datos del perfil de nutricionista
                                </h3>

                                <div class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs text-blue-700 mt-3">
                                    Completa estos datos para mantener activo el perfil profesional.
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="esp_nut" value="Especialidad" />
                                    <TextInput
                                        id="esp_nut"
                                        type="text"
                                        :class="claseInputClaro"
                                        v-model="form.esp_nut"
                                        maxlength="100"
                                        placeholder="Ej. Nutrición clínica"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.esp_nut" />
                                </div>

                                <div class="mt-6">
                                    <InputLabel for="est_nut" value="Estado del perfil" />
                                    <select
                                        id="est_nut"
                                        v-model="form.est_nut"
                                        :class="claseSelectClaro"
                                    >
                                        <option v-for="estado in estadosNutricionista" :key="estado" :value="estado">
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_nut" />
                                </div>
                            </section>

                            <div class="flex justify-end gap-2">
                                <Link
                                    :href="route('nutricionistas.index')"
                                    class="motion-btn rounded bg-slate-200 px-4 py-2 text-slate-800 hover:bg-slate-300"
                                >
                                    Cancelar
                                </Link>

                                <PrimaryButton class="motion-btn" :disabled="form.processing">
                                    Actualizar nutricionista
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

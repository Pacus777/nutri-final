<script setup>
import { computed, ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Stepper from '@/Components/UI/Stepper.vue';

import UsuarioTipoRegistroForm from '@/Components/Usuarios/Form/UsuarioTipoRegistroForm.vue';
import UsuarioDatosPersonalesForm from '@/Components/Usuarios/Form/UsuarioDatosPersonalesForm.vue';
import UsuarioAccesoForm from '@/Components/Usuarios/Form/UsuarioAccesoForm.vue';
import UsuarioPerfilNutricionistaForm from '@/Components/Usuarios/Form/UsuarioPerfilNutricionistaForm.vue';
import UsuarioPerfilPacienteForm from '@/Components/Usuarios/Form/UsuarioPerfilPacienteForm.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    estados: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['submit']);

const currentStep = ref(0);
const mensajePaso = ref('');

const steps = [
    {
        key: 'tipo',
        title: 'Tipo',
        description: 'Define el tipo de registro.',
    },
    {
        key: 'personales',
        title: 'Datos personales',
        description: 'Información básica del usuario.',
    },
    {
        key: 'acceso',
        title: 'Acceso',
        description: 'Correo, contraseña y estado.',
    },
    {
        key: 'perfil',
        title: 'Perfil',
        description: 'Datos adicionales según el tipo.',
    },
    {
        key: 'confirmacion',
        title: 'Confirmación',
        description: 'Revisión final antes de guardar.',
    },
];

const esNutricionista = computed(() => props.form.tipo_registro === 'Nutricionista');
const esPaciente = computed(() => props.form.tipo_registro === 'Paciente');

const descripcionTipo = computed(() => {
    if (props.form.tipo_registro === 'Administrador') {
        return 'Registro orientado a gestión administrativa y operativa del sistema.';
    }

    if (props.form.tipo_registro === 'Nutricionista') {
        return 'Registro con perfil profesional para seguimiento nutricional.';
    }

    return 'Registro de paciente para historia clínica y control nutricional.';
});

const nombreCompleto = computed(() => {
    return `${props.form.nom_usu || ''} ${props.form.ape_usu || ''}`.trim();
});

const tieneErrores = computed(() => Object.keys(props.form.errors).length > 0);

const estadoPerfilDesdeUsuario = (estadoUsuario) => {
    return estadoUsuario === 'activo' ? 'activo' : 'inactivo';
};

watch(
    () => props.form.est_usu,
    (nuevoEstado) => {
        props.form.est_nut = estadoPerfilDesdeUsuario(nuevoEstado);
        props.form.est_pac = estadoPerfilDesdeUsuario(nuevoEstado);
    },
    { immediate: true }
);

const calcularEdad = (fechaNacimiento) => {
    if (!fechaNacimiento) {
        return null;
    }

    const nacimiento = new Date(fechaNacimiento);
    const hoy = new Date();

    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();

    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad -= 1;
    }

    return edad;
};

const esMayorDeEdad = computed(() => {
    const edad = calcularEdad(props.form.fec_nac_usu);

    return edad !== null && edad >= 18;
});

const validarPasoActual = () => {
    mensajePaso.value = '';

    if (currentStep.value === 0) {
        if (!props.form.tipo_registro) {
            mensajePaso.value = 'Selecciona el tipo de registro para continuar.';
            return false;
        }
    }

    if (currentStep.value === 1) {
        if (!props.form.nom_usu || !props.form.ape_usu || !props.form.ci_usu || !props.form.ext_ci_usu || !props.form.fec_nac_usu) {
            mensajePaso.value = 'Completa nombres, apellidos, CI, extensión y fecha de nacimiento para continuar.';
            return false;
        }

        if (!esMayorDeEdad.value) {
            mensajePaso.value = 'El usuario debe tener 18 años o más para continuar.';
            return false;
        }
    }

    if (currentStep.value === 2) {
        if (!props.form.cor_usu || !props.form.cla_usu || !props.form.cla_usu_confirmation || !props.form.est_usu) {
            mensajePaso.value = 'Completa correo, contraseña, confirmación y estado para continuar.';
            return false;
        }

        if (props.form.cla_usu !== props.form.cla_usu_confirmation) {
            mensajePaso.value = 'La contraseña y su confirmación no coinciden.';
            return false;
        }
    }

    if (currentStep.value === 3) {
        if (esNutricionista.value && !props.form.esp_nut) {
            mensajePaso.value = 'Completa la especialidad del nutricionista para continuar.';
            return false;
        }

        if (esPaciente.value) {
            if (!props.form.sex_pac || !props.form.pes_pac || !props.form.estatura_pac) {
                mensajePaso.value = 'Completa sexo, peso y estatura del paciente para continuar.';
                return false;
            }
        }
    }

    return true;
};

const validarTodoAntesDeGuardar = () => {
    mensajePaso.value = '';

    if (!props.form.tipo_registro) {
        currentStep.value = 0;
        mensajePaso.value = 'Selecciona el tipo de registro.';
        return false;
    }

    if (!props.form.nom_usu || !props.form.ape_usu || !props.form.ci_usu || !props.form.ext_ci_usu || !props.form.fec_nac_usu) {
        currentStep.value = 1;
        mensajePaso.value = 'Completa los datos personales obligatorios.';
        return false;
    }

    if (!esMayorDeEdad.value) {
        currentStep.value = 1;
        mensajePaso.value = 'El usuario debe tener 18 años o más para guardar el registro.';
        return false;
    }

    if (!props.form.cor_usu || !props.form.cla_usu || !props.form.cla_usu_confirmation || !props.form.est_usu) {
        currentStep.value = 2;
        mensajePaso.value = 'Completa los datos de acceso obligatorios.';
        return false;
    }

    if (props.form.cla_usu !== props.form.cla_usu_confirmation) {
        currentStep.value = 2;
        mensajePaso.value = 'La contraseña y su confirmación no coinciden.';
        return false;
    }

    if (esNutricionista.value && !props.form.esp_nut) {
        currentStep.value = 3;
        mensajePaso.value = 'Completa la especialidad del nutricionista.';
        return false;
    }

    if (esPaciente.value) {
        if (!props.form.sex_pac || !props.form.pes_pac || !props.form.estatura_pac) {
            currentStep.value = 3;
            mensajePaso.value = 'Completa sexo, peso y estatura del paciente.';
            return false;
        }
    }

    return true;
};

const siguientePaso = () => {
    if (!validarPasoActual()) {
        return;
    }

    if (currentStep.value < steps.length - 1) {
        currentStep.value += 1;
    }
};

const anteriorPaso = () => {
    mensajePaso.value = '';

    if (currentStep.value > 0) {
        currentStep.value -= 1;
    }
};

const irAPaso = (index) => {
    if (index <= currentStep.value) {
        mensajePaso.value = '';
        currentStep.value = index;
    }
};

const guardar = () => {
    mensajePaso.value = '';

    if (currentStep.value < steps.length - 1) {
        siguientePaso();
        return;
    }

    if (!validarTodoAntesDeGuardar()) {
        return;
    }

    emit('submit');
};

</script>

<template>
    <form
        class="space-y-6"
        novalidate
        @submit.prevent="guardar"
    >
        <Stepper
            :steps="steps"
            :current-step="currentStep"
            allow-step-click
            @step-click="irAPaso"
        />

        <div
            v-if="tieneErrores"
            class="rounded-xl border border-rose-200 bg-rose-50 p-4"
        >
            <p class="text-sm font-semibold text-rose-800">
                Revisa la información del formulario.
            </p>

            <p class="mt-1 text-xs text-rose-700">
                Hay campos con errores. Corrige los mensajes marcados en rojo para continuar.
            </p>
        </div>

        <div
            v-if="mensajePaso"
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
        >
            {{ mensajePaso }}
        </div>

        <div v-show="currentStep === 0">
            <UsuarioTipoRegistroForm
                :form="form"
                :descripcion-tipo="descripcionTipo"
            />
        </div>

        <div v-show="currentStep === 1">
            <UsuarioDatosPersonalesForm
                :form="form"
            />
        </div>

        <div v-show="currentStep === 2">
            <UsuarioAccesoForm
                :form="form"
                :estados="estados"
                modo="crear"
            />
        </div>

        <div v-show="currentStep === 3">
            <UsuarioPerfilNutricionistaForm
                v-if="esNutricionista"
                :form="form"
                descripcion="Completa estos campos para habilitar el perfil profesional del nutricionista."
            />

            <UsuarioPerfilPacienteForm
                v-else-if="esPaciente"
                :form="form"
                descripcion="Registra estos datos para crear el perfil clínico base del paciente."
            />

            <div
                v-else
                class="rounded-xl border border-slate-200 bg-slate-50 p-5"
            >
                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                    Perfil adicional
                </h3>

                <p class="mt-2 text-sm text-slate-600">
                    El tipo de registro <strong>Administrador</strong> no requiere datos de perfil adicional.
                </p>
            </div>
        </div>

        <div
            v-show="currentStep === 4"
            class="rounded-xl border border-slate-200 bg-slate-50 p-5"
        >
            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                Confirmación del registro
            </h3>

            <p class="mt-2 text-sm text-slate-500">
                Revisa los datos principales antes de guardar el usuario.
            </p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                        Usuario
                    </p>

                    <div class="mt-3 space-y-2 text-sm text-slate-700">
                        <p><strong>Tipo:</strong> {{ form.tipo_registro }}</p>
                        <p><strong>Nombre:</strong> {{ nombreCompleto || 'No registrado' }}</p>
                        <p><strong>CI:</strong> {{ form.ci_usu || 'No registrado' }} {{ form.ext_ci_usu }}</p>
                        <p><strong>Fecha de nacimiento:</strong> {{ form.fec_nac_usu || 'No registrada' }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                        Acceso
                    </p>

                    <div class="mt-3 space-y-2 text-sm text-slate-700">
                        <p><strong>Correo:</strong> {{ form.cor_usu || 'No registrado' }}</p>
                        <p><strong>Estado:</strong> {{ form.est_usu }}</p>
                    </div>
                </div>
            </div>

            <div
                v-if="esNutricionista"
                class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4"
            >
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-800">
                    Perfil nutricionista
                </p>

                <div class="mt-3 space-y-2 text-sm text-blue-900">
                    <p><strong>Especialidad:</strong> {{ form.esp_nut || 'No registrada' }}</p>
                    <p><strong>Estado del perfil:</strong> {{ form.est_nut }}</p>
                </div>
            </div>

            <div
                v-if="esPaciente"
                class="mt-4 rounded-xl border border-green-200 bg-green-50 p-4"
            >
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-green-800">
                    Perfil paciente
                </p>

                <div class="mt-3 space-y-2 text-sm text-green-900">
                    <p><strong>Sexo:</strong> {{ form.sex_pac || 'No registrado' }}</p>
                    <p><strong>Peso:</strong> {{ form.pes_pac || 'No registrado' }}</p>
                    <p><strong>Estatura:</strong> {{ form.estatura_pac || 'No registrada' }}</p>
                    <p><strong>Tipo de sangre:</strong> {{ form.tip_san_pac || 'No registrado' }}</p>
                    <p><strong>Alergias:</strong> {{ form.ale_pac || 'No registradas' }}</p>
                    <p><strong>Estado del perfil:</strong> {{ form.est_pac }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap justify-between gap-3">
            <button
                v-if="currentStep > 0"
                type="button"
                class="motion-btn rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                @click="anteriorPaso"
            >
                Anterior
            </button>

            <span v-else></span>

            <button
                v-if="currentStep < steps.length - 1"
                type="button"
                class="motion-btn rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                @click="siguientePaso"
            >
                Siguiente
            </button>

            <PrimaryButton
                v-else
                class="motion-btn"
                :disabled="form.processing"
            >
                Guardar registro
            </PrimaryButton>
        </div>
    </form>
</template>
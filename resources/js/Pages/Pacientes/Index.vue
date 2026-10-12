<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    pacientes: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const mostrandoModal = ref(false);
const previewFoto = ref(null);
const busqueda = ref('');
const mostrarContrasena = ref(false);
const mostrarConfirmacion = ref(false);
const fechaNacimientoInput = ref(null);

// Estado para el ConfirmModal
const showConfirmModal = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmType = ref('warning');
const pendingAction = ref(null);

const extensionesCi = ['LP', 'CB', 'SC', 'OR', 'PT', 'TJ', 'CH', 'BE', 'PD', 'BN', 'EX'];
const tiposSangre = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

const claseInputClaro =
    'mt-1 block w-full !rounded-xl !border-slate-300 !bg-white !text-slate-900 placeholder:!text-slate-400 focus:!border-emerald-500 focus:!ring-emerald-500';

const claseControlClaro =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';

const claseSelectClaro = 'app-select';

const hoy = new Date().toISOString().slice(0, 10);

const form = useForm({
    nom_usu: '',
    ape_usu: '',
    ci_usu: '',
    ext_ci_usu: '',
    fec_nac_usu: '',
    fot_usu: null,
    cor_usu: '',
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: 'activo',

    sex_pac: '',
    pes_pac: '',
    estatura_pac: '',
    tip_san_pac: '',
    ale_pac: '',
    est_pac: 'activo',
});

const tieneErrores = computed(() => Object.keys(form.errors).length > 0);

const resumen = computed(() => ({
    total: props.pacientes.length,
    activos: props.pacientes.filter((paciente) => paciente.est_pac === 'activo').length,
    inactivos: props.pacientes.filter((paciente) => paciente.est_pac === 'inactivo').length,
    bloqueados: props.pacientes.filter((paciente) => paciente.usuario?.est_usu === 'bloqueado').length,
}));

const pacientesFiltrados = computed(() => {
    const termino = busqueda.value.trim().toLowerCase();

    if (!termino) {
        return props.pacientes;
    }

    return props.pacientes.filter((paciente) => {
        const nombre = String(nombreCompletoPaciente(paciente)).toLowerCase();
        const correo = String(paciente.usuario?.cor_usu ?? '').toLowerCase();
        const codigo = String(paciente.cod_pacientes ?? '');
        const sexo = String(paciente.sex_pac ?? '').toLowerCase();
        const sangre = String(paciente.tip_san_pac ?? '').toLowerCase();
        const estadoPerfil = String(paciente.est_pac ?? '').toLowerCase();
        const estadoUsuario = String(paciente.usuario?.est_usu ?? '').toLowerCase();

        return [nombre, correo, codigo, sexo, sangre, estadoPerfil, estadoUsuario].some((valor) => valor.includes(termino));
    });
});

const claseEstadoPerfil = (estado) => {
    if (estado === 'activo') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    return 'bg-amber-100 text-amber-700 ring-amber-200';
};

const claseEstadoUsuario = (estado) => {
    if (estado === 'activo') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (estado === 'inactivo') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    return 'bg-rose-100 text-rose-700 ring-rose-200';
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

const abrirModal = () => {
    mostrandoModal.value = true;
};

const cerrarModalConEscape = (event) => {
    if (event.key === 'Escape' && mostrandoModal.value) {
        cerrarModal();
    }
};

const cerrarModal = () => {
    mostrandoModal.value = false;
    previewFoto.value = null;
    mostrarContrasena.value = false;
    mostrarConfirmacion.value = false;
    form.reset();
    form.clearErrors();
    form.est_usu = 'activo';
    form.est_pac = 'activo';
};

const handleFile = (event) => {
    const file = event.target.files[0];
    form.fot_usu = file ?? null;

    if (file) {
        previewFoto.value = URL.createObjectURL(file);
    } else {
        previewFoto.value = null;
    }
};

const submitRegistroRapido = () => {
    form.post(route('pacientes.guardar-registro-rapido'), {
        forceFormData: true,
        onSuccess: () => cerrarModal(),
    });
};

const nombreCompletoPaciente = (paciente) => {
    if (paciente.usuario?.nombre_completo) {
        return paciente.usuario.nombre_completo;
    }

    const nombre = `${paciente.usuario?.nom_usu ?? ''} ${paciente.usuario?.ape_usu ?? ''}`.trim();

    return nombre || 'Sin usuario';
};

const cambiarEstado = (codPaciente, estadoActual) => {
    // Si no está activo (es decir, está inactivo), activarlo no requiere confirmación crítica
    if (estadoActual !== 'activo') {
        router.patch(route('pacientes.cambiar-estado', codPaciente));
        return;
    }

    // Configurar modal de confirmación al inactivar
    confirmTitle.value = 'Inactivar Paciente';
    confirmMessage.value = '¿Estás seguro de que deseas inactivar a este paciente? Se desactivará su acceso al sistema y su estado operacional. No podrá registrar comidas ni ver sus planes nutricionales hasta ser reactivado.';
    confirmType.value = 'warning';

    pendingAction.value = () => {
        router.patch(route('pacientes.cambiar-estado', codPaciente));
    };
    showConfirmModal.value = true;
};

const confirmarCambio = () => {
    if (pendingAction.value) {
        pendingAction.value();
    }
    showConfirmModal.value = false;
    pendingAction.value = null;
};

const cerrarConfirmModal = () => {
    showConfirmModal.value = false;
    pendingAction.value = null;
};
</script>

<template>
    <Head title="Pacientes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Gestion de Pacientes
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Centraliza fichas clinicas, estado del perfil y acceso del usuario asociado.
                    </p>
                </div>

                <button
                    type="button"
                    @click="abrirModal"
                    class="app-primary-cta"
                >
                    Nuevo paciente
                </button>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">


                <div class="auth-panel auth-glow auth-fade-up motion-lift relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_18%,rgba(34,197,94,0.12),transparent_22%),radial-gradient(circle_at_85%_82%,rgba(255,255,255,0.05),transparent_24%)]"></div>

                    <div class="relative z-10 grid gap-5 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-green-400">
                                Panel clinico
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                Pacientes
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Gestiona historias de pacientes, datos biometricos y su estado operacional.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Resumen rapido</p>
                            <div class="mt-3 space-y-2 text-sm text-slate-300">
                                <p>Total: <span class="font-semibold text-white">{{ resumen.total }}</span></p>
                                <p>Perfiles activos: <span class="font-semibold text-emerald-300">{{ resumen.activos }}</span></p>
                                <p>Usuarios bloqueados: <span class="font-semibold text-rose-300">{{ resumen.bloqueados }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-6 grid gap-4 md:grid-cols-4">
                    <div class="motion-lift rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ resumen.total }}</p>
                    </div>
                    <div class="motion-lift rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">Perfiles activos</p>
                        <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ resumen.activos }}</p>
                    </div>
                    <div class="motion-lift rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-amber-500">Perfiles inactivos</p>
                        <p class="mt-2 text-2xl font-semibold text-amber-700">{{ resumen.inactivos }}</p>
                    </div>
                    <div class="motion-lift rounded-xl border border-rose-200 bg-rose-50/50 p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-rose-500">Usuarios bloqueados</p>
                        <p class="mt-2 text-2xl font-semibold text-rose-700">{{ resumen.bloqueados }}</p>
                    </div>
                </div>

                <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                        Buscar paciente
                    </label>
                    <input
                        v-model="busqueda"
                        type="text"
                        placeholder="Busca por nombre, correo, codigo, sexo, tipo de sangre o estado"
                        class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <div v-if="pacientesFiltrados.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No se encontraron pacientes con los filtros actuales.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Codigo</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Paciente</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Contacto</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Indicadores</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Estado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="paciente in pacientesFiltrados"
                                        :key="paciente.cod_pacientes"
                                        class="transition-colors duration-150 hover:bg-slate-50"
                                    >
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ paciente.cod_pacientes }}</td>

                                        <td class="px-4 py-3">
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ nombreCompletoPaciente(paciente) }}
                                            </p>
                                            <p class="text-xs text-slate-500 capitalize">
                                                Sexo: {{ paciente.sex_pac || 'No especificado' }}
                                            </p>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <p>{{ paciente.usuario?.cor_usu ?? 'Sin correo' }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ paciente.usuario?.ci_usu ? `${paciente.usuario.ci_usu} ${paciente.usuario.ext_ci_usu}` : 'Sin CI' }}
                                            </p>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <p>Peso: {{ paciente.pes_pac }}</p>
                                            <p>Estatura: {{ paciente.estatura_pac }}</p>
                                            <p class="text-xs text-slate-500">
                                                Sangre: {{ paciente.tip_san_pac || 'No especificado' }}
                                            </p>
                                        </td>

                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1"
                                                :class="claseEstadoPerfil(paciente.est_pac)"
                                            >
                                                Perfil: {{ paciente.est_pac }}
                                            </span>
                                            <p class="mt-2">
                                                <span
                                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1"
                                                    :class="claseEstadoUsuario(paciente.usuario?.est_usu)"
                                                >
                                                    Usuario: {{ paciente.usuario?.est_usu ?? 'N/D' }}
                                                </span>
                                            </p>
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <Link
                                                    :href="route('pacientes.show', paciente.cod_pacientes)"
                                                    class="motion-btn rounded bg-sky-100 px-2.5 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-200"
                                                >
                                                    Ver ficha
                                                </Link>

                                                <Link
                                                    :href="route('pacientes.edit', paciente.cod_pacientes)"
                                                    class="motion-btn rounded bg-indigo-100 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-200"
                                                >
                                                    Editar
                                                </Link>

                                                <button
                                                    type="button"
                                                    class="motion-btn rounded px-2.5 py-1.5 text-xs font-medium text-white"
                                                    :class="paciente.est_pac === 'activo'
                                                        ? 'bg-amber-500 hover:bg-amber-600'
                                                        : 'bg-emerald-600 hover:bg-emerald-700'"
                                                    @click="cambiarEstado(paciente.cod_pacientes, paciente.est_pac)"
                                                >
                                                    {{ paciente.est_pac === 'activo' ? 'Inactivar' : 'Activar' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="page.props.errors?.general"
                            class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700"
                        >
                            {{ page.props.errors.general }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="mostrandoModal" @close="cerrarModal">
            <div class="p-6" @keydown.window="cerrarModalConEscape">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">
                            Registro rapido de paciente
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Crea el usuario y su perfil de paciente en un solo flujo.
                        </p>
                    </div>

                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">
                        Rapido
                    </span>
                </div>

                <form @submit.prevent="submitRegistroRapido" class="mt-6 space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <InputLabel for="nom_usu" value="Nombres" />
                            <TextInput
                                id="nom_usu"
                                type="text"
                                :class="claseInputClaro"
                                v-model="form.nom_usu"
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
                                type="text"
                                :class="claseInputClaro"
                                v-model="form.ape_usu"
                                required
                                autocomplete="off"
                                @input="limpiarSoloLetras('ape_usu')"
                            />
                            <InputError class="mt-2" :message="form.errors.ape_usu" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <InputLabel for="ci_usu" value="CI" />
                            <TextInput
                                id="ci_usu"
                                type="text"
                                :class="claseInputClaro"
                                v-model="form.ci_usu"
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

                    <div>
                        <InputLabel for="fot_usu" value="Foto (opcional)" />
                        <input
                            id="fot_usu"
                            type="file"
                            accept="image/*"
                            @change="handleFile"
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-200"
                        >
                        <p class="mt-1 text-xs text-slate-500">Solo imágenes. Tamaño máximo: 2 MB.</p>
                        <InputError class="mt-2" :message="form.errors.fot_usu" />

                        <div v-if="previewFoto" class="mt-4">
                            <p class="mb-2 text-sm text-gray-600">Vista previa:</p>
                            <img
                                :src="previewFoto"
                                alt="Foto de usuario"
                                class="h-28 w-28 rounded-lg border border-slate-300 object-cover"
                            >
                        </div>
                    </div>

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

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <InputLabel for="cla_usu" value="Contrasena" />
                            <div class="relative mt-1">
                                <TextInput
                                    id="cla_usu"
                                    :type="mostrarContrasena ? 'text' : 'password'"
                                    :class="`${claseInputClaro} pr-11`"
                                    v-model="form.cla_usu"
                                    required
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
                            <InputLabel for="cla_usu_confirmation" value="Confirmar contrasena" />
                            <div class="relative mt-1">
                                <TextInput
                                    id="cla_usu_confirmation"
                                    :type="mostrarConfirmacion ? 'text' : 'password'"
                                    :class="`${claseInputClaro} pr-11`"
                                    v-model="form.cla_usu_confirmation"
                                    required
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

                    <div>
                        <InputLabel for="est_usu" value="Estado del usuario" />
                        <select
                            id="est_usu"
                            v-model="form.est_usu"
                            :class="claseSelectClaro"
                        >
                            <option value="activo">activo</option>
                            <option value="inactivo">inactivo</option>
                            <option value="bloqueado">bloqueado</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.est_usu" />
                    </div>

                    <div class="space-y-6 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 sm:p-5">
                        <h3 class="text-base font-semibold text-emerald-800">
                            Datos del perfil de paciente
                        </h3>

                        <div>
                            <InputLabel for="sex_pac" value="Sexo" />
                            <select
                                id="sex_pac"
                                v-model="form.sex_pac"
                                :class="claseSelectClaro"
                                required
                            >
                                <option value="">Seleccione una opcion</option>
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
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    v-model="form.pes_pac"
                                    :class="claseControlClaro"
                                    required
                                >
                                <InputError class="mt-2" :message="form.errors.pes_pac" />
                            </div>

                            <div>
                                <InputLabel for="estatura_pac" value="Estatura (m)" />
                                <input
                                    id="estatura_pac"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    v-model="form.estatura_pac"
                                    :class="claseControlClaro"
                                    required
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
                                <option v-for="tipo in tiposSangre" :key="tipo" :value="tipo">{{ tipo }}</option>
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
                                placeholder="Describe alergias relevantes"
                            />
                            <InputError class="mt-2" :message="form.errors.ale_pac" />
                        </div>

                        <div>
                            <InputLabel for="est_pac" value="Estado del perfil" />
                            <select
                                id="est_pac"
                                v-model="form.est_pac"
                                :class="claseSelectClaro"
                            >
                                <option value="activo">activo</option>
                                <option value="inactivo">inactivo</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.est_pac" />
                        </div>
                    </div>

                    <div v-if="tieneErrores" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        Revisa los campos marcados antes de guardar.
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                        <button
                            type="button"
                            @click="cerrarModal"
                            class="motion-btn rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200"
                        >
                            Cancelar
                        </button>

                        <PrimaryButton :disabled="form.processing" class="motion-btn rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            {{ form.processing ? 'Guardando...' : 'Guardar paciente' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal de confirmación interactivo -->
        <ConfirmModal
            :show="showConfirmModal"
            :title="confirmTitle"
            :message="confirmMessage"
            :type="confirmType"
            @confirm="confirmarCambio"
            @close="cerrarConfirmModal"
        />
    </AuthenticatedLayout>
</template>

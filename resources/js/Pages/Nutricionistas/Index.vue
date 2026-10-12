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
    nutricionistas: {
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
    esp_nut: '',
    est_nut: 'activo',
});

const tieneErrores = computed(() => Object.keys(form.errors).length > 0);

const resumen = computed(() => ({
    total: props.nutricionistas.length,
    activos: props.nutricionistas.filter((nutricionista) => nutricionista.est_nut === 'activo').length,
    inactivos: props.nutricionistas.filter((nutricionista) => nutricionista.est_nut === 'inactivo').length,
}));

const nutricionistasFiltrados = computed(() => {
    const termino = busqueda.value.trim().toLowerCase();

    if (!termino) {
        return props.nutricionistas;
    }

    return props.nutricionistas.filter((nutricionista) => {
        const nombre = String(nutricionista.usuario?.nombre_completo || `${nutricionista.usuario?.nom_usu ?? ''} ${nutricionista.usuario?.ape_usu ?? ''}`)
            .trim()
            .toLowerCase();
        const correo = String(nutricionista.usuario?.cor_usu ?? '').toLowerCase();
        const codigo = String(nutricionista.cod_nutricionistas ?? '');
        const especialidad = String(nutricionista.esp_nut ?? '').toLowerCase();
        const estadoPerfil = String(nutricionista.est_nut ?? '').toLowerCase();
        const estadoUsuario = String(nutricionista.usuario?.est_usu ?? '').toLowerCase();

        return [nombre, correo, codigo, especialidad, estadoPerfil, estadoUsuario].some((valor) => valor.includes(termino));
    });
});

const claseEstado = (estado) => {
    if (estado === 'activo') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    return 'bg-amber-100 text-amber-700 ring-amber-200';
};

const cerrarModalConEscape = (event) => {
    if (event.key === 'Escape' && mostrandoModal.value) {
        cerrarModal();
    }
};

const abrirModal = () => {
    mostrandoModal.value = true;
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

const cerrarModal = () => {
    mostrandoModal.value = false;
    previewFoto.value = null;
    mostrarContrasena.value = false;
    mostrarConfirmacion.value = false;
    form.reset();
    form.clearErrors();
    form.est_usu = 'activo';
    form.est_nut = 'activo';
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
    form.post(route('nutricionistas.guardar-registro-rapido'), {
        forceFormData: true,
        onSuccess: () => cerrarModal(),
    });
};

const nombreCompletoNutricionista = (nutricionista) => {
    if (nutricionista.usuario?.nombre_completo) {
        return nutricionista.usuario.nombre_completo;
    }

    const nombre = `${nutricionista.usuario?.nom_usu ?? ''} ${nutricionista.usuario?.ape_usu ?? ''}`.trim();

    return nombre || 'Sin usuario';
};

const cambiarEstado = (codNutricionista, estadoActual) => {
    // Si está inactivo, activarlo no requiere confirmación crítica
    if (estadoActual !== 'activo') {
        router.patch(route('nutricionistas.cambiar-estado', codNutricionista));
        return;
    }

    // Configurar modal de confirmación al inactivar
    confirmTitle.value = 'Inactivar Nutricionista';
    confirmMessage.value = '¿Estás seguro de que deseas inactivar a este nutricionista? Se desactivará su acceso al sistema y su perfil profesional. Los pacientes asignados no se desvincularán, pero el profesional no podrá gestionarlos hasta ser reactivado.';
    confirmType.value = 'warning';

    pendingAction.value = () => {
        router.patch(route('nutricionistas.cambiar-estado', codNutricionista));
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
    <Head title="Nutricionistas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Gestión de Nutricionistas
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Administra perfiles profesionales, estados y accesos vinculados.
                    </p>
                </div>

                <button
                    type="button"
                    @click="abrirModal"
                    class="app-primary-cta"
                >
                    Nuevo nutricionista
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
                                Panel profesional
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                Nutricionistas
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Consulta, filtra y administra perfiles profesionales vinculados a usuarios del sistema.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Resumen rápido</p>
                            <div class="mt-3 space-y-2 text-sm text-slate-300">
                                <p>Total: <span class="font-semibold text-white">{{ resumen.total }}</span></p>
                                <p>Activos: <span class="font-semibold text-emerald-300">{{ resumen.activos }}</span></p>
                                <p>Inactivos: <span class="font-semibold text-amber-300">{{ resumen.inactivos }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-6 grid gap-4 md:grid-cols-3">
                    <div class="motion-lift rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ resumen.total }}</p>
                    </div>
                    <div class="motion-lift rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-emerald-500">Activos</p>
                        <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ resumen.activos }}</p>
                    </div>
                    <div class="motion-lift rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-amber-500">Inactivos</p>
                        <p class="mt-2 text-2xl font-semibold text-amber-700">{{ resumen.inactivos }}</p>
                    </div>
                </div>

                <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                        Buscar nutricionista
                    </label>
                    <input
                        v-model="busqueda"
                        type="text"
                        placeholder="Busca por nombre, correo, código, especialidad o estado"
                        class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <div v-if="nutricionistasFiltrados.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No se encontraron nutricionistas con los filtros actuales.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Código</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Nutricionista</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Contacto</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Especialidad</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Estado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Roles</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="nutricionista in nutricionistasFiltrados"
                                        :key="nutricionista.cod_nutricionistas"
                                        class="transition-colors duration-150 hover:bg-slate-50"
                                    >
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ nutricionista.cod_nutricionistas }}</td>

                                        <td class="px-4 py-3">
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ nombreCompletoNutricionista(nutricionista) }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ nutricionista.est_nut === 'activo' ? 'Perfil profesional activo' : 'Perfil profesional inactivo' }}
                                            </p>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <p>{{ nutricionista.usuario?.cor_usu ?? 'Sin correo' }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ nutricionista.usuario?.ci_usu ? `${nutricionista.usuario.ci_usu} ${nutricionista.usuario.ext_ci_usu}` : 'Sin CI' }}
                                            </p>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ nutricionista.esp_nut || 'No especificada' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1"
                                                :class="claseEstado(nutricionista.est_nut)"
                                            >
                                                {{ nutricionista.est_nut }}
                                            </span>
                                            <p class="mt-2 text-xs text-slate-500 capitalize">
                                                Usuario: {{ nutricionista.usuario?.est_usu ?? 'N/D' }}
                                            </p>
                                        </td>

                                        <td class="px-4 py-3">
                                            <span
                                                v-if="nutricionista.usuario?.roles?.length"
                                                class="flex flex-wrap gap-2"
                                            >
                                                <span
                                                    v-for="rol in nutricionista.usuario.roles"
                                                    :key="rol.cod_roles"
                                                    class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                                >
                                                    {{ rol.nom_rol }}
                                                </span>
                                            </span>
                                            <span v-else class="text-xs text-rose-600">
                                                Sin roles
                                            </span>
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <Link
                                                    :href="route('nutricionistas.show', nutricionista.cod_nutricionistas)"
                                                    class="motion-btn rounded bg-sky-100 px-2.5 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-200"
                                                >
                                                    Ver ficha
                                                </Link>

                                                <Link
                                                    :href="route('nutricionistas.edit', nutricionista.cod_nutricionistas)"
                                                    class="motion-btn rounded bg-indigo-100 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-200"
                                                >
                                                    Editar
                                                </Link>

                                                <button
                                                    type="button"
                                                    class="motion-btn rounded px-2.5 py-1.5 text-xs font-medium text-white"
                                                    :class="nutricionista.est_nut === 'activo'
                                                        ? 'bg-amber-500 hover:bg-amber-600'
                                                        : 'bg-emerald-600 hover:bg-emerald-700'"
                                                    @click="cambiarEstado(nutricionista.cod_nutricionistas, nutricionista.est_nut)"
                                                >
                                                    {{ nutricionista.est_nut === 'activo' ? 'Inactivar' : 'Activar' }}
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
                            Registro rápido de nutricionista
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Crea usuario y perfil profesional en un solo paso.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="motion-btn rounded-full bg-slate-100 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-200"
                        @click="cerrarModal"
                    >
                        Cerrar
                    </button>
                </div>

                <h2 v-if="false" class="text-lg font-semibold text-gray-800">
                    Registro rápido de nutricionista
                </h2>

                <div
                    v-if="tieneErrores"
                    class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4"
                >
                    <p class="text-sm font-semibold text-rose-800">Revisa la información del formulario.</p>
                    <p class="mt-1 text-xs text-rose-700">Hay campos con errores. Corrige los mensajes marcados en rojo para continuar.</p>
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
                                type="tel"
                                :class="claseInputClaro"
                                v-model="form.ci_usu"
                                required
                                minlength="8"
                                maxlength="12"
                                inputmode="numeric"
                                placeholder="Solo numeros (8 a 12)"
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
                                <option value="">Seleccione extensión</option>
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

                    <div>
                        <InputLabel for="fot_usu" value="Foto (opcional)" />
                        <input
                            id="fot_usu"
                            type="file"
                            accept="image/*"
                            @change="handleFile"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.fot_usu" />
                        <p class="mt-1 text-xs text-slate-500">Solo imágenes. Tamaño máximo: 2 MB.</p>

                        <div v-if="previewFoto" class="mt-4">
                            <p class="mb-2 text-sm text-gray-600">Vista previa:</p>
                            <img
                                :src="previewFoto"
                                alt="Foto de usuario"
                                class="h-28 w-28 rounded object-cover border"
                            />
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
                            <InputLabel for="cla_usu" value="Contraseña" />
                                <div class="relative">
                                    <input
                                        id="cla_usu"
                                        v-model="form.cla_usu"
                                        :type="mostrarContrasena ? 'text' : 'password'"
                                        :class="`${claseControlClaro} pr-10`"
                                        required
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
                                        required
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

                    <div class="space-y-6 rounded border border-blue-200 bg-blue-50 p-4">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-800">
                            Datos del perfil de nutricionista
                        </h3>

                            <div class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs text-blue-700">
                                Completa estos datos para crear el perfil profesional del nutricionista.
                            </div>

                        <div>
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

                        <div>
                            <InputLabel for="est_nut" value="Estado del perfil" />
                                <select
                                    id="est_nut"
                                    v-model="form.est_nut"
                                    :class="claseSelectClaro"
                                >
                                <option value="activo">activo</option>
                                <option value="inactivo">inactivo</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.est_nut" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="cerrarModal"
                                class="motion-btn rounded bg-slate-200 px-4 py-2 text-slate-800 hover:bg-slate-300"
                        >
                            Cancelar
                        </button>

                        <PrimaryButton :disabled="form.processing">
                            Guardar nutricionista
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
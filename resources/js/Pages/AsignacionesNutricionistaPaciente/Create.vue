<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    nutricionistas: {
        type: Array,
        default: () => [],
    },
    pacientes: {
        type: Array,
        default: () => [],
    },
    pacientesSinAsignacion: {
        type: Array,
        default: () => [],
    },
    estados: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const currentStep = ref(1);
const busquedaPaciente = ref('');
const busquedaNutricionista = ref('');
const fechaAsignacionInput = ref(null);
const mostrarConfirmacionReasignar = ref(false);

const hoy = new Date().toISOString().slice(0, 10);

const form = useForm({
    cod_nutricionistas_anp: '',
    cod_pacientes_anp: '',
    fec_anp: hoy,
    est_anp: 'activa',
});

const pacientesFiltrados = computed(() => {
    const termino = busquedaPaciente.value.trim().toLowerCase();

    if (!termino) {
        return props.pacientes;
    }

    return props.pacientes.filter((paciente) => {
        const nombre = String(paciente.usuario?.nom_usu ?? '').toLowerCase();
        const correo = String(paciente.usuario?.cor_usu ?? '').toLowerCase();
        const codigo = String(paciente.cod_pacientes ?? '');

        return [nombre, correo, codigo].some((valor) => valor.includes(termino));
    });
});

const nutricionistasFiltrados = computed(() => {
    const termino = busquedaNutricionista.value.trim().toLowerCase();

    if (!termino) {
        return props.nutricionistas;
    }

    return props.nutricionistas.filter((nutricionista) => {
        const nombre = String(nutricionista.usuario?.nom_usu ?? '').toLowerCase();
        const correo = String(nutricionista.usuario?.cor_usu ?? '').toLowerCase();
        const especialidad = String(nutricionista.esp_nut ?? '').toLowerCase();
        const codigo = String(nutricionista.cod_nutricionistas ?? '');

        return [nombre, correo, especialidad, codigo].some((valor) => valor.includes(termino));
    });
});

const selectedPacienteData = computed(() => {
    return props.pacientes.find(p => p.cod_pacientes === form.cod_pacientes_anp) || null;
});

const selectedNutricionistaData = computed(() => {
    return props.nutricionistas.find(n => n.cod_nutricionistas === form.cod_nutricionistas_anp) || null;
});

const selectPaciente = (pacienteId) => {
    form.cod_pacientes_anp = pacienteId;
};

const selectNutricionista = (nutricionistaId) => {
    form.cod_nutricionistas_anp = nutricionistaId;
};

const nextStep = () => {
    if (currentStep.value === 1 && !form.cod_pacientes_anp) {
        if (window.showToast) window.showToast('Por favor, selecciona un paciente antes de continuar.', 'warning');
        return;
    }
    if (currentStep.value === 2 && !form.cod_nutricionistas_anp) {
        if (window.showToast) window.showToast('Por favor, selecciona un nutricionista antes de continuar.', 'warning');
        return;
    }
    if (currentStep.value < 3) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const abrirSelectorFecha = () => {
    const input = fechaAsignacionInput.value;
    if (!input) return;
    input.focus();
    if (typeof input.showPicker === 'function') {
        input.showPicker();
    }
};

const submit = () => {
    if (!form.cod_pacientes_anp || !form.cod_nutricionistas_anp) {
        if (window.showToast) window.showToast('Asignación incompleta. Por favor revisa los pasos anteriores.', 'error');
        return;
    }

    const nutricionistaActivo = selectedPacienteData.value?.nutricionista_activo;
    if (nutricionistaActivo) {
        mostrarConfirmacionReasignar.value = true;
        return;
    }

    ejecutarEnvio();
};

const ejecutarEnvio = () => {
    mostrarConfirmacionReasignar.value = false;
    form.post(route('asignaciones.store'), {
        onError: (errors) => {
            if (errors.cod_pacientes_anp) {
                currentStep.value = 1;
                if (window.showToast) window.showToast(errors.cod_pacientes_anp, 'error');
            } else if (errors.cod_nutricionistas_anp) {
                currentStep.value = 2;
                if (window.showToast) window.showToast(errors.cod_nutricionistas_anp, 'error');
            } else if (errors.fec_anp) {
                if (window.showToast) window.showToast(errors.fec_anp, 'error');
            } else if (errors.est_anp) {
                if (window.showToast) window.showToast(errors.est_anp, 'error');
            } else if (window.showToast) {
                window.showToast('Revisa los errores de validación.', 'error');
            }
        }
    });
};
</script>

<template>
    <Head title="Nueva Asignación" />

    <AuthenticatedLayout>
        <!-- Premium Header with clean medical design -->
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                            Asistente
                        </span>
                        <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-600/10">
                            Paso a Paso
                        </span>
                    </div>
                    <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 leading-none">
                        Nueva Asignación
                    </h2>
                    <p class="mt-2 text-sm text-slate-500 font-medium">
                        Vincula un paciente con un nutricionista activo de forma controlada y segura.
                    </p>
                </div>

                <Link
                    :href="route('asignaciones.index')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 transition duration-150 cursor-pointer h-[44px]"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al Panel
                </Link>
            </div>
        </template>

        <div class="py-8 bg-slate-50/30 min-h-screen">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Interactive Steps Progress Bar -->
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <nav aria-label="Progress">
                        <ol role="list" class="flex flex-col md:flex-row md:items-center justify-between gap-6 md:divide-x md:divide-slate-200">
                            <!-- Step 1 -->
                            <li class="flex-1 first:pl-0 pl-0 md:pl-4 cursor-pointer" @click="form.cod_pacientes_anp ? currentStep = 1 : null">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold text-sm transition-all duration-300"
                                        :class="currentStep === 1 ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : (form.cod_pacientes_anp ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500')">
                                        <svg v-if="form.cod_pacientes_anp && currentStep > 1" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span v-else>1</span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Paso 1</p>
                                        <h4 class="text-xs font-black text-slate-900 mt-0.5" :class="currentStep === 1 ? 'text-emerald-700' : ''">Paciente</h4>
                                        <p v-if="form.cod_pacientes_anp" class="text-[9px] font-extrabold text-emerald-600 mt-0.5 truncate max-w-[120px]">
                                            {{ selectedPacienteData?.usuario?.nom_usu ?? 'Seleccionado' }}
                                        </p>
                                        <p v-else class="text-[9px] font-semibold text-slate-400 mt-0.5">Elegir paciente</p>
                                    </div>
                                </div>
                            </li>

                            <!-- Step 2 -->
                            <li class="flex-1 pl-0 md:pl-6 cursor-pointer" @click="form.cod_nutricionistas_anp ? currentStep = 2 : null">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold text-sm transition-all duration-300"
                                        :class="currentStep === 2 ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : (form.cod_nutricionistas_anp && currentStep > 2 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500')">
                                        <svg v-if="form.cod_nutricionistas_anp && currentStep > 2" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span v-else>2</span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Paso 2</p>
                                        <h4 class="text-xs font-black text-slate-900 mt-0.5" :class="currentStep === 2 ? 'text-emerald-700' : ''">Nutricionista</h4>
                                        <p v-if="form.cod_nutricionistas_anp" class="text-[9px] font-extrabold text-emerald-600 mt-0.5 truncate max-w-[120px]">
                                            Dr. {{ selectedNutricionistaData?.usuario?.nom_usu ?? 'Seleccionado' }}
                                        </p>
                                        <p v-else class="text-[9px] font-semibold text-slate-400 mt-0.5">Elegir profesional</p>
                                    </div>
                                </div>
                            </li>

                            <!-- Step 3 -->
                            <li class="flex-1 pl-0 md:pl-6">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold text-sm transition-all duration-300"
                                        :class="currentStep === 3 ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-slate-100 text-slate-500'">
                                        <span>3</span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Paso 3</p>
                                        <h4 class="text-xs font-black text-slate-900 mt-0.5" :class="currentStep === 3 ? 'text-emerald-700' : ''">Confirmación</h4>
                                        <p class="text-[9px] font-semibold text-slate-400 mt-0.5">
                                            {{ currentStep === 3 ? 'Revisión y envío' : 'Pendiente' }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                <!-- Form Card -->
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden transition-all duration-300">
                    <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">
                        
                        <!-- ---------------- STEP 1: PACIENTE SELECTION ---------------- -->
                        <div v-show="currentStep === 1" class="space-y-6">
                            <div>
                                <h3 class="text-lg font-black text-slate-950">Seleccionar Paciente</h3>
                                <p class="text-xs text-slate-400 font-semibold mt-1">Busca y elige un paciente activo de la cartera institucional.</p>
                            </div>

                            <div class="bg-slate-50 border border-slate-200/60 p-5 rounded-2xl">
                                <label for="buscar_paciente" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Buscar Paciente</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="buscar_paciente"
                                        v-model="busquedaPaciente"
                                        type="text"
                                        placeholder="Busca por nombre, correo o código..."
                                        class="pl-9 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 placeholder-slate-400"
                                    >
                                </div>
                            </div>

                            <!-- Patients without active assignment (Direct Shortcuts) -->
                            <div v-if="props.pacientesSinAsignacion && props.pacientesSinAsignacion.length > 0" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-450">Nuevos pacientes sin asignar</label>
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold text-amber-800 ring-1 ring-inset ring-amber-600/20">
                                        Prioridad de asignación
                                    </span>
                                </div>

                                <div class="grid gap-3 grid-cols-1 sm:grid-cols-3">
                                    <div
                                        v-for="paciente in props.pacientesSinAsignacion"
                                        :key="paciente.cod_pacientes"
                                        @click="selectPaciente(paciente.cod_pacientes)"
                                        class="flex items-center justify-between p-3.5 rounded-2xl border transition-all duration-300 cursor-pointer text-left relative overflow-hidden"
                                        :class="form.cod_pacientes_anp === paciente.cod_pacientes ? 'border-emerald-500 bg-emerald-50/20 ring-1 ring-emerald-500 shadow-sm' : 'border-amber-200/80 bg-amber-50/5 hover:border-amber-300 hover:bg-amber-50/10'"
                                    >
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="h-9 w-9 rounded-xl flex items-center justify-center font-extrabold text-[10px] shrink-0"
                                                :class="form.cod_pacientes_anp === paciente.cod_pacientes ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gradient-to-br from-amber-50 to-orange-100 border border-amber-200 text-amber-800'">
                                                {{ paciente.usuario?.nom_usu ? paciente.usuario.nom_usu.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase() : 'P' }}
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-black text-slate-900 leading-none truncate max-w-[130px]">{{ paciente.usuario?.nom_usu }}</h4>
                                                <p class="text-[9px] font-semibold text-slate-400 mt-1.5 truncate max-w-[130px]">{{ paciente.usuario?.cor_usu }}</p>
                                            </div>
                                        </div>

                                        <!-- Small selection badge -->
                                        <span class="h-4.5 w-4.5 rounded-full border flex items-center justify-center shrink-0 transition-colors"
                                            :class="form.cod_pacientes_anp === paciente.cod_pacientes ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 bg-white'">
                                            <svg v-if="form.cod_pacientes_anp === paciente.cod_pacientes" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Search Selection Grid (Highly intuitive cards!) -->
                            <div class="space-y-3">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Resultados del Filtro</label>
                                
                                <div v-if="pacientesFiltrados.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-xs text-slate-400 font-semibold">
                                    No se encontraron pacientes activos coincidentes.
                                </div>

                                <div v-else class="grid gap-3 grid-cols-1 sm:grid-cols-2">
                                    <div
                                        v-for="paciente in pacientesFiltrados.slice(0, 6)"
                                        :key="paciente.cod_pacientes"
                                        @click="selectPaciente(paciente.cod_pacientes)"
                                        class="flex items-center justify-between p-4 rounded-2xl border transition-all duration-300 cursor-pointer text-left"
                                        :class="form.cod_pacientes_anp === paciente.cod_pacientes ? 'border-emerald-500 bg-emerald-50/20 ring-1 ring-emerald-500' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="h-10 w-10 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0"
                                                :class="form.cod_pacientes_anp === paciente.cod_pacientes ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-gradient-to-br from-violet-50 to-indigo-100 border border-violet-200 text-violet-800'">
                                                {{ paciente.usuario?.nom_usu ? paciente.usuario.nom_usu.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase() : 'P' }}
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-slate-900 leading-none truncate max-w-[160px]">{{ paciente.usuario?.nom_usu }}</h4>
                                                <p class="text-[10px] font-semibold text-slate-400 mt-1 truncate max-w-[160px]">{{ paciente.usuario?.cor_usu }}</p>
                                            </div>
                                        </div>

                                        <!-- Selection Indicator -->
                                        <span class="h-5 w-5 rounded-full border flex items-center justify-center shrink-0 transition-colors"
                                            :class="form.cod_pacientes_anp === paciente.cod_pacientes ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 bg-white'">
                                            <svg v-if="form.cod_pacientes_anp === paciente.cod_pacientes" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <InputError class="mt-2" :message="form.errors.cod_pacientes_anp" />
                        </div>

                        <!-- ---------------- STEP 2: NUTRICIONISTA SELECTION ---------------- -->
                        <div v-show="currentStep === 2" class="space-y-6">
                            <div>
                                <h3 class="text-lg font-black text-slate-950">Seleccionar Nutricionista</h3>
                                <p class="text-xs text-slate-400 font-semibold mt-1">Busca y elige un profesional de la nutrición activo.</p>
                            </div>

                            <div class="bg-slate-50 border border-slate-200/60 p-5 rounded-2xl">
                                <label for="buscar_nutricionista" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Buscar Nutricionista</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="buscar_nutricionista"
                                        v-model="busquedaNutricionista"
                                        type="text"
                                        placeholder="Busca por nombre, correo, especialidad..."
                                        class="pl-9 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 placeholder-slate-400"
                                    >
                                </div>
                            </div>

                            <!-- Live Search Selection Grid (Nutricionistas cards!) -->
                            <div class="space-y-3">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Resultados del Filtro</label>
                                
                                <div v-if="nutricionistasFiltrados.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-xs text-slate-400 font-semibold">
                                    No se encontraron nutricionistas activos coincidentes.
                                </div>

                                <div v-else class="grid gap-3 grid-cols-1 sm:grid-cols-2">
                                    <div
                                        v-for="nutricionista in nutricionistasFiltrados.slice(0, 6)"
                                        :key="nutricionista.cod_nutricionistas"
                                        @click="selectNutricionista(nutricionista.cod_nutricionistas)"
                                        class="flex items-center justify-between p-4 rounded-2xl border transition-all duration-300 cursor-pointer text-left"
                                        :class="form.cod_nutricionistas_anp === nutricionista.cod_nutricionistas ? 'border-emerald-500 bg-emerald-50/20 ring-1 ring-emerald-500' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="h-10 w-10 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0"
                                                :class="form.cod_nutricionistas_anp === nutricionista.cod_nutricionistas ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 text-emerald-800'">
                                                {{ nutricionista.usuario?.nom_usu ? nutricionista.usuario.nom_usu.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase() : 'N' }}
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-slate-900 leading-none truncate max-w-[160px]">Dr. {{ nutricionista.usuario?.nom_usu }}</h4>
                                                <p class="text-[10px] font-semibold text-slate-400 mt-1 truncate max-w-[160px]">{{ nutricionista.usuario?.cor_usu }}</p>
                                                <span class="mt-1 inline-block text-[9px] font-extrabold text-emerald-700 bg-emerald-50 px-1 rounded">
                                                    {{ nutricionista.esp_nut || 'Nutricionista General' }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Selection Indicator -->
                                        <span class="h-5 w-5 rounded-full border flex items-center justify-center shrink-0 transition-colors"
                                            :class="form.cod_nutricionistas_anp === nutricionista.cod_nutricionistas ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 bg-white'">
                                            <svg v-if="form.cod_nutricionistas_anp === nutricionista.cod_nutricionistas" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <InputError class="mt-2" :message="form.errors.cod_nutricionistas_anp" />
                        </div>

                        <!-- ---------------- STEP 3: CLINICAL CONFIRMATION ---------------- -->
                        <div v-show="currentStep === 3" class="space-y-6">
                            <div>
                                <h3 class="text-lg font-black text-slate-950">Ajustes Clínicos y Confirmación</h3>
                                <p class="text-xs text-slate-400 font-semibold mt-1">Configura la vigencia de la asignación y revisa los datos de conexión.</p>
                            </div>

                            <!-- Connection Review Map (Matches Asignaciones Index visualization!) -->
                            <div class="rounded-3xl border border-slate-200 bg-slate-50/50 p-6 flex flex-col items-center">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-4">Relación Clínica por Establecer</p>
                                
                                <div class="flex items-center justify-between gap-4 w-full max-w-lg">
                                    <!-- Selected Patient Avatar -->
                                    <div class="flex flex-col items-center text-center max-w-[45%]">
                                        <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-violet-50 to-indigo-100 border border-violet-200 text-violet-800 font-black text-sm flex items-center justify-center shadow-inner">
                                            {{ selectedPacienteData?.usuario?.nom_usu ? selectedPacienteData.usuario.nom_usu.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase() : 'P' }}
                                        </div>
                                        <h4 class="mt-2.5 text-xs font-black text-slate-900 truncate w-full" :title="selectedPacienteData?.usuario?.nom_usu">
                                            {{ selectedPacienteData?.usuario?.nom_usu ?? 'Paciente' }}
                                        </h4>
                                        <p class="text-[9px] text-slate-400 font-semibold truncate w-full" :title="selectedPacienteData?.usuario?.cor_usu">
                                            {{ selectedPacienteData?.usuario?.cor_usu ?? 'Sin correo' }}
                                        </p>
                                        <span class="mt-1.5 inline-flex rounded-md bg-violet-50 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wide text-violet-700 ring-1 ring-inset ring-violet-700/10">
                                            Paciente
                                        </span>
                                    </div>

                                    <!-- Connector Visual Icon -->
                                    <div class="flex-1 flex flex-col items-center justify-center">
                                        <div class="relative w-full flex items-center justify-center">
                                            <span class="absolute h-0.5 w-full bg-dashed border-t border-slate-200"></span>
                                            <span class="relative z-10 h-3 w-3 rounded-full bg-emerald-500 shadow-md shadow-emerald-500/50 animate-ping"></span>
                                        </div>
                                        <span class="mt-2 text-[8px] font-extrabold uppercase tracking-widest text-slate-400 select-none">
                                            Vínculo Activo
                                        </span>
                                    </div>

                                    <!-- Selected Doctor Avatar -->
                                    <div class="flex flex-col items-center text-center max-w-[45%]">
                                        <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 text-emerald-800 font-black text-sm flex items-center justify-center shadow-inner">
                                            {{ selectedNutricionistaData?.usuario?.nom_usu ? selectedNutricionistaData.usuario.nom_usu.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase() : 'N' }}
                                        </div>
                                        <h4 class="mt-2.5 text-xs font-black text-slate-900 truncate w-full" :title="selectedNutricionistaData?.usuario?.nom_usu">
                                            Dr. {{ selectedNutricionistaData?.usuario?.nom_usu ?? 'Nutricionista' }}
                                        </h4>
                                        <p class="text-[9px] text-slate-400 font-semibold truncate w-full" :title="selectedNutricionistaData?.usuario?.cor_usu">
                                            {{ selectedNutricionistaData?.usuario?.cor_usu ?? 'Sin correo' }}
                                        </p>
                                        <span class="mt-1.5 inline-flex rounded-md bg-emerald-50 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-700/10">
                                            Nutricionista
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Parameters Inputs -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                
                                <!-- Date Input with custom trigger icon picker -->
                                <div>
                                    <InputLabel for="fec_anp" value="Fecha de Asignación" />
                                    <div class="mt-1.5 flex items-center gap-2">
                                        <div class="relative flex-1">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <input
                                                id="fec_anp"
                                                ref="fechaAsignacionInput"
                                                type="date"
                                                v-model="form.fec_anp"
                                                class="pl-9 mt-1 block w-full rounded-xl border-slate-200 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 pr-3 h-[44px]"
                                                required
                                            >
                                        </div>
                                        <button
                                            type="button"
                                            class="rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-3.5 h-[44px] text-xs font-bold text-slate-700 transition cursor-pointer"
                                            @click="abrirSelectorFecha"
                                        >
                                            Elegir
                                        </button>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.fec_anp" />
                                </div>

                                <!-- Status Input -->
                                <div>
                                    <InputLabel for="est_anp" value="Estado Inicial" />
                                    <select
                                        id="est_anp"
                                        v-model="form.est_anp"
                                        class="app-select mt-1.5"
                                        required
                                    >
                                        <option
                                            v-for="estado in estados"
                                            :key="estado"
                                            :value="estado"
                                        >
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_anp" />
                                </div>
                            </div>

                            <!-- Error list banner for step 3 -->
                            <div v-if="Object.keys(form.errors).length > 0" class="rounded-2xl border border-red-200 bg-red-50/70 p-4 text-xs leading-relaxed text-red-850 flex items-start gap-2.5 shadow-sm mb-4">
                                <svg class="h-5 w-5 text-red-650 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <span class="font-bold block text-red-950 mb-0.5">Se encontraron errores de validación:</span>
                                    <ul class="list-disc list-inside space-y-0.5 font-medium text-red-700/95">
                                        <li v-if="form.errors.cod_pacientes_anp">Paciente: {{ form.errors.cod_pacientes_anp }}</li>
                                        <li v-if="form.errors.cod_nutricionistas_anp">Nutricionista: {{ form.errors.cod_nutricionistas_anp }}</li>
                                        <li v-if="form.errors.fec_anp">Fecha: {{ form.errors.fec_anp }}</li>
                                        <li v-if="form.errors.est_anp">Estado: {{ form.errors.est_anp }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Rules banner -->
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 text-xs leading-relaxed text-emerald-800 flex items-start gap-2.5 shadow-sm">
                                <svg class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <span class="font-bold block text-emerald-950 mb-0.5">Regla de Asignación Única</span>
                                    <span class="text-emerald-700/90 font-medium">Un paciente solo puede contar con una vinculación clínica activa a la vez. Al guardar, el nutricionista recibirá una notificación instantánea sobre la asignación del paciente.</span>
                                </div>
                            </div>
                        </div>

                        <!-- ---------------- WIZARD NAVIGATION FOOTER ---------------- -->
                        <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between gap-4">
                            <!-- Prev Step Button -->
                            <div>
                                <button
                                    v-if="currentStep > 1"
                                    @click="prevStep"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 px-5 py-3 text-xs font-bold text-slate-700 transition cursor-pointer"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Paso Anterior
                                </button>
                            </div>

                            <!-- Next / Save Step Button -->
                            <div>
                                <!-- Next Step Button -->
                                <button
                                    v-if="currentStep < 3"
                                    @click="nextStep"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition cursor-pointer"
                                >
                                    Siguiente Paso
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>

                                <!-- Submit Button (on step 3) -->
                                <button
                                    v-else
                                    :disabled="form.processing"
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition cursor-pointer disabled:opacity-50"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ form.processing ? 'Procesando...' : 'Confirmar y Guardar Asignación' }}
                                </button>
                            </div>
                        </div>

                        <!-- General errors handling -->
                        <div
                            v-if="page.props.errors?.general"
                            class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3.5 text-sm text-red-700 shadow-sm flex items-center gap-2"
                        >
                            <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            {{ page.props.errors.general }}
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirm Reassignment Modal -->
        <ConfirmModal
            :show="mostrarConfirmacionReasignar"
            title="Reasignar Paciente"
            :message="`Este paciente ya tiene asignado a &quot;${selectedPacienteData?.nutricionista_activo}&quot;. ¿Desea reasignar igualmente? Esto dará de baja su asignación anterior y establecerá al nuevo nutricionista.`"
            confirm-text="Sí, Reasignar"
            cancel-text="Cancelar"
            type="warning"
            @confirm="ejecutarEnvio"
            @close="mostrarConfirmacionReasignar = false"
        />
    </AuthenticatedLayout>
</template>

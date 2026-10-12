<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    pacientes: {
        type: Array,
        default: () => [],
    },
    pacienteSeleccionado: {
        type: [String, Number],
        default: '',
    },
    paciente: {
        type: Object,
        default: null,
    },
    estados: {
        type: Array,
        default: () => ['activo', 'inactivo'],
    },
    tipos: {
        type: Array,
        default: () => [
            'Patología',
            'Alergia',
            'Medicamento',
            'Antecedente familiar',
            'Hábitos alimentarios',
            'Actividad física',
            'Observación clínica',
            'Otro',
        ],
    },
    prioridades: {
        type: Array,
        default: () => ['baja', 'media', 'alta'],
    },
    modo: {
        type: String,
        default: 'admin',
    },
    action: {
        type: String,
        default: '',
    },
    volverUrl: {
        type: String,
        default: '',
    },
});

const busquedaPaciente = ref('');

const form = useForm({
    cod_pacientes_cxp: props.pacienteSeleccionado || props.paciente?.cod_pacientes || '',
    des_cxp: '',
    fec_cxp: new Date().toISOString().slice(0, 10),
    tip_cxp: '',
    pri_cxp: 'media',
    est_cxp: 'activo',
});

const esNutricionista = computed(() => props.modo === 'nutricionista');

const tiposContextoCatalogo = [
    {
        tipo: 'Alergia',
        titulo: 'Alergia',
        descripcion: 'Reacción o sensibilidad relevante ante alimentos específicos.',
        prioridadSugerida: 'alta',
        ejemplo: 'Alergia a mariscos. Evitar derivados y contaminación cruzada.',
    },
    {
        tipo: 'Restricción alimentaria',
        titulo: 'Restricción alimentaria',
        descripcion: 'Alimentos o grupos que deben evitarse por indicación, tolerancia o condición.',
        prioridadSugerida: 'alta',
        ejemplo: 'Evitar lactosa por intolerancia reportada.',
    },
    {
        tipo: 'Patología',
        titulo: 'Patología',
        descripcion: 'Condición médica que influye en objetivos, planes o seguimiento.',
        prioridadSugerida: 'alta',
        ejemplo: 'Hipertensión arterial. Controlar sodio y alimentos ultraprocesados.',
    },
    {
        tipo: 'Medicamento',
        titulo: 'Medicamento',
        descripcion: 'Tratamiento actual que puede afectar alimentación, horarios o tolerancia.',
        prioridadSugerida: 'media',
        ejemplo: 'Uso de metformina. Considerar horarios de comida y tolerancia digestiva.',
    },
    {
        tipo: 'Hábitos alimentarios',
        titulo: 'Hábitos alimentarios',
        descripcion: 'Patrones de consumo, horarios, frecuencia o preferencias habituales.',
        prioridadSugerida: 'media',
        ejemplo: 'Omite desayuno con frecuencia y concentra consumo por la noche.',
    },
    {
        tipo: 'Actividad física',
        titulo: 'Actividad física',
        descripcion: 'Nivel de actividad o entrenamiento que influye en requerimientos.',
        prioridadSugerida: 'media',
        ejemplo: 'Entrena fuerza 4 veces por semana en horario nocturno.',
    },
    {
        tipo: 'Preferencia alimentaria',
        titulo: 'Preferencia alimentaria',
        descripcion: 'Preferencias útiles para adherencia del plan nutricional.',
        prioridadSugerida: 'baja',
        ejemplo: 'Prefiere preparaciones caseras y evita alimentos muy condimentados.',
    },
    {
        tipo: 'Factor económico/social',
        titulo: 'Factor económico/social',
        descripcion: 'Condiciones de acceso, presupuesto, tiempo o entorno familiar.',
        prioridadSugerida: 'media',
        ejemplo: 'Cuenta con poco tiempo para cocinar durante días laborales.',
    },
    {
        tipo: 'Antecedente familiar',
        titulo: 'Antecedente familiar',
        descripcion: 'Antecedentes relevantes para seguimiento preventivo.',
        prioridadSugerida: 'media',
        ejemplo: 'Antecedente familiar de diabetes tipo 2.',
    },
    {
        tipo: 'Observación clínica',
        titulo: 'Observación clínica',
        descripcion: 'Hallazgo profesional útil para seguimiento nutricional.',
        prioridadSugerida: 'media',
        ejemplo: 'Refiere ansiedad por comer en horarios nocturnos.',
    },
    {
        tipo: 'Otro',
        titulo: 'Otro',
        descripcion: 'Información adicional no clasificada en los tipos anteriores.',
        prioridadSugerida: 'baja',
        ejemplo: 'Detalle complementario relevante para la atención.',
    },
];

const tipoSeleccionadoInfo = computed(() => {
    return tiposContextoCatalogo.find((item) => item.tipo === form.tip_cxp) || null;
});

const seleccionarTipoContexto = (tipo) => {
    form.tip_cxp = tipo;
};

watch(
    () => form.tip_cxp,
    (tipo) => {
        const info = tiposContextoCatalogo.find((item) => item.tipo === tipo);

        if (!info) {
            return;
        }

        form.pri_cxp = info.prioridadSugerida || 'media';
    }
);

const prioridadLabel = (prioridad) => {
    const labels = {
        baja: 'Baja',
        media: 'Media',
        alta: 'Alta',
    };

    return labels[prioridad] || prioridad;
};

const prioridadDescripcion = (prioridad) => {
    const labels = {
        baja: 'Dato complementario o de baja urgencia clínica.',
        media: 'Dato relevante para seguimiento y toma de decisiones.',
        alta: 'Dato crítico que debe considerarse antes de objetivos, planes o validaciones.',
    };

    return labels[prioridad] || '';
};

const clasePrioridad = (prioridad) => {
    if (prioridad === 'alta') {
        return 'border-rose-200 bg-rose-50 text-rose-800';
    }

    if (prioridad === 'media') {
        return 'border-amber-200 bg-amber-50 text-amber-800';
    }

    return 'border-slate-200 bg-slate-50 text-slate-700';
};

const placeholderDescripcion = computed(() => {
    if (tipoSeleccionadoInfo.value?.ejemplo) {
        return `Ejemplo: ${tipoSeleccionadoInfo.value.ejemplo}`;
    }

    return 'Describe antecedentes relevantes, hábitos alimentarios, restricciones, medicamentos u observaciones útiles para el plan nutricional.';
});

const actionUrl = computed(() => {
    return props.action || route('contextos-paciente.store');
});

const volver = computed(() => {
    if (props.volverUrl) {
        return props.volverUrl;
    }

    return route('contextos-paciente.index');
});

const pacientesFiltrados = computed(() => {
    const termino = busquedaPaciente.value.trim().toLowerCase();

    if (!termino) {
        return props.pacientes;
    }

    return props.pacientes.filter((p) => {
        const nombre = nombrePaciente(p).toLowerCase();
        const correo = String(p.usuario?.cor_usu ?? '').toLowerCase();
        const ci = String(p.usuario?.ci_usu ?? '').toLowerCase();

        return [nombre, correo, ci].some((v) => v.includes(termino));
    });
});

const pacienteActivo = computed(() => {
    return props.pacientes.find((p) => String(p.cod_pacientes) === String(form.cod_pacientes_cxp))
        ?? props.paciente
        ?? null;
});

const submit = () => {
    form.post(actionUrl.value, {
        preserveScroll: true,
    });
};

const nombrePaciente = (p) => {
    if (!p) {
        return 'Paciente sin nombre';
    }

    if (p.usuario?.nombre_completo) {
        return p.usuario.nombre_completo;
    }

    return `${p.usuario?.nom_usu ?? ''} ${p.usuario?.ape_usu ?? ''}`.trim() || 'Paciente sin nombre';
};

const obtenerIniciales = (nombre) => {
    if (!nombre || nombre === 'Paciente sin nombre') return 'P';
    return nombre
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const obtenerEstiloCategoria = (tipo) => {
    const estilos = {
        'Alergia': {
            bg: 'bg-rose-50/60 text-rose-800 border-rose-100/80',
            active: 'border-rose-500 bg-rose-50/90 text-rose-900 ring-4 ring-rose-500/10 shadow-lg shadow-rose-500/5',
            badge: 'bg-rose-100 text-rose-800',
            iconColor: 'text-rose-600',
        },
        'Restricción alimentaria': {
            bg: 'bg-amber-50/60 text-amber-800 border-amber-100/80',
            active: 'border-amber-500 bg-amber-50/90 text-amber-900 ring-4 ring-amber-500/10 shadow-lg shadow-amber-500/5',
            badge: 'bg-amber-100 text-amber-800',
            iconColor: 'text-amber-600',
        },
        'Patología': {
            bg: 'bg-rose-50/60 text-rose-800 border-rose-100/80',
            active: 'border-rose-500 bg-rose-50/90 text-rose-900 ring-4 ring-rose-500/10 shadow-lg shadow-rose-500/5',
            badge: 'bg-rose-100 text-rose-800',
            iconColor: 'text-rose-600',
        },
        'Medicamento': {
            bg: 'bg-indigo-50/60 text-indigo-800 border-indigo-100/80',
            active: 'border-indigo-500 bg-indigo-50/90 text-indigo-900 ring-4 ring-indigo-500/10 shadow-lg shadow-indigo-500/5',
            badge: 'bg-indigo-100 text-indigo-850',
            iconColor: 'text-indigo-600',
        },
        'Hábitos alimentarios': {
            bg: 'bg-emerald-50/60 text-emerald-800 border-emerald-100/80',
            active: 'border-emerald-500 bg-emerald-50/90 text-emerald-900 ring-4 ring-emerald-500/10 shadow-lg shadow-emerald-500/5',
            badge: 'bg-emerald-100 text-emerald-850',
            iconColor: 'text-emerald-600',
        },
        'Actividad física': {
            bg: 'bg-sky-50/60 text-sky-800 border-sky-100/80',
            active: 'border-sky-500 bg-sky-50/90 text-sky-900 ring-4 ring-sky-500/10 shadow-lg shadow-sky-500/5',
            badge: 'bg-sky-100 text-sky-850',
            iconColor: 'text-sky-600',
        },
        'Preferencia alimentaria': {
            bg: 'bg-teal-50/60 text-teal-800 border-teal-100/80',
            active: 'border-teal-500 bg-teal-50/90 text-teal-900 ring-4 ring-teal-500/10 shadow-lg shadow-teal-500/5',
            badge: 'bg-teal-100 text-teal-855',
            iconColor: 'text-teal-600',
        },
        'Factor económico/social': {
            bg: 'bg-purple-50/60 text-purple-800 border-purple-100/80',
            active: 'border-purple-500 bg-purple-50/90 text-purple-900 ring-4 ring-purple-500/10 shadow-lg shadow-purple-500/5',
            badge: 'bg-purple-100 text-purple-850',
            iconColor: 'text-purple-600',
        },
        'Antecedente familiar': {
            bg: 'bg-violet-50/60 text-violet-850 border-violet-100/80',
            active: 'border-violet-500 bg-violet-50/90 text-violet-900 ring-4 ring-violet-500/10 shadow-lg shadow-violet-500/5',
            badge: 'bg-violet-100 text-violet-850',
            iconColor: 'text-violet-600',
        },
        'Observación clínica': {
            bg: 'bg-slate-50/60 text-slate-800 border-slate-100/80',
            active: 'border-slate-500 bg-slate-50/90 text-slate-900 ring-4 ring-slate-500/10 shadow-lg shadow-slate-500/5',
            badge: 'bg-slate-100 text-slate-700',
            iconColor: 'text-slate-600',
        },
    };

    return estilos[tipo] || {
        bg: 'bg-slate-50/60 text-slate-800 border-slate-100/80',
        active: 'border-slate-400 bg-slate-50/90 text-slate-900 ring-4 ring-slate-400/10 shadow-lg shadow-slate-400/5',
        badge: 'bg-slate-100 text-slate-700',
        iconColor: 'text-slate-500',
    };
};
</script>

<template>
    <Head title="Nuevo contexto del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 shadow-sm ring-1 ring-emerald-100">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">
                            Ficha Clínica de Seguimiento
                        </p>
                        <h2 class="mt-0.5 text-2xl font-extrabold tracking-tight leading-tight text-slate-900">
                            Registrar Contexto del Paciente
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Agrega antecedentes, hábitos o condiciones críticas de salud que modelarán su pauta nutricional.
                        </p>
                    </div>
                </div>

                <Link
                    :href="volver"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-slate-950 active:scale-95 transition-all"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] items-start">
                    
                    <!-- Formulario de Contexto Clínico -->
                    <div class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-5">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-600">
                                    Historial Diagnóstico
                                </p>
                                <h3 class="mt-1 text-lg font-extrabold text-slate-950">
                                    Nuevo Antecedente Clínico
                                </h3>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100/60">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ esNutricionista ? 'Modo Clínico Activo' : 'Administrador' }}
                            </span>
                        </div>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            
                            <!-- BÚSQUEDA Y SELECCIÓN DE PACIENTE (Solo Administrador) -->
                            <div v-if="!esNutricionista" class="space-y-4">
                                <!-- CASO 1: Paciente no seleccionado todavía -->
                                <div v-if="!form.cod_pacientes_cxp" class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 shadow-xs transition-all relative">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                        Buscar Paciente
                                    </label>
                                    
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </span>
                                        <input
                                            id="busqueda_paciente"
                                            v-model="busquedaPaciente"
                                            type="text"
                                            class="block w-full rounded-2xl border-slate-300 pl-11 pr-4 py-3 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm transition-all"
                                            placeholder="Ingresa nombre, correo o CI del paciente..."
                                        />
                                    </div>

                                    <!-- Listado de pacientes encontrados -->
                                    <div v-if="busquedaPaciente.trim()" class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white max-h-60 overflow-y-auto divide-y divide-slate-100 shadow-lg">
                                        <button
                                            v-for="p in pacientesFiltrados"
                                            :key="p.cod_pacientes"
                                            type="button"
                                            @click="form.cod_pacientes_cxp = p.cod_pacientes; busquedaPaciente = ''"
                                            class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 transition active:bg-slate-100"
                                        >
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-bold text-slate-700">
                                                {{ obtenerIniciales(nombrePaciente(p)) }}
                                            </span>
                                            <div>
                                                <p class="text-xs font-bold text-slate-900 leading-tight">
                                                    {{ nombrePaciente(p) }}
                                                </p>
                                                <p class="text-[10px] text-slate-400 mt-0.5 leading-none">
                                                    {{ p.usuario?.cor_usu }} &nbsp;•&nbsp; CI: {{ p.usuario?.ci_usu }}
                                                </p>
                                            </div>
                                        </button>
                                        <div v-if="pacientesFiltrados.length === 0" class="p-4 text-center text-xs text-slate-400">
                                            No se encontraron pacientes activos con esos datos.
                                        </div>
                                    </div>
                                </div>

                                <!-- CASO 2: Paciente seleccionado -->
                                <div v-else class="flex items-center justify-between gap-4 rounded-2xl bg-emerald-500/5 p-4 border border-emerald-500/20 shadow-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-xs font-bold text-white shadow-sm">
                                            {{ obtenerIniciales(nombrePaciente(pacienteActivo)) }}
                                        </span>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Paciente seleccionado</p>
                                            <p class="text-sm font-bold text-slate-800 mt-0.5">{{ nombrePaciente(pacienteActivo) }}</p>
                                        </div>
                                    </div>
                                    <button 
                                        type="button"
                                        @click="form.cod_pacientes_cxp = ''"
                                        class="inline-flex items-center gap-1 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 active:scale-95 transition-all shadow-xs"
                                    >
                                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Cambiar paciente
                                    </button>
                                </div>
                                <InputError :message="form.errors.cod_pacientes_cxp" />
                            </div>

                            <!-- MODO NUTRICIONISTA: Visualizador Estático de Paciente -->
                            <div v-else class="flex items-center gap-3 rounded-2xl bg-sky-500/5 p-4 border border-sky-500/10 shadow-xs">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-500 text-sm font-bold text-white shadow-sm">
                                    {{ obtenerIniciales(nombrePaciente(pacienteActivo)) }}
                                </span>
                                <div>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">Paciente Clínico Asignado</p>
                                    <p class="text-sm font-bold text-slate-800 mt-1 leading-tight">
                                        {{ nombrePaciente(pacienteActivo) }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ pacienteActivo?.usuario?.cor_usu }}
                                    </p>
                                </div>
                                <InputError :message="form.errors.cod_pacientes_cxp" />
                            </div>

                            <!-- FECHA DEL CONTEXTO -->
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label for="fec_cxp" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                        Fecha del registro
                                    </label>
                                    <input
                                        id="fec_cxp"
                                        v-model="form.fec_cxp"
                                        type="date"
                                        class="block w-full rounded-2xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm transition-all"
                                    />
                                    <InputError class="mt-2" :message="form.errors.fec_cxp" />
                                </div>
                            </div>

                            <!-- SELECCIÓN DE TIPO DE CONTEXTO -->
                            <div class="space-y-3">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Tipo de Antecedente Clínico
                                </label>

                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <button
                                        v-for="item in tiposContextoCatalogo.filter((item) => tipos.includes(item.tipo))"
                                        :key="item.tipo"
                                        type="button"
                                        class="relative flex flex-col justify-between p-4 rounded-2xl border text-left transition-all duration-200 group active:scale-97 select-none overflow-hidden"
                                        :class="form.tip_cxp === item.tipo
                                            ? obtenerEstiloCategoria(item.tipo).active
                                            : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-xs text-slate-700'"
                                        @click="seleccionarTipoContexto(item.tipo)"
                                    >
                                        <div class="space-y-3.5">
                                            <!-- Fila superior: Icono y Badge -->
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 transition-colors group-hover:bg-white shadow-xs" :class="form.tip_cxp === item.tipo ? 'bg-white shadow-md shadow-slate-900/5' : ''">
                                                    <!-- Renderizado condicional de iconos SVG -->
                                                    <svg v-if="item.tipo === 'Patología'" class="h-5 w-5" :class="obtenerEstiloCategoria(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                    </svg>
                                                    <svg v-else-if="item.tipo === 'Alergia'" class="h-5 w-5" :class="obtenerEstiloCategoria(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                    </svg>
                                                    <svg v-else-if="item.tipo === 'Medicamento'" class="h-5 w-5" :class="obtenerEstiloCategoria(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 9.172V5L8 4z" />
                                                    </svg>
                                                    <svg v-else-if="item.tipo === 'Antecedente familiar'" class="h-5 w-5" :class="obtenerEstiloCategoria(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    <svg v-else-if="item.tipo === 'Hábitos alimentarios'" class="h-5 w-5" :class="obtenerEstiloCategoria(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <svg v-else-if="item.tipo === 'Actividad física'" class="h-5 w-5" :class="obtenerEstiloCategoria(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                    </svg>
                                                    <svg v-else-if="item.tipo === 'Observación clínica'" class="h-5 w-5" :class="obtenerEstiloCategoria(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <svg v-else class="h-5 w-5" :class="obtenerEstiloCategoria(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </span>
                                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider shadow-xs" :class="obtenerEstiloCategoria(item.tipo).badge">
                                                    {{ prioridadLabel(item.prioridadSugerida) }}
                                                </span>
                                            </div>
                                            <!-- Titulo y Descripcion -->
                                            <div>
                                                <p class="text-xs font-extrabold text-slate-900 group-hover:text-emerald-950 transition">
                                                    {{ item.titulo }}
                                                </p>
                                                <p class="mt-1 text-[10px] leading-relaxed text-slate-500 line-clamp-2">
                                                    {{ item.descripcion }}
                                                </p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                <InputError :message="form.errors.tip_cxp" />
                            </div>

                            <!-- PRIORIDAD CLÍNICA SEGMENTADA -->
                            <div class="rounded-3xl border border-slate-200 bg-slate-50/50 p-5 space-y-4">
                                <div class="flex items-center justify-between gap-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Prioridad del Antecedente
                                    </label>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase">Categorización clínica</span>
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="prioridad in prioridades"
                                        :key="prioridad"
                                        type="button"
                                        @click="form.pri_cxp = prioridad"
                                        class="flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all duration-200 shadow-xs active:scale-97 select-none font-bold text-xs"
                                        :class="form.pri_cxp === prioridad
                                            ? clasePrioridad(prioridad) + ' ring-2 ring-emerald-500/20'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
                                    >
                                        {{ prioridadLabel(prioridad) }}
                                    </button>
                                </div>

                                <!-- Mensaje condicional de prioridad Alta -->
                                <div v-if="form.pri_cxp === 'alta'" class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 text-xs text-rose-800 flex items-start gap-2.5 shadow-sm animate-pulse-subtle">
                                    <span class="flex h-2.5 w-2.5 relative mt-1 shrink-0">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                                    </span>
                                    <div>
                                        <p class="font-extrabold">Alerta de Antecedente Crítico</p>
                                        <p class="mt-0.5 leading-relaxed text-[11px] text-rose-700">Este antecedente se considerará crítico de salud y saltará como advertencia prioritaria en el buzón de control del nutricionista antes de cualquier pauta alimenticia.</p>
                                    </div>
                                </div>
                                <InputError :message="form.errors.pri_cxp" />
                            </div>

                            <!-- DESCRIPCIÓN DEL CONTEXTO CLÍNICO -->
                            <div class="space-y-2">
                                <label for="des_cxp" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Descripción / Detalles del Contexto
                                </label>

                                <textarea
                                    id="des_cxp"
                                    v-model="form.des_cxp"
                                    rows="6"
                                    class="block w-full rounded-2xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm leading-relaxed"
                                    :placeholder="placeholderDescripcion"
                                />
                                <InputError :message="form.errors.des_cxp" />

                                <!-- Guía dinámica animada de redacción médica -->
                                <div
                                    v-if="tipoSeleccionadoInfo"
                                    class="rounded-2xl border border-sky-100 bg-sky-50/40 p-4 text-xs text-sky-950 transition-all duration-300"
                                >
                                    <div class="flex items-center gap-2">
                                        <svg class="h-5 w-5 text-sky-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        <p class="font-extrabold text-sky-900">
                                            Guía para registrar: {{ tipoSeleccionadoInfo.titulo }}
                                        </p>
                                    </div>

                                    <p class="mt-2 leading-relaxed text-[11px] text-sky-850">
                                        {{ tipoSeleccionadoInfo.descripcion }}
                                    </p>

                                    <div class="mt-3 rounded-xl bg-white/70 p-3 border border-sky-100/50 flex flex-col gap-1">
                                        <p class="text-[9px] font-bold text-sky-700 uppercase tracking-widest leading-none">Ejemplo sugerido</p>
                                        <p class="mt-1 font-mono text-[10px] text-sky-800 italic leading-relaxed">
                                            "{{ tipoSeleccionadoInfo.ejemplo }}"
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- ESTADO (PÍLDORAS CONMUTADORAS PREMIUM) -->
                            <div class="rounded-3xl border border-slate-200 bg-slate-50/50 p-5 space-y-4">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Vigencia del Antecedente
                                </label>

                                <div class="flex gap-3">
                                    <button
                                        v-for="estado in estados"
                                        :key="estado"
                                        type="button"
                                        @click="form.est_cxp = estado"
                                        class="flex items-center gap-2 px-4 py-2.5 rounded-2xl border text-xs font-bold capitalize transition-all duration-200 select-none"
                                        :class="form.est_cxp === estado
                                            ? estado === 'activo'
                                                ? 'border-emerald-500 bg-emerald-50 text-emerald-800 ring-2 ring-emerald-500/10'
                                                : 'border-slate-400 bg-slate-100 text-slate-700'
                                            : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                                    >
                                        <span 
                                            class="h-2.5 w-2.5 rounded-full"
                                            :class="estado === 'activo'
                                                ? form.est_cxp === estado ? 'bg-emerald-500 animate-pulse' : 'bg-emerald-400'
                                                : 'bg-slate-400'"
                                        ></span>
                                        {{ estado }}
                                    </button>
                                </div>

                                <p class="text-[10px] text-slate-400 leading-normal">
                                    Mantén la información en vigencia <strong>"activo"</strong>. Si el paciente supera esta condición o ya no requiere control prioritario, cámbialo a <strong>"inactivo"</strong> para archivar el antecedente de forma clínica y segura.
                                </p>
                                <InputError :message="form.errors.est_cxp" />
                            </div>

                            <InputError :message="form.errors.contexto" />

                            <!-- FOOTER DE ACCIONES -->
                            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                                <Link
                                    :href="volver"
                                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 active:scale-95 transition-all shadow-xs"
                                >
                                    Cancelar
                                </Link>

                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white shadow-md shadow-emerald-600/10 hover:bg-emerald-700 hover:shadow-emerald-700/15 active:scale-95 transition-all disabled:opacity-50"
                                >
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    Registrar Antecedente
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Ficha y Resumen del Paciente a la Derecha -->
                    <div class="space-y-6 lg:sticky lg:top-8">
                        
                        <!-- Tarjeta Ficha Paciente Premium -->
                        <div class="overflow-hidden rounded-[2rem] border border-slate-800 bg-slate-950 p-6 text-white shadow-xl relative">
                            <!-- Background gradient flare -->
                            <div class="absolute -right-16 -top-16 h-36 w-36 rounded-full bg-emerald-500/10 blur-2xl"></div>
                            
                            <p class="text-[9px] font-bold uppercase tracking-[0.25em] text-emerald-400">
                                Información de Ficha Médica
                            </p>

                            <div v-if="pacienteActivo" class="mt-6 flex items-center gap-4">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 text-lg font-bold text-white shadow-md shadow-emerald-500/15">
                                    {{ obtenerIniciales(nombrePaciente(pacienteActivo)) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold leading-tight text-white">
                                        {{ nombrePaciente(pacienteActivo) }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-1 leading-none">
                                        {{ pacienteActivo.usuario?.cor_usu }}
                                    </p>
                                </div>
                            </div>
                            
                            <div v-else class="mt-6 flex flex-col items-center justify-center py-8 text-center border border-slate-800 rounded-2xl">
                                <svg class="h-10 w-10 text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <p class="text-xs text-slate-400">Selecciona un paciente para cargar sus detalles clínicos</p>
                            </div>

                            <div v-if="pacienteActivo" class="mt-6 border-t border-slate-800 pt-5 space-y-3.5 text-xs text-slate-300">
                                <div class="flex items-center justify-between border-b border-slate-900 pb-2">
                                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[9px] tracking-[0.15em]">Cédula de Identidad</span>
                                    <span class="font-extrabold text-white">{{ pacienteActivo.usuario?.ci_usu ?? 'No disponible' }}</span>
                                </div>

                                <div class="flex items-center justify-between border-b border-slate-900 pb-2">
                                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[9px] tracking-[0.15em]">Sexo</span>
                                    <span class="font-extrabold text-white capitalize">{{ pacienteActivo.sex_pac ?? 'No disponible' }}</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[9px] tracking-[0.15em]">Estado Perfil</span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-400 capitalize">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        {{ pacienteActivo.est_pac ?? 'No disponible' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta Recomendaciones Clínicas Premium -->
                        <div class="rounded-[2rem] border border-emerald-100 bg-emerald-50/20 p-6 shadow-sm ring-1 ring-emerald-500/5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-700 flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Indicaciones Clínicas de Registro
                            </p>

                            <ul class="mt-4 space-y-3.5 text-xs leading-relaxed text-emerald-900">
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold mt-0.5 shrink-0">✓</span>
                                    <span>Registra antecedentes, intolerancias y patologías que influyan directamente en el cálculo calórico y macronutrientes.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold mt-0.5 shrink-0">✓</span>
                                    <span>Define descripciones detalladas con dosis o severidad para facilitar las consultas rápidas en controles.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold mt-0.5 shrink-0">✓</span>
                                    <span>Inactiva los contextos que el paciente logre superar (por ejemplo, intolerancias superadas), manteniendo el historial intacto en lugar de borrarlo.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
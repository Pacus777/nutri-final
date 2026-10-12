<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    contexto: Object,
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

const form = useForm({
    des_cxp: props.contexto.des_cxp || '',
    fec_cxp: props.contexto.fec_cxp || '',
    tip_cxp: props.contexto.tip_cxp || '',
    pri_cxp: props.contexto.pri_cxp || 'media',
    est_cxp: props.contexto.est_cxp || 'activo',
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

const aplicarPrioridadSugerida = () => {
    if (!tipoSeleccionadoInfo.value) {
        return;
    }

    form.pri_cxp = tipoSeleccionadoInfo.value.prioridadSugerida || 'media';
};

const placeholderDescripcion = computed(() => {
    if (tipoSeleccionadoInfo.value?.ejemplo) {
        return `Ejemplo: ${tipoSeleccionadoInfo.value.ejemplo}`;
    }

    return 'Describe el contexto clínico del paciente.';
});

const esContextoCritico = computed(() => {
    return form.est_cxp === 'activo' && form.pri_cxp === 'alta';
});

const actionUrl = computed(() => {
    return props.action || route('contextos-paciente.update', props.contexto.cod_contextos_paciente);
});

const volver = computed(() => {
    if (props.volverUrl) {
        return props.volverUrl;
    }

    return route('contextos-paciente.historial', props.contexto.cod_pacientes_cxp);
});

const paciente = computed(() => props.contexto?.paciente || null);

const nombrePaciente = computed(() => {
    const usuario = paciente.value?.usuario;

    if (!usuario) {
        return 'Paciente no disponible';
    }

    return usuario.nombre_completo
        || `${usuario.nom_usu ?? ''} ${usuario.ape_usu ?? ''}`.trim()
        || 'Paciente no disponible';
});

const prioridadLabel = (prioridad) => {
    const labels = {
        baja: 'Baja',
        media: 'Media',
        alta: 'Alta',
    };

    return labels[prioridad] || prioridad || 'Media';
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

const submit = () => {
    form.put(actionUrl.value, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar contexto" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">
                        Ficha clínica
                    </p>

                    <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                        Editar contexto del paciente
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Ajusta el contenido manteniendo el historial clínico.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link
                        :href="volver"
                        class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 rounded-3xl border border-amber-200 bg-amber-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">
                        {{ esNutricionista ? 'Paciente asignado' : 'Paciente' }}
                    </p>

                    <h3 class="mt-2 text-lg font-bold text-amber-950">
                        {{ nombrePaciente }}
                    </h3>

                    <p class="mt-1 text-sm text-amber-900">
                        {{ paciente?.usuario?.cor_usu || 'Correo no disponible' }}
                    </p>
                </div>

                <div
                    v-if="esContextoCritico"
                    class="mb-5 rounded-3xl border-2 border-rose-300 bg-rose-50 p-5 shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-700">
                        Contexto crítico activo
                    </p>

                    <h3 class="mt-2 text-lg font-black text-rose-950">
                        Este contexto impacta decisiones clínicas
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-rose-900">
                        Al guardar este contexto como prioridad alta y estado activo, será visible en la ficha del paciente,
                        creación de objetivos y validación de registros alimenticios.
                    </p>
                </div>

                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="fec_cxp" value="Fecha" />

                                    <input
                                        id="fec_cxp"
                                        v-model="form.fec_cxp"
                                        type="date"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    />

                                    <InputError class="mt-2" :message="form.errors.fec_cxp" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel value="Tipo de contexto clínico" />

                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <button
                                            v-for="item in tiposContextoCatalogo.filter((item) => tipos.includes(item.tipo))"
                                            :key="item.tipo"
                                            type="button"
                                            class="rounded-2xl border p-4 text-left transition"
                                            :class="form.tip_cxp === item.tipo
                                                ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-100'
                                                : 'border-slate-200 bg-white hover:border-amber-200 hover:bg-amber-50/40'"
                                            @click="seleccionarTipoContexto(item.tipo)"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900">
                                                        {{ item.titulo }}
                                                    </p>

                                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                                        {{ item.descripcion }}
                                                    </p>
                                                </div>

                                                <span
                                                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                                    :class="clasePrioridad(item.prioridadSugerida)"
                                                >
                                                    {{ prioridadLabel(item.prioridadSugerida) }}
                                                </span>
                                            </div>
                                        </button>
                                    </div>

                                    <InputError class="mt-2" :message="form.errors.tip_cxp" />
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <InputLabel for="pri_cxp" value="Prioridad clínica" />

                                <select
                                    id="pri_cxp"
                                    v-model="form.pri_cxp"
                                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option
                                        v-for="prioridad in prioridades"
                                        :key="prioridad"
                                        :value="prioridad"
                                    >
                                        {{ prioridadLabel(prioridad) }}
                                    </option>
                                </select>

                                <div
                                    class="mt-3 rounded-xl border px-4 py-3 text-sm"
                                    :class="clasePrioridad(form.pri_cxp)"
                                >
                                    <p class="font-semibold">
                                        Prioridad {{ prioridadLabel(form.pri_cxp) }}
                                    </p>

                                    <p class="mt-1">
                                        {{ prioridadDescripcion(form.pri_cxp) }}
                                    </p>
                                </div>

                                <div
                                    v-if="tipoSeleccionadoInfo && tipoSeleccionadoInfo.prioridadSugerida !== form.pri_cxp"
                                    class="mt-3 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800"
                                >
                                    <p>
                                        Prioridad sugerida para este tipo:
                                        <strong>{{ prioridadLabel(tipoSeleccionadoInfo.prioridadSugerida) }}</strong>.
                                    </p>

                                    <button
                                        type="button"
                                        class="mt-2 text-xs font-semibold text-blue-700 hover:text-blue-900"
                                        @click="aplicarPrioridadSugerida"
                                    >
                                        Aplicar prioridad sugerida
                                    </button>
                                </div>

                                <InputError class="mt-2" :message="form.errors.pri_cxp" />
                            </div>

                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
                                <InputLabel for="est_cxp" value="Estado del contexto" />

                                <select
                                    id="est_cxp"
                                    v-model="form.est_cxp"
                                    class="mt-1 block w-full rounded-xl border-emerald-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                    <option
                                        v-for="estado in estados"
                                        :key="estado"
                                        :value="estado"
                                    >
                                        {{ estado }}
                                    </option>
                                </select>

                                <p class="mt-2 text-xs text-emerald-800">
                                    Usa activo cuando el contexto siga vigente. Usa inactivo cuando ya no aplique, sin borrar el antecedente.
                                </p>

                                <InputError class="mt-2" :message="form.errors.est_cxp" />
                            </div>

                            <div>
                                <InputLabel for="des_cxp" value="Descripción" />

                                <textarea
                                    id="des_cxp"
                                    v-model="form.des_cxp"
                                    rows="7"
                                    class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    :placeholder="placeholderDescripcion"
                                />

                                <InputError class="mt-2" :message="form.errors.des_cxp" />

                                <div
                                    v-if="tipoSeleccionadoInfo"
                                    class="mt-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900"
                                >
                                    <p class="font-semibold">
                                        Guía para editar {{ tipoSeleccionadoInfo.titulo }}
                                    </p>

                                    <p class="mt-1 leading-6">
                                        {{ tipoSeleccionadoInfo.descripcion }}
                                    </p>

                                    <p class="mt-2 text-xs leading-5">
                                        Ejemplo: {{ tipoSeleccionadoInfo.ejemplo }}
                                    </p>
                                </div>
                            </div>

                            <InputError class="mt-2" :message="form.errors.contexto" />

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    Resumen del contexto actualizado
                                </p>

                                <div class="mt-3 grid gap-3 md:grid-cols-3">
                                    <p>
                                        <strong>Tipo:</strong>
                                        {{ form.tip_cxp || 'No seleccionado' }}
                                    </p>

                                    <p>
                                        <strong>Prioridad:</strong>
                                        {{ prioridadLabel(form.pri_cxp) }}
                                    </p>

                                    <p>
                                        <strong>Estado:</strong>
                                        {{ form.est_cxp }}
                                    </p>
                                </div>

                                <p
                                    v-if="esContextoCritico"
                                    class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700"
                                >
                                    Este contexto quedará marcado como crítico activo y será visible en módulos de decisión clínica.
                                </p>
                            </div>

                            <div class="flex justify-end gap-3">
                                <Link
                                    :href="volver"
                                    class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                                >
                                    Cancelar
                                </Link>

                                <PrimaryButton :disabled="form.processing">
                                    Guardar cambios
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
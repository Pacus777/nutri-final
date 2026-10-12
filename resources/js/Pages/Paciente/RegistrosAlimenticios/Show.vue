<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    registro: Object,
    resumen: Object,
});

const fmt = (val) => {
    if (val === null || val === undefined || val === '') return '—';
    return Number(val).toFixed(1);
};

const etiquetasTipoComida = {
    desayuno: 'Desayuno',
    media_manana: 'Media mañana',
    almuerzo: 'Almuerzo',
    merienda: 'Merienda',
    cena: 'Cena',
    snack: 'Snack',
};

const ordenTipoComida = ['desayuno', 'media_manana', 'almuerzo', 'merienda', 'cena', 'snack'];

const detallesPorComida = computed(() => {
    const grupos = {};
    for (const d of props.registro.detalles) {
        const tipo = d.tip_dra || 'snack';
        if (!grupos[tipo]) grupos[tipo] = [];
        grupos[tipo].push(d);
    }
    return ordenTipoComida
        .filter((t) => grupos[t])
        .map((t) => ({ tipo: t, label: etiquetasTipoComida[t] ?? t, items: grupos[t] }));
});

const sinDatos = computed(() => props.resumen.total_alimentos - props.resumen.con_datos);

const stats = computed(() => [
    { label: 'Calorías', valor: fmt(props.resumen.cal), unidad: 'kcal', color: 'orange' },
    { label: 'Proteínas', valor: fmt(props.resumen.pro), unidad: 'g', color: 'blue' },
    { label: 'Grasas', valor: fmt(props.resumen.gra), unidad: 'g', color: 'yellow' },
    { label: 'Carbohidratos', valor: fmt(props.resumen.car), unidad: 'g', color: 'green' },
    { label: 'Vitaminas', valor: fmt(props.resumen.vit), unidad: 'µg eq.', color: 'purple' },
    { label: 'Minerales', valor: fmt(props.resumen.min), unidad: 'mg', color: 'teal' },
]);

const colorClasses = {
    orange: {
        wrap: 'bg-orange-50 border-orange-200',
        label: 'text-orange-500',
        valor: 'text-orange-700',
        unidad: 'text-orange-400',
    },
    blue: {
        wrap: 'bg-blue-50 border-blue-200',
        label: 'text-blue-500',
        valor: 'text-blue-700',
        unidad: 'text-blue-400',
    },
    yellow: {
        wrap: 'bg-yellow-50 border-yellow-200',
        label: 'text-yellow-600',
        valor: 'text-yellow-700',
        unidad: 'text-yellow-500',
    },
    green: {
        wrap: 'bg-green-50 border-green-200',
        label: 'text-green-600',
        valor: 'text-green-700',
        unidad: 'text-green-400',
    },
    purple: {
        wrap: 'bg-purple-50 border-purple-200',
        label: 'text-purple-500',
        valor: 'text-purple-700',
        unidad: 'text-purple-400',
    },
    teal: {
        wrap: 'bg-teal-50 border-teal-200',
        label: 'text-teal-600',
        valor: 'text-teal-700',
        unidad: 'text-teal-400',
    },
};

const validacionActual = computed(() => {
    return props.registro?.ultima_validacion_informacion
        || props.registro?.ultimaValidacionInformacion
        || props.registro?.ultima_validacion
        || null;
});

const estadoRegistroVisible = computed(() => {
    if (props.registro.est_ral === 'cerrado') {
        return 'Enviado a revisión';
    }

    return 'Borrador';
});

const estadoValidacionVisible = computed(() => {
    if (!validacionActual.value) {
        return props.registro.est_ral === 'cerrado'
            ? 'Pendiente de revisión'
            : 'No enviado';
    }

    const etiquetas = {
        aceptado: 'Validado',
        observado: 'Observado',
        rechazado: 'Rechazado',
    };

    return etiquetas[validacionActual.value.est_vin] || validacionActual.value.est_vin;
});

const claseValidacion = computed(() => {
    if (!validacionActual.value) {
        return props.registro.est_ral === 'cerrado'
            ? 'border-amber-200 bg-amber-50 text-amber-800'
            : 'border-slate-200 bg-slate-50 text-slate-700';
    }

    if (validacionActual.value.est_vin === 'aceptado') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    }

    if (validacionActual.value.est_vin === 'observado') {
        return 'border-amber-200 bg-amber-50 text-amber-800';
    }

    return 'border-rose-200 bg-rose-50 text-rose-800';
});

const puedeEditarRegistro = computed(() => {
    return props.registro.est_ral === 'activo' && !validacionActual.value;
});

const mensajeHistorialNoEditable = computed(() => {
    if (validacionActual.value) {
        return 'Este registro ya fue revisado por el nutricionista y se conserva como historial.';
    }

    if (props.registro.est_ral === 'cerrado') {
        return 'Este registro ya fue enviado a revisión y no puede modificarse.';
    }

    return null;
});
</script>

<template>
    <Head title="Resumen nutricional del día" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Resumen nutricional del día</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ registro.paciente?.usuario?.nombre_completo }} — {{ registro.fec_ral }}
                    </p>
                </div>
                <Link
                    :href="route('paciente.registros-alimenticios.historial', registro.paciente?.cod_pacientes)"
                    class="rounded bg-gray-600 px-4 py-2 text-sm text-white hover:bg-gray-700"
                >
                    Volver al historial
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

                    <div
                        v-if="$page.props.flash?.success"
                        class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                    >
                        {{ $page.props.flash.success }}
                    </div>

                <!-- Aviso sin datos -->
                <div
                    v-if="resumen.total_alimentos === 0"
                    class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-700"
                >
                    <span v-if="puedeEditarRegistro">
                        Este registro no tiene alimentos cargados todavía. Puedes agregarlos desde
                        <Link
                            :href="route('paciente.registros-alimenticios.create', { paciente: registro.paciente?.cod_pacientes, fecha: registro.fec_ral })"
                            class="font-semibold underline"
                        >
                            Editar este día
                        </Link>.
                    </span>

                    <span v-else>
                        Este registro no tiene alimentos cargados y ya no puede modificarse.
                    </span>
                </div>

                <!-- Revisión nutricional -->
                <section
                    class="rounded-xl border px-5 py-4 text-sm shadow-sm"
                    :class="claseValidacion"
                >
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]">
                                Revisión nutricional
                            </p>

                            <h3 class="mt-1 text-lg font-bold">
                                {{ estadoValidacionVisible }}
                            </h3>

                            <p class="mt-1">
                                Estado del registro:
                                <strong>{{ estadoRegistroVisible }}</strong>
                            </p>
                        </div>

                        <div
                            v-if="validacionActual"
                            class="rounded-lg bg-white/70 px-4 py-3"
                        >
                            <p>
                                <strong>Fecha:</strong>
                                {{ validacionActual.fec_vin }}
                            </p>

                            <p>
                                <strong>Nutricionista:</strong>
                                {{ validacionActual.nutricionista?.usuario?.nombre_completo || 'No disponible' }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="validacionActual?.obs_vin"
                        class="mt-4 rounded-lg bg-white/80 p-4"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.14em]">
                            Observación del nutricionista
                        </p>

                        <p class="mt-2 whitespace-pre-line leading-6">
                            {{ validacionActual.obs_vin }}
                        </p>
                    </div>

                    <p
                        v-else-if="!validacionActual && registro.est_ral === 'cerrado'"
                        class="mt-3"
                    >
                        Tu registro ya fue enviado. El nutricionista lo revisará próximamente.
                    </p>

                    <p
                        v-else-if="registro.est_ral === 'activo'"
                        class="mt-3"
                    >
                        Este registro está como borrador. Envíalo a revisión cuando termines de registrar tus alimentos del día.
                    </p>
                </section>

                <!-- Tarjetas de estadísticas -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        :class="[colorClasses[stat.color].wrap, 'rounded-xl border p-4 text-center']"
                    >
                        <p :class="[colorClasses[stat.color].label, 'text-xs font-semibold uppercase tracking-wide']">
                            {{ stat.label }}
                        </p>
                        <p :class="[colorClasses[stat.color].valor, 'mt-2 text-2xl font-bold']">
                            {{ stat.valor }}
                        </p>
                        <p :class="[colorClasses[stat.color].unidad, 'text-xs']">{{ stat.unidad }}</p>
                    </div>
                </div>

                <!-- Aviso alimentos sin composición -->
                <div
                    v-if="sinDatos > 0"
                    class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-700"
                >
                    {{ sinDatos }} alimento{{ sinDatos > 1 ? 's' : '' }} sin composición nutricional disponible — no se incluyen en los totales anteriores.
                </div>

                <!-- Contenido principal -->
                <div class="grid gap-6 lg:grid-cols-[280px_1fr]">

                    <!-- Panel info -->
                    <div class="flex flex-col gap-4">
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <h3 class="mb-3 font-semibold text-gray-800">Datos del registro</h3>
                            <dl class="space-y-2 text-sm text-gray-600">
                                <div class="flex justify-between gap-2">
                                    <dt class="font-medium text-gray-700">Fecha</dt>
                                    <dd>{{ registro.fec_ral }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="font-medium text-gray-700">Estado</dt>
                                    <dd>
                                        <span
                                            :class="registro.est_ral === 'cerrado'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-slate-100 text-slate-600'"
                                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                        >
                                            {{ estadoRegistroVisible }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="font-medium text-gray-700">Alimentos</dt>
                                    <dd>{{ resumen.total_alimentos }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="font-medium text-gray-700">Con datos nutricionales</dt>
                                    <dd>{{ resumen.con_datos }}</dd>
                                </div>
                            </dl>
                        </div>

                        <Link
                            v-if="puedeEditarRegistro"
                            :href="route('paciente.registros-alimenticios.create', { paciente: registro.paciente?.cod_pacientes, fecha: registro.fec_ral })"
                            class="rounded-lg bg-orange-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-orange-700"
                        >
                            Continuar borrador
                        </Link>

                        <div
                            v-else-if="mensajeHistorialNoEditable"
                            class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
                        >
                            {{ mensajeHistorialNoEditable }}
                        </div>
                    </div>

                    <!-- Lista de alimentos -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold text-gray-800">Alimentos registrados</h3>

                        <div v-if="registro.detalles.length === 0" class="text-sm text-gray-400">
                            No hay alimentos para este día.
                        </div>

                        <div v-else class="space-y-6">
                            <div v-for="grupo in detallesPorComida" :key="grupo.tipo">
                                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    {{ grupo.label }}
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        v-for="detalle in grupo.items"
                                        :key="detalle.cod_detalle_registro_alimenticio"
                                        class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-4 py-3"
                                    >
                                        <div class="min-w-0 text-sm">
                                            <p class="truncate font-medium text-gray-800">
                                                {{ detalle.alimento?.nom_ali || 'Sin nombre' }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ detalle.can_dra }} {{ detalle.uni_dra }} · {{ detalle.hor_dra }}
                                            </p>
                                        </div>
                                        <div v-if="detalle.aporte" class="ml-4 shrink-0 text-right text-xs">
                                            <p class="font-semibold text-orange-600">{{ fmt(detalle.aporte.cal) }} kcal</p>
                                            <p class="text-gray-500">
                                                P {{ fmt(detalle.aporte.pro) }}g &nbsp;
                                                G {{ fmt(detalle.aporte.gra) }}g &nbsp;
                                                C {{ fmt(detalle.aporte.car) }}g
                                            </p>
                                        </div>
                                        <div v-else class="ml-4 shrink-0 text-xs text-gray-400">
                                            sin datos
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

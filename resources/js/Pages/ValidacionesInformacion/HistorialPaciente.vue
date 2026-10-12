<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    paciente: Object,
    historial: {
        type: Array,
        default: () => [],
    },
});

const totalValidaciones = computed(() => props.historial.length);

const totalAceptadas = computed(() => {
    return props.historial.filter((item) => item.est_vin === 'aceptado').length;
});

const totalObservadas = computed(() => {
    return props.historial.filter((item) => item.est_vin === 'observado').length;
});

const totalRechazadas = computed(() => {
    return props.historial.filter((item) => item.est_vin === 'rechazado').length;
});

const filtroEstado = ref('todos');

const historialFiltrado = computed(() => {
    if (filtroEstado.value === 'todos') {
        return props.historial;
    }

    return props.historial.filter((item) => item.est_vin === filtroEstado.value);
});

const limpiarFiltroEstado = () => {
    filtroEstado.value = 'todos';
};

const etiquetaEstado = (estado) => {
    const etiquetas = {
        aceptado: 'Validado',
        observado: 'Observado',
        rechazado: 'Rechazado',
    };

    return etiquetas[estado] || estado || 'Sin estado';
};

const claseEstado = (estado) => {
    if (estado === 'aceptado') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (estado === 'observado') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    if (estado === 'rechazado') {
        return 'bg-rose-100 text-rose-700 ring-rose-200';
    }

    return 'bg-slate-100 text-slate-700 ring-slate-200';
};

const estadoRegistroVisible = (estado) => {
    if (estado === 'cerrado') {
        return 'Enviado a revisión';
    }

    if (estado === 'activo') {
        return 'Borrador';
    }

    return estado || '-';
};

const puedeAbrirRegistro = (item) => {
    return Boolean(item.registro_alimenticio?.cod_registros_alimenticios);
};
</script>

<template>
    <Head title="Historial de validaciones" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Validaciones nutricionales"
                title="Historial de revisiones del paciente"
                :description="`${paciente.usuario?.nombre_completo || 'Paciente'} · ${paciente.usuario?.cor_usu || 'Sin correo registrado'}`"
            >
                <template #actions>
                    <Link
                        :href="route('nutricionista.seguimientos.create', paciente.cod_pacientes)"
                        class="motion-btn rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                    >
                        Nuevo seguimiento
                    </Link>

                    <Link
                        :href="route('nutricionista.seguimientos.consolidado', paciente.cod_pacientes)"
                        class="motion-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Consolidado
                    </Link>

                    <Link
                        :href="route('nutricionista.validaciones-informacion.pendientes')"
                        class="motion-btn rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Volver a pendientes
                    </Link>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-4 md:grid-cols-4">
                    <ClinicalStatCard
                        label="Total"
                        :value="totalValidaciones"
                        helper="validaciones registradas"
                        tone="neutral"
                    />

                    <ClinicalStatCard
                        label="Validados"
                        :value="totalAceptadas"
                        helper="sin observación clínica"
                        tone="success"
                    />

                    <ClinicalStatCard
                        label="Observados"
                        :value="totalObservadas"
                        helper="con retroalimentación"
                        tone="warning"
                    />

                    <ClinicalStatCard
                        label="Rechazados"
                        :value="totalRechazadas"
                        helper="no confiables"
                        tone="danger"
                    />
                </section>

                <ClinicalSection
                    eyebrow="Filtro de revisiones"
                    title="Buscar por estado de validación"
                    description="Filtra el historial para revisar registros validados, observados o rechazados."
                    tone="neutral"
                >
                    <template #actions>
                        <button
                            type="button"
                            class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                            @click="limpiarFiltroEstado"
                        >
                            Limpiar filtro
                        </button>
                    </template>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-semibold ring-1"
                            :class="filtroEstado === 'todos'
                                ? 'bg-slate-900 text-white ring-slate-900'
                                : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50'"
                            @click="filtroEstado = 'todos'"
                        >
                            Todos
                        </button>

                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-semibold ring-1"
                            :class="filtroEstado === 'aceptado'
                                ? 'bg-emerald-600 text-white ring-emerald-600'
                                : 'bg-white text-emerald-700 ring-emerald-200 hover:bg-emerald-50'"
                            @click="filtroEstado = 'aceptado'"
                        >
                            Validados
                        </button>

                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-semibold ring-1"
                            :class="filtroEstado === 'observado'
                                ? 'bg-amber-600 text-white ring-amber-600'
                                : 'bg-white text-amber-700 ring-amber-200 hover:bg-amber-50'"
                            @click="filtroEstado = 'observado'"
                        >
                            Observados
                        </button>

                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-semibold ring-1"
                            :class="filtroEstado === 'rechazado'
                                ? 'bg-rose-600 text-white ring-rose-600'
                                : 'bg-white text-rose-700 ring-rose-200 hover:bg-rose-50'"
                            @click="filtroEstado = 'rechazado'"
                        >
                            Rechazados
                        </button>
                    </div>
                </ClinicalSection>

                <ClinicalSection
                    eyebrow="Registros revisados"
                    title="Historial clínico de validaciones"
                    description="Cada tarjeta representa una revisión profesional realizada sobre un registro alimenticio enviado por el paciente."
                    tone="neutral"
                >
                    <template #actions>
                        <ClinicalBadge
                            :label="`${historialFiltrado.length} mostrado${historialFiltrado.length === 1 ? '' : 's'}`"
                            tone="info"
                        />
                    </template>

                    <ClinicalAlert
                        v-if="historial.length === 0"
                        title="Sin validaciones"
                        tone="info"
                    >
                        No existen validaciones para este paciente.
                    </ClinicalAlert>

                    <ClinicalAlert
                        v-else-if="historialFiltrado.length === 0"
                        title="Sin resultados"
                        tone="warning"
                    >
                        No hay validaciones con el estado seleccionado.
                    </ClinicalAlert>

                    <div
                        v-else
                        class="space-y-4"
                    >
                        <article
                            v-for="item in historialFiltrado"
                            :key="item.cod_validaciones_informacion"
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <ClinicalBadge
                                            :label="etiquetaEstado(item.est_vin)"
                                            :tone="item.est_vin === 'aceptado'
                                                ? 'success'
                                                : item.est_vin === 'observado'
                                                    ? 'warning'
                                                    : 'danger'"
                                        />

                                        <ClinicalBadge
                                            :label="`Registro #${item.registro_alimenticio?.cod_registros_alimenticios || '-'}`"
                                            tone="neutral"
                                        />

                                        <span class="text-xs text-slate-400">
                                            Validado el {{ item.fec_vin || '-' }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-3 text-sm text-slate-700 md:grid-cols-3">
                                        <p>
                                            <strong>Fecha registro:</strong>
                                            {{ item.registro_alimenticio?.fec_ral || '-' }}
                                        </p>

                                        <p>
                                            <strong>Estado registro:</strong>
                                            {{ estadoRegistroVisible(item.registro_alimenticio?.est_ral) }}
                                        </p>

                                        <p>
                                            <strong>Resultado:</strong>
                                            {{ etiquetaEstado(item.est_vin) }}
                                        </p>
                                    </div>

                                    <ClinicalAlert
                                        v-if="item.obs_vin"
                                        class="mt-4"
                                        title="Retroalimentación clínica"
                                        :tone="item.est_vin === 'rechazado' ? 'danger' : 'warning'"
                                    >
                                        <p class="whitespace-pre-line">
                                            {{ item.obs_vin }}
                                        </p>
                                    </ClinicalAlert>

                                    <ClinicalAlert
                                        v-else
                                        class="mt-4"
                                        title="Sin observación"
                                        tone="success"
                                    >
                                        Validación registrada sin observación adicional.
                                    </ClinicalAlert>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <Link
                                        v-if="puedeAbrirRegistro(item)"
                                        :href="route('nutricionista.validaciones-informacion.validar', item.registro_alimenticio.cod_registros_alimenticios)"
                                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                    >
                                        Ver registro
                                    </Link>

                                    <Link
                                        :href="route('nutricionista.seguimientos.create', paciente.cod_pacientes)"
                                        class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                                    >
                                        Seguimiento
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>
                </ClinicalSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

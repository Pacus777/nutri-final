<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    paciente: Object,
    observaciones: {
        type: Array,
        default: () => [],
    },
});

const totalObservaciones = computed(() => props.observaciones.length);

const totalObservadas = computed(() => {
    return props.observaciones.filter((item) => item.est_vin === 'observado').length;
});

const totalRechazadas = computed(() => {
    return props.observaciones.filter((item) => item.est_vin === 'rechazado').length;
});

const etiquetaEstado = (estado) => {
    const etiquetas = {
        observado: 'Observado',
        rechazado: 'Rechazado',
    };

    return etiquetas[estado] || estado || 'Sin estado';
};

const claseEstado = (estado) => {
    if (estado === 'observado') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    if (estado === 'rechazado') {
        return 'bg-rose-100 text-rose-700 ring-rose-200';
    }

    return 'bg-slate-100 text-slate-700 ring-slate-200';
};

const puedeAbrirRegistro = (item) => {
    return Boolean(item.registro_alimenticio?.cod_registros_alimenticios);
};
</script>

<template>
    <Head title="Observaciones del nutricionista" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Observaciones nutricionales"
                title="Retroalimentación clínica del paciente"
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
                        :href="route('nutricionista.seguimientos.historial', paciente.cod_pacientes)"
                        class="motion-btn rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Historial seg.
                    </Link>

                    <Link
                        :href="route('nutricionista.validaciones-informacion.historial-paciente', paciente.cod_pacientes)"
                        class="motion-btn rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Historial completo
                    </Link>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-4 md:grid-cols-3">
                    <ClinicalStatCard
                        label="Total observaciones"
                        :value="totalObservaciones"
                        helper="retroalimentaciones"
                        tone="neutral"
                    />

                    <ClinicalStatCard
                        label="Observados"
                        :value="totalObservadas"
                        helper="requieren atención"
                        tone="warning"
                    />

                    <ClinicalStatCard
                        label="Rechazados"
                        :value="totalRechazadas"
                        helper="registros no confiables"
                        tone="danger"
                    />
                </section>

                <ClinicalSection
                    eyebrow="Retroalimentación registrada"
                    title="Observaciones del nutricionista"
                    description="Aquí se muestran validaciones observadas o rechazadas con comentario profesional."
                    tone="warning"
                >
                    <template #actions>
                        <ClinicalBadge
                            :label="`${observaciones.length} observación${observaciones.length === 1 ? '' : 'es'}`"
                            tone="warning"
                        />
                    </template>

                    <ClinicalAlert
                        v-if="observaciones.length === 0"
                        title="Sin observaciones"
                        tone="info"
                    >
                        No hay observaciones registradas para este paciente.
                    </ClinicalAlert>

                    <div
                        v-else
                        class="space-y-4"
                    >
                        <article
                            v-for="item in observaciones"
                            :key="item.cod_validaciones_informacion"
                            class="rounded-2xl border p-5 shadow-sm"
                            :class="item.est_vin === 'rechazado'
                                ? 'border-rose-200 bg-rose-50'
                                : 'border-amber-200 bg-amber-50/70'"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <ClinicalBadge
                                            :label="etiquetaEstado(item.est_vin)"
                                            :tone="item.est_vin === 'rechazado' ? 'danger' : 'warning'"
                                        />

                                        <ClinicalBadge
                                            :label="`Registro #${item.registro_alimenticio?.cod_registros_alimenticios || '-'}`"
                                            tone="neutral"
                                        />

                                        <span class="text-xs text-slate-500">
                                            Validado el {{ item.fec_vin || '-' }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-3 text-sm text-slate-700 md:grid-cols-2">
                                        <p>
                                            <strong>Fecha registro:</strong>
                                            {{ item.registro_alimenticio?.fec_ral || '-' }}
                                        </p>

                                        <p>
                                            <strong>Resultado:</strong>
                                            {{ etiquetaEstado(item.est_vin) }}
                                        </p>
                                    </div>

                                    <div class="mt-4 rounded-2xl bg-white p-4 text-sm leading-6 text-slate-800 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                            Observación profesional
                                        </p>

                                        <p class="mt-2 whitespace-pre-line">
                                            {{ item.obs_vin }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <Link
                                        v-if="puedeAbrirRegistro(item)"
                                        :href="route('nutricionista.validaciones-informacion.validar', item.registro_alimenticio.cod_registros_alimenticios)"
                                        class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                                    >
                                        Revisar registro
                                    </Link>

                                    <Link
                                        :href="route('nutricionista.seguimientos.create', paciente.cod_pacientes)"
                                        class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                                    >
                                        Crear seguimiento
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

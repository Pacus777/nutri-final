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
    seguimientos: Array,
});

const filtro = ref('');

const seguimientosFiltrados = computed(() => {
    const texto = filtro.value.trim().toLowerCase();

    if (texto === '') return props.seguimientos;

    return props.seguimientos.filter((item) => {
        return String(item.fec_seg ?? '').toLowerCase().includes(texto)
            || String(item.res_seg ?? '').toLowerCase().includes(texto)
            || String(item.cam_seg ?? '').toLowerCase().includes(texto);
    });
});

const totalSeguimientos = computed(() => props.seguimientos.length);

const totalCambiosSignificativos = computed(() => {
    return props.seguimientos.filter((item) => item.tiene_cambio_significativo).length;
});

const totalSinCambios = computed(() => {
    return props.seguimientos.filter((item) => !item.tiene_cambio_significativo).length;
});

const ultimoSeguimiento = computed(() => {
    return props.seguimientos?.[0] || null;
});

const resumenFiltro = computed(() => {
    return `Mostrando ${seguimientosFiltrados.value.length} de ${props.seguimientos.length} seguimientos`;
});

const limpiarFiltro = () => {
    filtro.value = '';
};

const textoCorto = (texto, limite = 220) => {
    if (!texto) {
        return '';
    }

    return texto.length > limite
        ? `${texto.slice(0, limite)}...`
        : texto;
};

const claseCambio = (item) => {
    return item.tiene_cambio_significativo
        ? 'border-amber-200 bg-amber-50'
        : 'border-slate-200 bg-white';
};
</script>

<template>
    <Head title="Historial de seguimientos" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Seguimiento nutricional"
                title="Historial de seguimientos"
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
                        :href="route('nutricionista.seguimientos.comparativo', paciente.cod_pacientes)"
                        class="motion-btn rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Comparativo
                    </Link>

                    <Link
                        :href="route('nutricionista.seguimientos.consolidado', paciente.cod_pacientes)"
                        class="motion-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Consolidado
                    </Link>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-6 px-4 sm:px-0">
                    <section class="grid gap-4 md:grid-cols-4">
                        <ClinicalStatCard
                            label="Total seguimientos"
                            :value="totalSeguimientos"
                            helper="controles registrados"
                            tone="neutral"
                        />

                        <ClinicalStatCard
                            label="Cambios significativos"
                            :value="totalCambiosSignificativos"
                            helper="con variación relevante"
                            tone="warning"
                        />

                        <ClinicalStatCard
                            label="Sin cambios"
                            :value="totalSinCambios"
                            helper="evolución estable"
                            tone="success"
                        />

                        <ClinicalStatCard
                            label="Último seguimiento"
                            :value="ultimoSeguimiento?.fec_seg || '-'"
                            helper="fecha más reciente"
                            tone="info"
                        />
                    </section>

                    <ClinicalSection
                        eyebrow="Búsqueda clínica"
                        title="Filtrar seguimientos"
                        description="Busca por fecha, resultado del seguimiento o cambio significativo registrado."
                        tone="neutral"
                    >
                        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                            <div class="flex-1">
                                <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                    Buscar en historial
                                </label>

                                <input
                                    v-model="filtro"
                                    type="text"
                                    placeholder="Fecha, resultado o cambio significativo"
                                    class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                >
                            </div>

                            <button
                                type="button"
                                class="w-fit rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                                @click="limpiarFiltro"
                            >
                                Limpiar
                            </button>
                        </div>

                        <p class="mt-3 text-xs text-slate-500">
                            {{ resumenFiltro }}
                        </p>
                    </ClinicalSection>

                    <ClinicalAlert
                        v-if="seguimientos.length === 0"
                        title="Sin seguimientos registrados"
                        tone="info"
                    >
                        Aún no hay seguimientos nutricionales para este paciente. Registra el primer seguimiento para documentar su evolución.
                    </ClinicalAlert>

                    <ClinicalAlert
                        v-else-if="seguimientosFiltrados.length === 0"
                        title="Sin resultados"
                        tone="warning"
                    >
                        No hay seguimientos que coincidan con el filtro aplicado.
                    </ClinicalAlert>

                    <ClinicalSection
                        v-else
                        eyebrow="Evolución del paciente"
                        title="Línea clínica de seguimientos"
                        description="Cada tarjeta resume un control nutricional registrado para este paciente."
                        tone="neutral"
                    >
                        <div class="space-y-4">
                            <article
                                v-for="item in seguimientosFiltrados"
                                :key="item.cod_seguimientos_nutricionales"
                                class="rounded-2xl border p-5 shadow-sm"
                                :class="claseCambio(item)"
                            >
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <ClinicalBadge
                                                :label="item.fec_seg"
                                                tone="info"
                                            />

                                            <ClinicalBadge
                                                v-if="item.tiene_cambio_significativo"
                                                label="Cambio significativo"
                                                tone="warning"
                                            />

                                            <ClinicalBadge
                                                v-else
                                                label="Sin cambio significativo"
                                                tone="neutral"
                                            />
                                        </div>

                                        <div class="mt-4 rounded-xl bg-white/80 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                Resultado del seguimiento
                                            </p>

                                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                                {{ textoCorto(item.res_seg) }}
                                            </p>
                                        </div>

                                        <div
                                            v-if="item.tiene_cambio_significativo"
                                            class="mt-4 rounded-xl border border-amber-200 bg-white p-4"
                                        >
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">
                                                Cambio significativo registrado
                                            </p>

                                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-amber-900">
                                                {{ textoCorto(item.cam_seg) }}
                                            </p>
                                        </div>

                                        <p
                                            v-else
                                            class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500"
                                        >
                                            No se registró un cambio significativo en este seguimiento.
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </ClinicalSection>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

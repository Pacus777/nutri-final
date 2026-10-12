<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    snapshots: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filtros: {
        type: Object,
        default: () => ({ estado: null }),
    },
    estadisticas: {
        type: Object,
        default: () => ({ total_snapshots: 0, evaluados: 0, pendientes: 0 }),
    },
});

const filtroEstado = ref(props.filtros.estado || '');

const aplicarFiltro = (estado) => {
    filtroEstado.value = estado;
    router.get(route('nutricionista.etiquetado-riesgo.index'), {
        estado: estado || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const badgeColorPorNivel = (nivel, estado) => {
    if (estado === 'insuficiente') return 'warning';
    if (nivel === 'bajo') return 'success';
    if (nivel === 'moderado') return 'warning';
    if (nivel === 'alto') return 'danger';
    return 'neutral';
};
</script>

<template>
    <Head title="Etiquetado Profesional de Riesgo - ML" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <ClinicalPageHeader
                title="Captura de Etiquetas Profesionales de Riesgo"
                subtitle="Valoración clínica independiente de snapshots reales para el dataset supervisado de Machine Learning (Fase 2D.1)."
            >
                <template #actions>
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Aislamiento de Sesgo Activo
                    </div>
                </template>
            </ClinicalPageHeader>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <ClinicalStatCard
                    title="Snapshots Clínicos Reales"
                    :value="estadisticas.total_snapshots"
                    subtitle="Ventanas de 7 días disponibles"
                />
                <ClinicalStatCard
                    title="Mis Evaluaciones"
                    :value="estadisticas.evaluados"
                    subtitle="Casos etiquetados por usted"
                />
                <ClinicalStatCard
                    title="Pendientes por Valorar"
                    :value="estadisticas.pendientes"
                    subtitle="Esperando juicio clínico"
                />
            </div>

            <!-- Filtros de pestañas -->
            <ClinicalSection title="Listado de Ventanas Clínicas Disponibles">
                <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-100 pb-4">
                    <button
                        @click="aplicarFiltro('')"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                            !filtroEstado
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Todos ({{ estadisticas.total_snapshots }})
                    </button>
                    <button
                        @click="aplicarFiltro('pendiente')"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                            filtroEstado === 'pendiente'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Pendientes ({{ estadisticas.pendientes }})
                    </button>
                    <button
                        @click="aplicarFiltro('evaluado')"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                            filtroEstado === 'evaluado'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Evaluados por mí ({{ estadisticas.evaluados }})
                    </button>
                </div>

                <!-- Tabla de snapshots -->
                <div v-if="snapshots.data.length === 0" class="text-center py-12 text-gray-500 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-base font-semibold text-gray-800">No se encontraron snapshots clínicos</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Utilice el comando <code class="bg-gray-200 px-1.5 py-0.5 rounded text-xs font-mono text-gray-800">php artisan ml:generate-clinical-snapshots</code> para generar ventanas clínicas reales.
                    </p>
                </div>

                <div v-else class="overflow-x-auto rounded-xl border border-gray-100 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600 font-semibold text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5 text-left">Paciente</th>
                                <th class="px-5 py-3.5 text-left">Período / Ventana</th>
                                <th class="px-5 py-3.5 text-center">Días Registrados</th>
                                <th class="px-5 py-3.5 text-center">Adherencia</th>
                                <th class="px-5 py-3.5 text-right">Calorías Prom.</th>
                                <th class="px-5 py-3.5 text-center">Mi Valoración</th>
                                <th class="px-5 py-3.5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="snap in snapshots.data" :key="snap.cod_features_nutricionales" class="hover:bg-gray-50/75 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-gray-900">{{ snap.paciente.nombre_completo }}</div>
                                    <div class="text-xs text-gray-500">C.I: {{ snap.paciente.cedula || 'No registrada' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-gray-800 font-medium">{{ snap.periodo.inicio }} - {{ snap.periodo.fin }}</div>
                                    <div class="text-xs text-gray-500">{{ snap.periodo.ventana_dias }} días continuos</div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                        {{ snap.dias_registrados }} / {{ snap.periodo.ventana_dias }} días
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="text-xs font-semibold" :class="snap.adherencia_pct >= 70 ? 'text-emerald-700' : 'text-amber-700'">
                                        {{ snap.adherencia_pct }}%
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-gray-900">
                                    {{ snap.promedio_calorias ? `${Math.round(snap.promedio_calorias)} kcal` : 'N/D' }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span v-if="snap.mi_evaluacion" class="inline-flex items-center gap-1.5">
                                        <ClinicalBadge :tone="badgeColorPorNivel(snap.mi_evaluacion.nivel_riesgo, snap.mi_evaluacion.estado)">
                                            {{ snap.mi_evaluacion.estado === 'insuficiente' ? 'Información Insuficiente' : (snap.mi_evaluacion.nivel_riesgo ? snap.mi_evaluacion.nivel_riesgo.toUpperCase() : 'N/D') }}
                                        </ClinicalBadge>
                                    </span>
                                    <span v-else class="text-xs text-gray-400 italic">
                                        Pendiente
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Link
                                        :href="route('nutricionista.etiquetado-riesgo.show', snap.cod_features_nutricionales)"
                                        :class="[
                                            'inline-flex items-center px-3.5 py-1.5 text-xs font-medium rounded-lg shadow-sm transition-colors',
                                            snap.mi_evaluacion
                                                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                : 'bg-emerald-600 text-white hover:bg-emerald-700'
                                        ]"
                                    >
                                        {{ snap.mi_evaluacion ? 'Revisar / Modificar' : 'Evaluar Caso' }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </ClinicalSection>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    registrosPendientes: {
        type: Array,
        default: () => [],
    },
    filtroFecha: {
        type: String,
        default: null,
    },
});

const filtros = reactive({
    fecha: props.filtroFecha || '',
    paciente: '',
});

const filtrar = () => {
    router.get(route('nutricionista.validaciones-informacion.pendientes'), {
        fecha: filtros.fecha || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const limpiar = () => {
    filtros.fecha = '';
    filtros.paciente = '';

    router.get(route('nutricionista.validaciones-informacion.pendientes'), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const registrosFiltrados = computed(() => {
    const busqueda = filtros.paciente.trim().toLowerCase();

    if (!busqueda) {
        return props.registrosPendientes;
    }

    return props.registrosPendientes.filter((registro) => {
        const nombre = registro.paciente?.usuario?.nombre_completo || '';
        const correo = registro.paciente?.usuario?.cor_usu || '';

        return `${nombre} ${correo}`.toLowerCase().includes(busqueda);
    });
});

const totalPendientes = computed(() => props.registrosPendientes.length);

const totalFiltrados = computed(() => registrosFiltrados.value.length);

const totalItems = computed(() => {
    return registrosFiltrados.value.reduce((total, registro) => {
        return total + Number(registro.detalles_count || 0);
    }, 0);
});

const registrosConPocosItems = computed(() => {
    return registrosFiltrados.value.filter((registro) => {
        return Number(registro.detalles_count || 0) <= 2;
    }).length;
});

const registrosOrdenados = computed(() => {
    return [...registrosFiltrados.value].sort((a, b) => {
        const fechaA = String(a.fec_ral || '');
        const fechaB = String(b.fec_ral || '');

        if (fechaA !== fechaB) {
            return fechaA < fechaB ? 1 : -1;
        }

        return Number(b.cod_registros_alimenticios || 0) - Number(a.cod_registros_alimenticios || 0);
    });
});

const inicialesPaciente = (registro) => {
    const nombre = registro.paciente?.usuario?.nombre_completo || 'Paciente';

    return nombre
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte[0])
        .join('')
        .toUpperCase();
};

const nombrePaciente = (registro) => {
    return registro.paciente?.usuario?.nombre_completo || 'Paciente sin nombre';
};

const correoPaciente = (registro) => {
    return registro.paciente?.usuario?.cor_usu || 'Sin correo registrado';
};
</script>

<template>
    <Head title="Registros enviados a revisión" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Validación nutricional"
                title="Registros enviados a revisión"
                description="Bandeja de trabajo para revisar registros alimenticios cerrados por pacientes y emitir validación profesional."
            >
                <template #actions>
                    <ClinicalBadge
                        :label="`${totalPendientes} pendiente${totalPendientes === 1 ? '' : 's'}`"
                        :tone="totalPendientes > 0 ? 'warning' : 'success'"
                    />
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-4 md:grid-cols-4">
                    <ClinicalStatCard
                        label="Pendientes"
                        :value="totalPendientes"
                        helper="registros enviados"
                        :tone="totalPendientes > 0 ? 'warning' : 'success'"
                    />

                    <ClinicalStatCard
                        label="Mostrados"
                        :value="totalFiltrados"
                        helper="según filtros"
                        tone="info"
                    />

                    <ClinicalStatCard
                        label="Alimentos"
                        :value="totalItems"
                        helper="ítems registrados"
                        tone="success"
                    />

                    <ClinicalStatCard
                        label="Requieren atención"
                        :value="registrosConPocosItems"
                        helper="con pocos alimentos"
                        :tone="registrosConPocosItems > 0 ? 'warning' : 'neutral'"
                    />
                </section>

                <ClinicalSection
                    eyebrow="Filtros de revisión"
                    title="Buscar registros pendientes"
                    description="Filtra por fecha o busca un paciente por nombre o correo dentro de los registros cargados."
                    tone="neutral"
                >
                    <template #actions>
                        <button
                            type="button"
                            class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                            @click="limpiar"
                        >
                            Limpiar filtros
                        </button>
                    </template>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Fecha
                            </label>

                            <input
                                v-model="filtros.fecha"
                                type="date"
                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Paciente
                            </label>

                            <input
                                v-model="filtros.paciente"
                                type="text"
                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Buscar por nombre o correo"
                            >
                        </div>

                        <div class="flex items-end">
                            <button
                                type="button"
                                class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                @click="filtrar"
                            >
                                Filtrar por fecha
                            </button>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-slate-500">
                        La búsqueda por paciente se aplica en pantalla. El filtro de fecha consulta nuevamente los registros.
                    </p>
                </ClinicalSection>

                <ClinicalAlert
                    v-if="registrosConPocosItems > 0"
                    title="Registros que requieren atención"
                    tone="warning"
                >
                    Hay {{ registrosConPocosItems }} registro{{ registrosConPocosItems === 1 ? '' : 's' }}
                    con pocos alimentos registrados. Revisa si corresponden a un día parcial o si el paciente omitió comidas.
                </ClinicalAlert>

                <ClinicalSection
                    eyebrow="Bandeja de trabajo"
                    title="Registros por revisar"
                    description="Abre cada registro para revisar alimentos, resumen nutricional, alertas automáticas y observaciones clínicas."
                    tone="neutral"
                >
                    <template #actions>
                        <ClinicalBadge
                            :label="`${registrosOrdenados.length} mostrado${registrosOrdenados.length === 1 ? '' : 's'}`"
                            tone="info"
                        />
                    </template>

                    <ClinicalAlert
                        v-if="registrosOrdenados.length === 0"
                        title="Sin registros pendientes"
                        tone="success"
                    >
                        No hay registros enviados a revisión con los filtros actuales.
                    </ClinicalAlert>

                    <div
                        v-else
                        class="grid gap-4 lg:grid-cols-2"
                    >
                        <article
                            v-for="registro in registrosOrdenados"
                            :key="registro.cod_registros_alimenticios"
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-black text-emerald-700">
                                        {{ inicialesPaciente(registro) }}
                                    </div>

                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="font-bold text-slate-900">
                                                {{ nombrePaciente(registro) }}
                                            </h4>

                                            <ClinicalBadge
                                                label="En revisión"
                                                tone="info"
                                            />
                                        </div>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ correoPaciente(registro) }}
                                        </p>

                                        <div class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                                            <p>
                                                <strong>Registro:</strong>
                                                #{{ registro.cod_registros_alimenticios }}
                                            </p>

                                            <p>
                                                <strong>Fecha:</strong>
                                                {{ registro.fec_ral }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <ClinicalBadge
                                    :label="`${registro.detalles_count} alimento${registro.detalles_count === 1 ? '' : 's'}`"
                                    :tone="Number(registro.detalles_count || 0) <= 2 ? 'warning' : 'success'"
                                />
                            </div>

                            <ClinicalAlert
                                v-if="Number(registro.detalles_count || 0) <= 2"
                                class="mt-4"
                                title="Registro posiblemente parcial"
                                tone="warning"
                            >
                                Este registro tiene pocos alimentos. Conviene revisar si representa un día completo o si faltan comidas.
                            </ClinicalAlert>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <Link
                                    :href="route('nutricionista.validaciones-informacion.validar', registro.cod_registros_alimenticios)"
                                    class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                >
                                    Validar registro
                                </Link>

                                <Link
                                    :href="route('nutricionista.validaciones-informacion.historial-paciente', registro.paciente?.cod_pacientes)"
                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    Historial
                                </Link>

                                <Link
                                    :href="route('nutricionista.validaciones-informacion.observaciones-paciente', registro.paciente?.cod_pacientes)"
                                    class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                                >
                                    Observaciones
                                </Link>

                                <Link
                                    :href="route('nutricionista.seguimientos.create', registro.paciente?.cod_pacientes)"
                                    class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                                >
                                    Seguimiento
                                </Link>
                            </div>
                        </article>
                    </div>
                </ClinicalSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

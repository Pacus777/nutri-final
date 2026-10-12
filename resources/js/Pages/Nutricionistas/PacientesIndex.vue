<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    nutricionista: Object,
    pacientes: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
});

const page = usePage();

const pacientesPagina = computed(() => props.pacientes?.data ?? []);

const filtro = ref('');
const filtroEstado = ref('todos');

const nombreCompleto = (usuario) => {
    if (!usuario) {
        return 'No disponible';
    }

    return usuario.nombre_completo
        || (usuario.nom_usu && usuario.ape_usu
            ? `${usuario.nom_usu} ${usuario.ape_usu}`
            : usuario.nom_usu || 'No disponible');
};

const correoUsuario = (usuario) => {
    return usuario?.cor_usu || 'Sin correo registrado';
};

const inicialesPaciente = (paciente) => {
    return nombreCompleto(paciente.usuario)
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte[0])
        .join('')
        .toUpperCase();
};

const pacientesFiltrados = computed(() => {
    const texto = filtro.value.trim().toLowerCase();

    return pacientesPagina.value.filter((paciente) => {
        const nombre = nombreCompleto(paciente.usuario).toLowerCase();
        const correo = correoUsuario(paciente.usuario).toLowerCase();
        const sexo = String(paciente.sex_pac || '').toLowerCase();
        const estado = String(paciente.est_pac || '').toLowerCase();

        const coincideTexto = texto === ''
            ? true
            : `${nombre} ${correo} ${sexo} ${estado}`.includes(texto);

        const coincideEstado = filtroEstado.value === 'todos'
            ? true
            : paciente.est_pac === filtroEstado.value;

        return coincideTexto && coincideEstado;
    });
});

const totalPacientes = computed(() => props.pacientes?.total ?? 0);

const totalActivos = computed(() => {
    return props.pacientes?.total ?? 0;
});

const totalInactivos = computed(() => {
    return 0;
});

const totalMostrados = computed(() => pacientesFiltrados.value.length);

const limpiarFiltros = () => {
    filtro.value = '';
    filtroEstado.value = 'todos';
};

const estadoTone = (estado) => {
    return estado === 'activo' ? 'success' : 'neutral';
};

const sexoVisible = (sexo) => {
    const mapa = {
        M: 'Masculino',
        F: 'Femenino',
        masculino: 'Masculino',
        femenino: 'Femenino',
    };

    return mapa[sexo] || sexo || 'No registrado';
};
</script>

<template>
    <Head title="Pacientes asignados" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Pacientes asignados"
                title="Cartera activa del nutricionista"
                :description="`${nombreCompleto(nutricionista.usuario)} · ${nutricionista.esp_nut || 'Sin especialidad registrada'}`"
            >
                <template #actions>
                    <Link
                        :href="route('dashboard')"
                        class="motion-btn rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Volver al dashboard
                    </Link>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <ClinicalAlert
                    v-if="page.props.flash?.success"
                    title="Operación realizada"
                    tone="success"
                >
                    {{ page.props.flash.success }}
                </ClinicalAlert>

                <section class="grid gap-4 md:grid-cols-4">
                    <ClinicalStatCard
                        label="Total pacientes"
                        :value="totalPacientes"
                        helper="asignados"
                        tone="neutral"
                    />

                    <ClinicalStatCard
                        label="Activos"
                        :value="totalActivos"
                        helper="en seguimiento"
                        tone="success"
                    />

                    <ClinicalStatCard
                        label="No activos"
                        :value="totalInactivos"
                        helper="históricos o inactivos"
                        :tone="totalInactivos > 0 ? 'warning' : 'neutral'"
                    />

                    <ClinicalStatCard
                        label="Mostrados"
                        :value="totalMostrados"
                        helper="según filtros"
                        tone="info"
                    />
                </section>

                <ClinicalSection
                    eyebrow="Búsqueda de pacientes"
                    title="Filtrar cartera asignada"
                    description="Busca por nombre, correo, sexo o estado del paciente."
                    tone="neutral"
                >
                    <template #actions>
                        <button
                            type="button"
                            class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                            @click="limpiarFiltros"
                        >
                            Limpiar filtros
                        </button>
                    </template>

                    <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                Buscar paciente
                            </label>

                            <input
                                v-model="filtro"
                                type="text"
                                placeholder="Nombre, correo, sexo o estado"
                                class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                Estado
                            </label>

                            <select
                                v-model="filtroEstado"
                                class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="todos">Todos</option>
                                <option value="activo">Activos</option>
                                <option value="inactivo">Inactivos</option>
                            </select>
                        </div>
                    </div>
                </ClinicalSection>

                <ClinicalSection
                    eyebrow="Pacientes"
                    title="Pacientes asignados"
                    description="Abre la ficha clínica para revisar evaluación inicial, contextos, objetivos, registros y seguimientos."
                    tone="info"
                >
                    <template #actions>
                        <ClinicalBadge
                            :label="`${pacientesFiltrados.length} paciente${pacientesFiltrados.length === 1 ? '' : 's'}`"
                            tone="info"
                        />
                    </template>

                    <ClinicalAlert
                        v-if="totalPacientes === 0"
                        title="Sin pacientes asignados"
                        tone="info"
                    >
                        No tienes pacientes activos asignados.
                    </ClinicalAlert>

                    <ClinicalAlert
                        v-else-if="pacientesFiltrados.length === 0"
                        title="Sin resultados"
                        tone="warning"
                    >
                        No hay pacientes que coincidan con los filtros aplicados.
                    </ClinicalAlert>

                    <div
                        v-else
                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <article
                            v-for="paciente in pacientesFiltrados"
                            :key="paciente.cod_pacientes"
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-cyan-200 hover:shadow-md"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-100 text-sm font-black text-cyan-700">
                                    {{ inicialesPaciente(paciente) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-slate-900">
                                            {{ nombreCompleto(paciente.usuario) }}
                                        </h3>

                                        <ClinicalBadge
                                            :label="paciente.est_pac || 'sin estado'"
                                            :tone="estadoTone(paciente.est_pac)"
                                        />

                                        <ClinicalBadge
                                            v-if="paciente.adherencia"
                                            :label="paciente.adherencia.label"
                                            :tone="paciente.adherencia.color"
                                        />
                                    </div>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ correoUsuario(paciente.usuario) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-2 text-sm text-slate-700">
                                <p>
                                    <strong>Sexo:</strong>
                                    {{ sexoVisible(paciente.sex_pac) }}
                                </p>

                                <p>
                                    <strong>Código:</strong>
                                    #{{ paciente.cod_pacientes }}
                                </p>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <Link
                                    :href="route('nutricionista.pacientes.show', paciente.cod_pacientes)"
                                    class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                                >
                                    Ver ficha
                                </Link>

                                <Link
                                    :href="route('nutricionista.seguimientos.create', paciente.cod_pacientes)"
                                    class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                >
                                    Seguimiento
                                </Link>

                                <Link
                                    :href="route('nutricionista.validaciones-informacion.historial-paciente', paciente.cod_pacientes)"
                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    Historial
                                </Link>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="(pacientes.links || []).length > 3"
                        class="mt-6 flex flex-wrap gap-2"
                    >
                        <Link
                            v-for="link in pacientes.links"
                            :key="link.label"
                            :href="link.url || ''"
                            :class="[
                                'rounded-xl border px-3 py-2 text-sm font-semibold transition',
                                link.active
                                    ? 'border-cyan-600 bg-cyan-600 text-white'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-cyan-200 hover:bg-cyan-50',
                                !link.url ? 'pointer-events-none cursor-default opacity-50' : '',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </ClinicalSection>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

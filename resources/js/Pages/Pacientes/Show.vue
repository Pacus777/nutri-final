<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    paciente: Object,
});

const page = usePage();
const esRolNutricionista = computed(() => (page.props.auth?.rol_activo ?? '') === 'Nutricionista');

const nombreCompleto = (usuario) => {
    if (!usuario) {
        return 'No disponible';
    }

    if (usuario.nombre_completo) {
        return usuario.nombre_completo;
    }

    const nombre = `${usuario.nom_usu ?? ''} ${usuario.ape_usu ?? ''}`.trim();
    return nombre || 'No disponible';
};

const estadoClase = (estado) => {
    if (estado === 'activo') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (estado === 'inactivo') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    return 'bg-rose-100 text-rose-700 ring-rose-200';
};

const estaturaCm = (estaturaMetros) => {
    const metros = Number(estaturaMetros);

    if (!Number.isFinite(metros) || metros <= 0) {
        return 'No disponible';
    }

    return `${Math.round(metros * 100)} cm`;
};

const pesoChartCanvas = ref(null);
let pesoChart = null;

const renderPesoChart = () => {
    if (!pesoChartCanvas.value || !props.paciente?.historial_peso || props.paciente.historial_peso.length === 0) return;

    const ctx = pesoChartCanvas.value.getContext('2d');
    const labels = props.paciente.historial_peso.map(d => d.fecha);
    const pesos = props.paciente.historial_peso.map(d => d.peso);
    const imcs = props.paciente.historial_peso.map(d => d.imc);

    if (pesoChart) {
        pesoChart.destroy();
    }

    pesoChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Peso (kg)',
                    data: pesos,
                    borderColor: '#10b981', // emerald-500
                    backgroundColor: 'rgba(16, 185, 129, 0.03)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'yPeso',
                },
                {
                    label: 'IMC',
                    data: imcs,
                    borderColor: '#6366f1', // indigo-500
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderDash: [5, 5],
                    yAxisID: 'yImc',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { family: 'Inter, sans-serif', size: 12, weight: '600' },
                        color: '#475569',
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 12,
                    cornerRadius: 12,
                    titleFont: { family: 'Inter, sans-serif', size: 12, weight: 'bold' },
                    bodyFont: { family: 'Inter, sans-serif', size: 12 }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                },
                yPeso: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Peso (kg)',
                        color: '#10b981',
                        font: { family: 'Inter, sans-serif', size: 11, weight: 'bold' }
                    },
                    grid: { color: '#f1f5f9' },
                    ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                },
                yImc: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'IMC',
                        color: '#6366f1',
                        font: { family: 'Inter, sans-serif', size: 11, weight: 'bold' }
                    },
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#64748b', font: { family: 'Inter, sans-serif', size: 11 } }
                }
            }
        }
    });
};

onMounted(() => {
    nextTick(() => {
        renderPesoChart();
    });
});

onUnmounted(() => {
    if (pesoChart) {
        pesoChart.destroy();
    }
});

</script>

<template>
    <Head title="Ficha del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Ficha del Paciente
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Vista integral del perfil clinico y accesos rapidos por modulo.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('pacientes.edit', paciente.cod_pacientes)"
                        class="motion-btn rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Editar ficha
                    </Link>
                    <Link
                        :href="route('pacientes.index')"
                        class="motion-btn rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="auth-panel auth-glow auth-fade-up motion-lift relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_14%_20%,rgba(34,197,94,0.14),transparent_24%),radial-gradient(circle_at_85%_82%,rgba(255,255,255,0.06),transparent_24%)]"></div>

                    <div class="relative z-10 grid gap-5 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-green-400">
                                Panel clinico
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                {{ nombreCompleto(paciente.usuario) }}
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Utiliza los slices de accion para navegar por evaluacion, contexto, objetivos, plan y seguimiento alimenticio.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Resumen rapido</p>
                            <div class="mt-3 space-y-2 text-sm text-slate-300">
                                <p>Codigo: <span class="font-semibold text-white">{{ paciente.cod_pacientes }}</span></p>
                                <p>Sexo: <span class="font-semibold capitalize text-white">{{ paciente.sex_pac || 'No definido' }}</span></p>
                                <p>Estado perfil: <span class="font-semibold capitalize text-white">{{ paciente.est_pac }}</span></p>
                                <p>Estado usuario: <span class="font-semibold capitalize text-white">{{ paciente.usuario?.est_usu ?? 'N/D' }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <article class="motion-lift rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-600">Evaluacion inicial</p>
                        <p class="mt-2 text-sm text-slate-600">
                            <span v-if="paciente.evaluacion_inicial_activa">
                                Registrada el {{ paciente.evaluacion_inicial_activa.fec_evi }}
                            </span>
                            <span v-else>
                                Aun no registrada.
                            </span>
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-if="paciente.evaluacion_inicial_activa"
                                :href="route('evaluaciones-iniciales.show', paciente.evaluacion_inicial_activa.cod_evaluaciones_iniciales)"
                                class="motion-btn rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                            >
                                Ver evaluacion
                            </Link>
                            <Link
                                v-else
                                :href="route('evaluaciones-iniciales.create', { paciente: paciente.cod_pacientes })"
                                class="motion-btn rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                            >
                                Registrar evaluacion
                            </Link>
                        </div>
                    </article>

                    <article class="motion-lift rounded-2xl border border-amber-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-600">Contexto del paciente</p>
                        <p class="mt-2 text-sm text-slate-600">
                            Registra nuevos contextos y consulta el historial para seguimiento clinico.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                :href="route('contextos-paciente.create', { paciente: paciente.cod_pacientes })"
                                class="motion-btn rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600"
                            >
                                Nuevo contexto
                            </Link>
                            <Link
                                :href="route('contextos-paciente.historial', paciente.cod_pacientes)"
                                class="motion-btn rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                            >
                                Ver historial
                            </Link>
                        </div>
                    </article>

                    <article class="motion-lift rounded-2xl border border-violet-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-violet-600">Objetivos nutricionales</p>
                        <p class="mt-2 text-sm text-slate-600">
                            Administra objetivos activos y crea nuevos objetivos de control.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                :href="route('objetivos-nutricionales.paciente', paciente.cod_pacientes)"
                                class="motion-btn rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700"
                            >
                                Ver objetivos
                            </Link>
                            <Link
                                :href="route('objetivos-nutricionales.create', { paciente: paciente.cod_pacientes })"
                                class="motion-btn rounded-lg bg-fuchsia-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-fuchsia-700"
                            >
                                Nuevo objetivo
                            </Link>
                        </div>
                    </article>

                    <article class="motion-lift rounded-2xl border border-cyan-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-700">Plan nutricional</p>
                        <p class="mt-2 text-sm text-slate-600">
                            <span v-if="paciente.plan_nutricional_vigente">
                                Existe plan vigente desde {{ paciente.plan_nutricional_vigente.fec_pan }}.
                            </span>
                            <span v-else>
                                Aun no existe plan vigente para este paciente.
                            </span>
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-if="paciente.plan_nutricional_vigente"
                                :href="route('planes-nutricionales.vigente', paciente.cod_pacientes)"
                                class="motion-btn rounded-lg bg-cyan-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-800"
                            >
                                Ver plan vigente
                            </Link>
                            <Link
                                v-else
                                :href="route('planes-nutricionales.create', { paciente: paciente.cod_pacientes })"
                                class="motion-btn rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700"
                            >
                                Registrar plan
                            </Link>
                        </div>
                    </article>

                    <article class="motion-lift rounded-2xl border border-orange-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-600">Registro alimenticio</p>
                        <p class="mt-2 text-sm text-slate-600">
                            Carga de consumos y revision historica para acompanamiento nutricional.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                :href="route('paciente.registros-alimenticios.create', { paciente: paciente.cod_pacientes })"
                                class="motion-btn rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700"
                            >
                                Nuevo registro
                            </Link>
                            <Link
                                :href="route('paciente.registros-alimenticios.historial', paciente.cod_pacientes)"
                                class="motion-btn rounded-lg bg-orange-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-900"
                            >
                                Ver historial
                            </Link>
                        </div>
                    </article>

                    <article v-if="esRolNutricionista" class="motion-lift rounded-2xl border border-cyan-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-700">Seguimiento nutricional</p>
                        <p class="mt-2 text-sm text-slate-600">
                            Acceso directo al modulo 11 para registrar controles, comparar fechas y ver consolidado.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                :href="route('nutricionista.seguimientos.create', paciente.cod_pacientes)"
                                class="motion-btn rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700"
                            >
                                Nuevo seguimiento
                            </Link>
                            <Link
                                :href="route('nutricionista.seguimientos.historial', paciente.cod_pacientes)"
                                class="motion-btn rounded-lg bg-cyan-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-900"
                            >
                                Historial
                            </Link>
                            <Link
                                :href="route('nutricionista.seguimientos.consolidado', paciente.cod_pacientes)"
                                class="motion-btn rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                            >
                                Consolidado
                            </Link>
                        </div>
                    </article>

                    <article class="motion-lift rounded-2xl border border-indigo-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Gestion de ficha</p>
                        <p class="mt-2 text-sm text-slate-600">
                            Accesos de mantenimiento rapido para editar datos y volver al panel.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                :href="route('pacientes.edit', paciente.cod_pacientes)"
                                class="motion-btn rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                            >
                                Editar ficha
                            </Link>
                            <Link
                                :href="route('pacientes.index')"
                                class="motion-btn rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                            >
                                Volver al listado
                            </Link>
                        </div>
                    </article>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">Datos clinicos</h4>
                        </div>
                        <div class="space-y-3 p-5 text-sm text-slate-700">
                            <p><span class="font-semibold text-slate-900">Codigo:</span> {{ paciente.cod_pacientes }}</p>
                            <p><span class="font-semibold text-slate-900">Sexo:</span> <span class="capitalize">{{ paciente.sex_pac || 'No especificado' }}</span></p>
                            <p><span class="font-semibold text-slate-900">Peso:</span> {{ paciente.pes_pac }} kg</p>
                            <p><span class="font-semibold text-slate-900">Estatura:</span> {{ estaturaCm(paciente.estatura_pac) }}</p>
                            <p><span class="font-semibold text-slate-900">Tipo de sangre:</span> {{ paciente.tip_san_pac || 'No especificado' }}</p>
                            <p><span class="font-semibold text-slate-900">Alergias:</span> {{ paciente.ale_pac || 'No registradas' }}</p>
                            <p>
                                <span class="font-semibold text-slate-900">Estado del perfil:</span>
                                <span class="ml-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1" :class="estadoClase(paciente.est_pac)">
                                    {{ paciente.est_pac }}
                                </span>
                            </p>
                            <p><span class="font-semibold text-slate-900">Fecha de registro:</span> {{ paciente.fecha_registro || 'No disponible' }}</p>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">Usuario vinculado</h4>
                        </div>
                        <div class="p-5">
                            <div v-if="paciente.usuario?.fot_usu" class="mb-4">
                                <img
                                    :src="`/storage/${paciente.usuario.fot_usu}`"
                                    alt="Foto de paciente"
                                    class="h-28 w-28 rounded-lg border border-slate-200 object-cover"
                                >
                            </div>

                            <div class="space-y-3 text-sm text-slate-700">
                                <p><span class="font-semibold text-slate-900">Nombre completo:</span> {{ nombreCompleto(paciente.usuario) }}</p>
                                <p>
                                    <span class="font-semibold text-slate-900">CI:</span>
                                    <span v-if="paciente.usuario?.ci_usu">
                                        {{ paciente.usuario.ci_usu }} {{ paciente.usuario.ext_ci_usu }}
                                    </span>
                                    <span v-else>No disponible</span>
                                </p>
                                <p><span class="font-semibold text-slate-900">Fecha de nacimiento:</span> {{ paciente.usuario?.fec_nac_usu ?? 'No disponible' }}</p>
                                <p><span class="font-semibold text-slate-900">Correo:</span> {{ paciente.usuario?.cor_usu ?? 'No disponible' }}</p>
                                <p>
                                    <span class="font-semibold text-slate-900">Estado de usuario:</span>
                                    <span class="ml-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1" :class="estadoClase(paciente.usuario?.est_usu)">
                                        {{ paciente.usuario?.est_usu ?? 'N/D' }}
                                    </span>
                                </p>

                                <div>
                                    <p class="font-semibold text-slate-900">Roles</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span
                                            v-for="rol in paciente.usuario?.roles ?? []"
                                            :key="rol.cod_roles"
                                            class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                        >
                                            {{ rol.nom_rol }}
                                        </span>
                                        <span
                                            v-if="!(paciente.usuario?.roles?.length)"
                                            class="text-xs text-slate-500"
                                        >
                                            Sin roles registrados
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Gráfica de Evolución de Peso e IMC -->
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Evolución Clínica</span>
                            <h3 class="text-base font-black text-slate-900 mt-1">Historial de Peso e IMC</h3>
                            <p class="text-xs text-slate-500 font-semibold mt-0.5">Evolución de peso e Índice de Masa Corporal del paciente.</p>
                        </div>
                        <span class="text-[11px] font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full">
                            {{ paciente.historial_peso?.length || 0 }} mediciones
                        </span>
                    </div>

                    <!-- Canvas o Estado Vacío -->
                    <div v-if="paciente.historial_peso && paciente.historial_peso.length > 0" class="relative min-h-[300px] w-full">
                        <canvas ref="pesoChartCanvas"></canvas>
                    </div>
                    <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-10 px-5 text-center">
                        <p class="text-sm font-bold text-slate-500">Aún no hay historial de peso registrado.</p>
                        <p class="text-xs text-slate-400 mt-1">Cuando se completen evaluaciones o seguimientos, verás aquí su gráfico comparativo.</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
                        <div class="border-b border-emerald-100 px-5 py-4">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-700">Nutricionista actual</h4>
                        </div>
                        <div class="space-y-3 p-5 text-sm text-emerald-900">
                            <template v-if="paciente.nutricionista_actual">
                                <p><span class="font-semibold text-emerald-950">Profesional:</span> {{ nombreCompleto(paciente.nutricionista_actual.nutricionista?.usuario) }}</p>
                                <p><span class="font-semibold text-emerald-950">Especialidad:</span> {{ paciente.nutricionista_actual.nutricionista?.esp_nut || 'No especificada' }}</p>
                                <p><span class="font-semibold text-emerald-950">Asignado desde:</span> {{ paciente.nutricionista_actual.fec_anp }}</p>
                            </template>
                            <p v-else>Este paciente aún no tiene nutricionista activo asignado.</p>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-green-200 bg-white shadow-sm">
                        <div class="border-b border-green-100 px-5 py-4">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-green-700">Historial de asignaciones</h4>
                        </div>
                        <div class="space-y-3 p-5 text-sm text-green-900">
                            <div v-if="paciente.historial_asignaciones?.length" class="space-y-3">
                                <div
                                    v-for="asignacion in paciente.historial_asignaciones"
                                    :key="asignacion.cod_asignacion_nutricionista_paciente"
                                    class="rounded-xl border border-green-200 bg-green-50 p-3"
                                >
                                    <p><span class="font-semibold text-green-950">Fecha:</span> {{ asignacion.fec_anp }}</p>
                                    <p><span class="font-semibold text-green-950">Estado:</span> {{ asignacion.est_anp }}</p>
                                    <p><span class="font-semibold text-green-950">Nutricionista:</span> {{ nombreCompleto(asignacion.nutricionista?.usuario) }}</p>
                                </div>
                            </div>
                            <p v-else>No hay historial de asignaciones registrado.</p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

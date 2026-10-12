<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';
import { computed, ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';

const props = defineProps({
    estadisticas: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

const usuario = computed(() => page.props.auth?.user ?? null);
const rolActivo = computed(() => page.props.auth?.rol_activo ?? null);
const estadisticas = computed(() => props.estadisticas ?? {});

const coloresEdad = ['#8b5cf6', '#14b8a6', '#f97316', '#ef4444', '#0ea5e9', '#64748b'];

const rangosEdad = computed(() => {
    // Prefer 18-50 cohort ranges when available
    const source = estadisticas.value.rangos_edad_pacientes_18_50 ?? estadisticas.value.rangos_edad_pacientes ?? {};
    return Object.entries(source).map(([rango, cantidad], index) => ({
        rango,
        cantidad: Number(cantidad ?? 0),
        color: coloresEdad[index % coloresEdad.length],
    }));
});

const sexoPacientes = computed(() => {
    const source = estadisticas.value.sexo_pacientes_18_50 ?? estadisticas.value.sexo_pacientes ?? {};
    return Object.entries(source).map(([sexo, cantidad], index) => ({
        sexo,
        cantidad: Number(cantidad ?? 0),
        color: coloresEdad[(index + 1) % coloresEdad.length],
    }));
});

const totalEdad = computed(() => rangosEdad.value.reduce((acumulado, item) => acumulado + item.cantidad, 0));

// --- CHART.JS CONFIGURATION ---
const edadChartCanvas = ref(null);
const sexoChartCanvas = ref(null);
const rolesChartCanvas = ref(null);

let edadChart = null;
let sexoChart = null;
let rolesChart = null;

const totalPacientes = computed(() => Number(estadisticas.value.pacientes_totales ?? 0));
const totalNutricionistas = computed(() => Number(estadisticas.value.nutricionistas_totales ?? 0));
const totalUsuarios = computed(() => Number(estadisticas.value.usuarios_totales ?? 0));
const totalAdmins = computed(() => Math.max(0, totalUsuarios.value - (totalPacientes.value + totalNutricionistas.value)));

const hayDatosEdad = computed(() => totalEdad.value > 0);
const hayDatosSexo = computed(() => sexoPacientes.value.reduce((total, item) => total + item.cantidad, 0) > 0);
const hayDatosRoles = computed(() => totalUsuarios.value > 0);

const renderCharts = async () => {
    await nextTick();

    // Reset charts
    if (edadChart) { edadChart.destroy(); edadChart = null; }
    if (sexoChart) { sexoChart.destroy(); sexoChart = null; }
    if (rolesChart) { rolesChart.destroy(); rolesChart = null; }

    // Chart 1: Rango de edad (Doughnut)
    if (edadChartCanvas.value && hayDatosEdad.value) {
        edadChart = new Chart(edadChartCanvas.value, {
            type: 'doughnut',
            data: {
                labels: rangosEdad.value.map(item => item.rango === 'sin_dato' ? 'Sin fecha' : `Edad ${item.rango}`),
                datasets: [{
                    data: rangosEdad.value.map(item => item.cantidad),
                    backgroundColor: rangosEdad.value.map(item => item.color),
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 12,
                            font: { size: 11, family: 'Outfit, sans-serif' }
                        }
                    },
                }
            }
        });
    }

    // Chart 2: Sexo de Pacientes (Barra Horizontal)
    if (sexoChartCanvas.value && hayDatosSexo.value) {
        sexoChart = new Chart(sexoChartCanvas.value, {
            type: 'bar',
            data: {
                labels: sexoPacientes.value.map(item => item.sexo),
                datasets: [{
                    data: sexoPacientes.value.map(item => item.cantidad),
                    backgroundColor: sexoPacientes.value.map(item => item.color),
                    borderRadius: 8,
                    borderSkipped: false,
                    barThickness: 24,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { precision: 0, font: { family: 'Outfit, sans-serif' } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold' } }
                    }
                }
            }
        });
    }

    // Chart 3: Distribución de Cuentas por Rol (Polar Area o Pie)
    if (rolesChartCanvas.value && hayDatosRoles.value) {
        rolesChart = new Chart(rolesChartCanvas.value, {
            type: 'pie',
            data: {
                labels: ['Pacientes', 'Nutricionistas', 'Administradores'],
                datasets: [{
                    data: [totalPacientes.value, totalNutricionistas.value, totalAdmins.value],
                    backgroundColor: ['#10b981', '#8b5cf6', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 12,
                            font: { size: 11, family: 'Outfit, sans-serif' }
                        }
                    }
                }
            }
        });
    }
};

onMounted(() => {
    renderCharts();
});

watch([estadisticas], () => {
    renderCharts();
}, { deep: true });

onBeforeUnmount(() => {
    if (edadChart) edadChart.destroy();
    if (sexoChart) sexoChart.destroy();
    if (rolesChart) rolesChart.destroy();
});

const mensajeRol = computed(() => {
    switch (rolActivo.value) {
        case 'SuperAdmin':
            return 'Tienes acceso técnico y administrativo de alto nivel.';
        case 'Administrador':
            return 'Estás gestionando la operación administrativa del sistema.';
        case 'Nutricionista':
            return 'Estás operando como profesional de atención nutricional.';
        case 'Paciente':
            return 'Estás accediendo a tu espacio personal dentro del sistema.';
        default:
            return 'No hay un rol activo definido.';
    }
});

const esSuperAdmin = computed(() => rolActivo.value === 'SuperAdmin');
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard administrativo
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Vista operativa para gestión interna del sistema nutricional.
                </p>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="auth-panel auth-glow auth-fade-up motion-lift relative overflow-hidden rounded-[1.75rem] p-6 text-white sm:p-8">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(34,197,94,0.12),transparent_24%),radial-gradient(circle_at_85%_85%,rgba(255,255,255,0.05),transparent_26%)]"></div>

                    <div class="relative z-10 grid gap-6 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.26em] text-green-400">
                                Centro de control
                            </p>

                            <h3 class="mt-3 text-3xl font-black uppercase tracking-tight text-white sm:text-4xl">
                                Bienvenido {{ usuario?.nom_usu }}
                            </h3>

                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                {{ mensajeRol }}
                            </p>

                            <div class="mt-6 flex flex-wrap gap-2">
                                <span class="rounded-full bg-green-500/15 px-3 py-1 text-xs font-semibold text-green-300 ring-1 ring-green-500/25">
                                    Rol: {{ rolActivo ?? 'Sin rol activo' }}
                                </span>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                    :class="esSuperAdmin
                                        ? 'bg-amber-500/15 text-amber-300 ring-amber-500/25'
                                        : 'bg-blue-500/15 text-blue-300 ring-blue-500/25'"
                                >
                                    {{ esSuperAdmin ? 'Acceso técnico total' : 'Gestión operativa' }}
                                </span>
                            </div>
                        </div>

                        <div class="motion-card-enter motion-card-enter-delay-1 rounded-3xl border border-white/10 bg-white/5 p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Cuenta activa</p>
                            <p class="mt-3 text-sm font-semibold text-white">{{ usuario?.nom_usu }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ usuario?.cor_usu }}</p>

                            <div class="mt-5 space-y-2 text-xs text-slate-300">
                                <p>Monitorea módulos y accesos desde un único punto.</p>
                                <p>Utiliza el menú superior para navegar por entidades.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nuevos pacientes (30 días)</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ estadisticas.nuevos_pacientes_30dias ?? 0 }}</p>
                            <p class="mt-1 text-sm text-slate-600">Registro de usuarios pacientes en los últimos 30 días</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Pacientes 18-50 (activos)</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ estadisticas.pacientes_18_50_activos ?? 0 }}</p>
                            <p class="mt-1 text-sm text-slate-600">Pacientes dentro del rango 18-50 y con estado activo</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <div class="motion-lift motion-card-enter motion-card-enter-delay-1 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Módulo principal</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">Usuarios</p>
                        <p class="mt-1 text-sm text-slate-600">Gestión de altas, actualización y estado.</p>
                        <Link
                            :href="route('admin.usuarios.index')"
                            class="motion-btn mt-4 inline-flex rounded bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900"
                        >
                            Ir a usuarios
                        </Link>
                    </div>

                    <div class="motion-lift motion-card-enter motion-card-enter-delay-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Módulo operativo</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">Pacientes</p>
                        <p class="mt-1 text-sm text-slate-600">Vista de perfiles y seguimiento clínico.</p>
                        <Link
                            :href="route('pacientes.index')"
                            class="motion-btn mt-4 inline-flex rounded bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900"
                        >
                            Ir a pacientes
                        </Link>
                    </div>

                    <div class="motion-lift motion-card-enter motion-card-enter-delay-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Módulo de coordinación</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">Asignaciones</p>
                        <p class="mt-1 text-sm text-slate-600">Relación nutricionista-paciente y control de estado.</p>
                        <Link
                            :href="route('asignaciones.index')"
                            class="motion-btn mt-4 inline-flex rounded bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900"
                        >
                            Ir a asignaciones
                        </Link>
                    </div>

                    <div class="motion-lift motion-card-enter motion-card-enter-delay-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                        <p class="text-sm text-amber-600 font-medium">Ficha clínica</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">Contextos de pacientes</p>
                        <p class="mt-1 text-sm text-slate-600">Antecedentes, hábitos y restricciones por paciente.</p>
                        <Link
                            :href="route('contextos-paciente.index')"
                            class="motion-btn mt-4 inline-flex rounded bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700"
                        >
                            Ver contextos
                        </Link>
                    </div>
                </div>

                <div class="motion-card-enter motion-card-enter-delay-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Estado del sistema</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        La plataforma cuenta con autenticación personalizada, gestión de estado por usuario,
                        control por rol activo y navegación segmentada por perfil.
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Estadísticas poblacionales</p>
                            <h3 class="mt-2 text-2xl font-bold text-slate-900">Usuarios y pacientes por edad</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Resumen operativo de usuarios, pacientes y distribución etaria para seguimiento administrativo.
                            </p>
                        </div>

                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            Edad promedio (18-50): {{ estadisticas.edad_promedio_pacientes_18_50 ?? estadisticas.edad_promedio_pacientes ?? 'N/D' }} años
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl bg-slate-950 p-5 text-white">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Usuarios totales</p>
                            <p class="mt-3 text-3xl font-black">{{ estadisticas.usuarios_totales ?? 0 }}</p>
                            <p class="mt-2 text-sm text-slate-300">Activos: {{ estadisticas.usuarios_activos ?? 0 }} · Inactivos: {{ estadisticas.usuarios_inactivos ?? 0 }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Pacientes</p>
                            <p class="mt-3 text-3xl font-black text-slate-900">{{ estadisticas.pacientes_totales ?? 0 }}</p>
                            <p class="mt-2 text-sm text-slate-600">Activos: {{ estadisticas.pacientes_activos ?? 0 }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nutricionistas</p>
                            <p class="mt-3 text-3xl font-black text-slate-900">{{ estadisticas.nutricionistas_totales ?? 0 }}</p>
                            <p class="mt-2 text-sm text-slate-600">Activos: {{ estadisticas.nutricionistas_activos ?? 0 }}</p>
                        </div>

                        <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-violet-600">Distribución</p>
                            <p class="mt-3 text-3xl font-black text-violet-950">100%</p>
                            <p class="mt-2 text-sm text-violet-800">Pacientes agrupados por rango etario y sexo</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-3">
                        <!-- Chart 1: Edad -->
                        <div class="rounded-2xl border border-slate-200 p-5 bg-white shadow-sm flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-base font-bold text-slate-900">Distribución por edad</h4>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded">18-50 años</span>
                            </div>
                            <div class="mt-5 h-64 relative">
                                <canvas ref="edadChartCanvas"></canvas>
                                <div v-if="!hayDatosEdad" class="absolute inset-0 flex items-center justify-center bg-slate-50 border border-dashed rounded-xl text-sm text-slate-500">
                                    Sin datos de edad
                                </div>
                            </div>
                        </div>

                        <!-- Chart 2: Sexo -->
                        <div class="rounded-2xl border border-slate-200 p-5 bg-white shadow-sm flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-base font-bold text-slate-900">Pacientes por sexo</h4>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Género</span>
                            </div>
                            <div class="mt-5 h-64 relative">
                                <canvas ref="sexoChartCanvas"></canvas>
                                <div v-if="!hayDatosSexo" class="absolute inset-0 flex items-center justify-center bg-slate-50 border border-dashed rounded-xl text-sm text-slate-500">
                                    Sin datos de género
                                </div>
                            </div>
                        </div>

                        <!-- Chart 3: Roles -->
                        <div class="rounded-2xl border border-slate-200 p-5 bg-white shadow-sm flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-base font-bold text-slate-900">Cuentas por rol</h4>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Global</span>
                            </div>
                            <div class="mt-5 h-64 relative">
                                <canvas ref="rolesChartCanvas"></canvas>
                                <div v-if="!hayDatosRoles" class="absolute inset-0 flex items-center justify-center bg-slate-50 border border-dashed rounded-xl text-sm text-slate-500">
                                    Sin datos de usuarios
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
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
    resumen: {
        type: Object,
        default: () => ({
            total_seguimientos: 0,
            cambios_significativos: 0,
            ultimo_seguimiento: null,
            total_historial: 0,
        }),
    },
    historial: {
        type: Array,
        default: () => [],
    },
});

const totalSeguimientos = computed(() => Number(props.resumen?.total_seguimientos || 0));
const cambiosSignificativos = computed(() => Number(props.resumen?.cambios_significativos || 0));
const totalHistorial = computed(() => Number(props.resumen?.total_historial || 0));
const ultimoSeguimiento = computed(() => props.resumen?.ultimo_seguimiento || '-');

const tieneHistorial = computed(() => props.historial.length > 0);

const porcentajeCambios = computed(() => {
    if (totalSeguimientos.value === 0) {
        return 0;
    }

    return Math.round((cambiosSignificativos.value / totalSeguimientos.value) * 100);
});

const estadoEvolucion = computed(() => {
    if (totalSeguimientos.value === 0) {
        return {
            label: 'Sin seguimientos',
            tone: 'neutral',
            mensaje: 'Aún no existen seguimientos suficientes para interpretar la evolución del paciente.',
        };
    }

    if (cambiosSignificativos.value > 0) {
        return {
            label: 'Con cambios relevantes',
            tone: 'warning',
            mensaje: 'Existen cambios significativos documentados. Revisa el historial antes de definir nuevas decisiones clínicas.',
        };
    }

    return {
        label: 'Evolución estable',
        tone: 'success',
        mensaje: 'No se registran cambios significativos en los seguimientos disponibles.',
    };
});

const dialColorClass = computed(() => {
    const pct = porcentajeCambios.value;
    if (pct > 50) return 'text-rose-500';
    if (pct > 0) return 'text-amber-500';
    return 'text-emerald-500';
});

const dialBgColorClass = computed(() => {
    const pct = porcentajeCambios.value;
    if (pct > 50) return 'bg-rose-50/50 border-rose-100 text-rose-900';
    if (pct > 0) return 'bg-amber-50/50 border-amber-100 text-amber-900';
    return 'bg-emerald-50/50 border-emerald-100 text-emerald-900';
});

const entradasExpandidas = ref({});
const toggleExpandir = (index) => {
    entradasExpandidas.value[index] = !entradasExpandidas.value[index];
};

const textoCorto = (texto, limite = 160) => {
    if (!texto) {
        return 'Sin descripción registrada.';
    }

    return texto.length > limite
        ? `${texto.slice(0, limite)}...`
        : texto;
};
</script>

<template>
    <Head title="Consolidado histórico del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Seguimiento nutricional"
                title="Consolidado histórico del paciente"
                description="Vista unificada para interpretar rápidamente la evolución y los cambios clínicos acumulados del paciente."
            >
                <template #actions>
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur transition-all duration-300 hover:shadow-md">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 px-1">
                            Acciones de Consulta
                        </p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link
                                :href="route('nutricionista.seguimientos.create', paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-cyan-600 px-4 py-2 text-xs font-bold text-white hover:bg-cyan-700 transition"
                            >
                                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Nuevo seguimiento
                            </Link>

                            <Link
                                :href="route('nutricionista.seguimientos.historial', paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200/80 hover:bg-slate-100 hover:text-slate-900 transition"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Ver historial
                            </Link>

                            <Link
                                :href="route('nutricionista.seguimientos.comparativo', paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200/60 hover:bg-emerald-100 transition"
                            >
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Ver comparativo
                            </Link>
                        </div>
                    </div>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                
                <!-- Tarjeta Resumen del Paciente Principal (Consistente con Create.vue) -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm motion-card-enter">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-cyan-500 to-emerald-400 text-white font-black text-lg shadow-sm">
                                {{ paciente.usuario?.nombre_completo ? paciente.usuario.nombre_completo.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'P' }}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                                    {{ paciente.usuario?.nombre_completo || 'Paciente no disponible' }}
                                </h2>
                                <p class="text-sm text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                    {{ paciente.usuario?.cor_usu || 'Correo no disponible' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-slate-500 font-semibold bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">
                                Diagnóstico
                            </span>
                            <ClinicalBadge
                                :label="estadoEvolucion.label"
                                :tone="estadoEvolucion.tone"
                                class="shadow-sm border animate-pulse"
                            />
                        </div>
                    </div>
                </div>

                <!-- Contenido Principal: Dos Columnas -->
                <div class="grid gap-6 xl:grid-cols-[5fr_7fr] items-start">
                    
                    <!-- COLUMNA IZQUIERDA: Dashboard Sticky de Métricas -->
                    <aside class="space-y-6 xl:sticky xl:top-6">
                        <div class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden motion-card-enter motion-card-enter-delay-1">
                            
                            <!-- Cabecera del Dashboard -->
                            <div class="border-b border-slate-100 bg-slate-50/50 p-5">
                                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-cyan-600 block">Lectura Rápida</span>
                                <h3 class="text-base font-bold text-slate-900 mt-0.5">Métricas de Evolución</h3>
                            </div>

                            <div class="p-5 space-y-6">
                                
                                <!-- Dial Circular de Cambios Clínicos SVG -->
                                <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl border border-slate-100 bg-slate-50/30">
                                    <div class="relative flex-shrink-0 flex items-center justify-center">
                                        <div class="p-2.5 rounded-full border transition-all duration-300" :class="dialBgColorClass">
                                            <svg class="w-20 h-20" viewBox="0 0 36 36">
                                                <!-- Círculo de fondo (Gris claro) -->
                                                <path class="text-slate-200/60" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                                <!-- Círculo activo (Progreso) -->
                                                <path class="transition-all duration-700 ease-out" 
                                                      :class="dialColorClass" 
                                                      stroke-width="3.2" 
                                                      :stroke-dasharray="porcentajeCambios + ', 100'" 
                                                      stroke-linecap="round" 
                                                      transform="rotate(-90 18 18)" 
                                                      stroke="currentColor" 
                                                      fill="none" 
                                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <span class="text-lg font-black tracking-tighter text-slate-900" style="line-height: 1;">
                                                    {{ porcentajeCambios }}%
                                                </span>
                                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Cambios</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Leyenda del dial -->
                                    <div class="space-y-1 flex-1 text-center sm:text-left">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                            Frecuencia de Cambios
                                        </h4>
                                        <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                            de los controles clínicos del paciente han registrado cambios significativos en el plan o estado nutricional.
                                        </p>
                                    </div>
                                </div>

                                <!-- Alerta Clínica Directa -->
                                <ClinicalAlert
                                    :title="estadoEvolucion.label"
                                    :tone="estadoEvolucion.tone"
                                    class="border shadow-sm"
                                >
                                    {{ estadoEvolucion.mensaje }}
                                </ClinicalAlert>

                                <!-- Grid de 4 Estadísticas en Formato Premium Cards -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/40 p-4 space-y-1">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Total Controles</span>
                                        <p class="text-2xl font-black text-slate-800">{{ totalSeguimientos }}</p>
                                        <p class="text-[10px] text-slate-500 font-semibold">Registrados</p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/40 p-4 space-y-1">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Cambios Reg.</span>
                                        <p class="text-2xl font-black text-slate-800">{{ cambiosSignificativos }}</p>
                                        <p class="text-[10px] text-slate-500 font-semibold">Variaciones</p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/40 p-4 space-y-1">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Última Visita</span>
                                        <p class="text-sm font-black text-slate-800 truncate mt-1.5">{{ ultimoSeguimiento }}</p>
                                        <p class="text-[10px] text-slate-500 font-semibold">Fecha control</p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/40 p-4 space-y-1">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Entradas Hst.</span>
                                        <p class="text-2xl font-black text-slate-800">{{ totalHistorial }}</p>
                                        <p class="text-[10px] text-slate-500 font-semibold">Historial Clínico</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de Consejos Rápidos para el Consolidado -->
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-sm space-y-3 motion-card-enter motion-card-enter-delay-2">
                            <h4 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 1 1 1.063 1.063L12 12.75 11.25 13.5M12 7.5h.008v.008H12v7.5zM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                                Guía de Interpretación
                            </h4>
                            <div class="space-y-2 text-xs text-slate-600 leading-relaxed">
                                <p><strong>Indicador de Cambios:</strong> Un porcentaje alto indica que el paciente requiere reajustes frecuentes del plan por fluctuaciones.</p>
                                <p><strong>Línea de Tiempo:</strong> Revisa el orden cronológico del historial a la derecha para ver la progresión de notas clínicas.</p>
                            </div>
                        </div>
                    </aside>

                    <!-- COLUMNA DERECHA: Historial Consolidado con Diseño de Línea de Tiempo -->
                    <main class="space-y-6">
                        <ClinicalSection
                            eyebrow="Evolución del Paciente"
                            title="Línea de Tiempo Clínico"
                            description="Registro histórico de observaciones, avances y hitos clínicos documentados durante las visitas."
                            tone="neutral"
                            class="motion-card-enter motion-card-enter-delay-1"
                        >
                            <!-- Estado Sin Historial -->
                            <div v-if="!tieneHistorial" class="py-12 text-center space-y-3">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h.008v.008H12V7.5zM12 11.25h.008v.008H12v-.008zm0 3.75h.008v.008H12V15zm0 3.75h.008v.008H12v-.008zM12 3v18" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-slate-700">Sin historial registrado</h4>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                    Aún no hay entradas de evolución nutricional archivadas para este paciente en el sistema.
                                </p>
                            </div>

                            <!-- Contenedor de la Línea de Tiempo -->
                            <div v-else class="relative border-l-2 border-slate-200 ml-4 py-2 space-y-6">
                                
                                <article
                                    v-for="(item, idx) in historial"
                                    :key="`${item.fec_hin}-${idx}`"
                                    class="relative pl-7 transition-all duration-300"
                                >
                                    <!-- Nodo / Punto de la Línea de Tiempo -->
                                    <div class="absolute -left-[9px] top-1.5 flex h-4 w-4 items-center justify-center rounded-full border-2 border-cyan-500 bg-white ring-4 ring-white shadow-sm">
                                        <div class="h-1.5 w-1.5 rounded-full bg-cyan-500"></div>
                                    </div>

                                    <!-- Tarjeta de Entrada Histórica -->
                                    <div class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm motion-lift">
                                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                            
                                            <!-- Fecha con Icono SVG -->
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 rounded-lg bg-cyan-50 border border-cyan-100 text-cyan-700">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                    </svg>
                                                </div>
                                                <ClinicalBadge
                                                    :label="item.fec_hin || 'Sin fecha'"
                                                    tone="info"
                                                    class="border border-cyan-200/50 font-bold"
                                                />
                                            </div>

                                            <span class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400 bg-slate-50 px-2.5 py-1 rounded-md border border-slate-100">
                                                Entrada #{{ idx + 1 }}
                                            </span>
                                        </div>

                                        <!-- Descripción Clínica con Expansor Interactivo -->
                                        <div class="pt-3">
                                            <p class="text-sm leading-relaxed text-slate-700 font-medium whitespace-pre-line">
                                                {{ entradasExpandidas[idx] ? item.des_hin : textoCorto(item.des_hin, 160) }}
                                            </p>

                                            <button 
                                                v-if="item.des_hin && item.des_hin.length > 160"
                                                type="button"
                                                @click="toggleExpandir(idx)"
                                                class="mt-3 text-xs font-bold text-cyan-600 hover:text-cyan-700 hover:underline transition flex items-center gap-1"
                                            >
                                                <span>{{ entradasExpandidas[idx] ? 'Ocultar detalles' : 'Leer nota completa' }}</span>
                                                <svg class="w-3.5 h-3.5 transform transition-transform duration-300" :class="entradasExpandidas[idx] ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </ClinicalSection>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

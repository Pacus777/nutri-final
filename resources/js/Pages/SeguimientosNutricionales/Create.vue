<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    paciente: Object,
    nutricionista: Object,
    fechaSugerida: String,
    contextosCriticos: {
        type: Array,
        default: () => [],
    },
    analisisInteligente: {
        type: Object,
        default: null,
    },
});

const page = usePage();

const activeTab = ref('contextos'); // 'contextos', 'analisis', 'dataset'
const incluirCambio = ref(false);
const mostrarGuia = ref(true);

const form = useForm({
    cod_pacientes_seg: props.paciente.cod_pacientes,
    cod_nutricionistas_seg: props.nutricionista.cod_nutricionistas,
    fec_seg: props.fechaSugerida,
    res_seg: '',
    cam_seg: '',
});

// Watch to clear cam_seg when toggle is turned off
watch(incluirCambio, (val) => {
    if (!val) {
        form.cam_seg = '';
    }
});

const tieneContextosCriticos = computed(() => props.contextosCriticos.length > 0);

const analisisSeguimiento = computed(() => {
    return props.analisisInteligente || {
        modo: 'prototipo',
        periodo: 'Últimos 28 días',
        registros_analizados: 18,
        alimentos_procesados: 74,
        variables_generadas: 12,
        riesgo: {
            nivel: 'Moderado',
            probabilidad: 68,
            interpretacion: 'Posible tendencia a desequilibrio por alto consumo de carbohidratos e irregularidad en horarios.',
        },
        patron: {
            cluster: 'Perfil B',
            nombre: 'Consumo alto en carbohidratos con horarios irregulares',
            similitud: 82,
            descripcion: 'El paciente se agrupa con perfiles que concentran calorías en pocas comidas y presentan baja regularidad en el desayuno.',
        },
        variables: [
            {
                nombre: 'Distribución de carbohidratos',
                valor: '58%',
                lectura: 'Tendencia alta',
                estado: 'warning',
            },
            {
                nombre: 'Frecuencia de desayuno',
                valor: '3/7 días',
                lectura: 'Baja regularidad',
                estado: 'danger',
            },
            {
                nombre: 'Variabilidad horaria',
                valor: 'Alta',
                lectura: 'Rutina irregular',
                estado: 'warning',
            },
        ],
        arbol_decision: [
            {
                condicion: 'Carbohidratos promedio > 55%',
                resultado: 'Sí',
                estado: 'warning',
            },
            {
                condicion: 'Desayuno registrado menos de 4 días/semana',
                resultado: 'Sí',
                estado: 'warning',
            },
            {
                condicion: 'Variabilidad horaria alta',
                resultado: 'Sí',
                estado: 'warning',
            },
        ],
    };
});

const datasetAnaliticoSeguimiento = computed(() => {
    return analisisSeguimiento.value.dataset || {
        registros_totales_periodo: analisisSeguimiento.value.registros_analizados || 0,
        registros_usados: analisisSeguimiento.value.registros_analizados || 0,
        registros_excluidos: 0,
        aceptados: 0,
        observados: 0,
        rechazados: 0,
        pendientes: 0,
        criterio_uso: 'El análisis usa registros aceptados y observados. Excluye registros rechazados o pendientes de validación.',
    };
});

const modoAnalisisSeguimiento = computed(() => {
    if (analisisSeguimiento.value.modo === 'real_preliminar') {
        return {
            label: 'Datos reales preliminares',
            tone: 'success',
            mensaje: 'El seguimiento puede comparar evolución usando registros aceptados u observados del paciente.',
        };
    }

    return {
        label: 'Vista prototipo',
        tone: 'warning',
        mensaje: 'El análisis se muestra como estructura visual preparada para conexión con datos reales.',
    };
});

const analisisSeguimientoSinDatos = computed(() => {
    return Number(analisisSeguimiento.value.registros_analizados || 0) === 0
        || String(analisisSeguimiento.value.riesgo?.nivel || '').toLowerCase().includes('no estimado');
});

const variablesAnaliticasEnAlerta = computed(() => {
    return (analisisSeguimiento.value.variables || []).filter((variable) => {
        return ['warning', 'danger'].includes(variable.estado);
    });
});

const tonoRiesgoAnalitico = computed(() => {
    const nivel = String(analisisSeguimiento.value.riesgo?.nivel || '').toLowerCase();

    if (nivel.includes('alto')) return 'danger';
    if (nivel.includes('moderado')) return 'warning';
    if (nivel.includes('bajo')) return 'success';

    return 'info';
});

const dialColorClass = computed(() => {
    const nivel = String(analisisSeguimiento.value.riesgo?.nivel || '').toLowerCase();
    if (nivel.includes('alto')) return 'text-rose-500';
    if (nivel.includes('moderado')) return 'text-amber-500';
    if (nivel.includes('bajo')) return 'text-emerald-500';
    return 'text-cyan-500';
});

const dialBgColorClass = computed(() => {
    const nivel = String(analisisSeguimiento.value.riesgo?.nivel || '').toLowerCase();
    if (nivel.includes('alto')) return 'bg-rose-50/50 border-rose-100 text-rose-900';
    if (nivel.includes('moderado')) return 'bg-amber-50/50 border-amber-100 text-amber-900';
    if (nivel.includes('bajo')) return 'bg-emerald-50/50 border-emerald-100 text-emerald-900';
    return 'bg-cyan-50/50 border-cyan-100 text-cyan-900';
});

const estadoCaso = computed(() => {
    if (tieneContextosCriticos.value && variablesAnaliticasEnAlerta.value.length > 0) {
        return {
            label: 'Requiere atención',
            tone: 'danger',
            mensaje: 'Este seguimiento debe considerar contextos críticos y variables alimenticias en alerta.',
        };
    }

    if (variablesAnaliticasEnAlerta.value.length > 0) {
        return {
            label: 'Monitoreo recomendado',
            tone: 'warning',
            mensaje: 'Existen variables alimenticias que conviene revisar durante el seguimiento.',
        };
    }

    return {
        label: 'Seguimiento regular',
        tone: 'success',
        mensaje: 'No se observan alertas críticas fuertes para este seguimiento.',
    };
});

const prioridadLabel = (prioridad) => {
    const labels = {
        baja: 'Baja',
        media: 'Media',
        alta: 'Alta',
    };

    return labels[prioridad] || 'Media';
};

const clasePrioridadContexto = (prioridad) => {
    if (prioridad === 'alta') {
        return 'danger';
    }

    if (prioridad === 'media') {
        return 'warning';
    }

    return 'neutral';
};

const agregarTexto = (campo, texto) => {
    const actual = String(form[campo] || '').trim();

    if (!actual) {
        form[campo] = texto;
        return;
    }

    if (!actual.includes(texto)) {
        form[campo] = `${actual}\n\n${texto}`;
    }
};

const insertarContextoEnResultado = (contexto) => {
    agregarTexto(
        'res_seg',
        `Contexto clínico considerado: ${contexto.tip_cxp || 'Contexto'} - ${contexto.des_cxp}`
    );
};

const insertarContextoEnCambio = (contexto) => {
    // Activar el switch de cambio opcional antes de añadir
    incluirCambio.value = true;
    agregarTexto(
        'cam_seg',
        `Punto de control clínico: ${contexto.tip_cxp || 'Contexto'} - ${contexto.des_cxp}`
    );
};

const insertarAnalisisEnResultado = () => {
    agregarTexto(
        'res_seg',
        `Análisis inteligente considerado:\nPatrón detectado: ${analisisSeguimiento.value.patron.cluster} - ${analisisSeguimiento.value.patron.nombre}\nRiesgo estimado: ${analisisSeguimiento.value.riesgo.nivel} (${analisisSeguimiento.value.riesgo.probabilidad}%)\nInterpretación: ${analisisSeguimiento.value.riesgo.interpretacion}`
    );
};

const insertarVariablesEnCambio = () => {
    incluirCambio.value = true;
    const variables = variablesAnaliticasEnAlerta.value
        .map((variable) => `- ${variable.nombre}: ${variable.valor} (${variable.lectura})`)
        .join('\n');

    agregarTexto(
        'cam_seg',
        `Variables alimenticias a monitorear:\n${variables || '- Sin variables en alerta registradas.'}\n\nComparar si el paciente mejoró, mantuvo o empeoró respecto al patrón detectado.`
    );
};

const usarPlantillaResultado = (tipo) => {
    const plantillas = {
        mejora: `Evolución favorable: el paciente muestra avances respecto al seguimiento anterior. Se recomienda mantener las estrategias actuales y continuar monitoreando el patrón alimenticio detectado.`,
        estable: `Evolución estable: el paciente mantiene un comportamiento similar al periodo previo. Se recomienda reforzar adherencia y revisar variables alimenticias en alerta.`,
        alerta: `Evolución con puntos de atención: se identifican dificultades relacionadas con adherencia, horarios o composición alimenticia. Se recomienda ajustar estrategias y realizar control cercano.`,
    };

    agregarTexto('res_seg', plantillas[tipo]);
};

const usarPlantillaCambio = (tipo) => {
    const plantillas = {
        conducta: `Cambio significativo en conducta alimentaria: registrar si hubo mejora o retroceso en horarios, frecuencia de comidas o selección de alimentos.`,
        riesgo: `Cambio significativo relacionado con riesgo nutricional: revisar si las variables en alerta disminuyeron, se mantuvieron o aumentaron.`,
        adherencia: `Cambio significativo en adherencia: valorar cumplimiento de objetivos, recomendaciones y registro alimenticio.`,
    };

    agregarTexto('cam_seg', plantillas[tipo]);
};

const fichaPacienteUrl = computed(() => {
    return route('nutricionista.pacientes.show', props.paciente.cod_pacientes);
});

const submit = () => {
    form.post(route('nutricionista.seguimientos.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('res_seg', 'cam_seg');
            incluirCambio.value = false;
        },
    });
};
</script>

<template>
    <Head title="Nuevo seguimiento nutricional" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Seguimiento nutricional"
                title="Registrar evolución del paciente"
                description="Espacio clínico de trabajo para documentar la evolución del paciente, asistido por IA y contextos clínicos."
            >
                <template #actions>
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur transition-all duration-300 hover:shadow-md">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 px-1">
                            Accesos rápidos
                        </p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link
                                :href="route('nutricionista.seguimientos.historial', paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200/80 hover:bg-slate-100 hover:text-slate-900 transition-all"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Historial
                            </Link>

                            <Link
                                :href="route('nutricionista.seguimientos.consolidado', paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-cyan-50 px-3.5 py-2 text-xs font-bold text-cyan-700 ring-1 ring-cyan-200/60 hover:bg-cyan-100 transition-all"
                            >
                                <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Consolidado
                            </Link>

                            <Link
                                :href="fichaPacienteUrl"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200/60 hover:bg-emerald-100 transition-all"
                            >
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                </svg>
                                Volver a ficha
                            </Link>
                        </div>
                    </div>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Toast de éxito -->
                <Transition
                    enter-active-class="transform ease-out duration-300 transition"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <ClinicalAlert
                        v-if="page.props.flash?.success"
                        title="Seguimiento guardado exitosamente"
                        tone="success"
                        class="shadow-sm border border-emerald-100"
                    >
                        {{ page.props.flash.success }}
                    </ClinicalAlert>
                </Transition>

                <!-- Tarjeta Resumen del Paciente Principal -->
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
                                <p class="text-sm text-slate-500 flex items-center gap-1.5">
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
                                :label="estadoCaso.label"
                                :tone="estadoCaso.tone"
                                class="shadow-sm border animate-pulse"
                            />
                        </div>
                    </div>
                </div>

                <!-- Contenido Principal: Dos Columnas -->
                <div class="grid gap-6 xl:grid-cols-[5fr_7fr] items-start">
                    
                    <!-- COLUMNA IZQUIERDA: Apoyo Clínico Interactivo (Sticky) -->
                    <aside class="space-y-6 xl:sticky xl:top-6">
                        <div class="rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden motion-card-enter motion-card-enter-delay-1">
                            <!-- Cabecera de Diagnóstico y Métricas Visuales -->
                            <div class="border-b border-slate-100 bg-slate-50/50 p-5">
                                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-cyan-600 block">Indicadores Clínicos</span>
                                <h3 class="text-base font-bold text-slate-900 mt-0.5">Diagnóstico Rápido de IA</h3>
                            </div>

                            <div class="p-5 space-y-5">
                                <!-- Dial de Riesgo SVG & Resumen del Patrón -->
                                <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl border border-slate-100 bg-slate-50/30">
                                    
                                    <!-- Dial Circular de Riesgo SVG -->
                                    <div class="relative flex-shrink-0 flex items-center justify-center">
                                        <div class="p-2.5 rounded-full border transition-all duration-300" :class="dialBgColorClass">
                                            <svg class="w-20 h-20" viewBox="0 0 36 36">
                                                <!-- Círculo de fondo (Gris claro) -->
                                                <path class="text-slate-200/60" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                                <!-- Círculo activo (Progreso) -->
                                                <path class="transition-all duration-700 ease-out" 
                                                      :class="dialColorClass" 
                                                      stroke-width="3.2" 
                                                      :stroke-dasharray="analisisSeguimientoSinDatos ? '0, 100' : (analisisSeguimiento.riesgo.probabilidad + ', 100')" 
                                                      stroke-linecap="round" 
                                                      transform="rotate(-90 18 18)" 
                                                      stroke="currentColor" 
                                                      fill="none" 
                                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <span class="text-lg font-black tracking-tighter text-slate-900" style="line-height: 1;">
                                                    {{ analisisSeguimientoSinDatos ? '--' : (analisisSeguimiento.riesgo.probabilidad + '%') }}
                                                </span>
                                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Riesgo</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Detalles del Riesgo Clínico -->
                                    <div class="space-y-1.5 flex-1 text-center sm:text-left">
                                        <div class="flex flex-wrap justify-center sm:justify-start items-center gap-2">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Riesgo Estimado:</span>
                                            <ClinicalBadge :label="analisisSeguimiento.riesgo.nivel" :tone="tonoRiesgoAnalitico" />
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                            {{ analisisSeguimiento.riesgo.interpretacion }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Patron Detectado -->
                                <div class="rounded-2xl border border-amber-100 bg-amber-50/20 p-4 space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-amber-700">Patrón Detectado</span>
                                        <span class="text-xs font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-lg border border-amber-200">
                                            {{ analisisSeguimiento.patron.cluster }} ({{ analisisSeguimiento.patron.similitud }}% similitud)
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">
                                        {{ analisisSeguimiento.patron.nombre }}
                                    </h4>
                                    <p class="text-xs text-slate-600 leading-relaxed">
                                        {{ analisisSeguimiento.patron.descripcion }}
                                    </p>
                                </div>

                                <!-- PESTAÑAS (TABS) PARA DETALLE CLÍNICO -->
                                <div class="space-y-4">
                                    <!-- Botones de Pestaña -->
                                    <div class="flex border-b border-slate-100 p-0.5 bg-slate-100/70 rounded-xl">
                                        <button
                                            type="button"
                                            class="flex-1 py-2 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5"
                                            :class="activeTab === 'contextos' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'"
                                            @click="activeTab = 'contextos'"
                                        >
                                            <svg class="w-3.5 h-3.5" :class="tieneContextosCriticos ? 'text-rose-500' : 'text-slate-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" />
                                            </svg>
                                            <span>Contextos</span>
                                            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-black"
                                                  :class="tieneContextosCriticos ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-600'">
                                                {{ contextosCriticos.length }}
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            class="flex-1 py-2 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5"
                                            :class="activeTab === 'analisis' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'"
                                            @click="activeTab = 'analisis'"
                                        >
                                            <svg class="w-3.5 h-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21m0 0l-1.813-5.096M9 21h7.5M12 3v3m0 12v3M3 12h3m12 0h3M5.636 5.636l2.122 2.122m8.485 8.485l2.122 2.122M5.636 18.364l2.122-2.122m8.485-8.485l2.122-2.122" />
                                            </svg>
                                            <span>Alertas IA</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="flex-1 py-2 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5"
                                            :class="activeTab === 'dataset' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800'"
                                            @click="activeTab = 'dataset'"
                                        >
                                            <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v16.5M21 19.5H3.75M6.75 12v4.5m4.5-9v9M15.75 9v7.5M19.5 6v10.5" />
                                            </svg>
                                            <span>Dataset</span>
                                        </button>
                                    </div>

                                    <!-- Contenido de Pestañas -->
                                    <div class="min-h-[200px]">
                                        <!-- TAB 1: Contextos Clínicos -->
                                        <div v-if="activeTab === 'contextos'" class="space-y-3">
                                            <ClinicalAlert
                                                v-if="!tieneContextosCriticos"
                                                title="Paciente Estable"
                                                tone="success"
                                                class="border border-emerald-100"
                                            >
                                                No existen contextos clínicos de alta prioridad reportados para este paciente.
                                            </ClinicalAlert>

                                            <div v-else class="space-y-3">
                                                <article
                                                    v-for="contexto in contextosCriticos"
                                                    :key="contexto.cod_contextos_paciente"
                                                    class="rounded-2xl border border-rose-100 bg-white p-4 shadow-sm space-y-3 transition hover:shadow"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <ClinicalBadge
                                                            :label="contexto.tip_cxp || 'Contexto'"
                                                            tone="danger"
                                                            class="border border-rose-200"
                                                        />
                                                        <span class="text-[10px] font-black uppercase tracking-wider text-rose-500 bg-rose-50 px-2 py-0.5 rounded-md">
                                                            Prioridad {{ prioridadLabel(contexto.pri_cxp) }}
                                                        </span>
                                                    </div>

                                                    <p class="text-xs leading-relaxed text-slate-700">
                                                        {{ contexto.des_cxp }}
                                                    </p>

                                                    <div class="flex gap-2 pt-1">
                                                        <button
                                                            type="button"
                                                            class="motion-btn flex-1 rounded-xl bg-rose-600 py-2 text-[11px] font-bold text-white hover:bg-rose-700 transition flex items-center justify-center gap-1.5"
                                                            @click="insertarContextoEnResultado(contexto)"
                                                        >
                                                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                            </svg>
                                                            Inyectar a Evolución
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="motion-btn flex-1 rounded-xl bg-slate-100 py-2 text-[11px] font-bold text-slate-700 hover:bg-slate-200 transition flex items-center justify-center gap-1.5"
                                                            @click="insertarContextoEnCambio(contexto)"
                                                        >
                                                            <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                            </svg>
                                                            Inyectar a Cambios
                                                        </button>
                                                    </div>
                                                </article>
                                            </div>
                                        </div>

                                        <!-- TAB 2: Análisis IA (Alertas y Árbol de Decisión) -->
                                        <div v-if="activeTab === 'analisis'" class="space-y-4">
                                            <!-- Variables en Alerta -->
                                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-2">Variables en Alerta</span>
                                                <div v-if="variablesAnaliticasEnAlerta.length === 0" class="text-xs text-slate-500">
                                                    Sin alertas de variables detectadas.
                                                </div>
                                                <div v-else class="flex flex-wrap gap-2">
                                                    <ClinicalBadge
                                                        v-for="variable in variablesAnaliticasEnAlerta"
                                                        :key="variable.nombre"
                                                        :label="`${variable.nombre}: ${variable.valor} (${variable.lectura})`"
                                                        :tone="variable.estado"
                                                        class="border"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Árbol de Decisión -->
                                            <div class="rounded-2xl border border-violet-100 bg-violet-50/10 p-4 space-y-2.5">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-violet-700 block">Reglas de Árbol de Decisión</span>
                                                <div class="space-y-2">
                                                    <div
                                                        v-for="regla in analisisSeguimiento.arbol_decision"
                                                        :key="regla.condicion"
                                                        class="rounded-xl border border-slate-100 bg-white p-2.5 text-xs flex items-center justify-between gap-2 shadow-sm"
                                                    >
                                                        <span class="font-bold text-slate-700">
                                                            {{ regla.condicion }}
                                                        </span>
                                                        <ClinicalBadge
                                                            :label="regla.resultado"
                                                            :tone="regla.estado"
                                                            class="border"
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Inyectar análisis -->
                                            <div class="grid gap-2 grid-cols-2">
                                                <button
                                                    type="button"
                                                    class="motion-btn rounded-xl bg-cyan-600 py-2.5 px-3 text-[11px] font-bold text-white hover:bg-cyan-700 transition flex items-center justify-center gap-1.5 shadow-sm"
                                                    @click="insertarAnalisisEnResultado"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21m0 0l-1.813-5.096M9 21h7.5M12 3v3m0 12v3M3 12h3m12 0h3M5.636 5.636l2.122 2.122m8.485 8.485l2.122 2.122M5.636 18.364l2.122-2.122m8.485-8.485l2.122-2.122" />
                                                    </svg>
                                                    Añadir a Evolución
                                                </button>
                                                <button
                                                    type="button"
                                                    class="motion-btn rounded-xl bg-violet-600 py-2.5 px-3 text-[11px] font-bold text-white hover:bg-violet-700 transition flex items-center justify-center gap-1.5 shadow-sm"
                                                    @click="insertarVariablesEnCambio"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v16.5M21 19.5H3.75M6.75 12v4.5m4.5-9v9M15.75 9v7.5M19.5 6v10.5" />
                                                    </svg>
                                                    Añadir a Cambios
                                                </button>
                                            </div>
                                        </div>

                                        <!-- TAB 3: Dataset y Confianza de los Datos -->
                                        <div v-if="activeTab === 'dataset'" class="space-y-4">
                                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Criterio del Dataset</span>
                                                <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                                    {{ datasetAnaliticoSeguimiento.criterio_uso }}
                                                </p>
                                            </div>

                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                                <div class="rounded-xl bg-emerald-50/60 border border-emerald-100/80 p-3 text-center">
                                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-emerald-600">Aceptados</span>
                                                    <p class="text-xl font-black text-emerald-700 mt-0.5">{{ datasetAnaliticoSeguimiento.aceptados }}</p>
                                                </div>
                                                <div class="rounded-xl bg-amber-50/60 border border-amber-100/80 p-3 text-center">
                                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-amber-600">Observados</span>
                                                    <p class="text-xl font-black text-amber-700 mt-0.5">{{ datasetAnaliticoSeguimiento.observados }}</p>
                                                </div>
                                                <div class="rounded-xl bg-rose-50/60 border border-rose-100/80 p-3 text-center">
                                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-rose-600">Rechazados</span>
                                                    <p class="text-xl font-black text-rose-700 mt-0.5">{{ datasetAnaliticoSeguimiento.rechazados }}</p>
                                                </div>
                                                <div class="rounded-xl bg-slate-50 border border-slate-200/60 p-3 text-center">
                                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-500">Pendientes</span>
                                                    <p class="text-xl font-black text-slate-700 mt-0.5">{{ datasetAnaliticoSeguimiento.pendientes }}</p>
                                                </div>
                                                <div class="rounded-xl bg-cyan-50/60 border border-cyan-100/80 p-3 text-center col-span-2 sm:col-span-1">
                                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-cyan-600">Excluidos</span>
                                                    <p class="text-xl font-black text-cyan-700 mt-0.5">{{ datasetAnaliticoSeguimiento.registros_excluidos }}</p>
                                                </div>
                                            </div>

                                            <div class="text-[11px] text-slate-400 bg-slate-50 p-3 rounded-xl border border-slate-100 text-center font-semibold">
                                                Registros totales analizados: <strong class="text-slate-700">{{ datasetAnaliticoSeguimiento.registros_usados }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Consejos Rápidos de Uso -->
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-sm space-y-3 motion-card-enter motion-card-enter-delay-2">
                            <h4 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 1 1 1.063 1.063L12 12.75 11.25 13.5M12 7.5h.008v.008H12V7.5zM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                                Indicaciones Clínicas
                            </h4>
                            <div class="space-y-2 text-xs text-slate-600 leading-relaxed">
                                <p><strong>1. Diagnóstico</strong>: Consulta las variables de riesgo del paciente antes de comenzar.</p>
                                <p><strong>2. Inyección rápida</strong>: Usa los botones de inyección para incluir en tus apuntes los contextos críticos en un solo clic.</p>
                                <p><strong>3. Redacción</strong>: Escribe los detalles principales en el resultado del seguimiento utilizando plantillas dinámicas.</p>
                            </div>
                        </div>
                    </aside>

                    <!-- COLUMNA DERECHA: Formulario de Registro Clínico -->
                    <main class="space-y-6">
                        <ClinicalSection
                            eyebrow="Registro Profesional"
                            title="Formulario de Seguimiento Nutricional"
                            description="Por favor, completa los campos requeridos para documentar oficialmente este control clínico."
                            tone="neutral"
                            class="motion-card-enter motion-card-enter-delay-1"
                        >
                            <form class="space-y-6 mt-4" @submit.prevent="submit">
                                
                                <!-- Sección 1: Configuración de Fecha y Responsable -->
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-2">
                                        <InputLabel value="Fecha de Consulta" class="text-xs font-bold text-slate-700" />
                                        <div class="relative">
                                            <input
                                                v-model="form.fec_seg"
                                                type="date"
                                                class="block w-full rounded-xl border-slate-200 bg-white text-slate-800 shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-500/10 transition-all font-medium text-sm"
                                            >
                                        </div>
                                        <InputError class="mt-1" :message="form.errors.fec_seg" />
                                    </div>

                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 flex flex-col justify-between">
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Responsable Clínico</span>
                                            <p class="text-sm font-bold text-slate-800 mt-1">
                                                Nutricionista: {{ nutricionista.usuario?.nombre_completo || `#${nutricionista.cod_nutricionistas}` }}
                                            </p>
                                        </div>
                                        <p class="text-[11px] text-slate-400 font-medium mt-2">
                                            El registro quedará firmado y sellado por tu perfil profesional.
                                        </p>
                                    </div>
                                </div>

                                <!-- Sección 2: Resultado del Seguimiento (Evolución) -->
                                <div class="rounded-2xl border border-cyan-100 bg-cyan-50/20 p-5 shadow-sm space-y-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <InputLabel value="Resultado de la Evolución (Requerido)" class="text-sm font-bold text-slate-800" />
                                            <p class="text-xs text-slate-500">
                                                Describe adherencia, síntomas, metas nutricionales y estado general.
                                            </p>
                                        </div>

                                        <!-- Contador de caracteres dinámico con indicador de estado -->
                                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-bold ring-1 transition-all"
                                              :class="String(form.res_seg || '').trim().length >= 80 ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/50' : 'bg-amber-50 text-amber-600 ring-amber-200/50'">
                                            {{ String(form.res_seg || '').trim().length }} caracteres
                                            <span class="ml-1.5 h-1.5 w-1.5 rounded-full" :class="String(form.res_seg || '').trim().length >= 80 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                        </span>
                                    </div>

                                    <!-- Fila de Plantillas Rápidas -->
                                    <div class="rounded-xl border border-cyan-100 bg-white p-3 space-y-2">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-cyan-700 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                            </svg>
                                            Plantillas de evolución rápida
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                class="motion-btn rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition flex items-center gap-1.5"
                                                @click="usarPlantillaResultado('mejora')"
                                            >
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                Evolución Favorable
                                            </button>

                                            <button
                                                type="button"
                                                class="motion-btn rounded-lg bg-slate-50 border border-slate-200/60 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition flex items-center gap-1.5"
                                                @click="usarPlantillaResultado('estable')"
                                            >
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-3-3m3 3l-3 3M4.5 12l3-3m-3 3l3 3" />
                                                </svg>
                                                Evolución Estable
                                            </button>

                                            <button
                                                type="button"
                                                class="motion-btn rounded-lg bg-rose-50 border border-rose-100 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition flex items-center gap-1.5"
                                                @click="usarPlantillaResultado('alerta')"
                                            >
                                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                </svg>
                                                Con Puntos de Atención
                                            </button>
                                        </div>
                                    </div>

                                    <textarea
                                        v-model="form.res_seg"
                                        rows="7"
                                        placeholder="Ejemplo: El paciente mantiene una adherencia parcial. Se detecta dificultad en mantener regularidad de comidas, especialmente por la noche..."
                                        class="block w-full rounded-2xl border-slate-200 bg-white text-slate-800 shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-500/10 transition-all text-sm leading-relaxed"
                                    ></textarea>

                                    <!-- Línea de progreso de caracteres minimalista -->
                                    <div class="h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full transition-all duration-300"
                                             :class="String(form.res_seg || '').trim().length >= 80 ? 'bg-emerald-500 w-full' : 'bg-amber-400'"
                                             :style="{ width: `${Math.min(100, (String(form.res_seg || '').trim().length / 80) * 100)}%` }">
                                        </div>
                                    </div>

                                    <InputError class="mt-1" :message="form.errors.res_seg" />
                                </div>

                                <!-- Sección 3: Cambio Significativo (OPCIONAL con Switch Toggle) -->
                                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                                    
                                    <!-- Cabecera del Toggle -->
                                    <div class="p-5 flex items-center justify-between bg-slate-50/50 border-b border-slate-100">
                                        <div class="space-y-0.5">
                                            <h4 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                                </svg>
                                                Registrar Cambio Clínico Significativo
                                            </h4>
                                            <p class="text-xs text-slate-500">
                                                Actívalo si hubo variaciones importantes de riesgo, conducta o adherencia.
                                            </p>
                                        </div>

                                        <!-- Toggle Switch -->
                                        <button
                                            type="button"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
                                            :class="incluirCambio ? 'bg-violet-600' : 'bg-slate-200'"
                                            @click="incluirCambio = !incluirCambio"
                                        >
                                            <span class="sr-only">Habilitar cambio significativo</span>
                                            <span
                                                aria-hidden="true"
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="incluirCambio ? 'translate-x-5' : 'translate-x-0'"
                                            />
                                        </button>
                                    </div>

                                    <!-- Panel desplegable con transición -->
                                    <Transition
                                        enter-active-class="transition-all duration-300 ease-out"
                                        enter-from-class="max-h-0 opacity-0"
                                        enter-to-class="max-h-[500px] opacity-100"
                                        leave-active-class="transition-all duration-200 ease-in"
                                        leave-from-class="max-h-[500px] opacity-100"
                                        leave-to-class="max-h-0 opacity-0"
                                    >
                                        <div v-if="incluirCambio" class="p-5 bg-violet-50/10 space-y-4 border-t border-slate-100">
                                            
                                            <!-- Fila de Plantillas Rápidas para Cambio -->
                                            <div class="rounded-xl border border-violet-100 bg-white p-3 space-y-2">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-violet-700 flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                                    </svg>
                                                    Plantillas de cambio rápido
                                                </p>
                                                <div class="flex flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        class="motion-btn rounded-lg bg-violet-50 border border-violet-100 px-3 py-1.5 text-xs font-bold text-violet-700 hover:bg-violet-100 transition flex items-center gap-1.5"
                                                        @click="usarPlantillaCambio('conducta')"
                                                    >
                                                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                                        </svg>
                                                        Conducta Alimentaria
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="motion-btn rounded-lg bg-violet-50 border border-violet-100 px-3 py-1.5 text-xs font-bold text-violet-700 hover:bg-violet-100 transition flex items-center gap-1.5"
                                                        @click="usarPlantillaCambio('riesgo')"
                                                    >
                                                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                        </svg>
                                                        Riesgo Nutricional
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="motion-btn rounded-lg bg-violet-50 border border-violet-100 px-3 py-1.5 text-xs font-bold text-violet-700 hover:bg-violet-100 transition flex items-center gap-1.5"
                                                        @click="usarPlantillaCambio('adherencia')"
                                                    >
                                                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                        Adherencia
                                                    </button>
                                                </div>
                                            </div>

                                            <textarea
                                                v-model="form.cam_seg"
                                                rows="4"
                                                placeholder="Ejemplo: Se observa un cambio positivo en la regularidad horaria. El paciente logró desayunar 6 de 7 días, superando la tendencia anterior..."
                                                class="block w-full rounded-2xl border-slate-200 bg-white text-slate-800 shadow-sm focus:border-violet-500 focus:ring focus:ring-violet-500/10 transition-all text-sm leading-relaxed"
                                            ></textarea>

                                            <InputError class="mt-1" :message="form.errors.cam_seg" />
                                        </div>
                                    </Transition>
                                </div>

                                <!-- Inputs Ocultos de Control -->
                                <input type="hidden" v-model="form.cod_pacientes_seg">
                                <input type="hidden" v-model="form.cod_nutricionistas_seg">
                                <InputError :message="form.errors.cod_pacientes_seg" />
                                <InputError :message="form.errors.cod_nutricionistas_seg" />

                                <!-- Sección 4: Validaciones previas al guardado -->
                                <div v-if="tieneContextosCriticos || variablesAnaliticasEnAlerta.length" 
                                     class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 flex gap-3">
                                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    <div class="space-y-1">
                                        <h5 class="text-xs font-bold text-amber-800">Verificación Clínica Sugerida</h5>
                                        <p class="text-[11px] text-amber-700 leading-relaxed">
                                            Asegúrate de que tus notas reflejen el plan de control para los contextos críticos activos o las alertas de alimentación identificadas en la columna de apoyo.
                                        </p>
                                    </div>
                                </div>

                                <!-- Sección 5: Botón de Guardado con Micro-animación -->
                                <div class="rounded-3xl border border-slate-200/80 bg-slate-50 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <p class="text-xs text-slate-500 leading-relaxed max-w-md">
                                        Al guardar, esta consulta se registrará en el historial de evolución del paciente y formará parte de su dataset analítico consolidado.
                                    </p>

                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="motion-btn inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-3.5 text-sm font-bold text-white hover:brightness-105 active:scale-95 shadow-md hover:shadow-lg disabled:opacity-50 disabled:pointer-events-none transition-all"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        {{ form.processing ? 'Guardando Registro...' : 'Guardar Seguimiento' }}
                                    </button>
                                </div>
                            </form>
                        </ClinicalSection>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
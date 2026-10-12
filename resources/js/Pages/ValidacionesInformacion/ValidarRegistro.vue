<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import ClinicalStatCard from '@/Components/NutricionistaUI/ClinicalStatCard.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    registro: Object,
    resumen: {
        type: Object,
        default: () => ({
            total_alimentos: 0,
            con_datos: 0,
            sin_datos: 0,
            cal: 0,
            pro: 0,
            car: 0,
            gra: 0,
        }),
    },
    validacionExistente: {
        type: Object,
        default: null,
    },
    estadosValidacion: {
        type: Array,
        default: () => ['aceptado', 'observado', 'rechazado'],
    },
    contextosCriticos: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    fec_vin: props.validacionExistente?.fec_vin || new Date().toISOString().slice(0, 10),
    est_vin: props.validacionExistente?.est_vin || '',
    obs_vin: props.validacionExistente?.obs_vin || '',
});

const requiereObservacion = computed(() =>
    ['observado', 'rechazado'].includes(form.est_vin)
);

const tieneContextosCriticos = computed(() => props.contextosCriticos.length > 0);

const totalComidasRegistradas = computed(() => {
    return detallesPorComida.value.length;
});

const horariosRegistrados = computed(() => {
    return (props.registro.detalles || []).filter((detalle) => detalle.hor_dra).length;
});

const totalDetallesRegistro = computed(() => {
    return (props.registro.detalles || []).length;
});

const coberturaNutricionalPorcentaje = computed(() => {
    const total = Number(props.resumen.total_alimentos || 0);
    const conDatos = Number(props.resumen.con_datos || 0);

    if (total === 0) {
        return 0;
    }

    return Math.round((conDatos / total) * 100);
});

const coberturaHorariaPorcentaje = computed(() => {
    if (totalDetallesRegistro.value === 0) {
        return 0;
    }

    return Math.round((horariosRegistrados.value / totalDetallesRegistro.value) * 100);
});

const calidadDatoAnalitico = computed(() => {
    const cobertura = coberturaNutricionalPorcentaje.value;
    const comidas = totalComidasRegistradas.value;
    const horarios = coberturaHorariaPorcentaje.value;
    const totalAlimentos = Number(props.resumen.total_alimentos || 0);
    const sinDatos = Number(props.resumen.sin_datos || 0);

    if (totalAlimentos === 0) {
        return {
            nivel: 'No usable',
            tone: 'danger',
            porcentaje: 0,
            mensaje: 'El registro no contiene alimentos, por lo tanto no puede alimentar variables nutricionales ni modelos analíticos.',
        };
    }

    if (sinDatos > 0 || cobertura < 70 || comidas < 2 || horarios < 80) {
        return {
            nivel: 'Usable con advertencia',
            tone: 'warning',
            porcentaje: Math.min(cobertura, horarios),
            mensaje: 'El registro puede conservarse como dato histórico, pero requiere observación porque tiene información incompleta o limitada.',
        };
    }

    return {
        nivel: 'Dato confiable',
        tone: 'success',
        porcentaje: Math.min(cobertura, horarios),
        mensaje: 'El registro tiene suficiente estructura para alimentar el cálculo nutricional y variables analíticas.',
    };
});

const variablesQueActualiza = computed(() => {
    return [
        {
            nombre: 'Calorías diarias',
            valor: `${fmt(props.resumen.cal)} kcal`,
            tone: 'warning',
        },
        {
            nombre: 'Proteínas',
            valor: `${fmt(props.resumen.pro)} g`,
            tone: 'info',
        },
        {
            nombre: 'Carbohidratos',
            valor: `${fmt(props.resumen.car)} g`,
            tone: 'violet',
        },
        {
            nombre: 'Grasas',
            valor: `${fmt(props.resumen.gra)} g`,
            tone: 'danger',
        },
        {
            nombre: 'Frecuencia de comidas',
            valor: `${totalComidasRegistradas.value} registrada${totalComidasRegistradas.value === 1 ? '' : 's'}`,
            tone: totalComidasRegistradas.value >= 3 ? 'success' : 'warning',
        },
        {
            nombre: 'Regularidad horaria',
            valor: `${coberturaHorariaPorcentaje.value}%`,
            tone: coberturaHorariaPorcentaje.value >= 80 ? 'success' : 'warning',
        },
    ];
});

const impactoAnaliticoValidacion = computed(() => {
    if (form.est_vin === 'aceptado') {
        return {
            title: 'Dato confiable para análisis inteligente',
            tone: 'success',
            mensaje: 'Si guardas este registro como validado, podrá considerarse como dato confiable para alimentar variables de análisis posterior.',
        };
    }

    if (form.est_vin === 'observado') {
        return {
            title: 'Dato usable con advertencia',
            tone: 'warning',
            mensaje: 'Si guardas este registro como observado, se conservará como historial, pero debe interpretarse con cautela por inconsistencias.',
        };
    }

    if (form.est_vin === 'rechazado') {
        return {
            title: 'Dato no recomendado para análisis',
            tone: 'danger',
            mensaje: 'Si guardas este registro como rechazado, no debería alimentar el análisis inteligente porque no es suficientemente confiable.',
        };
    }

    return {
        title: 'Selecciona una decisión',
        tone: 'info',
        mensaje: 'La decisión de validación define si este registro será confiable, usable con advertencia o no recomendable para análisis posterior.',
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

const etiquetasTipoComida = {
    desayuno: 'Desayuno',
    media_manana: 'Media mañana',
    almuerzo: 'Almuerzo',
    merienda: 'Merienda',
    cena: 'Cena',
    snack: 'Snack',
};

const ordenTipoComida = ['desayuno', 'media_manana', 'almuerzo', 'merienda', 'cena', 'snack'];

const fmt = (valor) => {
    if (valor === null || valor === undefined || valor === '') {
        return '0.0';
    }

    const numero = Number.parseFloat(String(valor).replace(',', '.'));

    return Number.isFinite(numero) ? numero.toFixed(1) : '0.0';
};

const etiquetaTipoComida = (tipo) => etiquetasTipoComida[tipo] || tipo || 'Sin tipo';

const detallesPorComida = computed(() => {
    const grupos = {};

    for (const detalle of props.registro.detalles || []) {
        const tipo = detalle.tip_dra || 'snack';

        if (!grupos[tipo]) {
            grupos[tipo] = [];
        }

        grupos[tipo].push(detalle);
    }

    return ordenTipoComida
        .filter((tipo) => grupos[tipo])
        .map((tipo) => ({
            tipo,
            label: etiquetaTipoComida(tipo),
            items: grupos[tipo],
        }));
});

const alertasAutomaticasRegistro = computed(() => {
    const alertas = [];
    const resumen = props.resumen || {};
    const totalAlimentos = Number(resumen.total_alimentos || 0);
    const conDatos = Number(resumen.con_datos || 0);
    const sinDatos = Number(resumen.sin_datos || 0);
    const calorias = Number(resumen.cal || 0);
    const proteina = Number(resumen.pro || 0);
    const grasa = Number(resumen.gra || 0);

    if (totalAlimentos === 0) {
        alertas.push({
            tipo: 'riesgo',
            titulo: 'Registro sin alimentos',
            mensaje: 'El registro no contiene alimentos para validar.',
        });

        return alertas;
    }

    if (totalAlimentos === 1) {
        alertas.push({
            tipo: 'info',
            titulo: 'Registro limitado',
            mensaje: 'El paciente registró solo un alimento. Puede ser correcto, pero conviene revisar si el día fue registrado parcialmente.',
        });
    }

    if (sinDatos > 0) {
        alertas.push({
            tipo: 'advertencia',
            titulo: 'Composición nutricional incompleta',
            mensaje: `${sinDatos} alimento${sinDatos === 1 ? '' : 's'} no tiene${sinDatos === 1 ? '' : 'n'} composición nutricional disponible.`,
        });
    }

    if (conDatos > 0 && calorias < 800 && totalAlimentos >= 2) {
        alertas.push({
            tipo: 'info',
            titulo: 'Total calórico bajo',
            mensaje: 'El total calórico parece bajo para un registro diario. Revisa si faltan comidas o porciones.',
        });
    }

    if (calorias > 3000) {
        alertas.push({
            tipo: 'advertencia',
            titulo: 'Total calórico elevado',
            mensaje: 'El total calórico registrado es elevado. Revisa si las cantidades son coherentes.',
        });
    }

    if (grasa > 100) {
        alertas.push({
            tipo: 'advertencia',
            titulo: 'Grasas elevadas',
            mensaje: 'El registro presenta un aporte alto de grasas. Puede requerir observación según los objetivos.',
        });
    }

    if (proteina > 0 && proteina < 20 && totalAlimentos >= 2) {
        alertas.push({
            tipo: 'info',
            titulo: 'Proteínas bajas',
            mensaje: 'El aporte proteico parece bajo. Revisa si las cantidades son correctas.',
        });
    }

    if (tieneContextosCriticos.value) {
        alertas.push({
            tipo: 'riesgo',
            titulo: 'Paciente con contextos clínicos críticos',
            mensaje: 'Este registro debe validarse considerando alergias, restricciones o patologías activas.',
        });
    }

    return alertas;
});

const claseAlertaAutomatica = (tipo) => {
    if (tipo === 'riesgo') {
        return 'border-rose-200 bg-rose-50/50 text-rose-800';
    }

    if (tipo === 'advertencia') {
        return 'border-amber-200 bg-amber-50/50 text-amber-800';
    }
    return 'border-blue-200 bg-blue-50/50 text-blue-800';
};

const iconoAlertaAutomatica = (tipo) => {
    if (tipo === 'riesgo') return 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z';
    if (tipo === 'advertencia') return 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z';
    return 'M11.25 11.25l.041-.02a.75.75 0 1 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z';
};

const etiquetaFuente = (fuente) => {
    if (fuente === 'usda') return 'USDA';
    if (fuente === 'manual') return 'Manual';
    if (fuente === 'fatsecret') return 'FatSecret';

    return 'Local';
};

const insertarAlertaComoObservacion = (alerta) => {
    const texto = `${alerta.titulo}: ${alerta.mensaje}`;
    const actual = String(form.obs_vin || '').trim();

    if (!actual) {
        form.obs_vin = texto;
    } else if (!actual.includes(texto)) {
        form.obs_vin = `${actual}\n\n${texto}`;
    }

    if (!form.est_vin || form.est_vin === 'aceptado') {
        form.est_vin = 'observado';
    }
};

const plantillasObservacion = [
    {
        titulo: 'Registro parcial',
        texto: 'El registro parece parcial. Se recomienda completar todas las comidas del día para mejorar la calidad del análisis nutricional.',
    },
    {
        titulo: 'Cantidades dudosas',
        texto: 'Se recomienda revisar las cantidades registradas, ya que podrían afectar el cálculo nutricional y la interpretación del consumo diario.',
    },
    {
        titulo: 'Falta composición',
        texto: 'Algunos alimentos no cuentan con composición nutricional disponible, por lo que el resumen energético puede estar incompleto.',
    },
    {
        titulo: 'Contexto crítico',
        texto: 'El registro debe revisarse considerando los contextos clínicos críticos activos del paciente.',
    },
    {
        titulo: 'Dato analítico incompleto',
        texto: 'Este registro puede conservarse como historial, pero presenta limitaciones para alimentar el análisis inteligente del paciente.',
    },
];

const insertarPlantilla = (texto) => {
    const actual = String(form.obs_vin || '').trim();

    if (!actual) {
        form.obs_vin = texto;
        return;
    }

    if (!actual.includes(texto)) {
        form.obs_vin = `${actual}\n\n${texto}`;
    }
};

const limpiarObservacion = () => {
    form.obs_vin = '';
};

const insertarImpactoAnaliticoComoObservacion = () => {
    insertarPlantilla(`${impactoAnaliticoValidacion.value.title}: ${impactoAnaliticoValidacion.value.mensaje}`);

    if (!form.est_vin || form.est_vin === 'aceptado') {
        form.est_vin = calidadDatoAnalitico.value.tone === 'success'
            ? 'aceptado'
            : 'observado';
    }
};

const etiquetaEstadoValidacion = (estado) => {
    const etiquetas = {
        aceptado: 'Validado',
        observado: 'Observado',
        rechazado: 'Rechazado',
    };

    return etiquetas[estado] || estado;
};

const guardar = () => {
    form.post(route('nutricionista.validaciones-informacion.guardar', props.registro.cod_registros_alimenticios));
};

const claseBotonEstado = (estado) => {
    const isActive = form.est_vin === estado;
    if (estado === 'aceptado') {
        return isActive 
            ? 'border-2 border-emerald-500 bg-emerald-50/50 text-emerald-800 ring-4 ring-emerald-500/10' 
            : 'border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/10 text-slate-600';
    }
    if (estado === 'observado') {
        return isActive 
            ? 'border-2 border-amber-500 bg-amber-50/50 text-amber-800 ring-4 ring-amber-500/10' 
            : 'border border-slate-200 hover:border-amber-300 hover:bg-amber-50/10 text-slate-600';
    }
    if (estado === 'rechazado') {
        return isActive 
            ? 'border-2 border-rose-500 bg-rose-50/50 text-rose-800 ring-4 ring-rose-500/10' 
            : 'border border-slate-200 hover:border-rose-300 hover:bg-rose-50/10 text-slate-600';
    }
    return 'border-slate-200 text-slate-600';
};

const claseFocusInput = computed(() => {
    if (form.est_vin === 'aceptado') {
        return 'focus:border-emerald-500 focus:ring-emerald-500/10';
    }
    if (form.est_vin === 'observado') {
        return 'focus:border-amber-500 focus:ring-amber-500/10';
    }
    if (form.est_vin === 'rechazado') {
        return 'focus:border-rose-500 focus:ring-rose-500/10';
    }
    return 'focus:border-cyan-500 focus:ring-cyan-500/10';
});
</script>

<template>
    <Head title="Validar registro alimenticio" />

    <AuthenticatedLayout>
        <template #header>
            <ClinicalPageHeader
                eyebrow="Validación nutricional"
                title="Revisar registro alimenticio"
                description="Analiza el consumo registrado por el paciente, revisa alertas automáticas y emite una validación profesional."
            >
                <template #actions>
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur transition-all duration-300 hover:shadow-md">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 px-1">
                            Acciones de Expediente
                        </p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link
                                :href="route('nutricionista.validaciones-informacion.pendientes')"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 ring-1 ring-slate-200/85 hover:bg-slate-100 hover:text-slate-900 transition"
                            >
                                <svg class="h-4 w-4 text-slate-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                </svg>
                                Volver a Pendientes
                            </Link>

                            <Link
                                v-if="registro.paciente?.cod_pacientes"
                                :href="route('nutricionista.validaciones-informacion.historial-paciente', registro.paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-indigo-50 px-4 py-2.5 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100 hover:text-indigo-900 transition"
                            >
                                <svg class="h-4 w-4 text-indigo-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Historial de Validaciones
                            </Link>

                            <Link
                                v-if="registro.paciente?.cod_pacientes"
                                :href="route('nutricionista.seguimientos.create', registro.paciente.cod_pacientes)"
                                class="motion-btn flex items-center gap-1.5 rounded-xl bg-cyan-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-cyan-700 shadow-sm transition"
                            >
                                <svg class="h-4 w-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Registrar Seguimiento
                            </Link>
                        </div>
                    </div>
                </template>
            </ClinicalPageHeader>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                
                <!-- Tarjeta Resumen del Paciente Principal (Identidad Unificada) -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm motion-card-enter">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-cyan-500 to-emerald-400 text-white font-black text-lg shadow-sm">
                                {{ registro.paciente?.usuario?.nombre_completo ? registro.paciente.usuario.nombre_completo.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'P' }}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                                    {{ registro.paciente?.usuario?.nombre_completo || 'Paciente no disponible' }}
                                </h2>
                                <p class="text-sm text-slate-500 flex items-center gap-1.5 mt-0.5 font-semibold">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5" />
                                    </svg>
                                    Registro del: <span class="text-slate-700 font-bold">{{ registro.fec_ral || '-' }}</span>
                                    <span class="text-slate-300">|</span>
                                    <span>ID Registro: #{{ registro.cod_registros_alimenticios }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <ClinicalBadge
                                label="En revisión"
                                tone="info"
                                class="shadow-sm border uppercase font-bold text-[10px]"
                            />
                            <ClinicalBadge
                                v-if="validacionExistente"
                                label="Con validación previa"
                                tone="warning"
                                class="shadow-sm border uppercase font-bold text-[10px]"
                            />
                            <ClinicalBadge
                                v-if="tieneContextosCriticos"
                                :label="`${contextosCriticos.length} contextos clínicos críticos`"
                                tone="danger"
                                class="shadow-sm border animate-pulse text-[10px] font-bold"
                            />
                        </div>
                    </div>

                    <!-- Fila de Estadísticas Físicas Clínicas -->
                    <div class="mt-6 grid gap-4 grid-cols-2 md:grid-cols-4 pt-6 border-t border-slate-100">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/30 p-4 flex items-center gap-3 transition hover:bg-slate-50 duration-200">
                            <div class="p-2.5 rounded-xl bg-slate-100 text-slate-600 border border-slate-200/50 shadow-sm">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H9.75M9.75 8.25h4.5m-.75 11.25c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H5.25" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Total Alimentos</span>
                                <p class="text-lg font-black text-slate-800">{{ resumen.total_alimentos }} <span class="text-xs font-semibold text-slate-500">ítems</span></p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/30 p-4 flex items-center gap-3 transition hover:bg-emerald-50/10 hover:border-emerald-100 duration-200">
                            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100/50 shadow-sm">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Con Composición</span>
                                <p class="text-lg font-black text-slate-800">{{ resumen.con_datos }} <span class="text-xs font-semibold text-slate-500">ítems</span></p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/30 p-4 flex items-center gap-3 transition duration-200"
                             :class="resumen.sin_datos > 0 ? 'hover:bg-amber-50/10 hover:border-amber-250' : 'hover:bg-slate-50'">
                            <div class="p-2.5 rounded-xl border shadow-sm"
                                 :class="resumen.sin_datos > 0 ? 'bg-amber-50 text-amber-600 border-amber-100/50' : 'bg-slate-100 text-slate-500 border-slate-200/50'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 font-semibold">Sin Composición</span>
                                <p class="text-lg font-black text-slate-800">{{ resumen.sin_datos }} <span class="text-xs font-semibold text-slate-500">ítems</span></p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/30 p-4 flex items-center gap-3 transition hover:bg-violet-50/10 hover:border-violet-100 duration-200">
                            <div class="p-2.5 rounded-xl bg-violet-50 text-violet-600 border border-violet-100/50 shadow-sm">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Cobertura Cálculo</span>
                                <p class="text-lg font-black text-slate-800">{{ coberturaNutricionalPorcentaje }}% <span class="text-xs font-semibold text-slate-500">calculable</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL NUTRICIONAL PREMIUM (Gauges de macronutrientes) -->
                <div class="grid gap-6 grid-cols-2 lg:grid-cols-4">
                    <!-- Calorías -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md duration-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Calorías Diarias</span>
                            <div class="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-100/50 shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.467 5.99 5.99 0 0 0-1.925 3.546 5.974 5.974 0 0 1-2.133-1A3.75 3.75 0 0 0 12 18Z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-2xl font-black text-slate-800">{{ fmt(resumen.cal) }} <span class="text-xs font-semibold text-slate-400">kcal</span></span>
                            <p class="text-[11px] text-slate-500 font-semibold mt-1">Aporte energético total del día.</p>
                        </div>
                        <div class="mt-4 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-full" :style="{ width: Math.min(100, (resumen.cal / 2500) * 100) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Proteínas -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md duration-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Proteínas</span>
                            <div class="p-2 rounded-xl bg-blue-50 text-blue-600 border border-blue-100/50 shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.745 3A23.933 23.933 0 0 0 3 12c0 3.183.62 6.22 1.745 9M19.5 3c.967 2.78 1.5 5.817 1.5 9s-.533 6.22-1.5 9M8.25 5.25h7.5M8.25 8.25h7.5M8.25 11.25h7.5M8.25 14.25h7.5M8.25 17.25h7.5M8.25 20.25h7.5" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-2xl font-black text-slate-800">{{ fmt(resumen.pro) }} <span class="text-xs font-semibold text-slate-400">g</span></span>
                            <p class="text-[11px] text-slate-500 font-semibold mt-1">Esencial para mantenimiento muscular.</p>
                        </div>
                        <div class="mt-4 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-400 to-indigo-550 rounded-full" :style="{ width: Math.min(100, (resumen.pro / 120) * 100) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Carbohidratos -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md duration-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Carbohidratos</span>
                            <div class="p-2 rounded-xl bg-violet-50 text-violet-600 border border-violet-100/50 shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-2xl font-black text-slate-800">{{ fmt(resumen.car) }} <span class="text-xs font-semibold text-slate-400">g</span></span>
                            <p class="text-[11px] text-slate-500 font-semibold mt-1">Fuente primaria de energía celular.</p>
                        </div>
                        <div class="mt-4 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-violet-400 to-fuchsia-550 rounded-full" :style="{ width: Math.min(100, (resumen.car / 300) * 100) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Grasas -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md duration-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Grasas Totales</span>
                            <div class="p-2 rounded-xl bg-rose-50 text-rose-600 border border-rose-100/50 shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-2xl font-black text-slate-800">{{ fmt(resumen.gra) }} <span class="text-xs font-semibold text-slate-400">g</span></span>
                            <p class="text-[11px] text-slate-500 font-semibold mt-1">Soporte hormonal y absorción de vitaminas.</p>
                        </div>
                        <div class="mt-4 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-rose-400 to-pink-500 rounded-full" :style="{ width: Math.min(100, (resumen.gra / 80) * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <div v-if="resumen.sin_datos > 0" class="motion-card-enter">
                    <ClinicalAlert
                        title="Composición nutricional incompleta detectada"
                        tone="warning"
                        class="border"
                    >
                        Hay {{ resumen.sin_datos }} alimento(s) en este registro que no tienen datos nutricionales en la tabla de referencia. 
                        El total estimado de calorías y macronutrientes podría ser menor al consumo real.
                    </ClinicalAlert>
                </div>

                <!-- CALIDAD DEL DATO E IMPACTO ML (Sleek Glassmorphism style card) -->
                <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-sm backdrop-blur transition-all duration-300 hover:shadow-md motion-card-enter">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-violet-700">Calidad Analítica del Dato</span>
                            <h3 class="mt-1 text-lg font-bold text-slate-800">Calidad del Registro para Algoritmos de IA</h3>
                            <p class="text-xs text-slate-500 font-semibold mt-0.5">La rigurosidad horaria y nutricional dictamina la usabilidad del dato clínico.</p>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">
                            <ClinicalBadge
                                :label="calidadDatoAnalitico.nivel"
                                :tone="calidadDatoAnalitico.tone"
                                class="shadow-sm border uppercase font-bold text-[9px]"
                            />
                            <ClinicalBadge
                                :label="`${calidadDatoAnalitico.porcentaje}% Estructura Confiable`"
                                :tone="calidadDatoAnalitico.tone"
                                class="shadow-sm border text-[9px]"
                            />
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 grid-cols-2 md:grid-cols-4">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/20 p-4 transition duration-200 hover:bg-slate-50">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Cobertura Nutricional</span>
                            <p class="mt-1.5 text-base font-black text-slate-800">{{ coberturaNutricionalPorcentaje }}%</p>
                            <span class="text-[10px] text-slate-400 mt-1 block font-medium">Con datos del USDA/Local</span>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/20 p-4 transition duration-200 hover:bg-slate-50">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Comidas Declaradas</span>
                            <p class="mt-1.5 text-base font-black text-slate-800">{{ totalComidasRegistradas }} momentos</p>
                            <span class="text-[10px] text-slate-400 mt-1 block font-medium">Frecuencia alimentaria diaria</span>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/20 p-4 transition duration-200 hover:bg-slate-50">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Horarios Declarados</span>
                            <p class="mt-1.5 text-base font-black text-slate-800">{{ coberturaHorariaPorcentaje }}%</p>
                            <span class="text-[10px] text-slate-400 mt-1 block font-medium">Regularidad analizable</span>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/20 p-4 transition duration-200 hover:bg-slate-50">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Estado de Aporte ML</span>
                            <p class="mt-1.5 text-base font-black text-slate-800" :class="`text-${calidadDatoAnalitico.tone}-700`">
                                {{ calidadDatoAnalitico.nivel.split(' ')[0] }}
                            </p>
                            <span class="text-[10px] text-slate-400 mt-1 block font-medium">Según consistencia del registro</span>
                        </div>
                    </div>

                    <!-- Panel de Variables de Machine Learning -->
                    <div class="mt-6 rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50/20 to-indigo-50/10 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 leading-tight">Variables clínicas alimentadas por este registro</h4>
                                <p class="text-xs text-slate-500 font-semibold mt-1">Si este registro se valida y acepta, aportará directamente al entrenamiento de los siguientes perfiles de predicción:</p>
                            </div>
                            <ClinicalBadge label="Base para Inteligencia Artificial" tone="violet" class="text-[9px] uppercase font-bold border" />
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <ClinicalBadge
                                v-for="variable in variablesQueActualiza"
                                :key="variable.nombre"
                                :label="`${variable.nombre}: ${variable.valor}`"
                                :tone="variable.tone"
                                class="shadow-sm border font-bold text-[10px]"
                            />
                        </div>
                    </div>
                </div>

                <!-- ALERTAS CLÍNICAS AUTOMÁTICAS -->
                <div v-if="alertasAutomaticasRegistro.length" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm motion-card-enter">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                        <div class="p-1 rounded bg-amber-50 text-amber-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-600">Alertas Clínicas Sugeridas</h3>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <article
                            v-for="alerta in alertasAutomaticasRegistro"
                            :key="`${alerta.tipo}-${alerta.titulo}`"
                            class="rounded-2xl border p-4 text-xs shadow-sm flex flex-col justify-between gap-3 transition hover:shadow-md"
                            :class="claseAlertaAutomatica(alerta.tipo)"
                        >
                            <div>
                                <p class="font-bold flex items-center gap-1.5 text-slate-900">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="iconoAlertaAutomatica(alerta.tipo)" />
                                    </svg>
                                    {{ alerta.titulo }}
                                </p>
                                <p class="mt-1 text-slate-600 leading-relaxed font-semibold">
                                    {{ alerta.mensaje }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="mt-2 w-fit rounded-xl bg-white border border-slate-200 px-3.5 py-2 text-[10px] font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm flex items-center gap-1"
                                @click.prevent="insertarAlertaComoObservacion(alerta)"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Usar en Observación
                            </button>
                        </article>
                    </div>
                </div>

                <!-- GRID PRINCIPAL DE VALIDACIÓN (Consumo vs Formulario) -->
                <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] items-start">
                    
                    <!-- COLUMNA 1: CONSUMO DECLARADO (Timeline Alimenticio) -->
                    <div class="space-y-6">
                        
                        <!-- Panel de Advertencia si hay Contextos Clínicos Críticos -->
                        <div v-if="tieneContextosCriticos" class="rounded-3xl border border-rose-100 bg-rose-50/20 p-6 shadow-sm space-y-4">
                            <div class="flex items-center gap-2 border-b border-rose-100 pb-3">
                                <div class="p-1 rounded bg-rose-100 text-rose-700 shadow-sm border border-rose-200/50">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                    </svg>
                                </div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-rose-800">Contextos Clínicos de Alta Prioridad</h3>
                            </div>
                            <div class="grid gap-3">
                                <article
                                    v-for="contexto in  contextosCriticos"
                                    :key="contexto.cod_contextos_paciente"
                                    class="rounded-2xl border border-rose-100 bg-white p-4 shadow-sm flex flex-col justify-between gap-3"
                                >
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <ClinicalBadge :label="contexto.tip_cxp" tone="danger" class="font-bold border uppercase text-[9px]" />
                                            <span class="text-[10px] text-slate-400 font-bold">{{ contexto.fec_cxp }}</span>
                                        </div>
                                        <p class="mt-2 text-xs text-slate-700 font-semibold leading-relaxed">
                                            {{ contexto.des_cxp }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="w-fit rounded-xl bg-rose-50 border border-rose-100 px-3.5 py-1.5 text-[10px] font-bold text-rose-700 hover:bg-rose-100 transition flex items-center gap-1"
                                        @click="insertarPlantilla(`Contexto crítico activo (${contexto.tip_cxp || 'Contexto'}): ${contexto.des_cxp}`)"
                                    >
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Usar en Observación
                                    </button>
                                </article>
                            </div>
                        </div>

                        <!-- Detalle de Alimentos Registrados (Sleek Timeline) -->
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Consumo Declarado</span>
                                <h3 class="mt-1 text-lg font-bold text-slate-800">Alimentos Agrupados por Comida</h3>
                                <p class="text-xs text-slate-500 font-semibold mt-0.5">Revisa cantidades, unidades, aportes e inconsistencias.</p>
                            </div>

                            <div v-if="detallesPorComida.length > 0" class="space-y-8 relative border-l-2 border-slate-100 ml-4 pl-6 py-2">
                                <section
                                    v-for="grupo in detallesPorComida"
                                    :key="grupo.tipo"
                                    class="relative space-y-4"
                                >
                                    <!-- Timeline dot indicating meal type -->
                                    <div class="absolute -left-[35px] top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-white ring-4 ring-slate-50 border border-slate-300 shadow-sm">
                                        <div class="h-1.5 w-1.5 rounded-full bg-emerald-500"></div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-600 bg-slate-100 px-3 py-1 rounded-lg">
                                            {{ grupo.label }}
                                        </h4>
                                        <span class="text-[10px] text-slate-400 font-bold">({{ grupo.items.length }} alimento{{ grupo.items.length === 1 ? '' : 's' }})</span>
                                    </div>

                                    <div class="grid gap-3">
                                        <article
                                            v-for="detalle in grupo.items"
                                            :key="detalle.cod_detalle_registro_alimenticio"
                                            class="rounded-2xl border border-slate-200/80 bg-slate-50/20 p-4 transition-all hover:bg-slate-50 hover:shadow-sm duration-200"
                                        >
                                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                                <div>
                                                    <p class="font-bold text-slate-800 text-sm leading-snug">
                                                        {{ detalle.alimento?.nom_ali || 'Alimento no disponible' }}
                                                    </p>
                                                    <p v-if="detalle.alimento?.nom_ext_ali" class="text-[10px] text-slate-400 mt-0.5 font-semibold">
                                                        USDA: {{ detalle.alimento.nom_ext_ali }}
                                                    </p>
                                                    <div class="flex flex-wrap gap-1.5 mt-2 text-[10px] text-slate-400 font-bold">
                                                        <span class="bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">{{ detalle.alimento?.mar_ali || 'Sin marca' }}</span>
                                                        <span class="bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">{{ etiquetaFuente(detalle.alimento?.src_ali) }}</span>
                                                        <span class="bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">{{ detalle.alimento?.tip_ali || 'Alimento' }}</span>
                                                    </div>
                                                </div>

                                                <div class="rounded-xl border border-slate-200 bg-white p-3 text-center shrink-0 w-32 shadow-sm">
                                                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-400">Porción</span>
                                                    <p class="text-xs font-black text-slate-700 mt-0.5">{{ detalle.can_dra }} {{ detalle.uni_dra }}</p>
                                                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-400 mt-1.5">Hora</span>
                                                    <p class="text-[10px] font-bold text-slate-600 mt-0.5">{{ detalle.hor_dra }}</p>
                                                </div>
                                            </div>

                                            <!-- Aportes de macronutrientes del alimento específico -->
                                            <div v-if="detalle.aporte" class="mt-4 grid grid-cols-4 gap-2 border-t border-slate-100 pt-3">
                                                <div class="rounded-xl border border-amber-100 bg-amber-50/20 px-2.5 py-1.5 text-center transition hover:bg-amber-50 duration-150">
                                                    <span class="block text-[8px] font-black uppercase tracking-wider text-amber-600">Calorías</span>
                                                    <p class="text-[11px] font-black text-slate-800 mt-0.5">{{ fmt(detalle.aporte.cal) }} <span class="text-[9px] font-normal text-slate-500">kcal</span></p>
                                                </div>
                                                <div class="rounded-xl border border-blue-100 bg-blue-50/20 px-2.5 py-1.5 text-center transition hover:bg-blue-50 duration-150">
                                                    <span class="block text-[8px] font-black uppercase tracking-wider text-blue-600">Proteína</span>
                                                    <p class="text-[11px] font-black text-slate-800 mt-0.5">{{ fmt(detalle.aporte.pro) }} <span class="text-[9px] font-normal text-slate-500">g</span></p>
                                                </div>
                                                <div class="rounded-xl border border-violet-100 bg-violet-50/20 px-2.5 py-1.5 text-center transition hover:bg-violet-50 duration-150">
                                                    <span class="block text-[8px] font-black uppercase tracking-wider text-violet-600">Carbos</span>
                                                    <p class="text-[11px] font-black text-slate-800 mt-0.5">{{ fmt(detalle.aporte.car) }} <span class="text-[9px] font-normal text-slate-500">g</span></p>
                                                </div>
                                                <div class="rounded-xl border border-rose-100 bg-rose-50/20 px-2.5 py-1.5 text-center transition hover:bg-rose-50 duration-150">
                                                    <span class="block text-[8px] font-black uppercase tracking-wider text-rose-600">Grasa</span>
                                                    <p class="text-[11px] font-black text-slate-800 mt-0.5">{{ fmt(detalle.aporte.gra) }} <span class="text-[9px] font-normal text-slate-500">g</span></p>
                                                </div>
                                            </div>
                                            <div v-else class="mt-4 rounded-xl border border-amber-100 bg-amber-50/10 p-2 text-center text-[10px] text-amber-700 font-bold">
                                                Este alimento no posee información nutricional cargada en el sistema.
                                            </div>
                                        </article>
                                    </div>
                                </section>
                            </div>

                            <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-10 px-5 text-center space-y-2">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-slate-500">El registro está vacío.</p>
                                <p class="text-xs text-slate-400">Este registro no contiene ningún alimento declarado.</p>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA 2: DECISIÓN Y VALIDACIÓN PROFESIONAL -->
                    <div class="lg:sticky lg:top-8 space-y-6">
                        
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Decisión Clínica</span>
                                <h3 class="mt-1 text-lg font-bold text-slate-800">Resultado de la Revisión</h3>
                                <p class="text-xs text-slate-500 font-semibold mt-0.5">Define la confiabilidad del registro para el control clínico del paciente.</p>
                            </div>

                            <form class="space-y-5" @submit.prevent="guardar">
                                
                                <!-- Custom Radio Cards for Decision -->
                                <div class="space-y-3">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">
                                        Dictamen del Registro
                                    </label>
                                    
                                    <div class="grid grid-cols-3 gap-2">
                                        <!-- Aceptado -->
                                        <button
                                            type="button"
                                            @click="form.est_vin = 'aceptado'"
                                            class="flex flex-col items-center p-3.5 rounded-2xl border text-center transition-all duration-200 focus:outline-none"
                                            :class="claseBotonEstado('aceptado')"
                                        >
                                            <div class="p-1.5 rounded-full" :class="form.est_vin === 'aceptado' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-extrabold mt-2">Validado</span>
                                        </button>

                                        <!-- Observado -->
                                        <button
                                            type="button"
                                            @click="form.est_vin = 'observado'"
                                            class="flex flex-col items-center p-3.5 rounded-2xl border text-center transition-all duration-200 focus:outline-none"
                                            :class="claseBotonEstado('observado')"
                                        >
                                            <div class="p-1.5 rounded-full" :class="form.est_vin === 'observado' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400'">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.03V12m0 3h.008v.008H12V15Z" />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-extrabold mt-2">Observar</span>
                                        </button>

                                        <!-- Rechazado -->
                                        <button
                                            type="button"
                                            @click="form.est_vin = 'rechazado'"
                                            class="flex flex-col items-center p-3.5 rounded-2xl border text-center transition-all duration-200 focus:outline-none"
                                            :class="claseBotonEstado('rechazado')"
                                        >
                                            <div class="p-1.5 rounded-full" :class="form.est_vin === 'rechazado' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-400'">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-extrabold mt-2">Rechazar</span>
                                        </button>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.est_vin" />
                                </div>

                                <!-- Dynamic Impact Warning Box -->
                                <div class="transition-all duration-300">
                                    <article 
                                        class="rounded-2xl border p-4 text-xs shadow-sm flex flex-col justify-between gap-3"
                                        :class="form.est_vin === 'aceptado' ? 'border-emerald-100 bg-emerald-50/20 text-emerald-800' 
                                               : form.est_vin === 'observado' ? 'border-amber-100 bg-amber-50/20 text-amber-800' 
                                               : form.est_vin === 'rechazado' ? 'border-rose-100 bg-rose-50/20 text-rose-800' 
                                               : 'border-slate-150 bg-slate-50/30 text-slate-600'"
                                    >
                                        <div>
                                            <p class="font-bold uppercase tracking-wider text-[9px] text-slate-400 mb-1">Impacto Analítico</p>
                                            <p class="font-bold text-slate-800 text-xs">{{ impactoAnaliticoValidacion.title }}</p>
                                            <p class="mt-1.5 leading-relaxed font-semibold">{{ impactoAnaliticoValidacion.mensaje }}</p>
                                        </div>
                                        <button
                                            v-if="form.est_vin"
                                            type="button"
                                            class="w-fit rounded-xl bg-white border border-slate-200 px-3.5 py-1.5 text-[10px] font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm"
                                            @click="insertarImpactoAnaliticoComoObservacion"
                                        >
                                            Copiar en Observaciones
                                        </button>
                                    </article>
                                </div>

                                <!-- Validation Date -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">
                                        Fecha de Validación
                                    </label>
                                    <input
                                        v-model="form.fec_vin"
                                        type="date"
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 text-xs font-bold text-slate-700 shadow-sm transition-all focus:bg-white focus:ring-4"
                                        :class="claseFocusInput"
                                    >
                                    <InputError class="mt-2" :message="form.errors.fec_vin" />
                                </div>

                                <!-- Observation textarea with Quick Templates -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">
                                            Observaciones <span v-if="requiereObservacion" class="text-rose-500 font-black">(Obligatoria)</span>
                                        </label>
                                        <button
                                            v-if="form.obs_vin"
                                            type="button"
                                            class="text-[10px] font-bold text-slate-400 hover:text-slate-600 transition"
                                            @click="limpiarObservacion"
                                        >
                                            Limpiar nota
                                        </button>
                                    </div>

                                    <!-- Quick template pills -->
                                    <div v-if="requiereObservacion" class="rounded-2xl border border-slate-100 bg-slate-50/40 p-3.5 space-y-2">
                                        <span class="block text-[8px] font-black uppercase tracking-widest text-slate-400">Plantillas Rápidas</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <button
                                                v-for="plantilla in plantillasObservacion"
                                                :key="plantilla.titulo"
                                                type="button"
                                                class="rounded-lg bg-white border border-slate-200 px-2 py-1 text-[9px] font-bold text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition shadow-sm"
                                                @click="insertarPlantilla(plantilla.texto)"
                                            >
                                                {{ plantilla.titulo }}
                                            </button>
                                        </div>
                                    </div>

                                    <textarea
                                        v-model="form.obs_vin"
                                        rows="6"
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 text-xs font-semibold leading-relaxed text-slate-700 shadow-sm transition-all focus:bg-white focus:ring-4"
                                        :class="claseFocusInput"
                                        placeholder="Escribe recomendaciones, observaciones del consumo o justificación para el paciente..."
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.obs_vin" />
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="motion-btn w-full rounded-2xl py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-md transition disabled:opacity-50"
                                    :class="form.est_vin === 'rechazado' ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-200'
                                           : form.est_vin === 'observado' ? 'bg-amber-600 hover:bg-amber-700 shadow-amber-200'
                                           : form.est_vin === 'aceptado' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200'
                                           : 'bg-slate-500 cursor-not-allowed'"
                                    :disabled="form.processing || !form.est_vin"
                                >
                                    <span v-if="form.processing">Guardando Cambios...</span>
                                    <span v-else-if="form.est_vin === 'aceptado'">Validar y Cerrar Registro</span>
                                    <span v-else-if="form.est_vin === 'observado'">Guardar como Observado</span>
                                    <span v-else-if="form.est_vin === 'rechazado'">Guardar como Rechazado</span>
                                    <span v-else>Guardar Dictamen</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

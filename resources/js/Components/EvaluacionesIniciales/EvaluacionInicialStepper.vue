<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    modo: {
        type: String,
        default: 'paciente',
        validator: (value) => ['paciente', 'nutricionista'].includes(value),
    },
    paciente: {
        type: Object,
        default: null,
    },
    nutricionista: {
        type: Object,
        default: null,
    },
    evaluacion: {
        type: Object,
        default: null,
    },
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: 'post',
        validator: (value) => ['post', 'put', 'patch'].includes(value),
    },
    volverUrl: {
        type: String,
        default: '',
    },
    enviarRevisionUrl: {
        type: String,
        default: '',
    },
});

const currentStep = ref(0);

const hoy = new Date().toISOString().slice(0, 10);

const form = useForm({
    fec_evi: props.evaluacion?.fec_evi || hoy,
    peso_evi: props.evaluacion?.peso_evi || props.paciente?.pes_pac || '',
    estatura_evi: props.evaluacion?.estatura_evi || props.paciente?.estatura_pac || '',
    objetivo_inicial_evi: props.evaluacion?.objetivo_inicial_evi || '',
    restricciones_evi: props.evaluacion?.restricciones_evi || props.paciente?.ale_pac || '',
    contexto_general_evi: props.evaluacion?.contexto_general_evi || '',
    obs_evi: props.evaluacion?.obs_evi || '',
});

const esPaciente = computed(() => props.modo === 'paciente');
const esNutricionista = computed(() => props.modo === 'nutricionista');

const tituloPrincipal = computed(() => {
    return esPaciente.value
        ? 'Comencemos con tu evaluación inicial'
        : 'Registrar evaluación inicial del paciente';
});

const subtituloPrincipal = computed(() => {
    return esPaciente.value
        ? 'Estos datos ayudarán a personalizar mejor tu proceso nutricional.'
        : 'Registra el punto de partida clínico del paciente antes de definir objetivos y planes.';
});

const steps = computed(() => [
    {
        title: esPaciente.value ? 'Bienvenida' : 'Inicio clínico',
        subtitle: esPaciente.value
            ? 'Te guiaremos paso a paso.'
            : 'Información base del checkpoint inicial.',
    },
    {
        title: esPaciente.value ? 'Tu cuerpo' : 'Datos antropométricos',
        subtitle: 'Peso, estatura e IMC inicial.',
    },
    {
        title: esPaciente.value ? 'Tu meta' : 'Objetivo inicial',
        subtitle: 'Qué se desea lograr al iniciar.',
    },
    {
        title: 'Restricciones',
        subtitle: 'Alergias, intolerancias o limitaciones.',
    },
    {
        title: esPaciente.value ? 'Tu contexto' : 'Contexto general',
        subtitle: 'Rutina, hábitos y situación inicial.',
    },
    {
        title: 'Confirmación',
        subtitle: 'Revisa antes de guardar.',
    },
]);

const progressPercentage = computed(() => {
    return Math.round(((currentStep.value + 1) / steps.value.length) * 100);
});

const pesoNumerico = computed(() => {
    if (!form.peso_evi) return null;

    const value = String(form.peso_evi).replace(',', '.');
    const number = Number(value);

    return Number.isFinite(number) && number > 0 ? number : null;
});

const estaturaEnMetros = computed(() => {
    if (!form.estatura_evi) return null;

    const value = String(form.estatura_evi).replace(',', '.');
    let number = Number(value);

    if (!Number.isFinite(number) || number <= 0) {
        return null;
    }

    if (number > 3) {
        number = number / 100;
    }

    return number;
});

const imcPreview = computed(() => {
    if (!pesoNumerico.value || !estaturaEnMetros.value) {
        return null;
    }

    const imc = pesoNumerico.value / (estaturaEnMetros.value * estaturaEnMetros.value);

    return imc.toFixed(2);
});

const categoriaImc = computed(() => {
    if (!imcPreview.value) {
        return 'Ingresa peso y estatura para calcularlo.';
    }

    const imc = Number(imcPreview.value);

    if (imc < 18.5) return 'Bajo peso';
    if (imc < 25) return 'Rango normal';
    if (imc < 30) return 'Sobrepeso';

    return 'Obesidad';
});

const imcColorClasses = computed(() => {
    if (!imcPreview.value) {
        return {
            bg: 'bg-slate-50 ring-slate-100',
            text: 'text-slate-600',
            badgeBg: 'bg-slate-100 text-slate-700',
            dot: 'bg-slate-400'
        };
    }
    const imc = Number(imcPreview.value);
    if (imc < 18.5) {
        return {
            bg: 'bg-amber-50 ring-amber-100/80',
            text: 'text-amber-800',
            badgeBg: 'bg-amber-100 text-amber-800',
            dot: 'bg-amber-500'
        };
    }
    if (imc < 25) {
        return {
            bg: 'bg-emerald-50 ring-emerald-100/80',
            text: 'text-emerald-800',
            badgeBg: 'bg-emerald-100 text-emerald-800',
            dot: 'bg-emerald-500'
        };
    }
    if (imc < 30) {
        return {
            bg: 'bg-amber-50 ring-amber-100/80',
            text: 'text-amber-800',
            badgeBg: 'bg-amber-100 text-amber-800',
            dot: 'bg-amber-500'
        };
    }
    return {
        bg: 'bg-rose-50 ring-rose-100/80',
        text: 'text-rose-800',
        badgeBg: 'bg-rose-100 text-rose-800',
        dot: 'bg-rose-500'
    };
});

const puedeRetroceder = computed(() => currentStep.value > 0);
const puedeAvanzar = computed(() => currentStep.value < steps.value.length - 1);
const estaEnUltimoPaso = computed(() => currentStep.value === steps.value.length - 1);

const puedeEnviarRevision = computed(() => {
    return Boolean(
        esPaciente.value &&
        props.evaluacion?.cod_evaluaciones_iniciales &&
        props.evaluacion?.est_evi === 'borrador' &&
        props.enviarRevisionUrl
    );
});

const nombrePaciente = computed(() => {
    return props.paciente?.usuario?.nombre_completo || 'Paciente';
});

const correoPaciente = computed(() => {
    return props.paciente?.usuario?.cor_usu || 'Correo no disponible';
});

// Paso 3: Sugerencias Rápidas de Objetivos
const objetivosRapidos = [
    { label: 'Bajar de peso', text: 'Bajar de peso y reducir el porcentaje de grasa corporal de forma saludable y progresiva, mejorando la relación con la comida y la energía diaria.' },
    { label: 'Masa muscular', text: 'Aumentar la masa muscular magra (hipertrofia), mejorar la fuerza física y optimizar la composición corporal general.' },
    { label: 'Hábitos saludables', text: 'Mejorar la calidad de alimentación diaria, aprender a planificar porciones y adoptar hábitos nutricionales saludables y sostenibles a largo plazo.' },
    { label: 'Control metabólico', text: 'Optimizar parámetros clínicos y metabólicos (glucosa, perfil lipídico, presión arterial) mediante una adecuada terapia nutricional.' },
    { label: 'Energía y Rendimiento', text: 'Incrementar los niveles de energía diaria y optimizar el rendimiento en actividades físicas o deportivas mediante nutrición periodizada.' }
];

const seleccionarObjetivo = (text) => {
    form.objetivo_inicial_evi = text;
};

// Paso 4: Restricciones Interactivas
const tieneRestricciones = ref('no');
const alergiasSeleccionadas = ref([]);
const intoleranciasSeleccionadas = ref([]);
const patologiasSeleccionadas = ref([]);
const otraRestriccion = ref('');

const alergiasOpciones = ['Maní / Frutos Secos', 'Mariscos y Pescados', 'Leche / Lácteos', 'Huevo', 'Trigo / Gluten', 'Soya'];
const intoleranciasOpciones = ['Lactosa', 'Fructosa', 'Gluten (Sensibilidad no celíaca)', 'Sorbitol'];
const patologiasOpciones = ['Diabetes Mellitus', 'Hipertensión Arterial', 'Resistencia a la Insulina', 'Colon Irritable / SII', 'Dislipidemia (Colesterol)', 'Hipotiroidismo'];

const actualizarRestriccionesTexto = () => {
    if (tieneRestricciones.value === 'no') {
        form.restricciones_evi = 'Ninguna restricción o cuidado especial registrado.';
        return;
    }

    let partes = [];
    if (alergiasSeleccionadas.value.length > 0) {
        partes.push(`• ALERGIAS: ${alergiasSeleccionadas.value.join(', ')}`);
    }
    if (intoleranciasSeleccionadas.value.length > 0) {
        partes.push(`• INTOLERANCIAS: ${intoleranciasSeleccionadas.value.join(', ')}`);
    }
    if (patologiasSeleccionadas.value.length > 0) {
        partes.push(`• CONDICIONES: ${patologiasSeleccionadas.value.join(', ')}`);
    }
    if (otraRestriccion.value.trim()) {
        partes.push(`• OTRAS OBSERVACIONES: ${otraRestriccion.value.trim()}`);
    }

    form.restricciones_evi = partes.length > 0 ? partes.join('\n') : '';
};

watch([tieneRestricciones, alergiasSeleccionadas, intoleranciasSeleccionadas, patologiasSeleccionadas, otraRestriccion], () => {
    actualizarRestriccionesTexto();
}, { deep: true });

// Paso 5: Hábitos Onboarding "Estilo Duolingo" para Pacientes
const habitosPaciente = ref({
    actividad: '',
    sueno: '',
    estres: '',
    agua: '',
    extra: ''
});

const actividadOpciones = [
    { value: 'Sedentario', label: 'Sedentario', desc: 'Poco o nada de ejercicio diario.' },
    { value: 'Moderado', label: 'Moderado', desc: 'Ejercicio ligero o caminatas 1-3 veces/semana.' },
    { value: 'Activo', label: 'Activo', desc: 'Entrenamiento estructurado 3-5 veces/semana.' },
    { value: 'Muy Activo', label: 'Muy Activo', desc: 'Deportista o trabajo físico diario intenso.' }
];

const suenoOpciones = [
    { value: 'Excelente', label: 'Excelente', desc: 'Duermo 7-8 horas continuas y descanso bien.' },
    { value: 'Regular', label: 'Regular', desc: 'Duermo 6-7 horas, a veces despierto cansado.' },
    { value: 'Mala', label: 'Mala', desc: 'Insomnio frecuente o duermo menos de 6 horas.' }
];

const estresOpciones = [
    { value: 'Bajo', label: 'Bajo', desc: 'Tranquilo, relajado y con bienestar general.' },
    { value: 'Moderado', label: 'Moderado', desc: 'Estrés laboral o de estudio que controlo.' },
    { value: 'Alto', label: 'Alto', desc: 'Nivel de estrés constante que afecta mi descanso.' }
];

const aguaOpciones = [
    { value: 'Suficiente', label: 'Suficiente', desc: 'Tomo 2 o más litros de agua pura al día.' },
    { value: 'Moderado', label: 'Moderado', desc: 'Tomo de 1 a 2 litros de agua pura al día.' },
    { value: 'Bajo', label: 'Bajo', desc: 'Tomo muy poca agua pura (menos de 1 litro al día).' }
];

const actualizarContextoTexto = () => {
    let partes = [];
    if (habitosPaciente.value.actividad) {
        const item = actividadOpciones.find(o => o.value === habitosPaciente.value.actividad);
        partes.push(`• Actividad Física: ${item.label} (${item.desc})`);
    }
    if (habitosPaciente.value.sueno) {
        const item = suenoOpciones.find(o => o.value === habitosPaciente.value.sueno);
        partes.push(`• Calidad de Sueño: ${item.label} (${item.desc})`);
    }
    if (habitosPaciente.value.estres) {
        const item = estresOpciones.find(o => o.value === habitosPaciente.value.estres);
        partes.push(`• Nivel de Estrés: ${item.label} (${item.desc})`);
    }
    if (habitosPaciente.value.agua) {
        const item = aguaOpciones.find(o => o.value === habitosPaciente.value.agua);
        partes.push(`• Consumo de Agua: ${item.label} (${item.desc})`);
    }
    if (habitosPaciente.value.extra.trim()) {
        partes.push(`• DETALLE ADICIONAL: ${habitosPaciente.value.extra.trim()}`);
    }

    form.contexto_general_evi = partes.length > 0 ? partes.join('\n') : '';
};

watch(habitosPaciente, () => {
    if (esPaciente.value) {
        actualizarContextoTexto();
    }
}, { deep: true });

onMounted(() => {
    // Inicialización y parseo de Restricciones del paciente
    const textoOriginal = props.evaluacion?.restricciones_evi || props.paciente?.ale_pac || '';
    if (textoOriginal && textoOriginal !== 'Ninguna' && !textoOriginal.toLowerCase().includes('ninguna restricción')) {
        tieneRestricciones.value = 'si';
        
        // Intentar parsear Alergias
        const matchAlergias = textoOriginal.match(/ALERGIAS:\s*([^\n•]+)/i);
        if (matchAlergias) {
            const items = matchAlergias[1].split(',').map(s => s.trim());
            alergiasSeleccionadas.value = items.filter(item => alergiasOpciones.includes(item));
        }
        
        // Intentar parsear Intolerancias
        const matchIntolerancias = textoOriginal.match(/INTOLERANCIAS:\s*([^\n•]+)/i);
        if (matchIntolerancias) {
            const items = matchIntolerancias[1].split(',').map(s => s.trim());
            intoleranciasSeleccionadas.value = items.filter(item => intoleranciasOpciones.includes(item));
        }

        // Intentar parsear Condiciones
        const matchPatologias = textoOriginal.match(/CONDICIONES:\s*([^\n•]+)/i);
        if (matchPatologias) {
            const items = matchPatologias[1].split(',').map(s => s.trim());
            patologiasSeleccionadas.value = items.filter(item => patologiasOpciones.includes(item));
        }

        // Intentar parsear Otras Observaciones
        const matchOtras = textoOriginal.match(/OTRAS OBSERVACIONES:\s*([\s\S]+)/i);
        if (matchOtras) {
            otraRestriccion.value = matchOtras[1].trim();
        } else if (!matchAlergias && !matchIntolerancias && !matchPatologias) {
            otraRestriccion.value = textoOriginal.trim();
        }
    } else {
        tieneRestricciones.value = 'no';
        form.restricciones_evi = 'Ninguna restricción o cuidado especial registrado.';
    }

    // Inicialización y parseo del Contexto en modo paciente
    const textoContexto = props.evaluacion?.contexto_general_evi || '';
    if (textoContexto && esPaciente.value) {
        for (const opt of actividadOpciones) {
            if (textoContexto.includes(opt.label)) habitosPaciente.value.actividad = opt.value;
        }
        for (const opt of suenoOpciones) {
            if (textoContexto.includes(opt.label)) habitosPaciente.value.sueno = opt.value;
        }
        for (const opt of estresOpciones) {
            if (textoContexto.includes(opt.label)) habitosPaciente.value.estres = opt.value;
        }
        for (const opt of aguaOpciones) {
            if (textoContexto.includes(opt.label)) habitosPaciente.value.agua = opt.value;
        }
        
        const matchExtra = textoContexto.match(/DETALLE ADICIONAL:\s*([\s\S]+)/i);
        if (matchExtra) {
            habitosPaciente.value.extra = matchExtra[1].trim();
        } else {
            const lineasExtra = textoContexto.split('\n').filter(l => !l.trim().startsWith('•'));
            if (lineasExtra.length > 0) {
                habitosPaciente.value.extra = lineasExtra.join('\n').trim();
            }
        }
    }
});

const siguiente = () => {
    if (puedeAvanzar.value) {
        currentStep.value += 1;
    }
};

const anterior = () => {
    if (puedeRetroceder.value) {
        currentStep.value -= 1;
    }
};

const irAlPaso = (index) => {
    if (index <= currentStep.value) {
        currentStep.value = index;
    }
};

const submit = () => {
    const options = {
        preserveScroll: true,
    };

    if (props.method === 'put') {
        form.put(props.action, options);
        return;
    }

    if (props.method === 'patch') {
        form.patch(props.action, options);
        return;
    }

    form.post(props.action, options);
};

const enviarRevision = () => {
    if (!props.enviarRevisionUrl) return;

    router.patch(props.enviarRevisionUrl, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-50 via-white to-sky-50 shadow-sm ring-1 ring-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">
                            Evaluación inicial
                        </p>
                        <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">
                            {{ tituloPrincipal }}
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                            {{ subtituloPrincipal }}
                        </p>

                        <div v-if="paciente" class="mt-4 rounded-2xl bg-white/80 p-4 text-sm text-slate-700 ring-1 ring-slate-100">
                            <p class="font-semibold text-slate-900">
                                {{ nombrePaciente }}
                            </p>
                            <p class="text-slate-500">
                                {{ correoPaciente }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Progreso
                        </p>
                        <p class="mt-2 text-4xl font-bold text-emerald-600">
                            {{ progressPercentage }}%
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Paso {{ currentStep + 1 }} de {{ steps.length }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="hidden gap-3 md:grid md:grid-cols-6">
                <button
                    v-for="(step, index) in steps"
                    :key="step.title"
                    type="button"
                    class="rounded-2xl border p-3 text-left transition"
                    :class="[
                        index === currentStep
                            ? 'border-emerald-500 bg-emerald-50 text-emerald-800'
                            : index < currentStep
                                ? 'border-emerald-200 bg-white text-slate-700 hover:bg-emerald-50'
                                : 'border-slate-200 bg-slate-50 text-slate-400',
                    ]"
                    @click="irAlPaso(index)"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                            :class="[
                                index <= currentStep
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-slate-200 text-slate-500',
                            ]"
                        >
                            {{ index + 1 }}
                        </span>
                        <span class="text-sm font-semibold">
                            {{ step.title }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs leading-5">
                        {{ step.subtitle }}
                    </p>
                </button>
            </div>

            <div class="md:hidden">
                <div class="rounded-2xl bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-800">
                        Paso {{ currentStep + 1 }}: {{ steps[currentStep].title }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-700">
                        {{ steps[currentStep].subtitle }}
                    </p>
                </div>
            </div>
        </section>

        <form @submit.prevent="submit" class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
            <div class="p-6 sm:p-8">
                <!-- PASO 0: Bienvenida / Inicio Clínico -->
                <div v-if="currentStep === 0" class="space-y-6">
                    <div class="rounded-3xl bg-gradient-to-br from-emerald-500/5 to-teal-500/5 p-6 border border-emerald-500/10">
                        <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.188.904z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.071 4.929l-.707.707-.707-.707.707-.707.707.707zM12 3a1 1 0 011 1v1a1 1 0 11-2 0V4a1 1 0 011-1z" />
                            </svg>
                            {{ esPaciente ? '¡Te damos la bienvenida a tu proceso!' : 'Punto de partida y checkpoint clínico' }}
                        </h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            <template v-if="esPaciente">
                                Completa esta información inicial con total tranquilidad. No se trata de un examen, sino de una guía visual para entender tu estado actual y ayudarte a alcanzar tus metas de forma saludable y personalizada.
                            </template>
                            <template v-else>
                                Esta evaluación representa el estado inicial del paciente al comenzar el proceso clínico. Servirá como línea base (baseline) para definir objetivos inteligentes, diseñar planes nutricionales y realizar seguimientos comparativos eficaces.
                            </template>
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200/80 p-5 bg-white shadow-xs">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 text-lg font-bold">
                                    <svg class="h-5 w-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </span>
                                <p class="text-sm font-bold text-slate-950">Información básica</p>
                            </div>
                            <ul class="mt-4 space-y-2.5 text-xs text-slate-600">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">✓</span> Peso y estatura inicial real.
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">✓</span> IMC calculado automáticamente con rangos de salud.
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">✓</span> Objetivos, alergias y rutinas diarias.
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-2xl border border-slate-200/80 p-5 bg-white shadow-xs">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-800 text-lg font-bold">
                                    <svg class="h-5 w-5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </span>
                                <p class="text-sm font-bold text-slate-950">Consejo útil</p>
                            </div>
                            <p class="mt-4 text-xs leading-5 text-slate-600">
                                Intenta ser lo más sincero y preciso posible con los datos. Si no conoces algún valor exacto, puedes usar un estimado. El nutricionista podrá ajustar cualquier información posteriormente durante la consulta presencial.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PASO 1: Datos Antropométricos -->
                <div v-if="currentStep === 1" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                            {{ esPaciente ? 'Datos sobre tu cuerpo' : 'Datos antropométricos iniciales' }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Registra el peso y la estatura para conocer el estado nutricional de partida.
                        </p>
                    </div>

                    <!-- Ficha Médica de Referencia (Con botón Restaurar) -->
                    <div v-if="paciente" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/50">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Datos actuales en ficha médica</p>
                                <p class="text-sm font-bold text-slate-700 mt-0.5">
                                    {{ paciente.pes_pac ? `${paciente.pes_pac} kg` : 'Sin peso' }} &nbsp;•&nbsp; 
                                    {{ paciente.estatura_pac ? `${paciente.estatura_pac} m` : 'Sin estatura' }}
                                </p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="form.peso_evi = paciente.pes_pac; form.estatura_evi = paciente.estatura_pac"
                            class="rounded-xl bg-white px-3 py-2 text-xs font-bold text-emerald-600 shadow-xs ring-1 ring-slate-200 hover:bg-slate-50 active:scale-95 transition-all"
                        >
                            Importar valores de la Ficha
                        </button>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">
                        <div>
                            <label for="fec_evi" class="block text-xs font-bold text-slate-700">
                                Fecha de evaluación
                            </label>
                            <input
                                id="fec_evi"
                                v-model="form.fec_evi"
                                type="date"
                                class="mt-2 block w-full rounded-2xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                            />
                            <InputError class="mt-2" :message="form.errors.fec_evi" />
                        </div>

                        <div>
                            <label for="peso_evi" class="block text-xs font-bold text-slate-700">
                                Peso inicial (kg)
                            </label>
                            <div class="relative mt-2">
                                <input
                                    id="peso_evi"
                                    v-model="form.peso_evi"
                                    type="number"
                                    step="0.01"
                                    min="20"
                                    max="300"
                                    placeholder="Ej. 68.5"
                                    class="block w-full rounded-2xl border-slate-300 pr-12 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                />
                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-xs font-bold text-slate-400">kg</span>
                            </div>
                            <InputError class="mt-2" :message="form.errors.peso_evi" />
                        </div>

                        <div>
                            <label for="estatura_evi" class="block text-xs font-bold text-slate-700">
                                Estatura (m o cm)
                            </label>
                            <div class="relative mt-2">
                                <input
                                    id="estatura_evi"
                                    v-model="form.estatura_evi"
                                    type="number"
                                    step="0.01"
                                    min="0.5"
                                    max="250"
                                    placeholder="Ej. 1.72 o 172"
                                    class="block w-full rounded-2xl border-slate-300 pr-12 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                />
                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-xs font-bold text-slate-400">m / cm</span>
                            </div>
                            <InputError class="mt-2" :message="form.errors.estatura_evi" />
                        </div>
                    </div>

                    <!-- Caja de IMC Estilizada con Colores Semánticos -->
                    <div class="rounded-3xl p-6 ring-1 transition-all duration-300" :class="imcColorClasses.bg">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Índice de Masa Corporal (IMC) Estimado
                        </p>
                        <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                <p class="text-4xl font-extrabold" :class="imcColorClasses.text">
                                    {{ imcPreview || '--' }}
                                </p>
                                <span 
                                    v-if="imcPreview"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold shadow-xs border border-white/50"
                                    :class="imcColorClasses.badgeBg"
                                >
                                    <span class="h-2 w-2 rounded-full" :class="imcColorClasses.dot"></span>
                                    {{ categoriaImc }}
                                </span>
                            </div>
                            <p class="max-w-md text-xs leading-relaxed text-slate-600">
                                El IMC es un indicador referencial que calcula la relación entre peso y estatura. Recuerda que no distingue masa grasa de masa muscular; la interpretación final corresponde al especialista.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: Objetivo Inicial -->
                <div v-if="currentStep === 2" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ esPaciente ? '¿Cuál es tu objetivo principal?' : 'Objetivo inicial del proceso' }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Describe de forma breve qué se busca lograr en el acompañamiento nutricional.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <textarea
                            id="objetivo_inicial_evi"
                            v-model="form.objetivo_inicial_evi"
                            rows="6"
                            placeholder="Ej. Mejorar hábitos alimenticios, reducir porcentaje graso de forma saludable, aumentar masa muscular magra, mejorar rendimiento físico..."
                            class="block w-full rounded-2xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm leading-relaxed"
                        />
                        <InputError :message="form.errors.objetivo_inicial_evi" />

                        <!-- Sugerencias Rápidas -->
                        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-200/50">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                Sugerencias Rápidas (Haz clic para completar)
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="obj in objetivosRapidos"
                                    :key="obj.label"
                                    type="button"
                                    @click="seleccionarObjetivo(obj.text)"
                                    class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs ring-1 ring-slate-200 hover:bg-emerald-50 hover:text-emerald-800 hover:ring-emerald-300 transition-all active:scale-95 duration-100"
                                >
                                    {{ obj.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 3: Restricciones o Cuidados -->
                <div v-if="currentStep === 3" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Restricciones o cuidados importantes
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Registra si existen alergias, intolerancias o patologías que condicionen la pauta de alimentación.
                        </p>
                    </div>

                    <!-- Selector Sí / No Premium -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <button
                            type="button"
                            @click="tieneRestricciones = 'no'"
                            class="flex flex-col items-center justify-center p-5 rounded-2xl border text-center transition-all duration-200 shadow-xs"
                            :class="tieneRestricciones === 'no' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-800 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        >
                            <svg class="h-6 w-6 text-emerald-600 mb-1.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-bold text-sm">No, ninguna restricción</span>
                            <span class="text-[10px] text-slate-500 mt-1">Sin alergias, intolerancias ni condiciones de cuidado</span>
                        </button>

                        <button
                            type="button"
                            @click="tieneRestricciones = 'si'"
                            class="flex flex-col items-center justify-center p-5 rounded-2xl border text-center transition-all duration-200 shadow-xs"
                            :class="tieneRestricciones === 'si' ? 'border-amber-500 bg-amber-50/40 text-amber-800 ring-2 ring-amber-500/20' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        >
                            <svg class="h-6 w-6 text-amber-600 mb-1.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="font-bold text-sm">Sí, registrar restricciones</span>
                            <span class="text-[10px] text-slate-500 mt-1">Alergias alimentarias, intolerancias, diabetes u otras</span>
                        </button>
                    </div>

                    <!-- Formulario de Restricciones (Si "Sí" es elegido) -->
                    <div v-if="tieneRestricciones === 'si'" class="rounded-3xl bg-slate-50/60 p-6 border border-slate-200/50 space-y-6 transition-all duration-300">
                        <div class="grid gap-6 sm:grid-cols-3">
                            <!-- Alergias -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-200/60 pb-1.5 flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    Alergias Alimentarias
                                </h4>
                                <div class="space-y-2">
                                    <label v-for="opc in alergiasOpciones" :key="opc" class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-600 select-none hover:text-slate-800 transition">
                                        <input
                                            type="checkbox"
                                            v-model="alergiasSeleccionadas"
                                            :value="opc"
                                            class="rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
                                        />
                                        <span>{{ opc }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Intolerancias -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-200/60 pb-1.5 flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 9.172V5L8 4z" />
                                    </svg>
                                    Intolerancias
                                </h4>
                                <div class="space-y-2">
                                    <label v-for="opc in intoleranciasOpciones" :key="opc" class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-600 select-none hover:text-slate-800 transition">
                                        <input
                                            type="checkbox"
                                            v-model="intoleranciasSeleccionadas"
                                            :value="opc"
                                            class="rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
                                        />
                                        <span>{{ opc }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Condiciones Clínicas -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-200/60 pb-1.5 flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    Condiciones Clínicas
                                </h4>
                                <div class="space-y-2">
                                    <label v-for="opc in patologiasOpciones" :key="opc" class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-600 select-none hover:text-slate-800 transition">
                                        <input
                                            type="checkbox"
                                            v-model="patologiasSeleccionadas"
                                            :value="opc"
                                            class="rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
                                        />
                                        <span>{{ opc }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Campo manual extra -->
                        <div class="border-t border-slate-200/60 pt-4">
                            <label for="otra_restriccion" class="block text-xs font-bold text-slate-700 mb-1.5">
                                Detalle adicional o indicaciones específicas:
                            </label>
                            <textarea
                                id="otra_restriccion"
                                v-model="otraRestriccion"
                                rows="3"
                                placeholder="Ej. Evitar sal agregada por recomendación cardiológica, prefiere dieta baja en carbohidratos, etc."
                                class="block w-full rounded-2xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-xs leading-relaxed"
                            />
                        </div>

                        <!-- Caja de Vista Previa Clínica -->
                        <div class="rounded-2xl bg-white p-4 border border-slate-200 text-xs">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Registro estructurado final para la Ficha
                            </p>
                            <pre class="font-sans whitespace-pre-wrap leading-relaxed text-slate-700 bg-slate-50/50 p-2.5 rounded-xl border border-slate-100">{{ form.restricciones_evi || 'Sin opciones seleccionadas.' }}</pre>
                        </div>
                    </div>

                    <!-- Mensaje No Restricciones -->
                    <div v-else class="rounded-3xl bg-emerald-50/50 p-6 border border-emerald-500/10 text-center space-y-2">
                        <svg class="h-10 w-10 text-emerald-600 mx-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="text-sm font-bold text-emerald-800">¡Excelente estado!</h4>
                        <p class="text-xs text-emerald-600 max-w-md mx-auto">
                            Has confirmado que no presentas alergias, intolerancias alimentarias o patologías de cuidado clínico. La pauta se diseñará con flexibilidad completa.
                        </p>
                    </div>

                    <InputError :message="form.errors.restricciones_evi" />
                </div>

                <!-- PASO 4: Tu Contexto / Hábitos (Duolingo para Pacientes, Tradicional para Clinico) -->
                <div v-if="currentStep === 4" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ esPaciente ? 'Cuéntanos sobre tu rutina diaria' : 'Contexto general del paciente' }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ esPaciente ? 'Selecciona las opciones que mejor describan tus hábitos y estilo de vida.' : 'Registra hábitos, horarios y condiciones generales de salud del paciente.' }}
                        </p>
                    </div>

                    <!-- FLUJO PACIENTE: Estilo Duolingo interactivo -->
                    <div v-if="esPaciente" class="space-y-8">
                        <!-- Actividad Física -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <svg class="h-4.5 w-4.5 text-emerald-600 inline shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                ¿Cuál es tu nivel de actividad física habitual?
                            </h3>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <button
                                    v-for="opc in actividadOpciones"
                                    :key="opc.value"
                                    type="button"
                                    @click="habitosPaciente.actividad = opc.value"
                                    class="flex flex-col items-center p-4 rounded-2xl border text-center transition-all duration-200 hover:bg-slate-50 active:scale-95 shadow-xs"
                                    :class="habitosPaciente.actividad === opc.value ? 'border-emerald-500 bg-emerald-50/50 text-emerald-800 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-white text-slate-600'"
                                >
                                    <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-100/80 mb-2 transition-colors">
                                        <!-- Sedentario: Couch SVG -->
                                        <svg v-if="opc.value === 'Sedentario'" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12h-2v-2a3 3 0 00-3-3H9a3 3 0 00-3 3v2H4a2 2 0 00-2 2v2a2 2 0 002 2h16a2 2 0 002-2v-2a2 2 0 00-2-2zM4 14h16M7 16v2M17 16v2" />
                                        </svg>
                                        <!-- Moderado: Walking SVG -->
                                        <svg v-else-if="opc.value === 'Moderado'" class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 5a2 2 0 11-4 0 2 2 0 014 0zM9 20h2l1-4.5M10.5 9.5L9 15.5H7.5M14 9.5l.5 3M11 11h2.5L15 15.5h1.5" />
                                        </svg>
                                        <!-- Activo: Running SVG -->
                                        <svg v-else-if="opc.value === 'Activo'" class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <!-- Muy Activo: Fire SVG -->
                                        <svg v-else-if="opc.value === 'Muy Activo'" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-bold text-xs">{{ opc.label }}</span>
                                    <span class="text-[10px] text-slate-500 mt-1 leading-normal">{{ opc.desc }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Calidad de Sueño -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <svg class="h-4.5 w-4.5 text-emerald-600 shrink-0 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                                ¿Cómo describirías la calidad de tu descanso/sueño?
                            </h3>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <button
                                    v-for="opc in suenoOpciones"
                                    :key="opc.value"
                                    type="button"
                                    @click="habitosPaciente.sueno = opc.value"
                                    class="flex flex-col items-center p-4 rounded-2xl border text-center transition-all duration-200 hover:bg-slate-50 active:scale-95 shadow-xs"
                                    :class="habitosPaciente.sueno === opc.value ? 'border-emerald-500 bg-emerald-50/50 text-emerald-800 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-white text-slate-600'"
                                >
                                    <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-100/80 mb-2">
                                        <!-- Excelente: Crescent Moon with Stars -->
                                        <svg v-if="opc.value === 'Excelente'" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                        </svg>
                                        <!-- Regular: Cloud -->
                                        <svg v-else-if="opc.value === 'Regular'" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                        </svg>
                                        <!-- Mala: Exclamation Cloud -->
                                        <svg v-else-if="opc.value === 'Mala'" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <span class="font-bold text-xs">{{ opc.label }}</span>
                                    <span class="text-[10px] text-slate-500 mt-1 leading-normal">{{ opc.desc }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Estrés -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <svg class="h-4.5 w-4.5 text-emerald-600 shrink-0 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                ¿Cómo describirías tu nivel de estrés diario?
                            </h3>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <button
                                    v-for="opc in estresOpciones"
                                    :key="opc.value"
                                    type="button"
                                    @click="habitosPaciente.estres = opc.value"
                                    class="flex flex-col items-center p-4 rounded-2xl border text-center transition-all duration-200 hover:bg-slate-50 active:scale-95 shadow-xs"
                                    :class="habitosPaciente.estres === opc.value ? 'border-emerald-500 bg-emerald-50/50 text-emerald-800 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-white text-slate-600'"
                                >
                                    <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-100/80 mb-2">
                                        <!-- Bajo: Smiley face -->
                                        <svg v-if="opc.value === 'Bajo'" class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <!-- Moderado: Neutral face -->
                                        <svg v-else-if="opc.value === 'Moderado'" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z M9 15h6" />
                                        </svg>
                                        <!-- Alto: Warning/Stress shield -->
                                        <svg v-else-if="opc.value === 'Alto'" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-bold text-xs">{{ opc.label }}</span>
                                    <span class="text-[10px] text-slate-500 mt-1 leading-normal">{{ opc.desc }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Consumo de Agua -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <svg class="h-4.5 w-4.5 text-emerald-600 shrink-0 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547" />
                                </svg>
                                ¿Cuál es tu ingesta habitual de agua al día?
                            </h3>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <button
                                    v-for="opc in aguaOpciones"
                                    :key="opc.value"
                                    type="button"
                                    @click="habitosPaciente.agua = opc.value"
                                    class="flex flex-col items-center p-4 rounded-2xl border text-center transition-all duration-200 hover:bg-slate-50 active:scale-95 shadow-xs"
                                    :class="habitosPaciente.agua === opc.value ? 'border-emerald-500 bg-emerald-50/50 text-emerald-800 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-white text-slate-600'"
                                >
                                    <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-100/80 mb-2">
                                        <!-- Suficiente: Full glass -->
                                        <svg v-if="opc.value === 'Suficiente'" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 9.172V5L8 4z" />
                                        </svg>
                                        <!-- Moderado: Half full glass -->
                                        <svg v-else-if="opc.value === 'Moderado'" class="h-5 w-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v4" />
                                        </svg>
                                        <!-- Bajo: Crossed Drop -->
                                        <svg v-else-if="opc.value === 'Bajo'" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </div>
                                    <span class="font-bold text-xs">{{ opc.label }}</span>
                                    <span class="text-[10px] text-slate-500 mt-1 leading-normal">{{ opc.desc }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Notas adicionales del paciente -->
                        <div class="pt-4 border-t border-slate-200/50">
                            <label for="habito_extra" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-600 shrink-0 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                ¿Hay algún detalle adicional que desees indicarle a tu nutricionista sobre tus rutinas?
                            </label>
                            <textarea
                                id="habito_extra"
                                v-model="habitosPaciente.extra"
                                rows="3"
                                placeholder="Ej. Horarios de comida, trabajo nocturno por turnos, snacks de preferencia..."
                                class="block w-full rounded-2xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-xs leading-relaxed"
                            />
                        </div>
                    </div>

                    <!-- FLUJO PROFESIONAL: Textareas tradicionales y observaciones clínicas -->
                    <div v-else class="space-y-6">
                        <textarea
                            id="contexto_general_evi"
                            v-model="form.contexto_general_evi"
                            rows="7"
                            placeholder="Ej. Horarios de comida, actividad física, sueño, trabajo, estrés, hábitos frecuentes..."
                            class="block w-full rounded-2xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm leading-relaxed"
                        />
                        <InputError :message="form.errors.contexto_general_evi" />

                        <div>
                            <label for="obs_evi" class="block text-xs font-bold text-slate-700">
                                Observaciones clínicas adicionales (Uso exclusivo del Nutricionista)
                            </label>
                            <textarea
                                id="obs_evi"
                                v-model="form.obs_evi"
                                rows="4"
                                placeholder="Registra comentarios adicionales, impresiones diagnósticas o aspectos no cubiertos por la evaluación básica."
                                class="mt-2 block w-full rounded-2xl border-slate-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-sm leading-relaxed"
                            />
                            <InputError class="mt-2" :message="form.errors.obs_evi" />
                        </div>
                    </div>
                </div>

                <!-- PASO 5: Confirmación / Resumen Clínico Digital -->
                <div v-if="currentStep === 5" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Revisión final de la evaluación inicial
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Revisa que todos los parámetros estén correctos antes de registrar oficialmente el checkpoint.
                        </p>
                    </div>

                    <!-- Scorecard Grid Premium -->
                    <div class="grid gap-5 md:grid-cols-2">
                        <!-- Card 1: Composición Corporal -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider border-b border-slate-100 pb-2">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                    </svg>
                                    Composición Corporal
                                </h3>
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">Peso Inicial</p>
                                        <p class="text-lg font-extrabold text-slate-800 mt-0.5">{{ form.peso_evi || '--' }} kg</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">Estatura</p>
                                        <p class="text-lg font-extrabold text-slate-800 mt-0.5">{{ form.estatura_evi || '--' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-xs text-slate-500">IMC Estimado:</span>
                                <span 
                                    v-if="imcPreview"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold"
                                    :class="imcColorClasses.badgeBg"
                                >
                                    <strong>{{ imcPreview }}</strong> ({{ categoriaImc }})
                                </span>
                                <span v-else class="text-xs font-bold text-slate-400">Sin datos de IMC</span>
                            </div>
                        </div>

                        <!-- Card 2: Objetivo Inicial -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider border-b border-slate-100 pb-2">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Objetivo Clínico
                                </h3>
                                <div class="mt-3 bg-emerald-50/20 border-l-4 border-emerald-500 p-3 rounded-r-xl">
                                    <p class="text-xs text-slate-600 leading-relaxed italic">
                                        "{{ form.objetivo_inicial_evi || 'Sin objetivo inicial registrado.' }}"
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Restricciones y Alergias -->
                        <div 
                            class="rounded-2xl border bg-white p-5 shadow-xs flex flex-col justify-between"
                            :class="tieneRestricciones === 'si' ? 'border-amber-200 bg-amber-50/5' : 'border-slate-200'"
                        >
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider border-b border-slate-100 pb-2">
                                    <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Restricciones alimenticias
                                </h3>
                                <div class="mt-3">
                                    <p class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">
                                        {{ form.restricciones_evi || 'Sin restricciones registradas.' }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-100/50 flex justify-end">
                                <span 
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="tieneRestricciones === 'si' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                                >
                                    {{ tieneRestricciones === 'si' ? 'Requiere cuidados' : 'Pauta libre' }}
                                </span>
                            </div>
                        </div>

                        <!-- Card 4: Estilo de Vida y Contexto -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider border-b border-slate-100 pb-2">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Estilo de Vida & Contexto
                                </h3>
                                <div class="mt-3">
                                    <p class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">
                                        {{ form.contexto_general_evi || 'Sin información de hábitos registrada.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones (Solo si es Nutricionista y hay texto) -->
                    <div v-if="esNutricionista && form.obs_evi" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-xs">
                        <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider border-b border-slate-200 pb-2">
                            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Observaciones Profesionales Adicionales
                        </h3>
                        <p class="text-xs text-slate-600 mt-3 whitespace-pre-line leading-relaxed">
                            {{ form.obs_evi }}
                        </p>
                    </div>

                    <!-- Nota Informativa de Guardado -->
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-200/60 text-xs text-slate-500">
                        <p class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span>Al confirmar, la evaluación se registrará en el sistema. Si eres el paciente, tu nutricionista podrá revisarla en tu próximo control clínico.</span>
                        </p>
                    </div>

                    <InputError class="mt-2" :message="form.errors.evaluacion" />
                    <InputError class="mt-2" :message="form.errors.cod_pacientes_evi" />
                    <InputError class="mt-2" :message="form.errors.cod_nutricionistas_evi" />
                </div>
            </div>

            <!-- BOTONES DE NAVEGACIÓN Y ACCIÓN -->
            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <div class="flex gap-3">
                    <SecondaryButton
                        v-if="volverUrl"
                        type="button"
                        @click="router.visit(volverUrl)"
                    >
                        Volver
                    </SecondaryButton>

                    <SecondaryButton
                        v-if="puedeRetroceder"
                        type="button"
                        @click="anterior"
                    >
                        Anterior
                    </SecondaryButton>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <SecondaryButton
                        v-if="puedeEnviarRevision"
                        type="button"
                        @click="enviarRevision"
                    >
                        Enviar a revisión
                    </SecondaryButton>

                    <PrimaryButton
                        v-if="puedeAvanzar"
                        type="button"
                        @click="siguiente"
                    >
                        Siguiente
                    </PrimaryButton>

                    <PrimaryButton
                        v-if="estaEnUltimoPaso"
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ esPaciente ? 'Guardar mi evaluación' : 'Guardar evaluación inicial' }}
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </div>
</template>
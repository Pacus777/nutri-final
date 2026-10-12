<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ClinicalAlert from '@/Components/NutricionistaUI/ClinicalAlert.vue';
import ClinicalBadge from '@/Components/NutricionistaUI/ClinicalBadge.vue';
import ClinicalPageHeader from '@/Components/NutricionistaUI/ClinicalPageHeader.vue';
import ClinicalSection from '@/Components/NutricionistaUI/ClinicalSection.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    snapshot: {
        type: Object,
        required: true,
    },
    mi_evaluacion: {
        type: Object,
        default: null,
    },
    catalogo_tipos_riesgo: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    opcion_riesgo: props.mi_evaluacion?.estado_etiqueta_erp === 'insuficiente'
        ? 'insuficiente'
        : (props.mi_evaluacion?.nivel_riesgo_erp || ''),
    tipo_riesgo_erp: props.mi_evaluacion?.tipo_riesgo_erp || '',
    observacion_profesional_erp: props.mi_evaluacion?.observacion_profesional_erp || '',
    version_criterio_erp: props.mi_evaluacion?.version_criterio_erp || 'v1.0',
});

const submit = () => {
    form.post(route('nutricionista.etiquetado-riesgo.store', props.snapshot.cod_features_nutricionales), {
        preserveScroll: true,
    });
};

const esInformacionInsuficiente = computed(() => form.opcion_riesgo === 'insuficiente');

const formatoNumero = (val, decimales = 1) => {
    if (val === null || val === undefined || isNaN(val)) return 'N/D';
    return Number(val).toLocaleString('es-ES', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
};
</script>

<template>
    <Head :title="`Evaluar Caso Clínico #${snapshot.cod_features_nutricionales}`" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <ClinicalPageHeader
                :title="`Valoración Profesional de Riesgo Nutricional`"
                :subtitle="`Evaluación independiente de vector de features para paciente ${snapshot.paciente.nombre_completo}.`"
            >
                <template #actions>
                    <Link
                        :href="route('nutricionista.etiquetado-riesgo.index')"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        &larr; Volver al listado
                    </Link>
                </template>
            </ClinicalPageHeader>

            <!-- Alerta de Blind Assessment / Prevención de sesgo -->
            <ClinicalAlert
                tone="info"
                title="Protocolo de Evaluación Independiente (Prevención de Sesgo Cognitivo)"
            >
                Esta pantalla presenta únicamente variables clínicas y nutricionales observadas objetivamente durante la ventana. Las sugerencias heurísticas y predicciones algorítmicas de agrupamiento se encuentran estrictamente ocultas para garantizar un target de entrenamiento supervisado puro y riguroso.
            </ClinicalAlert>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Izquierda: Datos Objetivos del Paciente y Ventana (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Resumen del Paciente y Período -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center justify-between">
                            <span>Datos del Paciente y Período Evaluado</span>
                            <span class="text-xs font-mono text-gray-400">ID Snapshot: #{{ snapshot.cod_features_nutricionales }}</span>
                        </h3>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div class="bg-gray-50 p-3.5 rounded-xl">
                                <span class="text-xs text-gray-500 block">Paciente</span>
                                <span class="font-semibold text-gray-900 block mt-0.5">{{ snapshot.paciente.nombre_completo }}</span>
                            </div>
                            <div class="bg-gray-50 p-3.5 rounded-xl">
                                <span class="text-xs text-gray-500 block">Edad / Sexo</span>
                                <span class="font-semibold text-gray-900 block mt-0.5">
                                    {{ snapshot.paciente.edad ? `${snapshot.paciente.edad} años` : 'N/D' }} / {{ snapshot.paciente.sexo ? (snapshot.paciente.sexo === 'M' ? 'Masc.' : 'Fem.') : 'N/D' }}
                                </span>
                            </div>
                            <div class="bg-gray-50 p-3.5 rounded-xl">
                                <span class="text-xs text-gray-500 block">IMC Antropométrico</span>
                                <span class="font-semibold text-gray-900 block mt-0.5">
                                    {{ snapshot.paciente.imc ? `${formatoNumero(snapshot.paciente.imc, 1)} kg/m²` : 'No disponible' }}
                                </span>
                            </div>
                            <div class="bg-gray-50 p-3.5 rounded-xl">
                                <span class="text-xs text-gray-500 block">Cambio de Peso</span>
                                <span class="font-semibold text-gray-900 block mt-0.5">
                                    {{ snapshot.paciente.cambio_peso !== null ? `${formatoNumero(snapshot.paciente.cambio_peso, 1)} kg` : 'Sin cambio' }}
                                </span>
                            </div>
                        </div>

                        <!-- Métricas de adherencia de la ventana -->
                        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-xs text-gray-500 block">Fechas Ventana</span>
                                <span class="font-medium text-gray-800 text-xs mt-0.5 block">{{ snapshot.periodo.fec_inicio }} al {{ snapshot.periodo.fec_fin }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500 block">Días Registrados</span>
                                <span class="font-bold text-gray-900 mt-0.5 block">{{ snapshot.adherencia.dias_registrados }} de {{ snapshot.adherencia.total_ventana }} días</span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500 block">Adherencia Registro</span>
                                <span class="font-bold mt-0.5 block" :class="snapshot.adherencia.adherencia_pct >= 70 ? 'text-emerald-700' : 'text-amber-700'">
                                    {{ snapshot.adherencia.adherencia_pct }}%
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500 block">Validación Profesional</span>
                                <span class="font-medium text-gray-700 mt-0.5 block">{{ snapshot.adherencia.ratio_validacion }}% validado</span>
                            </div>
                        </div>
                    </div>

                    <!-- Ingesta Calórica y Macronutrientes -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                        <h3 class="text-base font-semibold text-gray-900 pb-2 border-b border-gray-100">
                            Ingesta Nutricional y Distribución de Macronutrientes
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="p-4 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50/40 border border-amber-100">
                                <span class="text-xs font-medium text-amber-900 block">Promedio Energético</span>
                                <div class="text-2xl font-bold text-amber-950 mt-1">
                                    {{ formatoNumero(snapshot.nutricion.promedio_calorias, 0) }} <span class="text-xs font-normal text-amber-800">kcal/día</span>
                                </div>
                                <span class="text-xs text-amber-700 block mt-1">Desviación: &plusmn;{{ formatoNumero(snapshot.nutricion.std_calorias, 0) }} kcal</span>
                            </div>

                            <div class="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50/40 border border-blue-100">
                                <span class="text-xs font-medium text-blue-900 block">Proteína</span>
                                <div class="text-2xl font-bold text-blue-950 mt-1">
                                    {{ formatoNumero(snapshot.nutricion.promedio_proteina_g, 1) }} <span class="text-xs font-normal text-blue-800">g/día</span>
                                </div>
                                <span class="text-xs text-blue-700 block mt-1">Aporte: {{ formatoNumero(snapshot.nutricion.pct_proteina, 1) }}% de VCT</span>
                            </div>

                            <div class="p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50/40 border border-emerald-100">
                                <span class="text-xs font-medium text-emerald-900 block">Carbohidratos</span>
                                <div class="text-2xl font-bold text-emerald-950 mt-1">
                                    {{ formatoNumero(snapshot.nutricion.promedio_carbohidratos_g, 1) }} <span class="text-xs font-normal text-emerald-800">g/día</span>
                                </div>
                                <span class="text-xs text-emerald-700 block mt-1">Aporte: {{ formatoNumero(snapshot.nutricion.pct_carbohidratos, 1) }}% de VCT</span>
                            </div>
                        </div>

                        <!-- Micronutrientes clave y Grasas -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 block">Grasas Totales</span>
                                <span class="font-bold text-gray-900 mt-0.5 block">{{ formatoNumero(snapshot.nutricion.promedio_grasas_g, 1) }} g ({{ formatoNumero(snapshot.nutricion.pct_grasa, 1) }}%)</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 block">Fibra Dietética</span>
                                <span class="font-bold text-gray-900 mt-0.5 block">{{ formatoNumero(snapshot.nutricion.promedio_fibra_g, 1) }} g/día</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 block">Azúcares Simples</span>
                                <span class="font-bold text-gray-900 mt-0.5 block">{{ formatoNumero(snapshot.nutricion.promedio_azucar_g, 1) }} g/día</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 block">Sodio Estimado</span>
                                <span class="font-bold text-gray-900 mt-0.5 block">{{ formatoNumero(snapshot.nutricion.promedio_sodio_mg, 0) }} mg/día</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hábitos y Regularidad de Comidas -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                            Estructura de Horarios y Frecuencia de Ingestas
                        </h3>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div class="p-3.5 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 block">Comidas / Día Prom.</span>
                                <span class="font-bold text-gray-900 text-lg block mt-0.5">{{ formatoNumero(snapshot.habitos.promedio_comidas_dia, 1) }}</span>
                            </div>
                            <div class="p-3.5 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 block">Frecuencia Desayuno</span>
                                <span class="font-bold text-gray-900 text-lg block mt-0.5">{{ snapshot.habitos.frecuencia_desayuno_pct }}%</span>
                            </div>
                            <div class="p-3.5 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 block">Frecuencia Cena</span>
                                <span class="font-bold text-gray-900 text-lg block mt-0.5">{{ snapshot.habitos.frecuencia_cena_pct }}%</span>
                            </div>
                            <div class="p-3.5 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 block">Variabilidad Horaria</span>
                                <span class="font-bold text-gray-900 text-lg block mt-0.5">
                                    {{ snapshot.habitos.variabilidad_horaria_min !== null ? `&plusmn;${formatoNumero(snapshot.habitos.variabilidad_horaria_min, 0)} min` : 'N/D' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Formulario de Evaluación Profesional Independiente (1 col) -->
                <div class="space-y-6">
                    <form @submit.prevent="submit" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-6">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Dictamen Profesional</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Seleccione el nivel de riesgo nutricional global con base en su criterio clínico sobre las variables de este snapshot.
                            </p>
                        </div>

                        <!-- Opciones de Nivel de Riesgo (Bajo, Moderado, Alto o Insuficiente) -->
                        <div class="space-y-2.5">
                            <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider block">
                                Nivel de Riesgo Nutricional <span class="text-red-500">*</span>
                            </label>

                            <!-- Opción BAJO -->
                            <label
                                class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="form.opcion_riesgo === 'bajo' ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/20' : 'border-gray-200 hover:bg-gray-50'"
                            >
                                <input
                                    type="radio"
                                    name="opcion_riesgo"
                                    value="bajo"
                                    v-model="form.opcion_riesgo"
                                    class="text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <span class="block text-sm font-semibold text-emerald-950">Riesgo Bajo</span>
                                    <span class="block text-xs text-emerald-700">Patrón equilibrado, buena adherencia y regularidad.</span>
                                </div>
                            </label>

                            <!-- Opción MODERADO -->
                            <label
                                class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="form.opcion_riesgo === 'moderado' ? 'border-amber-500 bg-amber-50/50 ring-2 ring-amber-500/20' : 'border-gray-200 hover:bg-gray-50'"
                            >
                                <input
                                    type="radio"
                                    name="opcion_riesgo"
                                    value="moderado"
                                    v-model="form.opcion_riesgo"
                                    class="text-amber-600 focus:ring-amber-500"
                                />
                                <div>
                                    <span class="block text-sm font-semibold text-amber-950">Riesgo Moderado</span>
                                    <span class="block text-xs text-amber-700">Desbalances moderados en macros, fibra o regularidad horaria.</span>
                                </div>
                            </label>

                            <!-- Opción ALTO -->
                            <label
                                class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="form.opcion_riesgo === 'alto' ? 'border-red-500 bg-red-50/50 ring-2 ring-red-500/20' : 'border-gray-200 hover:bg-gray-50'"
                            >
                                <input
                                    type="radio"
                                    name="opcion_riesgo"
                                    value="alto"
                                    v-model="form.opcion_riesgo"
                                    class="text-red-600 focus:ring-red-500"
                                />
                                <div>
                                    <span class="block text-sm font-semibold text-red-950">Riesgo Alto</span>
                                    <span class="block text-xs text-red-700">Desviaciones críticas en calorías, macros, azúcar/sodio o hábitos severos.</span>
                                </div>
                            </label>

                            <!-- Opción INFORMACIÓN INSUFICIENTE -->
                            <label
                                class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="form.opcion_riesgo === 'insuficiente' ? 'border-gray-500 bg-gray-100 ring-2 ring-gray-400/20' : 'border-gray-200 hover:bg-gray-50'"
                            >
                                <input
                                    type="radio"
                                    name="opcion_riesgo"
                                    value="insuficiente"
                                    v-model="form.opcion_riesgo"
                                    class="text-gray-600 focus:ring-gray-500"
                                />
                                <div>
                                    <span class="block text-sm font-semibold text-gray-900">Información Insuficiente</span>
                                    <span class="block text-xs text-gray-500">Datos no concluyentes o incompletos para clasificar riesgo.</span>
                                </div>
                            </label>

                            <div v-if="form.errors.opcion_riesgo" class="text-xs text-red-600 mt-1">
                                {{ form.errors.opcion_riesgo }}
                            </div>
                        </div>

                        <!-- Tipo de Riesgo Específico (Catálogo Opcional) -->
                        <div v-if="!esInformacionInsuficiente" class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider block">
                                Tipo Específico de Riesgo (Opcional)
                            </label>
                            <select
                                v-model="form.tipo_riesgo_erp"
                                class="w-full text-sm rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">-- Sin tipo específico asignado --</option>
                                <option v-for="(label, key) in catalogo_tipos_riesgo" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <!-- Observación Profesional -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider block">
                                Justificación / Observación Clínica (Opcional)
                            </label>
                            <textarea
                                v-model="form.observacion_profesional_erp"
                                rows="4"
                                placeholder="Describa el motivo clínico de su clasificación..."
                                class="w-full text-sm rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500"
                            ></textarea>
                            <div v-if="form.errors.observacion_profesional_erp" class="text-xs text-red-600">
                                {{ form.errors.observacion_profesional_erp }}
                            </div>
                        </div>

                        <!-- Estado previo si ya fue evaluado -->
                        <div v-if="mi_evaluacion" class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-900">
                            <span class="font-semibold block">Evaluación registrada previamente:</span>
                            <span>Fecha: {{ mi_evaluacion.fec_evaluacion_erp }} (Criterio {{ mi_evaluacion.version_criterio_erp }})</span>
                        </div>

                        <!-- Botón de Guardar -->
                        <button
                            type="submit"
                            :disabled="form.processing || !form.opcion_riesgo"
                            class="w-full py-3 px-4 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-500/20 shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Guardando evaluación...</span>
                            <span v-else>{{ mi_evaluacion ? 'Actualizar Dictamen Profesional' : 'Guardar Dictamen Profesional' }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    objetivos: {
        type: Array,
        default: () => [],
    },
    nutricionistas: {
        type: Array,
        default: () => [],
    },
    objetivoSeleccionado: {
        type: [String, Number],
        default: '',
    },
    nutricionistaSeleccionado: {
        type: [String, Number],
        default: '',
    },
    objetivo: {
        type: Object,
        default: null,
    },
    motivos: {
        type: Array,
        default: () => [
            'ajuste_manual',
            'seguimiento',
        ],
    },
    modo: {
        type: String,
        default: 'admin',
    },
    action: {
        type: String,
        default: '',
    },
    volverUrl: {
        type: String,
        default: '',
    },
});

const hoy = new Date().toISOString().slice(0, 10);

const esNutricionista = computed(() => props.modo === 'nutricionista');

const form = useForm({
    cod_objetivos_nutricionales_ajn: props.objetivoSeleccionado || props.objetivo?.cod_objetivos_nutricionales || '',
    cod_nutricionistas_ajn: props.nutricionistaSeleccionado || '',
    fec_ajn: hoy,
    des_ajn: '',
    motivo_ajn: 'ajuste_manual',
    estado_anterior_ajn: props.objetivo?.est_obe || '',
    estado_nuevo_ajn: props.objetivo?.est_obe || '',
    descripcion_anterior_ajn: props.objetivo?.des_obe || '',
    descripcion_nueva_ajn: props.objetivo?.des_obe || '',
});

const actionUrl = computed(() => {
    return props.action || route('ajustes-objetivos-nutricionales.store');
});

const volver = computed(() => {
    return props.volverUrl || route('dashboard');
});

const objetivoActivo = computed(() => {
    if (props.objetivo) {
        return props.objetivo;
    }

    return props.objetivos.find((objetivo) => {
        return String(objetivo.cod_objetivos_nutricionales) === String(form.cod_objetivos_nutricionales_ajn);
    }) || null;
});

const nutricionistaActivo = computed(() => {
    return props.nutricionistas.find((nutricionista) => {
        return String(nutricionista.cod_nutricionistas) === String(form.cod_nutricionistas_ajn);
    }) || null;
});

const motivoLabel = (motivo) => {
    const labels = {
        creacion_objetivo: 'Creación del objetivo',
        cambio_estado: 'Cambio de estado',
        cambio_descripcion: 'Cambio de descripción',
        ajuste_manual: 'Ajuste manual',
        seguimiento: 'Seguimiento',
    };

    return labels[motivo] || motivo;
};

const submit = () => {
    form.post(actionUrl.value, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Registrar ajuste de objetivo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">
                        Ajuste de objetivo
                    </p>

                    <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">
                        Registrar ajuste nutricional
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Documenta un cambio, seguimiento u observación importante del objetivo.
                    </p>
                </div>

                <Link
                    :href="volver"
                    class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div v-if="!esNutricionista">
                                <InputLabel for="cod_objetivos_nutricionales_ajn" value="Objetivo nutricional" />

                                <select
                                    id="cod_objetivos_nutricionales_ajn"
                                    v-model="form.cod_objetivos_nutricionales_ajn"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                    <option value="">Seleccione un objetivo</option>

                                    <option
                                        v-for="objetivoItem in objetivos"
                                        :key="objetivoItem.cod_objetivos_nutricionales"
                                        :value="objetivoItem.cod_objetivos_nutricionales"
                                    >
                                        {{ objetivoItem.des_obe }}
                                    </option>
                                </select>

                                <InputError class="mt-2" :message="form.errors.cod_objetivos_nutricionales_ajn" />
                            </div>

                            <div
                                v-else
                                class="rounded-2xl border border-violet-200 bg-violet-50 p-4"
                            >
                                <p class="text-sm font-semibold text-violet-950">
                                    Objetivo seleccionado
                                </p>

                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-violet-900">
                                    {{ objetivoActivo?.des_obe || 'Objetivo no disponible' }}
                                </p>

                                <p class="mt-2 text-xs text-violet-800">
                                    Estado actual: {{ objetivoActivo?.est_obe || 'No disponible' }}
                                </p>

                                <InputError class="mt-2" :message="form.errors.cod_objetivos_nutricionales_ajn" />
                            </div>

                            <div v-if="!esNutricionista">
                                <InputLabel for="cod_nutricionistas_ajn" value="Nutricionista responsable" />

                                <select
                                    id="cod_nutricionistas_ajn"
                                    v-model="form.cod_nutricionistas_ajn"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                    <option value="">Seleccione un nutricionista</option>

                                    <option
                                        v-for="nutricionista in nutricionistas"
                                        :key="nutricionista.cod_nutricionistas"
                                        :value="nutricionista.cod_nutricionistas"
                                    >
                                        {{ nutricionista.usuario?.nombre_completo || 'Nutricionista' }}
                                    </option>
                                </select>

                                <InputError class="mt-2" :message="form.errors.cod_nutricionistas_ajn" />
                            </div>

                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="fec_ajn" value="Fecha del ajuste" />

                                    <input
                                        id="fec_ajn"
                                        v-model="form.fec_ajn"
                                        type="date"
                                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                    />

                                    <InputError class="mt-2" :message="form.errors.fec_ajn" />
                                </div>

                                <div>
                                    <InputLabel for="motivo_ajn" value="Motivo del ajuste" />

                                    <select
                                        id="motivo_ajn"
                                        v-model="form.motivo_ajn"
                                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                    >
                                        <option
                                            v-for="motivo in motivos"
                                            :key="motivo"
                                            :value="motivo"
                                        >
                                            {{ motivoLabel(motivo) }}
                                        </option>
                                    </select>

                                    <InputError class="mt-2" :message="form.errors.motivo_ajn" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="des_ajn" value="Descripción del ajuste" />

                                <textarea
                                    id="des_ajn"
                                    v-model="form.des_ajn"
                                    rows="6"
                                    class="mt-1 block w-full rounded-2xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                    placeholder="Describe el motivo del ajuste, observación clínica o cambio aplicado."
                                />

                                <InputError class="mt-2" :message="form.errors.des_ajn" />
                            </div>

                            <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-bold text-slate-900">
                                    Trazabilidad automática
                                </p>

                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    Este ajuste manual no cambia el estado ni la descripción del objetivo.
                                    El sistema registrará automáticamente el estado actual y la descripción actual para mantener el historial limpio.
                                </p>

                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div class="rounded-xl bg-white p-4 text-sm text-slate-700">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Estado actual
                                        </p>

                                        <p class="mt-2 font-semibold capitalize text-slate-900">
                                            {{ objetivoActivo?.est_obe || 'No disponible' }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl bg-white p-4 text-sm text-slate-700">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Descripción actual
                                        </p>

                                        <p class="mt-2 line-clamp-3 text-slate-700">
                                            {{ objetivoActivo?.des_obe || 'No disponible' }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <div class="flex justify-end gap-3">
                                <Link
                                    :href="volver"
                                    class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                                >
                                    Cancelar
                                </Link>

                                <PrimaryButton :disabled="form.processing">
                                    Guardar ajuste
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <aside class="space-y-5">
                        <div class="rounded-3xl border border-violet-200 bg-violet-50 p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-700">
                                Objetivo relacionado
                            </p>

                            <h3 class="mt-3 text-lg font-bold text-violet-950">
                                {{ objetivoActivo?.des_obe || 'Sin objetivo seleccionado' }}
                            </h3>

                            <p class="mt-3 text-sm text-violet-900">
                                Estado actual:
                                <strong>{{ objetivoActivo?.est_obe || 'No disponible' }}</strong>
                            </p>

                            <p class="mt-2 text-sm text-violet-900">
                                Paciente:
                                <strong>{{ objetivoActivo?.paciente?.usuario?.nombre_completo || 'No disponible' }}</strong>
                            </p>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                Responsable
                            </p>

                            <h3 class="mt-3 text-lg font-bold text-slate-900">
                                {{ nutricionistaActivo?.usuario?.nombre_completo || 'Nutricionista actual' }}
                            </h3>

                            <p class="mt-2 text-sm text-slate-500">
                                En modo nutricionista, el responsable se toma automáticamente del usuario autenticado.
                            </p>
                        </div>
                    </aside>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
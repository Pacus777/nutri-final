<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    objetivo: Object,
    estados: {
        type: Array,
        default: () => ['activo', 'cumplido', 'pausado', 'cancelado', 'cerrado'],
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
    puedeCambiarEstadoDesdeEdicion: {
        type: Boolean,
        default: true,
    },
});

const form = useForm({
    des_obe: props.objetivo.des_obe || '',
    fec_obe: props.objetivo.fec_obe || '',
    est_obe: props.objetivo.est_obe || 'activo',
});

const esNutricionista = computed(() => props.modo === 'nutricionista');

const actionUrl = computed(() => {
    return props.action || route('objetivos-nutricionales.update', props.objetivo.cod_objetivos_nutricionales);
});

const volver = computed(() => {
    return props.volverUrl || route('objetivos-nutricionales.show', props.objetivo.cod_objetivos_nutricionales);
});

const paciente = computed(() => props.objetivo?.paciente || null);

const submit = () => {
    form.put(actionUrl.value, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar objetivo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Editar objetivo nutricional
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Actualiza la redacción y el estado del objetivo.
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
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="paciente"
                    class="mb-5 rounded-3xl border border-emerald-200 bg-emerald-50 p-5"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">
                        Paciente
                    </p>

                    <h3 class="mt-2 text-lg font-bold text-emerald-950">
                        {{ paciente.usuario?.nombre_completo || 'Paciente no disponible' }}
                    </h3>

                    <p class="mt-1 text-sm text-emerald-900">
                        {{ paciente.usuario?.cor_usu || 'Correo no disponible' }}
                    </p>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="fec_obe" value="Fecha" />

                                <input
                                    id="fec_obe"
                                    v-model="form.fec_obe"
                                    type="date"
                                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                />

                                <InputError class="mt-2" :message="form.errors.fec_obe" />
                            </div>

                            <div>
                                <InputLabel for="est_obe" value="Estado" />

                                <select
                                    id="est_obe"
                                    v-model="form.est_obe"
                                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    :disabled="!puedeCambiarEstadoDesdeEdicion"
                                >
                                    <option
                                        v-for="estado in estados"
                                        :key="estado"
                                        :value="estado"
                                    >
                                        {{ estado }}
                                    </option>
                                </select>

                                <p
                                    v-if="!puedeCambiarEstadoDesdeEdicion"
                                    class="mt-2 text-xs text-slate-500"
                                >
                                    El estado se cambia desde los botones del detalle del objetivo para mantener la trazabilidad automática.
                                </p>

                                <InputError class="mt-2" :message="form.errors.est_obe" />
                            </div>

                            <div>
                                <InputLabel for="des_obe" value="Descripción" />

                                <textarea
                                    id="des_obe"
                                    v-model="form.des_obe"
                                    rows="8"
                                    class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                />

                                <InputError class="mt-2" :message="form.errors.des_obe" />
                                <InputError class="mt-2" :message="form.errors.objetivo" />

                                <div
                                    v-if="esNutricionista"
                                    class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs leading-5 text-slate-600"
                                >
                                    <p class="font-semibold text-slate-800">
                                        Recomendación para objetivos clínicos:
                                    </p>

                                    <ul class="mt-2 space-y-1">
                                        <li>• Evita objetivos genéricos como “bajar peso” o “comer mejor”.</li>
                                        <li>• Indica una acción concreta: reducir, aumentar, mejorar, controlar o adaptar.</li>
                                        <li>• Incluye contexto: duración, restricciones, alergias, hábitos o seguimiento.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3">
                                <Link
                                    :href="volver"
                                    class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
                                >
                                    Cancelar
                                </Link>

                                <PrimaryButton :disabled="form.processing">
                                    Guardar cambios
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
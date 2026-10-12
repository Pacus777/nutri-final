<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    alimento: {
        type: Object,
        required: true,
    },
});

const valorONoDisponible = (valor) => {
    if (valor === null || valor === undefined || valor === '') {
        return 'No disponible';
    }

    return valor;
};
</script>

<template>
    <Head title="Detalle de alimento" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        Detalle de alimento
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Alimento almacenado en caché local.
                    </p>
                </div>

                <Link
                    :href="route('alimentos.buscar')"
                    class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700"
                >
                    Volver a buscar
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Datos generales
                        </h3>

                        <div class="mt-4 space-y-3 text-sm text-gray-700">
                            <p>
                                <strong>Código local:</strong>
                                {{ alimento.cod_alimentos }}
                            </p>

                            <p>
                                <strong>Nombre local:</strong>
                                {{ valorONoDisponible(alimento.nom_ali) }}
                            </p>

                            <p>
                                <strong>Nombre externo:</strong>
                                {{ valorONoDisponible(alimento.nom_ext_ali) }}
                            </p>

                            <p>
                                <strong>Tipo:</strong>
                                {{ valorONoDisponible(alimento.tip_ali) }}
                            </p>

                            <p>
                                <strong>Descripción:</strong>
                                {{ valorONoDisponible(alimento.des_ali) }}
                            </p>

                            <p>
                                <strong>Marca:</strong>
                                {{ valorONoDisponible(alimento.mar_ali) }}
                            </p>

                            <p>
                                <strong>Fuente:</strong>
                                {{ valorONoDisponible(alimento.src_ali) }}
                            </p>

                            <p>
                                <strong>Referencia externa:</strong>
                                {{ valorONoDisponible(alimento.ref_ext_ali) }}
                            </p>

                            <p>
                                <strong>Idioma:</strong>
                                {{ valorONoDisponible(alimento.idi_ali) }}
                            </p>

                            <p>
                                <strong>País:</strong>
                                {{ valorONoDisponible(alimento.pai_ali) }}
                            </p>

                            <p>
                                <strong>Estado:</strong>
                                {{ valorONoDisponible(alimento.est_ali) }}
                            </p>

                            <p>
                                <strong>Última sincronización:</strong>
                                {{ valorONoDisponible(alimento.fec_sync_ali) }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Composición nutricional vigente
                        </h3>

                        <div
                            v-if="!alimento.composicion_vigente"
                            class="mt-4 text-sm text-gray-500"
                        >
                            Este alimento no tiene composición nutricional vigente registrada.
                        </div>

                        <div
                            v-else
                            class="mt-4 space-y-4 text-sm text-gray-700"
                        >
                            <div class="rounded border border-gray-200 bg-gray-50 p-4">
                                <p>
                                    <strong>Porción referencia:</strong>
                                    {{ valorONoDisponible(alimento.composicion_vigente.por_ref_tcn) }}
                                </p>
                                <p class="mt-2">
                                    <strong>Cantidad referencia:</strong>
                                    {{ valorONoDisponible(alimento.composicion_vigente.cant_ref_tcn) }}
                                    {{ valorONoDisponible(alimento.composicion_vigente.uni_ref_tcn) }}
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded border border-gray-200 bg-gray-50 p-4">
                                    <h4 class="mb-3 font-semibold text-gray-800">
                                        Macronutrientes
                                    </h4>

                                    <p><strong>Calorías:</strong> {{ alimento.composicion_vigente.cal_tcn }}</p>
                                    <p><strong>Proteínas:</strong> {{ alimento.composicion_vigente.pro_tcn }}</p>
                                    <p><strong>Grasas:</strong> {{ alimento.composicion_vigente.gra_tcn }}</p>
                                    <p><strong>Carbohidratos:</strong> {{ alimento.composicion_vigente.car_tcn }}</p>
                                    <p><strong>Fibra:</strong> {{ alimento.composicion_vigente.fib_tcn }}</p>
                                    <p><strong>Azúcares:</strong> {{ alimento.composicion_vigente.azu_tcn }}</p>
                                </div>

                                <div class="rounded border border-gray-200 bg-gray-50 p-4">
                                    <h4 class="mb-3 font-semibold text-gray-800">
                                        Grasas y otros
                                    </h4>

                                    <p><strong>Grasa saturada:</strong> {{ alimento.composicion_vigente.gra_sat_tcn }}</p>
                                    <p><strong>Grasa monoinsaturada:</strong> {{ alimento.composicion_vigente.gra_mono_tcn }}</p>
                                    <p><strong>Grasa poliinsaturada:</strong> {{ alimento.composicion_vigente.gra_poli_tcn }}</p>
                                    <p><strong>Colesterol:</strong> {{ alimento.composicion_vigente.col_tcn }}</p>
                                    <p><strong>Sodio:</strong> {{ alimento.composicion_vigente.sod_tcn }}</p>
                                    <p><strong>Potasio:</strong> {{ alimento.composicion_vigente.pot_tcn }}</p>
                                </div>

                                <div class="rounded border border-gray-200 bg-gray-50 p-4">
                                    <h4 class="mb-3 font-semibold text-gray-800">
                                        Minerales
                                    </h4>

                                    <p><strong>Calcio:</strong> {{ alimento.composicion_vigente.calcio_tcn }}</p>
                                    <p><strong>Hierro:</strong> {{ alimento.composicion_vigente.hierro_tcn }}</p>
                                </div>

                                <div class="rounded border border-gray-200 bg-gray-50 p-4">
                                    <h4 class="mb-3 font-semibold text-gray-800">
                                        Vitaminas
                                    </h4>

                                    <p><strong>Vitamina A:</strong> {{ alimento.composicion_vigente.vit_a_tcn }}</p>
                                    <p><strong>Vitamina C:</strong> {{ alimento.composicion_vigente.vit_c_tcn }}</p>
                                    <p><strong>Vitamina D:</strong> {{ alimento.composicion_vigente.vit_d_tcn }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Fuente externa original: USDA FoodData Central. Estos datos ya fueron almacenados localmente
                    en el sistema para su reutilización posterior.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
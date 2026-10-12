<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    paciente: Object,
    registros: Array,
});

const filtroTexto = ref('');

const registrosFiltrados = computed(() => {
    const texto = filtroTexto.value.trim().toLowerCase();

    if (texto === '') {
        return props.registros;
    }

    return props.registros.filter((item) => {
        return String(item.fec_ral ?? '').toLowerCase().includes(texto)
            || String(item.calculo?.fecha_calculo ?? '').toLowerCase().includes(texto);
    });
});

const resumen = computed(() => {
    const total = props.registros.length;
    const visibles = registrosFiltrados.value.length;

    return `Mostrando ${visibles} de ${total} calculos nutricionales`;
});
</script>

<template>
    <Head title="Historial de calculos nutricionales" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        Historial de calculos nutricionales
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ paciente.usuario?.nombre_completo }} - {{ paciente.usuario?.cor_usu }}
                    </p>
                </div>

                <Link
                    :href="route('paciente.registros-alimenticios.historial', paciente.cod_pacientes)"
                    class="motion-btn rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                >
                    Volver al historial diario
                </Link>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <section class="auth-panel auth-glow auth-fade-up relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(6,182,212,0.16),transparent_24%),radial-gradient(circle_at_85%_80%,rgba(255,255,255,0.06),transparent_25%)]"></div>

                    <div class="relative z-10 grid gap-4 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-300">
                                Seguimiento nutricional
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                Calculos realizados
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                                Consulta tus dias ya procesados para revisar tendencia y evolucion de consumo.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4 text-sm text-slate-200">
                            <p>{{ resumen }}</p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-4">
                        <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Buscar por fecha de registro o fecha de calculo
                        </label>
                        <input
                            v-model="filtroTexto"
                            type="text"
                            placeholder="Ej: 2026-04-25"
                            class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                    </div>

                    <div v-if="registros.length === 0" class="p-6 text-sm text-slate-500">
                        Aun no tienes calculos nutricionales realizados.
                    </div>

                    <div v-else-if="registrosFiltrados.length === 0" class="p-6 text-sm text-slate-500">
                        No hay calculos que coincidan con tu busqueda.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-100/90 text-xs uppercase tracking-[0.14em] text-slate-600">
                                <tr>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Fecha registro</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Alimentos</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Calorias</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Proteinas</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Carbohidratos</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Fecha calculo</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Accion</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                                <tr
                                    v-for="registro in registrosFiltrados"
                                    :key="registro.cod_registros_alimenticios"
                                    class="hover:bg-slate-50/80"
                                >
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ registro.fec_ral }}</td>
                                    <td class="px-4 py-3">{{ registro.detalles_count }}</td>
                                    <td class="px-4 py-3">{{ registro.calculo?.cal_cnr }}</td>
                                    <td class="px-4 py-3">{{ registro.calculo?.pro_cnr }}</td>
                                    <td class="px-4 py-3">{{ registro.calculo?.car_cnr }}</td>
                                    <td class="px-4 py-3">{{ registro.calculo?.fecha_calculo }}</td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="route('paciente.registros-alimenticios.consolidado', registro.cod_registros_alimenticios)"
                                            class="motion-btn inline-flex items-center rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700"
                                        >
                                            Ver consolidado
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

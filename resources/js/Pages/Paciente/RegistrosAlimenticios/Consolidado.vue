<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    registro: Object,
    consolidado: Object,
});

const page = usePage();
</script>

<template>
    <Head title="Consolidado nutricional" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        Consolidado nutricional del dia
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ registro.paciente?.usuario?.nombre_completo }} - {{ registro.fec_ral }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('paciente.registros-alimenticios.show', registro.cod_registros_alimenticios)"
                        class="motion-btn rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Volver al detalle
                    </Link>

                    <Link
                        :href="route('paciente.registros-alimenticios.historial-calculos', registro.paciente?.cod_pacientes)"
                        class="motion-btn rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                    >
                        Historial de calculos
                    </Link>
                </div>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.success"
                    class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ page.props.flash.success }}
                </div>

                <section class="auth-panel auth-glow auth-fade-up relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_25%,rgba(16,185,129,0.16),transparent_24%),radial-gradient(circle_at_80%_80%,rgba(255,255,255,0.07),transparent_25%)]"></div>

                    <div class="relative z-10 grid gap-5 md:grid-cols-[1.1fr_0.9fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">
                                Resultado del calculo
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                Resumen nutricional
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                                Este consolidado refleja el consumo registrado del dia y sirve de base para seguimiento y riesgo.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4 text-sm text-slate-200">
                            <p><strong>Registro:</strong> #{{ registro.cod_registros_alimenticios }}</p>
                            <p class="mt-2"><strong>Fecha:</strong> {{ registro.fec_ral }}</p>
                            <p class="mt-2"><strong>Estado:</strong> {{ registro.est_ral }}</p>
                            <p class="mt-2"><strong>Alimentos:</strong> {{ registro.detalles_count }}</p>
                            <p class="mt-2"><strong>Actualizado:</strong> {{ consolidado.actualizado_en }}</p>
                        </div>
                    </div>
                </section>

                <div class="grid gap-4 md:grid-cols-3">
                    <article class="motion-lift rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Calorias</p>
                        <p class="mt-2 text-3xl font-black text-emerald-800">{{ consolidado.cal_cnr }}</p>
                    </article>

                    <article class="motion-lift rounded-xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-cyan-700">Proteinas</p>
                        <p class="mt-2 text-3xl font-black text-cyan-800">{{ consolidado.pro_cnr }}</p>
                    </article>

                    <article class="motion-lift rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-amber-700">Carbohidratos</p>
                        <p class="mt-2 text-3xl font-black text-amber-800">{{ consolidado.car_cnr }}</p>
                    </article>

                    <article class="motion-lift rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-rose-700">Grasas</p>
                        <p class="mt-2 text-3xl font-black text-rose-800">{{ consolidado.gra_cnr }}</p>
                    </article>

                    <article class="motion-lift rounded-xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-violet-700">Vitaminas</p>
                        <p class="mt-2 text-3xl font-black text-violet-800">{{ consolidado.vit_cnr }}</p>
                    </article>

                    <article class="motion-lift rounded-xl border border-slate-300 bg-slate-50 p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-700">Minerales</p>
                        <p class="mt-2 text-3xl font-black text-slate-800">{{ consolidado.min_cnr }}</p>
                    </article>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

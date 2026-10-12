<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    nutricionista: Object,
});

const nombreCompleto = (usuario) => {
    if (!usuario) return 'No disponible';

    if (usuario.nombre_completo) {
        return usuario.nombre_completo;
    }

    const nombre = `${usuario.nom_usu ?? ''} ${usuario.ape_usu ?? ''}`.trim();
    return nombre || 'No disponible';
};

const claseEstadoPerfil = (estado) => {
    if (estado === 'activo') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    return 'bg-amber-100 text-amber-700 ring-amber-200';
};

const claseEstadoUsuario = (estado) => {
    if (estado === 'activo') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (estado === 'inactivo') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    return 'bg-rose-100 text-rose-700 ring-rose-200';
};
</script>

<template>
    <Head title="Detalle de nutricionista" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Ficha del Nutricionista
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Consulta la información profesional y el usuario vinculado.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('nutricionistas.edit', nutricionista.cod_nutricionistas)"
                        class="motion-btn rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    >
                        Editar
                    </Link>

                    <Link
                        :href="route('nutricionistas.index')"
                        class="motion-btn rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-800"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="auth-panel auth-glow auth-fade-up motion-lift relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_18%,rgba(34,197,94,0.12),transparent_22%),radial-gradient(circle_at_85%_82%,rgba(255,255,255,0.05),transparent_24%)]"></div>

                    <div class="relative z-10 grid gap-5 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-green-400">
                                Perfil profesional
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                {{ nombreCompleto(nutricionista.usuario) }}
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Ficha consolidada con datos del perfil, acceso y estado actual.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Estado del perfil</p>
                            <span
                                class="mt-3 inline-flex rounded-full px-3 py-1.5 text-xs font-semibold capitalize ring-1"
                                :class="claseEstadoPerfil(nutricionista.est_nut)"
                            >
                                {{ nutricionista.est_nut }}
                            </span>
                            <p class="mt-3 text-xs leading-5 text-slate-300">
                                Registrado el {{ nutricionista.fecha_registro || 'No disponible' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Datos del nutricionista
                        </h3>

                        <div class="mt-4 space-y-3 text-sm text-slate-700">
                            <p><strong>Código:</strong> {{ nutricionista.cod_nutricionistas }}</p>
                            <p><strong>Especialidad:</strong> {{ nutricionista.esp_nut || 'No especificada' }}</p>
                            <p>
                                <strong>Estado del perfil:</strong>
                                <span class="ml-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1" :class="claseEstadoPerfil(nutricionista.est_nut)">
                                    {{ nutricionista.est_nut }}
                                </span>
                            </p>
                            <p><strong>Fecha de registro:</strong> {{ nutricionista.fecha_registro || 'No disponible' }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Usuario vinculado
                        </h3>

                        <div v-if="nutricionista.usuario?.fot_usu" class="mt-4 flex justify-start">
                            <img
                                :src="`/storage/${nutricionista.usuario.fot_usu}`"
                                alt="Foto de nutricionista"
                                class="h-28 w-28 rounded-2xl border border-slate-200 object-cover shadow-sm"
                            />
                        </div>

                        <div class="mt-4 space-y-3 text-sm text-slate-700">
                            <p><strong>Nombre completo:</strong> {{ nombreCompleto(nutricionista.usuario) }}</p>
                            <p><strong>CI:</strong> {{ nutricionista.usuario?.ci_usu ? `${nutricionista.usuario.ci_usu} ${nutricionista.usuario.ext_ci_usu}` : 'No disponible' }}</p>
                            <p><strong>Fecha de nacimiento:</strong> {{ nutricionista.usuario?.fec_nac_usu ?? 'No disponible' }}</p>
                            <p><strong>Correo:</strong> {{ nutricionista.usuario?.cor_usu ?? 'No disponible' }}</p>
                            <p>
                                <strong>Estado de usuario:</strong>
                                <span class="ml-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1" :class="claseEstadoUsuario(nutricionista.usuario?.est_usu)">
                                    {{ nutricionista.usuario?.est_usu ?? 'No disponible' }}
                                </span>
                            </p>

                            <div>
                                <strong>Roles:</strong>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-for="rol in nutricionista.usuario?.roles ?? []"
                                        :key="rol.cod_roles"
                                        class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                    >
                                        {{ rol.nom_rol }}
                                    </span>
                                    <span v-if="!(nutricionista.usuario?.roles ?? []).length" class="text-xs text-slate-500">
                                        Sin roles asignados
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
                        <div class="border-b border-emerald-100 px-5 py-4">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-700">Pacientes activos</h4>
                        </div>
                        <div class="space-y-3 p-5 text-sm text-emerald-900">
                            <div v-if="nutricionista.pacientes_activos?.length" class="space-y-3">
                                <div
                                    v-for="paciente in nutricionista.pacientes_activos"
                                    :key="paciente.cod_pacientes"
                                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-3"
                                >
                                    <p><span class="font-semibold text-emerald-950">Paciente:</span> {{ nombreCompleto(paciente.usuario) }}</p>
                                    <p><span class="font-semibold text-emerald-950">Estado:</span> {{ paciente.est_pac }}</p>
                                    <p><span class="font-semibold text-emerald-950">Sexo:</span> {{ paciente.sex_pac || 'No disponible' }}</p>
                                </div>
                            </div>
                            <p v-else>No tiene pacientes activos asignados.</p>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">Historial de asignaciones</h4>
                        </div>
                        <div class="space-y-3 p-5 text-sm text-slate-700">
                            <div v-if="nutricionista.historial_asignaciones?.length" class="space-y-3">
                                <div
                                    v-for="asignacion in nutricionista.historial_asignaciones"
                                    :key="asignacion.cod_asignacion_nutricionista_paciente"
                                    class="rounded-xl border border-slate-200 bg-slate-50 p-3"
                                >
                                    <p><span class="font-semibold text-slate-900">Paciente:</span> {{ nombreCompleto(asignacion.paciente?.usuario) }}</p>
                                    <p><span class="font-semibold text-slate-900">Fecha:</span> {{ asignacion.fec_anp }}</p>
                                    <p><span class="font-semibold text-slate-900">Estado:</span> {{ asignacion.est_anp }}</p>
                                </div>
                            </div>
                            <p v-else>No hay historial de asignaciones registrado.</p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

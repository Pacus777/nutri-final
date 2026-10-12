<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import HeroPanel from '@/Components/UI/HeroPanel.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    usuario: Object,
});



const etiquetaPerfil = (activo) => (activo ? 'Sí' : 'No');
</script>

<template>
    <Head title="Ficha de usuario" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="Ficha del Usuario"
                description="Consulta la información general, de acceso y los perfiles vinculados."
                action-text="Editar"
                :action-href="route('admin.usuarios.edit', usuario.cod_usuarios)"
                back-text="Volver"
                :back-href="route('admin.usuarios.index')"
            />
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <HeroPanel
                    eyebrow="Identidad y acceso"
                    :title="usuario.nombre_completo || 'Usuario'"
                    description="Revisa el estado actual del usuario y sus perfiles asociados en una sola vista."
                >
                    <template #aside>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Estado actual</p>

                        <div class="mt-3">
                            <StatusBadge :estado="usuario.est_usu" />
                        </div>

                        <p class="mt-3 text-xs leading-5 text-slate-300">
                            Registrado el {{ usuario.fecha_registro || 'no disponible' }}
                        </p>
                    </template>
                </HeroPanel>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                            Datos personales
                        </h3>

                        <div class="mt-4 space-y-3 text-sm text-slate-700">
                            <p><strong>Código:</strong> {{ usuario.cod_usuarios }}</p>
                            <p><strong>Nombre completo:</strong> {{ usuario.nombre_completo || 'No disponible' }}</p>
                            <p><strong>CI:</strong> {{ usuario.ci_usu }} {{ usuario.ext_ci_usu }}</p>
                            <p><strong>Fecha de nacimiento:</strong> {{ usuario.fec_nac_usu || 'No disponible' }}</p>
                            <p><strong>Correo:</strong> {{ usuario.cor_usu }}</p>
                            <p><strong>Estado:</strong> {{ usuario.est_usu }}</p>
                            <p><strong>Fecha de registro:</strong> {{ usuario.fecha_registro || 'No disponible' }}</p>
                        </div>

                        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Roles</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-for="rol in usuario.roles"
                                    :key="rol.cod_roles"
                                    class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                >
                                    {{ rol.nom_rol }}
                                </span>
                                <span v-if="!usuario.roles.length" class="text-sm text-slate-500">
                                    Sin roles asignados
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                                Acceso
                            </h3>

                            <div class="mt-4 space-y-3 text-sm text-slate-700">
                                <p><strong>Correo:</strong> {{ usuario.cor_usu }}</p>
                                <p><strong>Estado de usuario:</strong> {{ usuario.est_usu }}</p>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 p-6 shadow-sm">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-800">
                                Perfil de nutricionista
                            </h3>

                            <div class="mt-3 text-sm text-blue-900">
                                <p><strong>Registrado:</strong> {{ etiquetaPerfil(!!usuario.nutricionista) }}</p>
                                <p><strong>Especialidad:</strong> {{ usuario.nutricionista?.esp_nut || 'No disponible' }}</p>
                                <p><strong>Estado del perfil:</strong> {{ usuario.nutricionista?.est_nut || 'No disponible' }}</p>
                            </div>

                            <div
                                v-if="usuario.nutricionista"
                                class="mt-4"
                            >
                                <Link
                                    :href="route('nutricionistas.show', usuario.nutricionista.cod_nutricionistas)"
                                    class="motion-btn inline-flex rounded bg-blue-700 px-4 py-2 text-white hover:bg-blue-800"
                                >
                                    Ver perfil profesional
                                </Link>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-green-200 bg-green-50 p-6 shadow-sm">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-green-800">
                                Perfil de paciente
                            </h3>

                            <div class="mt-3 text-sm text-green-900">
                                <p><strong>Registrado:</strong> {{ etiquetaPerfil(!!usuario.paciente) }}</p>
                                <p><strong>Sexo:</strong> {{ usuario.paciente?.sex_pac || 'No disponible' }}</p>
                                <p><strong>Peso:</strong> {{ usuario.paciente?.pes_pac || 'No disponible' }}</p>
                                <p><strong>Estatura:</strong> {{ usuario.paciente?.estatura_pac || 'No disponible' }}</p>
                                <p><strong>Tipo de sangre:</strong> {{ usuario.paciente?.tip_san_pac || 'No disponible' }}</p>
                                <p><strong>Alergias:</strong> {{ usuario.paciente?.ale_pac || 'No registradas' }}</p>
                                <p><strong>Estado del perfil:</strong> {{ usuario.paciente?.est_pac || 'No disponible' }}</p>
                            </div>

                            <div
                                v-if="usuario.paciente"
                                class="mt-4"
                            >
                                <Link
                                    :href="route('pacientes.show', usuario.paciente.cod_pacientes)"
                                    class="motion-btn inline-flex rounded bg-emerald-700 px-4 py-2 text-white hover:bg-emerald-800"
                                >
                                    Ver ficha del paciente
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <Link
                        :href="route('admin.usuarios.edit', usuario.cod_usuarios)"
                        class="motion-btn rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    >
                        Editar usuario
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
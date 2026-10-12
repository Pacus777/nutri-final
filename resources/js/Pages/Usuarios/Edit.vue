<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import HeroPanel from '@/Components/UI/HeroPanel.vue';

import UsuarioDatosPersonalesForm from '@/Components/Usuarios/Form/UsuarioDatosPersonalesForm.vue';
import UsuarioAccesoForm from '@/Components/Usuarios/Form/UsuarioAccesoForm.vue';
import UsuarioRolesForm from '@/Components/Usuarios/Form/UsuarioRolesForm.vue';
import UsuarioPerfilNutricionistaForm from '@/Components/Usuarios/Form/UsuarioPerfilNutricionistaForm.vue';
import UsuarioPerfilPacienteForm from '@/Components/Usuarios/Form/UsuarioPerfilPacienteForm.vue';

import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    usuario: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        default: () => [],
    },
    estados: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    nom_usu: props.usuario.nom_usu,
    ape_usu: props.usuario.ape_usu,
    ci_usu: props.usuario.ci_usu,
    ext_ci_usu: props.usuario.ext_ci_usu,
    fec_nac_usu: props.usuario.fec_nac_usu,
    fot_usu: null,
    cor_usu: props.usuario.cor_usu,
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: props.usuario.est_usu,
    roles: props.usuario.roles,

    tiene_perfil_nutricionista: props.usuario.tiene_perfil_nutricionista,
    tiene_perfil_paciente: props.usuario.tiene_perfil_paciente,

    esp_nut: props.usuario.nutricionista?.esp_nut ?? '',
    est_nut: props.usuario.nutricionista?.est_nut ?? 'activo',

    sex_pac: props.usuario.paciente?.sex_pac ?? '',
    pes_pac: props.usuario.paciente?.pes_pac ?? '',
    estatura_pac: props.usuario.paciente?.estatura_pac ?? '',
    tip_san_pac: props.usuario.paciente?.tip_san_pac ?? '',
    ale_pac: props.usuario.paciente?.ale_pac ?? '',
    est_pac: props.usuario.paciente?.est_pac ?? 'activo',
});

const tienePerfilNutricionista = computed(() => form.tiene_perfil_nutricionista);
const tienePerfilPaciente = computed(() => form.tiene_perfil_paciente);
const tieneErrores = computed(() => Object.keys(form.errors).length > 0);

const previewFoto = computed(() => {
    return props.usuario.fot_usu ? `/storage/${props.usuario.fot_usu}` : null;
});

const estadoPerfilDesdeUsuario = (estadoUsuario) => {
    return estadoUsuario === 'activo' ? 'activo' : 'inactivo';
};

watch(
    () => form.est_usu,
    (nuevoEstado) => {
        if (form.tiene_perfil_paciente) {
            form.est_pac = estadoPerfilDesdeUsuario(nuevoEstado);
        }

        if (form.tiene_perfil_nutricionista) {
            form.est_nut = estadoPerfilDesdeUsuario(nuevoEstado);
        }
    },
    { immediate: true }
);

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            est_pac: data.tiene_perfil_paciente
                ? estadoPerfilDesdeUsuario(data.est_usu)
                : data.est_pac,
            est_nut: data.tiene_perfil_nutricionista
                ? estadoPerfilDesdeUsuario(data.est_usu)
                : data.est_nut,
            _method: 'put',
        }))
        .post(route('admin.usuarios.update', props.usuario.cod_usuarios), {
            forceFormData: true,
        });
};

</script>

<template>
    <Head title="Editar usuario" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="Editar Usuario"
                description="Actualiza información personal, acceso y perfiles relacionados."
                back-text="Volver"
                :back-href="route('admin.usuarios.index')"
            />
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <HeroPanel
                    eyebrow="Gestión de acceso"
                    title="Editar usuario"
                    description="Ajusta datos clave del usuario y sincroniza su perfil administrativo, profesional o clínico."
                >
                    <template #aside>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
                            Correo actual
                        </p>

                        <p class="mt-3 text-sm font-semibold text-white">
                            {{ form.cor_usu }}
                        </p>

                        <p class="mt-2 text-xs leading-5 text-slate-300">
                            ID: {{ usuario.cod_usuarios }}
                        </p>
                    </template>
                </HeroPanel>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <form
                            @submit.prevent="submit"
                            class="space-y-6"
                        >
                            <div
                                v-if="tieneErrores"
                                class="rounded-xl border border-rose-200 bg-rose-50 p-4"
                            >
                                <p class="text-sm font-semibold text-rose-800">
                                    Revisa la información del formulario.
                                </p>

                                <p class="mt-1 text-xs text-rose-700">
                                    Hay campos con errores. Corrige los mensajes marcados en rojo para continuar.
                                </p>
                            </div>

                            <div
                                v-if="usuario.tiene_perfil_nutricionista"
                                class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                            >
                                Este usuario tiene un perfil registrado en el módulo de nutricionistas.
                                Mientras exista ese perfil, el rol <strong>Nutricionista</strong> es obligatorio.
                            </div>

                            <div
                                v-if="usuario.tiene_perfil_paciente"
                                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                            >
                                Este usuario tiene un perfil registrado en el módulo de pacientes.
                                Mientras exista ese perfil, el rol <strong>Paciente</strong> es obligatorio.
                            </div>

                            <UsuarioDatosPersonalesForm
                                :form="form"
                                :preview-inicial="previewFoto"
                            />

                            <UsuarioAccesoForm
                                :form="form"
                                :estados="props.estados"
                                modo="editar"
                            />

                            <UsuarioRolesForm
                                :form="form"
                                :roles="props.roles"
                            />

                            <UsuarioPerfilNutricionistaForm
                                v-if="tienePerfilNutricionista"
                                :form="form"
                                descripcion="Ajusta estos datos para mantener actualizado el perfil profesional del nutricionista."
                                bloquear-estado
                            />

                            <UsuarioPerfilPacienteForm
                                v-if="tienePerfilPaciente"
                                :form="form"
                                descripcion="Ajusta estos datos para mantener actualizado el perfil clínico base del paciente."
                                bloquear-estado
                            />

                            <div class="flex justify-end">
                                <PrimaryButton
                                    class="motion-btn"
                                    :disabled="form.processing"
                                >
                                    Actualizar usuario
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
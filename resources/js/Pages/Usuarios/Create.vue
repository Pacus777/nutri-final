<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import HeroPanel from '@/Components/UI/HeroPanel.vue';
import UsuarioCreateStepper from '@/Components/Usuarios/UsuarioCreateStepper.vue';

import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    estados: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    nom_usu: '',
    ape_usu: '',
    ci_usu: '',
    ext_ci_usu: 'LP',
    fec_nac_usu: '',
    fot_usu: null,
    cor_usu: '',
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: 'activo',
    tipo_registro: 'Administrador',

    // Nutricionista
    esp_nut: '',
    est_nut: 'activo',

    // Paciente
    sex_pac: '',
    pes_pac: '',
    estatura_pac: '',
    tip_san_pac: '',
    ale_pac: '',
    est_pac: 'activo',
});

const descripcionTipo = computed(() => {
    if (form.tipo_registro === 'Administrador') {
        return 'Registro orientado a gestión administrativa y operativa del sistema.';
    }

    if (form.tipo_registro === 'Nutricionista') {
        return 'Registro con perfil profesional para seguimiento nutricional.';
    }

    return 'Registro de paciente para historia clínica y control nutricional.';
});

const submit = () => {
    form.post(route('admin.usuarios.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Crear usuario" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="Crear Registro"
                description="Alta unificada de usuario con perfil según el tipo seleccionado."
                back-text="Volver"
                :back-href="route('admin.usuarios.index')"
            />
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <HeroPanel
                    eyebrow="Registro de acceso"
                    title="Nuevo usuario"
                    description="Completa datos personales, acceso y perfil específico según corresponda."
                >
                    <template #aside>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
                            Tipo actual
                        </p>

                        <p class="mt-3 text-sm font-semibold text-white">
                            {{ form.tipo_registro }}
                        </p>

                        <p class="mt-2 text-xs leading-5 text-slate-300">
                            {{ descripcionTipo }}
                        </p>
                    </template>
                </HeroPanel>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <UsuarioCreateStepper
                            :form="form"
                            :estados="props.estados"
                            @submit="submit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
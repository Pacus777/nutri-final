<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    usuarios: {
        type: Array,
        default: () => [],
    },
    estados: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    cod_usuarios_nut: '',
    esp_nut: '',
    est_nut: 'activo',
});

const claseInputClaro =
    'mt-1 block w-full !rounded-xl !border-slate-300 !bg-white !text-slate-900 placeholder:!text-slate-400 focus:!border-emerald-500 focus:!ring-emerald-500';

const claseSelectClaro = 'app-select';

const tieneErrores = computed(() => Object.keys(form.errors).length > 0);
const totalUsuarios = computed(() => props.usuarios.length);

const submit = () => {
    form.post(route('admin.nutricionistas.store'));
};
</script>

<template>
    <Head title="Crear nutricionista" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Registrar Nutricionista
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Asocia un usuario activo y habilita su perfil profesional.
                    </p>
                </div>

                <Link
                    :href="route('admin.nutricionistas.index')"
                    class="motion-btn rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-800"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="auth-panel auth-glow auth-fade-up motion-lift relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_18%,rgba(34,197,94,0.12),transparent_22%),radial-gradient(circle_at_85%_82%,rgba(255,255,255,0.05),transparent_24%)]"></div>

                    <div class="relative z-10 grid gap-5 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-green-400">
                                Nuevo perfil profesional
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                Nutricionista
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Selecciona un usuario activo, define su especialidad y deja listo su perfil profesional.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Resumen rápido</p>
                            <div class="mt-3 space-y-2 text-sm text-slate-300">
                                <p>Usuarios disponibles: <span class="font-semibold text-white">{{ totalUsuarios }}</span></p>
                                <p>Estado por defecto: <span class="font-semibold text-emerald-300">activo</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <div v-if="tieneErrores" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4">
                            <p class="text-sm font-semibold text-rose-800">Revisa la información del formulario.</p>
                            <p class="mt-1 text-xs text-rose-700">
                                Hay campos con errores. Corrige los mensajes marcados en rojo para continuar.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <section class="rounded-xl border border-slate-200 p-5">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
                                    Vincular usuario
                                </h3>

                                <div class="mt-4">
                                    <InputLabel for="cod_usuarios_nut" value="Usuario vinculado" />
                                    <select
                                        id="cod_usuarios_nut"
                                        v-model="form.cod_usuarios_nut"
                                        :class="claseSelectClaro"
                                    >
                                        <option value="">Seleccione un usuario</option>
                                        <option
                                            v-for="usuario in usuarios"
                                            :key="usuario.cod_usuarios"
                                            :value="usuario.cod_usuarios"
                                        >
                                            {{ usuario.nom_usu }} - {{ usuario.cor_usu }}
                                        </option>
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Solo se muestran usuarios activos que aún no tienen perfil de nutricionista.
                                    </p>
                                    <InputError class="mt-2" :message="form.errors.cod_usuarios_nut" />
                                </div>
                            </section>

                            <section class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-800">
                                    Datos del perfil profesional
                                </h3>

                                <div class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs text-blue-700 mt-3">
                                    El sistema asignará automáticamente el rol <strong>Nutricionista</strong> al guardar.
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="esp_nut" value="Especialidad" />
                                    <TextInput
                                        id="esp_nut"
                                        type="text"
                                        :class="claseInputClaro"
                                        v-model="form.esp_nut"
                                        maxlength="100"
                                        placeholder="Ej.: Nutrición clínica, deportiva, pediátrica..."
                                    />
                                    <InputError class="mt-2" :message="form.errors.esp_nut" />
                                </div>

                                <div class="mt-6">
                                    <InputLabel for="est_nut" value="Estado del perfil" />
                                    <select
                                        id="est_nut"
                                        v-model="form.est_nut"
                                        :class="claseSelectClaro"
                                    >
                                        <option
                                            v-for="estado in estados"
                                            :key="estado"
                                            :value="estado"
                                        >
                                            {{ estado }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.est_nut" />
                                </div>
                            </section>

                            <div class="flex justify-end gap-2">
                                <Link
                                    :href="route('admin.nutricionistas.index')"
                                    class="motion-btn rounded bg-slate-200 px-4 py-2 text-slate-800 hover:bg-slate-300"
                                >
                                    Cancelar
                                </Link>

                                <PrimaryButton class="motion-btn" :disabled="form.processing">
                                    Guardar nutricionista
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

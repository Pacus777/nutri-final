<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    usuarios: {
        type: Array,
        default: () => [],
    },
    estados: {
        type: Array,
        default: () => [],
    },
    sexos: {
        type: Array,
        default: () => [],
    },
});

const busquedaUsuario = ref('');
const tiposSangre = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

const claseControlClaro =
    'mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500';

const claseSelectClaro = 'app-select';

const form = useForm({
    cod_usuarios_pac: '',
    sex_pac: '',
    pes_pac: '',
    estatura_pac: '',
    tip_san_pac: '',
    ale_pac: '',
    est_pac: 'activo',
});

const usuariosFiltrados = computed(() => {
    const termino = busquedaUsuario.value.trim().toLowerCase();

    if (!termino) {
        return props.usuarios;
    }

    return props.usuarios.filter((usuario) => {
        const nombre = String(usuario.nombre_completo || `${usuario.nom_usu} ${usuario.ape_usu || ''}`)
            .trim()
            .toLowerCase();

        const correo = String(usuario.cor_usu || '').toLowerCase();

        return nombre.includes(termino) || correo.includes(termino);
    });
});

const limitarDecimal = (campo, maxEnteros = 3, maxDecimales = 2) => {
    const valor = String(form[campo] ?? '').replace(/,/g, '.').replace(/[^\d.]/g, '');
    const partes = valor.split('.');
    const enteros = (partes[0] ?? '').slice(0, maxEnteros);
    const decimales = (partes[1] ?? '').slice(0, maxDecimales);

    form[campo] = decimales.length > 0 ? `${enteros}.${decimales}` : enteros;
};

const limpiarCentimetros = () => {
    form.estatura_pac = String(form.estatura_pac ?? '').replace(/\D/g, '').slice(0, 3);
};

const centimetrosAMetros = (valorEntrada) => {
    const cm = Number(String(valorEntrada ?? '').replace(/\D/g, ''));

    if (!Number.isFinite(cm) || cm <= 0) {
        return '';
    }

    return (cm / 100).toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
};

const limpiarAlergias = () => {
    form.ale_pac = String(form.ale_pac ?? '')
        .replace(/[\u0000-\u001F\u007F]/g, ' ')
        .replace(/\s{2,}/g, ' ')
        .trimStart()
        .slice(0, 1000);
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            estatura_pac: centimetrosAMetros(data.estatura_pac),
        }))
        .post(route('pacientes.store'));
};
</script>

<template>
    <Head title="Nuevo paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Nuevo Paciente
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Vincula un usuario activo y registra su perfil clinico inicial.
                    </p>
                </div>

                <Link
                    :href="route('pacientes.index')"
                    class="motion-btn rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="auth-panel auth-glow auth-fade-up motion-lift relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_20%,rgba(34,197,94,0.14),transparent_24%),radial-gradient(circle_at_86%_80%,rgba(255,255,255,0.06),transparent_24%)]"></div>

                    <div class="relative z-10 grid gap-4 md:grid-cols-[1.15fr_0.85fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-green-400">
                                Alta clinica
                            </p>
                            <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                                Registro de paciente
                            </h3>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Esta accion crea o completa el perfil del paciente y mantiene consistencia con el usuario vinculado.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Controles de seguridad</p>
                            <div class="mt-3 space-y-2 text-sm text-slate-300">
                                <p>Usuario vinculado debe estar activo.</p>
                                <p>Rangos numericos y entradas saneadas.</p>
                                <p>Tipo de sangre validado por catalogo.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900 sm:p-7">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-600">
                                    Datos de vinculacion
                                </h4>

                                <div class="mt-4">
                                    <InputLabel for="buscar_usuario" value="Buscar usuario" />
                                    <input
                                        id="buscar_usuario"
                                        v-model="busquedaUsuario"
                                        type="text"
                                        placeholder="Busca por nombre o correo"
                                        :class="claseControlClaro"
                                    >
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="cod_usuarios_pac" value="Usuario vinculado" />
                                    <select
                                        id="cod_usuarios_pac"
                                        v-model="form.cod_usuarios_pac"
                                        :class="claseSelectClaro"
                                        required
                                    >
                                        <option value="">Seleccione un usuario</option>
                                        <option
                                            v-for="usuario in usuariosFiltrados"
                                            :key="usuario.cod_usuarios"
                                            :value="usuario.cod_usuarios"
                                        >
                                            {{ usuario.nombre_completo || `${usuario.nom_usu} ${usuario.ape_usu ?? ''}` }} - {{ usuario.cor_usu }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.cod_usuarios_pac" />
                                </div>
                            </div>

                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 sm:p-5">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                    Perfil del paciente
                                </h4>

                                <div class="mt-4">
                                    <InputLabel for="sex_pac" value="Sexo" />
                                    <select
                                        id="sex_pac"
                                        v-model="form.sex_pac"
                                        :class="claseSelectClaro"
                                        required
                                    >
                                        <option value="">Seleccione una opcion</option>
                                        <option
                                            v-for="sexo in sexos"
                                            :key="sexo"
                                            :value="sexo"
                                        >
                                            {{ sexo }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.sex_pac" />
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="pes_pac" value="Peso (kg)" />
                                        <input
                                            id="pes_pac"
                                            type="text"
                                            inputmode="decimal"
                                            v-model="form.pes_pac"
                                            :class="claseControlClaro"
                                            placeholder="Ej. 70.5"
                                            required
                                            @input="limitarDecimal('pes_pac', 3, 2)"
                                        >
                                        <InputError class="mt-2" :message="form.errors.pes_pac" />
                                    </div>

                                    <div>
                                        <InputLabel for="estatura_pac" value="Estatura (cm)" />
                                        <input
                                            id="estatura_pac"
                                            type="text"
                                            inputmode="numeric"
                                            v-model="form.estatura_pac"
                                            :class="claseControlClaro"
                                            placeholder="Ej. 170"
                                            required
                                            @input="limpiarCentimetros"
                                        >
                                        <p class="mt-1 text-xs text-slate-500">Ingresa la estatura en centimetros. Ejemplo: 170.</p>
                                        <InputError class="mt-2" :message="form.errors.estatura_pac" />
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="tip_san_pac" value="Tipo de sangre" />
                                        <select
                                            id="tip_san_pac"
                                            v-model="form.tip_san_pac"
                                            :class="claseSelectClaro"
                                        >
                                            <option value="">Seleccione tipo de sangre</option>
                                            <option v-for="tipo in tiposSangre" :key="tipo" :value="tipo">{{ tipo }}</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.tip_san_pac" />
                                    </div>

                                    <div>
                                        <InputLabel for="est_pac" value="Estado del perfil" />
                                        <select
                                            id="est_pac"
                                            v-model="form.est_pac"
                                            :class="claseSelectClaro"
                                            required
                                        >
                                            <option
                                                v-for="estado in estados"
                                                :key="estado"
                                                :value="estado"
                                            >
                                                {{ estado }}
                                            </option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.est_pac" />
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="ale_pac" value="Alergias" />
                                    <textarea
                                        id="ale_pac"
                                        v-model="form.ale_pac"
                                        :class="claseControlClaro"
                                        rows="4"
                                        maxlength="1000"
                                        placeholder="Describe alergias relevantes"
                                        @input="limpiarAlergias"
                                    />
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ form.ale_pac?.length || 0 }}/1000 caracteres
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.ale_pac" />
                                </div>
                            </div>

                            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                Si el usuario seleccionado no tiene el rol Paciente, el sistema lo asignara automaticamente al guardar.
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing" class="motion-btn rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                    {{ form.processing ? 'Guardando...' : 'Guardar paciente' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

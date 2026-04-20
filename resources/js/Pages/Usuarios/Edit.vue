<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    usuario: Object,
    roles: Array,
    estados: Array,
});

const form = useForm({
    nom_usu: props.usuario.nom_usu,
    cor_usu: props.usuario.cor_usu,
    cla_usu: '',
    cla_usu_confirmation: '',
    est_usu: props.usuario.est_usu,
    roles: props.usuario.roles,
});

const submit = () => {
    form.put(route('usuarios.update', props.usuario.cod_usuarios));
};

const toggleRol = (codRol) => {
    if (form.roles.includes(codRol)) {
        form.roles = form.roles.filter((id) => id !== codRol);
        return;
    }

    form.roles.push(codRol);
};
</script>

<template>
    <Head title="Editar usuario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Editar Usuario
                </h2>

                <Link
                    :href="route('usuarios.index')"
                    class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div
                                v-if="usuario.tiene_perfil_nutricionista"
                                class="rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                            >
                                Este usuario tiene un perfil registrado en el módulo de nutricionistas.
                                Mientras exista ese perfil, el rol <strong>Nutricionista</strong> es obligatorio.
                            </div>

                            <div>
                                <InputLabel for="nom_usu" value="Nombre completo" />
                                <TextInput
                                    id="nom_usu"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.nom_usu"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.nom_usu" />
                            </div>

                            <div>
                                <InputLabel for="cor_usu" value="Correo" />
                                <TextInput
                                    id="cor_usu"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.cor_usu"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.cor_usu" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="cla_usu" value="Nueva contraseña" />
                                    <TextInput
                                        id="cla_usu"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.cla_usu"
                                        placeholder="Opcional"
                                    />
                                    <InputError class="mt-2" :message="form.errors.cla_usu" />
                                </div>

                                <div>
                                    <InputLabel for="cla_usu_confirmation" value="Confirmar nueva contraseña" />
                                    <TextInput
                                        id="cla_usu_confirmation"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.cla_usu_confirmation"
                                        placeholder="Opcional"
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="est_usu" value="Estado del usuario" />
                                <select
                                    id="est_usu"
                                    v-model="form.est_usu"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="estado in estados"
                                        :key="estado"
                                        :value="estado"
                                    >
                                        {{ estado }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.est_usu" />
                            </div>

                            <div>
                                <InputLabel value="Roles" />
                                <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <label
                                        v-for="rol in roles"
                                        :key="rol.cod_roles"
                                        class="flex items-center gap-3 rounded border border-gray-200 px-4 py-3"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="rol.cod_roles"
                                            :checked="form.roles.includes(rol.cod_roles)"
                                            @change="toggleRol(rol.cod_roles)"
                                        />
                                        <span>{{ rol.nom_rol }}</span>
                                    </label>
                                </div>
                                <InputError class="mt-2" :message="form.errors.roles" />
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="form.processing">
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
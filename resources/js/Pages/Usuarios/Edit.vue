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
            <h2 class="text-xl font-semibold text-gray-800">
                Editar Usuario
            </h2>
        </template>

        <div class="p-6">
            <form @submit.prevent="submit" class="space-y-6">

                <TextInput v-model="form.nom_usu" />
                <InputError :message="form.errors.nom_usu" />

                <TextInput v-model="form.cor_usu" />
                <InputError :message="form.errors.cor_usu" />

                <TextInput type="password" v-model="form.cla_usu" placeholder="Nueva contraseña (opcional)" />

                <select v-model="form.est_usu">
                    <option v-for="estado in estados" :value="estado">
                        {{ estado }}
                    </option>
                </select>

                <div>
                    <label v-for="rol in roles" :key="rol.cod_roles">
                        <input
                            type="checkbox"
                            :checked="form.roles.includes(rol.cod_roles)"
                            @change="toggleRol(rol.cod_roles)"
                        />
                        {{ rol.nom_rol }}
                    </label>
                </div>

                <PrimaryButton>Actualizar</PrimaryButton>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
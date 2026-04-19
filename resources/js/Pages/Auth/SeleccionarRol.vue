<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    cod_rol: null,
});

const submit = () => {
    form.post(route('roles.guardar'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Seleccionar rol" />

        <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow">
            <h1 class="mb-4 text-2xl font-semibold text-gray-800">
                Selecciona tu rol de trabajo
            </h1>

            <p class="mb-6 text-sm text-gray-600">
                Tu cuenta tiene varios roles asignados. Elige con cuál deseas ingresar al sistema.
            </p>

            <form @submit.prevent="submit" class="space-y-4">
                <div
                    v-for="rol in roles"
                    :key="rol.cod_roles"
                    class="rounded border border-gray-200 p-4"
                >
                    <label class="flex cursor-pointer items-start gap-3">
                        <input
                            v-model="form.cod_rol"
                            type="radio"
                            name="cod_rol"
                            :value="rol.cod_roles"
                            class="mt-1"
                        />

                        <div>
                            <div class="font-medium text-gray-800">
                                {{ rol.nom_rol }}
                            </div>
                            <div v-if="rol.des_rol" class="text-sm text-gray-500">
                                {{ rol.des_rol }}
                            </div>
                        </div>
                    </label>
                </div>

                <InputError class="mt-2" :message="form.errors.cod_rol" />

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">
                        Continuar
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
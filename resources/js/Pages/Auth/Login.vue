<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    cor_usu: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div class="auth-panel rounded-[2rem] p-8 text-white shadow-2xl backdrop-blur-xl sm:p-10">
            <div class="mb-10">
                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-green-400">
                    Acceso al sistema
                </p>

                <h2 class="text-3xl font-black uppercase leading-tight tracking-tight sm:text-4xl">
                    Inicia sesión
                </h2>

                <p class="mt-3 text-sm leading-6 text-slate-400">
                    Accede a tu panel para gestionar pacientes, nutricionistas,
                    evaluaciones iniciales y seguimiento nutricional.
                </p>
            </div>

            <div
                v-if="status"
                class="mb-6 rounded-2xl border border-green-500/20 bg-green-500/10 px-4 py-3 text-sm text-green-300"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel
                        for="cor_usu"
                        value="Correo"
                        class="text-sm font-medium text-slate-200"
                    />

                    <TextInput
                        id="cor_usu"
                        type="email"
                        class="block w-full"
                        v-model="form.cor_usu"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Ingresa tu correo"
                    />

                    <InputError class="mt-2" :message="form.errors.cor_usu" />
                </div>

                <div>
                    <InputLabel
                        for="password"
                        value="Contraseña"
                        class="text-sm font-medium text-slate-200"
                    />

                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Ingresa tu contraseña"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between gap-4">
                    <label class="flex items-center gap-3">
                        <Checkbox v-model:checked="form.remember" name="remember" />
                        <span class="text-sm text-slate-300">Recordarme</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-medium text-green-400 transition hover:text-green-300"
                    >
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <div class="pt-2">
                    <PrimaryButton
                        class="w-full"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Ingresar
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
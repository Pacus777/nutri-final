<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();

const usuario = computed(() => page.props.auth?.user ?? null);
const rolActivo = computed(() => page.props.auth?.rol_activo ?? null);
const rolesDisponibles = computed(() => page.props.auth?.roles_disponibles ?? []);

const cambiarRol = (codRol) => {
    router.post(route('roles.cambiar'), {
        cod_rol: codRol,
    });
};

const puedeVerUsuarios = computed(() =>
    ['SuperAdmin', 'Administrador'].includes(rolActivo.value)
);

const puedeVerPanelSuperadmin = computed(() =>
    rolActivo.value === 'SuperAdmin'
);

const puedeVerNutricionistas = computed(() =>
    ['SuperAdmin', 'Administrador'].includes(rolActivo.value)
);

const puedeVerPacientes = computed(() =>
    ['SuperAdmin', 'Administrador'].includes(rolActivo.value)
);

const puedeVerAsignaciones = computed(() =>
    ['SuperAdmin', 'Administrador'].includes(rolActivo.value)
);

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div
            v-if="page.props.flash?.success"
            class="mx-auto mt-4 max-w-7xl rounded border border-green-200 bg-green-50 px-4 py-3 text-green-700 sm:px-6 lg:px-8"
        >
            {{ page.props.flash.success }}
        </div>

        <div class="min-h-screen bg-gray-100">
            <nav class="border-b border-gray-100 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>

                                <NavLink
                                    v-if="puedeVerUsuarios"
                                    :href="route('usuarios.index')"
                                    :active="route().current('usuarios.index') || route().current('usuarios.create') || route().current('usuarios.edit')"
                                >
                                    Usuarios
                                </NavLink>

                                <NavLink
                                    v-if="puedeVerPanelSuperadmin"
                                    :href="route('panel.superadmin')"
                                    :active="route().current('panel.superadmin')"
                                >
                                    Panel SuperAdmin
                                </NavLink>

                                <NavLink
                                    v-if="puedeVerNutricionistas"
                                    :href="route('nutricionistas.index')"
                                    :active="route().current('nutricionistas.index')
                                        || route().current('nutricionistas.create')
                                        || route().current('nutricionistas.edit')
                                        || route().current('nutricionistas.show')"
                                >
                                    Nutricionistas
                                </NavLink>

                                <NavLink
                                    v-if="puedeVerPacientes"
                                    :href="route('pacientes.index')"
                                    :active="route().current('pacientes.index')
                                        || route().current('pacientes.create')
                                        || route().current('pacientes.edit')
                                        || route().current('pacientes.show')"
                                >
                                    Pacientes
                                </NavLink>

                                <NavLink
                                    v-if="puedeVerAsignaciones"
                                    :href="route('asignaciones.index')"
                                    :active="route().current('asignaciones.index')
                                        || route().current('asignaciones.create')
                                        || route().current('asignaciones.historial-paciente')"
                                >
                                    Asignaciones
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-4">
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold">Rol activo:</span>
                                <span class="ml-1">{{ rolActivo ?? 'Sin rol activo' }}</span>
                            </div>

                            <div v-if="rolesDisponibles.length > 1">
                                <select
                                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :value="page.props.auth?.cod_rol_activo ?? ''"
                                    @change="cambiarRol(Number($event.target.value))"
                                >
                                    <option
                                        v-for="rol in rolesDisponibles"
                                        :key="rol.cod_roles"
                                        :value="rol.cod_roles"
                                    >
                                        {{ rol.nom_rol }}
                                    </option>
                                </select>
                            </div>

                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ usuario?.nom_usu }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Perfil
                                        </DropdownLink>

                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Cerrar sesión
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="puedeVerUsuarios"
                            :href="route('usuarios.index')"
                            :active="route().current('usuarios.index') || route().current('usuarios.create') || route().current('usuarios.edit')"
                        >
                            Usuarios
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="puedeVerPanelSuperadmin"
                            :href="route('panel.superadmin')"
                            :active="route().current('panel.superadmin')"
                        >
                            Panel SuperAdmin
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="puedeVerNutricionistas"
                            :href="route('nutricionistas.index')"
                            :active="route().current('nutricionistas.index')
                                || route().current('nutricionistas.create')
                                || route().current('nutricionistas.edit')
                                || route().current('nutricionistas.show')"
                        >
                            Nutricionistas
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="puedeVerPacientes"
                            :href="route('pacientes.index')"
                            :active="route().current('pacientes.index')
                                || route().current('pacientes.create')
                                || route().current('pacientes.edit')
                                || route().current('pacientes.show')"
                        >
                            Pacientes
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="puedeVerAsignaciones"
                            :href="route('asignaciones.index')"
                            :active="route().current('asignaciones.index')
                                || route().current('asignaciones.create')
                                || route().current('asignaciones.historial-paciente')"
                        >
                            Asignaciones
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">
                                {{ usuario?.nom_usu }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ usuario?.cor_usu }}
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold">Rol activo:</span>
                                <span class="ml-1">{{ rolActivo ?? 'Sin rol activo' }}</span>
                            </div>
                        </div>

                        <div v-if="rolesDisponibles.length > 1" class="mt-3 px-4">
                            <select
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :value="page.props.auth?.cod_rol_activo ?? ''"
                                @change="cambiarRol(Number($event.target.value))"
                            >
                                <option
                                    v-for="rol in rolesDisponibles"
                                    :key="rol.cod_roles"
                                    :value="rol.cod_roles"
                                >
                                    {{ rol.nom_rol }}
                                </option>
                            </select>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Perfil
                            </ResponsiveNavLink>

                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Cerrar sesión
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
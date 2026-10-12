<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import HeroPanel from '@/Components/UI/HeroPanel.vue';
import UsuarioTable from '@/Components/Usuarios/UsuarioTable.vue';
import UsuarioStats from '@/Components/Usuarios/UsuarioStats.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    usuarios: {
        type: Object,
        required: true,
    },
    filtros: {
        type: Object,
        default: () => ({ buscar: '' }),
    },
    resumenGlobal: {
        type: Object,
        required: true,
    },
});

const cambiarEstado = (codUsuario, estado) => {
    router.patch(route('admin.usuarios.cambiar-estado', codUsuario), {
        est_usu: estado,
    });
};

const page = usePage();
const busqueda = ref(props.filtros.buscar || '');
let timeoutId = null;

// Búsqueda en servidor en tiempo real con 300ms de debounce
watch(busqueda, (nuevoValor) => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }

    timeoutId = setTimeout(() => {
        router.get(
            route('admin.usuarios.index'),
            { buscar: nuevoValor },
            {
                preserveState: true,
                replace: true,
            }
        );
    }, 300);
});

// Resumen del sistema
const resumen = computed(() => ({
    total: props.resumenGlobal.total,
    activos: props.resumenGlobal.activos,
    inactivos: props.resumenGlobal.inactivos,
    bloqueados: props.resumenGlobal.bloqueados,
}));
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="Gestión de Usuarios"
                description="Administra accesos, estados y perfiles del sistema."
                action-text="Nuevo usuario"
                :action-href="route('admin.usuarios.create')"
            />
        </template>

        <div class="auth-shell py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">


                <HeroPanel
                    eyebrow="Administración interna"
                    title="Panel de usuarios"
                    description="Centraliza la gestión de accesos y estados para mantener la operación segura y ordenada."
                >
                    <template #aside>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Resumen rápido</p>

                        <div class="mt-3 space-y-2 text-sm text-slate-300">
                            <p>
                                Total de usuarios:
                                <span class="font-semibold text-white">{{ resumen.total }}</span>
                            </p>
                            <p>
                                Usuarios activos:
                                <span class="font-semibold text-emerald-300">{{ resumen.activos }}</span>
                            </p>
                        </div>
                    </template>
                </HeroPanel>

                <UsuarioStats :resumen="resumen" />

                <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                        Buscar usuario
                    </label>
                    <input
                        v-model="busqueda"
                        type="text"
                        placeholder="Busca por nombre, correo, código o estado"
                        class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <UsuarioTable
                            :usuarios="usuarios.data"
                            @cambiar-estado="cambiarEstado"
                        />

                        <!-- Paginación responsiva premium -->
                        <Pagination :links="usuarios.links" />

                        <div
                            v-if="page.props.errors?.general"
                            class="mt-4 rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
                        >
                            {{ page.props.errors.general }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
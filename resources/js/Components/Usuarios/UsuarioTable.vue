<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';

defineProps({
    usuarios: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['cambiar-estado']);

// Estado del Modal de Confirmación
const showModal = ref(false);
const modalTitle = ref('');
const modalMessage = ref('');
const modalType = ref('danger');
const pendingAction = ref(null);

const cambiarEstado = (codUsuario, estado) => {
    // Para activar, no es necesario confirmación destructiva
    if (estado === 'activo') {
        emit('cambiar-estado', codUsuario, estado);
        return;
    }

    // Configurar modal según la acción solicitada
    if (estado === 'inactivo') {
        modalTitle.value = 'Inactivar Cuenta';
        modalMessage.value = '¿Estás seguro de que deseas inactivar este usuario? Se cambiará su estado base y el de sus perfiles asociados (nutricionista/paciente) a inactivo.';
        modalType.value = 'warning';
    } else if (estado === 'bloqueado') {
        modalTitle.value = 'Bloquear Cuenta';
        modalMessage.value = '¿Estás seguro de que deseas bloquear este usuario? Se le impedirá el acceso al sistema inmediatamente y se desactivarán sus perfiles asociados.';
        modalType.value = 'danger';
    }

    // Guardar la acción pendiente para ejecutar tras confirmación
    pendingAction.value = () => {
        emit('cambiar-estado', codUsuario, estado);
    };
    showModal.value = true;
};

const confirmarCambio = () => {
    if (pendingAction.value) {
        pendingAction.value();
    }
    showModal.value = false;
    pendingAction.value = null;
};

const cerrarModal = () => {
    showModal.value = false;
    pendingAction.value = null;
};
</script>

<template>
    <div v-if="usuarios.length === 0">
        <EmptyState message="No se encontraron usuarios con los filtros actuales." />
    </div>

    <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        Código
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        Usuario
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        Estado
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        Roles
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                        Acciones
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                <tr
                    v-for="usuario in usuarios"
                    :key="usuario.cod_usuarios"
                    class="transition-colors duration-150 hover:bg-slate-50"
                >
                    <td class="px-4 py-3 text-sm text-slate-700">
                        {{ usuario.cod_usuarios }}
                    </td>

                    <td class="px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">
                            {{ usuario.nombre_completo || usuario.nom_usu }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ usuario.cor_usu }}
                        </p>
                    </td>

                    <td class="px-4 py-3">
                        <StatusBadge :estado="usuario.est_usu" />
                    </td>

                    <td class="px-4 py-3">
                        <span
                            v-if="usuario.roles?.length"
                            class="flex flex-wrap gap-2"
                        >
                            <span
                                v-for="rol in usuario.roles"
                                :key="rol.cod_roles"
                                class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                            >
                                {{ rol.nom_rol }}
                            </span>
                        </span>

                        <span v-else class="text-xs text-rose-600">
                            Sin roles
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <Link
                                :href="route('admin.usuarios.show', usuario.cod_usuarios)"
                                class="motion-btn rounded bg-sky-100 px-2.5 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-200"
                            >
                                Ver ficha
                            </Link>

                            <Link
                                :href="route('admin.usuarios.edit', usuario.cod_usuarios)"
                                class="motion-btn rounded bg-indigo-100 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-200"
                            >
                                Editar
                            </Link>

                            <button
                                v-if="usuario.est_usu !== 'activo'"
                                type="button"
                                class="motion-btn rounded bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                                @click="cambiarEstado(usuario.cod_usuarios, 'activo')"
                            >
                                Activar
                            </button>

                            <button
                                v-if="usuario.est_usu !== 'inactivo'"
                                type="button"
                                class="motion-btn rounded bg-amber-500 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-amber-600"
                                @click="cambiarEstado(usuario.cod_usuarios, 'inactivo')"
                            >
                                Inactivar
                            </button>

                            <button
                                v-if="usuario.est_usu !== 'bloqueado'"
                                type="button"
                                class="motion-btn rounded bg-rose-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-rose-700"
                                @click="cambiarEstado(usuario.cod_usuarios, 'bloqueado')"
                            >
                                Bloquear
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal de confirmación interactivo -->
    <ConfirmModal
        :show="showModal"
        :title="modalTitle"
        :message="modalMessage"
        :type="modalType"
        @confirm="confirmarCambio"
        @close="cerrarModal"
    />
</template>
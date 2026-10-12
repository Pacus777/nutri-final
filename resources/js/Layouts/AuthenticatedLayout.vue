<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ToastList from '@/Components/UI/ToastList.vue';
import SearchModal from '@/Components/UI/SearchModal.vue';
import NotificationsInboxModal from '@/Components/UI/NotificationsInboxModal.vue';
import axios from 'axios';

const page = usePage();
const showSearchModal = ref(false);
const showInboxModal = ref(false);

const usuario = computed(() => page.props.auth?.user ?? null);
const rolActivo = computed(() => page.props.auth?.rol_activo ?? null);
const rolesDisponibles = computed(() => page.props.auth?.roles_disponibles ?? []);

const pacienteCod = computed(() => page.props.paciente?.cod_pacientes ?? null);

const puedeVerMisRegistros = computed(() =>
    rolActivo.value === 'Paciente' && !!pacienteCod.value
);

const puedeVerMiPlan = computed(() =>
    rolActivo.value === 'Paciente' && !!pacienteCod.value
);

const puedeVerPacientesNutricionista = computed(() =>
    rolActivo.value === 'Nutricionista'
);

const etiquetaPanelActivo = computed(() => {
    switch (rolActivo.value) {
        case 'SuperAdmin':
            return 'Panel de control total';
        case 'Administrador':
            return 'Panel administrativo';
        case 'Nutricionista':
            return 'Panel profesional';
        case 'Paciente':
            return 'Panel personal';
        default:
            return 'Panel no definido';
    }
});

const claseRolChip = computed(() => {
    switch (rolActivo.value) {
        case 'SuperAdmin':
            return 'bg-amber-500/15 text-amber-300 ring-amber-500/25';
        case 'Administrador':
            return 'bg-blue-500/15 text-blue-300 ring-blue-500/25';
        case 'Nutricionista':
            return 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/25';
        case 'Paciente':
            return 'bg-violet-500/15 text-violet-300 ring-violet-500/25';
        default:
            return 'bg-slate-500/15 text-slate-300 ring-slate-500/25';
    }
});

const claseRolChipColor = computed(() => {
    switch (rolActivo.value) {
        case 'SuperAdmin':
            return 'bg-amber-50 text-amber-800 ring-amber-600/20 border border-amber-200';
        case 'Administrador':
            return 'bg-sky-50 text-sky-800 ring-sky-600/20 border border-sky-200';
        case 'Nutricionista':
            return 'bg-emerald-50 text-emerald-800 ring-emerald-600/20 border border-emerald-200';
        case 'Paciente':
            return 'bg-violet-50 text-violet-800 ring-violet-600/20 border border-violet-200';
        default:
            return 'bg-slate-50 text-slate-800 ring-slate-600/20 border border-slate-200';
    }
});

const cambiarRol = () => {
    router.post(route('roles.cambiar'));
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

const puedeVerReportes = computed(() =>
    ['SuperAdmin', 'Administrador'].includes(rolActivo.value)
);

const puedeVerValidacionesInformacion = computed(() =>
    rolActivo.value === 'Nutricionista'
);

const showingNavigationDropdown = ref(false);

// Hrefs seguros: intentan resolver la ruta y devuelven null si no existe
const pacienteRegistrosHref = computed(() => {
    try {
        if (!puedeVerMisRegistros.value) return null;
        return route('paciente.registros-alimenticios.historial', pacienteCod.value);
    } catch (e) {
        return null;
    }
});

const pacientePlanHref = computed(() => {
    try {
        if (!puedeVerMiPlan.value) return null;
        return route('paciente.plan.show', pacienteCod.value);
    } catch (e) {
        return null;
    }
});

const nutricionistaPacientesHref = computed(() => {
    try {
        if (!puedeVerPacientesNutricionista.value) return null;
        return route('nutricionista.pacientes.index');
    } catch (e) {
        return null;
    }
});

// --- SISTEMA DE NOTIFICACIONES ---
const notifications = ref([]);
const unreadCount = ref(0);
const showNotificationsDropdown = ref(false);
const pollingInterval = ref(null);
const isFirstLoad = ref(true);

// Recomendaciones clínicas y estados locales para pacientes
const recomendaciones = ref([]);
const recomendacionesPendientesCount = ref(0);
const dropdownTab = ref('notificaciones');
const initialTabForModal = ref('all');
const initialSelectedIdForModal = ref(null);

const fetchRecomendaciones = async () => {
    if (rolActivo.value !== 'Paciente' || !pacienteCod.value) return;
    try {
        const response = await axios.get('/api/paciente/recomendaciones');
        recomendaciones.value = response.data.recomendaciones || [];
        calcularRecomendacionesPendientes();
    } catch (error) {
        console.error('Error al obtener recomendaciones:', error);
    }
};

const calcularRecomendacionesPendientes = () => {
    if (!pacienteCod.value) return;
    let guardados = {};
    try {
        const localData = localStorage.getItem(`estados_rec_${pacienteCod.value}`);
        if (localData) {
            guardados = JSON.parse(localData);
        }
    } catch (e) {
        console.error('Error al parsear estados_rec de localStorage', e);
    }

    const pendientes = recomendaciones.value.filter(r => {
        const estado = guardados[r.cod_seguimientos_nutricionales] || 'pendiente';
        return estado === 'pendiente';
    });
    recomendacionesPendientesCount.value = pendientes.length;
};

const obtenerEstadoInfoLocal = (id) => {
    let guardados = {};
    try {
        const localData = localStorage.getItem(`estados_rec_${pacienteCod.value}`);
        if (localData) {
            guardados = JSON.parse(localData);
        }
    } catch (e) {}
    const estado = guardados[id] || 'pendiente';
    const mapping = {
        pendiente: { label: 'Pendiente', color: 'bg-slate-100 text-slate-600 border-slate-200' },
        leida: { label: 'Leída', color: 'bg-blue-50 text-blue-700 border-blue-200' },
        en_practica: { label: 'En Práctica', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        consulta: { label: 'Tengo Dudas', color: 'bg-amber-50 text-amber-700 border-amber-200' },
    };
    return mapping[estado] || mapping.pendiente;
};

const openRecomendacionInModal = (rec) => {
    showNotificationsDropdown.value = false;
    initialTabForModal.value = 'recomendaciones';
    initialSelectedIdForModal.value = rec.cod_seguimientos_nutricionales;
    showInboxModal.value = true;
};

const openNormalNotificationsInModal = () => {
    showNotificationsDropdown.value = false;
    initialTabForModal.value = 'all';
    initialSelectedIdForModal.value = null;
    showInboxModal.value = true;
};

const fetchNotifications = async () => {
    try {
        if (rolActivo.value === 'Paciente' && pacienteCod.value) {
            await fetchRecomendaciones();
        }

        const response = await axios.get('/api/notificaciones/no-leidas');
        const prevCount = unreadCount.value;
        notifications.value = response.data.notificaciones;
        
        let total = response.data.total_no_leidas;
        if (rolActivo.value === 'Paciente') {
            total += recomendacionesPendientesCount.value;
        }
        unreadCount.value = total;

        // Si hay una nueva notificación y no es la primera carga
        if (!isFirstLoad.value && unreadCount.value > prevCount && notifications.value.length > 0) {
            const latestNotif = notifications.value[0];
            if (window.showToast) {
                let toastType = 'info';
                if (latestNotif.tip_not === 'registro_validado') toastType = 'success';
                else if (latestNotif.tip_not === 'registro_observado') toastType = 'warning';
                else if (latestNotif.tip_not === 'validacion_pendiente') toastType = 'info';

                window.showToast(`${latestNotif.tit_not}: ${latestNotif.des_not}`, toastType);
            }
        }
        isFirstLoad.value = false;
    } catch (error) {
        console.error('Error al obtener notificaciones:', error);
    }
};

const markAsRead = async (notif) => {
    try {
        await axios.post(`/api/notificaciones/${notif.cod_notificaciones}/leer`);
        await fetchNotifications();
        showNotificationsDropdown.value = false;
        if (notif.url_not) {
            router.visit(notif.url_not);
        }
    } catch (error) {
        console.error('Error al marcar la notificación como leída:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notificaciones/leer-todas');
        notifications.value = [];
        unreadCount.value = 0;
    } catch (error) {
        console.error('Error al marcar todas las notificaciones como leídas:', error);
    }
};

const closeNotificationsDropdown = (e) => {
    const bellContainer = document.getElementById('notification-bell-container');
    const mobileBellContainer = document.getElementById('mobile-notification-bell-container');
    if (showNotificationsDropdown.value && 
        bellContainer && !bellContainer.contains(e.target) &&
        (!mobileBellContainer || !mobileBellContainer.contains(e.target))) {
        showNotificationsDropdown.value = false;
    }
};

const handleOpenInboxEvent = (e) => {
    if (e.detail) {
        initialTabForModal.value = e.detail.tab || 'all';
        initialSelectedIdForModal.value = e.detail.selectedId || null;
    }
    showInboxModal.value = true;
};

onMounted(() => {
    fetchNotifications();
    pollingInterval.value = setInterval(fetchNotifications, 60000); // Polling ligero de 60 segundos
    document.addEventListener('click', closeNotificationsDropdown);
    window.addEventListener('open-inbox', handleOpenInboxEvent);
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    document.removeEventListener('click', closeNotificationsDropdown);
    window.removeEventListener('open-inbox', handleOpenInboxEvent);
});
</script>

<template>
    <div>
        <!-- Sistema de Notificaciones Flotantes (Toasts) Globales -->
        <ToastList />

        <div class="min-h-screen bg-gray-100">
            <nav class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md transition-all duration-300">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        <div class="flex items-center gap-8">
                            <!-- Premium Clinical Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-2 group">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-all duration-300">
                                        <svg class="h-6.5 w-6.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-lg font-black tracking-tight text-slate-900 select-none">
                                        Nutri<span class="text-emerald-600 font-extrabold">LaPaz</span>
                                    </span>
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
                                    :href="route('admin.usuarios.index')"
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
                                    v-if="pacienteRegistrosHref"
                                    :href="pacienteRegistrosHref"
                                    :active="route().current('paciente.registros-alimenticios.historial') || route().current('paciente.registros-alimenticios.create') || route().current('paciente.registros-alimenticios.show')"
                                >
                                    Mis registros
                                </NavLink>

                                <NavLink
                                    v-if="pacientePlanHref"
                                    :href="pacientePlanHref"
                                >
                                    Mi plan
                                </NavLink>

                                <NavLink
                                    v-if="nutricionistaPacientesHref"
                                    :href="nutricionistaPacientesHref"
                                    :active="route().current('nutricionista.pacientes.index')"
                                >
                                    Pacientes (profesional)
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

                                <NavLink
                                    v-if="puedeVerValidacionesInformacion"
                                    :href="route('nutricionista.validaciones-informacion.pendientes')"
                                    :active="route().current('nutricionista.validaciones-informacion.pendientes')
                                        || route().current('nutricionista.validaciones-informacion.validar')
                                        || route().current('nutricionista.validaciones-informacion.historial-paciente')
                                        || route().current('nutricionista.validaciones-informacion.observaciones-paciente')"
                                >
                                    Validaciones
                                </NavLink>

                                <NavLink
                                    v-if="puedeVerReportes"
                                    :href="route('reportes.index')"
                                    :active="route().current('reportes.index')"
                                >
                                    Reportes
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-4">
                            <!-- Buscador Global Trigger -->
                            <button
                                v-if="rolActivo !== 'Paciente'"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2 text-sm font-medium text-slate-500 hover:text-slate-800 hover:bg-slate-100/70 hover:border-slate-300 focus:outline-none transition duration-150 ease-in-out cursor-pointer"
                                @click="showSearchModal = true"
                            >
                                <svg
                                    class="h-4 w-4 text-slate-400"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z"
                                    />
                                </svg>
                                <span class="text-xs">Buscar...</span>
                                <kbd class="hidden lg:inline-flex items-center gap-0.5 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[9px] font-semibold text-slate-400 shadow-sm">
                                    <span>Ctrl</span>K
                                </kbd>
                            </button>

                            <!-- Campana de Notificaciones (Desktop) -->
                            <div id="notification-bell-container" class="relative">
                                <button
                                    type="button"
                                    class="relative rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100/70 hover:border-slate-300 focus:outline-none transition duration-150 ease-in-out cursor-pointer"
                                    @click="showNotificationsDropdown = !showNotificationsDropdown"
                                >
                                    <span class="sr-only">Ver notificaciones</span>
                                    <svg
                                        class="h-5 w-5"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                                        />
                                    </svg>

                                    <!-- Pulso Indicador en Rojo -->
                                    <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                                    </span>
                                </button>

                                <!-- Dropdown Panel (Desktop) -->
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                                    enter-to-class="opacity-100 scale-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="opacity-100 scale-100 translate-y-0"
                                    leave-to-class="opacity-0 scale-95 -translate-y-2"
                                >
                                    <div
                                        v-if="showNotificationsDropdown"
                                        class="absolute right-0 z-50 mt-2 w-80 sm:w-96 origin-top-right rounded-2xl border border-slate-100 bg-white/90 backdrop-blur-md p-4 shadow-xl ring-1 ring-black/5"
                                    >
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2">
                                            <h3 class="text-xs font-semibold text-slate-800 uppercase tracking-wider">
                                                Notificaciones ({{ unreadCount }})
                                            </h3>
                                            <button
                                                v-if="unreadCount > 0"
                                                @click="markAllAsRead"
                                                class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition"
                                            >
                                                Marcar todo como leído
                                            </button>
                                        </div>

                                        <!-- Pestañas exclusivas para el Paciente -->
                                        <div v-if="rolActivo === 'Paciente'" class="flex border-b border-slate-100 mb-3 text-xs gap-2">
                                            <button
                                                type="button"
                                                @click="dropdownTab = 'notificaciones'"
                                                class="flex-1 pb-2 font-bold transition text-center"
                                                :class="dropdownTab === 'notificaciones' ? 'text-emerald-600 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-slate-600'"
                                            >
                                                Notificaciones ({{ notifications.length }})
                                            </button>
                                            <button
                                                type="button"
                                                @click="dropdownTab = 'recomendaciones'"
                                                class="flex-1 pb-2 font-bold transition text-center relative"
                                                :class="dropdownTab === 'recomendaciones' ? 'text-emerald-600 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-slate-600'"
                                            >
                                                Sugerencias ({{ recomendacionesPendientesCount }})
                                                <span v-if="recomendacionesPendientesCount > 0" class="absolute -top-1.5 right-1.5 flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                                                </span>
                                            </button>
                                        </div>

                                        <!-- Lista de Notificaciones del Sistema -->
                                        <div v-if="rolActivo !== 'Paciente' || dropdownTab === 'notificaciones'" class="max-h-72 overflow-y-auto space-y-2 divide-y divide-slate-50">
                                            <div
                                                v-for="notif in notifications"
                                                :key="notif.cod_notificaciones"
                                                @click="markAsRead(notif)"
                                                class="group flex gap-3 p-2.5 rounded-xl hover:bg-slate-50/80 transition duration-150 ease-in-out cursor-pointer"
                                            >
                                                <!-- Icono dinámico según tipo -->
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <!-- Pendiente de Validación -->
                                                    <span
                                                        v-if="notif.tip_not === 'validacion_pendiente'"
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-500 group-hover:scale-110 transition"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </span>
                                                    <!-- Registro Validado -->
                                                    <span
                                                        v-if="notif.tip_not === 'registro_validado'"
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 group-hover:scale-110 transition"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </span>
                                                    <!-- Registro Observado -->
                                                    <span
                                                        v-if="notif.tip_not === 'registro_observed' || notif.tip_not === 'registro_observado'"
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-500 group-hover:scale-110 transition"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    </span>
                                                    <!-- Info o General -->
                                                    <span
                                                        v-else
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 text-slate-500 group-hover:scale-110 transition"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </span>
                                                </div>

                                                <!-- Contenido de notificación -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <p class="text-xs font-semibold text-slate-800 truncate">
                                                            {{ notif.tit_not }}
                                                        </p>
                                                        <span class="text-[10px] text-slate-400 flex-shrink-0">
                                                            {{ notif.fec_not }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">
                                                        {{ notif.des_not }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Estado Vacío -->
                                            <div v-if="notifications.length === 0" class="flex flex-col items-center justify-center py-6 text-center">
                                                <div class="rounded-full bg-slate-50 p-3 text-slate-400">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                    </svg>
                                                </div>
                                                <p class="mt-2 text-xs font-medium text-slate-600">Sin notificaciones pendientes</p>
                                                <p class="text-[10px] text-slate-400">Te avisaremos cuando haya novedades.</p>
                                            </div>
                                        </div>

                                        <!-- Lista de Recomendaciones Clínicas (Para el Paciente) -->
                                        <div v-if="rolActivo === 'Paciente' && dropdownTab === 'recomendaciones'" class="max-h-72 overflow-y-auto space-y-2 divide-y divide-slate-50">
                                            <div
                                                v-for="rec in recomendaciones"
                                                :key="rec.cod_seguimientos_nutricionales"
                                                @click="openRecomendacionInModal(rec)"
                                                class="group flex gap-3 p-2.5 rounded-xl hover:bg-slate-50/80 transition duration-150 ease-in-out cursor-pointer"
                                            >
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 group-hover:scale-110 transition">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <p class="text-xs font-semibold text-slate-800 truncate">
                                                            Sugerencia de {{ rec.nutricionista }}
                                                        </p>
                                                        <span class="text-[10px] text-slate-400 flex-shrink-0">
                                                            {{ rec.fecha }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">
                                                        {{ rec.recomendaciones || 'Indicaciones generales escritas.' }}
                                                    </p>
                                                    <span 
                                                        class="inline-block mt-1 text-[9px] font-bold px-1.5 py-0.5 rounded border"
                                                        :class="obtenerEstadoInfoLocal(rec.cod_seguimientos_nutricionales).color"
                                                    >
                                                        {{ obtenerEstadoInfoLocal(rec.cod_seguimientos_nutricionales).label }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div v-if="recomendaciones.length === 0" class="flex flex-col items-center justify-center py-6 text-center">
                                                <div class="rounded-full bg-slate-50 p-3 text-slate-400">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                                    </svg>
                                                </div>
                                                <p class="mt-2 text-xs font-medium text-slate-600">Sin indicaciones aún</p>
                                                <p class="text-[10px] text-slate-400">Cuando tu nutricionista realice un control verás sus consejos aquí.</p>
                                            </div>
                                        </div>

                                        <!-- Footer: Abrir Bandeja -->
                                        <div class="border-t border-slate-100 pt-2.5 mt-2 flex justify-center">
                                            <button
                                                type="button"
                                                @click="rolActivo === 'Paciente' && dropdownTab === 'recomendaciones' ? openRecomendacionInModal({cod_seguimientos_nutricionales: null}) : openNormalNotificationsInModal()"
                                                class="w-full text-center py-1.5 text-xs font-bold text-slate-500 hover:text-emerald-600 bg-slate-50 hover:bg-slate-100/70 rounded-xl transition duration-150 cursor-pointer"
                                            >
                                                Abrir bandeja de mensajes
                                            </button>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Divider line -->
                            <div class="h-6 w-px bg-slate-200"></div>

                            <!-- Role display: modern elegant labels instead of clunky black box -->
                            <div class="flex flex-col items-end text-right">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide ring-1" :class="claseRolChipColor">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="{
                                        'bg-amber-500': rolActivo === 'SuperAdmin',
                                        'bg-sky-500': rolActivo === 'Administrador',
                                        'bg-emerald-500': rolActivo === 'Nutricionista',
                                        'bg-violet-500': rolActivo === 'Paciente'
                                    }"></span>
                                    {{ rolActivo ?? 'Sin rol activo' }}
                                </span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1 select-none">
                                    {{ etiquetaPanelActivo }}
                                </span>
                            </div>

                            <!-- Cambiar Rol button (highly aesthetic) -->
                            <button
                                v-if="rolesDisponibles.length > 1"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-xl border border-emerald-200 bg-emerald-50/50 px-2.5 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition duration-150 cursor-pointer"
                                @click="cambiarRol"
                                title="Cambiar a otro rol asignado"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <span class="hidden md:inline">Cambiar Rol</span>
                            </button>

                            <!-- Profile avatar & dropdown -->
                            <div class="relative ms-2">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button type="button" class="flex items-center gap-2 rounded-xl hover:bg-slate-50 p-1.5 transition duration-150 ease-in-out cursor-pointer group">
                                            <!-- Gradient initials avatar -->
                                            <div class="h-8 w-8 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-100 flex items-center justify-center text-emerald-800 font-extrabold text-xs border border-emerald-200 group-hover:scale-105 transition-all duration-300">
                                                {{ usuario?.nombre_completo ? usuario.nombre_completo.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() : (usuario?.nom_usu ? usuario.nom_usu[0].toUpperCase() : 'U') }}
                                            </div>
                                            
                                            <span class="text-xs font-bold text-slate-700 group-hover:text-slate-900 transition-colors">
                                                {{ usuario?.nom_usu }}
                                            </span>
                                            
                                            <svg class="h-3 w-3 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Mi Perfil
                                        </DropdownLink>

                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Cerrar sesión
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden gap-2">
                            <!-- Campana de Notificaciones (Mobile) -->
                            <div id="mobile-notification-bell-container" class="relative">
                                <button
                                    type="button"
                                    class="relative rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100/70 hover:border-slate-300 focus:outline-none transition duration-150 ease-in-out cursor-pointer"
                                    @click="showNotificationsDropdown = !showNotificationsDropdown"
                                >
                                    <span class="sr-only">Ver notificaciones</span>
                                    <svg
                                        class="h-5 w-5"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                                        />
                                    </svg>

                                    <!-- Pulso Indicador en Rojo -->
                                    <span v-if="unreadCount > 0" class="absolute top-1 right-1 flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                                    </span>
                                </button>

                                <!-- Dropdown Panel (Mobile) -->
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                                    enter-to-class="opacity-100 scale-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="opacity-100 scale-100 translate-y-0"
                                    leave-to-class="opacity-0 scale-95 -translate-y-2"
                                >
                                    <div
                                        v-if="showNotificationsDropdown"
                                        class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-2xl border border-slate-100 bg-white/90 backdrop-blur-md p-4 shadow-xl ring-1 ring-black/5"
                                    >
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2">
                                            <h3 class="text-xs font-semibold text-slate-800 uppercase tracking-wider">
                                                Notificaciones ({{ unreadCount }})
                                            </h3>
                                            <button
                                                v-if="unreadCount > 0"
                                                @click="markAllAsRead"
                                                class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition"
                                            >
                                                Marcar todo como leído
                                            </button>
                                        </div>

                                        <!-- Pestañas para Móvil -->
                                        <div v-if="rolActivo === 'Paciente'" class="flex border-b border-slate-100 mb-3 text-xs gap-2">
                                            <button
                                                type="button"
                                                @click="dropdownTab = 'notificaciones'"
                                                class="flex-1 pb-2 font-bold transition text-center"
                                                :class="dropdownTab === 'notificaciones' ? 'text-emerald-600 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-slate-600'"
                                            >
                                                Alertas ({{ notifications.length }})
                                            </button>
                                            <button
                                                type="button"
                                                @click="dropdownTab = 'recomendaciones'"
                                                class="flex-1 pb-2 font-bold transition text-center relative"
                                                :class="dropdownTab === 'recomendaciones' ? 'text-emerald-600 border-b-2 border-emerald-500' : 'text-slate-400 hover:text-slate-600'"
                                            >
                                                Sugerencias ({{ recomendacionesPendientesCount }})
                                                <span v-if="recomendacionesPendientesCount > 0" class="absolute -top-1.5 right-1.5 flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                                                </span>
                                            </button>
                                        </div>

                                        <!-- Lista de Notificaciones del Sistema -->
                                        <div v-if="rolActivo !== 'Paciente' || dropdownTab === 'notificaciones'" class="max-h-72 overflow-y-auto space-y-2 divide-y divide-slate-50">
                                            <div
                                                v-for="notif in notifications"
                                                :key="notif.cod_notificaciones"
                                                @click="markAsRead(notif)"
                                                class="group flex gap-3 p-2.5 rounded-xl hover:bg-slate-50/80 transition duration-150 ease-in-out cursor-pointer"
                                            >
                                                <!-- Icono dinámico según tipo -->
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <span
                                                        v-if="notif.tip_not === 'validacion_pendiente'"
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-500 group-hover:scale-110 transition"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </span>
                                                    <span
                                                        v-else-if="notif.tip_not === 'registro_validado'"
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 group-hover:scale-110 transition"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </span>
                                                    <span
                                                        v-else-if="notif.tip_not === 'registro_observado'"
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-500 group-hover:scale-110 transition"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 text-slate-500 group-hover:scale-110 transition"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </span>
                                                </div>

                                                <!-- Contenido de notificación -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <p class="text-xs font-semibold text-slate-800 truncate">
                                                            {{ notif.tit_not }}
                                                        </p>
                                                        <span class="text-[10px] text-slate-400 flex-shrink-0">
                                                            {{ notif.fec_not }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">
                                                        {{ notif.des_not }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Estado Vacío -->
                                            <div v-if="notifications.length === 0" class="flex flex-col items-center justify-center py-6 text-center">
                                                <div class="rounded-full bg-slate-50 p-3 text-slate-400">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                    </svg>
                                                </div>
                                                <p class="mt-2 text-xs font-medium text-slate-600">Sin notificaciones pendientes</p>
                                                <p class="text-[10px] text-slate-400">Te avisaremos cuando haya novedades.</p>
                                            </div>
                                        </div>

                                         <!-- Lista de Recomendaciones Clínicas (Para el Paciente - Móvil) -->
                                         <div v-if="rolActivo === 'Paciente' && dropdownTab === 'recomendaciones'" class="max-h-72 overflow-y-auto space-y-2 divide-y divide-slate-50">
                                             <div
                                                 v-for="rec in recomendaciones"
                                                 :key="rec.cod_seguimientos_nutricionales"
                                                 @click="openRecomendacionInModal(rec)"
                                                 class="group flex gap-3 p-2.5 rounded-xl hover:bg-slate-50/80 transition duration-150 ease-in-out cursor-pointer"
                                             >
                                                 <div class="flex-shrink-0 mt-0.5">
                                                     <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 group-hover:scale-110 transition">
                                                         <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                             <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                         </svg>
                                                     </span>
                                                 </div>
                                                 <div class="flex-1 min-w-0">
                                                     <div class="flex items-center justify-between">
                                                         <p class="text-xs font-semibold text-slate-800 truncate">
                                                             Sugerencia de {{ rec.nutricionista }}
                                                         </p>
                                                         <span class="text-[10px] text-slate-400 flex-shrink-0">
                                                             {{ rec.fecha }}
                                                         </span>
                                                     </div>
                                                     <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">
                                                         {{ rec.recomendaciones || 'Indicaciones generales escritas.' }}
                                                     </p>
                                                     <span
                                                         class="inline-block mt-1 text-[9px] font-bold px-1.5 py-0.5 rounded border"
                                                         :class="obtenerEstadoInfoLocal(rec.cod_seguimientos_nutricionales).color"
                                                     >
                                                         {{ obtenerEstadoInfoLocal(rec.cod_seguimientos_nutricionales).label }}
                                                     </span>
                                                 </div>
                                             </div>

                                             <div v-if="recomendaciones.length === 0" class="flex flex-col items-center justify-center py-6 text-center">
                                                 <div class="rounded-full bg-slate-50 p-3 text-slate-400">
                                                     <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                         <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                                     </svg>
                                                 </div>
                                                 <p class="mt-2 text-xs font-medium text-slate-600">Sin indicaciones aún</p>
                                                 <p class="text-[10px] text-slate-400">Cuando tu nutricionista realice un control verás sus consejos aquí.</p>
                                             </div>
                                         </div>

                                         <!-- Footer: Abrir Bandeja -->
                                         <div class="border-t border-slate-100 pt-2.5 mt-2 flex justify-center">
                                             <button
                                                 type="button"
                                                 @click="rolActivo === 'Paciente' && dropdownTab === 'recomendaciones' ? openRecomendacionInModal({cod_seguimientos_nutricionales: null}) : openNormalNotificationsInModal()"
                                                class="w-full text-center py-1.5 text-xs font-bold text-slate-500 hover:text-emerald-600 bg-slate-50 hover:bg-slate-100/70 rounded-xl transition duration-150 cursor-pointer"
                                            >
                                                Abrir bandeja de mensajes
                                            </button>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

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
                            :href="route('admin.usuarios.index')"
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
                            v-if="pacienteRegistrosHref"
                            :href="pacienteRegistrosHref"
                            :active="route().current('paciente.registros-alimenticios.historial') || route().current('paciente.registros-alimenticios.create') || route().current('paciente.registros-alimenticios.show')"
                        >
                            Mis registros
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="pacientePlanHref"
                            :href="pacientePlanHref"
                        >
                            Mi plan
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="nutricionistaPacientesHref"
                            :href="nutricionistaPacientesHref"
                            :active="route().current('nutricionista.pacientes.index')"
                        >
                            Pacientes (profesional)
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

                        <ResponsiveNavLink
                            v-if="puedeVerValidacionesInformacion"
                            :href="route('nutricionista.validaciones-informacion.pendientes')"
                            :active="route().current('nutricionista.validaciones-informacion.pendientes')
                                || route().current('nutricionista.validaciones-informacion.validar')
                                || route().current('nutricionista.validaciones-informacion.historial-paciente')
                                || route().current('nutricionista.validaciones-informacion.observaciones-paciente')"
                        >
                            Validaciones
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="puedeVerReportes"
                            :href="route('reportes.index')"
                            :active="route().current('reportes.index')"
                        >
                            Reportes
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
                            
                            <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Rol activo</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold border" :class="claseRolChipColor">
                                        {{ rolActivo ?? 'Sin rol activo' }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500">{{ etiquetaPanelActivo }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="rolesDisponibles.length > 1" class="mt-3 px-4">
                            <button
                                type="button"
                                class="inline-flex w-full justify-center items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-600/20 transition-all duration-200"
                                @click="cambiarRol"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                Cambiar Rol Activo
                            </button>
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

            <header class="border-b border-slate-200/70 bg-white/80 backdrop-blur-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <div class="app-page-header motion-card-enter rounded-2xl px-4 py-4 sm:px-5 sm:py-5">
                        <slot name="header" />
                    </div>
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>

        <!-- Buscador Global Modal -->
        <SearchModal
            v-if="rolActivo !== 'Paciente'"
            :show="showSearchModal"
            @open="showSearchModal = true"
            @close="showSearchModal = false"
        />

        <!-- Bandeja de Entrada Modal (Gmail-style) -->
        <NotificationsInboxModal
            :show="showInboxModal"
            :initial-tab="initialTabForModal"
            :initial-selected-id="initialSelectedIdForModal"
            @close="showInboxModal = false"
            @refresh="fetchNotifications"
        />
    </div>
</template>
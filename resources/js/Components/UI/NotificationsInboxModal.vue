<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    initialTab: {
        type: String,
        default: 'all',
    },
    initialSelectedId: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['close', 'refresh']);

const notifications = ref([]);
const loading = ref(false);
const selectedNotif = ref(null);
const currentTab = ref('all'); // 'all', 'unread', 'read', 'recomendaciones'

// Lógica de usuario e Inertia
const page = usePage();
const rolActivo = computed(() => page.props.auth?.rol_activo ?? null);
const pacienteCod = computed(() => page.props.paciente?.cod_pacientes ?? null);

// Recomendaciones y estados locales
const recomendaciones = ref([]);
const estadosRecomendaciones = ref({});

const cargarEstadosLocal = () => {
    if (!pacienteCod.value) return;
    try {
        const localData = localStorage.getItem(`estados_rec_${pacienteCod.value}`);
        if (localData) {
            estadosRecomendaciones.value = JSON.parse(localData);
        }
    } catch (e) {
        console.error('Error al cargar estados de localStorage', e);
    }
};

const obtenerEstadoInfoLocal = (id) => {
    const estado = estadosRecomendaciones.value[id] || 'pendiente';
    const mapping = {
        pendiente: { label: 'Pendiente', color: 'bg-slate-100 text-slate-600 border-slate-200', estado: 'pendiente' },
        leida: { label: 'Leída', color: 'bg-blue-50 text-blue-700 border-blue-200', estado: 'leida' },
        en_practica: { label: 'En Práctica', color: 'bg-emerald-50 text-emerald-700 border-emerald-200', estado: 'en_practica' },
        consulta: { label: 'Tengo Dudas', color: 'bg-amber-50 text-amber-700 border-amber-200', estado: 'consulta' },
    };
    return mapping[estado] || mapping.pendiente;
};

const cambiarEstadoRecomendacionLocal = (id, nuevoEstado) => {
    if (!pacienteCod.value) return;
    cargarEstadosLocal();
    const actual = estadosRecomendaciones.value[id] || 'pendiente';
    const destino = actual === nuevoEstado ? 'pendiente' : nuevoEstado;
    
    estadosRecomendaciones.value[id] = destino;
    try {
        localStorage.setItem(
            `estados_rec_${pacienteCod.value}`,
            JSON.stringify(estadosRecomendaciones.value)
        );
        if (selectedNotif.value && selectedNotif.value.cod_seguimientos_nutricionales === id) {
            selectedNotif.value = {
                ...selectedNotif.value
            };
        }
        emit('refresh'); // Actualizar la campana en el layout
        window.dispatchEvent(new CustomEvent('recomendacion-actualizada'));
    } catch (e) {
        console.error('Error al guardar estados de localStorage', e);
    }
};

const fetchRecomendaciones = async () => {
    if (rolActivo.value !== 'Paciente') return;
    try {
        const response = await axios.get('/api/paciente/recomendaciones');
        recomendaciones.value = response.data.recomendaciones || [];
    } catch (e) {
        console.error('Error al obtener recomendaciones en modal:', e);
    }
};

const fetchAllNotifications = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/notificaciones/todas');
        notifications.value = response.data.notificaciones;
        
        // Re-sincronizar la notificación seleccionada si está en la lista y no es recomendación
        if (selectedNotif.value && !selectedNotif.value.esRecomendacion) {
            const updated = notifications.value.find(
                n => n.cod_notificaciones === selectedNotif.value.cod_notificaciones
            );
            if (updated) {
                selectedNotif.value = updated;
            }
        }
    } catch (error) {
        console.error('Error al obtener bandeja de notificaciones:', error);
    } finally {
        loading.value = false;
    }
};

// Cargar notificaciones y recomendaciones al abrir el modal
watch(() => props.show, async (newVal) => {
    if (newVal) {
        loading.value = true;
        await fetchAllNotifications();
        if (rolActivo.value === 'Paciente') {
            await fetchRecomendaciones();
            cargarEstadosLocal();
        }
        
        // Aplicar valores iniciales si se abren desde la campanita
        if (props.initialTab) {
            currentTab.value = props.initialTab;
        } else {
            currentTab.value = 'all';
        }

        if (currentTab.value === 'recomendaciones') {
            if (props.initialSelectedId) {
                const found = recomendaciones.value.find(
                    r => r.cod_seguimientos_nutricionales === props.initialSelectedId
                );
                if (found) {
                    selectedNotif.value = { ...found, esRecomendacion: true };
                } else if (recomendaciones.value.length > 0) {
                    selectedNotif.value = { ...recomendaciones.value[0], esRecomendacion: true };
                }
            } else if (recomendaciones.value.length > 0) {
                selectedNotif.value = { ...recomendaciones.value[0], esRecomendacion: true };
            }
        } else {
            // Seleccionar primera notificación si hay y ninguna seleccionada
            selectedNotif.value = null;
        }
        loading.value = false;
    } else {
        selectedNotif.value = null;
    }
});

const filteredNotifications = computed(() => {
    if (currentTab.value === 'unread') {
        return notifications.value.filter(n => n.est_not === 'no_leido');
    }
    if (currentTab.value === 'read') {
        return notifications.value.filter(n => n.est_not === 'leido');
    }
    return notifications.value;
});

const selectNotification = async (notif) => {
    selectedNotif.value = notif;
    if (notif.est_not === 'no_leido') {
        try {
            await axios.post(`/api/notificaciones/${notif.cod_notificaciones}/leer`);
            notif.est_not = 'leido';
            emit('refresh'); // Actualizar la campana en el layout
        } catch (error) {
            console.error('Error al marcar como leída:', error);
        }
    }
};

const selectRecomendacion = (rec) => {
    selectedNotif.value = {
        ...rec,
        esRecomendacion: true
    };
};

const toggleReadState = async (notif) => {
    try {
        const response = await axios.post(`/api/notificaciones/${notif.cod_notificaciones}/alternar`);
        if (response.data.success) {
            notif.est_not = response.data.est_not;
            emit('refresh'); // Actualizar la campana en el layout
        }
    } catch (error) {
        console.error('Error al alternar estado de la notificación:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notificaciones/leer-todas');
        notifications.value.forEach(n => {
            n.est_not = 'leido';
        });
        emit('refresh');
    } catch (error) {
        console.error('Error al marcar todo como leído:', error);
    }
};

const handleActionClick = (notif) => {
    emit('close');
    if (notif.url_not) {
        router.visit(notif.url_not);
    }
};

const close = () => {
    emit('close');
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 md:p-10 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <!-- Backdrop difuminado premium -->
        <div
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"
            @click="close"
        ></div>

        <!-- Caja de Bandeja de Entrada estilo Mail/Gmail Premium -->
        <div class="relative w-full max-w-5xl h-[80vh] min-h-[500px] transform rounded-3xl border border-slate-200/80 bg-white/95 shadow-[0_20px_50px_rgba(0,0,0,0.15)] transition-all duration-300 flex flex-col overflow-hidden">
            
            <!-- Cabecera de la bandeja -->
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-emerald-500/10 p-2 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 tracking-tight">Centro de Mensajes</h2>
                        <p class="text-xs font-semibold text-slate-500">Historial completo y notificaciones in-app</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="notifications.some(n => n.est_not === 'no_leido')"
                        @click="markAllAsRead"
                        class="text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100/70 border border-emerald-200/50 px-3 py-1.5 rounded-xl transition duration-150"
                    >
                        Marcar todos como leídos
                    </button>
                    
                    <button
                        @click="close"
                        class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition duration-150"
                    >
                        <span class="sr-only">Cerrar</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Cuerpo Principal Split-Screen -->
            <div class="flex-1 flex overflow-hidden">
                
                <!-- Panel Izquierdo: Lista de Mensajes -->
                <div class="w-full md:w-2/5 border-r border-slate-100 flex flex-col overflow-hidden bg-white">
                    <!-- Filtros -->
                    <div class="flex border-b border-slate-50 p-2 gap-1 bg-slate-50/30">
                        <button
                            @click="currentTab = 'all'"
                            class="flex-1 py-1.5 text-[10px] sm:text-xs font-bold rounded-lg transition duration-150"
                            :class="currentTab === 'all' ? 'bg-slate-200/60 text-slate-800' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
                        >
                            Todos
                        </button>
                        <button
                            @click="currentTab = 'unread'"
                            class="flex-1 py-1.5 text-[10px] sm:text-xs font-bold rounded-lg transition duration-150 relative"
                            :class="currentTab === 'unread' ? 'bg-slate-200/60 text-slate-800' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
                        >
                            No leídos
                            <span v-if="notifications.some(n => n.est_not === 'no_leido')" class="absolute top-1 right-1 h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                        </button>
                        <button
                            @click="currentTab = 'read'"
                            class="flex-1 py-1.5 text-[10px] sm:text-xs font-bold rounded-lg transition duration-150"
                            :class="currentTab === 'read' ? 'bg-slate-200/60 text-slate-800' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
                        >
                            Leídos
                        </button>
                        <button
                            v-if="rolActivo === 'Paciente'"
                            @click="currentTab = 'recomendaciones'; selectedNotif = recomendaciones.length > 0 ? { ...recomendaciones[0], esRecomendacion: true } : null"
                            class="flex-1 py-1.5 text-[10px] sm:text-xs font-bold rounded-lg transition duration-150 relative"
                            :class="currentTab === 'recomendaciones' ? 'bg-slate-200/60 text-slate-800' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
                        >
                            Sugerencias
                            <span v-if="recomendaciones.some(r => obtenerEstadoInfoLocal(r.cod_seguimientos_nutricionales).estado === 'pendiente')" class="absolute top-1 right-1 h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                        </button>
                    </div>

                    <!-- Lista de Notificaciones -->
                    <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
                        <div v-if="loading" class="p-6 space-y-4">
                            <div v-for="i in 4" :key="i" class="animate-pulse flex gap-3">
                                <div class="h-10 w-10 rounded-xl bg-slate-100"></div>
                                <div class="flex-1 space-y-2 py-1">
                                    <div class="h-3 w-1/3 rounded bg-slate-100"></div>
                                    <div class="h-2 w-3/4 rounded bg-slate-100"></div>
                                </div>
                            </div>
                        </div>

                        <template v-else-if="currentTab === 'recomendaciones'">
                            <div v-if="recomendaciones.length === 0" class="flex flex-col items-center justify-center p-8 text-center h-48">
                                <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <p class="mt-2 text-xs font-semibold text-slate-600">Bandeja vacía</p>
                                <p class="text-[10px] text-slate-400">No hay sugerencias médicas aún.</p>
                            </div>
                            <button
                                v-else
                                v-for="rec in recomendaciones"
                                :key="rec.cod_seguimientos_nutricionales"
                                type="button"
                                @click="selectRecomendacion(rec)"
                                class="w-full flex items-start gap-3.5 p-4 text-left border-l-4 transition-all duration-150"
                                :class="[
                                    selectedNotif?.cod_seguimientos_nutricionales === rec.cod_seguimientos_nutricionales && selectedNotif?.esRecomendacion
                                        ? 'bg-slate-50 border-emerald-500'
                                        : obtenerEstadoInfoLocal(rec.cod_seguimientos_nutricionales).estado === 'pendiente'
                                            ? 'bg-emerald-50/20 border-emerald-400/50 hover:bg-slate-50/50'
                                            : 'bg-white border-transparent hover:bg-slate-50/40'
                                ]"
                            >
                                <div class="mt-1 flex-shrink-0 flex items-center justify-center">
                                    <span
                                        v-if="obtenerEstadoInfoLocal(rec.cod_seguimientos_nutricionales).estado === 'pendiente'"
                                        class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"
                                    ></span>
                                    <span
                                        v-else
                                        class="h-2 w-2 rounded-full bg-slate-300"
                                    ></span>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-bold text-slate-800 truncate">
                                            Sugerencia de {{ rec.nutricionista }}
                                        </p>
                                        <span class="text-[10px] text-slate-400 flex-shrink-0">
                                            {{ rec.fecha }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">
                                        {{ rec.recomendaciones || 'Indicaciones generales escritas.' }}
                                    </p>
                                    <span
                                        class="inline-block mt-1 text-[9px] font-bold px-1.5 py-0.5 rounded border"
                                        :class="obtenerEstadoInfoLocal(rec.cod_seguimientos_nutricionales).color"
                                    >
                                        {{ obtenerEstadoInfoLocal(rec.cod_seguimientos_nutricionales).label }}
                                    </span>
                                </div>
                            </button>
                        </template>

                        <template v-else>
                            <div v-if="filteredNotifications.length === 0" class="flex flex-col items-center justify-center p-8 text-center h-48">
                                <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v2m16 4h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293H9.414a1 1 0 01-.707-.293L6.293 15.293A1 1 0 005.586 15H3" />
                                </svg>
                                <p class="mt-2 text-xs font-semibold text-slate-600">Bandeja vacía</p>
                                <p class="text-[10px] text-slate-400">No hay mensajes en esta pestaña.</p>
                            </div>

                            <button
                                v-else
                                v-for="notif in filteredNotifications"
                                :key="notif.cod_notificaciones"
                                type="button"
                                @click="selectNotification(notif)"
                                class="w-full flex items-start gap-3.5 p-4 text-left border-l-4 transition-all duration-150"
                                :class="[
                                    selectedNotif?.cod_notificaciones === notif.cod_notificaciones && !selectedNotif?.esRecomendacion
                                        ? 'bg-slate-50 border-emerald-500'
                                        : notif.est_not === 'no_leido'
                                            ? 'bg-emerald-50/20 border-emerald-400/50 hover:bg-slate-50/50'
                                            : 'bg-white border-transparent hover:bg-slate-50/40'
                                ]"
                            >
                                <div class="mt-1 flex-shrink-0 flex items-center justify-center">
                                    <span
                                        v-if="notif.est_not === 'no_leido'"
                                        class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"
                                    ></span>
                                    <span
                                        v-else
                                        class="h-2 w-2 rounded-full bg-slate-300"
                                    ></span>
                                </div>
                                <div class="flex-shrink-0">
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                                        :class="[
                                            notif.tip_not === 'validacion_pendiente' ? 'bg-blue-50 text-blue-500' :
                                            notif.tip_not === 'registro_validado' ? 'bg-emerald-50 text-emerald-500' :
                                            notif.tip_not === 'registro_observado' ? 'bg-amber-50 text-amber-500' :
                                            'bg-slate-50 text-slate-500'
                                        ]"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path v-if="notif.tip_not === 'validacion_pendiente'" stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            <path v-else-if="notif.tip_not === 'registro_validado'" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            <path v-else-if="notif.tip_not === 'registro_observado'" stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p
                                            class="text-xs font-bold text-slate-800 truncate"
                                            :class="{ 'font-black': notif.est_not === 'no_leido' }"
                                        >
                                            {{ notif.tit_not }}
                                        </p>
                                        <span class="text-[10px] text-slate-400 flex-shrink-0">
                                            {{ notif.fec_not }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">
                                        {{ notif.des_not }}
                                    </p>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Panel Derecho: Detalle de Notificación -->
                <div class="hidden md:flex md:w-3/5 flex-col bg-slate-50/40 p-6 overflow-y-auto justify-between">
                    
                    <!-- Contenido del mensaje -->
                    <div v-if="selectedNotif" class="space-y-6">
                        <!-- Cabecera Detalle -->
                        <div class="flex items-start justify-between">
                            <div>
                                <span
                                    class="inline-flex rounded-full border px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider mb-2"
                                    :class="[
                                        selectedNotif.esRecomendacion
                                            ? obtenerEstadoInfoLocal(selectedNotif.cod_seguimientos_nutricionales).color
                                            : selectedNotif.tip_not === 'validacion_pendiente' ? 'bg-blue-50 text-blue-800 border-blue-200' :
                                              selectedNotif.tip_not === 'registro_validado' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                                              selectedNotif.tip_not === 'registro_observado' ? 'bg-amber-50 text-amber-800 border-amber-200' :
                                              'bg-slate-50 text-slate-800 border-slate-200'
                                    ]"
                                >
                                    {{
                                        selectedNotif.esRecomendacion
                                            ? obtenerEstadoInfoLocal(selectedNotif.cod_seguimientos_nutricionales).label
                                            : selectedNotif.tip_not === 'validacion_pendiente' ? 'Pendiente' :
                                              selectedNotif.tip_not === 'registro_validado' ? 'Validado' :
                                              selectedNotif.tip_not === 'registro_observado' ? 'Observado' :
                                              'Mensaje'
                                    }}
                                </span>
                                <h3 class="text-base font-black text-slate-800 tracking-tight leading-snug">
                                    {{ selectedNotif.esRecomendacion ? 'Sugerencia de ' + selectedNotif.nutricionista : selectedNotif.tit_not }}
                                </h3>
                                <p class="text-[10px] font-semibold text-slate-400 mt-1 flex items-center gap-1">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ selectedNotif.esRecomendacion ? 'Emitida el ' + selectedNotif.fecha : 'Enviado el ' + selectedNotif.fec_full + ' (' + selectedNotif.fec_not + ')' }}
                                </p>
                            </div>

                            <button
                                v-if="!selectedNotif.esRecomendacion"
                                @click="toggleReadState(selectedNotif)"
                                class="text-xs font-bold text-slate-500 hover:text-slate-800 bg-white hover:bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-xl transition duration-150 flex items-center gap-1.5 shadow-sm"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2" />
                                </svg>
                                {{ selectedNotif.est_not === 'leido' ? 'No leído' : 'Leído' }}
                            </button>
                        </div>

                        <hr class="border-slate-100" />

                        <!-- Mensaje Cuerpo / Sugerencias -->
                        <div v-if="selectedNotif.esRecomendacion" class="space-y-6">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm space-y-2">
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wide">Evolución y Estado Clínico</h4>
                                    <p class="text-sm text-slate-700 leading-relaxed font-semibold whitespace-pre-line">
                                        {{ selectedNotif.recomendaciones || 'Sin indicaciones generales escritas.' }}
                                    </p>
                                </div>
                                <div v-if="selectedNotif.cambios_sugeridos" class="rounded-2xl border border-amber-100 bg-amber-50/20 p-5 shadow-sm space-y-2">
                                    <h4 class="text-xs font-bold text-amber-600 uppercase tracking-wide">Acciones y Cambios Prescritos</h4>
                                    <p class="text-sm text-slate-700 leading-relaxed font-semibold whitespace-pre-line">
                                        {{ selectedNotif.cambios_sugeridos }}
                                    </p>
                                </div>
                            </div>

                            <!-- Barra de Acciones de Estado -->
                            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm space-y-3">
                                <p class="text-xs font-bold text-slate-500">¿Cómo vas con esta recomendación?</p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="cambiarEstadoRecomendacionLocal(selectedNotif.cod_seguimientos_nutricionales, 'leida')"
                                        class="flex items-center gap-1.5 rounded-xl border border-blue-200 hover:bg-blue-50 px-3.5 py-2 text-xs font-bold text-blue-700 transition"
                                        :class="{'bg-blue-100 ring-2 ring-blue-200/50': estadosRecomendaciones[selectedNotif.cod_seguimientos_nutricionales] === 'leida'}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        Leída
                                    </button>
                                    <button
                                        type="button"
                                        @click="cambiarEstadoRecomendacionLocal(selectedNotif.cod_seguimientos_nutricionales, 'en_practica')"
                                        class="flex items-center gap-1.5 rounded-xl border border-emerald-200 hover:bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 transition"
                                        :class="{'bg-emerald-100 ring-2 ring-emerald-200/50': estadosRecomendaciones[selectedNotif.cod_seguimientos_nutricionales] === 'en_practica'}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        En práctica
                                    </button>
                                    <button
                                        type="button"
                                        @click="cambiarEstadoRecomendacionLocal(selectedNotif.cod_seguimientos_nutricionales, 'consulta')"
                                        class="flex items-center gap-1.5 rounded-xl border border-amber-200 hover:bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700 transition"
                                        :class="{'bg-amber-100 ring-2 ring-amber-200/50': estadosRecomendaciones[selectedNotif.cod_seguimientos_nutricionales] === 'consulta'}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                                        </svg>
                                        Tengo dudas
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje Cuerpo -->
                        <div v-else class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm space-y-4">
                            <p class="text-sm text-slate-600 leading-relaxed font-semibold">
                                {{ selectedNotif.des_not }}
                            </p>
                        </div>

                        <!-- Acción -->
                        <div v-if="!selectedNotif.esRecomendacion && selectedNotif.url_not" class="pt-2">
                            <button
                                @click="handleActionClick(selectedNotif)"
                                class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 py-3 text-sm font-bold text-white shadow-md hover:bg-emerald-700 hover:-translate-y-0.5 transition duration-150 cursor-pointer"
                            >
                                <span>Ir a revisar ahora</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Estado Vacío -->
                    <div v-else class="flex-1 flex flex-col items-center justify-center text-center p-8">
                        <div class="rounded-full bg-slate-100 p-4 text-slate-400">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 class="mt-4 text-sm font-bold text-slate-700">Ningún mensaje seleccionado</h4>
                        <p class="text-xs text-slate-400 max-w-xs mt-1">Haz clic en cualquier mensaje de la lista izquierda para ver su contenido completo.</p>
                    </div>

                    <div v-if="selectedNotif" class="text-[10px] text-center text-slate-400 font-semibold border-t border-slate-100 pt-3">
                        DILAN - Sistema de Monitoreo Nutricional
                    </div>
                </div>

                <!-- Vista Móvil para Detalle (Se muestra sobre la lista si hay una seleccionada) -->
                <div v-if="selectedNotif" class="md:hidden absolute inset-0 z-10 flex flex-col bg-slate-50 justify-between p-6">
                    <div class="space-y-6 overflow-y-auto flex-1 pb-4">
                        <!-- Cabecera Móvil -->
                        <div class="flex items-center justify-between">
                            <button
                                @click="selectedNotif = null"
                                class="text-xs font-bold text-slate-500 hover:text-slate-800 bg-white border border-slate-200 px-3 py-1.5 rounded-xl flex items-center gap-1 shadow-sm"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                Volver
                            </button>

                            <button
                                v-if="!selectedNotif.esRecomendacion"
                                @click="toggleReadState(selectedNotif)"
                                class="text-xs font-bold text-slate-500 hover:text-slate-800 bg-white border border-slate-200 px-3 py-1.5 rounded-xl shadow-sm"
                            >
                                {{ selectedNotif.est_not === 'leido' ? 'No leído' : 'Leído' }}
                            </button>
                        </div>

                        <!-- Info Notificación -->
                        <div>
                            <span
                                class="inline-flex rounded-full border px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider mb-2"
                                :class="[
                                    selectedNotif.esRecomendacion
                                        ? obtenerEstadoInfoLocal(selectedNotif.cod_seguimientos_nutricionales).color
                                        : selectedNotif.tip_not === 'validacion_pendiente' ? 'bg-blue-50 text-blue-800 border-blue-200' :
                                          selectedNotif.tip_not === 'registro_validado' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                                          selectedNotif.tip_not === 'registro_observado' ? 'bg-amber-50 text-amber-800 border-amber-200' :
                                          'bg-slate-50 text-slate-800 border-slate-200'
                                ]"
                            >
                                {{
                                    selectedNotif.esRecomendacion
                                        ? obtenerEstadoInfoLocal(selectedNotif.cod_seguimientos_nutricionales).label
                                        : selectedNotif.tip_not === 'validacion_pendiente' ? 'Pendiente' :
                                          selectedNotif.tip_not === 'registro_validado' ? 'Validado' :
                                          selectedNotif.tip_not === 'registro_observado' ? 'Observado' :
                                          'Mensaje'
                                }}
                            </span>
                            <h3 class="text-base font-black text-slate-800 tracking-tight leading-snug">
                                {{ selectedNotif.esRecomendacion ? 'Sugerencia de ' + selectedNotif.nutricionista : selectedNotif.tit_not }}
                            </h3>
                            <p class="text-[9px] font-semibold text-slate-400 mt-1">
                                {{ selectedNotif.esRecomendacion ? 'Emitida el ' + selectedNotif.fecha : 'Enviado el ' + selectedNotif.fec_full + ' (' + selectedNotif.fec_not + ')' }}
                            </p>
                        </div>

                        <hr class="border-slate-100" />

                        <!-- Mensaje Cuerpo / Sugerencias -->
                        <div v-if="selectedNotif.esRecomendacion" class="space-y-4">
                            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm space-y-2">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wide">Evolución y Estado Clínico</h4>
                                <p class="text-sm text-slate-600 leading-relaxed font-semibold whitespace-pre-line">
                                    {{ selectedNotif.recomendaciones || 'Sin indicaciones generales escritas.' }}
                                </p>
                            </div>
                            <div v-if="selectedNotif.cambios_sugeridos" class="rounded-2xl border border-amber-100 bg-amber-50/20 p-5 shadow-sm space-y-2">
                                <h4 class="text-xs font-bold text-amber-600 uppercase tracking-wide">Acciones y Cambios Prescritos</h4>
                                <p class="text-sm text-slate-600 leading-relaxed font-semibold whitespace-pre-line">
                                    {{ selectedNotif.cambios_sugeridos }}
                                </p>
                            </div>

                            <!-- Barra de Acciones de Estado -->
                            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm space-y-3">
                                <p class="text-xs font-bold text-slate-500">¿Cómo vas con esta recomendación?</p>
                                <div class="flex flex-col gap-2">
                                    <button
                                        type="button"
                                        @click="cambiarEstadoRecomendacionLocal(selectedNotif.cod_seguimientos_nutricionales, 'leida')"
                                        class="flex items-center justify-center gap-1.5 rounded-xl border border-blue-200 hover:bg-blue-50 px-3.5 py-2 text-xs font-bold text-blue-700 transition"
                                        :class="{'bg-blue-100 ring-2 ring-blue-200/50': estadosRecomendaciones[selectedNotif.cod_seguimientos_nutricionales] === 'leida'}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        Leída
                                    </button>
                                    <button
                                        type="button"
                                        @click="cambiarEstadoRecomendacionLocal(selectedNotif.cod_seguimientos_nutricionales, 'en_practica')"
                                        class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-200 hover:bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 transition"
                                        :class="{'bg-emerald-100 ring-2 ring-emerald-200/50': estadosRecomendaciones[selectedNotif.cod_seguimientos_nutricionales] === 'en_practica'}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        En práctica
                                    </button>
                                    <button
                                        type="button"
                                        @click="cambiarEstadoRecomendacionLocal(selectedNotif.cod_seguimientos_nutricionales, 'consulta')"
                                        class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-200 hover:bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700 transition"
                                        :class="{'bg-amber-100 ring-2 ring-amber-200/50': estadosRecomendaciones[selectedNotif.cod_seguimientos_nutricionales] === 'consulta'}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                                        </svg>
                                        Tengo dudas
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje Cuerpo -->
                        <div v-else class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                            <p class="text-sm text-slate-600 leading-relaxed font-semibold">
                                {{ selectedNotif.des_not }}
                            </p>
                        </div>

                        <!-- Acción -->
                        <div v-if="!selectedNotif.esRecomendacion && selectedNotif.url_not" class="pt-2">
                            <button
                                @click="handleActionClick(selectedNotif)"
                                class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 py-3 text-sm font-bold text-white shadow-md hover:bg-emerald-700 transition duration-150"
                            >
                                <span>Ir a revisar ahora</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="text-[10px] text-center text-slate-400 font-semibold border-t border-slate-100 pt-3">
                        DILAN - Sistema de Monitoreo Nutricional
                    </div>
                </div>

            </div>

        </div>
    </div>
</template>

<style scoped>
/* Estilos para scrollbars finas */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

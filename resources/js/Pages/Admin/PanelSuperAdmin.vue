<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    backups: {
        type: Array,
        default: () => [],
    },
    logs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const usuario = computed(() => page.props.auth?.user ?? null);

// Estados de carga e interacción para respaldos
const generandoBackup = ref(false);
const restaurandoBackup = ref(false);
const refrescando = ref(false);
const backupARestaurar = ref(null);
const backupAEliminar = ref(null);

// Estado de navegación entre bitácoras (Actividades vs Errores)
const activeTab = ref('actividades'); // 'actividades' o 'errores'

// Buscador de logs
const searchLogQuery = ref('');

// Paginación independiente para cada bitácora
const itemsPerPage = 30;
const pageActivities = ref(1);
const pageErrors = ref(1);

// Detalle expandido de logs (mapa de index -> boolean)
const expandedLogs = ref({});

const toggleLog = (key) => {
    expandedLogs.value[key] = !expandedLogs.value[key];
};

// Separación de logs: Actividades vs Errores
const rawActivityLogs = computed(() => {
    return props.logs.filter(log => {
        const lvl = log.level.toUpperCase();
        return ['INFO', 'NOTICE', 'DEBUG'].includes(lvl);
    });
});

const rawErrorLogs = computed(() => {
    return props.logs.filter(log => {
        const lvl = log.level.toUpperCase();
        return ['ERROR', 'WARNING', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(lvl);
    });
});

// Logs de actividades filtrados por el buscador
const filteredActivityLogs = computed(() => {
    let list = rawActivityLogs.value;
    if (searchLogQuery.value.trim() !== '') {
        const q = searchLogQuery.value.toLowerCase();
        list = list.filter(log => 
            (log.message && log.message.toLowerCase().includes(q)) ||
            (log.timestamp && log.timestamp.toLowerCase().includes(q)) ||
            (log.details && log.details.toLowerCase().includes(q))
        );
    }
    return list;
});

// Logs de errores filtrados por el buscador
const filteredErrorLogs = computed(() => {
    let list = rawErrorLogs.value;
    if (searchLogQuery.value.trim() !== '') {
        const q = searchLogQuery.value.toLowerCase();
        list = list.filter(log => 
            (log.message && log.message.toLowerCase().includes(q)) ||
            (log.timestamp && log.timestamp.toLowerCase().includes(q)) ||
            (log.details && log.details.toLowerCase().includes(q))
        );
    }
    return list;
});

// Paginación para Actividades
const paginatedActivityLogs = computed(() => {
    const start = (pageActivities.value - 1) * itemsPerPage;
    return filteredActivityLogs.value.slice(start, start + itemsPerPage);
});

const totalActivitiesPages = computed(() => {
    return Math.ceil(filteredActivityLogs.value.length / itemsPerPage) || 1;
});

// Paginación para Errores
const paginatedErrorLogs = computed(() => {
    const start = (pageErrors.value - 1) * itemsPerPage;
    return filteredErrorLogs.value.slice(start, start + itemsPerPage);
});

const totalErrorsPages = computed(() => {
    return Math.ceil(filteredErrorLogs.value.length / itemsPerPage) || 1;
});

// Acciones de respaldos
const crearBackup = () => {
    generandoBackup.value = true;
    router.post(route('panel.superadmin.backups.crear'), {}, {
        onFinish: () => {
            generandoBackup.value = false;
        }
    });
};

const confirmarRestaurar = (filename) => {
    backupARestaurar.value = filename;
};

const ejecutarRestaurar = () => {
    if (!backupARestaurar.value) return;
    restaurandoBackup.value = true;
    router.post(route('panel.superadmin.backups.restaurar', { filename: backupARestaurar.value }), {}, {
        onFinish: () => {
            restaurandoBackup.value = false;
            backupARestaurar.value = null;
        }
    });
};

const confirmarEliminar = (filename) => {
    backupAEliminar.value = filename;
};

const ejecutarEliminar = () => {
    if (!backupAEliminar.value) return;
    router.delete(route('panel.superadmin.backups.eliminar', { filename: backupAEliminar.value }), {
        onFinish: () => {
            backupAEliminar.value = null;
        }
    });
};

// Refrescar datos mediante recarga parcial con Inertia (muy rápido y suave)
const refrescarDatos = () => {
    refrescando.value = true;
    router.reload({
        only: ['logs', 'backups'],
        onFinish: () => {
            refrescando.value = false;
        }
    });
};

// Categorizar logs de acuerdo al mensaje e IP/severidad
const getLogCategory = (message, level) => {
    const lvl = level?.toUpperCase();
    if (['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING'].includes(lvl)) {
        return 'error';
    }
    if (!message) return 'sistema';
    const msg = message.toLowerCase();
    if (msg.includes('sesión') || msg.includes('sesion')) {
        return 'sesion';
    }
    if (msg.includes('usuario') || msg.includes('nutricionista') || msg.includes('paciente')) {
        if (msg.includes('validó') || msg.includes('valido') || msg.includes('validación') || msg.includes('validacion')) {
            return 'validacion';
        }
        if (msg.includes('objetivo')) {
            return 'objetivo';
        }
        return 'usuario';
    }
    if (msg.includes('registro alimenticio') || msg.includes('comida') || msg.includes('consumo') || msg.includes('alimento')) {
        return 'alimentacion';
    }
    if (msg.includes('objetivo') || msg.includes('ajuste') || msg.includes('ajustado')) {
        return 'objetivo';
    }
    if (msg.includes('respaldo') || msg.includes('backup') || msg.includes('base de datos') || msg.includes('restaurada')) {
        return 'backup';
    }
    return 'sistema';
};

const getCategoryStyles = (category) => {
    switch (category) {
        case 'error':
            return {
                bg: 'bg-rose-50 border-rose-200/60 text-rose-600',
                borderL: 'border-rose-500',
                badge: 'bg-rose-50 text-rose-700 border-rose-200'
            };
        case 'sesion':
            return {
                bg: 'bg-blue-50 border-blue-200/60 text-blue-600',
                borderL: 'border-blue-500',
                badge: 'bg-blue-50 text-blue-700 border-blue-200'
            };
        case 'usuario':
            return {
                bg: 'bg-purple-50 border-purple-200/60 text-purple-600',
                borderL: 'border-purple-500',
                badge: 'bg-purple-50 text-purple-700 border-purple-200'
            };
        case 'alimentacion':
            return {
                bg: 'bg-emerald-50 border-emerald-200/60 text-emerald-600',
                borderL: 'border-emerald-500',
                badge: 'bg-emerald-50 text-emerald-700 border-emerald-200'
            };
        case 'validacion':
            return {
                bg: 'bg-teal-50 border-teal-200/60 text-teal-600',
                borderL: 'border-teal-500',
                badge: 'bg-teal-50 text-teal-700 border-teal-200'
            };
        case 'objetivo':
            return {
                bg: 'bg-amber-50 border-amber-200/60 text-amber-600',
                borderL: 'border-amber-500',
                badge: 'bg-amber-50 text-amber-700 border-amber-200'
            };
        case 'backup':
            return {
                bg: 'bg-indigo-50 border-indigo-200/60 text-indigo-600',
                borderL: 'border-indigo-500',
                badge: 'bg-indigo-50 text-indigo-700 border-indigo-200'
            };
        default:
            return {
                bg: 'bg-slate-50 border-slate-200 text-slate-600',
                borderL: 'border-slate-300',
                badge: 'bg-slate-50 text-slate-650 border-slate-200'
            };
    }
};

const getLogIconSvg = (category) => {
    switch (category) {
        case 'sesion':
            return `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.02 5.912L9 17.25H6.75V15h2.25l.938-.938A6.002 6.002 0 0115.75 5.25z" />
            </svg>`;
        case 'usuario':
            return `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a.75.75 0 00.75-.75V12a3 3 0 00-3-3H8.25a3 3 0 00-3 3v5.97a.75.75 0 00.75.75h12zM12 6.75a3 3 0 100-6 3 3 0 000 6z" />
            </svg>`;
        case 'alimentacion':
            return `<svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>`;
        case 'validacion':
            return `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0110 21a3.745 3.745 0 01-3.068-1.593 3.746 3.746 0 01-3.296-1.043 3.746 3.746 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0114 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
            </svg>`;
        case 'objetivo':
            return `<svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a4.875 4.875 0 003.72-4.81V4.875C21 3.84 20.016 3 18.75 3H6.75a3 3 0 00-3 3v12z" />
            </svg>`;
        case 'backup':
            return `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-3.75m0 0v3.75m-16.5-3.75v3.75" />
            </svg>`;
        case 'error':
            return `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>`;
        default:
            return `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.645-.869l.214-1.28z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>`;
    }
};

const getCategoryLabel = (category) => {
    switch (category) {
        case 'sesion': return 'Sesión';
        case 'usuario': return 'Usuario';
        case 'alimentacion': return 'Alimentación';
        case 'validacion': return 'Validación';
        case 'objetivo': return 'Objetivo';
        case 'backup': return 'Respaldo';
        case 'error': return 'Alerta';
        default: return 'Sistema';
    }
};

const formatLogMessage = (message) => {
    if (!message) return '';
    let msg = message.replace(/^ACTIVIDAD:\s*/i, '');
    // Envuelve contenidos entre comillas simples en tags strong de estilo premium
    msg = msg.replace(/'([^']+)'/g, `<strong class="font-semibold text-slate-900 bg-slate-100 border border-slate-200/50 px-1 py-0.5 rounded text-[11px] font-sans">$1</strong>`);
    // Envuelve contenidos entre paréntesis en etiquetas pequeñas tipo pill
    msg = msg.replace(/\(([^)]+)\)/g, `<span class="inline-flex items-center text-[10px] text-slate-500 font-sans font-medium bg-slate-100 border border-slate-200/50 px-1.5 py-0.5 rounded-md ml-1">$1</span>`);
    return msg;
};

</script>

<template>
    <Head title="Panel Técnico SuperAdmin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-900 flex items-center gap-2">
                        <span>Panel Técnico SuperAdmin</span>
                        <span class="rounded-full bg-slate-900 text-white px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wide">
                            Supervisión de Infraestructura
                        </span>
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Visualización segregada de actividad, diagnóstico de errores y gestión de backups de base de datos.
                    </p>
                </div>
                
                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('panel.superadmin.documentacion')"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-3.5 py-2 text-xs font-bold transition shadow-sm shadow-emerald-600/20 cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Documentación del Sistema</span>
                    </Link>

                    <button
                        @click="refrescarDatos"
                        :disabled="refrescando"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-3.5 py-2 text-xs font-bold transition shadow-sm cursor-pointer disabled:opacity-50"
                    >
                        <svg
                            class="h-3.5 w-3.5 text-slate-500"
                            :class="{ 'animate-spin': refrescando }"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>{{ refrescando ? 'Sincronizando...' : 'Refrescar Datos' }}</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- Diseño UX/UI Avanzado en Dos Columnas (Escritorio) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- COLUMNA IZQUIERDA (4 de 12): Base de Datos y Metadata -->
                    <div class="lg:col-span-4 space-y-6">

                        <!-- Acceso Destacado a la Documentación -->
                        <div class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-800 p-5 text-white shadow-md space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black uppercase tracking-wider text-white">Manual & Docs</h4>
                                        <p class="text-[10px] text-slate-400 font-medium">Guías de Arquitectura y BD</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[9px] font-bold text-emerald-400 border border-emerald-500/20">v2.0</span>
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed">
                                Consulta la lógica del clasificador físico vs líquido, el dimensionamiento de porciones y el esquema relacional.
                            </p>

                            <Link
                                :href="route().has('panel.superadmin.documentacion') ? route('panel.superadmin.documentacion') : '/panel-superadmin/documentacion'"
                                class="flex items-center justify-between w-full rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-3.5 py-2 text-xs font-bold transition shadow-xs group"
                            >
                                <span>Abrir Portal de Documentación</span>
                                <svg class="h-4 w-4 transform group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                        </div>
                        
                        <!-- Panel de Datos del Sistema -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Servidor y Estado</h3>
                            
                            <div class="divide-y divide-slate-100 text-xs">
                                <div class="py-2.5 flex justify-between items-center">
                                    <span class="text-slate-500">Base de Datos</span>
                                    <span class="font-bold text-slate-900 font-mono">nutricional-dilan-sistema-final</span>
                                </div>
                                <div class="py-2.5 flex justify-between items-center">
                                    <span class="text-slate-500">Entorno actual</span>
                                    <span class="font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200/50 uppercase text-[10px]">Local (Laragon)</span>
                                </div>
                                <div class="py-2.5 flex justify-between items-center">
                                    <span class="text-slate-500">Gestor de Sesiones</span>
                                    <span class="font-bold text-slate-900 font-mono">File Storage</span>
                                </div>
                                <div class="py-2.5 flex justify-between items-center">
                                    <span class="text-slate-500">Respaldos Generados</span>
                                    <span class="font-bold text-slate-900 font-mono bg-slate-100 px-2 py-0.5 rounded">{{ backups.length }} archivos</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card de Respaldos de Base de Datos -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                <div>
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Respaldos SQL</h3>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Instantáneas físicas (.sql)</p>
                                </div>
                                <button
                                    @click="crearBackup"
                                    :disabled="generandoBackup"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 text-[10px] font-bold transition disabled:opacity-50 cursor-pointer"
                                >
                                    <svg v-if="generandoBackup" class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Respaldar</span>
                                </button>
                            </div>

                            <!-- Listado Compacto de Respaldos -->
                            <div class="divide-y divide-slate-100 max-h-[300px] overflow-y-auto">
                                <div v-for="backup in backups" :key="backup.filename" class="p-3.5 hover:bg-slate-50/50 transition flex flex-col gap-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-bold text-slate-800 truncate font-mono" :title="backup.filename">
                                                {{ backup.filename }}
                                            </p>
                                            <p class="text-[10px] text-slate-400 mt-0.5">
                                                {{ backup.size }} · {{ backup.created_at }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-1.5 text-[9px] font-bold">
                                        <a
                                            :href="route('panel.superadmin.backups.descargar', { filename: backup.filename })"
                                            class="rounded bg-slate-150 border border-slate-200 hover:bg-slate-200/50 text-slate-700 px-2 py-1 transition"
                                        >
                                            Descargar
                                        </a>
                                        <button
                                            @click="confirmarRestaurar(backup.filename)"
                                            class="rounded bg-amber-50 border border-amber-200/60 hover:bg-amber-100/50 text-amber-700 px-2 py-1 transition cursor-pointer"
                                        >
                                            Restaurar
                                        </button>
                                        <button
                                            @click="confirmarEliminar(backup.filename)"
                                            class="rounded bg-rose-50 border border-rose-200/60 hover:bg-rose-100/50 text-rose-700 px-2 py-1 transition cursor-pointer"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </div>

                                <div v-if="backups.length === 0" class="p-8 text-center text-xs text-slate-400">
                                    No hay archivos SQL de respaldos locales.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- COLUMNA DERECHA (8 de 12): Bitácoras (Logs) Segregadas -->
                    <div class="lg:col-span-8">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
                            
                            <!-- Pestañas de Navegación del Visor de Logs -->
                            <div class="border-b border-slate-100 bg-slate-50/70 px-5 pt-3.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div class="flex gap-6">
                                    <button
                                        @click="activeTab = 'actividades'; searchLogQuery = '';"
                                        class="pb-3 text-xs font-bold transition relative cursor-pointer select-none"
                                        :class="activeTab === 'actividades' ? 'text-slate-900 border-b-2 border-slate-900' : 'text-slate-400 hover:text-slate-650'"
                                    >
                                        Bitácora de Actividades
                                        <span class="ml-1.5 px-2 py-0.5 rounded-full text-[9px] bg-slate-200/75 text-slate-700 font-extrabold font-mono">
                                            {{ filteredActivityLogs.length }}
                                        </span>
                                    </button>
                                    
                                    <button
                                        @click="activeTab = 'errores'; searchLogQuery = '';"
                                        class="pb-3 text-xs font-bold transition relative cursor-pointer select-none"
                                        :class="activeTab === 'errores' ? 'text-rose-600 border-b-2 border-rose-600' : 'text-slate-400 hover:text-rose-500'"
                                    >
                                        Errores y Alertas
                                        <span class="ml-1.5 px-2 py-0.5 rounded-full text-[9px] font-mono font-extrabold"
                                            :class="rawErrorLogs.length > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700'"
                                        >
                                            {{ filteredErrorLogs.length }}
                                        </span>
                                    </button>
                                </div>

                                <!-- Botones de descarga y estado permanente -->
                                <div class="flex items-center gap-3 pb-3.5 w-full sm:w-auto justify-end">
                                    <span class="inline-flex items-center gap-1.5 text-[10px] text-slate-400 font-bold uppercase tracking-wider bg-slate-100 border border-slate-200/65 px-2.5 py-1 rounded-lg select-none">
                                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                        Bitácora Inalterable
                                    </span>
                                    <a
                                        :href="route('panel.superadmin.logs.descargar')"
                                        class="flex-1 sm:flex-initial text-center inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-3.5 py-2 text-xs font-bold transition shadow-sm"
                                    >
                                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        <span>Descargar laravel.log</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Barra de búsqueda e información -->
                            <div class="p-4 border-b border-slate-100 bg-white flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                                <div class="relative flex-1 max-w-md">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>
                                    <input
                                        type="text"
                                        v-model="searchLogQuery"
                                        placeholder="Buscar por usuario, alimento, IP, fecha o texto..."
                                        class="pl-9 block w-full rounded-xl border-slate-200 text-xs focus:ring-slate-500 focus:border-slate-500 shadow-sm"
                                    />
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">
                                    Mostrando {{ activeTab === 'actividades' ? filteredActivityLogs.length : filteredErrorLogs.length }} registros
                                </div>
                            </div>

                            <!-- Listado de Entradas con Estilizado UX Mejorado (Línea de Tiempo) -->
                            <div class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
                                
                                <!-- Caso: Bitácora de Actividades -->
                                <template v-if="activeTab === 'actividades'">
                                    <div
                                        v-for="(log, idx) in paginatedActivityLogs"
                                        :key="'act-' + idx"
                                        class="p-4 hover:bg-slate-50/40 transition flex items-start gap-4 border-l-4"
                                        :class="getCategoryStyles(getLogCategory(log.message, log.level)).borderL"
                                    >
                                        <!-- Timeline Left Indicator / Styled Icon Badge -->
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div 
                                                class="h-8 w-8 rounded-xl border flex items-center justify-center transition-all duration-300 shadow-sm"
                                                :class="getCategoryStyles(getLogCategory(log.message, log.level)).bg"
                                                v-html="getLogIconSvg(getLogCategory(log.message, log.level))"
                                            >
                                            </div>
                                        </div>

                                        <!-- Timeline Right Content -->
                                        <div class="flex-1 min-w-0 space-y-1">
                                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <!-- Colored category tag -->
                                                    <span 
                                                        class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider border"
                                                        :class="getCategoryStyles(getLogCategory(log.message, log.level)).badge"
                                                    >
                                                        {{ getCategoryLabel(getLogCategory(log.message, log.level)) }}
                                                    </span>
                                                    <span class="bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded text-[10px] font-bold font-mono">{{ log.environment }}</span>
                                                    <span class="text-slate-400 text-[10px] font-bold font-mono">{{ log.timestamp }}</span>
                                                </div>
                                                
                                                <button
                                                    v-if="log.details"
                                                    @click="toggleLog('act-' + idx)"
                                                    class="text-[10px] font-extrabold text-slate-500 hover:text-slate-900 border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 rounded-lg transition shadow-sm cursor-pointer"
                                                >
                                                    {{ expandedLogs['act-' + idx] ? 'Ocultar Contexto' : 'Ver Contexto' }}
                                                </button>
                                            </div>
                                            
                                            <!-- Message Render -->
                                            <p 
                                                class="text-xs text-slate-750 leading-relaxed font-normal"
                                                v-html="formatLogMessage(log.message)"
                                            ></p>
                                            
                                            <!-- Expanded Context Codeblock -->
                                            <div
                                                v-if="log.details && expandedLogs['act-' + idx]"
                                                class="mt-2.5 rounded-xl bg-slate-900 p-3.5 overflow-x-auto text-[10px] font-mono text-slate-300 border border-slate-800 shadow-inner"
                                            >
                                                <pre class="whitespace-pre-wrap leading-relaxed text-slate-300">{{ log.details }}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="filteredActivityLogs.length === 0" class="py-12 text-center text-slate-400 text-xs">
                                        No se encontraron registros de actividades del sistema.
                                    </div>
                                </template>

                                <!-- Caso: Bitácora de Errores y Alertas -->
                                <template v-if="activeTab === 'errores'">
                                    <div
                                        v-for="(log, idx) in paginatedErrorLogs"
                                        :key="'err-' + idx"
                                        class="p-4 hover:bg-slate-50/40 transition flex items-start gap-4 border-l-4 border-rose-500"
                                        :class="log.level.toUpperCase() === 'WARNING' ? 'border-amber-500 bg-amber-500/[0.01]' : 'bg-rose-500/[0.01]'"
                                    >
                                        <!-- Timeline Left Indicator / Styled Icon Badge -->
                                        <div class="flex-shrink-0 mt-0.5">
                                            <div 
                                                class="h-8 w-8 rounded-xl border flex items-center justify-center transition-all duration-300 shadow-sm"
                                                :class="log.level.toUpperCase() === 'WARNING' ? 'bg-amber-50 border-amber-200/60 text-amber-600' : 'bg-rose-50 border-rose-200/60 text-rose-600'"
                                                v-html="getLogIconSvg(log.level.toUpperCase() === 'WARNING' ? 'objetivo' : 'error')"
                                            >
                                            </div>
                                        </div>

                                        <!-- Timeline Right Content -->
                                        <div class="flex-1 min-w-0 space-y-1">
                                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span 
                                                        class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider border"
                                                        :class="log.level.toUpperCase() === 'WARNING' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-rose-100 text-rose-800 border-rose-200'"
                                                    >
                                                        {{ log.level }}
                                                    </span>
                                                    <span class="bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded text-[10px] font-bold font-mono">{{ log.environment }}</span>
                                                    <span class="text-slate-400 text-[10px] font-bold font-mono">{{ log.timestamp }}</span>
                                                </div>
                                                
                                                <button
                                                    v-if="log.details"
                                                    @click="toggleLog('err-' + idx)"
                                                    class="text-[10px] font-extrabold text-slate-500 hover:text-slate-900 border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 rounded-lg transition shadow-sm cursor-pointer"
                                                >
                                                    {{ expandedLogs['err-' + idx] ? 'Ocultar Traza' : 'Mostrar Traza' }}
                                                </button>
                                            </div>
                                            
                                            <!-- Message Render -->
                                            <p class="text-xs text-slate-800 font-medium leading-relaxed font-mono break-all bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                                                {{ log.message }}
                                            </p>
                                            
                                            <!-- Expanded Context Codeblock -->
                                            <div
                                                v-if="log.details && expandedLogs['err-' + idx]"
                                                class="mt-2.5 rounded-xl bg-slate-900 p-3.5 overflow-x-auto text-[10px] font-mono text-slate-350 border border-slate-800 shadow-inner"
                                            >
                                                <pre class="whitespace-pre-wrap leading-relaxed text-slate-300">{{ log.details }}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="filteredErrorLogs.length === 0" class="py-12 text-center text-slate-400 text-xs">
                                        Todo está en orden. No hay alertas ni errores registrados en la bitácora.
                                    </div>
                                </template>
                            </div>

                            <!-- Paginación de Logs (Actividades) -->
                            <div v-if="activeTab === 'actividades' && totalActivitiesPages > 1" class="p-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <button
                                    @click="pageActivities = Math.max(1, pageActivities - 1)"
                                    :disabled="pageActivities === 1"
                                    class="rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 text-slate-700 px-3 py-1 font-bold transition select-none cursor-pointer"
                                >
                                    Anterior
                                </button>
                                <span class="font-bold text-slate-500 uppercase">
                                    Página {{ pageActivities }} de {{ totalActivitiesPages }}
                                </span>
                                <button
                                    @click="pageActivities = Math.min(totalActivitiesPages, pageActivities + 1)"
                                    :disabled="pageActivities === totalActivitiesPages"
                                    class="rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 text-slate-700 px-3 py-1 font-bold transition select-none cursor-pointer"
                                >
                                    Siguiente
                                </button>
                            </div>

                            <!-- Paginación de Logs (Errores) -->
                            <div v-if="activeTab === 'errores' && totalErrorsPages > 1" class="p-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <button
                                    @click="pageErrors = Math.max(1, pageErrors - 1)"
                                    :disabled="pageErrors === 1"
                                    class="rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 text-slate-700 px-3 py-1 font-bold transition select-none cursor-pointer"
                                >
                                    Anterior
                                </button>
                                <span class="font-bold text-slate-500 uppercase">
                                    Página {{ pageErrors }} de {{ totalErrorsPages }}
                                </span>
                                <button
                                    @click="pageErrors = Math.min(totalErrorsPages, pageErrors + 1)"
                                    :disabled="pageErrors === totalErrorsPages"
                                    class="rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 text-slate-700 px-3 py-1 font-bold transition select-none cursor-pointer"
                                >
                                    Siguiente
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL: ELIMINACIÓN DE BACKUP -->
        <div v-if="backupAEliminar" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300">
            <div class="bg-white rounded-2xl p-5 max-w-md w-full border border-slate-100 shadow-2xl">
                <div class="flex items-center gap-3 text-rose-600">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                    <h5 class="text-sm font-extrabold text-slate-900">¿Eliminar Respaldo?</h5>
                </div>
                <p class="mt-3 text-xs text-slate-500 leading-relaxed">
                    Estás a punto de eliminar el archivo de copia de seguridad <span class="font-bold text-slate-850 font-mono">{{ backupAEliminar }}</span>. Esta acción no se puede deshacer y el archivo se destruirá permanentemente.
                </p>
                <div class="mt-5 flex justify-end gap-2 text-xs">
                    <button
                        @click="backupAEliminar = null"
                        class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 font-bold transition cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="ejecutarEliminar"
                        class="rounded-xl bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 font-bold transition cursor-pointer shadow-sm"
                    >
                        Confirmar y Eliminar
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: RESTAURACIÓN DE BASE DE DATOS -->
        <div v-if="backupARestaurar" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300">
            <div class="bg-white rounded-2xl p-5 max-w-md w-full border border-slate-100 shadow-2xl">
                <div class="flex items-center gap-3 text-amber-600">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                    <h5 class="text-sm font-extrabold text-slate-900">¿Restaurar Base de Datos?</h5>
                </div>
                
                <div class="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800 leading-relaxed font-semibold">
                    ¡ADVERTENCIA! Se reemplazarán todas las tablas y datos actuales de la base de datos con los registros del archivo: <span class="underline font-mono">{{ backupARestaurar }}</span>.
                </div>

                <div class="mt-5 flex justify-end gap-2 text-xs">
                    <button
                        @click="backupARestaurar = null"
                        :disabled="restaurandoBackup"
                        class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 font-bold transition disabled:opacity-50 cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="ejecutarRestaurar"
                        :disabled="restaurandoBackup"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 font-bold transition disabled:opacity-50 cursor-pointer shadow-sm"
                    >
                        <svg v-if="restaurandoBackup" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ restaurandoBackup ? 'Restaurando...' : 'Confirmar y Restaurar' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

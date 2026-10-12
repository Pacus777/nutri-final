<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const query = ref('');
const results = ref([]);
const loading = ref(false);
const selectedIndex = ref(0);
const searchInput = ref(null);
let debounceTimeout = null;

// Categorías estilizadas
const categoryBadges = {
    Paciente: 'bg-emerald-100 text-emerald-800 border-emerald-200/50',
    Nutricionista: 'bg-sky-100 text-sky-800 border-sky-200/50',
    Alimento: 'bg-violet-100 text-violet-800 border-violet-200/50',
};

const categoryIcons = {
    Paciente: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
    Nutricionista: 'M9 12h3.75M9 15h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H9.75M9.75 8.25h4.5m-.75 11.25c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H5.25',
    Alimento: 'M12 18a3.75 3.75 0 0 0 .495-7.467 5.99 5.99 0 0 0-1.925 3.546 5.974 5.974 0 0 1-2.133-1A3.75 3.75 0 0 0 12 18Z',
};

const close = () => {
    query.value = '';
    results.value = [];
    selectedIndex.value = 0;
    emit('close');
};

const performSearch = async () => {
    if (query.value.trim().length < 2) {
        results.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(route('buscar-global'), {
            params: { q: query.value },
        });
        results.value = response.data;
        selectedIndex.value = 0;
    } catch (e) {
        console.error('Error en búsqueda global', e);
    } finally {
        loading.value = false;
    }
};

// Debounce para evitar sobrecargar el servidor
watch(query, (val) => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        performSearch();
    }, 250);
});

// Auto-enfocar input al abrir
watch(() => props.show, async (newVal) => {
    if (newVal) {
        await nextTick();
        if (searchInput.value) {
            searchInput.value.focus();
        }
    }
});

// Navegación por teclado (Flechas y Enter)
const handleKeydown = (e) => {
    if (!props.show) return;

    if (e.key === 'Escape') {
        close();
        e.preventDefault();
    } else if (e.key === 'ArrowDown') {
        if (results.value.length > 0) {
            selectedIndex.value = (selectedIndex.value + 1) % results.value.length;
            e.preventDefault();
            scrollIntoView();
        }
    } else if (e.key === 'ArrowUp') {
        if (results.value.length > 0) {
            selectedIndex.value = (selectedIndex.value - 1 + results.value.length) % results.value.length;
            e.preventDefault();
            scrollIntoView();
        }
    } else if (e.key === 'Enter') {
        if (results.value.length > 0 && results.value[selectedIndex.value]) {
            navigateTo(results.value[selectedIndex.value]);
            e.preventDefault();
        }
    }
};

const scrollIntoView = () => {
    nextTick(() => {
        const activeItem = document.querySelector('.search-result-item-active');
        if (activeItem) {
            activeItem.scrollIntoView({ block: 'nearest' });
        }
    });
};

const navigateTo = (item) => {
    close();
    router.visit(item.url);
};

// Teclas rápidas globales (Ctrl + K o /)
const handleGlobalKeydown = (e) => {
    if (
        (e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')
    ) {
        e.preventDefault();
        if (props.show) close();
        else emit('open');
    } else if (e.key === '/' && !props.show && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
        e.preventDefault();
        emit('open');
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
    if (debounceTimeout) clearTimeout(debounceTimeout);
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:p-6 md:p-20 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <!-- Backdrop translúcido con desenfoque de fondo -->
        <div
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"
            @click="close"
        ></div>

        <!-- Caja de Búsqueda Flotante -->
        <div
            class="relative w-full max-w-2xl transform rounded-2xl border border-slate-200/80 bg-white/95 shadow-[0_20px_50px_rgba(0,0,0,0.15)] transition-all duration-300 flex flex-col max-h-[70vh] overflow-hidden"
            @keydown="handleKeydown"
        >
            <!-- Cabecera / Buscador principal -->
            <div class="relative flex items-center border-b border-slate-100 p-4">
                <svg
                    class="h-5 w-5 text-slate-400 shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z"
                    />
                </svg>

                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    placeholder="Buscar pacientes, nutricionistas o alimentos..."
                    class="ml-3 w-full border-0 bg-transparent p-0 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-0 text-base"
                    autocomplete="off"
                />

                <!-- Spinner de carga -->
                <div v-if="loading" class="flex h-5 w-5 shrink-0 items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Tecla Esc -->
                <kbd
                    v-else
                    class="hidden sm:inline-block rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-500 shadow-sm shrink-0"
                >
                    ESC
                </kbd>
            </div>

            <!-- Resultados de Búsqueda -->
            <div class="flex-1 overflow-y-auto p-3 space-y-2">
                <!-- Estado Inicial o Vacío -->
                <div
                    v-if="query.trim().length < 2"
                    class="py-12 text-center"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-slate-300"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25"
                        />
                    </svg>
                    <p class="mt-4 text-sm font-bold text-slate-500">¿Qué deseas buscar hoy?</p>
                    <p class="mt-1 text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                        Escribe al menos 2 letras para iniciar la búsqueda en pacientes, nutricionistas y base de alimentos.
                    </p>
                </div>

                <!-- Cero Resultados -->
                <div
                    v-else-if="results.length === 0 && !loading"
                    class="py-12 text-center"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-slate-300"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                        />
                    </svg>
                    <p class="mt-4 text-sm font-bold text-slate-500">Sin coincidencias encontradas</p>
                    <p class="mt-1 text-xs text-slate-400">
                        Intenta con otra palabra clave o verifica errores ortográficos.
                    </p>
                </div>

                <!-- Lista de Resultados -->
                <div
                    v-else-if="results.length > 0"
                    class="space-y-1"
                >
                    <button
                        v-for="(item, index) in results"
                        :key="item.id"
                        type="button"
                        class="w-full flex items-center justify-between p-3.5 rounded-xl border border-transparent text-left transition-all duration-150 relative"
                        :class="[
                            index === selectedIndex
                                ? 'bg-slate-50 border-slate-200/60 shadow-sm search-result-item-active'
                                : 'hover:bg-slate-50/50'
                        ]"
                        @click="navigateTo(item)"
                        @mouseenter="selectedIndex = index"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            <!-- Icono Dinámico según Categoría -->
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :class="[
                                    item.categoria === 'Paciente' ? 'bg-emerald-50 text-emerald-600' :
                                    item.categoria === 'Nutricionista' ? 'bg-sky-50 text-sky-600' :
                                    'bg-violet-50 text-violet-600'
                                ]"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :d="categoryIcons[item.categoria]"
                                    />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-800 truncate">
                                    {{ item.titulo }}
                                </p>
                                <p class="text-xs font-semibold text-slate-400 mt-0.5 truncate">
                                    {{ item.subtitulo }}
                                </p>
                            </div>
                        </div>

                        <!-- Categoría Badge -->
                        <span
                            class="rounded-full border px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider shrink-0"
                            :class="categoryBadges[item.categoria]"
                        >
                            {{ item.categoria }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Footer con Ayuda Teclado -->
            <div class="border-t border-slate-100 bg-slate-50/80 px-4 py-3 rounded-b-2xl flex items-center justify-between text-[11px] font-semibold text-slate-500">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1">
                        <kbd class="rounded bg-white px-1.5 py-0.5 border shadow-sm">↑↓</kbd> Navegar
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="rounded bg-white px-1.5 py-0.5 border shadow-sm">Enter</kbd> Seleccionar
                    </span>
                </div>
                <div>
                    Presiona <kbd class="rounded bg-white px-1.5 py-0.5 border shadow-sm">ESC</kbd> para salir
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rounded-Monogram {
    border-radius: 35% 65% 60% 40% / 30% 45% 55% 70%;
}
</style>

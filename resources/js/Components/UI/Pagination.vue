<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
});

// Limpiar las etiquetas para evitar entidades HTML crudas de Laravel
const cleanLabel = (label) => {
    if (label.includes('Previous')) {
        return 'Anterior';
    }
    if (label.includes('Next')) {
        return 'Siguiente';
    }
    return label;
};

const visibleLinks = computed(() => {
    return props.links.map(link => ({
        ...link,
        label: cleanLabel(link.label),
    }));
});
</script>

<template>
    <div v-if="links.length > 3" class="flex items-center justify-between border-t border-slate-100 bg-white px-4 py-3 sm:px-6 mt-6 rounded-xl shadow-sm">
        <!-- Vista móvil simplificada -->
        <div class="flex flex-1 justify-between sm:hidden">
            <template v-for="(link, key) in visibleLinks" :key="key">
                <span
                    v-if="link.url === null && (key === 0 || key === visibleLinks.length - 1)"
                    class="relative inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400 cursor-not-allowed"
                >
                    {{ link.label }}
                </span>
                <Link
                    v-else-if="key === 0 || key === visibleLinks.length - 1"
                    :href="link.url"
                    class="relative inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors duration-150 hover:bg-slate-50 active:bg-slate-100"
                    preserve-state
                    preserve-scroll
                >
                    {{ link.label }}
                </Link>
            </template>
        </div>

        <!-- Vista de escritorio -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">
                    Mostrando resultados del sistema
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm gap-1" aria-label="Pagination">
                    <template v-for="(link, key) in visibleLinks" :key="key">
                        <!-- Enlace inactivo/deshabilitado -->
                        <span
                            v-if="link.url === null"
                            class="relative inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-400 bg-slate-50 rounded-lg cursor-not-allowed border border-slate-100"
                        >
                            {{ link.label }}
                        </span>

                        <!-- Enlace activo (página actual) -->
                        <span
                            v-else-if="link.active"
                            class="relative z-10 inline-flex items-center bg-emerald-600 px-4 py-2 text-xs font-bold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 rounded-lg shadow-sm shadow-emerald-200"
                        >
                            {{ link.label }}
                        </span>

                        <!-- Enlace de página normal y navegable -->
                        <Link
                            v-else
                            :href="link.url"
                            class="relative inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 border border-slate-100 rounded-lg transition-all duration-150 active:scale-95"
                            preserve-state
                            preserve-scroll
                        >
                            {{ link.label }}
                        </Link>
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>

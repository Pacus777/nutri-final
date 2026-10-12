<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let toastIdCount = 0;

// Tipos de iconos vectoriales para cada estado
const icons = {
    success: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    error: 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    warning: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    info: 'M11.25 11.25l.041-.02a.75.75 0 111.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
};

// Clases de estilo según tipo
const typeStyles = {
    success: {
        border: 'border-emerald-500/30',
        bg: 'bg-slate-900/95 backdrop-blur-md',
        text: 'text-slate-100',
        iconColor: 'text-emerald-400',
        iconBg: 'bg-emerald-500/10',
        barColor: 'bg-emerald-500',
    },
    error: {
        border: 'border-rose-500/30',
        bg: 'bg-slate-900/95 backdrop-blur-md',
        text: 'text-slate-100',
        iconColor: 'text-rose-400',
        iconBg: 'bg-rose-500/10',
        barColor: 'bg-rose-500',
    },
    warning: {
        border: 'border-amber-500/30',
        bg: 'bg-slate-900/95 backdrop-blur-md',
        text: 'text-slate-100',
        iconColor: 'text-amber-400',
        iconBg: 'bg-amber-500/10',
        barColor: 'bg-amber-500',
    },
    info: {
        border: 'border-sky-500/30',
        bg: 'bg-slate-900/95 backdrop-blur-md',
        text: 'text-slate-100',
        iconColor: 'text-sky-400',
        iconBg: 'bg-sky-500/10',
        barColor: 'bg-sky-500',
    },
};

const addToast = (message, type = 'success') => {
    if (!message) return;
    
    // Evitar añadir toasts exactamente duplicados en el mismo instante
    const isDuplicate = toasts.value.some(t => t.message === message && t.type === type);
    if (isDuplicate) return;

    const id = ++toastIdCount;
    const toast = {
        id,
        message,
        type,
        progress: 100,
        intervalId: null,
        duration: 4000,
    };

    toasts.value.push(toast);

    // Animación de la barra de progreso
    const steps = 100;
    const stepTime = toast.duration / steps;
    
    toast.intervalId = setInterval(() => {
        toast.progress -= 1;
        if (toast.progress <= 0) {
            clearInterval(toast.intervalId);
            removeToast(id);
        }
    }, stepTime);
};

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index !== -1) {
        if (toasts.value[index].intervalId) {
            clearInterval(toasts.value[index].intervalId);
        }
        toasts.value.splice(index, 1);
    }
};

// Escuchar cambios en flash properties de Inertia
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            addToast(flash.success, 'success');
        }
        if (flash?.error) {
            addToast(flash.error, 'error');
        }
    },
    { deep: true, immediate: true }
);

// Permitir registrar Toast de forma global o manual si fuera necesario
onMounted(() => {
    window.showToast = (message, type = 'success') => {
        addToast(message, type);
    };
});
</script>

<template>
    <div
        class="fixed right-0 top-0 z-50 flex w-full max-w-sm flex-col gap-3 p-4 sm:p-6 pointer-events-none"
        aria-live="assertive"
    >
        <TransitionGroup
            name="toast"
            tag="div"
            class="flex flex-col gap-3"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto relative flex w-full max-w-md overflow-hidden rounded-2xl border bg-slate-900 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transition-all duration-300"
                :class="[typeStyles[toast.type].border, typeStyles[toast.type].bg]"
            >
                <div class="flex w-full items-start p-4 pr-10">
                    <!-- Icono según tipo -->
                    <div
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl"
                        :class="typeStyles[toast.type].iconBg"
                    >
                        <svg
                            class="h-6 w-6"
                            :class="typeStyles[toast.type].iconColor"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :d="icons[toast.type]"
                            />
                        </svg>
                    </div>

                    <!-- Mensaje -->
                    <div class="ml-3 mt-1.5 flex-1">
                        <p
                            class="text-sm font-medium leading-relaxed"
                            :class="typeStyles[toast.type].text"
                        >
                            {{ toast.message }}
                        </p>
                    </div>
                </div>

                <!-- Botón de descarte rápido -->
                <button
                    type="button"
                    @click="removeToast(toast.id)"
                    class="absolute right-2 top-4 flex h-6 w-6 items-center justify-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white transition-all duration-150"
                >
                    <span class="sr-only">Cerrar</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Barra de progreso decreciente -->
                <div
                    class="absolute bottom-0 left-0 h-1 transition-all duration-100 ease-linear"
                    :class="typeStyles[toast.type].barColor"
                    :style="{ width: `${toast.progress}%` }"
                ></div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
/* Transiciones suaves de entrada y salida */
.toast-enter-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-leave-active {
    transition: all 0.3s cubic-bezier(0.5, 0, 0.75, 0);
    position: absolute;
    width: 100%;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(50%) scale(0.9);
}

.toast-move {
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>

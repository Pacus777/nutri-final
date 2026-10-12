<script setup>
import Modal from '@/Components/Modal.vue';
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
    confirmText: {
        type: String,
        default: 'Confirmar',
    },
    cancelText: {
        type: String,
        default: 'Cancelar',
    },
    type: {
        type: String,
        default: 'danger', // 'danger', 'warning', 'info', 'success'
    },
});

const emit = defineEmits(['confirm', 'close']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};

const typeClasses = computed(() => {
    return {
        danger: {
            iconBg: 'bg-rose-100 text-rose-600',
            buttonBg: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
            icon: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        },
        warning: {
            iconBg: 'bg-amber-100 text-amber-600',
            buttonBg: 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500',
            icon: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
        },
        info: {
            iconBg: 'bg-sky-100 text-sky-600',
            buttonBg: 'bg-sky-600 hover:bg-sky-700 focus:ring-sky-500',
            icon: 'M11.25 11.25l.041-.02a.75.75 0 111.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        },
        success: {
            iconBg: 'bg-emerald-100 text-emerald-600',
            buttonBg: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
            icon: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
    }[props.type];
});
</script>

<template>
    <Modal :show="show" max-width="md" :closeable="true" @close="close">
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 rounded-t-xl">
            <div class="sm:flex sm:items-start">
                <div
                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                    :class="typeClasses.iconBg"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            :d="typeClasses.icon"
                        />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <h3
                        class="text-base font-semibold leading-6 text-slate-900 uppercase tracking-wider"
                        id="modal-title"
                    >
                        {{ title }}
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-slate-500">
                            {{ message }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-xl gap-2">
            <button
                type="button"
                class="inline-flex w-full justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-150 active:scale-95 sm:w-auto"
                :class="typeClasses.buttonBg"
                @click="confirm"
            >
                {{ confirmText }}
            </button>
            <button
                type="button"
                class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all duration-150 active:scale-95 sm:mt-0 sm:w-auto"
                @click="close"
            >
                {{ cancelText }}
            </button>
        </div>
    </Modal>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    actionText: {
        type: String,
        default: '',
    },
    actionHref: {
        type: String,
        default: '',
    },
    backText: {
        type: String,
        default: '',
    },
    backHref: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>

            <p
                v-if="description"
                class="mt-1 text-sm text-gray-500"
            >
                {{ description }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link
                v-if="backText && backHref"
                :href="backHref"
                class="motion-btn rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-800"
            >
                {{ backText }}
            </Link>

            <Link
                v-if="actionText && actionHref"
                :href="actionHref"
                class="app-primary-cta"
            >
                {{ actionText }}
            </Link>
        </div>
    </div>
</template>
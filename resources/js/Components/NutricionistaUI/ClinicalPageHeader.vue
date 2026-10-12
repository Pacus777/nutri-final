<script setup>
const props = defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p
                v-if="eyebrow"
                class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600"
            >
                {{ eyebrow }}
            </p>

            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">
                {{ title }}
            </h2>

            <p
                v-if="description"
                class="mt-2 max-w-3xl text-sm leading-6 text-slate-500"
            >
                {{ description }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

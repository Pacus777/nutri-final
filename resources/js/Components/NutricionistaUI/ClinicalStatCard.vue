<script setup>
const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: '--',
    },
    helper: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'neutral',
    },
});

const toneClasses = {
    neutral: 'border-slate-200 bg-white text-slate-900',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    warning: 'border-amber-200 bg-amber-50 text-amber-900',
    danger: 'border-rose-200 bg-rose-50 text-rose-900',
    info: 'border-cyan-200 bg-cyan-50 text-cyan-900',
    violet: 'border-violet-200 bg-violet-50 text-violet-900',
};
</script>

<template>
    <article
        class="rounded-2xl border p-5 shadow-sm"
        :class="toneClasses[tone] || toneClasses.neutral"
    >
        <p class="text-xs font-semibold uppercase tracking-wide opacity-75">
            {{ label }}
        </p>

        <p class="mt-2 text-3xl font-black">
            {{ value }}
        </p>

        <p
            v-if="helper"
            class="mt-1 text-xs opacity-75"
        >
            {{ helper }}
        </p>

        <slot />
    </article>
</template>

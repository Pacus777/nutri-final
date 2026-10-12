<script setup>
const props = defineProps({
    label: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'neutral',
    },
});

const toneClasses = {
    neutral: 'bg-slate-100 text-slate-700 ring-slate-200',
    success: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    warning: 'bg-amber-100 text-amber-700 ring-amber-200',
    danger: 'bg-rose-100 text-rose-700 ring-rose-200',
    info: 'bg-cyan-100 text-cyan-700 ring-cyan-200',
    violet: 'bg-violet-100 text-violet-700 ring-violet-200',
};
</script>

<template>
    <span
        class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold ring-1"
        :class="toneClasses[tone] || toneClasses.neutral"
    >
        <slot>{{ label }}</slot>
    </span>
</template>

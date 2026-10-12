<script setup>
const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'info',
    },
    strong: {
        type: Boolean,
        default: false,
    },
});

const toneClasses = {
    info: 'border-cyan-200 bg-cyan-50 text-cyan-900',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    warning: 'border-amber-200 bg-amber-50 text-amber-900',
    danger: 'border-rose-300 bg-rose-50 text-rose-900',
    neutral: 'border-slate-200 bg-slate-50 text-slate-700',
    violet: 'border-violet-200 bg-violet-50 text-violet-900',
};
</script>

<template>
    <section
        class="rounded-2xl border p-5 shadow-sm"
        :class="[
            toneClasses[tone] || toneClasses.info,
            strong ? 'border-2 shadow-md' : ''
        ]"
    >
        <p
            v-if="title"
            class="text-xs font-semibold uppercase tracking-[0.18em]"
        >
            {{ title }}
        </p>

        <div class="mt-2 text-sm leading-6">
            <slot />
        </div>
    </section>
</template>

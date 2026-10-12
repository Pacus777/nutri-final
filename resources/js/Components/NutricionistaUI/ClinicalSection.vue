<script setup>
const props = defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'neutral',
    },
});

const toneClasses = {
    neutral: 'border-slate-200 bg-white',
    info: 'border-cyan-200 bg-cyan-50',
    success: 'border-emerald-200 bg-emerald-50',
    warning: 'border-amber-200 bg-amber-50',
    danger: 'border-rose-300 bg-rose-50',
    violet: 'border-violet-200 bg-violet-50',
};

const eyebrowClasses = {
    neutral: 'text-slate-500',
    info: 'text-cyan-700',
    success: 'text-emerald-700',
    warning: 'text-amber-700',
    danger: 'text-rose-700',
    violet: 'text-violet-700',
};
</script>

<template>
    <section
        class="rounded-3xl border p-6 shadow-sm"
        :class="toneClasses[tone] || toneClasses.neutral"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p
                    v-if="eyebrow"
                    class="text-xs font-semibold uppercase tracking-[0.18em]"
                    :class="eyebrowClasses[tone] || eyebrowClasses.neutral"
                >
                    {{ eyebrow }}
                </p>

                <h3
                    v-if="title"
                    class="mt-1 text-xl font-black text-slate-950"
                >
                    {{ title }}
                </h3>

                <p
                    v-if="description"
                    class="mt-2 text-sm leading-6 text-slate-600"
                >
                    {{ description }}
                </p>
            </div>

            <slot name="actions" />
        </div>

        <div class="mt-5">
            <slot />
        </div>
    </section>
</template>

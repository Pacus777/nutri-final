<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    variant: {
        type: String,
        default: 'default',
    },
});

const variantClasses = {
    default: 'border-slate-200 bg-white text-slate-900',
    success: 'border-emerald-200 bg-emerald-50/50 text-emerald-700',
    warning: 'border-amber-200 bg-amber-50/50 text-amber-700',
    danger: 'border-rose-200 bg-rose-50/50 text-rose-700',
};
</script>

<template>
    <div
        class="motion-lift rounded-xl border p-4 shadow-sm"
        :class="variantClasses[variant] || variantClasses.default"
    >
        <p class="text-xs uppercase tracking-[0.2em] opacity-70">
            {{ label }}
        </p>

        <p class="mt-2 text-2xl font-semibold">
            {{ value }}
        </p>
    </div>
</template>
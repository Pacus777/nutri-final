<script setup>
defineProps({
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
    <section class="rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
            {{ title }}
        </h3>

        <p
            v-if="description"
            class="mt-2 text-xs text-slate-500"
        >
            {{ description }}
        </p>

        <div class="mt-4">
            <slot />
        </div>
    </section>
</template>
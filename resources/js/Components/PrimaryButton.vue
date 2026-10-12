<script setup>
defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :disabled="disabled"
        class="inline-flex items-center justify-center rounded-2xl bg-green-500 px-5 py-3 text-sm font-semibold uppercase tracking-wide text-white transition duration-200 hover:bg-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/40 disabled:cursor-not-allowed disabled:opacity-50"
    >
        <slot />
    </button>
</template>
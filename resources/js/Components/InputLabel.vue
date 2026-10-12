<script setup>
defineProps({
    value: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <label class="mb-2 block text-sm font-medium text-gray-700">
        <span v-if="value">{{ value }}</span>
        <slot v-else />
    </label>
</template>
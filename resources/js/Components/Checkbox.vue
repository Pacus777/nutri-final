<script setup>
defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        type: String,
        default: null,
    },
});

defineEmits(['update:checked']);
</script>

<template>
    <input
        type="checkbox"
        :checked="checked"
        :value="value"
        @change="$emit('update:checked', $event.target.checked)"
        class="rounded border-white/20 bg-white/5 text-green-500 shadow-sm focus:ring-green-500/40"
    />
</template>
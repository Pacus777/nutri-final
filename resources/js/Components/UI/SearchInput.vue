<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: 'Buscar',
    },
    placeholder: {
        type: String,
        default: 'Escribe para buscar',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
            {{ label }}
        </label>

        <input
            :value="modelValue"
            type="text"
            :placeholder="placeholder"
            class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            @input="$emit('update:modelValue', $event.target.value)"
        >
    </div>
</template>
<script setup>
import { computed, useAttrs } from 'vue';

const model = defineModel({
    type: [String, Number],
    required: false,
});

const props = defineProps({
    type: {
        type: String,
        default: 'text',
    },
});

const attrs = useAttrs();

const inputClasses = computed(() => {
    return [
        'mt-1 block w-full rounded-2xl border px-4 py-3 text-sm shadow-sm transition duration-200',
        'border-white/10 bg-white/5 text-white placeholder:text-slate-400',
        'focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/30',
    ].join(' ');
});
</script>

<template>
    <input
        v-model="model"
        :type="props.type"
        :class="inputClasses"
        v-bind="attrs"
    />
</template>
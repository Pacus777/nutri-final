<script setup>
defineProps({
    message: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
        {{ message }}
    </div>
</template>
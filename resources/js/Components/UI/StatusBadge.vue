<script setup>
import { computed } from 'vue';

const props = defineProps({
    estado: {
        type: String,
        required: true,
    },
});

const clases = computed(() => {
    const estado = props.estado;

    if (estado === 'activo' || estado === 'vigente' || estado === 'cumplido') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (estado === 'inactivo' || estado === 'pausado' || estado === 'pendiente') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    if (estado === 'bloqueado' || estado === 'cancelado' || estado === 'rechazado') {
        return 'bg-rose-100 text-rose-700 ring-rose-200';
    }

    if (estado === 'cerrado' || estado === 'reemplazado') {
        return 'bg-slate-100 text-slate-700 ring-slate-200';
    }

    return 'bg-blue-100 text-blue-700 ring-blue-200';
});
</script>

<template>
    <span
        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1"
        :class="clases"
    >
        {{ estado }}
    </span>
</template>
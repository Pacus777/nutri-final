<script setup>
import StatCard from '@/Components/UI/StatCard.vue';

defineProps({
    resumen: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <StatCard
            label="Total"
            :value="resumen.total"
            variant="default"
        />

        <StatCard
            label="Activos"
            :value="resumen.activos"
            variant="success"
        />

        <StatCard
            label="Inactivos"
            :value="resumen.inactivos"
            variant="warning"
        />

        <StatCard
            label="Bloqueados"
            :value="resumen.bloqueados"
            variant="danger"
        />
    </div>
</template>
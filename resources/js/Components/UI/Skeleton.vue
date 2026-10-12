<script setup>
defineProps({
    type: {
        type: String,
        default: 'line', // 'line', 'card', 'circle', 'avatar', 'chart', 'hero'
    },
    count: {
        type: Number,
        default: 1,
    },
    width: {
        type: String,
        default: 'w-full',
    },
    height: {
        type: String,
        default: 'h-4',
    },
});
</script>

<template>
    <div class="animate-pulse">
        <!-- Tipo: Línea de texto -->
        <div v-if="type === 'line'" class="space-y-3">
            <div
                v-for="i in count"
                :key="i"
                class="rounded bg-slate-200"
                :class="[width, height]"
            ></div>
        </div>

        <!-- Tipo: Círculo (ej. avatar) -->
        <div v-else-if="type === 'circle'" class="space-y-3">
            <div
                v-for="i in count"
                :key="i"
                class="rounded-full bg-slate-200"
                :class="[width, height]"
            ></div>
        </div>

        <!-- Tipo: Tarjeta de estadística -->
        <div v-else-if="type === 'card'" class="grid gap-4">
            <div
                v-for="i in count"
                :key="i"
                class="rounded-[1.5rem] border border-slate-100 bg-white p-5 shadow-sm"
            >
                <div class="h-3 w-2/5 rounded bg-slate-200"></div>
                <div class="mt-4 h-8 w-1/4 rounded bg-slate-200"></div>
                <div class="mt-2 h-3 w-3/4 rounded bg-slate-200"></div>
            </div>
        </div>

        <!-- Tipo: Panel principal (Hero) -->
        <div v-else-if="type === 'hero'" class="rounded-[2rem] border border-slate-100 bg-white p-6 sm:p-8 shadow-sm">
            <div class="grid gap-6 md:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-4">
                    <div class="h-3 w-1/6 rounded bg-slate-200"></div>
                    <div class="h-10 w-2/3 rounded bg-slate-200"></div>
                    <div class="h-4 w-4/5 rounded bg-slate-200"></div>
                    <div class="flex gap-2 pt-2">
                        <div class="h-6 w-20 rounded-full bg-slate-200"></div>
                        <div class="h-6 w-28 rounded-full bg-slate-200"></div>
                    </div>
                </div>
                <div class="rounded-3xl bg-slate-50 p-5 space-y-3">
                    <div class="h-3 w-1/4 rounded bg-slate-200"></div>
                    <div class="h-4 w-1/2 rounded bg-slate-200"></div>
                    <div class="h-3 w-2/3 rounded bg-slate-200"></div>
                </div>
            </div>
        </div>

        <!-- Tipo: Panel de Gráficos / Cuadros -->
        <div v-else-if="type === 'chart'" class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-50 pb-4">
                <div class="space-y-2 w-1/3">
                    <div class="h-4 w-1/2 rounded bg-slate-200"></div>
                    <div class="h-3 w-full rounded bg-slate-200"></div>
                </div>
                <div class="h-6 w-24 rounded-full bg-slate-200"></div>
            </div>
            
            <div class="mt-6 grid gap-6 md:grid-cols-[200px_1fr] md:items-center">
                <!-- Gráfico de Donut ficticio -->
                <div class="mx-auto flex h-48 w-48 items-center justify-center rounded-full border-8 border-slate-100 bg-white p-5 shadow-inner">
                    <div class="h-20 w-20 rounded-full bg-slate-200"></div>
                </div>
                <!-- Barras del gráfico -->
                <div class="space-y-4">
                    <div v-for="i in 3" :key="i" class="space-y-2">
                        <div class="flex justify-between">
                            <div class="h-3 w-1/4 rounded bg-slate-200"></div>
                            <div class="h-3 w-10 rounded bg-slate-200"></div>
                        </div>
                        <div class="h-3 w-full rounded-full bg-slate-100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

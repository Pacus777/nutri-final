<script setup>
import { computed } from 'vue';

const props = defineProps({
    steps: {
        type: Array,
        required: true,
    },
    currentStep: {
        type: Number,
        required: true,
    },
    allowStepClick: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['step-click']);

const totalSteps = computed(() => props.steps.length);

const progressPercentage = computed(() => {
    if (totalSteps.value <= 1) {
        return 100;
    }

    return (props.currentStep / (totalSteps.value - 1)) * 100;
});

const stepState = (index) => {
    if (index < props.currentStep) {
        return 'completed';
    }

    if (index === props.currentStep) {
        return 'current';
    }

    return 'pending';
};

const circleClasses = (index) => {
    const state = stepState(index);

    if (state === 'completed') {
        return 'border-emerald-600 bg-emerald-600 text-white';
    }

    if (state === 'current') {
        return 'border-emerald-600 bg-white text-emerald-700 ring-4 ring-emerald-100';
    }

    return 'border-slate-300 bg-white text-slate-400';
};

const textClasses = (index) => {
    const state = stepState(index);

    if (state === 'completed') {
        return 'text-emerald-700';
    }

    if (state === 'current') {
        return 'text-slate-900';
    }

    return 'text-slate-400';
};

const descriptionClasses = (index) => {
    const state = stepState(index);

    if (state === 'pending') {
        return 'text-slate-400';
    }

    return 'text-slate-500';
};

const handleStepClick = (index) => {
    if (!props.allowStepClick) {
        return;
    }

    emit('step-click', index);
};
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="relative">
            <div class="absolute left-0 top-5 hidden h-1 w-full rounded-full bg-slate-200 md:block"></div>

            <div
                class="absolute left-0 top-5 hidden h-1 rounded-full bg-emerald-600 transition-all duration-300 md:block"
                :style="{ width: `${progressPercentage}%` }"
            ></div>

            <div class="relative grid gap-4 md:grid-cols-5">
                <button
                    v-for="(step, index) in steps"
                    :key="step.key || index"
                    type="button"
                    class="group flex gap-3 text-left md:flex-col md:items-center md:text-center"
                    :class="allowStepClick ? 'cursor-pointer' : 'cursor-default'"
                    @click="handleStepClick(index)"
                >
                    <span
                        class="z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-bold transition-all duration-200"
                        :class="circleClasses(index)"
                    >
                        <span v-if="stepState(index) === 'completed'">
                            ✓
                        </span>

                        <span v-else>
                            {{ index + 1 }}
                        </span>
                    </span>

                    <span class="block">
                        <span
                            class="block text-sm font-semibold"
                            :class="textClasses(index)"
                        >
                            {{ step.title }}
                        </span>

                        <span
                            v-if="step.description"
                            class="mt-1 block text-xs leading-5"
                            :class="descriptionClasses(index)"
                        >
                            {{ step.description }}
                        </span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
<script setup>
defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="auth-panel auth-glow auth-fade-up motion-lift relative mb-6 overflow-hidden rounded-[1.6rem] p-6 text-white sm:p-7">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_18%,rgba(34,197,94,0.12),transparent_22%),radial-gradient(circle_at_85%_82%,rgba(255,255,255,0.05),transparent_24%)]"></div>

        <div class="relative z-10 grid gap-5 md:grid-cols-[1.2fr_0.8fr]">
            <div>
                <p
                    v-if="eyebrow"
                    class="text-xs font-semibold uppercase tracking-[0.22em] text-green-400"
                >
                    {{ eyebrow }}
                </p>

                <h3 class="mt-2 text-3xl font-black uppercase tracking-tight text-white">
                    {{ title }}
                </h3>

                <p
                    v-if="description"
                    class="mt-3 max-w-2xl text-sm leading-6 text-slate-400"
                >
                    {{ description }}
                </p>
            </div>

            <div
                v-if="$slots.aside"
                class="rounded-3xl border border-white/10 bg-white/5 p-4"
            >
                <slot name="aside" />
            </div>
        </div>
    </div>
</template>
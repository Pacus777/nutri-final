<script setup>
</script>

<template>
    <div class="auth-shell min-h-screen px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-7xl items-center justify-center">
            <div class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] bg-white/70 shadow-2xl backdrop-blur-sm lg:grid-cols-[1.08fr_0.92fr]">
                <!-- LADO VISUAL -->
                <div class="hidden p-8 lg:block xl:p-10">
                    <div class="flex h-full flex-col justify-between rounded-[1.8rem] border border-white/40 bg-white/40 p-6 backdrop-blur-sm">
                        <div class="auth-panel auth-glow auth-fade-up relative overflow-hidden rounded-[2rem] p-8 xl:p-10">                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(34,197,94,0.10),transparent_25%),radial-gradient(circle_at_80%_80%,rgba(255,255,255,0.04),transparent_22%)]"></div>
                            <div class="relative z-10 flex h-full flex-col justify-between">
                                <div>
                                    <div class="mb-8">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-500/10 ring-1 ring-green-500/20">
                                                <div class="h-4 w-4 rounded-sm bg-green-500"></div>
                                            </div>

                                            <span class="text-sm font-semibold tracking-[0.22em] text-white/70 uppercase">
                                                Nutricional System
                                            </span>
                                        </div>
                                    </div>

                                    <div class="max-w-xl">
                                        <h1 class="text-5xl font-black uppercase leading-[1] tracking-tight text-white xl:text-6xl">
                                            El sistema
                                            <span class="text-green-500">que acompaña</span>
                                            tu proceso
                                            <span class="text-green-500">nutricional</span>
                                        </h1>

                                        <p class="mt-6 max-w-md text-sm leading-6 text-slate-400 xl:text-base">
                                            Gestiona pacientes, nutricionistas y evaluaciones iniciales
                                            en una plataforma clara, moderna y enfocada en el seguimiento real.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-10 grid grid-cols-2 gap-5">
                                    <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                        <div class="mb-3 h-28 rounded-2xl bg-[linear-gradient(135deg,rgba(34,197,94,0.16),rgba(255,255,255,0.02))]"></div>
                                        <p class="text-sm font-semibold text-white">
                                            Gestión centralizada
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-slate-400">
                                            Pacientes, nutricionistas, asignaciones y evaluaciones en un solo lugar.
                                        </p>
                                    </div>

                                    <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                        <div class="mb-3 flex h-28 items-center justify-center rounded-2xl bg-[radial-gradient(circle,rgba(34,197,94,0.22),transparent_55%)]">
                                            <div class="h-16 w-16 rounded-full border border-green-400/40"></div>
                                        </div>
                                        <p class="text-sm font-semibold text-white">
                                            Seguimiento profesional
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-slate-400">
                                            Diseñado para flujos reales de atención nutricional y continuidad del caso.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="px-2 pt-6 text-xs text-slate-500">
                            Plataforma visualmente moderna para el sistema nutricional.
                        </p>
                    </div>
                </div>

                <!-- LADO FORMULARIO -->
                <div class="flex items-center justify-center p-6 sm:p-8 xl:p-10">
                    <div class="auth-fade-up-delay w-full max-w-md">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
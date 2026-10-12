<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { animate, createTimeline, stagger } from 'animejs';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const currentTestimonial = ref(0);
const testimonials = [
    { name: 'María González', text: 'Bajé 12kg en 4 meses con un plan personalizado. ¡Mi nutricionista ajustaba todo según mis avances!', role: 'Paciente desde 2025', avatar: 'MG' },
    { name: 'Carlos Rodríguez', text: 'Por fin entendí cómo alimentarme bien. El seguimiento diario y las gráficas me motivaron cada semana.', role: 'Paciente desde 2024', avatar: 'CR' },
    { name: 'Ana Martínez', text: 'El registro alimenticio me hizo consciente de lo que comía. Los reportes PDF son muy profesionales.', role: 'Paciente desde 2025', avatar: 'AM' },
];

let testimonialInterval = null;
const observers = [];

const setTestimonial = (index) => {
    currentTestimonial.value = index;
    if (document.querySelector('.testimonial-content')) {
        animate('.testimonial-content', {
            opacity: [0, 1],
            translateY: [15, 0],
            duration: 500,
            ease: 'outQuad',
        });
    }
};

const nextTestimonial = () => {
    setTestimonial((currentTestimonial.value + 1) % testimonials.length);
};

onMounted(() => {
    // ===== NAV =====
    if (document.querySelector('.landing-nav')) {
        animate('.landing-nav', {
            opacity: [0, 1],
            translateY: [-15, 0],
            duration: 800,
            ease: 'outQuad',
            delay: 100,
        });
    }

    // ===== HERO =====
    if (document.querySelector('.hero-overline')) {
        createTimeline({ defaults: { ease: 'outQuad' } })
            .add('.hero-overline', {
                opacity: [0, 1],
                translateY: [20, 0],
                duration: 700,
            })
            .add('.hero-title-line', {
                opacity: [0, 1],
                translateY: [40, 0],
                duration: 900,
                delay: stagger(100),
            }, '-=400')
            .add('.hero-desc', {
                opacity: [0, 1],
                translateY: [20, 0],
                duration: 700,
            }, '-=400')
            .add('.hero-cta-btn', {
                opacity: [0, 1],
                translateY: [20, 0],
                duration: 600,
                delay: stagger(100),
            }, '-=300')
            .add('.hero-visual', {
                opacity: [0, 1],
                translateX: [60, 0],
                duration: 1000,
            }, '-=800')
            .add('.hero-float-badge', {
                opacity: [0, 1],
                scale: [0.8, 1],
                duration: 600,
                delay: stagger(150),
            }, '-=500');
    }

    // ===== SCROLL-TRIGGERED =====
    const reveal = (root, selector, config, threshold = 0.15) => {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const targets = e.target.querySelectorAll(selector);
                    if (targets && targets.length > 0) {
                        animate(targets, config);
                    }
                    obs.unobserve(e.target);
                }
            });
        }, { threshold });
        const el = document.querySelector(root);
        if (el) {
            obs.observe(el);
            observers.push(obs);
        }
    };

    // Section headers
    document.querySelectorAll('.section-header').forEach(header => {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const children = e.target.children;
                    if (children && children.length > 0) {
                        animate(children, {
                            opacity: [0, 1],
                            translateY: [30, 0],
                            duration: 800,
                            delay: stagger(100),
                            ease: 'outQuad',
                        });
                    }
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.3 });
        obs.observe(header);
        observers.push(obs);
    });

    // Stats
    const statsEl = document.querySelector('.stats-section');
    if (statsEl) {
        const statsObs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const blocks = e.target.querySelectorAll('.stat-block');
                    if (blocks && blocks.length > 0) {
                        animate(blocks, {
                            opacity: [0, 1],
                            translateY: [40, 0],
                            duration: 800,
                            delay: stagger(120),
                            ease: 'outQuad',
                        });
                    }
                    e.target.querySelectorAll('.stat-number').forEach(el => {
                        const target = parseInt(el.dataset.target);
                        const obj = { val: 0 };
                        animate(obj, {
                            val: target,
                            duration: 2000,
                            ease: 'outQuad',
                            onUpdate: () => {
                                el.textContent = Math.round(obj.val).toLocaleString() + (el.dataset.suffix || '');
                            }
                        });
                    });
                    statsObs.unobserve(e.target);
                }
            });
        }, { threshold: 0.2 });
        statsObs.observe(statsEl);
        observers.push(statsObs);
    }

    reveal('.services-grid', '.service-card', {
        opacity: [0, 1], translateY: [50, 0], duration: 800, delay: stagger(100), ease: 'outQuad',
    });

    reveal('.steps-container', '.step-item', {
        opacity: [0, 1], translateX: [-60, 0], duration: 900, delay: stagger(200), ease: 'outQuad',
    });

    reveal('.benefits-grid', '.benefit-item', {
        opacity: [0, 1], translateY: [40, 0], duration: 800, delay: stagger(100), ease: 'outQuad',
    });

    reveal('.testimonials-section', '.testimonial-card', {
        opacity: [0, 1], translateY: [40, 0], duration: 900, ease: 'outQuad',
    });

    reveal('.final-cta', '.cta-inner', {
        opacity: [0, 1], translateY: [30, 0], duration: 900, ease: 'outQuad',
    });

    testimonialInterval = setInterval(nextTestimonial, 6000);
});

onUnmounted(() => {
    if (testimonialInterval) clearInterval(testimonialInterval);
    observers.forEach(obs => obs.disconnect());
});
</script>

<template>
    <Head title="NutriVida — Nutrición profesional personalizada" />

    <div class="min-h-screen bg-[#f8faf9] text-slate-800 overflow-x-hidden selection:bg-emerald-200 selection:text-emerald-900">

        <!-- ===== DECORATIVE BG ===== -->
        <div class="absolute top-0 left-0 right-0 h-[700px] bg-gradient-to-b from-emerald-50/80 via-white to-transparent pointer-events-none"></div>
        <div class="absolute top-20 right-[-10%] w-[500px] h-[500px] bg-emerald-100/50 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute top-40 left-[-8%] w-[400px] h-[400px] bg-teal-100/40 rounded-full blur-[100px] pointer-events-none"></div>

        <!-- ===== NAV ===== -->
        <nav class="landing-nav opacity-0 fixed top-0 left-0 right-0 z-50 backdrop-blur-xl bg-white/70 border-b border-slate-200/60 shadow-sm shadow-slate-200/30">
            <div class="max-w-7xl mx-auto px-6 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 shadow-md shadow-emerald-200">
                        <div class="h-3 w-3 rounded-sm bg-white"></div>
                    </div>
                    <span class="text-sm font-black tracking-[0.18em] text-slate-700 uppercase">NutriVida</span>
                </div>

                <div class="hidden md:flex items-center gap-7">
                    <a href="#servicios" class="text-sm text-slate-500 hover:text-emerald-600 transition-colors font-semibold">Servicios</a>
                    <a href="#proceso" class="text-sm text-slate-500 hover:text-emerald-600 transition-colors font-semibold">Proceso</a>
                    <a href="#beneficios" class="text-sm text-slate-500 hover:text-emerald-600 transition-colors font-semibold">Beneficios</a>
                    <a href="#testimonios" class="text-sm text-slate-500 hover:text-emerald-600 transition-colors font-semibold">Testimonios</a>
                </div>

                <div v-if="canLogin" class="flex items-center gap-3">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all duration-200 shadow-md shadow-emerald-200 hover:shadow-lg hover:shadow-emerald-200 hover:-translate-y-0.5">
                        Mi Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all duration-200 shadow-md shadow-emerald-200 hover:shadow-lg hover:shadow-emerald-200 hover:-translate-y-0.5">
                            Iniciar sesión
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- ===== HERO ===== -->
        <section class="relative max-w-7xl mx-auto px-6 pt-32 pb-12 sm:pt-40 sm:pb-20">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-10 items-center">
                <!-- TEXT -->
                <div class="max-w-xl">
                    <h1 class="text-4xl sm:text-5xl lg:text-[3.5rem] font-black leading-[1.05] tracking-tight text-slate-900">
                        <span class="hero-title-line block opacity-0">Transforma tu</span>
                        <span class="hero-title-line block opacity-0">salud con</span>
                        <span class="hero-title-line block opacity-0 text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500">nutrición</span>
                        <span class="hero-title-line block opacity-0 text-transparent bg-clip-text bg-gradient-to-r from-teal-500 to-emerald-500">personalizada</span>
                    </h1>

                    <p class="hero-desc opacity-0 mt-6 text-base sm:text-lg text-slate-500 leading-relaxed max-w-lg">
                        Conecta con nutricionistas certificados que diseñan planes alimenticios a tu medida,
                        realizan seguimiento continuo y te guían hacia tus objetivos de salud.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                            class="hero-cta-btn opacity-0 group inline-flex items-center gap-2.5 rounded-2xl bg-emerald-600 px-7 py-3.5 text-base font-bold text-white shadow-lg shadow-emerald-200 transition-all duration-300 hover:bg-emerald-700 hover:shadow-xl hover:shadow-emerald-200 hover:-translate-y-1">
                            <span>Comienza ahora</span>
                            <svg class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <Link v-if="canLogin && $page.props.auth.user" :href="route('dashboard')"
                            class="hero-cta-btn opacity-0 group inline-flex items-center gap-2.5 rounded-2xl bg-emerald-600 px-7 py-3.5 text-base font-bold text-white shadow-lg shadow-emerald-200 transition-all duration-300 hover:bg-emerald-700 hover:shadow-xl hover:shadow-emerald-200 hover:-translate-y-1">
                            <span>Ir al Dashboard</span>
                            <svg class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <a href="#servicios"
                            class="hero-cta-btn opacity-0 inline-flex items-center gap-2 rounded-2xl border-2 border-slate-200 bg-white px-6 py-3.5 text-base font-bold text-slate-600 transition-all duration-300 hover:border-emerald-300 hover:text-emerald-700 hover:-translate-y-0.5 shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                            </svg>
                            Ver servicios
                        </a>
                    </div>
                </div>

                <!-- VISUAL IMAGE -->
                <div class="hero-visual opacity-0 relative flex items-center justify-center h-full">
                    <!-- Background decorative circles -->
                    <div class="absolute -inset-20 bg-gradient-to-br from-emerald-100/50 via-transparent to-teal-100/50 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-white/80 to-transparent pointer-events-none rounded-3xl"></div>

                    <!-- Main image container with floating animation -->
                    <div class="relative w-full max-w-md aspect-square">
                        <!-- Floating frame -->
                        <div class="absolute inset-0 rounded-3xl border-2 border-emerald-200/40 bg-white/40 backdrop-blur-sm shadow-2xl shadow-emerald-200/20 overflow-hidden">
                            <img 
                                src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&q=80" 
                                alt="Nutrición saludable con frutas y verduras"
                                class="w-full h-full object-cover"
                            />
                            <!-- Gradient overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-emerald-950/20 via-transparent to-transparent"></div>
                        </div>

                        <!-- Floating badge info boxes -->
                        <div class="hero-float-badge opacity-0 absolute -top-4 -right-4 sm:right-4 rounded-2xl bg-white border-2 border-emerald-200 px-5 py-4 shadow-xl shadow-emerald-300/30 backdrop-blur-sm">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                                    <svg class="h-5 w-5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Nutricionista certificada</p>
                                    <p class="text-xs text-emerald-600 font-semibold">Disponible ahora</p>
                                </div>
                            </div>
                        </div>

                        <div class="hero-float-badge opacity-0 absolute -bottom-3 -left-4 sm:left-4 rounded-2xl bg-white border-2 border-teal-200 px-5 py-4 shadow-xl shadow-teal-300/30 backdrop-blur-sm">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-teal-100 flex items-center justify-center shrink-0">
                                    <svg class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Plan 100% personalizado</p>
                                    <p class="text-xs text-teal-600 font-semibold">Para ti</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== STATS ===== -->
        <section class="stats-section relative max-w-5xl mx-auto px-6 py-14">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-block opacity-0 rounded-2xl bg-white border border-slate-200/80 p-5 text-center shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-300 group">
                    <p class="stat-number text-3xl sm:text-4xl font-black text-slate-800 group-hover:text-emerald-600 transition-colors" data-target="500" data-suffix="+">0</p>
                    <p class="mt-1 text-xs font-bold text-slate-400 uppercase tracking-wider">Pacientes atendidos</p>
                </div>
                <div class="stat-block opacity-0 rounded-2xl bg-white border border-slate-200/80 p-5 text-center shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-300 group">
                    <p class="stat-number text-3xl sm:text-4xl font-black text-slate-800 group-hover:text-emerald-600 transition-colors" data-target="15" data-suffix="">0</p>
                    <p class="mt-1 text-xs font-bold text-slate-400 uppercase tracking-wider">Nutricionistas</p>
                </div>
                <div class="stat-block opacity-0 rounded-2xl bg-white border border-slate-200/80 p-5 text-center shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-300 group">
                    <p class="stat-number text-3xl sm:text-4xl font-black text-slate-800 group-hover:text-emerald-600 transition-colors" data-target="98" data-suffix="%">0</p>
                    <p class="mt-1 text-xs font-bold text-slate-400 uppercase tracking-wider">Satisfacción</p>
                </div>
                <div class="stat-block opacity-0 rounded-2xl bg-white border border-slate-200/80 p-5 text-center shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-300 group">
                    <p class="stat-number text-3xl sm:text-4xl font-black text-slate-800 group-hover:text-emerald-600 transition-colors" data-target="3200" data-suffix="+">0</p>
                    <p class="mt-1 text-xs font-bold text-slate-400 uppercase tracking-wider">Planes creados</p>
                </div>
            </div>
        </section>

        <!-- ===== SERVICES ===== -->
        <section id="servicios" class="relative max-w-7xl mx-auto px-6 py-20">
            <div class="section-header text-center mb-14">
                <span class="opacity-0 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 mb-5">
                    <span class="text-xs font-bold tracking-wider text-emerald-700 uppercase">Nuestros servicios</span>
                </span>
                <h2 class="opacity-0 text-3xl sm:text-4xl font-black tracking-tight text-slate-900">
                    ¿Qué te ofrece un <span class="text-emerald-600">nutricionista?</span>
                </h2>
                <p class="opacity-0 mt-4 text-base text-slate-500 max-w-2xl mx-auto">
                    Atención integral y personalizada para cada etapa de tu proceso nutricional.
                </p>
            </div>

            <div class="services-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="service-card opacity-0 group rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm hover:shadow-lg hover:border-emerald-200 transition-all duration-300 hover:-translate-y-1">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 14.5M14.25 3.104c.251.023.501.05.75.082M19.8 14.5l-1.47.735c-.22.11-.467.164-.716.164-.249 0-.496-.054-.716-.164L12 12.785l-4.898 2.45c-.22.11-.467.164-.716.164a1.537 1.537 0 01-.716-.164L4.2 14.5" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-800">Evaluación inicial completa</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">Medición de peso, estatura, composición corporal, hábitos alimentarios e historial clínico.</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-600 rounded-md border border-emerald-100">Antropometría</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-600 rounded-md border border-emerald-100">IMC</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-600 rounded-md border border-emerald-100">Diagnóstico</span>
                    </div>
                </div>

                <div class="service-card opacity-0 group rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm hover:shadow-lg hover:border-sky-200 transition-all duration-300 hover:-translate-y-1">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-100 text-sky-600 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-800">Plan alimenticio personalizado</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">Menús adaptados a tus necesidades calóricas, preferencias, alergias y condiciones médicas.</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-sky-50 text-sky-600 rounded-md border border-sky-100">Menú semanal</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-sky-50 text-sky-600 rounded-md border border-sky-100">Kcal</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-sky-50 text-sky-600 rounded-md border border-sky-100">Macros</span>
                    </div>
                </div>

                <div class="service-card opacity-0 group rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm hover:shadow-lg hover:border-violet-200 transition-all duration-300 hover:-translate-y-1">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-600 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-800">Seguimiento continuo</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">Gráficas de evolución de peso, IMC y ajustes dinámicos según tus resultados reales.</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-violet-50 text-violet-600 rounded-md border border-violet-100">Gráficas</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-violet-50 text-violet-600 rounded-md border border-violet-100">Evolución</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-violet-50 text-violet-600 rounded-md border border-violet-100">Ajustes</span>
                    </div>
                </div>

                <div class="service-card opacity-0 group rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm hover:shadow-lg hover:border-amber-200 transition-all duration-300 hover:-translate-y-1">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-800">Registro alimenticio diario</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">Bitácora digital donde registras cada comida. Tu nutricionista revisa y valida cada entrada.</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-600 rounded-md border border-amber-100">Desayuno</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-600 rounded-md border border-amber-100">Almuerzo</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-600 rounded-md border border-amber-100">Cena</span>
                    </div>
                </div>

                <div class="service-card opacity-0 group rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm hover:shadow-lg hover:border-rose-200 transition-all duration-300 hover:-translate-y-1">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-600 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-800">Validación profesional</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">Cada registro es revisado por tu nutricionista con observaciones y recomendaciones.</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-rose-50 text-rose-600 rounded-md border border-rose-100">Revisión</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-rose-50 text-rose-600 rounded-md border border-rose-100">Feedback</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-rose-50 text-rose-600 rounded-md border border-rose-100">Ajustes</span>
                    </div>
                </div>

                <div class="service-card opacity-0 group rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm hover:shadow-lg hover:border-teal-200 transition-all duration-300 hover:-translate-y-1">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-800">Reportes de progreso</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">Informes PDF con tu evolución, gráficas de peso, IMC y recomendaciones profesionales.</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-teal-50 text-teal-600 rounded-md border border-teal-100">PDF</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-teal-50 text-teal-600 rounded-md border border-teal-100">Gráficas</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-teal-50 text-teal-600 rounded-md border border-teal-100">Histórico</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== HOW IT WORKS ===== -->
        <section id="proceso" class="relative max-w-5xl mx-auto px-6 py-20">
            <div class="section-header text-center mb-14">
                <span class="opacity-0 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 mb-5">
                    <span class="text-xs font-bold tracking-wider text-emerald-700 uppercase">¿Cómo funciona?</span>
                </span>
                <h2 class="opacity-0 text-3xl sm:text-4xl font-black tracking-tight text-slate-900">
                    Tu camino hacia una <span class="text-emerald-600">vida saludable</span>
                </h2>
            </div>

            <div class="steps-container relative">
                <div class="absolute left-6 sm:left-8 top-0 bottom-0 w-0.5 bg-gradient-to-b from-emerald-300 via-teal-300 to-slate-200 rounded-full"></div>

                <div class="space-y-8">
                    <div v-for="(step, i) in [
                        { n: 1, color: 'emerald', title: 'Regístrate en la plataforma', desc: 'Crea tu cuenta con datos básicos. El administrador te asignará un nutricionista certificado.' },
                        { n: 2, color: 'teal', title: 'Evaluación inicial presencial', desc: 'Tu nutricionista registra peso, estatura, composición corporal y define tus metas.' },
                        { n: 3, color: 'green', title: 'Recibe tu plan personalizado', desc: 'Accede desde tu dashboard a menús, porciones y recomendaciones diseñadas para ti.' },
                        { n: 4, color: 'sky', title: 'Registra lo que comes cada día', desc: 'Usa la bitácora para registrar tus comidas. El sistema calcula los valores nutricionales.' },
                        { n: 5, color: 'violet', title: 'Observa tu evolución en tiempo real', desc: 'Gráficas de peso, IMC y adherencia. Tu nutricionista ajusta la estrategia según avances.' },
                    ]" :key="i" class="step-item opacity-0 relative pl-16 sm:pl-20">
                        <div class="absolute left-3 sm:left-5 top-1 h-7 w-7 rounded-full border-2 flex items-center justify-center bg-white shadow-sm"
                            :class="`border-${step.color}-400`">
                            <span class="text-xs font-black" :class="`text-${step.color}-600`">{{ step.n }}</span>
                        </div>
                        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-300"
                            :class="`hover:border-${step.color}-200`">
                            <h3 class="text-base font-bold text-slate-800">{{ step.title }}</h3>
                            <p class="mt-2 text-sm text-slate-500 leading-relaxed">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== BENEFITS ===== -->
        <section id="beneficios" class="relative max-w-7xl mx-auto px-6 py-20">
            <div class="section-header text-center mb-14">
                <span class="opacity-0 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 mb-5">
                    <span class="text-xs font-bold tracking-wider text-emerald-700 uppercase">Beneficios</span>
                </span>
                <h2 class="opacity-0 text-3xl sm:text-4xl font-black tracking-tight text-slate-900">
                    ¿Por qué elegir <span class="text-emerald-600">nutrición profesional?</span>
                </h2>
            </div>

            <div class="benefits-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="benefit-item opacity-0 text-center p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-300 group">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Resultados reales</h3>
                    <p class="mt-2 text-xs text-slate-500 leading-relaxed">Planes basados en evidencia con seguimiento profesional continuo.</p>
                </div>

                <div class="benefit-item opacity-0 text-center p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-sky-200 transition-all duration-300 group">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-7 w-7 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Ahorra tiempo</h3>
                    <p class="mt-2 text-xs text-slate-500 leading-relaxed">Sin papeles ni Excel. Registro digital rápido en un solo lugar.</p>
                </div>

                <div class="benefit-item opacity-0 text-center p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-violet-200 transition-all duration-300 group">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-7 w-7 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Comunicación directa</h3>
                    <p class="mt-2 text-xs text-slate-500 leading-relaxed">Notificaciones y observaciones de tu nutricionista en cada registro.</p>
                </div>

                <div class="benefit-item opacity-0 text-center p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-amber-200 transition-all duration-300 group">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Datos seguros</h3>
                    <p class="mt-2 text-xs text-slate-500 leading-relaxed">Información clínica protegida con autenticación y roles verificados.</p>
                </div>
            </div>
        </section>

        <!-- ===== TESTIMONIALS ===== -->
        <section id="testimonios" class="testimonials-section relative max-w-4xl mx-auto px-6 py-20">
            <div class="section-header text-center mb-14">
                <span class="opacity-0 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 mb-5">
                    <span class="text-xs font-bold tracking-wider text-emerald-700 uppercase">Testimonios</span>
                </span>
                <h2 class="opacity-0 text-3xl sm:text-4xl font-black tracking-tight text-slate-900">
                    Lo que dicen nuestros <span class="text-emerald-600">pacientes</span>
                </h2>
            </div>

            <div class="testimonial-card opacity-0 rounded-3xl bg-white border border-slate-200/80 p-8 sm:p-10 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 w-40 h-40 bg-emerald-100/40 rounded-full blur-[80px] -translate-x-10 -translate-y-10 pointer-events-none"></div>

                <div class="relative z-10">
                    <svg class="h-9 w-9 text-emerald-200 mb-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151C7.563 6.068 6 8.789 6 11h4v10H0z" />
                    </svg>

                    <div class="testimonial-content">
                        <p class="text-lg sm:text-xl text-slate-700 leading-relaxed font-medium italic">
                            "{{ testimonials[currentTestimonial].text }}"
                        </p>
                        <div class="mt-6 flex items-center gap-3">
                            <div class="h-11 w-11 rounded-full bg-emerald-100 flex items-center justify-center ring-2 ring-emerald-200">
                                <span class="text-sm font-black text-emerald-700">{{ testimonials[currentTestimonial].avatar }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">{{ testimonials[currentTestimonial].name }}</p>
                                <p class="text-xs text-emerald-600 font-medium">{{ testimonials[currentTestimonial].role }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-7 flex gap-2">
                        <button v-for="(t, i) in testimonials" :key="i"
                            @click="setTestimonial(i)"
                            class="h-2 rounded-full transition-all duration-300 focus:outline-none"
                            :class="i === currentTestimonial ? 'w-8 bg-emerald-500' : 'w-2 bg-slate-200 hover:bg-slate-300'"
                        ></button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CTA ===== -->
        <section class="final-cta relative max-w-4xl mx-auto px-6 py-20 text-center">
            <div class="cta-inner opacity-0 rounded-3xl bg-gradient-to-br from-emerald-600 via-emerald-600 to-teal-600 p-12 sm:p-16 shadow-xl shadow-emerald-200/60 relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.12),transparent_50%)] pointer-events-none"></div>
                <div class="absolute top-0 right-0 w-60 h-60 bg-white/[0.06] rounded-full blur-[60px] translate-x-10 -translate-y-10 pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 mb-6">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-60"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        <span class="text-xs font-bold tracking-wider text-white/90 uppercase">Cupos disponibles</span>
                    </div>

                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-white">
                        ¿Listo para transformar tu salud?
                    </h2>
                    <p class="mt-4 text-base text-emerald-100 max-w-lg mx-auto leading-relaxed">
                        Inicia tu proceso nutricional con profesionales certificados. Tu cuerpo te lo agradecerá.
                    </p>

                    <div class="mt-8">
                        <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                            class="group inline-flex items-center gap-2.5 rounded-2xl bg-white px-9 py-4 text-base font-bold text-emerald-700 shadow-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <span>Comienza ahora</span>
                            <svg class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <Link v-else-if="canLogin && $page.props.auth.user" :href="route('dashboard')"
                            class="group inline-flex items-center gap-2.5 rounded-2xl bg-white px-9 py-4 text-base font-bold text-emerald-700 shadow-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <span>Ir al Dashboard</span>
                            <svg class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer class="border-t border-slate-200/60 py-8 bg-white/50">
            <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-5">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600">
                        <div class="h-2 w-2 rounded-sm bg-white"></div>
                    </div>
                    <span class="text-xs font-black tracking-[0.15em] text-slate-400 uppercase">NutriVida</span>
                </div>
                <div class="flex items-center gap-6 text-xs text-slate-400 font-medium">
                    <a href="#servicios" class="hover:text-emerald-600 transition-colors">Servicios</a>
                    <a href="#proceso" class="hover:text-emerald-600 transition-colors">Proceso</a>
                    <a href="#beneficios" class="hover:text-emerald-600 transition-colors">Beneficios</a>
                    <a href="#testimonios" class="hover:text-emerald-600 transition-colors">Testimonios</a>
                </div>
                <p class="text-xs text-slate-400 font-medium">© 2026 NutriVida</p>
            </div>
        </footer>
    </div>
</template>

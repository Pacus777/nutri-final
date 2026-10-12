<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { marked } from 'marked';

const props = defineProps({
    documentos: {
        type: Array,
        default: () => [],
    },
    totalDocumentos: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const usuario = computed(() => page.props.auth?.user ?? null);

// Buscador y Filtros
const searchQuery = ref('');
const categoriaSeleccionada = ref('todas');
const articuloActivoId = ref(props.documentos.length > 0 ? props.documentos[0].id : null);
const copiadoFeedback = ref(false);

// Agrupación por categorías
const categoriasDisponibles = computed(() => {
    const cats = new Set(props.documentos.map(d => d.categoria).filter(Boolean));
    return Array.from(cats);
});

// Documentos filtrados
const documentosFiltrados = computed(() => {
    let docs = props.documentos;

    if (categoriaSeleccionada.value !== 'todas') {
        docs = docs.filter(d => d.categoria === categoriaSeleccionada.value);
    }

    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase().trim();
        docs = docs.filter(d => 
            d.titulo.toLowerCase().includes(q) ||
            d.categoria.toLowerCase().includes(q) ||
            d.contenido.toLowerCase().includes(q)
        );
    }

    return docs;
});

// Artículo actualmente en pantalla
const articuloActivo = computed(() => {
    if (!articuloActivoId.value && props.documentos.length > 0) {
        return props.documentos[0];
    }
    return props.documentos.find(d => d.id === articuloActivoId.value) || props.documentos[0] || null;
});

// Seleccionar artículo
const seleccionarArticulo = (id) => {
    articuloActivoId.value = id;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Navegación Anterior / Siguiente
const indiceActual = computed(() => {
    return props.documentos.findIndex(d => d.id === articuloActivoId.value);
});

const articuloAnterior = computed(() => {
    if (indiceActual.value > 0) {
        return props.documentos[indiceActual.value - 1];
    }
    return null;
});

const articuloSiguiente = computed(() => {
    if (indiceActual.value !== -1 && indiceActual.value < props.documentos.length - 1) {
        return props.documentos[indiceActual.value + 1];
    }
    return null;
});

// Estadísticas del artículo actual
const estadisticasArticulo = computed(() => {
    if (!articuloActivo.value) return { palabras: 0, tiempoLectura: 1 };
    const textoLimpio = articuloActivo.value.contenido.replace(/[#*`_\[\]()|>-]/g, ' ');
    const palabras = textoLimpio.trim().split(/\s+/).filter(Boolean).length;
    const tiempoLectura = Math.max(1, Math.ceil(palabras / 180));
    return { palabras, tiempoLectura };
});

// Extracción de Índice (Table of Contents) del documento actual
const indiceDocumento = computed(() => {
    if (!articuloActivo.value) return [];
    const lineas = articuloActivo.value.contenido.split('\n');
    const items = [];
    
    for (const linea of lineas) {
        const matchH2 = linea.match(/^##\s+(.+)$/);
        const matchH3 = linea.match(/^###\s+(.+)$/);
        
        if (matchH2) {
            const raw = matchH2[1].trim();
            const id = raw.toLowerCase().replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '-');
            items.push({ id, titulo: raw, nivel: 2 });
        } else if (matchH3) {
            const raw = matchH3[1].trim();
            const id = raw.toLowerCase().replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '-');
            items.push({ id, titulo: raw, nivel: 3 });
        }
    }
    return items;
});

// Scroll suave al encabezado
const saltarAEncabezado = (id) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Configuración de Marked personalizada
const customRenderer = new marked.Renderer();

// Tablas estilizadas con contenedor responsivo
customRenderer.table = function(token) {
    const header = token.header ? token.header.map(cell => `<th>${marked.parseInline(cell.text)}</th>`).join('') : '';
    const rows = token.rows ? token.rows.map(row => {
        const cells = row.map(cell => `<td>${marked.parseInline(cell.text)}</td>`).join('');
        return `<tr>${cells}</tr>`;
    }).join('') : '';
    return `<div class="doc-table-wrapper"><table class="doc-table"><thead><tr>${header}</tr></thead><tbody>${rows}</tbody></table></div>`;
};

// Encabezados con IDs y anclas
customRenderer.heading = function({ tokens, depth, text }) {
    const slug = text.toLowerCase().replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '-');
    const content = marked.parseInline(text);
    return `<h${depth} id="${slug}" class="doc-heading doc-h${depth}">
        <span>${content}</span>
        <a href="#${slug}" class="doc-heading-anchor" title="Enlace a esta sección">#</a>
    </h${depth}>`;
};

// Bloques de código y diagramas Mermaid
customRenderer.code = function({ text, lang }) {
    const cleanLang = (lang || 'codigo').toLowerCase();
    
    if (cleanLang === 'mermaid') {
        const lines = text.split('\n');
        const nodes = [];
        for (const line of lines) {
            const matches = line.matchAll(/([A-Za-z0-9_]+)\[([^\]]+)\]/g);
            for (const m of matches) {
                if (!nodes.some(n => n.label === m[2].trim())) {
                    nodes.push({ id: m[1], label: m[2].trim(), isCondition: false });
                }
            }
            const condMatches = line.matchAll(/([A-Za-z0-9_]+)\{([^}]+)\}/g);
            for (const m of condMatches) {
                if (!nodes.some(n => n.label === m[2].trim())) {
                    nodes.push({ id: m[1], label: m[2].trim(), isCondition: true });
                }
            }
        }

        let visualHtml = '';
        if (nodes.length > 0) {
            visualHtml = `
            <div class="doc-mermaid-visual">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 p-4 bg-slate-50 border-b border-slate-200">
                    ${nodes.map((n, i) => `
                        <div class="flex items-center gap-2.5 p-3 rounded-xl border ${n.isCondition ? 'bg-amber-50/90 border-amber-300 text-amber-950 ring-1 ring-amber-400/30' : 'bg-white border-slate-200 text-slate-800 shadow-xs'}">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg ${n.isCondition ? 'bg-amber-200 text-amber-800 font-extrabold' : 'bg-emerald-100 text-emerald-800 font-extrabold'} text-[10px]">
                                ${i + 1}
                            </span>
                            <span class="text-xs font-semibold leading-tight">${n.label}</span>
                        </div>
                    `).join('')}
                </div>
            </div>`;
        }

        return `<div class="doc-diagram-block">
            <div class="doc-diagram-header">
                <div class="flex items-center gap-2">
                    <span class="text-base">📊</span>
                    <span class="font-bold text-xs uppercase tracking-wider text-slate-800">Flujo Operativo / Diagrama de Proceso</span>
                </div>
                <span class="text-[10px] font-mono font-bold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full border border-emerald-300">Flujo Visual</span>
            </div>
            ${visualHtml}
            <details class="group">
                <summary class="flex items-center justify-between px-4 py-2 text-[11px] font-bold text-slate-500 hover:text-slate-800 bg-slate-100/70 cursor-pointer select-none border-t border-slate-200">
                    <span>Ver definición técnica en Mermaid</span>
                    <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <pre class="doc-diagram-code"><code>${text}</code></pre>
            </details>
        </div>`;
    }

    return `<div class="doc-code-block">
        <div class="doc-code-header">
            <div class="doc-code-dots">
                <span class="dot-red"></span>
                <span class="dot-yellow"></span>
                <span class="dot-green"></span>
            </div>
            <span class="doc-code-lang">${cleanLang}</span>
        </div>
        <pre class="doc-code-pre"><code class="language-${cleanLang}">${text}</code></pre>
    </div>`;
};

// Callouts y citas de estilo GitHub
customRenderer.blockquote = function({ text }) {
    const raw = text.trim();
    if (raw.includes('[!NOTE]') || raw.includes('[!INFO]')) {
        const clean = raw.replace(/\[!(NOTE|INFO)\]\s*/gi, '');
        return `<div class="doc-callout doc-callout-info">
            <div class="doc-callout-title">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Nota Informativa</span>
            </div>
            <div class="doc-callout-content">${clean}</div>
        </div>`;
    }
    if (raw.includes('[!TIP]')) {
        const clean = raw.replace(/\[!TIP\]\s*/gi, '');
        return `<div class="doc-callout doc-callout-tip">
            <div class="doc-callout-title">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span>Recomendación Práctica</span>
            </div>
            <div class="doc-callout-content">${clean}</div>
        </div>`;
    }
    if (raw.includes('[!WARNING]') || raw.includes('[!ALERT]')) {
        const clean = raw.replace(/\[!(WARNING|ALERT)\]\s*/gi, '');
        return `<div class="doc-callout doc-callout-warning">
            <div class="doc-callout-title">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Advertencia</span>
            </div>
            <div class="doc-callout-content">${clean}</div>
        </div>`;
    }
    if (raw.includes('[!IMPORTANT]') || raw.includes('[!CAUTION]')) {
        const clean = raw.replace(/\[!(IMPORTANT|CAUTION)\]\s*/gi, '');
        return `<div class="doc-callout doc-callout-important">
            <div class="doc-callout-title">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Punto Crítico</span>
            </div>
            <div class="doc-callout-content">${clean}</div>
        </div>`;
    }

    return `<blockquote class="doc-blockquote">${text}</blockquote>`;
};

// Separador horizontal
customRenderer.hr = function() {
    return `<hr class="doc-divider"/>`;
};

marked.use({ renderer: customRenderer, gfm: true, breaks: true });

// Renderizador reactivo con soporte para LaTeX y limpieza del encabezado duplicado
const contenidoHtml = computed(() => {
    if (!articuloActivo.value || !articuloActivo.value.contenido) return '';
    let raw = articuloActivo.value.contenido.trim();

    // 1. Evitar duplicar el título principal que ya se muestra en el encabezado
    raw = raw.replace(/^#\s+[^\n]+\n+/, '');

    // 2. Fórmulas de bloque $$...$$
    raw = raw.replace(/\$\$([\s\S]*?)\$\$/g, (match, formula) => {
        let f = formula.trim();
        f = f.replace(/\\text\{([^}]+)\}/g, '$1');
        f = f.replace(/\\frac\{([^}]+)\}\{([^}]+)\}/g, '<div class="doc-fraction"><span class="doc-num">$1</span><span class="doc-den">$2</span></div>');
        f = f.replace(/\\times/g, '×');
        return `\n\n<div class="doc-formula-box"><span class="doc-formula-title">Fórmula de Cálculo</span><div class="doc-formula-math">${f}</div></div>\n\n`;
    });

    // 3. Fórmulas inline $...$
    raw = raw.replace(/\$([^\$\n]+)\$/g, (match, formula) => {
        let f = formula.trim();
        if (f === '\\rightarrow' || f === '\\to') {
            return '<span class="text-emerald-600 font-bold mx-1">→</span>';
        }
        f = f.replace(/\\(rightarrow|to)/g, '→');
        f = f.replace(/\\text\{([^}]+)\}/g, '$1');
        f = f.replace(/\\frac\{([^}]+)\}\{([^}]+)\}/g, '($1 / $2)');
        f = f.replace(/\\times/g, '×');
        return `<span class="doc-math-inline">${f.trim()}</span>`;
    });

    return marked.parse(raw);
});

// Copiar código o contenido al portapapeles
const copiarContenido = () => {
    if (!articuloActivo.value) return;
    navigator.clipboard.writeText(articuloActivo.value.contenido);
    copiadoFeedback.value = true;
    setTimeout(() => {
        copiadoFeedback.value = false;
    }, 2500);
};

// Color del badge de categoría
const colorCategoria = (categoria) => {
    const cat = (categoria || '').toLowerCase();
    if (cat.includes('arquitectura')) return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    if (cat.includes('alimento') || cat.includes('motor')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    if (cat.includes('datos') || cat.includes('modelo')) return 'bg-sky-50 text-sky-700 border-sky-200';
    if (cat.includes('superadmin') || cat.includes('mantenimiento')) return 'bg-amber-50 text-amber-800 border-amber-200';
    return 'bg-slate-100 text-slate-700 border-slate-200';
};
</script>

<template>
    <Head title="Documentación Técnica del Sistema" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white shadow-lg shadow-emerald-500/20">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-black text-slate-900 tracking-tight">
                                Portal de Documentación del Sistema
                            </h2>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 border border-emerald-200">
                                Oficial
                            </span>
                        </div>
                        <p class="text-xs font-semibold text-slate-500 mt-0.5">
                            Arquitectura, Clasificador Físico vs Líquido, Porciones, Base de Datos y Mantenimiento
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route().has('panel.superadmin') ? route('panel.superadmin') : '/panel-superadmin'"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-xs transition cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Volver al Panel SuperAdmin</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- HERO BAR & SEARCH -->
                <div class="rounded-3xl border border-slate-200 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-1">
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Guías Técnicas Sincronizadas</span>
                        <h3 class="text-lg sm:text-xl font-black tracking-tight">Explorador de Arquitectura y Negocio</h3>
                        <p class="text-xs text-slate-300 max-w-xl">
                            Visualización enriquecida de todas las especificaciones del sistema, clasificadores de porciones y esquemas relacionales.
                        </p>
                    </div>

                    <!-- Search Box -->
                    <div class="relative w-full md:w-80 shrink-0">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar en la documentación..."
                            class="w-full rounded-2xl border border-slate-700 bg-slate-800/90 py-2.5 pl-10 pr-4 text-xs text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none transition shadow-inner"
                        />
                    </div>
                </div>

                <!-- CATEGORY PILLS -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                    <button
                        type="button"
                        @click="categoriaSeleccionada = 'todas'"
                        class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition shrink-0 cursor-pointer"
                        :class="categoriaSeleccionada === 'todas' 
                            ? 'bg-slate-900 text-white shadow-sm' 
                            : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
                    >
                        Todas las Secciones ({{ totalDocumentos }})
                    </button>

                    <button
                        v-for="cat in categoriasDisponibles"
                        :key="cat"
                        type="button"
                        @click="categoriaSeleccionada = cat"
                        class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition shrink-0 cursor-pointer"
                        :class="categoriaSeleccionada === cat 
                            ? 'bg-emerald-600 text-white shadow-sm' 
                            : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
                    >
                        {{ cat }}
                    </button>
                </div>

                <!-- MAIN WORKSPACE -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- LEFT COLUMN: ARTICLE LIST -->
                    <div class="lg:col-span-4 space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400">
                                Artículos disponibles ({{ documentosFiltrados.length }})
                            </span>
                        </div>

                        <div class="space-y-2">
                            <button
                                v-for="doc in documentosFiltrados"
                                :key="doc.id"
                                type="button"
                                @click="seleccionarArticulo(doc.id)"
                                class="w-full text-left rounded-2xl border p-4 transition-all duration-150 flex items-center justify-between gap-3 group cursor-pointer"
                                :class="articuloActivo?.id === doc.id 
                                    ? 'bg-emerald-50/70 border-emerald-500/80 shadow-sm ring-1 ring-emerald-500/20' 
                                    : 'bg-white border-slate-200/80 hover:border-slate-300 hover:bg-slate-50/60 shadow-xs'"
                            >
                                <div class="space-y-1.5 min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span 
                                            class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md border"
                                            :class="colorCategoria(doc.categoria)"
                                        >
                                            {{ doc.categoria }}
                                        </span>
                                    </div>
                                    <p 
                                        class="text-xs font-bold leading-snug line-clamp-2"
                                        :class="articuloActivo?.id === doc.id ? 'text-emerald-950 font-black' : 'text-slate-700 group-hover:text-slate-900'"
                                    >
                                        {{ doc.titulo }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ doc.ruta }}</p>
                                </div>

                                <svg 
                                    class="h-4 w-4 shrink-0 transition" 
                                    :class="articuloActivo?.id === doc.id ? 'text-emerald-600 translate-x-0.5' : 'text-slate-300 group-hover:text-slate-400'"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: DOCUMENT VIEWER -->
                    <div class="lg:col-span-8">
                        <div v-if="articuloActivo" class="rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-10 shadow-sm space-y-8">
                            
                            <!-- Article Header -->
                            <div class="border-b border-slate-100 pb-6 space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span 
                                            class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-md border"
                                            :class="colorCategoria(articuloActivo.categoria)"
                                        >
                                            {{ articuloActivo.categoria }}
                                        </span>
                                        <span class="text-xs font-semibold text-slate-400 flex items-center gap-1">
                                            <span>⏱️</span>
                                            <span>{{ estadisticasArticulo.tiempoLectura }} min de lectura</span>
                                        </span>
                                        <span class="text-xs text-slate-300">•</span>
                                        <span class="text-xs text-slate-400">
                                            {{ estadisticasArticulo.palabras }} palabras
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="copiarContenido"
                                            class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition shadow-xs cursor-pointer"
                                        >
                                            <svg v-if="!copiadoFeedback" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg v-else class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>{{ copiadoFeedback ? '¡Copiado!' : 'Copiar Texto' }}</span>
                                        </button>
                                    </div>
                                </div>

                                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                                    {{ articuloActivo.titulo }}
                                </h1>
                                
                                <div class="flex items-center gap-2 text-xs text-slate-400">
                                    <span class="font-mono bg-slate-50 px-2 py-0.5 rounded border border-slate-100">
                                        documentacion_sistema/{{ articuloActivo.ruta }}
                                    </span>
                                    <span>•</span>
                                    <span>Actualizado: {{ articuloActivo.modificado }}</span>
                                </div>
                            </div>

                            <!-- MINI TABLE OF CONTENTS (Índice de la Guía) -->
                            <div v-if="indiceDocumento.length > 1" class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                        </svg>
                                        En esta guía
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-bold">{{ indiceDocumento.length }} secciones</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pt-1">
                                    <button
                                        v-for="(item, idx) in indiceDocumento"
                                        :key="idx"
                                        type="button"
                                        @click="saltarAEncabezado(item.id)"
                                        class="text-left text-xs text-slate-600 hover:text-emerald-700 hover:bg-white px-2 py-1 rounded-lg transition truncate flex items-center gap-1.5 cursor-pointer"
                                        :class="item.nivel === 3 ? 'pl-4 text-[11px] text-slate-500' : 'font-semibold'"
                                    >
                                        <span class="text-emerald-500 text-[10px] font-bold">›</span>
                                        <span class="truncate">{{ item.titulo }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- ARTICLE FORMATTED CONTENT (RICH MARKDOWN) -->
                            <div 
                                class="doc-content"
                                v-html="contenidoHtml"
                            ></div>

                            <!-- Bottom Navigation Prev / Next -->
                            <div class="border-t border-slate-100 pt-6 flex items-center justify-between gap-4">
                                <div>
                                    <button
                                        v-if="articuloAnterior"
                                        @click="seleccionarArticulo(articuloAnterior.id)"
                                        class="flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-emerald-700 transition cursor-pointer"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                        <div class="text-left">
                                            <span class="text-[10px] text-slate-400 block font-normal">Anterior</span>
                                            <span class="line-clamp-1">{{ articuloAnterior.titulo }}</span>
                                        </div>
                                    </button>
                                </div>

                                <div>
                                    <button
                                        v-if="articuloSiguiente"
                                        @click="seleccionarArticulo(articuloSiguiente.id)"
                                        class="flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-emerald-700 transition cursor-pointer"
                                    >
                                        <div class="text-right">
                                            <span class="text-[10px] text-slate-400 block font-normal">Siguiente</span>
                                            <span class="line-clamp-1">{{ articuloSiguiente.titulo }}</span>
                                        </div>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-3xl border border-slate-200 bg-white p-12 text-center text-slate-400 shadow-sm">
                            <span class="text-4xl">📄</span>
                            <h3 class="text-base font-bold text-slate-700 mt-3">Selecciona un documento</h3>
                            <p class="text-xs text-slate-400 mt-1">Elige un artículo de la lista lateral para visualizarlo.</p>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Document Viewer Typography & Component Styling */
.doc-content {
    font-family: inherit;
    color: #334155;
    font-size: 0.9375rem;
    line-height: 1.8;
}

.doc-content p {
    margin-bottom: 1.25rem;
    color: #334155;
    line-height: 1.8;
}

.doc-content strong {
    font-weight: 700;
    color: #0f172a;
}

.doc-content em {
    font-style: italic;
    color: #475569;
}

/* Headings */
.doc-heading {
    position: relative;
    scroll-margin-top: 5rem;
}

.doc-h1 {
    font-size: 1.75rem;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.025em;
    margin-top: 2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.doc-h2 {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin-top: 2.25rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.doc-h3 {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.01em;
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.doc-heading-anchor {
    font-size: 0.875rem;
    font-weight: 600;
    color: #cbd5e1;
    text-decoration: none;
    padding: 0 0.5rem;
    opacity: 0;
    transition: opacity 0.2s ease, color 0.2s ease;
}

.doc-heading:hover .doc-heading-anchor {
    opacity: 1;
    color: #10b981;
}

/* Tables */
.doc-table-wrapper {
    overflow-x: auto;
    margin: 1.75rem 0;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.doc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    text-align: left;
}

.doc-table th {
    background: #f8fafc;
    color: #1e293b;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.875rem 1.25rem;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.doc-table td {
    padding: 0.875rem 1.25rem;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
    line-height: 1.6;
}

.doc-table tr:last-child td {
    border-bottom: none;
}

.doc-table tbody tr:nth-child(even) {
    background: #fafbfc;
}

.doc-table tbody tr:hover {
    background: #f1f5f9;
    transition: background 0.15s ease;
}

/* Lists */
.doc-content ul {
    list-style: none;
    padding-left: 0;
    margin: 1.25rem 0;
}

.doc-content ul > li {
    position: relative;
    padding-left: 1.75rem;
    margin-bottom: 0.65rem;
    color: #334155;
    line-height: 1.7;
}

.doc-content ul > li::before {
    content: "";
    position: absolute;
    left: 0.5rem;
    top: 0.65rem;
    height: 0.45rem;
    width: 0.45rem;
    border-radius: 9999px;
    background: #10b981;
}

.doc-content ol {
    margin: 1.25rem 0;
    counter-reset: doc-counter;
    list-style: none;
    padding-left: 0;
}

.doc-content ol > li {
    position: relative;
    padding-left: 2.25rem;
    margin-bottom: 0.85rem;
    color: #334155;
    line-height: 1.7;
    counter-increment: doc-counter;
}

.doc-content ol > li::before {
    content: counter(doc-counter);
    position: absolute;
    left: 0;
    top: 0.15rem;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: 0.375rem;
    background: #ecfdf5;
    color: #047857;
    font-weight: 800;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #a7f3d0;
}

.doc-content li > ul {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
    padding-left: 0.5rem;
}

.doc-content li > ul > li::before {
    background: #94a3b8;
    height: 0.35rem;
    width: 0.35rem;
}

/* Inline Code */
.doc-content code:not(pre code) {
    background: #ecfdf5;
    color: #047857;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.2rem 0.45rem;
    border-radius: 0.375rem;
    border: 1px solid #a7f3d0;
    word-break: break-word;
}

/* IDE Code Blocks */
.doc-code-block {
    border-radius: 1rem;
    overflow: hidden;
    margin: 1.5rem 0;
    border: 1px solid #1e293b;
    background: #0f172a;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
}

.doc-code-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.65rem 1rem;
    background: #090d16;
    border-bottom: 1px solid #1e293b;
}

.doc-code-dots {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.dot-red {
    height: 0.65rem;
    width: 0.65rem;
    border-radius: 9999px;
    background: #ef4444;
}

.dot-yellow {
    height: 0.65rem;
    width: 0.65rem;
    border-radius: 9999px;
    background: #f59e0b;
}

.dot-green {
    height: 0.65rem;
    width: 0.65rem;
    border-radius: 9999px;
    background: #10b981;
}

.doc-code-lang {
    font-family: ui-monospace, monospace;
    font-size: 0.6875rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.075em;
}

.doc-code-pre {
    padding: 1.25rem;
    margin: 0;
    overflow-x: auto;
    background: transparent;
}

.doc-code-pre code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.8125rem;
    color: #e2e8f0;
    line-height: 1.7;
}

/* Architecture Diagrams */
.doc-diagram-block {
    border-radius: 1.25rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    margin: 1.75rem 0;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.doc-diagram-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.doc-diagram-code {
    padding: 1.25rem;
    margin: 0;
    background: #0f172a;
    color: #38bdf8;
    font-family: ui-monospace, monospace;
    font-size: 0.8125rem;
    line-height: 1.6;
    overflow-x: auto;
}

/* Callouts & Alerts */
.doc-callout {
    border-radius: 1rem;
    padding: 1rem 1.25rem;
    margin: 1.5rem 0;
    border-left-width: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.doc-callout-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 800;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.4rem;
}

.doc-callout-content {
    font-size: 0.875rem;
    line-height: 1.65;
}

.doc-callout-info {
    background: #f0f9ff;
    border-left-color: #0284c7;
    color: #0c4a6e;
}

.doc-callout-tip {
    background: #ecfdf5;
    border-left-color: #10b981;
    color: #064e3b;
}

.doc-callout-warning {
    background: #fffbeb;
    border-left-color: #f59e0b;
    color: #78350f;
}

.doc-callout-important {
    background: #f5f3ff;
    border-left-color: #8b5cf6;
    color: #4c1d95;
}

.doc-blockquote {
    border-left: 4px solid #10b981;
    background: #ecfdf5;
    padding: 1rem 1.25rem;
    border-radius: 0 0.75rem 0.75rem 0;
    margin: 1.5rem 0;
    color: #065f46;
    font-style: italic;
    line-height: 1.7;
}

/* Formulas & Math */
.doc-formula-box {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 1rem;
    padding: 1.25rem;
    margin: 1.5rem 0;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

.doc-formula-title {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 800;
    color: #047857;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    margin-bottom: 0.75rem;
}

.doc-formula-math {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.doc-fraction {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    margin: 0 0.5rem;
    vertical-align: middle;
}

.doc-num {
    border-bottom: 2px solid #334155;
    padding: 0 0.5rem 0.2rem 0.5rem;
    font-size: 0.95rem;
}

.doc-den {
    padding: 0.2rem 0.5rem 0 0.5rem;
    font-size: 0.95rem;
}

.doc-math-inline {
    font-family: inherit;
    font-weight: 700;
    color: #047857;
    background: #ecfdf5;
    padding: 0.1rem 0.4rem;
    border-radius: 0.35rem;
    border: 1px solid #a7f3d0;
}

/* Dividers */
.doc-divider {
    border: none;
    height: 1px;
    background: linear-gradient(to right, transparent, #cbd5e1, transparent);
    margin: 2.25rem 0;
}
</style>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ObjetivosClinicosWidget from '@/Components/Paciente/ObjetivosClinicosWidget.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    paciente: {
        type: Object,
        required: true,
    },
    fechaSeleccionada: {
        type: String,
        required: true,
    },
    esFechaEditable: {
        type: Boolean,
        default: true,
    },
    motivoBloqueoEdicion: {
        type: String,
        default: null,
    },
    validacionActual: {
        type: Object,
        default: null,
    },
    ultimaRetroalimentacionClinica: {
        type: Object,
        default: null,
    },
    unidadesDisponibles: {
        type: Array,
        default: () => [],
    },
    tiposComida: {
        type: Array,
        default: () => [],
    },
    registroExistente: {
        type: Object,
        default: null,
    },
    metasNutricionales: {
        type: Object,
        default: () => ({
            calorias: 2000,
            proteina: 75,
            carbohidratos: 275,
            grasa: 67,
        }),
    },
    alimentosFrecuentes: {
        type: Array,
        default: () => [],
    },
    objetivosActivos: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const hoy = new Date().toISOString().slice(0, 10);

const claseControlClaro =
    'mt-1 block w-full rounded-xl border-slate-200 bg-white text-slate-900 shadow-2xs focus:border-emerald-500 focus:ring-emerald-500 text-sm py-2 px-3';

const claseSelectClaro =
    'mt-1 block w-full rounded-xl border-slate-200 bg-white text-slate-900 shadow-2xs focus:border-emerald-500 focus:ring-emerald-500 text-sm py-2 px-3';

// Date Navigation
const fechaAyer = computed(() => {
    const d = new Date(props.fechaSeleccionada + 'T00:00:00');
    d.setDate(d.getDate() - 1);
    return d.toISOString().slice(0, 10);
});

const fechaManana = computed(() => {
    const d = new Date(props.fechaSeleccionada + 'T00:00:00');
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
});

const esHoy = computed(() => props.fechaSeleccionada === hoy);

const cambiarFecha = (nuevaFecha) => {
    if (!nuevaFecha || nuevaFecha === props.fechaSeleccionada) return;
    router.get(route('paciente.registros-alimenticios.create', props.paciente.cod_pacientes), {
        fecha: nuevaFecha,
    }, {
        preserveState: false,
        preserveScroll: true,
    });
};

const detalleInicial = props.registroExistente?.detalle?.length
    ? props.registroExistente.detalle.map((item) => ({
        cod_alimentos_dra: item.cod_alimentos_dra,
        nombre_alimento: item.alimento?.nom_ali ?? item.nombre_alimento ?? '',
        nombre_original: item.alimento?.nom_ext_ali ?? item.nombre_original ?? null,
        origen: item.alimento?.src_ali ?? item.origen ?? null,
        pai_ali: item.alimento?.pai_ali ?? item.pai_ali ?? null,
        es_boliviano: item.es_boliviano ?? (item.alimento?.pai_ali === 'BO' || item.alimento?.src_ali === 'bolivia'),
        cant_ref: item.cant_ref ?? item.alimento?.composicionVigente?.cant_ref_tcn ?? 100,
        peso_porcion: item.peso_porcion ?? 100,
        es_liquido: item.es_liquido ?? false,
        unidades_permitidas: item.unidades_permitidas ?? (item.es_liquido ? ['mililitros', 'porcion', 'taza', 'cucharada'] : ['gramos', 'porcion', 'unidad', 'taza', 'cucharada']),
        tip_dra: item.tip_dra,
        can_dra: item.can_dra,
        uni_dra: item.uni_dra ?? (item.es_liquido ? 'mililitros' : 'gramos'),
        hor_dra: item.hor_dra,
        nutricion: item.nutricion ?? (item.alimento?.composicionVigente ? {
            calorias: item.alimento.composicionVigente.ene_tcn,
            proteina: item.alimento.composicionVigente.pro_tcn,
            carbohidratos: item.alimento.composicionVigente.cho_tcn,
            grasa: item.alimento.composicionVigente.lip_tcn,
        } : null),
    }))
    : [];

const form = useForm({
    cod_pacientes_ral: props.paciente.cod_pacientes,
    fec_ral: props.registroExistente?.fec_ral ?? props.fechaSeleccionada,
    est_ral: props.esFechaEditable ? (props.registroExistente?.est_ral ?? 'activo') : 'cerrado',
    detalle: detalleInicial,
});

// Search State
const buscador = ref({
    q: '',
    cargando: false,
    resultados: [],
    error: null,
});

const categoriaSeleccionada = ref(null);
const momentoComidaPreseleccionado = ref(null);
const totalLocales = ref(0);
const totalUsda = ref(0);

const categoriasRapidas = [
    { id: 'boliviano', label: 'Origen Bolivia', icon: 'flag' },
    { id: 'desayuno', label: 'Desayunos y Panes', icon: 'sun' },
    { id: 'plato', label: 'Platos Fuertes', icon: 'utensils' },
    { id: 'proteina', label: 'Carnes y Proteínas', icon: 'drumstick' },
    { id: 'cereal_tuberculo', label: 'Cereales y Tubérculos', icon: 'wheat' },
    { id: 'fruta_verdura', label: 'Frutas y Verduras', icon: 'apple' },
    { id: 'bebida', label: 'Bebidas e Infusiones', icon: 'cup' },
];

const showSearchDropdown = ref(false);
const alimentoDuplicado = ref(null);
const detalleResaltado = ref(null);

// Modal State
const showModalConfig = ref(false);
const comidaSeleccionadaManualmente = ref(false);
const modalData = ref({
    cod_alimentos_dra: null,
    nombre_alimento: '',
    nombre_original: null,
    origen: null,
    pai_ali: null,
    es_boliviano: false,
    por_ref: null,
    cant_ref: 100,
    peso_porcion: 100,
    es_liquido: false,
    unidades_permitidas: ['gramos', 'porcion', 'unidad', 'taza', 'cucharada'],
    tip_dra: 'desayuno',
    can_dra: '100',
    uni_dra: 'gramos',
    hor_dra: '08:00',
    nutricion: null,
    isEditing: false,
    editIndex: null,
});

// View mode: 'comidas' (cards by meal) or 'cronologico' (timeline)
const vistaDiario = ref('comidas');

// Auto-save debouncing
const autoGuardando = ref(false);
const ultimoAutoGuardado = ref(null);
let temporizadorAutoGuardado = null;

// Mathematical helpers
const numeroSeguro = (valor) => {
    const numero = Number.parseFloat(String(valor ?? '0').replace(',', '.'));
    return Number.isFinite(numero) ? numero : 0;
};

const factorCantidad = (item) => {
    const cantidad = numeroSeguro(item.can_dra);
    if (cantidad <= 0) return 0;

    const uni = String(item.uni_dra || 'gramos').toLowerCase();
    const cantRef = numeroSeguro(item.cant_ref || 100);

    if (uni === 'gramos' || uni === 'mililitros') {
        return cantidad / (cantRef > 0 ? cantRef : 100);
    }

    if (uni === 'porcion' || uni === 'unidad') {
        const pesoPorcion = numeroSeguro(item.peso_porcion || item.cant_ref || 100);
        return (cantidad * pesoPorcion) / (cantRef > 0 ? cantRef : 100);
    }

    if (uni === 'taza') {
        const factorTaza = item.es_liquido ? 240 : 150;
        return (cantidad * factorTaza) / (cantRef > 0 ? cantRef : 100);
    }

    if (uni === 'cucharada') {
        const factorCucharada = item.es_liquido ? 15 : 12;
        return (cantidad * factorCucharada) / (cantRef > 0 ? cantRef : 100);
    }

    return cantidad;
};

const aporteDetalle = (item) => {
    const nut = item?.nutricion || (item?.calorias !== undefined && item?.calorias !== null ? {
        calorias: item.calorias,
        proteina: item.proteina,
        carbohidratos: item.carbohidratos,
        grasa: item.grasa,
    } : null);

    if (!nut) {
        return {
            conDatos: false,
            calorias: 0,
            proteina: 0,
            carbohidratos: 0,
            grasa: 0,
        };
    }

    const factor = factorCantidad(item);
    return {
        conDatos: true,
        calorias: numeroSeguro(nut.calorias ?? nut.cal_tcn ?? nut.ene_tcn) * factor,
        proteina: numeroSeguro(nut.proteina ?? nut.pro_tcn) * factor,
        carbohidratos: numeroSeguro(nut.carbohidratos ?? nut.car_tcn ?? nut.cho_tcn) * factor,
        grasa: numeroSeguro(nut.grasa ?? nut.gra_tcn ?? nut.lip_tcn) * factor,
    };
};

const resumenEnVivo = computed(() => {
    return form.detalle.reduce(
        (acumulado, item) => {
            const aporte = aporteDetalle(item);
            acumulado.calorias += aporte.calorias;
            acumulado.proteina += aporte.proteina;
            acumulado.carbohidratos += aporte.carbohidratos;
            acumulado.grasa += aporte.grasa;
            if (!aporte.conDatos) {
                acumulado.sinDatos += 1;
            }
            return acumulado;
        },
        { calorias: 0, proteina: 0, carbohidratos: 0, grasa: 0, sinDatos: 0 }
    );
});

// Calorie & Macro Target Calculations
const metaCalorias = computed(() => props.metasNutricionales?.calorias || 2000);
const metaProteina = computed(() => props.metasNutricionales?.proteina || 75);
const metaCarbs = computed(() => props.metasNutricionales?.carbohidratos || 275);
const metaGrasa = computed(() => props.metasNutricionales?.grasa || 67);

const pctCalorias = computed(() => {
    const c = (resumenEnVivo.value.calorias / metaCalorias.value) * 100;
    return Math.min(Math.round(c), 100);
});
const caloriasRestantes = computed(() => Math.round(metaCalorias.value - resumenEnVivo.value.calorias));

const pctProteina = computed(() => Math.min(Math.round((resumenEnVivo.value.proteina / metaProteina.value) * 100), 100));
const pctCarbs = computed(() => Math.min(Math.round((resumenEnVivo.value.carbohidratos / metaCarbs.value) * 100), 100));
const pctGrasa = computed(() => Math.min(Math.round((resumenEnVivo.value.grasa / metaGrasa.value) * 100), 100));

// SVG Calorie Ring properties (radius = 38, circumference ~ 238.76)
const radioCirculo = 38;
const perimetroCirculo = 2 * Math.PI * radioCirculo;
const strokeDasharray = perimetroCirculo;
const strokeDashoffset = computed(() => {
    const progreso = Math.min(resumenEnVivo.value.calorias / metaCalorias.value, 1);
    return perimetroCirculo * (1 - progreso);
});

// Meal Moment Definitions & Grouping
const momentosConfig = [
    { id: 'desayuno', titulo: 'Desayuno', horarioSugerido: '07:00 – 09:30', defaultHour: '08:00', icon: 'sun' },
    { id: 'media_manana', titulo: 'Media Mañana', horarioSugerido: '10:00 – 11:30', defaultHour: '10:30', icon: 'leaf' },
    { id: 'almuerzo', titulo: 'Almuerzo', horarioSugerido: '12:30 – 14:30', defaultHour: '13:00', icon: 'utensils' },
    { id: 'merienda', titulo: 'Merienda', horarioSugerido: '16:00 – 17:30', defaultHour: '16:30', icon: 'cup' },
    { id: 'cena', titulo: 'Cena', horarioSugerido: '19:30 – 21:30', defaultHour: '20:00', icon: 'moon' },
    { id: 'snack', titulo: 'Snack / Colación', horarioSugerido: 'Libre', defaultHour: '18:00', icon: 'sparkles' },
];

const comidasOrganizadas = computed(() => {
    return momentosConfig.map((momento) => {
        const items = form.detalle
            .map((item, originalIndex) => ({ ...item, originalIndex }))
            .filter((item) => (item.tip_dra || 'desayuno') === momento.id);

        const calorias = items.reduce((acc, curr) => acc + aporteDetalle(curr).calorias, 0);
        const proteina = items.reduce((acc, curr) => acc + aporteDetalle(curr).proteina, 0);
        const carbs = items.reduce((acc, curr) => acc + aporteDetalle(curr).carbohidratos, 0);
        const grasa = items.reduce((acc, curr) => acc + aporteDetalle(curr).grasa, 0);

        return {
            ...momento,
            items,
            totalItems: items.length,
            calorias: Math.round(calorias),
            proteina: proteina.toFixed(1),
            carbs: carbs.toFixed(1),
            grasa: grasa.toFixed(1),
        };
    });
});

const itemsOrdenCronologico = computed(() => {
    return form.detalle
        .map((item, originalIndex) => ({ ...item, originalIndex }))
        .sort((a, b) => (a.hor_dra || '00:00').localeCompare(b.hor_dra || '00:00'));
});

// Formatting Helpers
const formatoNutricional = (valor) => numeroSeguro(valor).toFixed(1);

const etiquetaTipoComida = (tipo) => {
    const mapa = {
        desayuno: 'Desayuno',
        media_manana: 'Media Mañana',
        almuerzo: 'Almuerzo',
        merienda: 'Merienda',
        cena: 'Cena',
        snack: 'Snack / Colación',
    };
    return mapa[tipo] || String(tipo).replace(/_/g, ' ');
};

const etiquetaUnidad = (unidad) => {
    const mapa = {
        gramos: 'Gramos (g)',
        mililitros: 'Mililitros (ml)',
        porcion: 'Porción habitual',
        unidad: 'Unidad',
        taza: 'Taza',
        cucharada: 'Cucharada',
    };
    return mapa[unidad] || unidad;
};

// Search Handlers
let searchDebounceTimer = null;

const onSearchInput = () => {
    clearTimeout(searchDebounceTimer);
    if (!buscador.value.q || buscador.value.q.trim().length < 2) {
        if (!categoriaSeleccionada.value) {
            buscador.value.resultados = [];
            showSearchDropdown.value = false;
        }
        return;
    }
    searchDebounceTimer = setTimeout(() => {
        buscarAlimentos();
    }, 280);
};

const seleccionarCategoria = (catId) => {
    if (categoriaSeleccionada.value === catId) {
        categoriaSeleccionada.value = null;
        buscador.value.resultados = [];
        showSearchDropdown.value = false;
        return;
    }
    categoriaSeleccionada.value = catId;
    buscarAlimentos();
};

const buscarAlimentos = async (forzarUsda = false) => {
    buscador.value.error = null;
    if (!props.esFechaEditable) {
        buscador.value.error = 'No puedes modificar registros en fechas anteriores cerradas.';
        return;
    }

    const queryText = (buscador.value.q || '').trim();
    if (!queryText && !categoriaSeleccionada.value) {
        buscador.value.resultados = [];
        showSearchDropdown.value = false;
        return;
    }

    buscador.value.cargando = true;
    showSearchDropdown.value = true;

    try {
        const url = new URL(route('paciente.registros-alimenticios.buscar-alimentos'), window.location.origin);
        if (queryText) url.searchParams.set('q', queryText);
        if (categoriaSeleccionada.value) url.searchParams.set('categoria', categoriaSeleccionada.value);
        if (forzarUsda) url.searchParams.set('usda', '1');

        const response = await fetch(url.toString(), { credentials: 'same-origin' });
        if (!response.ok) throw new Error('Error al buscar alimentos');

        const data = await response.json();
        buscador.value.resultados = data.resultados ?? [];
        totalLocales.value = data.total_locales ?? 0;
        totalUsda.value = data.total_usda ?? 0;
    } catch (error) {
        buscador.value.error = 'No fue posible completar la búsqueda. Intenta nuevamente.';
    } finally {
        buscador.value.cargando = false;
    }
};

// Food Selection & Modal Flow
const buscarDuplicadoExacto = (codAlimento, tipDra) => {
    return form.detalle.findIndex(
        (item) => String(item.cod_alimentos_dra) === String(codAlimento) && item.tip_dra === tipDra
    );
};

const abrirBuscadorParaMomento = (momentoId) => {
    momentoComidaPreseleccionado.value = momentoId;
    const inputEl = document.getElementById('search-food-input');
    if (inputEl) {
        inputEl.focus();
        inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

const seleccionarResultado = async (resultado) => {
    if (!props.esFechaEditable) return;

    buscador.value.error = null;
    buscador.value.cargando = true;

    try {
        let foodData = resultado;

        // If USDA, import or resolve temporary item
        if (resultado.origen === 'usda') {
            const csrfToken = page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(route('paciente.registros-alimenticios.agregar-alimento-temporal'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ fdc_id: resultado.fdc_id }),
            });
            const resData = await res.json();
            if (!res.ok) throw new Error(resData.message || 'Error al descargar alimento de USDA');
            foodData = resData.alimento;
        }

        const esLiquido = Boolean(
            foodData.es_liquido ??
            (foodData.tip_ali === 'bebida' || foodData.uni_ref === 'ml' || /bebida|jugo|refresco|leche|agua|te|café|sopa|caldo/i.test(foodData.nom_ali || ''))
        );
        const cantRef = numeroSeguro(foodData.composicionVigente?.cant_ref_tcn ?? foodData.cant_ref ?? 100) || 100;
        
        let pesoPorcionFinal = foodData.peso_porcion ?? foodData.pes_por_ali;
        if (!pesoPorcionFinal && foodData.por_ref) {
            const match = String(foodData.por_ref).match(/(\d+(?:\.\d+)?)\s*(?:g|ml|gr)\b/i);
            if (match) {
                pesoPorcionFinal = Number.parseFloat(match[1]);
            }
        }
        if (!pesoPorcionFinal || pesoPorcionFinal <= 0) {
            pesoPorcionFinal = cantRef > 0 ? cantRef : 100;
        }

        const nutricionObj = foodData.nutricion ?? (foodData.calorias !== undefined && foodData.calorias !== null ? {
            calorias: numeroSeguro(foodData.calorias),
            proteina: numeroSeguro(foodData.proteina),
            carbohidratos: numeroSeguro(foodData.carbohidratos),
            grasa: numeroSeguro(foodData.grasa),
        } : null);

        const unidadesPermitidas = esLiquido
            ? ['porcion', 'mililitros', 'taza', 'cucharada']
            : ['porcion', 'gramos', 'unidad', 'taza', 'cucharada'];

        const momentoFinal = momentoComidaPreseleccionado.value || 'desayuno';
        const configMomento = momentosConfig.find((m) => m.id === momentoFinal) || momentosConfig[0];

        modalData.value = {
            cod_alimentos_dra: foodData.cod_alimentos ?? foodData.cod_alimentos_dra,
            nombre_alimento: foodData.nom_ali ?? foodData.nombre_alimento,
            nombre_original: foodData.nom_ext_ali ?? foodData.nombre_original ?? null,
            origen: foodData.src_ali ?? foodData.origen ?? null,
            pai_ali: foodData.pai_ali ?? null,
            es_boliviano: Boolean(foodData.es_boliviano || foodData.pai_ali === 'BO' || foodData.src_ali === 'bolivia'),
            por_ref: foodData.por_ref ?? null,
            cant_ref: cantRef,
            peso_porcion: pesoPorcionFinal,
            es_liquido: esLiquido,
            unidades_permitidas: unidadesPermitidas,
            tip_dra: momentoFinal,
            can_dra: '1',
            uni_dra: 'porcion',
            hor_dra: configMomento.defaultHour,
            nutricion: nutricionObj,
            isEditing: false,
            editIndex: null,
        };

        // Correctly set reactive ref value without constant assignment error
        comidaSeleccionadaManualmente.value = Boolean(momentoComidaPreseleccionado.value);
        showSearchDropdown.value = false;
        categoriaSeleccionada.value = null;
        buscador.value.q = '';
        momentoComidaPreseleccionado.value = null;
        showModalConfig.value = true;
    } catch (err) {
        buscador.value.error = err.message || 'Error al procesar el alimento seleccionado.';
    } finally {
        buscador.value.cargando = false;
    }
};

const editarAlimentoAgregado = (index) => {
    if (!props.esFechaEditable) return;
    const item = form.detalle[index];
    if (!item) return;

    modalData.value = {
        ...item,
        isEditing: true,
        editIndex: index,
        unidades_permitidas: item.unidades_permitidas ?? (item.es_liquido
            ? ['porcion', 'mililitros', 'taza', 'cucharada']
            : ['porcion', 'gramos', 'unidad', 'taza', 'cucharada']),
    };
    comidaSeleccionadaManualmente.value = true;
    showModalConfig.value = true;
};

const cambiarUnidadModal = (nuevaUnidad) => {
    modalData.value.uni_dra = nuevaUnidad;
    if (nuevaUnidad === 'porcion' || nuevaUnidad === 'unidad') {
        modalData.value.can_dra = '1';
    } else if (nuevaUnidad === 'gramos') {
        modalData.value.can_dra = String(modalData.value.peso_porcion || 100);
    } else if (nuevaUnidad === 'mililitros') {
        modalData.value.can_dra = '250';
    } else if (nuevaUnidad === 'taza') {
        modalData.value.can_dra = '1';
    } else if (nuevaUnidad === 'cucharada') {
        modalData.value.can_dra = '1';
    }
};

// Configuración de límites clínicos razonables por unidad de medida
const limitesPorUnidad = {
    gramos: { max: 2500, min: 1, paso: 25, label: 'gramos' },
    mililitros: { max: 3000, min: 1, paso: 25, label: 'mililitros' },
    porcion: { max: 15, min: 0.25, paso: 0.5, label: 'porciones' },
    unidad: { max: 15, min: 0.25, paso: 0.5, label: 'unidades' },
    taza: { max: 10, min: 0.25, paso: 0.5, label: 'tazas' },
    cucharada: { max: 20, min: 0.5, paso: 1, label: 'cucharadas' },
};

const limiteActual = computed(() => {
    return limitesPorUnidad[modalData.value.uni_dra] || { max: 2500, min: 0.1, paso: 1, label: modalData.value.uni_dra };
});

const errorCantidadModal = computed(() => {
    const raw = String(modalData.value.can_dra ?? '').trim();
    if (!raw) return 'Introduce una cantidad válida.';
    const val = numeroSeguro(raw);
    if (val <= 0) return 'La cantidad debe ser mayor a 0.';
    if (val > limiteActual.value.max) {
        return `Cantidad excesiva: el límite máximo es de ${limiteActual.value.max.toLocaleString()} ${limiteActual.value.label}.`;
    }
    return null;
});

const ajustarCantidadStepper = (delta) => {
    const actual = numeroSeguro(modalData.value.can_dra);
    const limite = limiteActual.value;
    const paso = limite.paso;

    let nuevo = actual + delta * paso;
    if (nuevo < limite.min) nuevo = limite.min;
    if (nuevo > limite.max) nuevo = limite.max;
    modalData.value.can_dra = String(Number(nuevo.toFixed(2)));
};

const fijarPorcionSugerida = (factor) => {
    const uni = modalData.value.uni_dra;
    if (['porcion', 'unidad', 'taza', 'cucharada'].includes(uni)) {
        modalData.value.can_dra = String(factor);
    } else if (uni === 'gramos') {
        const base = numeroSeguro(modalData.value.peso_porcion || 100);
        modalData.value.can_dra = String(Math.round(base * factor));
    } else if (uni === 'mililitros') {
        modalData.value.can_dra = String(Math.round(250 * factor));
    }
};

const confirmarAgregarAlimento = () => {
    if (errorCantidadModal.value) {
        return;
    }
    const cantidad = numeroSeguro(modalData.value.can_dra);
    if (cantidad <= 0) {
        return;
    }

    const itemParaGuardar = {
        cod_alimentos_dra: modalData.value.cod_alimentos_dra,
        nombre_alimento: modalData.value.nombre_alimento,
        nombre_original: modalData.value.nombre_original,
        origen: modalData.value.origen,
        pai_ali: modalData.value.pai_ali,
        es_boliviano: modalData.value.es_boliviano,
        cant_ref: modalData.value.cant_ref,
        peso_porcion: modalData.value.peso_porcion,
        es_liquido: modalData.value.es_liquido,
        unidades_permitidas: modalData.value.unidades_permitidas,
        tip_dra: modalData.value.tip_dra,
        can_dra: String(cantidad),
        uni_dra: modalData.value.uni_dra,
        hor_dra: modalData.value.hor_dra,
        nutricion: modalData.value.nutricion,
    };

    if (modalData.value.isEditing && modalData.value.editIndex !== null) {
        form.detalle[modalData.value.editIndex] = itemParaGuardar;
    } else {
        const duplicadoIdx = buscarDuplicadoExacto(itemParaGuardar.cod_alimentos_dra, itemParaGuardar.tip_dra);
        if (duplicadoIdx >= 0) {
            alimentoDuplicado.value = itemParaGuardar.nombre_alimento;
            detalleResaltado.value = duplicadoIdx;
            setTimeout(() => {
                alimentoDuplicado.value = null;
                detalleResaltado.value = null;
            }, 6000);
        }
        form.detalle.push(itemParaGuardar);
    }

    showModalConfig.value = false;
    activarAutoGuardado();
};

const eliminarDetalle = (index) => {
    if (!props.esFechaEditable) return;
    form.detalle.splice(index, 1);
    activarAutoGuardado();
};

// Form Actions & Saving
const activarAutoGuardado = () => {
    if (!props.esFechaEditable || !props.registroExistente) return;
    clearTimeout(temporizadorAutoGuardado);
    temporizadorAutoGuardado = setTimeout(() => {
        ejecutarGuardadoSilencioso();
    }, 3500);
};

const ejecutarGuardadoSilencioso = () => {
    if (form.processing || !props.esFechaEditable) return;
    autoGuardando.value = true;
    form.post(route('paciente.registros-alimenticios.store'), {
        preserveScroll: true,
        preserveState: true,
        only: ['registroExistente', 'flash'],
        onSuccess: () => {
            const d = new Date();
            ultimoAutoGuardado.value = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        onFinish: () => {
            autoGuardando.value = false;
        },
    });
};

const guardarRegistro = (estado = 'activo') => {
    if (!props.esFechaEditable) return;

    form.clearErrors();
    if (form.detalle.length === 0) {
        form.setError('detalle', 'Debes incluir al menos un alimento en tu diario.');
        return;
    }

    form.est_ral = estado;
    form.post(route('paciente.registros-alimenticios.store'), {
        preserveScroll: true,
        onSuccess: () => {
            const d = new Date();
            ultimoAutoGuardado.value = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
    });
};

const copiarDiarioAnterior = () => {
    if (!props.esFechaEditable) return;
    router.post(
        route('paciente.registros-alimenticios.copiar-anterior', props.paciente.cod_pacientes),
        { fecha_destino: form.fec_ral },
        {
            preserveScroll: true,
            preserveState: false,
        }
    );
};

// Close search on click outside
const handleClickOutside = (e) => {
    const searchBox = document.getElementById('search-container-box');
    if (searchBox && !searchBox.contains(e.target)) {
        showSearchDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    clearTimeout(temporizadorAutoGuardado);
    clearTimeout(searchDebounceTimer);
});
</script>

<template>
    <Head :title="`Diario de Ingesta · ${props.fechaSeleccionada}`" />

    <AuthenticatedLayout>
        <!-- TOP APP BAR / HEADER -->
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">
                            Diario de Alimentación
                        </h2>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                            :class="props.esFechaEditable 
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20' 
                                : 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-500/20'"
                        >
                            {{ props.esFechaEditable ? 'En Registro' : 'Modo Consulta' }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500">
                        {{ paciente.usuario?.nombre_completo || 'Paciente' }} · Ingesta y Nutrición Diaria
                    </p>
                </div>

                <!-- DATE CONTROLS & ACTIONS -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Date Stepper Navigator -->
                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-1 shadow-2xs">
                        <button
                            type="button"
                            @click="cambiarFecha(fechaAyer)"
                            class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer"
                            title="Día anterior"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <div class="px-2">
                            <input
                                type="date"
                                :value="form.fec_ral"
                                @change="cambiarFecha($event.target.value)"
                                class="rounded-md border-0 bg-transparent py-1 px-1 text-xs font-black text-slate-800 focus:ring-0 cursor-pointer text-center"
                            />
                        </div>

                        <button
                            v-if="!esHoy"
                            type="button"
                            @click="cambiarFecha(hoy)"
                            class="rounded-lg bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition cursor-pointer"
                        >
                            Hoy
                        </button>

                        <button
                            type="button"
                            @click="cambiarFecha(fechaManana)"
                            class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer"
                            title="Día siguiente"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Quick Action: Copy Yesterday when empty -->
                    <button
                        v-if="form.detalle.length === 0 && props.esFechaEditable"
                        @click="copiarDiarioAnterior"
                        class="rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-700 shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                        </svg>
                        <span>Re-registrar día anterior</span>
                    </button>

                    <!-- Save Draft Button -->
                    <button
                        v-if="props.esFechaEditable"
                        type="button"
                        class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs transition disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
                        :disabled="form.processing"
                        @click="guardarRegistro('activo')"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <span>{{ form.processing ? 'Guardando...' : 'Guardar borrador' }}</span>
                    </button>

                    <!-- Submit to Review Button -->
                    <button
                        v-if="props.esFechaEditable"
                        type="button"
                        class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-2xs transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                        :disabled="form.processing"
                        @click="guardarRegistro('cerrado')"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        <span>{{ form.processing ? 'Enviando...' : 'Enviar a revisión' }}</span>
                    </button>

                    <!-- History Button -->
                    <Link
                        :href="route('paciente.registros-alimenticios.historial', paciente.cod_pacientes)"
                        class="rounded-xl bg-slate-900 hover:bg-slate-800 px-3.5 py-2 text-xs font-bold text-white shadow-2xs transition flex items-center gap-1.5"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Historial</span>
                    </Link>
                </div>
            </div>
        </template>

        <!-- MAIN APP BODY -->
        <div class="py-6 sm:py-8 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- SUCCESS FLASH NOTIFICATION -->
                <div
                    v-if="page.props.flash?.success"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-800 shadow-2xs flex items-center justify-between"
                >
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ page.props.flash.success }}</span>
                    </div>
                </div>

                <!-- DUPLICATE FOOD WARNING -->
                <div
                    v-if="alimentoDuplicado"
                    class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-900 shadow-2xs flex items-center gap-2.5"
                >
                    <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Nota: Ya tenías registrado "{{ alimentoDuplicado }}" en este mismo momento del día. Se añadió como una porción adicional.</span>
                </div>

                <!-- CLINICAL PROGRESS & MACRO METRICS CARD -->
                <section class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm relative overflow-hidden">
                    <div class="grid gap-6 md:grid-cols-[0.8fr_1.2fr] items-center">
                        
                        <!-- Left: Circular Calorie Gauge -->
                        <div class="flex flex-col items-center justify-center p-3 border-b md:border-b-0 md:border-r border-slate-100">
                            <div class="relative flex items-center justify-center">
                                <svg class="w-36 h-36 transform -rotate-90">
                                    <circle
                                        cx="72"
                                        cy="72"
                                        :r="radioCirculo"
                                        stroke="#f1f5f9"
                                        stroke-width="9"
                                        fill="transparent"
                                    />
                                    <circle
                                        cx="72"
                                        cy="72"
                                        :r="radioCirculo"
                                        stroke="url(#calorieGradient)"
                                        stroke-width="9"
                                        fill="transparent"
                                        :stroke-dasharray="strokeDasharray"
                                        :stroke-dashoffset="strokeDashoffset"
                                        stroke-linecap="round"
                                        class="transition-all duration-700 ease-out"
                                    />
                                    <defs>
                                        <linearGradient id="calorieGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#10b981" />
                                            <stop offset="100%" stop-color="#059669" />
                                        </linearGradient>
                                    </defs>
                                </svg>

                                <div class="absolute text-center">
                                    <p class="text-2xl font-black text-slate-900 leading-none">
                                        {{ Math.round(resumenEnVivo.calorias) }}
                                    </p>
                                    <p class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400 mt-1">
                                        kcal de {{ metaCalorias }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 text-center space-y-1">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-black border"
                                    :class="caloriasRestantes >= 0 
                                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200' 
                                        : 'bg-rose-50 text-rose-800 border-rose-200'"
                                >
                                    {{ caloriasRestantes >= 0 ? `Restan ${caloriasRestantes} kcal para la meta` : `Excedido por ${Math.abs(caloriasRestantes)} kcal` }}
                                </span>
                            </div>
                        </div>

                        <!-- Right: Macronutrient Progress Bars & Sync Indicator -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between pb-1">
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Distribución de Macronutrientes</h3>
                                <span class="text-xs font-extrabold text-slate-600">{{ totalAlimentos = form.detalle.length }} alimentos registrados</span>
                            </div>

                            <!-- Protein Bar -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-teal-900">Proteína</span>
                                    <span class="text-slate-700">
                                        <strong>{{ formatoNutricional(resumenEnVivo.proteina) }}g</strong> / {{ metaProteina }}g
                                        <span class="text-teal-600 text-[11px] ml-1">({{ pctProteina }}%)</span>
                                    </span>
                                </div>
                                <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-teal-500 transition-all duration-500 ease-out"
                                        :style="{ width: `${pctProteina}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Carbs Bar -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-amber-900">Carbohidratos</span>
                                    <span class="text-slate-700">
                                        <strong>{{ formatoNutricional(resumenEnVivo.carbohidratos) }}g</strong> / {{ metaCarbs }}g
                                        <span class="text-amber-600 text-[11px] ml-1">({{ pctCarbs }}%)</span>
                                    </span>
                                </div>
                                <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-amber-500 transition-all duration-500 ease-out"
                                        :style="{ width: `${pctCarbs}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Fat Bar -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-rose-900">Grasas</span>
                                    <span class="text-slate-700">
                                        <strong>{{ formatoNutricional(resumenEnVivo.grasa) }}g</strong> / {{ metaGrasa }}g
                                        <span class="text-rose-600 text-[11px] ml-1">({{ pctGrasa }}%)</span>
                                    </span>
                                </div>
                                <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-rose-500 transition-all duration-500 ease-out"
                                        :style="{ width: `${pctGrasa}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Sync Status Bar -->
                            <div class="flex justify-between items-center text-[11px] text-slate-400 pt-3 border-t border-slate-100 font-medium">
                                <span v-if="autoGuardando" class="text-amber-700 font-bold flex items-center gap-1.5">
                                    <svg class="h-3 w-3 animate-spin text-amber-600" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Guardando en la nube...
                                </span>
                                <span v-else-if="ultimoAutoGuardado" class="text-emerald-700 font-bold flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 inline-block"></span>
                                    Guardado automático a las {{ ultimoAutoGuardado }}
                                </span>
                                <span v-else class="text-slate-400 flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-slate-300 inline-block"></span>
                                    Todos los cambios sincronizados
                                </span>

                                <span v-if="resumenEnVivo.sinDatos > 0" class="text-amber-700 font-bold">
                                    {{ resumenEnVivo.sinDatos }} alimento(s) sin composición
                                </span>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- OBJETIVOS CLÍNICOS Y METAS DE HÁBITO -->
                <ObjetivosClinicosWidget
                    v-if="props.objetivosActivos && props.objetivosActivos.length > 0"
                    :objetivos="props.objetivosActivos"
                    :paciente-id="props.paciente?.cod_pacientes"
                    :compact="true"
                    titulo="Tus Objetivos Clínicos y Pautas Diarias"
                    subtitulo="Monitorea tu avance hoy. Pulsa + para registrar el cumplimiento de cada una de tus metas."
                />

                <!-- EDITING LOCK BANNER (WHEN DATE IS CLOSED OR PAST) -->
                <section
                    v-if="motivoBloqueoEdicion"
                    class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 shadow-2xs space-y-2"
                >
                    <div class="flex items-center gap-2 text-amber-900 font-bold text-xs">
                        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Registro en Modo Consulta (Solo Lectura)</span>
                    </div>
                    <p class="text-xs text-slate-700 leading-relaxed">
                        {{ motivoBloqueoEdicion }}
                    </p>
                    <div v-if="validacionActual?.obs_vin" class="mt-2 text-xs text-slate-700 bg-white p-3 rounded-xl border border-amber-200/60">
                        <p class="font-extrabold text-slate-900 text-[10px] uppercase tracking-wider mb-1">Observación del Nutricionista:</p>
                        <p>{{ validacionActual.obs_vin }}</p>
                    </div>
                </section>

                <!-- SEARCH & FOOD DISCOVERY SECTION -->
                <section class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Paso 1: Busca o selecciona el alimento</p>
                            <h3 class="text-base font-black text-slate-900 tracking-tight mt-0.5">Buscador Nutricional de Alimentos</h3>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-800 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 flex items-center gap-1.5 self-start sm:self-auto">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>Catálogo Boliviano + Base Global USDA</span>
                        </span>
                    </div>

                    <!-- Category Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 -mx-1 px-1 scrollbar-none">
                        <button
                            v-for="cat in categoriasRapidas"
                            :key="cat.id"
                            type="button"
                            @click="seleccionarCategoria(cat.id)"
                            class="whitespace-nowrap px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border cursor-pointer"
                            :class="categoriaSeleccionada === cat.id 
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs' 
                                : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                            :disabled="!props.esFechaEditable"
                        >
                            <span>{{ cat.label }}</span>
                        </button>
                    </div>

                    <!-- Search Input Box & Autocomplete -->
                    <div id="search-container-box" class="relative w-full">
                        <div class="relative">
                            <input
                                id="search-food-input"
                                v-model="buscador.q"
                                type="text"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 py-3 pl-11 pr-10 text-sm font-medium text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none transition shadow-2xs"
                                placeholder="Escribe un alimento (ej. silpancho, pechuga de pollo, chuño, manzana, leche, huevo, salteña...)"
                                :disabled="!props.esFechaEditable"
                                @input="onSearchInput"
                                @focus="showSearchDropdown = (buscador.q && buscador.q.length >= 2) || !!categoriaSeleccionada"
                            />

                            <div class="absolute left-4 top-3.5 text-slate-400 pointer-events-none">
                                <svg v-if="buscador.cargando" class="animate-spin h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <button
                                v-if="buscador.q"
                                type="button"
                                @click="buscador.q = ''; showSearchDropdown = !!categoriaSeleccionada;"
                                class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600 p-0.5 rounded-lg cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Dropdown Results -->
                        <div
                            v-if="showSearchDropdown"
                            class="absolute z-30 mt-2 w-full max-h-96 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl divide-y divide-slate-100"
                        >
                            <div v-if="buscador.cargando" class="p-4 text-center text-xs text-slate-500 font-bold flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Buscando alimentos en catálogo...</span>
                            </div>

                            <div v-else-if="buscador.error" class="p-4 text-center text-xs text-rose-700 font-bold bg-rose-50/70 rounded-xl">
                                {{ buscador.error }}
                            </div>

                            <div v-else-if="buscador.resultados.length === 0" class="p-5 text-center text-xs text-slate-500 font-medium space-y-2">
                                <p>No se encontraron alimentos en el catálogo local para este criterio.</p>
                                <button
                                    v-if="buscador.q && buscador.q.length >= 3"
                                    type="button"
                                    @click="buscarAlimentos(true)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-50 text-blue-700 font-bold hover:bg-blue-100 transition border border-blue-200 cursor-pointer"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <span>Buscar y descargar desde la base global USDA</span>
                                </button>
                            </div>

                            <button
                                v-else
                                v-for="resultado in buscador.resultados"
                                :key="resultado.origen === 'local' ? `local-${resultado.cod_alimentos}` : `usda-${resultado.fdc_id}`"
                                type="button"
                                @click="seleccionarResultado(resultado)"
                                class="w-full flex items-center justify-between text-left p-3 rounded-xl hover:bg-emerald-50/60 border border-transparent hover:border-emerald-200 transition duration-150 group cursor-pointer"
                            >
                                <div class="min-w-0 pr-3 flex-1 space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span 
                                            v-if="resultado.es_boliviano || resultado.pai_ali === 'BO'" 
                                            class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-800 ring-1 ring-inset ring-amber-600/20"
                                        >
                                            Origen Bolivia
                                        </span>
                                        <p class="text-xs font-bold text-slate-900 group-hover:text-emerald-950 truncate">
                                            {{ resultado.nom_ali }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2 text-[10px] text-slate-500 flex-wrap font-medium">
                                        <span v-if="resultado.calorias !== null" class="font-extrabold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">
                                            {{ Math.round(resultado.calorias) }} kcal ({{ resultado.por_ref || '100g' }})
                                        </span>
                                        <span v-if="resultado.proteina !== null" class="text-teal-700 font-mono">
                                            P: {{ resultado.proteina }}g
                                        </span>
                                        <span v-if="resultado.carbohidratos !== null" class="text-amber-700 font-mono">
                                            C: {{ resultado.carbohidratos }}g
                                        </span>
                                        <span v-if="resultado.grasa !== null" class="text-rose-700 font-mono">
                                            G: {{ resultado.grasa }}g
                                        </span>
                                    </div>
                                </div>

                                <div class="shrink-0 flex items-center gap-2">
                                    <span 
                                        class="text-[10px] font-bold px-2 py-0.5 rounded-full border"
                                        :class="resultado.origen === 'usda' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                    >
                                        {{ resultado.origen === 'usda' ? 'USDA Global' : 'Local' }}
                                    </span>
                                    <span class="rounded-lg bg-emerald-600 text-white p-1 group-hover:bg-emerald-700 transition">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Frequent Foods Shortcut Buttons -->
                    <div v-if="props.alimentosFrecuentes && props.alimentosFrecuentes.length" class="space-y-2 pt-1 border-t border-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                            Tus alimentos frecuentes:
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="frecuente in props.alimentosFrecuentes"
                                :key="frecuente.cod_alimentos"
                                type="button"
                                class="rounded-xl border border-slate-200 bg-slate-50 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-900 px-3 py-1.5 text-xs font-semibold text-slate-700 transition active:scale-95 text-left flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                @click="seleccionarResultado(frecuente)"
                                :disabled="!props.esFechaEditable"
                            >
                                <span v-if="frecuente.es_boliviano || frecuente.pai_ali === 'BO'" class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>{{ frecuente.nom_ali }}</span>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- FOOD JOURNAL CONTAINER (BY MEALS OR CHRONOLOGICAL) -->
                <section class="rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden">
                    
                    <!-- Header with View Mode Switcher -->
                    <div class="border-b border-slate-100 px-6 py-4 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Paso 2: Revisa y confirma tus comidas</p>
                            <h3 class="text-sm font-black text-slate-900">Diario Alimenticio del Día</h3>
                        </div>

                        <!-- Segmented Switcher -->
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 self-start sm:self-auto shadow-2xs">
                            <button
                                type="button"
                                @click="vistaDiario = 'comidas'"
                                class="px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer flex items-center gap-1.5"
                                :class="vistaDiario === 'comidas' ? 'bg-slate-900 text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                                <span>Por Comidas</span>
                            </button>
                            <button
                                type="button"
                                @click="vistaDiario = 'cronologico'"
                                class="px-3 py-1 text-xs font-bold rounded-lg transition cursor-pointer flex items-center gap-1.5"
                                :class="vistaDiario === 'cronologico' ? 'bg-slate-900 text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Cronológico</span>
                            </button>
                        </div>
                    </div>

                    <!-- EMPTY STATE -->
                    <div v-if="form.detalle.length === 0" class="flex flex-col items-center justify-center text-center py-16 px-4 m-6 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50/40 space-y-3">
                        <div class="h-12 w-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800">Tu diario de hoy aún está vacío</h4>
                        <p class="text-xs text-slate-500 max-w-sm">
                            Usa el buscador para añadir los alimentos de cada momento del día o copia tu menú anterior.
                        </p>
                        <button
                            v-if="props.esFechaEditable"
                            type="button"
                            @click="copiarDiarioAnterior"
                            class="mt-2 inline-flex items-center gap-1.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs transition cursor-pointer"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                            <span>Copiar menú del último día registrado</span>
                        </button>
                    </div>

                    <!-- VIEW 1: ORGANIZED BY MEALS -->
                    <div v-else-if="vistaDiario === 'comidas'" class="p-6 space-y-5">
                        <div
                            v-for="comida in comidasOrganizadas"
                            :key="comida.id"
                            class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden transition"
                        >
                            <!-- Meal Header -->
                            <div class="px-5 py-3.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs">
                                        <svg v-if="comida.icon === 'sun'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <svg v-else-if="comida.icon === 'leaf'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                                        </svg>
                                        <svg v-else-if="comida.icon === 'utensils'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <svg v-else-if="comida.icon === 'cup'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <svg v-else-if="comida.icon === 'moon'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                        </svg>
                                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide">
                                                {{ comida.titulo }}
                                            </h4>
                                            <span class="text-[11px] text-slate-400 font-medium">({{ comida.horarioSugerido }})</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span 
                                        v-if="comida.totalItems > 0" 
                                        class="text-xs font-black text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2.5 py-0.5 rounded-lg"
                                    >
                                        {{ comida.calorias }} kcal
                                    </span>

                                    <!-- Quick Add for this specific meal -->
                                    <button
                                        v-if="props.esFechaEditable"
                                        type="button"
                                        @click="abrirBuscadorParaMomento(comida.id)"
                                        class="text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-xl transition border border-emerald-200 cursor-pointer flex items-center gap-1"
                                    >
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Añadir</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Foods List -->
                            <div v-if="comida.items.length > 0" class="divide-y divide-slate-100">
                                <div
                                    v-for="item in comida.items"
                                    :key="`${item.cod_alimentos_dra}-${item.originalIndex}`"
                                    class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/60 transition"
                                    :class="detalleResaltado === item.originalIndex ? 'bg-amber-50/80' : ''"
                                >
                                    <div class="space-y-1 min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-[10px] font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                                {{ item.hor_dra }}
                                            </span>
                                            <p class="text-xs font-bold text-slate-900 truncate">{{ item.nombre_alimento }}</p>
                                            <span 
                                                v-if="item.es_boliviano || item.pai_ali === 'BO'" 
                                                class="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.2 text-[9px] font-extrabold text-amber-800 border border-amber-200"
                                            >
                                                Bolivia
                                            </span>
                                        </div>

                                        <p class="text-xs text-slate-500">
                                            Cantidad: <strong class="text-slate-800">{{ item.can_dra }} {{ etiquetaUnidad(item.uni_dra) }}</strong> · 
                                            Aporte: <strong class="text-emerald-700">{{ Math.round(aporteDetalle(item).calorias) }} kcal</strong>
                                            <span v-if="item.nutricion" class="text-[10px] text-slate-400 ml-1.5">
                                                (P: {{ formatoNutricional(aporteDetalle(item).proteina) }}g · C: {{ formatoNutricional(aporteDetalle(item).carbohidratos) }}g · G: {{ formatoNutricional(aporteDetalle(item).grasa) }}g)
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Edit & Delete -->
                                    <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-center">
                                        <button
                                            type="button"
                                            @click="editarAlimentoAgregado(item.originalIndex)"
                                            class="rounded-xl bg-white border border-slate-200 hover:bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 transition shadow-2xs disabled:opacity-50 cursor-pointer flex items-center gap-1"
                                            :disabled="!props.esFechaEditable"
                                        >
                                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-2.036a5 5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <span>Editar</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="eliminarDetalle(item.originalIndex)"
                                            class="rounded-xl bg-white border border-rose-200 hover:bg-rose-50 px-2.5 py-1.5 text-xs font-bold text-rose-600 transition shadow-2xs disabled:opacity-50 cursor-pointer"
                                            :disabled="!props.esFechaEditable"
                                            title="Quitar alimento"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty Meal Placeholder -->
                            <div v-else class="p-4 text-center text-xs text-slate-400 bg-slate-50/30 flex items-center justify-center gap-2">
                                <span>Sin alimentos registrados en este momento.</span>
                                <button
                                    v-if="props.esFechaEditable"
                                    type="button"
                                    @click="abrirBuscadorParaMomento(comida.id)"
                                    class="text-emerald-700 font-bold hover:underline cursor-pointer"
                                >
                                    + Añadir ahora
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- VIEW 2: CHRONOLOGICAL TIMELINE -->
                    <div v-else-if="vistaDiario === 'cronologico'" class="p-6 space-y-4">
                        <div
                            v-for="item in itemsOrdenCronologico"
                            :key="`chrono-${item.cod_alimentos_dra}-${item.originalIndex}`"
                            class="flex items-start gap-4 p-4 rounded-2xl border border-slate-200/80 bg-white hover:bg-slate-50/60 transition shadow-2xs"
                        >
                            <div class="flex flex-col items-center shrink-0">
                                <span class="text-xs font-mono font-black text-slate-700 bg-slate-100 px-2 py-1 rounded-lg">
                                    {{ item.hor_dra }}
                                </span>
                                <span class="text-[10px] uppercase font-bold text-slate-400 mt-1">
                                    {{ etiquetaTipoComida(item.tip_dra) }}
                                </span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h5 class="text-xs font-bold text-slate-900 truncate">{{ item.nombre_alimento }}</h5>
                                    <span v-if="item.es_boliviano || item.pai_ali === 'BO'" class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">
                                        Bolivia
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Cantidad: <strong class="text-slate-800">{{ item.can_dra }} {{ etiquetaUnidad(item.uni_dra) }}</strong> · 
                                    Aporte: <strong class="text-emerald-700">{{ Math.round(aporteDetalle(item).calorias) }} kcal</strong>
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    @click="editarAlimentoAgregado(item.originalIndex)"
                                    class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 p-2 text-slate-600 transition shadow-2xs cursor-pointer"
                                    :disabled="!props.esFechaEditable"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-2.036a5 5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="eliminarDetalle(item.originalIndex)"
                                    class="rounded-xl border border-rose-200 bg-white hover:bg-rose-50 p-2 text-rose-600 transition shadow-2xs cursor-pointer"
                                    :disabled="!props.esFechaEditable"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <!-- PORTION CONFIGURATION MODAL -->
        <div
            v-if="showModalConfig"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
            @click.self="showModalConfig = false"
        >
            <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/80">
                    <div>
                        <div class="flex items-center gap-2">
                            <span 
                                v-if="modalData.es_boliviano || modalData.pai_ali === 'BO'"
                                class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-800 ring-1 ring-inset ring-amber-600/20"
                            >
                                Origen Bolivia
                            </span>
                            <span 
                                v-else-if="modalData.origen === 'usda'"
                                class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-extrabold text-blue-800 ring-1 ring-inset ring-blue-600/20"
                            >
                                USDA Global
                            </span>
                            <h3 class="text-base font-black text-slate-900 leading-tight">
                                {{ modalData.nombre_alimento }}
                            </h3>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">
                            Configura el momento del día y la cantidad que consumiste
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="showModalConfig = false"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-200/60 hover:text-slate-700 transition cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <div class="p-6 space-y-5">
                    
                    <!-- Meal Moment & Time -->
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Momento de Comida" class="text-xs font-bold uppercase tracking-wider text-slate-400" />
                            <select
                                v-model="modalData.tip_dra"
                                :class="claseSelectClaro"
                                @change="comidaSeleccionadaManualmente = true"
                            >
                                <option
                                    v-for="tipo in props.tiposComida"
                                    :key="tipo"
                                    :value="tipo"
                                >
                                    {{ etiquetaTipoComida(tipo) }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <InputLabel value="Hora de Ingesta" class="text-xs font-bold uppercase tracking-wider text-slate-400" />
                            <input
                                type="time"
                                v-model="modalData.hor_dra"
                                :class="claseControlClaro"
                            />
                        </div>
                    </div>

                    <!-- Unit Selector (Restricted to Valid Units) -->
                    <div class="space-y-1.5">
                        <InputLabel value="Unidad de Medida" class="text-xs font-bold uppercase tracking-wider text-slate-400" />
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button
                                v-for="unidad in modalData.unidades_permitidas"
                                :key="unidad"
                                type="button"
                                @click="cambiarUnidadModal(unidad)"
                                class="p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer text-center"
                                :class="modalData.uni_dra === unidad 
                                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs' 
                                    : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                            >
                                <span>{{ etiquetaUnidad(unidad) }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quantity with Stepper [+] and [-] -->
                    <div class="space-y-1.5">
                        <InputLabel value="Cantidad Consumida" class="text-xs font-bold uppercase tracking-wider text-slate-400" />
                        
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="ajustarCantidadStepper(-1)"
                                class="flex h-11 w-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 text-lg font-black transition cursor-pointer"
                            >
                                –
                            </button>

                            <div class="relative flex-1">
                                <input
                                    v-model="modalData.can_dra"
                                    type="text"
                                    inputmode="decimal"
                                    class="w-full rounded-xl border bg-white py-2.5 px-3 text-center text-base font-black text-slate-900 shadow-2xs focus:ring-1"
                                    :class="errorCantidadModal 
                                        ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500 bg-rose-50/20' 
                                        : 'border-slate-200 focus:border-emerald-500 focus:ring-emerald-500'"
                                />
                            </div>

                            <button
                                type="button"
                                @click="ajustarCantidadStepper(1)"
                                class="flex h-11 w-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 text-lg font-black transition cursor-pointer"
                            >
                                +
                            </button>
                        </div>

                        <!-- Quantity Limit & Error Message -->
                        <div class="flex items-center justify-between text-[11px] pt-1 px-1">
                            <span v-if="errorCantidadModal" class="text-rose-600 font-bold flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ errorCantidadModal }}</span>
                            </span>
                            <span v-else class="text-slate-400 font-medium">
                                Límite seguro: hasta {{ limiteActual.max.toLocaleString() }} {{ limiteActual.label }}
                            </span>
                        </div>
                    </div>

                    <!-- Helper Equivalence Card -->
                    <div 
                        v-if="['porcion', 'unidad'].includes(modalData.uni_dra)"
                        class="rounded-xl bg-emerald-50 border border-emerald-200/80 p-3 text-xs text-emerald-950 flex items-center gap-2.5"
                    >
                        <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <span class="font-bold">1 {{ modalData.uni_dra === 'unidad' ? 'unidad' : 'porción' }}</span> equivale a aprox. 
                            <strong class="font-black underline">{{ modalData.peso_porcion || modalData.cant_ref || 100 }} {{ modalData.es_liquido ? 'ml' : 'g' }}</strong>.
                            <span class="block text-[10px] text-emerald-800 font-normal">Ideal para registrar tus comidas sin necesidad de balanza.</span>
                        </div>
                    </div>

                    <!-- Quick Shortcut Buttons -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mr-1">Atajos:</span>
                        <template v-if="['porcion', 'unidad'].includes(modalData.uni_dra)">
                            <button 
                                type="button" 
                                @click="fijarPorcionSugerida(1)" 
                                class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-300 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition cursor-pointer"
                            >
                                1 porción
                            </button>
                            <button 
                                type="button" 
                                @click="fijarPorcionSugerida(0.5)" 
                                class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-300 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition cursor-pointer"
                            >
                                Media (0.5)
                            </button>
                            <button 
                                type="button" 
                                @click="fijarPorcionSugerida(2)" 
                                class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-300 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition cursor-pointer"
                            >
                                2 porciones
                            </button>
                        </template>
                        <template v-else-if="modalData.uni_dra === 'gramos'">
                            <button 
                                type="button" 
                                @click="modalData.can_dra = String(modalData.peso_porcion || 100)" 
                                class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-300 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition cursor-pointer"
                            >
                                1 porción ({{ modalData.peso_porcion || 100 }}g)
                            </button>
                            <button 
                                type="button" 
                                @click="modalData.can_dra = '100'" 
                                class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-300 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition cursor-pointer"
                            >
                                100g
                            </button>
                        </template>
                        <template v-else-if="modalData.uni_dra === 'mililitros'">
                            <button 
                                type="button" 
                                @click="modalData.can_dra = '250'" 
                                class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-300 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition cursor-pointer"
                            >
                                1 vaso (250ml)
                            </button>
                            <button 
                                type="button" 
                                @click="modalData.can_dra = '200'" 
                                class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-300 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition cursor-pointer"
                            >
                                1 taza (200ml)
                            </button>
                        </template>
                    </div>

                    <!-- Live Nutritional Contribution of this portion -->
                    <div class="border-t border-slate-100 pt-4 space-y-2">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Aporte de esta Porción</h4>
                        
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-2">
                                <p class="text-[9px] font-black uppercase tracking-wider text-emerald-800">Calorías</p>
                                <p class="text-xs font-black text-slate-900 mt-0.5">{{ Math.round(aporteDetalle(modalData).calorias) }}</p>
                                <p class="text-[8px] text-slate-400 font-bold">kcal</p>
                            </div>
                            <div class="rounded-xl border border-teal-100 bg-teal-50/50 p-2">
                                <p class="text-[9px] font-black uppercase tracking-wider text-teal-800">Proteína</p>
                                <p class="text-xs font-black text-slate-900 mt-0.5">{{ formatoNutricional(aporteDetalle(modalData).proteina) }}</p>
                                <p class="text-[8px] text-slate-400 font-bold">g</p>
                            </div>
                            <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-2">
                                <p class="text-[9px] font-black uppercase tracking-wider text-amber-800">Carbs</p>
                                <p class="text-xs font-black text-slate-900 mt-0.5">{{ formatoNutricional(aporteDetalle(modalData).carbohidratos) }}</p>
                                <p class="text-[8px] text-slate-400 font-bold">g</p>
                            </div>
                            <div class="rounded-xl border border-rose-100 bg-rose-50/50 p-2">
                                <p class="text-[9px] font-black uppercase tracking-wider text-rose-800">Grasa</p>
                                <p class="text-xs font-black text-slate-900 mt-0.5">{{ formatoNutricional(aporteDetalle(modalData).grasa) }}</p>
                                <p class="text-[8px] text-slate-400 font-bold">g</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Action Buttons -->
                <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/80 px-6 py-4">
                    <button
                        type="button"
                        @click="showModalConfig = false"
                        class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs transition cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="confirmarAgregarAlimento"
                        :disabled="!!errorCantidadModal"
                        class="rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed px-5 py-2.5 text-xs font-bold text-white shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <span>{{ modalData.isEditing ? 'Guardar Cambios' : 'Agregar al Diario' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

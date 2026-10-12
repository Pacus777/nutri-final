<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <title>Reporte General del Sistema</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9.5px; color:#1e293b; background:#fff; }

        /* ── Portada ── */
        .cover { background:#0f172a; color:#fff; padding:52px 44px 40px; margin-bottom:28px; }
        .cover-badge { display:inline-block; background:rgba(99,102,241,.2); color:#a5b4fc;
            font-size:7.5px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;
            padding:3px 12px; border-radius:99px; border:1px solid rgba(165,180,252,.3); margin-bottom:14px; }
        .cover-title  { font-size:24px; font-weight:bold; line-height:1.25; }
        .cover-sub    { font-size:10px; color:#94a3b8; margin-top:6px; }
        .cover-meta   { margin-top:20px; font-size:8.5px; color:#64748b; }
        .cover-meta span { color:#cbd5e1; }
        .cover-divider { border:none; border-top:1px solid rgba(255,255,255,.08); margin:18px 0; }
        .cover-stats  { width:100%; }
        .cover-stats td { padding:0 10px 0 0; vertical-align:top; }
        .cstat { text-align:center; }
        .cstat-val   { font-size:22px; font-weight:900; color:#fff; }
        .cstat-label { font-size:7.5px; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-top:2px; }

        /* ── Layout ── */
        .content { padding:0 36px 36px; }
        .section  { margin-bottom:26px; }
        .section-title { font-size:10px; font-weight:bold; letter-spacing:1.4px;
            text-transform:uppercase; color:#475569;
            border-bottom:2px solid #e2e8f0; padding-bottom:5px; margin-bottom:12px; }
        .two-col { width:100%; border-collapse:collapse; }
        .two-col td { vertical-align:top; padding:0; }
        .col-gap  { width:16px; }
        .page-break { page-break-before:always; }

        /* ── Highlight box ── */
        .highlight { background:#f8fafc; border-left:3px solid #6366f1;
            padding:10px 14px; border-radius:0 6px 6px 0; margin-bottom:14px;
            font-size:9px; color:#334155; line-height:1.6; }

        /* ── Cards ── */
        .cards { width:100%; border-collapse:collapse; }
        .cards td { padding:0 8px 0 0; vertical-align:top; }
        .cards td:last-child { padding-right:0; }
        .card { border-radius:10px; padding:12px; text-align:center; }
        .card-lbl { font-size:7px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; margin-bottom:5px; }
        .card-val { font-size:22px; font-weight:900; line-height:1; }
        .card-sub { font-size:7.5px; margin-top:4px; }

        .c-dark   { background:#0f172a; }
        .c-dark   .card-lbl { color:#64748b; }
        .c-dark   .card-val { color:#fff; }
        .c-dark   .card-sub { color:#64748b; }
        .c-green  { background:#f0fdf4; border:1px solid #bbf7d0; }
        .c-green  .card-lbl { color:#166534; } .c-green .card-val { color:#14532d; } .c-green .card-sub { color:#166534; }
        .c-violet { background:#f5f3ff; border:1px solid #ddd6fe; }
        .c-violet .card-lbl { color:#5b21b6; } .c-violet .card-val { color:#4c1d95; } .c-violet .card-sub { color:#5b21b6; }
        .c-amber  { background:#fffbeb; border:1px solid #fde68a; }
        .c-amber  .card-lbl { color:#92400e; } .c-amber .card-val { color:#78350f; } .c-amber .card-sub { color:#92400e; }
        .c-cyan   { background:#ecfeff; border:1px solid #a5f3fc; }
        .c-cyan   .card-lbl { color:#164e63; } .c-cyan .card-val { color:#0c4a6e; } .c-cyan .card-sub { color:#164e63; }
        .c-indigo { background:#eef2ff; border:1px solid #c7d2fe; }
        .c-indigo .card-lbl { color:#3730a3; } .c-indigo .card-val { color:#312e81; } .c-indigo .card-sub { color:#3730a3; }

        /* ── Tablas ── */
        .tbl { width:100%; border-collapse:collapse; font-size:9px; }
        .tbl th { background:#f1f5f9; color:#475569; font-weight:bold; text-align:left;
            padding:7px 9px; border-bottom:1px solid #e2e8f0; font-size:8px; letter-spacing:.5px; }
        .tbl td { padding:6px 9px; border-bottom:1px solid #f1f5f9; color:#334155; }
        .tbl tr:last-child td { border-bottom:none; }
        .num { text-align:right; font-weight:bold; color:#0f172a; }
        .pct { text-align:right; color:#64748b; }
        .badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:7.5px; font-weight:bold; }
        .bg-green  { background:#dcfce7; color:#166534; }
        .bg-gray   { background:#f1f5f9; color:#475569; }
        .bg-amber  { background:#fef3c7; color:#92400e; }
        .bg-blue   { background:#dbeafe; color:#1e40af; }
        .bg-violet { background:#ede9fe; color:#5b21b6; }
        .bg-cyan   { background:#cffafe; color:#164e63; }
        .bg-red    { background:#fee2e2; color:#991b1b; }
        .bg-indigo { background:#e0e7ff; color:#3730a3; }

        /* ── Footer ── */
        .footer { margin-top:32px; padding-top:10px; border-top:1px solid #e2e8f0;
            font-size:7.5px; color:#94a3b8; text-align:center; }

        /* ── Leyenda gráfica ── */
        .legend { font-size:8px; color:#334155; }
        .legend td { padding:2px 10px 2px 0; vertical-align:middle; }
        .dot { display:inline-block; width:9px; height:9px; border-radius:50%; margin-right:4px; vertical-align:middle; }
    </style>
</head>
<body>

{{-- ════════════════════════════════════
     PORTADA
═════════════════════════════════════ --}}
<div class="cover">
    <div class="cover-badge">Documento oficial · Uso interno</div>
    <div class="cover-title">Reporte General del Sistema</div>
    <div class="cover-sub">Sistema de Gestión Nutricional — Resumen estadístico operativo</div>
    <div class="cover-meta">Generado el <span>{{ $generadoEn }}</span></div>

    <hr class="cover-divider"/>

    @php
        $totalSistema = $resumen['usuarios']['total'] + $resumen['pacientes']['total']
            + $resumen['nutricionistas']['total'] + $resumen['asignaciones']['total'];
    @endphp
    <table class="cover-stats">
        <tr>
            <td width="25%"><div class="cstat"><div class="cstat-val">{{ $resumen['usuarios']['total'] }}</div><div class="cstat-label">Usuarios</div></div></td>
            <td width="25%"><div class="cstat"><div class="cstat-val">{{ $resumen['pacientes']['total'] }}</div><div class="cstat-label">Pacientes</div></div></td>
            <td width="25%"><div class="cstat"><div class="cstat-val">{{ $resumen['nutricionistas']['total'] }}</div><div class="cstat-label">Nutricionistas</div></div></td>
            <td width="25%"><div class="cstat"><div class="cstat-val">{{ $resumen['asignaciones']['activas'] }}</div><div class="cstat-label">Asig. activas</div></div></td>
        </tr>
    </table>
</div>

<div class="content">

{{-- ════════════════════════════════════
     SECCIÓN 1 — RESUMEN EJECUTIVO
═════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Resumen ejecutivo</div>
    <table class="tbl" style="margin-bottom:15px; border: 1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">
        <thead>
            <tr>
                <th style="background:#f1f5f9; color:#475569;">Indicador de Operación</th>
                <th style="background:#f1f5f9; color:#475569; text-align:right;">Total Registrado</th>
                <th style="background:#f1f5f9; color:#475569; text-align:right;">Estado / Cobertura</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight:bold; color:#1e293b;">Usuarios Registrados</td>
                <td class="num">{{ $resumen['usuarios']['total'] }}</td>
                <td class="num" style="color:#166534; font-weight:normal;">{{ $resumen['usuarios']['activos'] }} Activos</td>
            </tr>
            <tr>
                <td style="font-weight:bold; color:#1e293b;">Pacientes en Tratamiento</td>
                <td class="num">{{ $resumen['pacientes']['total'] }}</td>
                <td class="num" style="color:#166534; font-weight:normal;">{{ $resumen['pacientes']['activos'] }} Activos</td>
            </tr>
            <tr>
                <td style="font-weight:bold; color:#1e293b;">Cuerpo de Nutricionistas</td>
                <td class="num">{{ $resumen['nutricionistas']['total'] }}</td>
                <td class="num" style="color:#166534; font-weight:normal;">{{ $resumen['nutricionistas']['activos'] }} Activos</td>
            </tr>
            <tr>
                <td style="font-weight:bold; color:#1e293b;">Asignaciones Nutricionales Activas</td>
                <td class="num">{{ $resumen['asignaciones']['activas'] }}</td>
                <td class="num" style="color:#475569; font-weight:normal;">{{ $resumen['asignaciones']['cerradas'] }} Cerradas</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ════════════════════════════════════
     SECCIÓN 2 — MÉTRICAS PRINCIPALES + GRÁFICA DE BARRAS
     ═════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Métricas principales</div>

    {{-- Gráfica de barras horizontal — comparación activos vs inactivos --}}
    @php
        $barData = [
            ['label'=>'Usuarios activos',   'val'=>$resumen['usuarios']['activos'],         'color'=>'#6366f1'],
            ['label'=>'Usuarios inactivos', 'val'=>$resumen['usuarios']['inactivos'],        'color'=>'#c7d2fe'],
            ['label'=>'Pacientes activos',  'val'=>$resumen['pacientes']['activos'],         'color'=>'#22c55e'],
            ['label'=>'Pac. inactivos',     'val'=>$resumen['pacientes']['inactivos'],       'color'=>'#bbf7d0'],
            ['label'=>'Nutri. activos',     'val'=>$resumen['nutricionistas']['activos'],    'color'=>'#a78bfa'],
            ['label'=>'Nutri. inactivos',   'val'=>$resumen['nutricionistas']['inactivos'],  'color'=>'#ede9fe'],
            ['label'=>'Asig. activas',      'val'=>$resumen['asignaciones']['activas'],      'color'=>'#f59e0b'],
            ['label'=>'Asig. cerradas',     'val'=>$resumen['asignaciones']['cerradas'],     'color'=>'#fde68a'],
        ];
        $maxB = max(array_column($barData,'val')) ?: 1;
        $bW = 310; $bH = 13; $lW = 105; $vW = 40;
        $svgH = count($barData) * ($bH + 7) + 16;
    @endphp

    <div style="margin-top:14px;">
        <svg width="{{ $lW + $bW + $vW }}" height="{{ $svgH }}" xmlns="http://www.w3.org/2000/svg">
            @foreach($barData as $i => $b)
                @php $y = $i*($bH+7)+8; $w = max(3, round($b['val']/$maxB*$bW)); @endphp
                <text x="{{ $lW-5 }}" y="{{ $y+$bH*0.72 }}" text-anchor="end" font-size="8" fill="#475569">{{ $b['label'] }}</text>
                <rect x="{{ $lW }}" y="{{ $y }}" width="{{ $bW }}" height="{{ $bH }}" fill="#f1f5f9" rx="3"/>
                <rect x="{{ $lW }}" y="{{ $y }}" width="{{ $w }}" height="{{ $bH }}" fill="{{ $b['color'] }}" rx="3"/>
                <text x="{{ $lW+$bW+6 }}" y="{{ $y+$bH*0.72 }}" font-size="8" fill="#0f172a" font-weight="bold">{{ $b['val'] }}</text>
            @endforeach
        </svg>
    </div>
</div>

{{-- ════════════════════════════════════
     SECCIÓN 3 — DISTRIBUCIÓN DE PACIENTES
═════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Distribución de pacientes</div>
    @php
        $totalPac = $resumen['pacientes']['total'] ?: 1;
        $sexData  = array_filter($resumen['pacientes']['por_sexo'], fn($v) => $v > 0);
        $sexColors= ['#6366f1','#22c55e','#f59e0b','#94a3b8'];
        $edadData = $resumen['pacientes']['por_rango_edad'];
        $maxEdad  = max(array_values($edadData)) ?: 1;

        // Pie chart for sex
        $cx=80; $cy=80; $r=68; $innerR=36;
        $startAng = -90; // start from top
        $slices = [];
        $si = 0;
        foreach($sexData as $label => $val){
            $pct = $val/$totalPac;
            $endAng = $startAng + $pct*360;
            $startRad = deg2rad($startAng);
            $endRad   = deg2rad($endAng);
            $x1 = round($cx + $r*cos($startRad),2);
            $y1 = round($cy + $r*sin($startRad),2);
            $x2 = round($cx + $r*cos($endRad),2);
            $y2 = round($cy + $r*sin($endRad),2);
            $large = ($endAng - $startAng > 180) ? 1 : 0;
            $slices[] = [
                'path'  => "M $cx,$cy L $x1,$y1 A $r,$r 0 $large,1 $x2,$y2 Z",
                'color' => $sexColors[$si % count($sexColors)],
                'label' => $label,
                'val'   => $val,
                'pct'   => round($pct*100,1),
            ];
            $startAng = $endAng;
            $si++;
        }
    @endphp

    <table class="two-col">
        <tr>
            {{-- Pie chart — sexo --}}
            <td width="46%">
                <p style="font-size:8px;font-weight:bold;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Distribución por sexo</p>
                <svg width="160" height="160" xmlns="http://www.w3.org/2000/svg">
                    @foreach($slices as $sl)
                        <path d="{{ $sl['path'] }}" fill="{{ $sl['color'] }}"/>
                    @endforeach
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $innerR }}" fill="white"/>
                    <text x="{{ $cx }}" y="{{ $cy-6 }}" text-anchor="middle" font-size="8" fill="#64748b">Total</text>
                    <text x="{{ $cx }}" y="{{ $cy+8 }}" text-anchor="middle" font-size="14" font-weight="bold" fill="#0f172a">{{ $resumen['pacientes']['total'] }}</text>
                </svg>
                {{-- Leyenda --}}
                <table class="legend" style="margin-top:6px;">
                    @foreach($slices as $sl)
                    <tr>
                        <td><span class="dot" style="background:{{ $sl['color'] }};"></span></td>
                        <td>{{ $sl['label'] }}</td>
                        <td style="font-weight:bold;color:#0f172a;padding-left:4px;">{{ $sl['val'] }}</td>
                        <td style="color:#64748b;padding-left:4px;">{{ $sl['pct'] }}%</td>
                    </tr>
                    @endforeach
                </table>
            </td>

            <td class="col-gap"></td>

            {{-- Bar chart — edad --}}
            <td width="51%">
                <p style="font-size:8px;font-weight:bold;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Distribución por rango de edad</p>
                @php
                    $eBW=190; $eBH=14; $eLW=80; $eVW=50;
                    $eSvgH=count($edadData)*($eBH+8)+10;
                @endphp
                <svg width="{{ $eLW+$eBW+$eVW }}" height="{{ $eSvgH }}" xmlns="http://www.w3.org/2000/svg">
                    @foreach($edadData as $rango => $cant)
                    @php
                        $ey = $loop->index*($eBH+8)+6;
                        $ew = max(3, round($cant/$maxEdad*$eBW));
                        $eLabel = $rango==='Sin dato' ? 'Sin fecha nac.' : "Edad $rango";
                    @endphp
                    <text x="{{ $eLW-5 }}" y="{{ $ey+$eBH*0.72 }}" text-anchor="end" font-size="8" fill="#475569">{{ $eLabel }}</text>
                    <rect x="{{ $eLW }}" y="{{ $ey }}" width="{{ $eBW }}" height="{{ $eBH }}" fill="#f1f5f9" rx="3"/>
                    <rect x="{{ $eLW }}" y="{{ $ey }}" width="{{ $ew }}" height="{{ $eBH }}" fill="#6366f1" rx="3"/>
                    <text x="{{ $eLW+$eBW+6 }}" y="{{ $ey+$eBH*0.72 }}" font-size="8" fill="#0f172a" font-weight="bold">{{ $cant }}</text>
                    <text x="{{ $eLW+$eBW+26 }}" y="{{ $ey+$eBH*0.72 }}" font-size="7.5" fill="#94a3b8">{{ $totalPac>0 ? round($cant/$totalPac*100,1) : 0 }}%</text>
                    @endforeach
                </svg>
            </td>
        </tr>
    </table>
</div>

{{-- ════════════════════════════════════
     SECCIÓN 4 — MÓDULOS CLÍNICOS (tabla)
═════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Módulos clínicos — resumen</div>
    <table class="tbl" style="margin-bottom:15px; border: 1px solid #a5f3fc; border-radius:6px; background:#ecfeff;">
        <thead>
            <tr>
                <th style="background:#cffafe; color:#0e7490;">Módulo Clínico</th>
                <th style="background:#cffafe; color:#0e7490; text-align:right;">Total Registrado</th>
                <th style="background:#cffafe; color:#0e7490; text-align:right;">Métrica / Alcance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight:bold; color:#1e293b;">Contextos Clínicos Registrados</td>
                <td class="num">{{ $resumen['contextos']['total'] }}</td>
                <td class="num" style="color:#0891b2; font-weight:normal;">Patologías y condiciones de pacientes</td>
            </tr>
            <tr>
                <td style="font-weight:bold; color:#1e293b;">Objetivos Nutricionales Formulados</td>
                <td class="num">{{ $resumen['objetivos']['total'] }}</td>
                <td class="num" style="color:#0891b2; font-weight:normal;">Metas terapéuticas trazadas</td>
            </tr>
            <tr>
                <td style="font-weight:bold; color:#1e293b;">Planes de Alimentación Formulados</td>
                <td class="num">{{ $resumen['planes']['total'] }}</td>
                <td class="num" style="color:#0891b2; font-weight:normal;">Estrategias nutricionales prescritas</td>
            </tr>
            <tr>
                <td style="font-weight:bold; color:#1e293b;">Bitácoras Alimentarias Cargadas</td>
                <td class="num">{{ $resumen['registros_alimenticios']['total'] }}</td>
                <td class="num" style="color:#0891b2; font-weight:normal;">Registros diarios de comidas</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ════════════════════════════════════
     SECCIÓN 5 — GRÁFICAS OBJETIVOS Y PLANES
═════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Objetivos y planes por estado</div>

    @php
        $estadoColores = [
            'activo'      => '#22c55e',
            'vigente'     => '#6366f1',
            'cumplido'    => '#06b6d4',
            'pausado'     => '#f59e0b',
            'cancelado'   => '#ef4444',
            'cerrado'     => '#64748b',
            'reemplazado' => '#f97316',
        ];
        $objetivosEstados = $resumen['objetivos']['por_estado'];
        $planesEstados    = $resumen['planes']['por_estado'];
        $maxObj  = max(array_values($objetivosEstados) ?: [1]) ?: 1;
        $maxPlan = max(array_values($planesEstados) ?: [1]) ?: 1;
        $gBW=170; $gBH=14; $gLW=85; $gVW=40;
    @endphp

    <table class="two-col">
        <tr>
            {{-- Gráfica objetivos --}}
            <td width="48%">
                <p style="font-size:8px;font-weight:bold;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Objetivos nutricionales</p>
                @if(count($objetivosEstados))
                @php $gSvgH = count($objetivosEstados)*($gBH+8)+10; @endphp
                <svg width="{{ $gLW+$gBW+$gVW }}" height="{{ $gSvgH }}" xmlns="http://www.w3.org/2000/svg">
                    @foreach($objetivosEstados as $estado => $total)
                    @php
                        $gy = $loop->index*($gBH+8)+6;
                        $gw = max(3, round($total/$maxObj*$gBW));
                        $gc = $estadoColores[$estado] ?? '#94a3b8';
                    @endphp
                    <text x="{{ $gLW-5 }}" y="{{ $gy+$gBH*0.72 }}" text-anchor="end" font-size="8" fill="#475569">{{ ucfirst($estado) }}</text>
                    <rect x="{{ $gLW }}" y="{{ $gy }}" width="{{ $gBW }}" height="{{ $gBH }}" fill="#f1f5f9" rx="3"/>
                    <rect x="{{ $gLW }}" y="{{ $gy }}" width="{{ $gw }}" height="{{ $gBH }}" fill="{{ $gc }}" rx="3"/>
                    <text x="{{ $gLW+$gBW+6 }}" y="{{ $gy+$gBH*0.72 }}" font-size="8" fill="#0f172a" font-weight="bold">{{ $total }}</text>
                    @endforeach
                </svg>
                @else
                <p style="color:#94a3b8;font-size:8px;">Sin registros</p>
                @endif

                <table class="tbl" style="margin-top:10px;">
                    <thead><tr><th>Estado</th><th class="num">Total</th><th class="pct">%</th></tr></thead>
                    <tbody>
                        @forelse($objetivosEstados as $estado => $total)
                        @php $pctO = $resumen['objetivos']['total'] > 0 ? round($total/$resumen['objetivos']['total']*100,1) : 0; @endphp
                        <tr>
                            <td><span class="badge
                                @if(in_array($estado,['activo','vigente'])) bg-green
                                @elseif($estado==='cumplido') bg-blue
                                @elseif($estado==='pausado')  bg-amber
                                @elseif($estado==='cancelado') bg-red
                                @else bg-gray @endif">{{ ucfirst($estado) }}</span></td>
                            <td class="num">{{ $total }}</td>
                            <td class="pct">{{ $pctO }}%</td>
                        </tr>
                        @empty<tr><td colspan="3" style="color:#94a3b8">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>

            <td class="col-gap"></td>

            {{-- Gráfica planes --}}
            <td width="48%">
                <p style="font-size:8px;font-weight:bold;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Planes nutricionales</p>
                @if(count($planesEstados))
                @php $gSvgH2 = count($planesEstados)*($gBH+8)+10; @endphp
                <svg width="{{ $gLW+$gBW+$gVW }}" height="{{ $gSvgH2 }}" xmlns="http://www.w3.org/2000/svg">
                    @foreach($planesEstados as $estado => $total)
                    @php
                        $gy = $loop->index*($gBH+8)+6;
                        $gw = max(3, round($total/$maxPlan*$gBW));
                        $gc = $estadoColores[$estado] ?? '#94a3b8';
                    @endphp
                    <text x="{{ $gLW-5 }}" y="{{ $gy+$gBH*0.72 }}" text-anchor="end" font-size="8" fill="#475569">{{ ucfirst($estado) }}</text>
                    <rect x="{{ $gLW }}" y="{{ $gy }}" width="{{ $gBW }}" height="{{ $gBH }}" fill="#f1f5f9" rx="3"/>
                    <rect x="{{ $gLW }}" y="{{ $gy }}" width="{{ $gw }}" height="{{ $gBH }}" fill="{{ $gc }}" rx="3"/>
                    <text x="{{ $gLW+$gBW+6 }}" y="{{ $gy+$gBH*0.72 }}" font-size="8" fill="#0f172a" font-weight="bold">{{ $total }}</text>
                    @endforeach
                </svg>
                @else
                <p style="color:#94a3b8;font-size:8px;">Sin registros</p>
                @endif

                <table class="tbl" style="margin-top:10px;">
                    <thead><tr><th>Estado</th><th class="num">Total</th><th class="pct">%</th></tr></thead>
                    <tbody>
                        @forelse($planesEstados as $estado => $total)
                        @php $pctP = $resumen['planes']['total'] > 0 ? round($total/$resumen['planes']['total']*100,1) : 0; @endphp
                        <tr>
                            <td><span class="badge
                                @if(in_array($estado,['vigente','activo'])) bg-violet
                                @elseif($estado==='cerrado')     bg-cyan
                                @elseif($estado==='reemplazado') bg-amber
                                @else bg-gray @endif">{{ ucfirst($estado) }}</span></td>
                            <td class="num">{{ $total }}</td>
                            <td class="pct">{{ $pctP }}%</td>
                        </tr>
                        @empty<tr><td colspan="3" style="color:#94a3b8">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</div>

{{-- ════════════════════════════════════
     SECCIÓN 6 — TABLA GLOBAL COMPLETA
═════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Tabla resumen global</div>
    <table class="tbl">
        <thead>
            <tr>
                <th>Módulo</th>
                <th class="num">Total</th>
                <th class="num">Activos / En curso</th>
                <th class="num">Inactivos / Cerrados</th>
                <th class="pct">% Activo</th>
            </tr>
        </thead>
        <tbody>
            @php
                $rows = [
                    ['Usuarios',             $resumen['usuarios']['total'],         $resumen['usuarios']['activos'],          $resumen['usuarios']['inactivos']],
                    ['Pacientes',            $resumen['pacientes']['total'],         $resumen['pacientes']['activos'],         $resumen['pacientes']['inactivos']],
                    ['Nutricionistas',       $resumen['nutricionistas']['total'],    $resumen['nutricionistas']['activos'],    $resumen['nutricionistas']['inactivos']],
                    ['Asignaciones',         $resumen['asignaciones']['total'],      $resumen['asignaciones']['activas'],      $resumen['asignaciones']['cerradas']],
                    ['Contextos clínicos',   $resumen['contextos']['total'],         $resumen['contextos']['activos'],         $resumen['contextos']['inactivos']],
                    ['Objetivos nutric.',    $resumen['objetivos']['total'],         '—','—'],
                    ['Planes nutricionales', $resumen['planes']['total'],            '—','—'],
                    ['Registros alimenticios',$resumen['registros_alimenticios']['total'],'—','—'],
                ];
            @endphp
            @foreach($rows as $row)
            @php
                $pctA = is_numeric($row[1]) && $row[1] > 0 && is_numeric($row[2])
                    ? round($row[2]/$row[1]*100,1).'%' : '—';
            @endphp
            <tr>
                <td>{{ $row[0] }}</td>
                <td class="num">{{ $row[1] }}</td>
                <td class="num">{{ $row[2] }}</td>
                <td class="num">{{ $row[3] }}</td>
                <td class="pct">{{ $pctA }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- FOOTER --}}
<div class="footer">
    Sistema de Gestión Nutricional &nbsp;·&nbsp;
    Reporte generado el {{ $generadoEn }} &nbsp;·&nbsp;
    Documento de uso interno — No distribuir
</div>

</div><!-- /content -->
</body>
</html>

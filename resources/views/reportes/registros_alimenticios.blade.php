<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <title>Reporte de Registros Alimenticios</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9.5px; color:#1e293b; background:#fff; }

        /* ── Cabecera / Portada ── */
        .cover { background:#064e3b; color:#fff; padding:38px 44px 34px; margin-bottom:24px; }
        .cover-badge { display:inline-block; background:rgba(16,185,129,.25); color:#a7f3d0;
            font-size:7.5px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;
            padding:3.5px 12px; border-radius:99px; border:1px solid rgba(110,231,183,.4); margin-bottom:10px; }
        .cover-title  { font-size:21px; font-weight:bold; line-height:1.25; color:#ecfdf5; }
        .cover-sub    { font-size:9.5px; color:#a7f3d0; margin-top:4px; font-weight:500; }
        .cover-meta   { margin-top:18px; font-size:8.5px; color:#94a3b8; }
        .cover-meta span { color:#cbd5e1; font-weight:bold; }

        /* ── Layout ── */
        .content { padding:0 38px 38px; }
        .section  { margin-bottom:24px; }
        .section-title { font-size:10px; font-weight:bold; letter-spacing:1.5px;
            text-transform:uppercase; color:#0f766e;
            border-bottom:2px solid #ccfbf1; padding-bottom:5px; margin-bottom:14px; }
        .two-col { width:100%; border-collapse:collapse; }
        .two-col td { vertical-align:top; padding:0; }
        .col-gap  { width:18px; }

        /* ── Highlight box ── */
        .highlight { background:#f0fdf4; border-left:3px solid #10b981;
            padding:12px 16px; border-radius:0 8px 8px 0; margin-bottom:18px;
            font-size:9px; color:#064e3b; line-height:1.6; }

        /* ── Cards ── */
        .cards { width:100%; border-collapse:collapse; margin-bottom:18px; }
        .cards td { padding:0 8px 0 0; vertical-align:top; }
        .cards td:last-child { padding-right:0; }
        .card { border-radius:10px; padding:12px; text-align:center; box-shadow:0 1px 2px rgba(0,0,0,0.02); }
        .card-lbl { font-size:7px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; margin-bottom:5px; }
        .card-val { font-size:22px; font-weight:900; line-height:1; }
        .card-sub { font-size:7.5px; margin-top:4px; }

        .c-dark   { background:#064e3b; }
        .c-dark   .card-lbl { color:#a7f3d0; } .c-dark .card-val { color:#fff; } .c-dark .card-sub { color:#cbd5e1; }
        .c-green  { background:#f0fdf4; border:1px solid #bbf7d0; }
        .c-green  .card-lbl { color:#166534; } .c-green .card-val { color:#14532d; } .c-green .card-sub { color:#166534; }
        .c-amber  { background:#fffbeb; border:1px solid #fde68a; }
        .c-amber  .card-lbl { color:#92400e; } .c-amber .card-val { color:#78350f; } .c-amber .card-sub { color:#92400e; }
        .c-cyan   { background:#ecfeff; border:1px solid #a5f3fc; }
        .c-cyan   .card-lbl { color:#164e63; } .c-cyan .card-val { color:#0c4a6e; } .c-cyan .card-sub { color:#164e63; }

        /* ── Tablas ── */
        .tbl { width:100%; border-collapse:collapse; font-size:9px; }
        .tbl th { background:#f8fafc; color:#475569; font-weight:bold; text-align:left;
            padding:8px 10px; border-bottom:2px solid #e2e8f0; font-size:8px; letter-spacing:.5px; }
        .tbl td { padding:7px 10px; border-bottom:1px solid #f1f5f9; color:#334155; }
        .tbl tr:last-child td { border-bottom:none; }
        .num { text-align:right; font-weight:bold; color:#0f172a; }
        
        .badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:7.5px; font-weight:bold; }
        .bg-green  { background:#dcfce7; color:#166534; }
        .bg-gray   { background:#f1f5f9; color:#475569; }
        .bg-amber  { background:#fef3c7; color:#92400e; }
        .bg-red    { background:#fee2e2; color:#b91c1c; }

        /* ── Firmas ── */
        .signature-section { margin-top:40px; width:100%; border-collapse:collapse; }
        .signature-section td { width:50%; text-align:center; padding:15px; vertical-align:top; }
        .signature-line { border-top:1px solid #cbd5e1; width:180px; margin:0 auto 6px; }
        .signature-title { font-size:8px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; }

        /* ── Footer ── */
        .footer { margin-top:35px; padding-top:10px; border-top:1px solid #e2e8f0;
            font-size:7.5px; color:#94a3b8; text-align:center; }
    </style>
</head>
<body>

<div class="cover">
    <div class="cover-badge">Análisis Nutricional Avanzado</div>
    <div class="cover-title">Reporte de Consumo y Adherencia Alimentaria</div>
    <div class="cover-sub">Rango analizado: Desde {{ \Carbon\Carbon::parse($report['rango']['inicio'])->format('d/m/Y') }} hasta {{ \Carbon\Carbon::parse($report['rango']['fin'])->format('d/m/Y') }}</div>
    <div class="cover-meta">Generado el <span>{{ $generadoEn }}</span> &nbsp;·&nbsp; Registros totales en rango: <span>{{ $report['total_registros'] }}</span></div>
</div>

<div class="content">

    <div class="section">
        <div class="section-title">Análisis Ejecutivo del Período</div>
        <table class="tbl" style="margin-bottom:15px; border: 1px solid #ccfbf1; border-radius:6px; background:#f0fdf4;">
            <thead>
                <tr>
                    <th style="background:#e6fbf1; color:#0f766e;">Métrica Operativa</th>
                    <th style="background:#e6fbf1; color:#0f766e; text-align:right;">Cantidad Registrada</th>
                    <th style="background:#e6fbf1; color:#0f766e; text-align:right;">Estado del Período</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:bold; color:#1e293b;">Registros Alimenticios Ingresados</td>
                    <td class="num">{{ number_format($report['total_registros'], 0, ',', '.') }}</td>
                    <td class="num" style="color:#64748b; font-weight:normal;">Bitácoras completadas</td>
                </tr>
                <tr>
                    <td style="font-weight:bold; color:#1e293b;">Pacientes Únicos Activos</td>
                    <td class="num">{{ $report['pacientes_activos_conteo'] }}</td>
                    <td class="num" style="color:#64748b; font-weight:normal;">Pacientes con bitácora activa</td>
                </tr>
                <tr>
                    <td style="font-weight:bold; color:#1e293b;">Consumo Calórico Diario Promedio</td>
                    <td class="num">{{ number_format($report['calorias_promedio'], 0, ',', '.') }} kcal</td>
                    <td class="num" style="color:#64748b; font-weight:normal;">Consumo global promedio</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Promedio Diario y Balance de Macronutrientes</div>
        <table class="tbl" style="margin-bottom:15px; background:#f0fdf4; border: 1px solid #bbf7d0; border-radius:6px;">
            <thead>
                <tr>
                    <th style="background:#e6fbf1; color:#0f766e;">Nutriente / Componente</th>
                    <th style="background:#e6fbf1; color:#0f766e; text-align:right;">Promedio Diario Registrado</th>
                    <th style="background:#e6fbf1; color:#0f766e; text-align:right;">Métrica / Unidad</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:bold; color:#1e293b;">Calorías Totales</td>
                    <td class="num" style="font-size: 11px;">{{ number_format($report['calorias_promedio'], 0, ',', '.') }}</td>
                    <td style="color:#64748b;" class="num">kcal</td>
                </tr>
                <tr>
                    <td style="color:#064e3b; font-weight:bold;">Carbohidratos</td>
                    <td class="num" style="color:#166534;">{{ $report['carbohidratos_promedio'] }}</td>
                    <td style="color:#64748b;" class="num">gramos</td>
                </tr>
                <tr>
                    <td style="color:#064e3b; font-weight:bold;">Proteínas</td>
                    <td class="num" style="color:#1e40af;">{{ $report['proteinas_promedio'] }}</td>
                    <td style="color:#64748b;" class="num">gramos</td>
                </tr>
                <tr>
                    <td style="color:#064e3b; font-weight:bold;">Grasas</td>
                    <td class="num" style="color:#b45309;">{{ $report['grasas_promedio'] }}</td>
                    <td style="color:#64748b;" class="num">gramos</td>
                </tr>
            </tbody>
        </table>
    </div>

    @php
        $totalMacros = $report['carbohidratos_promedio'] + $report['proteinas_promedio'] + $report['grasas_promedio'];
    @endphp
    @if($totalMacros > 0)
    <div class="section">
        <div class="section-title">Distribución Porcentual Energética (%)</div>
        @php
            $pctCarbs = round(($report['carbohidratos_promedio'] / $totalMacros) * 100);
            $pctProt = round(($report['proteinas_promedio'] / $totalMacros) * 100);
            $pctFat = round(($report['grasas_promedio'] / $totalMacros) * 100);
        @endphp
        <div style="font-size: 8.5px; color:#475569; margin-bottom: 5px; font-weight: bold;">
            Carbohidratos: {{ $pctCarbs }}% &nbsp;|&nbsp;
            Proteínas: {{ $pctProt }}% &nbsp;|&nbsp;
            Grasas: {{ $pctFat }}%
        </div>
        <div style="background:#f1f5f9; border-radius:6px; height:12px; width:100%; overflow:hidden; border: 1px solid #e2e8f0;">
            @if($pctCarbs > 0)
                <div style="float:left; height:100%; width: {{ $pctCarbs }}%; background:#10b981;"></div>
            @endif
            @if($pctProt > 0)
                <div style="float:left; height:100%; width: {{ $pctProt }}%; background:#06b6d4;"></div>
            @endif
            @if($pctFat > 0)
                <div style="float:left; height:100%; width: {{ $pctFat }}%; background:#f59e0b;"></div>
            @endif
        </div>
    </div>
    @endif

    <table class="two-col" style="margin-bottom: 24px;">
        <tr>
            <td width="48%">
                <div class="section">
                    <div class="section-title">Alimentos Más Consumidos</div>
                    @if(count($report['comidas_frecuentes']) > 0)
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Alimento</th>
                                <th style="text-align:right;">Frecuencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['comidas_frecuentes'] as $com)
                            <tr>
                                <td>{{ $com['nombre'] }}</td>
                                <td style="text-align:right;" class="num">{{ $com['total'] }} veces</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p style="color:#94a3b8; font-size:8.5px; text-align:center; padding:10px 0;">Sin datos en este rango.</p>
                    @endif
                </div>
            </td>
            <td class="col-gap"></td>
            <td width="48%">
                <div class="section">
                    <div class="section-title">Distribución de Registros por Tipo de Comida</div>
                    @if(count($report['distribucion_comidas']) > 0)
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Comida</th>
                                <th style="text-align:right;">Registros</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['distribucion_comidas'] as $tipo => $total)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $tipo)) }}</td>
                                <td style="text-align:right;" class="num">{{ $total }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p style="color:#94a3b8; font-size:8.5px; text-align:center; padding:10px 0;">Sin datos en este rango.</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="two-col" style="margin-bottom: 24px;">
        <tr>
            <td width="48%">
                <div class="section">
                    <div class="section-title">Top Pacientes más Activos</div>
                    @if(count($report['top_pacientes']) > 0)
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th style="text-align:right;">Días Registrados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['top_pacientes'] as $tp)
                            <tr>
                                <td><strong>{{ $tp['nombre'] }}</strong></td>
                                <td style="text-align:right;" class="num">{{ $tp['total'] }} días</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p style="color:#94a3b8; font-size:8.5px; text-align:center; padding:10px 0;">Sin datos.</p>
                    @endif
                </div>
            </td>
            <td class="col-gap"></td>
            <td width="48%">
                <div class="section">
                    <div class="section-title">Pacientes Activos con Baja Actividad</div>
                    @if(count($report['pacientes_bajos']) > 0)
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th style="text-align:right;">Días Registrados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['pacientes_bajos'] as $pb)
                            <tr>
                                <td>
                                    <strong>{{ $pb['nombre'] }}</strong><br/>
                                    <span style="color:#64748b; font-size:7.5px;">{{ $pb['email'] }}</span>
                                </td>
                                <td style="text-align:right;">
                                    <span class="badge bg-red">{{ $pb['logs'] }} días</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p style="color:#94a3b8; font-size:8.5px; text-align:center; padding:10px 0;">Todos los pacientes registran adherencia normal.</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Firmas oficiales -->
    <table class="signature-section">
        <tr>
            <td>
                <div class="signature-line"></div>
                <p style="font-weight:bold; color:#1e293b;">Control de Calidad Alimentaria</p>
                <p class="signature-title">Auditor Nutricional</p>
            </td>
            <td>
                <div class="signature-line"></div>
                <p style="font-weight:bold; color:#1e293b;">Dirección General</p>
                <p class="signature-title">Administrador del Sistema</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        Sistema de Gestión Nutricional &nbsp;·&nbsp; Reporte de Consumo y Adherencia Alimentaria
    </div>

</div>

</body>
</html>

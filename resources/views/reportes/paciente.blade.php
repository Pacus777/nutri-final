<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <title>Historial Clínico - {{ $report['info']['nombre'] }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9.5px; color:#1e293b; background:#fff; }

        /* ── Cabecera / Portada ── */
        .cover { background:#1e3a8a; color:#fff; padding:38px 44px 34px; margin-bottom:24px; }
        .cover-badge { display:inline-block; background:rgba(59,130,246,.25); color:#bfdbfe;
            font-size:7.5px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;
            padding:3.5px 12px; border-radius:99px; border:1px solid rgba(147,197,253,.4); margin-bottom:10px; }
        .cover-title  { font-size:21px; font-weight:bold; line-height:1.25; color:#eff6ff; }
        .cover-sub    { font-size:9.5px; color:#93c5fd; margin-top:4px; font-weight:500; }
        .cover-meta   { margin-top:18px; font-size:8.5px; color:#cbd5e1; }
        .cover-meta span { color:#fff; font-weight:bold; }

        /* ── Layout ── */
        .content { padding:0 38px 38px; }
        .section  { margin-bottom:24px; }
        .section-title { font-size:10px; font-weight:bold; letter-spacing:1.5px;
            text-transform:uppercase; color:#1e3a8a;
            border-bottom:2px solid #dbeafe; padding-bottom:5px; margin-bottom:14px; }
        .two-col { width:100%; border-collapse:collapse; }
        .two-col td { vertical-align:top; padding:0; }
        .col-gap  { width:18px; }

        /* ── Alerts Banner ── */
        .alert-banner { background:#fef2f2; border:1px solid #fca5a5; border-left:4px solid #ef4444;
            padding:10px 14px; border-radius:6px; margin-bottom:18px; font-size:9px; color:#991b1b; }
        .alert-banner p { font-weight:bold; margin-bottom:4px; }

        /* ── Highlight box ── */
        .highlight { background:#f8fafc; border-left:3px solid #3b82f6;
            padding:12px 16px; border-radius:0 8px 8px 0; margin-bottom:18px;
            font-size:9px; color:#1e3a8a; line-height:1.6; }

        /* ── Cards ── */
        .cards { width:100%; border-collapse:collapse; margin-bottom:18px; }
        .cards td { padding:0 8px 0 0; vertical-align:top; }
        .cards td:last-child { padding-right:0; }
        .card { border-radius:10px; padding:12px; text-align:center; box-shadow:0 1px 2px rgba(0,0,0,0.02); }
        .card-lbl { font-size:7px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; margin-bottom:5px; }
        .card-val { font-size:22px; font-weight:900; line-height:1; }
        .card-sub { font-size:7.5px; margin-top:4px; }

        .c-dark   { background:#1e3a8a; }
        .c-dark   .card-lbl { color:#93c5fd; } .c-dark .card-val { color:#fff; } .c-dark .card-sub { color:#cbd5e1; }
        .c-green  { background:#f0fdf4; border:1px solid #bbf7d0; }
        .c-green  .card-lbl { color:#166534; } .c-green .card-val { color:#14532d; } .c-green .card-sub { color:#166534; }
        .c-violet { background:#faf5ff; border:1px solid #e9d5ff; }
        .c-violet .card-lbl { color:#7e22ce; } .c-violet .card-val { color:#581c87; } .c-violet .card-sub { color:#7e22ce; }
        .c-amber  { background:#fffbeb; border:1px solid #fde68a; }
        .c-amber  .card-lbl { color:#b45309; } .c-amber .card-val { color:#78350f; } .c-amber .card-sub { color:#b45309; }

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
        .bg-blue   { background:#dbeafe; color:#1e40af; }

        /* ── Info Grid ── */
        .info-grid { width:100%; border-collapse:collapse; margin-bottom:18px; font-size:9px; }
        .info-grid td { padding:7px 9px; border-bottom:1px solid #f1f5f9; }
        .info-grid tr:last-child td { border-bottom:none; }
        .info-grid td.label { font-weight:bold; color:#475569; width:22%; }
        .info-grid td.val { color:#1e293b; }

        /* ── Firmas ── */
        .signature-section { margin-top:40px; width:100%; border-collapse:collapse; }
        .signature-section td { width:50%; text-align:center; padding:15px; vertical-align:top; }
        .signature-line { border-top:1px solid #cbd5e1; width:180px; margin:0 auto 6px; }
        .signature-title { font-size:8px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; }

        .footer { margin-top:35px; padding-top:10px; border-top:1px solid #e2e8f0;
            font-size:7.5px; color:#94a3b8; text-align:center; }
    </style>
</head>
<body>

<div class="cover">
    <div class="cover-badge">Historial Clínico y Nutricional</div>
    <div class="cover-title">Paciente: {{ $report['info']['nombre'] }}</div>
    <div class="cover-sub">Edad: {{ $report['info']['edad'] ?? '—' }} años &nbsp;·&nbsp; Sexo: {{ $report['info']['sexo'] }} &nbsp;·&nbsp; Estado: {{ strtoupper($report['info']['estado']) }}</div>
    <div class="cover-meta">Generado el <span>{{ $generadoEn }}</span> &nbsp;·&nbsp; Correo: <span>{{ $report['info']['email'] }}</span></div>
</div>

<div class="content">

    @if(count($report['alertas_clinicas']) > 0)
    <div class="alert-banner">
        <p>⚠️ ALERTAS CLÍNICAS ACTIVAS (Prioridad Alta):</p>
        <ul style="margin-left:14px; list-style-type:square;">
            @foreach($report['alertas_clinicas'] as $alerta)
                <li><strong>{{ $alerta['tipo'] }}:</strong> {{ $alerta['descripcion'] }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Resumen Clínico Ejecutivo</div>
        <table class="tbl" style="margin-bottom:15px; border: 1px solid #dbeafe; border-radius:6px; background:#f8fafc;">
            <thead>
                <tr>
                    <th style="background:#eff6ff; color:#1e3a8a;">Indicador Clave de Seguimiento</th>
                    <th style="background:#eff6ff; color:#1e3a8a; text-align:right;">Métrica / Registro</th>
                    <th style="background:#eff6ff; color:#1e3a8a; text-align:right;">Evaluación del Período</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:bold; color:#1e293b;">Nivel de Adherencia Terapéutica</td>
                    <td class="num" style="color:#0f172a;">{{ $report['adherencia']['label'] }}</td>
                    <td class="num">
                        <span class="badge 
                            @if($report['adherencia']['estado'] === 'verde') bg-green
                            @elseif($report['adherencia']['estado'] === 'amarillo') bg-amber
                            @else bg-red @endif">
                            {{ strtoupper($report['adherencia']['estado']) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold; color:#1e293b;">Tasa de Cumplimiento de Bitácora (30 días)</td>
                    <td class="num" style="color:#0f172a;">{{ $report['logging_rate'] }}%</td>
                    <td class="num" style="color:#64748b; font-weight:normal;">
                        @if($report['logging_rate'] >= 85) Óptima
                        @elseif($report['logging_rate'] >= 60) Regular
                        @else Crítica @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold; color:#1e293b;">Variación de Peso desde Inicio</td>
                    <td class="num" style="color:{{ $report['cambio_peso'] < 0 ? '#166534' : '#b91c1c' }};">{{ $report['cambio_peso'] }} kg</td>
                    <td class="num" style="font-weight:bold; color:{{ $report['cambio_peso'] < 0 ? '#166534' : '#b91c1c' }};">
                        {{ $report['cambio_peso'] < 0 ? 'Pérdida de Peso' : 'Ganancia de Peso' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Ficha Clínico-Demográfica</div>
        <table class="info-grid" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background:#fafafa;">
            <tr>
                <td class="label">Estatura</td>
                <td class="val">{{ $report['info']['estatura'] ? $report['info']['estatura'] . ' m' : 'No registrada' }}</td>
                <td class="label">Peso Actual</td>
                <td class="val">{{ $report['info']['peso_actual'] ? $report['info']['peso_actual'] . ' kg' : 'No registrado' }}</td>
            </tr>
            <tr>
                <td class="label">IMC Estimado</td>
                <td class="val">
                    @if($report['info']['peso_actual'] && $report['info']['estatura'] && $report['info']['estatura'] > 0)
                        @php
                            $imc = round($report['info']['peso_actual'] / ($report['info']['estatura'] * $report['info']['estatura']), 1);
                        @endphp
                        {{ $imc }} &nbsp;
                        <span class="badge 
                            @if($imc < 18.5) bg-blue
                            @elseif($imc < 25) bg-green
                            @elseif($imc < 30) bg-amber
                            @else bg-red @endif">
                            @if($imc < 18.5) Bajo peso
                            @elseif($imc < 25) Normal
                            @elseif($imc < 30) Sobrepeso
                            @else Obesidad @endif
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td class="label">Tipo de Sangre</td>
                <td class="val">{{ $report['info']['tipo_sangre'] }}</td>
            </tr>
            <tr>
                <td class="label">Alergias</td>
                <td class="val" colspan="3">{{ $report['info']['alergias'] }}</td>
            </tr>
            <tr>
                <td class="label">Nutricionista Asignado</td>
                <td class="val" colspan="3">
                    @if($report['nutricionista'])
                        <strong>{{ $report['nutricionista']['nombre'] }}</strong> ({{ $report['nutricionista']['especialidad'] }}) &nbsp;·&nbsp; <span style="color:#64748b;">{{ $report['nutricionista']['email'] }}</span>
                    @else
                        <span style="color:#ef4444; font-weight:bold;">Ningún nutricionista asignado</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @if($report['paciente_averages'])
    <div class="section">
        <div class="section-title">Consumo Promedio Diario Realizado (Basado en {{ $report['paciente_averages']['total_dias'] }} días logueados)</div>
        <table class="tbl" style="margin-bottom:15px; background:#f8fafc; border: 1px solid #e2e8f0; border-radius:6px;">
            <thead>
                <tr>
                    <th style="background:#f1f5f9; color:#1e3a8a;">Nutriente / Componente</th>
                    <th style="background:#f1f5f9; color:#1e3a8a; text-align:right;">Promedio Diario Registrado</th>
                    <th style="background:#f1f5f9; color:#1e3a8a; text-align:right;">Métrica / Unidad</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:bold; color:#1e293b;">Calorías Totales</td>
                    <td class="num" style="font-size: 11px;">{{ number_format($report['paciente_averages']['calorias'], 0, ',', '.') }}</td>
                    <td style="color:#64748b;" class="num">kcal/día</td>
                </tr>
                <tr>
                    <td style="color:#1e3a8a; font-weight:bold;">Carbohidratos</td>
                    <td class="num" style="color:#2563eb;">{{ number_format($report['paciente_averages']['carbohidratos'], 1, ',', '.') }}</td>
                    <td style="color:#64748b;" class="num">gramos (g)</td>
                </tr>
                <tr>
                    <td style="color:#1e3a8a; font-weight:bold;">Proteínas</td>
                    <td class="num" style="color:#7c3aed;">{{ number_format($report['paciente_averages']['proteinas'], 1, ',', '.') }}</td>
                    <td style="color:#64748b;" class="num">gramos (g)</td>
                </tr>
                <tr>
                    <td style="color:#1e3a8a; font-weight:bold;">Grasas</td>
                    <td class="num" style="color:#d97706;">{{ number_format($report['paciente_averages']['grasas'], 1, ',', '.') }}</td>
                    <td style="color:#64748b;" class="num">gramos (g)</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Contexto Clínico del Paciente (Patologías y Condiciones)</div>
        @if(count($report['contextos']) > 0)
        <table class="tbl">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Prioridad</th>
                    <th>Descripción / Observación</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['contextos'] as $ctx)
                <tr>
                    <td>{{ $ctx['fecha'] }}</td>
                    <td><strong>{{ $ctx['tipo'] }}</strong></td>
                    <td>
                        <span class="badge 
                            @if($ctx['prioridad'] === 'alta') bg-red
                            @elseif($ctx['prioridad'] === 'media') bg-amber
                            @else bg-gray @endif">
                            {{ ucfirst($ctx['prioridad']) }}
                        </span>
                    </td>
                    <td>{{ $ctx['descripcion'] }}</td>
                    <td>{{ ucfirst($ctx['estado']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#94a3b8; font-size:9px; text-align:center; padding:10px 0;">Este paciente no tiene contextos clínicos registrados.</p>
        @endif
    </div>

    <table class="two-col" style="margin-bottom: 24px;">
        <tr>
            <td width="48%">
                <div class="section">
                    <div class="section-title">Objetivos Terapéuticos</div>
                    @if(count($report['objetivos']) > 0)
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Meta</th>
                                <th style="text-align:right;">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['objetivos'] as $obj)
                            <tr>
                                <td>{{ $obj['fecha'] }}</td>
                                <td>{{ $obj['descripcion'] }}</td>
                                <td style="text-align:right;">
                                    <span class="badge 
                                        @if(in_array($obj['estado'], ['activo','vigente'])) bg-green
                                        @elseif($obj['estado'] === 'cumplido') bg-blue
                                        @elseif($obj['estado'] === 'pausado') bg-amber
                                        @elseif($obj['estado'] === 'cancelado') bg-red
                                        @else bg-gray @endif">
                                        {{ ucfirst($obj['estado']) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p style="color:#94a3b8; font-size:8.5px; text-align:center; padding:10px 0;">Sin objetivos registrados.</p>
                    @endif
                </div>
            </td>
            <td class="col-gap"></td>
            <td width="48%">
                <div class="section">
                    <div class="section-title">Historial de Evolución de Peso</div>
                    @if(count($report['historial_peso']) > 0)
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th style="text-align:right;">Peso</th>
                                <th style="text-align:right;">IMC</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['historial_peso'] as $pes)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($pes['fecha'])->format('d/m/Y') }}</td>
                                <td style="text-align:right;" class="num">{{ number_format($pes['peso'], 1) }} kg</td>
                                <td style="text-align:right;">{{ $pes['imc'] ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p style="color:#94a3b8; font-size:8.5px; text-align:center; padding:10px 0;">Sin historial de peso registrado.</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="section" style="margin-bottom: 24px;">
        <div class="section-title">Plan Nutricional Vigente Prescrito</div>
        @if($report['plan'])
        <table class="tbl">
            <thead>
                <tr>
                    <th>Fecha de Creación</th>
                    <th>Plan / Prescripción</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td width="20%">{{ \Carbon\Carbon::parse($report['plan']['fecha'])->format('d/m/Y') }}</td>
                    <td>{{ $report['plan']['descripcion'] }}</td>
                    <td width="15%"><span class="badge bg-violet">{{ ucfirst($report['plan']['estado']) }}</span></td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="color:#94a3b8; font-size:9px; text-align:center; padding:10px 0;">No se registra un plan nutricional vigente para este paciente actualmente.</p>
        @endif
    </div>

    <!-- Firmas oficiales -->
    <table class="signature-section">
        <tr>
            <td>
                <div class="signature-line"></div>
                <p style="font-weight:bold; color:#1e293b;">Paciente</p>
                <p class="signature-title">Firma Paciente</p>
            </td>
            <td>
                <div class="signature-line"></div>
                <p style="font-weight:bold; color:#1e293b;">{{ $report['nutricionista'] ? $report['nutricionista']['nombre'] : 'Nutricionista Responsable' }}</p>
                <p class="signature-title">Firma Profesional</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        Sistema de Gestión Nutricional &nbsp;·&nbsp; Historial Clínico de Paciente
    </div>

</div>

</body>
</html>

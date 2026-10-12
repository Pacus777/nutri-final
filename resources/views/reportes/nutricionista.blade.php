<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <title>Reporte de Nutricionista - {{ $report['info']['nombre'] }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9.5px; color:#1e293b; background:#fff; }

        /* ── Cabecera / Portada ── */
        .cover { background:#1e1b4b; color:#fff; padding:38px 44px 34px; margin-bottom:24px; position:relative; }
        .cover-badge { display:inline-block; background:rgba(139,92,246,.25); color:#ddd6fe;
            font-size:7.5px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;
            padding:3.5px 12px; border-radius:99px; border:1px solid rgba(196,181,253,.4); margin-bottom:10px; }
        .cover-title  { font-size:21px; font-weight:bold; line-height:1.25; color:#f3e8ff; }
        .cover-sub    { font-size:9.5px; color:#c084fc; margin-top:4px; font-weight:500; }
        .cover-meta   { margin-top:18px; font-size:8.5px; color:#94a3b8; }
        .cover-meta span { color:#cbd5e1; font-weight:bold; }

        /* ── Layout ── */
        .content { padding:0 38px 38px; }
        .section  { margin-bottom:24px; }
        .section-title { font-size:10px; font-weight:bold; letter-spacing:1.5px;
            text-transform:uppercase; color:#4338ca;
            border-bottom:2px solid #e0e7ff; padding-bottom:5px; margin-bottom:14px; }
        .two-col { width:100%; border-collapse:collapse; }
        .two-col td { vertical-align:top; padding:0; }
        .col-gap  { width:18px; }

        /* ── Highlight box ── */
        .highlight { background:#f5f3ff; border-left:3px solid #8b5cf6;
            padding:12px 16px; border-radius:0 8px 8px 0; margin-bottom:18px;
            font-size:9px; color:#4c1d95; line-height:1.6; }

        /* ── Cards ── */
        .cards { width:100%; border-collapse:collapse; margin-bottom:18px; }
        .cards td { padding:0 8px 0 0; vertical-align:top; }
        .cards td:last-child { padding-right:0; }
        .card { border-radius:10px; padding:12px; text-align:center; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .card-lbl { font-size:7px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; margin-bottom:5px; }
        .card-val { font-size:22px; font-weight:900; line-height:1; }
        .card-sub { font-size:7.5px; margin-top:4px; }

        .c-dark   { background:#1e1b4b; }
        .c-dark   .card-lbl { color:#a78bfa; } .c-dark .card-val { color:#fff; } .c-dark .card-sub { color:#cbd5e1; }
        .c-violet { background:#faf5ff; border:1px solid #e9d5ff; }
        .c-violet .card-lbl { color:#7e22ce; } .c-violet .card-val { color:#581c87; } .c-violet .card-sub { color:#7e22ce; }
        .c-amber  { background:#fffbeb; border:1px solid #fde68a; }
        .c-amber  .card-lbl { color:#b45309; } .c-amber .card-val { color:#78350f; } .c-amber .card-sub { color:#b45309; }
        .c-cyan   { background:#f0fdfa; border:1px solid #99f6e4; }
        .c-cyan   .card-lbl { color:#0f766e; } .c-cyan .card-val { color:#115e59; } .c-cyan .card-sub { color:#0f766e; }

        /* ── Tablas ── */
        .tbl { width:100%; border-collapse:collapse; font-size:9px; }
        .tbl th { background:#f8fafc; color:#475569; font-weight:bold; text-align:left;
            padding:8px 10px; border-bottom:2px solid #e2e8f0; font-size:8px; letter-spacing:.5px; }
        .tbl td { padding:7px 10px; border-bottom:1px solid #f1f5f9; color:#334155; }
        .tbl tr:last-child td { border-bottom:none; }
        .num { text-align:right; font-weight:bold; color:#0f172a; }
        
        .badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:7.5px; font-weight:bold; }
        .bg-green  { background:#dcfce7; color:#15803d; }
        .bg-gray   { background:#f1f5f9; color:#475569; }
        .bg-amber  { background:#fef3c7; color:#b45309; }
        .bg-red    { background:#fee2e2; color:#b91c1c; }
        .bg-blue   { background:#e0f2fe; color:#0369a1; }

        /* ── Adherencia bar graph ── */
        .adherencia-bar-container { background:#f1f5f9; border-radius:6px; height:14px; width:100%; margin-top:6px; overflow:hidden; border: 1px solid #e2e8f0; }
        .adherencia-bar { float:left; height:100%; }

        /* ── Firmas / Footer ── */
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
    <div class="cover-badge">Reporte Oficial de Rendimiento</div>
    <div class="cover-title">Nutricionista: {{ $report['info']['nombre'] }}</div>
    <div class="cover-sub">Especialidad: {{ $report['info']['especialidad'] }} &nbsp;·&nbsp; Estado del Perfil: {{ strtoupper($report['info']['estado']) }}</div>
    <div class="cover-meta">Generado el <span>{{ $generadoEn }}</span> &nbsp;·&nbsp; Correo Electrónico: <span>{{ $report['info']['email'] }}</span></div>
</div>

<div class="content">

    @php
        $tot = array_sum($report['stats']['adherencia']);
        $pctGreen = $tot > 0 ? round(($report['stats']['adherencia']['verde'] / $tot) * 100) : 0;
    @endphp

    <div class="section">
        <div class="section-title">Resumen de Actividad Terapéutica</div>
        <table class="tbl" style="margin-bottom:15px; background:#f8fafc; border: 1px solid #e2e8f0; border-radius:6px;">
            <tbody>
                <tr>
                    <td style="font-weight:bold; color:#475569; width: 18%;">Pacientes Asignados</td>
                    <td class="num" style="width: 10%;">{{ $report['stats']['total_pacientes'] }}</td>
                    <td style="color:#64748b; width: 22%;">(Activos: {{ $report['stats']['pacientes_activos'] }})</td>
                    <td style="font-weight:bold; color:#475569; border-left:1px solid #e2e8f0; padding-left: 15px; width: 18%;">Planes Prescritos</td>
                    <td class="num" style="width: 10%;">{{ $report['stats']['total_planes'] }}</td>
                    <td style="color:#64748b; width: 22%;">acumulados</td>
                </tr>
                <tr>
                    <td style="font-weight:bold; color:#475569;">Objetivos Formulados</td>
                    <td class="num">{{ $report['stats']['total_objetivos'] }}</td>
                    <td style="color:#64748b;">clínicos</td>
                    <td style="font-weight:bold; color:#475569; border-left:1px solid #e2e8f0; padding-left: 15px;">Adherencia Óptima</td>
                    <td class="num" style="color:#15803d;">{{ $pctGreen }}%</td>
                    <td style="color:#15803d; font-weight:bold;">alta</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($tot > 0)
    <div class="section">
        <div class="section-title">Distribución de Adherencia (Seguimiento Nutricional)</div>
        <div style="font-size: 8.5px; color:#475569; margin-bottom: 5px; font-weight: bold;">
            Verde (Excelente): {{ $report['stats']['adherencia']['verde'] }} &nbsp;|&nbsp;
            Amarillo (Irregular): {{ $report['stats']['adherencia']['amarillo'] }} &nbsp;|&nbsp;
            Rojo (Atención/Inactivo): {{ $report['stats']['adherencia']['rojo'] }}
        </div>
        <div class="adherencia-bar-container">
            @php
                $pGreen = ($report['stats']['adherencia']['verde'] / $tot) * 100;
                $pYellow = ($report['stats']['adherencia']['amarillo'] / $tot) * 100;
                $pRed = ($report['stats']['adherencia']['rojo'] / $tot) * 100;
            @endphp
            @if($pGreen > 0)
                <div class="adherencia-bar" style="width: {{ $pGreen }}%; background:#22c55e;"></div>
            @endif
            @if($pYellow > 0)
                <div class="adherencia-bar" style="width: {{ $pYellow }}%; background:#f59e0b;"></div>
            @endif
            @if($pRed > 0)
                <div class="adherencia-bar" style="width: {{ $pRed }}%; background:#ef4444;"></div>
            @endif
        </div>
    </div>
    @endif

    <table class="two-col" style="margin-bottom: 24px;">
        <tr>
            <td width="48%">
                <div class="section">
                    <div class="section-title">Planes por Estado</div>
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th style="text-align:right;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['stats']['planes_breakdown'] as $est => $count)
                            <tr>
                                <td><span class="badge bg-violet">{{ ucfirst($est) }}</span></td>
                                <td style="text-align:right;" class="num">{{ $count }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" style="color:#94a3b8; text-align:center;">Sin datos de planes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </td>
            <td class="col-gap"></td>
            <td width="48%">
                <div class="section">
                    <div class="section-title">Objetivos por Estado</div>
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th style="text-align:right;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['stats']['objetivos_breakdown'] as $est => $count)
                            <tr>
                                <td>
                                    <span class="badge 
                                        @if(in_array($est, ['activo','vigente'])) bg-green
                                        @elseif($est==='cumplido') bg-blue
                                        @elseif($est==='pausado') bg-amber
                                        @elseif($est==='cancelado') bg-red
                                        @else bg-gray @endif">
                                        {{ ucfirst($est) }}
                                    </span>
                                </td>
                                <td style="text-align:right;" class="num">{{ $count }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" style="color:#94a3b8; text-align:center;">Sin datos de objetivos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Listado de Pacientes Asignados</div>
        @if(count($report['pacientes']) > 0)
        <table class="tbl">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Edad / Sexo</th>
                    <th>Fec. Asignación</th>
                    <th>Estado Asignación</th>
                    <th style="text-align:right;">Adherencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['pacientes'] as $pac)
                <tr>
                    <td>
                        <strong>{{ $pac['nombre'] }}</strong><br/>
                        <span style="color:#64748b; font-size:7.5px;">{{ $pac['email'] }}</span>
                    </td>
                    <td>{{ $pac['edad'] ?? '—' }} años · {{ $pac['sexo'] }}</td>
                    <td>{{ $pac['fecha_asignacion'] }}</td>
                    <td>
                        <span class="badge {{ $pac['estado_asignacion'] === 'activa' ? 'bg-green' : 'bg-gray' }}">
                            {{ ucfirst($pac['estado_asignacion']) }}
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <span class="badge 
                            @if($pac['adherencia']['estado'] === 'verde') bg-green
                            @elseif($pac['adherencia']['estado'] === 'amarillo') bg-amber
                            @else bg-red @endif">
                            {{ $pac['adherencia']['label'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#94a3b8; font-size:9px; text-align:center; padding: 20px 0;">Este nutricionista no tiene pacientes asignados en el sistema actualmente.</p>
        @endif
    </div>

    <!-- Firmas oficiales -->
    <table class="signature-section">
        <tr>
            <td>
                <div class="signature-line"></div>
                <p style="font-weight:bold; color:#1e293b;">{{ $report['info']['nombre'] }}</p>
                <p class="signature-title">Firma Nutricionista</p>
            </td>
            <td>
                <div class="signature-line"></div>
                <p style="font-weight:bold; color:#1e293b;">Control de Dirección</p>
                <p class="signature-title">Administrador del Sistema</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        Sistema de Gestión Nutricional &nbsp;·&nbsp; Reporte de Nutricionista
    </div>

</div>

</body>
</html>

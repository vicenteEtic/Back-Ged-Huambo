<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'Times New Roman', Times, serif; }
        body { font-size: 12px; color: #000; }
        .header { text-align: center; font-weight: bold; }
        .header .country { font-size: 14px; }
        .header .body-org { font-size: 13px; margin-top: 2px; }
        .title { text-align: center; font-weight: bold; text-decoration: underline; margin-top: 24px; font-size: 13px; }
        .meta { margin-top: 18px; }
        .meta .row { margin-bottom: 4px; }
        .meta .label { font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.data td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        table.data td.label { width: 32%; font-weight: bold; }
        .section { margin-top: 14px; font-weight: bold; }
        .content { margin-top: 6px; text-align: justify; }
        ul { margin: 4px 0 0 18px; padding: 0; }
        .decision { margin-top: 16px; font-size: 13px; }
        .decision .box { border: 1px solid #000; padding: 10px; margin-top: 6px; }
        .decision .approved { font-weight: bold; font-size: 15px; }
        .decision .rejected { font-weight: bold; font-size: 15px; }
        .signature { margin-top: 60px; text-align: right; }
        .signature .name { font-weight: bold; margin-top: 70px; }
        .signature .role { margin-top: 2px; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="country">REPÚBLICA DE ANGOLA</div>
        <div class="body-org">GOVERNO DA PROVÍNCIA DO {{ $province }}</div>
        <div class="body-org">GABINETE DE RECURSOS HUMANOS</div>
    </div>

    <div class="title">DESPACHO</div>

    <div class="meta">
        <div class="row"><span class="label">N.º:</span> {{ $request->despacho_number ?? $request->request_number }}</div>
        <div class="row">
            <span class="label">Data:</span>
            @php
                $months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                $date = $request->decided_at ?? $request->created_at;
            @endphp
            Huambo, aos {{ $date->format('d') }} de {{ $months[(int) $date->format('n') - 1] }} de {{ $date->format('Y') }}
        </div>
    </div>

    <table class="data">
        <tr>
            <td class="label">Funcionário</td>
            <td>{{ $request->employee?->nome_completo }}</td>
        </tr>
        <tr>
            <td class="label">N.º do Agente</td>
            <td>{{ $request->employee?->employee_number }}</td>
        </tr>
        <tr>
            <td class="label">Gabinete / Departamento</td>
            <td>{{ $request->employee?->department?->name }}</td>
        </tr>
        <tr>
            <td class="label">Cargo / Categoria</td>
            <td>
                {{ $request->employee?->position?->name }}
                {{ $request->employee?->position && $request->employee?->category ? ' — ' : '' }}
                {{ $request->employee?->category?->name }}
            </td>
        </tr>
        <tr>
            <td class="label">Tipo de Solicitação</td>
            <td>{{ $request->type?->name }}</td>
        </tr>
        <tr>
            <td class="label">Período da Dispensa</td>
            <td>{{ $request->start_date->format('d/m/Y') }} a {{ $request->end_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Motivo</td>
            <td>{{ $request->reason }}</td>
        </tr>
        @if ($request->description)
            <tr>
                <td class="label">Descrição</td>
                <td>{{ $request->description }}</td>
            </tr>
        @endif
        @if ($request->benefit_until)
            <tr>
                <td class="label">Fim do benefício</td>
                <td>{{ $request->benefit_until->format('d/m/Y') }}</td>
            </tr>
        @endif
        @if (count($request->documents))
            <tr>
                <td class="label">Documentos apresentados</td>
                <td>
                    <ul>
                        @foreach ($request->documents as $doc)
                            <li>{{ $doc->original_name }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endif
    </table>

    <div class="section">Parecer</div>
    <div class="content">{{ $note ?? 'Sem parecer adicional.' }}</div>

    <div class="decision">
        Considerando a solicitação apresentada, o Gabinete de Recursos Humanos decide:
        <div class="box">
            @if ($decision === 'approved')
                <div class="approved">APROVADO</div>
            @elseif ($decision === 'rejected')
                <div class="rejected">REJEITADO</div>
            @else
                <div>{{ $decision }}</div>
            @endif
        </div>
    </div>

    <div class="signature">
        <div>Huambo, aos {{ $date->format('d') }} de {{ $months[(int) $date->format('n') - 1] }} de {{ $date->format('Y') }}</div>
        <div class="name">{{ $request->decidedBy?->name ?? $request->decidedBy?->username ?? '' }}</div>
        <div class="role">Director do Gabinete de Recursos Humanos</div>
        <div class="role">________________________________________</div>
    </div>

    <div class="footer">Documento gerado pelo Sistema de Gestão de Recursos Humanos — {{ $appName }}</div>
</body>
</html>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'Times New Roman', Times, serif; }
        body { font-size: 10.5px; color: #000; }
        .header { text-align: center; font-weight: bold; }
        .header .country { font-size: 13px; }
        .header .body-org { font-size: 12px; margin-top: 2px; }
        .title { text-align: center; font-weight: bold; text-decoration: underline; margin-top: 16px; font-size: 12px; }
        .meta { margin-top: 12px; margin-bottom: 10px; }
        .meta .row { margin-bottom: 3px; }
        .meta .label { font-weight: bold; }
        .summary { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .summary td, .summary th { border: 1px solid #000; padding: 5px 7px; text-align: center; }
        .summary th { background: #f0f0f0; }
        table.records { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.records th, table.records td { border: 1px solid #000; padding: 4px 6px; }
        table.records th { background: #f0f0f0; }
        .signature { margin-top: 50px; text-align: center; }
        .signature td { width: 33%; }
        .signature .name { margin-top: 80px; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 8.5px; }
        .status { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="country">REPÚBLICA DE ANGOLA</div>
        <div class="body-org">GOVERNO DA PROVÍNCIA DO HUAMBO</div>
        <div class="body-org">GABINETE DE RECURSOS HUMANOS</div>
    </div>

    <div class="title">RELATÓRIO DE PONTUALIDADE E ASSIDUIDADE</div>

    <div class="meta">
        @if ($employee)
            <div class="row"><span class="label">Funcionário:</span> {{ $employee['name'] }}</div>
            <div class="row"><span class="label">N.º do Agente:</span> {{ $employee['employee_number'] }}</div>
            <div class="row"><span class="label">Gabinete / Departamento:</span> {{ $employee['department'] }}</div>
        @endif
        <div class="row"><span class="label">Período:</span> {{ $periodLabel }}</div>
        <div class="row"><span class="label">De:</span> {{ $listing['filters']['start_date'] }} <span class="label">a:</span> {{ $listing['filters']['end_date'] }}</div>
        <div class="row"><span class="label">Gerado a:</span> {{ $generatedAt }} <span class="label">por:</span> {{ $generatedBy }}</div>
    </div>

    <table class="summary">
        <tr>
            <th>Funcionários</th>
            <th>Registos</th>
            <th>Dias úteis</th>
            <th>Presentes</th>
            <th>Atrasos</th>
            <th>Faltas justificadas</th>
            <th>Faltas injustificadas</th>
            <th>Dispensas</th>
            <th>Total horas</th>
        </tr>
        <tr>
            <td>{{ $listing['summary']['employees_count'] }}</td>
            <td>{{ $listing['summary']['total_records'] }}</td>
            <td>{{ $listing['summary']['working_days'] }}</td>
            <td>{{ $listing['summary']['present'] }}</td>
            <td>{{ $listing['summary']['late'] }}</td>
            <td>{{ $listing['summary']['justified_absences'] }}</td>
            <td>{{ $listing['summary']['unjustified_absences'] }}</td>
            <td>{{ $listing['summary']['dispensado'] }}</td>
            <td>{{ $listing['summary']['total_hours_worked'] }}</td>
        </tr>
    </table>

    <table class="records">
        <thead>
            <tr>
                <th>Data</th>
                <th>Funcionário</th>
                <th>N.º Agente</th>
                <th>Entrada</th>
                <th>Saída</th>
                <th>Horas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td>{{ $record['date'] }}</td>
                    <td>{{ $record['employee_name'] }}</td>
                    <td>{{ $record['employee_number'] }}</td>
                    <td>{{ $record['check_in'] }}</td>
                    <td>{{ $record['check_out'] }}</td>
                    <td>{{ $record['hours_worked'] }}</td>
                    <td class="status">{{ $record['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">Sem registos no período seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>
                <div>O Técnico de RH</div>
                <div class="name">________________________</div>
            </td>
            <td></td>
            <td>
                <div>O Director de RH</div>
                <div class="name">________________________</div>
            </td>
        </tr>
    </table>

    <div class="footer">Documento gerado pelo Sistema de Gestão de Recursos Humanos — {{ $appName }}</div>
</body>
</html>
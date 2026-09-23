<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 9mm 12mm; }
        * { font-family: 'Times New Roman', Times, serif; }
        body { color: #000; font-size: 8px; }
        .header { text-align: center; font-weight: bold; line-height: 1.2; }
        /* O ficheiro já contém o cabeçalho horizontal; fica centrado acima da República. */
        .logo { display: block; width: 260px; height: auto; margin: 0 auto 4px; }
        .header .country { font-size: 13px; }
        .header .org { font-size: 11px; }
        .director { position: absolute; right: 0; top: 0; width: 125px; text-align: center; font-size: 8px; }
        .title { margin: 10px 0 5px; text-align: center; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 0.7px solid #333; text-align: center; padding: 2px; }
        th { font-weight: bold; }
        .group { height: 21px; font-size: 9px; }
        .vertical { height: 112px; padding: 0; vertical-align: bottom; }
        .vertical span { display: block; transform: rotate(-90deg); white-space: nowrap; width: 105px; margin: 0 auto 35px; }
        .item { width: 24px; }
        .number { width: 58px; }
        .name { width: 145px; text-align: left; }
        .category { width: 125px; text-align: left; }
        .absence { width: 27px; }
        .total { width: 33px; }
        .effective { width: 36px; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .footer { margin-top: 12px; text-align: center; font-weight: bold; font-size: 9px; }
        .signatures { margin-top: 20px; }
        .signatures td { border: 0; width: 50%; padding-top: 20px; }
        .small { font-size: 7px; font-weight: normal; }
    </style>
</head>
<body>
    <div class="director"><b>VISTO<br>O DIRECTOR</b><br><br>________________<br><span class="small">Data e assinatura</span></div>
    @if ($logo)
        <img class="logo" src="{{ $logo }}" alt="Logo do Huambo">
    @endif
    <div class="header">
        <div class="country">REPÚBLICA DE ANGOLA</div>
        <div class="org">GOVERNO DA PROVÍNCIA DO HUAMBO</div>
        <div class="org">GABINETE DE RECURSOS HUMANOS</div>
        <div class="org">DEPARTAMENTO DE GESTÃO DE CARREIRAS E CAPACITAÇÃO TÉCNICA</div>
    </div>
    <div class="title">Mapa de efectividade do pessoal da sede do Governo, afecto ao Gabinete de Sua Excelência Governador, referente ao mês de {{ $month_name }}/{{ $year }}@if ($department_name), {{ $department_name }}@endif</div>

    <table>
        <thead>
            <tr class="group">
                <th rowspan="3" class="item">Item</th>
                <th colspan="1">«1»</th>
                <th colspan="1">«2»</th>
                <th colspan="1">«3»</th>
                <th colspan="5">«4»</th>
                <th colspan="7">«5»</th>
                <th rowspan="3" class="total">Total de<br>faltas</th>
                <th rowspan="3" class="effective">Dias de<br>efectividade</th>
            </tr>
            <tr>
                <th rowspan="2" class="vertical number"><span>Número de Agente</span></th>
                <th rowspan="2" class="name">Nome Completo</th>
                <th rowspan="2" class="category">Categoria</th>
                <th colspan="5">FALTAS<br><span class="small">Justificadas nos termos da Lei n.º 26/22 de 22 de Agosto</span></th>
                <th colspan="7">LICENÇAS</th>
            </tr>
            <tr>
                <th class="vertical absence"><span>Injustificadas</span></th>
                <th class="vertical absence"><span>Artigo n.º 65</span></th>
                <th class="vertical absence"><span>Artigo n.º 66</span></th>
                <th class="vertical absence"><span>Artigo n.º 67</span></th>
                <th class="vertical absence"><span>Artigo n.º 68</span></th>
                <th class="vertical absence"><span>Licença p/ Doença</span></th>
                <th class="vertical absence"><span>Licença de Casamento</span></th>
                <th class="vertical absence"><span>Licença p/ Parto</span></th>
                <th class="vertical absence"><span>Licença Disciplinar</span></th>
                <th class="vertical absence"><span>Licença Registada</span></th>
                <th class="vertical absence"><span>Licença Chamada</span></th>
                <th class="vertical absence"><span>Outras</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td><td>{{ $row['employee_number'] }}</td>
                    <td class="name">{{ $row['full_name'] }}</td><td class="category">{{ $row['category'] }}</td>
                    <td>{{ $row['unjustified'] ?: '' }}</td><td>{{ $row['article_65'] ?: '' }}</td><td>{{ $row['article_66'] ?: '' }}</td><td>{{ $row['article_67'] ?: '' }}</td><td>{{ $row['article_68'] ?: '' }}</td>
                    <td>{{ $row['sickness'] ?: '' }}</td><td>{{ $row['marriage'] ?: '' }}</td><td>{{ $row['childbirth'] ?: '' }}</td><td>{{ $row['disciplinary'] ?: '' }}</td><td>{{ $row['registered'] ?: '' }}</td><td>{{ $row['called'] ?: '' }}</td><td>{{ max($row['total_absences'] - array_sum([$row['unjustified'], $row['article_65'], $row['article_66'], $row['article_67'], $row['article_68'], $row['sickness'], $row['marriage'], $row['childbirth'], $row['disciplinary'], $row['registered'], $row['called']]), 0) ?: '' }}</td>
                    <td>{{ $row['total_absences'] ?: '' }}</td><td>{{ $row['effective_days'] }}</td>
                </tr>
            @empty
                <tr><td colspan="17">Não existem funcionários activos sujeitos ao registo de ponto.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">DEPARTAMENTO DE GESTÃO DE CARREIRAS E CAPACITAÇÃO TÉCNICA, do Gabinete de Recursos Humanos,<br>no Huambo, aos {{ $month_end }} de {{ $month_name }} de {{ $year }}.<br><br>O CHEFE DE DEPARTAMENTO<br><br>________________________________________</div>
    <table class="signatures"><tr><td>Emitido por: {{ $generatedBy }}</td><td>Gerado em: {{ $generatedAt }}</td></tr></table>
</body>
</html>

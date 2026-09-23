<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        th { font-weight: bold; background: #e7e7e7; }
        .left { text-align: left; }
    </style>
</head>
<body>
    <table>
        <tr><th colspan="18">MAPA DE EFECTIVIDADE DO PESSOAL - {{ $month_name }}/{{ $year }}</th></tr>
        @if ($department_name)
            <tr><td colspan="18">Departamento/Gabinete: {{ $department_name }}</td></tr>
        @endif
        <tr>
            <th>Item</th><th>N.º do Agente</th><th>Nome Completo</th><th>Categoria</th>
            <th>Injustificadas</th><th>Artigo n.º 65</th><th>Artigo n.º 66</th><th>Artigo n.º 67</th><th>Artigo n.º 68</th>
            <th>Licença p/ Doença</th><th>Licença de Casamento</th><th>Licença p/ Parto</th><th>Licença Disciplinar</th><th>Licença Registada</th><th>Licença Chamada</th><th>Outras</th>
            <th>Total de faltas</th><th>Dias de efectividade</th>
        </tr>
        @forelse ($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row['employee_number'] }}</td>
                <td class="left">{{ $row['full_name'] }}</td>
                <td class="left">{{ $row['category'] }}</td>
                <td>{{ $row['unjustified'] }}</td><td>{{ $row['article_65'] }}</td><td>{{ $row['article_66'] }}</td><td>{{ $row['article_67'] }}</td><td>{{ $row['article_68'] }}</td>
                <td>{{ $row['sickness'] }}</td><td>{{ $row['marriage'] }}</td><td>{{ $row['childbirth'] }}</td><td>{{ $row['disciplinary'] }}</td><td>{{ $row['registered'] }}</td><td>{{ $row['called'] }}</td><td>{{ $row['other'] }}</td>
                <td>{{ $row['total_absences'] }}</td><td>{{ $row['effective_days'] }}</td>
            </tr>
        @empty
            <tr><td colspan="18">Não existem funcionários activos sujeitos ao registo de ponto.</td></tr>
        @endforelse
    </table>
</body>
</html>

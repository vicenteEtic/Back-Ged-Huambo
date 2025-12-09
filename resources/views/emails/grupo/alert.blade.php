@php
    use Illuminate\Support\Str;
    $currentHost = request()->getHost();
@endphp

<div class="container">

    {{-- Logo --}}
    @if(in_array($currentHost, ['localhost', '127.0.0.1', '172.17.100.11', '172.17.100.12','102.219.127.167']))
        <div class="logo">
            <img src="https://listapeps.keepcomply.co.ao:1130/Keepcompy.png" alt="Keepcompy">
        </div>
    @elseif(Str::contains($currentHost, 'nossa-denuncias.keepcomply.co.ao'))
        <div class="logo">
            <img src="https://www.nossaseguros.ao/assets/img/logo.png" alt="Nossa Seguros">
        </div>
    @elseif(Str::contains($currentHost, ['globalseguros.keepcomply.co.ao', '172.17.100.14', '172.17.100.14:8083']))
        <div class="logo">
           <img src="https://listapeps.keepcomply.co.ao:1130/global.png" alt="Global Seguros">
        </div>
    @elseif(Str::contains($currentHost, ['fortaleza.keepcomply.co.ao', '102.219.127.167']))
        <div class="logo">
           <img src="https://listapeps.keepcomply.co.ao:1130/fortaze.png" alt="Fortaleza Seguros">
        </div>
    @endif

    <p>Olá, <span class="highlight">{{ $user->first_name }}</span></p>
    <p>Identificámos um novo alerta associado à entidade:</p>

    <h2>{{ $alert->entity->social_denomination }}</h2>

    <div class="info-box">
        <p><strong>Tipo:</strong> {{ $alert->type }}</p>
        <p><strong>Nível:</strong> {{ $alert->level }}</p>
        <p><strong>Score:</strong> {{ $alert->score }}</p>
        <p><strong>Data:</strong> {{ \Carbon\Carbon::parse($alert->created_at)->format('d/m/Y H:i') }}</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }}
        @if(in_array($currentHost, ['localhost', '127.0.0.1', '172.17.100.11', '172.17.100.12']))
            Keepcompy — Todos os direitos reservados.
        @elseif(Str::contains($currentHost, 'nossa-denuncias.keepcomply.co.ao'))
            Nossa Seguros — Todos os direitos reservados.
        @elseif(Str::contains($currentHost, ['globalseguros.keepcomply.co.ao', '172.17.100.14', '172.17.100.12:8083']))
            Global Seguros — Todos os direitos reservados.
        @elseif(Str::contains($currentHost, ['fortaleza.keepcomply.co.ao', '102.219.127.167']))
            Fortaleza Seguros — Todos os direitos reservados.
        @else
            Sistema — Todos os direitos reservados.
        @endif
        </p>
    </div>
</div>

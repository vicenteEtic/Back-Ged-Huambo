@php
    $currentHost = request()->getHttpHost();

    $tenants = [
        'keepcomply' => [
            'hosts' => [
                'localhost',
                '127.0.0.1',
                '172.17.100.11',
                '172.17.100.12',
            ],
            'logo' => 'https://listapeps.keepcomply.co.ao/Keepcompay.png',
            'name' => 'Keepcomply'
        ],

        'nossa' => [
            'hosts' => [
                'nossa-denuncias.keepcomply.co.ao',
            ],
            'logo' => 'https://www.nossaseguros.ao/assets/img/logo.png',
            'name' => 'Nossa Seguros'
        ],

        'global' => [
            'hosts' => [
                'globalseguros.keepcomply.co.ao',
                '172.17.100.14',
                '172.17.100.14:8083',
            ],
            'logo' => 'https://listapeps.keepcomply.co.ao/global.png',
            'name' => 'Global Seguros'
        ],

        'fortaleza' => [
            'hosts' => [
                'fortaleza.keepcomply.co.ao',
                '102.219.127.167',
            ],
            'logo' => 'https://listapeps.keepcomply.co.ao/fortaze.png',
            'name' => 'Fortaleza Seguros'
        ],
    ];

    $tenant = collect($tenants)->first(function ($tenant) use ($currentHost) {
        return in_array($currentHost, $tenant['hosts']);
    });
@endphp

<div class="container">

    {{-- LOGO --}}
    @if($tenant)
        <div class="logo">
            <img src="{{ $tenant['logo'] }}" alt="{{ $tenant['name'] }}">
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

    {{-- FOOTER --}}
    <div class="footer">
        <p>
            &copy; {{ date('Y') }}
            {{ $tenant['name'] ?? 'Sistema' }} — Todos os direitos reservados.
        </p>
    </div>

</div>

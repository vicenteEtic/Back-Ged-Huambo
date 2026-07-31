<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha - {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-image: url('logo.png');
            background-size: cover;
            background-repeat: no-repeat
        }

        .auth-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .auth-logo img {
            width: 120px;
            margin-bottom: 20px;
        }

        .btn-ged {
            background-color: #0072CE;
            color: white;
        }

        .btn-ged:hover {
            background-color: #0057A0;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="auth-container">
            <div class="text-center auth-logo">
                <a href="/">
                    <img src="{{ \App\Support\FrontUrl::asset('logo_huambo-D4WV4fyp.png') }}" alt="{{ config('app.name') }}" width="200">
                </a>
            </div>

            <h4 class="text-center mb-4">Redefinir Senha</h4>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @error('new_password')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            @error('new_password_confirmation')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.resetPaword') }}">
                @csrf

                @csrf

                <input type="hidden" name="token" value="{{ request()->query('token') }}">
                <input type="hidden" name="email" value="{{ request()->query('email') }}">

                <div class="mb-3">
                    <label for="new_password" class="form-label">Nova Senha</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="new_password_confirmation" class="form-label">Confirmar Nova Senha</label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                        class="form-control" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-ged">Redefinir Senha</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS (opcional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

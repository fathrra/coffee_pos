<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CoffeePOS</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body>

<div class="login-screen">
    <div class="login-card">
        <div class="login-brand">
            <img class="login-logo" src="{{ asset('logo.png') }}" alt="Logo">
            <span>Kopi Nusantara POS</span>
        </div>
        <div class="login-sub">Masuk untuk mengakses sistem kasir</div>

        @if ($errors->any())
            <div class="login-error show">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="field">
                <label>Email / Username</label>
                <input class="input" type="text" name="email" value="{{ old('email') }}" placeholder="you@email.com" autocomplete="username" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input class="input" type="password" name="password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:11px;font-size:14px;">Masuk</button>
        </form>

        <div class="login-hint">
            <strong>Demo Akun:</strong><br>
            Admin: admin&#64;coffee-pos.test / password<br>
            Kasir: kasir&#64;coffee-pos.test / password
        </div>
    </div>
</div>

</body>
</html>

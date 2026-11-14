<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · Monitor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<div class="auth">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-mark">M</div>
            <div>
                <h1>Masuk ke Monitor</h1>
                <p>Pantau server, website, dan backup Anda.</p>
            </div>
        </div>

        <form class="auth-form" method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@domain.com" autocomplete="username" autofocus required>
                @error('email')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input class="input" id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required>
                @error('password')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <label class="check"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
            <button class="btn btn-primary" type="submit">Masuk</button>
        </form>
    </div>
</div>
</body>
</html>

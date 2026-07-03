<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-zinc-100 px-4 text-zinc-950">
    <form method="POST" action="{{ route('login') }}" class="w-full max-w-sm rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        <h1 class="text-xl font-semibold">Control contable interno</h1>
        <p class="mt-1 text-sm text-zinc-500">Ingresa para operar ingresos, egresos y reportes.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <label class="mt-5 block text-sm font-medium">Correo</label>
        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2" required autofocus>

        <label class="mt-4 block text-sm font-medium">Contraseña</label>
        <input name="password" type="password" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2" required>

        <label class="mt-4 flex items-center gap-2 text-sm">
            <input name="remember" type="checkbox" value="1" class="rounded border-zinc-300">
            Recordarme
        </label>

        <button class="mt-5 w-full rounded-md bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-800">Ingresar</button>
    </form>
</body>
</html>

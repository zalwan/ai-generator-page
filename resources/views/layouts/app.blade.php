<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI Sales Page Generator')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>body{font-family:'Inter',ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold">
                <span class="grid h-8 w-8 place-content-center rounded-lg bg-indigo-600 text-white">AI</span>
                <span>Sales Page Generator</span>
            </a>
            <nav class="flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-slate-900">Dashboard</a>
                    <a href="{{ route('sales-pages.index') }}" class="text-slate-600 hover:text-slate-900">Riwayat</a>
                    <a href="{{ route('sales-pages.create') }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700">+ Halaman Baru</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-slate-500 hover:text-rose-600">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700">Daftar</a>
                @endauth
            </nav>
        </div>
    </header>

    @if (session('status'))
        <div class="mx-auto max-w-6xl px-6 pt-4">
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        </div>
    @endif

    <main class="mx-auto max-w-6xl px-6 py-8">
        @yield('content')
    </main>

    <footer class="mt-16 border-t bg-white">
        <div class="mx-auto max-w-6xl px-6 py-4 text-xs text-slate-500">
            MCP-lite context engine — generations stay consistent across your account.
        </div>
    </footer>
</body>
</html>

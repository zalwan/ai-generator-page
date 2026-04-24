@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<div class="mx-auto max-w-md rounded-lg border bg-white p-8 shadow-sm">
    <h1 class="text-2xl font-semibold">Masuk</h1>
    <p class="mt-1 text-sm text-slate-500">Lanjutkan ke dashboard Anda.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Password</label>
            <input type="password" name="password" required
                class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="rounded border-slate-300"> Ingat saya
        </label>
        <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Masuk</button>
    </form>
    <p class="mt-4 text-center text-sm text-slate-500">
        Belum punya akun? <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Daftar</a>
    </p>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Daftar')

@section('content')
<div class="mx-auto max-w-md rounded-lg border bg-white p-8 shadow-sm">
    <h1 class="text-2xl font-semibold">Buat Akun</h1>
    <p class="mt-1 text-sm text-slate-500">Mulai membangun halaman penjualan Anda.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium">Nama</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Password</label>
            <input type="password" name="password" required
                class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" required
                class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Daftar</button>
    </form>
    <p class="mt-4 text-center text-sm text-slate-500">
        Sudah punya akun? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Masuk</a>
    </p>
</div>
@endsection

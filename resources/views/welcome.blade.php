@extends('layouts.app')
@section('title', 'AI Sales Page Generator')

@section('content')
<section class="py-16 text-center">
    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Halaman penjualan yang konsisten, bukan satu kali jadi.</h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
        Setiap generasi belajar dari riwayat Anda. Tone, audiens, dan positioning tetap selaras —
        dengan engine MCP-lite yang menyuntikkan konteks ke prompt secara otomatis.
    </p>
    <div class="mt-8 flex justify-center gap-3">
        @auth
            <a href="{{ route('sales-pages.create') }}" class="rounded-md bg-indigo-600 px-5 py-2.5 text-white shadow-sm hover:bg-indigo-700">Buat Halaman</a>
            <a href="{{ route('dashboard') }}" class="rounded-md border border-slate-300 px-5 py-2.5 hover:bg-white">Dashboard</a>
        @else
            <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-5 py-2.5 text-white shadow-sm hover:bg-indigo-700">Mulai Gratis</a>
            <a href="{{ route('login') }}" class="rounded-md border border-slate-300 px-5 py-2.5 hover:bg-white">Masuk</a>
        @endauth
    </div>
</section>

<section class="grid gap-6 md:grid-cols-3">
    <div class="rounded-lg border bg-white p-6">
        <h3 class="font-semibold">Konteks Otomatis</h3>
        <p class="mt-2 text-sm text-slate-600">5 generasi terakhir disuntikkan sebagai konteks — pola tone & audiens dipertahankan.</p>
    </div>
    <div class="rounded-lg border bg-white p-6">
        <h3 class="font-semibold">Anti-Repetisi</h3>
        <p class="mt-2 text-sm text-slate-600">Daftar headline & CTA sebelumnya dikirim sebagai larangan agar copy selalu segar.</p>
    </div>
    <div class="rounded-lg border bg-white p-6">
        <h3 class="font-semibold">Token Budget Cerdas</h3>
        <p class="mt-2 text-sm text-slate-600">Ringkasan dipotong otomatis di ~2.4k char agar prompt tetap ringan dan murah.</p>
    </div>
</section>
@endsection

@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="grid gap-6 md:grid-cols-3">
    <div class="rounded-lg border bg-white p-5">
        <p class="text-sm text-slate-500">Total Halaman</p>
        <p class="mt-1 text-3xl font-semibold">{{ $totalPages }}</p>
    </div>
    <div class="rounded-lg border bg-white p-5">
        <p class="text-sm text-slate-500">Konteks Aktif</p>
        <p class="mt-1 text-3xl font-semibold">{{ $context['used'] }} / 5</p>
        <p class="text-xs text-slate-500">generasi terakhir disuntikkan</p>
    </div>
    <div class="rounded-lg border bg-white p-5">
        <p class="text-sm text-slate-500">Tone Dominan</p>
        <p class="mt-1 text-3xl font-semibold">{{ $context['patterns']['dominant_tone'] ?? '—' }}</p>
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-lg border bg-white p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold">Halaman Terakhir</h2>
            <a href="{{ route('sales-pages.create') }}" class="text-sm text-indigo-600 hover:underline">+ Baru</a>
        </div>
        @forelse($recent as $p)
            <a href="{{ route('sales-pages.show', $p) }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">
                <p class="font-medium">{{ $p->product_name }}</p>
                <p class="text-xs text-slate-500">{{ $p->created_at->diffForHumans() }} · audiens: {{ $p->input_data['target_audience'] ?? '—' }}</p>
            </a>
        @empty
            <p class="text-sm text-slate-500">Belum ada halaman. <a href="{{ route('sales-pages.create') }}" class="text-indigo-600 hover:underline">Buat sekarang →</a></p>
        @endforelse
    </div>

    <div class="rounded-lg border bg-white p-6">
        <h2 class="mb-3 font-semibold">Konteks yang akan disuntikkan</h2>
        <p class="text-xs text-slate-500">Inilah yang dilihat LLM sebelum menulis halaman berikutnya:</p>
        <pre class="mt-3 max-h-80 overflow-auto rounded-md bg-slate-900 p-4 text-xs leading-relaxed text-slate-100 whitespace-pre-wrap">{{ $context['summary'] }}</pre>
    </div>
</div>
@endsection

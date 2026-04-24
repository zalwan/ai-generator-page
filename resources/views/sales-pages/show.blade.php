@extends('layouts.app')
@section('title', $page->product_name)

@section('content')
@php($g = $page->generated_content)

<div class="mb-4 flex items-center justify-between">
    <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">{{ $page->created_at->format('d M Y H:i') }}</p>
        <h1 class="text-2xl font-semibold">{{ $page->product_name }}</h1>
    </div>
    <div class="flex gap-2 text-sm">
        <a href="{{ route('sales-pages.preview', $page) }}" target="_blank" class="rounded-md border px-3 py-1.5 hover:bg-slate-50">↗ Preview Penuh</a>
        <a href="{{ route('sales-pages.create') }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700">+ Generate Lagi</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-lg border bg-white p-8">
        <p class="text-sm font-medium text-indigo-600">{{ $g['subheadline'] ?? '' }}</p>
        <h2 class="mt-2 text-3xl font-bold leading-tight">{{ $g['headline'] ?? '' }}</h2>
        <p class="mt-4 text-slate-700">{{ $g['description'] ?? '' }}</p>

        @if(!empty($g['benefits']))
            <h3 class="mt-8 font-semibold">Manfaat</h3>
            <ul class="mt-2 space-y-1 text-slate-700">
                @foreach($g['benefits'] as $b)
                    <li class="flex gap-2"><span class="text-emerald-500">✓</span>{{ $b }}</li>
                @endforeach
            </ul>
        @endif

        @if(!empty($g['features']))
            <h3 class="mt-6 font-semibold">Fitur</h3>
            <ul class="mt-2 space-y-1 text-slate-700">
                @foreach($g['features'] as $f)
                    <li class="flex gap-2"><span class="text-indigo-500">•</span>{{ $f }}</li>
                @endforeach
            </ul>
        @endif

        @if(!empty($g['social_proof']))
            <blockquote class="mt-6 rounded-md border-l-4 border-indigo-400 bg-slate-50 p-4 text-slate-700">
                {{ $g['social_proof'] }}
            </blockquote>
        @endif

        <div class="mt-8 flex flex-wrap items-center gap-3 rounded-md bg-indigo-50 p-4">
            <span class="text-2xl font-bold text-indigo-700">{{ $g['price'] ?? '' }}</span>
            <a href="#" class="ml-auto rounded-md bg-indigo-600 px-5 py-2.5 text-white shadow-sm hover:bg-indigo-700">{{ $g['call_to_action'] ?? '' }}</a>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="rounded-lg border bg-white p-5">
            <h3 class="text-sm font-semibold text-slate-700">Input</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="text-slate-500">Audiens</dt><dd>{{ $page->input_data['target_audience'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Tone</dt><dd>{{ $page->input_data['tone'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">USP</dt><dd>{{ $page->input_data['usp'] ?? '—' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-lg border bg-white p-5">
            <h3 class="text-sm font-semibold text-slate-700">Ringkasan untuk Konteks Berikutnya</h3>
            <p class="mt-2 break-words text-xs text-slate-600">{{ $page->context_summary }}</p>
        </div>
        <details class="rounded-lg border bg-white p-5">
            <summary class="cursor-pointer text-sm font-semibold text-slate-700">Raw JSON</summary>
            <pre class="mt-2 max-h-72 overflow-auto rounded bg-slate-900 p-3 text-xs text-slate-100">{{ json_encode($g, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </aside>
</div>
@endsection

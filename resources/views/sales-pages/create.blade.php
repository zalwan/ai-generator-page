@extends('layouts.app')
@section('title', 'Buat Halaman Penjualan')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-lg border bg-white p-6">
        <h1 class="text-2xl font-semibold">Buat Halaman Penjualan</h1>
        <p class="mt-1 text-sm text-slate-500">Lengkapi data produk. Konteks dari {{ $context['used'] }} generasi terakhir akan disuntikkan otomatis.</p>

        @if($errors->any())
            <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sales-pages.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium">Nama Produk</label>
                <input name="product_name" value="{{ old('product_name') }}" required
                    class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium">Deskripsi Produk</label>
                <textarea name="description" rows="3" required
                    class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium">Fitur Utama <span class="text-xs text-slate-400">(satu per baris atau pisah dengan koma)</span></label>
                <textarea name="features" rows="3"
                    class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm">{{ old('features') }}</textarea>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium">Target Audiens</label>
                    <input name="target_audience" value="{{ old('target_audience', $context['patterns']['dominant_audience'] ?? '') }}" required
                        class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">Harga</label>
                    <input name="price" value="{{ old('price') }}" required placeholder="Rp 199.000"
                        class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium">USP (Keunikan)</label>
                <input name="usp" value="{{ old('usp') }}"
                    class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium">Tone</label>
                <input name="tone" value="{{ old('tone', $context['patterns']['dominant_tone'] ?? 'profesional & meyakinkan') }}"
                    class="mt-1 w-full rounded-md border-slate-300 px-3 py-2 shadow-sm">
            </div>

            <button id="submit-btn" class="w-full rounded-md bg-indigo-600 px-4 py-2.5 text-white hover:bg-indigo-700">
                ✨ Generate dengan Konteks
            </button>
            <script>
                document.querySelector('form').addEventListener('submit', (e) => {
                    const btn = document.getElementById('submit-btn');
                    btn.disabled = true;
                    btn.classList.add('opacity-60','cursor-wait');
                    btn.textContent = '⏳ Sedang menghasilkan…';
                });
            </script>
        </form>
    </div>

    <aside class="rounded-lg border bg-white p-6">
        <h2 class="font-semibold">Konteks Aktif</h2>
        <p class="mt-1 text-xs text-slate-500">{{ $context['used'] }} generasi terakhir akan dipakai sebagai panduan konsistensi.</p>

        @if(!empty($context['patterns']))
            <dl class="mt-4 space-y-2 text-sm">
                @if($context['patterns']['dominant_tone'] ?? null)
                    <div><dt class="text-slate-500">Tone</dt><dd class="font-medium">{{ $context['patterns']['dominant_tone'] }}</dd></div>
                @endif
                @if($context['patterns']['dominant_audience'] ?? null)
                    <div><dt class="text-slate-500">Audiens</dt><dd class="font-medium">{{ $context['patterns']['dominant_audience'] }}</dd></div>
                @endif
            </dl>
        @endif

        <details class="mt-4">
            <summary class="cursor-pointer text-sm text-indigo-600 hover:underline">Lihat ringkasan konteks</summary>
            <pre class="mt-2 max-h-96 overflow-auto rounded-md bg-slate-900 p-3 text-xs text-slate-100 whitespace-pre-wrap">{{ $context['summary'] }}</pre>
        </details>
    </aside>
</div>
@endsection

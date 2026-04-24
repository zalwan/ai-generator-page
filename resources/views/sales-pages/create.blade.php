@extends('layouts.app')
@section('title', 'Buat Halaman Penjualan')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <nav class="text-xs text-slate-500"><a href="{{ route('sales-pages.index') }}" class="hover:underline">Riwayat</a> <span class="mx-1">/</span> <span class="text-slate-700">Baru</span></nav>
        <h1 class="mt-1 text-2xl font-bold tracking-tight">Buat Halaman Penjualan</h1>
    </div>
    <div class="hidden items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 sm:flex">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
        {{ $context['used'] }} generasi dipakai sebagai konteks
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3"
     x-data="{
        submitting: false,
        fields: {
            product_name:    @js(old('product_name','')),
            description:     @js(old('description','')),
            features:        @js(old('features','')),
            target_audience: @js(old('target_audience', $context['patterns']['dominant_audience'] ?? '')),
            price:           @js(old('price','')),
            usp:             @js(old('usp','')),
            tone:            @js(old('tone', $context['patterns']['dominant_tone'] ?? 'profesional & meyakinkan')),
        },
        suggest: { open: null, loading: null, items: {}, source: {}, error: null },
        counter(v, max) { return (v?.length || 0) + ' / ' + max },
        async toggleSuggest(field) {
            if (this.suggest.open === field) { this.suggest.open = null; return }
            this.suggest.open = field
            this.suggest.error = null
            if (!this.suggest.items[field]) await this.loadSuggest(field)
        },
        closeSuggest() { this.suggest.open = null },
        async refreshSuggest(field) { delete this.suggest.items[field]; await this.loadSuggest(field) },
        async loadSuggest(field) {
            this.suggest.loading = field
            try {
                const csrf = document.querySelector('meta[name=csrf-token]').content
                const res = await fetch(@js(route('sales-pages.suggest')), {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
                    body: JSON.stringify({
                        field,
                        form: {
                            product_name: this.fields.product_name,
                            description:  this.fields.description,
                            target_audience: this.fields.target_audience,
                            usp:  this.fields.usp,
                            tone: this.fields.tone,
                        }
                    })
                })
                if (!res.ok) throw new Error('http ' + res.status)
                const data = await res.json()
                this.suggest.items[field] = data.suggestions || []
                this.suggest.source[field] = data.source || 'llm'
            } catch (e) {
                this.suggest.error = 'Gagal mengambil saran. Coba lagi.'
                this.suggest.items[field] = []
            } finally {
                this.suggest.loading = null
            }
        },
        pickSuggest(field, text) {
            if (field === 'features') {
                const prev = (this.fields.features || '').trim()
                this.fields.features = prev ? prev + '\n' + text : text
                return
            }
            this.fields[field] = text
            this.suggest.open = null
        },
     }">

    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('sales-pages.store') }}" @submit="submitting = true" class="space-y-6">
            @csrf

            {{-- Reusable suggest popover partial embedded inline below each input --}}
            @php
                $suggest = <<<'BLADE'
                    <div x-show="suggest.open === '__FIELD__'" x-transition x-cloak
                         @click.outside="suggest.open === '__FIELD__' && closeSuggest()"
                         class="absolute left-0 right-0 top-full z-20 mt-2 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-xs font-semibold text-slate-700">
                                <span x-show="suggest.loading === '__FIELD__'">✨ Mencari ide…</span>
                                <span x-show="suggest.loading !== '__FIELD__'">✨ Saran AI</span>
                            </p>
                            <div class="flex items-center gap-2">
                                <span x-show="suggest.source['__FIELD__'] === 'fallback'" x-cloak
                                      class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700">demo</span>
                                <button type="button" @click="refreshSuggest('__FIELD__')" x-show="suggest.loading !== '__FIELD__'"
                                        class="text-xs text-slate-500 hover:text-slate-900" title="Refresh">↻</button>
                                <button type="button" @click="closeSuggest()" class="text-slate-400 hover:text-slate-600">×</button>
                            </div>
                        </div>

                        <div x-show="suggest.loading === '__FIELD__'" class="flex items-center gap-2 px-2 py-4 text-sm text-slate-500">
                            <svg class="h-4 w-4 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                            Memproses dengan konteks Anda…
                        </div>

                        <template x-if="suggest.loading !== '__FIELD__' && suggest.error">
                            <div class="rounded-md bg-rose-50 p-2 text-xs text-rose-700" x-text="suggest.error"></div>
                        </template>

                        <template x-if="suggest.loading !== '__FIELD__' && !suggest.error">
                            <div class="flex flex-col gap-1.5">
                                <template x-for="(s, i) in (suggest.items['__FIELD__'] || [])" :key="i">
                                    <button type="button" @click="pickSuggest('__FIELD__', s)"
                                            class="group flex items-start gap-2 rounded-lg px-2.5 py-2 text-left text-sm text-slate-700 transition hover:bg-brand-50">
                                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-content-center rounded-md bg-slate-100 text-[10px] text-slate-500 group-hover:bg-brand-100 group-hover:text-brand-700" x-text="i+1"></span>
                                        <span class="flex-1" x-text="s"></span>
                                        <svg class="h-4 w-4 opacity-0 transition group-hover:opacity-100 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                                    </button>
                                </template>
                                <template x-if="(suggest.items['__FIELD__'] || []).length === 0">
                                    <p class="px-2 py-3 text-center text-xs text-slate-500">Belum ada saran.</p>
                                </template>
                            </div>
                        </template>

                        <p class="mt-2 border-t border-slate-100 pt-2 text-[11px] text-slate-400">
                            __HINT__
                        </p>
                    </div>
                BLADE;
                $renderSuggest = function(string $field, string $hint) use ($suggest) {
                    return str_replace(['__FIELD__','__HINT__'], [$field, $hint], $suggest);
                };
                $suggestButton = fn(string $field) => <<<HTML
                    <button type="button" @click="toggleSuggest('$field')"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700"
                        :class="suggest.open === '$field' && 'border-brand-300 bg-brand-50 text-brand-700'">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l1.8 4.8L18 8.5l-4.2 1.7L12 15l-1.8-4.8L6 8.5l4.2-1.7L12 2z"/>
                        </svg>
                        <span x-show="suggest.loading !== '$field'">Saran</span>
                        <span x-show="suggest.loading === '$field'" x-cloak>…</span>
                    </button>
                HTML;
            @endphp

            {{-- SECTION 1 --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
                <div class="mb-5 flex items-center gap-3">
                    <span class="grid h-8 w-8 place-content-center rounded-lg bg-brand-50 text-sm font-semibold text-brand-700">1</span>
                    <div>
                        <h2 class="font-semibold">Tentang Produk</h2>
                        <p class="text-xs text-slate-500">Informasi dasar yang akan dikonversi jadi copy.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <div class="flex items-baseline justify-between">
                            <label class="block text-sm font-medium text-slate-700">Nama Produk</label>
                            <span class="text-xs text-slate-400" x-text="counter(fields.product_name, 255)"></span>
                        </div>
                        <input name="product_name" x-model="fields.product_name" maxlength="255" required placeholder="MarketBoost Pro"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-700">Deskripsi Produk</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400" x-text="counter(fields.description, 2000)"></span>
                                {!! $suggestButton('description') !!}
                            </div>
                        </div>
                        <textarea name="description" x-model="fields.description" maxlength="2000" rows="4" required
                            placeholder="Jelaskan produk Anda, masalah yang diselesaikan, untuk siapa…"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        {!! $renderSuggest('description', 'Klik salah satu untuk mengganti deskripsi.') !!}
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-700">Fitur Utama</label>
                            {!! $suggestButton('features') !!}
                        </div>
                        <textarea name="features" x-model="fields.features" rows="3"
                            placeholder="Satu fitur per baris, atau pisah dengan koma"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        {!! $renderSuggest('features', 'Klik saran untuk menambahkan sebagai baris baru.') !!}
                    </div>
                </div>
            </section>

            {{-- SECTION 2 --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
                <div class="mb-5 flex items-center gap-3">
                    <span class="grid h-8 w-8 place-content-center rounded-lg bg-brand-50 text-sm font-semibold text-brand-700">2</span>
                    <div>
                        <h2 class="font-semibold">Audiens & Harga</h2>
                        <p class="text-xs text-slate-500">Siapa yang Anda sasar dan berapa biayanya.</p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-700">Target Audiens</label>
                            {!! $suggestButton('target_audience') !!}
                        </div>
                        <input name="target_audience" x-model="fields.target_audience" required placeholder="pemilik UMKM"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        {!! $renderSuggest('target_audience', 'Klik salah satu untuk memilih persona.') !!}
                    </div>
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-700">Harga</label>
                            {!! $suggestButton('price') !!}
                        </div>
                        <input name="price" x-model="fields.price" required placeholder="Rp 299.000 / bulan"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        {!! $renderSuggest('price', 'Klik salah satu untuk memilih format harga.') !!}
                    </div>
                </div>
            </section>

            {{-- SECTION 3 --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
                <div class="mb-5 flex items-center gap-3">
                    <span class="grid h-8 w-8 place-content-center rounded-lg bg-brand-50 text-sm font-semibold text-brand-700">3</span>
                    <div>
                        <h2 class="font-semibold">Positioning & Tone</h2>
                        <p class="text-xs text-slate-500">Bagaimana Anda mau terdengar di mata pelanggan.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-slate-700">USP <span class="text-xs font-normal text-slate-400">(Keunikan)</span></label>
                            {!! $suggestButton('usp') !!}
                        </div>
                        <input name="usp" x-model="fields.usp" placeholder="Apa yang membuat produk ini beda?"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        {!! $renderSuggest('usp', 'Klik salah satu untuk memilih positioning.') !!}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tone</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach(['profesional & meyakinkan','santai & friendly','urgent & energik','edukatif','premium & elegan'] as $tone)
                                <button type="button" @click="fields.tone = @js($tone)"
                                        :class="fields.tone === @js($tone) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                                        class="rounded-full border px-3 py-1 text-sm transition">{{ $tone }}</button>
                            @endforeach
                        </div>
                        <input type="text" name="tone" x-model="fields.tone"
                            class="mt-3 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Atau ketik tone custom…">
                    </div>
                </div>
            </section>

            {{-- SUBMIT --}}
            <div class="sticky bottom-4 z-10">
                <button type="submit" :disabled="submitting"
                        class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 px-4 py-3.5 font-medium text-white shadow-lg transition hover:from-brand-700 hover:to-brand-900 disabled:cursor-wait disabled:opacity-70">
                    <svg x-show="!submitting" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.5 6.5L21 11l-6.5 2.5L12 20l-2.5-6.5L3 11l6.5-2.5L12 2z"/></svg>
                    <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                    <span x-text="submitting ? 'Sedang menghasilkan…' : 'Generate dengan Konteks'"></span>
                </button>
            </div>
        </form>
    </div>

    {{-- CONTEXT PANEL --}}
    <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Konteks Aktif</h3>
                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">{{ $context['used'] }}/5</span>
            </div>
            @if(!empty($context['patterns']))
                <dl class="mt-4 space-y-3 text-sm">
                    @if($context['patterns']['dominant_tone'] ?? null)
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 grid h-6 w-6 shrink-0 place-content-center rounded-md bg-emerald-50 text-emerald-600">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l18-8-4 18-4-8-4-1z"/></svg>
                            </span>
                            <div><div class="text-xs text-slate-500">Tone</div><div class="font-medium">{{ $context['patterns']['dominant_tone'] }}</div></div>
                        </div>
                    @endif
                    @if($context['patterns']['dominant_audience'] ?? null)
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 grid h-6 w-6 shrink-0 place-content-center rounded-md bg-amber-50 text-amber-600">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            </span>
                            <div><div class="text-xs text-slate-500">Audiens</div><div class="font-medium">{{ $context['patterns']['dominant_audience'] }}</div></div>
                        </div>
                    @endif
                </dl>
            @else
                <p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Ini generasi pertama — belum ada pola. Halaman berikutnya akan mulai mengikuti tone & audiens Anda.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50 to-fuchsia-50 p-5">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.8 4.8L18 8.5l-4.2 1.7L12 15l-1.8-4.8L6 8.5l4.2-1.7L12 2z"/></svg>
                <h3 class="font-semibold text-brand-900">Tips</h3>
            </div>
            <p class="mt-2 text-sm text-slate-700">Blank-page syndrome? Klik tombol <strong>Saran</strong> di setiap input — AI akan mengusulkan ide berdasarkan konteks yang sudah Anda isi.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft" x-data="{ open:false }">
            <button type="button" @click="open=!open" class="flex w-full items-center justify-between">
                <div class="text-left">
                    <h3 class="font-semibold">Lihat prompt konteks</h3>
                    <p class="text-xs text-slate-500">Yang akan disuntikkan ke LLM</p>
                </div>
                <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div x-show="open" x-transition x-cloak>
                <pre class="mt-4 max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-slate-100">{{ $context['summary'] }}</pre>
            </div>
        </div>
    </aside>
</div>
@endsection

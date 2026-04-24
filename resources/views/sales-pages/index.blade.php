@extends('layouts.app')
@section('title', 'Riwayat Halaman')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold">Riwayat Halaman</h1>
    <a href="{{ route('sales-pages.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">+ Halaman Baru</a>
</div>

@if($pages->isEmpty())
    <div class="rounded-lg border border-dashed bg-white p-12 text-center text-slate-500">
        Belum ada halaman.
    </div>
@else
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach($pages as $p)
            <div class="flex flex-col rounded-lg border bg-white p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $p->created_at->diffForHumans() }}</p>
                <h3 class="mt-1 font-semibold">{{ $p->product_name }}</h3>
                <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $p->generated_content['headline'] ?? '' }}</p>
                <div class="mt-4 flex gap-2 text-sm">
                    <a href="{{ route('sales-pages.show', $p) }}" class="rounded-md border px-3 py-1.5 hover:bg-slate-50">Detail</a>
                    <a href="{{ route('sales-pages.preview', $p) }}" target="_blank" class="rounded-md border px-3 py-1.5 hover:bg-slate-50">Preview</a>
                    <form method="POST" action="{{ route('sales-pages.destroy', $p) }}" onsubmit="return confirm('Hapus halaman ini?')" class="ml-auto">
                        @csrf @method('DELETE')
                        <button class="rounded-md border border-rose-200 px-3 py-1.5 text-rose-600 hover:bg-rose-50">Hapus</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $pages->links() }}</div>
@endif
@endsection

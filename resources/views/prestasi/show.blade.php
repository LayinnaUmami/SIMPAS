@extends('layouts.app')
@section('title', 'Detail Prestasi')
@section('subtitle', $prestasi->nama_prestasi)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @php
        $tingkatBadge = in_array($prestasi->tingkat, ['Pondok', 'Desa'])
            ? 'badge-netral'
            : (in_array($prestasi->tingkat, ['Kecamatan', 'Kabupaten'])
                ? 'bg-amber-50 text-amber-700 border-amber-200'
                : 'bg-emerald-50 text-emerald-700 border-emerald-200');
    @endphp

    <div class="card card-body animate-fade-up" data-reveal>
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <div class="grid place-items-center size-16 rounded-3xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 shrink-0 animate-pop">
                <i data-lucide="trophy" class="size-8"></i>
            </div>

            <div class="min-w-0 flex-1">
                <span class="badge {{ $tingkatBadge }} mb-2">{{ $prestasi->tingkat }}</span>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    {{ $prestasi->nama_prestasi }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $prestasi->tanggal?->format('d M Y') }}
                    @if($prestasi->penyelenggara) · {{ $prestasi->penyelenggara }} @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                <a href="{{ route('prestasi.index') }}" class="btn-secondary">
                    <i data-lucide="arrow-left" class="size-4"></i> Kembali
                </a>

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('prestasi.edit', $prestasi) }}" class="btn-primary">
                        <i data-lucide="pencil" class="size-4"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('prestasi.destroy', $prestasi) }}"
                          data-confirm="Yakin menghapus data ini?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <i data-lucide="trash-2" class="size-4"></i> Hapus
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Info --}}
        <div class="grid gap-4 sm:grid-cols-2 mt-6">
            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Santri</p>
                <p class="mt-1 font-bold text-slate-900">{{ $prestasi->santri?->nama ?? '-' }}</p>
                <p class="text-xs text-slate-500 mt-0.5">NIS {{ $prestasi->santri?->nis ?? '-' }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tanggal</p>
                <p class="mt-1 font-bold text-slate-900">{{ $prestasi->tanggal?->format('d M Y') ?? '-' }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Jenis</p>
                <p class="mt-1.5"><span class="badge badge-netral">{{ $prestasi->jenis }}</span></p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Penyelenggara</p>
                <p class="mt-1 font-bold text-slate-900">{{ $prestasi->penyelenggara ?: '-' }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:col-span-2">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Keterangan</p>
                <p class="mt-1 text-sm text-slate-700 leading-relaxed">{{ $prestasi->keterangan ?: '-' }}</p>
            </div>

            <div class="rounded-2xl border border-brand-200 bg-brand-50/60 p-4 sm:col-span-2 flex items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700">Poin Prestasi</p>
                    <p class="text-xs text-slate-500 mt-0.5">Ditambahkan ke perolehan poin santri</p>
                </div>
                <span class="badge bg-emerald-50 text-emerald-700 border-emerald-200 !px-4 !py-1.5 text-sm font-extrabold">
                    +{{ $prestasi->poin }} Poin
                </span>
            </div>
        </div>

        {{-- Bukti --}}
        @if($prestasi->bukti_url)
            @php($ext = strtolower(pathinfo(parse_url($prestasi->bukti_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)))
            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4" data-reveal>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Bukti</p>

                @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg']))
                    <img src="{{ $prestasi->bukti_url }}" alt="Bukti prestasi"
                         class="w-full max-h-[420px] object-contain rounded-2xl bg-white border border-slate-200">
                @else
                    <a href="{{ $prestasi->bukti_url }}" target="_blank" rel="noopener" class="btn-secondary">
                        <i data-lucide="image" class="size-4"></i> Lihat Bukti
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

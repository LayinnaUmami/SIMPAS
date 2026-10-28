@extends('layouts.app')
@section('title', 'Detail Pelanggaran')
@section('subtitle', $pelanggaran->jenis)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @php
        $tingkatBadge = match ($pelanggaran->tingkat) {
            'Ringan' => 'bg-amber-50 text-amber-700 border-amber-200',
            'Sedang' => 'bg-orange-50 text-orange-700 border-orange-200',
            'Berat' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'badge-netral',
        };
    @endphp

    @if($pelanggaran->is_auto)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 animate-fade-up">
            <i data-lucide="zap" class="size-4 mt-0.5 shrink-0"></i>
            <p>Data ini dibuat otomatis oleh sistem dari rekap absensi.</p>
        </div>
    @endif

    <div class="card card-body animate-fade-up" data-reveal>
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <div class="grid place-items-center size-16 rounded-3xl bg-rose-500 text-white shadow-lg shadow-rose-500/30 shrink-0 animate-pop">
                <i data-lucide="triangle-alert" class="size-8"></i>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5 mb-2">
                    <span class="badge {{ $tingkatBadge }}">{{ $pelanggaran->tingkat }}</span>
                    @if($pelanggaran->is_auto)
                        <span class="badge badge-netral">
                            <i data-lucide="zap" class="size-3"></i> Otomatis
                        </span>
                    @endif
                </div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    {{ $pelanggaran->jenis }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">{{ $pelanggaran->tanggal?->format('d M Y') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                <a href="{{ route('pelanggaran.index') }}" class="btn-secondary">
                    <i data-lucide="arrow-left" class="size-4"></i> Kembali
                </a>

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('pelanggaran.edit', $pelanggaran) }}" class="btn-primary">
                        <i data-lucide="pencil" class="size-4"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('pelanggaran.destroy', $pelanggaran) }}"
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
                <p class="mt-1 font-bold text-slate-900">{{ $pelanggaran->santri?->nama ?? '-' }}</p>
                <p class="text-xs text-slate-500 mt-0.5">NIS {{ $pelanggaran->santri?->nis ?? '-' }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tanggal</p>
                <p class="mt-1 font-bold text-slate-900">{{ $pelanggaran->tanggal?->format('d M Y') ?? '-' }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Jenis</p>
                <p class="mt-1 font-bold text-slate-900">{{ $pelanggaran->jenis }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tingkat</p>
                <p class="mt-1.5"><span class="badge {{ $tingkatBadge }}">{{ $pelanggaran->tingkat }}</span></p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:col-span-2">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Keterangan</p>
                <p class="mt-1 text-sm text-slate-700 leading-relaxed">{{ $pelanggaran->keterangan ?: '-' }}</p>
            </div>

            <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-4 sm:col-span-2 flex items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Poin Pelanggaran</p>
                    <p class="text-xs text-slate-500 mt-0.5">Dikurangkan dari perolehan poin santri</p>
                </div>
                <span class="badge bg-rose-50 text-rose-700 border-rose-200 !px-4 !py-1.5 text-sm font-extrabold">
                    {{ $pelanggaran->poin }} Poin
                </span>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Dashboard Wali')
@section('subtitle', 'Ringkasan perkembangan santri')

@section('content')
<div class="space-y-6">

    {{-- ================= GREETING ================= --}}
    <div class="relative overflow-hidden rounded-3xl border border-brand-200 bg-brand-600 text-white p-6 sm:p-8 mb-6 animate-fade-up">

        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="relative grid place-items-center size-16 rounded-2xl bg-white/10 backdrop-blur shadow-2xl shadow-brand-900/30 animate-pop ring-4 ring-brand-300/20 border border-brand-300/30">
                    @if($santri->foto_url)
                        <img src="{{ $santri->foto_url }}" alt="Foto {{ $santri->nama }}" class="size-full object-cover rounded-2xl">
                    @else
                        <div class="grid place-items-center size-full rounded-2xl bg-brand-500 text-white font-extrabold text-2xl">
                            {{ $santri->initials }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0">
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $santri->nama }}</h2>
                    <p class="text-brand-200 mt-1">NIS {{ $santri->nis }} · {{ $santri->kamar }}</p>
                </div>
            </div>

            @if($rankSaya)
                <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/20 px-5 py-4 text-center animate-pop min-w-[150px]">
                    <p class="text-[11px] uppercase tracking-wider text-brand-200 font-bold">Ranking Saya</p>
                    <p class="text-3xl font-extrabold mt-1">#{{ $rankSaya }}</p>
                    <p class="text-xs text-brand-100/80 mt-1">
                        <span data-count="{{ $poinSaya }}">0</span> poin
                    </p>
                </div>
            @endif
        </div>
    </div>

    <section class="space-y-3">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900">Data Anak</h2>
            <p class="text-sm text-slate-500">Pilih anak untuk membuka profil dan riwayatnya.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($santris as $anak)
                <a href="{{ route('santri.show', $anak) }}"
                   class="card-hover flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition-all">
                    <span class="grid place-items-center size-11 rounded-xl bg-brand-100 text-brand-700 font-bold">
                        {{ $anak->initials }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-bold text-slate-800">{{ $anak->nama }}</span>
                        <span class="block text-xs text-slate-500">NIS {{ $anak->nis }} · {{ $anak->kamar ?: 'Kamar belum ditentukan' }}</span>
                    </span>
                    <i data-lucide="chevron-right" class="size-4 text-slate-400"></i>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ================= STATISTIK HARI INI ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 mb-6 stagger">
        @foreach($aktivitasHariIni as $key => $act)
            <a href="{{ route('wali.laporan', ['jenis' => $key]) }}"
               class="card-hover rounded-2xl border border-brand-200 bg-white p-4 sm:p-5 transition-all"
               style="min-height: 110px;">
                <div class="flex items-start justify-between gap-3 h-full">
                    <div class="flex flex-col justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700">{{ $act['label'] }}</p>
                        <div>
                            <p class="text-3xl font-extrabold text-brand-900 tracking-tight" data-count="{{ $act['total'] }}">0</p>
                            <p class="text-[11px] text-brand-600 mt-1">
                                {{ $act['hadir'] }} hadir · {{ $act['alfa'] }} alfa
                            </p>
                        </div>
                    </div>
                    <div class="grid place-items-center size-12 rounded-2xl bg-brand-100 text-brand-600 shrink-0">
                        <i data-lucide="{{ $act['icon'] }}" class="size-5"></i>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ================= AKSI CEPAT ================= --}}
    <div class="relative overflow-hidden rounded-3xl border border-brand-200 bg-brand-600 text-white p-5 mb-6 animate-fade-up">

        <div class="relative flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <span class="grid place-items-center size-10 rounded-2xl bg-white/10 text-white">
                    <i data-lucide="zap" class="size-5"></i>
                </span>
                <div>
                    <h2 class="font-extrabold text-white tracking-tight">Aksi Cepat</h2>
                    <p class="text-xs text-brand-200/90">Akses data anak tanpa memilih Santri lain.</p>
                </div>
            </div>
            <a href="{{ route('wali.laporan') }}" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all">
                <i data-lucide="file-chart-column" class="size-4"></i> Semua Laporan
            </a>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('wali.laporan', ['jenis' => 'jamaah']) }}"
               class="flex items-center gap-3 rounded-2xl bg-white/10 hover:bg-white/20 p-4 transition-all border border-white/10">
                <span class="grid place-items-center size-11 rounded-2xl bg-teal-400 text-white shadow-lg">
                    <i data-lucide="building-2" class="size-5"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-extrabold text-white">Laporan Jamaah</span>
                    <span class="block text-xs text-brand-200/90">Lihat rekap kehadiran Jamaah anak</span>
                </span>
                <i data-lucide="arrow-right" class="size-4 text-brand-200/90"></i>
            </a>

            <a href="{{ route('santri.show', $santri) }}"
               class="flex items-center gap-3 rounded-2xl bg-white/10 hover:bg-white/20 p-4 transition-all border border-white/10">
                <span class="grid place-items-center size-11 rounded-2xl bg-white/20 text-white">
                    <i data-lucide="id-card" class="size-5"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-extrabold text-white">Detail Santri</span>
                    <span class="block text-xs text-brand-200/90">Lihat profil {{ $santri->nama }}</span>
                </span>
                <i data-lucide="arrow-right" class="size-4 text-brand-200/90"></i>
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5">

        {{-- ================= RANKING & POIN ================= --}}
        <div class="lg:col-span-2 card overflow-hidden animate-fade-up" style="animation-delay:.15s">
            <div class="flex flex-wrap items-center justify-between gap-3 p-5 sm:p-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center size-10 rounded-2xl bg-amber-50 border border-amber-100 text-amber-600">
                        <i data-lucide="medal" class="size-5"></i>
                    </span>
                    <div>
                        <h3 class="font-extrabold text-slate-900 tracking-tight">Ranking Bulan Ini</h3>
                        <p class="text-xs text-slate-500">Total = Aktivitas + Prestasi + Pelanggaran</p>
                    </div>
                </div>
                <a href="{{ route('wali.ranking') }}" class="btn-soft btn-sm">
                    Lihat Selengkapnya
                    <i data-lucide="arrow-right" class="size-4"></i>
                </a>
            </div>

            <div class="overflow-x-auto scrollbar-slim">
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Rank</th>
                            <th>Santri</th>
                            <th class="text-right">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                    @if($rankingSaya)
                        <tr class="animate-fade-up bg-amber-50/50">
                            <td class="text-center">
                                <span class="inline-grid place-items-center size-9 rounded-xl border font-extrabold text-sm {{ $rankingSaya['rank'] === 1 ? 'rank-1 text-amber-700' : ($rankingSaya['rank'] === 2 ? 'rank-2 text-slate-600' : 'rank-3 text-orange-700') }} shine">
                                    {{ $rankingSaya['rank'] }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid place-items-center size-9 rounded-xl bg-brand-100 text-brand-700 font-bold text-xs">
                                        {{ $santri->initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $santri->nama }}</p>
                                        <p class="text-xs text-slate-500">NIS {{ $santri->nis }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right">
                                <span class="font-extrabold {{ $rankingSaya['total'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $rankingSaya['total'] >= 0 ? '+' : '' }}{{ $rankingSaya['total'] }}
                                </span>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="3" class="text-center py-10">
                                <i data-lucide="medal" class="size-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="text-sm text-slate-500">Belum ada data ranking bulan ini.</p>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 text-xs text-slate-400 border-t border-slate-100">
                Menampilkan posisi anak Anda · Poin Diniyah tidak dihitung dalam ranking
            </div>
        </div>

        {{-- ================= POINT SUMMARY ================= --}}
        <div class="card animate-fade-up" style="animation-delay:.25s">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center gap-3">
                <span class="grid place-items-center size-10 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600">
                    <i data-lucide="target" class="size-5"></i>
                </span>
                <div>
                    <h3 class="font-extrabold text-slate-900 tracking-tight">Poin Bulan Ini</h3>
                    <p class="text-xs text-slate-500">Total = Aktivitas + Prestasi + Pelanggaran</p>
                </div>
            </div>

            <div class="p-4 sm:p-5 space-y-3">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 mb-2">Total Poin</p>
                    <p class="text-4xl font-extrabold text-emerald-600" data-count="{{ $poinSaya ?? 0 }}">0</p>
                    <p class="text-xs text-emerald-600/80 mt-1">Total: Aktivitas + Prestasi + Pelanggaran</p>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-3 text-center">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 mb-1">Aktivitas</p>
                        <p class="text-2xl font-extrabold text-emerald-600">{{ $poinAktivitasBulanIni ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-3 text-center">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700 mb-1">Prestasi</p>
                        <p class="text-2xl font-extrabold text-amber-600">{{ $totalPrestasi }}</p>
                    </div>
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-3 text-center">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700 mb-1">Pelanggaran</p>
                        <p class="text-2xl font-extrabold text-rose-600">{{ $totalPelanggaran }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= AKTIVITAS HARI INI DETAIL ================= --}}
    <div class="card animate-fade-up" style="animation-delay:.3s">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center gap-3">
            <span class="grid place-items-center size-10 rounded-2xl bg-brand-50 border border-brand-100 text-brand-600">
                <i data-lucide="activity" class="size-5"></i>
            </span>
            <div>
                <h3 class="font-extrabold text-slate-900 tracking-tight">Aktivitas Hari Ini</h3>
                <p class="text-xs text-slate-500">Detail kehadiran hari ini ({{ \Carbon\Carbon::today()->translatedFormat('d F Y') }})</p>
            </div>
        </div>

        <div class="p-4 sm:p-5 space-y-2.5 stagger">
            @foreach($aktivitasHariIni as $key => $act)
                <a href="{{ route('wali.laporan', ['jenis' => $key]) }}"
                   class="flex items-center gap-3 rounded-2xl border border-slate-100 p-3 hover:border-brand-200 hover:bg-brand-50/60 transition-all duration-200 group">
                    <span class="grid place-items-center size-9 rounded-xl bg-brand-50 border border-brand-100 text-brand-600 group-hover:bg-brand-100 group-hover:text-brand-700 transition-colors">
                        <i data-lucide="{{ $act['icon'] }}" class="size-[16px]"></i>
                    </span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-bold text-slate-800">{{ $act['label'] }}</span>
                        <span class="block text-[11px] text-slate-500">
                            {{ $act['hadir'] }} hadir · {{ $act['alfa'] }} alfa
                        </span>
                    </span>
                    <span class="text-right">
                        <span class="block text-sm font-extrabold text-slate-700">{{ $act['total'] }}</span>
                        <span class="block text-[10px] text-slate-400">absen</span>
                    </span>
                </a>
            @endforeach
        </div>

        <div class="px-4 py-3 border-t border-slate-100">
            <p class="text-xs text-center text-slate-500 flex items-center justify-center gap-2">
                <i data-lucide="info" class="size-4 text-sky-500 shrink-0"></i>
                Poin Diniyah tidak masuk ranking, hanya untuk laporan Diniyah
            </p>
        </div>
    </div>
</div>
@endsection
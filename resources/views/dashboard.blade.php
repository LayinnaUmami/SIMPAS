@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan aktivitas dan pencapaian santri hari ini')

@section('content')

    {{-- ================= GREETING ================= --}}
    <div class="relative overflow-hidden rounded-3xl border border-brand-200 bg-gradient-to-br from-brand-600
                via-brand-700 to-emerald-900 text-white p-6 sm:p-8 mb-6 animate-fade-up">
        <div class="blob size-64 bg-emerald-300/40 -top-20 -right-10"></div>
        <div class="blob size-40 bg-amber-300/30 bottom-0 left-1/3" style="animation-delay:-4s"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-6">
            <div>
                <p class="text-emerald-200/90 text-sm font-semibold tracking-wide">
                    Assalamu'alaikum, {{ explode(' ', auth()->user()->name)[0] }} 👋
                </p>
                <h2 class="text-2xl sm:text-3xl font-extrabold mt-1.5 tracking-tight">
                    Selamat datang di {{ config('simpas.nama_aplikasi') }}.
                </h2>
                <p class="text-emerald-100/80 text-sm mt-2 max-w-lg">
                    {{ config('simpas.tagline') }}
                </p>
            </div>

            @if($rankSaya)
                <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/20 px-5 py-4 text-center
                            animate-pop min-w-[150px]">
                    <p class="text-[11px] uppercase tracking-wider text-emerald-200 font-bold">Ranking Saya</p>
                    <p class="text-3xl font-extrabold mt-1">#{{ $rankSaya }}</p>
                    <p class="text-xs text-emerald-100/80 mt-1">
                        <span data-count="{{ $poinSaya }}">0</span> poin
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ================= STATISTIK ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 mb-6 stagger">
        @php
            $stats = [
                ['label' => 'Total Santri Aktif', 'value' => $totalSantri, 'icon' => 'users', 'bg' => 'bg-brand-50 text-brand-700 border-brand-100', 'delta' => 'Terdaftar aktif'],
                ['label' => 'Aktivitas Hari Ini', 'value' => $totalAktivitasHariIni, 'icon' => 'clipboard-check', 'bg' => 'bg-sky-50 text-sky-700 border-sky-100', 'delta' => \Carbon\Carbon::now()->translatedFormat('d M Y')],
                ['label' => 'Total Prestasi', 'value' => $totalPrestasi, 'icon' => 'trophy', 'bg' => 'bg-amber-50 text-amber-700 border-amber-100', 'delta' => 'Bulan ini'],
                ['label' => 'Total Pelanggaran', 'value' => $totalPelanggaran, 'icon' => 'triangle-alert', 'bg' => 'bg-rose-50 text-rose-700 border-rose-100', 'delta' => 'Bulan ini'],
            ];
        @endphp

        @foreach($stats as $stat)
            <div class="stat card-hover">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight" data-count="{{ $stat['value'] }}">0</p>
                        <p class="text-[11px] text-slate-500 mt-1.5">{{ $stat['delta'] }}</p>
                    </div>
                    <span class="stat-icon border {{ $stat['bg'] }}">
                        <i data-lucide="{{ $stat['icon'] }}" class="size-5"></i>
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-5">

        {{-- ================= RANKING BULAN INI ================= --}}
        <div class="lg:col-span-2 card overflow-hidden animate-fade-up" style="animation-delay:.15s">
            <div class="flex flex-wrap items-center justify-between gap-3 p-5 sm:p-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center size-10 rounded-2xl bg-amber-50 border border-amber-100 text-amber-600">
                        <i data-lucide="medal" class="size-5"></i>
                    </span>
                    <div>
                        <h3 class="font-extrabold text-slate-900 tracking-tight">{{ $rankingLabel }}</h3>
                        <p class="text-xs text-slate-500">Total = Aktivitas + Prestasi + Pelanggaran</p>
                    </div>
                </div>
                <a href="{{ route('ranking.index') }}" class="btn-soft btn-sm">
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
                    @forelse($ranking as $row)
                        @php $r = $row['rank']; @endphp
                        <tr class="animate-fade-up" style="animation-delay:{{ 0.05 * $loop->index }}s">
                            <td class="text-center">
                                @if($r <= 3)
                                    <span class="inline-grid place-items-center size-9 rounded-xl border font-extrabold text-sm
                                                 {{ $r === 1 ? 'rank-1 text-amber-700' : ($r === 2 ? 'rank-2 text-slate-600' : 'rank-3 text-orange-700') }} shine">
                                        {{ $r }}
                                    </span>
                                @else
                                    <span class="inline-grid place-items-center size-9 rounded-xl bg-slate-50 border border-slate-200
                                                 text-slate-600 font-bold text-sm">{{ $r }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid place-items-center size-9 rounded-xl bg-brand-100 text-brand-700 font-bold text-xs">
                                        {{ $row['santri']->initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $row['santri']->nama }}</p>
                                        <p class="text-xs text-slate-500">
                                            NIS {{ $row['santri']->nis }}
                                            @if($row['santri']->kamar) · {{ $row['santri']->kamar }} @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right">
                                <span class="font-extrabold {{ $row['total'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $row['total'] >= 0 ? '+' : '' }}{{ $row['total'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-10">
                                <i data-lucide="medal" class="size-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="text-sm text-slate-500">Belum ada data ranking bulan ini.</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($jumlahSantri)
                <p class="px-6 py-3 text-xs text-slate-400 border-t border-slate-100">
                    Menampilkan 5 teratas dari {{ $jumlahSantri }} santri aktif · Poin Diniyah tidak dihitung.
                </p>
            @endif
        </div>

        {{-- ================= QUICK ACTIONS ================= --}}
        <div class="card animate-fade-up" style="animation-delay:.25s">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center size-10 rounded-2xl bg-brand-50 border border-brand-100 text-brand-600">
                        <i data-lucide="zap" class="size-5"></i>
                    </span>
                    <div>
                        <h3 class="font-extrabold text-slate-900 tracking-tight">Aksi Cepat</h3>
                        <p class="text-xs text-slate-500">{{ auth()->user()->isWali() ? 'Pilih laporan untuk anak yang terhubung' : 'Pilih aktivitas untuk input absensi' }}</p>
                    </div>
                </div>
                <a href="{{ auth()->user()->isWali() ? route('wali.laporan') : route('aktivitas.menu') }}"
                   class="btn-soft btn-sm" @if(auth()->user()->isAdmin()) data-activity-modal @endif>
                    {{ auth()->user()->isWali() ? 'Lihat Semua Laporan' : 'Lihat Semua' }}
                    <i data-lucide="chevron-right" class="size-4"></i>
                </a>
            </div>

            <div class="p-4 sm:p-5 space-y-2.5 stagger">
                <div class="grid grid-cols-2 gap-3">
                    @foreach(config('simpas.activities') as $key => $act)
                        <a href="{{ auth()->user()->isWali() ? route('wali.laporan', ['jenis' => $key]) : route('aktivitas.input', $key) }}"
                           class="{{ auth()->user()->isAdmin() ? 'activity-option' : '' }} flex items-center gap-3 rounded-2xl border border-slate-100 p-3 hover:border-brand-200
                                 hover:bg-brand-50/60 transition-all duration-200 group">
                            <span class="grid place-items-center size-10 rounded-2xl border transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6
                                         @switch($act['color'])
                                             @case('emerald') bg-emerald-50 border-emerald-100 text-emerald-600 @break
                                             @case('amber') bg-amber-50 border-amber-100 text-amber-600 @break
                                             @case('sky') bg-sky-50 border-sky-100 text-sky-600 @break
                                             @case('violet') bg-violet-50 border-violet-100 text-violet-600 @break
                                             @case('teal') bg-teal-50 border-teal-100 text-teal-600 @break
                                             @case('rose') bg-rose-50 border-rose-100 text-rose-600 @break
                                             @default bg-indigo-50 border-indigo-100 text-indigo-600
                                         @endswitch">
                                <i data-lucide="{{ $act['icon'] }}" class="size-5"></i>
                            </span>

                            <div class="flex-1 min-w-0">
                                <span class="block text-sm font-bold text-slate-800">{{ $act['label'] }}</span>
                                <span class="block text-[11px] text-slate-500">
                                    {{ auth()->user()->isWali() ? 'Lihat laporan' : count($act['sessions']) . ' sesi · ' . ($act['cadence'] === 'mingguan' ? 'Mingguan' : 'Harian') }}
                                </span>
                            </div>

                            <i data-lucide="chevron-right" class="size-4 text-slate-300 group-hover:text-brand-600 transition-all"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="px-4 py-3 border-t border-slate-100">
                <p class="text-xs text-center text-slate-500 flex items-center justify-center gap-2">
                    <i data-lucide="info" class="size-3 text-sky-500"></i>
                    Diniyah: poin terpisah, tidak masuk ranking
                </p>
            </div>
        </div>
    </div>

@endsection
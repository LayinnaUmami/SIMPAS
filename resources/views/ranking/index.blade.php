@extends('layouts.app')

@section('title', 'Ranking')
@section('subtitle', 'Total = Poin Aktivitas + Prestasi + Pelanggaran (Diniyah tidak dihitung)')

@section('content')

    {{-- ================= FILTER ================= --}}
    <div class="card card-body mb-5 animate-fade-up" data-periode-form>
        <form method="GET" action="{{ route('ranking.index') }}" class="space-y-4" data-periode-form>
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[170px]">
                    <label for="periode" class="label">Periode</label>
                    <select name="periode" id="periode" class="select">
                        @foreach(['minggu' => 'Minggu', 'bulan' => 'Bulan', 'tahun' => 'Tahun', 'semua' => 'Semua', 'custom' => 'Custom tanggal'] as $val => $lbl)
                            <option value="{{ $val }}" {{ ($input['periode'] ?? 'bulan') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Minggu --}}
                <div class="min-w-[130px]" data-periode-field="minggu">
                    <label for="minggu" class="label">Minggu ke-</label>
                    <input type="number" name="minggu" id="minggu" min="1" max="53" value="{{ $minggu }}" class="input">
                </div>

                {{-- Bulan --}}
                <div class="min-w-[150px]" data-periode-field="bulan">
                    <label for="bulan" class="label">Bulan</label>
                    <select name="bulan" id="bulan" class="select">
                        @foreach(\App\Services\PeriodResolver::BULAN as $no => $nm)
                            <option value="{{ $no }}" {{ $bulan === $no ? 'selected' : '' }}>{{ $nm }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tahun --}}
                <div class="min-w-[120px]" data-periode-field="minggu,bulan,tahun">
                    <label for="tahun" class="label">Tahun</label>
                    <input type="number" name="tahun" id="tahun" min="2020" max="2100" value="{{ $input['tahun'] ?? $tahun }}" class="input">
                </div>

                {{-- Custom --}}
                <div class="min-w-[150px]" data-periode-field="custom">
                    <label for="mulai" class="label">Dari Tanggal</label>
                    <input type="date" name="mulai" id="mulai" value="{{ $input['mulai'] ?? '' }}" class="input">
                </div>
                <div class="min-w-[150px]" data-periode-field="custom">
                    <label for="akhir" class="label">Sampai Tanggal</label>
                    <input type="date" name="akhir" id="akhir" value="{{ $input['akhir'] ?? '' }}" class="input">
                </div>

                <div class="min-w-[190px] flex-1">
                    <label for="q" class="label">Cari Nama / NIS</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                        <input type="text" name="q" id="q" value="{{ $filter['nama'] ?? '' }}" placeholder="Contoh: Ahmad"
                               class="input pl-11!" autocomplete="off">
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i data-lucide="list-filter" class="size-4"></i> Terapkan
                </button>
                <a href="{{ route('ranking.index') }}" class="btn-secondary">Reset</a>
            </div>

            <div class="flex flex-wrap items-end gap-3 pt-4 border-t border-slate-100">
                <div class="min-w-[150px]">
                    <label for="jenis_kelamin" class="label">Putra / Putri</label>
                    <select name="jenis_kelamin" id="jenis_kelamin" class="select">
                        <option value="">Semua</option>
                        <option value="Putra" {{ ($input['jenis_kelamin'] ?? '') === 'Putra' ? 'selected' : '' }}>Putra</option>
                        <option value="Putri" {{ ($input['jenis_kelamin'] ?? '') === 'Putri' ? 'selected' : '' }}>Putri</option>
                    </select>
                </div>
                <div class="min-w-[170px]">
                    <label for="kamar" class="label">Kamar</label>
                    <select name="kamar" id="kamar" class="select">
                        <option value="">Semua Kamar</option>
                        @foreach($kamar as $k)
                            <option value="{{ $k }}" {{ ($input['kamar'] ?? '') === $k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-soft">
                    <i data-lucide="filter" class="size-4"></i> Filter Tambahan
                </button>
            </div>
        </form>
    </div>

    {{-- ================= JUDUL PERIODE ================= --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 animate-fade-up">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center size-11 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600
                        text-white shadow-lg shadow-amber-500/30">
                <i data-lucide="medal" class="size-5"></i>
            </span>
            <div>
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">
                    Ranking {{ str_contains($periode['label'], ':') ? trim(substr($periode['label'], strpos($periode['label'], ':') + 1)) : $periode['label'] }}
                </h2>
                <p class="text-xs text-slate-500">{{ count($rows) }} santri ditampilkan</p>
            </div>
        </div>

        @if($filter['nama'] ?? '')
            <span class="badge badge-izin animate-scale-in">
                <i data-lucide="search" class="size-3.5"></i>
                Pencarian "{{ $filter['nama'] }}" — hanya memfilter tampilan, posisi rank tidak berubah
            </span>
        @endif
    </div>

    {{-- ================= TABEL RANKING ================= --}}
    <div class="card overflow-hidden animate-fade-up" style="animation-delay:.1s">
        <div class="overflow-x-auto scrollbar-slim">
            <table class="table-simpas" style="min-width: 760px">
                <thead>
                    <tr>
                        <th class="w-20 text-center">Rank</th>
                        <th>Santri</th>
                        <th class="text-right">Aktivitas</th>
                        <th class="text-right">Prestasi</th>
                        <th class="text-right">Pelanggaran</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $i => $row)
                    @php $r = $row['rank']; @endphp
                    <tr class="animate-fade-up {{ $r <= 3 ? 'bg-gradient-to-r from-amber-50/60 to-transparent' : '' }}"
                        style="animation-delay:{{ min($i * 0.03, 0.4) }}s">
                        <td class="text-center">
                            @if($r <= 3)
                                <span class="inline-grid place-items-center size-10 rounded-xl border font-extrabold
                                             {{ $r === 1 ? 'rank-1 text-amber-700' : ($r === 2 ? 'rank-2 text-slate-600' : 'rank-3 text-orange-700') }} shine">
                                    {{ $r }}
                                </span>
                            @else
                                <span class="inline-grid place-items-center size-10 rounded-xl bg-slate-50 border border-slate-200
                                             text-slate-600 font-bold">{{ $r }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="grid place-items-center size-10 rounded-xl bg-brand-100 text-brand-700 font-bold text-xs">
                                    {{ $row['santri']->initials }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-800 truncate">{{ $row['santri']->nama }}</p>
                                    <p class="text-xs text-slate-500">
                                        NIS {{ $row['santri']->nis }}
                                        @if($row['santri']->kamar) · {{ $row['santri']->kamar }} @endif
                                        · <span class="uppercase">{{ $row['santri']->jenis_kelamin }}</span>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="text-right font-semibold {{ $row['poin_aktivitas'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $row['poin_aktivitas'] >= 0 ? '+' : '' }}{{ $row['poin_aktivitas'] }}
                        </td>
                        <td class="text-right font-semibold text-amber-600">
                            {{ $row['poin_prestasi'] > 0 ? '+' : '' }}{{ $row['poin_prestasi'] }}
                        </td>
                        <td class="text-right font-semibold text-rose-600">
                            {{ $row['poin_pelanggaran'] }}
                        </td>
                        <td class="text-right">
                            <span class="inline-block min-w-[64px] rounded-xl px-3 py-1.5 font-extrabold
                                         {{ $row['total'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $row['total'] >= 0 ? '+' : '' }}{{ $row['total'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-14">
                            <i data-lucide="medal" class="size-10 mx-auto text-slate-300 mb-3"></i>
                            <p class="font-semibold text-slate-600">Tidak ada data ranking</p>
                            <p class="text-sm text-slate-400 mt-1">Coba ubah periode atau filter pencarian.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 bg-slate-50/60 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
            <span class="flex items-center gap-1.5"><i data-lucide="info" class="size-3.5 text-sky-500"></i>
                Rumus: <b class="text-slate-700">Aktivitas + Prestasi + Pelanggaran = Total Ranking</b></span>
            <span class="flex items-center gap-1.5"><i data-lucide="graduation-cap" class="size-3.5 text-indigo-500"></i>
                Poin Diniyah <b class="text-rose-600">tidak</b> dihitung</span>
            <span class="flex items-center gap-1.5"><i data-lucide="trash-2" class="size-3.5 text-slate-400"></i>
                Data dalam Sampah tidak dihitung</span>
        </div>
    </div>

@endsection

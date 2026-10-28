@extends('layouts.app')

@section('title', 'Ranking')
@section('subtitle', 'Ranking anak-anak yang terhubung ke akun Anda')

@section('content')
<div class="space-y-6">

    <div class="card card-body animate-fade-up">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="grid place-items-center size-14 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg shadow-amber-500/30">
                <i data-lucide="medal" class="size-6"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Ranking Santri</h2>
                <p class="text-sm text-slate-500">{{ $santri->nama }} · NIS {{ $santri->nis }} · {{ $santri->kamar ?: 'Kamar belum ditentukan' }}</p>
            </div>
            <span class="badge badge-soft bg-amber-50 text-amber-700 border-amber-100 self-start sm:self-auto">{{ count($anakIds) }} anak tertaut</span>
        </div>
    </div>

    <div class="card card-body animate-fade-up" data-periode-form>
        <form method="GET" action="{{ route('wali.ranking') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="min-w-[170px]">
                <label for="periode" class="label">Periode</label>
                <select id="periode" name="periode" class="select">
                    @foreach([
                        'minggu' => 'Minggu',
                        'bulan' => 'Bulan',
                        'tahun' => 'Tahun',
                        'semua' => 'Semua',
                        'custom' => 'Custom tanggal',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ $periode === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[130px]" data-periode-field="minggu">
                <label for="minggu" class="label">Minggu ke-</label>
                <input type="number" id="minggu" name="minggu" min="1" max="53" value="{{ $filter['minggu'] }}" class="input">
            </div>

            <div class="min-w-[145px]" data-periode-field="bulan">
                <label for="bulan" class="label">Bulan</label>
                <select id="bulan" name="bulan" class="select">
                    @foreach(\App\Services\PeriodResolver::BULAN as $number => $name)
                        <option value="{{ $number }}" {{ (int) $filter['bulan'] === (int) $number ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[120px]" data-periode-field="minggu,bulan,tahun">
                <label for="tahun" class="label">Tahun</label>
                <input type="number" id="tahun" name="tahun" min="1950" max="2100" value="{{ $filter['tahun'] }}" class="input">
            </div>

            <div class="min-w-[150px] sm:col-span-2" data-periode-field="custom">
                <label for="mulai" class="label">Dari Tanggal</label>
                <input type="date" id="mulai" name="mulai" value="{{ $filter['mulai'] }}" class="input">
            </div>
            <div class="min-w-[150px] sm:col-span-2" data-periode-field="custom">
                <label for="akhir" class="label">Sampai Tanggal</label>
                <input type="date" id="akhir" name="akhir" value="{{ $filter['akhir'] }}" class="input">
            </div>

            <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary">
                    <i data-lucide="list-filter" class="size-4"></i> Tampilkan
                </button>
                <a href="{{ route('wali.ranking') }}" class="btn-secondary">
                    <i data-lucide="rotate-ccw" class="size-4"></i> Reset
                </a>
                <span class="text-xs text-slate-500">Hanya data anak yang tertaut ke akun Anda.</span>
            </div>
        </form>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Hasil Ranking</h2>
            <p class="text-xs text-slate-500">{{ $periodeData['label'] }}</p>
        </div>
        <span class="badge badge-soft bg-amber-50 text-amber-700 border-amber-100">Anak Anda</span>
    </div>

    <div class="card overflow-hidden animate-fade-up">
        <div class="overflow-x-auto scrollbar-slim">
            <table class="table-simpas" style="min-width: 620px">
                <thead>
                    <tr>
                        <th class="w-24 text-center">Rank</th>
                        <th>Santri</th>
                        <th class="text-right">Aktivitas</th>
                        <th class="text-right">Prestasi</th>
                        <th class="text-right">Pelanggaran</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {{--
                        @forelse, bukan @foreach.

                        Baris kosong di bawah memakai @empty, dan @empty hanya
                        sah dipasangkan dengan @forelse. Kalau @foreach yang
                        dipakai, kompilator Blade menganggap @empty sebagai
                        token tak dikenal, lalu @endforeach tidak punya pasangan
                        dan halaman gagal dengan "syntax error, unexpected token
                        'endforeach', expecting 'elseif' or 'else' or 'endif'".
                    --}}
                    @forelse($rows as $row)
                        @php
                            $isMyChild = in_array((int) $row['santri']->id, $anakIds, true);
                        @endphp
                        <tr class="animate-fade-up {{ $isMyChild ? 'bg-amber-50/50' : '' }}" style="animation-delay:{{ min($loop->index * 0.03, 0.4) }}s">
                            <td class="text-center">
                                @if($row['rank'] <= 3)
                                    <span class="inline-grid place-items-center size-9 rounded-xl border font-extrabold text-sm {{ $row['rank'] === 1 ? 'rank-1 text-amber-700' : ($row['rank'] === 2 ? 'rank-2 text-slate-600' : 'rank-3 text-orange-700') }} shine">
                                        {{ $row['rank'] }}
                                    </span>
                                @else
                                    <span class="inline-grid place-items-center size-9 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 font-bold text-sm">{{ $row['rank'] }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid place-items-center size-9 rounded-xl {{ $isMyChild ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700' }} font-bold text-xs">
                                        {{ $row['santri']->initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate {{ $isMyChild ? 'text-amber-700' : '' }}">
                                            {{ $row['santri']->nama }}
                                            @if($isMyChild) <span class="text-xs font-normal text-amber-600 ml-1">(Anak Anda)</span> @endif
                                        </p>
                                        <p class="text-xs text-slate-500">NIS {{ $row['santri']->nis }} @if($row['santri']->kamar) · {{ $row['santri']->kamar }} @endif</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right font-semibold {{ $row['poin_aktivitas'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $row['poin_aktivitas'] >= 0 ? '+' : '' }}{{ $row['poin_aktivitas'] }}</td>
                            <td class="text-right font-semibold text-amber-600">{{ $row['poin_prestasi'] >= 0 ? '+' : '' }}{{ $row['poin_prestasi'] }}</td>
                            <td class="text-right font-semibold text-rose-600">{{ $row['poin_pelanggaran'] }}</td>
                            <td class="text-right font-extrabold {{ $row['total'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $row['total'] >= 0 ? '+' : '' }}{{ $row['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-14">
                                <i data-lucide="medal" class="size-10 mx-auto text-slate-300 mb-3"></i>
                                <p class="font-semibold text-slate-600">Belum ada data ranking</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-100 text-xs text-slate-500">
            Poin Diniyah tidak dihitung. Baris berwarna amber = anak Anda.
        </div>
    </div>
</div>
@endsection
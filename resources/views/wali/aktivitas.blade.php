@extends('layouts.app')

@section('title', 'Aktivitas')
@section('subtitle', 'Detail kehadiran santri')

@section('content')
<div class="space-y-6">

    {{-- ================= FILTER TANGGAL ================= --}}
    <div class="card card-body mb-5 animate-fade-up">
        <form method="GET" action="{{ route('wali.aktivitas') }}"
              class="flex flex-wrap items-end gap-3">

            <div class="min-w-[180px]">
                <label for="tanggal" class="label">Tanggal</label>
                <div class="relative">
                    <i data-lucide="calendar-days" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                    <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal->format('Y-m-d') }}" class="input pl-11!">
                </div>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label for="type" class="label">Jenis Aktivitas</label>
                <select name="type" id="type" class="select" onchange="this.form.submit()">
                    @foreach($types as $activityType)
                        <option value="{{ $activityType }}" {{ $type === $activityType ? 'selected' : '' }}>
                            {{ config("simpas.activities.{$activityType}.label") }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary">
                <i data-lucide="search" class="size-4"></i> Tampilkan
            </button>

            <a href="{{ route('wali.dashboard') }}" class="btn-secondary">
                <i data-lucide="layout-dashboard" class="size-4"></i> Dashboard
            </a>
        </form>

        <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-slate-100">
            @foreach($cfg['sessions'] as $sesi)
                <span class="badge badge-netral">
                    <i data-lucide="{{ $cfg['icon'] }}" class="size-3.5"></i> {{ $sesi }}
                </span>
            @endforeach
            <span class="text-[11px] text-slate-500 ml-auto hidden sm:block">
                Hadir <b class="text-emerald-600">+2</b> ·
                Terlambat <b class="text-amber-600">+1</b> ·
                Izin/Sakit/Udzur/Libur <b class="text-slate-500">0</b> ·
                Alfa <b class="text-rose-600">-2</b>
                @if($type === 'diniyah')
                    <span class="badge ml-2" style="background:#eef2ff;color:#4338ca;border-color:#c7d2fe">
                        Poin Diniyah tidak masuk ranking
                    </span>
                @endif
            </span>
        </div>
    </div>

    {{-- ================= TABEL ABSENSI ================= --}}
    <div class="card overflow-hidden animate-fade-up" style="animation-delay:.1s">
        <div class="overflow-x-auto scrollbar-slim">
            <table class="table-simpas" style="min-width: {{ 300 + count($cfg['sessions']) * 140 }}px">
                <thead>
                    <tr>
                        <th class="w-12 text-center">No</th>
                        <th class="w-28">NIS</th>
                        <th>Nama Santri</th>
                        @foreach($cfg['sessions'] as $sesi)
                            <th class="text-center">{{ $sesi }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @forelse($records as $i => $row)
                    <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.02, 0.4) }}s">
                        <td class="text-center text-slate-400 font-semibold">{{ $i + 1 }}</td>
                        <td class="font-mono text-xs text-slate-600">{{ $row->santri->nis }}</td>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <span class="grid place-items-center size-8 rounded-lg bg-brand-100 text-brand-700 font-bold text-[11px] shrink-0">
                                    {{ $row->santri->initials }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 truncate">{{ $row->santri->nama }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $row->santri->kamar ?: '-' }}</p>
                            </div>
                        </div>
                            </td>
                            @foreach($cfg['sessions'] as $sesi)
                                @php $status = $row->sesi[$sesi] ?? '-'; @endphp
                                <td class="text-center" data-cell>
                                    @if($status !== '-')
                                        <span class="badge text-[10px] !px-2
                                            @switch($status)
                                                @case('Hadir') badge-hadir @break
                                                @case('Alfa') badge-alfa @break
                                                @case('Izin') badge-izin @break
                                                @case('Sakit') badge-sakit @break
                                                @case('Terlambat') badge-terlambat @break
                                                @case('Udzur') badge-udzur @break
                                                @default badge-libur
                                            @endswitch">{{ $status }}</span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + count($cfg['sessions']) }}" class="text-center py-12">
                            <i data-lucide="clipboard-check" class="size-9 mx-auto text-slate-300 mb-3"></i>
                            <p class="font-semibold text-slate-600">Belum ada data pada tanggal ini</p>
                            <p class="text-sm text-slate-400 mt-1">Ubah tanggal pada filter di atas.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
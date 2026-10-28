@extends('layouts.app')

@section('title', 'Input ' . $cfg['label'])
@section('subtitle', 'Absensi ' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') . ' · ' . count($santris) . ' santri aktif')

@section('content')

    {{-- ================= FILTER TANGGAL ================= --}}
    <div class="card card-body mb-5 animate-fade-up">
        <form method="GET" action="{{ route('aktivitas.input', $type) }}"
              class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label for="tanggal" class="label">Tanggal</label>
                <div class="relative">
                    <i data-lucide="calendar-days" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                    <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" class="input pl-11!">
                </div>
            </div>

            <button type="submit" class="btn-secondary">
                <i data-lucide="refresh-cw" class="size-4"></i>
                Muat Tanggal
            </button>

            @if($tanggal !== now()->format('Y-m-d'))
                <a href="{{ route('aktivitas.input', $type) }}" class="btn-soft">
                    <i data-lucide="calendar" class="size-4"></i>
                    Hari Ini
                </a>
            @endif

            <a href="{{ route('aktivitas.riwayat', $type) }}" class="btn-soft ml-auto">
                <i data-lucide="clock" class="size-4"></i>
                Riwayat {{ $cfg['label'] }}
            </a>
        </form>

        <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-slate-100">
            @foreach($cfg['sessions'] as $sesi)
                <span class="badge badge-netral">
                    <i data-lucide="{{ $cfg['icon'] }}" class="size-3.5"></i>
                    {{ $sesi }}
                </span>
            @endforeach
            <span class="text-[11px] text-slate-500 ml-1">
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
    <form method="POST" action="{{ route('aktivitas.store', $type) }}">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $tanggal }}">

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
                    @forelse($santris as $i => $santri)
                        <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.02, 0.4) }}s">
                            <td class="text-center text-slate-400 font-semibold">{{ $i + 1 }}</td>
                            <td class="font-mono text-xs text-slate-600">{{ $santri->nis }}</td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="grid place-items-center size-8 rounded-lg bg-brand-100 text-brand-700 font-bold text-[11px] shrink-0">
                                        {{ $santri->initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $santri->nama }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $santri->kamar ?: '-' }}</p>
                                    </div>
                                </div>
                            </td>

                            @foreach($cfg['sessions'] as $sesi)
                                @php
                                    $nilai = $map[$santri->id][$sesi] ?? 'Hadir';
                                @endphp
                                <td class="text-center absen-cell" data-cell>
                                    <select name="data[{{ $santri->id }}][{{ $sesi }}]"
                                            data-poin-select
                                            class="select absen-select !text-center {{ auth()->user()->isAdmin() ? '' : 'pointer-events-none opacity-70' }}"
                                            {{ auth()->user()->isAdmin() ? '' : 'disabled' }}>
                                        @foreach($cfg['statuses'] as $status)
                                            <option value="{{ $status }}"
                                                    data-poin="{{ \App\Models\BaseActivity::pointsFor($status) }}"
                                                {{ $nilai === $status ? 'selected' : '' }}>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($cfg['sessions']) }}" class="text-center py-12">
                                <i data-lucide="users" class="size-9 mx-auto text-slate-300 mb-3"></i>
                                <p class="font-semibold text-slate-600">Belum ada santri aktif</p>
                                <p class="text-sm text-slate-400 mt-1">Tambahkan santri dengan status Aktif terlebih dahulu.</p>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('santri.create') }}" class="btn-primary btn-sm mt-4">
                                        <i data-lucide="plus" class="size-4"></i> Tambah Santri
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(auth()->user()->isAdmin() && $santris->isNotEmpty())
                <div class="flex flex-wrap items-center justify-between gap-4 p-5 border-t border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-3">
                        <span class="grid place-items-center size-10 rounded-xl bg-white border border-slate-200 text-brand-600">
                            <i data-lucide="save" class="size-5"></i>
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Total poin halaman ini</p>
                            <p class="text-xs text-slate-500">Dihitung otomatis dari status yang dipilih</p>
                        </div>
                        <span id="poin-total" class="text-2xl font-extrabold text-emerald-600 ml-2">0</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('aktivitas.menu') }}" class="btn-secondary">
                            <i data-lucide="x" class="size-4"></i> Batal
                        </a>
                        <button type="submit" class="btn-primary !px-6">
                            <i data-lucide="save" class="size-4"></i>
                            Simpan Absensi
                        </button>
                    </div>
                </div>
            @else
                @if(!auth()->user()->isAdmin())
                    <div class="p-4 border-t border-slate-100 bg-slate-50/60">
                        <p class="text-xs text-slate-500 flex items-center gap-2">
                            <i data-lucide="info" class="size-4 text-sky-500"></i>
                            Akun Santri/Wali hanya dapat melihat data, tidak dapat mengubah penilaian atau absensi.
                        </p>
                    </div>
                @endif
            @endif
        </div>
    </form>

@endsection

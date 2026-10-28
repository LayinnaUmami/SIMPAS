@extends('layouts.app')

@section('title', 'Detail ' . $cfg['label'])
@section('subtitle', \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'))

@section('content')

    <div class="max-w-3xl mx-auto">

        {{-- Kepala detail --}}
        <div class="card card-body mb-5 animate-fade-up">
            <div class="flex flex-wrap items-center gap-4">
                <div class="grid place-items-center size-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700
                            text-white text-xl font-extrabold shadow-lg shadow-brand-600/30">
                    {{ $santri->initials }}
                </div>
                <div class="flex-1 min-w-[200px]">
                    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">{{ $santri->nama }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        NIS: <span class="font-semibold text-slate-700">{{ $santri->nis }}</span>
                        @if($santri->kamar) · Kamar: <span class="font-semibold text-slate-700">{{ $santri->kamar }}</span> @endif
                    </p>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Tanggal: <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</span>
                    </p>
                </div>
                <span class="badge badge-netral">
                    <i data-lucide="{{ $cfg['icon'] }}" class="size-3.5"></i>
                    {{ $cfg['label'] }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2 mt-5 pt-5 border-t border-slate-100">
                <a href="{{ route('aktivitas.riwayat', [$type, 'tanggal' => $tanggal]) }}" class="btn-secondary">
                    <i data-lucide="arrow-left" class="size-4"></i> Kembali ke Riwayat
                </a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('aktivitas.edit', [$type, $santri->id, 'tanggal' => $tanggal]) }}" class="btn-soft">
                        <i data-lucide="pencil" class="size-4"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('aktivitas.destroy', [$type, $santri->id, 'tanggal' => $tanggal]) }}"
                          data-confirm="Hapus seluruh data {{ $cfg['label'] }} santri ini pada tanggal tersebut?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <i data-lucide="trash-2" class="size-4"></i> Hapus
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Tabel sesi --}}
        <div class="card overflow-hidden animate-fade-up" style="animation-delay:.1s">
            <div class="p-5 border-b border-slate-100 flex items-center gap-3">
                <span class="grid place-items-center size-10 rounded-2xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="list-checks" class="size-5"></i>
                </span>
                <div>
                    <h3 class="font-extrabold text-slate-900">Rekap Sesi</h3>
                    <p class="text-xs text-slate-500">Status dan poin per sesi</p>
                </div>
            </div>

            <div class="overflow-x-auto scrollbar-slim">
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">No</th>
                            <th>Sesi</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = 1; @endphp
                        @foreach($sesi as $nama => $info)
                            <tr class="animate-fade-up" style="animation-delay:{{ 0.04 * $loop->index }}s">
                                <td class="text-center text-slate-400 font-semibold">{{ $i++ }}</td>
                                <td class="font-semibold text-slate-800">{{ $nama }}</td>
                                <td class="text-center">
                                    @if($info['ada'])
                                        <span class="badge
                                            @switch($info['status'])
                                                @case('Hadir') badge-hadir @break
                                                @case('Alfa') badge-alfa @break
                                                @case('Izin') badge-izin @break
                                                @case('Sakit') badge-sakit @break
                                                @case('Terlambat') badge-terlambat @break
                                                @case('Udzur') badge-udzur @break
                                                @default badge-libur
                                            @endswitch">
                                            {{ $info['status'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-300 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($info['ada'])
                                        <span class="font-extrabold {{ $info['poin'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $info['poin'] > 0 ? '+' : '' }}{{ $info['poin'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Total --}}
            <div class="p-5 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center size-10 rounded-xl bg-white border border-slate-200 text-brand-600">
                        <i data-lucide="chart-column" class="size-5"></i>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Total</p>
                        <p class="text-xs text-slate-500">Akumulasi seluruh sesi pada tanggal ini</p>
                    </div>
                </div>
                <span class="text-3xl font-extrabold {{ $total >= 0 ? 'text-emerald-600' : 'text-rose-600' }} animate-pop">
                    {{ $total >= 0 ? '+' : '' }}{{ $total }}
                    <span class="text-sm font-bold text-slate-500">poin</span>
                </span>
            </div>
        </div>

        @if($type === 'diniyah')
            <div class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 flex items-start gap-3 animate-fade-up">
                <i data-lucide="info" class="size-5 text-indigo-600 shrink-0 mt-0.5"></i>
                <p class="text-sm text-indigo-800">
                    <b>Poin Diniyah terpisah.</b> Tidak masuk ranking, tidak masuk poin aktivitas,
                    tidak masuk prestasi maupun pelanggaran. Poin ini hanya digunakan untuk
                    Riwayat, Laporan, dan Rekap Diniyah.
                </p>
            </div>
        @endif
    </div>

@endsection

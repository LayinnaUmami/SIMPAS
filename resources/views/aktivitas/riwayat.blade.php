@extends('layouts.app')

@section('title', 'Riwayat ' . $cfg['label'])
@section('subtitle', 'Data ' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'))

@section('content')

    {{-- ================= FILTER ================= --}}
    <div class="card card-body mb-5 animate-fade-up">
        <form method="GET" action="{{ route('aktivitas.riwayat', $type) }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[180px]">
                <label for="tanggal" class="label">Tanggal</label>
                <select name="tanggal" id="tanggal" class="select">
                    <option value="{{ now()->format('Y-m-d') }}" {{ $tanggal === now()->format('Y-m-d') ? 'selected' : '' }}>
                        Hari ini ({{ now()->translatedFormat('d M Y') }})
                    </option>
                    @foreach($daftarTanggal as $t)
                        <option value="{{ $t }}" {{ $tanggal === $t ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($t)->translatedFormat('d F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label for="q" class="label">Cari Santri</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                    <input type="text" name="q" id="q" value="{{ $q }}" placeholder="Nama atau NIS..."
                           class="input pl-11!" autocomplete="off">
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i data-lucide="search" class="size-4"></i> Cari
            </button>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('aktivitas.input', $type) }}" class="btn-secondary">
                    <i data-lucide="plus" class="size-4"></i> Input Baru
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-slate-100">
            <span class="badge {{ $tanggal === now()->format('Y-m-d') ? 'badge-hadir' : 'badge-netral' }}">
                <i data-lucide="calendar-days" class="size-3.5"></i>
                {{ $tanggal === now()->format('Y-m-d') ? 'Menampilkan data hari ini' : \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
            </span>
            <span class="text-xs text-slate-500">{{ count($rows) }} santri tercatat</span>
            <span class="text-[11px] text-slate-400 ml-auto hidden sm:block">
                Tanggal lama dapat dicari melalui filter di atas
            </span>
        </div>
    </div>

    {{-- ================= TABEL RIWAYAT ================= --}}
    <div class="card overflow-hidden animate-fade-up" style="animation-delay:.1s">
        <div class="overflow-x-auto scrollbar-slim">
            <table class="table-simpas">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th class="w-28">NIS</th>
                        <th>Nama Santri</th>
                        <th>Tanggal Terakhir</th>
                        <th class="text-center">Total Poin</th>
                        <th class="text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $i => $row)
                    @php $santri = $row['santri']; @endphp
                    <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.03, 0.35) }}s">
                        <td class="text-center text-slate-400 font-semibold">{{ $i + 1 }}</td>
                        <td class="font-mono text-xs text-slate-600">{{ $santri?->nis ?? '-' }}</td>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <span class="grid place-items-center size-8 rounded-lg bg-brand-100 text-brand-700 font-bold text-[11px]">
                                    {{ $santri?->initials ?? '?' }}
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $santri?->nama ?? 'Santri terhapus' }}</p>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($row['detail'] as $sesi => $status)
                                            <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold border
                                                @switch($status)
                                                    @case('Hadir') bg-emerald-50 text-emerald-700 border-emerald-100 @break
                                                    @case('Alfa') bg-rose-50 text-rose-700 border-rose-100 @break
                                                    @case('Izin') bg-sky-50 text-sky-700 border-sky-100 @break
                                                    @case('Sakit') bg-amber-50 text-amber-700 border-amber-100 @break
                                                    @case('Terlambat') bg-orange-50 text-orange-700 border-orange-100 @break
                                                    @default bg-slate-50 text-slate-600 border-slate-100
                                                @endswitch">
                                                {{ $sesi }}: {{ $status }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-slate-600 text-sm whitespace-nowrap">
                            <i data-lucide="calendar" class="size-3.5 inline mr-1 text-slate-400"></i>
                            {{ $row['tanggal_terakhir']->format('d M Y') }}
                        </td>
                        <td class="text-center">
                            <span class="font-extrabold {{ $row['total'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $row['total'] >= 0 ? '+' : '' }}{{ $row['total'] }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="relative inline-block">
                                <button type="button" data-dropdown="rm-{{ $santri?->id }}"
                                        class="grid place-items-center size-8 rounded-lg text-slate-400
                                               hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                        title="Aksi">
                                    <i data-lucide="ellipsis-vertical" class="size-4"></i>
                                </button>
                                <div id="rm-{{ $santri?->id }}"
                                     class="dropdown-menu hidden absolute right-0 z-30 mt-1 w-40 rounded-2xl
                                            border border-slate-200 bg-white p-1.5 shadow-xl">
                                    <a href="{{ route('aktivitas.detail', [$type, $santri?->id, 'tanggal' => $tanggal]) }}"
                                       class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-slate-700
                                              hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i data-lucide="eye" class="size-4"></i> Detail
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('aktivitas.edit', [$type, $santri?->id, 'tanggal' => $tanggal]) }}"
                                           class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-slate-700
                                                  hover:bg-sky-50 hover:text-sky-700 transition-colors">
                                            <i data-lucide="pencil" class="size-4"></i> Edit
                                        </a>
                                        <form method="POST"
                                              action="{{ route('aktivitas.destroy', [$type, $santri?->id, 'tanggal' => $tanggal]) }}"
                                              data-confirm="Hapus data {{ $cfg['label'] }} {{ $santri?->nama }} tanggal {{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm
                                                           text-rose-600 hover:bg-rose-50 transition-colors">
                                                <i data-lucide="trash-2" class="size-4"></i> Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-14">
                            <i data-lucide="clipboard-check" class="size-10 mx-auto text-slate-300 mb-3"></i>
                            <p class="font-semibold text-slate-600">Belum ada data pada tanggal ini</p>
                            <p class="text-sm text-slate-400 mt-1">
                                Ubah tanggal pada filter di atas, atau mulai input absensi.
                            </p>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('aktivitas.input', $type) }}" class="btn-primary btn-sm mt-4">
                                    <i data-lucide="plus" class="size-4"></i> Input Absensi
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

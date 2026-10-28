@extends('layouts.app')
@section('title', 'Prestasi')
@section('subtitle', 'Catatan prestasi dan pencapaian santri')

@section('content')
<div class="space-y-6">

    {{-- ====================== FILTER ====================== --}}
    <div class="card card-body" data-reveal>
        <form method="GET" action="{{ route('prestasi.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="label" for="q">Pencarian</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                    <input id="q" name="q" type="text" value="{{ request('q') }}"
                           class="input pl-11!" placeholder="Nama atau NIS santri...">
                </div>
            </div>

            <div>
                <label class="label" for="jenis">Jenis Prestasi</label>
                <select id="jenis" name="jenis" class="select">
                    <option value="">Semua Jenis</option>
                    @foreach($master['jenis_prestasi'] as $j)
                        <option value="{{ $j }}" @selected(request('jenis') === $j)>{{ $j }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="tingkat">Tingkat</label>
                <select id="tingkat" name="tingkat" class="select">
                    <option value="">Semua Tingkat</option>
                    @foreach($master['tingkat_prestasi'] as $t)
                        <option value="{{ $t }}" @selected(request('tingkat') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <button type="submit" class="btn-primary">
                    <i data-lucide="list-filter" class="size-4"></i> Filter
                </button>
                <a href="{{ route('prestasi.index') }}" class="btn-secondary">
                    <i data-lucide="rotate-ccw" class="size-4"></i> Reset
                </a>

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('prestasi.create') }}" class="btn-primary sm:ml-auto">
                        <i data-lucide="plus" class="size-4"></i> Tambah Prestasi
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ====================== TABEL ====================== --}}
    @if($prestasi->isEmpty())
        <div class="card card-body text-center py-16 animate-scale-in">
            <div class="grid place-items-center size-16 rounded-3xl bg-brand-50 text-brand-600 border border-brand-100 mx-auto">
                <i data-lucide="trophy" class="size-8"></i>
            </div>
            <p class="font-bold text-slate-800 mt-4">Belum ada data prestasi</p>
            <p class="text-sm text-slate-500 mt-1">Prestasi yang dicatat akan tampil di tabel ini.</p>
        </div>
    @else
        <div class="card card-body !p-0 overflow-visible" data-reveal>
            <div class="table-wrap !border-0 !rounded-3xl">
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-14">No</th>
                            <th>Tanggal</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Prestasi</th>
                            <th>Tingkat</th>
                            <th>Keterangan</th>
                            <th>Poin</th>
                            <th class="no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prestasi as $i => $p)
                            <tr>
                                <td class="text-slate-400">{{ $prestasi->firstItem() + $i }}</td>
                                <td class="whitespace-nowrap">{{ $p->tanggal->format('d M Y') }}</td>
                                <td class="font-semibold text-slate-800">{{ $p->santri->nis ?? '-' }}</td>
                                <td class="font-semibold text-slate-800">{{ $p->santri->nama ?? '-' }}</td>
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $p->nama_prestasi }}</p>
                                    <span class="badge badge-netral !py-0.5 !px-2 !text-[10px] mt-1">{{ $p->jenis }}</span>
                                </td>
                                <td>
                                    @php
                                        $tingkatBadge = in_array($p->tingkat, ['Pondok', 'Desa'])
                                            ? 'badge-netral'
                                            : (in_array($p->tingkat, ['Kecamatan', 'Kabupaten'])
                                                ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200');
                                    @endphp
                                    <span class="badge {{ $tingkatBadge }}">{{ $p->tingkat }}</span>
                                </td>
                                <td class="max-w-[220px] text-slate-500">
                                    {{ $p->keterangan ? \Illuminate\Support\Str::limit($p->keterangan, 60, '…') : '-' }}
                                </td>
                                <td>
                                    <span class="badge bg-emerald-50 text-emerald-700 border-emerald-200">
                                        +{{ $p->poin }}
                                    </span>
                                </td>
                                <td class="no-print">
                                    <div class="relative">
                                        <button type="button" data-dropdown="menu-{{ $p->id }}"
                                                class="btn-icon btn-sm !p-2 text-slate-500 hover:text-slate-800" title="Aksi">
                                            <i data-lucide="ellipsis-vertical" class="size-4"></i>
                                        </button>
                                        <div id="menu-{{ $p->id }}"
                                             class="dropdown-menu hidden absolute right-0 z-20 mt-1 w-40 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl">
                                            <a href="{{ route('prestasi.show', $p) }}"
                                               class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                                <i data-lucide="eye" class="size-4"></i> Lihat
                                            </a>

                                            @if(auth()->user()->isAdmin())
                                                <a href="{{ route('prestasi.edit', $p) }}"
                                                   class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                                    <i data-lucide="pencil" class="size-4"></i> Edit
                                                </a>
                                                <form method="POST" action="{{ route('prestasi.destroy', $p) }}"
                                                      data-confirm="Yakin menghapus data ini?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors">
                                                        <i data-lucide="trash-2" class="size-4"></i> Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-2">
            {{ $prestasi->links('pagination::tailwind') }}
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')
@section('title', 'Data Santri')
@section('subtitle', 'Kelola identitas dan informasi santri pondok')

@section('content')
<div class="space-y-6">

    {{-- ====================== FILTER ====================== --}}
    <div class="card card-body" data-reveal>
        <form method="GET" action="{{ route('santri.index') }}"
              class="flex flex-wrap items-end gap-3">

            <div class="flex-1 min-w-[250px]">
                <label class="label" for="q">Pencarian</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                    <input id="q" name="q" type="text" value="{{ request('q') }}"
                           class="input pl-11!" placeholder="Nama atau NIS...">
                </div>
            </div>

            {{-- Sorting --}}
            <div class="flex items-center gap-2 ml-auto">
                <label class="label" for="sort">Urutkan</label>
                <select id="sort" name="sort" class="select w-auto"
                        onchange="this.form.submit()">
                    <option value="abjad" {{ request('sort') === 'abjad' ? 'selected' : '' }}>Nama (A-Z)</option>
                    <option value="nis" {{ request('sort') === 'nis' ? 'selected' : '' }}>NIS</option>
                    <option value="terbaru" {{ request('sort') === 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                    <option value="terlama" {{ request('sort') === 'terlama' ? 'selected' : '' }}>Terlama</option>
                </select>
            </div>
            
            <button type="submit" class="btn-primary">
                <i data-lucide="search" class="size-4"></i> Cari
            </button>
            <a href="{{ route('santri.index') }}" class="btn-secondary">
                <i data-lucide="rotate-ccw" class="size-4"></i> Reset
            </a>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('santri.create') }}" class="btn-primary">
                    <i data-lucide="plus" class="size-4"></i> Tambah Santri
                </a>
            @endif

        
        </form>
    </div>

    <p class="text-xs font-semibold text-slate-500">
        Menampilkan {{ $santris->total() }} data santri
    </p>

    {{-- ====================== SANTRI TABLE (PREMIUM) ====================== --}}
    @if($santris->isEmpty())
        <div class="card card-body text-center py-16 animate-scale-in">
            <div class="grid place-items-center size-16 rounded-3xl bg-slate-100 text-slate-400 mx-auto">
                <i data-lucide="users" class="size-8"></i>
            </div>
            <p class="font-bold text-slate-800 mt-4">Belum ada data santri</p>
            <p class="text-sm text-slate-500 mt-1">Data yang cocok dengan filter akan tampil di sini.</p>
        </div>
    @else
        <div class="card overflow-hidden animate-fade-up">
            <div class="overflow-x-auto scrollbar-slim">
                <table class="table-simpas w-full">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="w-14 text-center">#</th>
                            <th>Santri</th>
                            <th class="hidden md:table-cell">Kamar</th>
                            <th class="hidden lg:table-cell">Pendidikan</th>
                            <th class="hidden lg:table-cell">Diniyah</th>
                            <th class="hidden lg:table-cell">Status</th>
                            <th class="w-40 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($santris as $index => $s)
                            @php
                                $statusBadge = [
                                    'Aktif' => 'badge-hadir',
                                    'Alumni' => 'badge-netral',
                                    'Pindah' => 'badge-izin',
                                    'Keluar' => 'badge-alfa'
                                ][$s->status_aktif] ?? 'badge-netral';
                                $diniyahLabel = $s->jenis_diniyah === 'Diniyah'
                                    ? 'Diniyah: ' . ($s->level_diniyah ?: '-')
                                    : $s->jenis_diniyah;
                            @endphp
                            <tr class="hover:bg-emerald-50/30 transition-colors">
                                <td class="text-center text-sm text-slate-500 px-4 py-4">
                                    {{ $santris->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="grid place-items-center size-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600
                                                    text-white font-bold text-sm shrink-0 shadow-lg shadow-emerald-500/30">
                                            {{ $s->initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">{{ $s->nama }}</p>
                                            <p class="text-xs text-slate-500 flex items-center gap-1">
                                                <i data-lucide="badge" class="size-3"></i>
                                                NIS {{ $s->nis }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell px-4 py-4 text-sm text-slate-600">
                                    @if($s->kamar)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 text-xs font-medium">
                                            <i data-lucide="home" class="size-2.5"></i>
                                            {{ $s->kamar }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell px-4 py-4 text-sm text-slate-600">
                                    @if($s->pendidikan_formal)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
                                            <i data-lucide="graduation-cap" class="size-2.5"></i>
                                            {{ $s->pendidikan_formal }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell px-4 py-4 text-sm text-slate-600">
                                    @if($s->jenis_diniyah === 'Diniyah')
                                        <span class="badge badge-info flex items-center gap-1">
                                            <i data-lucide="book-open" class="size-3"></i>
                                            {{ $diniyahLabel }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 flex items-center gap-1">
                                            <i data-lucide="graduation-cap" class="size-3"></i>
                                            {{ $diniyahLabel }}
                                        </span>
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell px-4 py-4 text-center">
                                    <span class="badge {{ $statusBadge }} flex items-center justify-center gap-1">
                                        @if($s->status_aktif === 'Aktif')
                                            <i data-lucide="check-circle" class="size-3"></i>
                                        @elseif($s->status_aktif === 'Alumni')
                                            <i data-lucide="award" class="size-3"></i>
                                        @elseif($s->status_aktif === 'Pindah')
                                            <i data-lucide="arrow-right-circle" class="size-3"></i>
                                        @elseif($s->status_aktif === 'Keluar')
                                            <i data-lucide="x-circle" class="size-3"></i>
                                        @endif
                                        {{ $s->status_aktif }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('santri.show', $s) }}"
                                           class="grid place-items-center size-10 rounded-xl bg-slate-100 hover:bg-emerald-50 hover:text-emerald-600
                                                  text-slate-500 hover:text-emerald-600 transition-all duration-200 group"
                                           title="Lihat Detail">
                                            <i data-lucide="eye" class="size-4 group-hover:scale-110 transition-transform"></i>
                                        </a>
                                        @if(auth()->user()->isAdmin())
                                            <a href="{{ route('santri.edit', $s) }}"
                                               class="grid place-items-center size-10 rounded-xl bg-slate-100 hover:bg-amber-50 hover:text-amber-600
                                                     text-slate-500 hover:text-amber-600 transition-all duration-200 group"
                                               title="Edit">
                                                <i data-lucide="pencil" class="size-4 group-hover:scale-110 transition-transform"></i>
                                            </a>
                                            <form method="POST" action="{{ route('santri.destroy', $s) }}"
                                                  data-confirm="Yakin menghapus data ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="grid place-items-center size-10 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600
                                                               text-slate-500 hover:text-rose-600 transition-all duration-200 group"
                                                        title="Hapus">
                                                    <i data-lucide="trash-2" class="size-4 group-hover:scale-110 transition-transform"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <p class="text-sm text-slate-500">
                    Menampilkan {{ $santris->firstItem() ?? 0 }} - {{ $santris->lastItem() ?? 0 }} dari {{ $santris->total() }} data
                </p>
                {{ $santris->links('pagination::tailwind') }}
            </div>
        </div>
    @endif
</div>

@endsection
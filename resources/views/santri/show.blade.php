@extends('layouts.app')
@section('title', $santri->nama)
@section('subtitle', 'Detail data santri · NIS ' . $santri->nis)

@section('content')
<div class="space-y-6">

    @php
        $statusBadge = ['Aktif' => 'badge-hadir', 'Alumni' => 'badge-netral', 'Pindah' => 'badge-izin', 'Keluar' => 'badge-alfa'][$santri->status_aktif] ?? 'badge-netral';
    @endphp

    {{-- ====================== HEADER ====================== --}}
    <div class="card card-body animate-fade-up">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            {{-- Foto/Avatar --}}
            <div class="relative shrink-0">
                @if($santri->foto_url)
                    <img src="{{ $santri->foto_url }}" alt="Foto {{ $santri->nama }}"
                         class="size-20 rounded-3xl object-cover shadow-lg shadow-slate-200/50 ring-4 ring-white shadow-slate-200/50">
                @else
                    <div class="grid place-items-center size-20 rounded-3xl bg-gradient-to-br from-brand-500 to-brand-700
                                text-white font-extrabold text-2xl shadow-lg shadow-brand-600/30 animate-pop">
                        {{ $santri->initials }}
                    </div>
                @endif
                {{-- Badge status on avatar --}}
                @if($santri->status_aktif === 'Aktif')
                    <span class="absolute bottom-1 right-1 size-5 rounded-full bg-emerald-500 ring-2 ring-white flex items-center justify-center">
                        <i data-lucide="check" class="size-2.5"></i>
                    </span>
                @elseif($santri->status_aktif === 'Alumni')
                    <span class="absolute bottom-1 right-1 size-5 rounded-full bg-amber-500 ring-2 ring-white flex items-center justify-center">
                        <i data-lucide="award" class="size-2.5"></i>
                    </span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $santri->nama }}</h2>
                <p class="text-sm text-slate-500 mt-1">NIS <span class="font-semibold text-slate-700">{{ $santri->nis }}</span></p>
                <div class="flex flex-wrap gap-1.5 mt-3">
                    <span class="badge {{ $statusBadge }}">{{ $santri->status_aktif }}</span>
                    <span class="badge badge-izin">{{ $santri->jenis_kelamin }}</span>
                    @if($santri->kamar)
                        <span class="badge badge-netral">
                            <i data-lucide="home" class="size-3"></i> {{ $santri->kamar }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                <a href="{{ auth()->user()->isWali() ? route('wali.dashboard') : route('santri.index') }}" class="btn-secondary">
                    <i data-lucide="arrow-left" class="size-4"></i> Kembali
                </a>

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('santri.edit', $santri) }}" class="btn-primary">
                        <i data-lucide="pencil" class="size-4"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('santri.destroy', $santri) }}"
                          data-confirm="Yakin menghapus data ini?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <i data-lucide="trash-2" class="size-4"></i> Hapus
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- ====================== DATA PRIBADI ====================== --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <div class="flex items-center gap-3 mb-4">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="id-card" class="size-5"></i>
                </div>
                <h3 class="font-extrabold text-slate-900 tracking-tight">Data Pribadi</h3>
            </div>

            <dl class="divide-y divide-slate-100">
                @foreach([
                    ['NIS', $santri->nis],
                    ['Nama', $santri->nama],
                    ['Jenis Kelamin', $santri->jenis_kelamin],
                    ['Alamat', $santri->alamat ?: '-'],
                    ['Kamar', $santri->kamar ?: '-'],
                    ['Status Aktif', $santri->status_aktif],
                    ['Pendidikan Formal', $santri->pendidikan_formal ?: '-'],
                    ['Jenis Diniyah', $santri->jenis_diniyah ?: '-'],
                    ['Level Diniyah', $santri->level_diniyah_tampil],
                ] as [$dt, $dd])
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $dt }}</dt>
                        <dd class="text-sm font-semibold text-slate-800 text-right">{{ $dd }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- ====================== ORANG TUA / WALI ====================== --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <div class="flex items-center gap-3 mb-4">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="heart" class="size-5"></i>
                </div>
                <h3 class="font-extrabold text-slate-900 tracking-tight">Orang Tua / Wali</h3>
            </div>

            <dl class="divide-y divide-slate-100">
                @foreach([
                    ['Nama Ayah', $santri->nama_ayah ?: '-'],
                    ['Pekerjaan Ayah', $santri->pekerjaan_ayah ?: '-'],
                    ['Nama Ibu', $santri->nama_ibu ?: '-'],
                    ['Pekerjaan Ibu', $santri->pekerjaan_ibu ?: '-'],
                    ['Nama Wali', $santri->nama_wali ?: '-'],
                    ['Pekerjaan Wali', $santri->pekerjaan_wali ?: '-'],
                ] as [$dt, $dd])
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $dt }}</dt>
                        <dd class="text-sm font-semibold text-slate-800 text-right">{{ $dd }}</dd>
                    </div>
                @endforeach

                <div class="flex items-start justify-between gap-4 py-3">
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <i data-lucide="phone" class="size-3.5"></i> No. HP
                    </dt>
                    <dd class="text-sm font-semibold text-slate-800 text-right">{{ $santri->no_hp ?: '-' }}</dd>
                </div>

                <div class="flex items-start justify-between gap-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat Wali</dt>
                    <dd class="text-sm font-semibold text-slate-800 text-right">{{ $santri->alamat_wali ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ====================== RIWAYAT KHATAMAN ====================== --}}
    <div class="card card-body animate-fade-up" data-reveal>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="bookmark-check" class="size-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 tracking-tight">Riwayat Khataman</h3>
                    <p class="text-xs text-slate-500">Santri dapat menyelesaikan khatam berkali-kali</p>
                </div>
            </div>

            @if(auth()->user()->isAdmin())
                <details class="group" id="form-khataman">
                    <summary class="btn-secondary cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        <i data-lucide="plus" class="size-4"></i> Tambah Riwayat
                    </summary>

                    <form method="POST" action="{{ route('santri.khataman.store', $santri) }}"
                          class="mt-4 grid gap-4 sm:grid-cols-[1fr_160px_auto] items-end rounded-2xl border border-dashed
                                 border-brand-200 bg-brand-50/50 p-4">
                        @csrf
                        <div>
                            <label class="label" for="jenis_khatam">Jenis Khatam</label>
                            <input id="jenis_khatam" name="jenis_khatam" type="text" class="input" required
                                   placeholder="Contoh: 30 Juz, 20 Juz, 1 Juz, dll."
                                   value="{{ old('jenis_khatam') }}">
                        </div>

                        <div>
                            <label class="label" for="tahun">Tahun</label>
                            <input id="tahun" name="tahun" type="number" min="1950" max="2100"
                                   value="{{ old('tahun', now()->year) }}" class="input" required>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i data-lucide="save" class="size-4"></i> Simpan
                        </button>

                        @error('jenis_khatam')<p class="hint text-rose-600 sm:col-span-3">{{ $message }}</p>@enderror
                        @error('tahun')<p class="hint text-rose-600 sm:col-span-3">{{ $message }}</p>@enderror
                    </form>
                </details>
            @endif
        </div>

        @if($santri->khatamans->isEmpty())
            <div class="text-center py-10 rounded-2xl border border-dashed border-slate-300 bg-slate-50/60">
                <div class="grid place-items-center size-14 rounded-3xl bg-white border border-slate-200 text-slate-400 mx-auto">
                    <i data-lucide="book-open" class="size-7"></i>
                </div>
                <p class="text-sm font-bold text-slate-700 mt-3">Belum ada riwayat khataman</p>
                <p class="text-xs text-slate-500 mt-1">Riwayat khatam santri ini masih kosong.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-16">No</th>
                            <th>Jenis Khatam</th>
                            <th>Tahun</th>
                            <th class="no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($santri->khatamans as $i => $k)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <span class="badge badge-hadir">
                                        <i data-lucide="badge-check" class="size-3"></i> {{ $k->jenis_khatam }}
                                    </span>
                                </td>
                                <td class="font-semibold text-slate-800">{{ $k->tahun }}</td>
                                <td class="no-print">
                                    @if(auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('khataman.destroy', $k) }}"
                                              data-confirm="Yakin menghapus data ini?" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger btn-sm">
                                                <i data-lucide="trash-2" class="size-4"></i> Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

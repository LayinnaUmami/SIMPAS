@extends('layouts.app')
@section('title', $prestasi->exists ? 'Edit Prestasi' : 'Tambah Prestasi')
@section('subtitle', $prestasi->exists ? 'Perbarui catatan prestasi santri' : 'Catat prestasi baru santri')

@section('content')
<div class="max-w-5xl mx-auto">

    @php
        $sid = old('santri_id', $prestasi->santri_id);
        $santriTerisi = $sid ? ($santris->find($sid) ?? $prestasi->santri) : null;
    @endphp

    <form method="POST"
          action="{{ $prestasi->exists ? route('prestasi.update', $prestasi) : route('prestasi.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if($prestasi->exists)
            @method('PUT')
        @endif

        <div class="card card-body animate-fade-up" data-reveal>
            <div class="flex items-center gap-3 mb-6">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="trophy" class="size-5"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-900 tracking-tight">Data Prestasi</h2>
                    <p class="text-xs text-slate-500">Pilih santri lalu isi rincian prestasinya</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                {{-- ========== ISI OTOMATIS SANTRI (ditangani JS global) ========== --}}
                <div class="sm:col-span-2">
                    <label class="label" for="santri-search">Santri *</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                        <input type="text" id="santri-search" class="input pl-11!"
                               placeholder="Ketik NIS atau nama santri..." autocomplete="off"
                               value="{{ $santriTerisi ? $santriTerisi->nis . ' - ' . $santriTerisi->nama : '' }}">
                        <div id="santri-results"
                             class="hidden absolute z-30 mt-1 w-full max-h-64 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl"></div>
                    </div>

                    <input type="hidden" name="santri_id" id="santri_id" value="{{ old('santri_id', $prestasi->santri_id) }}">

                    <div id="santri-info" class="identity mt-3 {{ $sid ? '' : 'hidden' }}">
                        @if($santriTerisi)
                            <div class="flex items-center gap-4">
                                <div class="grid place-items-center size-14 rounded-2xl bg-brand-600 text-white font-bold text-lg shadow-md shadow-brand-600/30">
                                    {{ $santriTerisi->initials }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-lg leading-tight">{{ $santriTerisi->nama }}</p>
                                    <p class="text-sm text-slate-600 mt-0.5">NIS: <span class="font-semibold">{{ $santriTerisi->nis }}</span></p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        @if($santriTerisi->kamar)Kamar: <span class="font-semibold">{{ $santriTerisi->kamar }}</span> · @endif
                                        Jenis Kelamin: <span class="font-semibold">{{ $santriTerisi->jenis_kelamin }}</span>
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    @error('santri_id')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="tanggal">Tanggal *</label>
                    <input id="tanggal" name="tanggal" type="date" class="input"
                           value="{{ old('tanggal', $prestasi->exists ? $prestasi->tanggal->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                    @error('tanggal')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="jenis">Jenis Prestasi *</label>
                    <select id="jenis" name="jenis" class="select" required>
                        <option value="">— Pilih —</option>
                        @foreach($master['jenis_prestasi'] as $j)
                            <option value="{{ $j }}" @selected(old('jenis', $prestasi->jenis) === $j)>{{ $j }}</option>
                        @endforeach
                    </select>
                    @error('jenis')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="nama_prestasi">Nama Prestasi *</label>
                    <input id="nama_prestasi" name="nama_prestasi" type="text" class="input"
                           value="{{ old('nama_prestasi', $prestasi->nama_prestasi) }}"
                           placeholder="contoh: Juara 1 Lomba Tahfidz" required>
                    @error('nama_prestasi')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="tingkat">Tingkat *</label>
                    <select id="tingkat" name="tingkat" class="select" required>
                        <option value="">— Pilih —</option>
                        @foreach($master['tingkat_prestasi'] as $t)
                            <option value="{{ $t }}" @selected(old('tingkat', $prestasi->tingkat) === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                    @error('tingkat')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="penyelenggara">Penyelenggara</label>
                    <input id="penyelenggara" name="penyelenggara" type="text" class="input"
                           value="{{ old('penyelenggara', $prestasi->penyelenggara) }}"
                           placeholder="Nama lembaga penyelenggara">
                    @error('penyelenggara')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="poin">Poin *</label>
                    <input id="poin" name="poin" type="number" min="0" max="1000" class="input"
                           value="{{ old('poin', $prestasi->poin ?? 0) }}" required>
                    <p class="hint">Poin prestasi bersifat positif.</p>
                    @error('poin')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="bukti">Bukti</label>
                    <input id="bukti" name="bukti" type="file" accept="image/*" class="input !px-3 !py-2">
                    <p class="hint">Opsional, format gambar maksimal 4 MB.</p>
                    @error('bukti')<p class="hint text-rose-600">{{ $message }}</p>@enderror

                    @if($prestasi->bukti_url)
                        <img src="{{ $prestasi->bukti_url }}" alt="Bukti saat ini"
                             class="mt-3 size-24 rounded-2xl object-cover border border-slate-200 bg-slate-50">
                    @endif
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="3" class="input"
                              placeholder="Keterangan tambahan tentang prestasi">{{ old('keterangan', $prestasi->keterangan) }}</textarea>
                    @error('keterangan')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ====================== AKSI ====================== --}}
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pb-4">
            <a href="{{ $prestasi->exists ? route('prestasi.show', $prestasi) : route('prestasi.index') }}"
               class="btn-secondary w-full sm:w-auto">Batal</a>
            <button type="submit" class="btn-primary w-full sm:w-auto">
                <i data-lucide="save" class="size-4"></i> Simpan
            </button>
        </div>
    </form>
</div>
@endsection

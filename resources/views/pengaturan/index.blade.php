@extends('layouts.app')

@section('title', 'Pengaturan')
@section('subtitle', 'Atur poin, otomatisasi, master data, dan data aplikasi')

@section('content')
<div class="space-y-6">

    <form method="POST" action="{{ route('pengaturan.simpan') }}" class="space-y-6">
        @csrf

        {{-- ============ POIN PER STATUS ============ --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <h2 class="font-extrabold text-slate-900 tracking-tight">Poin per Status</h2>
            <p class="text-sm text-slate-500 mt-1">Berlaku untuk seluruh aktivitas, termasuk Diniyah.</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($points as $status => $poin)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                        <label for="point-{{ \Illuminate\Support\Str::slug($status) }}" class="label !mb-2">{{ $status }}</label>
                        <input id="point-{{ \Illuminate\Support\Str::slug($status) }}"
                               name="points[{{ $status }}]" type="number" min="-10" max="10"
                               value="{{ old("points.$status", $poin) }}"
                               class="input text-center font-extrabold">
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ OTOMATISASI ============ --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <h2 class="font-extrabold text-slate-900 tracking-tight">Otomatisasi Pelanggaran</h2>
            <p class="text-sm text-slate-500 mt-1">Pelanggaran dibuat otomatis ketika Santri melewati ambang Alfa.</p>

            <label class="mt-4 flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="auto_enabled" value="1" @checked($auto['enabled'])
                       class="size-4 mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>
                    <span class="block font-semibold text-slate-800">Aktifkan otomatisasi</span>
                    <span class="block text-[13px] text-slate-500">
                        Ambang: 3 Alfa dalam 7 hari (Jamaah), 3 Alfa dalam 30 hari (aktivitas lain).
                    </span>
                </span>
            </label>
        </div>

        {{-- ============ POIN PELANGGARAN ============ --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <h2 class="font-extrabold text-slate-900 tracking-tight">Poin Pelanggaran per Tingkat</h2>
            <p class="text-sm text-slate-500 mt-1">Nilai ini dipakai saat admin mencatat pelanggaran manual.</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach($pelanggaranTingkat as $tingkat => $poin)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                        <p class="font-semibold text-slate-800">{{ $tingkat }}</p>
                        <p class="text-2xl font-extrabold text-rose-600 tabular mt-1">{{ $poin }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ INFO APLIKASI ============ --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <h2 class="font-extrabold text-slate-900 tracking-tight">Info Aplikasi</h2>

            <div class="mt-4">
                <label for="nama_aplikasi" class="label">Nama Aplikasi</label>
                <input id="nama_aplikasi" name="nama_aplikasi" type="text" class="input"
                       value="{{ old('nama_aplikasi', $namaAplikasi) }}">
            </div>
        </div>

        {{-- ============ MASTER DATA ============ --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <h2 class="font-extrabold text-slate-900 tracking-tight">Master Data</h2>
            <p class="text-sm text-slate-500 mt-1">Daftar yang dipakai formulir di seluruh aplikasi.</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach([
                    'Jenis Prestasi' => $master['jenis_prestasi'],
                    'Tingkat Prestasi' => $master['tingkat_prestasi'],
                    'Jenis Pelanggaran' => $master['jenis_pelanggaran'],
                    'Tingkat Pelanggaran' => $master['tingkat_pelanggaran'],
                    'Status Aktif' => $master['status_aktif'],
                    'Jenis Kelamin' => $master['jenis_kelamin'],
                ] as $label => $daftar)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                        <p class="font-semibold text-slate-800 mb-2">{{ $label }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($daftar as $item)
                                <span class="badge-neutral">{{ $item }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                <i data-lucide="save" class="size-4"></i>
                <span>Simpan Pengaturan</span>
            </button>
        </div>
    </form>

    {{-- ============ ZONA BAHAYA ============ --}}
    <div class="card card-body animate-fade-up border-rose-200 bg-rose-50/40" data-reveal>
        <h2 class="font-extrabold text-rose-700 tracking-tight flex items-center gap-2">
            <i data-lucide="triangle-alert" class="size-5"></i>
            Zona Bahaya
        </h2>
        <p class="text-sm text-rose-700/90 mt-1">
            Tindakan di bawah ini <strong>permanen dan tidak bisa dipulihkan</strong>.
            Akun pengguna dan pengaturan aplikasi tidak ikut terhapus.
        </p>

        <div class="mt-5 rounded-2xl border border-rose-200 bg-white p-4 space-y-3">
            <div>
                <p class="font-semibold text-slate-800">Hapus Semua Data</p>
                <p class="text-[13px] text-slate-500 mt-0.5">
                    Mengosongkan seluruh data Santri beserta aktivitas, prestasi, pelanggaran,
                    khataman, relasi wali-anak, dan kode verifikasi. Cocok untuk memulai dari nol
                    di awal tahun ajaran baru.
                </p>
            </div>

            <form method="POST" action="{{ route('pengaturan.hapus-semua-data') }}"
                  class="flex flex-col sm:flex-row gap-2 sm:items-end"
                  onsubmit="return confirm('PERINGATAN: Semua data Santri dan aktivitas akan dihapus PERMANEN. Lanjutkan?')">
                @csrf
                <div class="flex-1">
                    <label for="konfirmasi" class="label !text-rose-700">Ketik "HAPUS SEMUA" untuk melanjutkan</label>
                    <input id="konfirmasi" type="text" name="konfirmasi" required
                           autocomplete="off" spellcheck="false" class="input !border-rose-300 focus:!ring-rose-500"
                           placeholder="HAPUS SEMUA">
                </div>
                <button type="submit" class="btn-danger !px-4 !py-2.5">
                    <i data-lucide="trash-2" class="size-4"></i>
                    <span>Hapus Semua Data</span>
                </button>
            </form>

            @error('konfirmasi')
                <p class="text-[13px] text-rose-600 flex items-center gap-1.5">
                    <i data-lucide="circle-alert" class="size-3.5 shrink-0"></i>{{ $message }}
                </p>
            @enderror
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Data Santri')
@section('subtitle', 'Data anak yang terhubung dengan akun wali')

@section('content')
<div class="space-y-6">
    <div class="card card-body animate-fade-up">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <div class="relative shrink-0">
                @if($santri->foto_url)
                    <img src="{{ $santri->foto_url }}" alt="Foto {{ $santri->nama }}" class="size-24 rounded-3xl object-cover shadow-lg ring-4 ring-white">
                @else
                    <div class="grid place-items-center size-24 rounded-3xl bg-gradient-to-br from-brand-500 to-brand-700 text-white font-extrabold text-2xl shadow-lg shadow-brand-500/30">
                        {{ $santri->initials }}
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-600">Data Santri</p>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1">{{ $santri->nama }}</h2>
                <p class="text-sm text-slate-500 mt-1">NIS {{ $santri->nis }} · {{ $santri->jenis_kelamin }} · {{ $santri->kamar ?: 'Kamar belum ditentukan' }}</p>
                <div class="flex flex-wrap gap-2 mt-3">
                    <span class="badge {{ $santri->status_aktif === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-50 text-slate-600 border-slate-100' }}">{{ $santri->status_aktif }}</span>
                    <span class="badge bg-brand-50 text-brand-700 border-brand-100">Data terhubung otomatis</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card card-body animate-fade-up">
            <div class="flex items-center gap-3 mb-4">
                <span class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600"><i data-lucide="id-card" class="size-5"></i></span>
                <h3 class="font-extrabold text-slate-900">Data Santri</h3>
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
                ] as [$label, $value])
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                        <dd class="text-sm font-semibold text-slate-800 text-right">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="card card-body animate-fade-up" style="animation-delay:.1s">
            <div class="flex items-center gap-3 mb-4">
                <span class="grid place-items-center size-10 rounded-xl bg-rose-50 border border-rose-100 text-rose-600"><i data-lucide="heart" class="size-5"></i></span>
                <h3 class="font-extrabold text-slate-900">Orang Tua / Wali</h3>
            </div>
            <dl class="divide-y divide-slate-100">
                @foreach([
                    ['Nama Ayah', $santri->nama_ayah ?: '-'],
                    ['Pekerjaan Ayah', $santri->pekerjaan_ayah ?: '-'],
                    ['Nama Ibu', $santri->nama_ibu ?: '-'],
                    ['Pekerjaan Ibu', $santri->pekerjaan_ibu ?: '-'],
                    ['Nama Wali', $santri->nama_wali ?: '-'],
                    ['Pekerjaan Wali', $santri->pekerjaan_wali ?: '-'],
                    ['No. HP', $santri->no_hp ?: '-'],
                    ['Alamat Wali', $santri->alamat_wali ?: '-'],
                ] as [$label, $value])
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                        <dd class="text-sm font-semibold text-slate-800 text-right">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    <div class="card card-body animate-fade-up" style="animation-delay:.15s">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="font-extrabold text-slate-900">Riwayat Khataman</h3>
                <p class="text-xs text-slate-500">Data ini hanya menampilkan khataman anak yang terhubung.</p>
            </div>
            <a href="{{ route('wali.khataman') }}" class="btn-soft">
                <i data-lucide="bookmark-check" class="size-4"></i> Lihat Riwayat
            </a>
        </div>
        @if($santri->khatamans->isEmpty())
            <p class="text-sm text-slate-500 text-center py-6">Belum ada riwayat khataman.</p>
        @else
            <div class="table-wrap">
                <table class="table-simpas">
                    <thead><tr><th>Jenis Khatam</th><th class="text-center">Tahun</th></tr></thead>
                    <tbody>
                        @foreach($santri->khatamans as $khataman)
                            <tr>
                                <td><span class="badge bg-indigo-50 text-indigo-700 border-indigo-100">{{ $khataman->jenis_khatam }}</span></td>
                                <td class="text-center font-semibold text-slate-800">{{ $khataman->tahun }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="flex flex-wrap gap-3 animate-fade-up" style="animation-delay:.2s">
        <a href="{{ route('wali.laporan', ['jenis' => 'jamaah']) }}" class="btn-soft"><i data-lucide="file-chart-column" class="size-4"></i> Laporan</a>
        <a href="{{ route('wali.prestasi') }}" class="btn-soft"><i data-lucide="trophy" class="size-4"></i> Prestasi</a>
        <a href="{{ route('wali.pelanggaran') }}" class="btn-soft"><i data-lucide="triangle-alert" class="size-4"></i> Pelanggaran</a>
    </div>
</div>
@endsection
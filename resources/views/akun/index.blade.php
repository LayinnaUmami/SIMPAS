@extends('layouts.app')

@section('title', 'Kelola Akun · ' . config('simpas.nama_aplikasi'))

@section('content')
<div class="space-y-6">

    {{-- ============ HEADER ============ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 animate-fade-down">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kelola Akun Pengguna</h1>
            <p class="text-sm text-slate-500 mt-1">
                Akun wali dibuat melalui pendaftaran Santri. Halaman ini untuk mengelola akun yang sudah ada.
            </p>
        </div>
    </div>

    {{-- ============ KARTU STATISTIK ============ --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 animate-fade-up">
        @foreach([
            ['label' => 'Total Akun', 'nilai' => $statistik['total'],    'icon' => 'users',          'warna' => 'text-slate-600 bg-slate-100'],
            ['label' => 'Admin/Pengurus', 'nilai' => $statistik['admin'], 'icon' => 'shield-check',   'warna' => 'text-emerald-700 bg-emerald-100'],
            ['label' => 'Wali Santri', 'nilai' => $statistik['wali'],      'icon' => 'user-check',     'warna' => 'text-amber-700 bg-amber-100'],
            ['label' => 'Santri', 'nilai' => $statistik['santri'],        'icon' => 'graduation-cap', 'warna' => 'text-indigo-700 bg-indigo-100'],
            ['label' => 'Nonaktif', 'nilai' => $statistik['nonaktif'],    'icon' => 'user-x',         'warna' => 'text-rose-700 bg-rose-100'],
        ] as $s)
            <div class="card p-4 flex items-center gap-3">
                <span class="grid place-items-center size-10 rounded-xl {{ $s['warna'] }} shrink-0">
                    <i data-lucide="{{ $s['icon'] }}" class="size-5"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-xl font-extrabold text-slate-900 tabular">{{ $s['nilai'] }}</p>
                    <p class="text-[11px] text-slate-500 truncate">{{ $s['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ============ FILTER ============ --}}
    <div class="card p-4 animate-fade-up">
        <form method="GET" action="{{ route('akun.index') }}" class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-slate-400 pointer-events-none"></i>
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Cari nama atau kode akun..." class="input pl-10!">
            </div>

            <select name="peran" class="input sm:w-44">
                <option value="">Semua Peran</option>
                @foreach(['admin' => 'Admin/Pengurus', 'wali' => 'Wali Santri', 'santri' => 'Santri'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('peran') === $v)>{{ $l }}</option>
                @endforeach
            </select>

            <select name="status" class="input sm:w-36">
                <option value="">Semua Status</option>
                <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
            </select>

            <button type="submit" class="btn-secondary">Filter</button>

            @if(request()->hasAny(['q', 'peran', 'status']))
                <a href="{{ route('akun.index') }}" class="btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    {{-- ============ TABEL AKUN ============ --}}
    <div class="card overflow-hidden animate-fade-up">
        @if($akun->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="user-x" class="size-10 text-slate-300 mx-auto mb-3"></i>
                <p class="font-semibold text-slate-600">Tidak ada akun yang cocok</p>
                <p class="text-sm text-slate-500 mt-1">Ubah kata kunci atau filter peran.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Akun</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Peran</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Santri Tertaut</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Login Terakhir</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($akun as $a)
                            <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid place-items-center size-9 rounded-full bg-brand-100 text-brand-700 font-bold text-xs shrink-0">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($a->name, 0, 2)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-800 truncate">{{ $a->name }}</p>
                                            <p class="text-xs text-slate-500 tabular">{{ $a->username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                        'bg-emerald-100 text-emerald-700' => $a->role === 'admin',
                                        'bg-amber-100 text-amber-700' => $a->role === 'wali',
                                        'bg-indigo-100 text-indigo-700' => $a->role === 'santri',
                                    ])>{{ $a->peran_label }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $anakAnak = $a->isWali()
                                            ? $a->santris
                                            : collect([$a->anakUtama()])->filter();
                                        $anakUtama = $anakAnak->first();
                                    @endphp
                                    @if($a->isAdmin())
                                        <span class="text-slate-400">&mdash;</span>
                                        <p class="text-xs text-slate-400">
                                            Tidak punya anak tertaut
                                        </p>
                                    @elseif($anakUtama)
                                        <div class="space-y-1">
                                            @foreach($anakAnak as $anak)
                                                <div>
                                                    <a href="{{ route('santri.show', $anak) }}" class="text-brand-700 hover:underline">
                                                        {{ $anak->nama }}
                                                    </a>
                                                    <p class="text-xs text-slate-500 tabular">NIS {{ $anak->nis }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        {{-- Wali tanpa relasi tidak boleh ada; ditampilkan agar terlihat. --}}
                                        <span class="text-rose-600 font-medium">Belum tertaut</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    @if($a->last_login_at)
                                        {{ $a->last_login_at->translatedFormat('d M Y H:i') }}
                                    @else
                                        <span class="text-slate-400">Belum pernah</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                        'bg-emerald-100 text-emerald-700' => $a->isActive(),
                                        'bg-rose-100 text-rose-700' => !$a->isActive(),
                                    ])>
                                        <i data-lucide="{{ $a->isActive() ? 'check' : 'x' }}" class="size-3"></i>
                                        {{ $a->isActive() ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('akun.edit', $a) }}" class="btn-secondary !px-2.5 !py-1.5 text-xs"
                                           aria-label="Ubah akun {{ $a->username }}">
                                            <i data-lucide="pencil" class="size-3.5"></i>
                                        </a>

                                        @if($a->isWali())
                                            <form method="POST" action="{{ route('akun.destroy', $a) }}"
                                                  data-confirm-hapus data-nama="{{ $a->name }}"
                                                  data-jumlah-anak="{{ $a->santris->count() }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-secondary !px-2.5 !py-1.5 text-xs text-rose-700"
                                                        aria-label="Hapus akun wali {{ $a->username }}">
                                                    <i data-lucide="trash-2" class="size-3.5"></i>
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

            <div class="px-4 py-3 border-t border-slate-100">
                {{ $akun->links() }}
            </div>
        @endif
    </div>

    <p class="text-xs text-slate-500">
        Menghapus akun wali melepas tautan anaknya, tetapi tidak menghapus data santri atau riwayat aktivitas.
    </p>
</div>

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('[data-confirm-hapus]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var jumlahAnak = Number(form.dataset.jumlahAnak);
                var peringatanAnak = jumlahAnak > 0
                    ? '\nAkun ini masih terhubung dengan ' + jumlahAnak + ' santri.'
                    : '';
                var pesan = 'Hapus akun wali ' + form.dataset.nama + '?' + peringatanAnak
                    + '\nData santri dan riwayatnya tetap tersimpan.';

                if (!window.confirm(pesan)) {
                    event.preventDefault();
                }
            });
        });
    })();
</script>
@endpush
@endsection

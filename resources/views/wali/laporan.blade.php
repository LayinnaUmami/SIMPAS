@extends('layouts.app')

@section('title', 'Laporan')
@section('subtitle', 'Laporan data anak yang terhubung dengan akun wali')

@section('content')
<div class="space-y-6">

    <div class="card card-body animate-fade-up">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="grid place-items-center size-14 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-500/30">
                <i data-lucide="file-chart-column" class="size-6"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Laporan {{ $isActivity ? $cfg['label'] : ($jenis === 'prestasi' ? 'Prestasi' : 'Pelanggaran') }}</h2>
                <p class="text-sm text-slate-500">{{ $santri->nama }} · NIS {{ $santri->nis }} · {{ $santri->kamar ?: 'Kamar belum ditentukan' }}</p>
            </div>
            <span class="badge badge-soft bg-brand-50 text-brand-700 border-brand-100 self-start sm:self-auto">Read-only</span>
        </div>
    </div>

    <div class="card card-body animate-fade-up" data-periode-form>
        <form method="GET" action="{{ route('wali.laporan') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="min-w-[190px]">
                <label for="jenis" class="label">Jenis Laporan</label>
                <select id="jenis" name="jenis" class="select" onchange="this.form.submit()">
                    @foreach($jenisList as $value => $label)
                        <option value="{{ $value }}" {{ $jenis === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[170px]">
                <label for="periode" class="label">Periode</label>
                <select id="periode" name="periode" class="select">
                    @foreach([
                        'hari' => 'Hari',
                        'minggu' => 'Minggu',
                        'bulan' => 'Bulan',
                        'tahun' => 'Tahun',
                        'custom' => 'Custom',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ $periode['jenis'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[150px]" data-periode-field="hari">
                <label for="tanggal" class="label">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" value="{{ $filter['tanggal'] }}" class="input">
            </div>

            <div class="min-w-[130px]" data-periode-field="minggu">
                <label for="minggu" class="label">Minggu ke-</label>
                <input type="number" id="minggu" name="minggu" min="1" max="53" value="{{ $filter['minggu'] }}" class="input">
            </div>

            <div class="min-w-[145px]" data-periode-field="bulan">
                <label for="bulan" class="label">Bulan</label>
                <select id="bulan" name="bulan" class="select">
                    @foreach(\App\Services\PeriodResolver::BULAN as $number => $name)
                        <option value="{{ $number }}" {{ (int) $filter['bulan'] === (int) $number ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[120px]" data-periode-field="minggu,bulan,tahun">
                <label for="tahun" class="label">Tahun</label>
                <input type="number" id="tahun" name="tahun" min="1950" max="2100" value="{{ $filter['tahun'] }}" class="input">
            </div>

            <div class="min-w-[160px] sm:col-span-2" data-periode-field="custom">
                <label for="mulai" class="label">Dari Tanggal</label>
                <input type="date" id="mulai" name="mulai" value="{{ $filter['mulai'] }}" class="input">
            </div>
            <div class="min-w-[160px] sm:col-span-2" data-periode-field="custom">
                <label for="akhir" class="label">Sampai Tanggal</label>
                <input type="date" id="akhir" name="akhir" value="{{ $filter['akhir'] }}" class="input">
            </div>

            <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary">
                    <i data-lucide="list-filter" class="size-4"></i> Tampilkan
                </button>
                <a href="{{ route('wali.laporan', ['jenis' => $jenis]) }}" class="btn-secondary">
                    <i data-lucide="rotate-ccw" class="size-4"></i> Reset
                </a>
                <span class="text-xs text-slate-500 flex items-center gap-1.5">
                    <i data-lucide="shield" class="size-4 text-emerald-500"></i>
                    Data otomatis dari akun wali — hanya data anak yang terhubung.
                </span>
            </div>
        </form>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">{{ $isActivity ? $cfg['label'] : ucfirst($jenis) }}</h2>
            <p class="text-xs text-slate-500">{{ $periode['label'] }}</p>
        </div>
        <span class="badge badge-soft bg-brand-50 text-brand-700 border-brand-100">{{ $keterangan['jumlah_baris'] ?? $keterangan['total_data'] ?? $rows->count() }} baris data</span>
    </div>

    <div class="card overflow-hidden animate-fade-up">
        <div class="overflow-x-auto scrollbar-slim">
            @if($isActivity)
                <table class="table-simpas" style="min-width: {{ 420 + count($cfg['sessions']) * 120 }}px">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">No</th>
                            <th class="w-28">Tanggal</th>
                            <th>Santri</th>
                            @foreach($cfg['sessions'] as $session)
                                <th class="text-center">{{ $session }}</th>
                            @endforeach
                            <th class="text-center">Total Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                            <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.02, 0.3) }}s">
                                <td class="text-center text-slate-400 font-semibold">{{ $i + 1 }}</td>
                                <td class="text-slate-600 text-sm whitespace-nowrap">{{ $row['tanggal']->format('d M Y') }}</td>
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $row['santri']->nama }}</p>
                                    <p class="text-[11px] text-slate-500">NIS {{ $row['santri']->nis }}</p>
                                </td>
                                @foreach($cfg['sessions'] as $session)
                                    @php($status = $row['sesi'][$session] ?? null)
                                    <td class="text-center">
                                        @if($status)
                                            <span class="badge text-[10px] !px-2
                                                @switch($status)
                                                    @case('Hadir') bg-emerald-50 text-emerald-700 border-emerald-100 @break
                                                    @case('Alfa') bg-rose-50 text-rose-700 border-rose-100 @break
                                                    @case('Izin') bg-sky-50 text-sky-700 border-sky-100 @break
                                                    @case('Sakit') bg-amber-50 text-amber-700 border-amber-100 @break
                                                    @case('Terlambat') bg-orange-50 text-orange-700 border-orange-100 @break
                                                    @case('Udzur') bg-violet-50 text-violet-700 border-violet-100 @break
                                                    @default bg-slate-50 text-slate-600 border-slate-100
                                                @endswitch">{{ $status }}</span>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center font-extrabold {{ $row['total'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $row['total'] >= 0 ? '+' : '' }}{{ $row['total'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + count($cfg['sessions']) }}" class="text-center py-14">
                                    <i data-lucide="clipboard-check" class="size-10 mx-auto text-slate-300 mb-3"></i>
                                    <p class="font-semibold text-slate-600">Tidak ada data pada periode ini</p>
                                    <p class="text-sm text-slate-400 mt-1">Ubah periode atau reset filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">No</th>
                            <th class="w-28">Tanggal</th>
                            @if($jenis === 'prestasi')
                                <th>Prestasi</th>
                                <th>Jenis</th>
                                <th class="text-center">Tingkat</th>
                                <th class="text-center">Poin</th>
                            @else
                                <th>Pelanggaran</th>
                                <th class="text-center">Tingkat</th>
                                <th class="text-center">Poin</th>
                                <th class="text-center">Sumber</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                            <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.02, 0.3) }}s">
                                <td class="text-center text-slate-400 font-semibold">{{ $i + 1 }}</td>
                                <td class="text-slate-600 text-sm whitespace-nowrap">{{ $row->tanggal->format('d M Y') }}</td>
                                @if($jenis === 'prestasi')
                                    <td>
                                        <p class="font-semibold text-slate-800">{{ $row->nama_prestasi }}</p>
                                        @if($row->keterangan)<p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($row->keterangan, 90) }}</p>@endif
                                    </td>
                                    <td><span class="badge bg-amber-50 text-amber-700 border-amber-100">{{ $row->jenis }}</span></td>
                                    <td class="text-center"><span class="badge bg-violet-50 text-violet-700 border-violet-100">{{ $row->tingkat }}</span></td>
                                    <td class="text-center font-extrabold text-emerald-600">+{{ $row->poin }}</td>
                                @else
                                    <td>
                                        <p class="font-semibold text-slate-800">{{ $row->jenis }}</p>
                                        @if($row->keterangan)<p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($row->keterangan, 90) }}</p>@endif
                                    </td>
                                    <td class="text-center"><span class="badge {{ $row->tingkat === 'Berat' ? 'bg-rose-50 text-rose-700 border-rose-100' : ($row->tingkat === 'Sedang' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-sky-50 text-sky-700 border-sky-100') }}">{{ $row->tingkat }}</span></td>
                                    <td class="text-center font-extrabold text-rose-600">{{ $row->poin }}</td>
                                    <td class="text-center"><span class="badge {{ $row->is_auto ? 'bg-slate-50 text-slate-600 border-slate-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100' }}">{{ $row->is_auto ? 'Otomatis' : 'Dicatat Pengurus' }}</span></td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $jenis === 'prestasi' ? 6 : 7 }}" class="text-center py-14">
                                    <i data-lucide="{{ $jenis === 'prestasi' ? 'trophy' : 'triangle-alert' }}" class="size-10 mx-auto text-slate-300 mb-3"></i>
                                    <p class="font-semibold text-slate-600">Tidak ada data pada periode ini</p>
                                    <p class="text-sm text-slate-400 mt-1">Ubah periode atau reset filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card overflow-hidden animate-fade-up" style="animation-delay:.15s">
        <div class="p-5 border-b border-slate-100 flex items-center gap-3">
            <span class="grid place-items-center size-10 rounded-2xl bg-slate-50 border border-slate-200 text-slate-600">
                <i data-lucide="clipboard-list" class="size-5"></i>
            </span>
            <div>
                <h3 class="font-extrabold text-slate-900 tracking-tight">Keterangan Laporan</h3>
                <p class="text-xs text-slate-500">Dihitung otomatis dari data {{ $santri->nama }}</p>
            </div>
        </div>

        <div class="p-5 grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700">Periode</p>
                    <p class="text-sm font-semibold text-slate-800 mt-1">{{ $periode['label'] }}</p>
                </div>

                @if($isActivity)
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                            Rekap Status Absensi
                        </p>
                        <div class="table-wrap">
                            <table class="table-simpas !min-w-0">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($keterangan['status'] as $status => $jumlah)
                                        <tr>
                                            <td>
                                                <span class="badge
                                                    @switch($status)
                                                        @case('Hadir') bg-emerald-50 text-emerald-700 border-emerald-100 @break
                                                        @case('Alfa') bg-rose-50 text-rose-700 border-rose-100 @break
                                                        @case('Izin') bg-sky-50 text-sky-700 border-sky-100 @break
                                                        @case('Sakit') bg-amber-50 text-amber-700 border-amber-100 @break
                                                        @case('Terlambat') bg-orange-50 text-orange-700 border-orange-100 @break
                                                        @case('Udzur') bg-violet-50 text-violet-700 border-violet-100 @break
                                                        @default bg-slate-50 text-slate-600 border-slate-100
                                                    @endswitch">{{ $status }}</span>
                                            </td>
                                            <td class="text-right font-extrabold text-slate-800">{{ $jumlah }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-sm text-slate-600">Jumlah data</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-1">{{ $keterangan['total_data'] }}</p>
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                <div class="rounded-2xl border border-slate-200 p-4 flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-600">{{ $isActivity ? 'Total Absensi' : 'Total Data' }}</span>
                    <span class="text-2xl font-extrabold text-slate-900">{{ $isActivity ? $keterangan['total_absensi'] : $keterangan['total_data'] }}</span>
                </div>
                <div class="rounded-2xl border {{ $keterangan['total_poin'] >= 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-rose-50 border-rose-100' }} p-4 flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-600">Total Poin</span>
                    <span class="text-3xl font-extrabold {{ $keterangan['total_poin'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $keterangan['total_poin'] >= 0 ? '+' : '' }}{{ $keterangan['total_poin'] }}
                    </span>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-xs text-slate-500 leading-relaxed">
                    <p class="flex items-start gap-2">
                        <i data-lucide="info" class="size-4 mt-0.5 text-sky-500 shrink-0"></i>
                        Angka rekap dihitung otomatis. Wali hanya melihat data anak yang terhubung dengan akun ini.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
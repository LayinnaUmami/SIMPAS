@extends('layouts.app')

@section('title', 'Laporan ' . $label)
@section('subtitle', $periode['label'])

@section('content')

    {{-- ================= FILTER ================= --}}
    <div class="card card-body mb-5 no-print animate-fade-up">
        <form method="GET" action="{{ route('laporan.show', $jenis) }}" class="space-y-4" data-periode-form>
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[170px]">
                    <label for="periode" class="label">Periode</label>
                    <select name="periode" id="periode" class="select">
                        @foreach(['hari' => 'Hari', 'minggu' => 'Minggu', 'bulan' => 'Bulan', 'tahun' => 'Tahun', 'custom' => 'Custom'] as $val => $lbl)
                            <option value="{{ $val }}" {{ ($input['periode'] ?? 'minggu') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-[160px]" data-periode-field="hari">
                    <label for="tanggal" class="label">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ $input['tanggal'] ?? now()->format('Y-m-d') }}" class="input">
                </div>

                <div class="min-w-[130px]" data-periode-field="minggu">
                    <label for="minggu" class="label">Minggu ke-</label>
                    <input type="number" name="minggu" id="minggu" min="1" max="53" value="{{ $minggu }}" class="input">
                </div>

                <div class="min-w-[150px]" data-periode-field="bulan">
                    <label for="bulan" class="label">Bulan</label>
                    <select name="bulan" id="bulan" class="select">
                        @foreach(\App\Services\PeriodResolver::BULAN as $no => $nm)
                            <option value="{{ $no }}" {{ $bulan === $no ? 'selected' : '' }}>{{ $nm }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-[120px]" data-periode-field="minggu,bulan,tahun">
                    <label for="tahun" class="label">Tahun</label>
                    <input type="number" name="tahun" id="tahun" min="2020" max="2100" value="{{ $input['tahun'] ?? $tahun }}" class="input">
                </div>

                <div class="min-w-[150px]" data-periode-field="custom">
                    <label for="mulai" class="label">Dari Tanggal</label>
                    <input type="date" name="mulai" id="mulai" value="{{ $input['mulai'] ?? '' }}" class="input">
                </div>
                <div class="min-w-[150px]" data-periode-field="custom">
                    <label for="akhir" class="label">Sampai Tanggal</label>
                    <input type="date" name="akhir" id="akhir" value="{{ $input['akhir'] ?? '' }}" class="input">
                </div>

                <div class="min-w-[200px] flex-1">
                    <label for="q" class="label">Cari Nama / NIS</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                        <input type="text" name="q" id="q" value="{{ $cari ?? '' }}" placeholder="Ketik nama atau NIS..."
                               class="input pl-11!" autocomplete="off">
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i data-lucide="list-filter" class="size-4"></i> Terapkan
                </button>
                <a href="{{ route('laporan.show', $jenis) }}" class="btn-secondary">Reset</a>
            </div>
        </form>

        {{-- Aksi cetak / export mengikuti filter aktif --}}
        <div class="flex flex-wrap items-center gap-2 pt-4 mt-4 border-t border-slate-100">
            <span class="text-xs font-semibold text-slate-500 mr-1 flex items-center gap-1.5">
                <i data-lucide="info" class="size-4 text-sky-500"></i>
                Print & Excel mengikuti filter yang sedang digunakan
            </span>
            <a href="{{ route('laporan.print', array_merge(['jenis' => $jenis], request()->query())) }}"
               class="btn-secondary ml-auto" target="_blank">
                <i data-lucide="printer" class="size-4"></i> Print
            </a>
            <a href="{{ route('laporan.excel', array_merge(['jenis' => $jenis], request()->query())) }}"
               class="btn-primary">
                <i data-lucide="file-spreadsheet" class="size-4"></i> Export Excel
            </a>
        </div>
    </div>

    {{-- ================= INFO LAPORAN ================= --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 animate-fade-up">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center size-11 rounded-2xl border
                         @if($jenis === 'prestasi') bg-green-50 border-green-100 text-green-600
                         @elseif($jenis === 'pelanggaran') bg-red-50 border-red-100 text-red-600
                         @else bg-brand-50 border-brand-100 text-brand-600 @endif">
                <i data-lucide="{{ $icon }}" class="size-5"></i>
            </span>
            <div>
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">LAPORAN {{ strtoupper($label) }}</h2>
                <p class="text-xs text-slate-500">{{ $periode['label'] }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="badge badge-netral">{{ $keterangan['jumlah_baris'] }} baris data</span>
            <span class="badge badge-hadir"><i data-lucide="check" class="size-3.5"></i> Total Poin {{ $keterangan['total_poin'] >= 0 ? '+' : '' }}{{ $keterangan['total_poin'] }}</span>
        </div>
    </div>

    {{-- ================= TABEL ABSENSI ================= --}}
    <div class="card overflow-hidden mb-5 animate-fade-up" style="animation-delay:.1s">
        <div class="overflow-x-auto scrollbar-slim">
            <table class="table-simpas" style="min-width: {{ ($isActivity ? 260 + count($sessions) * 110 : 760) }}px">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        @if(!$isActivity)
                            <th>Tanggal</th>
                        @endif
                        <th class="w-28">NIS</th>
                        <th>Nama Santri</th>
                        @if($isActivity)
                            <th>Tanggal</th>
                            @foreach($sessions as $s)
                                <th class="text-center">{{ $s }}</th>
                            @endforeach
                            <th class="text-center">Total Poin</th>
                        @elseif($jenis === 'prestasi')
                            <th>Prestasi</th>
                            <th>Tingkat</th>
                            <th>Keterangan</th>
                            <th class="text-center">Poin</th>
                        @else
                            <th>Jenis Pelanggaran</th>
                            <th>Tingkat</th>
                            <th>Keterangan</th>
                            <th class="text-center">Poin</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $i => $row)
                    @if($isActivity)
                        @php $santri = $row['santri']; @endphp
                        <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.015, 0.3) }}s">
                            <td class="text-center text-slate-400 font-semibold">{{ $i + 1 }}</td>
                            <td class="font-mono text-xs text-slate-600">{{ $santri?->nis ?? '-' }}</td>
                            <td class="font-semibold text-slate-800">{{ $santri?->nama ?? '-' }}</td>
                            <td class="whitespace-nowrap text-slate-600">{{ $row['tanggal']->format('d M Y') }}</td>
                            @foreach($sessions as $s)
                                @php $status = $row['sesi'][$s] ?? null; @endphp
                                <td class="text-center">
                                    @if($status)
                                        <span class="badge text-[10px] !px-2
                                            @switch($status)
                                                @case('Hadir') badge-hadir @break
                                                @case('Alfa') badge-alfa @break
                                                @case('Izin') badge-izin @break
                                                @case('Sakit') badge-sakit @break
                                                @case('Terlambat') badge-terlambat @break
                                                @case('Udzur') badge-udzur @break
                                                @default badge-libur
                                            @endswitch">{{ $status }}</span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center">
                                <span class="font-extrabold {{ $row['total'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $row['total'] >= 0 ? '+' : '' }}{{ $row['total'] }}
                                </span>
                            </td>
                        </tr>
                    @else
                        @php $santri = $row->santri; @endphp
                        <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.015, 0.3) }}s">
                            <td class="text-center text-slate-400 font-semibold">{{ $i + 1 }}</td>
                            <td class="whitespace-nowrap text-slate-600">{{ $row->tanggal->format('d M Y') }}</td>
                            <td class="font-mono text-xs text-slate-600">{{ $santri?->nis ?? '-' }}</td>
                            <td class="font-semibold text-slate-800">{{ $santri?->nama ?? '-' }}</td>
                            @if($jenis === 'prestasi')
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $row->nama_prestasi }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $row->jenis }}</p>
                                </td>
                                <td><span class="badge badge-hadir">{{ $row->tingkat }}</span></td>
                                <td class="text-slate-600 max-w-[260px]">{{ \Illuminate\Support\Str::limit($row->keterangan ?? '-', 80) }}</td>
                                <td class="text-center"><span class="font-extrabold text-emerald-600">+{{ $row->poin }}</span></td>
                            @else
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $row->jenis }}</p>
                                    @if($row->is_auto)<p class="text-[11px] text-slate-400">Otomatis</p>@endif
                                </td>
                                <td>
                                    <span class="badge {{ $row->tingkat === 'Berat' ? 'badge-alfa' : ($row->tingkat === 'Sedang' ? 'badge-terlambat' : 'badge-sakit') }}">
                                        {{ $row->tingkat }}
                                    </span>
                                </td>
                                <td class="text-slate-600 max-w-[260px]">{{ \Illuminate\Support\Str::limit($row->keterangan ?? '-', 80) }}</td>
                                <td class="text-center"><span class="font-extrabold text-rose-600">{{ $row->poin }}</span></td>
                            @endif
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $isActivity ? 5 + count($sessions) : 8 }}" class="text-center py-14">
                            <i data-lucide="file-chart-column" class="size-10 mx-auto text-slate-300 mb-3"></i>
                            <p class="font-semibold text-slate-600">Tidak ada data pada periode ini</p>
                            <p class="text-sm text-slate-400 mt-1">Ubah periode atau kata kunci pencarian.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= KETERANGAN LAPORAN (otomatis, di bawah tabel) ================= --}}
    <div class="card overflow-hidden animate-fade-up" style="animation-delay:.2s">
        <div class="p-5 border-b border-slate-100 flex items-center gap-3">
            <span class="grid place-items-center size-10 rounded-2xl bg-slate-50 border border-slate-200 text-slate-600">
                <i data-lucide="clipboard-list" class="size-5"></i>
            </span>
            <div>
                <h3 class="font-extrabold text-slate-900 tracking-tight">Keterangan Laporan</h3>
                <p class="text-xs text-slate-500">Dihitung otomatis dari data yang ada</p>
            </div>
        </div>

        <div class="p-5 grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700">Periode</p>
                    <p class="text-sm font-semibold text-slate-800 mt-1">{{ $periode['label'] }}</p>
                    @if($cari)
                        <p class="text-xs text-slate-500 mt-1">Pencarian: <b>{{ $cari }}</b></p>
                    @endif
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
                                                        @case('Hadir') badge-hadir @break
                                                        @case('Alfa') badge-alfa @break
                                                        @case('Izin') badge-izin @break
                                                        @case('Sakit') badge-sakit @break
                                                        @case('Terlambat') badge-terlambat @break
                                                        @case('Udzur') badge-udzur @break
                                                        @default badge-libur
                                                    @endswitch">{{ $status }}</span>
                                            </td>
                                            <td class="text-right font-extrabold text-slate-800">{{ $jumlah }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif($jenis === 'prestasi')
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Rekap Jenis Prestasi</p>
                        <div class="table-wrap">
                            <table class="table-simpas !min-w-0">
                                <thead><tr><th>Jenis</th><th class="text-right">Jumlah</th></tr></thead>
                                <tbody>
                                    @foreach($keterangan['per_jenis'] as $j => $jumlah)
                                        <tr><td>{{ $j }}</td><td class="text-right font-extrabold text-slate-800">{{ $jumlah }}</td></tr>
                                    @endforeach
                                    @if(empty($keterangan['per_jenis']))
                                        <tr><td colspan="2" class="text-center text-slate-400">Tidak ada data</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Rekap Tingkat Pelanggaran</p>
                        <div class="table-wrap">
                            <table class="table-simpas !min-w-0">
                                <thead><tr><th>Tingkat</th><th class="text-right">Jumlah</th></tr></thead>
                                <tbody>
                                    @foreach($keterangan['per_tingkat'] as $t => $jumlah)
                                        <tr><td>{{ $t }}</td><td class="text-right font-extrabold text-slate-800">{{ $jumlah }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Total --}}
            <div class="space-y-3">
                <div class="rounded-2xl border border-slate-200 p-4 flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-600">Total Absensi</span>
                    <span class="text-2xl font-extrabold text-slate-900" data-count="{{ $keterangan['total_absensi'] }}">0</span>
                </div>
                <div class="rounded-2xl border {{ $keterangan['total_poin'] >= 0 ? 'border-emerald-200 bg-emerald-50/60' : 'border-rose-200 bg-rose-50/60' }} p-4 flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-600">Total Poin</span>
                    <span class="text-3xl font-extrabold {{ $keterangan['total_poin'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $keterangan['total_poin'] >= 0 ? '+' : '' }}{{ $keterangan['total_poin'] }}
                    </span>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-xs text-slate-500 leading-relaxed">
                    <p class="flex items-start gap-2">
                        <i data-lucide="info" class="size-4 mt-0.5 text-sky-500 shrink-0"></i>
                        Seluruh angka rekap di atas dihitung otomatis dari data absensi pada periode terpilih.
                        Admin tidak perlu mengetik jumlah Hadir, Izin, Sakit, Udzur, Terlambat, maupun Alfa.
                    </p>
                    @if($jenis === 'diniyah')
                        <p class="flex items-start gap-2 mt-2">
                            <i data-lucide="graduation-cap" class="size-4 mt-0.5 text-indigo-500 shrink-0"></i>
                            <span><b class="text-indigo-700">Poin Diniyah hanya untuk laporan Diniyah</b> — tidak masuk
                            ranking, poin aktivitas, prestasi, maupun pelanggaran.</span>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

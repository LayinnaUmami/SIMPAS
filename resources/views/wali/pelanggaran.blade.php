@extends('layouts.app')

@section('title', 'Pelanggaran')
@section('subtitle', 'Catatan pelanggaran anak yang terhubung dengan akun wali')

@section('content')
<div class="space-y-6">

    <div class="card card-body animate-fade-up">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="grid place-items-center size-14 rounded-2xl bg-gradient-to-br from-rose-400 to-rose-600 text-white shadow-lg shadow-rose-500/30">
                <i data-lucide="triangle-alert" class="size-6"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Pelanggaran Anak</h2>
                <p class="text-sm text-slate-500">{{ $santri->nama }} · NIS {{ $santri->nis }} · {{ $santri->kamar ?: 'Kamar belum ditentukan' }}</p>
            </div>
            <span class="badge badge-soft bg-rose-50 text-rose-700 border-rose-100 self-start sm:self-auto">Read-only</span>
        </div>
    </div>

    <div class="card card-body animate-fade-up" data-periode-form>
        <form method="GET" action="{{ route('wali.pelanggaran') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="min-w-[170px]">
                <label for="periode" class="label">Periode</label>
                <select id="periode" name="periode" class="select">
                    @foreach([
                        'semua' => 'Semua',
                        'hari' => 'Hari',
                        'minggu' => 'Minggu',
                        'bulan' => 'Bulan',
                        'tahun' => 'Tahun',
                        'custom' => 'Custom',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ $periode === $value ? 'selected' : '' }}>{{ $label }}</option>
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
                <a href="{{ route('wali.pelanggaran') }}" class="btn-secondary">
                    <i data-lucide="rotate-ccw" class="size-4"></i> Reset
                </a>
            </div>
        </form>
    </div>

    @if($pelanggaran->isEmpty())
        <div class="card card-body text-center py-16 animate-scale-in">
            <div class="grid place-items-center size-16 rounded-3xl bg-rose-50 text-rose-400 mx-auto">
                <i data-lucide="triangle-alert" class="size-8"></i>
            </div>
            <p class="font-bold text-slate-800 mt-4">Belum ada pelanggaran</p>
            <p class="text-sm text-slate-500 mt-1">Tidak ada catatan pelanggaran anak pada periode ini.</p>
        </div>
    @else
        <div class="card overflow-hidden animate-fade-up">
            <div class="overflow-x-auto scrollbar-slim">
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">No</th>
                            <th class="w-28">Tanggal</th>
                            <th>Pelanggaran</th>
                            <th class="text-center">Tingkat</th>
                            <th class="text-center">Poin</th>
                            <th class="w-28 text-center">Sumber</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pelanggaran as $i => $p)
                            <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.02, 0.3) }}s">
                                <td class="text-center text-slate-400 font-semibold">{{ $pelanggaran->firstItem() + $i }}</td>
                                <td class="text-slate-600 text-sm whitespace-nowrap">{{ $p->tanggal->format('d M Y') }}</td>
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $p->jenis }}</p>
                                    @if($p->keterangan)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ \Illuminate\Support\Str::limit($p->keterangan, 100) }}</p>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $p->tingkat === 'Berat' ? 'bg-rose-50 text-rose-700 border-rose-100' : ($p->tingkat === 'Sedang' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-sky-50 text-sky-700 border-sky-100') }}">{{ $p->tingkat }}</span>
                                </td>
                                <td class="text-center font-extrabold text-rose-600">{{ $p->poin }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $p->is_auto ? 'bg-slate-50 text-slate-600 border-slate-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100' }}">{{ $p->is_auto ? 'Otomatis' : 'Dicatat Pengurus' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50">
                <p class="text-sm text-slate-500">
                    Menampilkan {{ $pelanggaran->firstItem() ?? 0 }}–{{ $pelanggaran->lastItem() ?? 0 }} dari {{ $pelanggaran->total() }} data
                </p>
                {{ $pelanggaran->links('pagination::tailwind') }}
            </div>
        </div>
    @endif
</div>
@endsection
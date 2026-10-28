@extends('layouts.app')

@section('title', 'Prestasi')
@section('subtitle', 'Prestasi anak yang terhubung dengan akun wali')

@section('content')
<div class="space-y-6">

    <div class="card card-body animate-fade-up">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="grid place-items-center size-14 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg shadow-amber-500/30">
                <i data-lucide="trophy" class="size-6"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Prestasi Anak</h2>
                <p class="text-sm text-slate-500">{{ $santri->nama }} · NIS {{ $santri->nis }} · {{ $santri->kamar ?: 'Kamar belum ditentukan' }}</p>
            </div>
            <span class="badge badge-soft bg-amber-50 text-amber-700 border-amber-100 self-start sm:self-auto">Read-only</span>
        </div>
    </div>

    <div class="card card-body animate-fade-up" data-periode-form>
        <form method="GET" action="{{ route('wali.prestasi') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[180px]">
                <label for="periode" class="label">Periode</label>
                <select id="periode" name="periode" class="select">
                    <option value="semua" {{ $periode === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="tahun" {{ $periode === 'tahun' ? 'selected' : '' }}>Tahun</option>
                </select>
            </div>

            <div class="min-w-[150px]" data-periode-field="tahun">
                <label for="tahun" class="label">Tahun</label>
                <select id="tahun" name="tahun" class="select">
                    @foreach($tahunOptions as $option)
                        <option value="{{ $option }}" {{ (int) $tahun === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary">
                <i data-lucide="list-filter" class="size-4"></i> Tampilkan
            </button>
            <a href="{{ route('wali.prestasi') }}" class="btn-secondary">
                <i data-lucide="rotate-ccw" class="size-4"></i> Reset
            </a>
        </form>
    </div>

    @if($prestasi->isEmpty())
        <div class="card card-body text-center py-16 animate-scale-in">
            <div class="grid place-items-center size-16 rounded-3xl bg-amber-50 text-amber-400 mx-auto">
                <i data-lucide="trophy" class="size-8"></i>
            </div>
            <p class="font-bold text-slate-800 mt-4">Belum ada prestasi</p>
            <p class="text-sm text-slate-500 mt-1">Tidak ada prestasi anak pada periode ini.</p>
        </div>
    @else
        <div class="card overflow-hidden animate-fade-up">
            <div class="overflow-x-auto scrollbar-slim">
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">No</th>
                            <th class="w-28">Tanggal</th>
                            <th>Prestasi</th>
                            <th>Jenis</th>
                            <th class="text-center">Tingkat</th>
                            <th class="text-center">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prestasi as $i => $p)
                            <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.02, 0.3) }}s">
                                <td class="text-center text-slate-400 font-semibold">{{ $prestasi->firstItem() + $i }}</td>
                                <td class="text-slate-600 text-sm whitespace-nowrap">{{ $p->tanggal->format('d M Y') }}</td>
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $p->nama_prestasi }}</p>
                                    @if($p->keterangan)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ \Illuminate\Support\Str::limit($p->keterangan, 90) }}</p>
                                    @endif
                                </td>
                                <td><span class="badge bg-amber-50 text-amber-700 border-amber-100">{{ $p->jenis }}</span></td>
                                <td class="text-center"><span class="badge bg-violet-50 text-violet-700 border-violet-100">{{ $p->tingkat }}</span></td>
                                <td class="text-center font-extrabold text-emerald-600">+{{ $p->poin }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50">
                <p class="text-sm text-slate-500">
                    Menampilkan {{ $prestasi->firstItem() ?? 0 }}–{{ $prestasi->lastItem() ?? 0 }} dari {{ $prestasi->total() }} data
                </p>
                {{ $prestasi->links('pagination::tailwind') }}
            </div>
        </div>
    @endif
</div>
@endsection
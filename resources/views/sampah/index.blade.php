@extends('layouts.app')
@section('title', 'Sampah')
@section('subtitle', 'Data terhapus yang masih dapat dipulihkan')

@section('content')
<div class="space-y-6">

    {{-- ====================== CATATAN PENTING ====================== --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5 no-print" data-reveal>
        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 animate-fade-up" data-reveal>
            <i data-lucide="info" class="size-4 mt-0.5 shrink-0 text-slate-400"></i>
            <p>Data di Sampah tidak muncul di Riwayat, Laporan, dan tidak dihitung dalam Ranking.</        </p>
        </div>

        @if(auth()->user()->isAdmin())
            <div class="ml-auto">
                <form method="POST" action="{{ route('sampah.hapus-semua', $tipe) }}"
                      onsubmit="return confirm('PERHATIAN: Ini akan menghapus SEMUA data secara PERMANEN dan tidak bisa dipulihkan. Lanjutkan?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger btn-sm">
                        <i data-lucide="trash" class="size-4"></i> Hapus Semua Data
                    </button>
                </form>
            </div>
        @endif

    {{-- ====================== CHIPS FILTER ====================== --}}
    <div class="flex flex-wrap gap-2 no-print" data-reveal>
        <a href="{{ route('sampah.index', ['tipe' => 'semua']) }}"
           class="badge !py-1.5 !px-4 transition-colors {{ $tipe === 'semua'
               ? 'bg-brand-600 text-white border-brand-600'
               : 'bg-white text-slate-600 border-slate-200 hover:border-brand-300 hover:text-brand-700' }}">
            Semua
        </a>

        @foreach($registry as $key => $conf)
            <a href="{{ route('sampah.index', ['tipe' => $key]) }}"
               class="badge !py-1.5 !px-4 transition-colors {{ $tipe === $key
                   ? 'bg-brand-600 text-white border-brand-600'
                   : 'bg-white text-slate-600 border-slate-200 hover:border-brand-300 hover:text-brand-700' }}">
                {{ $conf['label'] }}
            </a>
        @endforeach
    </div>

    {{-- ====================== TABEL ====================== --}}
    @if($items->isEmpty())
        <div class="card card-body text-center py-16 animate-scale-in">
            <div class="grid place-items-center size-16 rounded-3xl bg-slate-100 text-slate-400 mx-auto">
                <i data-lucide="archive-restore" class="size-8"></i>
            </div>
            <p class="font-bold text-slate-800 mt-4">Sampah kosong</p>
            <p class="text-sm text-slate-500 mt-1">Belum ada data yang dihapus pada filter ini.</p>
        </div>
    @else
        <div class="card card-body !p-0" data-reveal>
            <div class="table-wrap !border-0 !rounded-3xl">
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-14">No</th>
                            <th>Jenis Data</th>
                            <th>Data</th>
                            <th>Dihapus Pada</th>
                            <th class="no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                            <tr>
                                <td class="text-slate-400">{{ $i + 1 }}</td>
                                <td>
                                    <span class="badge badge-netral">
                                        <i data-lucide="folder-open" class="size-3"></i>
                                        {{ $item->tipe_label }}
                                    </span>
                                </td>
                                <td>
                                    @if($item instanceof \App\Models\Santri)
                                        <span class="font-semibold text-slate-800">{{ $item->nama }}</span>
                                        <span class="text-xs text-slate-500"> · NIS {{ $item->nis }}</span>

                                    @elseif($item instanceof \App\Models\Prestasi)
                                        <span class="font-semibold text-slate-800">{{ $item->nama_prestasi }}</span>

                                    @elseif($item instanceof \App\Models\Pelanggaran)
                                        <span class="font-semibold text-slate-800">{{ $item->jenis }}</span>

                                    @elseif($item instanceof \App\Models\Khataman)
                                        <span class="font-semibold text-slate-800">{{ $item->jenis_khatam }}</span>
                                        <span class="text-xs text-slate-500"> · {{ $item->tahun }}</span>

                                    @elseif($item instanceof \App\Models\BaseActivity)
                                        @php
                                            $statusBadge = [
                                                'Hadir' => 'badge-hadir', 'Izin' => 'badge-izin', 'Sakit' => 'badge-sakit',
                                                'Udzur' => 'badge-udzur', 'Terlambat' => 'badge-terlambat',
                                                'Alfa' => 'badge-alfa', 'Libur' => 'badge-libur',
                                            ][$item->status] ?? 'badge-netral';
                                        @endphp
                                        <span class="font-semibold text-slate-800">{{ $item->santri?->nama ?? '-' }}</span>
                                        <span class="text-xs text-slate-500"> · {{ $item->tanggal?->format('d M Y') }} · {{ $item->sesi }}</span>
                                        <span class="badge {{ $statusBadge }} !py-0.5 !px-2 !text-[10px] mt-1">{{ $item->status }}</span>

                                    @else
                                        <span class="text-slate-600">-</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-slate-500">
                                    {{ $item->deleted_at?->format('d M Y H:i') ?? '-' }}
                                </td>
                                <td class="no-print">
                                    @if(auth()->user()->isAdmin())
                                        <div class="flex flex-wrap items-center gap-2">
                                            <form method="POST" action="{{ route('sampah.restore', [$item->tipe, $item->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn-soft btn-sm">
                                                    <i data-lucide="rotate-ccw" class="size-4"></i> Pulihkan
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('sampah.permanen', [$item->tipe, $item->id]) }}"
                                                  data-confirm="Data akan dihapus PERMANEN dan tidak dapat dipulihkan. Lanjutkan?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger btn-sm">
                                                    <i data-lucide="trash-2" class="size-4"></i> Hapus Permanen
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

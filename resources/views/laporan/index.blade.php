@extends('layouts.app')

@section('title', 'Laporan')
@section('subtitle', 'Pilih jenis laporan yang ingin dilihat')

@section('content')

    <div class="mb-6 animate-fade-up">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6 flex items-start gap-4">
            <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 border border-brand-100 text-brand-600 shrink-0">
                <i data-lucide="file-chart-column" class="size-5"></i>
            </span>
            <div>
                <h2 class="font-extrabold text-slate-900 tracking-tight">Laporan Absensi & Pencapaian</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Pilih salah satu kartu di bawah. Setiap laporan memiliki filter periode
                    <b>Hari · Minggu · Bulan · Tahun · Custom</b>, tabel data asli, serta
                    <b>Keterangan Laporan</b> yang dihitung otomatis di bawah tabel.
                    Tersedia cetak (Print) dan export Excel sesuai filter yang digunakan.
                </p>
            </div>
        </div>
    </div>

    @php
        $warna = [
            'emerald' => 'bg-emerald-50 border-emerald-100 text-emerald-600 group-hover:bg-emerald-100',
            'amber' => 'bg-amber-50 border-amber-100 text-amber-600 group-hover:bg-amber-100',
            'sky' => 'bg-sky-50 border-sky-100 text-sky-600 group-hover:bg-sky-100',
            'violet' => 'bg-violet-50 border-violet-100 text-violet-600 group-hover:bg-violet-100',
            'teal' => 'bg-teal-50 border-teal-100 text-teal-600 group-hover:bg-teal-100',
            'rose' => 'bg-rose-50 border-rose-100 text-rose-600 group-hover:bg-rose-100',
            'indigo' => 'bg-indigo-50 border-indigo-100 text-indigo-600 group-hover:bg-indigo-100',
            'green' => 'bg-green-50 border-green-100 text-green-600 group-hover:bg-green-100',
            'red' => 'bg-red-50 border-red-100 text-red-600 group-hover:bg-red-100',
        ];
        $kategori = [
            'jamaah' => 'Absensi', 'pengajian' => 'Absensi', 'deresan' => 'Absensi', 'sorogan' => 'Absensi',
            'murojaah' => 'Absensi', 'ratiban' => 'Absensi', 'diniyah' => 'Absensi',
            'prestasi' => 'Pencapaian', 'pelanggaran' => 'Penegakan',
        ];
    @endphp

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
        @foreach($cards as $key => $card)
            <a href="{{ route('laporan.show', $key) }}"
               class="card card-hover card-body group relative overflow-hidden">
                <div class="flex items-start justify-between gap-3">
                    <span class="grid place-items-center size-12 rounded-2xl border transition-transform duration-300
                                 group-hover:scale-110 group-hover:rotate-6 {{ $warna[$card['color']] ?? $warna['emerald'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="size-6"></i>
                    </span>
                    <span class="badge badge-netral">{{ $kategori[$key] ?? 'Laporan' }}</span>
                </div>

                <h3 class="font-extrabold text-slate-900 mt-4 text-lg tracking-tight">Laporan {{ $card['label'] }}</h3>
                <p class="text-xs text-slate-500 mt-1">
                    @if($key === 'prestasi')
                        Tanggal, NIS, Nama, Prestasi, Tingkat, Keterangan, Poin
                    @elseif($key === 'pelanggaran')
                        Tanggal, NIS, Nama, Jenis Pelanggaran, Tingkat, Keterangan, Poin
                    @else
                        NIS, Nama, Tanggal, {{ implode(', ', config("simpas.activities.{$key}.sessions")) }}, Total Poin
                    @endif
                </p>

                @if($key === 'diniyah')
                    <span class="mt-3 inline-flex w-fit rounded-lg bg-indigo-50 border border-indigo-100 px-2 py-1
                                 text-[10px] font-bold text-indigo-700">
                        Poin terpisah · tidak masuk ranking
                    </span>
                @endif

                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 mt-4 group-hover:gap-2.5 transition-all">
                    Buka laporan
                    <i data-lucide="arrow-right" class="size-3.5"></i>
                </span>
            </a>
        @endforeach
    </div>

@endsection

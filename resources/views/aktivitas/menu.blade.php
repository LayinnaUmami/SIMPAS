@extends('layouts.app')

@section('title', 'Aktivitas')
@section('subtitle', 'Pilih jenis aktivitas yang akan diinput')

@section('content')

    <div class="mb-6 animate-fade-up">
        <p class="text-sm text-slate-500">
        @if($readOnly)
            Pilih jenis aktivitas untuk melihat riwayat kehadiran akun Anda. Semua data bersifat read-only.
        @else
            Setiap aktivitas memiliki halaman <span class="font-semibold text-slate-700">Input → Riwayat → Detail → Edit → Hapus</span>.
            Poin Diniyah bersifat terpisah dan tidak masuk ranking.
        @endif
        </p>
    </div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5 stagger">
        @foreach($jenis as $key => $act)
            <a href="{{ $readOnly ? route('aktivitas.riwayat', $key) : route('aktivitas.input', $key) }}" class="card card-hover card-body group block">
                <div class="flex items-start justify-between gap-4">
                    <span class="grid place-items-center size-12 rounded-2xl border transition-transform duration-300
                                 group-hover:scale-110 group-hover:rotate-6
                                 @switch($act['color'])
                                     @case('emerald') bg-emerald-50 border-emerald-100 text-emerald-600 @break
                                     @case('amber') bg-amber-50 border-amber-100 text-amber-600 @break
                                     @case('sky') bg-sky-50 border-sky-100 text-sky-600 @break
                                     @case('violet') bg-violet-50 border-violet-100 text-violet-600 @break
                                     @case('teal') bg-teal-50 border-teal-100 text-teal-600 @break
                                     @case('rose') bg-rose-50 border-rose-100 text-rose-600 @break
                                     @default bg-indigo-50 border-indigo-100 text-indigo-600
                                 @endswitch">
                        <i data-lucide="{{ $act['icon'] }}" class="size-6"></i>
                    </span>

                    <span class="badge {{ $act['hari_ini'] > 0 ? 'badge-hadir' : 'badge-netral' }}">
                        {{ $act['hari_ini'] }} hari ini
                    </span>
                </div>

                <h3 class="font-extrabold text-slate-900 mt-4 tracking-tight">{{ $act['label'] }}</h3>
                <p class="text-xs text-slate-500 mt-1">
                    {{ count($act['sessions']) }} sesi · {{ $act['cadence'] === 'mingguan' ? 'Pelaksanaan mingguan' : 'Pelaksanaan harian' }}
                </p>

                <div class="flex flex-wrap gap-1.5 mt-3">
                    @foreach($act['sessions'] as $sesi)
                        <span class="rounded-lg bg-slate-50 border border-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                            {{ $sesi }}
                        </span>
                    @endforeach
                </div>

                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 mt-4
                             group-hover:gap-2.5 transition-all">
                    {{ $readOnly ? 'Lihat riwayat' : 'Buka halaman input' }}
                    <i data-lucide="arrow-right" class="size-3.5"></i>
                </span>
            </a>
        @endforeach
    </div>

@endsection

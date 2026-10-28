@extends('layouts.app')

@section('title', 'Edit ' . $cfg['label'])
@section('subtitle', $santri->nama . ' · ' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'))

@section('content')

    <div class="max-w-2xl mx-auto">

        <div class="card card-body mb-5 animate-fade-up">
            <div class="flex items-center gap-4">
                <div class="grid place-items-center size-14 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700
                            text-white font-extrabold shadow-lg shadow-brand-600/30">
                    {{ $santri->initials }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">{{ $santri->nama }}</h2>
                    <p class="text-sm text-slate-500">
                        NIS: <span class="font-semibold">{{ $santri->nis }}</span> ·
                        {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                    </p>
                </div>
                <a href="{{ route('aktivitas.detail', [$type, $santri->id, 'tanggal' => $tanggal]) }}"
                   class="btn-secondary btn-sm ml-auto">
                    <i data-lucide="eye" class="size-4"></i> Detail
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('aktivitas.update', $type) }}" class="card overflow-hidden animate-fade-up"
              style="animation-delay:.1s">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            <input type="hidden" name="santri_id" value="{{ $santri->id }}">

            <div class="p-5 border-b border-slate-100 flex items-center gap-3">
                <span class="grid place-items-center size-10 rounded-2xl bg-sky-50 border border-sky-100 text-sky-600">
                    <i data-lucide="pencil" class="size-5"></i>
                </span>
                <div>
                    <h3 class="font-extrabold text-slate-900">Ubah Status Sesi</h3>
                    <p class="text-xs text-slate-500">Poin akan diset ulang sesuai status baru (bukan ditambah)</p>
                </div>
            </div>

            <div class="p-5 space-y-3 stagger">
                @foreach($cfg['sessions'] as $sesi)
                    @php $nilai = $nilai[$sesi] ?? null; @endphp
                    <div class="flex items-center gap-4 rounded-2xl border border-slate-100 p-4"
                         data-cell
                         @if($nilai === 'Hadir') style="background:#ecfdf5" @elseif($nilai === 'Alfa') style="background:#fff1f2" @endif>
                        <span class="grid place-items-center size-9 rounded-xl bg-slate-50 border border-slate-100
                                     text-slate-500 shrink-0">
                            <i data-lucide="{{ $cfg['icon'] }}" class="size-4"></i>
                        </span>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-800">{{ $sesi }}</p>
                            <p class="text-[11px] text-slate-500">
                                @if($nilai)
                                    Tercatat: <span class="font-semibold">{{ $nilai }}</span>
                                @else
                                    Belum ada data
                                @endif
                            </p>
                        </div>

                        <select name="data[{{ $sesi }}]" data-poin-select class="select !w-auto min-w-[150px]">
                            @foreach($cfg['statuses'] as $status)
                                <option value="{{ $status }}"
                                        data-poin="{{ \App\Models\BaseActivity::pointsFor($status) }}"
                                    {{ ($nilai ?? 'Hadir') === $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>

                        <span class="w-16 text-right font-extrabold text-sm
                            {{ ($nilai && \App\Models\BaseActivity::pointsFor($nilai) >= 0) ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $nilai ? (\App\Models\BaseActivity::pointsFor($nilai) > 0 ? '+' : '') . \App\Models\BaseActivity::pointsFor($nilai) : '-' }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="p-5 border-t border-slate-100 bg-slate-50/60 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span class="font-bold">Total poin:</span>
                    <span id="poin-total" class="text-xl font-extrabold text-emerald-600">0</span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('aktivitas.riwayat', [$type, 'tanggal' => $tanggal]) }}" class="btn-secondary">
                        <i data-lucide="x" class="size-4"></i> Batal
                    </a>
                    <button type="submit" class="btn-primary">
                        <i data-lucide="save" class="size-4"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>

@endsection

@extends('layouts.app')

@section('title', 'Khataman')
@section('subtitle', 'Riwayat khataman santri')

@section('content')
<div class="space-y-6">

    <div class="card card-body animate-fade-up">
        <div class="flex items-center gap-4">
            <div class="grid place-items-center size-14 rounded-2xl bg-gradient-to-br from-indigo-400 to-indigo-600 text-white shadow-lg shadow-indigo-500/30">
                <i data-lucide="bookmark-check" class="size-6"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">{{ $santri->nama }}</h2>
                <p class="text-sm text-slate-500">NIS {{ $santri->nis }} · {{ $santri->kamar }}</p>
            </div>
        </div>
    </div>

    @if(auth()->user()->isAdmin())
    <div class="card card-body mb-5 animate-fade-up">
        <form method="POST" action="{{ route('wali.khataman.store', $santri) }}"
              class="grid gap-4 sm:grid-cols-[1fr_130px_auto] items-end">
            @csrf
            <div>
                <label class="label" for="jenis_khatam">Jenis Khatam</label>
                <select id="jenis_khatam" name="jenis_khatam" class="select" required>
                    <option value="">— Pilih —</option>
                    @foreach(config('simpas.master.jenis_khatam') as $j)
                        <option value="{{ $j }}">{{ $j }}</option>
                    @endforeach
                </select>
                @error('jenis_khatam')<p class="hint text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="tahun">Tahun</label>
                <input id="tahun" name="tahun" type="number" min="1950" max="2100" value="{{ old('tahun', now()->year) }}" class="input" required>
                @error('tahun')<p class="hint text-rose-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary">
                <i data-lucide="plus" class="size-4"></i> Tambah
            </button>
        </form>
    </div>
    @endif

    @if($khatamans->isEmpty())
        <div class="card card-body text-center py-16 animate-scale-in">
            <div class="grid place-items-center size-16 rounded-3xl bg-indigo-50 text-indigo-400 mx-auto">
                <i data-lucide="bookmark-check" class="size-8"></i>
            </div>
            <p class="font-bold text-slate-800 mt-4">Belum ada riwayat khataman</p>
            <p class="text-sm text-slate-500 mt-1">Riwayat khatam santri ini masih kosong.</p>
        </div>
    @else
        <div class="card overflow-hidden animate-fade-up">
            <div class="overflow-x-auto scrollbar-slim">
                <table class="table-simpas">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">No</th>
                            <th>Jenis Khatam</th>
                            <th class="text-center">Tahun</th>
                            <th class="w-28 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($khatamans as $i => $k)
                        <tr class="animate-fade-up" style="animation-delay:{{ min($i * 0.03, 0.35) }}s">
                            <td class="text-center text-slate-400 font-semibold">{{ $i + 1 }}</td>
                            <td>
                                <span class="badge bg-indigo-50 text-indigo-700 border-indigo-100 flex items-center gap-1">
                                    <i data-lucide="bookmark-check" class="size-3.5"></i> {{ $k->jenis_khatam }}
                                </span>
                            </td>
                            <td class="text-center font-semibold text-slate-800">{{ $k->tahun }}</td>
                            <td class="text-center">
                                @if(auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('khataman.destroy', $k) }}"
                                          data-confirm="Yakin menghapus riwayat khatam {{ $k->jenis_khatam }} tahun {{ $k->tahun }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="grid place-items-center size-9 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 hover:text-rose-600 transition-all duration-200"
                                                title="Hapus">
                                            <i data-lucide="trash-2" class="size-4"></i>
                                        </button>
                                    </form>
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
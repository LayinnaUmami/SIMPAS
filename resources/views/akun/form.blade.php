@extends('layouts.app')

@section('title', 'Ubah Akun · ' . config('simpas.nama_aplikasi'))

@section('content')
<div class="space-y-6 max-w-3xl">
    <div class="animate-fade-down">
        <a href="{{ route('akun.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-3">
            <i data-lucide="arrow-left" class="size-4"></i>
            Kembali ke daftar akun
        </a>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Ubah Akun</h1>
        <p class="text-sm text-slate-500 mt-1">Kosongkan password baru jika tidak ingin menggantinya.</p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">
            <p class="font-semibold">Data belum tersimpan</p>
            <ul class="mt-1 list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('akun.update', $akun) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="card p-5 sm:p-6 space-y-5">
            <h2 class="font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="user" class="size-4 text-brand-600"></i>
                Data Akun
            </h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="label">Nama Lengkap *</label>
                    <input id="name" type="text" name="name" required maxlength="120"
                           value="{{ old('name', $akun->name) }}" class="input">
                    @error('name')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="username" class="label">Kode Akun *</label>
                    <input id="username" type="text" name="username" required maxlength="120"
                           value="{{ old('username', $akun->username) }}" class="input"
                           autocapitalize="none" spellcheck="false">
                    <p class="hint">Kode akun harus unik.</p>
                    @error('username')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="label">Email (opsional)</label>
                    <input id="email" type="email" name="email" maxlength="190"
                           value="{{ old('email', $akun->email) }}" class="input">
                    @error('email')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="telepon" class="label">Nomor Telepon (opsional)</label>
                    <input id="telepon" type="text" name="telepon" maxlength="40"
                           value="{{ old('telepon', $akun->telepon) }}" class="input">
                    @error('telepon')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="text-sm text-slate-500">
                Peran akun: <strong class="text-slate-700">{{ $akun->peran_label }}</strong>
            </p>
        </div>

        @if($akun->isWali() || $akun->isSantri())
            <div class="card p-5 sm:p-6 space-y-4">
                <h2 class="font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="users" class="size-4 text-brand-600"></i>
                    Santri Tertaut
                </h2>
                @php
                    $defaultSantriIds = $akun->isWali()
                        ? $akun->santris->pluck('id')->map(fn ($id) => (string) $id)->all()
                        : ($akun->santri_id ? [(string) $akun->santri_id] : []);
                    $pilihanSantriIds = old('santri_ids', $defaultSantriIds);
                @endphp
                <label for="santri-search" class="label">Cari Santri</label>
                <input id="santri-search" type="search" class="input" placeholder="Cari nama atau NIS..." autocomplete="off">
                <label for="santri_ids" class="label">
                    {{ $akun->isWali() ? 'Pilih satu atau beberapa anak' : 'Pilih data Santri untuk akun ini' }}
                </label>
                <select id="santri_ids" name="santri_ids[]" @if($akun->isWali()) multiple @endif class="input min-h-40">
                    @foreach($santris as $santri)
                        <option value="{{ $santri->id }}"
                            @selected(in_array((string) $santri->id, (array) $pilihanSantriIds, true))>
                            {{ $santri->nis }} — {{ $santri->nama }}
                            @if($santri->kamar) ({{ $santri->kamar }}) @endif
                        </option>
                    @endforeach
                </select>
                @if($akun->isWali())
                    <p class="hint">Kosongkan pilihan untuk melepas semua santri dari akun wali ini.</p>
                @endif
                @error('santri_ids')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                @error('santri_ids.*')<p class="hint text-rose-600">{{ $message }}</p>@enderror
            </div>
        @endif

        <div class="card p-5 sm:p-6 space-y-4">
            <h2 class="font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="key-round" class="size-4 text-brand-600"></i>
                Ganti Password (opsional)
            </h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="label">Password Baru</label>
                    <input id="password" type="password" name="password" minlength="8"
                           autocomplete="new-password" class="input" placeholder="Minimal 8 karakter">
                    @error('password')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                           minlength="8" autocomplete="new-password" class="input">
                </div>
            </div>
            <p class="hint">Password tidak pernah ditampilkan dan selalu disimpan dalam bentuk hash.</p>
        </div>

        <div class="card p-5 sm:p-6">
            <input type="hidden" name="is_active" value="0">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $akun->isActive()))
                       class="size-4 mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>
                    <span class="block font-semibold text-slate-800">Akun Aktif</span>
                    <span class="block text-[13px] text-slate-500">Akun nonaktif tidak dapat masuk.</span>
                </span>
            </label>
            @error('is_active')<p class="hint text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('akun.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">
                <i data-lucide="check" class="size-4"></i>
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var select = document.getElementById('santri_ids');
        var search = document.getElementById('santri-search');
        if (!select || !search) return;

        search.addEventListener('input', function () {
            var term = search.value.trim().toLocaleLowerCase();
            Array.from(select.options).forEach(function (option) {
                option.hidden = !option.text.toLocaleLowerCase().includes(term);
            });
        });
    })();
</script>
@endpush

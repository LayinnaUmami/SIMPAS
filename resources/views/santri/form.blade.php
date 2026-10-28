@extends('layouts.app')
@section('title', $santri->exists ? 'Edit Santri' : 'Tambah Santri')
@section('subtitle', $santri->exists ? 'Perbarui data identitas santri' : 'Tambahkan data santri baru')

@section('content')
<div class="max-w-5xl mx-auto">

    <form method="POST"
          action="{{ $santri->exists ? route('santri.update', $santri) : route('santri.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if($santri->exists)
            @method('PUT')
        @endif

        {{-- ====================== BAGIAN 1: DATA PRIBADI ====================== --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <div class="flex items-center gap-3 mb-6">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="id-card" class="size-5"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-900 tracking-tight">Data Pribadi</h2>
                    <p class="text-xs text-slate-500">Identitas utama santri</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="nis">NIS *</label>
                    <input id="nis" name="nis" type="text" class="input"
                           value="{{ old('nis', $santri->nis) }}" placeholder="contoh: 240001" required>
                    @error('nis')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="nama">Nama Lengkap *</label>
                    <input id="nama" name="nama" type="text" class="input"
                           value="{{ old('nama', $santri->nama) }}" placeholder="Nama lengkap santri" required>
                    @error('nama')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="jenis_kelamin">Jenis Kelamin *</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="select" required>
                        <option value="">— Pilih —</option>
                        @foreach(config('simpas.master.jenis_kelamin') as $jk)
                            <option value="{{ $jk }}"
                                @selected(old('jenis_kelamin', $santri->jenis_kelamin ?? ($santri->exists ? null : 'Putri')) === $jk)>{{ $jk }}</option>
                        @endforeach
                    </select>
                    @error('jenis_kelamin')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="kamar">Kamar</label>
                    <select id="kamar" name="kamar" class="select">
                        <option value="">— Pilih —</option>
                        @php($kamarSekarang = old('kamar', $santri->kamar))
                        @foreach(config('simpas.master.kamar') as $k)
                            <option value="{{ $k }}" @selected($kamarSekarang === $k)>{{ $k }}</option>
                        @endforeach
                        @if($kamarSekarang && !in_array($kamarSekarang, config('simpas.master.kamar')))
                            <option value="{{ $kamarSekarang }}" selected>{{ $kamarSekarang }}</option>
                        @endif
                    </select>
                    @error('kamar')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat" rows="2" class="input"
                              placeholder="Alamat asal santri">{{ old('alamat', $santri->alamat) }}</textarea>
                    @error('alamat')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="foto">Foto</label>
                    <input id="foto" name="foto" type="file" accept="image/*" class="input !px-3 !py-2">
                    <p class="hint">Format gambar, maksimal 2 MB.</p>
                    @error('foto')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-end gap-3">
                    @if($santri->foto_url)
                        <img src="{{ $santri->foto_url }}" alt="Foto {{ $santri->nama }}"
                             class="size-20 rounded-2xl object-cover border border-slate-200 bg-slate-50">
                        <p class="hint mb-1">Foto saat ini</p>
                    @endif
                </div>

                <div>
                    <label class="label" for="status_aktif">Status Aktif *</label>
                    <select id="status_aktif" name="status_aktif" class="select" required>
                        <option value="">— Pilih —</option>
                        @foreach(config('simpas.master.status_aktif') as $st)
                            <option value="{{ $st }}" @selected(old('status_aktif', $santri->status_aktif ?: 'Aktif') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('status_aktif')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ====================== BAGIAN 2: PENDIDIKAN ====================== --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <div class="flex items-center gap-3 mb-6">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="graduation-cap" class="size-5"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-900 tracking-tight">Pendidikan</h2>
                    <p class="text-xs text-slate-500">Pendidikan formal dan diniyah</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="pendidikan_formal">Pendidikan Formal</label>
                    <select id="pendidikan_formal" name="pendidikan_formal" class="select">
                        <option value="">— Pilih —</option>
                        @foreach(config('simpas.master.pendidikan_formal') as $pf)
                            <option value="{{ $pf }}" @selected(old('pendidikan_formal', $santri->pendidikan_formal) === $pf)>{{ $pf }}</option>
                        @endforeach
                    </select>
                    @error('pendidikan_formal')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="jenis-diniyah">Jenis Diniyah *</label>
                    <select id="jenis-diniyah" name="jenis_diniyah" class="select" required>
                        <option value="">— Pilih —</option>
                        @foreach(config('simpas.master.jenis_diniyah') as $jd)
                            <option value="{{ $jd }}" @selected(old('jenis_diniyah', $santri->jenis_diniyah ?: 'Diniyah') === $jd)>{{ $jd }}</option>
                        @endforeach
                    </select>
                    @error('jenis_diniyah')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                {{-- Level hanya relevan untuk jenis Diniyah (spec bagian 5) --}}
                <div id="level-wrapper" class="sm:col-span-2">
                    <label class="label" for="level-diniyah">Level Diniyah</label>
                    <select id="level-diniyah" name="level_diniyah" class="select">
                        <option value="">— Pilih —</option>
                        @foreach(config('simpas.master.level_diniyah') as $ld)
                            <option value="{{ $ld }}" @selected(old('level_diniyah', $santri->level_diniyah) === $ld)>{{ $ld }}</option>
                        @endforeach
                    </select>
                    @error('level_diniyah')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                    <p class="hint">Level hanya diisi untuk jenis Diniyah.</p>
                </div>
            </div>
        </div>

        {{-- ====================== BAGIAN 3: ORANG TUA / WALI ====================== --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <div class="flex items-center gap-3 mb-6">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="heart" class="size-5"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-900 tracking-tight">Orang Tua / Wali</h2>
                    <p class="text-xs text-slate-500">Kontak pendamping santri</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="nama_ayah">Nama Ayah</label>
                    <input id="nama_ayah" name="nama_ayah" type="text" class="input"
                           value="{{ old('nama_ayah', $santri->nama_ayah) }}" placeholder="Nama ayah kandung">
                    @error('nama_ayah')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="pekerjaan_ayah">Pekerjaan Ayah</label>
                    <input id="pekerjaan_ayah" name="pekerjaan_ayah" type="text" class="input"
                           value="{{ old('pekerjaan_ayah', $santri->pekerjaan_ayah) }}" placeholder="Pekerjaan ayah">
                    @error('pekerjaan_ayah')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="nama_ibu">Nama Ibu</label>
                    <input id="nama_ibu" name="nama_ibu" type="text" class="input"
                           value="{{ old('nama_ibu', $santri->nama_ibu) }}" placeholder="Nama ibu kandung">
                    @error('nama_ibu')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="pekerjaan_ibu">Pekerjaan Ibu</label>
                    <input id="pekerjaan_ibu" name="pekerjaan_ibu" type="text" class="input"
                           value="{{ old('pekerjaan_ibu', $santri->pekerjaan_ibu) }}" placeholder="Pekerjaan ibu">
                    @error('pekerjaan_ibu')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="nama_wali">Nama Wali</label>
                    <input id="nama_wali" name="nama_wali" type="text" class="input"
                           value="{{ old('nama_wali', $santri->nama_wali) }}" placeholder="Opsional bila berbeda dengan ayah">
                    @error('nama_wali')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="pekerjaan_wali">Pekerjaan Wali</label>
                    <input id="pekerjaan_wali" name="pekerjaan_wali" type="text" class="input"
                           value="{{ old('pekerjaan_wali', $santri->pekerjaan_wali) }}" placeholder="Pekerjaan wali">
                    @error('pekerjaan_wali')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="no_hp">No. HP Orang Tua/Wali</label>
                    <div class="relative">
                        <i data-lucide="phone" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                        <input id="no_hp" name="no_hp" type="text" class="input pl-11!"
                               value="{{ old('no_hp', $santri->no_hp) }}" placeholder="08xxxxxxxxxx">
                    </div>
                    @error('no_hp')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="alamat_wali">Alamat Wali</label>
                    <textarea id="alamat_wali" name="alamat_wali" rows="2" class="input"
                              placeholder="Alamat domisili wali">{{ old('alamat_wali', $santri->alamat_wali) }}</textarea>
                    @error('alamat_wali')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <hr class="border-slate-200 my-6">

                <div class="flex items-center gap-3 mb-4">
                    <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                        <i data-lucide="lock" class="size-5"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 tracking-tight">Akun Wali Santri</h3>
                        <p class="text-xs text-slate-500">Buat atau tautkan akun wali yang dikelola Admin.</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="wali-search">Tautkan ke wali yang sudah ada</label>
                        <input id="wali-search" type="search" class="input mb-2"
                               placeholder="Cari nama atau kode wali..." autocomplete="off">
                        <select id="wali_id" name="wali_id" class="select">
                            <option value="">— {{ $santri->exists ? 'Tanpa akun wali' : 'Buat akun wali baru' }} —</option>
                            @foreach($waliOptions as $wali)
                                <option value="{{ $wali->id }}"
                                    @selected((string) old('wali_id', $santri->wali_id) === (string) $wali->id)>
                                    {{ $wali->name }} ({{ $wali->username }}) - {{ $wali->santris_count }} anak
                                </option>
                            @endforeach
                        </select>
                        <p class="hint">Pilih jika kakak/adiknya sudah terdaftar di pesantren.</p>
                        @error('wali_id')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    @unless($santri->exists)
                        <div id="wali-new-account" class="sm:col-span-2 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="label" for="wali_username">Kode Akun Wali *</label>
                                <input id="wali_username" name="wali_username" type="text" class="input"
                                       value="{{ old('wali_username') }}"
                                       placeholder="Kode akun wali yang unik" maxlength="120">
                                @error('wali_username')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="label" for="wali_password">Password Wali Santri *</label>
                                <input id="wali_password" name="wali_password" type="password" class="input"
                                       placeholder="Minimal 8 karakter" minlength="8">
                                @error('wali_password')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @else
                        <p class="hint sm:col-span-2">
                            Pilih akun wali lain untuk memindahkan tautan, atau pilih tanpa akun wali untuk melepasnya.
                        </p>
                    @endunless
                </div>
            </div>
        </div>

        {{-- ====================== AKSI ====================== --}}
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pb-4">
            <a href="{{ $santri->exists ? route('santri.show', $santri) : route('santri.index') }}"
               class="btn-secondary w-full sm:w-auto">
                Batal
            </a>
            <button type="submit" class="btn-primary w-full sm:w-auto">
                <i data-lucide="save" class="size-4"></i> Simpan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const toggleLevel = function () {
            const jenis = document.getElementById('jenis-diniyah');
            const wrap = document.getElementById('level-wrapper');
            const level = document.getElementById('level-diniyah');
            if (!jenis || !wrap || !level) return;

            const show = jenis.value === 'Diniyah';
            wrap.classList.toggle('hidden', !show);
            level.disabled = !show;
            level.required = show;
        };

        const jenis = document.getElementById('jenis-diniyah');
        if (jenis) jenis.addEventListener('change', toggleLevel);

        const waliSelect = document.getElementById('wali_id');
        const waliSearch = document.getElementById('wali-search');
        const waliNewAccount = document.getElementById('wali-new-account');
        const waliUsername = document.getElementById('wali_username');
        const waliPassword = document.getElementById('wali_password');

        function toggleWaliFields() {
            if (!waliSelect || !waliNewAccount) return;

            const buatAkunBaru = waliSelect.value === '';
            waliNewAccount.classList.toggle('hidden', !buatAkunBaru);
            waliUsername.disabled = !buatAkunBaru;
            waliPassword.disabled = !buatAkunBaru;
            waliUsername.required = buatAkunBaru;
            waliPassword.required = buatAkunBaru;
        }

        if (waliSelect) waliSelect.addEventListener('change', toggleWaliFields);
        if (waliSearch && waliSelect) {
            waliSearch.addEventListener('input', function () {
                const term = waliSearch.value.trim().toLocaleLowerCase();
                Array.from(waliSelect.options).forEach(function (option, index) {
                    if (index === 0) return;
                    option.hidden = !option.text.toLocaleLowerCase().includes(term);
                });
            });
        }
        toggleWaliFields();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', toggleLevel);
        } else {
            toggleLevel();
        }
    })();
</script>
@endpush

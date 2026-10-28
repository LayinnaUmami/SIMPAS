@extends('layouts.app')
@section('title', 'Profile')
@section('subtitle', 'Kelola akun dan informasi pribadi Anda')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">

    {{-- ====================== KOLOM KIRI ====================== --}}
    <div class="space-y-6">

        {{-- Kartu Profil --}}
        <div class="card card-body text-center animate-fade-up" data-reveal>
            {{-- Foto Profil --}}
            <div class="relative w-24 h-24 mx-auto mb-4">
                <img src="{{ $user->foto_url }}" alt="{{ $user->name }}"
                     class="w-full h-full rounded-2xl object-cover shadow-lg border-4 border-white shadow-slate-200/50">
                <label for="foto" class="absolute bottom-0 right-0 w-8 h-8 rounded-xl bg-brand-600 text-white
                                           grid place-items-center cursor-pointer hover:bg-brand-700 transition-colors
                                           border-4 border-white shadow-lg">
                    <i data-lucide="camera" class="size-4"></i>
                    <input id="foto" name="foto" type="file" accept="image/*"
                           class="sr-only" form="profile-form">
                </label>
            </div>

            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">{{ $user->name }}</h2>

            <span class="badge {{ $user->isAdmin() ? 'badge-hadir' : 'badge-izin' }} mx-auto mt-2">
                {{ $user->isAdmin() ? 'Admin / Pengurus' : 'Santri / Wali' }}
            </span>

            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-left text-sm">
                <p class="flex items-center gap-2 text-slate-600">
                    <i data-lucide="user" class="size-4 text-slate-400"></i>
                    Username: <span class="ml-auto font-semibold text-slate-800">{{ $user->username }}</span>
                </p>
                <p class="flex items-center gap-2 text-slate-600">
                    <i data-lucide="mail" class="size-4 text-slate-400"></i>
                    Email: <span class="ml-auto font-semibold text-slate-800 truncate">{{ $user->email ?: '-' }}</span>
                </p>
                <p class="flex items-center gap-2 text-slate-600">
                    <i data-lucide="phone" class="size-4 text-slate-400"></i>
                    Telepon: <span class="ml-auto font-semibold text-slate-800">{{ $user->telepon ?: '-' }}</span>
                </p>
            </div>
        </div>

        {{-- Data Santri Terhubung --}}
        @if($user->santri)
            <div class="card card-body animate-fade-up" data-reveal>
                <div class="flex items-center gap-3 mb-4">
                    <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                        <i data-lucide="id-card" class="size-5"></i>
                    </div>
                    <h3 class="font-extrabold text-slate-900 tracking-tight">Data Santri Terhubung</h3>
                </div>

                <dl class="divide-y divide-slate-100">
                    @foreach([
                        ['NIS', $user->santri->nis],
                        ['Nama', $user->santri->nama],
                        ['Kamar', $user->santri->kamar ?: '-'],
                        ['Jenis Kelamin', $user->santri->jenis_kelamin],
                        ['Status Aktif', $user->santri->status_aktif],
                    ] as [$dt, $dd])
                        <div class="flex items-start justify-between gap-3 py-2.5">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $dt }}</dt>
                            <dd class="text-sm font-semibold text-slate-800 text-right">{{ $dd }}</dd>
                        </div>
                    @endforeach
                </dl>

                <a href="{{ route('santri.show', $user->santri) }}" class="btn-soft btn-sm mt-4 w-full">
                    <i data-lucide="eye" class="size-4"></i> Lihat
                </a>
            </div>
        @endif
    </div>

    {{-- ====================== KOLOM KANAN ====================== --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Ubah Profil --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <div class="flex items-center gap-3 mb-5">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="pencil" class="size-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 tracking-tight">Ubah Profil</h3>
                    <p class="text-xs text-slate-500">Perbarui nama, email, dan telepon Anda</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="grid gap-5 sm:grid-cols-2" id="profile-form">
                @csrf

                <div>
                    <label class="label" for="name">Nama *</label>
                    <input id="name" name="name" type="text" class="input"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" name="email" type="email" class="input"
                           value="{{ old('email', $user->email) }}" placeholder="nama@email.com">
                    @error('email')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="telepon">Telepon</label>
                    <input id="telepon" name="telepon" type="text" class="input"
                           value="{{ old('telepon', $user->telepon) }}" placeholder="08xxxxxxxxxx">
                    @error('telepon')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="foto">Foto</label>
                    <input id="foto" name="foto" type="file" accept="image/*" class="input !px-3 !py-2">
                    @error('foto')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i data-lucide="save" class="size-4"></i> Simpan Profil
                    </button>
                </div>
            </form>
        </div>

        {{-- Ubah Password --}}
        <div class="card card-body animate-fade-up" data-reveal>
            <div class="flex items-center gap-3 mb-5">
                <div class="grid place-items-center size-10 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                    <i data-lucide="lock" class="size-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 tracking-tight">Ubah Password</h3>
                    <p class="text-xs text-slate-500">Gunakan minimal 8 karakter</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.password') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf

                <div class="sm:col-span-2">
                    <label class="label" for="current_password">Password Lama *</label>
                    <input id="current_password" name="current_password" type="password" class="input"
                           autocomplete="current-password" required>
                    @error('current_password')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="password">Password Baru *</label>
                    <input id="password" name="password" type="password" class="input"
                           autocomplete="new-password" required>
                    @error('password')<p class="hint text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="password_confirmation">Ulangi Password Baru *</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input"
                           autocomplete="new-password" required>
                </div>

                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i data-lucide="key-round" class="size-4"></i> Ubah Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

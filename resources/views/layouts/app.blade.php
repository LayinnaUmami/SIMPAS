<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#059669">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="description" content="{{ config('simpas.tagline') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') · {{ config('simpas.nama_aplikasi') }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/images/logo-rq.png" type="image/png">
    <link rel="apple-touch-icon" href="/images/logo-rq.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-slate min-h-screen">

<div class="flex min-h-screen">

    {{-- ====================== SIDEBAR ====================== --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-50 w-[280px] bg-white border-r border-slate-200 flex flex-col
                  lg:translate-x-0">

        {{-- Brand --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-100 bg-white relative">
            <div class="relative flex items-center gap-2">
                <img src="/images/logo-pondok.png" alt="Logo Pondok" class="size-9 rounded-2xl drop-shadow-md">
                <img src="/images/logo-rq.png" alt="Logo Roudlotul Qur'an" class="size-9 rounded-2xl drop-shadow-md">
                <div class="min-w-0">
                    <p class="font-extrabold text-slate-900 tracking-tight leading-none text-lg">SIMPAS</p>
                    <p class="text-[10px] text-slate-500 mt-1 leading-tight whitespace-nowrap">Komplek Roudlotul Qur'an Putri </p>
                    <p class="text-[10px] text-slate-500 mt-1 leading-tight whitespace-nowrap"> PP Al Ihya 'Ulumaddin </p>
                </div>
                <button data-toggle-sidebar class="ml-auto lg:hidden text-slate-400 hover:text-slate-700 transition-colors">
                    <i data-lucide="x" class="size-5"></i>
                </button>
            </div>
        </div>

        {{-- Navigasi --}}
        @php
            $currentUser = auth()->user();
            $isWali = $currentUser->isWali();
            $isAdmin = $currentUser->isAdmin();

            /*
             * Anak yang sedang dipantau. Satu wali bisa punya beberapa anak,
             * jadi daftar diambil dari relasi santris.wali_id.
             * Anak yang dipilih disimpan di sesi oleh WaliController.
             */
            $anakWali = $isWali ? $currentUser->santris()->get() : collect();
            $anakAktifId = $isWali
                ? (session('wali_santri_terpilih') ?: $currentUser->santri_id)
                : null;
            $waliSantriDetail = $isWali
                ? ($anakWali->firstWhere('id', (int) $anakAktifId) ?: $anakWali->first())
                : null;
        @endphp
        <nav class="flex-1 overflow-y-auto scrollbar-slim px-3 py-3">
            {{--
                Pemilih anak. Hanya ditampilkan bila wali punya lebih dari satu
                anak, karena untuk satu anak tidak ada yang perlu dipilih.
            --}}
            @if($isWali && $anakWali->count() > 1)
                <div class="mb-2 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <label for="wali-santri-select"
                           class="mb-2 block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">
                        Data Anak
                        <span class="ml-1 rounded-full bg-white px-1.5 py-0.5 text-[10px] text-slate-600 ring-1 ring-slate-200">
                            {{ $anakWali->count() }}
                        </span>
                    </label>

                    <form method="POST" action="{{ route('wali.pilih-santri') }}" id="form-pilih-santri">
                        @csrf
                        <select id="wali-santri-select" name="santri_id" onchange="this.form.submit()"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm
                                       focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                                aria-label="Pilih anak yang ditampilkan">
                            @foreach($anakWali as $anak)
                                <option value="{{ $anak->id }}" @selected((int) $anak->id === (int) ($waliSantriDetail?->id))>
                                    {{ $anak->nama }} ({{ $anak->nis }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            <p class="menu-group-title !pt-3">Menu Utama</p>

            <a href="{{ $isWali ? route('wali.dashboard') : route('dashboard') }}"
               class="menu-link {{ ($isWali ? request()->routeIs('wali.dashboard') : request()->routeIs('dashboard')) ? 'active' : '' }}">
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>

            <a href="{{ $isWali && $waliSantriDetail ? route('santri.show', $waliSantriDetail) : ($isWali ? route('wali.santri') : route('santri.index')) }}"
               class="menu-link {{ ($isWali ? (request()->routeIs('wali.santri') || request()->routeIs('santri.show')) : request()->routeIs('santri.*')) ? 'active' : '' }}">
                <i data-lucide="users"></i>
                <span>Data Santri</span>
            </a>

            @if($isAdmin)
                {{-- Hanya Admin yang memiliki input aktivitas. --}}
                <a href="{{ route('aktivitas.menu') }}"
                   class="menu-link {{ request()->routeIs('aktivitas.*') ? 'active' : '' }}"
                   data-activity-modal>
                    <i data-lucide="clipboard-check"></i>
                    <span>Input Aktivitas</span>
                    <i data-lucide="mouse-pointer-2" class="size-4 opacity-50"></i>
                </a>
            @endif

            <a href="{{ $isWali ? route('wali.prestasi') : route('prestasi.index') }}"
               class="menu-link {{ ($isWali ? request()->routeIs('wali.prestasi') : request()->routeIs('prestasi.*')) ? 'active' : '' }}">
                <i data-lucide="trophy"></i>
                <span>Prestasi</span>
            </a>

            <a href="{{ $isWali ? route('wali.pelanggaran') : route('pelanggaran.index') }}"
               class="menu-link {{ ($isWali ? request()->routeIs('wali.pelanggaran') : request()->routeIs('pelanggaran.*')) ? 'active' : '' }}">
                <i data-lucide="triangle-alert"></i>
                <span>Pelanggaran</span>
            </a>

            <a href="{{ $isWali ? route('wali.ranking') : route('ranking.index') }}"
               class="menu-link {{ ($isWali ? request()->routeIs('wali.ranking') : request()->routeIs('ranking.*')) ? 'active' : '' }}">
                <i data-lucide="medal"></i>
                <span>Ranking</span>
            </a>

            <a href="{{ $isWali ? route('wali.laporan') : route('laporan.index') }}"
               class="menu-link {{ ($isWali ? request()->routeIs('wali.laporan') : request()->routeIs('laporan.*')) ? 'active' : '' }}">
                <i data-lucide="file-chart-column"></i>
                <span>Laporan</span>
            </a>

            @if($isAdmin)
                <p class="menu-group-title">Sistem</p>

                <a href="{{ route('akun.index') }}"
                   class="menu-link {{ request()->routeIs('akun.*') ? 'active' : '' }}">
                    <i data-lucide="users"></i>
                    <span>Akun Pengguna</span>
                </a>

                <a href="{{ route('sampah.index') }}"
                   class="menu-link {{ request()->routeIs('sampah.*') ? 'active' : '' }}">
                    <i data-lucide="trash-2"></i>
                    <span>Sampah</span>
                </a>

                <a href="{{ route('pengaturan.index') }}"
                   class="menu-link {{ request()->routeIs('pengaturan.*') ? 'active' : '' }}">
                    <i data-lucide="settings"></i>
                    <span>Pengaturan</span>
                </a>
            @endif

            <a href="{{ $isWali ? route('wali.profil') : route('profile.index') }}"
               class="menu-link {{ ($isWali ? request()->routeIs('wali.profil') : request()->routeIs('profile.*')) ? 'active' : '' }}">
                <i data-lucide="user"></i>
                <span>Profile</span>
            </a>
        </nav>

        {{-- User card --}}
        <div class="p-3 border-t border-slate-100">
            <div class="flex items-center gap-3 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 p-3 border border-emerald-100">
                <div class="grid place-items-center size-10 rounded-xl bg-emerald-600 text-white font-bold text-sm shadow-md shadow-emerald-600/30">
                    @if($isWali && $waliSantriDetail && $waliSantriDetail->foto_url)
                        <img src="{{ $waliSantriDetail->foto_url }}" alt="{{ $waliSantriDetail->nama }}" class="size-full object-cover rounded-xl">
                    @elseif($isWali && $waliSantriDetail)
                        {{ $waliSantriDetail->initials }}
                    @elseif(auth()->user()->foto)
                        <img src="{{ auth()->user()->foto_url }}" alt="{{ auth()->user()->name }}" class="size-full object-cover rounded-xl">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-slate-500 capitalize">{{ $currentUser->isWali() ? 'Wali Santri' : ($currentUser->isAdmin() ? 'Admin / Pengurus' : 'Santri') }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="grid place-items-center size-9 rounded-xl text-slate-400 hover:text-rose-600
                                   hover:bg-rose-50 transition-all duration-200" title="Keluar">
                        <i data-lucide="log-out" class="size-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ====================== KONTEN ====================== --}}
    <div class="flex-1 lg:ml-[280px] flex flex-col min-w-0">

        {{-- TOPBAR --}}
        <header id="topbar" class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur-md">
            <div class="flex items-center gap-3 px-4 sm:px-6 h-16">
                <button data-toggle-sidebar class="lg:hidden grid place-items-center size-10 rounded-xl
                        border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <i data-lucide="menu" class="size-5"></i>
                </button>

                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight truncate">
                        @yield('title', 'Dashboard')
                    </h1>
                    <p class="hidden sm:block text-[11px] text-slate-500 -mt-0.5">
                        @yield('subtitle', \Carbon\Carbon::now()->translatedFormat('dddd, d F Y'))
                    </p>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <span class="hidden md:inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-100
                                 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                        <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        {{ \Carbon\Carbon::now()->translatedFormat('d M Y') }}
                    </span>

                    <div class="relative">
                        <button class="grid place-items-center size-10 rounded-xl border border-slate-200 text-slate-600
                                       hover:bg-slate-50 transition-colors" title="Notifikasi">
                            <i data-lucide="bell" class="size-[18px]"></i>
                        </button>
                    </div>

                    <a href="{{ $currentUser->isWali() ? route('wali.profil') : route('profile.index') }}"
                       class="relative size-10 rounded-xl overflow-hidden bg-gradient-to-br from-emerald-600 to-teal-700
                             text-white font-bold text-sm shadow-md shadow-emerald-600/30 hover:scale-105 transition-transform"
                       title="Profile">
                        @if($isWali && $waliSantriDetail && $waliSantriDetail->foto_url)
                            <img src="{{ $waliSantriDetail->foto_url }}" alt="{{ $waliSantriDetail->nama }}"
                                 class="w-full h-full object-cover">
                        @elseif(auth()->user()->foto)
                            <img src="{{ auth()->user()->foto_url }}" alt="{{ auth()->user()->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <span class="flex items-center justify-center w-full h-full">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>
        </header>

        {{-- ISI HALAMAN --}}
        <main id="main" class="flex-1 p-4 sm:p-6 lg:p-8 page-enter">
            @yield('content')
        </main>

        <footer class="px-6 py-5 text-center text-xs text-slate-400 no-print">
            {{ config('simpas.nama_aplikasi') }} · {{ config('simpas.tagline') }} ·
            {{ \Carbon\Carbon::now()->year }}
        </footer>
    </div>
</div>

@if($currentUser->isAdmin())
    {{-- ====================== ACTIVITY MODAL ====================== --}}
    <div id="activity-modal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true" aria-labelledby="activity-modal-title">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" data-activity-close></div>
    <div class="fixed inset-y-0 right-0 z-10 w-full max-w-2xl bg-white shadow-2xl animate-slide-in-right flex flex-col" data-activity-panel>
        <div class="flex items-center justify-between p-4 sm:p-5 border-b border-slate-100 sticky top-0 bg-white z-10">
            <div class="flex items-center gap-3">
                <div id="activity-modal-icon" class="grid place-items-center size-10 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600">
                    <i data-lucide="clipboard-check" class="size-5"></i>
                </div>
                <div>
                    <h3 id="activity-modal-title" class="font-extrabold text-slate-900">Pilih Aktivitas</h3>
                    <p class="text-xs text-slate-500">Klik untuk mulai input absensi</p>
                </div>
            </div>
            <button data-activity-close class="grid place-items-center size-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="size-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3" id="activity-modal-list">
            @foreach(config('simpas.activities') as $key => $act)
                <a href="{{ route('aktivitas.input', $key) }}"
                   class="activity-option flex items-center gap-3 rounded-2xl border border-slate-100 p-3 hover:border-brand-200 hover:bg-brand-50/60 transition-all duration-200 group"
                   data-activity="{{ $key }}">
                    <span class="grid place-items-center size-11 rounded-2xl border transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6
                                 @switch($act['color'])
                                     @case('emerald') bg-emerald-50 border-emerald-100 text-emerald-600 @break
                                     @case('amber') bg-amber-50 border-amber-100 text-amber-600 @break
                                     @case('sky') bg-sky-50 border-sky-100 text-sky-600 @break
                                     @case('violet') bg-violet-50 border-violet-100 text-violet-600 @break
                                     @case('teal') bg-teal-50 border-teal-100 text-teal-600 @break
                                     @case('rose') bg-rose-50 border-rose-100 text-rose-600 @break
                                     @default bg-indigo-50 border-indigo-100 text-indigo-600
                                 @endswitch">
                        <i data-lucide="{{ $act['icon'] }}" class="size-5"></i>
                    </span>

                    <div class="flex-1 min-w-0">
                        <span class="block text-sm font-bold text-slate-800">{{ $act['label'] }}</span>
                        <span class="block text-[11px] text-slate-500">
                            {{ count($act['sessions']) }} sesi · {{ $act['cadence'] === 'mingguan' ? 'Mingguan' : 'Harian' }}
                        </span>
                    </div>

                    <i data-lucide="chevron-right" class="size-4 text-slate-300 group-hover:text-brand-600 transition-all"></i>
                </a>
            @endforeach
        </div>

        <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl">
            <p class="text-xs text-center text-slate-500">Diniyah: poin terpisah, tidak masuk ranking</p>
        </div>
    </div>
</div>
@endif

{{-- ====================== TOAST ====================== --}}
<div id="toast-container"
     class="fixed top-4 right-4 z-[100] flex flex-col gap-3 items-end no-print"></div>

@if(session('toast'))
    <div id="flash-toast"
         data-type="{{ session('toast')['type'] ?? 'success' }}"
         data-message="{{ session('toast')['message'] ?? '' }}"
         hidden></div>
@endif

@if($errors->any())
    <div id="flash-errors" data-message="{{ $errors->first() }}" hidden></div>
@endif

@stack('scripts')
</body>
</html>
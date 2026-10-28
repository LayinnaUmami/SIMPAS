<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#059669">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masuk · {{ config('simpas.nama_aplikasi') }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/images/logo-rq.png" type="image/png">
    <link rel="apple-touch-icon" href="/images/logo-rq.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-slate min-h-screen relative">

{{-- ================== BACKGROUND IMAGE ================== --}}
<div class="fixed inset-0 z-0" aria-hidden="true">
    <img src="/images/bg-login.jpg" alt=""
         class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-br from-emerald-900/70 via-emerald-800/60 to-teal-900/70"></div>
</div>

<div class="min-h-screen grid lg:grid-cols-2 relative z-10">

    {{-- ================== SISI KIRI (BRANDING) ================== --}}
    <div class="relative hidden lg:flex flex-col justify-between overflow-hidden p-12 text-white">

        {{-- Animated decorative shapes --}}
        <div class="blob size-72 bg-emerald-300/30 -top-16 -left-10"></div>
        <div class="blob size-96 bg-brand-400/30 bottom-10 -right-24" style="animation-delay:-3s"></div>
        <div class="blob size-56 bg-amber-300/20 top-1/2 left-1/3" style="animation-delay:-5s"></div>

        {{-- Dual Logo & Title --}}
        <div class="relative flex items-center gap-3 animate-fade-down">
            <img src="/images/logo-pondok.png" alt="Logo Pondok" class="size-12 rounded-2xl drop-shadow-xl">
            <div class="relative">
                <div class="absolute inset-0 rounded-full bg-white/20 blur-sm"></div>
                <img src="/images/logo-rq.png" alt="Logo Roudlotul Qur'an" class="relative size-12 rounded-full drop-shadow-xl bg-white/10 p-1">
            </div>
            <div>
                <p class="text-2xl font-extrabold tracking-tight text-white">SIMPAS</p>
                <p class="text-xs text-emerald-100/90 mt-1">{{ config('simpas.tagline') }}</p>
            </div>
        </div>

        {{-- Welcome Content --}}
        <div class="relative animate-fade-up" style="animation-delay:.15s">
            <p class="text-sm font-semibold text-emerald-200 tracking-[0.2em] uppercase mb-4">Assalamu'alaikum Warahmatullahi Wabarakatuh</p>
            <h2 class="text-4xl xl:text-5xl font-extrabold leading-tight tracking-tight">
                Kelola aktivitas santri<br>
                <span class="text-amber-300">lebih mudah</span>.
            </h2>

            <div class="mt-8">
                <p class="text-sm font-medium text-emerald-200/80 mb-3">Komplek Roudlotul Qur'an Putri PP Al Ihya Ulumaddin</p>
            </div>

            <div class="mt-10 grid grid-cols-3 gap-4 max-w-lg">
                @foreach([
                    ['icon' => 'building-2', 'label' => 'Jamaah', 'color' => 'emerald'],
                    ['icon' => 'book-open', 'label' => 'Pengajian', 'color' => 'amber'],
                    ['icon' => 'graduation-cap', 'label' => 'Diniyah', 'color' => 'indigo'],
                    ['icon' => 'library-big', 'label' => 'Tadarus', 'color' => 'sky'],
                    ['icon' => 'book-copy', 'label' => 'Sorogan', 'color' => 'violet'],
                    ['icon' => 'bookmark-check', 'label' => 'Murojaah', 'color' => 'teal'],
                ] as $i => $f)
                    <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/15 p-4 text-center
                                animate-fade-up hover:bg-white/20 transition-all duration-300 group" style="animation-delay:{{ 0.2 + $i * 0.08 }}s">
                        <div class="group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300 mx-auto mb-2">
                            <i data-lucide="{{ $f['icon'] }}" class="size-6 text-emerald-200"></i>
                        </div>
                        <p class="text-[12px] font-semibold text-white">{{ $f['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Footer Info --}}
        <div class="relative flex flex-col items-end gap-2 animate-fade-in" style="animation-delay:.5s">
            <p class="text-[10px] text-emerald-200/50 text-right">
                &copy; {{ now()->year }} {{ config('simpas.nama_aplikasi') }}
            </p>
        </div>
    </div>

    {{-- ================== SISI KANAN (FORM LOGIN) ================== --}}
    <div class="flex items-center justify-center p-6 sm:p-10 relative overflow-hidden">

        <div class="blob size-64 bg-brand-200/40 -top-20 -right-16 lg:hidden"></div>

        <div class="w-full max-w-md relative">

            {{-- Logo Mobile --}}
            <div class="lg:hidden flex flex-col items-center gap-3 mb-8 animate-fade-down">
                <div class="flex items-center gap-3">
                    <img src="/images/logo-pondok.png" alt="Logo Pondok" class="size-10 rounded-2xl drop-shadow-xl">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-full bg-white/40 "></div>
                        <img src="/images/logo-rq.png" alt="Logo Roudlotul Qur'an" class="relative size-10 rounded-full drop-shadow-xl bg-white/10 p-1">
                    </div>
                </div>
                <div class="text-center">
                    <p class="font-extrabold text-white leading-none text-xl">SIMPAS</p>
                    <p class="text-[11px] text-emerald-100/80 mt-1">{{ config('simpas.tagline') }}</p>
                    <p class="text-[10px] text-amber-100/70 mt-0.5">Komplek Roudlotul Qur'an Putri</p>
                    <p class="text-[10px] text-amber-100/70 mt-0.5">PP Al Ihya Ulumaddin</p>
                </div>
            </div>

            {{-- Login Card --}}
            <div class="relative rounded-3xl bg-white/95 backdrop-blur-xl border border-white/30
                        shadow-2xl shadow-slate-900/10 p-7 sm:p-9 animate-fade-up overflow-hidden">

                <div class="relative mb-8">
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-100
                                 px-3 py-1.5 text-[11px] font-bold text-emerald-700 uppercase tracking-wider mb-4">
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Masuk ke akun Anda
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Selamat datang kembali</h1>
                    <p class="text-sm text-slate-500 mt-2">
                        Gunakan kode akun yang diberikan pengurus, atau NISapplicable untuk akun Santri.
                    </p>
                </div>

                {{--
                    Pesan umum (misalnya "terlalu banyak percobaan") ditampilkan
                    di atas. Pesan per-field tetap muncul dekat input masing-masing,
                    karena lebih mudah dikaitkan dengan isian yang salah.
                --}}
                @if($errors->any())
                    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 animate-scale-in"
                         role="alert" aria-live="polite">
                        <div class="flex items-start gap-2">
                            <i data-lucide="circle-alert" class="size-4 mt-0.5 shrink-0"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5" id="login-form" novalidate>
                    @csrf

                    {{-- Kode akun / NIS --}}
                    <div class="animate-fade-up" style="animation-delay:.1s">
                        <label for="username" class="label">Kode Akun / NIS</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                            <input id="username" name="username" value="{{ old('username') }}"
                                   required autocomplete="username" autocapitalize="none" spellcheck="false"
                                   placeholder="contoh: admin atau 240001"
                                   class="input pl-12!"
                                   @class(['ring-rose-300 bg-rose-50/40' => $errors->has('username')])
                                   aria-describedby="err-username"
                                   @if($errors->has('username')) aria-invalid="true" @endif>
                        </div>
                        @error('username')
                            <p id="err-username" class="mt-1.5 flex items-center gap-1.5 text-[13px] text-rose-600">
                                <i data-lucide="circle-alert" class="size-3.5 shrink-0"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="animate-fade-up" style="animation-delay:.18s">
                        <label for="password" class="label">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 size-[18px] text-slate-400 pointer-events-none"></i>
                            <input id="password" name="password" type="password" required
                                   autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                                   class="input pl-12! pr-12!"
                                   @class(['ring-rose-300 bg-rose-50/40' => $errors->has('password')])
                                   aria-describedby="err-password"
                                   @if($errors->has('password')) aria-invalid="true" @endif>
                            <button type="button" id="toggle-pass"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors"
                                    aria-label="Tampilkan password" aria-pressed="false">
                                <i data-lucide="eye" class="size-[18px]" data-lucide-eye></i>
                                <i data-lucide="eye-off" class="size-[18px] hidden" data-lucide-eye-off></i>
                            </button>
                        </div>
                        @error('password')
                            <p id="err-password" class="mt-1.5 flex items-center gap-1.5 text-[13px] text-rose-600">
                                <i data-lucide="circle-alert" class="size-3.5 shrink-0"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2.5 text-sm text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" value="1"
                               class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Ingat saya di perangkat ini
                    </label>

                    <button type="submit" id="login-submit"
                            class="btn-primary w-full !py-3.5 text-base animate-fade-up relative"
                            style="animation-delay:.26s">
                        {{-- Ikon idle --}}
                        <i data-lucide="log-in" class="size-[18px]" data-state="idle"></i>
                        {{-- Ikon proses: spinner, muncul hanya saat mengirim --}}
                        <i data-lucide="loader-circle" class="size-[18px] hidden animate-spin" data-state="busy"></i>
                        <span data-state="idle">Masuk</span>
                        <span data-state="busy" class="hidden">Memproses&hellip;</span>
                    </button>

                    {{--
                        Pengaman lapisan klien: tombol dinonaktifkan agar tidak
                        diklik dua kali. Verifikasi tetap dilakukan di server,
                        karena pemeriksaan di peramban bisa dilewati.
                    --}}
                    <noscript>
                        <p class="text-center text-xs text-amber-700">
                            JavaScript tidak aktif; tombol tetap bisa dipakai.
                        </p>
                    </noscript>
                </form>

                <p class="mt-7 text-center text-xs text-slate-500">
                    Akun wali dibuat oleh Admin/Pengurus saat pendaftaran di pesantren.
                </p>
            </div>

            <p class="text-center text-xs text-white/60 mt-6 animate-fade-up" style="animation-delay:.4s">
                &copy; {{ now()->year }} {{ config('simpas.nama_aplikasi') }} &middot; Ihya Media
            </p>
        </div>
    </div>
</div>

<div id="toast-container" class="fixed top-4 right-4 z-[100] flex flex-col gap-3 items-end no-print"></div>

<script>
    /*
     * Perilaku tombol masuk.
     *
     * Dua hal yang dikerjakan:
     * 1. Tampilkan/sembunyikan password, dengan Maintaining state aria-pressed
     *    supaya pembaca layar tahu tombol sedang dalam keadaan apa.
     * 2. Saat form dikirim, kunci tombol dan ganti ikon menjadi spinner.
     *
     * Yang SENGAJA tidak dikerchol: validasi. Validasi hanya di server,
     * supaya tidak ada aturan yang bisa dilewati dengan mematikan JavaScript.
     */
    (function () {
        var input = document.getElementById('password');
        var tombolLihat = document.getElementById('toggle-pass');
        var form = document.getElementById('login-form');
        var tombolKirim = document.getElementById('login-submit');

        if (tombolLihat && input) {
            tombolLihat.addEventListener('click', function () {
                var tampil = input.type === 'password';
                input.type = tampil ? 'text' : 'password';
                tombolLihat.setAttribute('aria-pressed', tampil ? 'true' : 'false');
                tombolLihat.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');

                var ikonTampil = tombolLihat.querySelector('[data-lucide-eye]');
                var ikonSembunyi = tombolLihat.querySelector('[data-lucide-eye-off]');
                if (ikonTampil && ikonSembunyi) {
                    ikonTampil.classList.toggle('hidden', tampil);
                    ikonSembunyi.classList.toggle('hidden', !tampil);
                }
            });
        }

        if (form && tombolKirim) {
            form.addEventListener('submit', function () {
                // Jangan kunci kalau field wajib masih kosong: biarkan pesan
                // dari server yang menjelaskan, atau isian yang dikunci klien.
                if (!input || !input.value || !document.getElementById('username').value) {
                    return;
                }

                tombolKirim.disabled = true;
                tombolKirim.classList.add('opacity-70', 'cursor-not-allowed');

                var ikon diam = tombolKirim.querySelector('[data-state="idle"]');
                var ikon sibuk = tombolKirim.querySelector('[data-state="busy"]');
                var label diam = tombolKirim.querySelector('span[data-state="idle"]');
                var label sibuk = tombolKirim.querySelector('span[data-state="busy"]');

                if (ikon diam && ikon sibuk) { ikon diam.classList.add('hidden'); ikon sibuk.classList.remove('hidden'); }
                if (label diam && label sibuk) { label diam.classList.add('hidden'); label sibuk.classList.remove('hidden'); }
            });
        }
    })();
</script>
</body>
</html>

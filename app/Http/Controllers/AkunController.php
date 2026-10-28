<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Pengelolaan akun yang sudah ada. Pembuatan akun wali dilakukan dari form Santri.
 */
class AkunController extends Controller
{
    /** Menu utama. */
    public function index(Request $request)
    {
        $this->requireAdmin();

        // Relasi yang dimuat adalah 'santris' (banyak anak), bukan 'santri'.
        // Nama 'santri' tidak lagi menjadi relasi Eloquent, melainkan accessor
        // yang mengembalikan anak utama; with('santri') akan gagal karena
        // Eloquent hanya mengenali method relasi.
        $query = User::query()->with('santris');

        if ($request->filled('q')) {
            $cari = $request->string('q')->trim();
            $query->where(function ($sub) use ($cari) {
                $sub->where('username', 'like', "%{$cari}%")
                    ->orWhere('name', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('peran')) {
            $peran = $request->string('peran')->toString();
            if (in_array($peran, [User::ROLE_ADMIN, User::ROLE_WALI, User::ROLE_SANTRI], true)) {
                $query->where('role', $peran);
            }
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $query->when(
                $status === 'aktif',
                fn ($q) => $q->where(function ($inner) {
                    $inner->where('is_active', true)->orWhereNull('is_active');
                })
            )->when(
                $status === 'nonaktif',
                fn ($q) => $q->where('is_active', false)
            );
        }

        $akun = $query->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'wali' THEN 2 WHEN 'santri' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('akun.index', [
            'akun' => $akun,
            'statistik' => [
                'total' => User::count(),
                'admin' => User::where('role', User::ROLE_ADMIN)->count(),
                'wali' => User::where('role', User::ROLE_WALI)->count(),
                'santri' => User::where('role', User::ROLE_SANTRI)->count(),
                'nonaktif' => User::where('is_active', false)->count(),
            ],
        ]);
    }

    /** Formulir ubah akun. */
    public function edit(User $akun)
    {
        $this->requireAdmin();

        return view('akun.form', [
            'akun' => $akun,
            'santris' => $this->daftarSantri($akun),
        ]);
    }

    /** Menyimpan perubahan akun. */
    public function update(Request $request, User $akun)
    {
        $this->requireAdmin();

        $data = $request->validate($this->rules($akun), $this->pesan(), $this->namaKolom());

        if ($request->user()->id === $akun->id && ! ($data['is_active'] ?? true)) {
            return back()->withInput()->withErrors([
                'is_active' => 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.',
            ]);
        }

        if ($akun->isAdmin() && ! ($data['is_active'] ?? true) && $this->jumlahAdminAktif() <= 1) {
            return back()->withInput()->withErrors([
                'is_active' => 'Minimal harus ada satu Admin/Pengurus yang aktif.',
            ]);
        }

        if ($request->filled('email')) {
            $email = strtolower(trim($request->input('email')));
            $bentrok = User::withTrashed()
                ->where('email', $email)
                ->where('id', '!=', $akun->id)
                ->exists();
            if ($bentrok) {
                return back()->withInput()->withErrors([
                    'email' => 'Email tersebut sudah dipakai akun lain.',
                ]);
            }
        }

        $santriIds = array_values(array_unique(array_map('intval', (array) $request->input('santri_ids', []))));

        DB::transaction(function () use ($request, $data, $akun, $santriIds) {
            $akun->name = $request->string('name')->trim()->toString();
            $akun->username = $request->string('username')->trim()->toString();
            $akun->email = $request->filled('email') ? strtolower(trim($request->input('email'))) : null;
            $akun->email_verified_at = $akun->email ? now() : null;
            $akun->telepon = $request->filled('telepon')
                ? $request->string('telepon')->trim()->toString()
                : null;
            $akun->is_active = (bool) ($data['is_active'] ?? true);
            if ($request->filled('password')) {
                $akun->password = Hash::make($data['password']);
                $akun->password_changed_at = now();
                $akun->remember_token = Str::random(60);
            }
            $akun->save();

            if ($akun->isWali() || $akun->isSantri()) {
                $this->syncSantris($akun, $santriIds);
            }
        });

        Log::info('akun diperbarui', [
            'user_id' => $akun->id,
            'username' => $akun->username,
            'role' => $akun->role,
            'is_active' => $akun->is_active,
            'oleh' => $request->user()->id,
        ]);

        return redirect()->route('akun.index')->with('toast', [
            'type' => 'success',
            'message' => "Akun {$akun->username} berhasil diperbarui.",
        ]);
    }

    /** Menghapus akun wali tanpa menghapus data atau riwayat anaknya. */
    public function destroy(Request $request, User $akun)
    {
        $this->requireAdmin();

        if ($request->user()->id === $akun->id) {
            abort(403, 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        abort_unless($akun->isWali(), 403, 'Hanya akun wali yang dapat dihapus dari halaman ini.');

        $jumlahAnak = DB::transaction(function () use ($akun) {
            $akun = User::whereKey($akun->id)->lockForUpdate()->firstOrFail();
            $jumlahAnak = Santri::withTrashed()->where('wali_id', $akun->id)->count();
            Santri::withTrashed()->where('wali_id', $akun->id)->update(['wali_id' => null]);
            $akun->forceFill(['santri_id' => null])->saveQuietly();
            $akun->delete();

            return $jumlahAnak;
        });

        Log::info('akun wali dihapus', [
            'user_id' => $akun->id,
            'jumlah_anak_dilepas' => $jumlahAnak,
            'oleh' => $request->user()->id,
        ]);

        return redirect()->route('akun.index')->with('toast', [
            'type' => 'success',
            'message' => "Akun {$akun->username} berhasil dihapus. {$jumlahAnak} santri tetap tersimpan.",
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Pembantu */
    /* ------------------------------------------------------------------ */

    /** Santri yang dapat ditautkan pada akun yang sedang diedit. */
    private function daftarSantri(User $akun)
    {
        return Santri::query()
            ->when(
                $akun->isWali(),
                fn ($query) => $query->where(function ($sub) use ($akun) {
                    $sub->whereNull('wali_id')->orWhere('wali_id', $akun->id);
                })
            )
            ->where(function ($query) use ($akun) {
                $query->where('status_aktif', 'Aktif');
                if ($akun->isWali()) {
                    $query->orWhere('wali_id', $akun->id);
                }
            })
            ->when(
                ! $akun->isWali(),
                fn ($query) => $query->whereNull('deleted_at')
            )
            ->orderBy('nis')
            ->get(['id', 'nis', 'nama', 'kamar', 'wali_id']);
    }

    private function jumlahAdminAktif(): int
    {
        return User::where('role', User::ROLE_ADMIN)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->count();
    }

    /**
     * Menyinkronkan anak wali lewat satu foreign key pada setiap data Santri.
     *
     * @param  array<int, int>  $santriIds
     */
    private function syncSantris(User $akun, array $santriIds): void
    {
        if ($akun->isWali()) {
            $santris = Santri::query()
                ->whereIn('id', $santriIds)
                ->lockForUpdate()
                ->get(['id', 'wali_id']);

            $memilikiWaliLain = $santris->contains(
                fn (Santri $santri) => $santri->wali_id !== null
                    && (int) $santri->wali_id !== (int) $akun->id
            );

            if ($memilikiWaliLain) {
                throw ValidationException::withMessages([
                    'santri_ids' => 'Salah satu Santri sudah terhubung dengan akun wali lain.',
                ]);
            }

            $akun->santris()
                ->when($santriIds !== [], fn ($query) => $query->whereNotIn('id', $santriIds))
                ->update(['wali_id' => null]);

            Santri::query()->whereIn('id', $santriIds)->update(['wali_id' => $akun->id]);
            $akun->forceFill(['santri_id' => $santriIds[0] ?? null])->saveQuietly();

            return;
        }

        $akun->forceFill([
            'santri_id' => $santriIds[0] ?? null,
        ])->saveQuietly();
    }

    /** Aturan validasi untuk mengubah akun yang sudah ada. */
    private function rules(User $akun): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'username' => [
                'required',
                'string',
                'max:120',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($akun->id),
            ],
            'email' => ['nullable', 'email', 'max:190'],
            'telepon' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            'santri_ids' => ['nullable', 'array'],
            'santri_ids.*' => ['integer', 'distinct', Rule::exists('santris', 'id')->whereNull('deleted_at')],
        ];

        if ($akun->isSantri()) {
            $rules['santri_ids'][] = 'max:1';
        }

        return $rules;
    }

    private function pesan(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap terlalu panjang.',
            'username.required' => 'Kode akun wajib diisi.',
            'username.regex' => 'Kode akun hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',
            'username.unique' => 'Kode akun sudah dipakai.',
            'username.max' => 'Kode akun terlalu panjang.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email terlalu panjang.',
            'telepon.max' => 'Nomor telepon terlalu panjang.',
            'santri_ids.*.exists' => 'Data Santri yang dipilih tidak ditemukan.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }

    private function namaKolom(): array
    {
        return [
            'name' => 'nama lengkap',
            'username' => 'kode akun',
            'email' => 'email',
            'telepon' => 'nomor telepon',
            'santri_ids' => 'data Santri',
            'password' => 'kata sandi',
        ];
    }
}

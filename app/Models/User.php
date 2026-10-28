<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_WALI = 'wali';
    public const ROLE_SANTRI = 'santri';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'santri_id',
        'foto',
        'telepon',
        'is_active',
        'last_login_at',
        'password_changed_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Cast 'hashed' membuat Eloquent mem-bcrypt nilai yang di-set.
            // Karena itu controller boleh mengirim kata sandi polos dan tetap
            // aman, dan tidak ada jalur yang bisa menyimpan teks biasa.
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Relasi                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Anak-anak yang terhubung ke akun wali.
     */
    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class, 'wali_id')->orderBy('santris.nis');
    }

    /**
     * Anak utama.
     *
     * Dipakai oleh kode lama yang masih membaca $user->santri. Untuk akun wali,
     * anak utama harus berasal dari daftar anak yang memang dimiliki akun itu.
     */
    public function anakUtama()
    {
        if ($this->isSantri()) {
            return $this->santri_id ? Santri::find($this->santri_id) : null;
        }

        return $this->santris()->whereKey($this->santri_id)->first()
            ?? $this->santris()->first();
    }

    /** Alias supaya $user->santri tetap bekerja seperti sebelumnya. */
    public function getSantriAttribute()
    {
        return $this->anakUtama();
    }

    /* ------------------------------------------------------------------ */
    /* Relasi anak                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Menautkan satu Santri ke akun ini.
     *
     * Kolom users.santri_id tetap menyimpan anak utama untuk kompatibilitas
     * dengan akun lama.
     */
    public function tautkanSantri(int $santriId, string $hubungan = 'Wali', bool $sebagaiUtama = false): void
    {
        $santri = Santri::findOrFail($santriId);

        if (! $this->isWali()) {
            throw new \LogicException('Hanya akun wali yang dapat ditautkan ke data anak.');
        }

        if ($santri->wali_id !== null && (int) $santri->wali_id !== (int) $this->id) {
            throw new \LogicException('Santri tersebut sudah terhubung dengan akun wali lain.');
        }

        $santri->wali_id = $this->id;
        $santri->save();

        if ($sebagaiUtama || ! $this->santri_id) {
            $this->forceFill(['santri_id' => $santri->id])->saveQuietly();
        }
    }

    /** Melepas satu Santri dari akun ini. */
    public function lepaskanSantri(int $santriId): void
    {
        $this->santris()->whereKey($santriId)->update(['wali_id' => null]);

        if ((int) $this->santri_id === $santriId) {
            $this->refreshAnakUtama();
        }
    }

    /** Mengganti anak utama tanpa mengubah daftar anak. */
    public function jadikanAnakUtama(int $santriId): void
    {
        if (! $this->santris()->whereKey($santriId)->exists()) {
            return;
        }

        $this->forceFill(['santri_id' => $santriId])->saveQuietly();
    }

    /**
     * Menyamakan users.santri_id dengan anak pertama yang masih tertaut.
     *
     * Dipanggil setiap kali daftar anak berubah. Kalau tidak ada anak utama
     * (misalnya semua anak dilepas), kolom dikosongkan agar tidak menunjuk
     * Santri yang sudah tidak dipantau oleh akun ini lagi.
     */
    public function refreshAnakUtama(): void
    {
        if (! $this->isWali()) {
            return;
        }

        $anakUtama = $this->santris()->first();

        if ($anakUtama) {
            $this->forceFill(['santri_id' => $anakUtama->id])->saveQuietly();
        } elseif ($this->santri_id !== null) {
            $this->forceFill(['santri_id' => null])->saveQuietly();
        }
    }

    /* ------------------------------------------------------------------ */
    /* Peran                                                              */
    /* ------------------------------------------------------------------ */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isWali(): bool
    {
        return $this->role === self::ROLE_WALI;
    }

    public function isSantri(): bool
    {
        return $this->role === self::ROLE_SANTRI;
    }

    public function isWaliOrSantri(): bool
    {
        return in_array($this->role, [self::ROLE_WALI, self::ROLE_SANTRI], true);
    }

    /**
     * Halaman tujuan setelah login.
     *
     * Wali DAN Santri memakai halaman "/wali/*". Wali dibatasi ke anak yang
     * terhubung melalui wali_id, sedangkan Santri dibatasi ke users.santri_id.
     *
     * Keduanya harus diarahkan ke sini. Kalau hanya wali yang diarahkan,
     * Santri akan mendarat di dashboard admin dan melihat rekap seluruh pondok.
     */
    public function landingRoute(): string
    {
        return $this->isWaliOrSantri() ? 'wali.dashboard' : 'dashboard';
    }

    /* ------------------------------------------------------------------ */
    /* Status akun                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Akun hanya boleh login bila is_active bernilai benar.
     *
     * Kolomnya baru ada sejak migrasi 2026_10_14. Untuk akun lama yang dibuat
     * sebelum kolom itu ada, null diperlakukan sebagai aktif.
     */
    public function isActive(): bool
    {
        return $this->is_active === null ? true : (bool) $this->is_active;
    }

    public function scopeAktif(Builder $q): Builder
    {
        return $q->where(function (Builder $inner) {
            $inner->where('is_active', true)->orWhereNull('is_active');
        });
    }

    /**
     * Email sudah dibuktikan kepemilikannya lewat kode OTP.
     *
     * Akun yang dibuat Admin/Pengurus tidak punya email sama sekali, dan
     * kondisi itu dianggap sah karena peran serta relasinya sudah ditentukan
     * petugas, bukan oleh pemohon yang mendaftar sendiri.
     */
    public function emailTerverifikasi(): bool
    {
        return $this->isWali() || $this->email === null || $this->email_verified_at !== null;
    }

    /**
     * Akun wali/santri harus punya minimal satu Santri yang dipantau.
     * Admin tidak punya, jadi selalu lolos.
     */
    public function hasSantriLink(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isSantri()) {
            return $this->santri_id !== null
                && Santri::whereKey($this->santri_id)->exists();
        }

        return $this->santris()->exists();
    }

    public function canViewSantri(Santri $santri): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isWali()) {
            return (int) $santri->wali_id === (int) $this->id;
        }

        return $this->isSantri() && (int) $santri->id === (int) $this->santri_id;
    }

    /* ------------------------------------------------------------------ */
    /* Label                                                              */
    /* ------------------------------------------------------------------ */

    public function getPeranLabelAttribute(): string
    {
        return [
            self::ROLE_ADMIN => 'Admin/Pengurus',
            self::ROLE_WALI => 'Wali Santri',
            self::ROLE_SANTRI => 'Santri',
        ][$this->role] ?? 'Tidak Diketahui';
    }

    public function getFotoUrlAttribute(): string
    {
        if ($this->foto) {
            return asset('storage/' . $this->foto);
        }

        return asset('images/logo-pondok.png');
    }
}

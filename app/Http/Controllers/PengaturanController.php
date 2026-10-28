<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pengaturan aplikasi.
 *
 * Yang diperbaiki di sini: ditambahkan "Zona Bahaya" untuk menghapus SEMUA
 * data Santri dan seluruh data turunannya dalam satu tindakan. Ini diperlukan
 * karena sebelumnya penghapusan hanya bisa dilakukan per jenis data (per tabel)
 * lewat halaman Sampah, yang tidak praktis ketika pengurus ingin memulai dari
 * nol (misalnya awal tahun ajaran baru).
 *
 * Yang dijaga:
 * - Akun pengguna (users) TIDAK ikut terhapus.
 * - Pengaturan (settings) TIDAK ikut terhapus, supaya konfigurasi tetap.
 * - Wajib mengetik "HAPUS SEMUA" persis, agar tidak terjadi karena klik
 *   tidak sengaja.
 * - Semua penghapusan berlangsung dalam satu transaksi: kalau satu gagal,
 *   seluruhnya dibatalkan.
 * - Dicatat di log siapa yang melakukan, kapan, dan berapa baris dihapus.
 */
class PengaturanController extends Controller
{
    public function index()
    {
        return view('pengaturan.index', [
            'points' => config('simpas.points'),
            'auto' => config('simpas.auto_violation'),
            'pelanggaranTingkat' => config('simpas.pelanggaran_tingkat'),
            'master' => config('simpas.master'),
            'aktivitas' => config('simpas.activities'),
            'namaAplikasi' => config('simpas.nama_aplikasi'),
            'tagline' => config('simpas.tagline'),
        ]);
    }

    public function simpan(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'points' => ['required', 'array'],
            'points.*' => ['required', 'integer', 'between:-10,10'],
        ]);

        Setting::set('points', json_encode($request->input('points')));
        Setting::set('auto_violation_enabled', $request->boolean('auto_enabled') ? '1' : '0');
        Setting::set('nama_aplikasi', $request->input('nama_aplikasi', 'SIMPAS'));

        // Terapkan langsung ke config berjalan
        config(['simpas.points' => $request->input('points')]);
        config(['simpas.auto_violation.enabled' => $request->boolean('auto_enabled')]);
        config(['simpas.nama_aplikasi' => $request->input('nama_aplikasi', 'SIMPAS')]);

        return redirect()->route('pengaturan.index')
            ->with('toast', ['type' => 'success', 'message' => 'Pengaturan berhasil disimpan.']);
    }

    /**
     * Menghapus SEMUA data Santri dan seluruh data turunannya sekaligus.
     *
     * Yang dihapus: 7 tabel aktivitas, prestasi, pelanggaran, khataman,
     * guardian_santri, account_verifications, dan santris. Yang TETAP: users
     * (akun) dan settings (konfigurasi).
     *
     * Soft-deleted row juga ikut forceDelete supaya tidak ada sisa.
     */
    public function hapusSemuaData(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'konfirmasi' => ['required', 'string'],
        ], [
            'konfirmasi.required' => 'Ketik "HAPUS SEMUA" untuk melanjutkan.',
        ], [
            'konfirmasi' => 'konfirmasi',
        ]);

        if (trim($request->input('konfirmasi')) !== 'HAPUS SEMUA') {
            return back()->withInput()->withErrors([
                'konfirmasi' => 'Teks konfirmasi tidak cocok. Ketik persis "HAPUS SEMUA".',
            ]);
        }

        // Jangan biarkan aplikasi kehilangan satu-satunya admin aktif.
        if (User::where('role', 'admin')->where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->count() === 0) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Tidak ada Admin/Pengurus aktif. Penghapusan dibatalkan.',
            ]);
        }

        $aktivitas = config('simpas.activities', []);

        $ringkasan = [];

        DB::transaction(function () use ($aktivitas, &$ringkasan) {
            // Aktivitas (7 tabel)
            foreach ($aktivitas as $cfg) {
                $model = $cfg['model'];
                $n = $model::query()->forceDelete();
                $ringkasan[$cfg['table']] = $n;
            }

            // Prestasi dan pelanggaran
            $ringkasan['prestasis'] = \App\Models\Prestasi::query()->forceDelete();
            $ringkasan['pelanggarans'] = \App\Models\Pelanggaran::query()->forceDelete();

            // Khataman
            $ringkasan['khatamans'] = \App\Models\Khataman::query()->forceDelete();

            // Relasi wali-anak
            $ringkasan['guardian_santri'] = DB::table('guardian_santri')->delete();

            // Kode verifikasi yang masih hidup
            $ringkasan['account_verifications'] = DB::table('account_verifications')->delete();

            // Santri terakhir, karena semua tabel di atas menunjuk ke santris.
            $ringkasan['santris'] = \App\Models\Santri::query()->forceDelete();

            // Akun wali yang terhubung ke Santri yang sudah hilang tidak lagi
            // punya data anak. Kolom santri_id diset null supaya tidak menunjuk
            // baris yang sudah tidak ada (FK ON DELETE SET NULL seharusnya
            // sudah melakukan ini, tetapi dipastikan di sini).
            \App\Models\User::where('role', '!=', 'admin')
                ->update(['santri_id' => null]);
        });

        $total = array_sum($ringkasan);

        Log::warning('HAPUS SEMUA DATA dilakukan', [
            'oleh' => $request->user()->id,
            'username' => $request->user()->username,
            'ip' => $request->ip(),
            'ringkasan' => $ringkasan,
            'total' => $total,
        ]);

        return redirect()->route('pengaturan.index')->with('toast', [
            'type' => 'success',
            'message' => "Semua data Santri dan aktivitas dihapus ({$total} record). Akun pengguna tetap utuh.",
        ]);
    }
}

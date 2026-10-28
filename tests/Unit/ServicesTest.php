<?php

namespace Tests\Unit;

use App\Models\Jamaah;
use App\Models\Pelanggaran;
use App\Models\Prestasi;
use App\Models\Santri;
use App\Services\AttendanceService;
use App\Services\AutoViolationService;
use App\Services\PendaftaranService;
use App\Services\PeriodResolver;
use App\Services\RankingService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['db']->connection('sqlite')->getPdo()->sqliteCreateFunction(
            'now',
            fn () => now()->toDateTimeString()
        );

        return $app;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_period_resolver_menentukan_rentang_bulan_dan_menjepit_bulan_tepi(): void
    {
        Carbon::setTestNow('2026-10-28 09:00:00');

        $periode = PeriodResolver::resolve('bulan', ['bulan' => 13, 'tahun' => 2024]);

        $this->assertSame('2024-12-01 00:00:00', $periode['mulai']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-12-31 23:59:59', $periode['akhir']->format('Y-m-d H:i:s'));
        $this->assertSame('bulan', $periode['jenis']);
    }

    public function test_period_resolver_membalikkan_tanggal_custom_yang_terbalik(): void
    {
        $periode = PeriodResolver::resolve('custom', [
            'mulai' => '2024-03-20',
            'akhir' => '2024-03-02',
        ]);

        $this->assertSame('2024-03-02 00:00:00', $periode['mulai']->format('Y-m-d H:i:s'));
        $this->assertSame('2024-03-20 23:59:59', $periode['akhir']->format('Y-m-d H:i:s'));
    }

    public function test_period_resolver_menjepit_minggu_iso_di_tahun_yang_hanya_memiliki_52_minggu(): void
    {
        $periode = PeriodResolver::resolve('minggu', ['tahun' => 2021, 'minggu' => 99]);

        $this->assertSame('2021-12-27', $periode['mulai']->format('Y-m-d'));
        $this->assertSame('2022-01-02', $periode['akhir']->format('Y-m-d'));
        $this->assertStringContainsString('Minggu ke-52 / 2021', $periode['label']);
    }

    public function test_ranking_service_menghitung_poin_aktivitas_prestasi_dan_pelanggaran(): void
    {
        $unggul = $this->buatSantri(['nis' => 'NIS-001', 'nama' => 'Aisyah']);
        $kedua = $this->buatSantri(['nis' => 'NIS-002', 'nama' => 'Zahra']);
        Jamaah::create([
            'santri_id' => $unggul->id,
            'tanggal' => '2026-10-10',
            'sesi' => 'Subuh',
            'status' => 'Hadir',
            'poin' => 2,
        ]);
        Prestasi::create([
            'santri_id' => $unggul->id,
            'tanggal' => '2026-10-11',
            'jenis' => 'Akademik',
            'nama_prestasi' => 'Juara',
            'tingkat' => 'Pondok',
            'poin' => 5,
        ]);
        Pelanggaran::create([
            'santri_id' => $unggul->id,
            'tanggal' => '2026-10-12',
            'jenis' => 'Terlambat',
            'tingkat' => 'Ringan',
            'poin' => -3,
        ]);
        Jamaah::create([
            'santri_id' => $kedua->id,
            'tanggal' => '2026-10-10',
            'sesi' => 'Subuh',
            'status' => 'Hadir',
            'poin' => 2,
        ]);

        $ranking = app(RankingService::class)->ranking([
            'mulai' => Carbon::parse('2026-10-01'),
            'akhir' => Carbon::parse('2026-10-31'),
        ]);

        $this->assertSame($unggul->id, $ranking[0]['santri']->id);
        $this->assertSame(4, $ranking[0]['total']);
        $this->assertSame(1, $ranking[0]['rank']);
        $this->assertSame(2, $ranking[1]['rank']);
    }

    public function test_ranking_service_mengembalikan_daftar_kosong_tanpa_santri_aktif(): void
    {
        $this->buatSantri(['status_aktif' => 'Alumni']);

        $this->assertSame([], app(RankingService::class)->ranking(['jenis' => 'semua']));
    }

    public function test_report_service_mengelompokkan_absensi_dan_merekap_status(): void
    {
        $santri = $this->buatSantri();
        Jamaah::create([
            'santri_id' => $santri->id,
            'tanggal' => '2026-10-10',
            'sesi' => 'Subuh',
            'status' => 'Hadir',
            'poin' => 2,
        ]);
        Jamaah::create([
            'santri_id' => $santri->id,
            'tanggal' => '2026-10-10',
            'sesi' => 'Isya',
            'status' => 'Alfa',
            'poin' => -2,
        ]);

        $laporan = app(ReportService::class)->activity('jamaah', '2026-10-01', '2026-10-31');

        $this->assertCount(1, $laporan['rows']);
        $this->assertSame('Hadir', $laporan['rows'][0]['sesi']['Subuh']);
        $this->assertSame('Alfa', $laporan['rows'][0]['sesi']['Isya']);
        $this->assertSame(0, $laporan['rows'][0]['total']);
        $this->assertSame(2, $laporan['keterangan']['total_absensi']);
        $this->assertSame(1, $laporan['keterangan']['status']['Hadir']);
        $this->assertSame(1, $laporan['keterangan']['status']['Alfa']);
    }

    public function test_report_service_mengembalikan_rekap_nol_untuk_rentang_kosong(): void
    {
        $laporan = app(ReportService::class)->activity('jamaah', '2026-10-01', '2026-10-31');

        $this->assertSame([], $laporan['rows']);
        $this->assertSame(0, $laporan['keterangan']['total_absensi']);
        $this->assertSame(0, $laporan['keterangan']['total_poin']);
        $this->assertSame(0, $laporan['keterangan']['jumlah_baris']);
    }

    public function test_attendance_service_menyimpan_absensi_valid_dengan_poin_status(): void
    {
        $santri = $this->buatSantri();
        $hasil = app(AttendanceService::class)->save('jamaah', '2026-10-10', [[
            'santri_id' => $santri->id,
            'sesi' => 'Subuh',
            'status' => 'Hadir',
        ]]);

        $this->assertSame(['created' => 1, 'updated' => 0, 'pelanggaran' => 0], $hasil);
        $this->assertSame(1, Jamaah::count());
        $this->assertSame('Hadir', Jamaah::firstOrFail()->status);
        $this->assertSame(2, Jamaah::firstOrFail()->poin);
    }

    public function test_attendance_service_mengabaikan_baris_tidak_lengkap_dan_menolak_tipe_tidak_dikenal(): void
    {
        $hasil = app(AttendanceService::class)->save('jamaah', '2026-10-10', [
            ['santri_id' => 0, 'sesi' => 'Subuh', 'status' => 'Hadir'],
            ['santri_id' => 1, 'sesi' => '', 'status' => 'Hadir'],
            ['santri_id' => 1, 'sesi' => 'Subuh', 'status' => ''],
        ]);

        $this->assertSame(['created' => 0, 'updated' => 0, 'pelanggaran' => 0], $hasil);
        $this->assertSame(0, Jamaah::count());

        $this->expectException(HttpException::class);
        app(AttendanceService::class)->save('tipe-tidak-ada', '2026-10-10', []);
    }

    public function test_auto_violation_service_membuat_pelanggaran_tepat_di_ambang_dan_mencegah_duplikasi(): void
    {
        $santri = $this->buatSantri();
        foreach (['Subuh', 'Dzuhur'] as $sesi) {
            Jamaah::create([
                'santri_id' => $santri->id,
                'tanggal' => '2026-10-26',
                'sesi' => $sesi,
                'status' => 'Alfa',
                'poin' => -2,
            ]);
        }

        $service = app(AutoViolationService::class);
        $this->assertSame(0, $service->check('jamaah', '2026-10-26'));

        Jamaah::create([
            'santri_id' => $santri->id,
            'tanggal' => '2026-10-27',
            'sesi' => 'Subuh',
            'status' => 'Alfa',
            'poin' => -2,
        ]);

        $this->assertSame(1, $service->check('jamaah', '2026-10-27'));
        $this->assertSame(0, $service->check('jamaah', '2026-10-27'));
        $this->assertSame(1, Pelanggaran::where('is_auto', true)->count());
    }

    public function test_auto_violation_service_mengabaikan_aktivitas_tanpa_aturan(): void
    {
        $this->assertSame(0, app(AutoViolationService::class)->check('diniyah', '2026-10-10'));
    }

    public function test_pendaftaran_service_menormalkan_nomor_dan_menolak_input_di_luar_batas(): void
    {
        $service = app(PendaftaranService::class);

        $this->assertSame('628123456789', $service->normalkanNomor('+62 812-3456-789'));
        $this->assertSame('628123456789', $service->normalkanNomor('0812 3456 789'));
        $this->assertNull($service->normalkanNomor(''));
        $this->assertNull($service->normalkanNomor('08123'));
        $this->assertNull($service->normalkanNomor('081234567890123'));
    }

    public function test_pendaftaran_service_mencari_semua_santri_dengan_nomor_yang_setara(): void
    {
        $urutKedua = $this->buatSantri(['nis' => 'NIS-002', 'no_hp' => '0812-3456-789']);
        $urutPertama = $this->buatSantri(['nis' => 'NIS-001', 'no_hp' => '+62 812 3456 789']);
        $urutKetiga = $this->buatSantri(['nis' => 'NIS-003', 'no_hp' => '08123456789']);
        $service = app(PendaftaranService::class);

        $hasil = $service->cariSantriDariTelepon('628123456789');

        $this->assertSame(
            [$urutPertama->id, $urutKedua->id, $urutKetiga->id],
            $hasil->modelKeys()
        );
        $this->assertCount(0, $service->cariSantriDariTelepon(null));
        $this->assertCount(0, $service->cariSantriDariTelepon('nomor-tidak-valid'));
    }

    private function buatSantri(array $attributes = []): Santri
    {
        static $counter = 0;
        $counter++;

        return Santri::create(array_merge([
            'nis' => 'NIS-SERVICE-'.$counter,
            'nama' => 'Santri Service '.$counter,
            'jenis_kelamin' => 'Putri',
            'status_aktif' => 'Aktif',
            'jenis_diniyah' => 'Diniyah',
        ], $attributes));
    }
}

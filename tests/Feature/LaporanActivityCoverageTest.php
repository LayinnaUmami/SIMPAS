<?php

namespace Tests\Feature;

use App\Models\Jamaah;
use App\Models\Pelanggaran;
use App\Models\Santri;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\AutoViolationService;
use App\Services\ExcelExporter;
use App\Services\PeriodResolver;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class LaporanActivityCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();

        DB::connection('sqlite')->getPdo()->sqliteCreateFunction(
            'now',
            fn () => now()->toDateTimeString()
        );

        return $app;
    }

    /* ------------------------------------------------------------------ */
    /* PeriodResolver */
    /* ------------------------------------------------------------------ */

    public function test_period_resolver_default_adalah_bulan_berjalan(): void
    {
        $p = PeriodResolver::resolve();

        $this->assertSame('bulan', $p['jenis']);
        $this->assertSame(now()->startOfMonth()->toDateString(), $p['mulai']->toDateString());
        $this->assertSame(now()->endOfMonth()->toDateString(), $p['akhir']->toDateString());
        $this->assertSame('bulan', PeriodResolver::resolve('tidak-dikenal')['jenis']);
    }

    public function test_period_resolver_bulan_dibatasi_antara_1_dan_12(): void
    {
        $atas = PeriodResolver::resolve('bulan', ['bulan' => 13, 'tahun' => 2026]);
        $bawah = PeriodResolver::resolve('bulan', ['bulan' => 0, 'tahun' => 2026]);

        $this->assertSame('2026-12-01', $atas['mulai']->toDateString());
        $this->assertSame('2026-01-01', $bawah['mulai']->toDateString());
    }

    public function test_period_resolver_hari_dengan_dan_tanpa_tanggal(): void
    {
        $dengan = PeriodResolver::resolve('hari', ['tanggal' => '2026-03-11']);
        $tanpa = PeriodResolver::resolve('hari');

        $this->assertSame('hari', $dengan['jenis']);
        $this->assertSame('2026-03-11', $dengan['mulai']->toDateString());
        $this->assertSame('2026-03-11', $dengan['akhir']->toDateString());
        $this->assertSame(now()->toDateString(), $tanpa['mulai']->toDateString());
    }

    public function test_period_resolver_minggu_dimulai_senin_dan_dibatasi(): void
    {
        $p = PeriodResolver::resolve('minggu', ['tahun' => 2026, 'minggu' => 10]);
        $this->assertSame(1, $p['mulai']->dayOfWeekIso);
        $this->assertEquals(6, $p['mulai']->diffInDays($p['akhir']->copy()->startOfDay()));

        $bawah = PeriodResolver::resolve('minggu', ['tahun' => 2026, 'minggu' => 0]);
        $this->assertStringContainsString('Minggu ke-1 /', $bawah['label']);

        $maxWeek = Carbon::create(2026, 12, 28)->isoWeek;
        $atas = PeriodResolver::resolve('minggu', ['tahun' => 2026, 'minggu' => 99]);
        $this->assertStringContainsString("Minggu ke-{$maxWeek} /", $atas['label']);
    }

    public function test_period_resolver_tahun_custom_dan_semua(): void
    {
        $tahun = PeriodResolver::resolve('tahun', ['tahun' => 2026]);
        $this->assertSame('2026-01-01', $tahun['mulai']->toDateString());
        $this->assertSame('2026-12-31', $tahun['akhir']->toDateString());

        $terbalik = PeriodResolver::resolve('custom', ['mulai' => '2026-03-31', 'akhir' => '2026-03-01']);
        $this->assertSame('2026-03-01', $terbalik['mulai']->toDateString());
        $this->assertSame('2026-03-31', $terbalik['akhir']->toDateString());

        $kosong = PeriodResolver::resolve('custom');
        $this->assertSame(now()->startOfMonth()->toDateString(), $kosong['mulai']->toDateString());
        $this->assertSame(now()->endOfMonth()->toDateString(), $kosong['akhir']->toDateString());

        $semua = PeriodResolver::resolve('semua');
        $this->assertSame('semua', $semua['jenis']);
        $this->assertSame('1900-01-01', $semua['mulai']->toDateString());
        $this->assertSame('semua', PeriodResolver::resolve('all')['jenis']);
    }

    public function test_period_resolver_kunci_minggu_dan_bulan(): void
    {
        $tanggal = Carbon::parse('2026-01-07');

        $this->assertSame('2026-W02', PeriodResolver::weekKey($tanggal));
        $this->assertSame('2026-01-05', PeriodResolver::weekStart($tanggal)->toDateString());
        $this->assertSame('2026-01', PeriodResolver::monthKey($tanggal));
    }

    /* ------------------------------------------------------------------ */
    /* ExcelExporter */
    /* ------------------------------------------------------------------ */

    public function test_excel_exporter_menghasilkan_file_dengan_meta_dan_escape(): void
    {
        $response = ExcelExporter::download('LAPORAN_UJI', ['No', 'Nama'], [[1, '<b>Budi</b>']], [
            'title' => 'Judul Uji',
            'periode' => 'Periode Uji',
            'cari' => 'budi',
            'keterangan' => ['Hadir' => 3],
        ]);

        $isi = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('LAPORAN_UJI.xls', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Judul Uji', $isi);
        $this->assertStringContainsString('Periode Uji', $isi);
        $this->assertStringContainsString('Pencarian: budi', $isi);
        $this->assertStringContainsString('&lt;b&gt;Budi&lt;/b&gt;', $isi);
        $this->assertStringNotContainsString('<b>Budi</b>', $isi);
        $this->assertStringContainsString('KETERANGAN LAPORAN', $isi);
    }

    public function test_excel_exporter_tanpa_meta_tidak_menampilkan_judul_dan_keterangan(): void
    {
        $isi = ExcelExporter::download('POLOS', ['A'], [['x']])->getContent();

        $this->assertStringContainsString('<th>A</th>', $isi);
        $this->assertStringNotContainsString('KETERANGAN LAPORAN', $isi);
        $this->assertStringNotContainsString('Dicetak', $isi);
    }

    /* ------------------------------------------------------------------ */
    /* AttendanceService */
    /* ------------------------------------------------------------------ */

    public function test_attendance_save_membuat_data_baru_dan_melewati_baris_tidak_valid(): void
    {
        $santri = $this->buatSantri('NIS-A1', 'Ahmad Satu');

        $hasil = (new AttendanceService)->save('jamaah', '2026-03-11', [
            ['santri_id' => $santri->id, 'sesi' => 'Subuh', 'status' => 'Hadir'],
            ['santri_id' => $santri->id, 'sesi' => 'Dzuhur', 'status' => 'Izin'],
            ['santri_id' => 0, 'sesi' => 'Ashar', 'status' => 'Hadir'],
            ['santri_id' => $santri->id, 'sesi' => '', 'status' => 'Hadir'],
            ['santri_id' => $santri->id, 'sesi' => 'Isya', 'status' => ''],
        ]);

        $this->assertSame(2, $hasil['created']);
        $this->assertSame(0, $hasil['updated']);
        $this->assertSame(0, $hasil['pelanggaran']);
        $this->assertDatabaseHas('jamaahs', ['santri_id' => $santri->id, 'sesi' => 'Subuh', 'poin' => 2]);
        $this->assertDatabaseHas('jamaahs', ['santri_id' => $santri->id, 'sesi' => 'Dzuhur', 'poin' => 0]);
    }

    public function test_attendance_save_memperbarui_data_lama_tanpa_duplikat(): void
    {
        $santri = $this->buatSantri('NIS-A2', 'Ahmad Dua');
        $this->seedAktivitas('jamaah', $santri->id, '2026-03-11', 'Subuh', 'Alfa');

        $hasil = (new AttendanceService)->save('jamaah', '2026-03-11', [
            ['santri_id' => $santri->id, 'sesi' => 'Subuh', 'status' => 'Hadir'],
        ]);

        $this->assertSame(0, $hasil['created']);
        $this->assertSame(1, $hasil['updated']);
        $this->assertSame(1, DB::table('jamaahs')->count());
        $this->assertDatabaseHas('jamaahs', ['santri_id' => $santri->id, 'status' => 'Hadir', 'poin' => 2]);
    }

    public function test_attendance_save_memulihkan_data_yang_ada_di_sampah(): void
    {
        $santri = $this->buatSantri('NIS-A3', 'Ahmad Tiga');
        $this->seedAktivitas('jamaah', $santri->id, '2026-03-11', 'Subuh', 'Alfa', null, now()->toDateTimeString());

        $hasil = (new AttendanceService)->save('jamaah', '2026-03-11', [
            ['santri_id' => $santri->id, 'sesi' => 'Subuh', 'status' => 'Hadir'],
        ]);

        $this->assertSame(1, $hasil['updated']);
        $this->assertSame(1, Jamaah::count());
        $this->assertSame(0, Jamaah::onlyTrashed()->count());
    }

    public function test_attendance_save_jenis_tidak_dikenal_menghasilkan_404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new AttendanceService)->save('tidak-ada', '2026-03-11', []);
    }

    public function test_attendance_current_map_dan_santri_aktif_untuk_diniyah(): void
    {
        $diniyah = $this->buatSantri('NIS-D1', 'Santri Diniyah', 'Diniyah');
        $mutimin = $this->buatSantri('NIS-D2', 'Santri Mutimin', 'Mutimin');
        $this->seedAktivitas('diniyah', $diniyah->id, '2026-03-11', 'Diniyah', 'Hadir');
        $this->seedAktivitas('diniyah', $mutimin->id, '2026-03-11', 'Diniyah', 'Hadir');
        $this->seedAktivitas('jamaah', $mutimin->id, '2026-03-11', 'Subuh', 'Hadir');

        $service = new AttendanceService;
        $mapDiniyah = $service->currentMap('diniyah', '2026-03-11');
        $mapJamaah = $service->currentMap('jamaah', '2026-03-11');

        $this->assertArrayHasKey($diniyah->id, $mapDiniyah);
        $this->assertArrayNotHasKey($mutimin->id, $mapDiniyah);
        $this->assertSame('Hadir', $mapJamaah[$mutimin->id]['Subuh']);
        $this->assertCount(1, $service->santriAktif('diniyah'));
        $this->assertCount(2, $service->santriAktif());
    }

    /* ------------------------------------------------------------------ */
    /* AutoViolationService */
    /* ------------------------------------------------------------------ */

    public function test_tiga_alfa_jamaah_dalam_seminggu_membuat_satu_pelanggaran_otomatis(): void
    {
        $santri = $this->buatSantri('NIS-V1', 'Santri Alfa');
        $kurang = $this->buatSantri('NIS-V2', 'Santri Dua Alfa');

        $hasil = (new AttendanceService)->save('jamaah', '2026-03-11', [
            ['santri_id' => $santri->id, 'sesi' => 'Subuh', 'status' => 'Alfa'],
            ['santri_id' => $santri->id, 'sesi' => 'Dzuhur', 'status' => 'Alfa'],
            ['santri_id' => $santri->id, 'sesi' => 'Ashar', 'status' => 'Alfa'],
            ['santri_id' => $kurang->id, 'sesi' => 'Subuh', 'status' => 'Alfa'],
            ['santri_id' => $kurang->id, 'sesi' => 'Dzuhur', 'status' => 'Alfa'],
        ]);

        $this->assertSame(1, $hasil['pelanggaran']);
        $this->assertSame(1, Pelanggaran::count());
        $this->assertDatabaseHas('pelanggarans', [
            'santri_id' => $santri->id,
            'is_auto' => true,
            'poin' => -5,
            'jenis' => 'Tidak mengikuti jamaah',
        ]);
        $this->assertDatabaseMissing('pelanggarans', ['santri_id' => $kurang->id]);

        // Dicek ulang: tidak boleh membuat pelanggaran yang sama dua kali.
        $this->assertSame(0, (new AutoViolationService)->check('jamaah', '2026-03-11'));
        $this->assertSame(1, Pelanggaran::count());
    }

    public function test_tiga_alfa_pengajian_dalam_sebulan_membuat_pelanggaran_bulanan(): void
    {
        $santri = $this->buatSantri('NIS-V3', 'Santri Pengajian');
        foreach (['2026-03-02', '2026-03-03', '2026-03-04'] as $tanggal) {
            $this->seedAktivitas('pengajian', $santri->id, $tanggal, 'Pengajian', 'Alfa');
        }

        $this->assertSame(1, (new AutoViolationService)->check('pengajian', '2026-03-04'));
        $this->assertDatabaseHas('pelanggarans', [
            'santri_id' => $santri->id,
            'auto_key' => 'pengajian|'.$santri->id.'|2026-03',
        ]);
    }

    public function test_diniyah_dan_pengaturan_nonaktif_tidak_membuat_pelanggaran(): void
    {
        $santri = $this->buatSantri('NIS-V4', 'Santri Diniyah Alfa');
        foreach (['2026-03-02', '2026-03-03', '2026-03-04'] as $tanggal) {
            $this->seedAktivitas('diniyah', $santri->id, $tanggal, 'Diniyah', 'Alfa');
            $this->seedAktivitas('pengajian', $santri->id, $tanggal, 'Pengajian', 'Alfa');
        }

        $this->assertSame(0, (new AutoViolationService)->check('diniyah', '2026-03-04'));

        config(['simpas.auto_violation.enabled' => false]);
        $this->assertSame(0, (new AutoViolationService)->check('pengajian', '2026-03-04'));
        $this->assertSame(0, Pelanggaran::count());
    }

    public function test_pelanggaran_otomatis_dilewati_jika_santri_sudah_dihapus(): void
    {
        $santri = $this->buatSantri('NIS-V5', 'Santri Dihapus');
        foreach (['2026-03-02', '2026-03-03', '2026-03-04'] as $tanggal) {
            $this->seedAktivitas('pengajian', $santri->id, $tanggal, 'Pengajian', 'Alfa');
        }
        $santri->delete();

        $this->assertSame(0, (new AutoViolationService)->check('pengajian', '2026-03-04'));
    }

    /* ------------------------------------------------------------------ */
    /* ReportService */
    /* ------------------------------------------------------------------ */

    public function test_report_service_activity_menghitung_rekap_keterangan(): void
    {
        $santri = $this->buatSantri('NIS-R1', 'Ahmad Laporan');
        $tanggal = now()->toDateString();
        $this->seedAktivitas('jamaah', $santri->id, $tanggal, 'Subuh', 'Hadir');
        $this->seedAktivitas('jamaah', $santri->id, $tanggal, 'Dzuhur', 'Alfa');

        $hasil = (new ReportService)->activity('jamaah', $this->awalBulan(), $this->akhirBulan());

        $this->assertCount(1, $hasil['rows']);
        $this->assertSame(2, $hasil['keterangan']['total_absensi']);
        $this->assertSame(0, $hasil['keterangan']['total_poin']);
        $this->assertSame(1, $hasil['keterangan']['status']['Hadir']);
        $this->assertSame(1, $hasil['keterangan']['status']['Alfa']);
    }

    public function test_report_service_activity_mendukung_pencarian_dan_filter_santri(): void
    {
        $a = $this->buatSantri('NIS-R2', 'Ahmad Cari');
        $b = $this->buatSantri('NIS-R3', 'Budi Lain');
        $tanggal = now()->toDateString();
        $this->seedAktivitas('jamaah', $a->id, $tanggal, 'Subuh', 'Hadir');
        $this->seedAktivitas('jamaah', $b->id, $tanggal, 'Subuh', 'Hadir');

        $service = new ReportService;
        $semua = $service->activity('jamaah', $this->awalBulan(), $this->akhirBulan());
        $dicari = $service->activity('jamaah', $this->awalBulan(), $this->akhirBulan(), 'Ahmad');
        $satu = $service->activity('jamaah', $this->awalBulan(), $this->akhirBulan(), null, $b->id);

        $this->assertCount(2, $semua['rows']);
        $this->assertCount(1, $dicari['rows']);
        $this->assertCount(1, $satu['rows']);
    }

    public function test_report_service_jenis_tidak_dikenal_menghasilkan_404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new ReportService)->activity('tidak-ada', $this->awalBulan(), $this->akhirBulan());
    }

    public function test_report_service_prestasi_dan_pelanggaran_dengan_rekap(): void
    {
        $a = $this->buatSantri('NIS-R4', 'Ahmad Prestasi');
        $b = $this->buatSantri('NIS-R5', 'Budi Prestasi');
        $tanggal = now()->toDateString();
        $this->seedPrestasi($a->id, $tanggal, 'Akademik', 10);
        $this->seedPrestasi($b->id, $tanggal, 'Akademik', 5);
        $this->seedPelanggaran($a->id, $tanggal, 'Berat', -10);
        $this->seedPelanggaran($b->id, $tanggal, 'Ringan', -3);

        $service = new ReportService;
        $prestasi = $service->build('prestasi', $this->awalBulan(), $this->akhirBulan());
        $prestasiA = $service->build('prestasi', $this->awalBulan(), $this->akhirBulan(), 'Ahmad', $a->id);
        $pelanggaran = $service->build('pelanggaran', $this->awalBulan(), $this->akhirBulan());
        $pelanggaranB = $service->build('pelanggaran', $this->awalBulan(), $this->akhirBulan(), null, $b->id);
        $aktivitas = $service->build('jamaah', $this->awalBulan(), $this->akhirBulan());

        $this->assertSame(2, $prestasi['keterangan']['per_jenis']['Akademik']);
        $this->assertSame(15, $prestasi['keterangan']['total_poin']);
        $this->assertCount(1, $prestasiA['rows']);
        $this->assertSame(1, $pelanggaran['keterangan']['per_tingkat']['Berat']);
        $this->assertSame(-13, $pelanggaran['keterangan']['total_poin']);
        $this->assertCount(1, $pelanggaranB['rows']);
        $this->assertSame('Jamaah', $aktivitas['keterangan']['label']);
    }

    /* ------------------------------------------------------------------ */
    /* LaporanController */
    /* ------------------------------------------------------------------ */

    public function test_laporan_index_untuk_admin_dan_pengalihan_untuk_wali_dan_tamu(): void
    {
        $this->actingAs($this->buatAdmin())->get(route('laporan.index'))->assertOk();
        $this->actingAs($this->buatWali('wali-laporan'))->get(route('laporan.index'))
            ->assertRedirect(route('wali.laporan'));

        auth()->logout();
        $this->get(route('laporan.index'))->assertRedirect(route('login'));
    }

    public function test_laporan_show_print_dan_excel_untuk_semua_jenis(): void
    {
        $admin = $this->buatAdmin();
        $this->isiDataLaporan();

        foreach ($this->semuaJenis() as $jenis) {
            $this->actingAs($admin)->get(route('laporan.show', $jenis))->assertOk();
            $this->actingAs($admin)->get(route('laporan.print', $jenis))->assertOk();
            $this->actingAs($admin)->get(route('laporan.excel', $jenis))->assertOk();
        }
    }

    public function test_laporan_show_mendukung_semua_jenis_periode(): void
    {
        $admin = $this->buatAdmin();
        $this->isiDataLaporan();

        $filter = [
            ['periode' => 'hari', 'tanggal' => now()->toDateString()],
            ['periode' => 'minggu', 'tahun' => now()->year, 'minggu' => now()->isoWeek],
            ['periode' => 'bulan', 'bulan' => now()->month, 'tahun' => now()->year],
            ['periode' => 'tahun', 'tahun' => now()->year],
            ['periode' => 'custom', 'mulai' => now()->startOfMonth()->toDateString(), 'akhir' => now()->endOfMonth()->toDateString()],
            ['periode' => 'semua'],
            ['q' => 'Ahmad'],
        ];

        foreach ($filter as $query) {
            $this->actingAs($admin)->get(route('laporan.show', ['jenis' => 'jamaah'] + $query))->assertOk();
            $this->actingAs($admin)->get(route('laporan.print', ['jenis' => 'prestasi'] + $query))->assertOk();
        }
    }

    public function test_laporan_excel_berisi_judul_keterangan_dan_pencarian(): void
    {
        $admin = $this->buatAdmin();
        $this->isiDataLaporan();

        $response = $this->actingAs($admin)->get(route('laporan.excel', ['jenis' => 'jamaah', 'q' => 'Ahmad']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $isi = $response->getContent();
        $this->assertStringContainsString('LAPORAN Jamaah - SIMPAS', $isi);
        $this->assertStringContainsString('Pencarian: Ahmad', $isi);
        $this->assertStringContainsString('KETERANGAN LAPORAN', $isi);
        $this->assertStringContainsString('Ahmad Anak', $isi);
        $this->assertStringNotContainsString('Budi Lain', $isi);
    }

    public function test_laporan_jenis_tidak_dikenal_menghasilkan_404(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->get(route('laporan.show', 'tidak-ada'))->assertNotFound();
        $this->actingAs($admin)->get(route('laporan.print', 'tidak-ada'))->assertNotFound();
        $this->actingAs($admin)->get(route('laporan.excel', 'tidak-ada'))->assertNotFound();
    }

    public function test_laporan_wali_dialihkan_ke_halaman_wali_untuk_show_print_dan_excel(): void
    {
        $wali = $this->buatWali('wali-laporan-2');
        $tujuan = route('wali.laporan', ['jenis' => 'jamaah']);

        $this->actingAs($wali)->get(route('laporan.show', 'jamaah'))->assertRedirect($tujuan);
        $this->actingAs($wali)->get(route('laporan.print', 'jamaah'))->assertRedirect($tujuan);
        $this->actingAs($wali)->get(route('laporan.excel', 'jamaah'))->assertRedirect($tujuan);
    }

    public function test_laporan_santri_hanya_melihat_datanya_sendiri(): void
    {
        [$anak, $lain] = $this->isiDataLaporan();
        $akun = $this->buatAkunSantri($anak);

        foreach (['jamaah', 'pengajian', 'prestasi', 'pelanggaran'] as $jenis) {
            $this->actingAs($akun)->get(route('laporan.show', $jenis))->assertOk();
            $this->actingAs($akun)->get(route('laporan.print', $jenis))->assertOk();

            $excel = $this->actingAs($akun)->get(route('laporan.excel', $jenis));
            $excel->assertOk();
            $this->assertStringContainsString('Ahmad Anak', $excel->getContent());
            $this->assertStringNotContainsString('Budi Lain', $excel->getContent());
        }
    }

    /* ------------------------------------------------------------------ */
    /* ActivityController */
    /* ------------------------------------------------------------------ */

    public function test_menu_aktivitas_untuk_admin_santri_dan_wali(): void
    {
        $santri = $this->buatSantri('NIS-M1', 'Santri Menu');
        $this->seedAktivitas('jamaah', $santri->id, now()->toDateString(), 'Subuh', 'Hadir');

        $this->actingAs($this->buatAdmin())->get(route('aktivitas.menu'))->assertOk();
        $this->actingAs($this->buatAkunSantri($santri))->get(route('aktivitas.menu'))->assertOk();
        $this->actingAs($this->buatWali('wali-menu'))->get(route('aktivitas.menu'))
            ->assertRedirect(route('wali.dashboard'));
    }

    public function test_form_input_aktivitas_untuk_admin(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-F1', 'Santri Form');
        $this->seedAktivitas('jamaah', $santri->id, '2026-03-11', 'Subuh', 'Hadir');

        $this->actingAs($admin)->get(route('aktivitas.input', 'jamaah'))->assertOk();
        $this->actingAs($admin)->get(route('aktivitas.input', ['type' => 'jamaah', 'tanggal' => '2026-03-11']))->assertOk();
        $this->actingAs($admin)->get(route('aktivitas.input', 'diniyah'))->assertOk();
        $this->actingAs($admin)->get(route('aktivitas.input', 'tidak-ada'))->assertNotFound();
    }

    public function test_form_input_aktivitas_ditolak_untuk_wali(): void
    {
        $this->actingAs($this->buatWali('wali-input'))
            ->get(route('aktivitas.input', 'jamaah'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_store_aktivitas_menyimpan_data_dan_menampilkan_notifikasi(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-S1', 'Santri Simpan');

        $this->actingAs($admin)
            ->post(route('aktivitas.store', 'jamaah'), [
                'tanggal' => '2026-03-11',
                'data' => [$santri->id => ['Subuh' => 'Hadir', 'Dzuhur' => 'Izin']],
            ])
            ->assertRedirect(route('aktivitas.input', ['type' => 'jamaah', 'tanggal' => '2026-03-11']))
            ->assertSessionHas('toast', fn ($toast) => str_contains($toast['message'], '2 baru'));

        $this->assertDatabaseHas('jamaahs', ['santri_id' => $santri->id, 'sesi' => 'Subuh', 'status' => 'Hadir']);
    }

    public function test_store_aktivitas_menyebut_pelanggaran_otomatis(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-S2', 'Santri Alfa Tiga');

        $this->actingAs($admin)
            ->post(route('aktivitas.store', 'jamaah'), [
                'tanggal' => '2026-03-11',
                'data' => [$santri->id => ['Subuh' => 'Alfa', 'Dzuhur' => 'Alfa', 'Ashar' => 'Alfa']],
            ])
            ->assertSessionHas('toast', fn ($toast) => str_contains($toast['message'], 'pelanggaran otomatis'));

        $this->assertSame(1, Pelanggaran::count());
    }

    public function test_store_aktivitas_menolak_data_tidak_valid_dan_jenis_tidak_dikenal(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('aktivitas.store', 'jamaah'), ['tanggal' => 'bukan-tanggal'])
            ->assertSessionHasErrors(['tanggal', 'data']);

        $this->actingAs($admin)
            ->post(route('aktivitas.store', 'tidak-ada'), ['tanggal' => '2026-03-11', 'data' => [1 => ['Subuh' => 'Hadir']]])
            ->assertNotFound();
    }

    public function test_riwayat_aktivitas_untuk_admin_dengan_filter(): void
    {
        $admin = $this->buatAdmin();
        $a = $this->buatSantri('NIS-H1', 'Ahmad Riwayat');
        $b = $this->buatSantri('NIS-H2', 'Budi Riwayat');
        $hariIni = now()->toDateString();
        $this->seedAktivitas('jamaah', $a->id, $hariIni, 'Subuh', 'Hadir');
        $this->seedAktivitas('jamaah', $a->id, $hariIni, 'Dzuhur', 'Alfa');
        $this->seedAktivitas('jamaah', $b->id, $hariIni, 'Subuh', 'Hadir');

        $this->actingAs($admin)->get(route('aktivitas.riwayat', 'jamaah'))->assertOk();
        $this->actingAs($admin)->get(route('aktivitas.riwayat', ['type' => 'jamaah', 'q' => 'Ahmad']))->assertOk();
        $this->actingAs($admin)->get(route('aktivitas.riwayat', ['type' => 'jamaah', 'tanggal' => $hariIni]))->assertOk();
    }

    public function test_riwayat_aktivitas_untuk_santri_wali_dan_jenis_tidak_dikenal(): void
    {
        $santri = $this->buatSantri('NIS-H3', 'Santri Riwayat');
        $this->seedAktivitas('jamaah', $santri->id, now()->toDateString(), 'Subuh', 'Hadir');

        $this->actingAs($this->buatAkunSantri($santri))->get(route('aktivitas.riwayat', 'jamaah'))->assertOk();
        $this->actingAs($this->buatWali('wali-riwayat'))->get(route('aktivitas.riwayat', 'jamaah'))
            ->assertRedirect(route('wali.laporan', ['jenis' => 'jamaah']));
        $this->actingAs($this->buatAdmin())->get(route('aktivitas.riwayat', 'tidak-ada'))->assertNotFound();
    }

    public function test_detail_aktivitas_untuk_admin_dan_santri_sendiri(): void
    {
        $santri = $this->buatSantri('NIS-T1', 'Santri Detail');
        $hariIni = now()->toDateString();
        $this->seedAktivitas('jamaah', $santri->id, $hariIni, 'Subuh', 'Hadir');

        $this->actingAs($this->buatAdmin())
            ->get(route('aktivitas.detail', ['type' => 'jamaah', 'santri' => $santri->id, 'tanggal' => $hariIni]))
            ->assertOk();
        $this->actingAs($this->buatAdmin())
            ->get(route('aktivitas.detail', ['type' => 'jamaah', 'santri' => $santri->id]))
            ->assertOk();
        $this->actingAs($this->buatAkunSantri($santri))
            ->get(route('aktivitas.detail', ['type' => 'jamaah', 'santri' => $santri->id]))
            ->assertOk();
        $this->actingAs($this->buatAdmin())
            ->get(route('aktivitas.detail', ['type' => 'jamaah', 'santri' => 999999]))
            ->assertNotFound();
    }

    public function test_edit_dan_update_aktivitas_untuk_admin(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-E1', 'Santri Edit');
        $this->seedAktivitas('jamaah', $santri->id, '2026-03-11', 'Subuh', 'Alfa');

        $this->actingAs($admin)
            ->get(route('aktivitas.edit', ['type' => 'jamaah', 'santri' => $santri->id, 'tanggal' => '2026-03-11']))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('aktivitas.edit', ['type' => 'jamaah', 'santri' => $santri->id]))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('aktivitas.edit', ['type' => 'jamaah', 'santri' => 999999]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('aktivitas.update', 'jamaah'), [
                'tanggal' => '2026-03-11',
                'santri_id' => $santri->id,
                'data' => ['Subuh' => 'Izin'],
            ])
            ->assertRedirect(route('aktivitas.riwayat', ['type' => 'jamaah', 'tanggal' => '2026-03-11']));

        $this->assertDatabaseHas('jamaahs', ['santri_id' => $santri->id, 'sesi' => 'Subuh', 'status' => 'Izin']);
        $this->assertSame(1, DB::table('jamaahs')->count());
    }

    public function test_update_aktivitas_menolak_data_tidak_valid(): void
    {
        $this->actingAs($this->buatAdmin())
            ->post(route('aktivitas.update', 'jamaah'), ['tanggal' => '2026-03-11'])
            ->assertSessionHasErrors(['santri_id', 'data']);
    }

    public function test_hapus_aktivitas_memindahkan_data_ke_sampah(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-X1', 'Santri Hapus');
        $hariIni = now()->toDateString();
        $this->seedAktivitas('jamaah', $santri->id, $hariIni, 'Subuh', 'Hadir');
        $this->seedAktivitas('jamaah', $santri->id, $hariIni, 'Dzuhur', 'Hadir');

        $this->actingAs($admin)
            ->delete(route('aktivitas.destroy', ['type' => 'jamaah', 'santri' => $santri->id, 'tanggal' => $hariIni]))
            ->assertRedirect()
            ->assertSessionHas('toast', fn ($toast) => str_contains($toast['message'], 'Sampah'));

        $this->assertSame(0, Jamaah::count());
        $this->assertSame(2, Jamaah::onlyTrashed()->count());
    }

    public function test_hapus_aktivitas_tanpa_data_memberi_tahu_pengguna(): void
    {
        $santri = $this->buatSantri('NIS-X2', 'Santri Kosong');

        $this->actingAs($this->buatAdmin())
            ->delete(route('aktivitas.destroy', ['type' => 'jamaah', 'santri' => $santri->id]))
            ->assertSessionHas('toast', fn ($toast) => $toast['message'] === 'Tidak ada data yang dihapus.');
    }

    /* ------------------------------------------------------------------ */
    /* Helper */
    /* ------------------------------------------------------------------ */

    /** @return array<int, string> */
    private function semuaJenis(): array
    {
        return array_merge(array_keys(config('simpas.activities')), ['prestasi', 'pelanggaran']);
    }

    /** @return array{0: Santri, 1: Santri} */
    private function isiDataLaporan(): array
    {
        $anak = $this->buatSantri('NIS-L1', 'Ahmad Anak');
        $lain = $this->buatSantri('NIS-L2', 'Budi Lain');
        $hariIni = now()->toDateString();

        foreach ([$anak, $lain] as $santri) {
            $this->seedAktivitas('jamaah', $santri->id, $hariIni, 'Subuh', 'Hadir');
            $this->seedAktivitas('jamaah', $santri->id, $hariIni, 'Dzuhur', 'Alfa');
            $this->seedAktivitas('pengajian', $santri->id, $hariIni, 'Pengajian', 'Hadir');
            $this->seedPrestasi($santri->id, $hariIni);
            $this->seedPelanggaran($santri->id, $hariIni);
        }

        return [$anak, $lain];
    }

    private function awalBulan(): string
    {
        return now()->startOfMonth()->toDateString();
    }

    private function akhirBulan(): string
    {
        return now()->endOfMonth()->toDateString();
    }

    private function buatAdmin(): User
    {
        return User::create([
            'name' => 'Admin Laporan',
            'username' => 'admin-'.uniqid(),
            'password' => Hash::make('admin-password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function buatWali(string $username): User
    {
        return User::create([
            'name' => 'Wali '.$username,
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => Hash::make('wali-password'),
            'role' => User::ROLE_WALI,
            'is_active' => true,
        ]);
    }

    private function buatAkunSantri(Santri $santri): User
    {
        return User::create([
            'name' => $santri->nama,
            'username' => 'akun-'.$santri->nis,
            'password' => Hash::make('santri-password'),
            'role' => User::ROLE_SANTRI,
            'santri_id' => $santri->id,
            'is_active' => true,
        ]);
    }

    private function buatSantri(string $nis, string $nama, string $jenisDiniyah = 'Diniyah'): Santri
    {
        return Santri::create([
            'nis' => $nis,
            'nama' => $nama,
            'jenis_kelamin' => 'Putra',
            'status_aktif' => 'Aktif',
            'jenis_diniyah' => $jenisDiniyah,
        ]);
    }

    private function seedAktivitas(string $type, int $santriId, string $tanggal, string $sesi, string $status, ?int $poin = null, ?string $deletedAt = null): void
    {
        $cfg = config("simpas.activities.{$type}");

        DB::table($cfg['table'])->insert([
            'santri_id' => $santriId,
            'tanggal' => $tanggal,
            'sesi' => $sesi,
            'status' => $status,
            'poin' => $poin ?? $cfg['model']::pointsFor($status),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }

    private function seedPrestasi(int $santriId, string $tanggal, string $jenis = 'Akademik', int $poin = 10): void
    {
        DB::table('prestasis')->insert([
            'santri_id' => $santriId,
            'tanggal' => $tanggal,
            'jenis' => $jenis,
            'nama_prestasi' => 'Juara Lomba',
            'tingkat' => 'Pondok',
            'poin' => $poin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPelanggaran(int $santriId, string $tanggal, string $tingkat = 'Ringan', int $poin = -3): void
    {
        DB::table('pelanggarans')->insert([
            'santri_id' => $santriId,
            'tanggal' => $tanggal,
            'jenis' => 'Alfa',
            'tingkat' => $tingkat,
            'poin' => $poin,
            'is_auto' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

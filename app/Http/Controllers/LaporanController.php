<?php

namespace App\Http\Controllers;

use App\Services\ExcelExporter;
use App\Services\PeriodResolver;
use App\Services\ReportService;
use Illuminate\Http\Request;

/**
 * LAPORAN (bagian 26 - 35)
 *
 * Halaman awal hanya kartu jenis laporan.
 * Detail: Filter -> Tabel absensi -> Keterangan otomatis (di bawah tabel).
 * Print & Excel mengikuti filter yang sedang digunakan (bagian 32).
 */
class LaporanController extends Controller
{
    private const JENIS = [
        'jamaah' => ['Jamaah', 'building-2', 'emerald'],
        'pengajian' => ['Pengajian', 'book-open', 'amber'],
        'deresan' => ['Deresan', 'library-big', 'sky'],
        'sorogan' => ['Sorogan', 'book-copy', 'violet'],
        'murojaah' => ['Murojaah', 'bookmark-check', 'teal'],
        'ratiban' => ['Ratiban', 'hand-heart', 'rose'],
        'diniyah' => ['Diniyah', 'graduation-cap', 'indigo'],
        'prestasi' => ['Prestasi', 'trophy', 'green'],
        'pelanggaran' => ['Pelanggaran', 'triangle-alert', 'red'],
    ];

    public function index(Request $request)
    {
        if (auth()->user()?->isWali()) {
            return redirect()->route('wali.laporan');
        }

        $cards = [];
        foreach (self::JENIS as $key => [$label, $icon, $color]) {
            $cards[$key] = [
                'label' => $label,
                'icon' => $icon,
                'color' => $color,
            ];
        }

        return view('laporan.index', ['cards' => $cards]);
    }

    public function show(Request $request, string $jenis, ReportService $report)
    {
        if (auth()->user()?->isWali()) {
            return redirect()->route('wali.laporan', array_merge($request->query(), ['jenis' => $jenis]));
        }

        $this->jenis($jenis);
        $periode = PeriodResolver::resolve($request->periode, $request->all());
        $cari = trim((string) $request->q) ?: null;
        $user = auth()->user();
        $santriId = $user->isWaliOrSantri() ? $user->santri_id : null;

        $data = $report->build(
            $jenis,
            $periode['mulai']->format('Y-m-d'),
            $periode['akhir']->format('Y-m-d'),
            $cari,
            $santriId
        );

        // Santri/wali: laporan hanya berisi datanya sendiri
        if ($user->isSantri()) {
            $data = $this->batasiSantri($data, $user->santri_id, $jenis, $periode, $cari, $report);
        }

        return view('laporan.show', [
            'jenis' => $jenis,
            'label' => self::JENIS[$jenis][0],
            'icon' => self::JENIS[$jenis][1],
            'color' => self::JENIS[$jenis][2],
            'periode' => $periode,
            'cari' => $cari,
            'rows' => $data['rows'],
            'keterangan' => $data['keterangan'],
            'isActivity' => !in_array($jenis, ['prestasi', 'pelanggaran']),
            'sessions' => $jenis !== 'prestasi' && $jenis !== 'pelanggaran'
                ? config("simpas.activities.{$jenis}.sessions")
                : [],
            'input' => $request->all(),
            'tahun' => now()->year,
            'minggu' => (int) $request->input('minggu', now()->isoWeek),
            'bulan' => (int) $request->input('bulan', now()->month),
        ]);
    }

    public function print(Request $request, string $jenis, ReportService $report)
    {
        if (auth()->user()?->isWali()) {
            return redirect()->route('wali.laporan', array_merge($request->query(), ['jenis' => $jenis]));
        }

        $this->jenis($jenis);
        $periode = PeriodResolver::resolve($request->periode, $request->all());
        $cari = trim((string) $request->q) ?: null;
        $user = auth()->user();
        $santriId = $user->isWaliOrSantri() ? $user->santri_id : null;

        $data = $report->build(
            $jenis,
            $periode['mulai']->format('Y-m-d'),
            $periode['akhir']->format('Y-m-d'),
            $cari,
            $santriId
        );

        if ($user->isSantri()) {
            $data = $this->batasiSantri($data, $user->santri_id, $jenis, $periode, $cari, $report);
        }

        return view('laporan.print', [
            'jenis' => $jenis,
            'label' => self::JENIS[$jenis][0],
            'periode' => $periode,
            'cari' => $cari,
            'rows' => $data['rows'],
            'keterangan' => $data['keterangan'],
            'isActivity' => !in_array($jenis, ['prestasi', 'pelanggaran']),
            'sessions' => !in_array($jenis, ['prestasi', 'pelanggaran'])
                ? config("simpas.activities.{$jenis}.sessions")
                : [],
        ]);
    }

    public function excel(Request $request, string $jenis, ReportService $report)
    {
        if (auth()->user()?->isWali()) {
            return redirect()->route('wali.laporan', array_merge($request->query(), ['jenis' => $jenis]));
        }

        $this->jenis($jenis);
        $periode = PeriodResolver::resolve($request->periode, $request->all());
        $cari = trim((string) $request->q) ?: null;
        $user = auth()->user();
        $santriId = $user->isWaliOrSantri() ? $user->santri_id : null;

        $data = $report->build(
            $jenis,
            $periode['mulai']->format('Y-m-d'),
            $periode['akhir']->format('Y-m-d'),
            $cari,
            $santriId
        );

        if ($user->isSantri()) {
            $data = $this->batasiSantri($data, $user->santri_id, $jenis, $periode, $cari, $report);
        }

        $label = self::JENIS[$jenis][0];
        [$headers, $rows] = $this->keTabel($jenis, $data);

        $keterangan = [];
        foreach (($data['keterangan']['status'] ?? $data['keterangan']['per_jenis'] ?? $data['keterangan']['per_tingkat'] ?? []) as $k => $v) {
            if ($v) {
                $keterangan[$k] = $v;
            }
        }
        $keterangan['Total Absensi'] = $data['keterangan']['total_absensi'];
        $keterangan['Total Poin'] = $data['keterangan']['total_poin'];

        $nama = 'LAPORAN_' . strtoupper($jenis) . '_' . $periode['mulai']->format('Ymd') . '_' . $periode['akhir']->format('Ymd');

        return ExcelExporter::download($nama, $headers, $rows, [
            'title' => "LAPORAN {$label} - SIMPAS",
            'periode' => $periode['label'] . ($cari ? " | Pencarian: {$cari}" : ''),
            'keterangan' => $keterangan,
        ]);
    }

    /* -------------------------------------------------------------- */

    private function jenis(string $jenis): void
    {
        abort_unless(isset(self::JENIS[$jenis]), 404, 'Jenis laporan tidak dikenal.');
    }

    private function batasiSantri(array $data, ?int $santriId, string $jenis, array $periode, ?string $cari, ReportService $report): array
    {
        if ($jenis === 'prestasi') {
            $items = $data['rows']->where('santri_id', $santriId)->values();
        } elseif ($jenis === 'pelanggaran') {
            $items = $data['rows']->where('santri_id', $santriId)->values();
        } else {
            $items = collect($data['rows'])->filter(fn ($r) => $r['santri']?->id === $santriId)->values();
        }

        $rows = $items;
        $totalPoin = 0;
        $status = array_fill_keys(config("simpas.activities.{$jenis}.statuses", []), 0);

        foreach ($items as $item) {
            if ($jenis === 'prestasi' || $jenis === 'pelanggaran') {
                $totalPoin += (int) $item->poin;
            } elseif ($jenis === 'jamaah') {
                foreach ($item['sesi'] as $s) {
                    $status[$s] = ($status[$s] ?? 0) + 1;
                    $totalPoin += (int) config("simpas.points.{$s}", 0);
                }
            } else {
                foreach ($item['sesi'] as $s) {
                    $status[$s] = ($status[$s] ?? 0) + 1;
                }
                $totalPoin += (int) $item['total'];
            }
        }

        return [
            'rows' => $rows,
            'keterangan' => array_merge($data['keterangan'], [
                'status' => $status,
                'total_absensi' => $jenis === 'prestasi' || $jenis === 'pelanggaran' ? $items->count() : collect($items)->sum(fn ($i) => count($i['sesi'])),
                'total_poin' => $totalPoin,
                'jumlah_baris' => $items->count(),
            ]),
        ];
    }

    /** Ubah data laporan menjadi [headers, rows] untuk print/excel. */
    private function keTabel(string $jenis, array $data): array
    {
        if ($jenis === 'prestasi') {
            $headers = ['No', 'Tanggal', 'NIS', 'Nama', 'Jenis', 'Prestasi', 'Tingkat', 'Keterangan', 'Poin'];
            $rows = [];
            foreach ($data['rows'] as $i => $r) {
                $rows[] = [
                    $i + 1,
                    $r->tanggal->format('d M Y'),
                    $r->santri->nis ?? '-',
                    $r->santri->nama ?? '-',
                    $r->jenis,
                    $r->nama_prestasi,
                    $r->tingkat,
                    $r->keterangan ?? '-',
                    $r->poin,
                ];
            }

            return [$headers, $rows];
        }

        if ($jenis === 'pelanggaran') {
            $headers = ['No', 'Tanggal', 'NIS', 'Nama', 'Jenis Pelanggaran', 'Tingkat', 'Keterangan', 'Poin'];
            $rows = [];
            foreach ($data['rows'] as $i => $r) {
                $rows[] = [
                    $i + 1,
                    $r->tanggal->format('d M Y'),
                    $r->santri->nis ?? '-',
                    $r->santri->nama ?? '-',
                    $r->jenis,
                    $r->tingkat,
                    $r->keterangan ?? '-',
                    $r->poin,
                ];
            }

            return [$headers, $rows];
        }

        $sessions = config("simpas.activities.{$jenis}.sessions");
        $headers = array_merge(['No', 'NIS', 'Nama Santri', 'Tanggal'], $sessions, ['Total Poin']);

        $rows = [];
        foreach ($data['rows'] as $i => $r) {
            $baris = [
                $i + 1,
                $r['santri']->nis ?? '-',
                $r['santri']->nama ?? '-',
                $r['tanggal']->format('d M Y'),
            ];
            foreach ($sessions as $s) {
                $baris[] = $r['sesi'][$s] ?? '-';
            }
            $baris[] = $r['total'];
            $rows[] = $baris;
        }

        return [$headers, $rows];
    }
}

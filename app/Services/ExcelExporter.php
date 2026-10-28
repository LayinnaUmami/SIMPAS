<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;

/**
 * Export Excel tanpa dependensi eksternal.
 * Menghasilkan file .xls (HTML table) yang dapat dibuka langsung oleh
 * Microsoft Excel, LibreOffice, dan Google Sheets.
 */
class ExcelExporter
{
    /**
     * @param  array  $headers  label kolom
     * @param  array  $rows     baris data (array of array)
     * @param  array  $meta     judul, periode, keterangan (opsional)
     */
    public static function download(string $filename, array $headers, array $rows, array $meta = []): Response
    {
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" '
            . 'xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook>'
            . '<x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Data</x:Name>'
            . '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->'
            . '<style>td,th{border:1px solid #999;padding:4px 8px;font-family:Arial;font-size:10pt;}'
            . 'th{background:#059669;color:#fff;font-weight:bold;}'
            . '.title{font-size:14pt;font-weight:bold;border:none;}'
            . '.sub{border:none;color:#555;}</style></head><body>';

        // Meta: judul & periode
        if (!empty($meta['title'])) {
            $html .= '<table><tr><td class="title">' . $e($meta['title']) . '</td></tr>';
            if (!empty($meta['periode'])) {
                $html .= '<tr><td class="sub">' . $e($meta['periode']) . '</td></tr>';
            }
            if (!empty($meta['cari'])) {
                $html .= '<tr><td class="sub">Pencarian: ' . $e($meta['cari']) . '</td></tr>';
            }
            $html .= '<tr><td class="sub">Dicetak: ' . $e(now()->translatedFormat('d F Y H:i')) . '</td></tr></table><br>';
        }

        // Tabel utama
        $html .= '<table><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . $e($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $e($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Keterangan laporan (di bawah tabel)
        if (!empty($meta['keterangan'])) {
            $html .= '<br><table><thead><tr><th colspan="2">KETERANGAN LAPORAN</th></tr></thead><tbody>';
            foreach ($meta['keterangan'] as $label => $value) {
                $html .= '<tr><td>' . $e($label) . '</td><td>' . $e($value) . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xls"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}

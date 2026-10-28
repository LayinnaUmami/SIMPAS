<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

echo "=== SANTRI_RQ_SESUAI_TEMPLATE.xlsx ===\n";
try {
    $spreadsheet = IOFactory::load('C:/Users/asus/Downloads/SANTRI_RQ_SESUAI_TEMPLATE.xlsx');
    foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
        echo "Sheet $sheetIndex: " . $sheet->getTitle() . "\n";
        $rows = $sheet->toArray();
        foreach ($rows as $i => $row) {
            echo "Row $i: " . implode(' | ', array_map(fn($v) => $v ?? '', $row)) . "\n";
            if ($i > 5) break;
        }
        echo "... (total rows: " . count($rows) . ")\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== daftar_user_password_roudlotul_quran.xlsx ===\n";
try {
    $spreadsheet = IOFactory::load('C:/Users/asus/Downloads/daftar_user_password_roudlotul_quran.xlsx');
    foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
        echo "Sheet $sheetIndex: " . $sheet->getTitle() . "\n";
        $rows = $sheet->toArray();
        foreach ($rows as $i => $row) {
            echo "Row $i: " . implode(' | ', array_map(fn($v) => $v ?? '', $row)) . "\n";
            if ($i > 10) break;
        }
        echo "... (total rows: " . count($rows) . ")\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
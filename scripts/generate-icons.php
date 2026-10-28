<?php

/**
 * Generator aset ikon SIMPAS (PWA + Android launcher).
 *
 * Cara pakai:
 *   php scripts/generate-icons.php
 *
 * Menghasilkan:
 *   - public/icons/icon-192.png, icon-512.png, icon-512-maskable.png,
 *     apple-touch-icon-180.png, favicon-32.png, favicon-16.png
 *   - android/app/src/main/res/mipmap-{m,h,x,xx,xxx}dpi/ic_launcher.png
 *
 * Semua gambar dibuat dengan PHP GD:
 *   - latar gradien vertikal emerald (#059669 -> #047857)
 *   - sudut membulat transparan (kecuali varian maskable: full bleed)
 *   - huruf "S" putih tebal memakai font TTF Windows (Segoe UI Bold)
 *   - bintang kecil amber di pojok kanan atas
 */

declare(strict_types=1);

$root = dirname(__DIR__);

const TOP_COLOR    = [5, 150, 105];   // #059669
const BOTTOM_COLOR = [4, 120, 87];    // #047857
const TEXT_COLOR   = [255, 255, 255]; // putih
const STAR_COLOR   = [245, 158, 11];  // #f59e0b amber

/** @var array<int, string> Daftar font tebal yang dipakai, dipilih yang pertama tersedia. */
$fontCandidates = [
    'C:/Windows/Fonts/segoeuib.ttf',  // Segoe UI Bold
    'C:/Windows/Fonts/arialbd.ttf',   // Arial Bold (cadangan)
    'C:/Windows/Fonts/verdanab.ttf',  // Verdana Bold (cadangan)
];

$fontFile = null;
foreach ($fontCandidates as $candidate) {
    if (is_file($candidate)) {
        $fontFile = $candidate;
        break;
    }
}

if ($fontFile === null) {
    fwrite(STDERR, "ERROR: font TTF tebal tidak ditemukan.\n");
    exit(1);
}

/**
 * Daftar target yang akan digenerate.
 *
 * Format: [path relatif terhadap root, ukuran px, varian]
 * Varian: 'any' (ikon biasa bersudut membulat), 'maskable' (full bleed),
 *         'favicon' (ikon kecil, radius lebih kecil).
 */
$targets = [
    // PWA
    ['public/icons/icon-192.png', 192, 'any'],
    ['public/icons/icon-512.png', 512, 'any'],
    ['public/icons/icon-512-maskable.png', 512, 'maskable'],
    ['public/icons/apple-touch-icon-180.png', 180, 'any'],
    ['public/icons/favicon-32.png', 32, 'favicon'],
    ['public/icons/favicon-16.png', 16, 'favicon'],
    // Android launcher densities
    ['android/app/src/main/res/mipmap-mdpi/ic_launcher.png', 48, 'any'],
    ['android/app/src/main/res/mipmap-hdpi/ic_launcher.png', 72, 'any'],
    ['android/app/src/main/res/mipmap-xhdpi/ic_launcher.png', 96, 'any'],
    ['android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png', 144, 'any'],
    ['android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png', 192, 'any'],
];

/**
 * Buat gambar truecolor berukuran $size x $size dengan latar gradien emerald vertikal.
 */
function createGradientCanvas(int $size): GdImage
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    imagealphablending($img, false);

    $top = TOP_COLOR;
    $bottom = BOTTOM_COLOR;

    for ($y = 0; $y < $size; $y++) {
        $t = $size > 1 ? $y / ($size - 1) : 0.0;
        $r = (int) round($top[0] + ($bottom[0] - $top[0]) * $t);
        $g = (int) round($top[1] + ($bottom[1] - $top[1]) * $t);
        $b = (int) round($top[2] + ($bottom[2] - $top[2]) * $t);
        $color = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, $y, $size - 1, $y, $color);
    }

    return $img;
}

/**
 * Potong empat sudut agar membulat (transparan) — khas rounded-rectangle icon.
 */
function applyRoundedCorners(GdImage $img, int $size, int $radius): void
{
    if ($radius <= 0) {
        return;
    }

    // Alpha 127 = transparan penuh pada GD.
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

    for ($corner = 0; $corner < 4; $corner++) {
        $ox = ($corner === 0 || $corner === 3) ? 0 : $size - $radius;
        $oy = ($corner < 2) ? 0 : $size - $radius;
        $cx = $ox + $radius;
        $cy = $oy + $radius;

        for ($y = $oy; $y < $oy + $radius; $y++) {
            for ($x = $ox; $x < $ox + $radius; $x++) {
                $dx = ($x + 0.5) - $cx;
                $dy = ($y + 0.5) - $cy;
                if (($dx * $dx + $dy * $dy) > ($radius * $radius)) {
                    imagesetpixel($img, $x, $y, $transparent);
                }
            }
        }
    }
}

/**
 * Gambar bintang 4 sudut (amber) di posisi tengah ($cx, $cy).
 */
function drawStar(GdImage $img, float $cx, float $cy, float $outer, float $inner, int $color): void
{
    $points = [];
    $spikes = 4;

    for ($k = 0; $k < $spikes * 2; $k++) {
        $rad = ($k % 2 === 0) ? $outer : $inner;
        $angle = deg2rad($k * 180 / $spikes - 90); // mulai dari puncak atas
        $points[] = (int) round($cx + $rad * cos($angle));
        $points[] = (int) round($cy + $rad * sin($angle));
    }

    imagefilledpolygon($img, $points, intdiv(count($points), 2), $color);
}

/**
 * Gambar huruf "S" putih tebal, di-center horizontal & vertikal pada kotak tertentu.
 */
function drawLetterS(GdImage $img, string $fontFile, int $size, float $fontRatio, int $color): void
{
    $fontSize = max(6, (int) round($size * $fontRatio));
    $bbox = imagettfbbox($fontSize, 0, $fontFile, 'S');
    if ($bbox === false) {
        return;
    }

    $textWidth  = $bbox[4] - $bbox[0];
    $textHeight = $bbox[5] - $bbox[1];

    $x = (int) round(($size - $textWidth) / 2 - $bbox[0]);
    $y = (int) round(($size - $textHeight) / 2 - $bbox[1]);

    imagettftext($img, $fontSize, 0, $x, $y, $color, $fontFile, 'S');
}

/**
 * Render satu ikon lengkap ke file PNG.
 */
function generateIcon(string $outPath, int $size, string $variant, string $fontFile): bool
{
    $img = createGradientCanvas($size);

    $isMaskable = ($variant === 'maskable');

    if (!$isMaskable) {
        // Radius membulat: 20% untuk ikon biasa, 18% untuk favicon kecil.
        $radiusRatio = ($variant === 'favicon') ? 0.18 : 0.20;
        applyRoundedCorners($img, $size, max(1, (int) round($size * $radiusRatio)));
    }

    // Dari sini semua gambar dibuat OPAQUE di atas latar, jadi aktifkan alpha blending
    // agar tepi anti-aliased huruf TTF ter-blend rapi dengan latar.
    imagealphablending($img, true);

    $white = imagecolorallocate($img, TEXT_COLOR[0], TEXT_COLOR[1], TEXT_COLOR[2]);
    $amber = imagecolorallocate($img, STAR_COLOR[0], STAR_COLOR[1], STAR_COLOR[2]);

    if ($isMaskable) {
        // Maskable: latar penuh emerald + logo di tengah dengan margin aman 20%.
        // Safe zone = area 60% di tengah, jadi font dibuat ~40% dari ukuran.
        drawLetterS($img, $fontFile, $size, 0.40, $white);
        // Bintang ditempatkan di kuadran kanan-atas tapi tetap di dalam safe zone.
        if ($size >= 48) {
            drawStar($img, $size * 0.73, $size * 0.27, $size * 0.05, $size * 0.019, $amber);
        }
    } else {
        drawLetterS($img, $fontFile, $size, ($variant === 'favicon' ? 0.58 : 0.52), $white);
        // Bintang kecil di pojok kanan atas (di dalam area membulat).
        if ($size >= 32) {
            drawStar($img, $size * 0.81, $size * 0.18, $size * 0.07, $size * 0.027, $amber);
        }
    }

    $dir = dirname($outPath);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, "ERROR: gagal membuat direktori {$dir}\n");
        imagedestroy($img);
        return false;
    }

    if (!imagepng($img, $outPath)) {
        fwrite(STDERR, "ERROR: gagal menulis {$outPath}\n");
        imagedestroy($img);
        return false;
    }

    imagedestroy($img);

    $kb = filesize($outPath) / 1024;
    $root = dirname(__DIR__);
    printf(
        "  OK  %-70s %4d x %-4d  %6.1f KB\n",
        ltrim(str_replace($root, '', str_replace('\\', '/', $outPath)), '/'),
        $size,
        $size,
        $kb
    );

    return true;
}

echo "Generate ikon SIMPAS (font: {$fontFile})\n";

$failed = 0;
foreach ($targets as [$rel, $size, $variant]) {
    $out = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!generateIcon($out, $size, $variant, $fontFile)) {
        $failed++;
    }
}

if ($failed > 0) {
    fwrite(STDERR, "Selesai dengan {$failed} kegagalan.\n");
    exit(1);
}

echo "Selesai. Semua ikon berhasil digenerate.\n";

<?php
/**
 * Mail Forge – PWA Icon Generator
 *
 * Generates PNG icons for the Mail Forge PWA manifest.
 * Design: solid circle (#0d6efd) with white "MF" text centred.
 *
 * CLI usage:  php generate-icons.php
 * HTTP usage: browse to /assets/icons/generate-icons.php
 */

declare(strict_types=1);

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

$outputDir = __DIR__;

$brandR = 13;
$brandG = 110;
$brandB = 253;

$generated = [];
$errors    = [];

foreach ($sizes as $size) {
    $filename = $outputDir . DIRECTORY_SEPARATOR . "icon-{$size}x{$size}.png";

    $img = imagecreatetruecolor($size, $size);
    if ($img === false) {
        $errors[] = "imagecreatetruecolor failed for size {$size}";
        continue;
    }

    // Transparent background so corners are clear
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    imagealphablending($img, true);

    $blue  = imagecolorallocate($img, $brandR, $brandG, $brandB);
    $white = imagecolorallocate($img, 255, 255, 255);

    // Filled circle (centred, radius = 47 % of size for a small margin)
    $radius  = (int)round($size * 0.47);
    $cx      = (int)round($size / 2);
    $cy      = (int)round($size / 2);
    $x1      = $cx - $radius;
    $y1      = $cy - $radius;
    $x2      = $cx + $radius;
    $y2      = $cy + $radius;
    imagefilledellipse($img, $cx, $cy, $x2 - $x1, $y2 - $y1, $blue);

    // Try TTF text first; fall back to built-in bitmap font
    $label    = 'MF';
    $fontFile = __DIR__ . '/RobotoBold.ttf';   // optional – place font here
    $drawn    = false;

    if (function_exists('imagettftext') && is_readable($fontFile)) {
        $fontSize = (int)round($size * 0.28);
        // Measure text bounding box to centre it precisely
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $label);
        if ($bbox !== false) {
            $textW  = abs($bbox[4] - $bbox[0]);
            $textH  = abs($bbox[5] - $bbox[1]);
            $textX  = (int)round($cx - $textW / 2);
            $textY  = (int)round($cy + $textH / 2);
            imagettftext($img, $fontSize, 0, $textX, $textY, $white, $fontFile, $label);
            $drawn = true;
        }
    }

    if (!$drawn) {
        // Built-in bitmap fonts: 1-5 (font 5 is largest ~9×15 px)
        // Pick the largest font that fits within ~40 % of the icon width
        $builtinFont = 5;
        $charW = imagefontwidth($builtinFont);
        $charH = imagefontheight($builtinFont);
        $textW = $charW * strlen($label);
        $textH = $charH;

        // Scale up by tiling multiple draws isn't possible with built-ins;
        // for small icons the built-in font is readable enough.
        $textX = (int)round($cx - $textW / 2);
        $textY = (int)round($cy - $textH / 2);
        imagestring($img, $builtinFont, $textX, $textY, $label, $white);
    }

    $ok = imagepng($img, $filename, 9);
    imagedestroy($img);

    if ($ok) {
        $generated[] = basename($filename);
    } else {
        $errors[] = "Failed to write {$filename}";
    }
}

// ── Output ────────────────────────────────────────────────────────────────────
$isCli = PHP_SAPI === 'cli';

if ($isCli) {
    foreach ($generated as $f) {
        echo "  [OK] {$f}\n";
    }
    foreach ($errors as $e) {
        echo "  [FAIL] {$e}\n";
    }
    echo count($errors) === 0
        ? "\nAll " . count($generated) . " icons generated successfully.\n"
        : "\nCompleted with " . count($errors) . " error(s).\n";
    exit(count($errors) > 0 ? 1 : 0);
} else {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<title>Icon Generator – Mail Forge</title>'
        . '<style>body{font-family:system-ui,sans-serif;max-width:600px;margin:2rem auto;padding:0 1rem}'
        . '.ok{color:#198754}.err{color:#dc3545}'
        . '</style></head><body>'
        . '<h2>Mail Forge – PWA Icon Generator</h2>';

    if (!empty($generated)) {
        echo '<p class="ok">&#10003; Generated ' . count($generated) . ' icon(s):</p><ul>';
        foreach ($generated as $f) {
            echo '<li>' . htmlspecialchars($f, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        echo '</ul>';
    }

    if (!empty($errors)) {
        echo '<p class="err">&#10007; Errors:</p><ul>';
        foreach ($errors as $e) {
            echo '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        echo '</ul>';
    }

    echo '</body></html>';
}

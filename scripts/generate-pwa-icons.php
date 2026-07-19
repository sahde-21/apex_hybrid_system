<?php

$outDir = dirname(__DIR__) . '/public/icons';
$splashDir = dirname(__DIR__) . '/public/splash';

if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}
if (! is_dir($splashDir)) {
    mkdir($splashDir, 0755, true);
}

function scfIcon(int $size, bool $maskable = false): GdImage
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $pad = $maskable ? (int) round($size * 0.12) : (int) round($size * 0.06);
    $radius = (int) round(($size - 2 * $pad) * 0.22);

    $navy = imagecolorallocate($img, 2, 6, 23);
    $blue = imagecolorallocate($img, 37, 99, 235);
    $sky = imagecolorallocate($img, 56, 189, 248);
    $white = imagecolorallocate($img, 255, 255, 255);

    $box = $size - 2 * $pad;
    imagefilledrectangle($img, $pad + $radius, $pad, $pad + $box - $radius, $pad + $box, $navy);
    imagefilledrectangle($img, $pad, $pad + $radius, $pad + $box, $pad + $box - $radius, $navy);
    imagefilledellipse($img, $pad + $radius, $pad + $radius, $radius * 2, $radius * 2, $navy);
    imagefilledellipse($img, $pad + $box - $radius, $pad + $radius, $radius * 2, $radius * 2, $navy);
    imagefilledellipse($img, $pad + $radius, $pad + $box - $radius, $radius * 2, $radius * 2, $navy);
    imagefilledellipse($img, $pad + $box - $radius, $pad + $box - $radius, $radius * 2, $radius * 2, $navy);

    $orb = (int) round($size * 0.45);
    imagefilledellipse($img, (int) ($size * 0.72), (int) ($size * 0.28), $orb, $orb, $blue);
    imagefilledellipse($img, (int) ($size * 0.30), (int) ($size * 0.75), (int) ($orb * 0.7), (int) ($orb * 0.7), $sky);

    $text = 'SCF';
    $fontFile = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';
    if (! is_file($fontFile)) {
        $fontFile = '/System/Library/Fonts/Helvetica.ttc';
    }

    if (is_file($fontFile) && function_exists('imagettftext')) {
        $fontSize = (int) max(10, round($size * 0.22));
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $tw = abs($bbox[2] - $bbox[0]);
        $th = abs($bbox[7] - $bbox[1]);
        $x = (int) (($size - $tw) / 2);
        $y = (int) (($size + $th) / 2);
        imagettftext($img, $fontSize, 0, $x, $y, $white, $fontFile, $text);
    } else {
        $font = 5;
        $tw = imagefontwidth($font) * strlen($text);
        $th = imagefontheight($font);
        imagestring($img, $font, (int) (($size - $tw) / 2), (int) (($size - $th) / 2), $text, $white);
    }

    return $img;
}

function savePng(GdImage $img, string $path): void
{
    imagepng($img, $path, 9);
    imagedestroy($img);
}

foreach ([72, 96, 128, 144, 152, 180, 192, 256, 384, 512] as $size) {
    savePng(scfIcon($size, false), "{$outDir}/icon-{$size}x{$size}.png");
}

savePng(scfIcon(192, true), "{$outDir}/maskable-192x192.png");
savePng(scfIcon(512, true), "{$outDir}/maskable-512x512.png");
savePng(scfIcon(180, false), dirname(__DIR__) . '/public/apple-touch-icon.png');

$splashes = [
    'iphone5' => [640, 1136],
    'iphone6' => [750, 1334],
    'iphone6plus' => [1242, 2208],
    'iphone-x' => [1125, 2436],
    'iphone-xr' => [828, 1792],
    'iphone-xs-max' => [1242, 2688],
    'ipad' => [1536, 2048],
    'ipadpro10' => [1668, 2224],
    'ipadpro11' => [1668, 2388],
    'ipadpro12' => [2048, 2732],
    'android-portrait' => [1080, 1920],
    'android-landscape' => [1920, 1080],
];

foreach ($splashes as $name => [$w, $h]) {
    $canvas = imagecreatetruecolor($w, $h);
    $navy = imagecolorallocate($canvas, 2, 6, 23);
    imagefill($canvas, 0, 0, $navy);
    $iconSize = (int) (min($w, $h) * 0.28);
    $icon = scfIcon(max(128, $iconSize), false);
    $iw = imagesx($icon);
    $ih = imagesy($icon);
    $dx = (int) (($w - $iw) / 2);
    $dy = (int) (($h - $ih) / 2 - $h * 0.05);
    imagecopy($canvas, $icon, $dx, $dy, 0, 0, $iw, $ih);
    imagedestroy($icon);
    $white = imagecolorallocate($canvas, 226, 232, 240);
    $label = 'SCF Enterprise';
    $fontFile = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';
    if (! is_file($fontFile)) {
        $fontFile = '/System/Library/Fonts/Helvetica.ttc';
    }
    if (is_file($fontFile)) {
        $fs = (int) max(18, round(min($w, $h) * 0.035));
        $bbox = imagettfbbox($fs, 0, $fontFile, $label);
        $tw = abs($bbox[2] - $bbox[0]);
        imagettftext($canvas, $fs, 0, (int) (($w - $tw) / 2), (int) ($dy + $ih + $fs * 2.2), $white, $fontFile, $label);
    }
    imagepng($canvas, "{$splashDir}/splash-{$name}.png", 9);
    imagedestroy($canvas);
}

echo 'icons='.count(glob("{$outDir}/*.png")).' splash='.count(glob("{$splashDir}/*.png")).PHP_EOL;

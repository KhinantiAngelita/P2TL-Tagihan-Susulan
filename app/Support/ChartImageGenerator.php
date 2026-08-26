<?php

namespace App\Support;

class ChartImageGenerator
{
    /** Faktor supersampling — render lebih besar lalu ditampilkan mengecil = lebih halus/HD */
    private const SCALE = 2;

    private static function fontPath(): string
    {
        $candidates = [
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
            base_path('resources/fonts/DejaVuSans.ttf'),
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        // fallback: kalau tidak ketemu, GD akan error saat imagettftext dipanggil,
        // jadi pastikan salah satu path di atas valid di server kamu.
        return $candidates[0];
    }

    private static function newCanvas(int $w, int $h)
    {
        $img = imagecreatetruecolor($w, $h);
        imageantialias($img, true);
        imagesavealpha($img, true);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);
        return $img;
    }

    private static function ttext($img, float $size, int $x, int $y, string $text, $color, bool $bold = false): void
    {
        imagettftext($img, $size, 0, $x, $y, $color, self::fontPath(), $text);
    }

    private static function centeredTtext($img, float $size, int $cx, int $y, string $text, $color): void
    {
        $box = imagettfbbox($size, 0, self::fontPath(), $text);
        $textWidth = abs($box[2] - $box[0]);
        self::ttext($img, $size, (int) ($cx - $textWidth / 2), $y, $text, $color);
    }

    public static function barChart(array $labels, array $values, array $subLine1, array $subLine2, array $colors, int $width = 700, int $height = 300): string
    {
        $s = self::SCALE;
        $W = $width * $s; $H = $height * $s;
        $img = self::newCanvas($W, $H);

        $gray      = imagecolorallocate($img, 231, 235, 242);
        $darkGray  = imagecolorallocate($img, 106, 118, 144);
        $textColor = imagecolorallocate($img, 27, 37, 89);

        $padLeft = 75 * $s; $padRight = 20 * $s; $padTop = 55 * $s; $padBottom = 35 * $s;
        $plotW = $W - $padLeft - $padRight;
        $plotH = $H - $padTop - $padBottom;

        $maxVal = !empty($values) ? max($values) : 1;
        $maxVal = $maxVal > 0 ? $maxVal * 1.3 : 1;

        for ($i = 0; $i <= 4; $i++) {
            $y = $padTop + $plotH - ($plotH * $i / 4);
            imagesetthickness($img, 1 * $s);
            imageline($img, $padLeft, (int) $y, $W - $padRight, (int) $y, $gray);
            self::ttext($img, 9 * $s, 5 * $s, (int) $y + (2 * $s), number_format($maxVal * $i / 4, 0, ',', '.'), $darkGray);
        }

        $count = count($values);
        if ($count === 0) { return self::toBase64($img); }

        $slot = $plotW / $count;
        $barWidth = $slot * 0.5;

        foreach ($values as $i => $val) {
            $barHeight = $maxVal > 0 ? ($val / $maxVal) * $plotH : 0;
            $x1 = $padLeft + ($i * $slot) + ($slot - $barWidth) / 2;
            $y1 = $padTop + $plotH - $barHeight;
            $x2 = $x1 + $barWidth;
            $y2 = $padTop + $plotH;

            [$r, $g, $b] = self::hexToRgb($colors[$i % count($colors)]);
            $color = imagecolorallocate($img, $r, $g, $b);
            self::roundedRect($img, (int) $x1, (int) $y1, (int) $x2, (int) $y2, 6 * $s, $color);

            $cx = (int) (($x1 + $x2) / 2);
            self::centeredTtext($img, 10 * $s, $cx, (int) $y1 - (26 * $s), number_format($val, 0, ',', '.') . ' KWH', $textColor);
            $sub = trim(($subLine1[$i] ?? '') . '  •  ' . ($subLine2[$i] ?? ''), ' •');
            self::centeredTtext($img, 9 * $s, $cx, (int) $y1 - (12 * $s), $sub, $darkGray);
            self::centeredTtext($img, 10 * $s, $cx, $H - (18 * $s), (string) ($labels[$i] ?? ''), $darkGray);
        }

        return self::toBase64($img);
    }

    public static function donutChart(int $pValue, int $kValue, string $colorP = '#0b3d91', string $colorK = '#ffce3a', int $width = 440, int $height = 320): string
    {
        $s = self::SCALE;
        $W = $width * $s; $H = $height * $s;
        $img = self::newCanvas($W, $H);

        $textColor = imagecolorallocate($img, 27, 37, 89);
        $darkGray  = imagecolorallocate($img, 106, 118, 144);

        $cx = intdiv($W, 2);
        $cy = 130 * $s;
        $outerR = 95 * $s;
        $innerR = 52 * $s;

        $total = max($pValue + $kValue, 1);
        $pPct = $pValue / $total * 100;
        $kPct = 100 - $pPct;

        [$r, $g, $b] = self::hexToRgb($colorP);
        $cP = imagecolorallocate($img, $r, $g, $b);
        [$r, $g, $b] = self::hexToRgb($colorK);
        $cK = imagecolorallocate($img, $r, $g, $b);

        $pStart = -90;
        $pEnd   = -90 + ($pPct / 100 * 360);
        imagefilledarc($img, $cx, $cy, $outerR * 2, $outerR * 2, (int) $pStart, (int) $pEnd, $cP, IMG_ARC_PIE);
        imagefilledarc($img, $cx, $cy, $outerR * 2, $outerR * 2, (int) $pEnd, 270, $cK, IMG_ARC_PIE);

        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledellipse($img, $cx, $cy, $innerR * 2, $innerR * 2, $white);

        self::centeredTtext($img, 17 * $s, $cx, $cy - (2 * $s), number_format($total, 0, ',', '.'), $textColor);
        self::centeredTtext($img, 10 * $s, $cx, $cy + (18 * $s), 'pelanggan', $darkGray);

        $legendY = $cy + $outerR + (30 * $s);
        imagefilledrectangle($img, $cx - (110 * $s), $legendY, $cx - (100 * $s), $legendY + (10 * $s), $cP);
        self::ttext($img, 10 * $s, $cx - (93 * $s), $legendY + (9 * $s),
            'P ' . number_format($pValue, 0, ',', '.') . ' (' . number_format($pPct, 1, ',', '.') . '%)', $darkGray);

        imagefilledrectangle($img, $cx + (20 * $s), $legendY, $cx + (30 * $s), $legendY + (10 * $s), $cK);
        self::ttext($img, 10 * $s, $cx + (37 * $s), $legendY + (9 * $s),
            'K ' . number_format($kValue, 0, ',', '.') . ' (' . number_format($kPct, 1, ',', '.') . '%)', $darkGray);

        return self::toBase64($img);
    }

    /** $series = [ ['data' => [...], 'color' => '#hex', 'label' => 'Nama'], ... ] */
    public static function lineChart(array $labels, array $series, int $width = 700, int $height = 300, bool $currency = false): string
    {
        $s = self::SCALE;
        $W = $width * $s; $H = $height * $s;
        $img = self::newCanvas($W, $H);

        $gray     = imagecolorallocate($img, 231, 235, 242);
        $axisGray = imagecolorallocate($img, 191, 199, 213);
        $darkGray = imagecolorallocate($img, 106, 118, 144);

        $hasLegend = count($series) > 1;
        $padLeft = 60 * $s; $padRight = 15 * $s; $padTop = ($hasLegend ? 30 : 15) * $s; $padBottom = 30 * $s;

        $plotW = $W - $padLeft - $padRight;
        $plotH = $H - $padTop - $padBottom;

        $allValues = [];
        foreach ($series as $sr) { $allValues = array_merge($allValues, $sr['data']); }
        $maxVal = !empty($allValues) ? max($allValues) : 1;
        $maxVal = $maxVal > 0 ? $maxVal * 1.15 : 1;

        imagesetthickness($img, 1 * $s);
        for ($i = 0; $i <= 4; $i++) {
            $y = $padTop + $plotH - ($plotH * $i / 4);
            imageline($img, $padLeft, (int) $y, $W - $padRight, (int) $y, $gray);
            $val = $maxVal * $i / 4;
            $label = $currency ? 'Rp' . number_format($val, 0, ',', '.') : number_format($val, 0, ',', '.');
            self::ttext($img, 8 * $s, 3 * $s, (int) $y + (3 * $s), $label, $darkGray);
        }
        imageline($img, $padLeft, $padTop, $padLeft, $padTop + $plotH, $axisGray);
        imageline($img, $padLeft, $padTop + $plotH, $W - $padRight, $padTop + $plotH, $axisGray);

        if ($hasLegend) {
            $lx = $padLeft;
            foreach ($series as $sr) {
                [$r, $g, $b] = self::hexToRgb($sr['color']);
                $c = imagecolorallocate($img, $r, $g, $b);
                imagefilledrectangle($img, $lx, 6 * $s, $lx + (10 * $s), 16 * $s, $c);
                self::ttext($img, 9 * $s, $lx + (15 * $s), 15 * $s, $sr['label'], $darkGray);
                $lx += (15 + (mb_strlen($sr['label']) * 7) + 20) * $s;
            }
        }

        $count = count($labels);
        foreach ($series as $sr) {
            $data = $sr['data'];
            [$r, $g, $b] = self::hexToRgb($sr['color']);
            $color = imagecolorallocate($img, $r, $g, $b);
            $points = [];

            foreach ($data as $i => $val) {
                $x = $count > 1 ? $padLeft + ($i / ($count - 1)) * $plotW : $padLeft + $plotW / 2;
                $y = $padTop + $plotH - ($maxVal > 0 ? ($val / $maxVal) * $plotH : 0);
                $points[] = [$x, $y];
            }

            if (!$hasLegend && count($points) > 1) {
                [$fr, $fg, $fb] = self::hexToRgb($sr['color']);
                $fillColor = imagecolorallocatealpha($img, $fr, $fg, $fb, 100);
                $poly = [];
                foreach ($points as $p) { $poly[] = $p[0]; $poly[] = $p[1]; }
                $poly[] = end($points)[0]; $poly[] = $padTop + $plotH;
                $poly[] = $points[0][0];   $poly[] = $padTop + $plotH;
                imagefilledpolygon($img, $poly, count($poly) / 2, $fillColor);
            }

            imagesetthickness($img, 2 * $s);
            for ($i = 0; $i < count($points) - 1; $i++) {
                imageline($img, (int) $points[$i][0], (int) $points[$i][1], (int) $points[$i + 1][0], (int) $points[$i + 1][1], $color);
            }
            foreach ($points as $p) {
                imagefilledellipse($img, (int) $p[0], (int) $p[1], 5 * $s, 5 * $s, $color);
            }
        }

        $step = max(1, (int) ceil($count / 8));
        foreach ($labels as $i => $lab) {
            if ($count > 12 && $i % $step !== 0 && $i !== $count - 1) { continue; }
            $x = $count > 1 ? $padLeft + ($i / ($count - 1)) * $plotW : $padLeft + $plotW / 2;
            self::centeredTtext($img, 8 * $s, (int) $x, $H - (10 * $s), (string) $lab, $darkGray);
        }

        return self::toBase64($img);
    }

    private static function roundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, $color): void
    {
        $radius = min($radius, (int) (($y2 - $y1) / 2), (int) (($x2 - $x1) / 2));
        if ($radius <= 0) {
            imagefilledrectangle($img, $x1, $y1, $x2, $y2, $color);
            return;
        }
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x1 + $radius, $y2, $color);
        imagefilledrectangle($img, $x2 - $radius, $y1 + $radius, $x2, $y2, $color);
        imagefilledarc($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color, IMG_ARC_PIE);
        imagefilledarc($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color, IMG_ARC_PIE);
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function toBase64($img): string
    {
        ob_start();
        imagepng($img, null, 9);
        $data = ob_get_clean();
        imagedestroy($img);
        return 'data:image/png;base64,' . base64_encode($data);
    }
}
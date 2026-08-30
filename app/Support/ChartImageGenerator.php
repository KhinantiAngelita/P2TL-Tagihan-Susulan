<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ChartImageGenerator
{
    /**
     * Faktor supersampling — render lebih besar lalu di-downscale balik.
     * Ini TIDAK mengubah ukuran akhir gambar ($width/$height tetap sama
     * persis seperti yang di-request) — cuma bikin garis/kurva/teksnya
     * lebih halus saat di-downscale. Karena hasilnya di-cache, biaya
     * render ekstra ini cuma kena sekali per kombinasi data.
     */
    private const SCALE = 3;

    /** Level kompresi PNG. 9 = paling lambat/paling kecil; 6 jauh lebih cepat dengan selisih ukuran file yang minor untuk chart sederhana begini. */
    private const PNG_COMPRESSION = 6;

    /**
     * Berapa lama hasil render sebuah chart disimpan di cache. Chart di
     * kelas ini murni fungsi dari argumennya (data + warna + ukuran) —
     * kalau argumennya sama persis, hasilnya pasti sama persis — jadi
     * aman di-cache. Download PDF kedua/ketiga untuk laporan yang sama
     * jadi skip render GD sama sekali (cuma decode dari cache).
     */
    private const CACHE_TTL_SECONDS = 21600; // 6 jam

    /** Cache hasil pencarian font path supaya file_exists() cuma dijalankan sekali per request, bukan setiap kali teks digambar. */
    private static ?string $fontPathCache = null;

    private static function fontPath(): string
    {
        if (self::$fontPathCache !== null) {
            return self::$fontPathCache;
        }

        $candidates = [
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
            base_path('resources/fonts/DejaVuSans.ttf'),
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return self::$fontPathCache = $path;
            }
        }
        // fallback: kalau tidak ketemu, GD akan error saat imagettftext dipanggil,
        // jadi pastikan salah satu path di atas valid di server kamu.
        return self::$fontPathCache = $candidates[0];
    }

    /**
     * Bungkus pemanggilan render chart dengan cache berbasis hash argumen.
     * Kalau kombinasi argumen ini pernah dirender sebelumnya (dalam TTL),
     * langsung dikembalikan tanpa sentuh GD sama sekali.
     */
    private static function remember(string $prefix, array $args, \Closure $render): string
    {
        $key = 'chart_img:' . $prefix . ':' . md5(json_encode($args));

        return Cache::remember($key, self::CACHE_TTL_SECONDS, $render);
    }

    private static function newCanvas(int $w, int $h)
    {
        $img = imagecreatetruecolor($w, $h);
        // imageantialias() sengaja TIDAK dipakai: semua garis pakai
        // imagesetthickness() dan semua bentuk lain pakai fungsi filled*
        // (arc/rectangle/polygon) — tidak satupun terpengaruh imageantialias.
        // Kehalusan visual didapat dari supersampling (SCALE) + downscale.
        imagesavealpha($img, true);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);
        return $img;
    }

    /**
     * Resample gambar hasil render (yang ukurannya SCALE× lebih besar) turun
     * ke ukuran tampil aslinya ($width × $height yang diminta pemanggil —
     * dimensi akhir TIDAK pernah berubah dari yang di-request).
     */
    private static function downscale($img, int $targetW, int $targetH)
    {
        if (imagesx($img) === $targetW && imagesy($img) === $targetH) {
            return $img;
        }

        $out = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $targetW, $targetH, imagesx($img), imagesy($img));
        imagedestroy($img);

        return $out;
    }

    private static function ttext($img, float $size, int $x, int $y, string $text, $color, bool $bold = false): void
    {
        imagettftext($img, $size, 0, $x, $y, $color, self::fontPath(), $text);
    }

    private static function textWidth(float $size, string $text): float
    {
        $box = imagettfbbox($size, 0, self::fontPath(), $text);
        return abs($box[2] - $box[0]);
    }

    private static function centeredTtext($img, float $size, int $cx, int $y, string $text, $color): void
    {
        $textWidth = self::textWidth($size, $text);
        self::ttext($img, $size, (int) ($cx - $textWidth / 2), $y, $text, $color);
    }

    /**
     * Bulatkan angka mentah ke "step" grid yang enak dibaca (1/2/5/10 × 10^n),
     * mis. rawMax=73 dengan 4 divisi -> step=20, axisMax=80 — sama seperti
     * gridline 0/20/40/60/80 pada chart referensi, bukan angka acak hasil
     * "maxVal * 1.15" seperti sebelumnya.
     */
    private static function niceStep(float $rough): float
    {
        if ($rough <= 0) {
            return 1;
        }
        $exponent  = floor(log10($rough));
        $magnitude = 10 ** $exponent;
        $residual  = $rough / $magnitude;

        if ($residual <= 1) {
            $nice = 1;
        } elseif ($residual <= 2) {
            $nice = 2;
        } elseif ($residual <= 5) {
            $nice = 5;
        } else {
            $nice = 10;
        }

        return $nice * $magnitude;
    }

    /**
     * Interpolasi Catmull-Rom: dari titik data asli (patah-patah), hasilkan
     * titik-titik tambahan di antaranya supaya saat disambung jadi garis
     * lurus pendek-pendek, terlihat sebagai kurva halus — persis gaya
     * chart pada referensi. Titik data asli tetap dipakai untuk marker
     * (lingkaran), cuma garis penghubungnya yang dihaluskan.
     */
    private static function smoothPoints(array $points, int $segmentsPerGap = 10): array
    {
        $n = count($points);
        if ($n < 3) {
            return $points;
        }

        $result = [];
        for ($i = 0; $i < $n - 1; $i++) {
            $p0 = $points[max($i - 1, 0)];
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $p3 = $points[min($i + 2, $n - 1)];

            for ($t = 0; $t <= $segmentsPerGap; $t++) {
                if ($i > 0 && $t === 0) {
                    continue; // hindari titik dobel di sambungan antar segmen
                }
                $tt = $t / $segmentsPerGap;
                $t2 = $tt * $tt;
                $t3 = $t2 * $tt;

                $x = 0.5 * ((2 * $p1[0]) + (-$p0[0] + $p2[0]) * $tt
                    + (2 * $p0[0] - 5 * $p1[0] + 4 * $p2[0] - $p3[0]) * $t2
                    + (-$p0[0] + 3 * $p1[0] - 3 * $p2[0] + $p3[0]) * $t3);
                $y = 0.5 * ((2 * $p1[1]) + (-$p0[1] + $p2[1]) * $tt
                    + (2 * $p0[1] - 5 * $p1[1] + 4 * $p2[1] - $p3[1]) * $t2
                    + (-$p0[1] + 3 * $p1[1] - 3 * $p2[1] + $p3[1]) * $t3);

                $result[] = [$x, $y];
            }
        }

        return $result;
    }

    public static function barChart(array $labels, array $values, array $subLine1, array $subLine2, array $colors, int $width = 700, int $height = 300): string
    {
        return self::remember('bar', func_get_args(), function () use ($labels, $values, $subLine1, $subLine2, $colors, $width, $height) {
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

            imagesetthickness($img, 1 * $s);
            for ($i = 0; $i <= 4; $i++) {
                $y = $padTop + $plotH - ($plotH * $i / 4);
                imageline($img, $padLeft, (int) $y, $W - $padRight, (int) $y, $gray);
                self::ttext($img, 9 * $s, 5 * $s, (int) $y + (2 * $s), number_format($maxVal * $i / 4, 0, ',', '.'), $darkGray);
            }

            $count = count($values);
            if ($count === 0) {
                $img = self::downscale($img, $width, $height);
                return self::toBase64($img);
            }

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

            $img = self::downscale($img, $width, $height);
            return self::toBase64($img);
        });
    }

    public static function donutChart(int $pValue, int $kValue, string $colorP = '#0b3d91', string $colorK = '#ffce3a', int $width = 440, int $height = 320): string
    {
        return self::remember('donut', func_get_args(), function () use ($pValue, $kValue, $colorP, $colorK, $width, $height) {
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

            $img = self::downscale($img, $width, $height);
            return self::toBase64($img);
        });
    }

    /** $series = [ ['data' => [...], 'color' => '#hex', 'label' => 'Nama'], ... ] */
    public static function lineChart(array $labels, array $series, int $width = 700, int $height = 300, bool $currency = false): string
    {
        return self::remember('line', func_get_args(), function () use ($labels, $series, $width, $height, $currency) {
            $s = self::SCALE;
            $W = $width * $s; $H = $height * $s;
            $img = self::newCanvas($W, $H);

            $gray     = imagecolorallocate($img, 231, 235, 242);
            $axisGray = imagecolorallocate($img, 191, 199, 213);
            $darkGray = imagecolorallocate($img, 106, 118, 144);

            $hasLegend = count($series) > 1;
            $padLeft = 55 * $s; $padRight = 15 * $s; $padTop = ($hasLegend ? 34 : 15) * $s; $padBottom = 26 * $s;

            $plotW = $W - $padLeft - $padRight;
            $plotH = $H - $padTop - $padBottom;

            // ===== Skala sumbu-Y dibulatkan ke angka rapi (0/20/40/60/80, dst) =====
            $allValues = [];
            foreach ($series as $sr) { $allValues = array_merge($allValues, $sr['data']); }
            $rawMax = !empty($allValues) ? max($allValues) : 1;
            $step   = self::niceStep($rawMax / 4);
            $axisMax = $step * 4;
            if ($axisMax <= 0) { $axisMax = 1; $step = 0.25; }

            imagesetthickness($img, 1 * $s);
            for ($i = 0; $i <= 4; $i++) {
                $val = $step * $i;
                $y = $padTop + $plotH - ($axisMax > 0 ? ($val / $axisMax) * $plotH : 0);
                imageline($img, $padLeft, (int) $y, $W - $padRight, (int) $y, $gray);
                $label = $currency ? 'Rp' . number_format($val, 0, ',', '.') : number_format($val, 0, ',', '.');
                self::ttext($img, 8 * $s, 3 * $s, (int) $y + (3 * $s), $label, $darkGray);
            }
            imageline($img, $padLeft, $padTop, $padLeft, $padTop + $plotH, $axisGray);
            imageline($img, $padLeft, $padTop + $plotH, $W - $padRight, $padTop + $plotH, $axisGray);

            // ===== Legend: kotak warna membulat, dipusatkan di atas plot area =====
            if ($hasLegend) {
                $boxSize    = 11 * $s;
                $boxTextGap = 6 * $s;
                $itemGap    = 22 * $s;

                $totalWidth = 0;
                foreach ($series as $idx => $sr) {
                    $totalWidth += $boxSize + $boxTextGap + self::textWidth(9 * $s, $sr['label']);
                    if ($idx < count($series) - 1) { $totalWidth += $itemGap; }
                }

                $lx = ($W - $totalWidth) / 2;
                $legendY = 6 * $s;

                foreach ($series as $sr) {
                    [$r, $g, $b] = self::hexToRgb($sr['color']);
                    $c = imagecolorallocate($img, $r, $g, $b);
                    self::roundedRect($img, (int) $lx, (int) $legendY, (int) ($lx + $boxSize), (int) ($legendY + $boxSize), 3 * $s, $c);
                    $lx += $boxSize + $boxTextGap;
                    self::ttext($img, 9 * $s, (int) $lx, (int) ($legendY + $boxSize - (1 * $s)), $sr['label'], $darkGray);
                    $lx += self::textWidth(9 * $s, $sr['label']) + $itemGap;
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
                    $y = $padTop + $plotH - ($axisMax > 0 ? ($val / $axisMax) * $plotH : 0);
                    $points[] = [$x, $y];
                }

                if (!$hasLegend && count($points) > 1) {
                    $smoothForFill = self::smoothPoints($points);
                    [$fr, $fg, $fb] = self::hexToRgb($sr['color']);
                    $fillColor = imagecolorallocatealpha($img, $fr, $fg, $fb, 100);
                    $poly = [];
                    foreach ($smoothForFill as $p) { $poly[] = $p[0]; $poly[] = $p[1]; }
                    $poly[] = end($smoothForFill)[0]; $poly[] = $padTop + $plotH;
                    $poly[] = $smoothForFill[0][0];   $poly[] = $padTop + $plotH;
                    imagefilledpolygon($img, $poly, count($poly) / 2, $fillColor);
                }

                // Garis dihaluskan (Catmull-Rom) — bukan garis lurus antar titik data,
                // supaya kurvanya melengkung mulus persis seperti chart referensi.
                $smooth = self::smoothPoints($points);
                imagesetthickness($img, 2 * $s);
                for ($i = 0; $i < count($smooth) - 1; $i++) {
                    imageline($img, (int) $smooth[$i][0], (int) $smooth[$i][1], (int) $smooth[$i + 1][0], (int) $smooth[$i + 1][1], $color);
                }

                // Marker tetap di titik data ASLI (bukan titik hasil interpolasi).
                foreach ($points as $p) {
                    imagefilledellipse($img, (int) $p[0], (int) $p[1], 5 * $s, 5 * $s, $color);
                }
            }

            // ===== Semua label sumbu-X ditampilkan (tidak di-skip sebagian) —
            // ukuran font mengecil otomatis kalau labelnya banyak, supaya
            // tetap muat tanpa tabrakan seperti pada chart referensi. =====
            $fontSize = $count > 20 ? (6 * $s) : ($count > 12 ? (7 * $s) : (8 * $s));
            foreach ($labels as $i => $lab) {
                $x = $count > 1 ? $padLeft + ($i / ($count - 1)) * $plotW : $padLeft + $plotW / 2;
                self::centeredTtext($img, $fontSize, (int) $x, $H - (8 * $s), (string) $lab, $darkGray);
            }

            $img = self::downscale($img, $width, $height);
            return self::toBase64($img);
        });
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
        imagepng($img, null, self::PNG_COMPRESSION);
        $data = ob_get_clean();
        imagedestroy($img);
        return 'data:image/png;base64,' . base64_encode($data);
    }
}
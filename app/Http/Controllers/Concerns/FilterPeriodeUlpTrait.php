<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DetailTagihanSusulan;
use Illuminate\Http\Request;

trait FilterPeriodeUlpTrait
{
    /**
     * Nama bulan (uppercase, sesuai format kolom laporan_susulans.bulan) -> angka 1-12.
     * Dipakai lolosFilterBaris() buat konversi bulan laporan jadi angka.
     */
    protected const BULAN_ANGKA = [
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4,
        'MEI' => 5, 'JUNI' => 6, 'JULI' => 7, 'AGUSTUS' => 8,
        'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
    ];

    /**
     * Ambil & normalisasi semua parameter filter periode + ULP dari request.
     * Triwulan, Bulan, dan Rentang Tanggal bisa dikombinasikan bebas — hasil
     * akhirnya adalah IRISAN (AND) dari semua filter periode yang sedang
     * aktif. ULP adalah filter terpisah (multi-select, OR di dalam dirinya
     * sendiri, AND terhadap filter periode).
     */
    protected function ambilFilter(Request $request, int $tahunAktif): array
    {
        $bersihkan = fn ($v) => $v !== null && $v !== '';

        $twTerpilih    = array_map('intval', array_values(array_filter((array) $request->input('tw', []), $bersihkan)));
        $bulanTerpilih = array_map('intval', array_values(array_filter((array) $request->input('bulan', []), $bersihkan)));
        $ulpTerpilih   = array_values(array_filter((array) $request->input('ulp', []), $bersihkan));
        $tglMulai      = $request->input('tgl_mulai') ?: null;
        $tglSelesai    = $request->input('tgl_selesai') ?: null;

        return [
            'twTerpilih'    => $twTerpilih,
            'bulanTerpilih' => $bulanTerpilih,
            'ulpTerpilih'   => $ulpTerpilih,
            'tglMulai'      => $tglMulai,
            'tglSelesai'    => $tglSelesai,
            'bulanEfektif'  => $this->hitungBulanEfektif($twTerpilih, $bulanTerpilih, $tglMulai, $tglSelesai, $tahunAktif),
        ];
    }

    /**
     * Irisan (AND) dari filter Triwulan, Bulan, dan Rentang Tanggal.
     * null artinya tidak ada filter periode sama sekali (semua bulan ikut).
     */
    private function hitungBulanEfektif(array $twTerpilih, array $bulanTerpilih, ?string $tglMulai, ?string $tglSelesai, int $tahunAktif): ?array
    {
        $himpunanAktif = [];

        if (! empty($twTerpilih)) {
            $bulanDariTw = [];
            foreach ($twTerpilih as $tw) {
                $bulanDariTw = array_merge($bulanDariTw, static::TRIWULAN_BULAN[$tw] ?? []);
            }
            $himpunanAktif[] = array_unique($bulanDariTw);
        }

        if (! empty($bulanTerpilih)) {
            $himpunanAktif[] = $bulanTerpilih;
        }

        if ($tglMulai || $tglSelesai) {
            $himpunanAktif[] = $this->bulanDalamRentangTanggal($tglMulai, $tglSelesai, $tahunAktif);
        }

        if (empty($himpunanAktif)) {
            return null; // tidak ada filter periode → semua bulan ikut
        }

        $hasil = array_shift($himpunanAktif);
        foreach ($himpunanAktif as $set) {
            $hasil = array_intersect($hasil, $set);
        }

        return array_values(array_unique($hasil));
    }

    /**
     * Konversi rentang tanggal (boleh cuma salah satu ujung yang diisi)
     * jadi daftar nomor bulan (1-12) yang beririsan dengan $tahunAktif.
     */
    private function bulanDalamRentangTanggal(?string $tglMulai, ?string $tglSelesai, int $tahunAktif): array
    {
        $tahunMulai   = $tglMulai ? (int) substr($tglMulai, 0, 4) : $tahunAktif;
        $tahunSelesai = $tglSelesai ? (int) substr($tglSelesai, 0, 4) : $tahunAktif;

        if ($tahunMulai > $tahunAktif || $tahunSelesai < $tahunAktif) {
            return [];
        }

        $bulanAwal  = $tahunMulai < $tahunAktif ? 1 : (int) substr($tglMulai, 5, 2);
        $bulanAkhir = $tahunSelesai > $tahunAktif ? 12 : (int) substr($tglSelesai, 5, 2);

        return range($bulanAwal, $bulanAkhir);
    }

    /**
     * Cek apakah satu baris (diidentifikasi lewat no_agenda) lolos filter
     * bulan efektif + rentang tanggal presisi hari + ULP.
     * Return kode ULP hasil parsing kalau lolos, atau null kalau tidak
     * (baik karena no_agenda tidak valid maupun karena kesaring filter).
     *
     * PERBAIKAN: parameter $bulanLaporan (nilai kolom laporan_susulans.bulan
     * milik baris ini, mis. "APRIL") ditambahkan. Sebelumnya filter
     * Triwulan/Bulan di method ini nentuin "bulan" dari tanggal yang
     * ke-parsing di no_agenda (segmen ke-3) — beda sumber dari
     * TrendController dkk yang konsisten pakai laporan_susulans.bulan
     * (bulan yang di-declare pas laporan di-upload). Cross-check ke Excel
     * nemuin 62 baris yang bulan laporannya beda dari bulan tanggal
     * agenda-nya (mis. laporan "APRIL" tapi tanggal agenda tercatat akhir
     * Maret) — baris begini kesaring beda antara halaman Target vs
     * Realisasi/Gol Tarif/Komposisi Temuan (pakai method ini) dengan
     * halaman Trend/Pencapaian (pakai laporan_susulans.bulan), bikin total
     * per ULP beda meski ULP-nya kebaca sama persis di dua sisi.
     * Filter rentang tanggal presisi hari (tgl_mulai/tgl_selesai) TETAP
     * pakai tanggal hasil parsing no_agenda — itu memang soal tanggal
     * kejadian spesifik, bukan bucket bulan pelaporan.
     */
    protected function lolosFilterBaris(?string $noAgenda, ?string $bulanLaporan, array $filter): ?string
    {
        $parts      = explode('/', (string) $noAgenda);
        $kodeUlp    = $parts[1] ?? null;
        $tanggalStr = $parts[2] ?? null;

        if (! $kodeUlp || ! $tanggalStr || ! preg_match('/^\d{8}$/', $tanggalStr)) {
            return null;
        }

        if (! empty($filter['ulpTerpilih']) && ! in_array($kodeUlp, $filter['ulpTerpilih'], true)) {
            return null;
        }

        if ($filter['bulanEfektif'] !== null) {
            $bulanAngka = self::BULAN_ANGKA[strtoupper((string) $bulanLaporan)] ?? null;
            if ($bulanAngka === null || ! in_array($bulanAngka, $filter['bulanEfektif'], true)) {
                return null;
            }
        }

        $tanggalPenuh = substr($tanggalStr, 0, 4) . '-' . substr($tanggalStr, 4, 2) . '-' . substr($tanggalStr, 6, 2);
        if ($filter['tglMulai'] && $tanggalPenuh < $filter['tglMulai']) {
            return null;
        }
        if ($filter['tglSelesai'] && $tanggalPenuh > $filter['tglSelesai']) {
            return null;
        }

        return $kodeUlp;
    }

    /**
     * Daftar ULP yang muncul di data golongan tertentu untuk tahun berjalan
     * (independen dari filter yang sedang aktif), dipakai buat isi opsi
     * checkbox filter ULP.
     */
    protected function daftarUlpTahunIni($laporanAktifIds, array $daftarGol): array
    {
        $barisMentah = DetailTagihanSusulan::query()
            ->whereIn('laporan_susulan_id', $laporanAktifIds)
            ->whereIn('gol', $daftarGol)
            ->select('no_agenda')
            ->get();

        $kodeUnik = [];
        foreach ($barisMentah as $baris) {
            $parts = explode('/', (string) $baris->no_agenda);
            $kode  = $parts[1] ?? null;
            if ($kode) {
                $kodeUnik[$kode] = true;
            }
        }

        $daftar = [];
        foreach (array_keys($kodeUnik) as $kode) {
            $daftar[] = ['kode' => $kode, 'nama' => DetailTagihanSusulan::namaUlp($kode) ?? $kode];
        }

        usort($daftar, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        return $daftar;
    }

    /**
     * Teks ringkas filter yang sedang aktif, dipakai di subheading halaman
     * dan di header hasil capture gambar.
     * Contoh: "Tahun 2026 • Triwulan I, II • ULP: ULP Bandung, ULP Cimahi
     * • Periode 01 Jan 2026 s/d 30 Jun 2026"
     */
    protected function teksFilterAktif(int $tahunAktif, array $filter, array $daftarNamaUlp = []): string
    {
        $bagian = ["Tahun {$tahunAktif}"];

        $labelTw = ['I', 'II', 'III', 'IV'];
        if (! empty($filter['twTerpilih'])) {
            $list = $filter['twTerpilih'];
            sort($list);
            $bagian[] = 'Triwulan ' . implode(', ', array_map(fn ($t) => $labelTw[$t - 1] ?? $t, $list));
        }

        $namaBulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
        if (! empty($filter['bulanTerpilih'])) {
            $list = $filter['bulanTerpilih'];
            sort($list);
            $bagian[] = 'Bulan ' . implode(', ', array_map(fn ($b) => $namaBulan[$b] ?? $b, $list));
        }

        if ($filter['tglMulai'] || $filter['tglSelesai']) {
            $mulai    = $filter['tglMulai'] ? date('d M Y', strtotime($filter['tglMulai'])) : '…';
            $selesai  = $filter['tglSelesai'] ? date('d M Y', strtotime($filter['tglSelesai'])) : '…';
            $bagian[] = "Periode {$mulai} s/d {$selesai}";
        }

        if (! empty($filter['ulpTerpilih'])) {
            $nama     = array_map(fn ($kode) => $daftarNamaUlp[$kode] ?? $kode, $filter['ulpTerpilih']);
            $bagian[] = 'ULP: ' . implode(', ', $nama);
        }

        return implode(' • ', $bagian);
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FilterPeriodeUlpTrait;
use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PenetapanBerulangController extends Controller
{
    use FilterPeriodeUlpTrait;

    /**
     * Golongan yang secara DEFAULT gak tercentang / gak ikut dihitung
     * kalau user belum pernah menyentuh filter Golongan sama sekali
     * (halaman baru dibuka). "UPDA" & "KWH" itu bukan kode golongan asli
     * — ketemu waktu cross-check ke Excel, itu baris koreksi manual yang
     * datanya kotor. "P4" dikecualikan default sesuai permintaan
     * eksplisit, meski itu kode golongan valid.
     *
     * User tetap bisa centang manual atau klik "Pilih/Batalkan Semua" di
     * panel filternya buat mengikutkan golongan ini — begitu user submit
     * filter secara eksplisit (request punya key 'golongan', walau
     * isinya kosong), default ini TIDAK dipakai lagi, murni ngikut
     * pilihan user.
     */
    private const GOLONGAN_DEFAULT_TIDAK_DICENTANG = ['UPDA', 'P4', 'KWH'];

    /**
     * Tentukan kode ULP + terapkan filter periode/ULP untuk SATU baris.
     *
     * Jalur normal (no_agenda format standar, P2TL/{ULP}/{tanggal}/{urut})
     * -> didelegasikan APA ADANYA ke lolosFilterBaris() dari
     * FilterPeriodeUlpTrait, biar konsisten persis dengan halaman lain
     * (Target vs Realisasi, Gol Tarif, Komposisi Temuan).
     *
     * Jalur fallback (no_agenda RUSAK, gak ada "/" — mis. baris koreksi
     * manual GOL "UPDA"/"KWH", ditemukan dari cross-check ke Excel asli)
     * TIDAK ditangani oleh lolosFilterBaris() trait (dia langsung
     * return null kalau format gak standar) — makanya method ini
     * nangkep sendiri kasus itu: cocokin awalan string ke kode ULP yang
     * dikenal sistem, lalu terapkan filter bulan (dari
     * laporan_susulans.bulan, SATU-SATUNYA info periode yang tersedia
     * buat baris ini) & filter ULP secara manual. Filter Rentang
     * Tanggal presisi harian SENGAJA di-skip untuk baris ini — gak ada
     * tanggal buat dicek, jadi kalau user lagi filter Rentang Tanggal,
     * baris rusak ini otomatis gak ikut (dianggap tidak match, bukan
     * dianggap lolos).
     *
     * Tanpa fallback ini, baris begini SELALU di-skip total, apapun
     * filter Golongan yang dipilih — regresi ke bug yang sudah pernah
     * diperbaiki khusus buat halaman ini.
     */
    private function kodeUlpTerfilter(?string $noAgenda, ?string $bulanLaporan, array $filter): ?string
    {
        $noAgenda = (string) $noAgenda;
        $parts = explode('/', $noAgenda);

        $formatStandar = isset($parts[1]) && $parts[1] !== '' && isset($parts[2]) && preg_match('/^\d{8}$/', $parts[2]);

        if ($formatStandar) {
            return $this->lolosFilterBaris($noAgenda, $bulanLaporan, $filter);
        }

        // ---- Jalur fallback: cocokin awalan string ke kode ULP dikenal. ----
        $kodeUlp = null;
        foreach (array_keys(DetailTagihanSusulan::PETA_NAMA_ULP) as $kode) {
            if (str_starts_with($noAgenda, (string) $kode)) {
                $kodeUlp = (string) $kode;
                break;
            }
        }
        if (! $kodeUlp) {
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

        // Rentang tanggal presisi harian gak bisa dicek buat baris ini —
        // kalau filter itu sedang aktif, anggap baris ini tidak match.
        if ($filter['tglMulai'] || $filter['tglSelesai']) {
            return null;
        }

        return $kodeUlp;
    }

    /**
     * Halaman "Penetapan Berulang" — pelanggan (IDPEL) yang muncul lebih
     * dari sekali di data temuan P2TL (laporan_susulans berstatus aktif).
     *
     * Filter Tahun/Triwulan/Bulan/Rentang Tanggal/ULP pakai panel yang
     * SAMA dengan Menu Trend (partial filter-periode-ulp.blade.php),
     * dengan logic yang SAMA PERSIS dengan halaman lain lewat
     * FilterPeriodeUlpTrait. Golongan Temuan & Non-Pelanggan tetap ada
     * tapi jadi tab TAMBAHAN yang cuma muncul di halaman ini.
     *
     * Route: GET /laporan/penetapan-berulang -> name('laporan.penetapan-berulang')
     */
    public function index(Request $request)
    {
        return view('laporan.penetapan-berulang', $this->siapkanData($request));
    }

    /**
     * Export Excel (2 sheet: Daftar Pelanggan + Pivot per ULP) — pakai
     * siapkanData() yang SAMA dengan index(), jadi file yang di-download
     * SELALU sinkron dengan apa yang lagi tampil di layar (filter apapun
     * yang lagi aktif di URL).
     *
     * Route: GET /laporan/penetapan-berulang/export -> name('laporan.penetapan-berulang.export')
     */
    public function exportExcel(Request $request)
    {
        $data = $this->siapkanData($request);

        $spreadsheet = new Spreadsheet();

        // ===== Sheet 1: Daftar Pelanggan =====
        $sheetPelanggan = $spreadsheet->getActiveSheet();
        $sheetPelanggan->setTitle('Daftar Pelanggan');

        $headerPelanggan = ['IDPEL', 'Nama', 'ULP', 'Golongan Terakhir', 'Daya', 'Nomor Agenda', 'Tanggal Penetapan', 'Jumlah Muncul', 'Total KWH', 'Total TS'];
        $sheetPelanggan->fromArray($headerPelanggan, null, 'A1');
        $sheetPelanggan->getStyle('A1:J1')->getFont()->setBold(true);

        $baris = 2;
        foreach ($data['daftarPelanggan'] as $p) {
            $temuanTerakhir = end($p['temuan']);

            // IDPEL ditulis eksplisit sebagai STRING (bukan lewat
            // fromArray biasa) — IDPEL berupa deretan digit panjang, dan
            // Excel/PhpSpreadsheet otomatis nebak itu angka lalu
            // menampilkannya dalam notasi ilmiah (mis. "5.38511E+17").
            // setCellValueExplicit(..., TYPE_STRING) mastiin nilainya
            // ditulis apa adanya, sama persis kayak di data aslinya.
            $sheetPelanggan->setCellValueExplicit("A{$baris}", (string) $p['idpel'], DataType::TYPE_STRING);

            $sheetPelanggan->fromArray([
                null, // kolom A (IDPEL) sudah diisi manual di atas sebagai string
                $p['nama'],
                $data['daftarUlpAssoc'][$p['ulp']] ?? $p['ulp'],
                $temuanTerakhir['gol'] ?? '-',
                $temuanTerakhir['daya'] ?? '-',
                $temuanTerakhir['no_agenda'] ?? '-',
                $temuanTerakhir['tanggal_register'] ? \Carbon\Carbon::parse($temuanTerakhir['tanggal_register'])->format('d-m-Y') : '-',
                $p['jumlah'],
                $p['total_kwh'],
                $p['total_ts'],
            ], null, "A{$baris}");
            $baris++;
        }
        foreach (range('A', 'J') as $kolom) {
            $sheetPelanggan->getColumnDimension($kolom)->setAutoSize(true);
        }

        // ===== Sheet 2: Pivot per ULP =====
        $sheetPivot = $spreadsheet->createSheet();
        $sheetPivot->setTitle('Pivot per ULP');

        $headerPivot = array_merge(['ULP'], array_map(fn ($j) => $j . 'x', $data['daftarJumlah']), ['Grand Total']);
        $kolomTerakhir = Coordinate::stringFromColumnIndex(count($headerPivot));
        $sheetPivot->fromArray($headerPivot, null, 'A1');
        $sheetPivot->getStyle("A1:{$kolomTerakhir}1")->getFont()->setBold(true);

        $baris = 2;
        foreach ($data['pivotRows'] as $row) {
            $isiBaris = [$row['nama']];
            foreach ($data['daftarJumlah'] as $j) {
                $isiBaris[] = $row['kolom'][$j] ?? 0;
            }
            $isiBaris[] = $row['total'];
            $sheetPivot->fromArray($isiBaris, null, "A{$baris}");
            $baris++;
        }

        $isiGrandTotal = ['Grand Total'];
        foreach ($data['daftarJumlah'] as $j) {
            $isiGrandTotal[] = $data['grandTotalKolom'][$j] ?? 0;
        }
        $isiGrandTotal[] = $data['grandTotalKeseluruhan'];
        $sheetPivot->fromArray($isiGrandTotal, null, "A{$baris}");
        $sheetPivot->getStyle("A{$baris}:{$kolomTerakhir}{$baris}")->getFont()->setBold(true);

        foreach (range('A', $kolomTerakhir) as $kolom) {
            $sheetPivot->getColumnDimension($kolom)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $namaFile = 'penetapan-berulang-' . ($data['tahunAktif'] ?: 'semua-tahun') . '-' . now()->format('Ymd-His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Kumpulkan seluruh data & filter buat halaman Penetapan Berulang.
     * Dipisah dari index() supaya DIPAKAI ULANG PERSIS SAMA oleh
     * exportExcel() — satu sumber kebenaran, gak ada cabang logic dua
     * versi yang bisa saling drift.
     */
    private function siapkanData(Request $request): array
    {
        $request->validate([
            'tahun'       => 'nullable|integer',
            'ulp'         => 'nullable|array',
            'ulp.*'       => 'nullable|string|max:20',
            'tw'          => 'nullable|array',
            'tw.*'        => 'integer|min:1|max:4',
            'bulan'       => 'nullable|array',
            'bulan.*'     => 'integer|min:1|max:12',
            'tgl_mulai'   => 'nullable|date',
            'tgl_selesai' => 'nullable|date',
            'golongan'    => 'nullable|array',
            'golongan.*'  => 'string|max:10',
            'nonpelang'   => 'nullable|in:sembunyikan,sertakan,hanya',
            'jumlah'      => 'nullable|integer|min:2',
            'search'      => 'nullable|string|max:100',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')->distinct()
            ->orderByDesc('tahun')->pluck('tahun');

        $tahunDipilih = $request->input('tahun') ?: null; // null = semua tahun
        // ambilFilter() dari trait butuh int buat hitung rentang bulan
        // efektif kalau ada filter Rentang Tanggal parsial. Kalau "Semua
        // Tahun" dipilih, ini CUMA dipakai buat perhitungan itu (bukan
        // buat WHERE tahun di query), jadi dikasih tahun berjalan
        // sebagai default yang aman.
        $tahunUntukHitung = $tahunDipilih ? (int) $tahunDipilih : (int) now()->year;

        $jumlahFilter     = $request->input('jumlah') ?: null;
        $searchFilter     = $request->input('search') ?: null;
        $modeNonPelanggan = $request->input('nonpelang', 'sembunyikan');

        $daftarUlpAssoc = DetailTagihanSusulan::PETA_NAMA_ULP;

        // ---- Filter Periode & ULP (Tahun/Triwulan/Bulan/Rentang
        // Tanggal/ULP) — logic-nya SAMA PERSIS dengan halaman lain lewat
        // FilterPeriodeUlpTrait (ambilFilter/lolosFilterBaris). ----
        $filter = $this->ambilFilter($request, $tahunUntukHitung);

        // ---- Laporan aktif yang relevan (buat golongan/ULP dropdown &
        // query utama), di-scope ke tahun kalau ada yang dipilih. ----
        $laporanAktifIds = LaporanSusulan::aktif()
            ->when($tahunDipilih, fn ($q) => $q->where('tahun', $tahunDipilih))
            ->pluck('id');

        // ---- Daftar golongan buat opsi checkbox — dari SELURUH temuan
        // aktif (opsional difilter tahun saja), TIDAK ikut difilter
        // golongan itu sendiri, biar daftarnya gak menyusut pas user
        // lagi milih sebagian golongan. ----
        $daftarGolongan = DetailTagihanSusulan::whereIn('laporan_susulan_id', $laporanAktifIds)
            ->select('gol')->distinct()->orderBy('gol')->pluck('gol')->filter()->values();

        // ---- Filter Golongan: kalau user BELUM PERNAH menyentuh filter
        // ini sama sekali (URL gak punya key 'golongan' sama sekali),
        // pakai default: SEMUA golongan KECUALI yang ada di
        // GOLONGAN_DEFAULT_TIDAK_DICENTANG. Begitu user pernah submit
        // filter Golongan secara eksplisit, default ini gak dipakai
        // lagi — murni ngikut apa yang dikirim. ----
        if ($request->has('golongan')) {
            $golonganFilter = $request->input('golongan') ?: null;
        } else {
            $golonganFilter = array_values(array_diff($daftarGolongan->all(), self::GOLONGAN_DEFAULT_TIDAK_DICENTANG));
        }

        // ---- Daftar ULP buat checklist filter — pakai method trait,
        // independen dari filter ULP yang sedang aktif. ----
        $daftarUlp = collect($this->daftarUlpTahunIni($laporanAktifIds, $daftarGolongan->all()));

        // ---- Teks ringkasan filter — pakai teksFilterAktif() dari
        // trait (dipakai bareng halaman lain), separator disamakan ke
        // " · " biar cocok sama cara partial filter-periode-ulp
        // meng-explode jadi chip terpisah (trait aslinya pakai " • ").
        // Golongan & Non-Pelanggan (khusus halaman ini) ditambahkan di
        // belakangnya. ----
        $filterInfoText = str_replace(' • ', ' · ', $this->teksFilterAktif($tahunUntukHitung, $filter, $daftarUlpAssoc));
        if (! $tahunDipilih) {
            $filterInfoText = str_replace("Tahun {$tahunUntukHitung}", 'Semua Tahun', $filterInfoText);
        }
        if ($golonganFilter && count($golonganFilter) < $daftarGolongan->count()) {
            $filterInfoText .= ' · Golongan ' . implode(', ', $golonganFilter);
        }
        if ($modeNonPelanggan !== 'sembunyikan') {
            $filterInfoText .= ' · ' . ($modeNonPelanggan === 'hanya' ? 'Hanya Non-Pelanggan' : 'Termasuk Non-Pelanggan');
        }

        // ---- Ambil SEMUA baris relevan (tahun + status aktif + golongan
        // saja di level query) — filter periode/ULP diterapkan PER BARIS
        // lewat kodeUlpTerfilter(), sama seperti halaman lain (lihat
        // dokumentasi lengkap di method itu, termasuk soal fallback
        // baris no_agenda rusak). ----
        $rows = DetailTagihanSusulan::query()
            ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
            ->where('laporan_susulans.status', 'aktif')
            ->when($tahunDipilih, fn ($q) => $q->where('laporan_susulans.tahun', $tahunDipilih))
            ->when($golonganFilter, fn ($q) => $q->whereIn('detail_tagihan_susulans.gol', $golonganFilter))
            ->select(
                'detail_tagihan_susulans.no_agenda',
                'detail_tagihan_susulans.idpel',
                'detail_tagihan_susulans.nama',
                'detail_tagihan_susulans.gol',
                'detail_tagihan_susulans.daya',
                'detail_tagihan_susulans.kwh',
                'detail_tagihan_susulans.ts',
                'detail_tagihan_susulans.tanggal_register',
                'laporan_susulans.bulan',
                'laporan_susulans.tahun',
            )
            ->get();

        $perPelanggan = [];
        $adaNonPelanggan = false;
        foreach ($rows as $r) {
            $kodeUlp = $this->kodeUlpTerfilter($r->no_agenda, $r->bulan, $filter);
            if (! $kodeUlp) {
                continue;
            }

            // IDPEL "NONPELANG" itu PLACEHOLDER buat temuan yang bukan
            // pelanggan terdaftar (mis. sambungan ilegal) — dipakai
            // BARENG oleh ratusan temuan berbeda (orang berbeda, NAMA
            // berbeda-beda). Kalau dikelompokkan pakai (ULP, IDPEL) kayak
            // pelanggan biasa, ratusan orang beda kebaca sebagai "1
            // pelanggan yang muncul ratusan kali" — jelas salah.
            //
            // Perlakuannya diatur $modeNonPelanggan (default "sembunyikan"
            // — dikecualikan total). Kalau mode-nya "sertakan" atau
            // "hanya", baris ini ikut disertakan — khusus buat IDPEL
            // "NONPELANG", dikelompokkan berdasarkan (ULP, NAMA) sebagai
            // ganti (ULP, IDPEL), karena NAMA-lah satu-satunya pembeda
            // identitas yang tersisa di data ini.
            $idpelBersih = strtoupper(trim((string) $r->idpel));
            $iniNonPelanggan = $idpelBersih === 'NONPELANG';

            if ($iniNonPelanggan) {
                $adaNonPelanggan = true;
                if ($modeNonPelanggan === 'sembunyikan') {
                    continue;
                }
            } elseif ($modeNonPelanggan === 'hanya') {
                continue;
            }

            $key = $iniNonPelanggan
                ? $kodeUlp . '|NONPELANG|' . strtoupper(trim((string) $r->nama))
                : $kodeUlp . '|' . $r->idpel;

            if (! isset($perPelanggan[$key])) {
                $perPelanggan[$key] = [
                    'ulp'       => $kodeUlp,
                    'idpel'     => $r->idpel,
                    'nama'      => $r->nama,
                    'jumlah'    => 0,
                    'total_kwh' => 0.0,
                    'total_ts'  => 0.0,
                    'temuan'    => [],
                ];
            }

            $perPelanggan[$key]['jumlah']++;
            $perPelanggan[$key]['total_kwh'] += (float) $r->kwh;
            $perPelanggan[$key]['total_ts']  += (float) $r->ts;
            $perPelanggan[$key]['temuan'][] = [
                'no_agenda'        => $r->no_agenda,
                'tanggal_register' => $r->tanggal_register,
                'bulan'            => $r->bulan,
                'tahun'            => $r->tahun,
                'gol'              => $r->gol,
                'daya'             => $r->daya,
                'kwh'              => (float) $r->kwh,
                'ts'               => (float) $r->ts,
            ];
        }

        // ---- Urutkan riwayat temuan tiap pelanggan berdasarkan tanggal
        // penetapan (tanggal_register), biar pas ditampilkan di modal
        // detail urutannya kronologis, dan biar end($temuan) di view/
        // export konsisten ngambil temuan yang PALING BARU. ----
        foreach ($perPelanggan as &$p) {
            usort($p['temuan'], fn ($a, $b) => strcmp((string) $a['tanggal_register'], (string) $b['tanggal_register']));
        }
        unset($p);

        // ---- Cuma yang muncul >= 2 kali yang dianggap "berulang". ----
        $pelangganBerulang = array_filter($perPelanggan, fn ($p) => $p['jumlah'] >= 2);

        // ---- Pivot: [ulp][jumlah] = jumlah pelanggan, plus daftar nilai
        // N yang benar-benar ada datanya (kolom pivot dinamis). ----
        $pivot = [];
        $daftarJumlahAssoc = [];
        foreach ($pelangganBerulang as $p) {
            $pivot[$p['ulp']][$p['jumlah']] = ($pivot[$p['ulp']][$p['jumlah']] ?? 0) + 1;
            $daftarJumlahAssoc[$p['jumlah']] = true;
        }
        $daftarJumlah = array_keys($daftarJumlahAssoc);
        sort($daftarJumlah);

        $pengulanganTertinggi = $daftarJumlah ? max($daftarJumlah) : 0;

        // ---- Baris pivot per ULP + total kolom (Grand Total baris paling bawah) ----
        $pivotRows = [];
        $grandTotalKolom = array_fill_keys($daftarJumlah, 0);
        $grandTotalKeseluruhan = 0;

        foreach ($pivot as $kodeUlp => $kolomJumlah) {
            $totalBaris = array_sum($kolomJumlah);
            $pivotRows[] = [
                'kode'  => $kodeUlp,
                'nama'  => DetailTagihanSusulan::namaUlp($kodeUlp) ?? $kodeUlp,
                'kolom' => $kolomJumlah,
                'total' => $totalBaris,
            ];
            foreach ($kolomJumlah as $j => $n) {
                $grandTotalKolom[$j] += $n;
            }
            $grandTotalKeseluruhan += $totalBaris;
        }
        usort($pivotRows, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        // ---- Ringkasan ----
        $totalPelangganBerulang = count($pelangganBerulang);
        $totalUlpTerdampak      = count($pivot);
        $rataRataPengulangan    = $totalPelangganBerulang > 0
            ? round(array_sum(array_column($pelangganBerulang, 'jumlah')) / $totalPelangganBerulang, 1)
            : 0;

        // ---- Daftar pelanggan (tabel bawah, difilter jumlah dari query
        // string, plus pencarian bebas per IDPEL/Nama). Filter ULP
        // sekarang sudah diterapkan lebih awal via kodeUlpTerfilter(),
        // jadi gak perlu ->when('ulp', ...) lagi di sini. ----
        $daftarPelanggan = collect(array_values($pelangganBerulang))
            ->when($jumlahFilter, fn ($c) => $c->where('jumlah', (int) $jumlahFilter))
            ->when($searchFilter, function ($c) use ($searchFilter) {
                $kata = strtolower($searchFilter);
                return $c->filter(function ($p) use ($kata) {
                    return str_contains(strtolower((string) $p['idpel']), $kata)
                        || str_contains(strtolower((string) $p['nama']), $kata);
                });
            })
            ->sortByDesc('jumlah')
            ->values();

        return [
            'daftarTahun'                 => $daftarTahun,
            'tahunAktif'                  => $tahunDipilih,
            'daftarUlp'                   => $daftarUlp,       // [['kode','nama'], ...] — dipakai partial filter
            'daftarUlpAssoc'              => $daftarUlpAssoc,  // [kode => nama] — dipakai tabel/ringkasan halaman ini
            'filter'                      => $filter,
            'filterInfoText'              => $filterInfoText,
            'tampilkanTahunFilter'        => true,
            'tampilkanSemuaTahunOpsi'     => true,
            'tampilkanGolonganFilter'     => true,
            'tampilkanNonPelangganFilter' => true,
            'daftarGolongan'              => $daftarGolongan,
            'golonganFilter'              => $golonganFilter,
            'jumlahFilter'                => $jumlahFilter,
            'searchFilter'                => $searchFilter,
            'daftarJumlah'                => $daftarJumlah,
            'pengulanganTertinggi'        => $pengulanganTertinggi,
            'pivotRows'                   => $pivotRows,
            'grandTotalKolom'             => $grandTotalKolom,
            'grandTotalKeseluruhan'       => $grandTotalKeseluruhan,
            'totalPelangganBerulang'      => $totalPelangganBerulang,
            'totalUlpTerdampak'           => $totalUlpTerdampak,
            'rataRataPengulangan'         => $rataRataPengulangan,
            'adaNonPelanggan'             => $adaNonPelanggan,
            'modeNonPelanggan'            => $modeNonPelanggan,
            'daftarPelanggan'             => $daftarPelanggan,
        ];
    }
}
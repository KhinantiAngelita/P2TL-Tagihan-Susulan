<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use Illuminate\Http\Request;

class PenetapanBerulangController extends Controller
{
    /**
     * Golongan yang secara DEFAULT gak tercentang / gak ikut dihitung
     * kalau user belum pernah menyentuh filter Golongan sama sekali
     * (halaman baru dibuka). "UPDA" & "KWH" itu bukan kode golongan asli
     * — ketemu waktu cross-check ke Excel, itu baris koreksi manual yang
     * datanya kotor (lihat catatan di dataTargetRealisasi-nya kalau ada,
     * atau riwayat obrolan sebelumnya). "P4" dikecualikan default sesuai
     * permintaan eksplisit, meski itu kode golongan valid.
     *
     * User tetap bisa centang manual atau klik "Pilih Semua" di panel
     * filternya buat mengikutkan golongan ini — begitu user submit
     * filter secara eksplisit (request punya key 'golongan', walau
     * isinya kosong), default ini TIDAK dipakai lagi, murni ngikut
     * pilihan user.
     */
    private const GOLONGAN_DEFAULT_TIDAK_DICENTANG = ['UPDA', 'P4', 'KWH'];

    /**
     * Ambil kode ULP dari no_agenda (P2TL/{ULP}/{tanggal}/{urut}).
     *
     * PERBAIKAN: ditemukan dari cross-check ke Excel asli — sebagian
     * baris (mis. baris koreksi manual dengan GOL "UPDA"/"KWH") punya
     * NOAGENDA yang RUSAK, gak pakai format ber-"/" sama sekali (cuma
     * angka polos, mis. "538510582603270620"). Sebelumnya baris begini
     * SELALU di-skip total (kodeUlp() balikin null), apapun filter
     * Golongan yang dipilih — karena skip-nya kejadian sebelum filter
     * golongan diterapkan. Sekarang ada fallback: kalau format standar
     * gak ketemu, coba cocokkan awal string-nya ke kode ULP yang
     * dikenal sistem (kode ULP-nya ternyata masih ada di situ, cuma
     * delimiter "/"-nya yang hilang).
     */
    private function kodeUlp(?string $noAgenda): ?string
    {
        $noAgenda = (string) $noAgenda;
        $parts = explode('/', $noAgenda);
        if (isset($parts[1]) && $parts[1] !== '') {
            return $parts[1];
        }

        foreach (array_keys(DetailTagihanSusulan::PETA_NAMA_ULP) as $kode) {
            if (str_starts_with($noAgenda, (string) $kode)) {
                return (string) $kode;
            }
        }

        return null;
    }

    /**
     * Halaman "Penetapan Berulang" — pelanggan (IDPEL) yang muncul lebih
     * dari sekali di data temuan P2TL (laporan_susulans berstatus aktif),
     * artinya sudah ditagih/ketahuan berulang kali.
     *
     * Menampilkan:
     * 1. Grafik batang total pelanggan per jumlah kemunculan (seluruh ULP).
     * 2. Ringkasan (jumlah pelanggan berulang, ULP terdampak, dst).
     * 3. Pivot ULP x Jumlah Kemunculan (berapa pelanggan per ULP yang
     *    muncul persis N kali) — kolomnya dinamis, cuma nampilin nilai N
     *    yang memang ada datanya.
     * 4. Daftar pelanggan berulang, bisa difilter per jumlah kemunculan
     *    (klik header kolom N di pivot) dan/atau per ULP.
     *
     * Route: GET /laporan/penetapan-berulang -> name('laporan.penetapan-berulang')
     */
    public function index(Request $request)
    {
        $request->validate([
            'tahun'      => 'nullable|integer',
            'ulp'        => 'nullable|string|max:20',
            'jumlah'     => 'nullable|integer|min:2',
            'golongan'   => 'nullable|array',
            'golongan.*' => 'string|max:10',
            'nonpelang'  => 'nullable|in:sembunyikan,sertakan,hanya',
            'search'     => 'nullable|string|max:100',
        ]);

        $daftarTahun = LaporanSusulan::aktif()
            ->whereNotNull('tahun')->distinct()
            ->orderByDesc('tahun')->pluck('tahun');

        $tahunAktif   = $request->input('tahun') ?: null;    // null = semua tahun
        $ulpFilter    = $request->input('ulp') ?: null;      // null = semua ULP
        $jumlahFilter = $request->input('jumlah') ?: null;   // null = semua
        $searchFilter = $request->input('search') ?: null;   // null = semua (cari IDPEL/Nama)

        // ---- Perlakuan Non-Pelanggan (IDPEL "NONPELANG") — dropdown 3
        // opsi, DEFAULT "sembunyikan" (dikecualikan total, kayak
        // perilaku semula):
        // - sembunyikan : NONPELANG gak ikut dihitung sama sekali.
        // - sertakan    : NONPELANG ikut, dicampur bareng pelanggan biasa
        //                 (dikelompokkan per Nama, bukan IDPEL).
        // - hanya       : CUMA NONPELANG yang ditampilkan, pelanggan
        //                 biasa (IDPEL asli) disembunyikan.
        $modeNonPelanggan = $request->input('nonpelang', 'sembunyikan');

        $daftarUlpAssoc = DetailTagihanSusulan::PETA_NAMA_ULP;

        // ---- Daftar golongan buat opsi checkbox — SELALU dari seluruh
        // temuan aktif (opsional difilter tahun saja), TIDAK ikut
        // difilter golongan itu sendiri, biar daftar checkbox-nya gak
        // ikut menyusut/hilang pas user lagi milih sebagian golongan. ----
        $daftarGolongan = DetailTagihanSusulan::query()
            ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
            ->where('laporan_susulans.status', 'aktif')
            ->when($tahunAktif, fn ($q) => $q->where('laporan_susulans.tahun', $tahunAktif))
            ->select('detail_tagihan_susulans.gol')
            ->distinct()
            ->orderBy('detail_tagihan_susulans.gol')
            ->pluck('gol');

        // ---- Filter Golongan: kalau user BELUM PERNAH menyentuh filter
        // ini sama sekali (URL gak punya key 'golongan' sama sekali —
        // beda dengan "dikirim tapi kosong karena semua checkbox
        // di-uncheck"), pakai default: SEMUA golongan KECUALI yang ada
        // di GOLONGAN_DEFAULT_TIDAK_DICENTANG. Begitu user pernah submit
        // filter Golongan secara eksplisit (misal klik "Pilih Semua" atau
        // centang manual lalu Terapkan), default ini gak dipakai lagi —
        // murni ngikut apa yang dikirim, termasuk kalau user MEMANG mau
        // mengikutkan UPDA/P4/KWH. ----
        if ($request->has('golongan')) {
            $golonganFilter = $request->input('golongan') ?: null;
        } else {
            $golonganFilter = array_values(array_diff($daftarGolongan->all(), self::GOLONGAN_DEFAULT_TIDAK_DICENTANG));
        }

        // ---- Ambil semua baris temuan aktif (opsional difilter tahun &
        // golongan), lalu dikelompokkan per (ULP, IDPEL) buat menghitung
        // berapa kali tiap pelanggan muncul di data temuan. Filter
        // golongan diterapkan DI SINI (bukan cuma di tabel daftar
        // pelanggan) — karena ini mengubah definisi "muncul": kalau
        // golongan P3 gak dicentang, kemunculan pelanggan itu dengan
        // golongan P3 gak ikut dihitung sama sekali, baik di pivot
        // maupun ringkasan. ----
        $rows = DetailTagihanSusulan::query()
            ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
            ->where('laporan_susulans.status', 'aktif')
            ->when($tahunAktif, fn ($q) => $q->where('laporan_susulans.tahun', $tahunAktif))
            ->when($golonganFilter, fn ($q) => $q->whereIn('detail_tagihan_susulans.gol', $golonganFilter))
            ->select(
                'detail_tagihan_susulans.no_agenda',
                'detail_tagihan_susulans.idpel',
                'detail_tagihan_susulans.nama',
                'detail_tagihan_susulans.gol',
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
            $kodeUlp = $this->kodeUlp($r->no_agenda);
            if (! $kodeUlp) {
                continue;
            }

            // PERBAIKAN (ditemukan dari cross-check ke data Excel asli):
            // IDPEL "NONPELANG" itu PLACEHOLDER buat temuan yang bukan
            // pelanggan terdaftar (mis. sambungan ilegal) — dipakai
            // BARENG oleh ratusan temuan berbeda (orang berbeda, NAMA
            // berbeda-beda). Kalau dikelompokkan pakai (ULP, IDPEL) kayak
            // pelanggan biasa, ratusan orang beda kebaca sebagai "1
            // pelanggan yang muncul ratusan kali" — jelas salah.
            //
            // Perlakuannya diatur $modeNonPelanggan (default "sembunyikan"
            // — dikecualikan total, kayak perilaku semula). Kalau mode-nya
            // "sertakan" atau "hanya", baris ini ikut disertakan — caranya,
            // khusus buat IDPEL "NONPELANG", dikelompokkan berdasarkan
            // (ULP, NAMA) sebagai ganti (ULP, IDPEL), karena NAMA-lah
            // satu-satunya pembeda identitas yang tersisa di data ini.
            // Jadi "budi" muncul 3x di ULP yang sama tetap kehitung
            // sebagai 1 pelanggan berulang 3x, tapi gak ke-mix sama
            // "amir" yang kebetulan juga NONPELANG di ULP yang sama. Ini
            // masih longgar (dua orang beda dengan nama yang persis sama
            // & ULP sama akan tetap ke-anggap satu orang), tapi jauh
            // lebih akurat dibanding nge-gabung semuanya lewat IDPEL yang
            // memang bukan identifier asli.
            $idpelBersih = strtoupper(trim((string) $r->idpel));
            $iniNonPelanggan = $idpelBersih === 'NONPELANG';

            if ($iniNonPelanggan) {
                $adaNonPelanggan = true;
                if ($modeNonPelanggan === 'sembunyikan') {
                    continue;
                }
            } elseif ($modeNonPelanggan === 'hanya') {
                // Mode "hanya" -> pelanggan biasa (IDPEL asli) disembunyikan.
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
                'kwh'              => (float) $r->kwh,
                'ts'               => (float) $r->ts,
            ];
        }

        // ---- Urutkan riwayat temuan tiap pelanggan berdasarkan tanggal
        // penetapan (tanggal_register), biar pas ditampilkan di modal
        // detail urutannya kronologis. ----
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

        // ---- Daftar pelanggan (tabel bawah, difilter jumlah & ULP dari
        // query string, plus pencarian bebas per IDPEL/Nama) ----
        $daftarPelanggan = collect(array_values($pelangganBerulang))
            ->when($jumlahFilter, fn ($c) => $c->where('jumlah', (int) $jumlahFilter))
            ->when($ulpFilter, fn ($c) => $c->where('ulp', $ulpFilter))
            ->when($searchFilter, function ($c) use ($searchFilter) {
                $kata = strtolower($searchFilter);
                return $c->filter(function ($p) use ($kata) {
                    return str_contains(strtolower((string) $p['idpel']), $kata)
                        || str_contains(strtolower((string) $p['nama']), $kata);
                });
            })
            ->sortByDesc('jumlah')
            ->values();

        return view('laporan.penetapan-berulang', [
            'daftarTahun'            => $daftarTahun,
            'tahunAktif'             => $tahunAktif,
            'daftarUlp'              => $daftarUlpAssoc,
            'ulpFilter'              => $ulpFilter,
            'daftarGolongan'         => $daftarGolongan,
            'golonganFilter'         => $golonganFilter,
            'jumlahFilter'           => $jumlahFilter,
            'searchFilter'           => $searchFilter,
            'daftarJumlah'           => $daftarJumlah,
            'pengulanganTertinggi'   => $pengulanganTertinggi,
            'pivotRows'              => $pivotRows,
            'grandTotalKolom'        => $grandTotalKolom,
            'grandTotalKeseluruhan'  => $grandTotalKeseluruhan,
            'totalPelangganBerulang' => $totalPelangganBerulang,
            'totalUlpTerdampak'      => $totalUlpTerdampak,
            'rataRataPengulangan'    => $rataRataPengulangan,
            'adaNonPelanggan'        => $adaNonPelanggan,
            'modeNonPelanggan'       => $modeNonPelanggan,
            'daftarPelanggan'        => $daftarPelanggan,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelangganController extends Controller
{
    /**
     * Ekspresi SQL buat "meniru" accessor getUlpAttribute() di level query,
     * karena kolom `ulp` bukan kolom asli di database — dia hasil parsing
     * dari segmen ke-2 `no_agenda` (P2TL/{ULP}/{tanggal}/{urut}). Sama
     * seperti yang dipakai di DetailDataController & TrendController.
     */
    private const ULP_SQL = "SUBSTRING_INDEX(SUBSTRING_INDEX(detail_tagihan_susulans.no_agenda, '/', 2), '/', -1)";

    /**
     * Rapikan whitespace pada suatu teks: gabung segala jenis whitespace
     * (spasi ganda, tab, newline, non-breaking space \u00A0, dan beberapa
     * varian spasi unicode lain yang kerap nyelip dari hasil import
     * Excel/PDF) jadi satu spasi biasa, lalu buang spasi nyasar di
     * awal/akhir. Modifier /u wajib supaya \x{...} unicode code point
     * dikenali oleh PCRE.
     */
    private static function rapikanSpasi(?string $teks): string
    {
        $teks = (string) $teks;

        return trim(preg_replace(
            '/[\x{00A0}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}\s]+/u',
            ' ',
            $teks
        ));
    }

    /**
     * Rapikan nama pelanggan. Selain rapikan spasi, sebagian nama di
     * database (hasil ekstraksi PDF/Excel) punya huruf yang "kepisah"
     * jadi token satu-huruf berturut-turut, misal "A C A N G" yang
     * seharusnya "ACANG". Fungsi ini menggabungkan RUN 2 token satu-huruf
     * atau lebih yang berurutan jadi satu kata, tapi TIDAK menyentuh
     * token satu huruf yang berdiri sendiri di antara kata utuh (biasanya
     * inisial nama asli, mis. "A DENDY S" tetap dibiarkan "A DENDY S").
     */
    private static function rapikanNama(?string $nama): string
    {
        $nama = self::rapikanSpasi($nama);

        if ($nama === '') {
            return '';
        }

        $token  = explode(' ', $nama);
        $hasil  = [];
        $buffer = [];

        foreach ($token as $t) {
            if (mb_strlen($t) === 1) {
                $buffer[] = $t;
                continue;
            }

            if (count($buffer) >= 2) {
                $hasil[] = implode('', $buffer);
            } elseif (count($buffer) === 1) {
                $hasil[] = $buffer[0];
            }
            $buffer = [];

            $hasil[] = $t;
        }

        // Flush sisa buffer di akhir string (kalau nama diakhiri huruf lepas).
        if (count($buffer) >= 2) {
            $hasil[] = implode('', $buffer);
        } elseif (count($buffer) === 1) {
            $hasil[] = $buffer[0];
        }

        return implode(' ', $hasil);
    }

    /**
     * Halaman "Daftar Pelanggan" — daftar SEMUA pelanggan unik (per idpel)
     * dari seluruh dokumen yang sudah diupload (laporan berstatus aktif).
     * Kalau satu idpel muncul di lebih dari satu laporan (mis. beberapa
     * bulan berbeda), yang ditampilkan adalah baris PALING BARU (id
     * terbesar) untuk idpel itu — supaya daftar tidak dobel per pelanggan.
     *
     * Route: GET /pelanggan -> name('pelanggan.index')
     */
    public function index(Request $request)
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'golongan' => 'nullable|string|max:20',
            'ulp'      => 'nullable|string|max:20',
        ]);

        $search        = $request->input('search');
        $golonganAktif = $request->input('golongan', 'semua');
        $ulpAktif      = $request->input('ulp', 'semua');

        // ---- Subquery: ambil id baris PALING BARU per idpel, hanya dari
        // laporan berstatus aktif. Ini yang bikin daftar jadi "1 baris per
        // pelanggan" walau idpel yang sama muncul di banyak laporan. ----
        $idTerbaruPerPelanggan = DetailTagihanSusulan::query()
            ->join('laporan_susulans', 'laporan_susulans.id', '=', 'detail_tagihan_susulans.laporan_susulan_id')
            ->where('laporan_susulans.status', 'aktif')
            ->whereNotNull('detail_tagihan_susulans.idpel')
            ->where('detail_tagihan_susulans.idpel', '!=', '')
            ->selectRaw('MAX(detail_tagihan_susulans.id) as id')
            ->groupBy('detail_tagihan_susulans.idpel');

        $query = DetailTagihanSusulan::query()
            ->joinSub($idTerbaruPerPelanggan, 'terbaru', 'terbaru.id', '=', 'detail_tagihan_susulans.id')
            ->select('detail_tagihan_susulans.*', DB::raw(self::ULP_SQL . ' as ulp_kode'))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('detail_tagihan_susulans.idpel', 'like', "%{$search}%")
                        ->orWhere('detail_tagihan_susulans.nama', 'like', "%{$search}%");
                });
            })
            ->when($golonganAktif && strtolower($golonganAktif) !== 'semua', function ($q) use ($golonganAktif) {
                $q->where('detail_tagihan_susulans.gol', $golonganAktif);
            })
            ->when($ulpAktif && strtolower($ulpAktif) !== 'semua', function ($q) use ($ulpAktif) {
                $q->havingRaw(self::ULP_SQL . ' = ?', [$ulpAktif]);
            });

        $pelanggan = (clone $query)
            ->orderBy('detail_tagihan_susulans.nama')
            ->paginate(15)
            ->withQueryString();

        // Tempelkan versi nama yang sudah dirapikan (spasi + huruf yang
        // kepisah-pisah) ke tiap baris, supaya blade tinggal pakai
        // langsung tanpa perlu regex lagi di view.
        $pelanggan->getCollection()->transform(function ($item) {
            $item->nama_tampil = self::rapikanNama($item->nama);
            return $item;
        });

        // Daftar opsi filter golongan & ULP — diambil dari seluruh
        // pelanggan unik (independen dari filter yang sedang aktif),
        // biar dropdown-nya konsisten walau lagi difilter.
        $daftarGolongan = DetailTagihanSusulan::query()
            ->joinSub($idTerbaruPerPelanggan, 'terbaru', 'terbaru.id', '=', 'detail_tagihan_susulans.id')
            ->select('detail_tagihan_susulans.gol')
            ->distinct()
            ->orderBy('detail_tagihan_susulans.gol')
            ->pluck('gol')
            ->filter();

        $daftarUlp = DetailTagihanSusulan::query()
            ->joinSub($idTerbaruPerPelanggan, 'terbaru', 'terbaru.id', '=', 'detail_tagihan_susulans.id')
            ->pluck('detail_tagihan_susulans.no_agenda')
            ->map(function ($noAgenda) {
                $parts = explode('/', (string) $noAgenda);
                return $parts[1] ?? null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($kode) => ['kode' => $kode, 'nama' => DetailTagihanSusulan::namaUlp($kode)]);

        $totalPelanggan = (clone $idTerbaruPerPelanggan)->get()->count();

        return view('pelanggan.index', [
            'pelanggan'      => $pelanggan,
            'search'         => $search,
            'golonganAktif'  => $golonganAktif,
            'daftarGolongan' => $daftarGolongan,
            'ulpAktif'       => $ulpAktif,
            'daftarUlp'      => $daftarUlp,
            'totalPelanggan' => $totalPelanggan,
        ]);
    }

    /**
     * Detail satu pelanggan (satu baris DetailTagihanSusulan) dalam
     * format JSON — dipakai buat modal pop-out di halaman Daftar
     * Pelanggan. READ-ONLY, tidak ada endpoint update/delete untuk
     * pelanggan dari halaman ini.
     *
     * Route: GET /pelanggan/{id}/json -> name('pelanggan.show.json')
     */
    public function show(int $id)
    {
        $detail = DetailTagihanSusulan::findOrFail($id);

        $parts      = explode('/', (string) $detail->no_agenda);
        $kodeUlp    = $parts[1] ?? null;
        $tanggalStr = $parts[2] ?? null;

        $tanggalAgenda = null;
        if ($tanggalStr && preg_match('/^\d{8}$/', $tanggalStr)) {
            try {
                $tanggalAgenda = \Carbon\Carbon::createFromFormat('Ymd', $tanggalStr)->format('Y-m-d');
            } catch (\Exception $e) {
                $tanggalAgenda = null;
            }
        }

        $laporan = LaporanSusulan::where('id', $detail->laporan_susulan_id)
            ->first(['unit_induk', 'unit_up3', 'bulan', 'tahun', 'versi', 'status']);

        return response()->json([
            'idpel'             => $detail->idpel,
            'nama'              => self::rapikanNama($detail->nama),
            'gol'               => $detail->gol,
            'alamat'            => self::rapikanSpasi($detail->alamat),
            'daya'              => $detail->daya,
            'ulp_kode'          => $kodeUlp,
            'ulp_nama'          => $kodeUlp ? DetailTagihanSusulan::namaUlp($kodeUlp) : null,
            'kwh'               => (float) $detail->kwh,
            'beban'             => (float) $detail->beban,
            'kwh_rupiah'        => (float) $detail->kwh_rupiah,
            'ts'                => (float) $detail->ts,
            'materai'           => (float) $detail->materai,
            'segel'             => (float) $detail->segel,
            'materia'           => (float) $detail->materia,
            'rpppj'             => (float) $detail->rpppj,
            'rpujl'             => (float) $detail->rpujl,
            'rpppn'             => (float) $detail->rpppn,
            'total'             => (float) $detail->total,
            'tunai'             => (float) $detail->tunai,
            'angsuran'          => (float) $detail->angsuran,
            'no_agenda'         => $detail->no_agenda,
            'tanggal_agenda'    => $tanggalAgenda,
            'tanggal_register'  => $detail->tanggal_register,
            'nomor_register'    => $detail->nomor_register,
            'tanggal_sph'       => $detail->tanggal_sph,
            'nomor_sph'         => $detail->nomor_sph,
            'laporan' => $laporan ? [
                'unit_induk' => $laporan->unit_induk,
                'unit_up3'   => $laporan->unit_up3,
                'bulan'      => $laporan->bulan,
                'tahun'      => $laporan->tahun,
                'versi'      => $laporan->versi,
            ] : null,
        ]);
    }
}
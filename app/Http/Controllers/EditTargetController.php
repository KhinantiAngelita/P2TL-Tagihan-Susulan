<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\TargetBulanan;
use Illuminate\Http\Request;

class EditTargetController extends Controller
{
    /**
     * Halaman "Edit Target" — form input target per bulan (kWh atau Rp TS)
     * per tahun & per ULP.
     *
     * Opsi "Semua ULP (UP3)" BUKAN entitas yang bisa diisi/disimpan.
     * Kalau dipilih, halaman ini cuma menampilkan REKAP: penjumlahan
     * (SUM) nilai_target dari seluruh ULP yang dikenal sistem, per bulan.
     * Read-only — untuk mengubah angka, pengguna wajib pilih ULP spesifik
     * di filter.
     *
     * Route: GET /edit-target -> name('edit-target.index')
     */
    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $jenis = $request->input('jenis', 'kwh');
        $ulp   = $request->input('ulp'); // null/'' = Semua ULP (rekap)

        if (! array_key_exists($jenis, TargetBulanan::JENIS)) {
            $jenis = 'kwh';
        }

        $daftarUlp = DetailTagihanSusulan::PETA_NAMA_ULP;

        if ($ulp) {
            // ---- ULP spesifik -> baca nilai target aslinya per bulan. ----
            $existing = TargetBulanan::where('tahun', $tahun)
                ->where('jenis', $jenis)
                ->where('ulp', $ulp)
                ->pluck('nilai_target', 'bulan');
        } else {
            // ---- "Semua ULP (UP3)" -> REKAP. Jumlahkan nilai_target dari
            // setiap kode ULP yang terdaftar di PETA_NAMA_ULP (bukan
            // sembarang baris — supaya kalau ada baris "sampah"/ulp tidak
            // dikenal di tabel, tidak ikut kehitung), dikelompokkan per
            // bulan. Ini murni tampilan, tidak pernah ditulis balik ke DB
            // dari sisi "Semua ULP". ----
            $existing = TargetBulanan::where('tahun', $tahun)
                ->where('jenis', $jenis)
                ->whereIn('ulp', array_keys($daftarUlp))
                ->selectRaw('bulan, SUM(nilai_target) as total')
                ->groupBy('bulan')
                ->pluck('total', 'bulan');
        }

        $targetBulanan = collect(range(1, 12))->mapWithKeys(function ($bulan) use ($existing) {
            return [$bulan => $existing->get($bulan, 0)];
        });

        $daftarTahun = range(now()->year - 2, now()->year + 1);

        return view('edit-target.index', [
            'tahun'         => $tahun,
            'jenis'         => $jenis,
            'ulpAktif'      => $ulp,
            'isSemuaUlp'    => ! $ulp,
            'targetBulanan' => $targetBulanan,
            'daftarTahun'   => $daftarTahun,
            'daftarUlp'     => $daftarUlp,
            'namaBulan'     => TargetBulanan::NAMA_BULAN,
            'jenisOptions'  => TargetBulanan::JENIS,
        ]);
    }

    /**
     * Simpan 12 nilai target sekaligus (upsert per bulan) untuk kombinasi
     * tahun + jenis + ulp yang dipilih.
     *
     * Tidak ada lagi mode "Semua ULP" di sini. 'ulp' WAJIB kode ULP
     * spesifik yang dikenal sistem — request tanpa ulp (atau ulp kosong)
     * ditolak di level validasi, jadi tidak mungkin ada submit yang
     * mendistribusikan/menimpa banyak ULP sekaligus lagi.
     *
     * Route: POST /edit-target -> name('edit-target.update')
     */
    public function update(Request $request)
    {
        $daftarUlp = DetailTagihanSusulan::PETA_NAMA_ULP;

        $validated = $request->validate([
            'tahun'    => 'required|integer|min:2000|max:2100',
            'jenis'    => 'required|in:kwh,ts',
            'ulp'      => 'required|string|max:20|in:' . implode(',', array_keys($daftarUlp)),
            'target'   => 'required|array',
            'target.*' => 'required|numeric|min:0',
        ]);

        foreach ($validated['target'] as $bulan => $nilai) {
            TargetBulanan::updateOrCreate(
                [
                    'tahun' => $validated['tahun'],
                    'bulan' => $bulan,
                    'jenis' => $validated['jenis'],
                    'ulp'   => $validated['ulp'],
                ],
                ['nilai_target' => $nilai]
            );
        }

        return redirect()
            ->route('edit-target.index', [
                'tahun' => $validated['tahun'],
                'jenis' => $validated['jenis'],
                'ulp'   => $validated['ulp'],
            ])
            ->with('success', 'Target ULP ' . $validated['ulp'] . ' berhasil disimpan.');
    }
}
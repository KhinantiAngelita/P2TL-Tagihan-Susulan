<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\TargetBulanan;
use Illuminate\Http\Request;

class EditTargetController extends Controller
{
    /**
     * Halaman "Edit Target" — form input target per bulan (kWh atau Rp TS),
     * bisa dipilih per tahun & per ULP, atau "Semua ULP" untuk mendistribusikan
     * nilai yang sama ke seluruh ULP sekaligus (lihat catatan di update()).
     *
     * Route: GET /edit-target -> name('edit-target.index')
     */
    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $jenis = $request->input('jenis', 'kwh');
        $ulp   = $request->input('ulp'); // null/'' = Semua ULP

        if (! array_key_exists($jenis, TargetBulanan::JENIS)) {
            $jenis = 'kwh';
        }

        $daftarUlp = DetailTagihanSusulan::PETA_NAMA_ULP;

        // ---- Nilai yang ditampilkan di form. Kalau filter "Semua ULP"
        // dipilih, tampilkan nilai ULP PERTAMA di daftar sebagai representasi
        // (karena sejak Solusi A, submit "Semua ULP" mendistribusikan nilai
        // yang SAMA ke semua ULP — jadi ULP mana pun yang dibaca nilainya
        // akan sama, selama tidak pernah di-override individual sesudahnya).
        // Kalau sebagian ULP pernah di-override manual, nilai yang tampil di
        // sini bisa saja tidak representatif untuk SEMUA ULP lagi — itu
        // risiko yang disadari dari desain override per-ULP. ----
        $ulpUntukBaca = $ulp ?: array_key_first($daftarUlp);

        $existing = TargetBulanan::where('tahun', $tahun)
            ->where('jenis', $jenis)
            ->where('ulp', $ulpUntukBaca)
            ->pluck('nilai_target', 'bulan');

        $targetBulanan = collect(range(1, 12))->mapWithKeys(function ($bulan) use ($existing) {
            return [$bulan => $existing->get($bulan, 0)];
        });

        $daftarTahun = range(now()->year - 2, now()->year + 1);

        return view('edit-target.index', [
            'tahun'         => $tahun,
            'jenis'         => $jenis,
            'ulpAktif'      => $ulp,
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
     * PERILAKU "Semua ULP" (ulp kosong/null saat submit):
     * Nilai yang diisi didistribusikan (di-upsert) ke SETIAP kode ULP yang
     * ada di DetailTagihanSusulan::PETA_NAMA_ULP — BUKAN disimpan sebagai
     * satu baris global (ulp = null) seperti sebelumnya. Jadi kalau ada 7
     * ULP, submit ini menghasilkan 7 baris TargetBulanan per bulan (7 x 12
     * = 84 baris), semuanya bernilai sama persis dengan yang diinput.
     *
     * PERILAKU ULP spesifik (ulp diisi kode tertentu):
     * Tetap seperti semula — cuma meng-upsert baris untuk ULP itu saja,
     * dipakai untuk override manual satu unit yang targetnya beda dari
     * ULP lain (dijalankan SETELAH submit "Semua ULP", supaya override-nya
     * tidak ketiban ulang oleh distribusi massal berikutnya).
     *
     * Route: POST /edit-target -> name('edit-target.update')
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'tahun'    => 'required|integer|min:2000|max:2100',
            'jenis'    => 'required|in:kwh,ts',
            'ulp'      => 'nullable|string|max:20',
            'target'   => 'required|array',
            'target.*' => 'required|numeric|min:0',
        ]);

        $ulpDipilih = $validated['ulp'] ?: null;

        // ---- "Semua ULP" -> distribusikan ke SETIAP kode ULP yang
        // dikenal sistem, bukan disimpan sebagai satu baris ulp=null. ----
        if ($ulpDipilih === null) {
            $semuaKodeUlp = array_keys(DetailTagihanSusulan::PETA_NAMA_ULP);

            foreach ($semuaKodeUlp as $kodeUlp) {
                foreach ($validated['target'] as $bulan => $nilai) {
                    TargetBulanan::updateOrCreate(
                        [
                            'tahun' => $validated['tahun'],
                            'bulan' => $bulan,
                            'jenis' => $validated['jenis'],
                            'ulp'   => $kodeUlp,
                        ],
                        ['nilai_target' => $nilai]
                    );
                }
            }
        } else {
            // ---- ULP spesifik -> override cuma untuk unit itu saja. ----
            foreach ($validated['target'] as $bulan => $nilai) {
                TargetBulanan::updateOrCreate(
                    [
                        'tahun' => $validated['tahun'],
                        'bulan' => $bulan,
                        'jenis' => $validated['jenis'],
                        'ulp'   => $ulpDipilih,
                    ],
                    ['nilai_target' => $nilai]
                );
            }
        }

        return redirect()
            ->route('edit-target.index', [
                'tahun' => $validated['tahun'],
                'jenis' => $validated['jenis'],
                'ulp'   => $ulpDipilih,
            ])
            ->with('success', $ulpDipilih === null
                ? 'Target berhasil didistribusikan ke semua ULP.'
                : 'Target ULP ' . ($ulpDipilih) . ' berhasil disimpan.'
            );
    }
}
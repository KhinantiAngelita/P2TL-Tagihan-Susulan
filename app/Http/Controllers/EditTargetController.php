<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use App\Models\TargetBulanan;
use Illuminate\Http\Request;

class EditTargetController extends Controller
{
    /**
     * Halaman "Edit Target" — form input target per bulan (kWh atau Rp TS),
     * bisa dipilih per tahun & per ULP (atau "Semua ULP" untuk target global).
     *
     * Route: GET /edit-target -> name('edit-target.index')
     */
    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $jenis = $request->input('jenis', 'kwh');
        $ulp   = $request->input('ulp'); // null/'' = Semua ULP (target global)

        if (! array_key_exists($jenis, TargetBulanan::JENIS)) {
            $jenis = 'kwh';
        }

        $existing = TargetBulanan::where('tahun', $tahun)
            ->where('jenis', $jenis)
            ->where('ulp', $ulp ?: null)
            ->pluck('nilai_target', 'bulan');

        $targetBulanan = collect(range(1, 12))->mapWithKeys(function ($bulan) use ($existing) {
            return [$bulan => $existing->get($bulan, 0)];
        });

        $daftarTahun = range(now()->year - 2, now()->year + 1);

        $daftarUlp = DetailTagihanSusulan::PETA_NAMA_ULP;;

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
     * Simpan 12 nilai target sekaligus (upsert per bulan) untuk
     * kombinasi tahun + jenis + ulp yang dipilih.
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

        $ulp = $validated['ulp'] ?: null;

        foreach ($validated['target'] as $bulan => $nilai) {
            TargetBulanan::updateOrCreate(
                [
                    'tahun' => $validated['tahun'],
                    'bulan' => $bulan,
                    'jenis' => $validated['jenis'],
                    'ulp'   => $ulp,
                ],
                ['nilai_target' => $nilai]
            );
        }

        return redirect()
            ->route('edit-target.index', [
                'tahun' => $validated['tahun'],
                'jenis' => $validated['jenis'],
                'ulp'   => $ulp,
            ])
            ->with('success', 'Target berhasil disimpan.');
    }
}
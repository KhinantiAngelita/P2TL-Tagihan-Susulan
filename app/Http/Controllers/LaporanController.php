<?php

namespace App\Http\Controllers;

use App\Imports\TagihanSusulanImport;
use App\Models\LaporanSusulan;
use App\Models\User;
use App\Notifications\LaporanBaruDiupload;
use App\Services\RingkasanGolTarifService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function __construct(
        private readonly RingkasanGolTarifService $ringkasanGolTarifService
    ) {
    }

    public function create()
    {
        $lastUpload = LaporanSusulan::latest()->first();
        $berhasilHariIni = LaporanSusulan::whereDate('created_at', today())->count();

        return view('laporan.create', compact('lastUpload', 'berhasilHariIni'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|array|min:1',
            'file_excel.*' => 'file|mimes:xls,xlsx|max:20480',
        ]);

        $files = $request->file('file_excel');

        $berhasil = [];
        $gagal = [];
        $laporanTerakhir = null;

        // Setiap file diproses dalam transaksi masing-masing supaya satu file
        // yang gagal tidak ikut me-rollback file lain yang sudah berhasil diimport.
        foreach ($files as $file) {
            $namaAsli = $file->getClientOriginalName();

            DB::beginTransaction();
            try {
                $storedPath = $file->store('laporan-excel');

                $import = new TagihanSusulanImport($namaAsli, $storedPath);
                Excel::import($import, $file);
                DB::commit();

                $laporanTerakhir = $import->laporan;
                $berhasil[] = [
                    'nama_file' => $namaAsli,
                    'laporan' => $import->laporan,
                ];

                // Kabarin user lain yang aktif (selain yang barusan upload) lewat notifikasi
                // di topbar. Ditaruh di luar transaksi DB supaya kalau gagal kirim notif,
                // data laporan yang sudah tersimpan tidak ikut ke-rollback.
                $penerima = User::where('id', '!=', auth()->id())->aktif()->get();
                if ($penerima->isNotEmpty()) {
                    Notification::send($penerima, new LaporanBaruDiupload($import->laporan, auth()->user()));
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $gagal[] = [
                    'nama_file' => $namaAsli,
                    'pesan' => $e->getMessage(),
                ];
            }
        }

        // ---- Hitung ulang ringkasan Gol Tarif untuk tiap tahun yang kena
        // dampak upload di request ini. Dedupe dulu (bisa saja beberapa file
        // punya tahun yang sama) supaya hitungUlang() tidak jalan berkali-kali
        // untuk tahun yang sama dalam satu request. Ditaruh di luar loop &
        // di luar transaksi per-file supaya tidak ikut ke-rollback kalau ada
        // file lain yang gagal setelahnya. ----
        $tahunTerdampak = collect($berhasil)
            ->pluck('laporan.tahun')
            ->filter()
            ->unique();

        foreach ($tahunTerdampak as $tahun) {
            $this->ringkasanGolTarifService->hitungUlang((int) $tahun);
        }

        return $this->redirectHasilUpload($berhasil, $gagal, $laporanTerakhir);
    }

    /**
     * Susun pesan hasil upload (berhasil/gagal) lalu redirect.
     * Kalau cuma 1 file dan berhasil, langsung ke halaman detail laporan seperti semula.
     * Kalau banyak file atau ada yang gagal, balik ke form upload dengan ringkasan.
     */
    private function redirectHasilUpload(array $berhasil, array $gagal, ?LaporanSusulan $laporanTerakhir)
    {
        if (count($berhasil) === 1 && count($gagal) === 0) {
            $laporan = $berhasil[0]['laporan'];
            $pesan = $laporan->versi > 1
                ? "File berhasil diimport sebagai versi {$laporan->versi} ({$laporan->jumlah_baris} baris). Versi sebelumnya otomatis dipindah ke riwayat."
                : "File berhasil diimport: {$laporan->jumlah_baris} baris data.";

            return redirect()
                ->route('laporan.show', $laporan->id)
                ->with('success', $pesan);
        }

        $ringkasanBerhasil = collect($berhasil)->map(function ($item) {
            $laporan = $item['laporan'];
            return "{$item['nama_file']} ({$laporan->jumlah_baris} baris, versi {$laporan->versi})";
        })->all();

        $ringkasanGagal = collect($gagal)->map(function ($item) {
            return "{$item['nama_file']}: {$item['pesan']}";
        })->all();

        $redirect = back();

        if (count($berhasil) > 0) {
            $pesanSukses = count($berhasil) . ' dari ' . (count($berhasil) + count($gagal)) . ' file berhasil diimport.';
            $redirect->with('success', $pesanSukses)->with('upload_berhasil', $ringkasanBerhasil);
        }

        if (count($gagal) > 0) {
            $redirect->withErrors(['file_excel' => count($gagal) . ' file gagal diproses.'])
                ->with('upload_gagal', $ringkasanGagal);
        }

        return $redirect;
    }

    public function index(Request $request)
    {
        $sort = $request->query('sort', 'terbaru');

        $laporans = LaporanSusulan::query()
            ->aktif()
            ->with('uploader:id,name')
            ->when($sort === 'terlama', fn ($q) => $q->oldest())
            ->when($sort !== 'terlama', fn ($q) => $q->latest())
            ->paginate(10);

        return view('laporan.index', compact('laporans'));
    }

    public function show(LaporanSusulan $laporan)
    {
        $perGol = $laporan->details()
            ->select('gol', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(total) as total_rp'))
            ->groupBy('gol')->orderByDesc('total_rp')->get();

        $perHari = $laporan->details()
            ->select('tanggal_register', DB::raw('SUM(total) as total_rp'), DB::raw('COUNT(*) as jumlah'))
            ->whereNotNull('tanggal_register')
            ->groupBy('tanggal_register')->orderBy('tanggal_register')->get();

        $top10 = $laporan->details()->orderByDesc('total')->limit(10)->get();

        $jumlahVersi = $laporan->semuaVersi()->count();

        return view('laporan.show', compact('laporan', 'perGol', 'perHari', 'top10', 'jumlahVersi'));
    }

    /**
     * Lihat semua versi (aktif + digantikan) untuk periode & unit yang sama.
     */
    public function riwayat(LaporanSusulan $laporan)
    {
        $versi = $laporan->semuaVersi();

        return view('laporan.riwayat', compact('laporan', 'versi'));
    }

    /**
     * Rollback: aktifkan kembali versi lama, versi yang sedang aktif dipindah jadi 'digantikan'.
     */
    public function aktifkan(LaporanSusulan $laporan)
    {
        DB::transaction(function () use ($laporan) {
            LaporanSusulan::aktif()
                ->where('unit_up3', $laporan->unit_up3)
                ->where('bulan', $laporan->bulan)
                ->where('tahun', $laporan->tahun)
                ->update(['status' => 'digantikan']);

            $laporan->update(['status' => 'aktif']);
        });

        // ---- Status "aktif" berpindah versi -> baris DetailTagihanSusulan yang
        // ikut dihitung RingkasanGolTarif berubah (query di service difilter
        // status='aktif'). Hitung ulang ringkasan tahun ini supaya konsisten. ----
        $this->ringkasanGolTarifService->hitungUlang((int) $laporan->tahun);

        return redirect()
            ->route('laporan.show', $laporan->id)
            ->with('success', "Versi {$laporan->versi} sekarang jadi versi aktif.");
    }

    public function destroy(LaporanSusulan $laporan)
    {
        $tahun = $laporan->tahun;

        $laporan->delete();

        // ---- Baris detail ikut terhapus (asumsi cascade / dihapus manual di
        // model event) -> ringkasan tahun ini harus dihitung ulang. ----
        $this->ringkasanGolTarifService->hitungUlang((int) $tahun);

        return redirect()->route('laporan.index')->with('success', 'Laporan dihapus.');
    }
}
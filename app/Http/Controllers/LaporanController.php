<?php

namespace App\Http\Controllers;

use App\Imports\TagihanSusulanImport;
use App\Models\LaporanSusulan;
use App\Models\User;
use App\Notifications\LaporanBaruDiupload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function create()
    {
        $lastUpload = LaporanSusulan::latest()->first();
        $berhasilHariIni = LaporanSusulan::whereDate('created_at', today())->count();

        return view('laporan.create', compact('lastUpload', 'berhasilHariIni'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xls,xlsx|max:20480',
        ]);

        $file = $request->file('file_excel');
        $storedPath = $file->store('laporan-excel');

        DB::beginTransaction();
        try {
            $import = new TagihanSusulanImport($file->getClientOriginalName(), $storedPath);
            Excel::import($import, $file);
            DB::commit();

            // Kabarin user lain yang aktif (selain yang barusan upload) lewat notifikasi
            // di topbar. Ditaruh di luar transaksi DB supaya kalau gagal kirim notif,
            // data laporan yang sudah tersimpan tidak ikut ke-rollback.
            $penerima = User::where('id', '!=', auth()->id())->aktif()->get();
            if ($penerima->isNotEmpty()) {
                Notification::send($penerima, new LaporanBaruDiupload($import->laporan, auth()->user()));
            }

            $pesan = $import->laporan->versi > 1
                ? "File berhasil diimport sebagai versi {$import->laporan->versi} ({$import->laporan->jumlah_baris} baris). Versi sebelumnya otomatis dipindah ke riwayat."
                : "File berhasil diimport: {$import->laporan->jumlah_baris} baris data.";

            return redirect()
                ->route('laporan.show', $import->laporan->id)
                ->with('success', $pesan);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['file_excel' => 'Gagal memproses file: ' . $e->getMessage()]);
        }
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

        return redirect()
            ->route('laporan.show', $laporan->id)
            ->with('success', "Versi {$laporan->versi} sekarang jadi versi aktif.");
    }

    public function destroy(LaporanSusulan $laporan)
    {
        $laporan->delete();
        return redirect()->route('laporan.index')->with('success', 'Laporan dihapus.');
    }
}
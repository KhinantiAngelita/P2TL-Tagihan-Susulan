<?php

namespace App\Http\Controllers;

use App\Imports\TagihanSusulanImport;
use App\Models\LaporanSusulan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function create()
    {
        $lastUpload = LaporanSusulan::latest()->first();

        // "Berhasil" dihitung beneran dari data yang masuk hari ini.
        // Gagal/Diproses/Antrian belum ada tracking-nya di skema saat ini
        // (butuh tabel log/queue job kalau mau real) — untuk sementara 0.
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

            $waktu = now()->translatedFormat('d/m/Y \p\u\k\u\l H:i');

            return redirect()
                ->route('laporan.show', $import->laporan->id)
                ->with('success', 'File berhasil diimport: ' . $import->laporan->jumlah_baris . ' baris data. Diupload pada ' . $waktu . '.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['file_excel' => 'Gagal memproses file: ' . $e->getMessage()]);
        }
    }

    public function index()
    {
        $laporans = LaporanSusulan::latest()->paginate(10);
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

        return view('laporan.show', compact('laporan', 'perGol', 'perHari', 'top10'));
    }

    public function destroy(LaporanSusulan $laporan)
    {
        $laporan->delete();
        return redirect()->route('laporan.index')->with('success', 'Laporan dihapus.');
    }
}
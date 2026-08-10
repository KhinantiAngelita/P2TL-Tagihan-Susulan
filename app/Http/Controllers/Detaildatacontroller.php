<?php

namespace App\Http\Controllers;

use App\Models\DetailTagihanSusulan;
use Illuminate\Http\Request;

class DetailDataController extends Controller
{
    public function index(Request $request)
    {
        $q   = trim((string) $request->query('q'));
        $gol = $request->query('gol');

        $query = DetailTagihanSusulan::with('laporan:id,bulan,tahun,unit_up3')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('idpel', 'like', "%{$q}%")
                        ->orWhere('nama', 'like', "%{$q}%")
                        ->orWhere('no_agenda', 'like', "%{$q}%");
                });
            })
            ->when($gol, fn ($query) => $query->where('gol', $gol))
            ->orderByDesc('tanggal_register');

        $details = $query->paginate(15)->withQueryString();

        $daftarGol = DetailTagihanSusulan::select('gol')
            ->whereNotNull('gol')->distinct()->orderBy('gol')->pluck('gol');

        return view('detail-data.index', compact('details', 'daftarGol', 'q', 'gol'));
    }
}
<?php

namespace App\Imports;

use App\Models\DetailTagihanSusulan;
use App\Models\LaporanSusulan;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class TagihanSusulanImport implements ToCollection, WithCalculatedFormulas
{
    protected string $originalName;
    protected string $storedPath;
    public LaporanSusulan $laporan;

    public function __construct(string $originalName, string $storedPath)
    {
        $this->originalName = $originalName;
        $this->storedPath = $storedPath;
    }

    /**
     * $rows adalah collection of collection, index kolom mulai dari 0 (A=0, B=1, dst)
     */
    public function collection(SupportCollection $rows): void
    {
        $unitInduk = trim((string) $rows->get(0)?->get(2));
        $unitUp3   = trim((string) $rows->get(1)?->get(2));
        $judul     = trim((string) $rows->get(2)?->get(5));

        $bulanTahunRaw = (string) $rows->get(3)?->get(6); // contoh: "BULAN  MEI    TAHUN  2026"
        $bulan = null; $tahun = null;
        if (preg_match('/BULAN\s+([A-Z]+)\s+TAHUN\s+(\d{4})/i', $bulanTahunRaw, $m)) {
            $bulan = strtoupper($m[1]);
            $tahun = $m[2];
        }

        // --- Cek apakah periode + unit yang sama sudah punya laporan aktif ---
        // Kalau ada, laporan lama ditandai 'digantikan' (tetap disimpan sebagai riwayat),
        // laporan baru jadi 'aktif' dengan nomor versi naik satu.
        $versiBaru = 1;
        if ($bulan && $tahun && $unitUp3) {
            $existingAktif = LaporanSusulan::aktif()
                ->where('unit_up3', $unitUp3)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();

            if ($existingAktif) {
                $versiBaru = $existingAktif->versi + 1;
                $existingAktif->update(['status' => 'digantikan']);
            }
        }

        $this->laporan = LaporanSusulan::create([
            'unit_induk'     => $unitInduk ?: null,
            'unit_up3'       => $unitUp3 ?: null,
            'judul_laporan'  => $judul ?: null,
            'bulan'          => $bulan,
            'tahun'          => $tahun,
            'nama_file_asli' => $this->originalName,
            'path_file'      => $this->storedPath,
            'status'         => 'aktif',
            'versi'          => $versiBaru,
        ]);

        $buffer = [];
        $jumlahBaris = 0;

        // Data dimulai baris index 9 (baris Excel ke-10)
        foreach ($rows->slice(9) as $row) {
            $col1 = trim((string) $row->get(1)); // NOAGENDA

            // Baris TOTAL = footer rekap, bukan data pelanggan
            if (strtoupper($col1) === 'TOTAL') {
                $this->laporan->update([
                    'total_ts'           => $this->num($row->get(15)),
                    'total_materai'      => $this->num($row->get(16)),
                    'total_segel'        => $this->num($row->get(19)),
                    'total_materia'      => $this->num($row->get(20)),
                    'total_rpppj'        => $this->num($row->get(22)),
                    'total_rpujl'        => $this->num($row->get(23)),
                    'total_rpppn'        => $this->num($row->get(24)),
                    'total_keseluruhan'  => $this->num($row->get(25)),
                    'total_tunai'        => $this->num($row->get(27)),
                    'total_angsuran'     => $this->num($row->get(28)),
                ]);
                break;
            }

            $no = $row->get(0);
            if ($no === null || $no === '' || !is_numeric($no)) {
                continue; // lewati baris kosong/pembatas
            }

            $buffer[] = [
                'laporan_susulan_id' => $this->laporan->id,
                'no'                 => (int) $no,
                'no_agenda'          => trim((string) $row->get(1)) ?: null,
                'idpel'              => trim((string) $row->get(3)) ?: null,
                'nama'               => trim((string) $row->get(4)) ?: null,
                'gol'                => trim((string) $row->get(7)) ?: null,
                'alamat'             => trim((string) $row->get(8)) ?: null,
                'daya'               => trim((string) $row->get(10)) ?: null,
                'kwh'                => $this->num($row->get(11)),
                'beban'              => $this->num($row->get(12)),
                'kwh_rupiah'         => $this->num($row->get(13)),
                'ts'                 => $this->num($row->get(15)),
                'materai'            => $this->num($row->get(16)),
                'segel'              => $this->num($row->get(19)),
                'materia'            => $this->num($row->get(20)),
                'rpppj'              => $this->num($row->get(22)),
                'rpujl'              => $this->num($row->get(23)),
                'rpppn'              => $this->num($row->get(24)),
                'total'              => $this->num($row->get(25)),
                'tunai'              => $this->num($row->get(27)),
                'angsuran'           => $this->num($row->get(28)),
                'tanggal_register'   => $this->parseDate($row->get(29)),
                'nomor_register'     => trim((string) $row->get(30)) ?: null,
                'tanggal_sph'        => $this->parseDate($row->get(31)),
                'nomor_sph'          => trim((string) $row->get(33)) ?: null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
            $jumlahBaris++;

            // Insert per 500 baris supaya hemat memori
            if (count($buffer) >= 500) {
                DetailTagihanSusulan::insert($buffer);
                $buffer = [];
            }
        }

        if (!empty($buffer)) {
            DetailTagihanSusulan::insert($buffer);
        }

        $this->laporan->update(['jumlah_baris' => $jumlahBaris]);
    }

    private function num($value): int|float
    {
        if ($value === null || $value === '') return 0;
        return is_numeric($value) ? $value : 0;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) return null;
        try {
            // format di excel: dd/mm/yyyy
            return \Carbon\Carbon::createFromFormat('d/m/Y', trim((string) $value))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
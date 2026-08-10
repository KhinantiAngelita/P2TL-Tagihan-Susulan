@extends('layouts.app')
@section('title', 'Riwayat Versi Laporan')
@section('content')
<div class="card">
    <h2 style="margin-top:0">Riwayat Versi Laporan</h2>
    <p style="color:#667">{{ $laporan->unit_up3 }} — {{ $laporan->bulan }} {{ $laporan->tahun }}</p>

    <table>
        <thead>
            <tr>
                <th>Versi</th>
                <th>Status</th>
                <th>File Asli</th>
                <th>Jumlah Baris</th>
                <th>Total (Rp)</th>
                <th>Diupload</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($versi as $v)
            <tr>
                <td>v{{ $v->versi }}</td>
                <td>
                    @if ($v->status === 'aktif')
                        <span class="badge badge-latest">Aktif</span>
                    @else
                        <span style="color:#888">Digantikan</span>
                    @endif
                </td>
                <td>{{ $v->nama_file_asli }}</td>
                <td>{{ $v->jumlah_baris }}</td>
                <td>Rp {{ number_format($v->total_keseluruhan, 0, ',', '.') }}</td>
                <td>{{ $v->created_at->translatedFormat('d M Y, H:i') }}</td>
                <td>
                    <a href="{{ route('laporan.show', $v->id) }}">Lihat</a>
                    @if ($v->status !== 'aktif')
                        |
                        <form action="{{ route('laporan.aktifkan', $v->id) }}" method="POST" style="display:inline"
                              onsubmit="return confirm('Jadikan v{{ $v->versi }} sebagai versi aktif? Versi aktif saat ini akan dipindah ke riwayat.')">
                            @csrf
                            <button type="submit" style="color:#0b3d91;background:none;border:none;cursor:pointer;">Aktifkan</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p style="margin-top:16px"><a href="{{ route('laporan.show', $laporan->id) }}">&larr; Kembali ke detail laporan</a></p>
</div>
@endsection
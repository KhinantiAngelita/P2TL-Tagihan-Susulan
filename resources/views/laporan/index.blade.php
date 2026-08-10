@extends('layouts.app')
@section('title', 'Data Detail')

@section('content')
<div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px;">
    <div>
        <h2 style="margin:0 0 4px;font-size:22px;">Data Detail</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">Seluruh baris data pelanggan dari semua laporan yang sudah diupload</p>
    </div>
</div>

<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
        <div>
            <h3 style="margin:0 0 2px;font-size:16px;">Semua Data Detail</h3>
            <p style="margin:0;font-size:12.5px;color:#6b7690;">
                Menampilkan {{ $details->firstItem() ?? 0 }}–{{ $details->lastItem() ?? 0 }} dari {{ $details->total() }} baris
            </p>
        </div>

        <form method="GET" action="{{ route('detail-data.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari IDPEL atau nama..."
                style="padding:9px 14px;border-radius:8px;border:1px solid #e7eaf3;font-size:13.5px;min-width:220px;">

            <select name="gol" onchange="this.form.submit()"
                style="padding:9px 14px;border-radius:8px;border:1px solid #e7eaf3;font-size:13.5px;font-weight:600;">
                <option value="">Semua Golongan</option>
                @foreach ($daftarGol as $g)
                    <option value="{{ $g }}" {{ $gol === $g ? 'selected' : '' }}>{{ $g }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn">Cari</button>
            @if ($q || $gol)
                <a href="{{ route('detail-data.index') }}" class="btn btn-outline">Reset</a>
            @endif
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>IDPEL</th><th>Nama</th><th>Gol</th><th>Alamat</th>
                    <th>Daya (VA)</th><th>Total</th><th>Tgl Register</th><th>Laporan</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($details as $d)
                <tr>
                    <td><strong>{{ $d->idpel }}</strong></td>
                    <td>{{ $d->nama }}</td>
                    <td><span class="badge">{{ $d->gol }}</span></td>
                    <td>{{ Str::limit($d->alamat, 30) }}</td>
                    <td>{{ $d->daya }}</td>
                    <td>Rp {{ number_format($d->total,0,',','.') }}</td>
                    <td>{{ $d->tanggal_register ? $d->tanggal_register->format('d/m/Y') : '-' }}</td>
                    <td>
                        @if ($d->laporan)
                            <a href="{{ route('laporan.show', $d->laporan->id) }}" style="color:#0b3d91;text-decoration:none;font-weight:600;">
                                {{ $d->laporan->bulan }} {{ $d->laporan->tahun }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#6b7690;">Tidak ada data yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">
        {{ $details->links() }}
    </div>
</div>
@endsection
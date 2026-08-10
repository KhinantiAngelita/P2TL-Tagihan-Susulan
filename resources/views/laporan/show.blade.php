@extends('layouts.app')
@section('title', 'Detail Laporan')
@section('content')
<div class="card">
    <h2>{{ $laporan->judul_laporan }}</h2>
    <p>{{ $laporan->unit_induk }} — {{ $laporan->unit_up3 }} — {{ $laporan->bulan }} {{ $laporan->tahun }}</p>
    <div class="grid">
        <div class="card stat"><h3>Total Keseluruhan</h3><p>Rp {{ number_format($laporan->total_keseluruhan,0,',','.') }}</p></div>
        <div class="card stat"><h3>Total Tunai</h3><p>Rp {{ number_format($laporan->total_tunai,0,',','.') }}</p></div>
        <div class="card stat"><h3>Total Angsuran</h3><p>Rp {{ number_format($laporan->total_angsuran,0,',','.') }}</p></div>
        <div class="card stat"><h3>Jumlah Baris</h3><p>{{ $laporan->jumlah_baris }}</p></div>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h3>Distribusi per Golongan Tarif</h3>
        <canvas id="chartGol"></canvas>
    </div>
    <div class="card">
        <h3>Tunai vs Angsuran</h3>
        <canvas id="chartBayar"></canvas>
    </div>
</div>

<div class="card">
    <h3>Tren Harian (Total Rp per Tanggal Register)</h3>
    <canvas id="chartHarian"></canvas>
</div>

<div class="card">
    <h3>Top 10 Tagihan Terbesar</h3>
    <table>
        <thead><tr><th>Nama</th><th>IDPEL</th><th>Gol</th><th>Total</th></tr></thead>
        <tbody>
        @foreach ($top10 as $d)
            <tr>
                <td>{{ $d->nama }}</td>
                <td>{{ $d->idpel }}</td>
                <td>{{ $d->gol }}</td>
                <td>Rp {{ number_format($d->total,0,',','.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
new Chart(document.getElementById('chartGol'), {
    type: 'bar',
    data: {
        labels: @json($perGol->pluck('gol')),
        datasets: [{ label: 'Total Rp', data: @json($perGol->pluck('total_rp')), backgroundColor: '#0b3d91' }]
    }
});

new Chart(document.getElementById('chartBayar'), {
    type: 'pie',
    data: {
        labels: ['Tunai', 'Angsuran'],
        datasets: [{ data: [{{ $laporan->total_tunai }}, {{ $laporan->total_angsuran }}], backgroundColor: ['#0b3d91', '#e0a800'] }]
    }
});

new Chart(document.getElementById('chartHarian'), {
    type: 'line',
    data: {
        labels: @json($perHari->pluck('tanggal_register')),
        datasets: [{ label: 'Total Rp per Hari', data: @json($perHari->pluck('total_rp')), borderColor: '#0b3d91', fill: false }]
    }
});
</script>
@endsection

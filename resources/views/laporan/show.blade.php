@extends('layouts.app')
@section('title', 'Detail Laporan')
@section('content')

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
            <h2 style="margin:0 0 4px">{{ $laporan->judul_laporan }}</h2>
            <p style="margin:0">{{ $laporan->unit_induk }} — {{ $laporan->unit_up3 }} — {{ $laporan->bulan }} {{ $laporan->tahun }}</p>
        </div>
        <div style="text-align:right">
            @if ($laporan->status === 'aktif')
                <span class="badge badge-latest">Versi Aktif (v{{ $laporan->versi }})</span>
            @else
                <span style="color:#888;font-size:13px">Versi Lama (v{{ $laporan->versi }}) — sudah digantikan</span>
            @endif
            @if ($jumlahVersi > 1)
                <div style="margin-top:6px">
                    <a href="{{ route('laporan.riwayat', $laporan->id) }}" style="font-size:13px">Lihat riwayat versi ({{ $jumlahVersi }})</a>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="dash-stats">
    <div class="dash-stat-card tone-yellow">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <h3>Total Keseluruhan</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($laporan->total_keseluruhan,0,',','.') }}</div>
        <div class="dash-stat-sub">Seluruh tagihan laporan ini</div>
    </div>

    <div class="dash-stat-card tone-blue">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <h3>Total Tunai</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($laporan->total_tunai,0,',','.') }}</div>
        <div class="dash-stat-sub">
            {{ $laporan->total_keseluruhan > 0 ? number_format($laporan->total_tunai / $laporan->total_keseluruhan * 100, 1) : 0 }}% dari total
        </div>
    </div>

    <div class="dash-stat-card tone-purple">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="6" rx="1"/><rect x="3" y="15" width="18" height="6" rx="1"/><rect x="3" y="3" width="18" height="2" rx="1"/></svg>
            </div>
            <h3>Total Angsuran</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($laporan->total_angsuran,0,',','.') }}</div>
        <div class="dash-stat-sub">
            {{ $laporan->total_keseluruhan > 0 ? number_format($laporan->total_angsuran / $laporan->total_keseluruhan * 100, 1) : 0 }}% dari total
        </div>
    </div>

    <div class="dash-stat-card tone-green">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h2"/></svg>
            </div>
            <h3>Jumlah Baris</h3>
        </div>
        <div class="dash-stat-value">{{ $laporan->jumlah_baris }}</div>
        <div class="dash-stat-sub">Baris data pelanggan</div>
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
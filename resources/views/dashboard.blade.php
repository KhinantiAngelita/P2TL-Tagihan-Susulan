@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px;">
    <div>
        <h2 style="margin:0 0 4px;font-size:22px;">Dashboard</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">Ringkasan seluruh laporan tagihan susulan</p>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:8px;">
        <select name="periode" onchange="this.form.submit()"
            style="padding:9px 14px;border-radius:8px;border:1px solid #e7eaf3;font-size:13.5px;color:#1b2559;background:#fff;font-weight:600;min-width:190px;">
            <option value="">Semua Bulan</option>
            @foreach ($periodeTersedia as $p)
                <option value="{{ $p->bulan }}|{{ $p->tahun }}"
                    {{ ($bulan === $p->bulan && $tahun == $p->tahun) ? 'selected' : '' }}>
                    {{ ucfirst(strtolower($p->bulan)) }} {{ $p->tahun }}
                </option>
            @endforeach
        </select>
        @if ($bulan || $tahun)
            <a href="{{ route('dashboard') }}" class="btn btn-outline">Reset</a>
        @endif
    </form>
</div>

<div class="grid">
    <div class="stat-card highlight">
        <div class="stat-top">
            <div class="stat-icon yellow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h2"/></svg>
            </div>
            <h3>Jumlah Laporan</h3>
        </div>
        <div class="stat-value">{{ $totalLaporan }}</div>
        <div class="stat-sub">Laporan aktif</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <h3>Total Pendapatan</h3>
        </div>
        <div class="stat-value">Rp {{ number_format($totalPendapatan,0,',','.') }}</div>
        <div class="stat-sub">Rp</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <h3>Total Tunai</h3>
        </div>
        <div class="stat-value">Rp {{ number_format($totalTunai,0,',','.') }}</div>
        <div class="stat-sub">
            {{ $totalPendapatan > 0 ? number_format($totalTunai / $totalPendapatan * 100, 1) : 0 }}% dari total
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <div class="stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="6" rx="1"/><rect x="3" y="15" width="18" height="6" rx="1"/><rect x="3" y="3" width="18" height="2" rx="1"/></svg>
            </div>
            <h3>Total Angsuran</h3>
        </div>
        <div class="stat-value">Rp {{ number_format($totalAngsuran,0,',','.') }}</div>
        <div class="stat-sub">
            {{ $totalPendapatan > 0 ? number_format($totalAngsuran / $totalPendapatan * 100, 1) : 0 }}% dari total
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="margin:0 0 2px;font-size:16px;">Distribusi per Golongan Tarif</h3>
        <p style="margin:0 0 16px;font-size:12.5px;color:#6b7690;">Total tagihan per golongan (Rp) — semua laporan</p>
        <canvas id="chartGolAll" height="160"></canvas>
    </div>
    <div class="card">
        <h3 style="margin:0 0 2px;font-size:16px;">Tunai vs Angsuran</h3>
        <p style="margin:0 0 16px;font-size:12.5px;color:#6b7690;">Proporsi pembayaran — semua laporan</p>
        <canvas id="chartBayarAll" height="160"></canvas>
    </div>
</div>

<div class="card">
    <h3 style="margin:0 0 2px;font-size:16px;">Tren Pendapatan per Bulan</h3>
    <p style="margin:0 0 16px;font-size:12.5px;color:#6b7690;">Total keseluruhan (Rp) tiap periode bulan/tahun laporan</p>
    <canvas id="chartTrenBulanan" height="90"></canvas>
</div>

<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h3 style="margin:0 0 2px;font-size:16px;">Ringkasan Data Detail</h3>
            <p style="margin:0;font-size:12.5px;color:#6b7690;">8 baris data pelanggan terbaru (isi Excel)</p>
        </div>
        <a href="{{ route('detail-data.index') }}" class="btn btn-outline">Lihat Data Lengkap</a>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr><th>IDPEL</th><th>Nama</th><th>Gol</th><th>Daya (VA)</th><th>Total</th><th>Tgl Register</th></tr>
            </thead>
            <tbody>
            @forelse ($detailPreview as $d)
                <tr>
                    <td><strong>{{ $d->idpel }}</strong></td>
                    <td>{{ $d->nama }}</td>
                    <td><span class="badge">{{ $d->gol }}</span></td>
                    <td>{{ $d->daya }}</td>
                    <td>Rp {{ number_format($d->total,0,',','.') }}</td>
                    <td>{{ $d->tanggal_register ? $d->tanggal_register->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#6b7690;">Belum ada data detail.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <h3 style="margin:0;font-size:16px;">Laporan Terbaru</h3>
        <a href="{{ route('laporan.index') }}" class="btn btn-outline">Lihat Semua</a>
    </div>
    <table>
        <thead>
            <tr><th>Unit UP3</th><th>Bulan/Tahun</th><th>Baris</th><th>Total</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        @forelse ($laporanTerbaru as $l)
            <tr>
                <td>{{ $l->unit_up3 }}</td>
                <td>{{ $l->bulan }} {{ $l->tahun }}</td>
                <td>{{ $l->jumlah_baris }}</td>
                <td>Rp {{ number_format($l->total_keseluruhan,0,',','.') }}</td>
                <td><a href="{{ route('laporan.show', $l->id) }}" class="badge">Lihat</a></td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#6b7690;">Belum ada laporan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
new Chart(document.getElementById('chartGolAll'), {
    type: 'bar',
    data: {
        labels: @json($perGol->pluck('gol')),
        datasets: [{
            label: 'Total Rp',
            data: @json($perGol->pluck('total_rp')),
            backgroundColor: ['#ffc700','#0b3d91','#1a3a7a','#3f6fd1'],
            borderRadius: 6,
        }]
    },
    options: { plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chartBayarAll'), {
    type: 'doughnut',
    data: {
        labels: ['Tunai', 'Angsuran'],
        datasets: [{
            data: [{{ $totalTunai }}, {{ $totalAngsuran }}],
            backgroundColor: ['#0b3d91', '#ffc700'],
        }]
    },
    options: { cutout: '65%' }
});

new Chart(document.getElementById('chartTrenBulanan'), {
    type: 'line',
    data: {
        labels: @json($perBulan->map(fn($b) => $b->bulan . ' ' . $b->tahun)),
        datasets: [{
            label: 'Total Rp',
            data: @json($perBulan->pluck('total_rp')),
            borderColor: '#023e8a',
            backgroundColor: 'rgba(2,62,138,0.08)',
            fill: true,
            tension: 0.35,
            pointBackgroundColor: '#ffc700',
            pointRadius: 4,
        }]
    },
    options: { plugins: { legend: { display: false } } }
});
</script>
@endpush
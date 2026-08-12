@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<style>
    .dash-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 22px; }
    .dash-header h2 { margin: 0 0 4px; font-size: 22px; }
    .dash-header p { margin: 0; color: #6b7690; font-size: 14px; }
    .dash-period-badge {
        display: inline-flex; align-items: center; gap: 6px; margin-top: 8px;
        background: #eaf1ff; color: #0b3d91; font-size: 12px; font-weight: 700;
        padding: 4px 12px; border-radius: 20px;
    }
    .dash-filter-form { display: flex; align-items: center; gap: 8px; }
    .dash-filter-form select {
        padding: 9px 14px; border-radius: 8px; border: 1px solid #e7eaf3;
        font-size: 13.5px; color: #1b2559; background: #fff; font-weight: 600; min-width: 190px;
    }

    /* ---------- Stat cards — desain lokal, gak pakai .stat-card.highlight bawaan layout ---------- */
    .dash-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .dash-stat-card {
        position: relative;
        background: #fff; border-radius: 14px; padding: 20px 22px;
        border: 1px solid #e7eaf3;
        box-shadow: 0 1px 4px rgba(20,30,80,.05);
        overflow: hidden;
        transition: box-shadow .18s, transform .18s;
    }
    .dash-stat-card:hover { box-shadow: 0 8px 20px rgba(20,30,80,.09); transform: translateY(-2px); }
    .dash-stat-card::before {
        content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    }
    .dash-stat-card.tone-yellow::before { background: #ffc700; }
    .dash-stat-card.tone-blue::before { background: #0b3d91; }
    .dash-stat-card.tone-green::before { background: #1a9c4a; }
    .dash-stat-card.tone-purple::before { background: #7c4dff; }

    .dash-stat-top { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .dash-stat-icon { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .dash-stat-icon svg { width: 19px; height: 19px; }
    .tone-yellow .dash-stat-icon { background: #fff6da; color: #b98600; }
    .tone-blue .dash-stat-icon { background: #eaf1ff; color: #0b3d91; }
    .tone-green .dash-stat-icon { background: #e6f7ea; color: #1a9c4a; }
    .tone-purple .dash-stat-icon { background: #f1ecff; color: #7c4dff; }
    .dash-stat-top h3 { margin: 0; font-size: 13px; color: #6b7690; font-weight: 700; }

    .dash-stat-value { font-size: 25px; font-weight: 800; color: #1b2559; letter-spacing: -.2px; }
    .dash-stat-sub { display: flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12px; color: #8a93ad; }
    .dash-stat-sub svg { width: 13px; height: 13px; flex-shrink: 0; }

    .dash-mini-insight {
        display: flex; align-items: center; gap: 10px;
        background: #f7f9fd; border: 1px solid #e7eaf3; border-radius: 10px;
        padding: 10px 14px; margin-bottom: 18px; font-size: 13px; color: #4b5570;
    }
    .dash-mini-insight strong { color: #1b2559; }
    .dash-mini-insight .dash-mini-icon {
        width: 30px; height: 30px; border-radius: 8px; background: #fff6da; color: #b98600;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .dash-mini-icon svg { width: 15px; height: 15px; }

    .gol-badge-1 { background: #fff3cd; color: #8a6300; }
    .gol-badge-2 { background: #eaf1ff; color: #0b3d91; }
    .gol-badge-3 { background: #e6f7ea; color: #17643a; }
    .gol-badge-4 { background: #f1ecff; color: #5b3fc9; }

    .table-empty-state { text-align: center; padding: 26px 0; color: #9aa4c2; font-size: 13.5px; }
    .table-empty-state svg { width: 26px; height: 26px; margin-bottom: 8px; opacity: .6; }

    .um-view-btn {
        width: 26px; height: 26px; border-radius: 7px; background: #eaf1ff;
        display: inline-flex; align-items: center; justify-content: center;
        color: #0b3d91; text-decoration: none;
    }
    .um-view-btn svg { width: 13px; height: 13px; }
</style>

<div class="dash-header">
    <div>
        <h2>Dashboard</h2>
        <p>Ringkasan seluruh laporan tagihan susulan</p>
        @if ($bulan && $tahun)
            <div class="dash-period-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Menampilkan periode {{ ucfirst(strtolower($bulan)) }} {{ $tahun }}
            </div>
        @endif
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="dash-filter-form">
        <select name="periode" onchange="this.form.submit()">
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

<div class="dash-stats">
    <div class="dash-stat-card tone-yellow">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h2"/></svg>
            </div>
            <h3>Jumlah Laporan</h3>
        </div>
        <div class="dash-stat-value">{{ $totalLaporan }}</div>
        <div class="dash-stat-sub">Laporan aktif</div>
    </div>

    <div class="dash-stat-card tone-blue">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            </div>
            <h3>Total Pendapatan</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($totalPendapatan,0,',','.') }}</div>
        <div class="dash-stat-sub">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/></svg>
            Rp {{ $totalLaporan > 0 ? number_format($totalPendapatan / $totalLaporan,0,',','.') : 0 }} rata-rata / laporan
        </div>
    </div>

    <div class="dash-stat-card tone-green">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <h3>Total Tunai</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($totalTunai,0,',','.') }}</div>
        <div class="dash-stat-sub">{{ $totalPendapatan > 0 ? number_format($totalTunai / $totalPendapatan * 100, 1) : 0 }}% dari total</div>
    </div>

    <div class="dash-stat-card tone-purple">
        <div class="dash-stat-top">
            <div class="dash-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="6" rx="1"/><rect x="3" y="15" width="18" height="6" rx="1"/><rect x="3" y="3" width="18" height="2" rx="1"/></svg>
            </div>
            <h3>Total Angsuran</h3>
        </div>
        <div class="dash-stat-value">Rp {{ number_format($totalAngsuran,0,',','.') }}</div>
        <div class="dash-stat-sub">{{ $totalPendapatan > 0 ? number_format($totalAngsuran / $totalPendapatan * 100, 1) : 0 }}% dari total</div>
    </div>
</div>

@if ($perGol->isNotEmpty())
    @php $golTerbesar = $perGol->sortByDesc('total_rp')->first(); @endphp
    <div class="dash-mini-insight">
        <div class="dash-mini-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v6c0 5 4 8.5 9 9 5-.5 9-4 9-9V7l-9-5Z"/></svg>
        </div>
        <span>
            Golongan tarif dengan kontribusi terbesar: <strong>{{ $golTerbesar->gol }}</strong>
            (Rp {{ number_format($golTerbesar->total_rp,0,',','.') }},
            {{ $totalPendapatan > 0 ? number_format($golTerbesar->total_rp / $totalPendapatan * 100, 1) : 0 }}% dari total pendapatan)
        </span>
    </div>
@endif

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
    <h3 style="margin:0 0 2px;font-size:16px;">
        {{ $trenMode === 'harian' ? 'Tren Pendapatan Harian' : 'Tren Pendapatan per Bulan' }}
    </h3>
    <p style="margin:0 0 16px;font-size:12.5px;color:#6b7690;">
        @if ($trenMode === 'harian')
            Total tagihan (Rp) per hari (berdasarkan tanggal register) — {{ ucfirst(strtolower($bulan)) }} {{ $tahun }}
        @else
            Total keseluruhan (Rp) tiap periode bulan/tahun laporan — pilih 1 periode di filter atas buat lihat rincian per hari
        @endif
    </p>
    <canvas id="chartTrenBulanan" height="90"></canvas>
</div>

<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
            <h3 style="margin:0 0 2px;font-size:16px;">Ringkasan Data Detail</h3>
            <p style="margin:0;font-size:12.5px;color:#6b7690;">
                8 baris data pelanggan terbaru
                @if ($bulan && $tahun)
                    &mdash; {{ ucfirst(strtolower($bulan)) }} {{ $tahun }}
                @else
                    (semua periode)
                @endif
            </p>
        </div>
        <a href="{{ route('detail-data.index') }}" class="btn btn-outline">Lihat Data Lengkap</a>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr><th>No</th><th>ULP</th><th>IDPEL</th><th>Nama</th><th>Gol</th><th>Tarif</th><th>Daya (VA)</th><th>KWH</th><th>TS</th></tr>
            </thead>
            <tbody>
            @forelse ($detailPreview as $d)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong style="color:#0b3d91">{{ $d->ulp }}</strong></td>
                    <td><strong>{{ $d->idpel }}</strong></td>
                    <td>{{ $d->nama }}</td>
                    <td><span class="badge gol-badge-{{ (crc32($d->gol) % 4) + 1 }}">{{ $d->gol }}</span></td>
                    <td>{{ $d->tarif }}</td>
                    <td>{{ $d->daya_va }}</td>
                    <td>{{ number_format($d->kwh,0,',','.') }}</td>
                    <td>Rp {{ number_format($d->ts,0,',','.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="table-empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/></svg>
                            <div>Belum ada data detail.</div>
                        </div>
                    </td>
                </tr>
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
                <td>
                    <a href="{{ route('laporan.show', $l->id) }}" class="um-view-btn" title="Lihat Detail">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <div class="table-empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/></svg>
                        <div>Belum ada laporan.</div>
                    </div>
                </td>
            </tr>
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
        labels: @json($trenLabels),
        datasets: [{
            label: {!! json_encode($trenMode === 'harian' ? 'Total Rp per Hari' : 'Total Rp per Bulan') !!},
            data: @json($trenData),
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
@extends('layouts.app')
@section('title', 'Daftar Laporan')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Daftar Laporan</strong>
@endsection

@push('styles')
<style>
    .lap-row {
        display: grid;
        grid-template-columns: 44px 1.6fr 1fr auto;
        gap: 16px;
        align-items: center;
        padding: 16px 22px;
        border-bottom: 1px solid var(--border);
        transition: background .12s;
    }
    .lap-row:last-child { border-bottom: none; }
    .lap-row:hover { background: #f9fafd; }

    .lap-avatar {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 13px; flex-shrink: 0;
    }

    .lap-meta { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; font-size: 12.5px; color: var(--text-muted); margin-top: 4px; }
    .lap-meta .dot { width: 3px; height: 3px; border-radius: 50%; background: #c2c9de; flex-shrink: 0; }

    .file-highlight {
        display: inline-flex; align-items: center; gap: 5px;
        background: #eef1f8; color: #3d4566;
        font-size: 11.5px; font-weight: 600;
        padding: 3px 9px; border-radius: 6px;
    }

    .date-highlight {
        display: inline-flex; align-items: center; gap: 5px;
        background: #fff6da; color: #8a6600;
        font-size: 12.5px; font-weight: 700;
        padding: 4px 10px; border-radius: 7px;
        white-space: nowrap;
    }

    .lap-actions { display: flex; gap: 6px; }
    .lap-actions .icon-btn {
        width: 34px; height: 34px; border-radius: 9px; border: 1px solid var(--border);
        background: #fff; display: flex; align-items: center; justify-content: center;
        color: var(--text-muted); cursor: pointer; transition: all .12s;
    }
    .lap-actions .icon-btn svg { width: 15px; height: 15px; }
    .lap-actions .icon-btn:hover { border-color: var(--blue-primary); color: var(--blue-primary); background: #eaf1ff; }
    .lap-actions .icon-btn.danger:hover { border-color: #e0433d; color: #e0433d; background: #fdecea; }

    .version-pill {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 11.5px; font-weight: 700; padding: 3px 9px; border-radius: 20px;
        background: #fff6da; color: #a67600;
    }

    .sort-toggle { display: inline-flex; background: #f0f2f8; border-radius: 9px; padding: 3px; gap: 2px; }
    .sort-toggle a { padding: 6px 14px; font-size: 12.5px; font-weight: 700; border-radius: 7px; text-decoration: none; color: var(--text-muted); }
    .sort-toggle a.active { background: #fff; color: var(--blue-primary); box-shadow: 0 1px 3px rgba(20,30,80,.12); }

    @media (max-width: 760px) {
        .lap-row { grid-template-columns: 40px 1fr; }
        .lap-row .lap-col-total, .lap-row .lap-col-date { grid-column: 2; }
        .lap-actions { grid-column: 2; justify-content: flex-start; margin-top: 8px; }
    }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="margin:0 0 4px;font-size:22px;">Daftar Laporan</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">Semua laporan susulan yang sudah diupload dan berstatus aktif.</p>
    </div>
    <a href="{{ route('laporan.create') }}" class="btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M12 16V4M12 4l-4 4M12 4l4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
        Upload Baru
    </a>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <span style="font-size:13px;color:#6b7690;">
        Menampilkan <strong style="color:#1b2559;">{{ $laporans->count() }}</strong> dari
        <strong style="color:#1b2559;">{{ $laporans->total() }}</strong> laporan aktif
    </span>

    <div class="sort-toggle">
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'terbaru']) }}"
           class="{{ request()->query('sort', 'terbaru') === 'terbaru' ? 'active' : '' }}">Terbaru</a>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'terlama']) }}"
           class="{{ request()->query('sort') === 'terlama' ? 'active' : '' }}">Terlama</a>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    @if ($laporans->isEmpty())
        <div style="padding:64px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:14px;background:#eaf1ff;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#0b3d91" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h2"/></svg>
            </div>
            <strong style="display:block;font-size:15px;color:#1b2559;margin-bottom:4px;">Belum ada laporan</strong>
            <span style="font-size:13px;color:#6b7690;">Upload file Excel pertama kamu untuk mulai mengisi daftar ini.</span>
            <div style="margin-top:16px;">
                <a href="{{ route('laporan.create') }}" class="btn">Upload Sekarang</a>
            </div>
        </div>
    @else
        @php
            // Palet warna avatar unit — dipilih deterministik dari nama unit biar konsisten tiap render
            $avatarPalette = [
                ['bg' => '#eaf1ff', 'fg' => '#0b3d91'],
                ['bg' => '#fff6da', 'fg' => '#a67600'],
                ['bg' => '#e6f7ea', 'fg' => '#17803c'],
                ['bg' => '#f1ecff', 'fg' => '#6b3fd4'],
                ['bg' => '#fdecea', 'fg' => '#c23a2f'],
            ];
        @endphp

        @foreach ($laporans as $laporan)
            @php
                $label = $laporan->unit_up3 ?: $laporan->unit_induk ?: '?';
                $color = $avatarPalette[crc32($label) % count($avatarPalette)];
                // Dipaksa ke WIB di sini sebagai jaga-jaga kalau config/app.php
                // 'timezone' belum diset ke Asia/Jakarta. Kalau config sudah benar,
                // baris setTimezone() ini tetap aman (no-op karena sudah WIB).
                $waktuUpload = $laporan->created_at->copy()->setTimezone('Asia/Jakarta');
            @endphp
            <div class="lap-row">
                <div class="lap-avatar" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }};">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:19px;height:19px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                </div>

                <div style="min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <strong style="font-size:14.5px;color:#1b2559;">{{ $laporan->judul_laporan ?: 'Laporan Susulan' }}</strong>
                        @if ($laporan->versi > 1)
                            <span class="version-pill">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                                Versi {{ $laporan->versi }}
                            </span>
                        @endif
                    </div>
                    <div class="lap-meta">
                        <span>{{ $label }}</span>
                        <span class="dot"></span>
                        <span>{{ $laporan->bulan ?? '-' }} {{ $laporan->tahun ?? '' }}</span>
                        <span class="dot"></span>
                        <span class="file-highlight">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                            {{ $laporan->nama_file_asli }}
                        </span>
                        <span class="dot"></span>
                        <span>Diupload oleh {{ $laporan->uploader?->name ?? 'Tidak diketahui' }}</span>
                    </div>
                </div>

                <div class="lap-col-total" style="text-align:right;">
                    <strong style="display:block;font-size:14.5px;color:#1b2559;">Rp {{ number_format($laporan->total_keseluruhan, 0, ',', '.') }}</strong>
                    <span style="font-size:12px;color:#6b7690;">{{ number_format($laporan->jumlah_baris, 0, ',', '.') }} baris</span>
                </div>

                <div style="display:flex;align-items:center;gap:18px;">
                    <div class="lap-col-date" style="text-align:right;">
                        <span class="date-highlight">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                            {{ $waktuUpload->translatedFormat('d M Y') }}, {{ $waktuUpload->format('H:i') }} WIB
                        </span>
                        <span style="display:block;font-size:11px;color:#9aa4c2;margin-top:4px;">{{ $waktuUpload->diffForHumans() }}</span>
                    </div>

                    <div class="lap-actions">
                        <a href="{{ route('laporan.show', $laporan) }}" class="icon-btn" title="Lihat Detail">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <a href="#" class="icon-btn" title="Edit (belum tersedia)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        </a>
                        <form action="{{ route('laporan.destroy', $laporan) }}" method="POST"
                              onsubmit="return confirm('Yakin hapus laporan {{ $laporan->judul_laporan ?? $laporan->nama_file_asli }}? Data detail terkait juga akan terhapus.');"
                              style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-btn danger" title="Hapus">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach

        @if ($laporans->hasPages())
            <div style="padding:16px 22px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:12.5px;color:#6b7690;">
                    Halaman {{ $laporans->currentPage() }} dari {{ $laporans->lastPage() }}
                </span>
                <div style="display:flex;gap:6px;">
                    @if ($laporans->onFirstPage())
                        <span class="btn btn-outline" style="padding:7px 14px;font-size:12.5px;opacity:.5;">‹ Sebelumnya</span>
                    @else
                        <a href="{{ $laporans->previousPageUrl() }}" class="btn btn-outline" style="padding:7px 14px;font-size:12.5px;">‹ Sebelumnya</a>
                    @endif

                    @if ($laporans->hasMorePages())
                        <a href="{{ $laporans->nextPageUrl() }}" class="btn btn-outline" style="padding:7px 14px;font-size:12.5px;">Selanjutnya ›</a>
                    @else
                        <span class="btn btn-outline" style="padding:7px 14px;font-size:12.5px;opacity:.5;">Selanjutnya ›</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
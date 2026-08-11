@extends('layouts.app')
@section('title', 'Detail Data Pelanggan')

@push('styles')
<style>
    .modal-overlay-page {
        max-width: 760px;
        margin: 24px auto;
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(20,30,80,.15);
        border: 1px solid var(--border);
    }

    .modal-header {
        background: linear-gradient(120deg, #0a1f4d 0%, #1454c9 100%);
        padding: 24px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #fff;
    }
    .modal-header .head-left { display: flex; align-items: center; gap: 14px; }
    .modal-header .head-icon {
        width: 46px; height: 46px; border-radius: 13px;
        background: rgba(255,255,255,.12);
        display: flex; align-items: center; justify-content: center;
        color: #ffce3a; flex-shrink: 0;
    }
    .modal-header .head-icon svg { width: 20px; height: 20px; }
    .modal-header h3 { margin: 0; font-size: 18px; }
    .modal-header .head-sub { margin: 3px 0 0; font-size: 12.5px; color: #c7d3f2; }
    .modal-header .head-sub strong { color: #fff; }
    .modal-header .close-btn {
        width: 34px; height: 34px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #d7deef; background: rgba(255,255,255,.08); flex-shrink: 0;
    }
    .modal-header .close-btn:hover { background: rgba(255,255,255,.18); color: #fff; }

    .modal-body { padding: 26px 28px; max-height: 70vh; overflow-y: auto; }

    .field-section-title {
        font-size: 12.5px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        color: #8a93b8; margin: 26px 0 12px;
    }
    .field-section-title:first-child { margin-top: 0; }

    .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 20px; }
    .field-grid .full { grid-column: 1 / -1; }

    .field-label { display: block; font-size: 13px; font-weight: 700; color: #1b2559; margin-bottom: 7px; }

    .value-icon-wrap {
        position: relative;
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #f6f7fb;
        font-size: 14px;
        color: #1b2559;
    }
    .value-icon-wrap svg { width: 16px; height: 16px; color: #9aa4c2; flex-shrink: 0; }
    .value-icon-wrap span.val { color: #1b2559; font-weight: 600; }
    .value-icon-wrap span.empty { color: #b3bad2; font-weight: 400; }

    .total-box {
        margin-top: 24px;
        display: flex; align-items: center; justify-content: space-between;
        border: 1.5px dashed #c3cbe6; border-radius: 14px;
        padding: 16px 20px; background: #f8f9fd;
    }
    .total-box .total-left { display: flex; align-items: center; gap: 12px; }
    .total-box .total-left svg { width: 20px; height: 20px; color: var(--blue-primary); }
    .total-box .total-label { font-size: 13.5px; font-weight: 700; color: #1b2559; margin: 0; }
    .total-box .total-caption { font-size: 11.5px; color: #9aa4c2; margin: 2px 0 0; }
    .total-box .total-value { font-size: 20px; font-weight: 800; color: #1b2559; }

    .modal-footer {
        padding: 18px 28px; border-top: 1px solid var(--border);
        display: flex; justify-content: flex-end; gap: 10px;
        background: #fafbfd;
    }
</style>
@endpush

@php
    $fmtRp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $val = fn ($v) => $v !== null && $v !== '' ? $v : null;
@endphp

@section('content')
<div class="modal-overlay-page">
    <div class="modal-header">
        <div class="head-left">
            <div class="head-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <div>
                <h3>Detail Data Pelanggan</h3>
                <p class="head-sub">No. Agenda: <strong>{{ $detail->no_agenda ?? '-' }}</strong></p>
            </div>
        </div>
        <a href="{{ route('detail-data.show', $detail->laporan_susulan_id) }}" class="close-btn" title="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </a>
    </div>

    <div class="modal-body">

        <div class="field-section-title">Data Pelanggan</div>
        <div class="field-grid">
            <div>
                <p class="field-label">No Agenda</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>
                    <span class="{{ $val($detail->no_agenda) ? 'val' : 'empty' }}">{{ $val($detail->no_agenda) ?? '-' }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">IDPEL</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h5"/></svg>
                    <span class="val">{{ $detail->idpel }}</span>
                </div>
            </div>
            <div class="full">
                <p class="field-label">Nama Pelanggan</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                    <span class="val">{{ $detail->nama }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Golongan</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                    <span class="{{ $val($detail->gol) ? 'val' : 'empty' }}">{{ $val($detail->gol) ?? '-' }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Daya (VA)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z"/></svg>
                    <span class="{{ $val($detail->daya) ? 'val' : 'empty' }}">{{ $val($detail->daya) ?? '-' }}</span>
                </div>
            </div>
            <div class="full">
                <p class="field-label">Alamat</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/></svg>
                    <span class="{{ $val($detail->alamat) ? 'val' : 'empty' }}">{{ $val($detail->alamat) ?? '-' }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">KWH</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    <span class="val">{{ number_format((float) $detail->kwh, 2, ',', '.') }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Beban (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg>
                    <span class="val">{{ $fmtRp($detail->beban) }}</span>
                </div>
            </div>
        </div>

        <div class="field-section-title">Rincian Biaya</div>
        <div class="field-grid">
            <div>
                <p class="field-label">TS (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    <span class="val">{{ $fmtRp($detail->ts) }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Materai (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/></svg>
                    <span class="val">{{ $fmtRp($detail->materai) }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Segel (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6l-8-4Z"/></svg>
                    <span class="val">{{ $fmtRp($detail->segel) }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Materia (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                    <span class="val">{{ $fmtRp($detail->materia) }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">PPJ (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M3 12h18"/></svg>
                    <span class="val">{{ $fmtRp($detail->rpppj) }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">UJL (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M3 12h18"/></svg>
                    <span class="val">{{ $fmtRp($detail->rpujl) }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">PPN (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M3 12h18"/></svg>
                    <span class="val">{{ $fmtRp($detail->rpppn) }}</span>
                </div>
            </div>
        </div>

        <div class="field-section-title">Pembayaran</div>
        <div class="field-grid">
            <div>
                <p class="field-label">Tunai (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    <span class="val">{{ $fmtRp($detail->tunai) }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Angsuran (Rp)</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/></svg>
                    <span class="val">{{ $fmtRp($detail->angsuran) }}</span>
                </div>
            </div>
        </div>

        <div class="field-section-title">Registrasi &amp; SPH</div>
        <div class="field-grid">
            <div>
                <p class="field-label">Tanggal Register</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span class="{{ $detail->tanggal_register ? 'val' : 'empty' }}">{{ optional($detail->tanggal_register)->format('d/m/Y') ?? '-' }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Nomor Register</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    <span class="{{ $val($detail->nomor_register) ? 'val' : 'empty' }}">{{ $val($detail->nomor_register) ?? '-' }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Tanggal SPH</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span class="{{ $detail->tanggal_sph ? 'val' : 'empty' }}">{{ optional($detail->tanggal_sph)->format('d/m/Y') ?? '-' }}</span>
                </div>
            </div>
            <div>
                <p class="field-label">Nomor SPH</p>
                <div class="value-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    <span class="{{ $val($detail->nomor_sph) ? 'val' : 'empty' }}">{{ $val($detail->nomor_sph) ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="total-box">
            <div class="total-left">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
                <div>
                    <p class="total-label">Total (Tunai + Angsuran)</p>
                    <p class="total-caption">Tersimpan di database</p>
                </div>
            </div>
            <div class="total-value">{{ $fmtRp($detail->total) }}</div>
        </div>

    </div>

    <div class="modal-footer">
        <a href="{{ route('detail-data.show', $detail->laporan_susulan_id) }}" class="btn btn-outline">Tutup</a>
        <a href="{{ route('detail-data.edit', $detail->id) }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            Edit Data
        </a>
    </div>
</div>
@endsection
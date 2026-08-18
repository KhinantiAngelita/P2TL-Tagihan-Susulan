@extends('layouts.app')
@section('title', 'Edit Target')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Edit Target</strong>
@endsection

@push('styles')
<style>
    .target-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 22px;
        border-bottom: 1px solid var(--border);
    }
    .target-tab {
        padding: 10px 18px;
        font-size: 13.5px;
        font-weight: 600;
        text-decoration: none;
        color: #6b7690;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        transition: .15s;
    }
    .target-tab.active {
        color: var(--blue-primary);
        border-bottom-color: var(--blue-primary);
    }
    .target-tab:hover:not(.active) {
        color: #1b2559;
    }

    .filter-wrap { position: relative; }
    .filter-wrap svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; color: #9aa4c2; pointer-events: none;
    }
    .filter-select {
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 8px 12px 8px 32px;
        font-size: 13px;
        background: #fff;
        appearance: none;
        min-width: 170px;
        cursor: pointer;
    }

    .target-info-bar {
        font-size: 12.5px;
        color: #6b7690;
        background: #f8f9fc;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 16px;
        margin-bottom: 22px;
    }
    .target-info-bar strong { color: #1b2559; }

    .target-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    @media (max-width: 900px) {
        .target-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 560px) {
        .target-grid { grid-template-columns: 1fr; }
    }
    .target-field label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #3d4566;
        margin-bottom: 6px;
    }
    .target-field input {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 12px;
        border-radius: 9px;
        border: 1px solid #ddd;
        background: #f8f9fb;
        font-size: 13.5px;
        color: #1b2559;
    }
    .target-field input:focus {
        outline: none;
        border-color: var(--blue-primary);
        background: #fff;
    }
</style>
@endpush

@section('content')

<div style="margin-bottom:22px;">
    <h2 style="margin:0 0 4px;font-size:22px;">Edit Target</h2>
    <p style="color:#6b7690;margin:0;font-size:14px;">
        Input manual nilai target per bulan &mdash; ditampilkan di halaman Trend
    </p>
</div>

@if (session('success'))
    <div style="margin-bottom:16px;border-radius:10px;background:#e6f7ea;border:1px solid #b9e6c4;color:#17803c;font-size:13.5px;padding:12px 16px;">
        {{ session('success') }}
    </div>
@endif

<div class="card" style="padding:24px 26px;">

    {{-- Tab jenis target --}}
    <div class="target-tabs">
        @foreach ($jenisOptions as $key => $label)
            <a href="{{ route('edit-target.index', ['tahun' => $tahun, 'jenis' => $key, 'ulp' => $ulpAktif]) }}"
               class="target-tab {{ $jenis === $key ? 'active' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Filter tahun & ULP --}}
    <form method="GET" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
        <input type="hidden" name="jenis" value="{{ $jenis }}">

        <div class="filter-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <select name="tahun" onchange="this.form.submit()" class="filter-select">
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M2 22h20M10 6h.01M14 6h.01M10 10h.01M14 10h.01M10 14h.01M14 14h.01M10 18h4"/></svg>
            <select name="ulp" onchange="this.form.submit()" class="filter-select">
                <option value="" {{ ! $ulpAktif ? 'selected' : '' }}>Semua ULP (target global)</option>
                @foreach ($daftarUlp as $kode => $nama)
                    <option value="{{ $kode }}" {{ $ulpAktif === $kode ? 'selected' : '' }}>{{ $nama }} ({{ $kode }})</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="target-info-bar">
        Mengisi target untuk:
        <strong>
            {{ $jenisOptions[$jenis] }} &mdash; Tahun {{ $tahun }} &mdash;
            {{ $ulpAktif ? ($daftarUlp[$ulpAktif] ?? $ulpAktif) . " ($ulpAktif)" : 'Semua ULP' }}
        </strong>
    </div>

    {{-- Form input 12 bulan --}}
    <form method="POST" action="{{ route('edit-target.update') }}">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="jenis" value="{{ $jenis }}">
        <input type="hidden" name="ulp" value="{{ $ulpAktif }}">

        <div class="target-grid">
            @foreach ($namaBulan as $bulanNum => $bulanNama)
                @php
                    $nilaiTersimpan = old("target.$bulanNum", $targetBulanan[$bulanNum]);
                @endphp
                <div class="target-field">
                    <label>{{ $bulanNama }}</label>
                    <input type="number" step="0.01" min="0" inputmode="decimal"
                        class="target-number-input"
                        name="target[{{ $bulanNum }}]"
                        placeholder="0"
                        value="{{ $nilaiTersimpan == 0 ? '' : $nilaiTersimpan }}"
                        onkeydown="return ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'].includes(event.key) || (event.key === '.' && !event.target.value.includes('.')) || /^[0-9]$/.test(event.key)"
                        oninput="if (this.value < 0) this.value = 0;">
                </div>
            @endforeach
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:26px;">
            <button type="submit" class="btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><polyline points="20 6 9 17 4 12"/></svg>
                Simpan Target
            </button>
        </div>
    </form>
</div>

@endsection
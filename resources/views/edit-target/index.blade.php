@extends('layouts.app')
@section('title', 'Edit Target')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Edit Target</strong>
@endsection

@push('styles')
<style>
    /* Sembunyikan native spinner number di seluruh halaman ini,
       jaga-jaga kalau sumber panah naik-turun itu dari input[type=number] lain */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }

    .target-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }
    .target-header h2 {
        margin: 0 0 4px;
        font-size: 22px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .target-header .icon-badge {
        width: 34px; height: 34px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--blue-primary), #4f7fff);
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        box-shadow: 0 4px 10px rgba(56,97,251,.25);
        flex-shrink: 0;
    }
    .target-header .icon-badge svg { width: 18px; height: 18px; }
    .target-header p { color: #6b7690; margin: 0; font-size: 14px; }

    /* Card pembungkus form — sebelumnya padding-nya inline
       (style="padding:24px 26px;") jadi gak bisa dikecilin lewat
       media query. Sekarang dipindah ke class ini biar responsive. */
    .target-card { padding: 24px 26px; }

    .target-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 22px;
        border-bottom: 1px solid var(--border);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .target-tab {
        padding: 10px 20px;
        font-size: 13.5px;
        font-weight: 600;
        text-decoration: none;
        color: #6b7690;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        white-space: nowrap;
        flex-shrink: 0;
        border-radius: 8px 8px 0 0;
        transition: .15s;
    }
    .target-tab.active {
        color: var(--blue-primary);
        border-bottom-color: var(--blue-primary);
        background: rgba(56,97,251,.06);
    }
    .target-tab:hover:not(.active) {
        color: #1b2559;
        background: #f8f9fc;
    }

    .filter-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 18px;
        flex-wrap: wrap;
        background: #f8f9fc;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 12px;
    }
    .filter-wrap { position: relative; flex: 1; min-width: 180px; }
    .filter-wrap svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px; color: #9aa4c2; pointer-events: none;
    }
    .filter-select {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid var(--border);
        border-radius: 9px;
        padding: 9px 14px 9px 32px;
        font-size: 13px;
        background: #fff;
        appearance: none;
        min-width: 0;
        cursor: pointer;
        transition: .15s;
    }
    .filter-select:hover { border-color: var(--blue-primary); }
    .filter-select:focus {
        outline: none;
        border-color: var(--blue-primary);
        box-shadow: 0 0 0 3px rgba(56,97,251,.12);
    }

    .target-info-bar {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        color: #3d4566;
        background: linear-gradient(135deg, rgba(56,97,251,.06), rgba(56,97,251,.02));
        border: 1px solid rgba(56,97,251,.18);
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .target-info-bar svg {
        width: 16px; height: 16px;
        color: var(--blue-primary);
        flex-shrink: 0;
        margin-top: 1px;
    }
    .target-info-bar strong { color: #1b2559; }

    .target-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    .target-field {
        position: relative;
        background: #f8f9fb;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 12px 14px;
        transition: .15s;
    }
    .target-field:hover {
        border-color: #c9d3f5;
    }
    .target-field:focus-within {
        border-color: var(--blue-primary);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(56,97,251,.1);
    }
    .target-field label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .2px;
        text-transform: uppercase;
        color: #8b93ac;
        margin-bottom: 8px;
    }
    .target-field input[type="text"] {
        width: 100%;
        box-sizing: border-box;
        padding: 4px 0;
        border: none;
        background: transparent;
        font-size: 16px;
        font-weight: 600;
        color: #1b2559;
    }
    .target-field input[type="text"]:focus { outline: none; }
    .target-field input[type="text"]::placeholder { color: #c3c9dc; font-weight: 400; }

    .target-save-btn {
        display: flex;
        justify-content: flex-end;
        margin-top: 26px;
        padding-top: 20px;
        border-top: 1px solid var(--border);
    }
    .target-save-btn .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 22px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(56,97,251,.25);
        transition: .15s;
    }
    .target-save-btn .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(56,97,251,.32);
    }

    /* ===== Responsive ===== */
    @media (max-width: 900px) {
        .target-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
        .target-card { padding: 18px 16px; }
        .target-header h2 { font-size: 19px; }
        .target-header p { font-size: 13px; }
        .filter-bar { padding: 10px; gap: 10px; }
        .filter-wrap { min-width: 0; flex: 1 1 100%; }
        .target-info-bar { padding: 11px 14px; font-size: 12.5px; }
        .target-save-btn { margin-top: 20px; padding-top: 16px; }
        .target-save-btn .btn { width: 100%; padding: 12px 22px; }
    }

    @media (max-width: 560px) {
        .target-grid { grid-template-columns: 1fr; gap: 10px; }
        .target-tab { padding: 9px 14px; font-size: 13px; }
    }
</style>
@endpush

@section('content')

<div class="target-header">
    <div>
        <h2>
            <span class="icon-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>
            </span>
            Edit Target
        </h2>
        <p>Input manual nilai target per bulan &mdash; ditampilkan di halaman Trend</p>
    </div>
</div>

{{-- Notif sukses ditangani global di layouts.app, jadi blok lokal tidak dipasang lagi di sini supaya tidak double --}}

<div class="card target-card">

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
    <form method="GET" class="filter-bar">
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>
            Mengisi target untuk:
            <strong>
                {{ $jenisOptions[$jenis] }} &mdash; Tahun {{ $tahun }} &mdash;
                {{ $ulpAktif ? ($daftarUlp[$ulpAktif] ?? $ulpAktif) . " ($ulpAktif)" : 'Semua ULP' }}
            </strong>
        </span>
    </div>

    {{-- Form input 12 bulan --}}
    <form method="POST" action="{{ route('edit-target.update') }}" id="form-edit-target">
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
                    {{-- Input yang terlihat: text + format titik ribuan otomatis --}}
                    <input type="text" inputmode="numeric"
                        class="target-number-input"
                        placeholder="0"
                        value="{{ $nilaiTersimpan == 0 ? '' : number_format($nilaiTersimpan, 0, ',', '.') }}">
                    {{-- Input asli yang dikirim ke server: angka polos tanpa titik --}}
                    <input type="hidden"
                        name="target[{{ $bulanNum }}]"
                        class="target-number-raw"
                        value="{{ $nilaiTersimpan == 0 ? '' : $nilaiTersimpan }}">
                </div>
            @endforeach
        </div>

        <div class="target-save-btn">
            <button type="submit" class="btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><polyline points="20 6 9 17 4 12"/></svg>
                Simpan Target
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fields = document.querySelectorAll('.target-field');

    function formatRibuan(value) {
        const angka = value.replace(/[^0-9]/g, '');
        if (!angka) return '';
        return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    fields.forEach(function (field) {
        const display = field.querySelector('.target-number-input');
        const hidden = field.querySelector('.target-number-raw');

        display.addEventListener('input', function () {
            const cursorFromEnd = display.value.length - display.selectionStart;
            const angkaMentah = display.value.replace(/[^0-9]/g, '');

            display.value = formatRibuan(display.value);
            hidden.value = angkaMentah;

            const newPos = display.value.length - cursorFromEnd;
            display.setSelectionRange(newPos, newPos);
        });

        display.addEventListener('keydown', function (e) {
            const allowed = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'];
            if (allowed.includes(e.key) || /^[0-9]$/.test(e.key)) return;
            e.preventDefault();
        });
    });

    // Jaga-jaga: pastikan nilai mentah tersinkron sebelum form dikirim
    document.getElementById('form-edit-target').addEventListener('submit', function () {
        document.querySelectorAll('.target-number-input').forEach(function (display) {
            const hidden = display.closest('.target-field').querySelector('.target-number-raw');
            hidden.value = display.value.replace(/[^0-9]/g, '');
        });
    });
});
</script>
@endpush
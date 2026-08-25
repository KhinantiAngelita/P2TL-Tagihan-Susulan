@extends('layouts.app')
@section('title', 'Export PDF')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Export PDF</strong>
@endsection

@push('styles')
<style>
    .xpdf-card { padding: 28px; border-radius: 14px; box-shadow: 0 2px 14px rgba(11,61,145,0.06); }
    .xpdf-grid { display: flex; gap: 22px; flex-wrap: wrap; margin-bottom: 22px; }
    .xpdf-filter { max-width: 220px; flex: 1; min-width: 180px; }
    .xpdf-filter label { display: block; font-size: 13px; font-weight: 700; color: #1b2559; margin-bottom: 7px; }
    .filter-wrap { position: relative; }
    .filter-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #9aa4c2; pointer-events: none; }
    .filter-select { width: 100%; box-sizing: border-box; border: 1px solid var(--border); border-radius: 9px; padding: 10px 14px 10px 34px; font-size: 13px; background: #fff; appearance: none; transition: .15s; }
    .filter-select:focus { border-color: #0b3d91; outline: none; box-shadow: 0 0 0 3px rgba(11,61,145,0.1); }
    .filter-select.plain { padding-left: 14px; }

    .xpdf-periode-tabs { display: flex; gap: 6px; margin-bottom: 14px; background: #f4f7fd; padding: 4px; border-radius: 10px; width: fit-content; }
    .xpdf-tab-btn { border: none; background: transparent; border-radius: 8px; padding: 8px 16px; font-size: 12.5px; font-weight: 700; color: #6b7690; cursor: pointer; transition: .15s; }
    .xpdf-tab-btn.active { background: #0b3d91; color: #fff; box-shadow: 0 2px 6px rgba(11,61,145,0.25); }
    .xpdf-periode-body { display: none; gap: 12px; flex-wrap: wrap; margin-bottom: 22px; }
    .xpdf-periode-body.active { display: flex; }

    .xpdf-section-title { font-size: 13.5px; font-weight: 700; color: #1b2559; margin: 28px 0 12px; padding-top: 18px; border-top: 1px solid #eef1f8; }
    .xpdf-section-title:first-of-type { margin-top: 0; padding-top: 0; border-top: none; }
    .xpdf-section-title small { display: block; font-weight: 500; color: #9aa4c2; font-size: 11.5px; margin-top: 3px; }

    .xpdf-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; }
    .xpdf-chip {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 14px 16px; border: 1px solid var(--border); border-radius: 10px;
        cursor: pointer; transition: .15s; background: #fff;
    }
    .xpdf-chip:hover { border-color: #c7d3f0; background: #f8f9fc; }
    .xpdf-chip:has(input:checked) { border-color: #0b3d91; background: #f4f7fd; box-shadow: 0 0 0 1px #0b3d91 inset; }
    .xpdf-chip input { width: 17px; height: 17px; accent-color: #0b3d91; cursor: pointer; flex-shrink: 0; margin-top: 2px; }
    .xpdf-chip-body { flex: 1; }
    .xpdf-chip-body > span { font-size: 13.5px; font-weight: 600; color: #1b2559; display: block; }
    .xpdf-chip-menu { display: inline-block; font-size: 10.5px; font-weight: 700; color: #0b3d91; background: #eaf0fb; border-radius: 5px; padding: 3px 8px; margin-top: 6px; }
    .xpdf-chip-desc { font-size: 12px; color: #6b7690; margin: 6px 0 0; line-height: 1.55; }

    .xpdf-ulp-box { max-height: 220px; overflow-y: auto; border: 1px solid var(--border); border-radius: 10px; padding: 8px; margin-bottom: 10px; background: #fbfcfe; }
    .xpdf-ulp-item { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 7px; cursor: pointer; transition: .12s; }
    .xpdf-ulp-item:hover { background: #eef2fb; }
    .xpdf-ulp-item input { width: 15px; height: 15px; accent-color: #0b3d91; cursor: pointer; }
    .xpdf-ulp-item span { font-size: 13px; color: #1b2559; }

    .xpdf-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 20px; margin-top: 6px; border-top: 1px solid var(--border); }
    .xpdf-select-all { background: none; border: none; color: #0b3d91; font-size: 12.5px; font-weight: 700; cursor: pointer; padding: 0; }
    .xpdf-submit-btn {
        background: #0b3d91; color: #fff; border: none; border-radius: 10px;
        padding: 13px 26px; font-size: 14px; font-weight: 800; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px; transition: .15s;
        box-shadow: 0 4px 12px rgba(11,61,145,0.25);
    }
    .xpdf-submit-btn:hover { background: #092f70; transform: translateY(-1px); }
    .xpdf-submit-btn svg { width: 16px; height: 16px; }
</style>
@endpush

@section('content')

<div style="margin-bottom:22px;">
    <h2 style="margin:0 0 4px;font-size:22px;">Export PDF</h2>
    <p style="color:#6b7690;margin:0;font-size:14px;">Atur filter periode & ULP, lalu pilih bagian laporan yang ingin digabung jadi satu file PDF.</p>
</div>

<div class="card xpdf-card">
    <form method="POST" action="{{ route('export-pdf.generate') }}" id="formExportPdf">
        @csrf

        <p class="xpdf-section-title">Tahun</p>
        <div class="xpdf-grid">
            <div class="xpdf-filter">
                <div class="filter-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <select name="tahun" class="filter-select">
                        @forelse ($daftarTahun as $t)
                            <option value="{{ $t }}" {{ (int) $tahunAktif === (int) $t ? 'selected' : '' }}>{{ $t }}</option>
                        @empty
                            <option value="">Belum ada data</option>
                        @endforelse
                    </select>
                </div>
            </div>
        </div>

        <p class="xpdf-section-title">
            Periode
            <small>Pilih salah satu: sepanjang tahun, per triwulan, atau rentang bulan tertentu.</small>
        </p>
        <div class="xpdf-periode-tabs">
            <button type="button" class="xpdf-tab-btn active" data-tab="tahun" onclick="pilihTabPeriode('tahun')">Sepanjang Tahun</button>
            <button type="button" class="xpdf-tab-btn" data-tab="triwulan" onclick="pilihTabPeriode('triwulan')">Triwulan</button>
            <button type="button" class="xpdf-tab-btn" data-tab="rentang" onclick="pilihTabPeriode('rentang')">Rentang Bulan</button>
        </div>

        <div class="xpdf-periode-body active" data-body="tahun">
            <p style="font-size:12.5px;color:#6b7690;margin:0;">Seluruh data tahun terpilih akan digunakan (Januari &ndash; Desember).</p>
        </div>

        <div class="xpdf-periode-body" data-body="triwulan">
            <div class="xpdf-filter">
                <label>Triwulan</label>
                <select name="triwulan" class="filter-select plain">
                    <option value="">Pilih triwulan</option>
                    @foreach ($triwulanLabel as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="xpdf-periode-body" data-body="rentang">
            <div class="xpdf-filter">
                <label>Bulan Awal</label>
                <select name="bulan_awal" class="filter-select plain">
                    <option value="">Pilih bulan</option>
                    @foreach ($bulanLabel as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xpdf-filter">
                <label>Bulan Akhir</label>
                <select name="bulan_akhir" class="filter-select plain">
                    <option value="">Pilih bulan</option>
                    @foreach ($bulanLabel as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <p class="xpdf-section-title">
            Filter ULP
            <small>Kosongkan / pilih semua kalau ingin mengikutkan seluruh ULP.</small>
        </p>
        <div class="xpdf-ulp-box">
            @forelse ($daftarUlp as $kode => $nama)
                <label class="xpdf-ulp-item">
                    <input type="checkbox" name="ulp[]" value="{{ $kode }}" class="xpdf-ulp-checkbox" checked>
                    <span>{{ $nama }}</span>
                </label>
            @empty
                <p style="font-size:12.5px;color:#6b7690;margin:6px;">Belum ada data ULP untuk tahun ini.</p>
            @endforelse
        </div>
        <div style="margin-bottom:24px;">
            <button type="button" class="xpdf-select-all" onclick="toggleSemuaUlp()">Pilih/Batalkan Semua ULP</button>
        </div>

        <p class="xpdf-section-title">Pilih Bagian Laporan</p>

        <div class="xpdf-list">
            @foreach ($sectionMeta as $key => $meta)
                <label class="xpdf-chip">
                    <input type="checkbox" name="sections[]" value="{{ $key }}" class="xpdf-checkbox" checked>
                    <span class="xpdf-chip-body">
                        <span>{{ $meta['label'] }}</span>
                        <span class="xpdf-chip-menu">Menu: {{ $meta['menu'] }}</span>
                        <p class="xpdf-chip-desc">{{ $meta['info'] }}</p>
                    </span>
                </label>
            @endforeach
        </div>

        <div class="xpdf-actions">
            <button type="button" class="xpdf-select-all" onclick="toggleSemuaSection()">Pilih/Batalkan Semua Bagian</button>
            <button type="submit" class="xpdf-submit-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15V3m0 12-4-4m4 4 4-4"/><path d="M2 17v3a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-3"/></svg>
                Export ke PDF
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
function pilihTabPeriode(tab) {
    document.querySelectorAll('.xpdf-tab-btn').forEach(function (b) {
        b.classList.toggle('active', b.dataset.tab === tab);
    });
    document.querySelectorAll('.xpdf-periode-body').forEach(function (b) {
        var aktif = b.dataset.body === tab;
        b.classList.toggle('active', aktif);
        if (! aktif) {
            b.querySelectorAll('select').forEach(function (s) { s.value = ''; });
        }
    });
}

function toggleSemuaSection() {
    var boxes = document.querySelectorAll('.xpdf-checkbox');
    var adaYangKosong = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
    boxes.forEach(function (b) { b.checked = adaYangKosong; });
}

function toggleSemuaUlp() {
    var boxes = document.querySelectorAll('.xpdf-ulp-checkbox');
    var adaYangKosong = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
    boxes.forEach(function (b) { b.checked = adaYangKosong; });
}

document.getElementById('formExportPdf').addEventListener('submit', function (e) {
    var checked = document.querySelectorAll('.xpdf-checkbox:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Pilih minimal 1 bagian laporan untuk di-export.');
        return;
    }

    var tabAktif = document.querySelector('.xpdf-tab-btn.active').dataset.tab;

    if (tabAktif === 'rentang') {
        var awal = document.querySelector('select[name="bulan_awal"]').value;
        var akhir = document.querySelector('select[name="bulan_akhir"]').value;
        if (!awal || !akhir) {
            e.preventDefault();
            alert('Lengkapi Bulan Awal dan Bulan Akhir, atau pilih tab periode lain.');
            return;
        }
    }

    if (tabAktif === 'triwulan') {
        var tw = document.querySelector('select[name="triwulan"]').value;
        if (!tw) {
            e.preventDefault();
            alert('Pilih triwulan, atau pilih tab periode lain.');
        }
    }
});
</script>
@endpush
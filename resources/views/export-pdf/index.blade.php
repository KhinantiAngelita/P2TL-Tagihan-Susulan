@extends('layouts.app')
@section('title', 'Export PDF')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Beranda</a>
    <span class="sep">›</span>
    <strong>Export PDF</strong>
@endsection

@push('styles')
<style>
    .xpdf-page-title { font-size: clamp(18px, 4.2vw, 22px); margin: 0 0 4px; color: #1b2559; font-weight: 700; }

    .xpdf-card {
        padding: 0; overflow: hidden; background: #fff;
        border: 1px solid var(--border); border-radius: 16px; box-sizing: border-box;
        box-shadow: 0 1px 2px rgba(16,24,64,.04);
    }

    /* ===== Header card, senada pola trend-table-head di halaman lain ===== */
    .xpdf-card-head {
        display: flex; align-items: center; gap: 12px;
        padding: 20px 24px; border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, #0b3d91, #2f6fdb);
    }
    .xpdf-card-head-icon {
        width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.16); color: #fff;
    }
    .xpdf-card-head-icon svg { width: 19px; height: 19px; }
    .xpdf-card-head h3 { margin: 0 0 2px; font-size: 16px; color: #fff; font-weight: 800; }
    .xpdf-card-head p { margin: 0; font-size: 12.5px; color: rgba(255,255,255,.85); }

    .xpdf-body { padding: 26px 24px 8px; }

    /* ===== Section — nomor bulat + judul + deskripsi ===== */
    .xpdf-section { margin-bottom: 30px; }
    .xpdf-section:last-child { margin-bottom: 0; }
    .xpdf-section-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
    .xpdf-step-no {
        width: 26px; height: 26px; border-radius: 999px; flex-shrink: 0;
        background: #eaf0fb; color: #0b3d91; font-size: 12px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; margin-top: 1px;
    }
    .xpdf-section-head-text { min-width: 0; }
    .xpdf-section-head h4 { margin: 0; font-size: 14px; color: #1b2559; font-weight: 700; }
    .xpdf-section-head p { margin: 3px 0 0; font-size: 12px; color: #8892a8; line-height: 1.5; }

    .xpdf-section-body { margin-left: 38px; }

    /* ===== Filter select ===== */
    .xpdf-grid { display: flex; gap: 14px; flex-wrap: wrap; }
    .xpdf-filter { max-width: 220px; flex: 1; min-width: 180px; }
    .xpdf-filter label { display: block; font-size: 12px; font-weight: 700; color: #6b7690; margin-bottom: 6px; }
    .filter-wrap { position: relative; }
    .filter-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #9aa4c2; pointer-events: none; }
    .filter-select {
        width: 100%; box-sizing: border-box; border: 1px solid var(--border); border-radius: 10px;
        padding: 10px 14px 10px 34px; font-size: 13px; background: #fff; appearance: none; transition: .15s;
        color: #1b2559; font-weight: 600;
    }
    .filter-select:focus { border-color: #0b3d91; outline: none; box-shadow: 0 0 0 3px rgba(11,61,145,.1); }
    .filter-select.plain { padding-left: 14px; }

    /* ===== Tab periode ===== */
    .xpdf-periode-tabs { display: flex; gap: 4px; margin-bottom: 14px; background: #f4f7fd; padding: 4px; border-radius: 11px; width: fit-content; }
    .xpdf-tab-btn { border: none; background: transparent; border-radius: 8px; padding: 8px 16px; font-size: 12.5px; font-weight: 700; color: #6b7690; cursor: pointer; transition: .15s; }
    .xpdf-tab-btn.active { background: #0b3d91; color: #fff; box-shadow: 0 2px 6px rgba(11,61,145,.25); }
    .xpdf-periode-body { display: none; gap: 12px; flex-wrap: wrap; }
    .xpdf-periode-body.active { display: flex; }
    .xpdf-periode-note {
        font-size: 12.5px; color: #6b7690; margin: 0;
        background: #f7f8fc; border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px;
    }

    /* ===== ULP box ===== */
    .xpdf-ulp-box {
        max-height: 220px; overflow-y: auto; border: 1px solid var(--border); border-radius: 12px;
        padding: 8px; margin-bottom: 10px; background: #fbfcfe;
    }
    .xpdf-ulp-item { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 8px; cursor: pointer; transition: .12s; }
    .xpdf-ulp-item:hover { background: #eef2fb; }
    .xpdf-ulp-item input { width: 15px; height: 15px; accent-color: #0b3d91; cursor: pointer; }
    .xpdf-ulp-item span { font-size: 13px; color: #1b2559; font-weight: 500; }

    /* ===== Chip bagian laporan ===== */
    .xpdf-list { display: flex; flex-direction: column; gap: 8px; }
    .xpdf-chip {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 14px 16px; border: 1px solid var(--border); border-radius: 12px;
        cursor: pointer; transition: .15s; background: #fff;
    }
    .xpdf-chip:hover { border-color: #c7d3f0; background: #f8f9fc; }
    .xpdf-chip:has(input:checked) { border-color: #0b3d91; background: #f4f7fd; box-shadow: 0 0 0 1px #0b3d91 inset; }
    .xpdf-chip input { width: 17px; height: 17px; accent-color: #0b3d91; cursor: pointer; flex-shrink: 0; margin-top: 2px; }
    .xpdf-chip-body { flex: 1; min-width: 0; }
    .xpdf-chip-body > span { font-size: 13.5px; font-weight: 700; color: #1b2559; display: block; }
    .xpdf-chip-menu { display: inline-block; font-size: 10.5px; font-weight: 700; color: #0b3d91; background: #eaf0fb; border-radius: 6px; padding: 3px 8px; margin-top: 6px; }
    .xpdf-chip-desc { font-size: 12px; color: #6b7690; margin: 6px 0 0; line-height: 1.55; }

    /* ===== Footer aksi (sticky-ish) ===== */
    .xpdf-actions {
        display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
        padding: 18px 24px; margin-top: 26px; border-top: 1px solid var(--border); background: #fafbfe;
    }
    .xpdf-actions-left { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .xpdf-select-all { background: none; border: none; color: #0b3d91; font-size: 12.5px; font-weight: 700; cursor: pointer; padding: 0; }
    .xpdf-counter {
        font-size: 12px; color: #6b7690; background: #eef0f6; padding: 4px 11px; border-radius: 999px; font-weight: 600;
    }
    .xpdf-counter strong { color: #1b2559; }
    .xpdf-submit-btn {
        background: #0b3d91; color: #fff; border: none; border-radius: 11px;
        padding: 13px 26px; font-size: 14px; font-weight: 800; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px; transition: .15s;
        box-shadow: 0 4px 12px rgba(11,61,145,.25);
    }
    .xpdf-submit-btn:hover { background: #092f70; transform: translateY(-1px); }
    .xpdf-submit-btn svg { width: 16px; height: 16px; }

    @media (max-width: 640px) {
        .xpdf-body { padding: 22px 16px 4px; }
        .xpdf-section-body { margin-left: 0; }
        .xpdf-card-head { padding: 18px 16px; }
        .xpdf-actions { padding: 16px; flex-direction: column; align-items: stretch; }
        .xpdf-actions-left { justify-content: space-between; }
        .xpdf-submit-btn { justify-content: center; }
    }
</style>
@endpush

@section('content')

<div style="margin-bottom:22px;">
    <h2 class="xpdf-page-title">Export PDF</h2>
    <p style="color:#6b7690;margin:0;font-size:14px;">Atur filter periode & ULP, lalu pilih bagian laporan yang ingin digabung jadi satu file PDF.</p>
</div>

<div class="xpdf-card">
    <div class="xpdf-card-head">
        <div class="xpdf-card-head-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M12 11v6m0 0-2.5-2.5M12 17l2.5-2.5"/></svg>
        </div>
        <div>
            <h3>Konfigurasi Export</h3>
            <p>Sesuaikan cakupan data sebelum digabung menjadi satu dokumen PDF</p>
        </div>
    </div>

    <form method="POST" action="{{ route('export-pdf.generate') }}" id="formExportPdf">
        @csrf

        <div class="xpdf-body">

            {{-- ===== 1. Tahun ===== --}}
            <div class="xpdf-section">
                <div class="xpdf-section-head">
                    <span class="xpdf-step-no">1</span>
                    <div class="xpdf-section-head-text">
                        <h4>Tahun</h4>
                        <p>Tahun laporan yang datanya ingin di-export</p>
                    </div>
                </div>
                <div class="xpdf-section-body">
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
                </div>
            </div>

            {{-- ===== 2. Periode ===== --}}
            <div class="xpdf-section">
                <div class="xpdf-section-head">
                    <span class="xpdf-step-no">2</span>
                    <div class="xpdf-section-head-text">
                        <h4>Periode</h4>
                        <p>Pilih salah satu: sepanjang tahun, per triwulan, atau rentang bulan tertentu</p>
                    </div>
                </div>
                <div class="xpdf-section-body">
                    <div class="xpdf-periode-tabs">
                        <button type="button" class="xpdf-tab-btn active" data-tab="tahun" onclick="pilihTabPeriode('tahun')">Sepanjang Tahun</button>
                        <button type="button" class="xpdf-tab-btn" data-tab="triwulan" onclick="pilihTabPeriode('triwulan')">Triwulan</button>
                        <button type="button" class="xpdf-tab-btn" data-tab="rentang" onclick="pilihTabPeriode('rentang')">Rentang Bulan</button>
                    </div>

                    <div class="xpdf-periode-body active" data-body="tahun">
                        <p class="xpdf-periode-note">Seluruh data tahun terpilih akan digunakan (Januari &ndash; Desember).</p>
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
                </div>
            </div>

            {{-- ===== 3. Filter ULP ===== --}}
            <div class="xpdf-section">
                <div class="xpdf-section-head">
                    <span class="xpdf-step-no">3</span>
                    <div class="xpdf-section-head-text">
                        <h4>Filter ULP</h4>
                        <p>Kosongkan / pilih semua kalau ingin mengikutkan seluruh ULP</p>
                    </div>
                </div>
                <div class="xpdf-section-body">
                    <div class="xpdf-ulp-box">
                        @forelse ($daftarUlp as $kode => $nama)
                            <label class="xpdf-ulp-item">
                                <input type="checkbox" name="ulp[]" value="{{ $kode }}" class="xpdf-ulp-checkbox" checked onchange="updateCounterUlp()">
                                <span>{{ $nama }}</span>
                            </label>
                        @empty
                            <p style="font-size:12.5px;color:#6b7690;margin:6px;">Belum ada data ULP untuk tahun ini.</p>
                        @endforelse
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <button type="button" class="xpdf-select-all" onclick="toggleSemuaUlp()">Pilih/Batalkan Semua ULP</button>
                        @if (count($daftarUlp) > 0)
                            <span class="xpdf-counter" id="counterUlp"><strong>{{ count($daftarUlp) }}</strong> dari {{ count($daftarUlp) }} ULP dipilih</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== 4. Pilih Bagian Laporan ===== --}}
            <div class="xpdf-section">
                <div class="xpdf-section-head">
                    <span class="xpdf-step-no">4</span>
                    <div class="xpdf-section-head-text">
                        <h4>Pilih Bagian Laporan</h4>
                        <p>Centang bagian yang ingin dimasukkan ke dalam dokumen PDF</p>
                    </div>
                </div>
                <div class="xpdf-section-body">
                    <div class="xpdf-list">
                        @foreach ($sectionMeta as $key => $meta)
                            <label class="xpdf-chip">
                                <input type="checkbox" name="sections[]" value="{{ $key }}" class="xpdf-checkbox" checked onchange="updateCounterSection()">
                                <span class="xpdf-chip-body">
                                    <span>{{ $meta['label'] }}</span>
                                    <span class="xpdf-chip-menu">Menu: {{ $meta['menu'] }}</span>
                                    <p class="xpdf-chip-desc">{{ $meta['info'] }}</p>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        <div class="xpdf-actions">
            <div class="xpdf-actions-left">
                <button type="button" class="xpdf-select-all" onclick="toggleSemuaSection()">Pilih/Batalkan Semua Bagian</button>
                <span class="xpdf-counter" id="counterSection"><strong>{{ count($sectionMeta) }}</strong> dari {{ count($sectionMeta) }} bagian dipilih</span>
            </div>
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
    updateCounterSection();
}

function toggleSemuaUlp() {
    var boxes = document.querySelectorAll('.xpdf-ulp-checkbox');
    var adaYangKosong = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
    boxes.forEach(function (b) { b.checked = adaYangKosong; });
    updateCounterUlp();
}

function updateCounterSection() {
    var el = document.getElementById('counterSection');
    if (!el) return;
    var boxes = document.querySelectorAll('.xpdf-checkbox');
    var checked = document.querySelectorAll('.xpdf-checkbox:checked').length;
    el.innerHTML = '<strong>' + checked + '</strong> dari ' + boxes.length + ' bagian dipilih';
}

function updateCounterUlp() {
    var el = document.getElementById('counterUlp');
    if (!el) return;
    var boxes = document.querySelectorAll('.xpdf-ulp-checkbox');
    var checked = document.querySelectorAll('.xpdf-ulp-checkbox:checked').length;
    el.innerHTML = '<strong>' + checked + '</strong> dari ' + boxes.length + ' ULP dipilih';
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
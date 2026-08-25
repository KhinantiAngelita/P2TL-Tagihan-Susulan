@php
    $namaBulanFilter = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    $twTerpilih     = $filter['twTerpilih'] ?? [];
    $bulanTerpilih = $filter['bulanTerpilih'] ?? [];
    $ulpTerpilih   = $filter['ulpTerpilih'] ?? [];
    $tglMulai      = $filter['tglMulai'] ?? null;
    $tglSelesai    = $filter['tglSelesai'] ?? null;
    $jumlahFilterAktif = count($twTerpilih) + count($bulanTerpilih) + count($ulpTerpilih) + ($tglMulai || $tglSelesai ? 1 : 0);
@endphp

@push('styles')
<style>
    .fp-trigger {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: none;
        background: linear-gradient(135deg, #0b3d91, #2f6fdb);
        color: #fff;
        font-size: 12.5px;
        font-weight: 700;
        padding: 8px 14px;
        border-radius: 999px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(11,61,145,.25);
        transition: opacity .15s;
    }
    .fp-trigger:hover { opacity: .9; }
    .fp-trigger svg { width: 13px; height: 13px; flex-shrink: 0; }
    .fp-trigger .fp-count {
        background: rgba(255,255,255,.25);
        border-radius: 999px;
        padding: 1px 7px;
        font-size: 10.5px;
    }

    /* Card Rangkuman Filter Aktif di Luar / Setelah Apply */
    .fp-active-card {
        background: #f8f9fc;
        border: 1px solid #eef0f6;
        border-radius: 12px;
        padding: 12px 16px;
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        box-shadow: 0 2px 6px rgba(11,31,77,.03);
    }
    .fp-active-card-left {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12.5px;
        color: #4a5578;
    }
    .fp-active-card-left strong { color: #1b2559; }
    .fp-active-card-left svg { width: 16px; height: 16px; color: #2f6fdb; flex-shrink: 0; }
    .fp-active-card-right {
        display: flex;
        gap: 6px;
    }
    .fp-btn-ubah {
        background: #fff;
        border: 1px solid #d0d7de;
        color: #24292f;
        font-size: 11.5px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
    }
    .fp-btn-ubah:hover { background: #f3f4f6; }

    /* Panel Modal / Dropdown */
    .fp-panel {
        display: none;
        background: #fff;
        border-radius: 14px;
        margin-top: 10px;
        margin-bottom: 14px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(11,31,77,.08);
        border: 1px solid #e5e9f5;
    }

    .fp-panel-head {
        background: linear-gradient(135deg, #0b3d91, #2f6fdb);
        padding: 13px 20px;
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .fp-panel-head-title { display: flex; align-items: center; gap: 7px; }
    .fp-panel-head svg { width: 14px; height: 14px; }
    .fp-close-btn { background: none; border: none; color: #fff; cursor: pointer; font-size: 16px; font-weight: bold; }

    /* Layout Menu Pilihan Kategori vs Detail */
    .fp-body-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        min-height: 280px;
    }
    @media (max-width: 768px) {
        .fp-body-layout { grid-template-columns: 1fr; }
    }

    .fp-menu-sidebar {
        background: #f8f9fc;
        border-right: 1px solid #eef0f6;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .fp-menu-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        color: #4a5578;
        cursor: pointer;
        background: transparent;
        border: none;
        width: 100%;
        text-align: left;
        transition: all .15s;
    }
    .fp-menu-item:hover { background: #edf2f7; color: #1b2559; }
    .fp-menu-item.active { background: #0b3d91; color: #fff; }
    .fp-menu-badge {
        background: rgba(11,61,145,.15);
        color: #0b3d91;
        font-size: 10.5px;
        padding: 1px 6px;
        border-radius: 999px;
    }
    .fp-menu-item.active .fp-menu-badge { background: rgba(255,255,255,0.3); color: #fff; }

    .fp-content-area {
        padding: 20px;
        background: #fff;
    }
    .fp-section-content {
        display: none;
    }
    .fp-section-content.active {
        display: block;
    }

    .fp-group-label {
        font-size: 12px;
        font-weight: 700;
        color: #1b2559;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin: 0 0 12px;
    }

    .fp-pill-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .fp-chip { position: relative; }
    .fp-chip input {
        position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer;
    }
    .fp-chip span {
        display: inline-block;
        padding: 7px 14px;
        border: 1px solid #e5e9f5;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 600;
        color: #4a5578;
        user-select: none;
        transition: all .15s;
    }
    .fp-chip input:checked + span {
        border-color: transparent;
        background: linear-gradient(135deg, #0b3d91, #2f6fdb);
        color: #fff;
    }

    .fp-tgl-field { margin-bottom: 12px; }
    .fp-tgl-field label { display: block; font-size: 11.5px; font-weight: 700; color: #6b7690; margin-bottom: 5px; }
    .fp-tgl-field input[type="date"] {
        width: 100%;
        border: 1px solid #e5e9f5;
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 13px;
        box-sizing: border-box;
        color: #1b2559;
    }
    .fp-tgl-field input[type="date"]:focus { border-color: #2f6fdb; outline: none; }

    .fp-ulp-search {
        width: 100%;
        border: 1px solid #e5e9f5;
        border-radius: 8px;
        padding: 8px 11px;
        font-size: 12.5px;
        box-sizing: border-box;
        margin-bottom: 10px;
    }
    .fp-ulp-search:focus { border-color: #2f6fdb; outline: none; }
    .fp-ulp-list {
        display: flex;
        flex-direction: column;
        gap: 2px;
        max-height: 180px;
        overflow-y: auto;
        border: 1px solid #f1f3f9;
        border-radius: 8px;
        padding: 6px;
    }
    .fp-ulp-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #4a5578;
        padding: 6px 8px;
        border-radius: 6px;
        cursor: pointer;
    }
    .fp-ulp-row:hover { background: #f8f9fc; }
    .fp-ulp-empty { font-size: 12px; color: #9aa4c2; }

    .fp-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 13px 20px;
        background: #f8f9fc;
        border-top: 1px solid #eef0f6;
        flex-wrap: wrap;
    }
    .fp-footer-left { font-size: 11.5px; color: #9aa4c2; }
    .fp-footer-actions { display: flex; gap: 8px; }
    .fp-btn-apply, .fp-btn-reset {
        border-radius: 999px;
        padding: 8px 16px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        border: none;
    }
    .fp-btn-apply { background: linear-gradient(135deg, #0b3d91, #2f6fdb); color: #fff; }
    .fp-btn-apply:hover { opacity: .9; }
    .fp-btn-reset { background: #eef0f6; color: #6b7690; }
    .fp-btn-reset:hover { background: #e4e7f0; }
</style>
@endpush

<div style="margin-bottom:18px;">
    <!-- Tombol Pemicu Utama -->
    <button type="button" class="fp-trigger" onclick="fpTogglePanel()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Filter Periode &amp; ULP
        @if ($jumlahFilterAktif)
            <span class="fp-count">{{ $jumlahFilterAktif }}</span>
        @endif
    </button>

    <!-- Card Rangkuman Filter Aktif (Muncul setelah Terapkan) -->
    @if ($jumlahFilterAktif)
        <div class="fp-active-card">
            <div class="fp-active-card-left">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <span>Filter aktif: <strong>{{ $filterInfoText }}</strong></span>
            </div>
            <div class="fp-active-card-right">
                <button type="button" class="fp-btn-ubah" onclick="fpTogglePanel()">Ubah Filter</button>
                <a href="{{ url()->current() }}?tahun={{ $tahunAktif }}" class="fp-btn-ubah" style="color: #d93838;">Reset</a>
            </div>
        </div>
    @endif

    <!-- Panel Interaktif Pilihan Kategori -->
    <div id="filter-panel" class="fp-panel">
        <div class="fp-panel-head">
            <div class="fp-panel-head-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Pilih Kategori Filter
            </div>
            <button type="button" class="fp-close-btn" onclick="fpTogglePanel()">&times;</button>
        </div>

        <form method="GET" id="fp-form">
            <input type="hidden" name="tahun" value="{{ $tahunAktif }}">

            <div class="fp-body-layout">
                <!-- Sidebar Menu Kategori -->
                <div class="fp-menu-sidebar">
                    <button type="button" class="fp-menu-item active" onclick="fpSwitchTab('tw', this)">
                        <span>Triwulan</span>
                        @if(count($twTerpilih)) <span class="fp-menu-badge">{{ count($twTerpilih) }}</span> @endif
                    </button>
                    <button type="button" class="fp-menu-item" onclick="fpSwitchTab('bulan', this)">
                        <span>Bulan</span>
                        @if(count($bulanTerpilih)) <span class="fp-menu-badge">{{ count($bulanTerpilih) }}</span> @endif
                    </button>
                    <button type="button" class="fp-menu-item" onclick="fpSwitchTab('tanggal', this)">
                        <span>Rentang Tanggal</span>
                        @if($tglMulai || $tglSelesai) <span class="fp-menu-badge">1</span> @endif
                    </button>
                    <button type="button" class="fp-menu-item" onclick="fpSwitchTab('ulp', this)">
                        <span>ULP</span>
                        @if(count($ulpTerpilih)) <span class="fp-menu-badge">{{ count($ulpTerpilih) }}</span> @endif
                    </button>
                </div>

                <!-- Area Konten Detail Filter -->
                <div class="fp-content-area">
                    <!-- 1. Konten Triwulan -->
                    <div id="section-tw" class="fp-section-content active">
                        <p class="fp-group-label">Pilih Triwulan</p>
                        <div class="fp-pill-row">
                            @foreach (['I','II','III','IV'] as $i => $labelTw)
                                <label class="fp-chip">
                                    <input type="checkbox" name="tw[]" value="{{ $i + 1 }}" {{ in_array($i + 1, $twTerpilih) ? 'checked' : '' }}>
                                    <span>TW {{ $labelTw }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 2. Konten Bulan -->
                    <div id="section-bulan" class="fp-section-content">
                        <p class="fp-group-label">Pilih Bulan</p>
                        <div class="fp-pill-row">
                            @foreach ($namaBulanFilter as $angka => $nama)
                                <label class="fp-chip">
                                    <input type="checkbox" name="bulan[]" value="{{ $angka }}" {{ in_array($angka, $bulanTerpilih) ? 'checked' : '' }}>
                                    <span>{{ $nama }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 3. Konten Rentang Tanggal -->
                    <div id="section-tanggal" class="fp-section-content">
                        <p class="fp-group-label">Pilih Rentang Tanggal</p>
                        <div class="fp-tgl-field">
                            <label>Dari tanggal</label>
                            <input type="date" name="tgl_mulai" value="{{ $tglMulai }}">
                        </div>
                        <div class="fp-tgl-field">
                            <label>Sampai tanggal</label>
                            <input type="date" name="tgl_selesai" value="{{ $tglSelesai }}">
                        </div>
                    </div>

                    <!-- 4. Konten ULP -->
                    <div id="section-ulp" class="fp-section-content">
                        <p class="fp-group-label">Pilih ULP</p>
                        @if (count($daftarUlp))
                            <input type="text" class="fp-ulp-search" placeholder="Cari nama ULP..." oninput="fpFilterUlp(this.value)">
                            <div class="fp-ulp-list" id="fp-ulp-list">
                                @foreach ($daftarUlp as $u)
                                    <label class="fp-ulp-row" data-nama="{{ strtolower($u['nama']) }}">
                                        <input type="checkbox" name="ulp[]" value="{{ $u['kode'] }}" {{ in_array($u['kode'], $ulpTerpilih) ? 'checked' : '' }}>
                                        {{ $u['nama'] }}
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <span class="fp-ulp-empty">Belum ada data ULP.</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="fp-footer">
                <span class="fp-footer-left">{{ $jumlahFilterAktif ? $jumlahFilterAktif . ' filter aktif saat ini' : 'Belum ada filter dipilih' }}</span>
                <div class="fp-footer-actions">
                    <a href="{{ url()->current() }}?tahun={{ $tahunAktif }}" class="fp-btn-reset">Reset Semua</a>
                    <button type="submit" class="fp-btn-apply">Terapkan Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="filter-info-text" style="display:none">{{ $filterInfoText }}</div>

<script>
function fpTogglePanel() {
    var panel = document.getElementById('filter-panel');
    if (!panel) return;
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
}

function fpSwitchTab(targetId, element) {
    // Sembunyikan semua section konten
    document.querySelectorAll('.fp-section-content').forEach(function(sec) {
        sec.classList.remove('active');
    });
    // Hilangkan status aktif pada semua tombol menu sidebar
    document.querySelectorAll('.fp-menu-item').forEach(function(btn) {
        btn.classList.remove('active');
    });
    // Tampilkan section yang diklik dan aktifkan tombolnya
    document.getElementById('section- ' + targetId)?.classList.add('active'); // Wait, let's fix string concatenation safely:
    // fix string concat:
    // document.getElementById('section-' + targetId).classList.add('active');
    // element.classList.add('active');
}

// Perbaikan fungsi switch tab agar bersih dari error spasi:
document.querySelectorAll('.fp-menu-item').forEach(function(btn, index) {
    var targets = ['tw', 'bulan', 'tanggal', 'ulp'];
    btn.onclick = function() {
        document.querySelectorAll('.fp-section-content').forEach(function(sec) {
            sec.classList.remove('active');
        });
        document.querySelectorAll('.fp-menu-item').forEach(function(b) {
            b.classList.remove('active');
        });
        document.getElementById('section-' + targets[index]).classList.add('active');
        this.classList.add('active');
    };
});

function fpFilterUlp(kata) {
    kata = kata.toLowerCase().trim();
    document.querySelectorAll('#fp-ulp-list .fp-ulp-row').forEach(function (row) {
        row.style.display = row.dataset.nama.indexOf(kata) !== -1 ? '' : 'none';
    });
}
</script>
@extends('layouts.app')
@section('title', 'Upload Excel')

@section('content')
<h2 style="margin:0 0 4px;font-size:22px;">Upload File Excel</h2>
<p style="color:#6b7690;margin:0 0 22px;font-size:14px;">Unggah data laporan dalam format Excel sesuai template PLN yang disediakan.</p>

<div style="display:grid;grid-template-columns:2.2fr 1fr;gap:20px;align-items:start;">
    {{-- Kolom kiri: form upload --}}
    <div class="card" style="margin-bottom:0;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h2"/></svg>
            </div>
            <div>
                <h3 style="margin:0;font-size:15px;">Pilih File Excel</h3>
                <span style="font-size:12.5px;color:#6b7690;">Format .xls / .xlsx · Maks. 20 MB</span>
            </div>
        </div>

        <form action="{{ route('laporan.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <label for="fileInput" id="dropzone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;
                border:2px dashed #b9c3e6;border-radius:12px;padding:60px 24px;cursor:pointer;text-align:center;
                background:#f7f9fd;transition:border-color .15s,background .15s;">
                <div style="width:56px;height:56px;border-radius:14px;background:#eaf1ff;display:flex;align-items:center;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#0b3d91" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:26px;height:26px;"><path d="M12 15V3"/><path d="m7 8 5-5 5 5"/><path d="M20 21H4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h3"/><path d="M17 13h3a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1"/></svg>
                </div>
                <strong id="dropzoneTitle" style="font-size:15px;color:#0b3d91;">Tarik file .xls/.xlsx ke sini</strong>
                <span style="font-size:13px;color:#6b7690;">atau klik untuk pilih file</span>
                <span class="badge" style="background:#eaf1ff;">Maksimal 20 MB</span>
            </label>
            <input type="file" name="file_excel" id="fileInput" accept=".xls,.xlsx" required style="display:none;">

            <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                <button type="submit" class="btn" id="submitBtn">Upload &amp; Proses</button>
            </div>
        </form>

        @if ($lastUpload)
            <div style="margin-top:18px;padding-top:16px;border-top:1px solid #e7eaf3;display:flex;align-items:center;gap:8px;font-size:12.5px;color:#6b7690;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;flex-shrink:0;"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                Upload terakhir: <strong style="color:#1b2559;">{{ $lastUpload->created_at->translatedFormat('d/m/Y') }}</strong>
                pukul <strong style="color:#1b2559;">{{ $lastUpload->created_at->format('H:i') }}</strong>
                ({{ $lastUpload->nama_file_asli }})
            </div>
        @endif
    </div>

    {{-- Kolom kanan: ketentuan format + aktivitas --}}
    <div>
        <div class="card">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#0b3d91" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:17px;height:17px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>
                <h3 style="margin:0;font-size:15px;">Ketentuan Format File</h3>
            </div>

            <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:14px;">
                @foreach ([
                    ['Format .xls atau .xlsx', 'Microsoft Excel 97-2019 / Office 365'],
                    ['Ukuran maks. 20 MB', 'Kompres data jika perlu'],
                    ['Struktur header sesuai template PLN', 'Kolom wajib: ID Unit, Periode, Nilai'],
                    ['Data tidak boleh kosong pada baris 1', 'Header harus di baris pertama'],
                    ['Gunakan titik sebagai desimal', 'Contoh: 1234.56 bukan 1234,56'],
                ] as [$judul, $ket])
                    <li style="display:flex;gap:10px;align-items:flex-start;">
                        <span style="width:18px;height:18px;border-radius:50%;border:2px solid #0b3d91;flex-shrink:0;margin-top:2px;"></span>
                        <span>
                            <strong style="display:block;font-size:13px;color:#1b2559;">{{ $judul }}</strong>
                            <span style="font-size:12px;color:#6b7690;">{{ $ket }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>

            <div style="margin-top:18px;padding-top:16px;border-top:1px solid #e7eaf3;">
                <span style="font-size:12px;color:#6b7690;">Butuh panduan?</span>
                {{-- TODO: sambungkan ke file template asli di storage --}}
                <a href="#" class="btn btn-yellow" style="width:100%;justify-content:center;margin-top:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M12 15V3"/><path d="m7 10 5 5 5-5"/><path d="M20 21H4"/></svg>
                    Download Template
                </a>
                <div style="font-size:11px;color:#9aa4c2;margin-top:8px;">Template resmi PLN · Rev. Q2/2026</div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin:0 0 14px;font-size:15px;">Aktivitas Upload Hari Ini</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <div style="font-size:22px;font-weight:800;color:#1a9c4a;">{{ $berhasilHariIni }}</div>
                    <div style="font-size:12px;color:#6b7690;">Berhasil</div>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#e0433d;">0</div>
                    <div style="font-size:12px;color:#6b7690;">Gagal</div>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#0b3d91;">0</div>
                    <div style="font-size:12px;color:#6b7690;">Diproses</div>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;color:#e0a800;">0</div>
                    <div style="font-size:12px;color:#6b7690;">Antrian</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const title = document.getElementById('dropzoneTitle');

    function showFileName(file) {
        if (!file) return;
        title.textContent = file.name;
        dropzone.style.borderColor = '#0b3d91';
        dropzone.style.background = '#eaf1ff';
    }

    fileInput.addEventListener('change', () => showFileName(fileInput.files[0]));

    ['dragover', 'dragenter'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#0b3d91';
            dropzone.style.background = '#eaf1ff';
        });
    });

    ['dragleave', 'dragend'].forEach(evt => {
        dropzone.addEventListener(evt, () => {
            if (!fileInput.files.length) {
                dropzone.style.borderColor = '#b9c3e6';
                dropzone.style.background = '#f7f9fd';
            }
        });
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            showFileName(fileInput.files[0]);
        }
    });
})();
</script>
@endpush
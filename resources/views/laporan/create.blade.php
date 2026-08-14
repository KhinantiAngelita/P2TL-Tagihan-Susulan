@extends('layouts.app')
@section('title', 'Upload Excel')

@section('content')
<style>
    .up-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .up-stat-card {
        background: #fff; border-radius: 14px; border: 1px solid #e7eaf3;
        box-shadow: 0 1px 4px rgba(11,61,145,.06);
        padding: 18px 20px; display: flex; align-items: center; gap: 14px;
    }
    .up-stat-icon {
        width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .up-stat-icon svg { width: 22px; height: 22px; }
    .up-stat-icon.green { background: #e6f7ea; color: #1a9c4a; }
    .up-stat-icon.red { background: #fdecea; color: #e0433d; }
    .up-stat-icon.blue { background: #eaf1ff; color: #0b3d91; }
    .up-stat-icon.amber { background: #fff6d6; color: #b88a00; }
    .up-stat-value { font-size: 24px; font-weight: 800; line-height: 1.1; }
    .up-stat-label { font-size: 12.5px; color: #6b7690; margin-top: 3px; }
    .up-stats-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .up-stats-eyebrow { font-size: 12px; font-weight: 800; letter-spacing: .06em; color: #8a93ad; text-transform: uppercase; }

    /* align-items: stretch membuat kedua kolom (form upload & ketentuan format)
       selalu punya tinggi yang sama, mengikuti kolom yang paling tinggi */
    .up-layout { display: grid; grid-template-columns: 2.2fr 1fr; gap: 20px; align-items: stretch; }
    .up-layout > .card { height: 100%; display: flex; flex-direction: column; margin-bottom: 0; }
    .up-card-grow { flex: 1; display: flex; flex-direction: column; }

    /* Footer/garis tipis selalu punya jarak minimal ke konten di atasnya,
       gak akan mepet lagi biarpun tinggi konten kolom kiri/kanan beda-beda */
    .up-card-footer {
        margin-top: 24px;
        padding-top: 16px;
        border-top: 1px solid #e7eaf3;
    }

    @media (max-width: 900px) {
        .up-stats { grid-template-columns: repeat(2, 1fr); }
        .up-layout { grid-template-columns: 1fr; }
        .up-layout > .card { height: auto; }
    }
</style>

<h2 style="margin:0 0 4px;font-size:22px;">Upload File Excel</h2>
<p style="color:#6b7690;margin:0 0 22px;font-size:14px;">Unggah data laporan dalam format Excel sesuai template PLN yang disediakan. Bisa pilih beberapa file sekaligus.</p>

@if (session('upload_berhasil') || session('upload_gagal'))
    <div style="margin-bottom:20px;display:flex;flex-direction:column;gap:10px;">
        @if (session('upload_berhasil'))
            <div style="background:#e6f7ea;border:1px solid #bfe8c9;border-radius:12px;padding:14px 18px;">
                <strong style="display:block;font-size:13px;color:#1a9c4a;margin-bottom:6px;">File berhasil diimport</strong>
                <ul style="margin:0;padding-left:18px;font-size:12.5px;color:#33553d;">
                    @foreach (session('upload_berhasil') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('upload_gagal'))
            <div style="background:#fdecea;border:1px solid #f5c2bd;border-radius:12px;padding:14px 18px;">
                <strong style="display:block;font-size:13px;color:#e0433d;margin-bottom:6px;">File gagal diproses</strong>
                <ul style="margin:0;padding-left:18px;font-size:12.5px;color:#7a3330;">
                    @foreach (session('upload_gagal') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

{{-- Ringkasan aktivitas upload hari ini, dipindah ke atas sebagai stat card --}}
<div class="up-stats-header">
    <span class="up-stats-eyebrow">Aktivitas Upload Hari Ini</span>
    <span class="badge" style="background:#f0f2f7;color:#4b5570;">{{ now()->translatedFormat('d F Y') }}</span>
</div>

<div class="up-stats">
    <div class="up-stat-card">
        <div class="up-stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10-3-3"/></svg>
        </div>
        <div>
            <div class="up-stat-value" style="color:#1a9c4a;">{{ $berhasilHariIni }}</div>
            <div class="up-stat-label">Berhasil Diupload</div>
        </div>
    </div>

    <div class="up-stat-card">
        <div class="up-stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
        </div>
        <div>
            <div class="up-stat-value" style="color:#e0433d;">0</div>
            <div class="up-stat-label">Gagal Diproses</div>
        </div>
    </div>

    <div class="up-stat-card">
        <div class="up-stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
        </div>
        <div>
            <div class="up-stat-value" style="color:#0b3d91;">0</div>
            <div class="up-stat-label">Sedang Diproses</div>
        </div>
    </div>

    <div class="up-stat-card">
        <div class="up-stat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div>
            <div class="up-stat-value" style="color:#b88a00;">0</div>
            <div class="up-stat-label">Dalam Antrian</div>
        </div>
    </div>
</div>

<div class="up-layout">
    {{-- Kolom kiri: form upload --}}
    <div class="card">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h2"/></svg>
            </div>
            <div>
                <h3 style="margin:0;font-size:15px;">Pilih File Excel</h3>
                <span style="font-size:12.5px;color:#6b7690;">Format .xls / .xlsx · Maks. 20 MB per file</span>
            </div>
        </div>

        <div class="up-card-grow">
            <form action="{{ route('laporan.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm" style="display:flex;flex-direction:column;flex:1;">
                @csrf
                <label for="fileInput" id="dropzone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;
                    border:2px dashed #b9c3e6;border-radius:12px;padding:60px 24px;cursor:pointer;text-align:center;
                    background:#f7f9fd;transition:border-color .15s,background .15s;flex:1;">
                    <div style="width:56px;height:56px;border-radius:14px;background:#eaf1ff;display:flex;align-items:center;justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#0b3d91" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:26px;height:26px;"><path d="M12 15V3"/><path d="m7 8 5-5 5 5"/><path d="M20 21H4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h3"/><path d="M17 13h3a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1"/></svg>
                    </div>
                    <strong id="dropzoneTitle" style="font-size:15px;color:#0b3d91;">Tarik file .xls/.xlsx ke sini</strong>
                    <span style="font-size:13px;color:#6b7690;">atau klik untuk pilih beberapa file sekaligus</span>
                    <span class="badge" style="background:#eaf1ff;">Maksimal 20 MB per file</span>
                </label>
                <input type="file" name="file_excel[]" id="fileInput" accept=".xls,.xlsx" multiple required style="display:none;">

                <ul id="fileList" style="list-style:none;margin:10px 0 0;padding:0;display:none;flex-direction:column;gap:6px;"></ul>

                <div style="display:flex;justify-content:flex-end;margin-top:20px;">
                    <button type="submit" class="btn" id="submitBtn">Upload &amp; Proses</button>
                </div>
            </form>
        </div>

        @if ($lastUpload)
            <div class="up-card-footer" style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#6b7690;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;flex-shrink:0;"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                Upload terakhir: <strong style="color:#1b2559;">{{ $lastUpload->created_at->translatedFormat('d/m/Y') }}</strong>
                pukul <strong style="color:#1b2559;">{{ $lastUpload->created_at->format('H:i') }}</strong>
                ({{ $lastUpload->nama_file_asli }})
            </div>
        @endif
    </div>

    {{-- Kolom kanan: ketentuan format --}}
    <div class="card">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#0b3d91" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:17px;height:17px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>
            <h3 style="margin:0;font-size:15px;">Ketentuan Format File</h3>
        </div>

        <ul class="up-card-grow" style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:14px;">
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

        <div class="up-card-footer">
            <span style="font-size:12px;color:#6b7690;">Butuh panduan?</span>
            <a href="{{ asset('templates/format-p2tl-kosong.xlsx') }}" download="Format_P2TL_Kosong.xlsx" class="btn btn-yellow" style="width:100%;justify-content:center;margin-top:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M12 15V3"/><path d="m7 10 5 5 5-5"/><path d="M20 21H4"/></svg>
                Download Template
            </a>
            <div style="font-size:11px;color:#9aa4c2;margin-top:8px;">Template resmi PLN · Rev. Q2/2026</div>
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
    const fileList = document.getElementById('fileList');

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function renderFiles(files) {
        fileList.innerHTML = '';

        if (!files || !files.length) {
            title.textContent = 'Tarik file .xls/.xlsx ke sini';
            dropzone.style.borderColor = '#b9c3e6';
            dropzone.style.background = '#f7f9fd';
            fileList.style.display = 'none';
            return;
        }

        title.textContent = files.length === 1
            ? files[0].name
            : files.length + ' file dipilih';

        dropzone.style.borderColor = '#0b3d91';
        dropzone.style.background = '#eaf1ff';

        fileList.style.display = 'flex';
        Array.from(files).forEach((file) => {
            const li = document.createElement('li');
            li.style.cssText = 'display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:#4b5570;background:#fff;border:1px solid #e7eaf3;border-radius:8px;padding:8px 12px;';
            li.innerHTML = `<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${file.name}</span><span style="flex-shrink:0;color:#9aa4c2;">${formatSize(file.size)}</span>`;
            fileList.appendChild(li);
        });
    }

    fileInput.addEventListener('change', () => renderFiles(fileInput.files));

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
            renderFiles(fileInput.files);
        }
    });
})();
</script>
@endpush
@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
<style>
    :root {
        --navy: #0b3d91;
        --navy-dark: #071233;
        --text-dark: #1b2559;
        --text-muted: #6b7690;
        --border: #e7eaf3;
        --bg-soft: #f7f9fd;
        --yellow: #ffd60a;
        --green: #1a9c4a;
        --green-bg: #e6f7ea;
        --red: #e0433d;
        --red-bg: #fdecea;
        --blue-bg: #eaf1ff;
    }

    .pf-header { margin-bottom: 22px; }
    .pf-header h2 { margin: 0 0 4px; font-size: 24px; font-weight: 800; color: var(--text-dark); }
    .pf-header p { margin: 0; font-size: 14px; color: var(--text-muted); }

    .pf-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 22px;
        align-items: stretch;
    }
    @media (max-width: 860px) {
        .pf-layout { grid-template-columns: 1fr; }
    }

    /* ---------- Kolom kiri: kartu ringkasan ---------- */
    .pf-summary {
        background: #fff;
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: 0 1px 4px rgba(11,61,145,.06);
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .pf-cover {
        height: 88px;
        background: linear-gradient(120deg, var(--navy) 0%, var(--navy-dark) 100%);
    }
    /* Avatar sengaja position:absolute + z-index, BUKAN margin negatif,
       supaya dijamin selalu digambar di atas .pf-cover (elemen positioned
       selalu di-paint di atas elemen statis, apapun urutan DOM-nya —
       kalau .pf-cover ikut diberi position, avatar bisa ketutup lagi). */
    .pf-avatar {
        position: absolute;
        top: 46px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2;
        width: 84px; height: 84px; border-radius: 50%;
        background: var(--yellow); color: var(--navy-dark);
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; font-weight: 800;
        border: 4px solid #fff;
    }
    .pf-summary-body {
        padding: 50px 24px 24px;
        text-align: center;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .pf-summary-body h3 { margin: 4px 0 2px; font-size: 17px; font-weight: 800; color: var(--text-dark); }
    .pf-summary-body .pf-email { font-size: 13px; color: var(--text-muted); word-break: break-all; }

    .pf-badges { display: flex; justify-content: center; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
    .pf-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 700;
        background: var(--blue-bg); color: var(--navy);
    }
    .pf-badge.status-aktif { background: var(--green-bg); color: #17643a; }
    .pf-badge.status-nonaktif { background: var(--red-bg); color: #9d2b1f; }
    .pf-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    .pf-summary-divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }

    .pf-meta-list { text-align: left; display: flex; flex-direction: column; gap: 14px; }
    .pf-meta-item { display: flex; align-items: center; gap: 12px; }
    .pf-meta-icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        background: var(--bg-soft); color: var(--navy);
        display: flex; align-items: center; justify-content: center;
    }
    .pf-meta-icon svg { width: 16px; height: 16px; }
    .pf-meta-item .pf-meta-label { font-size: 11.5px; color: var(--text-muted); display: block; }
    .pf-meta-item .pf-meta-value { font-size: 13.5px; color: var(--text-dark); font-weight: 700; display: block; margin-top: 1px; }

    /* Kotak tips ini flex:1 supaya otomatis ngisi sisa tinggi card kiri,
       jadi card kiri & kanan selalu sama panjang berapa pun isi masing-masing. */
    .pf-tips-box {
        flex: 1;
        margin-top: 20px;
        background: var(--bg-soft);
        border-radius: 12px;
        padding: 16px 16px 4px;
        text-align: left;
        display: flex;
        flex-direction: column;
    }
    .pf-tips-title { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    .pf-tips-title .pf-tips-icon {
        width: 26px; height: 26px; border-radius: 7px; flex-shrink: 0;
        background: var(--blue-bg); color: var(--navy);
        display: flex; align-items: center; justify-content: center;
    }
    .pf-tips-title .pf-tips-icon svg { width: 13px; height: 13px; }
    .pf-tips-title span { font-size: 12.5px; font-weight: 800; color: var(--text-dark); }
    .pf-tips-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
    .pf-tips-list li { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; line-height: 1.5; color: var(--text-muted); }
    .pf-tips-list li svg { width: 14px; height: 14px; color: var(--green); flex-shrink: 0; margin-top: 1px; }

    /* ---------- Kolom kanan: form ---------- */
    .pf-form-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: 0 1px 4px rgba(11,61,145,.06);
        padding: 28px 30px 26px;
    }

    .pf-section-title { display: flex; align-items: center; gap: 10px; margin: 0 0 4px; }
    .pf-section-title .pf-section-icon {
        width: 32px; height: 32px; border-radius: 9px;
        background: var(--blue-bg); color: var(--navy);
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .pf-section-title .pf-section-icon svg { width: 16px; height: 16px; }
    .pf-section-title h4 { margin: 0; font-size: 15px; font-weight: 800; color: var(--text-dark); }
    .pf-section-desc { margin: 4px 0 20px 42px; font-size: 12.5px; color: var(--text-muted); }

    .pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    @media (max-width: 560px) { .pf-grid { grid-template-columns: 1fr; } }

    .pf-field { margin-bottom: 18px; }
    .pf-field label { display: block; font-size: 13px; font-weight: 700; color: var(--text-dark); margin-bottom: 7px; }
    .pf-input-wrap { position: relative; }
    .pf-input-wrap svg {
        position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
        width: 16px; height: 16px; color: #9aa4c2;
    }
    .pf-field input {
        width: 100%; background: var(--bg-soft); border: 1px solid var(--border); border-radius: 10px;
        padding: 11px 14px 11px 40px; font-size: 13.5px; color: var(--text-dark); outline: none;
        font-family: inherit;
        transition: border-color .15s, background .15s;
    }
    .pf-field input:focus { border-color: var(--navy); background: #fff; }
    .pf-field small.pf-error { display: block; margin-top: 6px; font-size: 12px; color: var(--red); }

    .pf-divider-full { border: none; border-top: 1px solid var(--border); margin: 8px 0 24px; }

    .pf-form-footer {
        display: flex; align-items: center; justify-content: flex-end;
        gap: 12px; margin-top: 6px; padding-top: 20px; border-top: 1px solid var(--border);
    }
    .pf-btn-save {
        background: var(--yellow); color: var(--navy-dark);
        border: none; border-radius: 10px; padding: 11px 24px;
        font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit;
    }
    .pf-btn-save:hover { background: #e6c200; }
</style>

<div class="pf-header">
    <h2>Profil Saya</h2>
    <p>Kelola informasi dan keamanan akun Anda.</p>
</div>

@if (session('success'))
    <div class="alert-success" style="margin-bottom:18px;">{{ session('success') }}</div>
@endif

<div class="pf-layout">
    {{-- Kolom kiri: ringkasan profil --}}
    <div class="pf-summary">
        <div class="pf-cover"></div>
        <div class="pf-avatar">
            {{ strtoupper(collect(explode(' ', $user->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('')) }}
        </div>
        <div class="pf-summary-body">
            <h3>{{ $user->name }}</h3>
            <div class="pf-email">{{ $user->email }}</div>

            <div class="pf-badges">
                <span class="pf-badge">{{ $user->isSuperAdmin() ? 'Super Admin' : 'Pengguna' }}</span>
                <span class="pf-badge {{ $user->is_active ? 'status-aktif' : 'status-nonaktif' }}">
                    <span class="dot"></span>
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <hr class="pf-summary-divider">

            <div class="pf-meta-list">
                <div class="pf-meta-item">
                    <div class="pf-meta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <div>
                        <span class="pf-meta-label">Bergabung Sejak</span>
                        <span class="pf-meta-value">{{ $user->created_at->translatedFormat('d F Y') }}</span>
                    </div>
                </div>
                <div class="pf-meta-item">
                    <div class="pf-meta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <span class="pf-meta-label">Role</span>
                        <span class="pf-meta-value">{{ $user->isSuperAdmin() ? 'Super Admin' : 'Pengguna' }}</span>
                    </div>
                </div>
            </div>

            <div class="pf-tips-box">
                <div class="pf-tips-title">
                    <div class="pf-tips-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v6c0 5 4 8.5 9 9 5-.5 9-4 9-9V7l-9-5Z"/></svg>
                    </div>
                    <span>Tips Keamanan Akun</span>
                </div>
                <ul class="pf-tips-list">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Gunakan password minimal 8 karakter, kombinasi huruf dan angka.
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Jangan bagikan password ke siapa pun, termasuk pihak admin.
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Ganti password secara berkala untuk menjaga keamanan akun.
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Kolom kanan: form edit --}}
    <div class="pf-form-card">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="pf-section-title">
                <div class="pf-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <h4>Informasi Akun</h4>
            </div>
            <p class="pf-section-desc">Nama dan email yang tampil di seluruh sistem.</p>

            <div class="pf-grid">
                <div class="pf-field">
                    <label for="name">Nama Lengkap</label>
                    <div class="pf-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                    @error('name') <small class="pf-error">{{ $message }}</small> @enderror
                </div>
                <div class="pf-field">
                    <label for="email">Email</label>
                    <div class="pf-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                    @error('email') <small class="pf-error">{{ $message }}</small> @enderror
                </div>
            </div>

            <hr class="pf-divider-full">

            <div class="pf-section-title">
                <div class="pf-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h4>Ubah Password</h4>
            </div>
            <p class="pf-section-desc">Kosongkan bagian ini kalau tidak ingin mengganti password.</p>

            <div class="pf-field">
                <label for="current_password">Password Saat Ini</label>
                <div class="pf-input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" id="current_password" name="current_password" placeholder="Masukkan password saat ini">
                </div>
                @error('current_password') <small class="pf-error">{{ $message }}</small> @enderror
            </div>

            <div class="pf-grid">
                <div class="pf-field">
                    <label for="password">Password Baru</label>
                    <div class="pf-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" placeholder="Min. 8 karakter">
                    </div>
                    @error('password') <small class="pf-error">{{ $message }}</small> @enderror
                </div>
                <div class="pf-field">
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                    <div class="pf-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru">
                    </div>
                </div>
            </div>

            <div class="pf-form-footer">
                <button type="submit" class="pf-btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
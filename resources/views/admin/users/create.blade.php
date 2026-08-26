@extends('layouts.app')
@section('title', 'Tambah User')

@section('content')
<style>
    .cu-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 26px; gap: 16px; flex-wrap: wrap; }
    .cu-header h2 { margin: 0 0 4px; font-size: 26px; font-weight: 800; color: #1b2559; letter-spacing: -0.3px; }
    .cu-header p { margin: 0; color: #6b7690; font-size: 14px; }

    .cu-back-link {
        display: inline-flex; align-items: center; gap: 6px;
        color: #6b7690; font-size: 13px; font-weight: 600; text-decoration: none;
        margin-bottom: 16px; transition: gap .15s ease, color .15s ease;
    }
    .cu-back-link:hover { color: #0b3d91; gap: 9px; }
    .cu-back-link svg { width: 14px; height: 14px; }

    .cu-card {
        width: 100%;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 1px 2px rgba(16,24,64,.04), 0 12px 28px -12px rgba(16,24,64,.14);
        border: 1px solid #eef0f7;
        overflow: hidden;
        animation: cu-fade-up .35s ease;
    }
    @keyframes cu-fade-up {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .cu-card-head {
        display: flex; align-items: center; gap: 14px; padding: 22px 26px;
        border-bottom: 1px solid #eceef4;
        background: linear-gradient(180deg, #f8fafd 0%, #ffffff 100%);
    }
    .cu-card-head-icon {
        width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #0b3d91 0%, #1a56c4 100%);
        color: #fff;
        box-shadow: 0 6px 14px -4px rgba(11,61,145,.45);
    }
    .cu-card-head-icon svg { width: 20px; height: 20px; }
    .cu-card-head h3 { margin: 0; font-size: 15.5px; color: #1b2559; font-weight: 800; }
    .cu-card-head p { margin: 3px 0 0; font-size: 12px; color: #8892a8; }

    .cu-body { padding: 28px 32px; }

    .cu-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 20px; }

    .cu-field { margin-bottom: 20px; }
    .cu-field label { display: block; font-size: 13px; font-weight: 700; color: #1b2559; margin-bottom: 7px; }

    .cu-input-wrap { position: relative; display: flex; align-items: center; }
    .cu-input-wrap svg.leading-icon {
        position: absolute; left: 13px; width: 16px; height: 16px; color: #9aa4c2; pointer-events: none;
        transition: color .15s ease;
    }
    .cu-input-wrap input, .cu-input-wrap select {
        width: 100%; box-sizing: border-box;
        border: 1.5px solid #e3e6ee; border-radius: 11px;
        padding: 11px 14px 11px 38px; font-size: 13.5px; color: #1b2559;
        background: #f7f9fd; appearance: none; font-family: inherit;
        transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }
    .cu-input-wrap select { padding-right: 34px; cursor: pointer; }
    .cu-input-wrap input::placeholder { color: #aab2c8; }
    .cu-input-wrap input:hover, .cu-input-wrap select:hover { border-color: #c9cee0; }
    .cu-input-wrap input:focus, .cu-input-wrap select:focus {
        outline: none; border-color: #0b3d91; background: #fff; box-shadow: 0 0 0 4px rgba(11,61,145,.1);
    }
    .cu-input-wrap input:focus ~ svg.leading-icon,
    .cu-field:has(input:focus) svg.leading-icon { color: #0b3d91; }
    .cu-input-wrap .toggle-eye {
        position: absolute; right: 12px; background: none; border: none; cursor: pointer;
        color: #9aa4c2; display: flex; align-items: center; padding: 4px; border-radius: 6px;
        transition: color .15s ease, background .15s ease;
    }
    .cu-input-wrap .toggle-eye:hover { color: #0b3d91; background: #eaf0fb; }
    .cu-input-wrap .toggle-eye svg { width: 16px; height: 16px; }
    .cu-input-wrap .chevron { position: absolute; right: 13px; width: 11px; height: 11px; color: #9aa4c2; pointer-events: none; }

    .cu-field small.error {
        display: flex; align-items: center; gap: 5px;
        margin-top: 7px; font-size: 12px; color: #dc2626; font-weight: 600;
    }
    .cu-field .hint { display: block; margin-top: 7px; font-size: 11.5px; color: #9aa4c2; line-height: 1.5; }

    .cu-actions {
        display: flex; align-items: center; justify-content: flex-end; gap: 10px;
        padding-top: 8px; margin-top: 4px; border-top: 1px solid #f1f2f8;
        padding-top: 20px;
    }
    .cu-btn-cancel {
        border: 1.5px solid #e3e6ee; background: #fff; color: #6b7690;
        font-size: 13.5px; font-weight: 700; padding: 11px 22px; border-radius: 11px;
        cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
        transition: background .15s ease, border-color .15s ease;
    }
    .cu-btn-cancel:hover { background: #f7f8fc; border-color: #d3d7e4; }
    .cu-btn-submit {
        background: linear-gradient(135deg, #0b3d91 0%, #1450b4 100%);
        color: #fff; border: none; border-radius: 11px;
        padding: 11px 26px; font-size: 13.5px; font-weight: 800; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px;
        box-shadow: 0 8px 16px -6px rgba(11,61,145,.5);
        transition: transform .12s ease, box-shadow .12s ease, background .15s ease;
    }
    .cu-btn-submit:hover { background: linear-gradient(135deg, #092f70 0%, #0f3f92 100%); transform: translateY(-1px); box-shadow: 0 10px 20px -6px rgba(11,61,145,.55); }
    .cu-btn-submit:active { transform: translateY(0); }
    .cu-btn-submit svg { width: 15px; height: 15px; }

    .cu-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 700; color: #b45309;
        background: #fef3e2; border: 1px solid #fde3b8;
        padding: 4px 10px; border-radius: 999px; margin-top: 2px;
    }
    .cu-badge svg { width: 11px; height: 11px; }

    /* Responsive */
    @media (max-width: 560px) {
        .cu-body { padding: 20px; }
        .cu-card-head { padding: 18px 20px; }
        .cu-row { grid-template-columns: 1fr; gap: 0; }
        .cu-actions { flex-direction: column-reverse; align-items: stretch; }
        .cu-btn-submit, .cu-btn-cancel { justify-content: center; width: 100%; }
    }
</style>

<a href="{{ route('admin.users.index') }}" class="cu-back-link">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Kembali ke Manajemen User
</a>

<div class="cu-header">
    <div>
        <h2>Tambah User</h2>
        <p>Buat akun baru untuk pengguna sistem laporan P2TL</p>
    </div>
</div>

<div class="cu-card">
    <div class="cu-card-head">
        <div class="cu-card-head-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
        </div>
        <div>
            <h3>Data Akun Baru</h3>
            <p>Hanya Super Admin yang bisa membuat akun</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="cu-body">
        @csrf

        <div class="cu-row">
        <div class="cu-field">
            <label for="name">Nama Lengkap</label>
            <div class="cu-input-wrap">
                <svg class="leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" id="name" name="name" placeholder="Nama pengguna" value="{{ old('name') }}" required autofocus>
            </div>
            @error('name')
                <small class="error">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    {{ $message }}
                </small>
            @enderror
        </div>

        <div class="cu-field">
            <label for="email">Email</label>
            <div class="cu-input-wrap">
                <svg class="leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                <input type="email" id="email" name="email" placeholder="nama@pln.co.id" value="{{ old('email') }}" required>
            </div>
            @error('email')
                <small class="error">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    {{ $message }}
                </small>
            @enderror
        </div>
        </div>

        <div class="cu-row">
        <div class="cu-field">
            <label for="password">Password</label>
            <div class="cu-input-wrap">
                <svg class="leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required>
                <button type="button" class="toggle-eye" onclick="cu_toggle('password')" aria-label="Tampilkan password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password')
                <small class="error">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    {{ $message }}
                </small>
            @enderror
        </div>

        <div class="cu-field">
            <label for="password_confirmation">Konfirmasi Password</label>
            <div class="cu-input-wrap">
                <svg class="leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password" required>
                <button type="button" class="toggle-eye" onclick="cu_toggle('password_confirmation')" aria-label="Tampilkan password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
        </div>

        <div class="cu-field">
            <label for="role">Peran (Role)</label>
            <div class="cu-input-wrap">
                <svg class="leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><path d="M12 3a9 9 0 1 0 9 9"/></svg>
                <select id="role" name="role" required>
                    <option value="user" {{ old('role', 'user') === 'user' ? 'selected' : '' }}>Pengguna Biasa</option>
                    <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                </select>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </div>
            @error('role')
                <small class="error">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    {{ $message }}
                </small>
            @enderror
            <span class="hint">Super Admin bisa mengelola user lain &amp; fitur admin lainnya.</span>
        </div>

        <div class="cu-actions">
            <a href="{{ route('admin.users.index') }}" class="cu-btn-cancel">Batal</a>
            <button type="submit" class="cu-btn-submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Buat Akun
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function cu_toggle(id) {
    var input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
@endpush
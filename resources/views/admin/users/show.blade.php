@extends('layouts.app')
@section('title', 'Detail User')

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

    .ud-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; flex-wrap: wrap; }
    .ud-header h2 { margin: 0 0 4px; font-size: 24px; font-weight: 800; color: var(--text-dark); }
    .ud-header p { margin: 0; font-size: 14px; color: var(--text-muted); }

    .ud-back {
        display: inline-flex; align-items: center; gap: 8px;
        background: #fff; color: var(--text-dark); border: 1px solid var(--border);
        padding: 10px 16px; border-radius: 10px; text-decoration: none;
        font-size: 13.5px; font-weight: 700; transition: background .15s, border-color .15s;
    }
    .ud-back:hover { background: var(--bg-soft); border-color: #cfd6ea; }
    .ud-back svg { width: 16px; height: 16px; }

    .ud-layout { display: grid; grid-template-columns: 300px 1fr; gap: 22px; align-items: stretch; }
    @media (max-width: 860px) { .ud-layout { grid-template-columns: 1fr; } }

    /* ---------- Kolom kiri: kartu ringkasan ---------- */
    .ud-summary {
        background: #fff; border-radius: 14px; border: 1px solid var(--border);
        box-shadow: 0 1px 4px rgba(11,61,145,.06); overflow: hidden; position: relative;
        display: flex; flex-direction: column; height: 100%;
    }
    .ud-cover { height: 88px; background: linear-gradient(120deg, var(--navy) 0%, var(--navy-dark) 100%); }
    .ud-avatar {
        position: absolute; top: 46px; left: 50%; transform: translateX(-50%); z-index: 2;
        width: 84px; height: 84px; border-radius: 50%;
        background: var(--yellow); color: var(--navy-dark);
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; font-weight: 800; border: 4px solid #fff;
    }
    .ud-summary-body { padding: 50px 24px 24px; text-align: center; display: flex; flex-direction: column; flex: 1; }
    .ud-summary-body h3 { margin: 4px 0 2px; font-size: 17px; font-weight: 800; color: var(--text-dark); }
    .ud-email { font-size: 13px; color: var(--text-muted); word-break: break-all; }

    .ud-badges { display: flex; justify-content: center; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
    .ud-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 700; background: var(--blue-bg); color: var(--navy);
    }
    .ud-badge.status-aktif { background: var(--green-bg); color: #17643a; }
    .ud-badge.status-nonaktif { background: var(--red-bg); color: #9d2b1f; }
    .ud-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    .ud-summary-divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }

    .ud-meta-list { text-align: left; display: flex; flex-direction: column; gap: 14px; flex: 1; }
    .ud-meta-item { display: flex; align-items: center; gap: 12px; }
    .ud-meta-icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        background: var(--bg-soft); color: var(--navy);
        display: flex; align-items: center; justify-content: center;
    }
    .ud-meta-icon svg { width: 16px; height: 16px; }
    .ud-meta-item .ud-meta-label { font-size: 11.5px; color: var(--text-muted); display: block; }
    .ud-meta-item .ud-meta-value { font-size: 13.5px; color: var(--text-dark); font-weight: 700; display: block; margin-top: 1px; }

    /* ---------- Kolom kanan ---------- */
    .ud-form-card {
        background: #fff; border-radius: 14px; border: 1px solid var(--border);
        box-shadow: 0 1px 4px rgba(11,61,145,.06); padding: 28px 30px 26px;
    }

    .ud-section-title { display: flex; align-items: center; gap: 10px; margin: 0 0 4px; }
    .ud-section-icon {
        width: 32px; height: 32px; border-radius: 9px; background: var(--blue-bg); color: var(--navy);
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ud-section-icon svg { width: 16px; height: 16px; }
    .ud-section-title h4 { margin: 0; font-size: 15px; font-weight: 800; color: var(--text-dark); }
    .ud-section-desc { margin: 4px 0 20px 42px; font-size: 12.5px; color: var(--text-muted); }

    /* Status banner (versi lebih rapi, warna disamakan dengan token desain) */
    .ud-status-banner {
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
        padding: 18px 20px; border-radius: 12px; margin-bottom: 26px; flex-wrap: wrap;
    }
    .ud-status-banner.aktif { background: var(--green-bg); }
    .ud-status-banner.nonaktif { background: var(--red-bg); }
    .ud-status-label { display: flex; align-items: center; gap: 12px; }
    .ud-status-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .ud-status-dot.aktif { background: var(--green); box-shadow: 0 0 0 4px rgba(26,156,74,.15); }
    .ud-status-dot.nonaktif { background: var(--red); box-shadow: 0 0 0 4px rgba(224,67,61,.15); }
    .ud-status-text strong { display: block; font-size: 14.5px; }
    .ud-status-text.aktif strong { color: #17643a; }
    .ud-status-text.nonaktif strong { color: #9d2b1f; }
    .ud-status-text span { font-size: 12.5px; color: var(--text-muted); }

    .ud-switch { position: relative; display: inline-block; width: 46px; height: 26px; flex-shrink: 0; }
    .ud-switch input { opacity: 0; width: 0; height: 0; }
    .ud-switch .slider {
        position: absolute; cursor: pointer; inset: 0; background: var(--red);
        transition: .2s; border-radius: 999px;
    }
    .ud-switch .slider::before {
        content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px;
        background: #fff; transition: .2s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.25);
    }
    .ud-switch input:checked + .slider { background: var(--green); }
    .ud-switch input:checked + .slider::before { transform: translateX(20px); }

    .ud-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    @media (max-width: 560px) { .ud-info-grid { grid-template-columns: 1fr; } }

    .ud-info-field { background: var(--bg-soft); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; }
    .ud-info-field .ud-info-label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; }
    .ud-info-field .ud-info-value { display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: var(--text-dark); font-weight: 700; }
    .ud-info-field .ud-info-value svg { width: 15px; height: 15px; color: #9aa4c2; flex-shrink: 0; }
</style>

<div class="ud-header">
    <div>
        <h2>Detail User</h2>
        <p>Lihat informasi dan kelola status akun pengguna.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="ud-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Daftar User
    </a>
</div>

<div class="ud-layout">
    {{-- Kolom kiri: ringkasan user --}}
    <div class="ud-summary">
        <div class="ud-cover"></div>
        <div class="ud-avatar">
            {{ strtoupper(collect(explode(' ', $user->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('')) }}
        </div>
        <div class="ud-summary-body">
            <h3>{{ $user->name }}</h3>
            <div class="ud-email">{{ $user->email }}</div>

            <div class="ud-badges">
                <span class="ud-badge">{{ $user->isSuperAdmin() ? 'Super Admin' : 'Pengguna' }}</span>
                <span class="ud-badge {{ $user->is_active ? 'status-aktif' : 'status-nonaktif' }}">
                    <span class="dot"></span>
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <hr class="ud-summary-divider">

            <div class="ud-meta-list">
                <div class="ud-meta-item">
                    <div class="ud-meta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <div>
                        <span class="ud-meta-label">Terdaftar Sejak</span>
                        <span class="ud-meta-value">{{ $user->created_at->translatedFormat('d F Y, H:i') }}</span>
                    </div>
                </div>
                <div class="ud-meta-item">
                    <div class="ud-meta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <span class="ud-meta-label">Role</span>
                        <span class="ud-meta-value">{{ $user->isSuperAdmin() ? 'Super Admin' : 'Pengguna' }}</span>
                    </div>
                </div>
                @if ($user->status_changed_at)
                    <div class="ud-meta-item">
                        <div class="ud-meta-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                        </div>
                        <div>
                            <span class="ud-meta-label">{{ $user->is_active ? 'Diaktifkan Pada' : 'Dinonaktifkan Pada' }}</span>
                            <span class="ud-meta-value">{{ $user->status_changed_at->translatedFormat('d F Y, H:i') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Kolom kanan: status & info akun --}}
    <div class="ud-form-card">
        <div class="ud-section-title">
            <div class="ud-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            </div>
            <h4>Status Akun</h4>
        </div>
        <p class="ud-section-desc">Aktifkan atau nonaktifkan akses login pengguna ini.</p>

        <div class="ud-status-banner {{ $user->is_active ? 'aktif' : 'nonaktif' }}">
            <div class="ud-status-label">
                <span class="ud-status-dot {{ $user->is_active ? 'aktif' : 'nonaktif' }}"></span>
                <div class="ud-status-text {{ $user->is_active ? 'aktif' : 'nonaktif' }}">
                    <strong>{{ $user->is_active ? 'Akun Aktif' : 'Akun Nonaktif' }}</strong>
                    <span>
                        @if ($user->status_changed_at)
                            {{ $user->is_active ? 'Diaktifkan' : 'Dinonaktifkan' }} pada {{ $user->status_changed_at->translatedFormat('d F Y, H:i') }}
                        @else
                            Belum pernah diubah statusnya
                        @endif
                    </span>
                </div>
            </div>

            <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST"
                  onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $user->name }}?')">
                @csrf
                @method('PATCH')
                <label class="ud-switch" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun">
                    <input type="checkbox" {{ $user->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                    <span class="slider"></span>
                </label>
            </form>
        </div>

        <div class="ud-section-title">
            <div class="ud-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <h4>Informasi Akun</h4>
        </div>
        <p class="ud-section-desc">Data akun pengguna ini (tidak bisa diubah dari halaman ini).</p>

        <div class="ud-info-grid">
            <div class="ud-info-field">
                <span class="ud-info-label">Nama Lengkap</span>
                <div class="ud-info-value">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    {{ $user->name }}
                </div>
            </div>
            <div class="ud-info-field">
                <span class="ud-info-label">Email</span>
                <div class="ud-info-value">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                    {{ $user->email }}
                </div>
            </div>
            <div class="ud-info-field">
                <span class="ud-info-label">Role</span>
                <div class="ud-info-value">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    {{ $user->isSuperAdmin() ? 'Super Admin' : 'Pengguna' }}
                </div>
            </div>
            <div class="ud-info-field">
                <span class="ud-info-label">Terdaftar Sejak</span>
                <div class="ud-info-value">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    {{ $user->created_at->translatedFormat('d F Y, H:i') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
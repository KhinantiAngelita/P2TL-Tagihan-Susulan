@extends('layouts.app')
@section('title', 'Detail User')
@section('content')
<style>
    .user-detail-card { max-width: 640px; }
    .status-banner {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-radius: 12px; margin-bottom: 24px;
        transition: background .2s;
    }
    .status-banner.aktif { background: #e6f7ea; }
    .status-banner.nonaktif { background: #fdecea; }
    .status-banner-label { display: flex; align-items: center; gap: 10px; }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .status-dot.aktif { background: #1a9c4a; box-shadow: 0 0 0 4px rgba(26,156,74,.15); }
    .status-dot.nonaktif { background: #e0433d; box-shadow: 0 0 0 4px rgba(224,67,61,.15); }
    .status-banner-text strong { display: block; font-size: 14px; }
    .status-banner-text.aktif strong { color: #17643a; }
    .status-banner-text.nonaktif strong { color: #9d2b1f; }
    .status-banner-text span { font-size: 12px; color: #6b7690; }

    /* Toggle switch */
    .switch { position: relative; display: inline-block; width: 46px; height: 26px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider {
        position: absolute; cursor: pointer; inset: 0;
        background: #d6493f; transition: .2s; border-radius: 999px;
    }
    .switch .slider::before {
        content: ""; position: absolute; height: 20px; width: 20px;
        left: 3px; bottom: 3px; background: #fff; transition: .2s; border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,.25);
    }
    .switch input:checked + .slider { background: #1a9c4a; }
    .switch input:checked + .slider::before { transform: translateX(20px); }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .info-item span.label { font-size: 12px; color: #6b7690; display: block; margin-bottom: 4px; }
    .info-item strong { font-size: 14px; color: #1b2559; }
</style>

<div class="card user-detail-card">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px">
        <div class="user-avatar" style="width:52px;height:52px;font-size:18px">
            {{ strtoupper(collect(explode(' ', $user->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('')) }}
        </div>
        <div>
            <h2 style="margin:0 0 2px;font-size:20px">{{ $user->name }}</h2>
            <span style="color:#6b7690;font-size:14px">{{ $user->email }}</span>
        </div>
    </div>

    {{-- Status banner + toggle --}}
    <div class="status-banner {{ $user->is_active ? 'aktif' : 'nonaktif' }}">
        <div class="status-banner-label">
            <span class="status-dot {{ $user->is_active ? 'aktif' : 'nonaktif' }}"></span>
            <div class="status-banner-text {{ $user->is_active ? 'aktif' : 'nonaktif' }}">
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
            <label class="switch" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun">
                <input type="checkbox" {{ $user->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                <span class="slider"></span>
            </label>
        </form>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="label">Role</span>
            <strong>{{ $user->isSuperAdmin() ? 'Super Admin' : 'Pengguna' }}</strong>
        </div>
        <div class="info-item">
            <span class="label">Terdaftar Sejak</span>
            <strong>{{ $user->created_at->translatedFormat('d F Y, H:i') }}</strong>
        </div>
    </div>

    <div style="margin-top:28px">
        <a href="{{ route('admin.users.index') }}" class="btn" style="background:#eee;color:#333">&larr; Kembali ke Daftar User</a>
    </div>
</div>
@endsection
@extends('layouts.app')
@section('title', 'Manajemen User')
@section('content')
<style>
    .um-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 22px; gap: 16px; flex-wrap: wrap; }
    .um-header h2 { margin: 0 0 4px; font-size: 24px; }
    .um-header p { margin: 0; color: #6b7690; font-size: 14px; }

    .um-btn-tambah {
        background: #0b3d91; color: #fff; border: none; border-radius: 10px;
        padding: 11px 20px; font-size: 13.5px; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
        white-space: nowrap; transition: background .15s; flex-shrink: 0;
    }
    .um-btn-tambah:hover { background: #092f70; }
    .um-btn-tambah svg { width: 16px; height: 16px; }

    .um-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
    .um-stat-card {
        background: #fff; border-radius: 12px; padding: 18px 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
        display: flex; justify-content: space-between; align-items: center;
    }
    .um-stat-card .label { font-size: 12px; font-weight: 700; color: #8a93ad; letter-spacing: .04em; }
    .um-stat-card .value { font-size: 30px; font-weight: 800; margin-top: 4px; }
    .um-stat-card.total .value { color: #1b2559; }
    .um-stat-card.aktif .value { color: #1a9c4a; }
    .um-stat-card.nonaktif .value { color: #e0433d; }
    .um-stat-dot { width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .um-stat-dot svg { width: 20px; height: 20px; }
    .um-stat-card.total .um-stat-dot { background: #eceef4; color: #4b5570; }
    .um-stat-card.aktif .um-stat-dot { background: #e6f7ea; color: #1a9c4a; }
    .um-stat-card.nonaktif .um-stat-dot { background: #fdecea; color: #e0433d; }

    .um-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 16px; }
    .um-search { position: relative; flex: 1; max-width: 360px; }
    .um-search svg {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        width: 16px; height: 16px; color: #9aa4c2;
    }
    .um-search input {
        width: 100%; padding: 10px 14px 10px 38px; border-radius: 10px;
        border: 1px solid #e3e6ee; background: #f7f9fd; font-family: inherit; font-size: 14px;
    }
    .um-count { font-size: 13px; color: #6b7690; white-space: nowrap; }

    .um-filter-tabs { display: flex; gap: 8px; margin-bottom: 16px; }
    .um-filter-tab {
        padding: 7px 16px; border-radius: 8px; text-decoration: none;
        font-size: 13px; font-weight: 700; color: #4b5570; background: #f0f2f7;
    }
    .um-filter-tab.active { background: #0b3d91; color: #fff; }

    .um-avatar {
        width: 38px; height: 38px; border-radius: 50%; background: #0b3d91; color: #fff;
        display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;
        flex-shrink: 0;
    }
    .um-name-cell { display: flex; align-items: center; gap: 12px; }
    .um-name-cell strong { display: block; font-size: 14px; color: #1b2559; }
    .um-name-cell span { font-size: 12px; color: #9aa4c2; }

    .role-pill {
        display: inline-flex; align-items: center;
        padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700;
        background: #eaf0fb; color: #0b3d91;
    }
    .role-pill.super { background: #fdf1e6; color: #b45309; }

    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
    }
    .status-pill.aktif { background: #e6f7ea; color: #17643a; }
    .status-pill.nonaktif { background: #fdecea; color: #9d2b1f; }
    .status-pill .dot { width: 7px; height: 7px; border-radius: 50%; }
    .status-pill.aktif .dot { background: #1a9c4a; }
    .status-pill.nonaktif .dot { background: #e0433d; }

    .switch-sm { position: relative; display: inline-block; width: 38px; height: 22px; flex-shrink: 0; vertical-align: middle; }
    .switch-sm input { opacity: 0; width: 0; height: 0; }
    .switch-sm .slider {
        position: absolute; cursor: pointer; inset: 0;
        background: #d6493f; transition: .2s; border-radius: 999px;
    }
    .switch-sm .slider::before {
        content: ""; position: absolute; height: 16px; width: 16px;
        left: 3px; bottom: 3px; background: #fff; transition: .2s; border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,.25);
    }
    .switch-sm input:checked + .slider { background: #1a9c4a; }
    .switch-sm input:checked + .slider::before { transform: translateX(16px); }

    .um-status-cell { display: flex; align-items: center; gap: 10px; }

    .um-view-btn {
        width: 32px; height: 32px; border-radius: 8px; background: #eaf1ff;
        display: inline-flex; align-items: center; justify-content: center;
        color: #0b3d91; text-decoration: none;
    }
    .um-view-btn svg { width: 16px; height: 16px; }

    .um-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; font-size: 13px; color: #6b7690; }

    @media (max-width: 640px) {
        .um-header { flex-direction: column; align-items: stretch; }
        .um-btn-tambah { justify-content: center; }
    }
</style>

<div class="um-header">
    <div>
        <h2>Manajemen User</h2>
        <p>Kelola status akun pengguna yang terdaftar di sistem</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="um-btn-tambah">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
        Tambah User
    </a>
</div>

<div class="um-stats">
    <div class="um-stat-card total">
        <div><div class="label">TOTAL USER</div><div class="value">{{ $totalUser }}</div></div>
        <div class="um-stat-dot">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
    </div>
    <div class="um-stat-card aktif">
        <div><div class="label">AKTIF</div><div class="value">{{ $totalAktif }}</div></div>
        <div class="um-stat-dot">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
    </div>
    <div class="um-stat-card nonaktif">
        <div><div class="label">NONAKTIF</div><div class="value">{{ $totalNonaktif }}</div></div>
        <div class="um-stat-dot">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </div>
    </div>
</div>

<div class="card">
    <div class="um-toolbar">
        <form method="GET" action="{{ route('admin.users.index') }}" class="um-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau email..." onchange="this.form.submit()">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
        </form>
        <span class="um-count">{{ $users->total() }} pengguna</span>
    </div>

    <div class="um-filter-tabs">
        <a href="{{ route('admin.users.index', array_filter(['q' => $search])) }}" class="um-filter-tab {{ !$status ? 'active' : '' }}">Semua</a>
        <a href="{{ route('admin.users.index', array_filter(['q' => $search, 'status' => 'aktif'])) }}" class="um-filter-tab {{ $status === 'aktif' ? 'active' : '' }}">Aktif</a>
        <a href="{{ route('admin.users.index', array_filter(['q' => $search, 'status' => 'nonaktif'])) }}" class="um-filter-tab {{ $status === 'nonaktif' ? 'active' : '' }}">Nonaktif</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($users as $u)
            <tr>
                <td>{{ $users->firstItem() + $loop->index }}</td>
                <td>
                    <div class="um-name-cell">
                        <div class="um-avatar">{{ strtoupper(collect(explode(' ', $u->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('')) }}</div>
                        <div>
                            <strong>{{ $u->name }}</strong>
                            <span>ID-{{ str_pad($u->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </td>
                <td>{{ $u->email }}</td>
                <td>
                    <span class="role-pill {{ $u->isSuperAdmin() ? 'super' : '' }}">
                        {{ $u->isSuperAdmin() ? 'Super Admin' : 'Pengguna' }}
                    </span>
                </td>
                <td>
                    <div class="um-status-cell">
                        @if ($u->id !== auth()->id())
                            <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST"
                                  onsubmit="return confirm('{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $u->name }}?')">
                                @csrf
                                @method('PATCH')
                                <label class="switch-sm" title="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <input type="checkbox" {{ $u->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                    <span class="slider"></span>
                                </label>
                            </form>
                        @endif
                        <span class="status-pill {{ $u->is_active ? 'aktif' : 'nonaktif' }}">
                            <span class="dot"></span>
                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </td>
                <td>
                    <a href="{{ route('admin.users.show', $u->id) }}" class="um-view-btn" title="Lihat Detail">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#667">Tidak ada user yang cocok.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="um-footer">
        <span>Menampilkan {{ $users->count() }} dari {{ $users->total() }} pengguna</span>
        <span>Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}</span>
    </div>
    <div>{{ $users->links() }}</div>
</div>
@endsection
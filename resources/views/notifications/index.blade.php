@extends('layouts.app')
@section('title', 'Notifikasi')

@section('content')
<div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px;">
    <div>
        <h2 style="margin:0 0 4px;font-size:22px;">Notifikasi</h2>
        <p style="color:#6b7690;margin:0;font-size:14px;">Semua notifikasi aktivitas terkait akun Anda</p>
    </div>
    @if ($notifications->total() > 0 && auth()->user()->unreadNotifications()->count() > 0)
        <form action="{{ route('notifications.readAll') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline">Tandai Semua Dibaca</button>
        </form>
    @endif
</div>

<div class="card">
    <div style="display:flex;flex-direction:column;">
        @forelse ($notifications as $n)
            <form action="{{ route('notifications.read', $n->id) }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" style="display:flex;gap:14px;width:100%;text-align:left;padding:16px 6px;
                    border:none;border-bottom:1px solid #e7eaf3;background:{{ $n->read_at ? '#fff' : '#f3f6ff' }};cursor:pointer;">
                    <span style="width:38px;height:38px;border-radius:10px;background:#eaf1ff;color:#0b3d91;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M12 16V4M12 4l-4 4M12 4l4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
                    </span>
                    <span style="flex:1;">
                        <span style="display:block;font-size:14px;color:#1b2559;font-weight:{{ $n->read_at ? '500' : '700' }};">
                            {{ $n->data['pesan'] ?? $n->data['judul'] ?? 'Notifikasi' }}
                        </span>
                        <span style="display:block;font-size:12px;color:#6b7690;margin-top:3px;">
                            {{ $n->created_at->locale('id')->diffForHumans() }} · {{ $n->created_at->format('d/m/Y H:i') }}
                        </span>
                    </span>
                    @if (! $n->read_at)
                        <span style="width:9px;height:9px;border-radius:50%;background:#0b3d91;flex-shrink:0;margin-top:6px;"></span>
                    @endif
                </button>
            </form>
        @empty
            <div style="text-align:center;padding:40px 0;color:#6b7690;font-size:14px;">Belum ada notifikasi.</div>
        @endforelse
    </div>

    <div style="margin-top:16px;">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
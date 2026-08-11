<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Halaman daftar lengkap semua notifikasi milik user yang login.
     */
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Tandai satu notifikasi sudah dibaca, lalu arahkan ke laporan terkait (kalau ada).
     */
    public function read(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $laporanId = $notification->data['laporan_id'] ?? null;

        if ($laporanId) {
            return redirect()->route('laporan.show', $laporanId);
        }

        return back();
    }

    /**
     * Tandai semua notifikasi yang belum dibaca jadi sudah dibaca.
     */
    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
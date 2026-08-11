<?php

namespace App\Notifications;

use App\Models\LaporanSusulan;
use App\Models\User;
use Illuminate\Notifications\Notification;

class LaporanBaruDiupload extends Notification
{
    public function __construct(
        protected LaporanSusulan $laporan,
        protected User $pengupload,
    ) {}

    /**
     * Cuma disimpan ke database (muncul di dropdown lonceng topbar),
     * belum dikirim lewat email/broadcast.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'laporan_id' => $this->laporan->id,
            'judul'      => 'Laporan baru diupload',
            'pesan'      => "{$this->pengupload->name} mengupload laporan {$this->laporan->bulan} {$this->laporan->tahun} ({$this->laporan->unit_up3}).",
            'pengupload' => $this->pengupload->name,
            'bulan'      => $this->laporan->bulan,
            'tahun'      => $this->laporan->tahun,
            'unit_up3'   => $this->laporan->unit_up3,
        ];
    }
}
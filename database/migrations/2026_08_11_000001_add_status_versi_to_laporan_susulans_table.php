<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_susulans', function (Blueprint $table) {
            // 'aktif'   = versi yang dipakai di dashboard & daftar laporan
            // 'digantikan' = versi lama, disimpan sebagai riwayat/audit trail
            $table->string('status', 20)->default('aktif')->after('path_file');
            $table->unsignedInteger('versi')->default(1)->after('status');

            $table->index(['unit_up3', 'bulan', 'tahun', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('laporan_susulans', function (Blueprint $table) {
            $table->dropIndex(['unit_up3', 'bulan', 'tahun', 'status']);
            $table->dropColumn(['status', 'versi']);
        });
    }
};
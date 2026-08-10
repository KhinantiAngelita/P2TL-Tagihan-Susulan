<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_susulans', function (Blueprint $table) {
            $table->id();
            $table->string('unit_induk')->nullable();
            $table->string('unit_up3')->nullable();
            $table->string('judul_laporan')->nullable();
            $table->string('bulan')->nullable();
            $table->string('tahun', 4)->nullable();
            $table->string('nama_file_asli');
            $table->string('path_file');
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->bigInteger('total_ts')->default(0);
            $table->bigInteger('total_materai')->default(0);
            $table->bigInteger('total_segel')->default(0);
            $table->bigInteger('total_materia')->default(0);
            $table->bigInteger('total_rpppj')->default(0);
            $table->bigInteger('total_rpujl')->default(0);
            $table->bigInteger('total_rpppn')->default(0);
            $table->bigInteger('total_keseluruhan')->default(0);
            $table->bigInteger('total_tunai')->default(0);
            $table->bigInteger('total_angsuran')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_susulans');
    }
};

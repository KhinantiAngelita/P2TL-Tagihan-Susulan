<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_bulanans', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan'); // 1 = Januari ... 12 = Desember
            $table->string('jenis', 10); // 'kwh' atau 'ts'
            $table->string('ulp')->nullable(); // null = Semua ULP
            $table->decimal('nilai_target', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['tahun', 'bulan', 'jenis', 'ulp'], 'target_bulanan_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_bulanans');
    }
};
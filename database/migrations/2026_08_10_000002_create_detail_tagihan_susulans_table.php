<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_tagihan_susulans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_susulan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('no')->nullable();
            $table->string('no_agenda')->nullable()->index();
            $table->string('idpel', 20)->nullable()->index();
            $table->string('nama')->nullable();
            $table->string('gol', 10)->nullable()->index();
            $table->string('alamat')->nullable();
            $table->string('daya', 30)->nullable();
            $table->unsignedBigInteger('kwh')->default(0);
            $table->bigInteger('beban')->default(0);
            $table->bigInteger('kwh_rupiah')->default(0);
            $table->bigInteger('ts')->default(0);
            $table->bigInteger('materai')->default(0);
            $table->bigInteger('segel')->default(0);
            $table->bigInteger('materia')->default(0);
            $table->bigInteger('rpppj')->default(0);
            $table->bigInteger('rpujl')->default(0);
            $table->bigInteger('rpppn')->default(0);
            $table->bigInteger('total')->default(0);
            $table->bigInteger('tunai')->default(0);
            $table->bigInteger('angsuran')->default(0);
            $table->date('tanggal_register')->nullable();
            $table->string('nomor_register')->nullable();
            $table->date('tanggal_sph')->nullable();
            $table->string('nomor_sph')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_tagihan_susulans');
    }
};

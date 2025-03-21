<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voice_channels_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_channel');
            $table->string('kode_channel');
            $table->integer('id_travel')->nullable();
            $table->integer('id_paket')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('passcode')->nullable();
            $table->integer('maks_pengguna')->default(10);
            $table->boolean('is_online')->default(false);
            $table->enum('status', ['ditolak', 'proses', 'aktif', 'selesai'])->default('proses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_channels_v2');
    }
};

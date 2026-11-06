<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name');
            $table->integer('capacity');
            // Foreign Key merujuk ke users.id (User dengan role PIC Ruang)
            $table->foreignId('pic_id')->constrained('users')->onDelete('cascade');
            $table->enum('is_active', ['Aktif', 'Tidak aktif'])->default('Aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
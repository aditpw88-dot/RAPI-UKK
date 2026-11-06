<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->unsignedBigInteger('room_id')->nullable()->change();
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('events')->whereNull('room_id')->exists()) {
            throw new RuntimeException('Cannot require a room while school-wide events without a room exist.');
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->unsignedBigInteger('room_id')->nullable(false)->change();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
        });
    }
};

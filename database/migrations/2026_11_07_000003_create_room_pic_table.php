<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_pic', function (Blueprint $table) {
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['room_id', 'user_id']);
        });

        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'pic_id')) {
            $existing = DB::table('rooms')->whereNotNull('pic_id')->get(['id', 'pic_id']);
            foreach ($existing as $row) {
                if (DB::table('users')->where('id', $row->pic_id)->exists()) {
                    DB::table('room_pic')->insertOrIgnore([
                        'room_id' => $row->id,
                        'user_id' => $row->pic_id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_pic');
    }
};


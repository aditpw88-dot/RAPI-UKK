<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('complaints', 'repaired_at')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->timestamp('repaired_at')->nullable()->after('status_repair');
            });

            DB::table('complaints')
                ->where('status_repair', 'Sudah Diperbaiki')
                ->whereNull('repaired_at')
                ->update(['repaired_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('complaints', 'repaired_at')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->dropColumn('repaired_at');
            });
        }
    }
};


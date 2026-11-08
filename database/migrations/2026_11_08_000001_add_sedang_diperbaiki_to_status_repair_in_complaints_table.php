<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE complaints MODIFY COLUMN status_repair ENUM('Belum Diperbaiki', 'Sedang Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki') NOT NULL DEFAULT 'Belum Diperbaiki'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE complaints MODIFY COLUMN status_repair ENUM('Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki') NOT NULL DEFAULT 'Belum Diperbaiki'");
        }
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('events', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('rejection_reason');
            $table->string('cancellation_status', 25)->nullable()->after('cancellation_reason');
            $table->timestamp('cancellation_requested_at')->nullable()->after('cancellation_status');
            $table->foreignId('cancellation_approved_by')->nullable()->after('cancellation_requested_at')->constrained('users')->nullOnDelete();
            $table->timestamp('cancellation_approved_at')->nullable()->after('cancellation_approved_by');
            $table->text('cancellation_rejected_reason')->nullable()->after('cancellation_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['cancellation_approved_by']);
            $table->dropColumn([
                'cancellation_reason',
                'cancellation_status',
                'cancellation_requested_at',
                'cancellation_approved_by',
                'cancellation_approved_at',
                'cancellation_rejected_reason',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};


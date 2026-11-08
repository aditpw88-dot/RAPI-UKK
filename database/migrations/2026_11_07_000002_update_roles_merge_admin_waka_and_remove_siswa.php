<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename 'Admin' to 'Admin/Waka'
        $adminRole = DB::table('roles')->where('role_name', 'Admin')->first();
        if ($adminRole) {
            DB::table('roles')->where('id', $adminRole->id)->update(['role_name' => 'Admin/Waka']);
            $targetRoleId = $adminRole->id;
        } else {
            $existingAdminWaka = DB::table('roles')->where('role_name', 'Admin/Waka')->first();
            $targetRoleId = $existingAdminWaka?->id;
        }

        // 2. Reassign any users with 'Waka Sapras' to 'Admin/Waka', then delete 'Waka Sapras' role
        $wakaRoles = DB::table('roles')->whereIn('role_name', ['Waka Sapras', 'waka sapras', 'Waka'])->get();
        foreach ($wakaRoles as $waka) {
            if ($targetRoleId) {
                DB::table('users')->where('role_id', $waka->id)->update(['role_id' => $targetRoleId]);
            }
            DB::table('roles')->where('id', $waka->id)->delete();
        }

        // 3. Remove 'Siswa' role (and any associated users)
        $siswaRoles = DB::table('roles')->whereIn('role_name', ['Siswa', 'siswa'])->get();
        foreach ($siswaRoles as $siswa) {
            DB::table('users')->where('role_id', $siswa->id)->delete();
            DB::table('roles')->where('id', $siswa->id)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('role_name', 'Admin/Waka')->update(['role_name' => 'Admin']);
        DB::table('roles')->insertOrIgnore([
            ['role_name' => 'Waka Sapras'],
            ['role_name' => 'Siswa'],
        ]);
    }
};


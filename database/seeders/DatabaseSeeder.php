<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $guruRole = Role::firstOrCreate([
            'role_name' => 'Guru/Organisasi',
        ]);

        User::updateOrCreate(
            ['username' => 'guru'],
            [
                'role_id' => $guruRole->id,
                'fullname' => 'Guru Organisasi',
                'email' => 'guru@sekolah.sch.id',
                'password' => Hash::make('password123'),
                'phone_number' => '083115722144',
            ]
        );

        $this->call(RoomSeeder::class);
    }
}

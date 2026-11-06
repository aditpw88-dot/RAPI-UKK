<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $pic = User::whereHas('role', fn ($query) => $query->where('role_name', 'like', '%PIC%'))
            ->orderBy('id')
            ->first();

        if (! $pic) {
            throw new RuntimeException('Tambahkan akun dengan role PIC Ruang sebelum menjalankan RoomSeeder.');
        }

        DB::transaction(function () use ($pic): void {
            foreach (range(1, 21) as $number) {
                Room::updateOrCreate(
                    ['room_name' => "Lab {$number}"],
                    ['capacity' => 36, 'pic_id' => $pic->id]
                );
            }

            foreach (range(1, 32) as $number) {
                Room::updateOrCreate(
                    ['room_name' => "Ruang Kelas {$number}"],
                    ['capacity' => 36, 'pic_id' => $pic->id]
                );
            }
        });
    }
}

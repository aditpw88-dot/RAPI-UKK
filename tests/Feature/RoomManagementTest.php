<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_room_without_a_location_column(): void
    {
        $adminRole = $this->createRole('Admin');
        $picRole = $this->createRole('PIC Ruang');
        $admin = $this->createUser('admin', $adminRole);
        $pic = $this->createUser('pic', $picRole);

        $this->actingAs($admin)
            ->post(route('admin.rooms.store'), [
                'room_name' => 'Ruang Wijaya',
                'capacity' => 40,
                'pic_id' => $pic->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'room_name' => 'Ruang Wijaya',
            'capacity' => 40,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
        $this->assertFalse(Schema::hasColumn('rooms', 'location'));
        $this->assertSame(1, Room::count());
    }

    public function test_room_management_does_not_offer_or_expose_whatsapp_warning(): void
    {
        $adminRole = $this->createRole('Admin');
        $picRole = $this->createRole('PIC Ruang');
        $admin = $this->createUser('admin', $adminRole);
        $pic = $this->createUser('pic', $picRole);
        $room = Room::create([
            'room_name' => 'Ruang Wijaya',
            'capacity' => 40,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.rooms'))
            ->assertOk()
            ->assertDontSee('Peneguran via WhatsApp');

        $this->post("/admin/rooms/{$room->id}/warning")
            ->assertNotFound();
    }

    public function test_room_identity_toggle_is_compact_and_shows_details_without_repeating_room_name(): void
    {
        $adminRole = $this->createRole('Admin');
        $picRole = $this->createRole('PIC Ruang');
        $admin = $this->createUser('admin-identity', $adminRole);
        $pic = $this->createUser('pic-identity', $picRole);
        $pic->update(['phone_number' => '081234567890']);
        Room::create([
            'room_name' => 'Lab Identitas',
            'capacity' => 36,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.rooms'))
            ->assertOk()
            ->assertSee('<summary class="flex w-fit cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold', false)
            ->assertSee('Lihat Identitas Ruangan')
            ->assertSee('<p class="text-base font-semibold text-slate-900">Lab Identitas</p>', false)
            ->assertSee('Penanggung jawab : Pic-identity')
            ->assertSee('Data Diri')
            ->assertSee('Nomor whatsapp: 081234567890')
            ->assertSee('gmail: pic-identity@example.com')
            ->assertSee('Kuantitas: 36 orang')
            ->assertDontSee('<p class="text-lg font-semibold text-slate-900">Lab Identitas</p>', false);
    }

    public function test_admin_rooms_page_is_paginated_with_10_rooms_per_page(): void
    {
        $adminRole = $this->createRole('Admin');
        $picRole = $this->createRole('PIC Ruang');
        $admin = $this->createUser('admin-page', $adminRole);
        $pic = $this->createUser('pic-page', $picRole);

        for ($i = 1; $i <= 15; $i++) {
            Room::create([
                'room_name' => "Lab {$i}",
                'capacity' => 36,
                'pic_id' => $pic->id,
                'is_active' => 'Aktif',
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.rooms'))
            ->assertOk();

        $response->assertSee('Menampilkan 1–10 dari 15 ruangan', false);
        $response->assertSee('Lab 1');
        $response->assertSee('Lab 10');
        $response->assertDontSee('Lab 11');

        $responsePage2 = $this->actingAs($admin)
            ->get(route('admin.rooms', ['page' => 2]))
            ->assertOk();

        $responsePage2->assertSee('Menampilkan 11–15 dari 15 ruangan', false);
        $responsePage2->assertSee('Lab 11');
        $responsePage2->assertSee('Lab 15');
        $responsePage2->assertDontSee('Lab 1</p>', false);
    }

    public function test_pic_rooms_page_is_paginated_with_10_rooms_per_page(): void
    {
        $picRole = $this->createRole('PIC Ruang');
        $pic = $this->createUser('pic-user', $picRole);

        for ($i = 1; $i <= 12; $i++) {
            Room::create([
                'room_name' => "Lab {$i}",
                'capacity' => 36,
                'pic_id' => $pic->id,
                'is_active' => 'Aktif',
            ]);
        }

        $response = $this->actingAs($pic)
            ->get(route('pic.rooms'))
            ->assertOk();

        $response->assertSee('Menampilkan 1–10 dari 12 ruangan', false);
        $response->assertSee('Lab 1');
        $response->assertSee('Lab 10');
        $response->assertDontSee('Lab 11');

        $responsePage2 = $this->actingAs($pic)
            ->get(route('pic.rooms', ['page' => 2]))
            ->assertOk();

        $responsePage2->assertSee('Menampilkan 11–12 dari 12 ruangan', false);
        $responsePage2->assertSee('Lab 11');
        $responsePage2->assertSee('Lab 12');
    }

    public function test_admin_rooms_filter_by_condition_and_status_availability(): void
    {
        $adminRole = $this->createRole('Admin');
        $picRole = $this->createRole('PIC Ruang');
        $admin = $this->createUser('admin-filter', $adminRole);
        $pic = $this->createUser('pic-filter', $picRole);

        $activeRoom = Room::create([
            'room_name' => 'Lab Aktif Tersedia',
            'capacity' => 36,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $inactiveRoom = Room::create([
            'room_name' => 'Lab Rusak Tidak Aktif',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Tidak aktif',
        ]);

        // Filter condition=active
        $this->actingAs($admin)
            ->get(route('admin.rooms', ['condition' => 'active']))
            ->assertOk()
            ->assertSee('Lab Aktif Tersedia')
            ->assertDontSee('Lab Rusak Tidak Aktif');

        // Filter condition=inactive
        $this->actingAs($admin)
            ->get(route('admin.rooms', ['condition' => 'inactive']))
            ->assertOk()
            ->assertSee('Lab Rusak Tidak Aktif')
            ->assertSee('Kondisi: Tidak aktif')
            ->assertSee('Status: Tidak tersedia')
            ->assertDontSee('Lab Aktif Tersedia');

        // Filter status=available
        $this->actingAs($admin)
            ->get(route('admin.rooms', ['status' => 'available']))
            ->assertOk()
            ->assertSee('Lab Aktif Tersedia')
            ->assertDontSee('Lab Rusak Tidak Aktif');

        // Filter status=unavailable
        $this->actingAs($admin)
            ->get(route('admin.rooms', ['status' => 'unavailable']))
            ->assertOk()
            ->assertSee('Lab Rusak Tidak Aktif')
            ->assertDontSee('Lab Aktif Tersedia');
    }

    private function createRole(string $name): Role
    {
        $role = new Role();
        $role->role_name = $name;
        $role->save();

        return $role;
    }

    private function createUser(string $username, Role $role): User
    {
        $user = User::create([
            'username' => $username,
            'fullname' => ucfirst($username),
            'email' => $username . '@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}

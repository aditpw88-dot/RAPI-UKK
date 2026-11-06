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
            ->assertSee('Penanggung jawab: Pic-identity')
            ->assertSee('WhatsApp: 081234567890')
            ->assertSee('Kuantitas: 36 orang')
            ->assertSee('Status: Aktif')
            ->assertDontSee('<p class="text-lg font-semibold text-slate-900">Lab Identitas</p>', false);
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

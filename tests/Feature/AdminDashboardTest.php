<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_does_not_show_the_available_rooms_section(): void
    {
        $admin = $this->createUser();
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('dashboard'))
            ->assertDontSee('Ruang Kosong Hari Ini')
            ->assertSee('Agenda Terbaru')
            ->assertSee('Akses Cepat')
            ->assertSee('Tambah User');
    }

    public function test_admin_events_page_shows_requester_identity_in_popover(): void
    {
        $admin = $this->createUser();
        $guruRole = Role::create(['role_name' => 'Guru']);
        $guru = User::create([
            'username' => 'aditguru',
            'fullname' => 'Adit Guru Pengaju',
            'email' => 'aditguru@example.com',
            'phone_number' => '081234567890',
            'password' => 'password',
            'role_id' => $guruRole->id,
        ]);

        $room = \App\Models\Room::create([
            'room_name' => 'Ruang DAK',
            'room_type' => 'ruang',
            'capacity' => 40,
            'is_active' => 'Aktif',
            'pic_id' => $admin->id,
        ]);

        $event = \App\Models\Event::create([
            'event_name' => 'Rapat PIP Pengujian',
            'room_id' => $room->id,
            'pic_id' => $guru->id,
            'date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:30:00',
            'end_time' => '13:15:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Adit Guru Pengaju')
            ->assertSee('aditguru')
            ->assertSee('aditguru@example.com')
            ->assertSee('081234567890')
            ->assertSee('https://wa.me/6281234567890', false);
    }

    private function createUser(): User
    {
        $role = Role::create(['role_name' => 'Admin']);

        $user = User::create([
            'username' => 'dashboard-admin',
            'fullname' => 'Dashboard Admin',
            'email' => 'dashboard-admin@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

}

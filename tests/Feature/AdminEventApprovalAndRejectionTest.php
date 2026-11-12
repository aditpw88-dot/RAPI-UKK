<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventApprovalAndRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_acc_and_tolak_agenda_buttons_for_pending_events(): void
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $guruRole = Role::create(['role_name' => 'Guru']);

        $admin = User::create([
            'username' => 'admin_test_approval',
            'fullname' => 'Admin Approval',
            'email' => 'adminapproval@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        $guru = User::create([
            'username' => 'guru_pengaju',
            'fullname' => 'Guru Pengaju Agenda',
            'email' => 'gurupengaju@example.com',
            'password' => 'password',
            'role_id' => $guruRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab IPA 1',
            'room_type' => 'ruang',
            'capacity' => 36,
            'is_active' => 'Aktif',
            'pic_id' => $admin->id,
        ]);

        $event = Event::create([
            'event_name' => 'Praktikum Biologi Guru',
            'event_type' => 'akademik',
            'room_id' => $room->id,
            'pic_id' => $guru->id,
            'date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
        ]);
        $event->rooms()->sync([$room->id]);

        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Menunggu')
            ->assertSee('ACC Agenda')
            ->assertSee('Tolak Agenda');
    }

    public function test_admin_can_reject_pending_agenda_with_reason(): void
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $guruRole = Role::create(['role_name' => 'Guru']);

        $admin = User::create([
            'username' => 'admin_test_reject',
            'fullname' => 'Admin Reject',
            'email' => 'adminreject@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        $guru = User::create([
            'username' => 'guru_pengaju_2',
            'fullname' => 'Guru Pengaju Dua',
            'email' => 'gurupengaju2@example.com',
            'password' => 'password',
            'role_id' => $guruRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab Fisika 1',
            'room_type' => 'ruang',
            'capacity' => 36,
            'is_active' => 'Aktif',
            'pic_id' => $admin->id,
        ]);

        $event = Event::create([
            'event_name' => 'Praktikum Fisika Kelas XII',
            'event_type' => 'akademik',
            'room_id' => $room->id,
            'pic_id' => $guru->id,
            'date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
        ]);
        $event->rooms()->sync([$room->id]);

        $response = $this->actingAs($admin)
            ->post(route('admin.events.reject', $event), [
                'rejection_reason' => 'Ruangan akan dipakai untuk kegiatan simulasi ANBK.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals('rejected', $event->status);
        $this->assertEquals('Ruangan akan dipakai untuk kegiatan simulasi ANBK.', $event->rejection_reason);

        // Verify that admin sees the rejection badge and reason on index
        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Ditolak')
            ->assertSee('Ruangan akan dipakai untuk kegiatan simulasi ANBK.');
    }
}


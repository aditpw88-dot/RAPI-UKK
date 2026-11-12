<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBorrowGuruRoomAllocationTest extends TestCase
{
    use RefreshDatabase;

    private function createRolesAndUsers(): array
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru/Organisasi']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC Ruang']);

        $admin = User::create([
            'username' => 'admin_test',
            'fullname' => 'Administrator',
            'email' => 'admin_test@example.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
        ]);

        $admin2 = User::create([
            'username' => 'admin2_test',
            'fullname' => 'Administrator Dua',
            'email' => 'admin2_test@example.com',
            'password' => 'password123',
            'role_id' => $adminRole->id,
        ]);

        $guru = User::create([
            'username' => 'guru_test',
            'fullname' => 'Guru Pengajar',
            'email' => 'guru_test@example.com',
            'password' => 'password123',
            'role_id' => $guruRole->id,
        ]);

        $pic = User::create([
            'username' => 'pic_test',
            'fullname' => 'PIC Laboratorium',
            'email' => 'pic_test@example.com',
            'password' => 'password123',
            'role_id' => $picRole->id,
        ]);

        return [$admin, $admin2, $guru, $pic];
    }

    public function test_nearest_room_calculation(): void
    {
        [$admin, $admin2, $guru, $pic] = $this->createRolesAndUsers();

        $lab1 = Room::create(['room_name' => 'Lab 1', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);
        $lab2 = Room::create(['room_name' => 'Lab 2', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);
        $lab5 = Room::create(['room_name' => 'Lab 5', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);
        $kelas1 = Room::create(['room_name' => 'Ruang Kelas 1', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);

        // Distance between Lab 1 and Lab 2 is 1
        $this->assertEquals(1, Room::calculateDistance($lab1, $lab2));
        // Distance between Lab 1 and Lab 5 is 4
        $this->assertEquals(4, Room::calculateDistance($lab1, $lab5));
        // Lab 1 to Ruang Kelas 1 is cross-category (>= 1000)
        $this->assertGreaterThanOrEqual(1000, Room::calculateDistance($lab1, $kelas1));
    }

    public function test_admin_takes_room_from_guru_and_guru_is_automatically_relocated_to_nearest_room(): void
    {
        [$admin, $admin2, $guru, $pic] = $this->createRolesAndUsers();

        $lab1 = Room::create(['room_name' => 'Lab 1', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);
        $lab2 = Room::create(['room_name' => 'Lab 2', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);
        $lab3 = Room::create(['room_name' => 'Lab 3', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);

        $tomorrow = now()->addDay()->toDateString();
        $sessions = config('schedule.sessions');

        // Guru has already booked Lab 2
        $guruEvent = Event::create([
            'room_id' => $lab2->id,
            'pic_id' => $guru->id,
            'event_name' => 'Praktikum Kimia Guru',
            'event_type' => 'pembelajaran',
            'date' => $tomorrow,
            'end_date' => $tomorrow,
            'start_time' => $sessions[3]['start_time'],
            'end_time' => $sessions[5]['end_time'],
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $guruEvent->rooms()->sync([$lab2->id]);

        // Admin books Lab 2 on the same schedule
        $response = $this->actingAs($admin)->post(route('admin.events.store'), [
            'event_name' => 'Ujian Sertifikasi Admin',
            'event_type' => 'rapat',
            'room_ids' => [$lab2->id],
            'date' => $tomorrow,
            'end_date' => $tomorrow,
            'start_session' => 3,
            'end_session' => 5,
        ]);

        $response->assertRedirect(route('admin.events'));
        $response->assertSessionHas('success');

        // Admin event created and owns Lab 2
        $this->assertDatabaseHas('events', [
            'event_name' => 'Ujian Sertifikasi Admin',
            'room_id' => $lab2->id,
            'pic_id' => $admin->id,
            'status' => 'approved',
        ]);

        // Guru event relocated to nearest room (Lab 1 or Lab 3)
        $guruEvent->refresh();
        $this->assertNotEquals($lab2->id, $guruEvent->room_id);
        $this->assertContains($guruEvent->room_id, [$lab1->id, $lab3->id]);
        $this->assertStringContainsString('Dialokasikan otomatis', (string) $guruEvent->move_reason);
        $this->assertStringContainsString('Ujian Sertifikasi Admin', (string) $guruEvent->move_reason);
    }

    public function test_admin_cannot_override_another_admin_event(): void
    {
        [$admin, $admin2, $guru, $pic] = $this->createRolesAndUsers();

        $lab1 = Room::create(['room_name' => 'Lab 1', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);
        $tomorrow = now()->addDay()->toDateString();
        $sessions = config('schedule.sessions');

        // Admin 1 already booked Lab 1
        $admin1Event = Event::create([
            'room_id' => $lab1->id,
            'pic_id' => $admin->id,
            'event_name' => 'Rapat Pimpinan',
            'event_type' => 'rapat',
            'date' => $tomorrow,
            'end_date' => $tomorrow,
            'start_time' => $sessions[2]['start_time'],
            'end_time' => $sessions[4]['end_time'],
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $admin1Event->rooms()->sync([$lab1->id]);

        // Admin 2 tries to book Lab 1 on same schedule
        $response = $this->actingAs($admin2)->post(route('admin.events.store'), [
            'event_name' => 'Workshop Admin 2',
            'event_type' => 'akademik',
            'room_ids' => [$lab1->id],
            'date' => $tomorrow,
            'end_date' => $tomorrow,
            'start_session' => 2,
            'end_session' => 4,
        ]);

        $response->assertSessionHasErrors('room_ids');

        // Admin 1 event still intact
        $admin1Event->refresh();
        $this->assertEquals($lab1->id, $admin1Event->room_id);
    }

    public function test_move_modal_in_admin_events_index_has_combobox_and_left_alignment(): void
    {
        [$admin, $admin2, $guru, $pic] = $this->createRolesAndUsers();

        $lab1 = Room::create(['room_name' => 'Lab 1', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);
        $lab2 = Room::create(['room_name' => 'Lab 2', 'capacity' => 36, 'pic_id' => $pic->id, 'is_active' => 'Aktif']);

        $tomorrow = now()->addDay()->toDateString();
        $sessions = config('schedule.sessions');

        $event = Event::create([
            'room_id' => $lab1->id,
            'pic_id' => $admin->id,
            'event_name' => 'Agenda Pindah Ruang',
            'event_type' => 'rapat',
            'date' => $tomorrow,
            'end_date' => $tomorrow,
            'start_time' => $sessions[2]['start_time'],
            'end_time' => $sessions[4]['end_time'],
            'status' => 'approved',
        ]);
        $event->rooms()->sync([$lab1->id]);

        $response = $this->actingAs($admin)->get(route('admin.events'));

        $response->assertOk();
        $response->assertSee('data-move-combobox', false);
        $response->assertSee('data-move-combobox-input', false);
        $response->assertSee('data-move-combobox-dropdown', false);
        $response->assertSee('data-move-occupied-warning', false);
        $response->assertSee('text-left');
        $response->assertSee('Ruangan tujuan');
    }
}

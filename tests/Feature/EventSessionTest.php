<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-14 12:00:00'));
    }

    public function test_teacher_event_uses_selected_session_times(): void
    {
        [$teacher, $room] = $this->createRoomForRole('Guru/Organisasi');

        $this->actingAs($teacher)
            ->post(route('events.store'), [
                'event_name' => 'Rapat Wali Kelas',
                'room_id' => $room->id,
                'date' => '2026-10-15',
                'start_session' => 4,
                'end_session' => 4,
            ])
            ->assertRedirect(route('events.create'));

        $this->assertDatabaseHas('events', [
            'event_name' => 'Rapat Wali Kelas',
            'start_time' => '09:30',
            'end_time' => '10:15',
        ]);
    }

    public function test_teacher_can_book_a_room_for_multiple_days_and_conflicts_are_checked_across_the_range(): void
    {
        [$teacher, $room] = $this->createRoomForRole('Guru/Organisasi');

        $this->actingAs($teacher)
            ->post(route('events.store'), [
                'event_name' => 'Kegiatan Tiga Hari',
                'room_id' => $room->id,
                'date' => '2026-10-15',
                'end_date' => '2026-10-17',
                'start_session' => 4,
                'end_session' => 5,
            ])
            ->assertRedirect(route('events.create'));

        $this->assertDatabaseHas('events', [
            'event_name' => 'Kegiatan Tiga Hari',
            'date' => '2026-10-15',
            'end_date' => '2026-10-17',
            'start_time' => '09:30',
            'end_time' => '11:00',
        ]);

        $this->from(route('events.create'))
            ->post(route('events.store'), [
                'event_name' => 'Agenda Bentrok Hari Terakhir',
                'room_id' => $room->id,
                'date' => '2026-10-17',
                'end_date' => '2026-10-17',
                'start_session' => 5,
                'end_session' => 5,
            ])
            ->assertSessionHasErrors('room_ids');

        $this->assertSame(1, Event::count());
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        [$teacher, $room] = $this->createRoomForRole('Guru/Organisasi');

        $this->from(route('events.create'))
            ->actingAs($teacher)
            ->post(route('events.store'), [
                'event_name' => 'Rentang Tanggal Tidak Valid',
                'room_id' => $room->id,
                'date' => '2026-10-16',
                'end_date' => '2026-10-15',
                'start_session' => 4,
                'end_session' => 4,
            ])
            ->assertSessionHasErrors('end_date');

        $this->assertSame(0, Event::count());
    }

    public function test_admin_event_uses_selected_session_times(): void
    {
        [$admin, $room] = $this->createRoomForRole('Admin');

        $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'event_name' => 'Rapat Sekolah',
                'room_id' => $room->id,
                'date' => '2026-10-15',
                'start_session' => 8,
                'end_session' => 10,
            ])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseHas('events', [
            'event_name' => 'Rapat Sekolah',
            'start_time' => '12:30',
            'end_time' => '14:45',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_book_a_room_for_multiple_days(): void
    {
        [$admin, $room] = $this->createRoomForRole('Admin');

        $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'event_name' => 'Agenda Admin Beberapa Hari',
                'room_id' => $room->id,
                'date' => '2026-10-15',
                'end_date' => '2026-10-16',
                'start_session' => 8,
                'end_session' => 10,
            ])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseHas('events', [
            'event_name' => 'Agenda Admin Beberapa Hari',
            'date' => '2026-10-15',
            'end_date' => '2026-10-16',
            'start_time' => '12:30',
            'end_time' => '14:45',
        ]);
    }

    public function test_teacher_can_book_multiple_rooms_and_a_conflict_in_any_room_rejects_the_request(): void
    {
        [$teacher, $firstRoom] = $this->createRoomForRole('Guru/Organisasi');
        $secondRoom = Room::create([
            'room_name' => 'Ruang Uji Kedua',
            'capacity' => 30,
            'location' => '',
            'pic_id' => $teacher->id,
            'is_active' => 'Aktif',
        ]);

        $this->actingAs($teacher)
            ->get(route('events.create'))
            ->assertOk()
            ->assertSee('name="room_ids[]"', false)
            ->assertSee('+ Tambah Ruang')
            ->assertDontSee('type="checkbox" name="room_ids[]"', false);

        $this->actingAs($teacher)
            ->post(route('events.store'), [
                'event_name' => 'Rapat di Beberapa Ruang',
                'room_ids' => [$firstRoom->id, $secondRoom->id],
                'date' => '2026-10-15',
                'start_session' => 4,
                'end_session' => 4,
            ])
            ->assertRedirect(route('events.create'));

        $event = Event::where('event_name', 'Rapat di Beberapa Ruang')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$firstRoom->id, $secondRoom->id],
            $event->rooms()->pluck('rooms.id')->all()
        );

        $this->from(route('events.create'))
            ->post(route('events.store'), [
                'event_name' => 'Agenda Bentrok Ruang Kedua',
                'room_ids' => [$secondRoom->id],
                'date' => '2026-10-15',
                'start_session' => 4,
                'end_session' => 4,
            ])
            ->assertSessionHasErrors('room_ids');

        $this->assertSame(1, Event::count());
    }

    public function test_admin_can_book_multiple_rooms_but_not_when_any_selected_room_is_busy(): void
    {
        [$admin, $firstRoom] = $this->createRoomForRole('Admin');
        $secondRoom = Room::create([
            'room_name' => 'Ruang Admin Kedua',
            'capacity' => 30,
            'location' => '',
            'pic_id' => $admin->id,
            'is_active' => 'Aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.events.create'))
            ->assertOk()
            ->assertSee('name="room_ids[]"', false)
            ->assertSee('+ Tambah Ruang')
            ->assertDontSee('type="checkbox" name="room_ids[]"', false);

        $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'event_name' => 'Agenda Admin Dua Ruang',
                'room_ids' => [$firstRoom->id, $secondRoom->id],
                'date' => '2026-10-15',
                'start_session' => 8,
                'end_session' => 8,
            ])
            ->assertRedirect(route('admin.events'));

        $event = Event::where('event_name', 'Agenda Admin Dua Ruang')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$firstRoom->id, $secondRoom->id],
            $event->rooms()->pluck('rooms.id')->all()
        );

        $this->from(route('admin.events.create'))
            ->post(route('admin.events.store'), [
                'event_name' => 'Agenda Admin Bentrok',
                'room_ids' => [$secondRoom->id],
                'date' => '2026-10-15',
                'start_session' => 8,
                'end_session' => 8,
            ])
            ->assertSessionHasErrors('room_ids');

        $this->assertSame(1, Event::count());
    }

    public function test_admin_can_request_a_room_for_today_but_not_for_a_past_date(): void
    {
        [$admin, $room] = $this->createRoomForRole('Admin');

        $this->actingAs($admin)
            ->get(route('admin.events.create'))
            ->assertOk()
            ->assertSee('min="2026-10-14"', false)
            ->assertSee('boleh meminjam mulai hari ini');

        $this->actingAs($admin)
            ->from(route('admin.events.create'))
            ->post(route('admin.events.store'), [
                'event_name' => 'Agenda Hari Ini',
                'room_id' => $room->id,
                'date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'start_session' => 4,
                'end_session' => 4,
            ])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseHas('events', [
            'event_name' => 'Agenda Hari Ini',
            'date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $this->from(route('admin.events.create'))
            ->post(route('admin.events.store'), [
                'event_name' => 'Agenda Kemarin',
                'room_id' => $room->id,
                'date' => now()->subDay()->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
                'start_session' => 4,
                'end_session' => 4,
            ])
            ->assertSessionHasErrors('date');

        $this->assertSame(1, Event::count());
    }

    public function test_events_can_use_the_last_session_until_five_pm(): void
    {
        [$teacher, $room] = $this->createRoomForRole('Guru/Organisasi');

        $this->actingAs($teacher)
            ->post(route('events.store'), [
                'event_name' => 'Agenda Sore',
                'room_id' => $room->id,
                'date' => '2026-10-15',
                'start_session' => 13,
                'end_session' => 13,
            ])
            ->assertRedirect(route('events.create'));

        $this->assertDatabaseHas('events', [
            'event_name' => 'Agenda Sore',
            'start_time' => '16:15',
            'end_time' => '17:00',
        ]);

        [$admin, $adminRoom] = $this->createRoomForRole('Admin');

        $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'event_name' => 'Agenda Admin Sore',
                'room_id' => $adminRoom->id,
                'date' => '2026-10-15',
                'start_session' => 13,
                'end_session' => 13,
            ])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseHas('events', [
            'event_name' => 'Agenda Admin Sore',
            'start_time' => '16:15',
            'end_time' => '17:00',
        ]);
    }

    public function test_sessions_are_contiguous_and_each_last_45_minutes(): void
    {
        $sessions = config('schedule.sessions');

        $this->assertCount(13, $sessions);
        $this->assertSame('07:15', $sessions[1]['start_time']);
        $this->assertSame('17:00', $sessions[13]['end_time']);

        foreach ($sessions as $number => $session) {
            $start = \DateTimeImmutable::createFromFormat('H:i', $session['start_time']);
            $end = \DateTimeImmutable::createFromFormat('H:i', $session['end_time']);

            $this->assertNotFalse($start, "Sesi {$number} memiliki jam mulai valid.");
            $this->assertNotFalse($end, "Sesi {$number} memiliki jam selesai valid.");
            $this->assertSame(45, (int) (($end->getTimestamp() - $start->getTimestamp()) / 60));

            if ($number < count($sessions)) {
                $this->assertSame($session['end_time'], $sessions[$number + 1]['start_time']);
            }
        }
    }

    public function test_sessions_outside_the_available_range_are_rejected(): void
    {
        [$teacher, $room] = $this->createRoomForRole('Guru/Organisasi');

        $this->actingAs($teacher)
            ->from(route('events.create'))
            ->post(route('events.store'), [
                'event_name' => 'Rapat Wali Kelas',
                'room_id' => $room->id,
                'date' => '2026-10-15',
                'start_session' => 1,
                'end_session' => 14,
            ])
            ->assertSessionHasErrors('end_session');

        $this->assertSame(0, Event::count());
    }

    public function test_end_session_before_start_session_is_rejected(): void
    {
        [$teacher, $room] = $this->createRoomForRole('Guru/Organisasi');

        $this->actingAs($teacher)
            ->from(route('events.create'))
            ->post(route('events.store'), [
                'event_name' => 'Rapat Wali Kelas',
                'room_id' => $room->id,
                'date' => '2026-10-15',
                'start_session' => 5,
                'end_session' => 3,
            ])
            ->assertSessionHasErrors('end_session');

        $this->assertSame(0, Event::count());
    }

    public function test_teacher_must_request_room_at_least_one_day_before_event(): void
    {
        [$teacher, $room] = $this->createRoomForRole('Guru/Organisasi');

        $this->actingAs($teacher)
            ->get(route('events.create'))
            ->assertOk()
            ->assertSee('min="2026-10-15"', false)
            ->assertSee('paling lambat H-1');

        $this->from(route('events.create'))
            ->post(route('events.store'), [
                'event_name' => 'Agenda Hari Ini',
                'room_id' => $room->id,
                'date' => now()->toDateString(),
                'start_session' => 4,
                'end_session' => 4,
            ])
            ->assertSessionHasErrors('date');

        $this->assertSame(0, Event::count());

        $this->post(route('events.store'), [
            'event_name' => 'Agenda Besok',
            'room_id' => $room->id,
            'date' => now()->addDay()->toDateString(),
            'start_session' => 4,
            'end_session' => 4,
        ])->assertRedirect(route('events.create'));

        $this->assertDatabaseHas('events', [
            'event_name' => 'Agenda Besok',
            'date' => now()->addDay()->toDateString(),
        ]);
    }

    public function test_session_one_is_not_available_as_an_end_session(): void
    {
        [$teacher, $room] = $this->createRoomForRole('Guru/Organisasi');
        $this->actingAs($teacher)
            ->get(route('events.create'))
            ->assertOk()
            ->assertSee('<option value="1" data-session-start="07:15" data-session-end="08:00"', false)
            ->assertSee('>Sesi 2 (08:00 - 08:45)</option>', false)
            ->assertSee('<option value="4" data-session-start="09:30" data-session-end="10:15"', false)
            ->assertSee('<option value="13" data-session-start="16:15" data-session-end="17:00"', false)
            ->assertDontSee('<option value="1" data-session-start="08:00"', false);

        $this->from(route('events.create'))
            ->post(route('events.store'), [
                'event_name' => 'Agenda Guru',
                'room_id' => $room->id,
                'date' => '2026-10-15',
                'start_session' => 1,
                'end_session' => 1,
            ])
            ->assertSessionHasErrors('end_session');

        [$admin, $adminRoom] = $this->createRoomForRole('Admin');
        $this->actingAs($admin)
            ->get(route('admin.events.create'))
            ->assertOk()
            ->assertSee('<option value="1" data-session-start="07:15" data-session-end="08:00"', false)
            ->assertSee('>Sesi 2 (08:00 - 08:45)</option>', false)
            ->assertSee('<option value="4" data-session-start="09:30" data-session-end="10:15"', false)
            ->assertSee('<option value="13" data-session-start="16:15" data-session-end="17:00"', false)
            ->assertDontSee('<option value="1" data-session-start="08:00"', false);

        $this->from(route('admin.events.create'))
            ->post(route('admin.events.store'), [
                'event_name' => 'Agenda Admin',
                'room_id' => $adminRoom->id,
                'date' => '2026-10-15',
                'start_session' => 1,
                'end_session' => 1,
            ])
            ->assertSessionHasErrors('end_session');

        $this->assertSame(0, Event::count());
    }

    private function createRoomForRole(string $roleName): array
    {
        $role = new Role();
        $role->role_name = $roleName;
        $role->save();
        $user = User::create([
            'username' => strtolower(str_replace(['/', ' '], '-', $roleName)),
            'fullname' => $roleName . ' User',
            'email' => strtolower(str_replace(['/', ' '], '-', $roleName)) . '@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
        $room = Room::create([
            'room_name' => 'Ruang Uji',
            'capacity' => 30,
            'location' => '',
            'pic_id' => $user->id,
            'is_active' => 'Aktif',
        ]);

        return [$user, $room];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_sets_event_to_in_progress_without_a_manual_completion_action(): void
    {
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('event-pic', 'PIC Ruang');
        $room = $this->createRoom($pic);
        $event = $this->createEvent($room, $pic);

        $this->actingAs($admin)
            ->post(route('admin.events.approve', $event))
            ->assertRedirect()
            ->assertSessionHas('success', 'Agenda Guru disetujui dan statusnya menjadi sedang terlaksana.');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'approved',
            'completed_at' => null,
            'approved_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Sedang Terlaksana')
            ->assertDontSee('Tandai Sudah Terlaksana');
    }

    public function test_pic_approval_sets_event_to_in_progress_without_a_manual_completion_action(): void
    {
        $pic = $this->createUser('room-pic', 'PIC Ruang');
        $room = $this->createRoom($pic);
        $event = $this->createEvent($room, $this->createUser('teacher', 'Guru/Organisasi'));

        $this->actingAs($pic)
            ->post(route('pic.events.approve', $event))
            ->assertRedirect()
            ->assertSessionHas('success', 'Pengajuan disetujui dan statusnya menjadi sedang terlaksana.');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'approved',
            'completed_at' => null,
            'approved_by' => $pic->id,
        ]);

        $this->actingAs($pic)
            ->get(route('pic.events.approved'))
            ->assertOk()
            ->assertSee('Sedang Terlaksana')
            ->assertDontSee('Tandai Selesai');
    }

    public function test_approved_events_are_completed_automatically_after_the_scheduled_end_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05 10:00:00'));

        try {
            $pic = $this->createUser('event-owner', 'PIC Ruang');
            $room = $this->createRoom($pic);
            $expired = $this->createEvent($room, $pic, 'approved');
            $expired->update([
                'date' => '2026-11-05',
                'end_date' => '2026-11-05',
                'end_time' => '10:00:00',
            ]);

            $expiredMultiDay = $this->createEvent($room, $pic, 'approved');
            $expiredMultiDay->update([
                'date' => '2026-11-04',
                'end_date' => '2026-11-05',
                'end_time' => '09:59:00',
            ]);

            $future = $this->createEvent($room, $pic, 'approved');
            $future->update([
                'date' => '2026-11-05',
                'end_date' => '2026-11-05',
                'end_time' => '10:01:00',
            ]);

            $pastPending = $this->createEvent($room, $pic, 'pending');
            $pastPending->update([
                'date' => '2026-11-04',
                'end_date' => '2026-11-04',
                'end_time' => '09:00:00',
            ]);

            $this->artisan('events:complete-expired')
                ->expectsOutput('2 agenda ditandai sudah terlaksana.')
                ->assertExitCode(0);

            $this->assertNotNull($expired->fresh()->completed_at);
            $this->assertNotNull($expiredMultiDay->fresh()->completed_at);
            $this->assertNull($future->fresh()->completed_at);
            $this->assertNull($pastPending->fresh()->completed_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createUser(string $username, string $roleName): User
    {
        $role = Role::create(['role_name' => $roleName]);

        return User::create([
            'username' => $username,
            'fullname' => ucfirst($username),
            'email' => $username.'@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
    }

    private function createRoom(User $pic): Room
    {
        return Room::create([
            'room_name' => 'Ruang '.$pic->username,
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
    }

    private function createEvent(Room $room, User $pic, string $status = 'pending'): Event
    {
        return Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Agenda Sekolah',
            'event_type' => 'lainnya',
            'date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => $status,
        ]);
    }
}

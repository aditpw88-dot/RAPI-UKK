<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_upcoming_agenda_with_auto_approval(): void
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'username' => 'admin_cancel_test',
            'fullname' => 'Admin Pembatalan',
            'email' => 'admincancel@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab Komputer 1',
            'room_type' => 'ruang',
            'capacity' => 36,
            'is_active' => 'Aktif',
            'pic_id' => $admin->id,
        ]);

        $futureDate = now()->addDays(3)->toDateString();

        $event = Event::create([
            'event_name' => 'Workshop Internal Admin',
            'event_type' => 'rapat',
            'room_id' => $room->id,
            'pic_id' => $admin->id,
            'date' => $futureDate,
            'end_date' => $futureDate,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $event->rooms()->sync([$room->id]);

        $this->assertTrue($event->canBeCancelledByAdmin());

        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Batalkan Agenda');

        $response = $this->actingAs($admin)
            ->post(route('admin.events.cancel', $event), [
                'cancellation_reason' => 'Ada agenda kedinasan mendadak.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals('cancelled', $event->status);
        $this->assertEquals('approved', $event->cancellation_status);
        $this->assertEquals($admin->id, $event->cancellation_approved_by);
        $this->assertNotNull($event->cancellation_approved_at);
        $this->assertEquals('Ada agenda kedinasan mendadak.', $event->cancellation_reason);
    }

    public function test_admin_cannot_cancel_agenda_that_is_in_progress(): void
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'username' => 'admin_cancel_test2',
            'fullname' => 'Admin Pembatalan 2',
            'email' => 'admincancel2@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab Komputer 2',
            'room_type' => 'ruang',
            'capacity' => 36,
            'is_active' => 'Aktif',
            'pic_id' => $admin->id,
        ]);

        $today = now()->toDateString();
        $startTime = now()->subMinutes(30)->format('H:i:s');
        $endTime = now()->addMinutes(60)->format('H:i:s');

        $event = Event::create([
            'event_name' => 'Agenda Sedang Berlangsung',
            'event_type' => 'rapat',
            'room_id' => $room->id,
            'pic_id' => $admin->id,
            'date' => $today,
            'end_date' => $today,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $event->rooms()->sync([$room->id]);

        $this->assertEquals('in_progress', $event->lifecycle_status);
        $this->assertFalse($event->canBeCancelledByAdmin());

        $response = $this->actingAs($admin)
            ->post(route('admin.events.cancel', $event), [
                'cancellation_reason' => 'Coba batalkan',
            ]);

        $response->assertSessionHas('error', 'Agenda yang sedang terlaksana tidak dapat dibatalkan.');
        $event->refresh();
        $this->assertEquals('approved', $event->status);
    }

    public function test_admin_cannot_cancel_agenda_that_is_already_completed(): void
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'username' => 'admin_cancel_test3',
            'fullname' => 'Admin Pembatalan 3',
            'email' => 'admincancel3@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab Komputer 3',
            'room_type' => 'ruang',
            'capacity' => 36,
            'is_active' => 'Aktif',
            'pic_id' => $admin->id,
        ]);

        $pastDate = now()->subDays(2)->toDateString();

        $event = Event::create([
            'event_name' => 'Agenda Sudah Terlaksana',
            'event_type' => 'rapat',
            'room_id' => $room->id,
            'pic_id' => $admin->id,
            'date' => $pastDate,
            'end_date' => $pastDate,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'completed_at' => now()->subDays(2),
        ]);
        $event->rooms()->sync([$room->id]);

        $this->assertEquals('completed', $event->lifecycle_status);
        $this->assertFalse($event->canBeCancelledByAdmin());

        $response = $this->actingAs($admin)
            ->post(route('admin.events.cancel', $event), [
                'cancellation_reason' => 'Coba batalkan agenda lampau',
            ]);

        $response->assertSessionHas('error', 'Agenda yang sudah terlaksana tidak dapat dibatalkan.');
        $event->refresh();
        $this->assertEquals('approved', $event->status);
    }

    public function test_waka_cannot_delete_agenda_that_is_in_progress(): void
    {
        $wakaRole = Role::create(['role_name' => 'Waka Sapras']);
        $waka = User::create([
            'username' => 'waka_delete_test',
            'fullname' => 'Waka Hapus',
            'email' => 'wakadelete@example.com',
            'password' => 'password',
            'role_id' => $wakaRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab Komputer 4',
            'room_type' => 'ruang',
            'capacity' => 36,
            'is_active' => 'Aktif',
            'pic_id' => $waka->id,
        ]);

        $today = now()->toDateString();
        $startTime = now()->subMinutes(30)->format('H:i:s');
        $endTime = now()->addMinutes(60)->format('H:i:s');

        $inProgressEvent = Event::create([
            'event_name' => 'Agenda Sedang Berlangsung Tidak Bisa Dihapus',
            'event_type' => 'rapat',
            'room_id' => $room->id,
            'pic_id' => $waka->id,
            'date' => $today,
            'end_date' => $today,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'approved',
            'approved_by' => $waka->id,
        ]);
        $inProgressEvent->rooms()->sync([$room->id]);

        $this->assertEquals('in_progress', $inProgressEvent->lifecycle_status);
        $this->assertFalse($inProgressEvent->canBeDeletedByAdmin());

        // Check UI does not render delete form for in_progress event
        $this->actingAs($waka)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertDontSee(route('admin.events.destroy', $inProgressEvent), false)
            ->assertSee('Tidak dapat dihapus (Sedang berlangsung)');

        // Attempt to DELETE directly
        $response = $this->actingAs($waka)
            ->delete(route('admin.events.destroy', $inProgressEvent));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Agenda yang sedang berlangsung tidak dapat dihapus.');
        $this->assertDatabaseHas('events', [
            'id' => $inProgressEvent->id,
        ]);

        // Verify upcoming event CAN still be deleted by Waka
        $upcomingEvent = Event::create([
            'event_name' => 'Agenda Besok Bisa Dihapus',
            'event_type' => 'rapat',
            'room_id' => $room->id,
            'pic_id' => $waka->id,
            'date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'approved_by' => $waka->id,
        ]);
        $upcomingEvent->rooms()->sync([$room->id]);

        $this->assertTrue($upcomingEvent->canBeDeletedByAdmin());

        $deleteResponse = $this->actingAs($waka)
            ->delete(route('admin.events.destroy', $upcomingEvent));

        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success', 'Agenda berhasil dihapus.');
        $this->assertDatabaseMissing('events', [
            'id' => $upcomingEvent->id,
        ]);
    }
}


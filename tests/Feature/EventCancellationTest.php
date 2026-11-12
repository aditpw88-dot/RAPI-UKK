<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_can_request_cancellation_of_their_event(): void
    {
        $guru = $this->createUser('guru1', 'Guru/Organisasi');
        $pic = $this->createUser('pic1', 'PIC Ruang');
        $room = $this->createRoom($pic);
        $event = $this->createEvent($room, $guru, 'approved');

        $response = $this->actingAs($guru)
            ->post(route('guru.events.cancel-request', $event), [
                'cancellation_reason' => 'Ada keperluan mendadak dinas luar kota.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $event->refresh();
        $this->assertSame('pending', $event->cancellation_status);
        $this->assertSame('Ada keperluan mendadak dinas luar kota.', $event->cancellation_reason);
        $this->assertNotNull($event->cancellation_requested_at);
        $this->assertSame('cancellation_pending', $event->lifecycle_status);
        $this->assertSame('Menunggu Pembatalan', $event->lifecycle_status_label);
    }

    public function test_guru_cannot_cancel_other_gurus_event(): void
    {
        $guru1 = $this->createUser('guru1', 'Guru/Organisasi');
        $guru2 = $this->createUser('guru2', 'Guru/Organisasi');
        $pic = $this->createUser('pic1', 'PIC Ruang');
        $room = $this->createRoom($pic);
        $event = $this->createEvent($room, $guru1, 'approved');

        $response = $this->actingAs($guru2)
            ->post(route('guru.events.cancel-request', $event), [
                'cancellation_reason' => 'Mencoba membatalkan agenda guru lain.',
            ]);

        $response->assertForbidden();
    }

    public function test_pic_can_approve_event_cancellation(): void
    {
        $guru = $this->createUser('guru1', 'Guru/Organisasi');
        $pic = $this->createUser('pic1', 'PIC Ruang');
        $room = $this->createRoom($pic);
        $event = $this->createEvent($room, $guru, 'approved');

        $event->update([
            'cancellation_status' => 'pending',
            'cancellation_reason' => 'Kegiatan dibatalkan oleh dinas.',
            'cancellation_requested_at' => now(),
        ]);

        $response = $this->actingAs($pic)
            ->post(route('pic.events.approve-cancellation', $event));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pembatalan agenda berhasil disetujui. Jadwal ruangan telah dibebaskan.');

        $event->refresh();
        $this->assertSame('cancelled', $event->status);
        $this->assertSame('approved', $event->cancellation_status);
        $this->assertSame($pic->id, $event->cancellation_approved_by);
        $this->assertNotNull($event->cancellation_approved_at);
        $this->assertSame('cancelled', $event->lifecycle_status);
        $this->assertSame('Dibatalkan', $event->lifecycle_status_label);
    }

    public function test_pic_can_reject_event_cancellation(): void
    {
        $guru = $this->createUser('guru1', 'Guru/Organisasi');
        $pic = $this->createUser('pic1', 'PIC Ruang');
        $room = $this->createRoom($pic);
        $event = $this->createEvent($room, $guru, 'approved');

        $event->update([
            'cancellation_status' => 'pending',
            'cancellation_reason' => 'Mau batal saja.',
            'cancellation_requested_at' => now(),
        ]);

        $response = $this->actingAs($pic)
            ->post(route('pic.events.reject-cancellation', $event), [
                'cancellation_rejected_reason' => 'Fasilitas dan konsumsi sudah disiapkan, agenda harus tetap berjalan.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pengajuan pembatalan ditolak. Agenda tetap berjalan sesuai jadwal.');

        $event->refresh();
        $this->assertSame('approved', $event->status);
        $this->assertSame('rejected', $event->cancellation_status);
        $this->assertSame('Fasilitas dan konsumsi sudah disiapkan, agenda harus tetap berjalan.', $event->cancellation_rejected_reason);
    }

    public function test_admin_can_approve_event_cancellation(): void
    {
        $guru = $this->createUser('guru1', 'Guru/Organisasi');
        $admin = $this->createUser('admin1', 'Admin');
        $pic = $this->createUser('pic1', 'PIC Ruang');
        $room = $this->createRoom($pic);
        $event = $this->createEvent($room, $guru, 'approved');

        $event->update([
            'cancellation_status' => 'pending',
            'cancellation_reason' => 'Acara digeser ke semester depan.',
            'cancellation_requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.events.approve-cancellation', $event));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pembatalan agenda Guru berhasil disetujui. Jadwal ruangan telah dibebaskan.');

        $event->refresh();
        $this->assertSame('cancelled', $event->status);
        $this->assertSame('approved', $event->cancellation_status);
        $this->assertSame($admin->id, $event->cancellation_approved_by);
        $this->assertSame('cancelled', $event->lifecycle_status);
    }

    public function test_admin_can_reject_event_cancellation(): void
    {
        $guru = $this->createUser('guru1', 'Guru/Organisasi');
        $admin = $this->createUser('admin1', 'Admin');
        $pic = $this->createUser('pic1', 'PIC Ruang');
        $room = $this->createRoom($pic);
        $event = $this->createEvent($room, $guru, 'approved');

        $event->update([
            'cancellation_status' => 'pending',
            'cancellation_reason' => 'Alasan pembatalan tidak jelas.',
            'cancellation_requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.events.reject-cancellation', $event), [
                'cancellation_rejected_reason' => 'Pembatalan ditolak oleh Waka Sarpras.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pengajuan pembatalan agenda ditolak. Agenda tetap berjalan sesuai jadwal.');

        $event->refresh();
        $this->assertSame('approved', $event->status);
        $this->assertSame('rejected', $event->cancellation_status);
        $this->assertSame('Pembatalan ditolak oleh Waka Sarpras.', $event->cancellation_rejected_reason);
    }

    public function test_cancelled_event_frees_up_room_for_new_booking(): void
    {
        $guru1 = $this->createUser('guru1', 'Guru/Organisasi');
        $guru2 = $this->createUser('guru2', 'Guru/Organisasi');
        $admin = $this->createUser('admin1', 'Admin');
        $pic = $this->createUser('pic1', 'PIC Ruang');
        $room = $this->createRoom($pic);

        $event = $this->createEvent($room, $guru1, 'approved');
        $event->update([
            'date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'start_time' => '07:15:00',
            'end_time' => '08:45:00',
        ]);
        $event->rooms()->sync([$room->id]);

        // Pembatalan disetujui
        $event->update([
            'status' => 'cancelled',
            'cancellation_status' => 'approved',
            'cancellation_approved_by' => $admin->id,
        ]);

        // Guru 2 mengajukan peminjaman di ruangan dan jadwal yang sama
        $response = $this->actingAs($guru2)
            ->post(route('events.store'), [
                'event_name' => 'Agenda Baru Guru 2',
                'event_type' => 'lainnya',
                'room_ids' => [$room->id],
                'date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'start_session' => 1,
                'end_session' => 2,
            ]);

        $response->assertRedirect(route('events.create'));
        $response->assertSessionHas('success', 'Pengajuan berhasil disimpan dan menunggu persetujuan.');

        $this->assertDatabaseHas('events', [
            'event_name' => 'Agenda Baru Guru 2',
            'pic_id' => $guru2->id,
            'status' => 'pending',
        ]);
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
        $event = Event::create([
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
        $event->rooms()->sync([$room->id]);

        return $event;
    }
}


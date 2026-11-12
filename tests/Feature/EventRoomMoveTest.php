<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRoomMoveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_move_an_event_and_save_the_reason(): void
    {
        [$admin, $pic, $sourceRoom, $destinationRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');

        $this->actingAs($admin)
            ->patch(route('admin.events.move', $event), [
                'room_id' => $destinationRoom->id,
                'move_reason' => 'Ruang dibutuhkan untuk kegiatan sekolah yang lebih penting.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'room_id' => $destinationRoom->id,
            'move_reason' => 'Ruang dibutuhkan untuk kegiatan sekolah yang lebih penting.',
        ]);
    }

    public function test_admin_can_open_a_floating_note_editor_for_move_reason(): void
    {
        [$admin, $pic, $sourceRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $event->event_name = 'Rapat Wali Kelas';
        $event->save();

        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Catatan alasan pemindahan')
            ->assertSee('Buka Catatan')
            ->assertSee('move-note-' . $event->id)
            ->assertSee('move-note-input-' . $event->id)
            ->assertSee('Simpan Catatan')
            ->assertSee('data-note-input-for="move-reason-' . $event->id . '"', false)
            ->assertSee('type="hidden" id="move-reason-' . $event->id . '" name="move_reason"', false)
            ->assertSee(route('admin.events.move-reason', $event), false)
            ->assertSee("button.closest('[data-note-panel]')", false)
            ->assertDontSee('id="move-reason-' . $event->id . '" name="move_reason" rows=', false);
    }

    public function test_admin_can_save_a_move_reason_without_moving_the_event(): void
    {
        [$admin, $pic, $sourceRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $reason = 'Ruang diperlukan untuk kegiatan sekolah.';

        $response = $this->actingAs($admin)
            ->from(route('admin.events'))
            ->patch(route('admin.events.move-reason', $event), [
                'move_reason' => $reason,
            ]);

        $response->assertRedirect(route('admin.events'));
        $response->assertSessionHas('success', 'Catatan alasan pemindahan berhasil disimpan.');
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'room_id' => $sourceRoom->id,
            'move_reason' => $reason,
        ]);
    }

    public function test_moving_an_event_redirects_to_whatsapp_with_change_details(): void
    {
        [$admin, $pic, $sourceRoom, $destinationRoom] = $this->createRoomSetup();
        $pic->update(['phone_number' => '089999999999']);
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');

        $response = $this->actingAs($admin)
            ->patch(route('admin.events.move', $event), [
                'room_id' => $destinationRoom->id,
                'move_reason' => 'Ruang lama sedang diperbaiki.',
            ]);

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/6283115722144?text=', $response->headers->get('Location'));
        $message = rawurldecode(substr($response->headers->get('Location'), strpos($response->headers->get('Location'), '?text=') + 6));
        $this->assertStringContainsString('Ruang sebelumnya: Ruang Asal', $message);
        $this->assertStringContainsString('Ruang baru: Ruang Tujuan', $message);
        $this->assertStringContainsString('Sesi: 4', $message);
        $this->assertStringContainsString('Ruang lama sedang diperbaiki.', $message);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'room_id' => $destinationRoom->id,
        ]);
    }

    public function test_event_cannot_be_moved_to_a_room_with_a_schedule_conflict(): void
    {
        [$admin, $pic, $sourceRoom, $destinationRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $this->createEvent($destinationRoom, $pic, '10:00', '10:45');

        $this->actingAs($admin)
            ->patch(route('admin.events.move', $event), [
                'room_id' => $destinationRoom->id,
                'move_reason' => 'Ruang dibutuhkan untuk kegiatan sekolah.',
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'room_id' => $sourceRoom->id,
        ]);
    }

    public function test_event_cannot_be_moved_to_its_current_room(): void
    {
        [$admin, $pic, $sourceRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');

        $this->actingAs($admin)
            ->patch(route('admin.events.move', $event), [
                'room_id' => $sourceRoom->id,
                'move_reason' => 'Pemindahan diperlukan.',
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'room_id' => $sourceRoom->id,
        ]);
    }

    public function test_pic_can_approve_a_pending_event_for_their_room(): void
    {
        [$admin, $pic, $sourceRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $event->status = 'pending';
        $event->save();

        $this->actingAs($pic)
            ->post(route('pic.events.approve', $event))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'approved',
            'approved_by' => $pic->id,
        ]);
    }

    public function test_pic_can_open_floating_notepad_to_reject_an_agenda(): void
    {
        [, $pic, $sourceRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $event->update(['status' => 'pending']);

        $this->actingAs($pic)
            ->get(route('pic.approvals'))
            ->assertOk()
            ->assertSee('data-open-rejection-note="rejection-note-'.$event->id.'"', false)
            ->assertSee('id="rejection-note-'.$event->id.'"', false)
            ->assertSee('Notepad Penolakan')
            ->assertSee('name="rejection_reason"', false)
            ->assertSee('Simpan Penolakan');
    }

    public function test_pic_can_reject_pending_agenda_with_reason_from_notepad(): void
    {
        [, $pic, $sourceRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $event->update(['status' => 'pending']);
        $reason = 'Ruangan sedang digunakan untuk kegiatan sekolah.';

        $this->actingAs($pic)
            ->post(route('pic.events.reject', $event), [
                'rejection_reason' => $reason,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => null,
        ]);
    }

    public function test_waka_cannot_approve_a_pending_event(): void
    {
        [$admin, $pic, $sourceRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $event->status = 'pending';
        $event->save();

        $this->actingAs($admin)
            ->post(route('pic.events.approve', $event))
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }

    public function test_pic_cannot_approve_an_event_for_another_pics_room(): void
    {
        [, $pic, $sourceRoom] = $this->createRoomSetup();
        $otherPic = $this->createUser('other-pic', $this->createRole('PIC Ruang'));
        $otherRoom = Room::create([
            'room_name' => 'Ruang Lain',
            'capacity' => 25,
            'pic_id' => $otherPic->id,
            'is_active' => 'Aktif',
        ]);
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $event->status = 'pending';
        $event->save();

        $this->actingAs($otherPic)
            ->post(route('pic.events.approve', $event))
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }

    public function test_pic_approval_page_lists_only_pending_requests_for_their_rooms(): void
    {
        [, $pic, $sourceRoom] = $this->createRoomSetup();
        $pendingEvent = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');
        $pendingEvent->event_name = 'Pengajuan Guru';
        $pendingEvent->status = 'pending';
        $pendingEvent->save();

        $otherPic = $this->createUser('other-pic', $this->createRole('PIC Ruang'));
        $otherRoom = $this->createRoom('Ruang PIC Lain', $otherPic);
        $otherPendingEvent = $this->createEvent($otherRoom, $otherPic, '10:30', '11:15');
        $otherPendingEvent->event_name = 'Pengajuan PIC Lain';
        $otherPendingEvent->status = 'pending';
        $otherPendingEvent->save();

        $approvedEvent = $this->createEvent($sourceRoom, $pic, '11:30', '12:15');
        $approvedEvent->event_name = 'Rapat yang sudah disetujui';
        $approvedEvent->save();

        $this->actingAs($pic)
            ->get(route('pic.approvals'))
            ->assertOk()
            ->assertSee('Persetujuan Peminjaman Ruangan')
            ->assertSee('Pengajuan Guru')
            ->assertDontSee('Pengajuan PIC Lain')
            ->assertDontSee('Rapat yang sudah disetujui');
    }

    public function test_pic_can_view_only_agendas_they_approved(): void
    {
        [, $pic, $sourceRoom] = $this->createRoomSetup();
        $approvedByPic = $this->createEvent($sourceRoom, $pic, '11:30', '12:15');
        $approvedByPic->event_name = 'Disetujui PIC Ini';
        $approvedByPic->approved_by = $pic->id;
        $approvedByPic->save();

        $otherPic = $this->createUser('other-pic', $this->createRole('PIC Ruang'));
        $otherPicRoom1 = $this->createRoom('Ruang PIC Lain 1', $otherPic);
        $approvedByOtherPic = $this->createEvent($otherPicRoom1, $otherPic, '12:30', '13:15');
        $approvedByOtherPic->event_name = 'Disetujui PIC Lain';
        $approvedByOtherPic->approved_by = $otherPic->id;
        $approvedByOtherPic->save();

        $pending = $this->createEvent($sourceRoom, $pic, '13:30', '14:15');
        $pending->event_name = 'Masih Menunggu';
        $pending->status = 'pending';
        $pending->approved_by = null;
        $pending->save();

        $rejected = $this->createEvent($sourceRoom, $pic, '14:30', '15:15');
        $rejected->event_name = 'Tidak Disetujui PIC Ini';
        $rejected->status = 'rejected';
        $rejected->rejection_reason = 'Ruangan tidak tersedia.';
        $rejected->save();
        $otherPic = $this->createUser('other-pic-reject', $this->createRole('PIC Ruang'));
        $otherPicRoom = $this->createRoom('Ruang PIC Lain', $otherPic);
        $otherPicRejected = $this->createEvent($otherPicRoom, $otherPic, '15:30', '16:15');
        $otherPicRejected->event_name = 'Tidak Disetujui di Ruang PIC Lain';
        $otherPicRejected->status = 'rejected';
        $otherPicRejected->save();

        $this->actingAs($pic)
            ->get(route('pic.events.approved'))
            ->assertOk()
            ->assertSee('Manajemen Agenda PIC')
            ->assertSee('Disetujui')
            ->assertSee('Disetujui PIC Ini')
            ->assertDontSee('Disetujui PIC Lain')
            ->assertDontSee('Masih Menunggu')
            ->assertSee('Tidak Disetujui PIC Ini')
            ->assertSee('Ruangan tidak tersedia.')
            ->assertDontSee('Tidak Disetujui di Ruang PIC Lain');
    }

    public function test_non_pic_cannot_open_pic_approval_page(): void
    {
        [$admin] = $this->createRoomSetup();

        $this->actingAs($admin)
            ->get(route('pic.approvals'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('pic.events.approved'))
            ->assertForbidden();
    }

    private function createRoomSetup(): array
    {
        $adminRole = $this->createRole('Admin');
        $picRole = $this->createRole('PIC Ruang');
        $admin = $this->createUser('admin', $adminRole);
        $pic = $this->createUser('pic', $picRole);
        $sourceRoom = $this->createRoom('Ruang Asal', $pic);
        $destinationRoom = $this->createRoom('Ruang Tujuan', $pic);

        return [$admin, $pic, $sourceRoom, $destinationRoom];
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

    private function createRoom(string $name, User $pic): Room
    {
        return Room::create([
            'room_name' => $name,
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
    }

    public function test_admin_can_move_an_event_to_multiple_rooms(): void
    {
        [$admin, $pic, $sourceRoom, $destinationRoom1] = $this->createRoomSetup();
        $destinationRoom2 = Room::create([
            'room_name' => 'Lab IPA 2',
            'capacity' => 36,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');

        $response = $this->actingAs($admin)
            ->patch(route('admin.events.move', $event), [
                'room_ids' => [$destinationRoom1->id, $destinationRoom2->id],
                'move_reason' => 'Dipindahkan ke 2 ruangan untuk kapasitas lebih besar.',
            ]);

        $response->assertRedirect();
        $event->refresh();

        $this->assertEquals($destinationRoom1->id, $event->room_id);
        $this->assertEquals(2, $event->rooms()->count());
        $this->assertTrue($event->rooms->contains($destinationRoom1->id));
        $this->assertTrue($event->rooms->contains($destinationRoom2->id));
        $this->assertEquals('Dipindahkan ke 2 ruangan untuk kapasitas lebih besar.', $event->move_reason);

        // Check WhatsApp message contains both room names
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $message = rawurldecode($location);
        $this->assertStringContainsString($destinationRoom1->room_name, $message);
        $this->assertStringContainsString($destinationRoom2->room_name, $message);
    }

    public function test_admin_sees_whatsapp_coordination_shortcut_in_move_dialog(): void
    {
        [$admin, $pic, $sourceRoom] = $this->createRoomSetup();
        $pic->update(['fullname' => 'Pak Budi Guru', 'phone_number' => '083115722144']);
        $event = $this->createEvent($sourceRoom, $pic, '09:30', '10:15');

        $response = $this->actingAs($admin)
            ->get(route('admin.events'));

        $response->assertOk();
        $response->assertSee('Chat WhatsApp');
        $response->assertSee('https://wa.me/6283115722144', false);
        $response->assertSee('Peminjam Ruangan');
        $response->assertSee('Pak Budi Guru');
        $response->assertSee('Tambah Ruangan Tujuan');
    }

    public function test_completed_event_cannot_be_moved_by_admin(): void
    {
        [$admin, $pic, $sourceRoom, $destinationRoom] = $this->createRoomSetup();
        $event = $this->createEvent($sourceRoom, $pic, '08:00', '09:00');
        $event->update([
            'date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
            'completed_at' => now()->subDay(),
        ]);

        $this->assertEquals('completed', $event->lifecycle_status);
        $this->assertFalse($event->canBeMovedByAdmin());

        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Tidak dapat dipindahkan (Sudah terlaksana)')
            ->assertDontSee('data-open-move-dialog="move-event-' . $event->id . '"', false);

        $response = $this->actingAs($admin)
            ->patch(route('admin.events.move', $event), [
                'room_ids' => [$destinationRoom->id],
                'move_reason' => 'Ingin memindahkan agenda yang sudah selesai.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Agenda yang sudah terlaksana tidak dapat dipindahkan ruangannya karena sudah berakhir.');

        $event->refresh();
        $this->assertEquals($sourceRoom->id, $event->room_id);
    }

    private function createEvent(Room $room, User $pic, string $start, string $end): Event
    {
        $event = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Kegiatan Sekolah',
            'date' => now()->addDay()->toDateString(),
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'approved',
        ]);
        $event->rooms()->sync([$room->id]);

        return $event;
    }
}

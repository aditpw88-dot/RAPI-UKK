<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ComplaintEscalated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminComplaintHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_complaint_handler_list_only_contains_pic_users(): void
    {
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $this->createUser('waka-sapras', 'Waka Sapras');
        $this->createUser('guru-organisasi', 'Guru/Organisasi');
        $this->createComplaint($pic);

        $this->actingAs($admin)
            ->get(route('admin.complaints'))
            ->assertOk()
            ->assertSee('PIC User')
            ->assertDontSee('Waka Sapras')
            ->assertDontSee('Guru Organisasi');
    }

    public function test_admin_complaints_are_paginated_five_per_page_and_keep_filters(): void
    {
        $admin = $this->createUser('admin-pagination', 'Admin');
        $pic = $this->createUser('pic-pagination', 'PIC Ruang');
        $room = Room::create([
            'room_name' => 'Ruang Pagination',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        foreach (range(1, 6) as $number) {
            Complaint::create([
                'room_id' => $room->id,
                'reporter_id' => $pic->id,
                'description' => "Laporan pagination {$number}",
                'photo_path' => 'complaints/uji.jpg',
                'status_repair' => 'Belum Diperbaiki',
                'reported_at' => now()->subMinutes($number),
            ]);
        }

        $filters = ['search' => 'Laporan pagination', 'status' => 'Belum Diperbaiki', 'room_type' => 'ruang'];

        $this->actingAs($admin)
            ->get(route('admin.complaints', $filters))
            ->assertOk()
            ->assertSee('Laporan pagination 1')
            ->assertSee('Laporan pagination 5')
            ->assertDontSee('Laporan pagination 6')
            ->assertSee('page=2')
            ->assertSee('search=Laporan')
            ->assertSee('status=Belum')
            ->assertSee('room_type=ruang');

        $this->actingAs($admin)
            ->get(route('admin.complaints', [...$filters, 'page' => 2]))
            ->assertOk()
            ->assertSee('Laporan pagination 6')
            ->assertDontSee('Laporan pagination 1');
    }

    public function test_admin_can_filter_complaints_by_lab_or_room_type(): void
    {
        $admin = $this->createUser('admin-room-type', 'Admin');
        $pic = $this->createUser('pic-room-type', 'PIC Ruang');
        $this->createRoomWithComplaint($pic, 'Lab 1', 'Laporan Lab');
        $this->createRoomWithComplaint($pic, 'Ruang Wijaya', 'Laporan Ruang');

        $this->actingAs($admin)
            ->get(route('admin.complaints', ['room_type' => 'lab']))
            ->assertOk()
            ->assertSee('Laporan Lab')
            ->assertDontSee('Laporan Ruang')
            ->assertSee('value="lab" selected', false);

        $this->actingAs($admin)
            ->get(route('admin.complaints', ['room_type' => 'ruang']))
            ->assertOk()
            ->assertSee('Laporan Ruang')
            ->assertDontSee('Laporan Lab')
            ->assertSee('value="ruang" selected', false);
    }

    public function test_admin_complaint_list_shows_room_name_without_identity_toggle_or_pic_note(): void
    {
        $admin = $this->createUser('admin-complaint-room-identity', 'Admin');
        $pic = $this->createUser('pic-complaint-room-identity', 'PIC Ruang');
        $pic->update(['phone_number' => '081234567890']);
        $complaint = $this->createComplaint($pic);

        $this->actingAs($admin)
            ->get(route('admin.complaints'))
            ->assertOk()
            ->assertSee('<p class="text-base font-semibold text-slate-900">Ruang Uji</p>', false)
            ->assertDontSee('Lihat Identitas Ruangan')
            ->assertDontSee('Perubahan status dan penanganan dilakukan oleh PIC.')
            ->assertSee($complaint->description);
    }

    public function test_complaint_lists_show_reporter_identity_and_can_search_contact_details(): void
    {
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $pic->update(['phone_number' => '081234567890']);
        $this->createComplaint($pic);

        $this->actingAs($admin)
            ->get(route('admin.complaints'))
            ->assertOk()
            ->assertSee('PIC User')
            ->assertSee('Username: pic')
            ->assertSee('Peran: PIC Ruang')
            ->assertSee('Email: pic@example.com')
            ->assertSee('Telepon: 081234567890');

        $this->actingAs($admin)
            ->get(route('admin.complaints', ['search' => '081234567890']))
            ->assertOk()
            ->assertSee('PIC User')
            ->assertSee('081234567890');

        $this->actingAs($pic)
            ->get(route('pic.complaints.index'))
            ->assertOk()
            ->assertSee('PIC User')
            ->assertSee('Username: pic')
            ->assertSee('PIC Ruang')
            ->assertSee('pic@example.com')
            ->assertSee('081234567890');

        $this->actingAs($pic)
            ->get(route('pic.complaints.index', ['search' => '081234567890']))
            ->assertOk()
            ->assertSee('PIC User');
    }

    public function test_admin_can_open_the_complaint_form_from_management_and_create_a_complaint(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $this->createComplaint($pic);

        $this->actingAs($admin)
            ->get(route('admin.complaints'))
            ->assertOk()
            ->assertDontSee('Buat Pengaduan Kerusakan')
            ->assertDontSee('Buat Pengaduan</a>');

        $this->actingAs($admin)
            ->get(route('admin.complaints.create'))
            ->assertOk()
            ->assertSee(route('admin.complaints.store'))
            ->assertSee(route('admin.complaints'));

        $room = Room::create([
            'room_name' => 'Ruang Admin Uji',
            'capacity' => 25,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.complaints.store'), [
                'room_id' => $room->id,
                'description' => 'Kerusakan dilaporkan oleh admin.',
                'photo' => UploadedFile::fake()->image('kerusakan.jpg'),
            ])
            ->assertRedirect(route('admin.complaints'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'room_id' => $room->id,
            'reporter_id' => $admin->id,
            'description' => 'Kerusakan dilaporkan oleh admin.',
            'status_repair' => 'Belum Diperbaiki',
        ]);
    }

    public function test_pending_and_approved_agendas_have_a_complaint_shortcut_but_rejected_agendas_do_not(): void
    {
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $room = Room::create([
            'room_name' => 'Ruang Agenda',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
        $pendingEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $admin->id,
            'event_name' => 'Agenda Menunggu',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
        ]);
        Event::create([
            'room_id' => $room->id,
            'pic_id' => $admin->id,
            'event_name' => 'Agenda Berlangsung',
            'date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'approved',
        ]);
        Event::create([
            'room_id' => $room->id,
            'pic_id' => $admin->id,
            'event_name' => 'Agenda Ditolak',
            'date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Buat Pengaduan Kerusakan')
            ->assertSee('Agenda Menunggu')
            ->assertSee('Agenda Berlangsung')
            ->assertSee('Agenda Ditolak');
        $this->assertSame(2, substr_count(
            $response->getContent(),
            route('admin.complaints.create', ['room_id' => $room->id])
        ));

        $this->actingAs($admin)
            ->get(route('admin.complaints.create', ['room_id' => $room->id]))
            ->assertOk()
            ->assertSee('value="'.$room->id.'" selected', false);
    }

    public function test_admin_can_open_whatsapp_with_a_reminder_for_the_assigned_pic(): void
    {
        $admin = $this->createUser('admin', 'Admin');
        $roomPic = $this->createUser('pic-ruang', 'PIC Ruang');
        $assignedPic = $this->createUser('pic-penanggung-jawab', 'PIC Ruang');
        $assignedPic->update(['phone_number' => '081234567890']);
        $complaint = $this->createComplaint($roomPic);
        $complaint->update(['handler_id' => $assignedPic->id]);

        $this->actingAs($admin)
            ->get(route('admin.complaints'))
            ->assertOk()
            ->assertSee('Tegur PIC')
            ->assertSee('Minta PIC segera memperbaiki')
            ->assertSee('Lanjutkan ke WhatsApp')
            ->assertSee('Tekan tombol kirim di WhatsApp');

        $this->actingAs($admin)
            ->post(route('admin.complaints.remind', $complaint), [
                'message' => 'Mohon segera perbaiki kerusakan ini.',
            ])
            ->assertRedirect('https://wa.me/6281234567890?text='.rawurlencode(
                "Yth. {$assignedPic->fullname},\n\n"
                    ."Teguran terkait laporan kerusakan:\n"
                    ."Laporan #{$complaint->id}\n"
                    ."Ruang: Ruang Uji\n"
                    ."Deskripsi: Perangkat perlu diperiksa.\n"
                    ."Pesan Admin: Mohon segera perbaiki kerusakan ini.\n\n"
                    .'Mohon segera ditindaklanjuti dan perbarui status perbaikannya. Terima kasih.'
            ));
    }

    public function test_admin_cannot_send_a_reminder_when_no_pic_is_assigned(): void
    {
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $complaint = $this->createComplaint($pic);
        $pic->update(['role_id' => $admin->role_id]);

        $this->actingAs($admin)
            ->post(route('admin.complaints.remind', $complaint), [
                'message' => 'Mohon segera perbaiki.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'PIC penanggung jawab laporan ini belum ditentukan.');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_admin_cannot_send_a_reminder_for_a_repaired_complaint(): void
    {
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $complaint = $this->createComplaint($pic);
        $complaint->update(['status_repair' => 'Sudah Diperbaiki']);

        $this->actingAs($admin)
            ->post(route('admin.complaints.remind', $complaint), [
                'message' => 'Mohon segera perbaiki.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Teguran tidak dapat dikirim karena laporan sudah diperbaiki.');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_admin_cannot_open_whatsapp_when_pic_phone_number_is_invalid(): void
    {
        config()->set('services.whatsapp.pic_phone_number', null);
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $complaint = $this->createComplaint($pic);

        $this->actingAs($admin)
            ->post(route('admin.complaints.remind', $complaint), [
                'message' => 'Mohon segera perbaiki.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Nomor WhatsApp PIC belum diisi atau formatnya tidak valid. Perbarui nomor telepon PIC terlebih dahulu.');
    }

    public function test_admin_reminder_uses_configured_whatsapp_number_when_pic_phone_is_missing(): void
    {
        config()->set('services.whatsapp.pic_phone_number', '083115722144');
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $complaint = $this->createComplaint($pic);

        $this->actingAs($admin)
            ->post(route('admin.complaints.remind', $complaint), [
                'message' => 'Mohon segera perbaiki.',
            ])
            ->assertRedirect('https://wa.me/6283115722144?text='.rawurlencode(
                "Yth. {$pic->fullname},\n\n"
                    ."Teguran terkait laporan kerusakan:\n"
                    ."Laporan #{$complaint->id}\n"
                    ."Ruang: Ruang Uji\n"
                    ."Deskripsi: Perangkat perlu diperiksa.\n"
                    ."Pesan Admin: Mohon segera perbaiki.\n\n"
                    .'Mohon segera ditindaklanjuti dan perbarui status perbaikannya. Terima kasih.'
            ));
    }

    public function test_waka_can_reassign_an_escalated_complaint_to_another_pic(): void
    {
        $waka = $this->createUser('waka-sapras', 'Waka Sapras');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $replacementPic = $this->createUser('pic-pengganti', 'PIC Ruang');
        $complaint = $this->createComplaint($pic);
        $complaint->update(['is_escalated' => true]);

        $this->actingAs($waka)
            ->patch(route('admin.complaints.reassign', $complaint), [
                'handler_id' => $replacementPic->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $replacementPic->id,
            'is_escalated' => true,
        ]);
    }

    public function test_escalation_command_notifies_waka_and_marks_only_overdue_unresolved_complaints(): void
    {
        $waka = $this->createUser('waka-sapras', 'Waka Sapras');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $overdue = $this->createComplaint($pic);
        $overdue->update(['reported_at' => now()->subDays(4)]);

        $recent = $this->createComplaint($pic);
        $recent->update(['reported_at' => now()->subDays(2)]);

        $resolved = $this->createComplaint($pic);
        $resolved->update([
            'reported_at' => now()->subDays(4),
            'status_repair' => 'Sudah Diperbaiki',
            'is_escalated' => true,
        ]);

        $this->artisan('complaints:escalate')
            ->expectsOutput('1 laporan dikirim sebagai notifikasi eskalasi kepada 1 Waka.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('complaints', [
            'id' => $overdue->id,
            'is_escalated' => true,
        ]);
        $this->assertDatabaseHas('complaints', [
            'id' => $recent->id,
            'is_escalated' => false,
        ]);
        $this->assertDatabaseHas('complaints', [
            'id' => $resolved->id,
            'is_escalated' => false,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $waka->id,
            'type' => ComplaintEscalated::class,
        ]);
    }

    public function test_reassigned_pic_can_access_and_update_escalated_complaint(): void
    {
        $roomPic = $this->createUser('pic-ruang', 'PIC Ruang');
        $replacementPic = $this->createUser('pic-pengganti', 'PIC Ruang');
        $complaint = $this->createComplaint($roomPic);
        $complaint->update([
            'handler_id' => $replacementPic->id,
            'is_escalated' => true,
        ]);

        $this->actingAs($replacementPic)
            ->get(route('pic.complaints.index'))
            ->assertOk()
            ->assertSee('Perangkat perlu diperiksa.');

        $this->actingAs($replacementPic)
            ->patch(route('pic.complaints.update-status', $complaint), [
                'status_repair' => 'Sudah Diperbaiki',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status_repair' => 'Sudah Diperbaiki',
            'is_escalated' => false,
        ]);
    }

    public function test_escalation_command_marks_only_overdue_unresolved_complaints(): void
    {
        $waka = $this->createUser('waka-sapras', 'Waka Sapras');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $overdue = $this->createComplaint($pic);
        $overdue->update(['reported_at' => now()->subDays(4)]);

        $recent = $this->createComplaint($pic);
        $recent->update(['reported_at' => now()->subDays(2)]);

        $resolved = $this->createComplaint($pic);
        $resolved->update([
            'reported_at' => now()->subDays(4),
            'status_repair' => 'Sudah Diperbaiki',
            'is_escalated' => true,
        ]);

        $this->artisan('complaints:escalate')
            ->expectsOutput('1 laporan dikirim sebagai notifikasi eskalasi kepada 1 Waka.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('complaints', [
            'id' => $overdue->id,
            'is_escalated' => true,
        ]);
        $this->assertDatabaseHas('complaints', [
            'id' => $recent->id,
            'is_escalated' => false,
        ]);
        $this->assertDatabaseHas('complaints', [
            'id' => $resolved->id,
            'is_escalated' => false,
        ]);

        $this->actingAs($waka)
            ->get(route('admin.complaints'))
            ->assertOk()
            ->assertSee('Eskalasi Otomatis')
            ->assertSee('Notifikasi eskalasi');
    }

    public function test_waka_cannot_reassign_a_complaint_that_is_not_escalated(): void
    {
        $waka = $this->createUser('waka-sapras', 'Waka Sapras');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $replacementPic = $this->createUser('pic-pengganti', 'PIC Ruang');
        $complaint = $this->createComplaint($pic);

        $this->actingAs($waka)
            ->patch(route('admin.complaints.reassign', $complaint), [
                'handler_id' => $replacementPic->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => null,
        ]);
    }

    private function createUser(string $username, string $roleName): User
    {
        $role = new Role;
        $role->role_name = $roleName;
        $role->save();

        $user = User::create([
            'username' => $username,
            'fullname' => $roleName === 'PIC Ruang' ? 'PIC User' : ucwords(str_replace('-', ' ', $username)),
            'email' => $username.'@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function createComplaint(User $pic): Complaint
    {
        $room = Room::create([
            'room_name' => 'Ruang Uji',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        return Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $pic->id,
            'description' => 'Perangkat perlu diperiksa.',
            'photo_path' => 'complaints/uji.jpg',
            'status_repair' => 'Belum Diperbaiki',
        ]);
    }

    private function createRoomWithComplaint(User $pic, string $roomName, string $description): Room
    {
        $room = Room::create([
            'room_name' => $roomName,
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $pic->id,
            'description' => $description,
            'photo_path' => 'complaints/uji.jpg',
            'status_repair' => 'Belum Diperbaiki',
        ]);

        return $room;
    }
}

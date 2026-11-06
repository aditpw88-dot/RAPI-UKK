<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruComplaintHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_complaint_history_only_shows_reports_created_by_the_logged_in_user(): void
    {
        $reporter = $this->createUser('Guru/Organisasi', 'Pelapor Aktif');
        $otherReporter = $this->createUser('Guru/Organisasi', 'Pelapor Lain');
        $pic = $this->createUser('PIC Ruang', 'PIC Pengelola');
        $room = $this->createRoom($pic);
        $ownComplaint = $this->createComplaint($room, $reporter, 'Kerusakan proyektor kelas saya.', 'Belum Diperbaiki');
        $ownComplaint->update(['handler_id' => $pic->id]);
        $reporter->update(['phone_number' => '081234567890']);
        $this->createComplaint($room, $otherReporter, 'Kerusakan kipas ruang lain.', 'Sudah Diperbaiki');

        $this->actingAs($reporter)
            ->get(route('guru.complaints.status'))
            ->assertOk()
            ->assertSee('Riwayat Pengaduan Saya')
            ->assertSee('Daftar ini hanya menampilkan pengaduan yang dikirim dari akun Anda.')
            ->assertSee('Kerusakan proyektor kelas saya.')
            ->assertSee('Pelapor Aktif')
            ->assertSee('Username: '.$reporter->username)
            ->assertSee('· Guru/Organisasi')
            ->assertSee($reporter->email)
            ->assertSee('Telepon: 081234567890')
            ->assertSee('Belum Diperbaiki')
            ->assertSee('PIC Pengelola')
            ->assertSee('Total Pengaduan Saya')
            ->assertDontSee('Kerusakan kipas ruang lain.')
            ->assertDontSee('Pelapor Lain');
    }

    public function test_complaint_status_summary_counts_only_the_logged_in_users_reports(): void
    {
        $reporter = $this->createUser('Guru/Organisasi', 'Pelapor Aktif');
        $otherReporter = $this->createUser('Guru/Organisasi', 'Pelapor Lain');
        $pic = $this->createUser('PIC Ruang', 'PIC Pengelola');
        $room = $this->createRoom($pic);
        $this->createComplaint($room, $reporter, 'Proyektor rusak.', 'Belum Diperbaiki');
        $this->createComplaint($room, $reporter, 'Papan tulis rusak.', 'Sudah Diperbaiki');
        $this->createComplaint($room, $otherReporter, 'Kipas rusak.', 'Menunggu Spare Part');

        $this->actingAs($reporter)
            ->get(route('guru.complaints.status'))
            ->assertOk()
            ->assertSee('>2</p>', false)
            ->assertSee('>1</p>', false)
            ->assertDontSee('>3</p>', false);
    }

    public function test_guru_event_history_has_a_shortcut_to_the_complaint_form(): void
    {
        $guru = $this->createUser('Guru/Organisasi', 'Guru Shortcut');
        $pic = $this->createUser('PIC Ruang', 'PIC Shortcut');
        $room = $this->createRoom($pic);
        Event::create([
            'room_id' => $room->id,
            'pic_id' => $guru->id,
            'event_name' => 'Agenda Pengujian Shortcut',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
        ]);

        $this->actingAs($guru)
            ->get(route('guru.events.history'))
            ->assertOk()
            ->assertSee('Buat Pengajuan Kerusakan')
            ->assertSee('>Catatan</th>', false)
            ->assertSee('>Aksi</th>', false)
            ->assertSee('Agenda Pengujian Shortcut')
            ->assertSee('href="'.route('guru.complaints.create', ['room_id' => $room->id]).'"', false);

        $this->actingAs($guru)
            ->get(route('guru.complaints.create', ['room_id' => $room->id]))
            ->assertOk()
            ->assertSee('Buat Laporan Baru')
            ->assertSee('value="'.$room->id.'" selected', false);
    }

    private function createUser(string $roleName, string $name): User
    {
        $role = Role::create(['role_name' => $roleName]);

        return User::create([
            'username' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'fullname' => $name,
            'email' => uniqid().'@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
    }

    private function createRoom(User $pic): Room
    {
        return Room::create([
            'room_name' => 'Ruang Uji',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
    }

    private function createComplaint(Room $room, User $reporter, string $description, string $status): Complaint
    {
        return Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $reporter->id,
            'description' => $description,
            'photo_path' => 'complaints/uji.jpg',
            'status_repair' => $status,
        ]);
    }
}

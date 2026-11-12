<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_waka_can_view_monthly_borrowing_report_without_damage_reports(): void
    {
        $waka = $this->createUser('Waka Sapras');
        $pic = $this->createUser('PIC Ruang');
        $pic->update(['phone_number' => '081234567890']);
        $room = $this->createRoom($pic);
        $this->createEvent($room, $pic, '2026-10-03');
        $this->createComplaint($room, $pic, '2026-10-04 09:30:00');
        $this->createEvent($room, $pic, '2026-09-30');
        $this->createComplaint($room, $pic, '2026-11-01 09:30:00');

        $this->actingAs($waka)
            ->get(route('reports.monthly', ['period_type' => 'monthly', 'period_value' => '2026-10']))
            ->assertOk()
            ->assertSee('Laporan Bulanan Peminjaman Ruang')
            ->assertSee('Rapat Bulanan')
            ->assertDontSee('Kursi rusak')
            ->assertSee($pic->fullname)
            ->assertSee($pic->username)
            ->assertSee($pic->email)
            ->assertSee('081234567890')
            ->assertDontSee('Agenda 2026-09-30')
            ->assertDontSee('Lampu rusak')
            ->assertSee('Laporan Peminjaman')
            ->assertSee('Sesi 3 - Sesi 4')
            ->assertSee(route('reports.monthly.csv', ['period_type' => 'monthly', 'period_value' => '2026-10']))
            ->assertSee(route('reports.monthly.pdf', ['period_type' => 'monthly', 'period_value' => '2026-10']))
            ->assertSee('Cetak / Simpan PDF');
    }

    public function test_admin_sees_the_monthly_report_shortcut_in_the_sidebar(): void
    {
        $admin = $this->createUser('Admin');

        $this->actingAs($admin)
            ->get(route('admin.rooms'))
            ->assertOk()
            ->assertSee('Laporan Peminjaman')
            ->assertSee(route('reports.monthly'));
    }

    public function test_pic_can_view_its_damage_reports_by_period(): void
    {
        $pic = $this->createUser('PIC Ruang');
        $otherPic = $this->createUser('PIC Ruang');
        $assignedRoom = $this->createRoom($pic);
        $otherRoom = $this->createRoom($otherPic);
        $this->createComplaint($assignedRoom, $pic, '2026-10-19 09:30:00');
        $outsidePeriod = $this->createComplaint($assignedRoom, $pic, '2026-10-26 09:30:00');
        $outsidePeriod->update(['description' => 'Kerusakan di luar minggu']);
        $otherRoomComplaint = $this->createComplaint($otherRoom, $pic, '2026-10-19 09:30:00');
        $otherRoomComplaint->update(['description' => 'Kerusakan ruang PIC lain']);

        $this->actingAs($pic)
            ->get(route('pic.reports.complaints', ['period_type' => 'weekly', 'period_value' => '2026-W43']))
            ->assertOk()
            ->assertSee('Laporan Kerusakan Mingguan')
            ->assertSee('Kursi rusak')
            ->assertDontSee('Kerusakan di luar minggu')
            ->assertDontSee('Kerusakan ruang PIC lain');
    }

    public function test_pic_report_downloads_csv_and_pdf_for_selected_period(): void
    {
        $pic = $this->createUser('PIC Ruang');
        $room = $this->createRoom($pic);
        $this->createComplaint($room, $pic, '2026-10-19 09:30:00');

        $this->actingAs($pic)
            ->get(route('pic.reports.complaints.csv', ['period_type' => 'weekly', 'period_value' => '2026-W43']))
            ->assertOk()
            ->assertDownload('laporan-kerusakan-mingguan-2026-10-19.csv');

        $this->actingAs($pic)
            ->get(route('pic.reports.complaints.pdf', ['period_type' => 'daily', 'period_value' => '2026-10-19']))
            ->assertOk()
            ->assertDownload('laporan-kerusakan-harian-2026-10-19.pdf')
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_non_pic_cannot_access_pic_damage_reports(): void
    {
        $admin = $this->createUser('Admin');

        $this->actingAs($admin)
            ->get(route('pic.reports.complaints'))
            ->assertForbidden();
    }

    public function test_waka_can_download_monthly_report_as_csv(): void
    {
        $waka = $this->createUser('Waka Sapras');
        $pic = $this->createUser('PIC Ruang');
        $pic->update(['phone_number' => '081234567890']);
        $room = $this->createRoom($pic);
        $this->createEvent($room, $pic, '2026-10-03');
        $this->createComplaint($room, $pic, '2026-10-04 09:30:00');

        $response = $this->actingAs($waka)
            ->get(route('reports.monthly.csv', ['period_type' => 'monthly', 'period_value' => '2026-10']))
            ->assertOk()
            ->assertDownload('laporan-bulanan-2026-10.csv');

        ob_start();
        $response->getCallback()();
        $csv = ob_get_clean();

        $this->assertStringContainsString('Rapat Bulanan', $csv);
        $this->assertStringNotContainsString('Kursi rusak', $csv);
        $this->assertStringContainsString($pic->fullname, $csv);
        $this->assertStringContainsString($pic->username, $csv);
        $this->assertStringContainsString('PIC Ruang', $csv);
        $this->assertStringContainsString($pic->email, $csv);
        $this->assertStringContainsString('081234567890', $csv);
    }

    public function test_waka_can_download_monthly_report_as_pdf(): void
    {
        $waka = $this->createUser('Waka Sapras');
        $pic = $this->createUser('PIC Ruang');
        $room = $this->createRoom($pic);
        $this->createEvent($room, $pic, '2026-10-03');
        $this->createComplaint($room, $pic, '2026-10-04 09:30:00');

        $this->actingAs($waka)
            ->get(route('reports.monthly.pdf', ['period_type' => 'monthly', 'period_value' => '2026-10']))
            ->assertOk()
            ->assertDownload('laporan-bulanan-2026-10.pdf')
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF', false);
    }

    public function test_non_admin_and_non_waka_cannot_access_monthly_reports(): void
    {
        $guru = $this->createUser('Guru/Organisasi');

        $this->actingAs($guru)
            ->get(route('reports.monthly'))
            ->assertForbidden();
    }

    public function test_report_month_must_use_year_and_month_format(): void
    {
        $waka = $this->createUser('Waka Sapras');

        $this->actingAs($waka)
            ->get(route('reports.monthly', ['period_type' => 'monthly', 'period_value' => 'not-a-month']))
            ->assertSessionHasErrors('period_value');
    }

    public function test_daily_report_filters_events_and_complaints_by_day(): void
    {
        $waka = $this->createUser('Waka Sapras');
        $pic = $this->createUser('PIC Ruang');
        $room = $this->createRoom($pic);
        $this->createEvent($room, $pic, '2026-10-03');
        $this->createEvent($room, $pic, '2026-10-04');
        $this->createComplaint($room, $pic, '2026-10-03 09:30:00');
        $this->createComplaint($room, $pic, '2026-10-04 09:30:00');

        $this->actingAs($waka)
            ->get(route('reports.monthly', ['period_type' => 'daily', 'period_value' => '2026-10-03']))
            ->assertOk()
            ->assertSee('Laporan Harian Peminjaman Ruang')
            ->assertSee('Rapat Bulanan')
            ->assertDontSee('Kursi rusak')
            ->assertDontSee('Agenda 2026-10-04')
            ->assertDontSee('Lampu rusak')
            ->assertDontSee('Pengaduan Kerusakan');
    }

    public function test_weekly_report_filters_full_iso_week_from_monday_to_sunday(): void
    {
        $waka = $this->createUser('Waka Sapras');
        $pic = $this->createUser('PIC Ruang');
        $room = $this->createRoom($pic);
        $this->createEvent($room, $pic, '2026-10-19');
        $this->createEvent($room, $pic, '2026-10-25');
        $this->createEvent($room, $pic, '2026-10-26');
        $this->createComplaint($room, $pic, '2026-10-19 09:30:00');
        $this->createComplaint($room, $pic, '2026-10-26 09:30:00');

        $this->actingAs($waka)
            ->get(route('reports.monthly', ['period_type' => 'weekly', 'period_value' => '2026-W43']))
            ->assertOk()
            ->assertSee('Laporan Mingguan Peminjaman Ruang')
            ->assertSee('Agenda 2026-10-19')
            ->assertSee('Agenda 2026-10-25')
            ->assertDontSee('Agenda 2026-10-26')
            ->assertDontSee('Kursi rusak')
            ->assertDontSee('Lampu rusak');
    }

    public function test_daily_and_weekly_report_exports_use_the_selected_period(): void
    {
        $waka = $this->createUser('Waka Sapras');
        $pic = $this->createUser('PIC Ruang');
        $room = $this->createRoom($pic);
        $this->createEvent($room, $pic, '2026-10-19');

        $this->actingAs($waka)
            ->get(route('reports.monthly.csv', ['period_type' => 'weekly', 'period_value' => '2026-W43']))
            ->assertOk()
            ->assertDownload('laporan-mingguan-2026-10-19.csv');

        $this->actingAs($waka)
            ->get(route('reports.monthly.pdf', ['period_type' => 'daily', 'period_value' => '2026-10-19']))
            ->assertOk()
            ->assertDownload('laporan-harian-2026-10-19.pdf');
    }

    public function test_invalid_iso_week_is_rejected(): void
    {
        $waka = $this->createUser('Waka Sapras');

        $this->actingAs($waka)
            ->get(route('reports.monthly', ['period_type' => 'weekly', 'period_value' => '2026-W54']))
            ->assertSessionHasErrors('period_value');
    }

    public function test_completed_event_older_than_24_hours_disappears_from_admin_events_but_remains_in_monthly_report(): void
    {
        $admin = $this->createUser('Admin');
        $pic = $this->createUser('PIC Ruang');
        $room = $this->createRoom($pic);

        // Event A: Selesai 3 hari yang lalu (> 24 jam)
        $oldEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Agenda Jadul Sudah Lewat 24 Jam',
            'date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->subDays(3)->toDateString(),
            'start_time' => '07:15:00',
            'end_time' => '09:00:00',
            'status' => 'approved',
            'completed_at' => now()->subDays(3)->setTime(9, 0),
        ]);

        // Event B: Selesai 2 jam yang lalu (< 24 jam)
        $recentEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Agenda Baru Selesai 2 Jam Lalu',
            'date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_time' => now()->subHours(3)->format('H:i:s'),
            'end_time' => now()->subHours(2)->format('H:i:s'),
            'status' => 'approved',
            'completed_at' => now()->subHours(2),
        ]);

        // 1. Cek Manajemen Agenda (admin.events):
        // Event lama (> 24 jam) HARUS hilang, sedangkan event baru (< 24 jam) tetap terlihat
        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertDontSee('Agenda Jadul Sudah Lewat 24 Jam')
            ->assertSee('Agenda Baru Selesai 2 Jam Lalu');

        // 2. Cek Laporan Peminjaman (reports.monthly):
        // Kedua agenda HARUS tetap tercatat di laporan peminjaman
        $this->actingAs($admin)
            ->get(route('reports.monthly', [
                'period_type' => 'monthly',
                'period_value' => now()->format('Y-m'),
            ]))
            ->assertOk()
            ->assertSee('Agenda Jadul Sudah Lewat 24 Jam')
            ->assertSee('Agenda Baru Selesai 2 Jam Lalu');
    }

    public function test_monthly_report_supports_search_and_filters(): void
    {
        $admin = $this->createUser('Admin');
        $pic = $this->createUser('PIC Ruang');
        $room1 = $this->createRoom($pic);
        $room2 = Room::create([
            'room_name' => 'Ruang Laboratorium Khusus',
            'capacity' => 25,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        Event::create([
            'room_id' => $room1->id,
            'pic_id' => $pic->id,
            'event_name' => 'Pelatihan Guru Umum',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
        ]);

        Event::create([
            'room_id' => $room2->id,
            'pic_id' => $pic->id,
            'event_name' => 'Workshop Robotik Khusus',
            'date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'approved',
        ]);

        // Cari berdasarkan teks 'Robotik'
        $this->actingAs($admin)
            ->get(route('reports.monthly', [
                'period_type' => 'monthly',
                'period_value' => now()->format('Y-m'),
                'search' => 'Robotik',
            ]))
            ->assertOk()
            ->assertSee('Workshop Robotik Khusus')
            ->assertDontSee('Pelatihan Guru Umum');

        // Filter berdasarkan ruangan
        $this->actingAs($admin)
            ->get(route('reports.monthly', [
                'period_type' => 'monthly',
                'period_value' => now()->format('Y-m'),
                'room_id' => $room2->id,
            ]))
            ->assertOk()
            ->assertSee('Workshop Robotik Khusus')
            ->assertDontSee('Pelatihan Guru Umum');
    }

    public function test_monthly_report_is_sorted_by_newest_created_event(): void
    {
        $admin = $this->createUser('Admin');
        $pic = $this->createUser('PIC Ruang');
        $room = $this->createRoom($pic);

        $event1 = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Agenda Dibuat Pertama',
            'date' => '2026-11-20',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
            'created_at' => now()->subDays(2),
        ]);

        $event2 = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Agenda Dibuat Terakhir (Paling Baru)',
            'date' => '2026-11-05',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'approved',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.monthly', [
                'period_type' => 'monthly',
                'period_value' => '2026-11',
            ]))
            ->assertOk();

        $events = $response->viewData('events');
        $this->assertSame($event2->id, $events->first()->id);
        $this->assertSame($event1->id, $events->last()->id);
    }

    private function createUser(string $roleName): User
    {
        $role = Role::create(['role_name' => $roleName]);

        return User::create([
            'username' => strtolower(str_replace([' ', '/'], '-', $roleName)).'-'.uniqid(),
            'fullname' => $roleName.' User',
            'email' => uniqid().'@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
    }

    private function createRoom(User $pic): Room
    {
        return Room::create([
            'room_name' => 'Ruang Laporan',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
    }

    private function createEvent(Room $room, User $pic, string $date): Event
    {
        return Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => $date === '2026-10-03' ? 'Rapat Bulanan' : 'Agenda '.$date,
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'approved',
        ]);
    }

    private function createComplaint(Room $room, User $reporter, string $reportedAt): Complaint
    {
        return Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $reporter->id,
            'description' => str_starts_with($reportedAt, '2026-10') ? 'Kursi rusak' : 'Lampu rusak',
            'photo_path' => 'complaints/report.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => $reportedAt,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PicComplaintAutoHideAndFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $pic;
    private Room $room1;
    private Room $room2;

    protected function setUp(): void
    {
        parent::setUp();

        $picRole = Role::firstOrCreate(['role_name' => 'PIC Ruang']);

        $this->pic = User::factory()->create([
            'role_id' => $picRole->id,
            'fullname' => 'PIC Penguji',
            'email' => 'pic.test@sekolah.sch.id',
            'username' => 'pictest',
        ]);

        $this->room1 = Room::create([
            'room_name' => 'Lab Komputer 1',
            'capacity' => 36,
            'pic_id' => $this->pic->id,
            'is_active' => 'Aktif',
        ]);

        $this->room2 = Room::create([
            'room_name' => 'Lab Multimedia',
            'capacity' => 32,
            'pic_id' => $this->pic->id,
            'is_active' => 'Aktif',
        ]);
    }

    public function test_complaint_repaired_more_than_24_hours_ago_is_hidden_from_pic_management_but_visible_in_reports(): void
    {
        // 1. Complaint repaired 25 hours ago
        $oldRepaired = Complaint::create([
            'room_id' => $this->room1->id,
            'reporter_id' => $this->pic->id,
            'description' => 'Kerusakan kabel VGA lama',
            'photo_path' => 'complaints/test1.jpg',
            'status_repair' => 'Sudah Diperbaiki',
            'reported_at' => now()->subDays(3),
            'repaired_at' => now()->subHours(25),
        ]);

        // 2. Complaint repaired 2 hours ago
        $recentRepaired = Complaint::create([
            'room_id' => $this->room1->id,
            'reporter_id' => $this->pic->id,
            'description' => 'Kerusakan mouse baru selesai',
            'photo_path' => 'complaints/test2.jpg',
            'status_repair' => 'Sudah Diperbaiki',
            'reported_at' => now()->subHours(5),
            'repaired_at' => now()->subHours(2),
        ]);

        // 3. Complaint not repaired yet
        $unrepaired = Complaint::create([
            'room_id' => $this->room2->id,
            'reporter_id' => $this->pic->id,
            'description' => 'AC mati total',
            'photo_path' => 'complaints/test3.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now()->subHour(),
            'repaired_at' => null,
        ]);

        // Visit PIC Management (Manajemen Kerusakan)
        $response = $this->actingAs($this->pic)->get(route('pic.complaints.index'));
        $response->assertOk();
        $response->assertSee('Kerusakan mouse baru selesai');
        $response->assertSee('AC mati total');
        $response->assertDontSee('Kerusakan kabel VGA lama');

        // Visit PIC Reports (Laporan Kerusakan) - ALL must be visible!
        $reportResponse = $this->actingAs($this->pic)->get(route('pic.reports.complaints', [
            'period_type' => 'monthly',
            'period_value' => now()->format('Y-m'),
        ]));
        $reportResponse->assertOk();
        $reportResponse->assertSee('Kerusakan kabel VGA lama');
        $reportResponse->assertSee('Kerusakan mouse baru selesai');
        $reportResponse->assertSee('AC mati total');
    }

    public function test_updating_status_to_repaired_records_repaired_at_timestamp(): void
    {
        $complaint = Complaint::create([
            'room_id' => $this->room1->id,
            'reporter_id' => $this->pic->id,
            'description' => 'Proyektor buram',
            'photo_path' => 'complaints/test4.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'repaired_at' => null,
        ]);

        $response = $this->actingAs($this->pic)->patch(route('pic.complaints.update-status', $complaint), [
            'status_repair' => 'Sudah Diperbaiki',
        ]);

        $response->assertRedirect();
        $complaint->refresh();
        $this->assertEquals('Sudah Diperbaiki', $complaint->status_repair);
        $this->assertNotNull($complaint->repaired_at);
    }

    public function test_pic_complaints_report_filtering_by_status_and_room_and_search(): void
    {
        Complaint::create([
            'room_id' => $this->room1->id,
            'reporter_id' => $this->pic->id,
            'description' => 'Keyboard tombol enter lepas',
            'photo_path' => 'complaints/test5.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);

        Complaint::create([
            'room_id' => $this->room2->id,
            'reporter_id' => $this->pic->id,
            'description' => 'Lampu LED proyektor mati',
            'photo_path' => 'complaints/test6.jpg',
            'status_repair' => 'Menunggu Spare Part',
            'reported_at' => now(),
        ]);

        // Filter by room 1
        $response = $this->actingAs($this->pic)->get(route('pic.reports.complaints', [
            'period_type' => 'monthly',
            'period_value' => now()->format('Y-m'),
            'room_id' => $this->room1->id,
        ]));
        $response->assertOk();
        $response->assertSee('Keyboard tombol enter lepas');
        $response->assertDontSee('Lampu LED proyektor mati');

        // Filter by status 'Menunggu Spare Part'
        $responseStatus = $this->actingAs($this->pic)->get(route('pic.reports.complaints', [
            'period_type' => 'monthly',
            'period_value' => now()->format('Y-m'),
            'status' => 'Menunggu Spare Part',
        ]));
        $responseStatus->assertOk();
        $responseStatus->assertSee('Lampu LED proyektor mati');
        $responseStatus->assertDontSee('Keyboard tombol enter lepas');

        // Filter by search keyword
        $responseSearch = $this->actingAs($this->pic)->get(route('pic.reports.complaints', [
            'period_type' => 'monthly',
            'period_value' => now()->format('Y-m'),
            'search' => 'enter lepas',
        ]));
        $responseSearch->assertOk();
        $responseSearch->assertSee('Keyboard tombol enter lepas');
        $responseSearch->assertDontSee('Lampu LED proyektor mati');
    }
}

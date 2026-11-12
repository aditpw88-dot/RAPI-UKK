<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_national_holidays_are_loaded_and_limited_to_the_calendar_range(): void
    {
        Cache::forget('indonesia-holidays-remote');
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                '2026-03-18' => ['summary' => 'Cuti Bersama Nyepi'],
                '2026-03-19' => ['summary' => 'Hari Suci Nyepi'],
                '2026-03-20' => ['summary' => 'Cuti Bersama Idul Fitri'],
                'info' => ['author' => 'calendar source'],
            ]),
        ]);

        $response = $this->getJson('/calendar/holidays?start=2026-03-18&end=2026-03-20');

        $response
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.title', 'Cuti Bersama Nyepi')
            ->assertJsonPath('1.extendedProps.isHoliday', true);
    }

    public function test_national_calendar_includes_natal_and_national_holidays_without_external_api(): void
    {
        Cache::forget('indonesia-holidays-remote');

        $response = $this->getJson('/calendar/holidays?start=2026-12-01&end=2026-12-31');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Hari Raya Natal',
                'start' => '2026-12-25',
                'allDay' => true,
            ]);
    }

    public function test_calendar_event_colors_follow_the_selected_event_type(): void
    {
        $event = Event::make(['event_type' => 'akademik']);

        $this->assertSame([
            'backgroundColor' => '#ede9fe',
            'borderColor' => '#8b5cf6',
            'textColor' => '#5b21b6',
        ], $event->calendarColors());
    }

    public function test_calendars_show_agenda_names_and_open_the_full_day_list_before_details(): void
    {
        $picRole = Role::firstOrCreate(['role_name' => 'PIC Ruang']);
        $pic = User::create([
            'username' => 'pic-cal',
            'fullname' => 'PIC Calendar',
            'email' => 'pic-cal@example.com',
            'password' => 'password',
            'role_id' => $picRole->id,
        ]);
        $room = Room::create([
            'room_name' => 'Ruang Detail Agenda',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
        $tomorrow = now()->addDay()->toDateString();
        $event = Event::create([
            'room_id' => $room->id,
            'event_name' => 'Rapat Koordinasi',
            'event_type' => 'akademik',
            'date' => $tomorrow,
            'end_date' => $tomorrow,
            'start_time' => '09:30',
            'end_time' => '10:15',
            'status' => 'approved',
        ]);
        $event->rooms()->sync([$room->id]);

        foreach (['/', '/calendar'] as $calendarUrl) {
            $this->get($calendarUrl)
                ->assertOk()
                ->assertSee('"title":"Rapat Koordinasi"', false)
                ->assertDontSee('"title":"Rapat Koordinasi (Ruang Detail Agenda)"', false)
                ->assertSee('"roomName":"Ruang Detail Agenda"', false)
                ->assertSee('dayMaxEvents: false', false)
                ->assertSee('showDateAgendas(clickedDate)', false)
                ->assertSee('showEventDetails(event, dateString)', false);
        }
    }

    public function test_waka_can_create_a_school_wide_all_day_event_without_selecting_a_room(): void
    {
        $role = Role::create(['role_name' => 'Waka']);
        $waka = User::create([
            'username' => 'calendar-waka',
            'fullname' => 'Calendar Waka',
            'email' => 'calendar-waka@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);

        $this->actingAs($waka)
            ->postJson(route('admin.school-events.store'), [
                'event_name' => 'Upacara Sekolah',
                'event_type' => 'upacara',
                'date' => now()->addDay()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('title', 'Upacara Sekolah')
            ->assertJsonPath('roomName', 'Seluruh sekolah')
            ->assertJsonPath('allDay', true)
            ->assertJsonPath('backgroundColor', '#fee2e2');

        $this->assertDatabaseHas('events', [
            'event_name' => 'Upacara Sekolah',
            'room_id' => null,
            'status' => 'approved',
            'event_type' => 'upacara',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Upacara Sekolah')
            ->assertSee('Seluruh sekolah')
            ->assertSee('Sepanjang hari');
    }

    public function test_completed_agenda_disappears_from_student_calendar_grid_and_sidebar_and_remains_in_admin_loan_report(): void
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC Ruang']);

        $admin = User::create([
            'username' => 'admin-cal-24h',
            'fullname' => 'Admin Calendar 24h',
            'email' => 'admin-cal-24h@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        $pic = User::create([
            'username' => 'pic-cal-24h',
            'fullname' => 'PIC Calendar 24h',
            'email' => 'pic-cal-24h@example.com',
            'password' => 'password',
            'role_id' => $picRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab Multimedia Utama',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $now = now();

        // 1. Agenda completed > 24 hours ago (26 hours ago)
        $expiredDt = $now->copy()->subHours(26);
        $expiredEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Agenda Selesai Lebih 24 Jam',
            'event_type' => 'rapat',
            'date' => $expiredDt->toDateString(),
            'end_date' => $expiredDt->toDateString(),
            'start_time' => $expiredDt->copy()->subHour()->format('H:i:s'),
            'end_time' => $expiredDt->format('H:i:s'),
            'status' => 'approved',
        ]);
        $expiredEvent->rooms()->sync([$room->id]);

        // 2. Agenda completed di jam-jam sebelumnya (2 hours ago)
        $recentDt = $now->copy()->subHours(2);
        $recentEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Agenda Baru Selesai 2 Jam Lalu',
            'event_type' => 'akademik',
            'date' => $recentDt->toDateString(),
            'end_date' => $recentDt->toDateString(),
            'start_time' => $recentDt->copy()->subHour()->format('H:i:s'),
            'end_time' => $recentDt->format('H:i:s'),
            'status' => 'approved',
        ]);
        $recentEvent->rooms()->sync([$room->id]);

        // 3. Upcoming Agenda (tomorrow)
        $upcomingDt = $now->copy()->addDay();
        $upcomingEvent = Event::create([
            'room_id' => $room->id,
            'pic_id' => $pic->id,
            'event_name' => 'Agenda Besok Pagi',
            'event_type' => 'kegiatan',
            'date' => $upcomingDt->toDateString(),
            'end_date' => $upcomingDt->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'approved',
        ]);
        $upcomingEvent->rooms()->sync([$room->id]);

        // Check Student Calendar (/) and Internal Calendar (/calendar)
        foreach (['/', '/calendar'] as $calendarUrl) {
            $response = $this->get($calendarUrl)->assertOk();
            // Agenda yang sudah selesai di jam-jam sebelumnya & lebih dari 24 jam hilang dari kalender
            $response->assertDontSee('"title":"Agenda Selesai Lebih 24 Jam"', false);
            $response->assertDontSee('data-title="agenda selesai lebih 24 jam"', false);
            $response->assertDontSee('"title":"Agenda Baru Selesai 2 Jam Lalu"', false);
            $response->assertDontSee('data-title="agenda baru selesai 2 jam lalu"', false);

            // Agenda yang belum terlaksana tetap muncul di kalender dan sidebar
            $response->assertSee('"title":"Agenda Besok Pagi"', false);
            $response->assertSee('data-title="agenda besok pagi"', false);
        }

        // Check Admin Loan Report (Laporan Peminjaman) includes the completed agenda
        $this->actingAs($admin)
            ->get(route('reports.monthly', [
                'period_type' => 'daily',
                'period_value' => $expiredDt->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Agenda Selesai Lebih 24 Jam')
            ->assertSee('Sudah Terlaksana');
    }
}

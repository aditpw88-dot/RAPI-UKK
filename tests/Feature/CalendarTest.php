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
        Cache::forget('indonesia-holidays');
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
        $event = Event::create([
            'room_id' => $room->id,
            'event_name' => 'Rapat Koordinasi',
            'event_type' => 'akademik',
            'date' => '2026-11-10',
            'end_date' => '2026-11-10',
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
                ->assertSee('dayMaxEvents: 3', false)
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
}

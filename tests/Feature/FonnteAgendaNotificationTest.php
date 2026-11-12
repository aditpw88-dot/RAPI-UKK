<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteAgendaNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_teachers_grouped_by_mapel_in_create_agenda_form(): void
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $guruRole = Role::create(['role_name' => 'Guru']);

        $admin = User::create([
            'username' => 'adminwa',
            'fullname' => 'Admin WA',
            'email' => 'adminwa@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        User::create([
            'username' => 'guru_rpl_1',
            'fullname' => 'Budi Santoso, S.Kom',
            'email' => 'budi@example.com',
            'phone_number' => '081234567891',
            'mapel' => 'PRODUKTIF REKAYASA PERANGKAT LUNAK',
            'password' => 'password',
            'role_id' => $guruRole->id,
        ]);

        User::create([
            'username' => 'guru_mtk_1',
            'fullname' => 'Siti Aminah, S.Pd',
            'email' => 'siti@example.com',
            'phone_number' => '081234567892',
            'mapel' => 'MATEMATIKA',
            'password' => 'password',
            'role_id' => $guruRole->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.events'))
            ->assertOk()
            ->assertSee('Pengingat WhatsApp ke Guru (Per Mapel)')
            ->assertSee('PRODUKTIF REKAYASA PERANGKAT LUNAK')
            ->assertSee('Budi Santoso, S.Kom')
            ->assertSee('MATEMATIKA')
            ->assertSee('Siti Aminah, S.Pd');

        $this->actingAs($admin)
            ->get(route('admin.events.create'))
            ->assertOk()
            ->assertSee('Pengingat WhatsApp ke Guru (Per Mapel)')
            ->assertSee('PRODUKTIF REKAYASA PERANGKAT LUNAK')
            ->assertSee('MATEMATIKA');
    }

    public function test_storing_agenda_with_checked_teachers_schedules_fonnte_whatsapp_notification_one_hour_before(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true, 'detail' => 'success!'], 200),
        ]);

        config(['services.whatsapp.fonnte_token' => 'test-fonnte-token-123']);

        $adminRole = Role::create(['role_name' => 'Admin']);
        $guruRole = Role::create(['role_name' => 'Guru']);

        $admin = User::create([
            'username' => 'adminwa2',
            'fullname' => 'Admin WA 2',
            'email' => 'adminwa2@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
        ]);

        $guru1 = User::create([
            'username' => 'guru_rpl_a',
            'fullname' => 'Ahmad Dani',
            'email' => 'ahmad@example.com',
            'phone_number' => '081111222333',
            'mapel' => 'PRODUKTIF REKAYASA PERANGKAT LUNAK',
            'password' => 'password',
            'role_id' => $guruRole->id,
        ]);

        $guru2 = User::create([
            'username' => 'guru_rpl_b',
            'fullname' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
            'phone_number' => '082222333444',
            'mapel' => 'PRODUKTIF REKAYASA PERANGKAT LUNAK',
            'password' => 'password',
            'role_id' => $guruRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Ruang Rapat Utama',
            'room_type' => 'ruang',
            'capacity' => 50,
            'is_active' => 'Aktif',
            'pic_id' => $admin->id,
        ]);

        $date = now()->addDays(2)->toDateString();

        $response = $this->actingAs($admin)->post(route('admin.events.store'), [
            'event_name' => 'Rapat Koordinasi Kurikulum RPL',
            'event_type' => 'rapat',
            'room_ids' => [$room->id],
            'date' => $date,
            'end_date' => $date,
            'start_session' => 1,
            'end_session' => 3,
            'notify_teacher_ids' => [$guru1->id, $guru2->id],
        ]);

        $response->assertRedirect(route('admin.events'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString(
            'Pengingat WhatsApp otomatis telah dijadwalkan untuk 2 guru',
            (string) session('success')
        );

        $expectedSchedule = \Illuminate\Support\Carbon::parse("{$date} 07:15:00", 'Asia/Jakarta')->subHour()->getTimestamp();

        Http::assertSent(function ($request) use ($expectedSchedule) {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request->hasHeader('Authorization', 'test-fonnte-token-123')
                && $request['target'] === '081111222333|Ahmad Dani,082222333444|Dewi Lestari'
                && (int) ($request['schedule'] ?? 0) === $expectedSchedule
                && str_contains($request['message'], '{name}')
                && str_contains($request['message'], 'PENGINGAT AGENDA KEGIATAN SEKOLAH')
                && str_contains($request['message'], '1 jam ke depan')
                && str_contains($request['message'], 'Rapat Koordinasi Kurikulum RPL')
                && str_contains($request['message'], 'Ruang Rapat Utama')
                && str_contains($request['message'], 'Sesi 1 - Sesi 3');
        });
    }
}


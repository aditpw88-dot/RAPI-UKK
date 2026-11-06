<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Mail\SendOtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_can_sign_in_and_get_a_sanctum_token(): void
    {
        $user = $this->createUser('guru', 'Guru/Organisasi');

        $this->postJson('/api/login', [
            'login' => $user->username,
            'password' => 'password',
            'device_name' => 'Android',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.username', 'guru')
            ->assertJsonStructure(['token', 'user' => ['id', 'fullname', 'role']]);
    }

    public function test_mobile_user_can_sign_in_with_the_email_used_on_the_web(): void
    {
        $user = $this->createUser('guru', 'Guru/Organisasi');

        $this->postJson('/api/login', [
            'login' => $user->email,
            'password' => 'password',
            'device_name' => 'Flutter Web',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_mobile_sign_in_rejects_an_incorrect_password(): void
    {
        $user = $this->createUser('guru', 'Guru/Organisasi');

        $this->postJson('/api/login', [
            'login' => $user->email,
            'password' => 'incorrect',
            'device_name' => 'Flutter Web',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('login');
    }

    public function test_mobile_user_can_reset_a_password_with_an_email_otp(): void
    {
        Mail::fake();
        $user = $this->createUser('guru', 'Guru/Organisasi');

        $this->postJson('/api/password/forgot', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('success', true);
        Mail::assertSent(SendOtpMail::class);

        $otp = $user->fresh()->otp;
        $this->postJson('/api/password/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ])->assertOk();

        $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNull($user->fresh()->otp);
    }

    public function test_rooms_are_publicly_readable_but_only_admins_can_change_them(): void
    {
        $admin = $this->createUser('admin', 'Admin');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $room = Room::create([
            'room_name' => 'Ruang API',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $this->getJson('/api/rooms')
            ->assertOk()
            ->assertJsonPath('data.0.room_name', 'Ruang API');

        Sanctum::actingAs($pic);
        $this->postJson('/api/rooms', [
            'room_name' => 'Ruangan Tidak Sah',
            'capacity' => 20,
            'pic_id' => $pic->id,
        ])->assertForbidden();

        Sanctum::actingAs($admin);
        $this->patchJson("/api/rooms/{$room->id}", [
            'capacity' => 40,
        ])->assertOk()->assertJsonPath('data.capacity', 40);
    }

    public function test_teacher_can_create_a_non_conflicting_room_booking(): void
    {
        $teacher = $this->createUser('guru', 'Guru/Organisasi');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $room = Room::create([
            'room_name' => 'Ruang Rapat',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
        Sanctum::actingAs($teacher);
        $payload = [
            'event_name' => 'Rapat API',
            'room_id' => $room->id,
            'date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'start_session' => 2,
            'end_session' => 2,
        ];

        $this->postJson('/api/events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.start_time', '08:00')
            ->assertJsonPath('data.end_date', now()->addDays(4)->toDateString());
        $this->assertDatabaseHas('events', [
            'event_name' => 'Rapat API',
            'pic_id' => $teacher->id,
            'status' => 'pending',
        ]);
        $this->postJson('/api/events', $payload)->assertStatus(409);
    }

    public function test_dashboard_summary_returns_counts_and_only_five_approved_events(): void
    {
        $teacher = $this->createUser('guru', 'Guru/Organisasi');
        $room = Room::create([
            'room_name' => 'Ruang Dashboard',
            'capacity' => 30,
            'pic_id' => $teacher->id,
            'is_active' => 'Aktif',
        ]);

        foreach (range(1, 6) as $day) {
            Event::create([
                'room_id' => $room->id,
                'pic_id' => $teacher->id,
                'event_name' => "Agenda $day",
                'date' => now()->addDays($day)->toDateString(),
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'status' => 'approved',
            ]);
        }

        Event::create([
            'room_id' => $room->id,
            'pic_id' => $teacher->id,
            'event_name' => 'Agenda Menunggu',
            'date' => now()->addDays(8)->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => 'pending',
        ]);
        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $teacher->id,
            'description' => 'Lampu mati.',
            'photo_path' => 'complaints/lampu.jpg',
        ]);

        Sanctum::actingAs($teacher);
        $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.total_approved_events', 6)
            ->assertJsonPath('data.total_rooms', 1)
            ->assertJsonPath('data.my_events_count', 7)
            ->assertJsonPath('data.complaints_count', 1)
            ->assertJsonCount(5, 'data.upcoming_events');
    }

    public function test_assigned_pic_can_approve_an_event_but_another_pic_cannot(): void
    {
        $ownerPic = $this->createUser('pic1', 'PIC Ruang');
        $otherPic = $this->createUser('pic2', 'PIC Ruang');
        $teacher = $this->createUser('guru', 'Guru/Organisasi');
        $room = Room::create([
            'room_name' => 'Ruang Agenda',
            'capacity' => 30,
            'pic_id' => $ownerPic->id,
            'is_active' => 'Aktif',
        ]);
        $event = Event::create([
            'room_id' => $room->id,
            'pic_id' => $teacher->id,
            'event_name' => 'Agenda Menunggu',
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '07:15:00',
            'end_time' => '08:00:00',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($otherPic);
        $this->postJson("/api/events/{$event->id}/approve")->assertForbidden();

        Sanctum::actingAs($ownerPic);
        $this->postJson("/api/events/{$event->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_pic_can_view_all_agenda_statuses_for_assigned_rooms_only(): void
    {
        $pic = $this->createUser('pic1', 'PIC Ruang');
        $otherPic = $this->createUser('pic2', 'PIC Ruang');
        $teacher = $this->createUser('guru', 'Guru/Organisasi');
        $room = Room::create([
            'room_name' => 'Ruang Kelola',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);
        $otherRoom = Room::create([
            'room_name' => 'Ruang Lain',
            'capacity' => 30,
            'pic_id' => $otherPic->id,
            'is_active' => 'Aktif',
        ]);

        foreach (['approved', 'pending', 'rejected'] as $status) {
            Event::create([
                'room_id' => $room->id,
                'pic_id' => $teacher->id,
                'event_name' => "Agenda $status",
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '07:15:00',
                'end_time' => '08:00:00',
                'status' => $status,
            ]);
        }
        Event::create([
            'room_id' => $otherRoom->id,
            'pic_id' => $teacher->id,
            'event_name' => 'Agenda PIC lain',
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($pic);
        $this->getJson('/api/events/managed')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['status' => 'approved'])
            ->assertJsonFragment(['status' => 'pending'])
            ->assertJsonFragment(['status' => 'rejected'])
            ->assertJsonMissing(['event_name' => 'Agenda PIC lain']);
    }

    public function test_teacher_can_submit_a_complaint_and_assigned_pic_can_update_it(): void
    {
        Storage::fake('public');
        $teacher = $this->createUser('guru', 'Guru/Organisasi');
        $pic = $this->createUser('pic', 'PIC Ruang');
        $room = Room::create([
            'room_name' => 'Ruang Pengaduan',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        Sanctum::actingAs($teacher);
        $response = $this->post('/api/complaints', [
            'room_id' => $room->id,
            'description' => 'Lampu ruangan mati.',
            'photo' => UploadedFile::fake()->image('lampu.jpg'),
        ], ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonPath('data.status_repair', 'Belum Diperbaiki');
        $complaintId = $response->json('data.id');

        Sanctum::actingAs($pic);
        $this->patchJson("/api/complaints/{$complaintId}/status", [
            'status_repair' => 'Sudah Diperbaiki',
        ])->assertOk()->assertJsonPath('data.status_repair', 'Sudah Diperbaiki');

        $this->get('/api/pic/reports/complaints/pdf?period_type=monthly&period_value='.now()->format('Y-m'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
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
}

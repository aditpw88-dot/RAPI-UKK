<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PicRoomMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $roleName, array $overrides = []): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'phone_number' => '083115722144',
        ], $overrides));
    }

    public function test_pic_can_deactivate_managed_room_with_maintenance_note(): void
    {
        $pic = $this->createUserWithRole('PIC Ruang', ['fullname' => 'Budi PIC']);
        $room = Room::create([
            'room_name' => 'Lab 1',
            'capacity' => 36,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $response = $this->actingAs($pic)->patch(route('pic.rooms.update-status', $room), [
            'is_active' => 'Tidak aktif',
            'maintenance_note' => 'Sedang renovasi plafon dan pengecatan dinding sampai Jumat.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $room->refresh();
        $this->assertFalse($room->is_active);
        $this->assertSame('Sedang renovasi plafon dan pengecatan dinding sampai Jumat.', $room->maintenance_note);
    }

    public function test_pic_cannot_deactivate_room_without_maintenance_note(): void
    {
        $pic = $this->createUserWithRole('PIC Ruang');
        $room = Room::create([
            'room_name' => 'Lab 2',
            'capacity' => 30,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $response = $this->actingAs($pic)->patch(route('pic.rooms.update-status', $room), [
            'is_active' => 'Tidak aktif',
            'maintenance_note' => '   ',
        ]);

        $response->assertSessionHasErrors('maintenance_note');
        $this->assertTrue($room->fresh()->is_active);
    }

    public function test_other_pic_cannot_change_unassigned_room_status(): void
    {
        $pic1 = $this->createUserWithRole('PIC Ruang');
        $pic2 = $this->createUserWithRole('PIC Ruang');

        $room1 = Room::create([
            'room_name' => 'Lab 1',
            'capacity' => 30,
            'pic_id' => $pic1->id,
            'is_active' => 'Aktif',
        ]);
        Room::create([
            'room_name' => 'Lab 2',
            'capacity' => 30,
            'pic_id' => $pic2->id,
            'is_active' => 'Aktif',
        ]);

        $response = $this->actingAs($pic2)->patch(route('pic.rooms.update-status', $room1), [
            'is_active' => 'Tidak aktif',
            'maintenance_note' => 'Renovasi AC',
        ]);

        $response->assertForbidden();
        $this->assertTrue($room1->fresh()->is_active);
    }

    public function test_inactive_room_renders_maintenance_attributes_and_blocks_booking(): void
    {
        $pic = $this->createUserWithRole('PIC Ruang', [
            'fullname' => 'Pak Hendra PIC',
            'phone_number' => '083115722144',
        ]);
        $guru = $this->createUserWithRole('Guru');

        $room = Room::create([
            'room_name' => 'Ruang Wijaya',
            'capacity' => 100,
            'pic_id' => $pic->id,
            'is_active' => 'Tidak aktif',
            'maintenance_note' => 'Perbaikan sound system dan AC utama.',
        ]);

        $createPage = $this->actingAs($guru)->get(route('events.create'));
        $createPage->assertOk();
        $createPage->assertSee('Perbaikan sound system dan AC utama.');
        $createPage->assertSee('wa.me/6283115722144');

        $storeResp = $this->actingAs($guru)->post(route('events.store'), [
            'event_name' => 'Rapat Guru',
            'event_type' => 'rapat',
            'room_ids' => [$room->id],
            'date' => now()->addDays(2)->toDateString(),
            'start_session' => 1,
            'end_session' => 3,
        ]);

        $storeResp->assertSessionHasErrors('room_ids');
    }

    public function test_api_allows_pic_to_update_room_status_and_returns_maintenance_info(): void
    {
        $pic = $this->createUserWithRole('PIC Ruang', [
            'fullname' => 'PIC Mobile',
            'phone_number' => '083115722144',
        ]);
        $room = Room::create([
            'room_name' => 'DAK',
            'capacity' => 80,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $patchResp = $this->actingAs($pic, 'sanctum')->patchJson("/api/rooms/{$room->id}/status", [
            'is_active' => false,
            'maintenance_note' => 'Renovasi lantai keramik.',
        ]);

        $patchResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.maintenance_note', 'Renovasi lantai keramik.');

        $listResp = $this->getJson('/api/rooms');
        $listResp->assertOk()
            ->assertJsonPath('data.0.room_name', 'DAK')
            ->assertJsonPath('data.0.maintenance_note', 'Renovasi lantai keramik.')
            ->assertJsonPath('data.0.pic.phone_number', '083115722144');
    }

    public function test_pic_cannot_deactivate_room_if_agenda_is_currently_in_progress_and_receives_whatsapp_link_to_borrower(): void
    {
        $pic = $this->createUserWithRole('PIC Ruang', [
            'fullname' => 'Pak Joko PIC',
            'phone_number' => '083115722144',
        ]);
        $borrower = $this->createUserWithRole('Guru', [
            'fullname' => 'Ibu Siti Guru',
            'phone_number' => '081234567890',
        ]);

        $room = Room::create([
            'room_name' => 'Lab Komputer 1',
            'capacity' => 36,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $today = now()->toDateString();
        $startTime = now()->subMinutes(30)->format('H:i:s');
        $endTime = now()->addMinutes(60)->format('H:i:s');

        $event = Event::create([
            'event_name' => 'Ujian Praktik Multimedia',
            'event_type' => 'akademik',
            'room_id' => $room->id,
            'pic_id' => $borrower->id,
            'date' => $today,
            'end_date' => $today,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'approved',
            'approved_by' => $pic->id,
        ]);
        $event->rooms()->sync([$room->id]);

        $this->assertEquals('in_progress', $event->lifecycle_status);
        $this->assertNotNull($room->getOngoingEvent());

        $response = $this->actingAs($pic)->patch(route('pic.rooms.update-status', $room), [
            'is_active' => 'Tidak aktif',
            'maintenance_note' => 'Ruangan mau direnovasi plafon.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $response->assertSessionHas('ongoing_conflict');

        $conflict = session('ongoing_conflict');
        $this->assertIsArray($conflict);
        $this->assertSame('Lab Komputer 1', $conflict['room_name']);
        $this->assertSame('Ujian Praktik Multimedia', $conflict['event_name']);
        $this->assertSame('Ibu Siti Guru', $conflict['borrower_name']);
        $this->assertStringContainsString('https://wa.me/6281234567890', $conflict['whatsapp_url']);

        $this->assertTrue($room->fresh()->is_active);

        // Access rooms page to ensure the WhatsApp coordination link appears in the UI
        $pageResponse = $this->actingAs($pic)->get(route('pic.rooms'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Hubungi Ibu Siti Guru via WhatsApp');
        $pageResponse->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_api_rejects_deactivating_room_with_ongoing_event_and_returns_whatsapp_link(): void
    {
        $pic = $this->createUserWithRole('PIC Ruang', [
            'fullname' => 'PIC Mobile',
            'phone_number' => '083115722144',
        ]);
        $borrower = $this->createUserWithRole('Guru', [
            'fullname' => 'Bapak Ahmad',
            'phone_number' => '089876543210',
        ]);

        $room = Room::create([
            'room_name' => 'Lab IPA',
            'capacity' => 40,
            'pic_id' => $pic->id,
            'is_active' => 'Aktif',
        ]);

        $today = now()->toDateString();
        $startTime = now()->subMinutes(20)->format('H:i:s');
        $endTime = now()->addMinutes(45)->format('H:i:s');

        $event = Event::create([
            'event_name' => 'Praktikum Kimia Organik',
            'event_type' => 'akademik',
            'room_id' => $room->id,
            'pic_id' => $borrower->id,
            'date' => $today,
            'end_date' => $today,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'approved',
            'approved_by' => $pic->id,
        ]);
        $event->rooms()->sync([$room->id]);

        $response = $this->actingAs($pic, 'sanctum')->patchJson("/api/rooms/{$room->id}/status", [
            'is_active' => false,
            'maintenance_note' => 'Perbaikan keran air lab.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('ongoing_conflict.room_name', 'Lab IPA')
            ->assertJsonPath('ongoing_conflict.borrower_name', 'Bapak Ahmad');

        $this->assertStringContainsString('https://wa.me/6289876543210', (string) $response->json('whatsapp_url'));
        $this->assertTrue($room->fresh()->is_active);
    }
}


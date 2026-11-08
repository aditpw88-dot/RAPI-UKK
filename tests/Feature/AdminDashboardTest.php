<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_does_not_show_the_available_rooms_section(): void
    {
        $admin = $this->createUser();
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('dashboard').'" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm nav-active" title="Dashboard"', false)
            ->assertDontSee('Ruang Kosong Hari Ini')
            ->assertSee('Agenda Terbaru')
            ->assertSee('Akses Cepat')
            ->assertSee('Tambah User');
    }

    private function createUser(): User
    {
        $role = Role::create(['role_name' => 'Admin']);

        $user = User::create([
            'username' => 'dashboard-admin',
            'fullname' => 'Dashboard Admin',
            'email' => 'dashboard-admin@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

}

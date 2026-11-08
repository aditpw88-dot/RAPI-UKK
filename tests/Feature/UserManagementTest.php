<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_without_entering_username(): void
    {
        $adminRole = $this->createRole('Admin');
        $userRole = $this->createRole('Guru/Organisasi');
        $admin = $this->createUser('admin', $adminRole);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'fullname' => 'Siti Rahma',
                'email' => 'siti@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'phone_number' => '081234567890',
                'role_id' => $userRole->id,
            ])
            ->assertRedirect(route('users.index'));

        $createdUser = User::where('email', 'siti@example.com')->firstOrFail();
        $this->assertSame('siti-rahma', $createdUser->username);
        $this->assertSame($userRole->id, $createdUser->role_id);
        $this->assertSame('Guru/Organisasi', $createdUser->role?->role_name);
        $this->assertNull($createdUser->email_verified_at);

        $this->actingAs($createdUser)
            ->get(route('dashboard'))
            ->assertRedirect(route('guru.dashboard'));
    }

    public function test_generated_username_is_unique_for_matching_names(): void
    {
        $adminRole = $this->createRole('Admin');
        $userRole = $this->createRole('Guru/Organisasi');
        $admin = $this->createUser('admin', $adminRole);

        foreach (['one@example.com', 'two@example.com'] as $email) {
            $this->actingAs($admin)
                ->post(route('users.store'), [
                    'fullname' => 'Siti Rahma',
                    'email' => $email,
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'role_id' => $userRole->id,
                ])
                ->assertRedirect(route('users.index'));
        }

        $this->assertDatabaseHas('users', [
            'email' => 'one@example.com',
            'username' => 'siti-rahma',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'two@example.com',
            'username' => 'siti-rahma-2',
            'role_id' => $userRole->id,
        ]);
    }

    public function test_import_template_contains_a_separate_data_sheet_and_indonesian_guide(): void
    {
        $adminRole = $this->createRole('Admin');
        $admin = $this->createUser('admin', $adminRole);

        $response = $this->actingAs($admin)->get(route('users.import-template'));
        $response->assertDownload('template-impor-user.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'users-template-');
        $this->assertNotFalse($path);

        try {
            file_put_contents($path, $response->streamedContent());
            $spreadsheet = IOFactory::load($path);

            $this->assertSame(
                ['nama_lengkap', 'email', 'nomor_telepon', 'kata_sandi', 'peran'],
                $spreadsheet->getSheetByName('Data User')->rangeToArray('A1:E1')[0]
            );
            $this->assertSame(
                [
                    '1. Unduh contoh file, lalu isi satu baris untuk setiap user.',
                    '2. Isi nama, email, password, dan pilih role: Admin/Waka, Guru/Organisasi, PIC Ruang.',
                    '3. Nomor telepon boleh dikosongkan. Username akan dibuat otomatis.',
                    '4. Unggah file Excel atau CSV (maksimal 500 user, ukuran 5 MB).',
                ],
                array_map(
                    fn (array $row) => $row[0],
                    $spreadsheet->getSheetByName('Panduan Pengisian')->rangeToArray('A2:A5')
                )
            );

            $spreadsheet->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_admin_can_import_multiple_users_from_a_csv_file(): void
    {
        $adminRole = $this->createRole('Admin');
        $teacherRole = $this->createRole('Guru/Organisasi');
        $admin = $this->createUser('admin', $adminRole);
        $csv = "\xEF\xBB\xBF" . implode("\n", [
            'fullname;email;phone_number;password;role_name',
            'Siti Rahma;siti@example.com;081234567890;password123;Guru/Organisasi',
            'Budi Santoso;budi@example.com;;password456;Guru/Organisasi',
        ]);

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->post(route('users.import'), [
                'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', '2 akun berhasil ditambahkan dari file.');

        $siti = User::where('email', 'siti@example.com')->firstOrFail();
        $budi = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame('siti-rahma', $siti->username);
        $this->assertSame('budi-santoso', $budi->username);
        $this->assertSame($teacherRole->id, $siti->role_id);
        $this->assertSame('081234567890', $siti->phone_number);
        $this->assertNull($budi->phone_number);
        $this->assertTrue(Hash::check('password123', $siti->password));
    }

    public function test_admin_can_import_xlsx_and_invalid_rows_cancel_the_whole_import(): void
    {
        $adminRole = $this->createRole('Admin');
        $teacherRole = $this->createRole('Guru/Organisasi');
        $admin = $this->createUser('admin', $adminRole);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['fullname', 'email', 'phone_number', 'password', 'role_name'],
            ['Siti Rahma', 'siti@example.com', 81234567890, 12345678, 'Guru/Organisasi'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'users-import-');
        $this->assertNotFalse($path);
        $xlsxPath = $path . '.xlsx';

        try {
            (new Xlsx($spreadsheet))->save($xlsxPath);
            $upload = new UploadedFile(
                $xlsxPath,
                'users.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );

            $this->actingAs($admin)
                ->post(route('users.import'), ['file' => $upload])
                ->assertRedirect(route('users.index'))
                ->assertSessionHas('success', '1 akun berhasil ditambahkan dari file.');
        } finally {
            $spreadsheet->disconnectWorksheets();
            if (file_exists($xlsxPath)) {
                unlink($xlsxPath);
            }
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $this->assertDatabaseHas('users', [
            'email' => 'siti@example.com',
            'role_id' => $teacherRole->id,
            'phone_number' => '81234567890',
        ]);
        $this->assertTrue(Hash::check('12345678', User::where('email', 'siti@example.com')->firstOrFail()->password));

        $invalidCsv = implode("\n", [
            'fullname,email,phone_number,password,role_name',
            'Budi Santoso,budi@example.com,,password456,Guru/Organisasi',
            'Duplikat,budi@example.com,,password789,Guru/Organisasi',
        ]);

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->post(route('users.import'), [
                'file' => UploadedFile::fake()->createWithContent('invalid.csv', $invalidCsv),
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('import_error_count', 1);

        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);

        $shortPasswordCsv = implode("\n", [
            'fullname,email,phone_number,password,role_name',
            'Citra,citra@example.com,81234567890,123,Guru/Organisasi',
        ]);

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->post(route('users.import'), [
                'file' => UploadedFile::fake()->createWithContent('short-password.csv', $shortPasswordCsv),
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('import_errors', ['Baris 2: kata sandi minimal 8 karakter.']);
    }

    private function createRole(string $name): Role
    {
        $role = new Role();
        $role->role_name = $name;
        $role->save();

        return $role;
    }

    private function createUser(string $username, Role $role): User
    {
        $user = User::create([
            'username' => $username,
            'fullname' => ucfirst($username),
            'email' => $username . '@example.com',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}

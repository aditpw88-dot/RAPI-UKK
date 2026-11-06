<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    private const USER_IMPORT_HEADERS = ['fullname', 'email', 'phone_number', 'password', 'role_name'];

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);
        $search = trim($filters['search'] ?? '');

        $users = User::with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['role_id']), fn ($query) => $query->where('role_id', $filters['role_id']))
            ->get()
            ->groupBy(function (User $user) {
                return $user->role?->role_name ?? 'Belum Ditentukan';
            });
        $roles = Role::orderBy('role_name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:15',
            'role_id' => 'required|exists:roles,id',
        ]);

        $validated['username'] = $this->generateUsername($validated['fullname']);
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function downloadImportTemplate()
    {
        return response()->streamDownload(function (): void {
            $spreadsheet = new Spreadsheet();
            $dataSheet = $spreadsheet->getActiveSheet();
            $dataSheet->setTitle('Data User');
            $dataSheet->fromArray([['nama_lengkap', 'email', 'nomor_telepon', 'kata_sandi', 'peran']], null, 'A1');

            $guideSheet = $spreadsheet->createSheet();
            $guideSheet->setTitle('Panduan Pengisian');
            $guideSheet->fromArray([
                ['Panduan Mengisi Contoh File User'],
                ['1. Unduh contoh file, lalu isi satu baris untuk setiap user.'],
                ['2. Isi nama, email, password, dan pilih role: Admin, Guru/Organisasi, PIC Ruang, Siswa, Waka Sapras.'],
                ['3. Nomor telepon boleh dikosongkan. Username akan dibuat otomatis.'],
                ['4. Unggah file Excel atau CSV (maksimal 500 user, ukuran 5 MB).'],
            ], null, 'A1');

            $spreadsheet->setActiveSheetIndex(0);
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-impor-user.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ], [
            'file.mimes' => 'File harus berformat Excel .xlsx atau CSV.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $reader = IOFactory::createReaderForFile($validated['file']->getPathname());
            $reader->setReadDataOnly(true);

            if ($reader instanceof Csv) {
                $firstLine = file($validated['file']->getPathname(), FILE_IGNORE_NEW_LINES)[0] ?? '';
                $reader->setDelimiter(substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',');
            }

            $spreadsheet = $reader->load($validated['file']->getPathname());
        } catch (SpreadsheetReaderException|SpreadsheetException $exception) {
            Log::warning('Gagal membaca file impor user.', ['exception' => $exception->getMessage()]);

            return back()->withErrors(['file' => 'File tidak dapat dibaca. Pastikan formatnya .xlsx atau .csv yang valid.']);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow > 501) {
            $spreadsheet->disconnectWorksheets();

            return back()->withErrors(['file' => 'File maksimal berisi 500 akun, tidak termasuk baris judul.']);
        }

        $rows = $sheet->toArray(null, true, false, false);
        $spreadsheet->disconnectWorksheets();
        $headerAliases = [
            'nama_lengkap' => 'fullname',
            'nomor_telepon' => 'phone_number',
            'kata_sandi' => 'password',
            'peran' => 'role_name',
        ];
        $headers = array_map(
            fn ($header) => $headerAliases[strtolower(preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $header)))]
                ?? strtolower(preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $header))),
            $rows[0] ?? []
        );

        if (count($headers) !== count(array_unique($headers))) {
            return back()->withErrors(['file' => 'Nama kolom pada file tidak boleh duplikat.']);
        }

        $missingHeaders = array_diff(self::USER_IMPORT_HEADERS, $headers);
        if ($missingHeaders !== []) {
            return back()->withErrors([
                'file' => 'Kolom wajib: ' . implode(', ', self::USER_IMPORT_HEADERS) . '. Unduh template untuk format yang benar.',
            ]);
        }

        $roles = Role::all()->keyBy(fn (Role $role) => strtolower(trim($role->role_name)));
        $validatedUsers = [];
        $rowErrors = [];
        $seenEmails = [];

        foreach (array_slice($rows, 1, null, true) as $rowIndex => $values) {
            $values = array_slice(array_pad($values, count($headers), null), 0, count($headers));
            $userData = array_combine($headers, array_map(
                fn ($value) => is_string($value) ? trim($value) : $value,
                $values
            ));

            if (collect($userData)->every(fn ($value) => $value === null || $value === '')) {
                continue;
            }

            foreach (['phone_number', 'password'] as $field) {
                if (is_int($userData[$field]) || is_float($userData[$field])) {
                    $userData[$field] = (string) $userData[$field];
                }
            }

            $rowNumber = $rowIndex + 1;
            $validator = Validator::make($userData, [
                'fullname' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'phone_number' => ['nullable', 'string', 'max:15'],
                'password' => ['required', 'string', 'min:8'],
                'role_name' => ['required', 'string', 'max:255'],
            ], [
                'required' => ':attribute wajib diisi.',
                'string' => ':attribute harus berupa teks.',
                'email' => ':attribute harus berupa alamat email yang valid.',
                'max' => ':attribute maksimal :max karakter.',
                'min' => ':attribute minimal :min karakter.',
            ], [
                'fullname' => 'nama lengkap',
                'email' => 'email',
                'phone_number' => 'nomor telepon',
                'password' => 'kata sandi',
                'role_name' => 'peran',
            ]);

            if ($validator->fails()) {
                $rowErrors[] = 'Baris ' . $rowNumber . ': ' . implode(' ', $validator->errors()->all());
                continue;
            }

            $emailKey = strtolower($userData['email']);
            if (isset($seenEmails[$emailKey]) || User::where('email', $userData['email'])->exists()) {
                $rowErrors[] = "Baris {$rowNumber}: email {$userData['email']} sudah digunakan atau tercantum lebih dari sekali di dalam file.";
                continue;
            }

            $role = $roles->get(strtolower(trim($userData['role_name'])));
            if (! $role) {
                $rowErrors[] = "Baris {$rowNumber}: peran \"{$userData['role_name']}\" tidak ditemukan.";
                continue;
            }

            $seenEmails[$emailKey] = true;
            $validatedUsers[] = [
                'fullname' => $userData['fullname'],
                'email' => $userData['email'],
                'phone_number' => $userData['phone_number'] ?: null,
                'password' => $userData['password'],
                'role_id' => $role->id,
            ];
        }

        if ($validatedUsers === [] && $rowErrors === []) {
            $rowErrors[] = 'File belum berisi data akun.';
        }

        if ($rowErrors !== []) {
            return back()
                ->with('import_errors', array_slice($rowErrors, 0, 100))
                ->with('import_error_count', count($rowErrors));
        }

        DB::transaction(function () use ($validatedUsers): void {
            foreach ($validatedUsers as $userData) {
                $userData['username'] = $this->generateUsername($userData['fullname']);
                $userData['password'] = Hash::make($userData['password']);
                User::create($userData);
            }
        });

        return redirect()->route('users.index')
            ->with('success', count($validatedUsers) . ' akun berhasil ditambahkan dari file.');
    }

    private function generateUsername(string $fullname): string
    {
        $baseUsername = Str::limit(Str::slug($fullname), 240, '');
        $baseUsername = $baseUsername !== '' ? $baseUsername : 'user';
        $username = $baseUsername;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $suffix++;
            $username = Str::limit($baseUsername, 255 - strlen((string) $suffix) - 1, '') . '-' . $suffix;
        }

        return $username;
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:15',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil diperbarui');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus');
    }
}

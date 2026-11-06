@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Administrasi</p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Manajemen User</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola akun berdasarkan kelompok aksesnya.</p>
        </div>
        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700">
            <span class="text-lg leading-none">+</span>
            Tambah User
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('import_errors'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">User belum ditambahkan. Periksa {{ session('import_error_count') }} masalah berikut, lalu coba lagi:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach (session('import_errors') as $importError)
                    <li>{{ $importError }}</li>
                @endforeach
            </ul>
            @if (session('import_error_count') > 100)
                <p class="mt-2">Hanya 100 masalah pertama yang ditampilkan.</p>
            @endif
        </div>
    @endif

    @if ($errors->has('file'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            {{ $errors->first('file') }}
        </div>
    @endif

    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">Tambah Banyak User</h2>
                <ol class="mt-2 list-inside list-decimal space-y-1 text-sm text-gray-600">
                    <li>Unduh contoh file, lalu isi satu baris untuk setiap user.</li>
                    <li>Isi nama, email, password, dan pilih role: {{ $roles->pluck('role_name')->join(', ') }}.</li>
                    <li>Nomor telepon boleh dikosongkan. Username akan dibuat otomatis.</li>
                    <li>Unggah file Excel atau CSV (maksimal 500 user, ukuran 5 MB).</li>
                </ol>
                <p class="mt-2 text-sm text-amber-800">Simpan file dengan aman karena berisi password. Jika ada data yang salah, user tidak akan ditambahkan sampai file diperbaiki.</p>
            </div>
            <a href="{{ route('users.import-template') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Unduh Contoh File
            </a>
        </div>
        <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center">
            @csrf
            <label for="user-import-file" class="sr-only">Pilih file Excel atau CSV</label>
            <input id="user-import-file" type="file" name="file" accept=".xlsx,.csv" required class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
            <button type="submit" class="shrink-0 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">Impor User</button>
        </form>
    </section>

    <form method="GET" action="{{ route('users.index') }}" class="mb-6 grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_16rem_auto_auto]">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau telepon" aria-label="Cari nama, email, atau telepon" class="rounded-md border-gray-300 text-sm">
        <select name="role_id" aria-label="Filter role user" class="rounded-md border-gray-300 text-sm">
            <option value="">Semua role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>{{ $role->role_name }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Cari / Filter</button>
        <a href="{{ route('users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
    </form>

    @php
        $roleOrder = ['waka', 'Waka/Admin', 'admin', 'PIC Ruang', 'Guru/Organisasi', 'Belum Ditentukan'];
        $roleGroups = $users->sortBy(function ($group, $roleName) use ($roleOrder) {
            $position = array_search($roleName, $roleOrder, true);
            return $position === false ? count($roleOrder) : $position;
        });
    @endphp

    <div class="space-y-6">
        @forelse ($roleGroups as $roleName => $roleUsers)
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-gray-900">{{ $roleName }}</h2>
                        <p class="mt-1 text-xs text-gray-500">{{ $roleUsers->count() }} akun</p>
                    </div>
                    <span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-700">Role</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-white text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">User</th>
                                <th class="px-5 py-3 font-semibold">Email</th>
                                <th class="px-5 py-3 font-semibold">Telepon</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($roleUsers as $user)
                                <tr class="transition hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="font-semibold text-gray-900">{{ $user->fullname }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $user->email }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($user->phone_number)
                                            <a href="tel:{{ $user->phone_number }}" class="font-medium text-gray-900 hover:underline">{{ $user->phone_number }}</a>
                                        @else
                                            <span class="text-gray-400">Nomor belum diisi</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route('users.show', $user) }}" class="rounded-md border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100">Lihat</a>
                                            <a href="{{ route('users.edit', $user) }}" class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-700">Edit</a>
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" data-confirm-action="menghapus akun pengguna" data-confirm-item="Pengguna: {{ $user->fullname }} ({{ $user->email }})">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center text-sm text-gray-500">Belum ada data user.</div>
        @endforelse
    </div>
</div>
@endsection

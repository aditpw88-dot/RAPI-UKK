@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Administrasi</p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Manajemen User</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola akun berdasarkan kelompok aksesnya.</p>
        </div>
        <button type="button" id="open-create-user-btn" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700">
            <svg width="16" height="16" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah User</span>
        </button>
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
        $roleOrder = ['Admin/Waka', 'Admin', 'PIC Ruang', 'Guru/Organisasi', 'Belum Ditentukan'];
        $roleGroups = $users->sortBy(function ($group, $roleName) use ($roleOrder) {
            $position = array_search($roleName, $roleOrder, true);
            return $position === false ? count($roleOrder) : $position;
        });
    @endphp

    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
            Daftar Kelompok Role ({{ $roleGroups->count() }})
        </p>
        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="window.dispatchEvent(new CustomEvent('accordion-toggle-all', { detail: { open: true } }))"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs transition hover:bg-gray-50 hover:text-gray-900"
            >
                Buka Semua
            </button>
            <button 
                type="button" 
                onclick="window.dispatchEvent(new CustomEvent('accordion-toggle-all', { detail: { open: false } }))"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs transition hover:bg-gray-50 hover:text-gray-900"
            >
                Tutup Semua
            </button>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($roleGroups as $roleName => $roleUsers)
            <x-user-role-accordion 
                :role-name="$roleName" 
                :users="$roleUsers" 
                :default-open="true" 
                :per-page="5" 
            />
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center text-sm text-gray-500">
                Belum ada data user.
            </div>
        @endforelse
    </div>

    <!-- Modal Window Tambah User (Backdrop Blur) -->
    <div id="create-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="create-user-modal-title">
        <!-- Backdrop Blur Layer -->
        <div id="create-user-modal-backdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity duration-200" style="backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>

        <!-- Modal Card Content -->
        <div class="relative z-10 w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all my-auto max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                <div>
                    <span class="inline-block rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-800 uppercase tracking-wider">User Baru</span>
                    <h3 id="create-user-modal-title" class="mt-1 text-lg font-bold text-slate-900">Tambah User Baru</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Tambahkan akun pengguna baru dan tentukan hak akses / role-nya.</p>
                </div>
                <button type="button" id="create-user-modal-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition focus:outline-none" aria-label="Tutup modal">
                    <svg width="20" height="20" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form id="create-user-form" action="{{ route('users.store') }}" method="POST" class="space-y-4 pt-4" data-confirm-action="menambahkan akun pengguna baru" data-confirm-item="Akun pengguna baru" data-confirm-title="Konfirmasi Penambahan User" data-confirm-message="Apakah Anda yakin ingin menambahkan akun pengguna baru ini? Pastikan data nama, email, dan role sudah sesuai.">
                @csrf

                <div>
                    <label for="create_fullname" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" id="create_fullname" name="fullname" value="{{ old('fullname') }}" required placeholder="Contoh: Muhammad Fauzi" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    @error('fullname')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="create_email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="create_email" name="email" value="{{ old('email') }}" required placeholder="contoh@sekolah.sch.id" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    <p class="mt-1 text-[11px] text-slate-500">Username dibuat otomatis dari nama lengkap.</p>
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="create_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <input type="password" id="create_password" name="password" required placeholder="Minimal 8 karakter" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="create_password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <input type="password" id="create_password_confirmation" name="password_confirmation" required placeholder="Ulangi password" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="create_phone_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">No. Telepon / WhatsApp</label>
                        <input type="text" id="create_phone_number" name="phone_number" value="{{ old('phone_number') }}" placeholder="08xxxxxxxxxx" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                        @error('phone_number')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="create_gender" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Jenis Kelamin</label>
                        <select id="create_gender" name="gender" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="Laki-laki" @selected(old('gender') === 'Laki-laki')>Laki-laki</option>
                            <option value="Perempuan" @selected(old('gender') === 'Perempuan')>Perempuan</option>
                        </select>
                        @error('gender')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="create_role_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Role Akses <span class="text-red-500">*</span></label>
                    <select id="create_role_id" name="role_id" required class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                        <option value="">-- Pilih Role --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" data-role-name="{{ strtolower($role->role_name) }}" @selected(old('role_id') == $role->id)>
                                {{ $role->role_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="mapel-group">
                    <label for="create_mapel" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Mata Pelajaran (Khusus Guru)</label>
                    <input type="text" id="create_mapel" name="mapel" value="{{ old('mapel') }}" placeholder="Contoh: BAHASA INDONESIA, MATEMATIKA" class="w-full rounded-lg border-gray-300 text-sm font-medium focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
                    <p class="mt-1 text-[11px] text-slate-500">Hanya diisi jika pengguna memiliki role Guru.</p>
                    @error('mapel')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 mt-6">
                    <button type="button" id="create-user-modal-cancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700 shadow transition">
                        Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const createUserModal = document.getElementById('create-user-modal');
            const openCreateUserBtn = document.getElementById('open-create-user-btn');
            const closeCreateUserBtn = document.getElementById('create-user-modal-close');
            const cancelCreateUserBtn = document.getElementById('create-user-modal-cancel');
            const createUserBackdrop = document.getElementById('create-user-modal-backdrop');
            const fullnameInput = document.getElementById('create_fullname');
            const roleSelect = document.getElementById('create_role_id');
            const mapelGroup = document.getElementById('mapel-group');

            function openCreateUserModal() {
                if (!createUserModal) return;
                createUserModal.classList.remove('hidden');
                createUserModal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                setTimeout(() => fullnameInput?.focus(), 50);
            }

            function closeCreateUserModal() {
                if (!createUserModal) return;
                createUserModal.classList.add('hidden');
                createUserModal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }

            openCreateUserBtn?.addEventListener('click', openCreateUserModal);
            closeCreateUserBtn?.addEventListener('click', closeCreateUserModal);
            cancelCreateUserBtn?.addEventListener('click', closeCreateUserModal);
            createUserBackdrop?.addEventListener('click', closeCreateUserModal);

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && createUserModal && !createUserModal.classList.contains('hidden')) {
                    const confirmationDialog = document.getElementById('action-confirmation');
                    if (!confirmationDialog || !confirmationDialog.open) {
                        closeCreateUserModal();
                    }
                }
            });

            @if ($errors->any() && old('fullname'))
                openCreateUserModal();
            @endif
            if (new URLSearchParams(window.location.search).get('create') === '1') {
                openCreateUserModal();
            }

            function syncMapelVisibility() {
                const selectedOpt = roleSelect?.selectedOptions[0];
                const roleName = selectedOpt?.dataset.roleName || '';
                const isGuru = roleName.includes('guru');
                if (mapelGroup) {
                    mapelGroup.classList.toggle('opacity-50', !isGuru && roleSelect?.value !== '');
                }
            }

            roleSelect?.addEventListener('change', syncMapelVisibility);
            syncMapelVisibility();
        })();
    </script>
</div>
@endsection

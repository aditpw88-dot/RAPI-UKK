<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Administrasi Sistem</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Dashboard Admin</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau ruang, agenda, dan laporan kerusakan sekolah.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="admin-stat-grid grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([['Total User', $statistics['users'], 'users'], ['Total Ruang', $statistics['rooms'], 'admin.rooms'], ['Agenda', $statistics['events'], 'admin.events'], ['Kerusakan Terbuka', $statistics['open_complaints'], 'admin.complaints']] as [$label, $value, $route])
                    <a href="{{ $route === 'users' ? route('users.index') : route($route) }}" class="admin-stat-card rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <p class="admin-stat-label text-sm">{{ $label }}</p>
                        <p class="admin-stat-value mt-2 text-3xl font-bold">{{ $value }}</p>
                    </a>
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('admin.rooms') }}" class="admin-management-card rounded-xl p-5 text-white shadow-sm"><p class="text-lg font-semibold">Manajemen Ruang</p><p class="mt-1 text-sm">{{ $statistics['active_rooms'] }} ruang aktif</p></a>
                <a href="{{ route('admin.events') }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 hover:ring-slate-400">
                    <p class="text-lg font-semibold text-slate-900">Manajemen Agenda</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $statistics['pending_events'] }} agenda menunggu persetujuan
                        @if (!empty($statistics['pending_cancellations']))
                            · <span class="font-bold text-purple-600">{{ $statistics['pending_cancellations'] }} pengajuan batal</span>
                        @endif
                    </p>
                </a>
                <a href="{{ route('admin.complaints') }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 hover:ring-slate-400"><p class="text-lg font-semibold text-slate-900">Manajemen Kerusakan</p><p class="mt-1 text-sm text-slate-500">{{ $statistics['complaints'] }} laporan tercatat</p></a>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="grid content-start gap-6 sm:grid-cols-2 lg:col-span-2">
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4"><h3 class="font-semibold text-slate-900">Agenda Terbaru</h3><a href="{{ route('admin.events') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">Lihat semua</a></div>
                        <div class="divide-y divide-gray-100">
                            @forelse ($latestEvents as $event)
                                <div class="px-5 py-4"><p class="font-medium text-slate-900">{{ $event->event_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $event->room_names ?: '-' }} · <x-event-date-range :start-date="$event->date" :end-date="$event->end_date" /> · {{ $event->lifecycle_status_label }}</p></div>
                            @empty <p class="px-5 py-8 text-sm text-slate-500">Belum ada agenda.</p> @endforelse
                        </div>
                    </section>
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4"><h3 class="font-semibold text-slate-900">Kerusakan Terbaru</h3><a href="{{ route('admin.complaints') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">Lihat semua</a></div>
                        <div class="divide-y divide-gray-100">
                            @forelse ($latestComplaints as $complaint)
                                <div class="px-5 py-4"><p class="font-medium text-slate-900">{{ $complaint->room?->room_name ?? '-' }}</p><p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($complaint->description, 80) }} · {{ $complaint->status_repair }}</p><p class="mt-1 text-xs text-slate-600">Pelapor: {{ $complaint->reporter?->fullname ?? '-' }} · {{ $complaint->reporter?->username ?? '-' }} · {{ $complaint->reporter?->role?->role_name ?? '-' }}</p><p class="text-xs text-slate-500">{{ $complaint->reporter?->email ?? '-' }} · {{ $complaint->reporter?->phone_number ?: '-' }}</p></div>
                            @empty <p class="px-5 py-8 text-sm text-slate-500">Belum ada laporan kerusakan.</p> @endforelse
                        </div>
                    </section>
                </div>

                <aside class="h-fit rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Akses Cepat</h3>
                    <p class="mt-1 text-sm text-slate-500">Pilih tindakan yang ingin dilakukan.</p>
                    <div class="mt-4 space-y-3">
                        <a href="{{ route('admin.events.create') }}" class="group flex items-center justify-between rounded-lg border border-slate-200 p-4 transition hover:border-slate-400 hover:bg-slate-50">
                            <span>
                                <span class="block font-semibold text-slate-900">Peminjaman Ruang</span>
                                <span class="mt-1 block text-xs text-slate-500">Buat pengajuan pemakaian ruang</span>
                            </span>
                            <span aria-hidden="true" class="text-lg text-slate-400 transition group-hover:translate-x-1 group-hover:text-slate-700">→</span>
                        </a>
                        <a href="{{ route('admin.complaints.create') }}" class="group flex items-center justify-between rounded-lg border border-slate-200 p-4 transition hover:border-slate-400 hover:bg-slate-50">
                            <span>
                                <span class="block font-semibold text-slate-900">Pengaduan Cepat</span>
                                <span class="mt-1 block text-xs text-slate-500">Laporkan kerusakan ruang</span>
                            </span>
                            <span aria-hidden="true" class="text-lg text-slate-400 transition group-hover:translate-x-1 group-hover:text-slate-700">→</span>
                        </a>
                        <a href="{{ route('admin.rooms') }}" class="group flex items-center justify-between rounded-lg border border-slate-200 p-4 transition hover:border-slate-400 hover:bg-slate-50">
                            <span>
                                <span class="block font-semibold text-slate-900">Daftar Ruang</span>
                                <span class="mt-1 block text-xs text-slate-500">Lihat dan kelola ruang sekolah</span>
                            </span>
                            <span aria-hidden="true" class="text-lg text-slate-400 transition group-hover:translate-x-1 group-hover:text-slate-700">→</span>
                        </a>
                        <a href="{{ route('users.index', ['create' => 1]) }}" class="group flex items-center justify-between rounded-lg border border-slate-200 p-4 transition hover:border-slate-400 hover:bg-slate-50">
                            <span>
                                <span class="block font-semibold text-slate-900">Tambah User</span>
                                <span class="mt-1 block text-xs text-slate-500">Daftarkan akun pengguna baru</span>
                            </span>
                            <span aria-hidden="true" class="text-lg text-slate-400 transition group-hover:translate-x-1 group-hover:text-slate-700">→</span>
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>

<aside class="school-sidebar fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col shadow-xl transition-all duration-300 lg:translate-x-0" :class="[sidebarOpen ? 'translate-x-0 lg:w-64' : '-translate-x-full lg:w-20']">
    <div class="flex h-20 items-center justify-between border-b border-gray-200 px-5">
        <a href="{{ route('home') }}" class="flex items-center gap-3 overflow-hidden whitespace-nowrap">
            <img x-show="sidebarOpen" x-transition src="{{ asset('images/rapi-logo.png') }}" alt="Logo RAPI" class="h-9 w-9 shrink-0 rounded-lg bg-white object-contain p-0.5">
            <span x-show="sidebarOpen" x-transition class="font-semibold tracking-wide">Sistem Sekolah</span>
        </a>
        <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-2 text-gray-900 hover:bg-gray-200" aria-label="Toggle sidebar">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </button>
    </div>

    @auth
        <div class="border-b border-gray-200 px-5 py-5" x-show="sidebarOpen" x-transition>
            <p class="truncate text-sm font-semibold">{{ auth()->user()->fullname }}</p>
            <p class="mt-1 truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
        </div>
        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-5">
            <a href="{{ str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'pic') ? route('pic.dashboard') : route('dashboard') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') || request()->routeIs('pic.dashboard') || request()->routeIs('guru.dashboard') ? 'nav-active' : '' }}" title="Dashboard">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" /></svg>
                <span x-show="sidebarOpen" x-transition>Dashboard</span>
            </a>
            <a href="{{ str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'pic') ? route('pic.calendar') : route('calendar') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('calendar') || request()->routeIs('pic.calendar') ? 'nav-active' : '' }}" title="Kalender">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                <span x-show="sidebarOpen" x-transition>Kalender</span>
            </a>
            @if (auth()->user()->role && str_contains(strtolower(auth()->user()->role->role_name), 'pic'))
                <a href="{{ route('pic.rooms') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('pic.rooms*') ? 'nav-active' : '' }}" title="Manajemen Ruang">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V5l7-3 7 3v16M9 9h2m-2 4h2m2-4h2m-2 4h2" /></svg>
                    <span x-show="sidebarOpen" x-transition>Manajemen Ruang</span>
                </a>
                <a href="{{ route('pic.approvals') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('pic.approvals') ? 'nav-active' : '' }}" title="Persetujuan Peminjaman">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-4v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h12a2 2 0 012 2z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Persetujuan Peminjaman</span>
                </a>
                <a href="{{ route('pic.events.approved') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('pic.events.approved') ? 'nav-active' : '' }}" title="Manajemen Agenda PIC">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-4v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h12a2 2 0 012 2z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Manajemen Agenda PIC</span>
                </a>
                <a href="{{ route('pic.complaints.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('pic.complaints.*') ? 'nav-active' : '' }}" title="Pengaduan Kerusakan">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.8L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7 3z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Pengaduan Kerusakan</span>
                </a>
                <a href="{{ route('pic.reports.complaints') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('pic.reports.complaints*') ? 'nav-active' : '' }}" title="Laporan Kerusakan">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-8 4h5m5 5H6a2 2 0 01-2-2V6a2 2 0 012-2h8l5 5v9a2 2 0 01-2 2z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Laporan Kerusakan</span>
                </a>
            @endif
            @if (auth()->user()->role && str_contains(strtolower(auth()->user()->role->role_name), 'guru'))
                <a href="{{ route('events.create') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('events.create') ? 'nav-active' : '' }}" title="Ajukan Peminjaman">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V5l7-3 7 3v16M9 9h2m-2 4h2m2-4h2m-2 4h2" /></svg>
                    <span x-show="sidebarOpen" x-transition>Ajukan Peminjaman</span>
                </a>
                <a href="{{ route('guru.events.history') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('guru.events.history') ? 'nav-active' : '' }}" title="Riwayat & Status">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Riwayat &amp; Status</span>
                </a>
                <a href="{{ route('guru.complaints.create') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('guru.complaints.create') ? 'nav-active' : '' }}" title="Buat Laporan Baru">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.8L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7-3L13.7 3.8a2 2 0 00-3.4 0z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Buat Laporan Baru</span>
                </a>
                <a href="{{ route('guru.complaints.status') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('guru.complaints.status') ? 'nav-active' : '' }}" title="Riwayat Pengaduan Saya">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4m10-10h-4M6 12H2m17.07-7.07l-2.83 2.83M7.76 16.24l-2.83 2.83m14.14 0l-2.83-2.83M7.76 7.76L4.93 4.93" /></svg>
                    <span x-show="sidebarOpen" x-transition>Riwayat Pengaduan</span>
                </a>
            @endif
            @if (auth()->user()->role && (str_contains(strtolower(auth()->user()->role->role_name), 'admin') || str_contains(strtolower(auth()->user()->role->role_name), 'waka')))
                <a href="{{ route('admin.rooms') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('admin.rooms*') ? 'nav-active' : '' }}" title="Manajemen Ruang">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V5l7-3 7 3v16M9 9h2m-2 4h2m2-4h2m-2 4h2" /></svg>
                    <span x-show="sidebarOpen" x-transition>Manajemen Ruang</span>
                </a>
                <a href="{{ route('admin.events') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('admin.events*') ? 'nav-active' : '' }}" title="Manajemen Agenda">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Manajemen Agenda</span>
                </a>
                <a href="{{ route('admin.complaints') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('admin.complaints*') ? 'nav-active' : '' }}" title="Manajemen Kerusakan">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.8L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7-3L13.7 3.8a2 2 0 00-3.4 0z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Manajemen Kerusakan</span>
                </a>
                <a href="{{ route('reports.monthly') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('reports.monthly*') ? 'nav-active' : '' }}" title="Laporan Peminjaman">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-8 4h5m5 5H6a2 2 0 01-2-2V6a2 2 0 012-2h8l5 5v9a2 2 0 01-2 2z" /></svg>
                    <span x-show="sidebarOpen" x-transition>Laporan Peminjaman</span>
                </a>
                <a href="{{ route('users.index') }}" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm {{ request()->routeIs('users.*') ? 'nav-active' : '' }}" title="Manajemen User">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm10 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" /></svg>
                    <span x-show="sidebarOpen" x-transition>Manajemen User</span>
                </a>
            @endif
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-200 p-3" data-confirmation-exempt>
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-left text-sm text-red-600 hover:bg-red-50 hover:text-red-700" title="Logout">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1" /></svg>
                <span x-show="sidebarOpen" x-transition>Logout</span>
            </button>
        </form>
    @else
        <a href="{{ route('login') }}" class="m-3 rounded-lg bg-indigo-500 px-4 py-3 text-center text-sm font-semibold">Login</a>
    @endauth
</aside>

<div class="fixed right-4 top-4 z-40 lg:hidden">
    <button @click="sidebarOpen = true" class="rounded-lg bg-gray-900 p-3 text-white shadow-lg" aria-label="Open sidebar">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
    </button>
</div>
<div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" style="display: none;"></div>

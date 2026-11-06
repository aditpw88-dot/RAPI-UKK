<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-slate-500">Portal Guru/Organisasi</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Dashboard Guru</h2>
            <p class="mt-1 text-sm text-slate-500">Ajukan peminjaman ruang, pantau agenda, dan laporkan kerusakan.</p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('guru.events.history') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md"><p class="text-sm text-slate-500">Total Pengajuan</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $events->count() }}</p></a>
                <a href="{{ route('guru.events.history', ['status' => 'in_progress']) }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md"><p class="text-sm text-slate-500">Sedang Terlaksana</p><p class="mt-2 text-3xl font-bold text-amber-800">{{ $approvedEvents }}</p></a>
                <a href="{{ route('guru.complaints.status') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md"><p class="text-sm text-slate-500">Kerusakan Belum Selesai</p><p class="mt-2 text-3xl font-bold text-amber-600">{{ $openComplaints }}</p></a>
            </div>
        </div>
    </div>
</x-app-layout>

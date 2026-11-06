<footer class="border-t border-gray-200 bg-white text-gray-700">
    <div class="mx-auto grid max-w-7xl gap-8 px-6 py-8 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <h2 class="text-base font-semibold text-black">RAPI-Sekolah</h2>
            <p class="mt-2 max-w-sm text-sm leading-6 text-gray-600">
                Sistem informasi agenda dan pengelolaan fasilitas sekolah.
            </p>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-black">Panduan singkat</h2>
            <ul class="mt-2 space-y-1 text-sm leading-6 text-gray-600">
                <li>Pengunjung dapat melihat agenda yang disetujui melalui kalender.</li>
                <li>Guru dapat masuk untuk mengajukan agenda dan melaporkan kerusakan.</li>
                <li>PIC memproses agenda dan laporan pada ruang tanggung jawabnya.</li>
                <li>Admin mengelola data ruang, agenda, pengguna, dan kerusakan.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-black">Bantuan</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">
                Untuk bantuan penggunaan atau kendala akun, hubungi administrator sekolah.
            </p>
            <div class="mt-3 flex gap-4 text-sm font-medium">
                <a href="{{ route('calendar') }}" class="text-black underline-offset-4 hover:underline">Lihat kalender</a>
                @guest
                    <a href="{{ route('login') }}" class="text-black underline-offset-4 hover:underline">Masuk</a>
                @endguest
            </div>
        </div>
    </div>
    <div class="border-t border-gray-200">
        <p class="mx-auto max-w-7xl px-6 py-4 text-xs text-gray-500">
            &copy; {{ now()->year }} RAPI-Sekolah
        </p>
    </div>
</footer>

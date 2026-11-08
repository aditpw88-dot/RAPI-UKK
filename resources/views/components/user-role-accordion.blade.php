@props([
    'roleName' => 'User',
    'users' => collect(),
    'defaultOpen' => true,
    'perPage' => 5,
])

@php
    $totalUsers = $users->count();
    $accordionId = 'role-accordion-' . \Illuminate\Support\Str::slug($roleName ?: 'unassigned');
@endphp

<section 
    id="{{ $accordionId }}"
    x-data="{
        isOpen: @js($defaultOpen),
        page: 1,
        perPage: {{ (int) $perPage }},
        total: {{ (int) $totalUsers }},
        get totalPages() {
            return Math.ceil(this.total / this.perPage) || 1;
        },
        get startIndex() {
            return (this.page - 1) * this.perPage;
        },
        get endIndex() {
            return Math.min(this.startIndex + this.perPage, this.total);
        },
        isRowVisible(index) {
            return index >= this.startIndex && index < this.endIndex;
        },
        nextPage() {
            if (this.page < this.totalPages) {
                this.page++;
            }
        },
        prevPage() {
            if (this.page > 1) {
                this.page--;
            }
        },
        goToPage(p) {
            if (typeof p === 'number' && p >= 1 && p <= this.totalPages) {
                this.page = p;
            }
        },
        get pageList() {
            const pages = [];
            const total = this.totalPages;
            const current = this.page;
            if (total <= 6) {
                for (let i = 1; i <= total; i++) pages.push(i);
            } else {
                pages.push(1);
                if (current > 3) pages.push('...');
                const start = Math.max(2, current - 1);
                const end = Math.min(total - 1, current + 1);
                for (let i = start; i <= end; i++) pages.push(i);
                if (current < total - 2) pages.push('...');
                pages.push(total);
            }
            return pages;
        }
    }"
    @accordion-toggle-all.window="isOpen = $event.detail.open"
    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200 hover:border-gray-300"
>
    <!-- Header Accordion -->
    <header 
        @click="isOpen = !isOpen"
        class="flex cursor-pointer select-none items-center justify-between border-b border-gray-200 px-5 py-4 transition-colors"
        :class="isOpen ? 'bg-gray-50/90' : 'bg-white hover:bg-gray-50/50'"
        role="button"
        :aria-expanded="isOpen"
        aria-controls="{{ $accordionId }}-content"
    >
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-sm font-semibold text-white shadow-sm">
                {{ strtoupper(substr($roleName, 0, 1)) }}
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ $roleName }} <span class="font-normal text-gray-500">({{ $totalUsers }} akun)</span>
                    </h2>
                </div>
                <p class="text-xs text-gray-500">
                    Kelompok akses {{ $roleName }} • <span x-text="isOpen ? 'Klik untuk menutup' : 'Klik untuk membuka'"></span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-2xs">
                {{ $totalUsers }} akun
            </span>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900">
                <svg 
                    class="h-4 w-4 transition-transform duration-200" 
                    :class="isOpen ? 'rotate-180' : ''" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor" 
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>
    </header>

    <!-- Body Accordion -->
    <div 
        id="{{ $accordionId }}-content"
        x-show="isOpen" 
        x-collapse.duration.250ms
        class="border-t border-gray-100"
    >
        @if ($totalUsers === 0)
            <div class="px-6 py-10 text-center text-sm text-gray-500">
                Belum ada data akun untuk kelompok role <span class="font-medium text-gray-700">{{ $roleName }}</span>.
            </div>
        @else
            <!-- Tabel Data User -->
            <div class="overflow-x-auto min-h-[220px]">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/75 text-left text-xs uppercase tracking-wider text-gray-600">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 font-bold">User</th>
                            <th scope="col" class="px-5 py-3.5 font-bold">Email</th>
                            <th scope="col" class="px-5 py-3.5 font-bold">Telepon</th>
                            <th scope="col" class="w-20 px-5 py-3.5 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($users as $index => $user)
                            <tr 
                                x-show="isRowVisible({{ $index }})" 
                                style="display: {{ $index >= $perPage ? 'none' : '' }};"
                                class="transition-colors hover:bg-gray-50/70"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 font-bold text-sm text-gray-700 ring-1 ring-gray-200">
                                            {{ strtoupper(substr($user->fullname ?? $user->username ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 max-w-[260px] sm:max-w-sm">
                                            <p class="truncate text-base font-semibold text-gray-900" title="{{ $user->fullname }}">{{ $user->fullname }}</p>
                                            @if ($user->username)
                                                <p class="truncate text-sm text-gray-500 font-normal">@<span>{{ $user->username }}</span></p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-700">
                                    <div class="max-w-[320px] sm:max-w-md truncate text-base font-medium text-gray-700" title="{{ $user->email }}">
                                        {{ $user->email }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($user->phone_number)
                                        <a href="tel:{{ $user->phone_number }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-800 hover:text-gray-900 hover:underline" title="Hubungi {{ $user->phone_number }}">
                                            <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;max-width:16px;" class="h-4 w-4 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            <span>{{ $user->phone_number }}</span>
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-400 italic">Belum diisi</span>
                                    @endif
                                </td>
                                <td class="relative whitespace-nowrap px-5 py-4 text-right">
                                    <div 
                                        x-data="{ open: false }" 
                                        @click.outside="open = false" 
                                        @keydown.escape.window="open = false"
                                        class="relative inline-block text-left"
                                    >
                                        <button 
                                            type="button" 
                                            @click.stop="open = !open" 
                                            :class="open ? 'bg-amber-50 text-amber-900 border-amber-300 ring-2 ring-amber-500/20' : 'text-gray-600 bg-white border-gray-200 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-300 shadow-2xs'"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition-all focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                                            aria-haspopup="true"
                                            :aria-expanded="open"
                                            title="Pilihan tindakan"
                                        >
                                            <span class="sr-only">Menu aksi user</span>
                                            <svg width="20" height="20" style="width:20px;height:20px;min-width:20px;max-width:20px;" class="h-5 w-5 transition-transform duration-200" :class="open ? 'rotate-90 text-amber-800' : 'text-gray-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </button>

                                        <div 
                                            x-show="open" 
                                            x-transition:enter="transition ease-out duration-100" 
                                            x-transition:enter-start="transform opacity-0 scale-95" 
                                            x-transition:enter-end="transform opacity-100 scale-100" 
                                            x-transition:leave="transition ease-in duration-75" 
                                            x-transition:leave-start="transform opacity-100 scale-100" 
                                            x-transition:leave-end="transform opacity-0 scale-95" 
                                            :class="({{ $totalUsers }} <= 2 || ({{ $index }} % perPage) < 3) ? 'top-full mt-2 origin-top-right' : 'bottom-full mb-2 origin-bottom-right'"
                                            class="absolute right-0 z-30 w-44 rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl ring-1 ring-black/5 focus:outline-none"
                                            style="display: none;"
                                        >
                                            <a 
                                                href="{{ route('users.show', $user) }}" 
                                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900"
                                            >
                                                <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;max-width:16px;" class="h-4 w-4 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>Lihat Detail</span>
                                            </a>
                                            <a 
                                                href="{{ route('users.edit', $user) }}" 
                                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900"
                                            >
                                                <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;max-width:16px;" class="h-4 w-4 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit User</span>
                                            </a>
                                            <div class="my-1.5 border-t border-gray-100"></div>
                                            <form 
                                                action="{{ route('users.destroy', $user) }}" 
                                                method="POST" 
                                                class="block w-full" 
                                                data-confirm-action="menghapus akun pengguna" 
                                                data-confirm-item="Pengguna: {{ $user->fullname }} ({{ $user->email }})"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit" 
                                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50 hover:text-red-700"
                                                >
                                                    <svg width="16" height="16" style="width:16px;height:16px;min-width:16px;max-width:16px;" class="h-4 w-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>Hapus User</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar Terpisah per Accordion -->
            <footer class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 bg-gray-50/50 px-5 py-3 sm:flex-row">
                <div class="text-xs text-gray-500">
                    Menampilkan 
                    <span class="font-semibold text-gray-900" x-text="total === 0 ? 0 : startIndex + 1"></span>
                    -
                    <span class="font-semibold text-gray-900" x-text="endIndex"></span>
                    dari 
                    <span class="font-semibold text-gray-900" x-text="total"></span>
                    user
                </div>

                <div class="flex items-center gap-1.5" x-show="totalPages > 1">
                    <!-- Tombol Sebelumnya -->
                    <button 
                        type="button" 
                        @click="prevPage()" 
                        :disabled="page === 1"
                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-2xs transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Halaman sebelumnya"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Sebelumnya</span>
                    </button>

                    <!-- Tombol Angka Halaman -->
                    <div class="flex items-center gap-1">
                        <template x-for="(p, i) in pageList" :key="i">
                            <button 
                                type="button" 
                                @click="p !== '...' && goToPage(p)"
                                :disabled="p === '...'"
                                :class="{
                                    'bg-gray-900 text-white font-semibold shadow-2xs': page === p,
                                    'text-gray-700 hover:bg-gray-200 bg-white border border-gray-200': page !== p && p !== '...',
                                    'text-gray-400 cursor-default border-transparent': p === '...'
                                }"
                                class="flex h-7 min-w-[28px] items-center justify-center rounded-md px-1.5 text-xs font-medium transition"
                                x-text="p"
                            ></button>
                        </template>
                    </div>

                    <!-- Tombol Selanjutnya -->
                    <button 
                        type="button" 
                        @click="nextPage()" 
                        :disabled="page === totalPages"
                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-2xs transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Halaman selanjutnya"
                    >
                        <span>Selanjutnya</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </footer>
        @endif
    </div>
</section>


<!-- Modal Peringatan Ruangan Sedang Dalam Perbaikan -->
<div id="room-maintenance-alert-modal" class="fixed inset-0 z-[80] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="room-maintenance-alert-title">
    <div id="room-maintenance-alert-backdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"></div>
    <div class="relative z-10 w-full max-w-md rounded-2xl border border-amber-200 bg-white p-6 shadow-2xl">
        <div class="flex items-start gap-3.5 border-b border-slate-100 pb-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p id="maintenance-alert-room-name" class="text-xs font-bold uppercase tracking-wider text-amber-700">Ruangan</p>
                <h3 id="room-maintenance-alert-title" class="mt-0.5 text-lg font-bold text-slate-900">Ruangan Sedang Dalam Perbaikan</h3>
            </div>
            <button type="button" id="room-maintenance-alert-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <p class="text-sm font-medium leading-relaxed text-slate-700">
                Ruangan sedang dalam perbaikan, mohon lihat catatan ini dan hubungi nomor di bawah ini:
            </p>

            <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3.5">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-800">Catatan dari PIC tentang Ruangan</p>
                <p id="maintenance-alert-note" class="mt-1.5 whitespace-pre-line text-sm font-medium text-slate-800"></p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Penanggung Jawab (PIC) Ruangan</p>
                <p id="maintenance-alert-pic-name" class="mt-1 text-sm font-bold text-slate-900"></p>
                <p id="maintenance-alert-pic-phone" class="mt-0.5 text-xs font-medium text-slate-600"></p>

                <div id="maintenance-alert-wa-wrap" class="mt-3">
                    <a
                        id="maintenance-alert-wa-link"
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824zm-3.423-14.416c-6.627 0-12 5.373-12 12 0 2.625.846 5.059 2.284 7.033l-1.494 5.457 5.601-1.469c1.911 1.258 4.195 1.979 6.609 1.979 6.627 0 12-5.373 12-12s-5.373-12-12-12z"/>
                        </svg>
                        <span>Hubungi PIC via WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
            <button type="button" id="room-maintenance-alert-ok" class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Mengerti &amp; Tutup
            </button>
        </div>
    </div>
</div>

<script>
    window.showRoomMaintenanceModal = function (data) {
        const modal = document.getElementById('room-maintenance-alert-modal');
        if (!modal) return;

        const roomNameEl = document.getElementById('maintenance-alert-room-name');
        const noteEl = document.getElementById('maintenance-alert-note');
        const picNameEl = document.getElementById('maintenance-alert-pic-name');
        const picPhoneEl = document.getElementById('maintenance-alert-pic-phone');
        const waWrapEl = document.getElementById('maintenance-alert-wa-wrap');
        const waLinkEl = document.getElementById('maintenance-alert-wa-link');

        if (roomNameEl) roomNameEl.textContent = data.roomName || 'Ruangan';
        if (noteEl) {
            noteEl.textContent = (data.note && data.note.trim() !== '')
                ? data.note
                : 'Ruangan sedang dalam proses perbaikan / renovasi dan sementara tidak dapat digunakan.';
        }
        if (picNameEl) {
            picNameEl.textContent = (data.picName && data.picName !== '-') ? data.picName : 'PIC Ruangan';
        }
        if (picPhoneEl) {
            picPhoneEl.textContent = data.picPhone ? `Nomor WhatsApp: ${data.picPhone}` : 'Nomor WhatsApp belum tersedia';
        }

        if (waLinkEl && waWrapEl) {
            let waUrl = data.picWa || '';
            if (!waUrl && data.picPhone) {
                let clean = String(data.picPhone).replace(/[^0-9]/g, '');
                if (clean.startsWith('0')) clean = '62' + clean.substring(1);
                if (clean) {
                    const msg = encodeURIComponent(`Halo Bapak/Ibu PIC ${data.roomName || ''}, saya ingin menanyakan perihal ruangan ${data.roomName || ''} yang sedang dalam perbaikan.`);
                    waUrl = `https://wa.me/${clean}?text=${msg}`;
                }
            }
            if (waUrl) {
                waLinkEl.href = waUrl;
                waWrapEl.classList.remove('hidden');
            } else {
                waWrapEl.classList.add('hidden');
            }
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('room-maintenance-alert-modal');
        const closeBtn = document.getElementById('room-maintenance-alert-close');
        const okBtn = document.getElementById('room-maintenance-alert-ok');
        const backdrop = document.getElementById('room-maintenance-alert-backdrop');

        function closeMaintenanceModal() {
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        closeBtn?.addEventListener('click', closeMaintenanceModal);
        okBtn?.addEventListener('click', closeMaintenanceModal);
        backdrop?.addEventListener('click', closeMaintenanceModal);
    });
</script>


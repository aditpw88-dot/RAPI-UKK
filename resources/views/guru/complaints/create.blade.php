<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-medium text-slate-500">Pengaduan Kerusakan</p><h2 class="mt-1 text-2xl font-semibold text-slate-900">Buat Laporan Baru</h2></div></x-slot>
    <div class="min-h-screen bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php($isAdmin = str_contains(strtolower(auth()->user()->role?->role_name ?? ''), 'admin'))
            <form id="complaint-form" method="POST" action="{{ route($isAdmin ? 'admin.complaints.store' : 'guru.complaints.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm" data-confirm-action="mengirim laporan kerusakan" data-confirm-item="Laporan kerusakan baru">
                @csrf
                <div>
                    <label for="room_search" class="block text-sm font-semibold text-slate-700">Pilih Ruang</label>
                    <input type="hidden" id="room_id" name="room_id" value="{{ old('room_id', $selectedRoomId) }}">
                    <input type="search" id="room_search" list="complaint-room-options" value="{{ $rooms->firstWhere('id', old('room_id', $selectedRoomId))?->room_name }}" placeholder="Ketik nama ruang atau pilih dari daftar" autocomplete="off" required class="mt-2 w-full rounded-md border-gray-300 text-sm">
                    <datalist id="complaint-room-options">
                        @foreach ($rooms as $room)
                            <option value="{{ $room->room_name }}" data-room-id="{{ $room->id }}" label="Kapasitas {{ $room->capacity }} orang"></option>
                        @endforeach
                    </datalist>
                    <select id="room_select_sync" class="hidden" aria-hidden="true" tabindex="-1">
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id', $selectedRoomId) == $room->id)>{{ $room->room_name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Ruang yang terisi otomatis tetap bisa diganti. Ketik nama ruang lalu pilih hasil yang tersedia.</p>
                </div>
                <section class="rounded-lg border border-slate-200 bg-slate-50 p-4" aria-labelledby="complaint-reporter-heading">
                    <h3 id="complaint-reporter-heading" class="text-sm font-semibold text-slate-800">Detail Pengaju Laporan</h3>
                    <dl class="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-500">Nama</dt><dd class="font-medium text-slate-800">{{ auth()->user()->fullname }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Username</dt><dd class="font-medium text-slate-800">{{ auth()->user()->username }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Peran</dt><dd class="font-medium text-slate-800">{{ auth()->user()->role?->role_name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Email</dt><dd class="break-all font-medium text-slate-800">{{ auth()->user()->email }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Telepon</dt><dd class="font-medium text-slate-800">{{ auth()->user()->phone_number ?: 'Belum diisi' }}</dd></div>
                    </dl>
                    <p class="mt-3 text-xs text-slate-500">Identitas ini dicatat otomatis sebagai pelapor.</p>
                </section>
                <div>
                    <label for="description" class="block text-sm font-semibold text-slate-700">Deskripsi Kerusakan</label>
                    <textarea id="description" name="description" rows="5" required class="mt-2 w-full rounded-md border-gray-300 text-sm" placeholder="Jelaskan kerusakan yang ditemukan...">{{ old('description') }}</textarea>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                    <label class="block text-sm font-bold text-slate-800">
                        Foto Bukti Kerusakan <span class="text-red-500">*</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">Ambil foto kerusakan langsung menggunakan kamera HP atau unggah dari galeri/file (maksimal 5 MB).</p>

                    <!-- Input tersembunyi yang menyimpan file asli untuk dikirim ke backend -->
                    <input id="photo" name="photo" type="file" accept="image/*" required class="hidden">
                    
                    <!-- Input pemicu kamera HP asli (capture="environment") -->
                    <input id="camera-capture-input" type="file" accept="image/*" capture="environment" class="hidden">
                    
                    <!-- Input pemicu galeri / file picker -->
                    <input id="gallery-picker-input" type="file" accept="image/*" class="hidden">

                    <!-- Tombol Opsi Mobile Friendly -->
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <button type="button" id="btn-camera-hp" class="flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 active:scale-[0.98] transition">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Ambil Foto via Kamera HP</span>
                        </button>

                        <button type="button" id="btn-gallery" class="flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.98] transition">
                            <svg class="h-5 w-5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Pilih dari Galeri / File</span>
                        </button>

                        <!-- Tombol Webcam Laptop / PC Berukuran Setara 2 Tombol Di Atasnya (Span 2 Kolom) & Warna Oranye -->
                        <button type="button" id="start-camera" class="sm:col-span-2 flex w-full items-center justify-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 active:scale-[0.98] transition">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>Gunakan Webcam Laptop / PC</span>
                        </button>
                    </div>

                    <!-- Kontrol Webcam Saat Aktif -->
                    <div id="webcam-controls" class="mt-2.5 hidden flex-wrap gap-2">
                        <button id="capture-photo" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-700 active:scale-[0.98] transition">Jepret Webcam</button>
                        <button id="close-camera" type="button" class="rounded-xl border border-red-300 bg-white px-4 py-2.5 text-xs font-semibold text-red-700 shadow-sm hover:bg-red-50 active:scale-[0.98] transition">Tutup Webcam</button>
                    </div>

                    <p id="camera-message" class="mt-2 text-xs font-medium text-slate-600" role="status" aria-live="polite"></p>
                    <video id="camera-preview" class="mt-3 hidden max-h-80 w-full rounded-xl bg-black object-contain" autoplay muted playsinline></video>

                    <!-- Pratinjau Foto Yang Dipilih -->
                    <div id="photo-preview-container" class="mt-3 hidden rounded-xl border border-emerald-200 bg-white p-3 shadow-xs">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-emerald-800 flex items-center gap-1">
                                <svg class="h-4 w-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Foto Berhasil Dipilih
                            </span>
                            <button type="button" id="remove-photo-btn" class="text-xs font-semibold text-red-600 hover:text-red-700 hover:underline">
                                Hapus / Ganti Foto
                            </button>
                        </div>
                        <img id="photo-preview" class="max-h-80 w-full rounded-lg object-contain bg-slate-100" alt="Pratinjau foto kerusakan">
                        <p id="photo-filename" class="mt-1.5 text-center text-xs text-slate-500 font-mono truncate"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 pt-5">
                    <a href="{{ $isAdmin ? route('admin.complaints') : route('guru.dashboard') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const roomSearch = document.getElementById('room_search');
        const roomId = document.getElementById('room_id');
        const roomOptions = document.querySelectorAll('#complaint-room-options option');
        const complaintForm = document.getElementById('complaint-form');
        const requesterName = @js(auth()->user()->fullname);

        function syncRoomId() {
            const selectedRoom = Array.from(roomOptions).find((option) => option.value === roomSearch.value);
            roomId.value = selectedRoom?.dataset.roomId ?? '';
            roomSearch.setCustomValidity(selectedRoom ? '' : 'Pilih ruang dari daftar yang tersedia.');
            if (selectedRoom) {
                complaintForm.dataset.confirmItem = `Ruang: ${selectedRoom.value} · Pengaju: ${requesterName}`;
            }
        }

        roomSearch?.addEventListener('input', syncRoomId);
        complaintForm?.addEventListener('submit', syncRoomId);

        const photoInput = document.getElementById('photo');
        const cameraCaptureInput = document.getElementById('camera-capture-input');
        const galleryPickerInput = document.getElementById('gallery-picker-input');
        const btnCameraHp = document.getElementById('btn-camera-hp');
        const btnGallery = document.getElementById('btn-gallery');
        const photoPreviewContainer = document.getElementById('photo-preview-container');
        const photoPreview = document.getElementById('photo-preview');
        const photoFilename = document.getElementById('photo-filename');
        const removePhotoBtn = document.getElementById('remove-photo-btn');
        const cameraPreview = document.getElementById('camera-preview');
        const cameraMessage = document.getElementById('camera-message');
        const startCameraButton = document.getElementById('start-camera');
        const webcamControls = document.getElementById('webcam-controls');
        const capturePhotoButton = document.getElementById('capture-photo');
        const closeCameraButton = document.getElementById('close-camera');
        let cameraStream = null;
        let previewUrl = null;

        // Buka Kamera HP langsung via capture="environment"
        btnCameraHp?.addEventListener('click', () => {
            stopCamera();
            cameraCaptureInput?.click();
        });

        // Buka Galeri HP / File Picker
        btnGallery?.addEventListener('click', () => {
            stopCamera();
            galleryPickerInput?.click();
        });

        cameraCaptureInput?.addEventListener('change', () => {
            const file = cameraCaptureInput.files?.[0];
            if (file) {
                useSelectedPhoto(file);
            }
        });

        galleryPickerInput?.addEventListener('change', () => {
            const file = galleryPickerInput.files?.[0];
            if (file) {
                useSelectedPhoto(file);
            }
        });

        removePhotoBtn?.addEventListener('click', () => {
            photoInput.value = '';
            if (cameraCaptureInput) cameraCaptureInput.value = '';
            if (galleryPickerInput) galleryPickerInput.value = '';
            photoPreviewContainer?.classList.add('hidden');
            if (photoFilename) photoFilename.textContent = '';
            if (cameraMessage) cameraMessage.textContent = '';
        });

        function stopCamera() {
            cameraStream?.getTracks().forEach((track) => track.stop());
            cameraStream = null;
            cameraPreview.srcObject = null;
            cameraPreview.classList.add('hidden');
            webcamControls?.classList.add('hidden');
            capturePhotoButton.classList.add('hidden');
            closeCameraButton.classList.add('hidden');
            startCameraButton.classList.remove('hidden');
        }

        function useSelectedPhoto(file) {
            if (!file) {
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            photoInput.files = transfer.files;
            photoInput.dispatchEvent(new Event('change', { bubbles: true }));
            showPhoto(file);
            cameraMessage.textContent = '✓ Foto siap dikirim bersama laporan.';
        }

        function showPhoto(file) {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }

            previewUrl = URL.createObjectURL(file);
            photoPreview.src = previewUrl;
            photoPreviewContainer?.classList.remove('hidden');
            if (photoFilename) {
                const sizeKb = (file.size / 1024).toFixed(1);
                photoFilename.textContent = `${file.name || 'foto-kerusakan.jpg'} (${sizeKb} KB)`;
            }
        }

        startCameraButton?.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                cameraMessage.textContent = window.isSecureContext
                    ? 'Browser ini tidak mendukung akses webcam langsung. Silakan gunakan opsi Kamera HP atau Galeri.'
                    : 'Akses webcam memerlukan HTTPS. Silakan gunakan tombol Kamera HP di atas.';
                return;
            }

            try {
                cameraMessage.textContent = 'Meminta izin kamera...';
                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: { facingMode: { exact: 'environment' } },
                    });
                } catch (error) {
                    if (error.name !== 'OverconstrainedError') {
                        throw error;
                    }

                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: true,
                    });
                }
                cameraPreview.srcObject = cameraStream;
                cameraPreview.classList.remove('hidden');
                webcamControls?.classList.remove('hidden');
                capturePhotoButton.classList.remove('hidden');
                closeCameraButton.classList.remove('hidden');
                startCameraButton.classList.add('hidden');
                photoPreviewContainer?.classList.add('hidden');
                const facingMode = cameraStream.getVideoTracks()[0]?.getSettings().facingMode;
                cameraMessage.textContent = facingMode === 'user'
                    ? 'Webcam depan aktif. Arahkan ke kerusakan, lalu klik Jepret.'
                    : 'Kamera belakang aktif. Arahkan ke kerusakan, lalu klik Jepret.';
            } catch (error) {
                cameraStream?.getTracks().forEach((track) => track.stop());
                cameraStream = null;
                const messages = {
                    NotAllowedError: 'Izin webcam ditolak. Gunakan tombol "Ambil Foto via Kamera HP" di atas.',
                    NotFoundError: 'Webcam tidak ditemukan. Gunakan tombol "Ambil Foto via Kamera HP" di atas.',
                    NotReadableError: 'Webcam sedang digunakan aplikasi lain.',
                    OverconstrainedError: 'Kamera belakang tidak tersedia.',
                    SecurityError: 'Browser memblokir akses kamera langsung. Silakan gunakan tombol Kamera HP.',
                };
                cameraMessage.textContent = messages[error.name]
                    ?? 'Webcam tidak dapat dibuka. Silakan gunakan tombol Kamera HP di atas.';
            }
        });

        capturePhotoButton?.addEventListener('click', () => {
            if (!cameraPreview.videoWidth || !cameraPreview.videoHeight) {
                cameraMessage.textContent = 'Kamera belum siap. Coba lagi sebentar.';
                return;
            }

            const maxDimension = 1600;
            const scale = Math.min(1, maxDimension / Math.max(cameraPreview.videoWidth, cameraPreview.videoHeight));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(cameraPreview.videoWidth * scale);
            canvas.height = Math.round(cameraPreview.videoHeight * scale);
            const context = canvas.getContext('2d');
            if (!context) {
                cameraMessage.textContent = 'Foto gagal diproses.';
                return;
            }

            context.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) {
                    cameraMessage.textContent = 'Foto gagal diproses.';
                    return;
                }

                const photoFile = new File([blob], `foto-kerusakan-${Date.now()}.jpg`, { type: 'image/jpeg' });
                useSelectedPhoto(photoFile);
                stopCamera();
            }, 'image/jpeg', 0.85);
        });

        photoInput?.addEventListener('change', () => {
            const [file] = photoInput.files;
            if (file) {
                showPhoto(file);
                cameraMessage.textContent = '✓ Foto siap dikirim bersama laporan.';
            }
        });

        closeCameraButton?.addEventListener('click', () => {
            stopCamera();
            cameraMessage.textContent = 'Kamera sudah ditutup.';
        });

        complaintForm.addEventListener('submit', stopCamera);
        window.addEventListener('beforeunload', () => {
            stopCamera();
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        });
    });
</script>

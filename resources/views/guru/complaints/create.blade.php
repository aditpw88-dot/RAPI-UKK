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
                <div>
                    <label for="photo" class="block text-sm font-semibold text-slate-700">Foto Kerusakan</label>
                    <input id="photo" name="photo" type="file" accept="image/*" required class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Pilih file gambar atau ambil foto langsung. Maksimal 5 MB.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button id="start-camera" type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Buka Kamera</button>
                        <button id="capture-photo" type="button" class="hidden rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Ambil Foto</button>
                        <button id="close-camera" type="button" class="hidden rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Tutup Kamera</button>
                        <button id="retake-photo" type="button" class="hidden rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ambil Ulang</button>
                    </div>
                    <p id="camera-message" class="mt-2 text-sm text-slate-600" role="status" aria-live="polite"></p>
                    <video id="camera-preview" class="mt-3 hidden max-h-96 w-full rounded-lg bg-black object-contain" autoplay muted playsinline style="transform: none;"></video>
                    <img id="photo-preview" class="mt-3 hidden max-h-96 rounded-lg border border-gray-200 object-contain" alt="Pratinjau foto kerusakan">
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
        const cameraPreview = document.getElementById('camera-preview');
        const photoPreview = document.getElementById('photo-preview');
        const cameraMessage = document.getElementById('camera-message');
        const startCameraButton = document.getElementById('start-camera');
        const capturePhotoButton = document.getElementById('capture-photo');
        const closeCameraButton = document.getElementById('close-camera');
        const retakePhotoButton = document.getElementById('retake-photo');
        let cameraStream = null;
        let previewUrl = null;

        function stopCamera() {
            cameraStream?.getTracks().forEach((track) => track.stop());
            cameraStream = null;
            cameraPreview.srcObject = null;
            cameraPreview.classList.add('hidden');
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
            cameraMessage.textContent = 'Foto siap dikirim bersama laporan.';
        }

        function showPhoto(file) {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }

            previewUrl = URL.createObjectURL(file);
            photoPreview.src = previewUrl;
            photoPreview.classList.remove('hidden');
            retakePhotoButton.classList.remove('hidden');
        }

        startCameraButton.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                cameraMessage.textContent = window.isSecureContext
                    ? 'Browser ini tidak mendukung akses kamera langsung. Silakan upload file gambar.'
                    : 'Akses kamera langsung memerlukan HTTPS. Silakan upload file gambar.';
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
                capturePhotoButton.classList.remove('hidden');
                closeCameraButton.classList.remove('hidden');
                startCameraButton.classList.add('hidden');
                photoPreview.classList.add('hidden');
                retakePhotoButton.classList.add('hidden');
                const facingMode = cameraStream.getVideoTracks()[0]?.getSettings().facingMode;
                cameraMessage.textContent = facingMode === 'user'
                    ? 'Kamera depan aktif tanpa efek cermin. Arahkan ke kerusakan, lalu ambil foto.'
                    : 'Kamera belakang aktif. Arahkan ke kerusakan, lalu ambil foto.';
            } catch (error) {
                cameraStream?.getTracks().forEach((track) => track.stop());
                cameraStream = null;
                const messages = {
                    NotAllowedError: 'Izin kamera ditolak. Izinkan akses kamera di pengaturan browser atau upload file gambar.',
                    NotFoundError: 'Kamera tidak ditemukan. Silakan upload file gambar.',
                    NotReadableError: 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut atau upload file gambar.',
                    OverconstrainedError: 'Kamera belakang tidak tersedia. Silakan upload file gambar.',
                    SecurityError: 'Browser memblokir akses kamera. Buka situs melalui HTTPS atau upload file gambar.',
                };
                cameraMessage.textContent = messages[error.name]
                    ?? (!window.isSecureContext
                        ? 'Akses kamera langsung memerlukan HTTPS. Silakan upload file gambar.'
                        : 'Kamera tidak dapat dibuka. Periksa izin browser atau upload file gambar.');
            }
        });

        capturePhotoButton.addEventListener('click', () => {
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
                cameraMessage.textContent = 'Foto gagal diproses. Silakan gunakan upload file.';
                return;
            }

            context.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) {
                    cameraMessage.textContent = 'Foto gagal diproses. Silakan coba lagi.';
                    return;
                }

                const photoFile = new File([blob], `foto-kerusakan-${Date.now()}.jpg`, { type: 'image/jpeg' });
                useSelectedPhoto(photoFile);
                stopCamera();
            }, 'image/jpeg', 0.85);
        });

        photoInput.addEventListener('change', () => {
            const [file] = photoInput.files;
            if (file) {
                showPhoto(file);
                retakePhotoButton.classList.remove('hidden');
                cameraMessage.textContent = 'Foto siap dikirim bersama laporan.';
            }
        });

        retakePhotoButton.addEventListener('click', () => {
            photoInput.value = '';
            photoPreview.classList.add('hidden');
            retakePhotoButton.classList.add('hidden');
            cameraMessage.textContent = '';
            photoInput.click();
        });

        closeCameraButton.addEventListener('click', () => {
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

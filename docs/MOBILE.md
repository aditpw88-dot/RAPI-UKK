# Menjalankan aplikasi Flutter

Aplikasi Android Flutter berada di folder `mobile/` dan memakai API Laravel yang dijelaskan di [API.md](./API.md).

## Menjalankan di Android Emulator

1. Pastikan Flutter, Android Studio/Android SDK, dan emulator sudah terpasang.
2. Jalankan Laravel dari folder utama proyek dan biarkan terminal tetap berjalan:

   ```powershell
   php artisan serve --host=0.0.0.0
   ```

3. Dari folder `mobile/`, jalankan:

   ```powershell
   flutter pub get
   flutter run
   ```

4. Pada layar login, alamat API emulator Android adalah `http://10.0.2.2:8000/api`. Masuk dengan username/email dan password akun Laravel.

Di Windows, bila Flutter menampilkan pesan bahwa pembuatan symlink membutuhkan Developer Mode, aktifkan **Windows Settings → System → For developers → Developer Mode**, lalu ulangi `flutter pub get`.

Jika proses build berhenti karena lisensi Android SDK/NDK, buka **Android Studio → Tools → SDK Manager → SDK Tools**, pasang NDK yang diminta, lalu setujui lisensinya. Alternatifnya jalankan `sdkmanager --licenses` dari terminal Android SDK dan setujui lisensi yang ditampilkan.

## Menjalankan di ponsel Android fisik

Ponsel dan komputer harus berada di jaringan Wi-Fi yang sama. Jalankan Laravel seperti di atas, cari alamat IPv4 komputer (misalnya `192.168.1.20`), lalu masukkan `http://192.168.1.20:8000/api` pada kolom Alamat API. Izinkan koneksi jaringan privat pada Windows Firewall bila diminta.

Alamat `10.0.2.2` hanya untuk Android Emulator. Jangan menggunakan `localhost` pada ponsel karena itu menunjuk ke ponsel itu sendiri.

## Fitur aplikasi

- Login dan logout menggunakan token Sanctum; token disimpan dengan secure storage.
- Melihat ruangan aktif dan agenda yang disetujui.
- Guru/Admin/Waka dapat membuat pengajuan agenda dan laporan kerusakan beserta foto.
- PIC dapat melihat agenda untuk ruang yang ditanganinya, menyetujui/menolak agenda, serta memperbarui status pengaduan.
- Admin/Waka dapat menambah, mengubah, dan menghapus ruangan. Form ruangan memerlukan ID akun PIC.

Untuk membangun APK debug, jalankan dari folder `mobile/`:

```powershell
flutter build apk --debug
```

APK akan dibuat di `mobile/build/app/outputs/flutter-apk/app-debug.apk`.

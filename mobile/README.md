# Agenda & Sarpras Sekolah - Versi Mobile (Flutter)

Aplikasi mobile Flutter untuk sistem peminjaman ruangan, agenda kegiatan, dan pengaduan sarana/prasarana sekolah.

> **Catatan Peran:**
> - **Guru / Organisasi**: Mengajukan agenda peminjaman ruangan, memantau status persetujuan, dan membuat laporan pengaduan kerusakan fasilitas lengkap dengan foto.
> - **PIC Ruang**: Meninjau & menyetujui/menolak pengajuan ruangan di bawah tanggung jawabnya, serta memperbarui status perbaikan sarpras (*Belum Diperbaiki*, *Menunggu Spare Part*, *Sudah Diperbaiki*).
> - **Admin / Waka**: Satu kelompok role yang menggunakan aplikasi web Laravel, bukan aplikasi mobile.
> - **Siswa**: Tidak termasuk role aplikasi mobile.

---

## Fitur Utama Berdasarkan Peran

### 1. Guru / Organisasi
- **Dashboard Ringkasan**: Statistik agenda disetujui, agenda milik guru, jumlah ruangan aktif, dan total pengaduan.
- **Daftar Ruangan**: Menampilkan nama ruangan, kapasitas, dan nama PIC ruangan.
- **Kelola Agenda**:
  - Tab *Disetujui*: Semua kegiatan sekolah yang sudah disahkan.
  - Tab *Agenda Saya*: Status pengajuan guru (*Disetujui*, *Menunggu*, atau *Ditolak* lengkap dengan alasan penolakan dari PIC).
  - Floating Action Button **Ajukan Agenda**: Memilih ruangan, tanggal pelaksanaan, dan rentang sesi (Sesi 1 sampai Sesi 13: 07:15 - 17:00 WIB) dengan validasi konflik jadwal otomatis.
- **Pengaduan Sarpras**:
  - Formulir laporan kerusakan fasilitas dengan unggah foto (kamera atau galeri) dan keterangan.
  - Pemantauan status perbaikan secara langsung.

### 2. PIC Ruang
- **Dashboard Tindakan**: Kartu peringatan khusus untuk pengajuan agenda yang memerlukan persetujuan PIC.
- **Ruangan PIC**: Menampilkan penanda khusus (*PIC ANDA*) pada ruangan yang menjadi tanggung jawabnya.
- **Persetujuan Agenda**:
  - Tab *Perlu Persetujuan*: Daftar permintaan peminjaman ruangan oleh guru.
  - Tombol **Setujui** (konfirmasi instan) dan **Tolak** (dialog input alasan penolakan).
- **Update Status Pengaduan Sarpras**:
  - Memperbarui status perbaikan dengan 1 kali klik (*Belum Diperbaiki*, *Menunggu Spare Part*, *Sudah Diperbaiki*).

---

## Akun Demo Pengujian

| Peran | Username | Kata Sandi | Keterangan |
|---|---|---|---|
| **Guru** | `adit-guru` | `password123` | Jika akun demo ini tersedia pada database lokal |
| **PIC** | `adit-pic` | `password123` | Jika akun demo ini tersedia pada database lokal |
| **Admin / Waka** | - | - | Login melalui web Laravel |

Role mobile yang didukung hanya Guru dan PIC Ruang. Akun Admin/Waka diarahkan
menggunakan browser web. Siswa tidak disediakan sebagai role pada aplikasi ini.

---

## Menjalankan Aplikasi Mobile

Pastikan backend Laravel di Laragon aktif.

### 1. Menjalankan di Windows Desktop
```bash
cd mobile
flutter run -d windows
```
*(Gunakan alamat API `http://ukk-aditya.test/api` atau `http://127.0.0.1/UKK-aditya/public/api`)*

### 2. Menjalankan di Browser (Chrome)
```bash
cd mobile
flutter run -d chrome
```
Flutter Web akan langsung membuka kalender web Laravel di `http://ukk-aditya.test/`.
Pastikan virtual host Laravel Laragon aktif. Aplikasi Flutter native untuk Windows
dan Android tetap menyediakan login Guru/PIC.

### 3. Menjalankan di Android Emulator
```bash
cd mobile
flutter run
```
*(Di emulator Android, gunakan alamat API `http://10.0.2.2:8000/api` atau preset yang tersedia di aplikasi)*

## Integrasi API dan Database Laravel

Aplikasi Flutter berkomunikasi dengan Laravel melalui endpoint JSON di `/api`, bukan
mengakses database SQL secara langsung. Laravel menangani autentikasi Sanctum serta
membaca/menyimpan data melalui model dan koneksi database yang dikonfigurasi di `.env`.

Pastikan koneksi database Laravel sudah benar, migrasi sudah dijalankan dengan
`php artisan migrate`, dan `php artisan storage:link` sudah dibuat agar foto pengaduan
dapat ditampilkan. Jangan memasukkan kredensial database ke aplikasi mobile.

URL server di aplikasi harus menunjuk ke host Laravel dan diakhiri `/api`. Gunakan
`http://10.0.2.2:8000/api` untuk Android Emulator dengan `php artisan serve`,
`http://ukk-aditya.test/api` untuk komputer Laragon, atau alamat IP LAN komputer
Laragon saat memakai perangkat Android fisik. Login menggunakan akun Laravel yang
ada; data akan tersimpan pada database yang sama dengan aplikasi web.

### 4. Menjalankan Pengujian Unit & Logika Otorisasi
```bash
cd mobile
flutter test
```

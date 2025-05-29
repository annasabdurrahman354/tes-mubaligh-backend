# Aplikasi Pengetesan Mubaligh - Backend

Selamat datang di repositori backend untuk Aplikasi Pengetesan Mubaligh. Sistem ini dirancang untuk mengelola proses pengetesan calon mubaligh yang terdiri dari dua tahap utama, yaitu tahap pertama di Pondok Pesantren Wali Barokah, Kediri, dan tahap kedua di Pondok Pesantren Al Ubaidah, Kertosono.

Backend ini dibangun menggunakan Laravel dan menyediakan Admin Panel yang dikembangkan dengan Filament PHP, serta REST API untuk dikonsumsi oleh aplikasi frontend guru yang dibangun menggunakan React PWA.

## Fitur Utama

* **Manajemen Peserta**: Mengelola data peserta tes.
* **Penjadwalan Tes**: Mengatur jadwal tes untuk kedua tahap.
* **Penilaian**: Memfasilitasi input dan pengelolaan nilai tes dari para guru.
* **Pelaporan**: Menghasilkan laporan hasil tes.
* **Admin Panel**: Antarmuka administrasi berbasis web untuk mengelola seluruh aspek sistem (pengguna, peserta, periode tes, lokasi, dll.).
* **REST API**: Menyediakan endpoint yang aman dan terstruktur untuk aplikasi frontend guru.

## Tahapan Tes

Sistem ini mengakomodir dua tahapan tes yang berlokasi di:

1.  **Tahap 1**: Pondok Pesantren Wali Barokah, Kediri.
2.  **Tahap 2**: Pondok Pesantren Al Ubaidah, Kertosono.

Setiap tahapan memiliki kriteria dan proses penilaiannya masing-masing yang dikelola melalui sistem ini.

## Teknologi yang Digunakan

* **Backend**:
    * PHP 8.2 atau lebih tinggi
    * Laravel Framework 11.x
    * Filament PHP 3.x (untuk Admin Panel)
    * MySQL / MariaDB (atau database lain yang kompatibel dengan Laravel)
    * Laravel Sanctum (untuk autentikasi API)
* **API**:
    * RESTful API
* **Frontend (Terpisah)**:
    * React PWA (Progressive Web App) - *Repositori terpisah*

## Struktur Proyek (Backend)

* `app/`: Berisi logika inti aplikasi, termasuk Models, Controllers, Enums, Filament Resources, Policies, dll.
* `app/Http/Controllers/Api/`: Berisi controller yang menangani request untuk REST API.
* `app/Filament/`: Berisi konfigurasi dan resource untuk Admin Panel Filament.
* `config/`: Berisi file konfigurasi aplikasi.
* `database/`: Berisi migrasi database, seeder, dan factory.
* `routes/`:
    * `api.php`: Mendefinisikan rute untuk REST API.
    * `web.php`: Mendefinisikan rute untuk aplikasi web (termasuk Admin Panel Filament).
* `resources/views/`: Berisi file-file view (terutama untuk Filament).

## Instalasi & Setup (Lokal)

1.  **Clone Repositori:**
    ```bash
    git clone [https://github.com/annasabdurrahman354/tes-mubaligh-backend.git](https://github.com/annasabdurrahman354/tes-mubaligh-backend.git)
    cd tes-mubaligh-backend
    ```

2.  **Install Dependencies (Composer):**
    ```bash
    composer install
    ```

3.  **Buat File `.env`:**
    Salin file `.env.example` menjadi `.env`.
    ```bash
    cp .env.example .env
    ```
    Sesuaikan konfigurasi database dan variabel environment lainnya di dalam file `.env`:
    ```env
    APP_NAME="Tes Mubaligh Backend"
    APP_ENV=local
    APP_KEY=
    APP_DEBUG=true
    APP_URL=http://localhost:8000 # Sesuaikan dengan URL lokal Anda

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nama_database_anda
    DB_USERNAME=username_database_anda
    DB_PASSWORD=password_database_anda

    # Konfigurasi untuk Filament
    FILAMENT_FILESYSTEM_DISK=public
    FILAMENT_AVATAR_PROVIDER=gravatar # atau provider lain
    FILAMENT_BRAND="Tes Mubaligh"

    # Jika menggunakan Sanctum untuk SPA
    SANCTUM_STATEFUL_DOMAINS=localhost:3000 # URL frontend React PWA Anda
    SESSION_DOMAIN=localhost
    ```

4.  **Generate Application Key:**
    ```bash
    php artisan key:generate
    ```

5.  **Jalankan Migrasi Database:**
    Pastikan database sudah dibuat sesuai dengan konfigurasi di `.env`.
    ```bash
    php artisan migrate --seed # Jalankan seeder jika ada data awal yang dibutuhkan
    ```

6.  **Storage Link:**
    ```bash
    php artisan storage:link
    ```

7.  **Buat User Admin (jika belum ada di seeder):**
    Anda dapat membuat user admin pertama melalui `php artisan tinker` atau membuat seeder khusus.
    Contoh menggunakan tinker:
    ```bash
    php artisan tinker
    ```
    Lalu jalankan perintah berikut di dalam tinker:
    ```php
    // Contoh pembuatan user admin (sesuaikan dengan model User Anda dan role jika menggunakan Spatie Permission)
    // Pastikan roles 'super_admin' atau 'admin' sudah ada di database jika menggunakan Filament Shield atau Spatie Permission
    // $user = \App\Models\User::create([
    //     'name' => 'Admin User',
    //     'email' => 'admin@example.com',
    //     'password' => bcrypt('password'), // Ganti dengan password yang aman
    // ]);
    // $user->assignRole('super_admin'); // Jika menggunakan Spatie Permission dan Filament Shield
    ```
    Atau, jika Anda menggunakan Filament Shield, Anda bisa membuat user admin pertama dengan perintah:
    ```bash
    php artisan shield:user
    ```
    Ikuti instruksi yang muncul.

8.  **Jalankan Development Server:**
    ```bash
    php artisan serve
    ```
    Aplikasi backend (termasuk Admin Panel Filament) akan berjalan di `http://localhost:8000` (atau port lain jika ditentukan).
    Admin panel biasanya dapat diakses melalui `/admin`.

## REST API

Backend ini menyediakan REST API yang akan digunakan oleh aplikasi frontend guru (React PWA). Detail endpoint dan dokumentasi API dapat ditemukan atau dihasilkan menggunakan tools seperti Postman atau Swagger (jika diimplementasikan).

Beberapa contoh grup endpoint yang tersedia:
* Autentikasi (Login, Logout, Profil User)
* Data Peserta Tes Kediri & Kertosono
* Input Nilai Akademik & Akhlak untuk Kediri & Kertosono
* Opsi-opsi (seperti daftar periode, kelompok, dll.)
* Statistik

Pastikan untuk mengkonfigurasi Laravel Sanctum dengan benar untuk autentikasi SPA jika frontend Anda adalah Single Page Application.

## Admin Panel (Filament PHP)

Admin panel dapat diakses melalui `/admin` (atau path lain yang dikonfigurasi). Gunakan kredensial user admin yang telah dibuat untuk login.

Fitur yang tersedia di admin panel meliputi:
* Manajemen User & Peran (Roles & Permissions)
* Manajemen Data Master (Periode, Wilayah, Pondok, dll.)
* Manajemen Peserta Tes Kediri & Kertosono
* Manajemen Penilaian Akademik & Akhlak
* Pengaturan Situs
* Dan lain-lain.

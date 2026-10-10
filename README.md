# GigSync - Backend REST API (Laravel 11)

Backend RESTful API untuk platform marketplace **GigSync** (Upwork-Lite Android Native Kotlin Jetpack Compose), dibangun menggunakan **Laravel 11 (PHP 8.3+)** dan basis data relasional **MySQL 8.0+**.

---

## 🛠️ Tech Stack
- **Framework:** Laravel 11.x
- **Runtime:** PHP 8.3+
- **Database:** MySQL 8.0+ (InnoDB)
- **Authentication:** Laravel Sanctum (Bearer Token) + Google API Client (`google/apiclient`)
- **Media Handling:** Intervention Image (`intervention/image-laravel`)
- **JSON Serialization:** CamelCase response compatibility melalui Laravel API Resources (`JsonResource`)

---

## 🚀 Panduan Menjalankan di Lokal (Local Setup)

### 1. Prasyarat
- PHP 8.3+
- Composer
- MySQL Server (Laragon / XAMPP)

### 2. Konfigurasi Lingkungan (`.env`)
Pastikan file `.env` sudah diatur:
```env
APP_NAME=GigSync
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gigsync_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migrasi & Data Seeder
Jalankan perintah berikut untuk menginisialisasi skema tabel dan data demo Telkom University:
```bash
php artisan migrate:fresh --seed
```

### 4. Menjalankan Server Lokal untuk Android Emulator
Hubungkan emulator Android dengan mengikat server ke host `0.0.0.0`:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
> Di emulator Android Studio, gunakan URL: `http://10.0.2.2:8000/api/v1`

---

## 🧪 Menjalankan Pengujian Otomatis (Testing)
```bash
php artisan test
```

---

## 🔑 Akun Demo (Seeded Users)
| Role | Email | Password | Saldo Awal |
| :--- | :--- | :--- | :--- |
| **Client** | `client@gigsync.com` | `Password123!` | Rp 20.000.000 |
| **Freelancer** | `freelancer@gigsync.com` | `Password123!` | Rp 1.500.000 |

---

## 📡 Daftar Endpoint REST API (`/api/v1`)

### 1. Autentikasi
- `POST /api/v1/auth/register` - Registrasi akun
- `POST /api/v1/auth/login` - Login email & password
- `POST /api/v1/auth/google` - Verifikasi token Google Sign-In
- `POST /api/v1/auth/logout` - Logout & revoke token (Bearer token)
- `GET /api/v1/auth/me` - Ambil profil pengguna login
- `PUT /api/v1/users/active-role` - Ganti peran aktif (`CLIENT` / `FREELANCER`)
- `PUT /api/v1/users/profile` - Perbarui profil & avatar

### 2. Master Data
- `GET /api/v1/categories` - Daftar kategori
- `GET /api/v1/skills` - Daftar keahlian

### 3. Pekerjaan (Jobs)
- `GET /api/v1/jobs` - Feed lowongan pekerjaan (mendukung filter `search` & `category_id`)
- `GET /api/v1/jobs/{id}` - Detail lowongan pekerjaan
- `POST /api/v1/jobs` - Pasang lowongan baru (khusus Client)
- `GET /api/v1/client/jobs` - Lowongan milik klien yang sedang login

### 4. Proposal Pelamar
- `POST /api/v1/jobs/{jobId}/proposals` - Kirim proposal & unggah lampiran (Freelancer)
- `GET /api/v1/jobs/{jobId}/proposals` - Daftar pelamar untuk lowongan tertentu (Client)
- `GET /api/v1/proposals/my` - Daftar proposal yang diajukan oleh Freelancer
- `PUT /api/v1/proposals/{id}/status` - Terima (`ACCEPTED` $\rightarrow$ kunci escrow & buat kontrak) / Tolak (`REJECTED`)

### 5. Kontrak & Mock Escrow
- `GET /api/v1/contracts` - Daftar kontrak aktif pengguna
- `GET /api/v1/contracts/{id}` - Detail kontrak & status escrow
- `POST /api/v1/contracts/{id}/submit` - Penyerahan hasil kerja (status `UNDER_REVIEW`)
- `POST /api/v1/contracts/{id}/approve` - Setujui pekerjaan & rilis dana escrow (potong 10% platform fee)
- `POST /api/v1/contracts/{id}/revise` - Minta revisi pekerjaan (status `REVISION`)

### 6. Ulasan & Rating
- `POST /api/v1/contracts/{id}/reviews` - Beri rating (1-5) & ulasan setelah kontrak selesai
- `GET /api/v1/contracts/{id}/reviews` - Daftar ulasan kontrak
- `GET /api/v1/users/{id}/reviews` - Daftar ulasan pengguna

### 7. Dompet (Wallet)
- `GET /api/v1/wallet` - Saldo & riwayat mutasi transaksi
- `POST /api/v1/wallet/topup` - Top-up saldo mock dompet

### 8. Percakapan (Chat)
- `GET /api/v1/chats` - Daftar percakapan pengguna
- `POST /api/v1/chats` - Buat atau temukan ruang percakapan dengan pengguna lain
- `GET /api/v1/chats/{id}/messages` - Riwayat pesan dalam percakapan
- `POST /api/v1/chats/{id}/messages` - Kirim pesan teks & berkas lampiran
- `PUT /api/v1/chats/{id}/read` - Tandai pesan sebagai telah dibaca

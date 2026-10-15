# 🤖 Panduan Integrasi Backend Laravel GigSync untuk Agent Android (Kotlin Compose)
> **Dokumen ini ditujukan langsung untuk AI Agent / Developer yang mengerjakan aplikasi Android `FreelanceOS` (Kotlin Native / Jetpack Compose).**
> Semua endpoint, kontrak JSON, data models, dan petunjuk konfigurasi jaringan di bawah ini sudah aktif 100% dan teruji di backend Laravel 11.

---

## 1. Konfigurasi Jaringan & Base URL

Backend berjalan di peladen lokal Laravel (`php artisan serve --host=0.0.0.0 --port=8000`).

| Lingkungan Pengujian | Base URL Retrofit |
| :--- | :--- |
| **Android Studio Emulator** | `http://10.0.2.2:8000/api/v1/` |
| **HP Android Fisik (Satu Wi-Fi)** | `http://192.168.18.102:8000/api/v1/` |

### ⚠️ Wajib di `AndroidManifest.xml` (Modul `:app`)
Karena pengujian lokal menggunakan HTTP (bukan HTTPS), Android 9+ secara default memblokir lalu lintas *cleartext*. Wajib tambahkan konfigurasi ini:

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <uses-permission android:name="android.permission.INTERNET" />

    <application
        android:usesCleartextTraffic="true"
        ...>
    </application>
</manifest>
```

---

## 2. Standar Respons JSON & Serialization

### 2.1 Amplop Standar (`ApiResponse<T>`)
Semua endpoint membungkus respons dengan format:
```json
{
  "success": true,
  "message": "Operasi berhasil dieksekusi",
  "data": { ... } // atau null jika error/delete
}
```

Model Kotlin generik:
```kotlin
@Serializable
data class ApiResponse<T>(
    val success: Boolean,
    val message: String,
    val data: T? = null,
    val errors: Map<String, List<String>>? = null
)
```

### 2.2 Aturan Penamaan: Wajib `camelCase`!
- Backend **mentransformasikan seluruh field database menjadi `camelCase`** (`clientId`, `budgetAmount`, `locationName`, `passwordConfirmation`, `proposalsCount`, dll.).
- Backend juga **menerima payload request baik format `camelCase` maupun `snake_case`**.
- Anotasi `@SerialName` **tidak wajib** jika nama variabel Kotlin kamu sudah berformat `camelCase`.
- **Rekomendasi Json parser:** Setel `ignoreUnknownKeys = true` pada konfigurasi Kotlinx Serialization.

---

## 3. Akun Demo Database (Siap Digunakan)

Gunakan akun ini untuk pengujian instan tanpa perlu mendaftar ulang:

| Role | Email | Password | Saldo Awal | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **CLIENT** | `client@gigsync.com` | `Password123!` | Rp 20.000.000 | Punya lowongan dummy di Telkom University (TULT) |
| **FREELANCER** | `freelancer@gigsync.com` | `Password123!` | Rp 1.500.000 | Siap melamar lowongan dan mengajukan proposal |

---

## 4. Spesifikasi Lengkap Kontrak REST API v1

Semua request yang membutuhkan autentikasi wajib menyertakan header:
`Authorization: Bearer <token>` dan `Accept: application/json`.

---

### 4.1 Modul Autentikasi & Akun (`/auth` & `/users`)

#### 1. Register Akun Baru
- **Endpoint:** `POST /api/v1/auth/register`
- **Request Body:**
  ```json
  {
    "name": "Nama Lengkap",
    "email": "user@telkomuniversity.ac.id",
    "password": "Password123!",
    "passwordConfirmation": "Password123!",
    "activeRole": "FREELANCER" // atau "CLIENT"
  }
  ```
- **Response 201:** `ApiResponse<AuthData>`

#### 2. Login Akun
- **Endpoint:** `POST /api/v1/auth/login`
- **Request Body:**
  ```json
  {
    "email": "freelancer@gigsync.com",
    "password": "Password123!"
  }
  ```
- **Response 200:**
  ```json
  {
    "success": true,
    "message": "Login akun berhasil",
    "data": {
      "token": "1|xyz123...",
      "user": {
        "id": 1,
        "googleId": null,
        "name": "Zhafran Mufadhdhol",
        "email": "freelancer@gigsync.com",
        "activeRole": "FREELANCER",
        "headline": "Senior Native Android Engineer",
        "bio": "Mahasiswa D3 RPL Aplikasi...",
        "hourlyRate": 175000.0,
        "avatarUrl": "https://...",
        "walletBalance": 1500000.0,
        "createdAt": "2026-10-10T14:00:00Z"
      }
    }
  }
  ```

#### 3. Google Sign-In Exchange (Credential Manager)
- **Endpoint:** `POST /api/v1/auth/google`
- **Request Body:**
  ```json
  {
    "idToken": "eyJhbGciOiJSUzI1NiIs..."
  }
  ```
- **Response 200:** Sama persis dengan respons login/register.

#### 4. Ganti Peran Aktif (Dual-Role Toggle)
- **Endpoint:** `PUT /api/v1/users/active-role` *(Auth Bearer Token)*
- **Request Body:**
  ```json
  {
    "activeRole": "CLIENT" // atau "FREELANCER"
  }
  ```
- **Response 200:** `ApiResponse<UserDto>`

#### 5. Ambil Profil Sendiri
- **Endpoint:** `GET /api/v1/auth/me` *(Auth Bearer Token)*
- **Response 200:** `ApiResponse<UserDto>`

---

### 4.2 Modul Pekerjaan / Lowongan (`/jobs`)

#### 1. Jelajah Lowongan (Feed SCR-06)
- **Endpoint:** `GET /api/v1/jobs?search={keyword}&category_id={id}`
- **Response 200:**
  ```json
  {
    "success": true,
    "message": "Daftar lowongan pekerjaan berhasil diambil",
    "data": [
      {
        "id": 1,
        "clientId": 1,
        "clientName": "PT Telkom Digital Solusindo",
        "clientAvatarUrl": "https://...",
        "title": "Pengembangan Fitur Peta Android GigSync",
        "description": "Membangun integrasi Google Maps/OSM...",
        "categoryId": 1,
        "categoryName": "Mobile Development",
        "budgetType": "FIXED", // "FIXED" atau "HOURLY"
        "budgetAmount": 4500000.0,
        "deadline": "2026-10-30",
        "latitude": -6.9740279,
        "longitude": 107.6303481,
        "locationName": "Telkom University Landmark Tower (TULT)",
        "skills": ["Android", "Kotlin", "Jetpack Compose"],
        "status": "OPEN",
        "proposalsCount": 0,
        "createdAt": "2026-10-10T16:53:35.000000Z"
      }
    ]
  }
  ```

#### 2. Detail Lowongan (SCR-08)
- **Endpoint:** `GET /api/v1/jobs/{id}`
- **Response 200:** `ApiResponse<JobDto>`

#### 3. Pasang Lowongan Baru (Klien SCR-13 s/d SCR-15)
- **Endpoint:** `POST /api/v1/jobs` *(Auth Bearer Token)*
- **Request Body:**
  ```json
  {
    "title": "Aplikasi Presensi GPS Kampus",
    "description": "Dibutuhkan developer Android Compose...",
    "categoryId": 1,
    "budgetType": "FIXED",
    "budgetAmount": 3000000.0,
    "deadline": "2026-11-20",
    "latitude": -6.9740279,
    "longitude": 107.6303481,
    "locationName": "Gedung Selaru, Telkom University",
    "skills": ["Android", "Kotlin", "Room"]
  }
  ```
- **Response 201:** `ApiResponse<JobDto>`

#### 4. Lowongan Milik Klien Login (SCR-12)
- **Endpoint:** `GET /api/v1/client/jobs` *(Auth Bearer Token)*
- **Response 200:** `ApiResponse<List<JobDto>>`

---

### 4.3 Modul Proposal & Pelamar (`/proposals`)

#### 1. Freelancer Kirim Proposal (SCR-09)
- **Endpoint:** `POST /api/v1/jobs/{jobId}/proposals` *(Auth Bearer Token, Multipart)*
- **Form-Data:**
  - `bidAmount`: `3000000` (Number)
  - `estimatedDays`: `14` (Int)
  - `coverLetter`: `Teks penawaran...` (String)
  - `attachment`: *[Optional file binary: .jpg, .png, .pdf maks 5MB]*
- **Response 201:**
  ```json
  {
    "success": true,
    "message": "Proposal penawaran berhasil diajukan",
    "data": {
      "id": 10,
      "jobId": 1,
      "jobTitle": "Pengembangan Fitur Peta Android GigSync",
      "freelancerId": 2,
      "freelancerName": "Zhafran Mufadhdhol",
      "freelancerAvatarUrl": "https://...",
      "freelancerHeadline": "Senior Native Android Engineer",
      "bidAmount": 3000000.0,
      "estimatedDays": 14,
      "coverLetter": "Saya siap menyelesaikan sesuai rubrik...",
      "attachmentUrl": "http://10.0.2.2:8000/storage/proposals/proposal_1_2_17289.pdf",
      "status": "PENDING",
      "createdAt": "2026-10-15T15:00:00Z"
    }
  }
  ```

#### 2. Klien Melihat Pelamar pada Lowongannya (SCR-16)
- **Endpoint:** `GET /api/v1/jobs/{jobId}/proposals` *(Auth Bearer Token)*
- **Response 200:** `ApiResponse<List<ProposalDto>>`

#### 3. Proposal Saya (Freelancer)
- **Endpoint:** `GET /api/v1/proposals/my` *(Auth Bearer Token)*
- **Response 200:** `ApiResponse<List<ProposalDto>>`

#### 4. Klien Terima / Tolak Proposal (SCR-17 & Escrow Lock)
- **Endpoint:** `PUT /api/v1/proposals/{id}/status` *(Auth Bearer Token)*
- **Request Body:**
  ```json
  {
    "status": "ACCEPTED" // atau "REJECTED"
  }
  ```
- **Logika Otomatis Backend saat `ACCEPTED`:**
  1. Backend memverifikasi apakah saldo dompet klien mencukupi `bidAmount`.
  2. Saldo klien otomatis dipotong dan dikunci ke rekening bersama (`escrow_status: HELD`).
  3. Dibuatkan objek `Contract` baru berstatus `ACTIVE`.
  4. Status job otomatis berubah menjadi `IN_PROGRESS`.
- **Response 200:**
  ```json
  {
    "success": true,
    "message": "Proposal berhasil diterima dan dana escrow telah dikunci.",
    "data": {
      "proposal": { "id": 10, "status": "ACCEPTED", ... },
      "contract": {
        "id": 5,
        "jobId": 1,
        "agreedAmount": 3000000.0,
        "escrowStatus": "HELD",
        "contractStatus": "ACTIVE",
        "createdAt": "2026-10-15T15:30:00Z"
      }
    }
  }
  ```

---

### 4.4 Modul Kontrak & Rekening Bersama (Escrow `/contracts`)

#### 1. Detail Kontrak (SCR-20)
- **Endpoint:** `GET /api/v1/contracts/{id}` *(Auth Bearer Token)*
- **Response 200:** `ApiResponse<ContractDto>`

#### 2. Freelancer Menyerahkan Hasil Kerja (SCR-21)
- **Endpoint:** `POST /api/v1/contracts/{id}/submit` *(Auth Bearer Token)*
- **Request Body:**
  ```json
  {
    "submissionNotes": "Pekerjaan telah rampung, tautan repositori GitHub terlampir.",
    "submissionUrl": "https://github.com/d3if/tubes-gigsync"
  }
  ```
- **Response 200:** Mengubah `contractStatus` menjadi `UNDER_REVIEW`.

#### 3. Klien Menyetujui Kerja & Rilis Saldo Escrow (SCR-22)
- **Endpoint:** `POST /api/v1/contracts/{id}/approve` *(Auth Bearer Token)*
- **Logika Otomatis Backend:**
  - `contractStatus` $\rightarrow$ `COMPLETED`
  - `escrowStatus` $\rightarrow$ `RELEASED`
  - Saldo `agreedAmount` dikurangi fee platform (10%) langsung ditransfer ke `wallet_balance` freelancer.
- **Response 200:** `ApiResponse<ContractDto>`

#### 4. Klien Meminta Revisi Hasil Kerja (SCR-22)
- **Endpoint:** `POST /api/v1/contracts/{id}/revise` *(Auth Bearer Token)*
- **Request Body:**
  ```json
  {
    "revisionNotes": "Mohon perbaiki tampilan UI pada resolusi tablet."
  }
  ```
- **Response 200:** Mengubah `contractStatus` menjadi `REVISION`.

---

### 4.5 Modul Ulasan & Rating (`/reviews`)
- **Endpoint:** `POST /api/v1/contracts/{id}/reviews` *(Auth Bearer Token)*
- **Request Body:**
  ```json
  {
    "rating": 5, // 1 sampai 5
    "comment": "Pekerjaan sangat rapi dan komunikatif!"
  }
  ```
- **Response 201:** `ApiResponse<ReviewDto>`

---

### 4.6 Modul Dompet (Wallet `/wallet`)
- **Cek Saldo & Mutasi:** `GET /api/v1/wallet` *(Auth Bearer Token)*
- **Mock Top-up Saldo:** `POST /api/v1/wallet/topup`
  ```json
  { "amount": 2000000 }
  ```

---

## 5. Salin-Tempel Lengkap Kode Kotlin Android

Agent Android dapat langsung menyalin file-file ini ke dalam arsitektur modul Android:

### File 1: `:core:model` $\rightarrow$ `AuthModels.kt`
```kotlin
package com.d3ifcool.freelanceos.core.model

import kotlinx.serialization.Serializable

@Serializable
data class ApiResponse<T>(
    val success: Boolean,
    val message: String,
    val data: T? = null,
    val errors: Map<String, List<String>>? = null
)

@Serializable
data class LoginRequest(
    val email: String,
    val password: String
)

@Serializable
data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String,
    val passwordConfirmation: String,
    val activeRole: String = "FREELANCER"
)

@Serializable
data class UserDto(
    val id: Long,
    val googleId: String? = null,
    val name: String,
    val email: String,
    val activeRole: String,
    val headline: String? = null,
    val bio: String? = null,
    val hourlyRate: Double = 0.0,
    val avatarUrl: String? = null,
    val walletBalance: Double = 0.0,
    val createdAt: String? = null
)

@Serializable
data class AuthData(
    val token: String,
    val user: UserDto
)

@Serializable
data class JobDto(
    val id: Long,
    val clientId: Long,
    val clientName: String? = null,
    val clientAvatarUrl: String? = null,
    val title: String,
    val description: String,
    val categoryId: Int,
    val categoryName: String? = null,
    val budgetType: String,
    val budgetAmount: Double,
    val deadline: String,
    val latitude: Double? = null,
    val longitude: Double? = null,
    val locationName: String? = null,
    val skills: List<String> = emptyList(),
    val status: String,
    val proposalsCount: Int = 0,
    val createdAt: String? = null
)

@Serializable
data class ProposalDto(
    val id: Long,
    val jobId: Long,
    val jobTitle: String? = null,
    val freelancerId: Long,
    val freelancerName: String? = null,
    val freelancerAvatarUrl: String? = null,
    val freelancerHeadline: String? = null,
    val bidAmount: Double,
    val estimatedDays: Int,
    val coverLetter: String,
    val attachmentUrl: String? = null,
    val status: String,
    val createdAt: String? = null
)

@Serializable
data class ContractDto(
    val id: Long,
    val jobId: Long,
    val jobTitle: String? = null,
    val clientId: Long,
    val clientName: String? = null,
    val freelancerId: Long,
    val freelancerName: String? = null,
    val agreedAmount: Double,
    val escrowStatus: String, // HELD, RELEASED, REFUNDED
    val contractStatus: String, // ACTIVE, UNDER_REVIEW, REVISION, COMPLETED, CANCELLED
    val submissionNotes: String? = null,
    val submissionUrl: String? = null,
    val createdAt: String? = null
)
```

---

### File 2: `:core:network` $\rightarrow$ `ApiService.kt`
```kotlin
package com.d3ifcool.freelanceos.core.network

import com.d3ifcool.freelanceos.core.model.*
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.*

interface ApiService {
    // Auth
    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): ApiResponse<AuthData>

    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): ApiResponse<AuthData>

    @GET("auth/me")
    suspend fun getProfile(): ApiResponse<UserDto>

    @PUT("users/active-role")
    suspend fun switchRole(@Body body: Map<String, String>): ApiResponse<UserDto>

    // Jobs
    @GET("jobs")
    suspend fun getJobs(
        @Query("search") search: String? = null,
        @Query("category_id") categoryId: Int? = null
    ): ApiResponse<List<JobDto>>

    @GET("jobs/{id}")
    suspend fun getJobDetail(@Path("id") id: Long): ApiResponse<JobDto>

    @POST("jobs")
    suspend fun createJob(@Body job: Map<String, @JvmSuppressWildcards Any>): ApiResponse<JobDto>

    // Proposals
    @Multipart
    @POST("jobs/{jobId}/proposals")
    suspend fun submitProposal(
        @Path("jobId") jobId: Long,
        @Part("bidAmount") bidAmount: RequestBody,
        @Part("estimatedDays") estimatedDays: RequestBody,
        @Part("coverLetter") coverLetter: RequestBody,
        @Part attachment: MultipartBody.Part? = null
    ): ApiResponse<ProposalDto>

    @GET("jobs/{jobId}/proposals")
    suspend fun getJobProposals(@Path("jobId") jobId: Long): ApiResponse<List<ProposalDto>>

    @GET("proposals/my")
    suspend fun getMyProposals(): ApiResponse<List<ProposalDto>>

    @PUT("proposals/{id}/status")
    suspend fun updateProposalStatus(
        @Path("id") id: Long,
        @Body body: Map<String, String> // {"status": "ACCEPTED"}
    ): ApiResponse<Map<String, @JvmSuppressWildcards Any>>

    // Contracts & Escrow
    @GET("contracts/{id}")
    suspend fun getContractDetail(@Path("id") id: Long): ApiResponse<ContractDto>

    @POST("contracts/{id}/submit")
    suspend fun submitDeliverable(
        @Path("id") id: Long,
        @Body body: Map<String, String> // {"submissionNotes": "...", "submissionUrl": "..."}
    ): ApiResponse<ContractDto>

    @POST("contracts/{id}/approve")
    suspend fun approveContract(@Path("id") id: Long): ApiResponse<ContractDto>

    @POST("contracts/{id}/revise")
    suspend fun requestRevision(
        @Path("id") id: Long,
        @Body body: Map<String, String> // {"revisionNotes": "..."}
    ): ApiResponse<ContractDto>
}
```

---

### File 3: `:core:network` $\rightarrow$ `RetrofitClient.kt`
```kotlin
package com.d3ifcool.freelanceos.core.network

import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory

object RetrofitClient {
    // Ganti ke "http://192.168.18.102:8000/api/v1/" jika menggunakan HP Fisik asli
    private const val BASE_URL = "http://10.0.2.2:8000/api/v1/"

    fun create(tokenProvider: () -> String?): ApiService {
        val okHttpClient = OkHttpClient.Builder()
            .addInterceptor { chain ->
                val reqBuilder = chain.request().newBuilder()
                    .addHeader("Accept", "application/json")
                tokenProvider()?.let { token ->
                    reqBuilder.addHeader("Authorization", "Bearer $token")
                }
                chain.proceed(reqBuilder.build())
            }
            .addInterceptor(HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.BODY
            })
            .build()

        val json = Json {
            ignoreUnknownKeys = true
            coerceInputValues = true
        }

        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()
            .create(ApiService::class.java)
    }
}
```

---

## 6. Verifikasi & Debugging Langsung

Jika Agent Android ingin memeriksa apakah server backend menyala dan database aktif, buka alamat ini di browser komputer / HP:
- **Status API & Endpoint:** `http://192.168.18.102:8000/`
- **Halaman Uji Interaktif (Register & Login Live):** `http://192.168.18.102:8000/test`

# PANDUAN LENGKAP IMPLEMENTASI ANDROID STUDIO (JETPACK COMPOSE / XML)
## Sistem Informasi Inventaris Barang (Modern Slate & Indigo)

Dokumen ini disusun sebagai **spesifikasi teknis presisi (pixel-perfect & API-perfect)** untuk dieksekusi langsung oleh **Gemini di Android Studio**. Seluruh kode warna, tipografi, struktur komponen, model data Kotlin, interface Retrofit, dan pemetaan API telah diselaraskan 100% dengan backend Laravel `inventaris-api` dan template desain di folder `/home/abu/Downloads/stitch_ui_layout_refinement`.

---

## 1. Master Design System: Modern Slate & Indigo

### 1.1 Color Palette (Material 3 Tokens)
Gunakan kode warna hex ini pada `ui/theme/Color.kt` atau `colors.xml`:

```kotlin
// Primary (Indigo)
val Primary = Color(0xFF3525CD)
val OnPrimary = Color(0xFFFFFFFF)
val PrimaryContainer = Color(0xFF4F46E5)
val OnPrimaryContainer = Color(0xFFDAD7FF)
val PrimaryFixed = Color(0xFFE2DFFF)
val OnPrimaryFixed = Color(0xFF0F0069)

// Secondary (Emerald / Success)
val Secondary = Color(0xFF006C49)
val OnSecondary = Color(0xFFFFFFFF)
val SecondaryContainer = Color(0xFF6CF8BB)
val OnSecondaryContainer = Color(0xFF00714D)

// Tertiary (Warm Amber / Pending)
val Tertiary = Color(0xFFD97706)
val OnTertiary = Color(0xFFFFFFFF)
val TertiaryFixed = Color(0xFFFFDDB8)
val OnTertiaryFixed = Color(0xFF2A1700)

// Destructive / Error
val Error = Color(0xFFBA1A1A)
val OnError = Color(0xFFFFFFFF)
val ErrorContainer = Color(0xFFFFDAD6)
val OnErrorContainer = Color(0xFF93000A)

// Surfaces & Background (Cool Slate)
val Background = Color(0xFFF8F9FF)
val OnBackground = Color(0xFF0B1C30)
val Surface = Color(0xFFF8F9FF)
val OnSurface = Color(0xFF0B1C30)
val SurfaceContainerLowest = Color(0xFFFFFFFF)
val SurfaceContainerLow = Color(0xFFEFF4FF)
val SurfaceContainer = Color(0xFFE5EEFF)
val SurfaceContainerHigh = Color(0xFFDCE9FF)
val SurfaceContainerHighest = Color(0xFFD3E4FE)
val OnSurfaceVariant = Color(0xFF464555)
val Outline = Color(0xFF777587)
val OutlineVariant = Color(0xFFC7C4D8)
```

### 1.2 Tipografi & Font Family
- **Headlines, Judul Kartu, Header**: `Plus Jakarta Sans` (Bold 700 / SemiBold 600)
- **Body, Form Input, Transaksi, Angka**: `Inter` (Regular 400 / Medium 500 / SemiBold 600)

### 1.3 Shapes & Elevation
- **Cards & Modals**: Rounded Corner `16.dp` (`RoundedCornerShape(16.dp)` / `rounded-xl`)
- **Form Inputs & Buttons**: Rounded Corner `12.dp` (`RoundedCornerShape(12.dp)` / `rounded-lg`)
- **Status Pills, Badges & Floating Action Button (FAB)**: Full Pill `9999.dp` (`CircleShape`)
- **Card Shadow**: Ambient Soft Blur (`elevation = 2.dp` s/d `4.dp`)
- **FAB Shadow**: Elevated Indigo Tint (`elevation = 8.dp`)

---

## 2. Arsitektur & Networking Android

### 2.1 Dependencies (`build.gradle.kts` / `app`)
```kotlin
dependencies {
    // Retrofit & Moshi / Gson
    implementation("com.squareup.retrofit2:retrofit:2.11.0")
    implementation("com.squareup.retrofit2:converter-gson:2.11.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

    // Coroutines & Lifecycle ViewModel
    implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.8.7")
    implementation("androidx.lifecycle:lifecycle-runtime-compose:2.8.7")

    // Image Loading (Coil)
    implementation("io.coil-kt:coil-compose:2.7.0")

    // Material Icons Extended
    implementation("androidx.compose.material:material-icons-extended:1.7.5")

    // QR & Barcode Scanning (CameraX / ML Kit)
    implementation("com.google.mlkit:barcode-scanning:17.3.0")
    implementation("androidx.camera:camera-camera2:1.4.0")
    implementation("androidx.camera:camera-lifecycle:1.4.0")
    implementation("androidx.camera:camera-view:1.4.0")
}
```

### 2.2 Konfigurasi Base URL & Token Interceptor
- **Emulator Android**: `http://10.0.2.2:8000/api/`
- **Device Fisik (via USB / Wi-Fi)**: `http://192.168.x.x:8000/api/` (atau gunakan `adb reverse tcp:8000 tcp:8000` lalu akses `http://localhost:8000/api/`)

```kotlin
class AuthInterceptor(private val tokenProvider: () -> String?) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val requestBuilder = chain.request().newBuilder()
            .addHeader("Accept", "application/json")
            .addHeader("Content-Type", "application/json")

        tokenProvider()?.let { token ->
            requestBuilder.addHeader("Authorization", "Bearer $token")
        }

        return chain.proceed(requestBuilder.build())
    }
}
```

---

## 3. Kotlin Data Models

```kotlin
// Standar Generic Response
data class ApiResponse<T>(
    val status: Boolean,
    val message: String,
    val data: T?,
    val meta: Map<String, Any>? = null
)

// Autentikasi & User
data class User(
    val id: Int,
    val name: String,
    val username: String,
    val email: String?,
    val role: String, // "admin" atau "user"
    val divisi: String,
    val status_aktif: Boolean = true,
    val avatar: String?,
    val total_approval: Int? = 0,
    val total_peminjaman: Int? = 0,
    val peminjamans_count: Int? = 0
)

data class LoginResponse(
    val user: User,
    val token: String,
    val token_type: String
)

// Kategori Barang
data class Kategori(
    val id: Int,
    val kode_kategori: String,
    val nama_kategori: String,
    val deskripsi: String?,
    val icon: String?,
    val barangs_count: Int = 0
)

// Barang Inventaris
data class Barang(
    val id: Int,
    val kategori_id: Int,
    val kode_barang: String,
    val nama_barang: String,
    val stok: Int,
    val kondisi: String, // "Baik", "Rusak Ringan", "Rusak Berat"
    val deskripsi: String?,
    val lokasi: String?,
    val gambar: String?,
    val status_ketersediaan: String, // "Tersedia" atau "Habis"
    val qr_code_url: String?,
    val kategori: Kategori?
)

// Transaksi Peminjaman
data class Peminjaman(
    val id: Int,
    val kode_peminjaman: String,
    val user_id: Int,
    val barang_id: Int,
    val jumlah: Int,
    val tanggal_pinjam: String,
    val tanggal_kembali: String?,
    val status: String, // "Menunggu", "Disetujui", "Ditolak", "Dikembalikan"
    val sisa_hari: Int?,
    val status_tempo: String?,
    val qr_code_url: String?,
    val keterangan: String?,
    val user: User?,
    val barang: Barang?
)

// Data Dashboard
data class DashboardData(
    val total_jenis_barang: Int,
    val total_stok_barang: Int,
    val total_kategori: Int,
    val peminjaman_menunggu: Int,
    val peminjaman_aktif: Int,
    val peminjaman_terbaru: List<Peminjaman>
)

// Hasil Scan Cerdas
data class ScanResult(
    val type: String, // "barang" atau "peminjaman"
    val item: Any // Barang atau Peminjaman
)

// Notifikasi Lonceng
data class NotifikasiItem(
    val id: String,
    val tipe: String,
    val judul: String,
    val pesan: String,
    val kode_transaksi: String?,
    val peminjaman_id: Int?,
    val status: String?,
    val waktu: String,
    val is_read: Boolean
)
```

---

## 4. Retrofit API Service Interface

```kotlin
interface ApiService {
    // 1. Auth & Profil
    @POST("register")
    suspend fun register(@Body request: Map<String, String>): ApiResponse<User>

    @POST("login")
    suspend fun login(@Body request: Map<String, String>): ApiResponse<LoginResponse>

    @GET("profile")
    suspend fun getProfile(): ApiResponse<User>

    @PUT("profile")
    suspend fun updateProfile(@Body body: Map<String, Any>): ApiResponse<User>

    @POST("change-password")
    suspend fun changePassword(@Body body: Map<String, String>): ApiResponse<Unit>

    @POST("logout")
    suspend fun logout(): ApiResponse<Unit>

    // 2. Beranda & Dashboard
    @GET("dashboard")
    suspend fun getDashboard(): ApiResponse<DashboardData>

    @GET("notifikasi")
    suspend fun getNotifikasi(): ApiResponse<List<NotifikasiItem>>

    // 3. Scan Barcode / QR Cerdas
    @GET("scan")
    suspend fun scanCode(@Query("code") code: String): ApiResponse<Map<String, Any>>

    // 4. Kategori Inventaris
    @GET("kategori")
    suspend fun getKategori(@Query("search") search: String? = null): ApiResponse<List<Kategori>>

    @POST("kategori")
    suspend fun createKategori(@Body body: Map<String, Any>): ApiResponse<Kategori>

    @PUT("kategori/{id}")
    suspend fun updateKategori(@Path("id") id: Int, @Body body: Map<String, Any>): ApiResponse<Kategori>

    @DELETE("kategori/{id}")
    suspend fun deleteKategori(@Path("id") id: Int): ApiResponse<Unit>

    // 5. Barang Inventaris
    @GET("barang")
    suspend fun getBarang(
        @Query("search") search: String? = null,
        @Query("kategori_id") kategoriId: Int? = null,
        @Query("kondisi") kondisi: String? = null
    ): ApiResponse<List<Barang>>

    @GET("barang/{id}")
    suspend fun getDetailBarang(@Path("id") id: Int): ApiResponse<Barang>

    @POST("barang")
    suspend fun createBarang(@Body body: Map<String, Any?>): ApiResponse<Barang>

    @PUT("barang/{id}")
    suspend fun updateBarang(@Path("id") id: Int, @Body body: Map<String, Any?>): ApiResponse<Barang>

    @DELETE("barang/{id}")
    suspend fun deleteBarang(@Path("id") id: Int): ApiResponse<Unit>

    // 6. Peminjaman
    @GET("peminjaman")
    suspend fun getPeminjaman(
        @Query("search") search: String? = null,
        @Query("status") status: String? = null,
        @Query("user_id") userId: Int? = null
    ): ApiResponse<List<Peminjaman>>

    @POST("peminjaman")
    suspend fun createPeminjaman(@Body body: Map<String, Any?>): ApiResponse<Peminjaman>

    @PUT("peminjaman/{id}")
    suspend fun updateStatusPeminjaman(
        @Path("id") id: Int,
        @Body body: Map<String, String> // {"status": "Disetujui" / "Ditolak" / "Dikembalikan"}
    ): ApiResponse<Peminjaman>

    @DELETE("peminjaman/{id}")
    suspend fun deletePeminjaman(@Path("id") id: Int): ApiResponse<Unit>

    // 7. Pengguna (Admin)
    @GET("users")
    suspend fun getUsers(
        @Query("search") search: String? = null,
        @Query("role") role: String? = null
    ): ApiResponse<List<User>>

    @POST("users")
    suspend fun createUser(@Body body: Map<String, Any?>): ApiResponse<User>

    @PUT("users/{id}")
    suspend fun updateUser(@Path("id") id: Int, @Body body: Map<String, Any?>): ApiResponse<User>

    @DELETE("users/{id}")
    suspend fun deleteUser(@Path("id") id: Int): ApiResponse<Unit>
}
```

---

## 5. Panduan Layar per Layar (11 Screen Guide)

### Layar 1: Beranda Inventaris (`fix_beranda_inventaris/code.html`)
- **Komponen**:
  1. *Header bar*: Logo inventaris, judul `"Beranda"`, ikon Lonceng (`notifications`) dengan badge dot merah, avatar user.
  2. *Welcome Card*: Greeting `"Halo, Administrator!"`, badge status hub `"Aktif"`.
  3. *Ringkasan Statistik (Grid 2x2 Bento)*:
     - Total Stok: `data.total_stok_barang` (Unit)
     - Kategori Aset: `data.total_kategori` (Grup)
     - Sedang Dipinjam: `data.peminjaman_aktif` (Item)
     - Perlu Approval: `data.peminjaman_menunggu` (Tiket)
  4. *Katalog 8 Modul Cepat*: Grid 4 kolom (Data Barang, Peminjaman, Kategori, Pengguna, Pindai QR, Kondisi Fisik, Log Mutasi, Tambah Aset).
  5. *Peminjaman Terbaru*: Kartu transaksi peminjaman antrean menunggu dengan tombol aksi langsung: `[Tolak]` (`PUT status: Ditolak`) dan `[Setujui]` (`PUT status: Disetujui`).
  6. *Bottom Navigation*: 5 Tab (Beranda, Barang, FAB Pindai QR di tengah, Pinjam, Profil).

### Layar 2: Daftar Barang (`fix_data_barang/code.html`)
- **Komponen**:
  1. *Header bar*: Judul `"Daftar Barang"`, ikon lonceng & avatar.
  2. *Metric Pill*: Total Unit Terdaftar & Kategori Aktif (diambil dari `meta`).
  3. *Search Input & Filter Tune*: Mencari nama/SKU secara realtime (`?search=`).
  4. *Filter Chips Horizontal Scroll*: Semua, Jaringan, Multimedia, Komputer, Acara (mengirim `?kategori_id=`).
  5. *Inventory Card Stream*: Menampilkan foto barang, SKU badge, badge kondisi (`Baik`), nama barang, lokasi rak (`lokasi`), stok unit (`Tersedia`), tombol *Edit* & *Hapus*.
  6. *Floating Action Button*: `"Tambah Barang"` $\rightarrow$ membuka Layar 3.

### Layar 3: Form Tambah Barang (`fix_tambah_barang_baru/code.html`)
- **Header**: Tombol kembali $\leftarrow$, Judul `"Tambah Barang Baru"`.
- **Form Fields**:
  - `kode_barang`: Input teks dengan tombol `[Scan]` untuk auto-fill dari barcode fisik.
  - `nama_barang`: Input nama alat.
  - `kategori_id`: Dropdown pilihan kategori.
  - `stok`: Stepper angka $(- / +)$.
  - `lokasi`: Input lokasi rak/gudang penyimpanan.
  - `kondisi`: Radio pill Material: `Baik`, `Rusak Ringan`, `Rusak Berat`.
  - `gambar`: Input URL gambar atau picker galeri/kamera.
  - `deskripsi`: Textarea keterangan unit.
  - Tombol submit: `Simpan Barang Baru` $\rightarrow$ kirim `POST /api/barang`.

### Layar 4: Kategori Barang (`fix_kategori_barang/code.html`)
- **Header**: Judul `"Kategori Barang"`.
- **Bento Metric**: Kategori Aktif & Total Aset Terverifikasi.
- **Search & Chips**: Pencarian kategori.
- **Category Card**: Ikon kategori, Kode Kategori (`KTG-001`), status aktif, nama kategori, badge total unit (`barangs_count`), tombol *Edit* dan *Hapus*.
- **Modal / FAB Tambah**: Tombol `+ Kategori` membuka form input `kode_kategori`, `nama_kategori`, `deskripsi`, `icon`.

### Layar 5: Form Tambah Kategori (`fix_tambah_kategori_baru/code.html`)
- **Header**: Tombol kembali $\leftarrow$, Judul `"Tambah Kategori Baru"`.
- **Form Fields**:
  - `kode_kategori`: Input teks + tombol generate otomatis (`KTG-xxx`).
  - `nama_kategori`: Input nama kategori.
  - `icon`: Grid 6 icon selector (`router`, `photo_camera`, `headphones`, `computer`, `construction`, `shelves`).
  - `deskripsi`: Catatan/spesifikasi kategori.
  - Live Preview Card: Menampilkan pratinjau kartu kategori secara realtime.
  - Tombol submit: `Simpan Kategori Baru` $\rightarrow$ kirim `POST /api/kategori`.

### Layar 6: Data Peminjaman (`fix_data_peminjaman/code.html`)
- **Header**: Judul `"Peminjaman"`.
- **Ringkasan Bento Strip**: Menunggu (1), Dipinjam (2), Kembali (2).
- **Search & Tabs**: `Semua`, `Menunggu`, `Disetujui`, `Dikembalikan`.
- **Kartu Transaksi**:
  - Kode transaksi (`TRX-58219`), status badge warna semantik (Kuning = Menunggu, Hijau = Disetujui, Abu = Dikembalikan).
  - Foto alat, nama alat, jumlah unit.
  - Identitas peminjam (avatar, nama, divisi).
  - Durasi tanggal pinjam s/d batas pengembalian, status jatuh tempo (`Sisa 3 Hari`, `Jatuh Tempo Besok`).
  - Keperluan pinjam.
  - Tombol aksi adaptif:
    - Jika *Menunggu*: `[Tolak]` & `[Setujui Peminjaman]`.
    - Jika *Disetujui*: `[Tiket QR]` & `[Konfirmasi Pengembalian]`.
    - Jika *Dikembalikan*: `[Detail Riwayat]`.
  - FAB: `"Ajukan Pinjam"` $\rightarrow$ membuka Layar 7.

### Layar 7: Form Ajukan Peminjaman (`fix_ajukan_peminjaman/code.html`)
- **Header**: Tombol kembali $\leftarrow$, Judul `"Ajukan Peminjaman"`.
- **Form Fields**:
  - `barang_id`: Dropdown inventaris dengan kartu pratinjau barang terpilih (foto, kode, stok tersedia).
  - `jumlah`: Stepper kuantitas yang dibatasi secara otomatis tidak boleh melebihi stok barang.
  - Peminjam: Terisi otomatis dari profil login (`name` & `divisi`).
  - `tanggal_pinjam`: Date picker (default hari ini).
  - `tanggal_kembali`: Date picker (maksimal 7 hari).
  - `keterangan`: Textarea keperluan kegiatan.
  - Checkbox terms tanggung jawab.
  - Tombol submit: `Ajukan Peminjaman Sekarang` $\rightarrow$ kirim `POST /api/peminjaman`.

### Layar 8: Kelola Pengguna (`fix_kelola_pengguna/code.html`)
- **Header**: Judul `"Kelola Pengguna"`.
- **Bento Stats**: Total Aktif (6), Admin (1), Anggota (5).
- **Search & Filter**: Pencarian nama/email/divisi & Filter chip (`Semua`, `Admin`, `Anggota`).
- **User Cards**: Avatar, nama, email terverifikasi, role badge (`Super Admin` / `Anggota`), divisi, counter barang dipinjam (`peminjamans_count`), tombol *Edit*, *Reset Password*, dan *Hapus*.
- **FAB**: `"Tambah User"` $\rightarrow$ membuka Layar 9.

### Layar 9: Form Tambah Pengguna Baru (`fix_tambah_pengguna_baru/code.html`)
- **Header**: Tombol kembali $\leftarrow$, Judul `"Tambah Pengguna Baru"`.
- **Form Fields**:
  - `name`: Nama lengkap.
  - `username`: Username sistem.
  - `email`: Email resmi internal.
  - `divisi`: Dropdown divisi penugasan.
  - `role`: Pilihan radio card: `Anggota / Staf` (`value="user"`) atau `Super Admin` (`value="admin"`).
  - `password`: Input kata sandi dengan toggle intip mata.
  - `status_aktif`: Toggle switch aktifkan akun langsung.
  - Tombol submit: `Daftarkan Pengguna` $\rightarrow$ kirim `POST /api/users`.

### Layar 10: Profil Pengguna (`fix_profil_pengguna/code.html`)
- **Header**: Judul `"Profil Saya"`.
- **Profile Identity Card**: Avatar foto bulat besar, indikator online hijau, nama, role pill, email, divisi, status sesi server.
- **Admin Stats Card**: Persetujuan selesai (`total_approval`) & status verifikasi.
- **Menu Navigasi**: Kelola Pengguna, Kelola Kategori, Ubah Kata Sandi (`POST /api/change-password`), Bahasa Aplikasi, Tentang Aplikasi.
- **Tombol Keluar**: `[Keluar dari Akun (Logout)]` $\rightarrow$ kirim `POST /api/logout`, hapus token lokal, lalu arahkan ke layar Login.

### Layar 11: Scan Barcode & Hub Aksi Cepat (`fix_scan_barcode_aksi/code.html`)
- **Header**: Tombol kembali $\leftarrow$, Judul `"Scan Barcode"`.
- **Mode Toggle Tabs**: `[Scan Aset Barang]` vs `[Scan Tiket Pinjam]`.
- **Viewfinder Kamera**: Reticle target kotak bercahaya neon laser, tombol toggle Senter (Flashlight), tombol upload dari Galeri.
- **Input Manual**: Text field untuk memasukkan kode manual (misal `BRG-NET-001` atau `TRX-58219`) + tombol Cari.
- **Hasil Deteksi Otomatis**: Menampilkan kartu barang/transaksi yang terdeteksi dari endpoint `GET /api/scan?code=...`.
- **Hub Aksi Cepat (4 Pilihan)**:
  1. *Ajukan Pinjam Cepat*: Membuka Form Pinjam dengan item yang langsung terpilih.
  2. *Cek & Laporkan Kondisi*: Memperbarui status barang (`Baik`, `Rusak Ringan`, `Rusak Berat`).
  3. *Update Stok & Audit Fisik*: Memperbarui kuantitas stok barang.
  4. *Cetak Ulang Label Barcode*: Menampilkan QR Code beresolusi tinggi dari `qr_code_url` untuk dicetak ke printer label.

---

## 6. Prompt Siap Tempel untuk Gemini di Android Studio

Salin teks berikut dan tempel langsung ke jendela chat **Gemini di Android Studio**:

```text
Halo Gemini! Saya ingin mengimplementasikan aplikasi Android native (Kotlin + Jetpack Compose) untuk "Sistem Informasi Inventaris Barang" berdasarkan backend RESTful API Laravel dan spesifikasi desain Modern Slate & Indigo.

Panduan arsitektur dan spesifikasi teknis lengkap terdapat pada berkas:
- Dokumentasi API Backend: API_DOCS.md
- Panduan Spesifikasi Android: ANDROID_STUDIO_SPEC.md
- Referensi Template Desain: /home/abu/Downloads/stitch_ui_layout_refinement/

Aturan Implementasi:
1. Gunakan Material 3 dengan warna tema Modern Slate & Indigo (Primary #3525CD, Container #4F46E5, Background #F8F9FF, Card Surface #FFFFFF).
2. Gunakan Retrofit + OkHttp dengan AuthInterceptor untuk Sanctum Bearer token. Base URL: "http://10.0.2.2:8000/api/".
3. Implementasikan 5 Tab Utama Navigasi Bawah:
   - Tab 1: Beranda (Dashboard stats, 8 modul cepat, antrean peminjaman dengan tombol Setujui/Tolak).
   - Tab 2: Barang (Katalog barang, filter chips kategori, pencarian, status ketersediaan, FAB Tambah Barang).
   - Tab 3 (Tombol Tengah FAB): Scan Barcode (CameraX barcode scanning & manual search).
   - Tab 4: Pinjam (Manajemen transaksi peminjaman, filter status Menunggu/Disetujui/Dikembalikan, tombol pengembalian & tiket QR).
   - Tab 5: Profil (Profil pengguna, statistik approval, menu kelola pengguna, ubah sandi, logout).
4. Buat ViewModel terpisah untuk setiap fitur dengan StateFlow untuk mengelola Loading, Success, dan Error state.
5. Pastikan semua field input form tervalidasi sesuai dengan validasi backend Laravel (role: "admin"/"user", kondisi: "Baik"/"Rusak Ringan"/"Rusak Berat").

Mari mulai dengan membuat Data Layer (Network API, DTO Models, dan Repository). Tolong buatkan kodenya sekarang!
```

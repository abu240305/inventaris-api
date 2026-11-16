# Dokumentasi RESTful API - Sistem Informasi Inventaris Barang

Backend RESTful API untuk **Sistem Informasi Inventaris Barang** yang dioptimalkan khusus untuk integrasi aplikasi mobile Android (sesuai UI 5 Tab: *Barang, Beranda, Pinjam, Kategori, Profil*).

---

## 1. Konfigurasi Dasar & Header

- **Base URL**: `http://localhost:8000/api` (Gunakan `http://10.0.2.2:8000/api` jika dijalankan dari Android Emulator)
- **Header Request Standar**:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `Authorization: Bearer <token_sanctum>` *(untuk endpoint terautentikasi)*

### Standar Format Response JSON
Seluruh response API mengembalikan struktur JSON yang konsisten:

#### Success Response
```json
{
  "status": true,
  "message": "Pesan deskriptif operasi berhasil",
  "data": {},
  "meta": {} // Opsional, berisi data ringkasan counter / statistik UI
}
```

#### Error Response (Validasi 422 / Not Found 404 / Auth 401 / Server 500)
```json
{
  "status": false,
  "message": "Pesan deskriptif kegagalan",
  "data": null
}
```

---

## 2. Kredensial Awal Hasil Seeding

Setelah menjalankan `php artisan migrate:fresh --seed`, tersedia data siap uji yang sesuai dengan mockup aplikasi Android:

| Role | Username | Password | Nama | Divisi |
|---|---|---|---|---|
| **Admin** | `admin` | `admin123` | Administrator | Divisi Sarana & Prasarana |
| **User** | `user` | `user123` | Anggota | Divisi Operasional |
| **User** | `ahmad` | `password123` | Ahmad Setiawan | Divisi Media & IT |
| **User** | `rian` | `password123` | Rian Dirgantara | Humas & Dokumentasi |
| **User** | `siti` | `password123` | Siti Indrawati | Fasilitator Workshop |
| **User** | `bambang` | `password123` | Bambang Pamungkas | Divisi Acara |

---

## 3. Modul Autentikasi & Akun (`/api/*`)

### 3.1 Register Pengguna Baru
- **Endpoint**: `POST /api/register`
- **Akses**: Publik
- **Body JSON**:
  ```json
  {
    "name": "Budi Santoso",
    "username": "budisantoso",
    "email": "budi@example.com",
    "password": "password123",
    "divisi": "Divisi Media & IT"
  }
  ```
- **Response Success (201 Created)**:
  ```json
  {
    "status": true,
    "message": "Registrasi berhasil",
    "data": {
      "id": 7,
      "name": "Budi Santoso",
      "username": "budisantoso",
      "email": "budi@example.com",
      "role": "user",
      "divisi": "Divisi Media & IT",
      "status_aktif": true,
      "created_at": "2026-11-17T01:10:00.000000Z"
    }
  }
  ```

---

### 3.2 Login Pengguna & Admin
- **Endpoint**: `POST /api/login`
- **Akses**: Publik
- **Body JSON**:
  ```json
  {
    "username": "admin",
    "password": "admin123"
  }
  ```
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Login berhasil",
    "data": {
      "user": {
        "id": 1,
        "name": "Administrator",
        "username": "admin",
        "email": "admin@example.com",
        "role": "admin",
        "divisi": "Divisi Sarana & Prasarana",
        "status_aktif": true,
        "avatar": "https://ui-avatars.com/api/?name=Administrator&background=4F46E5&color=fff"
      },
      "token": "1|qXy...sanctum_token...",
      "token_type": "Bearer"
    }
  }
  ```

---

### 3.3 Profil Pengguna (Tab Profil)
- **Endpoint**: `GET /api/profile`
- **Akses**: Bearer Token ATAU parameter query `?user_id=1`
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Data profil berhasil diambil",
    "data": {
      "id": 1,
      "name": "Administrator",
      "username": "admin",
      "email": "admin@example.com",
      "role": "admin",
      "divisi": "Divisi Sarana & Prasarana",
      "status_aktif": true,
      "avatar": "https://ui-avatars.com/api/?name=Administrator&background=4F46E5&color=fff",
      "total_approval": 2,
      "total_peminjaman": 0
    }
  }
  ```

---

### 3.4 Update Profil Mandiri (Tab Profil)
- **Endpoint**: `PUT /api/profile` atau `POST /api/profile` *(mendukung multipart/form-data untuk upload foto avatar)*
- **Akses**: Terautentikasi (`Authorization: Bearer <token>`) atau query `?user_id=1`
- **Body JSON / Form-Data**:
  ```json
  {
    "name": "Administrator",
    "email": "admin@example.com",
    "divisi": "Divisi Sarana & Prasarana",
    "avatar": "https://example.com/avatar.jpg" // atau upload file gambar 'avatar'
  }
  ```
- **Response Success (200 OK)**: Mengembalikan data profil terbaru.

---

### 3.5 Ubah Kata Sandi (Tab Profil)
- **Endpoint**: `POST /api/change-password`
- **Akses**: Terautentikasi (`Authorization: Bearer <token>`)
- **Body JSON**:
  ```json
  {
    "current_password": "admin123",
    "new_password": "adminbaru123"
  }
  ```
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Kata sandi berhasil diperbarui",
    "data": null
  }
  ```

---

### 3.6 Logout
- **Endpoint**: `POST /api/logout`
- **Akses**: Terautentikasi (`Authorization: Bearer <token>`)
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Logout berhasil",
    "data": null
  }
  ```

---

## 4. Modul Manajemen Pengguna / Anggota (`/api/users`)

Modul ini digunakan oleh Admin untuk mengelola seluruh data pengguna/anggota organisasi dari aplikasi Android.

### 4.1 Menampilkan Semua Pengguna
- **Endpoint**: `GET /api/users`
- **Parameter Query (Opsional)**:
  - `?search=Doni` : Mencari berdasarkan nama, username, email, atau divisi
  - `?role=user` : Filter berdasarkan role (`admin` atau `user`)
  - `?status_aktif=true` : Filter akun aktif / non-aktif
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Daftar pengguna berhasil diambil",
    "data": [
      {
        "id": 3,
        "name": "Ahmad Setiawan",
        "username": "ahmad",
        "email": "ahmad@example.com",
        "role": "user",
        "divisi": "Divisi Media & IT",
        "status_aktif": true,
        "avatar": "https://ui-avatars.com/api/?name=Ahmad+Setiawan&background=6366F1&color=fff",
        "peminjamans_count": 1
      }
    ],
    "meta": {
      "total_user": 6,
      "total_admin": 1,
      "total_anggota": 5
    }
  }
  ```

### 4.2 Admin Menambah Pengguna Baru
- **Endpoint**: `POST /api/users`
- **Body JSON**:
  ```json
  {
    "name": "Doni Wahyudi",
    "username": "doni",
    "email": "doni@example.com",
    "password": "password123",
    "role": "user", // opsi: 'admin' atau 'user'
    "divisi": "Divisi Multimedia & Event",
    "status_aktif": true
  }
  ```
- **Response Success (201 Created)**: HTTP 201

### 4.3 Detail Pengguna
- **Endpoint**: `GET /api/users/{id}`
- **Response Success (200 OK)**: Mengembalikan detail pengguna beserta riwayat peminjamannya.

### 4.4 Mengubah Data Pengguna
- **Endpoint**: `PUT /api/users/{id}`
- **Body JSON**:
  ```json
  {
    "name": "Doni Wahyudi S.Kom",
    "divisi": "Divisi Jaringan & Cloud",
    "role": "admin",
    "status_aktif": true,
    "password": "password_baru_opsional"
  }
  ```
- **Response Success (200 OK)**: HTTP 200

### 4.5 Menghapus Pengguna
- **Endpoint**: `DELETE /api/users/{id}`
- **Response Success (200 OK)**: HTTP 200

---

## 5. Modul Beranda & Utilitas Mobile (Tab Beranda)

Mengembalikan ringkasan statistik inventaris untuk 4 kartu indikator utama, daftar transaksi terbaru, pemindaian barcode, dan notifikasi lonceng.

### 5.1 Dashboard Statistik
- **Endpoint**: `GET /api/dashboard`
- **Akses**: Publik / Terautentikasi
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Data dashboard berhasil dimuat",
    "data": {
      "total_jenis_barang": 6,
      "total_stok_barang": 21,
      "total_kategori": 4,
      "peminjaman_menunggu": 1,
      "peminjaman_aktif": 2,
      "peminjaman_terbaru": [
        {
          "id": 2,
          "kode_peminjaman": "TRX-58220",
          "user_id": 4,
          "barang_id": 3,
          "jumlah": 1,
          "tanggal_pinjam": "2026-11-16",
          "tanggal_kembali": "2026-11-19",
          "status": "Menunggu",
          "sisa_hari": 2,
          "status_tempo": "Perlu Review",
          "keterangan": "Peminjaman kamera untuk dokumentasi seminar nasional",
          "user": {
            "id": 4,
            "name": "Rian Dirgantara",
            "username": "rian",
            "divisi": "Humas & Dokumentasi"
          },
          "barang": {
            "id": 3,
            "nama_barang": "Kamera Sony Alpha A6400 Kit 16-50mm",
            "kode_barang": "BRG-MM-001",
            "gambar": "https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=300&q=80",
            "status_ketersediaan": "Tersedia",
            "kategori": {
              "id": 2,
              "nama_kategori": "Multimedia & Dokumentasi"
            }
          }
        }
      ]
    }
  }
  ```

---

### 5.2 Pemindaian Cepat Barcode / QR Code (Tombol [Pindai] di Beranda)
- **Endpoint**: `GET /api/scan?code=BRG-NET-001` atau `POST /api/scan` dengan body `{"code": "..."}`
- **Fungsi**: Kamera Android men-scan barcode fisik barang atau struk transaksi, lalu server otomatis mendeteksi apakah itu data Barang atau Peminjaman.
- **Response Success Barang (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Data barang berhasil ditemukan",
    "data": {
      "type": "barang",
      "item": {
        "id": 1,
        "kode_barang": "BRG-NET-001",
        "nama_barang": "Router MikroTik RB951Ui-2HnD",
        "stok": 4,
        "kondisi": "Baik",
        "status_ketersediaan": "Tersedia",
        "kategori": { ... }
      }
    }
  }
  ```
- **Response Success Peminjaman (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Data transaksi peminjaman berhasil ditemukan",
    "data": {
      "type": "peminjaman",
      "item": {
        "id": 1,
        "kode_peminjaman": "TRX-58219",
        "status": "Disetujui",
        "user": { ... },
        "barang": { ... }
      }
    }
  }
  ```

---

### 5.3 Notifikasi Lonceng (Header Semua Layar)
- **Endpoint**: `GET /api/notifikasi`
- **Parameter Query (Opsional)**: `?user_id=1` (atau otomatis via Token login)
- **Fungsi**: Menampilkan notifikasi persetujuan baru untuk Admin dan pengingat jatuh tempo untuk User.
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Daftar notifikasi berhasil dimuat",
    "data": [
      {
        "id": "notif-menunggu-2",
        "tipe": "peminjaman_baru",
        "judul": "Pengajuan Peminjaman Baru",
        "pesan": "Rian Dirgantara mengajukan pinjam Kamera Sony Alpha A6400 Kit 16-50mm (1 unit)",
        "kode_transaksi": "TRX-58220",
        "peminjaman_id": 2,
        "status": "Menunggu",
        "waktu": "2 jam yang lalu",
        "is_read": false
      }
    ],
    "meta": {
      "total_notifikasi": 1,
      "unread_count": 1
    }
  }
  ```

---

## 6. Modul Kategori (Tab Kategori)

### 6.1 Menampilkan Semua Kategori
- **Endpoint**: `GET /api/kategori`
- **Parameter Query (Opsional)**:
  - `?search=Jaringan` : Pencarian kategori berdasarkan nama, kode (`KTG-001`), atau deskripsi.
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Daftar kategori berhasil diambil",
    "data": [
      {
        "id": 1,
        "kode_kategori": "KTG-001",
        "nama_kategori": "Perangkat Jaringan",
        "deskripsi": "Peralatan dan infrastruktur jaringan komputer serta telekomunikasi.",
        "barangs_count": 2
      },
      {
        "id": 2,
        "kode_kategori": "KTG-002",
        "nama_kategori": "Multimedia & Dokumentasi",
        "deskripsi": "Peralatan audio visual, kamera fotografi, dan perlengkapan video",
        "barangs_count": 2
      }
    ],
    "meta": {
      "total_kategori": 4,
      "total_aset_fisik": 21
    }
  }
  ```
  *(Nilai `meta.total_kategori` dan `meta.total_aset_fisik` langsung mengisi 2 kartu ringkasan atas di layar Kategori: "4 Terdaftar" & "6 Unit Terdata")*

### 6.2 Menambah Kategori Baru
- **Endpoint**: `POST /api/kategori`
- **Body JSON**:
  ```json
  {
    "kode_kategori": "KTG-005", // Opsional, auto-generate jika kosong
    "nama_kategori": "Peralatan Laboratorium",
    "deskripsi": "Peralatan penunjang praktikum lab komputer",
    "icon": "science" // Opsional: router, photo_camera, speaker, laptop_mac, science, dll.
  }
  ```
- **Response Success (201 Created)**:
  ```json
  {
    "status": true,
    "message": "Kategori berhasil ditambahkan",
    "data": {
      "id": 5,
      "kode_kategori": "KTG-005",
      "nama_kategori": "Peralatan Laboratorium",
      "deskripsi": "Peralatan penunjang praktikum lab komputer",
      "icon": "science",
      "barangs_count": 0
    }
  }
  ```

### 6.3 Detail Kategori
- **Endpoint**: `GET /api/kategori/{id}`

### 6.4 Mengubah Kategori
- **Endpoint**: `PUT /api/kategori/{id}`
- **Body JSON**:
  ```json
  {
    "kode_kategori": "KTG-001",
    "nama_kategori": "Perangkat Jaringan & Cloud",
    "deskripsi": "Peralatan jaringan dan server"
  }
  ```

### 6.5 Menghapus Kategori
- **Endpoint**: `DELETE /api/kategori/{id}`

---

## 7. Modul Barang Inventaris (Tab Barang)

### 7.1 Menampilkan Semua Barang
- **Endpoint**: `GET /api/barang`
- **Parameter Query (Opsional)**:
  - `?search=MikroTik` : Pencarian berdasarkan nama barang atau kode barang
  - `?kategori_id=1` : Filter berdasarkan ID kategori
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Daftar barang berhasil diambil",
    "data": [
      {
        "id": 1,
        "kategori_id": 1,
        "kode_barang": "BRG-NET-001",
        "nama_barang": "Router MikroTik RB951Ui-2HnD",
        "stok": 4,
        "kondisi": "Baik",
        "gambar": "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=300&q=80",
        "status_ketersediaan": "Tersedia",
        "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=BRG-NET-001",
        "deskripsi": "Router indoor dengan 5 port ethernet dan wireless internal",
        "kategori": {
          "id": 1,
          "kode_kategori": "KTG-001",
          "nama_kategori": "Perangkat Jaringan"
        }
      }
    ],
    "meta": {
      "total_inventaris_unit": 21,
      "total_jenis_barang": 6
    }
  }
  ```

### 7.2 Menambah Barang Baru
- **Endpoint**: `POST /api/barang`
- **Body JSON**:
  ```json
  {
    "kategori_id": 1,
    "kode_barang": "BRG-NET-003",
    "nama_barang": "Access Point TP-Link EAP225",
    "stok": 5,
    "kondisi": "Baik",
    "lokasi": "Ruang Server Lantai 2, Rak B3",
    "gambar": "https://example.com/eap225.jpg",
    "deskripsi": "AC1350 Wireless MU-MIMO Gigabit Ceiling Mount Access Point"
  }
  ```
- **Response Success (201 Created)**: HTTP 201

### 7.3 Detail Barang
- **Endpoint**: `GET /api/barang/{id}`

### 7.4 Mengubah Data Barang
- **Endpoint**: `PUT /api/barang/{id}`
- **Body JSON**:
  ```json
  {
    "kategori_id": 1,
    "kode_barang": "BRG-NET-001",
    "nama_barang": "Router MikroTik RB951Ui-2HnD",
    "stok": 5,
    "kondisi": "Baik",
    "gambar": "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=300&q=80",
    "deskripsi": "Update keterangan unit"
  }
  ```

### 7.5 Menghapus Data Barang
- **Endpoint**: `DELETE /api/barang/{id}`

---

## 8. Modul Peminjaman (Tab Pinjam)

### 8.1 Menampilkan Daftar Peminjaman & Counter Status
- **Endpoint**: `GET /api/peminjaman`
- **Parameter Query (Opsional)**:
  - `?search=MikroTik` : Pencarian cerdas berdasarkan kode peminjaman (`TRX-58219`), nama barang, kode barang, nama peminjam, atau divisi.
  - `?user_id=2` : Riwayat peminjaman user tertentu.
  - `?status=Menunggu` : Filter status (`Menunggu`, `Disetujui`, `Ditolak`, `Dikembalikan`).
- **Response Success (200 OK)**:
  ```json
  {
    "status": true,
    "message": "Daftar peminjaman berhasil diambil",
    "data": [
      {
        "id": 1,
        "kode_peminjaman": "TRX-58219",
        "user_id": 3,
        "barang_id": 1,
        "jumlah": 1,
        "tanggal_pinjam": "2026-11-14",
        "tanggal_kembali": "2026-11-20",
        "status": "Disetujui",
        "sisa_hari": 3,
        "status_tempo": "Sisa 3 hari",
        "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=TRX-58219",
        "keterangan": "Peminjaman router untuk persiapan praktikum jaringan",
        "user": {
          "id": 3,
          "name": "Ahmad Setiawan",
          "username": "ahmad",
          "divisi": "Divisi Media & IT",
          "avatar": "https://ui-avatars.com/api/?name=Ahmad+Setiawan&background=6366F1&color=fff"
        },
        "barang": {
          "id": 1,
          "kode_barang": "BRG-NET-001",
          "nama_barang": "Router MikroTik RB951Ui-2HnD",
          "gambar": "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=300&q=80",
          "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=BRG-NET-001",
          "kategori": {
            "id": 1,
            "nama_kategori": "Perangkat Jaringan"
          }
        }
      }
    ],
    "meta": {
      "total_semua": 4,
      "total_menunggu": 1,
      "total_disetujui": 2,
      "total_dikembalikan": 1,
      "total_ditolak": 0
    }
  }
  ```
  *(Objek `meta` langsung menyediakan angka untuk tab chip filter Android: `Semua (4)`, `Menunggu (1)`, `Disetujui (2)`, `Dikembalikan (1)`)*

---

### 8.2 Mengajukan Peminjaman Baru
- **Endpoint**: `POST /api/peminjaman`
- **Catatan Validasi & Otomatisasi**:
  - `kode_peminjaman` otomatis di-generate jika tidak diisi (misal: `TRX-83921`).
  - `user_id` otomatis diambil dari token login jika tidak disertakan di body.
  - Permintaan peminjaman otomatis ditolak (422) jika `jumlah` melebihi stok barang yang tersedia.
- **Body JSON**:
  ```json
  {
    "barang_id": 2,
    "jumlah": 1,
    "tanggal_pinjam": "2026-11-17",
    "tanggal_kembali": "2026-11-22",
    "keterangan": "Pinjam Switch Cisco untuk simulasi topologi jaringan"
  }
  ```
- **Response Success (201 Created)**: HTTP 201

---

### 8.3 Mengubah Status & Mutasi Stok Otomatis
- **Endpoint**: `PUT /api/peminjaman/{id}`
- **Logika Otomatisasi Stok**:
  1. **Disetujui**: Stok pada tabel `barangs` otomatis berkurang sesuai `jumlah`.
  2. **Dikembalikan**: Stok pada tabel `barangs` otomatis bertambah kembali dan `tanggal_kembali` terisi otomatis hari ini jika sebelumnya kosong.
  3. **Ditolak**: Jika status sebelumnya `Disetujui`, stok dikembalikan ke tabel `barangs`.
- **Body JSON (Tombol [Setujui] di Android)**:
  ```json
  {
    "status": "Disetujui"
  }
  ```
- **Body JSON (Tombol [Tolak] di Android)**:
  ```json
  {
    "status": "Ditolak"
  }
  ```
- **Body JSON (Tombol [Kembalikan Barang] di Android)**:
  ```json
  {
    "status": "Dikembalikan"
  }
  ```

---

### 8.4 Menghapus / Membatalkan Peminjaman
- **Endpoint**: `DELETE /api/peminjaman/{id}`
- **Catatan**: Jika data peminjaman yang dihapus berstatus `Disetujui`, stok barang otomatis dikembalikan ke semula sebelum baris dihapus.

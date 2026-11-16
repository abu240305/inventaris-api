<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with sample data matching the Android UI.
     */
    public function run(): void
    {
        // 1. Akun Pengguna (Admin & Anggota dengan Divisi)
        $admin = User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'divisi' => 'Divisi Sarana & Prasarana',
            'status_aktif' => true,
            'avatar' => 'https://ui-avatars.com/api/?name=Administrator&background=4F46E5&color=fff',
        ]);

        $userDefault = User::create([
            'name' => 'Anggota',
            'username' => 'user',
            'email' => 'user@example.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
            'divisi' => 'Divisi Operasional',
            'status_aktif' => true,
            'avatar' => 'https://ui-avatars.com/api/?name=Anggota&background=0D9488&color=fff',
        ]);

        $userAhmad = User::create([
            'name' => 'Ahmad Setiawan',
            'username' => 'ahmad',
            'email' => 'ahmad@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'divisi' => 'Divisi Media & IT',
            'status_aktif' => true,
            'avatar' => 'https://ui-avatars.com/api/?name=Ahmad+Setiawan&background=6366F1&color=fff',
        ]);

        $userRian = User::create([
            'name' => 'Rian Dirgantara',
            'username' => 'rian',
            'email' => 'rian@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'divisi' => 'Humas & Dokumentasi',
            'status_aktif' => true,
            'avatar' => 'https://ui-avatars.com/api/?name=Rian+Dirgantara&background=10B981&color=fff',
        ]);

        $userSiti = User::create([
            'name' => 'Siti Indrawati',
            'username' => 'siti',
            'email' => 'siti@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'divisi' => 'Fasilitator Workshop',
            'status_aktif' => true,
            'avatar' => 'https://ui-avatars.com/api/?name=Siti+Indrawati&background=EC4899&color=fff',
        ]);

        $userBambang = User::create([
            'name' => 'Bambang Pamungkas',
            'username' => 'bambang',
            'email' => 'bambang@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'divisi' => 'Divisi Acara',
            'status_aktif' => true,
            'avatar' => 'https://ui-avatars.com/api/?name=Bambang+Pamungkas&background=8B5CF6&color=fff',
        ]);

        // 2. Kategori Inventaris (dengan kode_kategori & icon)
        $katJaringan = Kategori::create([
            'kode_kategori' => 'KTG-001',
            'nama_kategori' => 'Perangkat Jaringan',
            'deskripsi' => 'Peralatan dan infrastruktur jaringan komputer serta telekomunikasi.',
            'icon' => 'router',
        ]);

        $katMultimedia = Kategori::create([
            'kode_kategori' => 'KTG-002',
            'nama_kategori' => 'Multimedia & Dokumentasi',
            'deskripsi' => 'Peralatan audio visual, kamera fotografi, dan perlengkapan video',
            'icon' => 'photo_camera',
        ]);

        $katAcara = Kategori::create([
            'kode_kategori' => 'KTG-003',
            'nama_kategori' => 'Perlengkapan Acara',
            'deskripsi' => 'Perlengkapan panggung, tata suara, dan logistik kegiatan',
            'icon' => 'speaker',
        ]);

        $katKomputer = Kategori::create([
            'kode_kategori' => 'KTG-004',
            'nama_kategori' => 'Perangkat Komputer',
            'deskripsi' => 'Perangkat komputer, laptop, proyektor, dan perlengkapan periferal',
            'icon' => 'laptop_mac',
        ]);

        // 3. Contoh Data Barang Inventaris (6 Barang lengkap dengan foto, stok & lokasi)
        $barang1 = Barang::create([
            'kategori_id' => $katJaringan->id,
            'kode_barang' => 'BRG-NET-001',
            'nama_barang' => 'Router MikroTik RB951Ui-2HnD',
            'stok' => 4, // 5 unit fisik, 1 sedang dipinjam
            'kondisi' => 'Baik',
            'gambar' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=300&q=80',
            'deskripsi' => 'Router indoor dengan 5 port ethernet dan wireless internal',
            'lokasi' => 'Ruang Server Gedung A • Rak 02',
        ]);

        $barang2 = Barang::create([
            'kategori_id' => $katJaringan->id,
            'kode_barang' => 'BRG-NET-002',
            'nama_barang' => 'Switch Cisco Catalyst 2960 24-Port',
            'stok' => 3,
            'kondisi' => 'Baik',
            'gambar' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=300&q=80',
            'deskripsi' => 'Managed switch 24 port 10/100 Mbps untuk praktikum jaringan',
            'lokasi' => 'Ruang Server Gedung B • Rak 01',
        ]);

        $barang3 = Barang::create([
            'kategori_id' => $katMultimedia->id,
            'kode_barang' => 'BRG-MM-001',
            'nama_barang' => 'Kamera Sony Alpha A6400 Kit 16-50mm',
            'stok' => 2,
            'kondisi' => 'Baik',
            'gambar' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=300&q=80',
            'deskripsi' => 'Kamera mirrorless untuk dokumentasi kegiatan',
            'lokasi' => 'Lemari Multimedia • Kompartemen M-1',
        ]);

        $barang4 = Barang::create([
            'kategori_id' => $katMultimedia->id,
            'kode_barang' => 'BRG-MM-002',
            'nama_barang' => 'Tripod Beike Q999H',
            'stok' => 4,
            'kondisi' => 'Baik',
            'gambar' => 'https://images.unsplash.com/photo-1512790182412-b19e6d62bc39?w=300&q=80',
            'deskripsi' => 'Tripod aluminium multifungsi dengan orientasi horizontal',
            'lokasi' => 'Lemari Multimedia • Kompartemen M-3',
        ]);

        $barang5 = Barang::create([
            'kategori_id' => $katAcara->id,
            'kode_barang' => 'BRG-EVT-001',
            'nama_barang' => 'Wireless Microphone Shure PGX24/SM58',
            'stok' => 6,
            'kondisi' => 'Baik',
            'gambar' => 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=300&q=80',
            'deskripsi' => 'Microphone wireless dual channel untuk seminar dan workshop',
            'lokasi' => 'Gudang Acara • Rak Audio 01',
        ]);

        $barang6 = Barang::create([
            'kategori_id' => $katKomputer->id,
            'kode_barang' => 'BRG-KOM-001',
            'nama_barang' => 'Proyektor Epson EB-X500 XGA 3600 Lumens',
            'stok' => 2,
            'kondisi' => 'Baik',
            'gambar' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=300&q=80',
            'deskripsi' => 'Proyektor presentasi portabel dengan koneksi HDMI dan VGA',
            'lokasi' => 'Gudang IT • Rak C-02',
        ]);

        // 4. Contoh Transaksi Peminjaman (Sesuai Mockup UI Android)
        Peminjaman::create([
            'kode_peminjaman' => 'TRX-58219',
            'user_id' => $userAhmad->id,
            'barang_id' => $barang1->id,
            'jumlah' => 1,
            'tanggal_pinjam' => '2026-11-14',
            'tanggal_kembali' => '2026-11-20',
            'status' => 'Disetujui',
            'keterangan' => 'Peminjaman router untuk persiapan praktikum jaringan',
        ]);

        Peminjaman::create([
            'kode_peminjaman' => 'TRX-58220',
            'user_id' => $userRian->id,
            'barang_id' => $barang3->id,
            'jumlah' => 1,
            'tanggal_pinjam' => '2026-11-16',
            'tanggal_kembali' => '2026-11-19',
            'status' => 'Menunggu',
            'keterangan' => 'Peminjaman kamera untuk dokumentasi seminar nasional',
        ]);

        Peminjaman::create([
            'kode_peminjaman' => 'TRX-58221',
            'user_id' => $userSiti->id,
            'barang_id' => $barang6->id,
            'jumlah' => 1,
            'tanggal_pinjam' => '2026-11-12',
            'tanggal_kembali' => '2026-11-18',
            'status' => 'Disetujui',
            'keterangan' => 'Peminjaman proyektor untuk fasilitas workshop internal',
        ]);

        Peminjaman::create([
            'kode_peminjaman' => 'TRX-58222',
            'user_id' => $userBambang->id,
            'barang_id' => $barang5->id,
            'jumlah' => 1,
            'tanggal_pinjam' => '2026-11-05',
            'tanggal_kembali' => '2026-11-10',
            'status' => 'Dikembalikan',
            'keterangan' => 'Peminjaman mic wireless untuk penutupan kegiatan',
        ]);
    }
}

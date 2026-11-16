<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventarisApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_successfully(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'username' => 'budisantoso',
            'email' => 'budi@example.com',
            'password' => 'secret123',
            'divisi' => 'Divisi Media & IT',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'Registrasi berhasil',
            ])
            ->assertJsonPath('data.username', 'budisantoso')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonPath('data.divisi', 'Divisi Media & IT');

        $this->assertDatabaseHas('users', [
            'username' => 'budisantoso',
            'role' => 'user',
        ]);
    }

    public function test_user_registration_validation_error(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'username' => '',
            'password' => '123', // min 6
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Validasi gagal',
            ])
            ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_login_successful_and_returns_token(): void
    {
        User::create([
            'name' => 'Admin Inventaris',
            'username' => 'admin_test',
            'email' => 'admintest@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'divisi' => 'Divisi Sarana & Prasarana',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'admin_test',
            'password' => 'admin123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Login berhasil',
            ])
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonPath('data.user.divisi', 'Divisi Sarana & Prasarana')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'unknown_user',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Username atau password salah',
                'data' => null,
            ]);
    }

    public function test_get_profile_via_token_and_user_id(): void
    {
        $user = User::create([
            'name' => 'Member User',
            'username' => 'member1',
            'email' => 'member1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'divisi' => 'Divisi Jaringan',
        ]);

        // Access with Sanctum token
        $token = $user->createToken('test_token')->plainTextToken;
        $responseToken = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profile');

        $responseToken->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Data profil berhasil diambil',
            ])
            ->assertJsonPath('data.username', 'member1')
            ->assertJsonPath('data.divisi', 'Divisi Jaringan');

        // Access with ?user_id= parameter
        $responseId = $this->getJson('/api/profile?user_id='.$user->id);
        $responseId->assertStatus(200)
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_change_password_successfully(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => Hash::make('oldpassword'),
            'role' => 'user',
        ]);

        $token = $user->createToken('pw_test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/change-password', [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Kata sandi berhasil diperbarui',
            ]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::create([
            'name' => 'Member User',
            'username' => 'member2',
            'email' => 'member2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $token = $user->createToken('logout_test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Logout berhasil',
            ]);
    }

    public function test_dashboard_statistics(): void
    {
        $kat = Kategori::create([
            'kode_kategori' => 'KTG-001',
            'nama_kategori' => 'Networking',
            'deskripsi' => 'Net',
        ]);

        $user = User::create([
            'name' => 'User One',
            'username' => 'user1',
            'password' => Hash::make('pass'),
            'role' => 'user',
        ]);

        $barang = Barang::create([
            'kategori_id' => $kat->id,
            'kode_barang' => 'TEST-001',
            'nama_barang' => 'Cisco Switch',
            'stok' => 10,
            'kondisi' => 'Baik',
        ]);

        Peminjaman::create([
            'kode_peminjaman' => 'TRX-11111',
            'user_id' => $user->id,
            'barang_id' => $barang->id,
            'jumlah' => 2,
            'tanggal_pinjam' => now()->toDateString(),
            'status' => 'Menunggu',
        ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'total_jenis_barang' => 1,
                    'total_stok_barang' => 10,
                    'total_kategori' => 1,
                    'peminjaman_menunggu' => 1,
                    'peminjaman_aktif' => 0,
                ],
            ])
            ->assertJsonCount(1, 'data.peminjaman_terbaru');
    }

    public function test_kategori_crud_operations(): void
    {
        // 1. Create
        $createResponse = $this->postJson('/api/kategori', [
            'kode_kategori' => 'KTG-SRV',
            'nama_kategori' => 'Perangkat Server',
            'deskripsi' => 'Unit rack server',
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.nama_kategori', 'Perangkat Server')
            ->assertJsonPath('data.kode_kategori', 'KTG-SRV');

        $kategoriId = $createResponse->json('data.id');

        // 2. Index with barangs_count and meta
        $indexResponse = $this->getJson('/api/kategori');
        $indexResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [['id', 'nama_kategori', 'kode_kategori', 'barangs_count']],
                'meta' => ['total_kategori', 'total_aset_fisik'],
            ]);

        // Search test
        $searchResponse = $this->getJson('/api/kategori?search=Server');
        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 3. Show
        $showResponse = $this->getJson('/api/kategori/'.$kategoriId);
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.nama_kategori', 'Perangkat Server');

        // 4. Update
        $updateResponse = $this->putJson('/api/kategori/'.$kategoriId, [
            'nama_kategori' => 'Perangkat Server & Cloud',
            'deskripsi' => 'Updated deskripsi',
        ]);
        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.nama_kategori', 'Perangkat Server & Cloud');

        // 5. Delete
        $deleteResponse = $this->deleteJson('/api/kategori/'.$kategoriId);
        $deleteResponse->assertStatus(200)
            ->assertJson(['status' => true, 'message' => 'Kategori berhasil dihapus']);

        $this->assertDatabaseMissing('kategoris', ['id' => $kategoriId]);
    }

    public function test_barang_crud_and_filters(): void
    {
        $kat = Kategori::create([
            'kode_kategori' => 'KTG-IT',
            'nama_kategori' => 'Perangkat IT',
            'deskripsi' => 'IT',
        ]);

        // 1. Create
        $createResponse = $this->postJson('/api/barang', [
            'kategori_id' => $kat->id,
            'kode_barang' => 'BRG-IT-001',
            'nama_barang' => 'Monitor Dell 24 Inch',
            'stok' => 8,
            'kondisi' => 'Baik',
            'deskripsi' => 'Monitor IPS',
            'gambar' => 'https://example.com/monitor.jpg',
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.nama_barang', 'Monitor Dell 24 Inch')
            ->assertJsonPath('data.status_ketersediaan', 'Tersedia')
            ->assertJsonPath('data.kategori.nama_kategori', 'Perangkat IT');

        $barangId = $createResponse->json('data.id');

        // 2. Index with search filter and meta
        $searchResponse = $this->getJson('/api/barang?search=Dell');
        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['meta' => ['total_inventaris_unit', 'total_jenis_barang']]);

        $searchFail = $this->getJson('/api/barang?search=NonExistent');
        $searchFail->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // 3. Update (with unique code excluding current ID)
        $updateResponse = $this->putJson('/api/barang/'.$barangId, [
            'kategori_id' => $kat->id,
            'kode_barang' => 'BRG-IT-001',
            'nama_barang' => 'Monitor Dell 27 Inch 4K',
            'stok' => 12,
            'kondisi' => 'Baik',
            'deskripsi' => 'Updated 4K monitor',
        ]);
        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.nama_barang', 'Monitor Dell 27 Inch 4K')
            ->assertJsonPath('data.stok', 12);

        // 4. Delete
        $deleteResponse = $this->deleteJson('/api/barang/'.$barangId);
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('barangs', ['id' => $barangId]);
    }

    public function test_peminjaman_stock_validation_and_lifecycle(): void
    {
        $user = User::create([
            'name' => 'Peminjam',
            'username' => 'peminjam',
            'password' => Hash::make('password'),
            'role' => 'user',
            'divisi' => 'Divisi Multimedia',
        ]);

        $kat = Kategori::create([
            'kode_kategori' => 'KTG-KBL',
            'nama_kategori' => 'Kabel',
            'deskripsi' => 'Kabel',
        ]);

        $barang = Barang::create([
            'kategori_id' => $kat->id,
            'kode_barang' => 'KBL-001',
            'nama_barang' => 'Kabel UTP Cat6 50m',
            'stok' => 5,
            'kondisi' => 'Baik',
        ]);

        // 1. Gagal jika jumlah > stok
        $overStockResponse = $this->postJson('/api/peminjaman', [
            'user_id' => $user->id,
            'barang_id' => $barang->id,
            'jumlah' => 10, // stok cuma 5
            'tanggal_pinjam' => now()->toDateString(),
        ]);

        $overStockResponse->assertStatus(422)
            ->assertJson(['status' => false]);

        // 2. Sukses membuat peminjaman status Menunggu
        $createResponse = $this->postJson('/api/peminjaman', [
            'user_id' => $user->id,
            'barang_id' => $barang->id,
            'jumlah' => 3,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(4)->toDateString(),
            'keterangan' => 'Pinjam untuk event',
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'Menunggu')
            ->assertJsonPath('data.status_tempo', 'Perlu Review');

        $peminjamanId = $createResponse->json('data.id');
        $this->assertNotEmpty($createResponse->json('data.kode_peminjaman'));

        // Pastikan stok belum berkurang saat status masih Menunggu
        $this->assertEquals(5, $barang->fresh()->stok);

        // Test index dengan meta counter status dan search
        $listResponse = $this->getJson('/api/peminjaman?search=Cat6');
        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['meta' => ['total_semua', 'total_menunggu', 'total_disetujui']]);

        // 3. Ubah status menjadi 'Disetujui' -> stok berkurang dari 5 menjadi 2
        $approveResponse = $this->putJson('/api/peminjaman/'.$peminjamanId, [
            'status' => 'Disetujui',
        ]);

        $approveResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'Disetujui');
        $this->assertEquals(2, $barang->fresh()->stok);

        // 4. Ubah status menjadi 'Dikembalikan' -> stok bertambah kembali dari 2 menjadi 5
        $returnResponse = $this->putJson('/api/peminjaman/'.$peminjamanId, [
            'status' => 'Dikembalikan',
        ]);

        $returnResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'Dikembalikan')
            ->assertJsonPath('data.status_tempo', 'Telah Dikembalikan');
        $this->assertEquals(5, $barang->fresh()->stok);
        $this->assertNotNull($returnResponse->json('data.tanggal_kembali'));

        // 5. Uji penghapusan peminjaman yang berstatus Disetujui mengembalikan stok
        $this->putJson('/api/peminjaman/'.$peminjamanId, ['status' => 'Disetujui']);
        $this->assertEquals(2, $barang->fresh()->stok);

        // Hapus peminjaman
        $deleteResponse = $this->deleteJson('/api/peminjaman/'.$peminjamanId);
        $deleteResponse->assertStatus(200);
        $this->assertEquals(5, $barang->fresh()->stok);
        $this->assertDatabaseMissing('peminjamans', ['id' => $peminjamanId]);
    }

    public function test_barang_and_peminjaman_contain_qr_code_url(): void
    {
        $kat = Kategori::create([
            'kode_kategori' => 'KTG-QR',
            'nama_kategori' => 'Kategori QR',
        ]);

        $barang = Barang::create([
            'kategori_id' => $kat->id,
            'kode_barang' => 'BRG-QR-001',
            'nama_barang' => 'Barang QR',
            'stok' => 5,
        ]);

        $this->assertNotEmpty($barang->qr_code_url);
        $this->assertStringContainsString('BRG-QR-001', $barang->qr_code_url);

        $user = User::create([
            'name' => 'User QR',
            'username' => 'userqr',
            'password' => Hash::make('password'),
        ]);

        $peminjaman = Peminjaman::create([
            'kode_peminjaman' => 'TRX-QR-001',
            'user_id' => $user->id,
            'barang_id' => $barang->id,
            'jumlah' => 1,
            'tanggal_pinjam' => now()->toDateString(),
        ]);

        $this->assertNotEmpty($peminjaman->qr_code_url);
        $this->assertStringContainsString('TRX-QR-001', $peminjaman->qr_code_url);
    }

    public function test_user_crud_operations(): void
    {
        // 1. Create user by admin
        $createResponse = $this->postJson('/api/users', [
            'name' => 'Doni Wahyudi',
            'username' => 'doni',
            'email' => 'doni@example.com',
            'password' => 'secret123',
            'role' => 'user',
            'divisi' => 'Divisi Multimedia',
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.username', 'doni')
            ->assertJsonPath('data.divisi', 'Divisi Multimedia');

        $userId = $createResponse->json('data.id');

        // 2. Index with search and meta
        $indexResponse = $this->getJson('/api/users?search=Doni');
        $indexResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['meta' => ['total_user', 'total_admin', 'total_anggota']]);

        // 3. Show
        $showResponse = $this->getJson('/api/users/'.$userId);
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Doni Wahyudi');

        // 4. Update
        $updateResponse = $this->putJson('/api/users/'.$userId, [
            'name' => 'Doni Wahyudi S.Kom',
            'divisi' => 'Divisi Jaringan & Cloud',
            'role' => 'admin',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Doni Wahyudi S.Kom')
            ->assertJsonPath('data.role', 'admin');

        // 5. Delete
        $deleteResponse = $this->deleteJson('/api/users/'.$userId);
        $deleteResponse->assertStatus(200)
            ->assertJson(['status' => true, 'message' => 'Pengguna berhasil dihapus']);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_scan_barcode_finds_barang_and_peminjaman(): void
    {
        $kat = Kategori::create([
            'kode_kategori' => 'KTG-SCAN',
            'nama_kategori' => 'Scan Category',
        ]);

        $barang = Barang::create([
            'kategori_id' => $kat->id,
            'kode_barang' => 'BRG-SCAN-001',
            'nama_barang' => 'Barang Scan',
            'stok' => 5,
        ]);

        $user = User::create([
            'name' => 'User Scan',
            'username' => 'userscan',
            'password' => Hash::make('password'),
        ]);

        $peminjaman = Peminjaman::create([
            'kode_peminjaman' => 'TRX-SCAN-001',
            'user_id' => $user->id,
            'barang_id' => $barang->id,
            'jumlah' => 1,
            'tanggal_pinjam' => now()->toDateString(),
        ]);

        // Scan barang
        $scanBarang = $this->getJson('/api/scan?code=BRG-SCAN-001');
        $scanBarang->assertStatus(200)
            ->assertJsonPath('data.type', 'barang')
            ->assertJsonPath('data.item.kode_barang', 'BRG-SCAN-001');

        // Scan peminjaman
        $scanPeminjaman = $this->postJson('/api/scan', ['code' => 'TRX-SCAN-001']);
        $scanPeminjaman->assertStatus(200)
            ->assertJsonPath('data.type', 'peminjaman')
            ->assertJsonPath('data.item.kode_peminjaman', 'TRX-SCAN-001');

        // Scan not found
        $scanNotFound = $this->getJson('/api/scan?code=INVALID-CODE');
        $scanNotFound->assertStatus(404)
            ->assertJson(['status' => false]);
    }

    public function test_update_profile_and_kondisi_filter(): void
    {
        $user = User::create([
            'name' => 'Original Name',
            'username' => 'origuser',
            'email' => 'orig@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'divisi' => 'Old Divisi',
        ]);

        $token = $user->createToken('prof_token')->plainTextToken;

        // Update profile
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/profile', [
                'name' => 'Updated Name',
                'divisi' => 'New Divisi',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.divisi', 'New Divisi');

        $this->assertEquals('Updated Name', $user->fresh()->name);

        // Test notifikasi
        $notifResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/notifikasi');

        $notifResponse->assertStatus(200)
            ->assertJsonStructure(['status', 'data', 'meta' => ['total_notifikasi']]);
    }

    public function test_json_error_handling_for_404_and_401(): void
    {
        // 404 Not Found route
        $response404 = $this->getJson('/api/non-existent-route');
        $response404->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Data atau rute tidak ditemukan.',
            ]);

        // 401 Unauthenticated on protected route
        $response401 = $this->postJson('/api/logout');
        $response401->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Unauthenticated atau token tidak valid.',
            ]);
    }
}

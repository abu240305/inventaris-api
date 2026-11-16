<?php

namespace App\Http\Controllers\Api;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    /**
     * Get dashboard summary and statistics for Android client.
     */
    public function index(): JsonResponse
    {
        $totalJenisBarang = Barang::count();
        $totalStokBarang = (int) Barang::sum('stok');
        $totalKategori = Kategori::count();
        $peminjamanMenunggu = Peminjaman::where('status', 'Menunggu')->count();
        $peminjamanAktif = Peminjaman::where('status', 'Disetujui')->count();
        $peminjamanTerbaru = Peminjaman::with(['user', 'barang.kategori'])
            ->latest()
            ->limit(5)
            ->get();

        return $this->successResponse([
            'total_jenis_barang' => $totalJenisBarang,
            'total_stok_barang' => $totalStokBarang,
            'total_kategori' => $totalKategori,
            'peminjaman_menunggu' => $peminjamanMenunggu,
            'peminjaman_aktif' => $peminjamanAktif,
            'peminjaman_terbaru' => $peminjamanTerbaru,
        ], 'Data dashboard berhasil dimuat');
    }
}

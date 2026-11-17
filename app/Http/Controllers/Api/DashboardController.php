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

        $stokBaik = (int) Barang::where('kondisi', 'Baik')->sum('stok');
        $stokRusakRingan = (int) Barang::whereIn('kondisi', ['Rusak Ringan', 'Perbaikan'])->sum('stok');
        $stokRusakBerat = (int) Barang::whereIn('kondisi', ['Rusak Berat', 'Rusak'])->sum('stok');

        $jenisBaik = Barang::where('kondisi', 'Baik')->count();
        $jenisRusakRingan = Barang::whereIn('kondisi', ['Rusak Ringan', 'Perbaikan'])->count();
        $jenisRusakBerat = Barang::whereIn('kondisi', ['Rusak Berat', 'Rusak'])->count();

        return $this->successResponse([
            'total_jenis_barang' => $totalJenisBarang,
            'total_stok_barang' => $totalStokBarang,
            'total_kategori' => $totalKategori,
            'peminjaman_menunggu' => $peminjamanMenunggu,
            'peminjaman_aktif' => $peminjamanAktif,
            'peminjaman_terbaru' => $peminjamanTerbaru,
            'stok_baik' => $stokBaik,
            'stok_rusak_ringan' => $stokRusakRingan,
            'stok_rusak_berat' => $stokRusakBerat,
            'jenis_baik' => $jenisBaik,
            'jenis_rusak_ringan' => $jenisRusakRingan,
            'jenis_rusak_berat' => $jenisRusakBerat,
        ], 'Data dashboard berhasil dimuat');
    }
}

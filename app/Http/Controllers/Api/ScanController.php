<?php

namespace App\Http\Controllers\Api;

use App\Models\Barang;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScanController extends BaseController
{
    /**
     * Scan and identify QR code / barcode from mobile camera.
     */
    public function scan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Parameter code wajib diisi', $validator->errors(), 422);
        }

        $code = trim((string) $request->input('code'));

        // 1. Cek apakah kode barang
        $barang = Barang::with('kategori')->where('kode_barang', $code)->first();
        if ($barang) {
            return $this->successResponse([
                'type' => 'barang',
                'item' => $barang,
            ], 'Data barang berhasil ditemukan');
        }

        // 2. Cek apakah kode transaksi peminjaman
        $peminjaman = Peminjaman::with(['user', 'barang.kategori'])->where('kode_peminjaman', $code)->first();
        if ($peminjaman) {
            return $this->successResponse([
                'type' => 'peminjaman',
                'item' => $peminjaman,
            ], 'Data transaksi peminjaman berhasil ditemukan');
        }

        return $this->errorResponse('Barcode / QR Code ['.$code.'] tidak ditemukan di sistem', null, 404);
    }
}

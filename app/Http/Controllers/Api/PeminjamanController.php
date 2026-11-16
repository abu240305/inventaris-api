<?php

namespace App\Http\Controllers\Api;

use App\Models\Barang;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PeminjamanController extends BaseController
{
    /**
     * Display a listing of the resource with optional filters, search, and status counters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Peminjaman::with(['user', 'barang.kategori']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('kode_peminjaman', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('divisi', 'like', "%{$search}%");
                    })
                    ->orWhereHas('barang', function ($bq) use ($search) {
                        $bq->where('nama_barang', 'like', "%{$search}%")
                            ->orWhere('kode_barang', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $peminjamans = $query->latest()->get();

        $meta = [
            'total_semua' => Peminjaman::count(),
            'total_menunggu' => Peminjaman::where('status', 'Menunggu')->count(),
            'total_disetujui' => Peminjaman::where('status', 'Disetujui')->count(),
            'total_dikembalikan' => Peminjaman::where('status', 'Dikembalikan')->count(),
            'total_ditolak' => Peminjaman::where('status', 'Ditolak')->count(),
        ];

        return $this->successResponse($peminjamans, 'Daftar peminjaman berhasil diambil', 200, $meta);
    }

    /**
     * Store a newly created peminjaman in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->input('user_id') ?? $request->user('sanctum')?->id ?? $request->user()?->id;

        $request->merge(['user_id' => $userId]);

        if (! $request->filled('kode_peminjaman')) {
            do {
                $generatedCode = 'TRX-'.rand(10000, 99999);
            } while (Peminjaman::where('kode_peminjaman', $generatedCode)->exists());

            $request->merge(['kode_peminjaman' => $generatedCode]);
        }

        $validator = Validator::make($request->all(), [
            'kode_peminjaman' => 'required|string|max:50|unique:peminjamans,kode_peminjaman',
            'user_id' => 'required|exists:users,id',
            'barang_id' => 'required|exists:barangs,id',
            'jumlah' => 'required|integer|min:1',
            'tanggal_pinjam' => 'required|date',
            'tanggal_kembali' => 'nullable|date|after_or_equal:tanggal_pinjam',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $barang = Barang::find($request->barang_id);

        if (! $barang) {
            return $this->errorResponse('Barang tidak ditemukan', null, 404);
        }

        if ($request->jumlah > $barang->stok) {
            return $this->errorResponse(
                'Jumlah peminjaman melebihi stok barang yang tersedia. Stok saat ini: '.$barang->stok,
                [
                    'stok_tersedia' => $barang->stok,
                    'jumlah_diminta' => (int) $request->jumlah,
                ],
                422
            );
        }

        $peminjaman = Peminjaman::create([
            'kode_peminjaman' => $request->kode_peminjaman,
            'user_id' => $request->user_id,
            'barang_id' => $request->barang_id,
            'jumlah' => $request->jumlah,
            'tanggal_pinjam' => $request->tanggal_pinjam,
            'tanggal_kembali' => $request->tanggal_kembali,
            'status' => 'Menunggu',
            'keterangan' => $request->keterangan,
        ]);

        $peminjaman->load(['user', 'barang.kategori']);

        return $this->successResponse($peminjaman, 'Pengajuan peminjaman berhasil dibuat', 201);
    }

    /**
     * Display the specified peminjaman.
     */
    public function show(int $id): JsonResponse
    {
        $peminjaman = Peminjaman::with(['user', 'barang.kategori'])->find($id);

        if (! $peminjaman) {
            return $this->errorResponse('Data peminjaman tidak ditemukan', null, 404);
        }

        return $this->successResponse($peminjaman, 'Detail peminjaman berhasil diambil');
    }

    /**
     * Update the specified peminjaman with automatic stock adjustment.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kode_peminjaman' => 'sometimes|required|string|max:50|unique:peminjamans,kode_peminjaman,'.$id,
            'user_id' => 'sometimes|required|exists:users,id',
            'barang_id' => 'sometimes|required|exists:barangs,id',
            'jumlah' => 'sometimes|required|integer|min:1',
            'tanggal_pinjam' => 'sometimes|required|date',
            'tanggal_kembali' => 'nullable|date',
            'status' => 'sometimes|required|in:Menunggu,Disetujui,Ditolak,Dikembalikan',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        return DB::transaction(function () use ($request, $id) {
            $peminjaman = Peminjaman::lockForUpdate()->find($id);

            if (! $peminjaman) {
                return $this->errorResponse('Data peminjaman tidak ditemukan', null, 404);
            }

            $oldStatus = $peminjaman->status;
            $newStatus = $request->input('status', $oldStatus);

            // Jika status berubah menjadi Disetujui (misal dari Menunggu / Ditolak / Dikembalikan)
            if ($oldStatus !== 'Disetujui' && $newStatus === 'Disetujui') {
                $barang = Barang::lockForUpdate()->find($peminjaman->barang_id);

                if (! $barang || $barang->stok < $peminjaman->jumlah) {
                    $stokTersedia = $barang ? $barang->stok : 0;

                    return $this->errorResponse(
                        'Stok barang tidak mencukupi untuk menyetujui peminjaman ini. Stok tersedia: '.$stokTersedia,
                        [
                            'stok_tersedia' => $stokTersedia,
                            'jumlah_diminta' => $peminjaman->jumlah,
                        ],
                        422
                    );
                }

                $barang->decrement('stok', $peminjaman->jumlah);
            }

            // Jika status sebelumnya Disetujui dan berubah menjadi status lain (Dikembalikan / Ditolak / Menunggu)
            if ($oldStatus === 'Disetujui' && $newStatus !== 'Disetujui') {
                $barang = Barang::lockForUpdate()->find($peminjaman->barang_id);
                if ($barang) {
                    $barang->increment('stok', $peminjaman->jumlah);
                }
            }

            $updateData = $request->only([
                'kode_peminjaman',
                'user_id',
                'barang_id',
                'jumlah',
                'tanggal_pinjam',
                'tanggal_kembali',
                'status',
                'keterangan',
            ]);

            // Jika status diubah menjadi Dikembalikan, isi tanggal_kembali otomatis jika kosong
            if ($newStatus === 'Dikembalikan' && empty($updateData['tanggal_kembali']) && empty($peminjaman->tanggal_kembali)) {
                $updateData['tanggal_kembali'] = now()->toDateString();
            }

            $peminjaman->update($updateData);
            $peminjaman->load(['user', 'barang.kategori']);

            return $this->successResponse($peminjaman, 'Data peminjaman berhasil diperbarui');
        });
    }

    /**
     * Remove the specified peminjaman and restore stock if previously approved.
     */
    public function destroy(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::lockForUpdate()->find($id);

            if (! $peminjaman) {
                return $this->errorResponse('Data peminjaman tidak ditemukan', null, 404);
            }

            // Jika status sebelumnya Disetujui, kembalikan stok barang terlebih dahulu
            if ($peminjaman->status === 'Disetujui') {
                $barang = Barang::lockForUpdate()->find($peminjaman->barang_id);
                if ($barang) {
                    $barang->increment('stok', $peminjaman->jumlah);
                }
            }

            $peminjaman->delete();

            return $this->successResponse(null, 'Data peminjaman berhasil dihapus');
        });
    }
}

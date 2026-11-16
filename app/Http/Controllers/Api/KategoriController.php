<?php

namespace App\Http\Controllers\Api;

use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriController extends BaseController
{
    /**
     * Display a listing of the resource with barangs count, search, and meta summary.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kategori::withCount('barangs');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_kategori', 'like', "%{$search}%")
                    ->orWhere('kode_kategori', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $kategoris = $query->latest()->get();

        $meta = [
            'total_kategori' => Kategori::count(),
            'total_aset_fisik' => (int) Barang::sum('stok'),
        ];

        return $this->successResponse($kategoris, 'Daftar kategori berhasil diambil', 200, $meta);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->filled('kode_kategori')) {
            $nextNumber = (Kategori::max('id') ?? 0) + 1;
            $request->merge([
                'kode_kategori' => 'KTG-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'kode_kategori' => 'required|string|max:50|unique:kategoris,kode_kategori',
            'nama_kategori' => 'required|string|max:100|unique:kategoris,nama_kategori',
            'deskripsi' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $kategori = Kategori::create([
            'kode_kategori' => $request->kode_kategori,
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'icon' => $request->icon,
        ]);

        $kategori->loadCount('barangs');

        return $this->successResponse($kategori, 'Kategori berhasil ditambahkan', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $kategori = Kategori::withCount('barangs')->find($id);

        if (! $kategori) {
            return $this->errorResponse('Kategori tidak ditemukan', null, 404);
        }

        return $this->successResponse($kategori, 'Detail kategori berhasil diambil');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $kategori = Kategori::find($id);

        if (! $kategori) {
            return $this->errorResponse('Kategori tidak ditemukan', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'kode_kategori' => 'sometimes|required|string|max:50|unique:kategoris,kode_kategori,'.$id,
            'nama_kategori' => 'required|string|max:100|unique:kategoris,nama_kategori,'.$id,
            'deskripsi' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $kategori->update($request->only(['kode_kategori', 'nama_kategori', 'deskripsi', 'icon']));
        $kategori->loadCount('barangs');

        return $this->successResponse($kategori, 'Kategori berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $kategori = Kategori::find($id);

        if (! $kategori) {
            return $this->errorResponse('Kategori tidak ditemukan', null, 404);
        }

        $kategori->delete();

        return $this->successResponse(null, 'Kategori berhasil dihapus');
    }
}

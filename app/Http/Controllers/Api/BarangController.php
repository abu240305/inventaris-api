<?php

namespace App\Http\Controllers\Api;

use App\Models\Barang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BarangController extends BaseController
{
    /**
     * Display a listing of the resource with optional search, filter, and meta summary.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Barang::with('kategori');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->query('kategori_id'));
        }

        if ($request->filled('kondisi')) {
            $query->where('kondisi', $request->query('kondisi'));
        }

        $barangs = $query->latest()->get();

        $meta = [
            'total_inventaris_unit' => (int) Barang::sum('stok'),
            'total_jenis_barang' => Barang::count(),
            'total_siap_pakai' => Barang::where('kondisi', 'Baik')->count(),
            'total_rusak' => Barang::whereIn('kondisi', ['Rusak Ringan', 'Rusak Berat'])->count(),
        ];

        return $this->successResponse($barangs, 'Daftar barang berhasil diambil', 200, $meta);
    }

    /**
     * Store a newly created resource in storage (supports URL or physical image upload).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kategori_id' => 'required|exists:kategoris,id',
            'kode_barang' => 'required|string|max:50|unique:barangs,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'stok' => 'required|integer|min:0',
            'kondisi' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat',
            'deskripsi' => 'nullable|string',
            'lokasi' => 'nullable|string|max:255',
            'gambar' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $gambarUrl = $request->input('gambar');

        if ($request->hasFile('gambar')) {
            $fileValidator = Validator::make($request->all(), [
                'gambar' => 'image|mimes:jpeg,png,jpg,webp|max:3072',
            ]);

            if ($fileValidator->fails()) {
                return $this->errorResponse('File gambar tidak valid', $fileValidator->errors(), 422);
            }

            $path = $request->file('gambar')->store('barangs', 'public');
            $gambarUrl = url('storage/'.$path);
        }

        $barang = Barang::create([
            'kategori_id' => $request->kategori_id,
            'kode_barang' => $request->kode_barang,
            'nama_barang' => $request->nama_barang,
            'stok' => $request->stok,
            'kondisi' => $request->input('kondisi', 'Baik'),
            'deskripsi' => $request->deskripsi,
            'lokasi' => $request->lokasi,
            'gambar' => $gambarUrl,
        ]);

        $barang->load('kategori');

        return $this->successResponse($barang, 'Data barang berhasil ditambahkan', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $barang = Barang::with('kategori')->find($id);

        if (! $barang) {
            return $this->errorResponse('Barang tidak ditemukan', null, 404);
        }

        return $this->successResponse($barang, 'Detail barang berhasil diambil');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $barang = Barang::find($id);

        if (! $barang) {
            return $this->errorResponse('Barang tidak ditemukan', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'kategori_id' => 'required|exists:kategoris,id',
            'kode_barang' => 'required|string|max:50|unique:barangs,kode_barang,'.$id,
            'nama_barang' => 'required|string|max:255',
            'stok' => 'required|integer|min:0',
            'kondisi' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'deskripsi' => 'nullable|string',
            'lokasi' => 'nullable|string|max:255',
            'gambar' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $gambarUrl = $barang->gambar;

        if ($request->hasFile('gambar')) {
            $fileValidator = Validator::make($request->all(), [
                'gambar' => 'image|mimes:jpeg,png,jpg,webp|max:3072',
            ]);

            if ($fileValidator->fails()) {
                return $this->errorResponse('File gambar tidak valid', $fileValidator->errors(), 422);
            }

            $path = $request->file('gambar')->store('barangs', 'public');
            $gambarUrl = url('storage/'.$path);
        } elseif ($request->has('gambar') && is_string($request->input('gambar'))) {
            $gambarUrl = $request->input('gambar');
        }

        $barang->update([
            'kategori_id' => $request->kategori_id,
            'kode_barang' => $request->kode_barang,
            'nama_barang' => $request->nama_barang,
            'stok' => $request->stok,
            'kondisi' => $request->kondisi,
            'deskripsi' => $request->deskripsi,
            'lokasi' => $request->lokasi,
            'gambar' => $gambarUrl,
        ]);

        $barang->load('kategori');

        return $this->successResponse($barang, 'Data barang berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $barang = Barang::find($id);

        if (! $barang) {
            return $this->errorResponse('Barang tidak ditemukan', null, 404);
        }

        $barang->delete();

        return $this->successResponse(null, 'Data barang berhasil dihapus');
    }
}

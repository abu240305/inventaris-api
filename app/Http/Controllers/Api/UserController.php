<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * Display a listing of users with search, role filters, and summary meta.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::withCount('peminjamans');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('divisi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('status_aktif')) {
            $status = filter_var($request->query('status_aktif'), FILTER_VALIDATE_BOOLEAN);
            $query->where('status_aktif', $status);
        }

        $users = $query->latest()->get();

        $meta = [
            'total_user' => User::count(),
            'total_admin' => User::where('role', 'admin')->count(),
            'total_anggota' => User::where('role', 'user')->count(),
        ];

        return $this->successResponse($users, 'Daftar pengguna berhasil diambil', 200, $meta);
    }

    /**
     * Store a newly created user (by Admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|in:admin,user',
            'divisi' => 'nullable|string|max:255',
            'status_aktif' => 'nullable|boolean',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $avatar = $request->input('avatar')
            ?? 'https://ui-avatars.com/api/?name='.urlencode($request->name).'&background=4F46E5&color=fff';

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->input('role', 'user'),
            'divisi' => $request->input('divisi', 'Anggota'),
            'status_aktif' => $request->boolean('status_aktif', true),
            'avatar' => $avatar,
        ]);

        return $this->successResponse($user, 'Pengguna berhasil ditambahkan', 201);
    }

    /**
     * Display the specified user detail with history.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::withCount('peminjamans')
            ->with(['peminjamans.barang'])
            ->find($id);

        if (! $user) {
            return $this->errorResponse('Pengguna tidak ditemukan', null, 404);
        }

        return $this->successResponse($user, 'Detail pengguna berhasil diambil');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse('Pengguna tidak ditemukan', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:50|unique:users,username,'.$id,
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|required|in:admin,user',
            'divisi' => 'nullable|string|max:255',
            'status_aktif' => 'nullable|boolean',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $updateData = $request->only([
            'name',
            'username',
            'email',
            'role',
            'divisi',
            'status_aktif',
            'avatar',
        ]);

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return $this->successResponse($user, 'Data pengguna berhasil diperbarui');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse('Pengguna tidak ditemukan', null, 404);
        }

        $user->delete();

        return $this->successResponse(null, 'Pengguna berhasil dihapus');
    }
}

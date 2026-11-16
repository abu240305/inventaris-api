<?php

namespace App\Http\Controllers\Api;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'divisi' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'divisi' => $request->divisi ?? 'Anggota',
            'status_aktif' => true,
        ]);

        return $this->successResponse($user, 'Registrasi berhasil', 201);
    }

    /**
     * Authenticate user and generate Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Username atau password salah', null, 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'divisi' => $user->divisi,
                'status_aktif' => $user->status_aktif,
                'avatar' => $user->avatar,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil');
    }

    /**
     * Get user profile by token or user_id parameter.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        if ($request->filled('user_id')) {
            $userFromId = User::find($request->user_id);
            if ($userFromId) {
                $user = $userFromId;
            } elseif (! $user) {
                return $this->errorResponse('User tidak ditemukan', null, 404);
            }
        }

        if (! $user) {
            return $this->errorResponse('User tidak ditemukan atau belum login', null, 401);
        }

        $userData = $user->toArray();
        $userData['total_approval'] = Peminjaman::where('status', 'Disetujui')->count();
        $userData['total_peminjaman'] = Peminjaman::where('user_id', $user->id)->count();

        return $this->successResponse($userData, 'Data profil berhasil diambil');
    }

    /**
     * Update current authenticated user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user && $request->filled('user_id')) {
            $user = User::find($request->user_id);
        }

        if (! $user) {
            return $this->errorResponse('Unauthenticated atau token tidak valid', null, 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$user->id,
            'divisi' => 'nullable|string|max:255',
            'avatar' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        $avatarUrl = $user->avatar;

        if ($request->hasFile('avatar')) {
            $fileValidator = Validator::make($request->all(), [
                'avatar' => 'image|mimes:jpeg,png,jpg,webp|max:3072',
            ]);

            if ($fileValidator->fails()) {
                return $this->errorResponse('File avatar tidak valid', $fileValidator->errors(), 422);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $avatarUrl = url('storage/'.$path);
        } elseif ($request->has('avatar') && is_string($request->input('avatar'))) {
            $avatarUrl = $request->input('avatar');
        }

        $updateData = $request->only(['name', 'email', 'divisi']);
        if ($avatarUrl) {
            $updateData['avatar'] = $avatarUrl;
        }

        $user->update($updateData);

        $userData = $user->fresh()->toArray();
        $userData['total_approval'] = Peminjaman::where('status', 'Disetujui')->count();
        $userData['total_peminjaman'] = Peminjaman::where('user_id', $user->id)->count();

        return $this->successResponse($userData, 'Profil berhasil diperbarui');
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated atau token tidak valid', null, 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|different:current_password',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal', $validator->errors(), 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Kata sandi saat ini tidak cocok', null, 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->successResponse(null, 'Kata sandi berhasil diperbarui');
    }

    /**
     * Logout and revoke active token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ($user) {
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            } else {
                $user->tokens()->delete();
            }

            return $this->successResponse(null, 'Logout berhasil');
        }

        return $this->errorResponse('Unauthenticated atau token tidak valid', null, 401);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Send a standardized success JSON response with optional meta.
     */
    public function successResponse(
        mixed $data = null,
        string $message = 'Operasi berhasil',
        int $code = 200,
        ?array $meta = null
    ): JsonResponse {
        $payload = [
            'status' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $code);
    }

    /**
     * Send a standardized error JSON response.
     */
    public function errorResponse(string $message = 'Terjadi kesalahan', mixed $data = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends BaseController
{
    /**
     * Get real-time notifications for Android bell icon.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user && $request->filled('user_id')) {
            $user = User::find($request->user_id);
        }

        $notifications = [];

        // 1. Notifikasi untuk Admin (Permintaan Peminjaman Menunggu Persetujuan)
        if (! $user || $user->role === 'admin') {
            $menunggu = Peminjaman::with(['user', 'barang'])
                ->where('status', 'Menunggu')
                ->latest()
                ->get();

            foreach ($menunggu as $item) {
                $peminjamName = $item->user ? $item->user->name : 'Anggota';
                $barangName = $item->barang ? $item->barang->nama_barang : 'Barang';

                $notifications[] = [
                    'id' => 'notif-menunggu-'.$item->id,
                    'tipe' => 'peminjaman_baru',
                    'judul' => 'Pengajuan Peminjaman Baru',
                    'pesan' => "{$peminjamName} mengajukan pinjam {$barangName} ({$item->jumlah} unit)",
                    'kode_transaksi' => $item->kode_peminjaman,
                    'peminjaman_id' => $item->id,
                    'status' => $item->status,
                    'waktu' => $item->created_at ? $item->created_at->diffForHumans() : 'Baru saja',
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : now()->toIso8601String(),
                    'is_read' => false,
                ];
            }
        }

        // 2. Notifikasi untuk Anggota (Status Persetujuan & Peringatan Jatuh Tempo)
        if ($user && $user->role === 'user') {
            $userPeminjamans = Peminjaman::with('barang')
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            foreach ($userPeminjamans as $item) {
                $barangName = $item->barang ? $item->barang->nama_barang : 'Barang';

                // Disetujui
                if ($item->status === 'Disetujui') {
                    $notifications[] = [
                        'id' => 'notif-disetujui-'.$item->id,
                        'tipe' => 'peminjaman_disetujui',
                        'judul' => 'Peminjaman Disetujui',
                        'pesan' => "Peminjaman {$barangName} telah disetujui admin. Silakan ambil di bagian inventaris.",
                        'kode_transaksi' => $item->kode_peminjaman,
                        'peminjaman_id' => $item->id,
                        'status' => $item->status,
                        'waktu' => $item->updated_at ? $item->updated_at->diffForHumans() : 'Baru saja',
                        'created_at' => $item->updated_at ? $item->updated_at->toIso8601String() : now()->toIso8601String(),
                        'is_read' => false,
                    ];

                    // Peringatan Jatuh Tempo
                    if ($item->sisa_hari !== null) {
                        if ($item->sisa_hari <= 2 && $item->sisa_hari >= 0) {
                            $notifications[] = [
                                'id' => 'notif-tempo-'.$item->id,
                                'tipe' => 'peringatan_tempo',
                                'judul' => 'Pengingat Pengembalian',
                                'pesan' => "Peminjaman {$barangName} jatuh tempo {$item->status_tempo}. Harap kembalikan tepat waktu.",
                                'kode_transaksi' => $item->kode_peminjaman,
                                'peminjaman_id' => $item->id,
                                'status' => $item->status,
                                'waktu' => 'Segera',
                                'created_at' => now()->toIso8601String(),
                                'is_read' => false,
                            ];
                        } elseif ($item->sisa_hari < 0) {
                            $notifications[] = [
                                'id' => 'notif-overdue-'.$item->id,
                                'tipe' => 'terlambat',
                                'judul' => 'Peminjaman Melewati Batas Waktu!',
                                'pesan' => "Peminjaman {$barangName} telah terlambat ".abs($item->sisa_hari).' hari. Segera kembalikan ke bagian inventaris!',
                                'kode_transaksi' => $item->kode_peminjaman,
                                'peminjaman_id' => $item->id,
                                'status' => $item->status,
                                'waktu' => 'Terlambat',
                                'created_at' => now()->toIso8601String(),
                                'is_read' => false,
                            ];
                        }
                    }
                }

                // Ditolak
                if ($item->status === 'Ditolak') {
                    $notifications[] = [
                        'id' => 'notif-ditolak-'.$item->id,
                        'tipe' => 'peminjaman_ditolak',
                        'judul' => 'Pengajuan Peminjaman Ditolak',
                        'pesan' => "Pengajuan pinjam {$barangName} ditolak oleh admin.",
                        'kode_transaksi' => $item->kode_peminjaman,
                        'peminjaman_id' => $item->id,
                        'status' => $item->status,
                        'waktu' => $item->updated_at ? $item->updated_at->diffForHumans() : 'Baru saja',
                        'created_at' => $item->updated_at ? $item->updated_at->toIso8601String() : now()->toIso8601String(),
                        'is_read' => false,
                    ];
                }
            }
        }

        $meta = [
            'total_notifikasi' => count($notifications),
            'unread_count' => count($notifications),
        ];

        return $this->successResponse($notifications, 'Daftar notifikasi berhasil dimuat', 200, $meta);
    }
}

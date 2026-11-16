<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Peminjaman extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'peminjamans';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kode_peminjaman',
        'user_id',
        'barang_id',
        'jumlah',
        'tanggal_pinjam',
        'tanggal_kembali',
        'status',
        'keterangan',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'sisa_hari',
        'status_tempo',
        'qr_code_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal_pinjam' => 'date:Y-m-d',
            'tanggal_kembali' => 'date:Y-m-d',
        ];
    }

    /**
     * Get remaining days until return date.
     */
    public function getSisaHariAttribute(): ?int
    {
        if (! $this->tanggal_kembali) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(Carbon::parse($this->tanggal_kembali)->startOfDay(), false);
    }

    /**
     * Get human readable deadline / tempo status.
     */
    public function getStatusTempoAttribute(): string
    {
        if ($this->status === 'Dikembalikan') {
            return 'Telah Dikembalikan';
        }

        if ($this->status === 'Ditolak') {
            return 'Ditolak';
        }

        if ($this->status === 'Menunggu') {
            return 'Perlu Review';
        }

        if (! $this->tanggal_kembali) {
            return 'Sedang Dipinjam';
        }

        $sisa = $this->sisa_hari;

        if ($sisa > 0) {
            return "Sisa {$sisa} hari";
        }

        if ($sisa === 0) {
            return 'Jatuh tempo hari ini';
        }

        return 'Terlambat '.abs($sisa).' hari';
    }

    /**
     * Get the high-resolution QR code URL for transaction receipt / verification.
     */
    public function getQrCodeUrlAttribute(): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=350x350&data='.urlencode($this->kode_peminjaman);
    }

    /**
     * Get the user who made the peminjaman.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the barang that was borrowed.
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}

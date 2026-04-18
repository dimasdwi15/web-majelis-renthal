<?php

namespace App\Models;

use App\Enums\MetodePembayaran;
use App\Enums\StatusTransaksi;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'user_id',
        'nomor_transaksi',
        'status',
        'metode_pembayaran',
        'status_pembayaran',
        'total_sewa',
        'total_denda',
        'total_charge',
        'tanggal_ambil',
        'tanggal_kembali',
        'tanggal_dikembalikan',
        'batas_pembayaran',
    ];

    protected $casts = [
        'status'              => StatusTransaksi::class,
        'metode_pembayaran'   => MetodePembayaran::class,
        'tanggal_ambil'       => 'date',
        'tanggal_kembali'     => 'date',
        'tanggal_dikembalikan'=> 'datetime',
        'batas_pembayaran'    => 'datetime',
        'total_sewa'          => 'decimal:2',
        'total_denda'         => 'decimal:2',
        'total_charge'        => 'decimal:2',
    ];

    // ── Relasi ──────────────────────────────────────────────────────────

    /**
     * Penyewa (user).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Detail item yang disewa.
     */
    public function details()
    {
        return $this->hasMany(TransaksiDetail::class, 'transaksi_id');
    }

    /**
     * Alias 'detail' → 'details' untuk backward compatibility.
     */
    public function detail()
    {
        return $this->details();
    }

    /**
     * Jaminan identitas penyewa.
     */
    public function jaminanIdentitas()
    {
        return $this->hasOne(JaminanIdentitas::class, 'transaksi_id');
    }

    /**
     * Alias 'jaminan' → 'jaminanIdentitas' untuk backward compatibility.
     */
    public function jaminan()
    {
        return $this->jaminanIdentitas();
    }

    /**
     * Riwayat pembayaran.
     */
    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'transaksi_id');
    }

    /**
     * Pembayaran terakhir (paling baru).
     */
    public function pembayaranTerakhir()
    {
        return $this->hasOne(Pembayaran::class, 'transaksi_id')->latestOfMany();
    }

    /**
     * Pembayaran utama (sewa).
     */
    public function pembayaranUtama()
    {
        return $this->hasOne(Pembayaran::class, 'transaksi_id')
                    ->where('jenis', 'utama')
                    ->latestOfMany();
    }

    /**
     * Denda yang dikenakan.
     */
    public function denda()
    {
        return $this->hasMany(Denda::class, 'transaksi_id');
    }

    // ── Computed Properties ─────────────────────────────────────────────

    /**
     * Cek apakah sudah melewati tanggal kembali.
     */
    public function getIsLewatBatasAttribute(): bool
    {
        return now()->gt($this->tanggal_kembali);
    }

    /**
     * Hitung hari keterlambatan.
     */
    public function getHariTelatAttribute(): int
    {
        $batasKembali = Carbon::parse($this->tanggal_kembali)->startOfDay();

        if ($this->tanggal_dikembalikan) {
            // Sudah dikembalikan — hitung dari tanggal aktual dikembalikan
            $aktualKembali = Carbon::parse($this->tanggal_dikembalikan)->startOfDay();
        } else {
            // Belum dikembalikan — hitung dari hari ini
            $aktualKembali = now()->startOfDay();
        }

        $selisih = $aktualKembali->diffInDays($batasKembali, false);

        // diffInDays dengan false: negatif = aktualKembali SETELAH batasKembali = terlambat
        // Kita ingin nilai positif untuk "hari terlambat"
        return $selisih < 0 ? (int) abs($selisih) : 0;
    }

    /**
     * Hitung denda keterlambatan otomatis.
     * Rumus: 50% × harga_per_hari × jumlah × hari_telat
     */
    public function getHitungDendaTelatAttribute(): float
    {
        if ($this->hari_telat > 0) {
            return round($this->total_sewa * 0.5, 2);
        }

        return 0.0;
    }

    /**
     * Total tagihan akhir (denda telat + denda kerusakan).
     */
    public function getTotalTagihanDendaAttribute(): float
    {
        return $this->hitung_denda_telat + (float) ($this->total_denda ?? 0);
    }

    /**
     * Grand total (sewa + semua denda).
     */
    public function getTotalKeseluruhanAttribute(): float
    {
        return $this->total_sewa + $this->total_denda;
    }

    /**
     * Cek apakah semua denda sudah lunas.
     */
    public function getIsDendaLunasAttribute(): bool
    {
        $dendaBelumBayar = $this->denda()->whereNull('dibayar_pada')->count();
        return $dendaBelumBayar === 0;
    }

    /**
     * Cek apakah pembayaran utama lunas.
     */
    public function getIsPembayaranLunasAttribute(): bool
    {
        return $this->pembayaranUtama?->status === 'lunas';
    }

    /**
     * Cek apakah COD sudah melewati batas waktu.
     */
    public function getIsCodExpiredAttribute(): bool
    {
        if (!$this->metode_pembayaran?->isCod()) {
            return false;
        }

        if (!$this->batas_pembayaran) {
            return false;
        }

        return now()->gt($this->batas_pembayaran)
            && $this->status === StatusTransaksi::MenungguPembayaran;
    }

    /**
     * Sisa waktu batas pembayaran COD (dalam jam).
     */
    public function getSisaWaktuCodAttribute(): ?string
    {
        if (!$this->batas_pembayaran || !$this->metode_pembayaran?->isCod()) {
            return null;
        }

        if (now()->gt($this->batas_pembayaran)) {
            return 'Expired';
        }

        return now()->diffForHumans($this->batas_pembayaran, ['parts' => 2]);
    }
}

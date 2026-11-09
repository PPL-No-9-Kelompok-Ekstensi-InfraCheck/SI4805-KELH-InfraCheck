<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'id_laporan';

    /**
     * Tahapan status pemantauan laporan, berurutan dari pengajuan hingga selesai.
     */
    public const TAHAPAN_STATUS = [
        'Menunggu Verifikasi',
        'Diverifikasi',
        'Ditugaskan',
        'Sedang Dikerjakan',
        'Menunggu Validasi',
        'Selesai',
    ];

    /**
     * Persentase progres laporan berdasarkan statusnya (tanpa data progres teknisi).
     */
    public function getPersentaseStatusAttribute(): int
    {
        $indeks = array_search($this->status?->nama_status, self::TAHAPAN_STATUS, true);

        if ($indeks === false) {
            return 0;
        }

        return [10, 30, 50, 70, 90, 100][$indeks];
    }

    /**
     * Persentase progres laporan, memakai progres teknisi terakhir bila tersedia.
     */
    public function getPersentaseProgresAttribute(): int
    {
        $statusPenutup = in_array($this->status?->nama_status, ['Ditolak', 'Dibatalkan'], true);

        if ($statusPenutup) {
            return 0;
        }

        $penugasan = $this->assignments->sortByDesc('waktu_penugasan')->first();
        $progresTerakhir = $penugasan?->progressUpdates->first();

        if ($progresTerakhir !== null) {
            return max(0, min(100, (int) $progresTerakhir->persentase));
        }

        return $this->persentase_status;
    }

    protected $fillable = [
        'id_pelapor',
        'id_perangkat',
        'id_ruangan',
        'id_status',
        'id_urgensi',
        'id_prioritas',
        'id_admin_verifikator',
        'deskripsi_kerusakan',
        'waktu_laporan',
        'waktu_verifikasi',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'waktu_laporan' => 'datetime',
            'waktu_verifikasi' => 'datetime',
        ];
    }

    /**
     * Nomor laporan untuk keperluan tampilan, mis. LP-2026-0012 (tidak tersimpan di DB).
     */
    public function getNomorLaporanAttribute(): string
    {
        $tahun = $this->waktu_laporan?->format('Y') ?? now()->format('Y');

        return sprintf('LP-%s-%04d', $tahun, $this->id_laporan);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'id_pelapor', 'id_pengguna');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'id_admin_verifikator', 'id_pengguna');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'id_perangkat', 'id_perangkat');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'id_ruangan', 'id_ruangan');
    }

    public function status()
    {
        return $this->belongsTo(ReportStatus::class, 'id_status', 'id_status');
    }

    public function urgency()
    {
        return $this->belongsTo(UrgencyLevel::class, 'id_urgensi', 'id_urgensi');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'id_prioritas', 'id_prioritas');
    }

    public function photos()
    {
        return $this->hasMany(ReportPhoto::class, 'id_laporan', 'id_laporan');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'id_laporan', 'id_laporan');
    }

    public function activeAssignment()
    {
        return $this->hasOne(Assignment::class, 'id_laporan', 'id_laporan')->where('status_penugasan', 'aktif');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'id_laporan', 'id_laporan');
    }

    public function statusHistories()
    {
        return $this->hasMany(StatusHistory::class, 'id_laporan', 'id_laporan');
    }
}

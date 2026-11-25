<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairDocument extends Model
{
    /**
     * Jenis dokumentasi (sesuai proposal 4.3.5) beserta nama field formulirnya.
     *
     * @var array<string, string>
     */
    public const JENIS = [
        'Sebelum' => 'foto_sebelum',
        'Selama' => 'foto_selama',
        'Sesudah' => 'foto_sesudah',
    ];

    /** Disk penyimpanan berkas dokumentasi (storage/app/public). */
    public const DISK = 'public';

    protected $table = 'repair_documents';
    protected $primaryKey = 'id_dokumentasi';

    protected $fillable = [
        'id_progres',
        'lokasi_berkas',
        'jenis_dokumentasi',
        'waktu_unggah',
    ];

    protected function casts(): array
    {
        return [
            'waktu_unggah' => 'datetime',
        ];
    }

    public function progress()
    {
        return $this->belongsTo(RepairProgress::class, 'id_progres', 'id_progres');
    }

    /**
     * URL publik untuk menampilkan foto dokumentasi.
     */
    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->lokasi_berkas, 'http://') || str_starts_with($this->lokasi_berkas, 'https://')) {
            return $this->lokasi_berkas;
        }

        if (str_starts_with($this->lokasi_berkas, 'storage/')) {
            return asset($this->lokasi_berkas);
        }

        if (str_starts_with($this->lokasi_berkas, 'public/')) {
            return asset(substr($this->lokasi_berkas, 7));
        }

        return asset('storage/' . ltrim($this->lokasi_berkas, '/'));
    }
}

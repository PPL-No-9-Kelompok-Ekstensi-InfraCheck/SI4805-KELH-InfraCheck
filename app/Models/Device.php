<?php

namespace App\Models;

use App\Enums\Kondisi;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';
    protected $primaryKey = 'id_perangkat';

    protected $fillable = [
        'id_jenis',
        'id_ruangan',
        'kode_aset',
        'nama_perangkat',
        'kondisi',
    ];

    protected function casts(): array
    {
        return [
            'kondisi' => Kondisi::class,
        ];
    }

    /**
     * Label jenis perangkat untuk ditampilkan di daftar.
     */
    public function getJenisLabelAttribute(): string
    {
        return $this->deviceType?->nama_jenis ?? '-';
    }

    /**
     * Label kategori perangkat (opsional).
     */
    public function getKategoriLabelAttribute(): ?string
    {
        return $this->deviceType?->category?->nama_kategori;
    }

    /**
     * Label ruangan tempat perangkat berada.
     */
    public function getLokasiLabelAttribute(): string
    {
        return $this->room?->nama_ruangan ?? '-';
    }

    /**
     * Label gedung tempat perangkat berada (opsional).
     */
    public function getGedungLabelAttribute(): ?string
    {
        return $this->room?->building?->nama_gedung;
    }

    public function deviceType()
    {
        return $this->belongsTo(DeviceType::class, 'id_jenis', 'id_jenis');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'id_ruangan', 'id_ruangan');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'id_perangkat', 'id_perangkat');
    }
}

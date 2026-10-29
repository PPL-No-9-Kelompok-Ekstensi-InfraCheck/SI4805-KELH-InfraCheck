<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairDocument extends Model
{
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
}

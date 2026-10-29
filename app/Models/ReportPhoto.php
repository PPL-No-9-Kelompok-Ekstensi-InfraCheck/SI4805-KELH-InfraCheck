<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportPhoto extends Model
{
    protected $table = 'report_photos';
    protected $primaryKey = 'id_foto';

    protected $fillable = [
        'id_laporan',
        'lokasi_berkas',
        'waktu_unggah',
    ];

    protected function casts(): array
    {
        return [
            'waktu_unggah' => 'datetime',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Report::class, 'id_laporan', 'id_laporan');
    }
}

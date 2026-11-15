<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusHistory extends Model
{
    protected $table = 'status_histories';

    protected $primaryKey = 'id_riwayat';

    protected $fillable = [
        'id_laporan',
        'id_status',
        'id_pengubah',
        'waktu_perubahan',
        'catatan_perubahan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_perubahan' => 'datetime',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Report::class, 'id_laporan', 'id_laporan');
    }

    public function status()
    {
        return $this->belongsTo(ReportStatus::class, 'id_status', 'id_status');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'id_pengubah', 'id_pengguna');
    }
}

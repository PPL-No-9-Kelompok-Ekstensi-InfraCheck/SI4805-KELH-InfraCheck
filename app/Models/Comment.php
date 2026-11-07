<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comments';

    protected $primaryKey = 'id_tanggapan';

    protected $fillable = [
        'id_laporan',
        'id_pengguna',
        'isi_tanggapan',
        'waktu_tanggapan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_tanggapan' => 'datetime',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Report::class, 'id_laporan', 'id_laporan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id_pengguna');
    }
}

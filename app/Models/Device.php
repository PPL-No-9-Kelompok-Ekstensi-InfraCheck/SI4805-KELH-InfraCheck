<?php

namespace App\Models;

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

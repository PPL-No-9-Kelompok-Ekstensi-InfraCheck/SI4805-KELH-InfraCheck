<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';
    protected $primaryKey = 'id_ruangan';

    protected $fillable = [
        'id_gedung',
        'kode_ruangan',
        'nama_ruangan',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class, 'id_gedung', 'id_gedung');
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'id_ruangan', 'id_ruangan');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'id_ruangan', 'id_ruangan');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $table = 'buildings';
    protected $primaryKey = 'id_gedung';

    protected $fillable = [
        'kode_gedung',
        'nama_gedung',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'id_gedung', 'id_gedung');
    }
}

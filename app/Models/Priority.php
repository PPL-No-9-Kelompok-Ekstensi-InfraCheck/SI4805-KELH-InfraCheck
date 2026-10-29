<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    protected $table = 'priorities';
    protected $primaryKey = 'id_prioritas';

    protected $fillable = [
        'nama_prioritas',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class, 'id_prioritas', 'id_prioritas');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrgencyLevel extends Model
{
    protected $table = 'urgency_levels';
    protected $primaryKey = 'id_urgensi';

    protected $fillable = [
        'nama_urgensi',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class, 'id_urgensi', 'id_urgensi');
    }
}
